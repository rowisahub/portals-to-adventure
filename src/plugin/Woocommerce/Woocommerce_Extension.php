<?php
namespace PTA\Woocommerce;
/*
File: woocommerce-exten.php
Description: WooCommerce extension functions for the plugin.
Author: Rowan Wachtler
Created: 10-12-2024
Version: 1.0
*/

// Prevent direct access\
if (!defined('ABSPATH')) {
  exit;
}

// Requires
use PTA\logger\Log;
use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\functions\submission\submission_functions;
use PTA\DB\functions\image\image_functions;
use PTA\DB\functions\user\user_functions;

/**
 * Class PTA_Woocommerce_Extension
 *
 * This class contains the WooCommerce extension functions for the plugin.
 */
class Woocommerce_Extension
{
  private $isLoaded;
  private $log;
  private $handler_instance;
  private $db_functions;
  private $submission_func;
  private $image_func;
  private $user_func;
  public function __construct()
  {
    $this->log = new Log(name: 'WooCommerce');
  }

  public function init(
    db_handler $handler_instance = null,
    db_functions $db_functions = null
  )
  {
    $this->log = $this->log->getLogger();

    // Get the handler instance and db functions instance
    $this->handler_instance = $handler_instance ?? new db_handler();
    $this->db_functions = $db_functions ?? new db_functions();

    // if handler_instance is null or db_functions is null, set them
    if ($handler_instance == null || $db_functions == null) {

      // Set the functions instance in the handler, and initialize the functions
      $this->handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
      $this->db_functions->init(handler_instance: $this->handler_instance);

    }

    // Set the functions instances for the submission, image, and user functions
    $this->submission_func = $sub_functions ?? new submission_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    $this->image_func = $img_functions ?? new image_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    $this->user_func = $user_functions ?? new user_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);

    $this->register_hooks();
  }

  public function register_hooks()
  {
    add_action(hook_name: 'woocommerce_loaded', callback: array($this, 'prefix_woocommerce_loaded'));
  }

  public function prefix_woocommerce_loaded()
  {

    //$this->log->info('WooCommerce is loaded loaded');

    $this->isLoaded = true;

    // Display submission title in order
    add_filter(hook_name: 'woocommerce_get_item_data', callback: array($this, 'display_submission_title_in_order'), accepted_args: 2);
    // Add submission ID to order items
    add_action(hook_name: 'woocommerce_checkout_create_order_line_item', callback: array($this, 'add_submission_id_to_order_items'), accepted_args: 4);
    // Order status processing
    add_action(hook_name: 'woocommerce_order_status_processing', callback: array($this, 'wldpta_order_status_processing'));
    // Order status completed
    add_action(hook_name: 'woocommerce_order_status_completed', callback: array($this, 'wldpta_order_status_completed'));
    // Add to cart quantity validation
    add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: array($this, 'add_to_cart_quantity_validation_pta'), accepted_args: 3, priority: 10);
    // Check cart items quantity
    //add_action(hook_name: 'woocommerce_check_cart_items', callback: array($this, 'check_cart_items_quantity'));
  }

  /**
   * Checks if the WooCommerce extension is loaded.
   *
   * @return bool True if the WooCommerce extension is loaded, false otherwise.
   */
  public function isLoaded()
  {
    return $this->isLoaded;
  }

  /**
   * Displays the submission title in the order.
   *
   * This function hooks into the WooCommerce order item data and displays the submission title in the order.
   *
   */
  public function display_submission_title_in_order($item_data, $cart_item)
  {
    if (isset($cart_item['submission_id'])) {
      $submission = $this->submission_func->get_submission($cart_item['submission_id'])[0];

      $submission_title = wp_unslash($submission['title']);

      $item_data[] = array(
        'key' => __('Submission Title', 'portals-to-adventure'),
        'value' => $submission_title,
        'display' => '',
      );
    }
    return $item_data;
  }

  /**
   * Adds a submission ID to WooCommerce order items.
   *
   * This function hooks into the WooCommerce order item creation process and adds a submission ID to each order item.
   *
   */
  public function add_submission_id_to_order_items($item, $cart_item_key, $values, $order)
  {
    if (isset($values['submission_id'])) {
      $item->add_meta_data('submission_id', $values['submission_id'], true);
    }
  }

  /**
   * Handles the order status processing event.
   *
   * This function is triggered when an order status changes to 'processing'.
   *
   * @param int $order_id The ID of the order that is being processed.
   */
  public function wldpta_order_status_processing($order_id)
  {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $total = $order->get_total();

    // Check if the user exists in the custom database
    if (!$this->db_functions->check_id_exists('user_info', $user_id)) {
      $user = get_user_by('ID', $user_id);
      $userPerms = $this->db_functions->format_permissions(1, 0, 0, 0);
      $this->user_func->register_user(email: $user->user_email, username: $user->display_name, firstName: $user->first_name, lastName: $user->last_name, permissions: $userPerms);
    }

    // Log the order
    $this->log->info('Order processing: ' . $order_id . ' by user: ' . $user_id . ' for total: ' . $total);


    // Set order status to 'Completed'
    $order->update_status(new_status: 'completed', note: 'Order completed automatically by system');

  }

  /**
   * Handles the WooCommerce order status completed action.
   *
   * This function is triggered when an order status is marked as completed.
   *
   * @param int $order_id The ID of the completed order.
   */
  public function wldpta_order_status_completed($order_id)
  {
    global $logWooCommerce;
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $order_items = $order->get_items();
    $total = $order->get_total();

    foreach ($order_items as $item) {
      $submission_id = $item->get_meta('submission_id');
      $quantity = $item->get_quantity();

      $this->submission_func->add_submission_vote($submission_id, $quantity);
    }

    // Log the order
    $this->log->info('Order completed: ' . $order_id . ' by user: ' . $user_id . ' for total: ' . $total);
  }

  /**
   * Validates the quantity of a product being added to the cart.
   *
   * @param bool $passed Indicates whether the validation has passed so far.
   * @param int $product_id The ID of the product being added to the cart.
   * @param int $quantity The quantity of the product being added to the cart.
   * @return bool True if the quantity is valid, false otherwise.
   */
  public function add_to_cart_quantity_validation_pta($passed, $product_id, $quantity)
  {
    $prod_limit = get_option('wldpta_product_limit', 10);

    $this->log->debug('Product limit: ' . $prod_limit);

    // set limit on product quantity when adding to cart
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
      $product_id = $cart_item['product_id'];
      $quantity = $cart_item['quantity'];
      $submission_id = $cart_item->get_meta('submission_id');

      $sub_title = $this->submission_func->get_submission_value($submission_id, 'title');

      // Get the total quantity purchased by the user
      $user_id = get_current_user_id();
      $total_purchased = $this->get_user_product_purchase_count($user_id, $submission_id);

      // if ($quantity + $total_purchased > $prod_limit) {
      //   wc_add_notice(__('You can only purchase a maximum of ' . $prod_limit . ' of this product.', 'wldpta'), 'error');
      //   WC()->cart->remove_cart_item($cart_item_key);
      // }
      if ($quantity + $total_purchased > $prod_limit) {
        wc_add_notice(__('You can only purchase a maximum of ' . $prod_limit . ' votes for ' . $sub_title . '.', 'portals-to-adventure'), 'error');
        WC()->cart->remove_cart_item($cart_item_key);
      }
    }

    return $passed;

  }

  public function check_cart_items_quantity()
  {
    $prod_limit = get_option('wldpta_product_limit', 10);

    $this->log->debug('Checking cart items quantity');

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
      $product_id = $cart_item['product_id'];
      $quantity = $cart_item['quantity'];
      $submission_id = $cart_item->get_meta('submission_id');

      // Get the total quantity purchased by the user
      $user_id = get_current_user_id();
      $total_purchased = $this->get_user_product_purchase_count($user_id, $submission_id);

      // if ($quantity + $total_purchased > $prod_limit) {
      //   wc_add_notice(__('You can only purchase a maximum of ' . $prod_limit . ' of this product.', 'wldpta'), 'error');
      //   WC()->cart->remove_cart_item($cart_item_key);
      // }
      if ($quantity + $total_purchased > $prod_limit) {
        wc_add_notice(__('You can only purchase a maximum of ' . $prod_limit . ' votes for this submission.', 'portals-to-adventure'), 'error');
        WC()->cart->remove_cart_item($cart_item_key);
      }
    }
  }

  public function get_user_submissions_vote_purchased($user_id)
  {
    $prod_limit = get_option('wldpta_product_limit', 10);
   
    $this->log->debug('Getting user submission count', array('user_id' => $user_id));

    $total_submissions = [];

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
      $product_id = $cart_item['product_id'];
      $quantity = $cart_item['quantity'];
      $submission_id = $cart_item->get_meta('submission_id');

      if(!isset($submission_id)){
        continue;
      }

      // Get the total quantity purchased by the user
      $user_id = get_current_user_id();
      $total_purchased = $this->get_user_product_purchase_count($user_id, $submission_id);

      // if ($quantity + $total_purchased > $prod_limit) {
      //   wc_add_notice(__('You can only purchase a maximum of ' . $prod_limit . ' of this product.', 'wldpta'), 'error');
      //   WC()->cart->remove_cart_item($cart_item_key);
      // }
      $total_submissions[$submission_id] = $quantity + $total_purchased;
    }
    
  }

  /**
   * Checks if a user has purchased a specific product.
   *
   * @param int $user_id The ID of the user.
   * @param int $submission_id The ID of the submission.
   * @param int $product_id The ID of the product.
   * @return int The total quantity of the product by submission purchased by the user.
   */
  public function get_user_product_purchase_count($user_id, $submission_id, $product_id = null)
  {
    $this->log->debug('Getting user product purchase count', array('user_id' => $user_id, 'product_id' => $product_id, 'submission_id' => $submission_id));

    $product_id = $product_id ?? get_option('pta_woocommerce_product_id');;

    $orders = wc_get_orders(array(
      'customer_id' => $user_id,
      'status' => array('completed', 'processing')
    ));

    $total_quantity = 0;

    foreach ($orders as $order) {
      // Get the order items
      foreach ($order->get_items() as $item) {
        $submission_id_check = $item->get_meta('submission_id');

        if ($submission_id_check == $submission_id) {
          $total_quantity += $item->get_quantity();
        }
      }
    }

    return $total_quantity;
  }

}