<?php
/*
Plugin Name: Portals to Adventure
Description: PTA Plugin for submissions and voting.
Version: 1.6.7.1
Author: Rowan W.
*/

/* Prevent direct access */
if (!defined(constant_name: 'ABSPATH')) {
  exit;
}

/* Constants */
define(constant_name: 'PTA_PLUGIN_DIR', value: plugin_dir_path(file: __FILE__));
// check to make sure the constant is defined and has portals-to-adventure in the path
if (defined(constant_name: 'PTA_PLUGIN_DIR') && strpos(haystack: PTA_PLUGIN_DIR, needle: 'portals-to-adventure') === false) {
  define(constant_name: 'PTA_PLUGIN_URL', value: plugin_dir_url(file: __FILE__) . "portals-to-adventure/");
}


/* Require Class */
use PTA\PTA;

try{
  /* Autoload classes and start plugin */
  if (file_exists(filename: __DIR__ . '/vendor/autoload.php')) {

    //error_log(message: 'Portals to Adventure plugin is working');

    /* Load the Composer autoload file */
    require_once __DIR__ . '/vendor/autoload.php';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    /* Start the plugin */
    try {
      //error_log(message: 'Portals to Adventure plugin is working');
      //error_log($_SERVER['REQUEST_URI']);
      $PTA = new PTA();
      $PTA->init(__FILE__);
      $PTA->register_activation();
    } catch (\Exception $e) {
      add_action('admin_notices', function () use ($e) {
        ?>
        <div class="notice notice-error">
          <p>
            <?php _e('Portals to Adventure plugin encountered an error: ' . $e->getMessage()); ?>
          </p>
        </div>
        <?php
      });
    }

  } else {

    // Display an admin notice if the vendor/autoload.php file is missing
    add_action(hook_name: 'admin_notices', callback: function () {
      ?>
      <div class="notice notice-error">
        <p>
          <?php _e('Portals to Adventure plugin is not working Please contact the devs. Autoload!', 'pta'); ?>
        </p>
      </div>
      <?php
    });

    // Exit the plugin
    //wp_die();

  }
} catch (\Exception $e) {
  // Display an admin notice if the vendor/autoload.php file is missing
  add_action(hook_name: 'admin_notices', callback: function () {
    ?>
    <div class="notice notice-error">
      <p>
        <?php _e('Portals to Adventure plugin is not working Please contact the devs.', 'pta'); ?>
      </p>
    </div>
    <?php
  });

  // Exit the plugin
  //wp_die();
}