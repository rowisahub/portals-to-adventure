<?php
namespace PTA\API\REST;

use PTA\API\Restv2;

class Contest
{
  private Restv2 $restv2;

  public function __construct(Restv2 $restv2_instance)
  {
      $this->restv2 = $restv2_instance;
  }


  public function get_contest_info(\WP_REST_Request $request)
  {
    $user = wp_get_current_user();
    $user_primary_role = $this->restv2->permissionChecker->get_user_role($user);

    $errors = [];

    // Get time remaining for contest
    $pta_clock_start_date = get_option('pta_clock_start_date');
    $pta_clock_end_date = get_option('pta_clock_end_date');

    if(!$pta_clock_start_date){
      $errors[] = new \WP_Error('no_contest_date_start', 'No contest start date was found.');
    }
    if(!$pta_clock_end_date){
      $errors[] = new \WP_Error('no_contest_date_end', 'No contest end date was found.');
    }

    $this->restv2->logger->debug('Start Date: ' . $pta_clock_start_date);
    $this->restv2->logger->debug('End Date: ' . $pta_clock_end_date);

    // Get total submissions count to date
    $total_submissions = $this->restv2->submission_functions->get_total_approved_submissions();

    $this->restv2->logger->debug('Total Submissions: ' . $total_submissions);

    // check if woocommerce is active
    if($this->restv2->isWooCommerceActive){
        $this->restv2->logger->debug('WooCommerce is active');
    } else {
      $errors[] = new \WP_Error('no_woocommerce', 'WooCommerce is not active.');
    }

    $return_data = [
      'contest_start_date' => $pta_clock_start_date,
      'contest_end_date' => $pta_clock_end_date,
      'total_approved_submissions' => $total_submissions,
      'errors' => $errors
    ];

    return rest_ensure_response($return_data);
  }
}