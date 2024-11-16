<?php
/*
file: src/interfaces/pta_enqueue_interface.php
description: Enqueue interface for the plugin.
*/

namespace PTA\interfaces\enqueue;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

interface PTAEnqueueInterface
{

  /**
   * Adds the enqueue action for the plugin's assets.
   *
   * This method hooks into WordPress to enqueue the necessary scripts and styles
   * for the plugin to function properly.
   *
   * @return void
   */
  public function add_enqueue_action();

  /**
   * Enqueues the necessary scripts and styles for the plugin.
   *
   * This method enqueues the necessary scripts and styles for the plugin to
   * function properly. 
   *
   * @return void
   */
  public function enqueue_scripts();
}