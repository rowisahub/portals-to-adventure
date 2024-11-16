<?php
/*
Plugin Name: Portals to Adventure
Description: PTA Plugin for custom submissions and voting.
Version: 1.5.0
Author: Rowan and Braedon
*/

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Constants */
define('PTA_PLUGIN_DIR', plugin_dir_path(__FILE__));

/* Autoload classes */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Handle the error
    wp_die('Autoload file not found. Please run contact the devs.');
}