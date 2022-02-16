<?php
class ajax_Fragment
{

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */

    function __construct()
    {
        add_action('wp_ajax_pick_delivery', array(&$this, 'get_pick_delivery'));
        add_action('wp_ajax_nopriv_pick_delivery', array(&$this, 'get_pick_delivery'));
    }


    /**
     * Change the delivery date during checkout
     *
     * @since    1.0.0
     */

    public function get_pick_delivery()
    {
        $cart = WC()->cart->cart_contents;
        foreach ($cart as $cart_item_id => $cart_item) {
            $cart_item['delivery_date'] = $_POST['pick_delivery'];
            WC()->cart->cart_contents[$cart_item_id] = $cart_item;
        }
        WC()->cart->set_session();
        die();
    }
}
