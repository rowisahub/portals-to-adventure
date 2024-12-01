<?php
/*
File: src/plugin/API/REST.php
Description: REST API for Portals to Adventure.
*/

namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Require Class */
use PTA\logger\Log;
use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\functions\user\user_functions;
use PTA\DB\functions\submission\submission_functions;
use PTA\DB\functions\image\image_functions;
use PTA\admin\admin_functions;

/**
 * Class API
 *
 * This class is the REST API for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class REST
{
  private $logger;
  private db_handler $handler_instance;
  private db_functions $db_functions;
  private user_functions $user_func;
  private submission_functions $submission_func;
  private image_functions $image_func;
  private admin_functions $admin_func;

  public function __construct()
  {
    /* Logger */
    $this->logger = new Log(name: 'API');

    /* Initialize */
    //$this->init();
  }

  /**
   * Initializes the REST API.
   *
   * This method initializes the REST API by calling the necessary methods.
   */
  public function init(
    submission_functions $sub_functions = null,
    image_functions $img_functions = null,
    user_functions $user_functions = null,
    db_handler $handler_instance = null,
    db_functions $db_functions = null,
    admin_functions $admin_functions = null
  )
  {
    $this->logger = $this->logger->getLogger();

    // Get the handler instance and db functions instance
    $this->handler_instance = $handler_instance ?? new db_handler();
    $this->db_functions = $db_functions ?? new db_functions();

    // if handler_instance is null or db_functions is null, set them
    if ($handler_instance == null || $db_functions == null) {

      // Set the functions instance in the handler, and initialize the functions
      $this->handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
      $this->db_functions->init(handler_instance: $this->handler_instance);

    }

    // Set the functions instances for the submission, image, and user functions
    $this->submission_func = $sub_functions ?? new submission_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    $this->image_func = $img_functions ?? new image_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    $this->user_func = $user_functions ?? new user_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);

    // Set the functions instances for the admin functions
    $this->admin_func = $admin_functions ?? new admin_functions();

    $this->admin_func->init(
      sub_functions: $this->submission_func,
      img_functions: $this->image_func,
      user_functions: $this->user_func,
      handler_instance: $this->handler_instance,
      db_functions: $this->db_functions
    );

    add_action('rest_api_init', array($this, 'register_routes'));
  }

  /**
   * Registers the REST API routes.
   *
   * This method registers the REST API routes for the plugin.
   */
  public function register_routes()
  {
    register_rest_route('pta/v1', '/submissions', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_submissions'),
      'permission_callback' => array($this, 'pta_api_check_permissions'),
    ));
    register_rest_route('pta/v1', '/submission-action', array(
      'methods' => 'POST',
      'callback' => array($this, 'submission_action'),
      'permission_callback' => array($this, 'pta_api_check_permissions'),
    ));
  }

  public function submission_action($request)
  {

    $params = $request->get_params();

    $action = $params['action'];
    $submission_id = $params['id'];
    $reason = $params['reason'];
    $user_id = get_current_user_id();

    // log API request
    $this->logger->info('API request: ' . json_encode($params));

    // check if the submission exists
    if (!$this->db_functions->check_id_exists("submission_data", $submission_id)) {
      return new \WP_Error('submission_not_found', 'Submission not found', array('status' => 404));
    }

    // check if the user is the owner of the submission, or if the user is an admin
    $submission = $this->submission_func->get_submission($submission_id);
    if ($submission['user_owner_id'] != $user_id && !current_user_can('administrator')) {
      return new \WP_Error('forbidden', 'You do not have permission to modify this submission', array('status' => 403));
    }

    switch ($action) {
      case 'approve':
        $this->logger->debug('Approve submission');

        if (!current_user_can('administrator')) {
          error_log('User is not admin');
          return new \WP_Error('forbidden', 'You do not have permission to use action', array('status' => 403));
        }

        $this->admin_func->approve_submission($submission_id);

        break;
      case 'reject':
        $this->logger->debug('Reject submission');

        if (!current_user_can('administrator')) {
          error_log('User is not admin');
          return new \WP_Error('forbidden', 'You do not have permission to use action', array('status' => 403));
        }

        $this->admin_func->reject_submission($submission_id, $reason);

        break;
      case 'delete':
        $this->logger->debug('Delete submission');
        $this->admin_func->delete_submission($submission_id, $reason);

        break;
      case 'unreject':
        $this->logger->debug('Unreject submission');

        if (!current_user_can('administrator')) {
          error_log('User is not admin');
          return new \WP_Error('forbidden', 'You do not have permission to use action', array('status' => 403));
        }

        $this->admin_func->unreject_submission($submission_id);

        break;
      default:
        return new \WP_Error('invalid_action', 'Invalid action', array('status' => 400));
    }

    //return rest_ensure_response("worked: " . $action);
    // return with status 200, 'acton_success' is a custom status code, and 'This action was successful' is the message
    return new \WP_REST_Response(array('message' => 'This action was successful', 'code' => 'success_action', 'data' => array('action' => $action, 'reason' => $reason)), 200);
  }

  public function get_submissions($request)
  {

    $params = $request->get_params();
    $user_id = get_current_user_id();
    $submissions = array();

    // log API request
    $this->logger->info('API request: ' . json_encode($params));

    // Include your database functions if not already included
    // Adjust the path as necessary
    // require_once plugin_dir_path( __FILE__ ) . 'path/to/your-database-functions.php';

    // Fetch submissions by ID if 'id' parameter is provided
    if (isset($params['id'])) {
      $submission_id = $params['id'];

      // Check if the submission exists
      if (!$this->db_functions->check_id_exists("submission_data", $submission_id)) {
        return new \WP_Error('submission_not_found', 'Submission not found', array('status' => 404));
      }

      // Get the submission
      $submission = $this->submission_func->get_submission($submission_id);

      // $submission['user_owner_id] != $user_id
      // Check permissions
      if ($submission['state'] != 'Approved' && $submission['user_owner_id'] != $user_id && !current_user_can('administrator')) {
        return new \WP_Error('forbidden', 'You do not have permission to view this submission', array('status' => 403));
      }
      // if 

      // if submission is Approved, increment views
      if ($submission['state'] == 'Approved') {
        $this->process_user_view($submission_id);
      }

      $submissions[] = $submission;

    } else if (isset($params['user_id'])) {

      $user_id = $params['user_id'];

      if ($user_id == 0) {
        return new \WP_Error('invalid_user_id', 'Invalid user ID', array('status' => 400));
      }

      $submissions = $this->submission_func->get_submissions_by_user($user_id);

    } else if (isset($params['requested'])) {
      // Get all submisssions exept the ones that are removed

      if (!current_user_can('administrator')) {
        return new \WP_Error('forbidden', 'You do not have permission to view this submission', array('status' => 403));
      }

      $submissions = $this->submission_func->get_all_submissions();


    } else if (isset($params['state'])) {
      $state = $params['state'];

      if ($state == 'Approved') {
        $submissions = $this->submission_func->get_all_submissions_by_state($state);
      } else {
        return new \WP_Error('invalid_state', 'Invalid state', array('status' => 400));
      }
    } else {
      // Fetch all 'Approved' submissions
      $public_submissions = $this->submission_func->get_all_submissions_by_state('Approved');

      // Fetch 'In Progress' submissions for the current user
      if ($user_id) {
        $user_submissions = $this->submission_func->get_submissions_by_user($user_id);
      } else {
        $user_submissions = array();
      }

      // Combine and remove duplicates
      $all_submissions = array_merge($public_submissions, $user_submissions);
      $submissions = $this->remove_duplicate_submissions($all_submissions);
    }

    // Add full image data if requested
    // if (isset($params['full_image_data'])) {
    //   foreach ($submissions as $key => $submission) {
    //     $submissions[$key]['image_uploads'] = $this->get_submission_images_data_full($submission['id']);
    //   }
    // } else {
    //   foreach ($submissions as $key => $submission) {
    //     $submissions[$key]['image_uploads'] = get_submission_images($submission['id']);
    //   }
    // }

    //error_log($submission['image_uploads']);
    // Format the submissions
    foreach ($submissions as $key => $submission) {
      $submissions[$key] = $this->format_submission($submission);
    }

    return rest_ensure_response($submissions);
  }

  public function pta_api_check_permissions(\WP_REST_Request $request)
  {

    // $logAPI->debug('Checking permissions');
    // pta_api_nonce

    $nonce = $request->get_header('X-WP-Nonce');

    //error_log('Nonce: ' . $nonce);

    if (!$nonce) {
      $this->logger->warning('No nonce provided');
      return new \WP_Error('no_nonce', 'Nonce is required for authentication.', array('status' => 403));
    }

    //$verified = wp_verify_nonce($nonce, $nonce_action);
    $verified = wp_verify_nonce($nonce, 'wp_rest');

    // $logAPI->debug('Nonce verified: ' . $verified);

    // Verify the nonce. 'my_plugin_nonce_action' should match the action used when creating the nonce.
    if (!$verified) {
      $this->logger->warning('Invalid nonce');
      return new \WP_Error('invalid_nonce', 'Invalid nonce provided.', array('status' => 403));
    }

    // $logAPI->debug('Nonce verified');
    return true;
  }

  private function remove_duplicate_submissions($submissions)
  {
    $unique_submissions = array();
    $ids = array();

    foreach ($submissions as $submission) {
      if (!in_array($submission['id'], $ids)) {
        $ids[] = $submission['id'];
        $unique_submissions[] = $submission;
      }
    }

    return $unique_submissions;
  }

  private function format_submission($submission)
  {
    //error_log('Submission: ' . print_r($submission, true));
    $user = $this->user_func->get_user_by_id($submission['user_owner_id'])[0];

    //$this->logger->debug('User: ' . $user['username']);

    $userName = $user['username'];
    $imageFormated = $this->formate_image_data($submission['image_uploads']);

    $thumbnail_url = '';
    $map_url = '';
    foreach ($imageFormated as $image) {
      if ($image['is_thumbnail']) {
        $thumbnail_url = $image['image_url'];
      }
      if ($image['is_map']) {
        $map_url = $image['image_url'];
      }
    }

    // Title and description are unescaped
    $title = wp_unslash($submission['title']);
    $description = wp_unslash($submission['description']);

    $front_end_info = array(
      'id' => $submission['id'],
      'title' => $title,
      'description' => $description,
      'video_link' => $submission['video_link'],
      'state' => $submission['state'],
      'views' => $submission['views'],
      'likes' => $submission['likes_votes'],
      'user_id' => $submission['user_owner_id'],
      'user_name' => $userName,
      'created_at' => $submission['created_at'],
      'images' => $imageFormated,
      'thumbnail_url' => $thumbnail_url,
      'map_url' => $map_url,
      'rejected_reason' => $submission['rejected_reason']
    );

    $admin_end_in_info = array(
      'id' => $submission['id'],
      'title' => $title,
      'description' => $description,
      'video_link' => $submission['video_link'],
      'state' => $submission['state'],
      'views' => $submission['views'],
      'likes' => $submission['likes_votes'],
      'user_id' => $submission['user_owner_id'],
      'user_name' => $userName,
      'created_at' => $submission['created_at'],
      'images' => $imageFormated,
      'thumbnail_url' => $thumbnail_url,
      'map_url' => $map_url,
      'is_rejected' => $submission['is_rejected'] == 1,
      'was_rejected' => $submission['was_rejected'] == 1,
      'rejected_reason' => $submission['rejected_reason'],
      'is_removed' => $submission['is_removed'] == 1,
      'removed_reason' => $submission['removed_reason'],
    );

    // if user is admin, return admin end info
    if (current_user_can('administrator')) {
      return $admin_end_in_info;
    } else {
      return $front_end_info;
    }
  }

  public function formate_image_data($imageIDs)
  {

    $images = array();
    $imgIDs = json_decode($imageIDs);
    //error_log('Image IDs: ' . print_r($imgIDs, true));
    foreach ($imgIDs as $imageID) {
      $image = $this->image_func->get_image_data($imageID)[0];
      //$this->logger->debug('Image get: ' . print_r($image, true));
      if ($image == null) {
        continue;
      }
      $arrt = array(
        'id' => $image['image_id'],
        'image_url' => $image['image_reference'],
        'is_thumbnail' => $image['is_thumbnail'] == 1,
        'is_map' => $image['is_map'] == 1,
        'imageData' => $image,
      );
      $images[] = $arrt;
    }

    return $images;
  }

  public function process_user_view($submission_id)
  {
    $cookie_name = 'submission_views';
    $current_time = time(); // Current Unix timestamp
  
    // Retrieve the views from the cookie
    if (isset($_COOKIE[$cookie_name])) {
      $views = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
    } else {
      $views = array();
    }
  
    // Ensure $views is an array
    if (!is_array($views)) {
      $views = array();
    }
  
    // Clean up old views (older than 1 hour)
    foreach ($views as $key => $timestamp) {
      if (($current_time - $timestamp) >= HOUR_IN_SECONDS) {
        unset($views[$key]);
      }
    }
  
    // Check if the user has viewed this submission in the last hour
    if (isset($views[$submission_id])) {
      // Already viewed within the last hour; do nothing
    } else {
      // Increment the view count
      $this->submission_func->add_view_count($submission_id);
      // Record the view with the current timestamp
      $views[$submission_id] = $current_time;
    }
  
    // Limit the size of the $views array to prevent cookie size issues
    $max_views = 50; // Adjust as needed
    if (count($views) > $max_views) {
      // Keep only the most recent $max_views entries
      $views = array_slice($views, -$max_views, $max_views, true);
    }
  
    // Update the cookie with new views data
  // Set cookie to expire in 1 hour from now
    setcookie(
      $cookie_name,
      json_encode($views),
      $current_time + HOUR_IN_SECONDS,
      COOKIEPATH,
      COOKIE_DOMAIN,
      is_ssl(),
      true // httponly flag for security
    );
  }
}