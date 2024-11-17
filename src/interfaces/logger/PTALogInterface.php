<?php

namespace PTA\interfaces\logger;

use Monolog\Logger;

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

interface PTALogInterface
{
  /**
   * Get the logger instance.
   *
   * @return Logger The logger instance.
   */
  public function getLogger();
}