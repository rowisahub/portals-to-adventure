jQuery(document).ready(function($) {
    // Listen for the 'added_to_cart' event triggered by WooCommerce
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
        // Trigger the mini-cart to open
        openMiniCart();
    });

    function openMiniCart() {

        $('.wc-block-mini-cart__button').trigger('click');

    }
});