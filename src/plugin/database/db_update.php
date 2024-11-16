<?php
namespace PTA\DB;
/*
File: db-backups.php
Description: Database backup functions for the plugin.
Author: Rowan Wachtler
Created: 10-12-2024
Version: 1.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Requires
require_once PTA_PLUGIN_DIR . 'pta-logger.php';


class db_update
{
  private $logger;
  private $handler_instance;

  /**
   * Constructor for the class. Initializes the logger and checks for updates.
   */
  public function __construct($handler_instance)
  {
    $this->logger = createLogger('DB.Update');
    $this->handler_instance = $handler_instance;

    add_action('plugins_loaded', [$this, 'after_plugin_load']);
  }

  public function after_plugin_load()
  {
    $IfUpdate = $this->check_for_updates();
    if ($IfUpdate) {

      $this->logger->info('New database updates found');

      if ($this->backup_db()) {
        $this->logger->debug('Database backup created successfully');

        $this->update_db();

      }

    }
  }

  /**
   * Creates a backup of the current database.
   *
   * This method is responsible for creating a backup of the database before any updates are applied.
   * It ensures that there is a restore point in case something goes wrong during the update process.
   *
   * @return bool True if the backup was successful, false otherwise.
   */
  private function backup_db()
  {
    return $this->handler_instance->db_backup->perform_backup();
  }

  private function update_db()
  {
    global $wpdb;
    $current_table_schemas = $this->handler_instance->get_table('all');

    foreach ($current_table_schemas as $table_name) {
      $this->logger->debug('Checking table: ' . $table_name);

      $result = $table_name->update_table();

      if ($result === false) {
        $this->logger->error("Failed to update table: {$table_name}");
      } else {
        $this->logger->info("Table {$table_name} updated successfully");
      }
    }

    $this->logger->info('Database updated');

    update_option('wld_pta_db_version', $this->handler_instance->get_db_version_local());

  }

  /**
   * Checks for updates in the database.
   *
   * This function is responsible for verifying if there are any updates
   * that need to be applied to the database schema or data.
   *
   * @return bool True if updates are required, false otherwise.
   */
  private function check_for_updates()
  {
    $db_version = $this->handler_instance->get_db_version_local();

    $pta_db_version = $this->handler_instance->get_db_version_wp();

    if (version_compare($db_version, $pta_db_version, '>')) {
      return true;
    }

    return false;
  }

}