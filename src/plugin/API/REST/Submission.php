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

      $ids = $this->restv2->get_id_from_params($params, 'id', $user, $errors);

      foreach ($ids as $id) {
        $submissions[] = $this->restv2->submission_functions->get_submission($id)[0];
      }

    }

    // if user_id is provided, get all submissions for that user
    if (isset($params['user_id'])) {
      // $this->logger->info('User ID: ' . $params['user_id']);
      // $this->restv2->logger->debug('User ID: ' . $params['user_id']);
      // error_log('User ID: ' . $params['user_id']);

      $user_ids = $this->restv2->get_id_from_params(params: $params, id_name: 'user_id', user: $user, errors: $errors, check_sub: false);

      foreach ($user_ids as $user_id) {
        $user_submissions = $this->restv2->submission_functions->get_submissions_by_user($user_id);
        // error_log('User Submissions: ' . print_r($user_submissions, true));
        foreach($user_submissions as $submission){
          if($this->restv2->permissionChecker->check_sub_perms($user, $submission)){
            // error_log('User has perms');
            $submissions[] = $submission;
          }
        }
        //$submissions[];
      }
    }

    if(isset($params['state'])) {
      //$this->logger->info('State: ' . $params['state']);

      $limitedSubmssionsByState = $this->restv2->submission_functions->get_submission_by_state($params['state'], 'ARRAY_A', true);

      //$this->logger->info('Limited Submissions: ' . print_r($limitedSubmssionsByState, true));

      foreach ($limitedSubmssionsByState as $submission) {

        if ($this->restv2->permissionChecker->check_sub_perms($user, $submission)) {
          $submissions[] = $this->restv2->submission_functions->get_submission($submission['id'])[0];
        } else {
          $errors[] = new \WP_Error('no_perms', 'User does not have permissions to view this submission.', array('status' => 403, 'submission_id' => $submission['id']));
        }

      }
    }

    if(isset($params['requested'])){

      $pendingSubmissions = $this->restv2->submission_functions->get_all_submissions_by_state('Pending Approval');

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

   //$this->logger->debug('API Response: ' . print_r($logResponce, true));

    return rest_ensure_response($return_data);
  }

  public function submission_action(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();
    $user_primary_role = $this->restv2->permissionChecker->get_user_role($user);

    // check if user is an admin or editor
    if($user_primary_role !== $this->constants::ADMIN && $user_primary_role !== $this->constants::EDITOR){
      return new \WP_Error('no_perms', 'User does not have permissions to perform this action.', array('status' => 403));
    }

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
    $submission_ids = $this->restv2->get_id_from_params(params: $params, id_name: 'id', user: $user, errors: $errors, check_sub: false);

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

    foreach($submission_ids as $submission_id){
      
      switch ($action) {
        case 'approve':
          $this->restv2->admin_functions->approve_submission($submission_id);
          break;
        case 'reject':
          $this->restv2->admin_functions->reject_submission($submission_id, $reason);
          break;
        case 'delete':
          $this->restv2->admin_functions->delete_submission($submission_id, $reason);
          break;
        case 'unreject':
          $this->restv2->admin_functions->unreject_submission($submission_id);
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