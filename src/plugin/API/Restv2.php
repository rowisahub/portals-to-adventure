<?php
namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\client\Client;
use OpenApi\Annotations as OA;

/* Requires MOVE LATER */
use PTA\DB\QueryBuilder;

/**
 * Class Restv2
 *
 * API class for V2
 *
 * @package PortalsToAdventure
 */
class Restv2 extends Client
{

  public function __construct()
  {
    parent::__construct(LogName: "APIv2", callback_after_init: $this->register_hooks());
  }

  /* init function is here, the `callback_function` runs that funciton after init is ran */

  public function register_hooks()
  {
    //error_log('API Hooks Registered');
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes()
  {
    //error_log('API Routes Registered');
    /* This is the route for getting submissions */
    register_rest_route('pta/v2', '/submission', array(
      'methods' => 'GET',
      'callback' => array($this, 'submission_get'),
      'permission_callback' => [$this, 'check_permissions'],
    ));

    /* This is the route for editing submissions */
    register_rest_route('pta/v2', '/submission', array(
      'methods' => 'POST',
      'callback' => [$this, 'submission_edit'],
      'permission_callback' => [$this, 'check_permissions'],
    ));

    /* This is the route for deleting submissions */
    register_rest_route('pta/v2', '/submission/action', array(
      'methods' => 'POST',
      'callback' => [$this, 'submission_action'],
      'permission_callback' => [$this, 'check_permissions'],
    ));
  }

  public function get_openapi_json()
  {
    $swag_json = file_get_contents('bitnami/wordpress/wp-content/plugins/portals-to-adventure/docs/openapi.json');
    return new \WP_REST_Response($swag_json, 200);
  }

  /**
   * Retrieves the submission data based on the provided parameters.
   * 
   * @param \WP_REST_Request $request The request object containing the parameters.
   * @return \WP_REST_Response The response object containing the submission data.
  */
  public function submission_get(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();

    $params = $request->get_params();

    $submissions = [];
    $errors = [];

    /* If an ID is provided, get the submission with that ID */
    if (isset($params['id'])) {
      //$this->logger->info('ID: ' . $params['id']);

      $ids = $this->get_id_from_params($params, 'id', $user, $errors);

      foreach ($ids as $id) {
        $submissions[] = $this->submission_functions->get_submission($id)[0];
      }

    }

    // if user_id is provided, get all submissions for that user
    if (isset($params['user_id'])) {
      //$this->logger->info('User ID: ' . $params['user_id']);

      $user_ids = $this->get_id_from_params(params: $params, id_name: 'user_id', user: $user, errors: $errors, check_sub: false);

      foreach ($user_ids as $user_id) {
        $user_submissions = $this->submission_functions->get_submissions_by_user($user_id);
        foreach($user_submissions as $submission){
          if($this->check_sub_perms($user, $submission)){
            $submissions[] = $submission;
          }
        }
        //$submissions[];
      }
    }

    if(isset($params['state'])) {
      //$this->logger->info('State: ' . $params['state']);

      $limitedSubmssionsByState = $this->submission_functions->get_submission_by_state($params['state'], 'ARRAY_A', true);

      //$this->logger->info('Limited Submissions: ' . print_r($limitedSubmssionsByState, true));

      foreach ($limitedSubmssionsByState as $submission) {

        if ($this->check_sub_perms($user, $submission)) {
          $submissions[] = $this->submission_functions->get_submission($submission['id'])[0];
        } else {
          $errors[] = new \WP_Error('no_perms', 'User does not have permissions to view this submission.', array('status' => 403, 'submission_id' => $submission['id']));
        }

      }
    }

    if(isset($params['requested'])){

      $pendingSubmissions = $this->submission_functions->get_all_submissions_by_state('Pending Approval');

      //$this->logger->info('Pending Submissions: ' . print_r($pendingSubmissions, true));

      foreach ($pendingSubmissions as $submission) {
        $submissions[] = $submission;
      }
    }

    if(isset($params['limit'])){
      //$this->logger->info('Limit: ' . $params['limit']);

      $limit = sanitize_text_field($params['limit']);

      if(!is_numeric($limit)){
        $errors[] = new \WP_Error('invalid_limit', 'Limit must be a number.', array('status' => 400));
      }

      // $submissions = array_slice($submissions, 0, $limit);
    }

    //$this->logger->info('Submissions: ' . print_r($submissions, true));

    // if no parameters are provided, exepct for limit, get all submissions
    if (empty($submissions)) {
        foreach ($this->submission_functions->get_all_submissions_by_state('Approved') as $submission){
          $submissions[] = $submission;
        }
    }

    $this->remove_duplicate_submissions($submissions);

    // End of Get Submissions
    foreach ($submissions as $key => $submission) {
      $submissions[$key] = $this->format_submission($submission, $user);
    }

    $return_data = [
      'submissions' => $submissions,
      'errors' => $errors
    ];

    $logResponce = [
      'Request URI' => $_SERVER['REQUEST_URI'],
      'Params' => $params,
      'Submissions_count' => count($submissions),
      // 'submissions' => $submissions,
      'Errors' => $errors
    ];

   //$this->logger->debug('API Response: ' . print_r($logResponce, true));

    return rest_ensure_response($return_data);
  }

  public function submission_edit(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();
    $user_primary_role = $this->get_user_role($user);

    $params = $request->get_params();
  }

  public function submission_action(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();
    $user_primary_role = $this->get_user_role($user);

    // check if user is an admin or editor
    if($user_primary_role !== self::ADMIN && $user_primary_role !== self::EDITOR){
      return new \WP_Error('no_perms', 'User does not have permissions to perform this action.', array('status' => 403));
    }

    $params = $request->get_params();

    $this->logger->debug('Action Params: ' . print_r($params, true));

    if(empty($params['action'])){
      return new \WP_Error('no_action', 'No action provided.', array('status' => 400));
    }

    $action = $params['action'];
    $submission_ids = $params['id'];
    $reason = $params['reason'];

    $errors = [];

    // $this->logger->info('Action: ' . $params);

    // check if there are multiple ids, both ways should return an array
    $submission_ids = $this->get_id_from_params(params: $params, id_name: 'id', user: $user, errors: $errors, check_sub: false);

    $this->logger->debug('Submission IDs: ' . print_r($submission_ids, true));

    if(empty($submission_ids)){
      return rest_ensure_response([
        'message' => 'No submissions found.',
        'code' => 'no_submissions',
        'data' => [
          'action' => $action,
          'reason' => $reason,
          'errors' => $errors
        ]
      ]);
    }

    $this->logger->debug('Action: ' . $action);

    foreach($submission_ids as $submission_id){
      
      switch ($action) {
        case 'approve':
          $this->admin_functions->approve_submission($submission_id);
          break;
        case 'reject':
          $this->admin_functions->reject_submission($submission_id, $reason);
          break;
        case 'delete':
          $this->admin_functions->delete_submission($submission_id, $reason);
          break;
        case 'unreject':
          $this->admin_functions->unreject_submission($submission_id);
          break;
        default:
          $errors[] = new \WP_Error('invalid_action', 'Invalid action provided.', array('status' => 400));
          break;
      }

    }

    $this->logger->debug('Errors: ' . print_r($errors, true));

    if(!empty($errors)){
      // all submissions failed
      if(count($errors) == count($submission_ids)){
        return rest_ensure_response([
          'message' => 'All submissions actions failed.',
          'code' => 'failed_action',
          'data' => [
            'action' => $action,
            'reason' => $reason,
            'errors' => $errors
          ]
        ]);
      }

      // some submissions failed
      if(count($errors) < count($submission_ids)){
        return rest_ensure_response([
          'message' => 'Some submissions actions failed.',
          'code' => 'failed_action',
          'data' => [
            'action' => $action,
            'reason' => $reason,
            'errors' => $errors
          ]
        ]);
      }
    }

    $this->logger->debug('All Submissions Actions Successful');

    return rest_ensure_response([
      'message' => 'All submissions actions were successful.',
      'code' => 'success_action',
      'data' => [
        'action' => $action,
        'reason' => $reason,
        'errors' => $errors
      ]
    ]);
   

  }

  public function check_permissions(\WP_REST_Request $request)
  {
    /* Nonce check */
    $nonce = $request->get_header('X-WP-Nonce');
    if (!$nonce) {
      // No nonce
      return new \WP_Error('no_nonce', 'Nonce is required for authentication.', array('status' => 403));
    } elseif (!wp_verify_nonce($nonce, 'wp_rest')) {
      // Invalid nonce
      return new \WP_Error('invalid_nonce', 'Invalid nonce.', array('status' => 403));
    }

    return true;
  }

  /**
   * Removes duplicate submissions from the provided submissions array.
   *
   * @param array &$submissions The array of submissions to be processed. This parameter is passed by reference.
   */
  private function remove_duplicate_submissions(&$submissions)
  {
    $ids = [];

    foreach ($submissions as $key => $submission) {
      if (in_array($submission['id'], $ids)) {
        unset($submissions[$key]);
      } else {
        $ids[] = $submission['id'];
      }
    }
  }

  /**
   * Retrieves the ID from the provided parameters. Also checks if the ID is valid and permissions are correct.
   *
   * @param array $params The parameters from which to extract the ID.
   * @return array|null The extracted ID if found, otherwise null.
   */
  protected function get_id_from_params($params, $id_name, $user, &$errors, $check_public = true, $check_sub = true)
  {
    $ids = sanitize_text_field($params[$id_name]);

    if(!$ids){
      $errors[] = new \WP_Error('no_id', 'No ID provided.', array('status' => 400));
      return [];
    }

    $submissions_ids = [];

    // check if there are multiple ids
    if (strpos($ids, ',') !== false) { // Multiple ids
      //$this->logger->info('Multiple IDs: ' . $ids);

      $submission_ids = explode(',', $ids);

      if($check_sub){

        foreach ($submission_ids as $id) {

          if ($this->check_sub_exists(id: $id, user: $user, errors: $errors, check_public: $check_public)) {
            $submissions_ids[] = $id;
          }
          
        }

      } else {
        $submissions_ids[] = $submission_ids;
      }
      
    } else { // Only one id
      //$this->logger->info('Single ID: ' . $ids);

      if($check_sub){

        if ($this->check_sub_exists(id: $ids, user: $user, errors: $errors, check_public: $check_public)) {
          $submissions_ids[] = $ids;
        }

      }else{
        $submissions_ids[] = $ids;
      }

    }

    return $submissions_ids;
    
  }

  /**
   * Check the permissions of a user for a specific submission.
   *
   * @param \WP_User $user The user object whose permissions are being checked.
   * @param mixed $submission The submission object or data that the user is attempting to access.
   * @return bool True if the user has the necessary permissions, false otherwise.
   */
  protected function check_sub_perms($user, $submission, $check_public = true)
  {
    $user_primary_role = $this->get_user_role($user);

    // return true if user is a admin, the submission is public 'Approved', or the user is the owner of the submission
    if (
      (
        $user_primary_role === self::ADMIN
        ||
        $user_primary_role === self::EDITOR
      )
      ||
      (
        $check_public
        &&
        $submission['state'] === 'Approved'
      )
      ||
      $submission['user_owner_id'] === $user->ID
    ) {
      return true;
    }
    return false;
  }

  /**
   * Checks if a submission exists and if the user has the necessary permissions to view it.
   * 
   * @param int $id The ID of the submission to check.
   * @param \WP_User $user The user object whose permissions are being checked.
   * @param array &$errors An array of errors to which any errors will be added.
   * @return bool True if the submission exists and the user has the necessary permissions, false otherwise.
   */
  private function check_sub_exists($id, $user, &$errors, $check_perms = true, $check_public = true)
  {
    $limitSubmission = $this->get_limitedInfo_submission_by_id($id)[0];
    if(!$limitSubmission){
      $errors[] = new \WP_Error('no_submission', 'Submission does not exist.', array('status' => 404, 'submission_id' => $id));
      return false;
    }

    if($check_perms){

      if(!$this->check_sub_perms($user, $limitSubmission, $check_public)){
        $errors[] = new \WP_Error('no_perms', 'User does not have permissions to view this submission.', array('status' => 403, 'submission_id' => $id));
        return false;
      }

    }

    return true;
  }

  /**
   * Retrieves limited information about a submission by its ID.
   *
   * @param int $id The ID of the submission.
   * @return array The limited information of the submission.
   */
  private function get_limitedInfo_submission_by_id($id){
    // make query to get submission by id state and user_owner_id
    $queryBuilder = new QueryBuilder($this->db_handler_instance->get_wpdb());
    $queryBuilder->select(['id', 'state', 'user_owner_id'])
      ->from($this->db_handler_instance->get_table_path('submission_data'))
      ->where(['id' => $id]);

    $results = $this->db_functions->exe_from_builder($queryBuilder);

    return $results;

  }

  /**
   * Retrieve the role of a given user.
   *
   * @param \WP_User $user The user object whose role is to be retrieved.
   * @return string|\WP_Error The role of the user or a WP_Error object if the user is invalid.
   */
  protected function get_user_role($user)
  {
    if (!($user instanceof \WP_User)) {
      return new \WP_Error('invalid_user', 'Invalid user.', array('status' => 403));
    }

    $user_primary_role = self::USER;

    if (in_array('administrator', $user->roles)) {
      $user_primary_role = self::ADMIN;
    } elseif (in_array('editor', $user->roles)) {
      $user_primary_role = self::EDITOR;
    }

    return $user_primary_role;
  }

  /**
   * Formats the submission data.
   *
   * @param array $submission The submission data to format.
   * @param \WP_User $user The user associated with the submission.
   * @return array The formatted submission data.
   */
  private function format_submission($submission, $user){
    $formated_images = $this->format_image($submission);
    $thumbnail_url = $formated_images['thumbnail_url'];
    $map_url = $formated_images['map_url'];
    $imagesShare = $formated_images['images'];

    // Title and description are unescaped
    $title = wp_unslash($submission['title']);
    $description = wp_unslash($submission['description']);

    $userpta = $this->user_functions->get_user_by_id($submission['user_owner_id'])[0];
    $username = $userpta['username'];

    $submission_api_base = [
      'id' => $submission['id'],
      'title' => $title,
      'description' => $description,
      'video_link' => $submission['video_link'],
      'state' => $submission['state'],
      'views' => $submission['views'],
      'likes' => $submission['likes_votes'],
      'user_id' => $submission['user_owner_id'],
      'user_name' => $username,
      'created_at' => $submission['created_at'],
      'images' => $imagesShare,
      'thumbnail_url' => $thumbnail_url,
      'map_url' => $map_url
    ];

    $user_primary_role = $this->get_user_role($user);

    // check if user is an admin editor or the owner of the submission
    if($user_primary_role === self::ADMIN || $user_primary_role === self::EDITOR || $submission['user_owner_id'] === $user->ID){
      $submission_api_base['removed_reason'] = $submission['removed_reason'];
      $submission_api_base['is_removed'] = $submission['is_removed'] == 1;
      $submission_api_base['is_rejected'] = $submission['is_rejected'] == 1;
      $submission_api_base['rejected_reason'] = $submission['rejected_reason'];
    }

    // check if user is an admin or editor
    if($user_primary_role === self::ADMIN || $user_primary_role === self::EDITOR){
      $submission_api_base['was_rejected'] = $submission['was_rejected'] == 1;
    }

    return $submission_api_base;
  }

  /**
   * Formats the image data from the given submission.
   *
   * @param array $submission The submission data containing image information.
   * @return array The formatted image data.
   */
  private function format_image($submission){
    $image_ids = json_decode($submission['image_uploads']);

    $images = [];

    $thumbnail_url = '';
    $map_url = '';

    foreach ($image_ids as $image_id) {
      $image = $this->image_functions->get_image_data($image_id)[0];

      if(!$image){
        continue;
      }

      if($image['is_thumbnail'] == 1){
        $thumbnail_url = $image['image_reference'];
      }
      if($image['is_map'] == 1){
        $map_url = $image['image_reference'];
      }

      $images[] = [
        'id' => $image['image_id'],
        'image_url' => $image['image_reference'],
        'is_thumbnail' => $image['is_thumbnail'] == 1,
        'is_map' => $image['is_map'] == 1,
        'imageData' => $image
      ];
    }

    return [
      'images' => $images,
      'thumbnail_url' => $thumbnail_url,
      'map_url' => $map_url
    ];
    
  }

    /* Constants */
    public const USER = 'user';
    public const ADMIN = 'admin';
    public const EDITOR = 'editor';
    public const USER_PERMS = [
      self::USER,
      self::ADMIN,
      self::EDITOR
    ];
    /* Constants */
}