<?php
namespace PTA\Interfaces\DB;
/*
File: DBHandlerInterface.php
Description: Table interface for the plugin.
Author: Rowan Wachtler
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

interface DBHandlerInterface
{
  /**
   * Retrieves the path of the table.
   *
   * @param string $table_name The name of the table.
   * @return string The path of the table.
   */
  public function get_table_path($table_name);

  /**
   * Retrieve the specified table from the database.
   *
   * @param string $table_name The name of the table to retrieve.
   * @return mixed The table data.
   */
  public function get_table($table_name);

  /**
   * Method to handle actions required during the plugin activation.
   *
   * This method is called when the plugin is activated. It should contain
   * any setup or initialization code required for the plugin to function
   * properly.
   *
   * @return void
   */
  public function plugin_activation();

  /**
   * Retrieves the current version of the WordPress database.
   *
   * @return string The version of the WordPress database.
   */
  public function get_db_version_wp();

  /**
   * Retrieves the local database version.
   *
   * @return string The local database version.
   */
  public function get_db_version_local();

  /**
   * Retrieve the prefix used for PTA (Parent Teacher Association) related database tables.
   *
   * @return string The prefix used for PTA related database tables.
   */
  public function get_pta_prefix();


}