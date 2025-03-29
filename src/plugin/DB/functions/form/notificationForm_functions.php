<?php
namespace PTA\DB\functions\form;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\QueryBuilder;
use PTA\logger\Log;

class notificationForm_functions
{
  private $table_path;
  private $db_functions;
  private $handler_instance;
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

    $this->table_path = $this->handler_instance->get_table_path('form_notification');

    $this->logger = new Log('Contact Form Functions');
    $this->logger = $this->logger->getLogger();
  }

  public function add_completed_form($form_id, $user_id, $email, $name = ""){
    $uuid = $this->db_functions->generate_uuid('form_notification');

    $data = [
      'id' => $uuid,
      'form_id' => $form_id,
      'user_id' => $user_id,
      'email' => $email,
      'name' => $name,
    ];

    $this->wpdb->insert($this->table_path, $data);
  }

  public function get_form_notifications($form_id = null, $user_id = null)
  {
    $queryBuilder = new QueryBuilder($this->wpdb);
    $queryBuilder->select('*')->from($this->table_path);

    if ($form_id) {
      $queryBuilder->where([
        'form_id' => $form_id,
      ]);
    }

    if ($user_id) {
      $queryBuilder->where([
        'user_id' => $user_id,
      ]);
    }

    return $queryBuilder->get();
  }

}