<?php
namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\client\Client;

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
    parent::__construct(LogName: "API", callback_after_init: $this->register_hooks());
  }

  /* init function is here, the `callback_function` runs that funciton after init is ran */

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
      $ids = $this->get_id_from_params($params, 'id', $user, $errors);

      foreach ($ids as $id) {
        $submissions[] = $this->submission_functions->get_submission($id);
      }

    }

    // if user_id is provided, get all submissions for that user
    if (isset($params['user_id'])) {
      $user_ids = $this->get_id_from_params($params, 'user_id', $user, $errors);

      foreach ($user_ids as $user_id) {
        $submissions[] = $this->submission_functions->get_submissions_by_user($user_id);
      }
    }

    if(isset($params['state'])) {
      $limitedSubmssionsByState = $this->submission_functions->get_submission_by_state($params['state'], 'ARRAY_A', true);

      foreach ($limitedSubmssionsByState as $submission) {

        if ($this->check_sub_perms($user, $submission)) {
          $submissions[] = $this->submission_functions->get_submission($submission['id']);
        } else {
          $errors[] = new \WP_Error('no_perms', 'User does not have permissions to view this submission.', array('status' => 403, 'submission_id' => $submission['id']));
        }

      }
    }

    $this->remove_duplicate_submissions($submissions);

    // End of Get Submissions
    foreach ($submissions as $key => $submission) {
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
  protected function get_id_from_params($params, $id_name, $user, &$errors)
  {
    $ids = sanitize_text_field($params[$id_name]);

    if(!$ids){
      $errors[] = new \WP_Error('no_id', 'No ID provided.', array('status' => 400));
      return [];
    }

    $submissions_ids = [];

    // check if there are multiple ids
    if (strpos($ids, ',') !== false) { // Multiple ids

      $submission_ids = explode(',', $ids);

      foreach ($submission_ids as $id) {

        if ($this->check_sub_exists($id, $user, $errors)) {
          $submissions_ids[] = $id;
        }
        
      }
      
    } else { // Only one id

      if ($this->check_sub_exists($ids, $user, $errors)) {
        $submissions_ids[] = $ids;
      }
    }

    return $submissions_ids;
    
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

  private function check_sub_exists($id, $user, &$errors)
  {
    $limitSubmission = $this->get_limitedInfo_submission_by_id($id)[0];
    if(!$limitSubmission){
      $errors[] = new \WP_Error('no_submission', 'Submission does not exist.', array('status' => 404, 'submission_id' => $id));
      return false;
    }

    if(!$this->check_sub_perms($user, $limitSubmission)){
      $errors[] = new \WP_Error('no_perms', 'User does not have permissions to view this submission.', array('status' => 403, 'submission_id' => $id));
      return false;
    }

    return true;
  }

  private function get_limitedInfo_submission_by_id($id){
    // make query to get submission by id state and user_owner_id
    $queryBuilder = new QueryBuilder($this->db_handler_instance->get_wpdb());
    $queryBuilder->select(['id', 'state', 'user_owner_id'])
      ->from($this->db_handler_instance->get_table('submission_data'))
      ->where(['id' => $id]);

    $results = $this->db_functions->exe_from_builder($queryBuilder);

    return $results;

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