<?php
/*
File: src/plugin/API/AJAX.php
Description: AJAX API for Portals to Adventure.
*/

namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Require Class */
use PTA\logger\Log;

/**
 * Class AJAX
 *
 * This class is the AJAX API for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class AJAX
{
  private $logger;

  public function __construct()
  {
    /* Logger */
    $this->logger = new Log(name: 'AJAX');

    /* Initialize */
    $this->init();
  }

  /**
   * Initializes the AJAX API.
   *
   * This method initializes the AJAX API by calling the necessary methods.
   */
  private function init()
  {
    $this->logger = $this->logger->getInstance();
    
    $this->logger->log('Initializing AJAX API...');
  }
}