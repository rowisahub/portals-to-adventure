<?php
namespace PTA\logger;
/*
File: logger.php
Description: Logger for the plugin.
Author: Rowan Wachtler
*/

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\ErrorHandler;
use Monolog\Processor\IntrospectionProcessor;

use PTA\interfaces\logger\PTALogInterface;


// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Class

/**
 * Class log
 *
 * This class is responsible for handling logging functionality within the Portals to Adventure plugin.
 * It provides methods to log various events and messages for debugging and monitoring purposes.
 *
 * @package PortalsToAdventure
 * @subpackage Logger
 */
class Log implements PTALogInterface
{
  private static $initialized = [];
  private static $loggers = [];
  private Logger $logger;
  private $logPath;
  private $logDir;
  private $upload_dir;
  private $log_level;

  /**
   * Logger constructor.
   *
   * @param string $name The name of the logger.
   * @param string $path The path to the log file. Default is 'debug.log'.
   * @param int $level The logging level. Default is Logger::DEBUG.
   * @param bool $ifLogUncaught Whether to log uncaught exceptions. Default is false.
   */
  public function __construct($name = "PTA", $path = 'pta.log', $level = Logger::DEBUG, $ifLogUncaught = false)
  {
    $classname = static::class . $name;

    if (isset(self::$initialized[$classname]) && self::$initialized[$classname]) {
      $this->logger = self::$loggers[$classname];
      return;
    }

    $this->logger = new Logger($name);

    self::$loggers[$classname] = $this->logger;

    // Skip file system setup if running in test environment
    if (defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING) {
      // Set up a NullHandler to avoid file operations
      $this->logger->pushHandler(new \Monolog\Handler\NullHandler($level));
      return;
    }

    $this->log_level = $level;

    $evnLvl = get_option('pta_environment');
    if ($evnLvl == 'production') {
      $level = Logger::INFO;
      $ifLogUncaught = false;
    }

    // use upload directory for logs and add .htaccess file to prevent access
    $this->upload_dir = wp_upload_dir();

    $this->logPath = $this->upload_dir['basedir'] . "/portals_to_adventure-uploads/logs/{$path}";

    $this->logDir = $this->upload_dir['basedir'] . "/portals_to_adventure-uploads/logs";


    $createLogRes = $this->createLogDir();

    if (!$createLogRes) {
      $this->logger->error('Log directory is not writable');
      return;
    }

    $this->createLog($level, $ifLogUncaught);

    self::$initialized[$classname] = true;

    //$this->logger->debug('Logger initialized');
  }

  /**
   * Retrieves the logger instance.
   *
   * @return Logger The logger instance.
   */
  public function getLogger()
  {
    return $this->logger;
  }

  private function createLogDir()
  {
    if (!file_exists($this->logDir)) {
      mkdir($this->logDir, 0755, true);
    }

    if (!is_writable($this->logDir)) {
      return false;
    }

    $this->hideLogDir();

    // check if the .htaccess file exists
    // if (!file_exists($this->logDir . '/.htaccess')) {
    //   file_put_contents($this->logDir . '/.htaccess', $this->htaccess_content());
    // }

    return true;
  }

  private function createLog($level, $ifLogUncaught = false)
  {
    $handler = new RotatingFileHandler($this->logPath, 7, $level);
    $handler->setFormatter(new LineFormatter(null, null, true, true));
    $this->logger->pushHandler($handler);
    //$this->logger->pushHandler();
    $this->logger->pushProcessor(new IntrospectionProcessor());

    if ($ifLogUncaught) {
      ErrorHandler::register($this->logger);
    }

  }

  private function hideLogDir()
  {
    $htaccess = $this->logDir . '/.htaccess';
    $index = $this->logDir . '/index.html';
    $stored_path = plugin_dir_path(__FILE__) . '../../../assets/admin/';

    if (!file_exists($htaccess)) {
      $htaccess_content = file_get_contents($stored_path . '.htaccess');
      file_put_contents($htaccess, $htaccess_content);
    }

    if (!file_exists($index)) {
      $index_content = file_get_contents($stored_path . 'index.html');
      file_put_contents($index, $index_content);
    }
  }

  /**
   * Get the content for the .htaccess file to secure the logs directory.
   *
   * @return string Content of the .htaccess file.
   */
  private function htaccess_content()
  {
    return <<<HTACCESS
    # Deny all access to this directory
    Order allow,deny
    Deny from all

    # For Apache 2.4 and above
    <IfModule mod_authz_core.c>
      Require all denied
    </IfModule>
    HTACCESS;
  }
}