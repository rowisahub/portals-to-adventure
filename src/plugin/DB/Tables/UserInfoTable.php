<?php
namespace PTA\DB\Tables;
/*
File: UserInfoTable.php
Description: User info table for the plugin.
Author: Rowan Wachtler
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

use PTA\interfaces\DB\TableInterface;
use PTA\interfaces\DB\DBHandlerInterface;
use PTA\logger\Log;

class UserInfoTable implements TableInterface
{

  /**
   * Generates the schema for the ImageDataTable.
   */
  private function table_schema()
  {
    // User info table
    $sql_user_info = "CREATE TABLE $this->table_path (
        id varchar(255) NOT NULL,
        token varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        username varchar(255) NOT NULL,
        birthday date DEFAULT NULL,
        permissions varchar(4) DEFAULT '0000', -- 4-bit binary string for permissions (permissions to send email, is admin, can review submissions, is banned)
        payment_info text DEFAULT NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
      ) $this->charset_collate;";

    $this->table_schema = $sql_user_info;

  }

  private $logger;
  private $wpdb;
  private $table_name = 'user_info';
  private $table_path;
  private $table_schema;
  private $charset_collate;
  private $handler_instance;
  private $pta_prefix;
  private $table_requirements;

  public function __construct(DBHandlerInterface $handler_instance, $wpdb)
  {
    $this->wpdb = $wpdb;
    
    $this->logger =
      $this->handler_instance = $handler_instance;

    $this->logger = new log('DB.Tables.UserInfoTable');
    $this->logger = $this->logger->getLogger();

    $this->pta_prefix = $handler_instance->get_pta_prefix();

    $this->table_path = $wpdb->prefix . $this->pta_prefix . $this->table_name;
    $this->charset_collate = $wpdb->get_charset_collate();

    $this->table_schema();

  }

  /**
   * Get the name of the user info table.
   *
   * @return string The name of the user info table.
   */
  public function get_table_name()
  {
    return $this->table_name;
  }

  /**
   * Retrieves the path of the user information table.
   *
   * @return string The path of the user information table.
   */
  public function get_table_path()
  {
    return $this->table_path;
  }

  /**
   * Retrieves the schema for the user information table.
   *
   * This method returns the SQL schema definition for the user information table,
   * which includes the table structure, columns, data types, and any constraints.
   *
   * @return string The SQL schema definition for the user information table.
   */
  public function get_table_schema()
  {
    return $this->table_schema;
  }

  /**
   * Creates the user info table in the database.
   *
   * This method is responsible for creating the necessary table
   * in the database to store user information.
   *
   * @return bool True if the table was created, false otherwise.
   */
  public function create_table()
  {
    // Check if the table exists
    if ($this->wpdb->get_var("SHOW TABLES LIKE '$this->table_path'") != $this->table_path) {

      dbDelta($this->table_schema);

      $this->logger->debug('User info table created');

      return true;
    } else {
      $this->logger->debug('User info table already exists');
      return true;
    }

    // check for errors
    if (!empty($this->wpdb->last_error)) {
      $this->logger->error($this->wpdb->last_error);
      return false;
    }
  }

  /**
   * Upgrade the ImageDataTable to the latest schema.
   *
   * This method handles the necessary changes to update the table structure
   * to match the latest version requirements. It ensures that any new columns,
   * indexes, or other modifications are applied correctly.
   *
   * @return bool True if the table was upgraded, false otherwise.
   */
  public function upgrade_table()
  {
    //$this->logger->debug('Upgrading image data table');

    // Store original error counts
    $initial_errors = $this->wpdb->num_queries ? count($this->wpdb->queries_errors) : 0;

    $result = dbDelta($this->table_schema);

    // Check for new errors
    if ($this->wpdb->last_error) {
      $this->logger->error("Error upgrading {$this->table_name} table: " . $this->wpdb->last_error);
      return false;
    }


    // Log changes if any were made
    if (!empty($result)) {
      $this->logger->info("Changes made to {$this->table_name} table: " . print_r($result, true));
    } else {
        $this->logger->debug("No changes required for {$this->table_name} table");
    }

    //$this->logger->info('Image data table upgraded');

    return true;
  }
}