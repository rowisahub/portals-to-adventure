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
   * Extracts an identifier from the provided parameters and validates it according to user permissions.
   *
   * This method retrieves an identifier from the given associative parameters array using the specified key name.
   * It also performs validation based on the provided user context, appending any encountered errors to the errors array.
   * Optional checks for public accessibility and sub-permission restrictions can be enabled.
   *
   * @param array  $params      An associative array containing the parameters.
   * @param string $id_name     The key name used to locate the identifier within the $params array.
   * @param mixed  $user        The user context used for validation.
   * @param array  &$errors     A reference to an array where error messages will be stored.
   * @param bool   $check_perms Optional. Whether to enforce permission checking. Defaults to true.
   * @param bool   $check_public Optional. Whether to check if the submission is public. Defaults to false.
   * @param bool   $check_user   Optional. Whether to check if the user is the owner of the submission. Defaults to true.
   * @param bool   $check_admin Optional. Whether to check if the user is an admin. Defaults to false.
   * @param bool   $check_if_submission Optional. Whether to check if the identifier is a submission ID. Defaults to true.
   *
   * @return mixed Returns the identifier if successfully retrieved and validated, otherwise returns an appropriate error indication.
   */
  public function get_id_from_params($params, $id_name, $user, &$errors, $check_perms = true, $check_public = false, $check_user = true, $check_admin = false, $check_if_submission = true)
  {
    $ids = sanitize_text_field($params[$id_name]);

    if(!$ids){
      $errors[] = new \WP_Error('no_id', 'No ID provided.', array('status' => 400));
      return [];
    }

    // $this->logger->debug('ID Name: ' . $id_name);
    // $this->logger->debug('IDs: ' . $ids);

    $submissions_ids = [];

    // check if there are multiple ids
    if (strpos($ids, ',') !== false) { // Multiple ids
      $this->logger->info('Multiple IDs: ' . $ids);

      $submission_ids = explode(',', $ids);

      foreach ($submission_ids as $id) {

        if ($check_if_submission) {
          if ($this->permissionChecker->check_sub_exists(id: $id, user: $user, errors: $errors, check_public: $check_public, check_user: $check_user, check_admin: $check_admin, check_perms: $check_perms)) {
            $submissions_ids[] = $id;
          }
        } else {
          // If no permission checking is required, just return the id
          $submissions_ids[] = $id;
        }
        
      }
      
    } else { // Only one id
      if($check_if_submission){
        if ($this->permissionChecker->check_sub_exists(id: $ids, user: $user, errors: $errors, check_public: $check_public, check_user: $check_user, check_admin: $check_admin, check_perms: $check_perms)) {
          $submissions_ids[] = $ids;
        }
      } else {
        // If no permission checking is required, just return the id
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