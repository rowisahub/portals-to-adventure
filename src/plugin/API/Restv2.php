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
  /* Constants */
  public const USER = 'user';
  public const ADMIN = 'admin';
  public const EDITOR = 'editor';
  public const USER_PERMS = [
    self::USER,
    self::ADMIN,
    self::EDITOR
  ];
  /* Constants */

  public function __construct()
  {
    parent::__construct("API");
  }

  public function init(
    $sub = null,
    $img = null,
    $user = null,
    $handler = null,
    $db = null,
    $admin = null
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
      'permission_callback' => array($this, 'check_permissions'),
    ));

    /* This is the route for editing submissions */
    register_rest_route('pta/v2', '/submission', array(
      'methods' => 'POST',
      'callback' => [$this, 'submission_edit'],
      'permission_callback' => [$this, 'check_permissions'],
    ));

    /* This is the route for deleting submissions */
    register_rest_route('pta/v2', '/submission/action', array(
      'methods' => 'POST',
      'callback' => [$this, 'submission_action'],
      'permission_callback' => [$this, 'check_permissions'],
    ));

  }

  public function submission_get(\WP_REST_Request $request)
  {
    //
  }

  public function submission_edit(\WP_REST_Request $request)
  {
    //
  }

  public function submission_action(\WP_REST_Request $request)
  {
    //
  }

  protected function check_permissions(\WP_REST_Request $request)
  {
    /* Nonce check */
    $nonce = $request->get_header('X-WP-Nonce');
    if (!$nonce) {
      // No nonce
      return new \WP_Error('no_nonce', 'Nonce is required for authentication.', array('status' => 403));
    } elseif (!wp_verify_nonce($nonce, 'wp_rest')) {
      // Invalid nonce
      return new \WP_Error('invalid_nonce', 'Invalid nonce.', array('status' => 403));
    }

    // check if user is a Adminastrator, Editor, and User (Any other role)
    $user = wp_get_current_user();
    $user_primary_role = self::USER;

    if (in_array('administrator', $user->roles)) {
      $user_primary_role = self::ADMIN;
    } elseif (in_array('editor', $user->roles)) {
      $user_primary_role = self::EDITOR;
    }

    return true;
  }

  protected function format_response($submission)
  {
    //return new \WP_REST_Response($data, 200);
  }
}