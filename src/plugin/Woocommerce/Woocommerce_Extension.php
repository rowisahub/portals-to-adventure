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
    add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: array($this, 'add_to_cart_quantity_validation_pta'), accepted_args: 3);
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
        'key' => __('Submission Title', 'your-text-domain'),
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
    $prod_limit = get_option('wldpta_product_limit');

    $this->log->debug('Product limit: ' . $prod_limit);

    // set limit on product quantity when adding to cart
    if ($prod_limit > 0) {
      $cart = WC()->cart->get_cart();
      $cart_count = 0;
      foreach ($cart as $cart_item_key => $cart_item) {
        if ($cart_item['product_id'] == $product_id) {
          $cart_count += $cart_item['quantity'];
        }
      }
      $cart_count += $quantity;
      if ($cart_count > $prod_limit) {
        wc_add_notice(__('You can only purchase a maximum of ' . $prod_limit . ' of this product.', 'wldpta'), 'error');
        return false;
      }
    }

    return $passed;

  }

}