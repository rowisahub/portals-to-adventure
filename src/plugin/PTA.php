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
use PTA\logger\Log;
use PTA\DB\db_handler;
use PTA\Woocommerce\Woocommerce_Extension;
use PTA\Update\Plugin_Updater;

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
  private $woocommerceExtension;
  private $update;

  public function __construct()
  {
    /* Enqueue */
    $this->enqueue = new Enqueue();

    /* Logger */
    $this->logger = new log(name: 'Main', ifLogUncaught: true);
    
    /* Database Handler */
    $this->dbHandler = new db_handler();

    /* Woocommerce Extension */
    $this->woocommerceExtension = new Woocommerce_Extension();

    /* Update */
    $this->update = new Plugin_Updater();

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
    /* Enqueue */
    $this->enqueue->add_enqueue_action();

    /* Database Handler */
    $this->dbHandler->init();

    /* Woocommerce Extension */
    $this->woocommerceExtension->init();

    /* Update WIP */
    //$this->update->init();

    /* Logger */
    $this->logger = $this->logger->getLogger();
  }
}
