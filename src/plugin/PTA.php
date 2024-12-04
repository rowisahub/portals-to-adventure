<?php
/*
file: src/plugin/pta.php
description: Main plugin file for Portals to Adventure.
*/

namespace PTA;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Require Class */
use PTA\enqueue\Enqueue;
use PTA\logger\Log;
use PTA\DB\db_handler;
use PTA\Woocommerce\Woocommerce_Extension;
use PTA\Update\Plugin_Updater;
use PTA\shortcodes\Shortcodes;
use PTA\API\AJAX;
use PTA\API\REST;
use PTA\admin\admin_settings;

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
  private $shortcodes;
  private $ajax;
  private $rest;
  private $admin;

  public function __construct()
  {
    /* Enqueue */
    $this->enqueue = new Enqueue();

    /* Logger */
    $this->logger = new log(name: 'Main', ifLogUncaught: true);

    /* Database Handler */
    $this->dbHandler = new db_handler();

    /* Update */
    $this->update = new Plugin_Updater();

    /* Woocommerce Extension */
    $this->woocommerceExtension = new Woocommerce_Extension();

    /* Shortcodes */
    $this->shortcodes = new Shortcodes();

    /* API */
    $this->rest = new REST();
    $this->ajax = new AJAX();

    /* Admin */
    $this->admin = new admin_settings();

    /* Initialize */
    //$this->init();
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
    /* Logger */
    $this->logger = $this->logger->getLogger();

    //$this->logger->info(message: 'Portals to Adventure plugin is initializing...');

    /* Enqueue */
    $this->enqueue->add_enqueue_action();

    /* Database Handler */
    $this->dbHandler->init();

    /* Update WIP */
    //$this->update->init();

    /* Shortcodes */
    $this->shortcodes->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

    /* Woocommerce Extension */
    $this->woocommerceExtension->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

    /* API */
    $this->rest->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));
    $this->ajax->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

    $this->logger->info('Admin functions: ' . $this->admin);

    /* Admin */
    $this->admin->init(
      handler_instance: $this->dbHandler,
      db_functions: $this->dbHandler->get_instance('functions')
    );

    //$this->logger->info(message: 'Portals to Adventure plugin has been initialized.');

  }

  public function get_instance($name)
  {
    switch ($name) {
      case 'enqueue':
        return $this->enqueue;
      case 'dbHandler':
        return $this->dbHandler;
      case 'logger':
        return $this->logger;
      case 'woocommerceExtension':
        return $this->woocommerceExtension;
      case 'update':
        return $this->update;
      case 'shortcodes':
        return $this->shortcodes;
      case 'ajax':
        return $this->ajax;
      case 'rest':
        return $this->rest;
      case 'admin':
        return $this->admin;
      default:
        return null;
    }
  }
}
