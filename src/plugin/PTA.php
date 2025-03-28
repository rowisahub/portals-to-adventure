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
//use PTA\Woocommerce\Woocommerce_Extension;
use PTA\Woocommerce\Woocom_Ext;
use PTA\Update\Plugin_Updater;
use PTA\shortcodes\Shortcodes;
use PTA\API\AJAX;
//use PTA\API\REST;
use PTA\API\Restv2;
use PTA\admin\admin_settings;
use PTA\forms\Forms;

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
  private $rest_v2;
  private $admin;
  private $forms;

  private $plugin_file;

  public function __construct()
  {
    /* Enqueue */
    $this->enqueue = new Enqueue();

    /* Logger */
    $this->logger = new Log(name: 'Main', ifLogUncaught: true);

    /* Database Handler */
    $this->dbHandler = new db_handler();

    /* Update */
    $this->update = new Plugin_Updater();

    /* Woocommerce Extension */
    //$this->woocommerceExtension = new Woocommerce_Extension();
    $this->woocommerceExtension = new Woocom_Ext();

    /* Shortcodes */
    $this->shortcodes = new Shortcodes();

    /* API */
    //$this->rest = new REST();
    $this->ajax = new AJAX();

    $this->rest_v2 = new Restv2();

    /* Admin */
    $this->admin = new admin_settings();

    /* Forms */
    $this->forms = new Forms();

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
  public function init($plugin_file_in)
  {

    $this->plugin_file = $plugin_file_in;

    try{

      //error_log(message: 'Portals to Adventure plugin is initializing...');

      /* Logger */
      $this->logger = $this->logger->getLogger();

      //error_log(message: 'Got logger');

      //$this->logger->info(message: 'Portals to Adventure plugin is initializing...');

      /* Enqueue */
      $this->enqueue->add_enqueue_action();

      //error_log(message: 'Added enqueue action');
      //$this->logger->info(message: 'Added enqueue action');

      /* Database Handler */
      $this->dbHandler->init();

      //error_log(message: 'Initialized database handler');
      //$this->logger->info(message: 'Initialized database handler');

      /* Shortcodes */
      $this->shortcodes->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

      //error_log(message: 'Initialized shortcodes');
      //$this->logger->info(message: 'Initialized shortcodes');

      /* Woocommerce Extension */
      $this->woocommerceExtension->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

      //error_log(message: 'Initialized Woocommerce Extension');
      //$this->logger->info(message: 'Initialized Woocommerce Extension');

      /* Admin */
      $this->admin->init(
        handler_instance: $this->dbHandler,
        db_functions: $this->dbHandler->get_instance('functions')
      );

      //error_log(message: 'Initialized Admin');
      //$this->logger->info(message: 'Initialized Admin');

      /* Client */


      /* API */
      //$this->rest->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));
      $this->rest_v2->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

      //error_log(message: 'Initialized REST API');
      //$this->logger->info(message: 'Initialized REST API');

      $this->ajax->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

      //error_log(message: 'Initialized AJAX API');
      //$this->logger->info(message: 'Initialized AJAX API');

      //$this->logger->info(message: 'Portals to Adventure plugin has been initialized.');

      /* Update WIP */
      $this->update->init($this->plugin_file);

      //error_log(message: 'Initialized Update');
      //$this->logger->info(message: 'Initialized Update');

      /* Forms */
      $this->forms->init(handler_instance: $this->dbHandler, db_functions: $this->dbHandler->get_instance('functions'));

    } catch (\Exception $e) {
      $this->logger->error('Portals to Adventure plugin encountered an error: ' . $e->getMessage());
    }

  }

  public function register_activation()
  {
    $this->dbHandler->register_activation($this->plugin_file);
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
