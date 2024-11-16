<?php
/*
file: src/plugin/pta.php
description: Main plugin file for Portals to Adventure.
*/

namespace PTA\plugin;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Require Class */
use PTA\Enqueue;
use PTA\logger\log;
use PTA\DB\db_handler;

/**
 * Class PTA
 *
 * This class is the main plugin file for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class PTA
{
  private $enqueue;
  private $dbHandler;
  private $logger;

  public function __construct()
  {
    /* Enqueue */
    $this->enqueue = new Enqueue();

    /* Logger */
    $this->logger = new log(name: 'Main', ifLogUncaught: true);
    
    /* Database Handler */
    $this->dbHandler = new db_handler();

    /* Initialize */
    $this->init();
  }

  /**
   * Initializes the plugin.
   *
   * This method initializes the plugin by calling the necessary methods
   * to set up the plugin.
   *
   * @return void
   */
  public function init()
  {
    $this->enqueue->add_enqueue_action();
    $this->dbHandler->init();

    $this->logger = $this->logger->getLogger();
  }
}
