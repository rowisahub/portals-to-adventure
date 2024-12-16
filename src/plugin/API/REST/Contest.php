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
    //$user_primary_role = $this->restv2->permissionChecker->get_user_role($user);

    $errors = [];

    $return_data = [];

    // Get time remaining for contest
    $pta_clock_start_date = get_option('pta_clock_start_date');
    $pta_clock_end_date = get_option('pta_clock_end_date');

    if($pta_clock_start_date){
      $return_data['contest_date_start'] = $pta_clock_start_date;
    } else {
      $return_data['contest_date_start'] = "";
      $errors[] = new \WP_Error('no_contest_date_start', 'No contest start date was found.');
    }

    if($pta_clock_end_date){
      $return_data['contest_date_end'] = $pta_clock_end_date;
    } else {
      $return_data['contest_date_end'] = "";
      $errors[] = new \WP_Error('no_contest_date_end', 'No contest end date was found.');
    }

    //$this->restv2->logger->debug('Start Date: ' . $pta_clock_start_date);
    //$this->restv2->logger->debug('End Date: ' . $pta_clock_end_date);

    // Get total submissions count to date
    $total_submissions = $this->restv2->submission_functions->get_total_approved_submissions();

    $total_submissions = intval($total_submissions);

    if($total_submissions > 0){
      $return_data['total_approved_submissions'] = $total_submissions;
    } else {
      $return_data['total_approved_submissions'] = 0;
      $errors[] = new \WP_Error('no_submissions', 'No approved submissions were found.', ['total_approved_submissions' => $total_submissions]);
    }

    //$this->restv2->logger->debug('Total Submissions: ' . $total_submissions);

    // check if woocommerce is active
    if($this->restv2->isWooCommerceActive){
      //$this->restv2->logger->debug('WooCommerce is active');

      $WooCom_net_sales = $this->get_total_woocommerce_net_sales();

      // if($WooCom_net_sales > 0){
      //   $return_data['total_net_sales'] = $WooCom_net_sales;
      // } else {
      //   $errors[] = new \WP_Error('no_net_sales', 'No  were found.');
      // }

      $pta_percentage_prize_total = get_option('pta_percentage_prize_total', 50);

      // Calculate prize total with percentage off
      $prize_total = $WooCom_net_sales * ($pta_percentage_prize_total / 100);

      // round to the nearest whole number
      $prize_total = round(num: $prize_total, mode: PHP_ROUND_HALF_DOWN);

      if($prize_total > 0){
        $return_data['prize_total_usd_estimate'] = $prize_total;
      } else {
        $return_data['prize_total_usd_estimate'] = 0;
        $errors[] = new \WP_Error('no_prize_total', 'No prize total was found.');
      }

    } else {
      $return_data['prize_total_usd'] = 0;
      $errors[] = new \WP_Error('no_woocommerce', 'WooCommerce is not active.');
    }

    $return_data['errors'] = $errors;

    return rest_ensure_response($return_data);
  }

  private function get_total_woocommerce_net_sales()
  {
    $net_sales = 0;

    $args = [
      'status' => 'completed',
      'limit' => -1,
    ];

    $orders = wc_get_orders($args);

    foreach($orders as $order){
      $net_sales += $order->get_total();
    }

    return $net_sales;
  }
}