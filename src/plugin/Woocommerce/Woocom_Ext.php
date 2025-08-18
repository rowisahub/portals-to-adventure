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
        // $this->logger->debug('WooCommerce loaded');

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
        add_filter('woocommerce_store_api_cart_item_images', array($this, 'API_cart_image'), 10, 3);
        add_filter('woocommerce_cart_item_permalink', array($this, 'change_item_permalink'), 10, 3);
        // add_filter('woocommerce_add_to_cart_fragments', array($this, 'update_cart_fragments'), 10, 1);

        // $this->logger->debug('WooCommerce hooks registered');
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
        // $this->logger->debug('Replacing product image with submission image');
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

    /**
     * Processes and retrieves the cart image based on the given product images and cart item details.
     *
     * @param array  $product_images Array of product images associated with the cart item.
     * @param array  $cart_item      Array containing the WooCommerce cart item details.
     * @param string $cart_item_key  Unique identifier for the cart item in the cart.
     */
    public function API_cart_image($product_images, $cart_item, $cart_item_key)
    {
        // Current Working Code For Cart Image

        $image_url = $cart_item['submission_image'] ?? null;
        if($image_url){
            return [
                (object)[
                    'id' => (int) 0,
                    'src' => $image_url,
                    'thumbnail' => $image_url,
                    'srcset' => (string)'',
                    'sizes' => (string)'',
                    'name' => 'PTA Submission Image',
                    'alt' => 'PTA Submission Image',
                ]
            ];
        }
    }

    /**
     * Modifies the permalink for a specific cart item.
     *
     * This method adjusts the original permalink associated with a cart item. 
     * It takes into account the details within the cart item and its key to generate 
     * a new, context-specific permalink.
     *
     * @param string $permalink      The original permalink of the cart item.
     * @param array  $cart_item      An associative array containing the cart item details.
     * @param string $cart_item_key  The unique identifier for the cart item.
     *
     * @return string The modified permalink.
     */
    public function change_item_permalink($permalink, $cart_item, $cart_item_key)
    {
        if ( ! empty( $cart_item['submission_id'] ) ) {
            $reutn_permalink = '/submission/?id=' . $cart_item['submission_id'];
            return $reutn_permalink;
        }
        return $permalink;
    }
    
}

class Woocom_order_status{
    private Woocom_Ext $woocom_ext;

    public function __construct(Woocom_Ext $woocom_ext)
    {
        $this->woocom_ext = $woocom_ext;
    }

    public function register_hooks(){
        add_action(hook_name: 'woocommerce_order_status_processing', callback: [$this, 'wldpta_order_status_processing'], priority: 10, accepted_args: 1);
        add_action(hook_name: 'woocommerce_order_status_completed', callback: [$this, 'wldpta_order_status_completed'], priority: 10, accepted_args: 1);
    }

    public function wldpta_order_status_processing($order_id){
        $this->woocom_ext->logger->debug('Order status processing');
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        $this->woocom_ext->logger->info('Processing order!');


        // Check if user has a wordpress user account
        
        if(!$user_id || $user_id <= 0){
            // Use the order email to create a user account
            $user_id = $this->woocom_ext->user_functions->register_user(
                email: $order->get_billing_email(),
                username: sanitize_user($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                firstName: $order->get_billing_first_name(),
                lastName: $order->get_billing_last_name(),
                permissions: $this->woocom_ext->db_functions->format_permissions(0, 0, 0, 0)
            );
            if($user_id == null){
                $this->woocom_ext->logger->error('Error creating user');

                // Add an error notice to the order
                // $order->add_order_note('Error creating user account. Updating order status to On Hold.');
                $order->update_status(new_status: 'On Hold', note: 'Order halted due to user creation error. Please contact support.');

                return;
            }

            $order->set_customer_id($user_id);

            // Login the user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            $this->woocom_ext->logger->info('User created with ID: ' . $user_id);
        }

        // Add vote to the user
        $order_items = $order->get_items();
        foreach($order_items as $item){
            $submission_id = $item->get_meta('submission_id');
            $quantity = $item->get_quantity();

            $this->woocom_ext->user_submission_functions->add_user_vote($user_id, $submission_id, $quantity);
            $this->woocom_ext->logger->info('User ID: ' . $user_id . ' has purchased a submission vote. Submission ID: ' . $submission_id . ' Quantity: ' . $quantity);
        }

        // Update the total votes for the submission
        $total_votes = $this->woocom_ext->user_submission_functions->get_total_votes_for_submission($submission_id);
        $this->woocom_ext->submission_functions->update_submission($submission_id, [
            'likes_votes' => $total_votes
        ]);
        
        // $this->woocom_ext->logger->info('User ID: ' . $user_id . ' has purchased a submission vote.');
        $order->update_status(new_status: 'completed', note: 'Order completed by Portals to Adventure automatically.');
    }

    public function wldpta_order_status_completed($order_id){
        $this->woocom_ext->logger->info('Order completed successfully.');
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

        // add_filter('woocommerce_cart_contents_changed', [$this, 'pta_cart_contents_changed']);

        // add_action('woocommerce_add_to_cart', [$this, 'wc_add_to_cart'], 10, 6);
    }

    public function wc_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        // $this->woocom_ext->logger->debug('Product added to cart: ', array(
        //     'cart_item_key' => $cart_item_key,
        //     'product_id' => $product_id,
        //     'quantity' => $quantity,
        //     'variation_id' => $variation_id,
        //     'variation' => $variation,
        //     'cart_item_data' => $cart_item_data
        // ));
        // if $cart_item_data is empty, we can assume that the product was added from the products page
        if(empty($cart_item_data)){
            $this->woocom_ext->logger->debug('Product does not have submission data, not adding to cart');
            $cart = WC()->cart;
            // Remove the item from the cart
            $cart->remove_cart_item($cart_item_key);
        }
    }

    public function pta_cart_contents_changed($changed){
        // $this->woocom_ext->logger->debug('Cart contents changed: ' . json_encode($changed));

        
        return $changed;
    }

    public function add_to_cart_validation($passed, $product_id, $quantity){

        $this->woocom_ext->logger->debug('Adding to cart validation');

        // From my understanding, this function only fires when a product is added from the products page.
        // And when its added from there is does not have a submission ID. So we can just return false,
        // to not add it

        return false;

        // Check if 

        // $total_vote_count = get_option('wldpta_product_limit', 10);

        // $cart = WC()->cart->get_cart();
        // $this->woocom_ext->logger->debug('Cart: ' . json_encode($cart));

        // foreach ($cart as $cart_item) {
        //     $submission_id = $cart_item['submission_id'];
        //     $quantity = $cart_item['quantity'];
            
        //     $this->woocom_ext->logger->debug('Submission ID: ' . $submission_id . ' Quantity: ' . $quantity);

        //     $votes_from_user = $this->woocom_ext->user_submission_functions->get_user_submission_votes(get_current_user_id(), $submission_id);

        //     $total_votes_from_user = $votes_from_user + $quantity;

        //     $this->woocom_ext->logger->debug('Total votes from user: ' . $total_votes_from_user);

        //     if($total_votes_from_user > $total_vote_count){
        //         // wc_add_notice(__('You can only purchase up to ' . $total_vote_count . ' votes for each submission.', 'portals-to-adventure'), 'error');
        //         $this->woocom_ext->logger->debug('You can only purchase up to ' . $total_vote_count . ' votes for each submission.');
        //         return false;
        //     }
        // }

        // return $passed;
    }

    public function add_to_cart($cart){
        $total_vote_count = get_option('wldpta_product_limit', 10);
        // $this->woocom_ext->logger->debug('Total vote count: ' . $total_vote_count);

        $user_id = get_current_user_id();
        // $this->woocom_ext->logger->debug('User ID: ' . $user_id);

        $this->woocom_ext->logger->debug('Checking cart for vote limits');

		$iferror = false;
        foreach($cart->get_cart() as $cart_item){

            $submission_id = $cart_item['submission_id'];
            $quantity = $cart_item['quantity'];

            // If no submission ID, remove the item from the cart
            if(!$submission_id){
                $cart_item_key = $cart_item['key'];
                $cart->remove_cart_item($cart_item_key);
                $this->woocom_ext->logger->debug('No submission ID found, removing item from cart');
                continue;
            }

            if($iferror) continue;

            $votes_from_user = $this->woocom_ext->user_submission_functions->get_user_submission_votes($user_id, $submission_id);

            $total_votes_from_user = $votes_from_user + $quantity;

            $total_error_text = 'You can only purchase up to ' . $total_vote_count . ' votes for each submission.';

            if($total_votes_from_user > $total_vote_count){
                $iferror = true;

                if($votes_from_user > 0){
                    $total_error_text .= ' You have already purchased ' . $votes_from_user . ' votes.';
                }

                $cart_item_key = $cart_item['key'];
                $cart->set_quantity($cart_item_key, $total_vote_count - $votes_from_user);

                $this->manage_notices($total_error_text);

            } else {

                $this->manage_notices($total_error_text, true);
            }
        }
    }

		protected function manage_notices($total_error_text, $remove = false){
			$notices = wc_get_notices();

			// wc_clear_notices();

			// $this->woocom_ext->logger->debug('All notices: ' . json_encode($notices));

			foreach($notices as $key => $notice){
				if($key == 'error'){
					foreach($notice as $key2 => $notice2){
						if(strpos($notice2['notice'], $total_error_text) !== false){
							// $this->woocom_ext->logger->debug('Removing notice from error notice');
							unset($notices[$key][$key2]);
						}
					}
				} else {
					if(strpos($notice['notice'], $total_error_text) !== false){
						// $this->woocom_ext->logger->debug('Removing notice single');
						unset($notices[$key]);
					}
				}
			}
			wc_set_notices($notices);

			if(!$remove){
				// $this->woocom_ext->logger->debug('Adding notice: ' . $total_error_text);

				wc_add_notice(__($total_error_text, 'portals-to-adventure'), 'error');
			}
		}
    
      private function check_contest_date()
  {
    $pta_clock_start_date = get_option('pta_clock_start_date');
    $pta_clock_end_date = get_option('pta_clock_end_date');

    $pta_start_date = \DateTime::createFromFormat('Y-m-d\TH:i', $pta_clock_start_date, new \DateTimeZone('UTC'));
    $pta_end_date = \DateTime::createFromFormat('Y-m-d\TH:i', $pta_clock_end_date, new \DateTimeZone('UTC'));

    $current_date_time = \DateTime::createFromFormat('Y-m-d\TH:i', current_time('Y-m-d\TH:i'), new \DateTimeZone('UTC'));

    if ($pta_start_date && $pta_end_date) {
      if ($current_date_time >= $pta_start_date && $current_date_time <= $pta_end_date) {
        return true;
      }
    }

    return false;
  }

}