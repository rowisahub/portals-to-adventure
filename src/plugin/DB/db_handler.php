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
//use PTA\DB\backup\db_backup;
use PTA\DB\functions\db_functions;

use PTA\DB\Tables\UserInfoTable;
use PTA\DB\Tables\SubmissionDataTable;
use PTA\DB\Tables\ImageDataTable;

use PTA\logger\Log;

// Class
class db_handler implements DBHandlerInterface
{
  // Instances
  private $update;
  private $backup;
  private $functions;

  // Database version
  private $db_version = '1.0.3';

  // Tables
  private $wld_prefix = 'wld_pta_';
  private $db_tables = [];

  // Table schemas
  private $user_info_table;
  private $submission_data_table;
  private $image_data_table;

  // Logger
  private $logger;

  // wpdb
  private \wpdb $wpdb;


  public function __construct(
    Log $logger = null,
    db_update $update = null,
    //db_backup $backup = null,
    db_functions $functions = null,
    UserInfoTable $userInfoTable = null,
    SubmissionDataTable $submissionDataTable = null,
    ImageDataTable $imageDataTable = null,
    \wpdb $wpdbIn = null
  ) {
    global $wpdb;
    $this->wpdb = $wpdbIn ?? $wpdb;

    $this->user_info_table = $userInfoTable ?? new UserInfoTable($this, $this->wpdb);
    $this->submission_data_table = $submissionDataTable ?? new SubmissionDataTable($this, $this->wpdb);
    $this->image_data_table = $imageDataTable ?? new ImageDataTable($this, $this->wpdb);

    $this->db_tables = [
      $this->user_info_table,
      $this->submission_data_table,
      $this->image_data_table
    ];

    $this->logger = $logger ?? new log(name: 'DB.Handler');
    $this->logger = $this->logger->getLogger();

    $this->update = $update ?? new db_update($this);
    //$this->backup = $backup ?? new db_backup($this->wpdb);
    $this->functions = $functions ?? new db_functions();
  }

  public function init()
  {
    //$this->register_activation();
    //$this->logger = $this->logger->getLogger();

    $this->update->init();
    $this->backup->init();
    $this->functions->init(handler_instance: $this, wpdbIn: $this->wpdb);
  }

  public function register_activation($plugin_file)
  {
    //$this->logger->info('Registering activation hook');

    register_activation_hook($plugin_file, [$this, 'plugin_activation']);
  }

  public function get_instance($name)
  {
    switch ($name) {
      case 'update':
        return $this->update;
      case 'backup':
        return $this->backup;
      case 'functions':
        return $this->functions;
      default:
        return null;
    }
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
    $this->logger->info('Plugin activated, running activation hook');

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

  /**
   * Retrieves the prefix used for the Portals to Adventure (PTA) database tables.
   *
   * @return string The prefix for the PTA database tables.
   */
  public function get_pta_prefix()
  {
    return $this->wld_prefix;
  }

  /**
   * Retrieves the WordPress database object.
   *
   * @return \wpdb The WordPress database object.
   */
  public function get_WPDB()
  {
    return $this->wpdb;
  }

  public function set_functions($name, $function_instance)
  {
    switch ($name) {
      case 'update':
        $this->update = $function_instance;
        break;
      case 'backup':
        $this->backup = $function_instance;
        break;
      case 'functions':
        $this->functions = $function_instance;
        break;
      default:
        return null;
    }
  }
}