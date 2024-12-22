<?php
/*
file: assets/enqueue.php
description: Enqueue scripts and styles
*/

namespace PTA\enqueue;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Require Interface */
use PTA\interfaces\enqueue\PTAEnqueueInterface;

/**
 * Class Enqueue
 *
 * This class implements the PTAEnqueueInterface and is responsible for
 * enqueuing scripts and styles for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class Enqueue implements PTAEnqueueInterface
{
  /**
   * Adds the enqueue action for the plugin's assets.
   *
   * This method hooks into WordPress to enqueue the necessary scripts and styles
   * for the plugin to function properly.
   *
   * @return void
   */
  public function add_enqueue_action()
  {
    add_action(hook_name: 'wp_enqueue_scripts', callback: [$this, 'enqueue_scripts']);
  }

  public function enqueue_scripts()
  {
    /*

      Public files to enqueue 

    */

    /* Sidebar */
    wp_enqueue_style(
      handle: 'pta-sidebar-style',
      src: plugins_url(path: 'portals-to-adventure/assets/public/css/sidebar.css'),
      deps: [],
      ver: '1.0.0',
      media: 'all'
    );
    wp_enqueue_script(
      handle: 'pta-sidebar-script',
      src: plugins_url(path: 'portals-to-adventure/assets/public/js/sidebar.js'),
      deps: ['jquery'],
      ver: '1.0.0',
      args: true
    );

    /* Login */
    wp_enqueue_style(
      handle: 'pta-login-style',
      src: plugins_url(path: 'portals-to-adventure/assets/public/css/login.css'),
      deps: [],
      ver: '1.0.0',
      media: 'all'
    );
    wp_enqueue_script(
      handle: 'pta-login-script',
      src: plugins_url(path: 'portals-to-adventure/assets/public/js/login.js'),
      deps: ['jquery'],
      ver: '1.0.0',
      args: true
    );

    /* Login Google */
    wp_enqueue_script(
      handle: 'pta-login-google',
      src: 'https://accounts.google.com/gsi/client',
      deps: [],
      ver: '1.0.0',
      args: true
    );

    /* API */
    wp_enqueue_script(
      handle: 'pta-api',
      src: plugins_url(path: 'portals-to-adventure/assets/public/js/api.js'),
      deps: ['jquery'],
      ver: '1.0.0',
      args: true
    );
    wp_enqueue_script(
      handle: 'pta-api-v2',
      src: plugins_url(path: 'portals-to-adventure/assets/public/js/api-v2.js'),
      deps: ['jquery'],
      ver: '1.0.2',
      args: true
    );

    /* Admin */
    // $current_user = wp_get_current_user();
    // $allowed_roles = array('administrator', 'editor');
    // if (!empty(array_intersect($current_user->roles, $allowed_roles))) {
    //   //error_log(message: 'Enqueuing admin scripts');
    //   wp_enqueue_style(
    //     handle: 'pta-api-v2-admin',
    //     src: plugins_url(path: 'portals-to-adventure/assets/admin/js/api-v2-admin.js'),
    //     deps: ['jquery'],
    //     ver: '1.0.0',
    //     media: 'all'
    //   );
    // }

    /* Woocommerce */
    if (class_exists(class: 'WooCommerce')) {
      wp_enqueue_script(
        handle: 'pta-woocommerce',
        src: plugins_url(path: 'portals-to-adventure/assets/public/js/woocommerce.js'),
        deps: ['jquery'],
        ver: '1.0.0',
        args: true
      );
    }

    /* Custom data to enqueue */

    // Ajax
    $ajax_object = array(
      'ajax_url' => admin_url(path: 'admin-ajax.php'),
      'nonce' => wp_create_nonce(action: 'wldpta_ajax_nonce')
    );
    $ajax_object_json = wp_json_encode($ajax_object);

    // User data
    $user_data = array(
      'is_logged_in' => is_user_logged_in(),
      'user_name' => is_user_logged_in() ? wp_get_current_user()->display_name : ''
    );
    $user_data_json = wp_json_encode($user_data);

    // API
    $api_data = array(
      'api_url' => home_url(path: '/wp-json/pta/v1/'),
      'apiv2_url' => home_url(path: '/wp-json/pta/v2/'),
      'nonce' => wp_create_nonce(action: 'wp_rest'),
      'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
      'user_admin' => is_user_logged_in() ? current_user_can(capability: 'administrator') : false,
      //'woocommerce_product_id' => get_option('pta_woocommerce_product_id'), // get_option('pta_woocommerce_product_id')
    );
    $api_data_json = wp_json_encode($api_data);

    // Contest data
    $contest_data = [
      'is_contest_active' => $this->check_contest_date(),
      'contest_state' => $this->check_contest_state(),
      'contest_start_date' => get_option('pta_clock_start_date'),
      'contest_end_date' => get_option('pta_clock_end_date')
    ];
    $contest_data_json = json_encode($contest_data);

    /* Enqueue inlined scripts */
    wp_add_inline_script(
      handle: 'pta-api',
      data: "const pta_api_data = $api_data_json; const ajax_object = $ajax_object_json; const user_data = $user_data_json; const contest_data = $contest_data_json;",
      position: 'before'
    );

    /*

      Admin files to enqueue 

    */

    //
  }

  private function check_contest_date()
  {
    $pta_clock_start_date = get_option('pta_clock_start_date');
    $pta_clock_end_date = get_option('pta_clock_end_date');

    if ($pta_clock_start_date && $pta_clock_end_date) {
      $current_date = date('Y-m-d H:i:s');
      if ($current_date >= $pta_clock_start_date && $current_date <= $pta_clock_end_date) {
        return true;
      }
    }

    return false;
  }

  private function check_contest_state()
  {
    // return 'active', 'pre', 'post'

    $pta_clock_start_date = get_option('pta_clock_start_date');
    $pta_clock_end_date = get_option('pta_clock_end_date');

    if ($pta_clock_start_date && $pta_clock_end_date) {
      $current_date = date('Y-m-d H:i:s');
      if ($current_date >= $pta_clock_start_date && $current_date <= $pta_clock_end_date) {
        return 'active';
      } else if ($current_date < $pta_clock_start_date) {
        return 'pre';
      } else {
        return 'post';
      }
    }
  }
}