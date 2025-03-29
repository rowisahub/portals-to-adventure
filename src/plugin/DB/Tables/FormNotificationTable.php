<?php
namespace PTA\DB\Tables;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

use PTA\interfaces\DB\TableInterface;
use PTA\interfaces\DB\DBHandlerInterface;
use PTA\logger\Log;

class FormNotificationTable implements TableInterface
{
  /**
   * Generates the schema for the FormNotificationTable.
   *
   * This method defines the structure of the form notification data table,
   * including columns, data types, and constraints.
   */
  private function table_schema()
  {
    // Form notification data table
    $sql_form_notification_data = "CREATE TABLE $this->table_path (
      id varchar(255) NOT NULL,
      form_id varchar(255) NOT NULL,
      user_id varchar(255) NOT NULL,
      email varchar(255) NOT NULL,
      name varchar(255) NOT NULL,
      created_at timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id),
      KEY form_id (form_id),
      KEY user_id (user_id)
    ) $this->charset_collate;";

    $this->table_schema = $sql_form_notification_data;
  }

  private $logger;
  private $wpdb;
  private $table_name = 'form_notification_data';
  private $table_path;
  private $table_schema;
  private $charset_collate;
  private $handler_instance;
  private $pta_prefix;

  public function __construct(DBHandlerInterface $handler_instance, $wpdb)
  {
    $this->wpdb = $wpdb;

    $this->logger = new log('DB.FormNotificationTable');
    $this->logger = $this->logger->getLogger();

    $this->handler_instance = $handler_instance;

    $this->pta_prefix = $handler_instance->get_pta_prefix();

    $this->table_path = $wpdb->prefix . $this->pta_prefix . $this->table_name;
    $this->charset_collate = $wpdb->get_charset_collate();

    $this->table_schema();
  }

  /**
   * Get the name of the table.
   *
   * @return string The name of the table.
   */
  public function get_table_name()
  {
    return $this->table_name;
  }

  /**
   * Retrieves the path of the table.
   *
   * @return string The path of the table.
   */
  public function get_table_path()
  {
    return $this->table_path;
  }

  /**
   * Retrieves the schema of the table.
   *
   * @return string The schema of the table.
   */
  public function get_table_schema()
  {
    return $this->table_schema;
  }
  
  public function create_table()
  {
    if ($this->wpdb->get_var("SHOW TABLES LIKE '$this->table_path'") != $this->table_path) {
      dbDelta($this->table_schema);
      $this->logger->info('Form notification data table created successfully.');
      return true;
    } else {
      $this->logger->info('Form notification data table already exists.');
      return false;
    }

    if (!empty($this->wpdb->last_error)) {
      $this->logger->error($this->wpdb->last_error);
      return false;
    }
  }

  public function upgrade_table()
  {
    $result = dbDelta($this->table_schema);

    if ($this->wpdb->last_error) {
      $this->logger->error("Error upgrading {$this->table_name} table: " . $this->wpdb->last_error);
      return false;
    }

    if (!empty($result)) {
      $this->logger->info("Changes made to {$this->table_name} table: " . print_r($result, true));
    } else {
        $this->logger->debug("No changes required for {$this->table_name} table");
    }

    return true;
  }

}