<?php
namespace PTA;
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
class log
{
  private $logger;
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
  public function __construct($name, $path = 'debug.log', $level = Logger::DEBUG, $ifLogUncaught = false)
  {
    $this->logger = new Logger($name);

    $this->log_level = $level;

    $evnLvl = get_option('pta_environment');
    if ($evnLvl == 'production') {
      $level = Logger::INFO;
    }

    // use upload directory for logs and add .htaccess file to prevent access
    $this->upload_dir = wp_upload_dir();

    $this->logPath = $this->upload_dir['basedir'] . "/pta/logs/{$path}";

    $this->logDir = $this->upload_dir['basedir'] . "/pta/logs";


    $createLogRes = $this->createLogDir();

    if (!$createLogRes) {
      $this->logger->error('Log directory is not writable');
      return;
    }

    $this->createLog($level, $ifLogUncaught);
  }

  public function getLogger()
  {
    return $this->logger;
  }

  private function createLogDir()
  {
    if (!file_exists($this->logDir)) {
      mkdir($this->logDir, 0755, true);
      file_put_contents($this->logDir . '/.htaccess', htaccess_content());
    }

    if (!is_writable($this->logDir)) {
      return false;
    }

    return true;
  }

  private function createLog($level, $ifLogUncaught = false)
  {
    $handler = new RotatingFileHandler($this->logPath, 7, $level);
    $handler->setFormatter(new LineFormatter(null, null, true, true));
    $this->logger->pushHandler($handler);
    $this->logger->pushProcessor(new IntrospectionProcessor());

    if ($ifLogUncaught) {
      ErrorHandler::register($this->logger);
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