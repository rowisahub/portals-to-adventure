<?php
namespace PTA\DB\backup;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\logger\Log;

class encryption
{
  private $log;
  private $key;

  public function __construct()
  {
    $this->log = new Log('DB.Encryption');

    $this->key = defined('PTA_BACKUP_ENCRYPTION_KEY') ? base64_decode(PTA_BACKUP_ENCRYPTION_KEY) : null;

    // show wordpress admin notice if encryption key is not set
    if (empty($this->key)) {
      //add_action('admin_notices', array($this, 'encryption_key_missing_notice'));
    }
  }

  public function init()
  {
    $this->log = $this->log->getLogger();
  }

  public function encryption_key_missing_notice()
  {
    $this->log->error('Encryption key is missing');
    ?>
    <div class="notice notice-error is-dismissible">
      <p><?php _e('Portals to Adventure: Database backup encryption key is missing. Please set the key in wp-config.php.', 'portals-to-adventure'); ?></p>
    </div>
    <?php
  }

  private function check_key()
  {
    if (empty($this->key)) {
      $this->log->error('Encryption key is missing or invalid');

      return false;
    }
    return true;
  }

  public function encrypt_backup($sql, $encryption_method = 'libsodium')
  {
    switch($encryption_method){
      case 'libsodium':
        return $this->libsodium_encrypt($sql);

      default:
        $this->log->error('Invalid encryption method specified');
        return false;
    }
  }

  public function decrypt_backup($data, $encryption_method = 'libsodium')
  {
    switch($encryption_method){
      case 'libsodium':
        return $this->libsodium_decrypt($data);

      default:
        $this->log->error('Invalid encryption method specified');
        return false;
    }
  }

  public function libsodium_encrypt($data)
  {
    $this->log->debug('Encrypting data with libsodium');

    if (!$this->check_key()) {
        $this->log->error('Encryption key is missing or invalid');
        return false;
    }

    if (extension_loaded('sodium')) {

      $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

      $encrypted = sodium_crypto_secretbox($data, $nonce, $this->key);

      if($encrypted === false) {
        $this->log->error('Failed to encrypt data');
        return false;
      }

      $this->log->debug('Encrypted data');

      return base64_encode($nonce . $encrypted);

    } else {
      $this->log->error('Libsodium extension not loaded');
      return false;
    }
  }

  public function libsodium_decrypt($data)
  {
    $this->log->debug('Decrypting data with libsodium');

    if (!$this->check_key()) {
        $this->log->error('Encryption key is missing or invalid');
        return false;
    }

    if (extension_loaded('sodium')) {

      $data = base64_decode($data);

      // $this->log->debug('Data length: ' . strlen($data));

      $nonce = mb_substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
      $ciphertext = mb_substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

      // $this->log->debug('Nonce length: ' . strlen($nonce));
      // $this->log->debug('Ciphertext length: ' . strlen($ciphertext));

      $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);

      if($decrypted === false) {
        $this->log->error('Failed to decrypt data');
        return false;
      }

      return $decrypted;

    } else {
      $this->log->error('Libsodium extension not loaded');
      return false;
    }
  }

  public function openssl_encrypt($data){
    $this->log->debug('Encrypting data with OpenSSL');

    if (!$this->check_key()) {
        $this->log->error('Encryption key is missing or invalid');
        return false;
    }

    $ivlen = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivlen);

    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);

    if($encrypted === false) {
      $this->log->error('Failed to encrypt data');
      return false;
    }
  }
}