<?php
/*
File: src/plugin/API/AJAX.php
Description: AJAX API for Portals to Adventure.
*/

namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Require Class */
use Google_Client;
use PTA\client\Client;


/**
 * Class AJAX
 *
 * This class is the AJAX API for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class AJAX extends Client
{
  public function __construct()
  {
    parent::__construct(LogName: "AJAX", callback_after_init: $this->register_hooks());
  }

  public function register_hooks()
  {
    /* For Google Login */
    add_action('wp_ajax_nopriv_wldpta_google_login', array($this, 'wldpta_google_login'));
    add_action('wp_ajax_wldpta_google_login', array($this, 'wldpta_google_login'));

    /* For adding a vote to the cart */
    add_action('wp_ajax_nopriv_wldpta_vote_add_to_cart', array($this, 'wldpta_vote_add_to_cart'));
    add_action('wp_ajax_wldpta_vote_add_to_cart', array($this, 'wldpta_vote_add_to_cart'));
  }

  public function wldpta_google_login()
  {

    if (!isset($_POST['credential'])) {
      wp_send_json_error(array('message' => 'Missing Google credential'));
    }

    $credential = $_POST['credential'];
    //$client = new Google_Client(['client_id' => '1056480373919-if6qv2rnlinuimldu4jsp9eeorvm2nmu.apps.googleusercontent.com']); // test
    $client = new Google_Client(['client_id' => '322331838115-fhp6ql51sqb6ounq5psj1rm83385j449.apps.googleusercontent.com']); // live
    $payload = $client->verifyIdToken($credential);

    if (!$payload) {
      wp_send_json_error(array('message' => 'Invalid Google token'));
    }

    //error_log('Google login payload: ' . print_r($payload, true));

    $email = $payload['email'];
    $username = sanitize_user($payload['name']);
    $user = get_user_by('email', $email);

    // check if the promotional emails checkbox is checked
    $promotionalEmails = isset($_POST['promotionalEmails']) ? $_POST['promotionalEmails'] : '0';

    $userPerms = $this->db_functions->format_permissions($promotionalEmails, 0, 0, 0);

    if (!$user) {
      // User doesn't exist, create new one
      $user_id = $this->user_functions->register_user(
        $email,
        $username,
        $payload['given_name'],
        $payload['family_name'],
        $payload['email_verified'],
        $payload['sub'],
        $permissions = $userPerms
      );
      if ($user_id == null) {
        wp_send_json_error(array('message' => 'Error creating user'));
      }
      $user = get_user_by('id', $user_id);
    }

    // Log the user in
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);

    wp_send_json_success(array('message' => 'User logged in successfully'));

  }

  public function wldpta_vote_add_to_cart(){
  
    $checkNonce = check_ajax_referer('wldpta_ajax_nonce', 'nonce');
  
    if (!$checkNonce) {
      wp_send_json_error(array('message' => 'Invalid nonce'));
    }
  
    // check submission status
  
    $product_id = get_option('pta_woocommerce_product_id');
    $submission_id = $_POST['submission_id'];
  
    // Add the product to the cart
    if (class_exists('WooCommerce')) {
      if($product_id > 0){
        $cart = WC()->cart;
        $added = $cart->add_to_cart(product_id: $product_id, cart_item_data: array('submission_id' => $submission_id));
        //$cart->check_cart_item_validity();

        $this->logger->debug('Cart valid', array('valid' => $cart->check_cart_item_validity()));
    
        if($added){
          $this->logger->debug('Product added to cart', array('product_id' => $product_id, 'submission_id' => $submission_id, 'added' => $added));
          wp_send_json_success(array('message' => 'Product added to cart', 'added' => $added));
        } else {
          $this->logger->error('Error adding product to cart', array('product_id' => $product_id, 'submission_id' => $submission_id, 'added' => $added));
          wp_send_json_error(array('message' => 'Error adding product to cart'));
        }
        
      } else {
        wp_send_json_error(array('message' => 'Invalid product ID'));
      }
    } else {
      wp_send_json_error(array('message' => 'WooCommerce not installed'));
    }
    
    wp_die();
  
  }

}