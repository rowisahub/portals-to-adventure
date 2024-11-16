<?php
/*
Plugin Name: Portals to Adventure
Description: PTA Plugin for custom submissions and voting.
Version: 1.5.0
Author: Rowan and Braedon
*/

namespace PTA;

/* Prevent direct access */
if (!defined(constant_name: 'ABSPATH')) {
  exit;
}

/* Constants */
define(constant_name: 'PTA_PLUGIN_DIR', value: plugin_dir_path(file: __FILE__));

/* Require Class */
use PTA\plugin\PTA;

/* Autoload classes and start plugin */
if (file_exists(filename: __DIR__ . '/vendor/autoload.php')) {

  /* Load the Composer autoload file */
  require_once __DIR__ . '/vendor/autoload.php';

  /* Start the plugin */
  add_action(hook_name: 'plugins_loaded', callback: function () {
    new PTA();
  }, priority: 9999);

} else {

  // Display an admin notice if the vendor/autoload.php file is missing
  add_action(hook_name: 'admin_notices', callback: function () {
    ?>
    <div class="notice notice-error">
      <p>
        <?php _e('Portals to Adventure plugin is not working because the vendor/autoload.php file is missing. Please contact the devs.', 'pta'); ?>
      </p>
    </div>
    <?php
  });

  // Exit the plugin
  wp_die();

}
