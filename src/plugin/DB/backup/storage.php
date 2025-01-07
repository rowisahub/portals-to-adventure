<?php
namespace PTA\DB\backup;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\logger\Log;

class storage
{
  private $log;
  private $backup_dir;

  public function __construct()
  {
    $upload_dir = wp_upload_dir();
    $this->backup_dir = trailingslashit($upload_dir['basedir']) . 'portals_to_adventure-uploads/backups/';

    $this->log = new Log('DB.Storage');

  }

  public function init()
  {
    $this->log = $this->log->getLogger();
  }

  public function store_backup($backupData, $dataHash, $compressed = false, $encrypted = false)
  {
    $this->log->debug('Storing backup data');
    $backup_file = $this->backup_dir . 'backup-' . date('Y-m-d-H-i-s');

    // Check if the backup directory exists
    if (!file_exists($this->backup_dir)) {
      if (!wp_mkdir_p($this->backup_dir)) {
        //wp_die(__('Failed to create backup directory.', 'portals-to-adventure'));
        return false;
      }
      $this->log->debug('Backup directory created');
    }

    $this->hide_backup_dir();

    if($compressed) {
      $backup_file .= '.gz';
    } elseif($encrypted) {
      $backup_file .= '.enc';
    } else {
      $backup_file .= '.sql';
    }

    // Save locally
    $save_result = $this->save_local($backup_file, $backupData, $dataHash, $compressed);

    if (!$save_result) {
      return false;
    } else {
      $this->log->debug('Local backup stored successfully');
    }
  }

  private function save_local($backup_file, $backupData, $dataHash, $compressed = false)
  {
    if($compressed){
      $gz = gzopen($backup_file, 'w9');
      if (!$gz) {
        $this->log->error("Failed to open file for compression: {$backup_file}");
        return false;
      }

      gzwrite($gz, $backupData);
      gzclose($gz);

    } else {

      $file_result = file_put_contents($backup_file, $backupData);

      if ($file_result === false) {
        $this->log->error("Failed to write backup file: {$backup_file}");
        return false;
      }
    }

    // Save hash
    $hash_file = $backup_file . '.hash';
    $hash_result = file_put_contents($hash_file, $dataHash);
    if ($hash_result === false) {
      $this->log->error("Failed to write hash file: {$hash_file}");
      return false;
    } else {
      $this->log->debug("Hash saved successfully at {$hash_file}");
    }


    $this->log->debug("Backup saved successfully at {$backup_file}");

    return true;
  }

  private function hide_backup_dir()
  {
    $htaccess = $this->backup_dir . '.htaccess';
    $index = $this->backup_dir . 'index.html';
    $stored_path = plugin_dir_path(__FILE__) . '../../../../assets/admin/';

    if (!file_exists($htaccess)) {
      $this->log->debug('Creating .htaccess file in backup directory');
      $htaccess_content = file_get_contents($stored_path . '.htaccess');
      file_put_contents($htaccess, $htaccess_content);
    }

    if (!file_exists($index)) {
      $this->log->debug('Creating index.html file in backup directory');
      $index_content = file_get_contents($stored_path . 'index.html');
      file_put_contents($index, $index_content);
    }
  }
}