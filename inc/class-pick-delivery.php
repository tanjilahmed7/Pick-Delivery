<?php
class Pick_Delivery
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Pick_Delivery_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;
    
    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    function __construct()
    {
        add_action('woocommerce_before_add_to_cart_button', array(&$this, 'pick_delivery_add_text_field'));
        add_filter('woocommerce_add_to_cart_validation', array(&$this, 'pick_delivery_add_to_cart_validation'), 10, 4);
        add_filter('woocommerce_add_cart_item_data', array(&$this, 'pick_delivery_add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array(&$this, 'pick_delivery_get_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array(&$this, 'pick_delivery_checkout_create_order_line_item'), 10, 4);
        add_action('woocommerce_after_order_notes', array(&$this, 'checkout_fields'));
        add_action('wp_footer', array(&$this, 'checkout_pick_delivery_script'));
    }

    public function run()
    {
        $this->loader = new ajax_Fragment();

    }

    /**
     * Add a custom text input field to the product page
     */

    public function pick_delivery_add_text_field()
    {
?>
        <div class="pick_delivery-wrap">
            <label for="pick_delivery"><?php _e('Delivery Date', 'pick_delivery'); ?></label>
            <input type="date" name='pick_delivery' id='pick_delivery' value=''>
        </div>

        <?php
    }

    /**
     * Validate our custom text input field value
     */
    public function pick_delivery_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = null)
    {
        if (empty($_POST['pick_delivery'])) {
            $passed = false;
            wc_add_notice(__('Delivery Date is a required field.', 'pick_delivery'), 'error');
        }
        if (!empty(WC()->cart->get_cart()) && $passed) {
            WC()->cart->empty_cart();
            wc_add_notice('Whoa hold up. You can only have 1 item in your cart', 'error');
        }

        return $passed;
    }

    /**
     * Add custom cart item data
     */
    public function pick_delivery_add_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        if (isset($_POST['pick_delivery'])) {
            $cart_item_data['delivery_date'] = sanitize_text_field($_POST['pick_delivery']);
        }
        return $cart_item_data;
    }

    /**
     * Display custom item data in the cart
     */
    public function pick_delivery_get_item_data($item_data, $cart_item_data)
    {
        if (isset($cart_item_data['delivery_date'])) {
            $item_data[] = array(
                'key' => __('Delivery Date', 'pick_delivery'),
                'value' => wc_clean($cart_item_data['delivery_date'])
            );
        }
        return $item_data;
    }

    /**
     * Add custom meta to order
     */
    public function pick_delivery_checkout_create_order_line_item($item, $cart_item_key, $values, $order)
    {
        if (isset($values['delivery_date'])) {
            $item->add_meta_data(
                __('Delivery Date', 'pick_delivery'),
                $values['delivery_date'],
                true
            );
        }
    }


    /*
    * Checkout Fields
    */

    public function checkout_fields($checkout)
    {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();

        $data = [];

        if (is_array($items) && !empty($items)) {
            foreach ($items as $item => $values) {
                $data['date'] = $values['delivery_date'];
            }
        }
        woocommerce_form_field('pick_delivery', array(
            'type'        => 'date',
            'required'    => true,
            'label'       => 'Select Delivery Date',
        ), $data['date']);
    }

    // jQuery - Ajax script

    public function checkout_pick_delivery_script()
    {
        // Only on Checkout
        if (is_checkout() && !is_wc_endpoint_url()) :

            if (WC()->session->__isset('pick_delivery'))
                WC()->session->__unset('pick_delivery')
        ?>
            <script type="text/javascript">
                jQuery(function($) {
                    if (typeof wc_checkout_params === 'undefined')
                        return false;

                    $('form.checkout').on('change', 'input[name=pick_delivery]', function() {
                        var pick_delivery = $(this).val();
                        console.log(pick_delivery);
                        $.ajax({
                            type: 'POST',
                            url: wc_checkout_params.ajax_url,
                            data: {
                                'action': 'pick_delivery',
                                'pick_delivery': pick_delivery,
                            },
                            success: function(result) {
                                $('body').trigger('update_checkout');
                            },
                        });
                    });
                });
            </script>
<?php
        endif;
    }
}
