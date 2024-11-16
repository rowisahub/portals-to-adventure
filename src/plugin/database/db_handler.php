<?php
namespace PTA\DB;
/*
File: db-handler.php
Description: Database handler for the plugin.
Author: Rowan Wachtler
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Requires
use PTA\interfaces\DB\DBHandlerInterface;

use PTA\DB\db_update;
use PTA\DB\db_backup;
use PTA\DB\db_functions;

use PTA\DB\Tables\UserInfoTable;
use PTA\DB\Tables\SubmissionDataTable;
use PTA\DB\Tables\ImageDataTable;

// Class
class db_handler implements DBHandlerInterface
{
  // Instances
  private $update;
  private $backup;
  private $functions;

  // Database version
  private $db_version = '1.0';

  // Tables
  private $wld_prefix = 'wld_pta_';
  private $db_tables = [];

  // Table schemas
  private $user_info_table;
  private $submission_data_table;
  private $image_data_table;

  // Logger
  private $logger;

  // Plugin file
  private $PTA_Plugin_File;


  public function __construct($PTA_Plugin_File)
  {
    $this->define_tables();
    $this->PTA_Plugin_File = $PTA_Plugin_File;

    $this->logger = createLogger('DB.Handler');

    $this->update = new db_update($this);
    $this->backup = new db_backup($this);
    $this->functions = new db_functions($this);

    //register_activation_hook(PTA_PLUGIN_FILE, [$this, 'install']);
    register_activation_hook(PTA_PLUGIN_DIR, [$this, 'plugin_activation']);
  }

  public function register_activation()
  {
    register_activation_hook($this->PTA_Plugin_File, [$this, 'plugin_activation']);
  }

  public function get_table_path($table_name)
  {
    switch ($table_name) {
      case 'user_info':
        return $this->user_info_table->get_table_path();
      case 'submission_data':
        return $this->submission_data_table->get_table_path();
      case 'image_data':
        return $this->image_data_table->get_table_path();
      default:
        return null;
    }
  }

  public function get_table($table_name)
  {
    switch ($table_name) {
      case 'user_info':
        return $this->user_info_table;
      case 'submission_data':
        return $this->submission_data_table;
      case 'image_data':
        return $this->image_data_table;
      case 'all':
        return $this->db_tables;
      default:
        return null;
    }
  }

  public function plugin_activation()
  {
    $ifSuccess = false;
    foreach ($this->db_tables as $table) {
      $res = $table->create_table();
      if ($res) {
        $ifSuccess = true;
      } else {
        $ifSuccess = false;
        break;
      }
    }

    if ($ifSuccess) {
      $this->logger->info('Tables created successfully');
    } else {
      $this->logger->error('Failed to create tables');
    }

  }

  /**
   * Retrieves the current version of the WordPress database.
   *
   * @return string The version of the WordPress database.
   */
  public function get_db_version_wp()
  {

    $db_version_int = get_option('wld_pta_db_version');

    if (!$db_version_int) {
      update_option('wld_pta_db_version', $this->db_version);
      $db_version_int = $this->db_version;
    }

    return $db_version_int;
  }

  /**
   * Retrieves the local database version.
   *
   * @return string The local database version.
   */
  public function get_db_version_local()
  {
    return $this->db_version;
  }

  public function get_pta_prefix()
  {
    return $this->wld_prefix;
  }

  /**
   * Defines the database tables used by the plugin.
   *
   * This method sets up the necessary database tables for the plugin to function properly.
   * It ensures that the required tables are created and available for use.
   *
   * @return void
   */
  private function define_tables()
  {
    // Load user_info, submission_data, and image_data table in this order
    $this->user_info_table = new UserInfoTable($this);
    $this->submission_data_table = new SubmissionDataTable($this);
    $this->image_data_table = new ImageDataTable($this);

    $this->db_tables = [
      $this->user_info_table,
      $this->submission_data_table,
      $this->image_data_table
    ];
  }

}