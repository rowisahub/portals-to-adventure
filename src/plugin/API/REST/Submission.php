<?php
namespace PTA\API\REST;

use PTA\API\Restv2;
use PTA\API\REST\utils\Constants;

class Submission
{
  private Constants $constants;
  private Restv2 $restv2;

  public function __construct(Restv2 $restv2_instance)
  {
    $this->restv2 = $restv2_instance;
    $this->constants = new Constants();

  }

    public function submission_get(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();

    $params = $request->get_params();

    $submissions = [];
    $errors = [];

    /* If an ID is provided, get the submission with that ID */
    if (isset($params['id'])) {
      //$this->logger->info('ID: ' . $params['id']);

      $ids = $this->restv2->get_id_from_params($params, 'id', $user, $errors, $check_perms = true, $check_public = true, $check_user = true, $check_admin = true);

      foreach ($ids as $id) {
        $submissions[] = $this->restv2->submission_functions->get_submission($id)[0];
      }

    }

    // if user_id is provided, get all submissions for that user
    if (isset($params['user_id'])) {
      // $this->logger->info('User ID: ' . $params['user_id']);
      // $this->restv2->logger->debug('User ID: ' . $params['user_id']);
      // error_log('User ID: ' . $params['user_id']);

      $user_ids = $this->restv2->get_id_from_params(params: $params, id_name: 'user_id', user: $user, errors: $errors, check_if_submission: false);

      // $this->restv2->logger->debug('User IDs: ' . print_r($user_ids, true));

      if (empty($user_ids)) {
        $errors[] = new \WP_Error('no_user_id', 'No user ID provided or user does not exist.', array('status' => 400, 'code' => 'no_user_id'));
      } else {

        foreach ($user_ids as $user_id) {
          $user_submissions = $this->restv2->submission_functions->get_submissions_by_user($user_id);

          foreach ($user_submissions as $submission) {
            // Check permissions for each submission
            if ($this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: false, check_user: true, check_admin: true)) {
              $submissions[] = $submission;
            }
          }
        }

      }
    }

    if(isset($params['state'])) {
      //$this->logger->info('State: ' . $params['state']);

      $limitedSubmssionsByState = $this->restv2->submission_functions->get_submission_by_state($params['state'], 'ARRAY_A', true);

      //$this->logger->info('Limited Submissions: ' . print_r($limitedSubmssionsByState, true));

      foreach ($limitedSubmssionsByState as $submission) {

        if ($this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: true, check_user: true, check_admin: true)) {
          $submissions[] = $this->restv2->submission_functions->get_submission($submission['id'])[0];
        }

      }
    }

    if(isset($params['requested'])){

      $pendingSubmissions = $this->restv2->submission_functions->get_all_submissions_by_state('Pending Approval');

      //$this->logger->info('Pending Submissions: ' . print_r($pendingSubmissions, true));

      foreach ($pendingSubmissions as $submission) {
        if($this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: false, check_user: false, check_admin: true)){
          $submissions[] = $submission;
        }
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

    // if no parameters are provided, expect for limit, get all submissions
    if (empty($submissions) && empty($params)) {
        foreach ($this->restv2->submission_functions->get_all_submissions_by_state('Approved') as $submission){
          $submissions[] = $submission;
        }
    }

    $this->restv2->submissionFormatter->remove_duplicate_submissions($submissions);

    // End of Get Submissions
    foreach ($submissions as $key => $submission) {
      //$submissions[$key] = $this->format_submission($submission, $user);
      $submissions[$key] = $this->restv2->submissionFormatter->format_submission($submission, $user);
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

    $this->restv2->logger->debug('API Response: ' . print_r($logResponce, true));

    return rest_ensure_response($return_data);
  }

  public function submission_action(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();
    $user_primary_role = $this->restv2->permissionChecker->get_user_role($user);

    $params = $request->get_params();

    $this->restv2->logger->debug('Action Params: ' . print_r($params, true));

    if(empty($params['action'])){
      return new \WP_Error('no_action', 'No action provided.', array('status' => 400));
    }

    $action = $params['action'];
    $submission_ids = $params['id'];
    $reason = $params['reason'];

    $errors = [];

    // $this->logger->info('Action: ' . $params);

    // check if there are multiple ids, both ways should return an array
    $submission_ids = $this->restv2->get_id_from_params(params: $params, id_name: 'id', user: $user, errors: $errors, check_perms: false);

    $this->restv2->logger->debug('Submission IDs: ' . print_r($submission_ids, true));

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

    $this->restv2->logger->debug('Action: ' . $action);

    // check if user is an admin or editor
    // if($user_primary_role !== $this->constants::ADMIN && $user_primary_role !== $this->constants::EDITOR){
    //   return new \WP_Error('no_perms', 'User does not have permissions to perform this action.', array('status' => 403));
    // }

    foreach($submission_ids as $submission_id){
      $this->restv2->logger->debug('Processing Submission ID: ' . $submission_id);
      $submission = $this->restv2->submission_functions->get_submission($submission_id)[0];
      
      switch ($action) {
        case 'approve':
          // Admin action, check if user has permissions
          if(!$this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: false, check_user: false, check_admin: true)){
            $errors[] = new \WP_Error('no_perms', 'User does not have permissions to approve submissions.', array('status' => 403, 'submission_id' => $submission_id));
            continue; // Skip to the next submission
          }
          $this->restv2->admin_functions->approve_submission($submission_id);
          break;
        case 'reject':
          // Admin action, check if user has permissions
          if(!$this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: false, check_user: false, check_admin: true)){
            $errors[] = new \WP_Error('no_perms', 'User does not have permissions to reject submissions.', array('status' => 403, 'submission_id' => $submission_id));
            continue; // Skip to the next submission
          }
          $this->restv2->admin_functions->reject_submission($submission_id, $reason);
          break;
        case 'delete':
          // User action, check if user
          if(!$this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: false, check_user: true, check_admin: true)){
            $errors[] = new \WP_Error('no_perms', 'User does not have permissions to delete submissions.', array('status' => 403, 'submission_id' => $submission_id));
            continue; // Skip to the next submission
          }
          $this->restv2->admin_functions->delete_submission($submission_id, $reason);
          break;
        case 'unreject':
          // Admin action, check if user has permissions
          if(!$this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: false, check_user: false, check_admin: true)){
            $errors[] = new \WP_Error('no_perms', 'User does not have permissions to unreject submissions.', array('status' => 403, 'submission_id' => $submission_id));
            continue; // Skip to the next submission
          }
          $this->restv2->admin_functions->unreject_submission($submission_id);
          break;
        case 'revert':
          // User action
          if(!$this->restv2->permissionChecker->check_sub_perms($user, $submission, check_public: false, check_user: true, check_admin: true)){
            $errors[] = new \WP_Error('no_perms', 'User does not have permissions to revert submissions.', array('status' => 403, 'submission_id' => $submission_id));
            continue; // Skip to the next submission
          }
          $this->restv2->admin_functions->revert_submission($submission_id);
          break;
        default:
          $errors[] = new \WP_Error('invalid_action', 'Invalid action provided.', array('status' => 400));
          break;
      }

    }

    $this->restv2->logger->debug('Errors: ' . print_r($errors, true));

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

    $this->restv2->logger->debug('All Submissions Actions Successful');

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
}