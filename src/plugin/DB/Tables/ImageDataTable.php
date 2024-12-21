<?php
namespace PTA\DB\Tables;
/*
File: ImageDataTable.php
Description: Image data table for the plugin.
Author: Rowan Wachtler
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

use PTA\interfaces\DB\TableInterface;
use PTA\interfaces\DB\DBHandlerInterface;
use PTA\logger\Log;

class ImageDataTable implements TableInterface
{

  /**
   * Generates the schema for the ImageDataTable.
   */
  private function table_schema()
  {

    $user_info_path = $this->handler_instance->get_table_path('user_info');
    $submission_data_path = $this->handler_instance->get_table_path('submission_data');

    // Image data table
    $sql_image_data = "CREATE TABLE $this->table_path (
            image_id varchar(255) NOT NULL,
            user_id varchar(255) NOT NULL,
            submission_id varchar(255) NOT NULL,
            image_reference varchar(255) NOT NULL,
            is_thumbnail tinyint(1) DEFAULT 0,
            is_map tinyint(1) DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (image_id),
            FOREIGN KEY  (user_id) REFERENCES $user_info_path(id) ON DELETE CASCADE,
            FOREIGN KEY  (submission_id) REFERENCES $submission_data_path(id) ON DELETE CASCADE
        ) $this->charset_collate;";

    $this->table_schema = $sql_image_data;

  }

  private $logger;
  private $wpdb;
  private $table_name = 'image_data';
  private $table_path;
  private $table_schema;
  private $charset_collate;
  private $handler_instance;
  private $pta_prefix;
  private $table_requirements = [
    'user_info',
    'submission_data'
  ];

  public function __construct(DBHandlerInterface $handler_instance, $wpdb)
  {
    $this->wpdb = $wpdb;

    $this->logger = new log('DB.Tables.ImageDataTable');
    $this->logger = $this->logger->getLogger();
    
    $this->handler_instance = $handler_instance;

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
   * Retrieves the schema for the image data table.
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
    $this->logger->debug('Upgrading image data table');

    $result = dbDelta($this->table_schema);

    if (!empty($this->wpdb->last_error)) {
      $this->logger->error($this->wpdb->last_error);
      return false;
    }

    $this->logger->info('Image data table upgraded');

    return true;
  }
}