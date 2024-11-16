<?php
namespace PTA\interfaces\DB;

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

interface UpdateInterface
{
  /**
   * Constructor for the class. Initializes the logger and checks for updates.
   *
   * @param DBHandlerInterface $handler_instance The database handler instance.
   */
  public function __construct(DBHandlerInterface $handler_instance);

  /**
   * Initializes the logger and registers hooks.
   *
   * @return void
   */
  public function init();

  /**
   * Registers hooks for the plugin.
   *
   * @return void
   */
  public function register_hooks();

  /**
   * Callback function for the 'plugins_loaded' action.
   *
   * @return void
   */
  public function after_plugin_load();

  /**
   * Creates a backup of the current database.
   *
   * This method is responsible for creating a backup of the database before any updates are applied.
   * It ensures that there is a restore point in case something goes wrong during the update process.
   *
   * @return bool True if the backup was successful, false otherwise.
   */
  private function backup_db();

  /**
   * Updates the database schema and data.
   *
   * This method is responsible for updating the database schema and data.
   * It iterates through all the tables and applies necessary updates.
   *
   * @return void
   */
  private function update_db();

  /**
   * Checks for updates in the database.
   *
   * This function is responsible for verifying if there are any updates
   * that need to be applied to the database schema or data.
   *
   * @return bool True if updates are required, false otherwise.
   */
  private function check_for_updates();
}