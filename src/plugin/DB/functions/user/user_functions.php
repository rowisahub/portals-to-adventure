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

class user_functions {

  private $table_path;
  private $handler_instance;
  private $db_functions;
  private $logger;
  private \wpdb $wpdb;

  public function __construct(db_handler $handler_instance = null, db_functions $db_functions = null) {
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

  function register_user($email, $username, $firstName = null, $lastName = null, $verified_email = 0, $token = '', $birthday = null, $permissions = '', $payment_info = null) {

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

  function get_user_permissions($user_id, $perm = null) {
    $queryBuilder = new QueryBuilder(wpdb: $this->wpdb);
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['id' => $user_id]);

    $result = $this->db_functions->exe_from_builder($queryBuilder);

    if ($perm != null) {
      return $result['permissions'][$perm];
    }

    return $result['permissions'];
  }

  function get_user_by_id($user_id) {
    $queryBuilder = new QueryBuilder(wpdb: $this->wpdb);
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['id' => $user_id]);

    return $this->db_functions->exe_from_builder(query_builder: $queryBuilder);
  }

  function check_user_exists($user_id) {
    $result = $this->get_user_by_id($user_id);

    if (!$result) {
      return false;
    }

    return true;
  }

  function remove_user($user_id) {
    $this->wpdb->delete($this->table_path, ['id' => $user_id]);
    // remove user from WordPress
    wp_delete_user($user_id);
  }
}
