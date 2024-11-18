<?php
/*
File: src/plugin/API/REST.php
Description: REST API for Portals to Adventure.
*/

namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Require Class */
use PTA\logger\Log;

/**
 * Class API
 *
 * This class is the REST API for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class REST
{
  private $logger;

  public function __construct()
  {
    /* Logger */
    $this->logger = new Log(name: 'API');

    /* Initialize */
    $this->init();
  }

  /**
   * Initializes the REST API.
   *
   * This method initializes the REST API by calling the necessary methods.
   */
  private function init()
  {
    $this->logger = $this->logger->getInstance();
    
    $this->logger->log('Initializing REST API...');
  }
}