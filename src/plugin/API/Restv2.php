<?php
namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\client\Client;

class Restv2 extends Client
{
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

  public function __construct()
  {
    parent::__construct("API");
  }

  public function init(
    $sub = null,
    $img = null,
    $user = null,
    $handler = null,
    $db = null,
    $admin = null
  ) {
    parent::init(
      sub_functions: $sub,
      img_functions: $img,
      user_functions: $user,
      handler_instance: $handler,
      db_functions: $db,
      admin_functions: $admin
    );

    $this->register_hooks();

  }

  public function register_hooks()
  {
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes()
  {
    /* This is the route for getting submissions */
    register_rest_route('pta/v2', '/submission', array(
      'methods' => 'GET',
      'callback' => array($this, 'submission_get'),
      'permission_callback' => array($this, 'check_permissions'),
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

  public function submission_get(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();
    $user_primary_role = $this->get_user_role($user);

    $params = $request->get_params();

    $submissions = [];
    $errors = [];

    /* If an ID is provided, get the submission with that ID */
    if (isset($params['id'])) {

      // sanitize the id, the id is a uuid
      $submission_id_in = sanitize_text_field($params['id']);

      // check if there are multiple ids
      if (strpos($submission_id_in, ',') !== false) { // Multiple ids

        $submission_ids = explode(',', $submission_id_in);

        foreach ($submission_ids as $id) {

          if ($this->db_functions->check_id_exists('submission_data', $id)) {
            $submissions[] = $this->submission_functions->get_submission($id)[0];
          } else {
            $errors[] = new \WP_Error('no_submission', 'Submission does not exist.', array('status' => 404, 'submission_id' => $id));
          }

        }
        
      } else { // Only one id

        if ($this->db_functions->check_id_exists('submission_data', $submission_id_in)) {
          $submissions[] = $this->submission_functions->get_submission($submission_id_in)[0];
        } else {
          $errors[] = new \WP_Error('no_submission', 'Submission does not exist.', array('status' => 404, 'submission_id' => $submission_id_in));
        }

      }

    }

    // if user_id is provided, get all submissions for that user
    if (isset($params['user_id'])) {
      $user_id = sanitize_text_field($params['user_id']);

      // check if there are multiple user ids
      if (strpos($user_id, ',') !== false) { // Multiple user ids

        $user_ids = explode(',', $user_id);

        foreach ($user_ids as $id) {

          // Check if id exists
          if ($this->db_functions->check_id_exists('user_info', $id)) {
            $submissions = $this->submission_functions->get_submissions_by_user($id);
          } else {
            $errors[] = new \WP_Error('no_user', 'User does not exist.', array('status' => 404));
          }
        }

      } else { // Only one user id

        // Check if id exists
        if ($this->db_functions->check_id_exists('user_info', $user_id)) {
          $submissions = $this->submission_functions->get_submissions_by_user($user_id);
        } else {
          $errors[] = new \WP_Error('no_user', 'User does not exist.', array('status' => 404));
        }

      }
    }

    // End of Get Submissions
    foreach ($submissions as $key => $submission) {
      if (!$this->check_sub_perms($user, $submission)) {
        unset($submissions[$key]);
        $errors[] = new \WP_Error('no_perms', 'User does not have permissions to view this submission.', array('status' => 403, 'submission_id' => $submission['id']));
      }

      $submissions[$key] = $this->format_response($submission);
    }

    $return_data = [
      'submissions' => $submissions,
      'errors' => $errors
    ];

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

    $params = $request->get_params();
  }

  protected function check_permissions(\WP_REST_Request $request)
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

  protected function check_sub_perms($user, $submission)
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
      $submission['state'] === 'Approved'
      ||
      $submission['user_owner_id'] === $user->ID
    ) {
      return true;
    }
    return false;
  }

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

  protected function format_response($submission)
  {
    //return new \WP_REST_Response($data, 200);
    return 'Test';
  }
}