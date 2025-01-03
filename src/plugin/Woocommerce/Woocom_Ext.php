<?php
namespace PTA\Woocommerce;

// Prevent direct access\
if (!defined('ABSPATH')) {
    exit;
}

  
/* Requires */
use PTA\client\Client;

/**
 * Class Woocom_Ext
 *
 * This class is the Woocommerce Extension for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class Woocom_Ext extends Client{
    public bool $isWooCommerceActive = false;
    private Woocom_order_status $order_status;
    private Woocom_cart $cart;

    public function __construct()
    {
        parent::__construct(LogName: "WooCommerce.Extension", callback_after_init: $this->register_hooks());
    }

    public function register_hooks()
    {
        add_action(hook_name: 'woocommerce_loaded', callback: array($this, 'woocommerce_loaded'));
    }

    public function woocommerce_loaded()
    {
        $this->isWooCommerceActive = true;

        // Add WooCommerce related hooks here
        //$this->logger->debug('WooCommerce loaded');

        /* Order Status */
        $this->order_status = new Woocom_order_status($this);
        $this->order_status->register_hooks();

        /* Cart */
        $this->cart = new Woocom_cart($this);
        $this->cart->register_hooks();

        /* Add submission data to the order item */
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_submission_data_to_order_item'), 10, 4);
        /* Display the submission title in the order */
        add_filter('woocommerce_get_item_data', array($this, 'display_submission_data_in_order_description'), 10, 2);
        /* Replace product image with submission image */
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'replace_product_image_with_submission_image'), 10, 3);
    }

    /**
     * Adds submission data to the order item.
     *
     * @param \WC_Order_Item_Product $item The order item object.
     * @param string $cart_item_key The cart item key.
     * @param array $values The cart item values.
     * @param \WC_Order $order The order object.
     */
    public function add_submission_data_to_order_item($item = null, $cart_item_key = null, $values = null, $order = null){

        //$this->logger->debug('Adding submission data to order item');
        if(isset($values['submission_id'])){
            $item->add_meta_data('submission_id', $values['submission_id'], true);
        }
        if(isset($values['submission_title'])){
            $item->add_meta_data('submission_title', $values['submission_title'], true);
        }
        if(isset($values['submission_image'])){
            $item->add_meta_data('submission_image', $values['submission_image'], true);
        }
    }

    /**
     * Display the submission title in the order.
     *
     * This function adds the submission title to the order item data.
     *
     * @param array $item_data The current item data.
     * @param array $cart_item The cart item data.
     * @return array The modified item data with the submission title included.
     */
    public function display_submission_data_in_order_description($item_data, $cart_item)
    {
        //$this->logger->debug('Displaying submission title in order');
        if(isset($cart_item['submission_title'])){
            $item_data[] = array(
                'key' => 'Submission Title',
                'value' => $cart_item['submission_title']
            );
        }
        return $item_data;
    }

    /**
     * Replace the product image with the submission image in the cart.
     *
     * @param string $thumbnail The product thumbnail.
     * @param array $cart_item The cart item data.
     * @param string $cart_item_key The cart item key.
     * @return string The modified product thumbnail.
     */
    public function replace_product_image_with_submission_image($thumbnail, $cart_item, $cart_item_key)
    {
        //$this->logger->debug('Replacing product image with submission image');
        if(strpos($thumbnail, 'placeholder')){
            //$this->logger->debug('Product image is a placeholder');
            if(isset($cart_item['submission_image'])){
                //$this->logger->debug('Submission image found');
                $return_image = '<img';
                $return_image .= ' src="' . $cart_item['submission_image'] . '"';
                $return_image .= ' class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"';
                $return_image .= ' alt="' . $cart_item['submission_title'] . '"';
                $return_image .= ' width="300" height="300" />';
                return $return_image;
            }
        }
        return $thumbnail;
    }
}

class Woocom_order_status{
    private Woocom_Ext $woocom_ext;

    public function __construct(Woocom_Ext $woocom_ext)
    {
        $this->woocom_ext = $woocom_ext;

        //$this->register_hooks();
    }

    public function register_hooks(){
        add_action(hook_name: 'woocommerce_order_status_processing', callback: [$this, 'wldpta_order_status_processing'], priority: 10, accepted_args: 1);
        add_action(hook_name: 'woocommerce_order_status_completed', callback: [$this, 'wldpta_order_status_completed'], priority: 10, accepted_args: 1);
    }

    public function wldpta_order_status_processing($order_id){
        //$this->woocom_ext->logger->debug('Order status processing');
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if(!$this->woocom_ext->db_functions->check_id_exists('user_info', $user_id)){
            $user = get_user_by('ID', $user_id);
            $userPerms = $this->woocom_ext->db_functions->format_permissions(1, 0, 0, 0);
            $this->woocom_ext->user_functions->register_user(email: $user->user_email, username: $user->display_name, firstName: $user->first_name, lastName: $user->last_name, permissions: $userPerms);
        }
        
        $order->update_status(new_status: 'completed', note: 'Order completed by Portals to Adventure');
    }

    public function wldpta_order_status_completed($order_id){
        //$this->woocom_ext->logger->debug('Order status completed');
        $order = wc_get_order($order_id);
        $order_items = $order->get_items();

        foreach($order_items as $item){
            $submission_id = $item->get_meta('submission_id');
            $quantity = $item->get_quantity();

            $this->woocom_ext->submission_functions->add_submission_vote($submission_id, $quantity);
        }
    }
}

class Woocom_cart {
    private Woocom_Ext $woocom_ext;

    public function __construct(Woocom_Ext $woocom_ext)
    {
        $this->woocom_ext = $woocom_ext;
    }

    public function register_hooks(){
        add_filter(hook_name: 'woocommerce_add_to_cart_validation', callback: [$this, 'add_to_cart_validation'], priority: 10, accepted_args: 3);
        //$this->woocom_ext->logger->debug('Cart hooks registered');
        add_action('woocommerce_before_calculate_totals', [$this, 'add_to_cart'], 10, 1);
    }

    public function add_to_cart_validation($passed, $product_id, $quantity){
        $max_quantity = 10; // Set your maximum quantity here

        // Get the current quantity of the product in the cart
        $cart = WC()->cart->get_cart();
        $current_quantity = 0;
        foreach ($cart as $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                $current_quantity += $cart_item['quantity'];
            }
        }

        // Check if the total quantity exceeds the maximum quantity
        if (($current_quantity + $quantity) > $max_quantity) {
            wc_add_notice(__('You can only purchase a maximum of ' . $max_quantity . ' of this product.', 'portals-to-adventure'), 'error');
            return false;
        }

        return $passed;
    }

    public function add_to_cart($cart){
        //$this->woocom_ext->logger->debug('Adding to cart');
        // works
        // show the The submission ID and qunitity of the items in the cart
        foreach($cart->get_cart() as $cart_item){
            $submission_id = $cart_item['submission_id'];
            $quantity = $cart_item['quantity'];
            //$this->woocom_ext->logger->debug('Submission ID: ' . $submission_id . ' Quantity: ' . $quantity);

            if($quantity > 10){

                wc_add_notice(__('You can only purchase up to 10 of each submission.', 'portals-to-adventure'), 'error');

                $cart_item_key = $cart_item['key'];

                $cart->set_quantity($cart_item_key, 10);

                //$this->woocom_ext->sse->send_message('You can only purchase up to 10 of each submission.', 'error');
                
                // update mini cart
                //$cart->calculate_totals();

                // update cart
                //$cart->calculate_totals();
                //WC()->cart->calculate_totals();
                //WC_AJAX::get_refreshed_fragments();



            }
        }

        //
    }

}