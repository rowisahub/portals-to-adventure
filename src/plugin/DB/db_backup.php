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
use PTA\logger\log;
use PTA\interfaces\DB\DBHandlerInterface;
use PTA\interfaces\DB\BackupInterface;

class db_backup implements BackupInterface
{
  private $backup_dir;
  private $log;
  private $wld_prefix = 'wld_pta_';
  private $full_prefix;
  private $tables_to_backup = [
    'user_info',
    'submission_data',
    'image_data'
  ];

  public function __construct()
  {
    global $wpdb;
    $upload_dir = wp_upload_dir();
    $this->backup_dir = trailingslashit($upload_dir['basedir']) . 'pta/backups/';
    
    $this->log = new log('DB.Backup');

    $this->full_prefix = $wpdb->prefix . $this->wld_prefix;

    //$this->log->info('Database backup class initialized');
  }

  public function init()
  {
    $this->log = $this->log->getLogger();

    // Check if the backup directory exists
    if (!file_exists($this->backup_dir)) {
      if (!wp_mkdir_p($this->backup_dir)) {
        wp_die(__('Failed to create backup directory.', 'pta-plugin'));
      }
      $this->log->info('Backup directory created');
    }
  }

  /**
   * Create a database backup for specific tables.
   *
   * @return string|false The SQL backup string or false on failure.
   */
  public function create_backup()
  {
    $this->log->info('Creating database backup');
    global $wpdb;

    $backup = '';
    foreach ($this->tables_to_backup as $table) {
      $table_name = $this->full_prefix . $table;

      // check if the table exists
      if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
        $this->log->error("Table {$table_name} does not exist");
        continue;
      }

      // Get the CREATE TABLE statement to recreate the table
      $create_table = $wpdb->get_row("SHOW CREATE TABLE {$table_name}", ARRAY_N);
      if ($create_table && isset($create_table[1])) {
        $backup .= $create_table[1] . ";\n\n";
      } else {
        $this->log->error("Failed to retrieve CREATE statement for {$table_name}.");
        continue;
      }

      // Get the table data
      $rows = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_N);
      if (!empty($rows)) {
        foreach ($rows as $row) {
          $columns = array_map(function ($col) use ($wpdb) {
            return '`' . esc_sql($col) . '`';
          }, array_keys($row));

          $values = array_map(function ($val) use ($wpdb) {
            if (is_null($val)) {
              return 'NULL';
            }
            return "'" . esc_sql($val) . "'";
          }, array_values($row));

          $backup .= "INSERT INTO {$table_name} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        $backup .= "\n";
      }
    }

    $this->log->info('Database backup created');

    return $backup;
  }

  /**
   * Save a database backup to a file.
   *
   * @param string $sql The SQL backup string.
   * @param bool $compress Whether to compress the backup file.
   * @return string|false The path to the backup file or false on failure.
   */
  public function save_backup($sql, $compress = false)
  {
    $this->log->info('Saving database backup to file');
    $backup_file = $this->backup_dir . 'backup-' . date('Y-m-d-H-i-s') . '.sql';

    if (!$compress) {

      $result = file_put_contents($backup_file, $sql);

      if ($result === false) {
        $this->log->error("Failed to write backup file: {$backup_file}");
        return false;
      }

    } else {

      $backup_file .= '.gz';

      $gz = gzopen($backup_file, 'w9');
      if (!$gz) {
        $this->log->error("Failed to open file for writing: {$backup_file}");
        return false;
      }

      gzwrite($gz, $sql);
      gzclose($gz);

    }

    $this->log->info("Backup saved successfully at {$backup_file}");

    return $backup_file;

  }

  /**
   * Perform the backup process: create and save the backup.
   *
   * @return bool True on success, false on failure.
   */
  public function perform_backup($compression = false)
  {
    $sql = $this->create_backup();
    if ($sql === false) {
      $this->log->error('Failed to create database backup');
      return false;
    }

    $backup = $this->save_backup($sql, $compression);
    if ($backup === false) {
      $this->log->error('Failed to save database backup');
      return false;
    }

    return true;
  }

}

// Instantiate the backup class
//new pta_db_backup();

// check options for How often to backup, what to backup (users, submissions, images), and to check if there are external places to backup to
/*
Full backup command:
$backup_file = $this->backup_dir . 'backup-' . date('Y-m-d-H-i-s') . '.sql';
$command = 'mysqldump --user=' . DB_USER . ' --password=' . DB_PASSWORD . ' --host=' . DB_HOST . ' ' . DB_NAME . ' > ' . $backup_file;
exec($command);
**/
