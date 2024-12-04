<?php
namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\client\Client;

class Restv2 extends Client
{
  public function __construct()
  {
    parent::__construct("API");
  }

  public function init(
    $sub = null, $img = null, $user = null, $handler = null, $db = null, $admin = null
    ) {
    parent::init(
      sub_functions: $sub,
      img_functions: $img,
      user_functions: $user,
      handler_instance: $handler,
      db_functions: $db,
      admin_functions: $admin
    );

    $this->register_hooks();

  }

  public function register_hooks()
  {
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes()
  {
    /* This is the route for getting submissions */
    register_rest_route('pta/v2', '/submission', array(
      'methods' => 'GET',
      'callback' => array($this, 'submission_get'),
      'permission_callback' => array($this, 'pta_api_check_permissions'),
    ));

    /* This is the route for editing submissions */
    register_rest_route('pta/v2', '/submission', array(
      'methods' => 'POST',
      'callback' => [$this, 'submission_edit'],
      'permission_callback' => [$this, 'pta_api_check_permissions'],
    ));

    /* This is the route for deleting submissions */
    register_rest_route('pta/v2', '/submission/action', array(
      'methods' => 'POST',
      'callback' => [$this, 'submission_action'],
      'permission_callback' => [$this, 'pta_api_check_permissions'],
    ));

  }
}