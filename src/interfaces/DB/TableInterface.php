<?php
namespace PTA\interfaces\DB;
/*
File: TableInterface.php
Description: Table interface for the plugin.
Author: Rowan Wachtler
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

use PTA\interfaces\DB\DBHandlerInterface;

interface TableInterface
{

  /**
   * Constructs the table interface. 
   * 
   * @param DBHandlerInterface $handler_instance An instance implementing the DBHandlerInterface for database interactions.
   * @param mixed              $wpdb             The WordPress database object for executing database operations.
   */
  public function __construct(DBHandlerInterface $handler_instance, $wpdb);

  /**
   * Get the name of the table.
   *
   * @return string The name of the table.
   */
  public function get_table_name();

  /**
   * Retrieves the path of the table.
   *
   * @return string The path of the table.
   */
  public function get_table_path();

  /**
   * Retrieves the schema for the table.
   *
   * This method returns the SQL schema definition for the table,
   * which includes the table structure, columns, data types, and any constraints.
   *
   * @return string The SQL schema definition for the table.
   */
  public function get_table_schema();

  /**
   * Creates the user info table in the database.
   *
   * This method is responsible for creating the necessary table
   * in the database
   *
   * @return bool True if the table was created, false otherwise.
   */
  public function create_table();

  /**
   * Upgrade the Table to the latest schema.
   *
   * This method handles the necessary changes to update the table structure
   * to match the latest version requirements. It ensures that any new columns,
   * indexes, or other modifications are applied correctly.
   *
   * @return bool True if the table was upgraded, false otherwise.
   */
  public function upgrade_table();
}