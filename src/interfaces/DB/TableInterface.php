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

interface TableInterface
{
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
   * @return string The SQL schema definition for the table.
   */
  public function get_table_schema();

  /**
   * Creates the table in the database.
   *
   * @return bool True if the table was created, false otherwise.
   */
  public function create_table();

  /**
   * Upgrade the table to the latest schema.
   *
   * @return bool True if the table was upgraded, false otherwise.
   */
  public function upgrade_table();
}