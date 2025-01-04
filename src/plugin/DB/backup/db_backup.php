<?php
namespace PTA\DB\backup;
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
use PTA\logger\Log;
use PTA\interfaces\DB\DBHandlerInterface;
use PTA\interfaces\DB\BackupInterface;
use PTA\DB\backup\encryption;

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

  private $wpdb;

  private encryption $encryption_instance;

  public function __construct($wpdbIn)
  {
    $this->wpdb = $wpdbIn;

    $upload_dir = wp_upload_dir();
    $this->backup_dir = trailingslashit($upload_dir['basedir']) . 'portals_to_adventure-uploads/backups/';

    $this->log = new log('DB.Backup');

    $this->full_prefix = $this->wpdb->prefix . $this->wld_prefix;

    $this->encryption_instance = new encryption();

    //$this->log->info('Database backup class initialized');
  }

  public function init()
  {
    $this->log = $this->log->getLogger();

  }

  /**
   * Create a database backup for specific tables.
   *
   * @return string|false The SQL backup string or false on failure.
   */
  public function create_backup()
  {
    $this->log->debug('Creating database backup');
    //global $wpdb;

    $wpdb = $this->wpdb;

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

    $this->log->debug('Database backup created');

    return $backup;
  }

  /**
   * Save a database backup to a file.
   *
   * @param string $sql The SQL backup string.
   * @param bool $compress Whether to compress the backup file.
   * @return string|false The path to the backup file or false on failure.
   */
  public function save_backup($sql, $compress = false, $encryption = false)
  {
    $this->log->debug('Saving database backup to file');
    $backup_file = $this->backup_dir . 'backup-' . date('Y-m-d-H-i-s');

    // Check if the backup directory exists
    if (!file_exists($this->backup_dir)) {
      if (!wp_mkdir_p($this->backup_dir)) {
        //wp_die(__('Failed to create backup directory.', 'portals-to-adventure'));
        return false;
      }
      $this->log->info('Backup directory created');
    }

    if($compress && !$encryption){
      $backup_file .= '.gz';

      $gz = gzopen($backup_file, 'w9');
      if (!$gz) {
        $this->log->error("Failed to open file for compression and encryption: {$backup_file}");
        return false;
      }

      gzwrite($gz, $sql);
      gzclose($gz);

    }

    if($compress && $encryption){
      // Will not implement this part as encrypting a compressed file will not compress the data effectively
      $this->log->error('Cannot compress and encrypt backup file at the same time');
    }

    if(!$compress){
      if($encryption){
        $backup_file .= '.enc';
      } else {
        $backup_file .= '.sql';
      }

      $result = file_put_contents($backup_file, $sql);

      if ($result === false) {
        $this->log->error("Failed to write backup file normal: {$backup_file}");
        return false;
      }

    }

    $this->log->debug("Backup saved successfully at {$backup_file}");

    return $backup_file;

  }

  public function restore_backup($backup_file)
  {
    $this->log->debug('Restoring database backup from file', $backup_file);

    $temp_file = $backup_file['tmp_name'];
    $file_name = $backup_file['name'];


    if (!is_readable($temp_file)) {
      $this->log->error('Backup file is not readable');
      return false;
    }


    // if the backup file has a .gz extension, it is compressed
    // if the backup file has a .enc extension, it is encrypted
    // if the backup file has a .sql extension, it is normal

    // if file name ends with .gz, it is compressed
    $compression = false;
    if (substr($file_name, -3) === '.gz') {
      $compression = true;
    }

    $encryption = false;
    if (substr($file_name, -4) === '.enc') {
      $encryption = true;
    }

    if($compression && $encryption){
      $this->log->error('Cannot restore compressed and encrypted backup file');
      return false;
    }

    $this->log->debug('Compression: ' . $compression);
    $this->log->debug('Encryption: ' . $encryption);

    if($compression && !$encryption){
      $gz = gzopen($temp_file, 'r');
      if (!$gz) {
        $this->log->error("Failed to open compressed backup file:", $temp_file);
        return false;
      }

      $sql = '';
      while (!gzeof($gz)) {
        $sql .= gzread($gz, 4096);
      }

      gzclose($gz);

    }

    if($encryption && !$compression){
      $sql = file_get_contents($temp_file);

      $sql_decrypt = $this->encryption_instance->libsodium_decrypt($sql);
      if ($sql_decrypt === false) {
        $this->log->error('Failed to decrypt database backup');
        return false;
      }
      $sql = $sql_decrypt;
    }

    if(!$compression && !$encryption){
      $sql = file_get_contents($temp_file);
    }

    // save the file for now
    $restore_file = $this->backup_dir . 'restore-' . date('Y-m-d-H-i-s') . '.sql';
    $result = file_put_contents($restore_file, $sql);
    if($result === false){
      $this->log->error("Failed to write restore file: {$restore_file}");
      return false;
    }

    $this->log->info('Database backup restored successfully, saved at: ' . $restore_file);

    return true;
  }

  /**
   * Perform the backup process: create and save the backup.
   *
   * @return bool True on success, false on failure.
   */
  public function perform_backup($compression = false, $encryption = true)
  {
    $sql = $this->create_backup();
    if ($sql === false) {
      $this->log->error('Failed to create database backup');
      return false;
    }

    if($encryption){
      $sql_encrypt = $this->encrypt_backup($sql);
      if ($sql_encrypt === false) {
        $this->log->error('Failed to encrypt database backup, normal backup will be saved');
        $encryption = false;
      }
      $sql = $sql_encrypt;
    }

    $backup = $this->save_backup($sql, $compression, $encryption);
    if ($backup === false) {
      $this->log->error('Failed to save database backup');
      return false;
    }

    $this->log->info('Database backup process completed successfully');

    return true;
  }

  public function encrypt_backup($sql, $encryption_method = 'libsodium')
  {
    switch($encryption_method){
      case 'libsodium':
        return $this->encryption_instance->libsodium_encrypt($sql);

      default:
        $this->log->error('Invalid encryption method specified');
        return false;
    }
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
