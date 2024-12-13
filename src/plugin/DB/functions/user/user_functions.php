<?php
namespace PTA\DB\functions\user;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\QueryBuilder;
use PTA\logger\Log;

class user_functions
{

  private $table_path;
  private $handler_instance;
  private $db_functions;
  private $logger;
  private \wpdb $wpdb;

  public function __construct(db_handler $handler_instance = null, db_functions $db_functions = null)
  {
    // Get the handler instance and db functions instance
    $this->handler_instance = $handler_instance ?? new db_handler();
    $this->db_functions = $db_functions ?? new db_functions();

    // if handler_instance is null or db_functions is null
    if ($handler_instance == null || $db_functions == null) {

      // Set the functions instance in the handler, and initialize the functions
      $this->handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
      $this->db_functions->init(handler_instance: $this->handler_instance);

    }

    $this->wpdb = $this->handler_instance->get_WPDB();

    $this->table_path = $this->handler_instance->get_table_path('user_info');

    $this->logger = new Log('User Functions');
    $this->logger = $this->logger->getLogger();
  }

  function register_user($email, $username, $firstName = null, $lastName = null, $verified_email = 0, $token = '', $birthday = null, $permissions = '', $payment_info = null)
  {

    // also need to register user in WordPress
    $user = get_user_by('email', $email);

    if (!$user) {
      $user_id = wp_create_user($username, wp_generate_password(15, true, false), $email);

      if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
      }

      if ($firstName != null) {
        update_user_meta($user_id, 'first_name', $firstName);
      }
      if ($lastName != null) {
        update_user_meta($user_id, 'last_name', $lastName);
      }

      if ($verified_email == 1) {
        add_user_meta($user_id, '_user_verified_email', 'true');
      } else {
        add_user_meta($user_id, '_user_verified_email', 'false');
      }

      $user = get_user_by('id', $user_id);
    }

    if (!$user) {
      $this->logger->error('Error creating user');
      return null;
    }

    $user_id = $user->ID;

    if ($this->db_functions->check_id_exists('user_info', $user_id)) {
      $this->logger->debug('User already exists in user_info table');
      return $user_id;
    }

    $data = [
      'id' => $user_id,
      'token' => $token,
      'email' => $email,
      'username' => $username,
      'birthday' => $birthday,
      'permissions' => $permissions,
      'payment_info' => $payment_info
    ];

    $this->wpdb->insert($this->table_path, $data);

    $this->logger->info('User created in user_info table');

    return $user_id;
  }

  function get_user_permissions($user_id, $perm = null)
  {
    $queryBuilder = new QueryBuilder(wpdb: $this->wpdb);
    $queryBuilder->select('*')
      ->from($this->table_path)
      ->where(['id' => $user_id]);

    $result = $this->db_functions->exe_from_builder(query_builder: $queryBuilder);

    if ($perm != null) {
      return $result['permissions'][$perm];
    }

    return $result['permissions'];
  }

  function get_user_by_id($user_id)
  {
    $queryBuilder = new QueryBuilder(wpdb: $this->wpdb);
    $queryBuilder->select('*')
      ->from($this->table_path)
      ->where(['id' => $user_id]);

    return $this->db_functions->exe_from_builder(query_builder: $queryBuilder);
  }

  function check_user_exists($user_id)
  {
    $result = $this->get_user_by_id($user_id);

    if (!$result) {
      return false;
    }

    return true;
  }

  function remove_user($user_id)
  {
    $this->wpdb->delete($this->table_path, ['id' => $user_id]);
    // remove user from WordPress
    wp_delete_user($user_id);
  }

  /**
   * Check the number of submissions made by a user within a specified time period.
   *
   * @param int $user_id The ID of the user whose submissions are being checked.
   * @param string $time_period The time period within which to check for submissions. 
   *                            This should be a valid time period string (e.g., '1 hour', '1 day').
   *
   * @return array|false The submissions made by the user within the specified time period.
   */
  // @return int The number of submissions made by the user within the specified time period.
  function get_user_submissions_in_time_period($user_id, $time_period = null)
  {
    //$this->logger->debug('time period: ' . $time_period);
    if ($time_period == null) {
      /* Get time period from options */
      $pta_number_of_submissions_per_time_period = get_option('pta_number_of_submissions_per_time_period', 1);

      $pta_time_period = get_option('pta_time_period', 'days');
      

      if(!is_numeric($pta_number_of_submissions_per_time_period)){
        $this->logger->error('Number of submissions per time period is not a number: ' . $pta_number_of_submissions_per_time_period);
        return false;
      }

      $time_period = $pta_number_of_submissions_per_time_period . ' ' . $pta_time_period;
    }
    //$this->logger->debug('time period: ' . $time_period);

    // filter if $time_period is in hours or days
    if (strpos($time_period, 'day') !== false || strpos($time_period, 'days') !== false) {
      $time_period = str_replace('day', '', $time_period);
      $time_period = str_replace('s', '', $time_period);
      $time_period = $time_period * 24;
    } else if (strpos($time_period, 'hour') !== false || strpos($time_period, 'hours') !== false) {
      $time_period = str_replace('hour', '', $time_period);
      $time_period = str_replace('s', '', $time_period);
    } else {
      return false;
    }

    // check if $time_period is a number
    if (!is_numeric($time_period)) {
      $this->logger->error('Time period is not a number: ' . $time_period);
      return false;
    }

    //$this->logger->debug('time period: ' . $time_period);

    /* Make the query */
    $queryBuilder = new QueryBuilder(wpdb: $this->wpdb);

    $start_time = $queryBuilder->raw("NOW() - INTERVAL $time_period HOUR");
    //$start_time = $queryBuilder->raw("NOW() - INTERVAL 240000 HOUR");

    $queryBuilder->select(['user_owner_id', 'id'])
      ->from($this->handler_instance->get_table_path('submission_data'))
      ->where([
        'user_owner_id' => $user_id,
        'created_at >=' => $start_time
      ]);

    //$this->logger->debug('Query: ' . $queryBuilder->get_sql());

    // return the count of submissions
    $result = $this->db_functions->exe_from_builder(query_builder: $queryBuilder);

    return $result;

  }

  /**
   * Check the number of user submissions within a specified time period.
   *
   * @param int $user_id The ID of the user.
   * @param string|null $time_period The time period to check submissions for. If null, defaults to a predefined period.
   * @return bool|null True if the user has made submissions within the specified time period, false otherwise. Null if an error occurred.
   */
  function check_user_submissions_in_time_period($user_id, $time_period = null)
  {
    $result = $this->get_user_submissions_in_time_period($user_id, $time_period);

    $pta_number_of_submissions_per_time_period = get_option('pta_number_of_submissions_per_time_period', 1);
    if(!is_numeric($pta_number_of_submissions_per_time_period)){
      $this->logger->error('Number of submissions per time period is not a number: ' . $pta_number_of_submissions_per_time_period);
      return null;
    }

    if ($result === false) {
      $this->logger->error('Error getting user submissions in time period');
      return null;
    }

    $this->logger->debug('Number of submissions: ' . count($result));

    if (count($result) > $pta_number_of_submissions_per_time_period) {
      return true;
    }

    return false;
  }

}
