<?php
namespace PTA\API\REST\utils;

use PTA\API\Restv2;
use PTA\API\REST\utils\Constants;
use PTA\DB\QueryBuilder;

class PermissionChecker
{
    private Restv2 $restV2_instance;
    private Constants $constants;

    public function __construct($restV2_instance)
    {
        $this->restV2_instance = $restV2_instance;
        $this->constants = new Constants();
    }
    
    public function check_permission(\WP_REST_Request $request)
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

        /* Date check */
        if (!$this->check_contest_date()) {
            if(!current_user_can('administrator') && !current_user_can('editor')){
                return new \WP_Error('contest_inactive', 'Contest is not active.', array('status' => 403));
            } else {
                return true;
            }
        }

        return true;
    }

    /**
   * Retrieve the role of a given user.
   *
   * @param \WP_User $user The user object whose role is to be retrieved.
   * @return string|\WP_Error The role of the user or a WP_Error object if the user is invalid.
   */
  public function get_user_role($user)
  {
    if (!($user instanceof \WP_User)) {
      return new \WP_Error('invalid_user', 'Invalid user.', array('status' => 403));
    }

    $user_primary_role = $this->constants::USER;

    if (in_array('administrator', $user->roles)) {
      $user_primary_role = $this->constants::ADMIN;
    } elseif (in_array('editor', $user->roles)) {
      $user_primary_role = $this->constants::EDITOR;
    }

    return $user_primary_role;
  }

  /**
   * Check the permissions of a user for a specific submission.
   *
   * @param \WP_User $user The user object whose permissions are being checked.
   * @param mixed $submission The submission object or data that the user is attempting to access.
   * @param bool $check_public Optional. Whether to check if the submission is public. Default is true.
   * @return bool True if the user has the necessary permissions, false otherwise.
   */
  public function check_sub_perms($user, $submission, $check_public = true, $check_user = true, $check_admin = true)
  {
    $user_primary_role = $this->get_user_role($user);

    // error_log('User Role: ' . $user_primary_role);
    // error_log('Submission State: ' . $submission['state']);
    // error_log('Submission Owner ID: ' . $submission['user_owner_id']);
    // error_log('User ID: ' . $user->ID);
    // error_log('Check Public: ' . $check_public);

    // return true if user is a admin, the submission is public 'Approved', or the user is the owner of the submission
    if (
      (
        $check_admin
        &&
        (
          $user_primary_role === $this->constants::ADMIN
          ||
          $user_primary_role === $this->constants::EDITOR
        )
      )
      ||
      (
        $check_public
        &&
        $submission['state'] === 'Approved'
      )
      ||
      (
        $check_user
        &&
        $submission['user_owner_id'] == $user->ID
      )
    ) {
      // error_log('User has permissions to view this submission.');
      return true;
    }
    // error_log('User does not have permissions to view this submission.');
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
  public function check_sub_exists($id, $user, &$errors, $check_perms = true, $check_public = true)
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
    $queryBuilder = new QueryBuilder($this->restV2_instance->db_handler_instance->get_wpdb());
    $queryBuilder->select(['id', 'state', 'user_owner_id'])
      ->from($this->restV2_instance->db_handler_instance->get_table_path('submission_data'))
      ->where(['id' => $id]);

    $results = $this->restV2_instance->db_functions->exe_from_builder($queryBuilder);

    return $results;

  }

  private function check_contest_date()
  {
    $pta_clock_start_date = get_option('pta_clock_start_date');
    $pta_clock_end_date = get_option('pta_clock_end_date');

    // $this->restV2_instance->logger->info('Start Date: ' . $pta_clock_start_date);
    // $this->restV2_instance->logger->info('End Date: ' . $pta_clock_end_date);

    if ($pta_clock_start_date && $pta_clock_end_date) {
      $current_date = date('Y-m-d H:i:s');

      // $this->restV2_instance->logger->info('Current Date: ' . $current_date);

      if ($current_date >= $pta_clock_start_date && $current_date <= $pta_clock_end_date) {
        // $this->restV2_instance->logger->debug('Contest is active.');
        return true;
      }

      // $this->restV2_instance->logger->debug('Contest is not active.');
      return false;

    } else {

      // $this->restV2_instance->logger->debug('Contest dates not set.');
      return true;

    }
  }
}