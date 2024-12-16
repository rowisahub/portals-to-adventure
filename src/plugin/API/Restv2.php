<?php
namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\client\Client;
use PTA\API\REST\utils\SubmissionFormatter;
use PTA\API\REST\utils\Constants;
use PTA\API\REST\utils\PermissionChecker;
use PTA\API\REST\utils\RouteRegistrar;

use PTA\API\REST\Submission;
use PTA\API\REST\Contest;

/**
 * Class Restv2
 *
 * API class for V2
 *
 * @package PortalsToAdventure
 */
class Restv2 extends Client
{
  public bool $isWooCommerceActive = false;
  private Constants $constants;
  public PermissionChecker $permissionChecker;
  public SubmissionFormatter $submissionFormatter;
  protected Submission $submission_api;
  protected Contest $contest_api;


  public function __construct()
  {
    parent::__construct(LogName: "APIv2", callback_after_init: $this->setup());
  }

  /* init function is here, the `callback_function` runs that funciton after init is ran */

  public function setup(){
    $this->constants = new Constants();
    $this->permissionChecker = new PermissionChecker($this);
    $this->submissionFormatter = new SubmissionFormatter($this);
    $this->submission_api = new Submission($this);
    $this->contest_api = new Contest($this);

    add_action('rest_api_init', [$this, 'register_routes']);

    add_action(hook_name: 'woocommerce_loaded', callback: array($this, 'woocommerce_loaded'));
  }

  public function register_routes()
  {
    RouteRegistrar::register_routes($this);
  }


  public function submission_get(\WP_REST_Request $request)
  {
    return $this->submission_api->submission_get($request);
  }

  public function submission_action(\WP_REST_Request $request)
  {
    return $this->submission_api->submission_action($request);
  }

  public function get_contest_info(\WP_REST_Request $request)
  {
    return $this->contest_api->get_contest_info($request);
  }

   /**
   * Retrieves the ID from the provided parameters. Also checks if the ID is valid and permissions are correct.
   *
   * @param array $params The parameters from which to extract the ID.
   * @return array|null The extracted ID if found, otherwise null.
   */
  public function get_id_from_params($params, $id_name, $user, &$errors, $check_public = true, $check_sub = true)
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

          if ($this->permissionChecker->check_sub_exists(id: $id, user: $user, errors: $errors, check_public: $check_public)) {
            $submissions_ids[] = $id;
          }
          
        }

      } else {
        $submissions_ids[] = $submission_ids;
      }
      
    } else { // Only one id
      //$this->logger->info('Single ID: ' . $ids);

      if($check_sub){

        if ($this->permissionChecker->check_sub_exists(id: $ids, user: $user, errors: $errors, check_public: $check_public)) {
          $submissions_ids[] = $ids;
        }

      }else{
        $submissions_ids[] = $ids;
      }

    }

    return $submissions_ids;
    
  }

  public function check_permissions(\WP_REST_Request $request)
  {
    return $this->permissionChecker->check_permission($request);
  }

  public function woocommerce_loaded()
  {
    $this->isWooCommerceActive = true;
  }
}