<?php
/*
Plugin Name: xWallet by xUU Labs
Plugin URI:   
Description: Scan and pay with xWallet mobile payment app
Version: 1.0
Author:
Author URI:
License:
License URI:
Text Domain:
Domain Path:
*/

global $currencies;
$currencies = [
    [
        'id' => 'usd', 
        'name'=> 'US Dollar (USD)',
        'token_name' => 'xUU USD',
        'token_symbol' => 'xUSD',
        'token_decimals'=> 2,
        'token_contract'=> '0x98C5bE44b7a811897D188f7F661861c245a47DEC'
    ], [
        'id' => 'cad',
        'name'=> 'Canadian Dollar (CAD)',
        'token_name' => 'xUU CAD',
        'token_symbol' => 'xUSD',
        'token_decimals'=> 2,
        'token_contract'=> '0x1Fe7134a652D7515D6e697D45bb4e94d3bEa127A'
    ], [
        'id' => 'eur',
        'name'=> 'Euro (EUR)',
        'token_name' => 'xUU EUR',
        'token_symbol' => 'xUSD',
        'token_decimals'=> 2,
        'token_contract'=> '0x169513c4Cfc15619D0DF0A3fC2b5A80251Fa92c7'
    ], [
        'id' => 'huf',
        'name'=> 'Hungarian Forint (HUF)',
        'token_name' => 'xUU HUF',
        'token_symbol' => 'xUSD',
        'token_decimals'=> 0,
        'token_contract'=> '0x8Be3F9eD28ACB991fc0d735EbAB4907B6e48F948'
    ], [
        'id' => 'xbeta',
        'name'=> 'xUU BETA (xBETA)',
        'token_name' => 'xUU xBETA',
        'token_symbol' => 'xUSD',
        'token_decimals'=> 2,
        'token_contract'=> '0x847eCB5DCBA24946A55a7dd4A526f429caDFF6fC'
    ],
];

add_filter( 'woocommerce_payment_gateways', 'x_wallet_add_gateway_class' );
function x_wallet_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_xWallet_Gateway'; // your class name is here
	return $gateways;
}

add_action( 'plugins_loaded', 'x_wallet_init_gateway_class' );
function x_wallet_init_gateway_class() {

	class WC_xWallet_Gateway extends WC_Payment_Gateway {

 		public function __construct() {
            $this->id = 'xwallet'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'xWallet by xUU Labs';
            $this->method_description = 'Scan and pay with xWallet mobile payment app';

            $this->supports = array(
                'products'
            );
        
            // Method with all the options fields
            $this->init_form_fields();
        
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->banner_photo = $this->get_option( 'banner_photo' );
            $this->profile_photo = $this->get_option( 'profile_photo' );
            $this->merchant_wallet = $this->get_option( 'merchant_wallet' );
            $this->public_name = $this->get_option( 'public_name' );
            $this->currency = $this->get_option( 'currency' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            // $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
            // $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
        
            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
            wp_enqueue_media();
            // wp_localize_script('custom-order-script', 'customOrderData', ['ajaxUrl' => admin_url('admin-ajax.php')]);
            wp_enqueue_style( 'xwallet-style', plugin_dir_url( __FILE__ ) . 'style.css', '1.0', true );
            wp_enqueue_script( 'xwallet-script', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), '1.0', true );
            wp_localize_script('xwallet-script', 'customAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom_ajax_nonce'),
            ));
             
            add_action('woocommerce_thankyou_xwallet',  array($this, 'add_qrcode_section'), 20);

            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );

            
        } 

		public function init_form_fields(){
            global $currencies;
            
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable xWallet by xUU Labs payment method',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'banner_photo' => array(
                    'title' => 'Banner Photo',
                    'type' => 'image_upload',
                    'description' => 'Please upload a photo to be used for banner.',
                ),
                'profile_photo' => array(
                    'title' => 'Profile Photo',
                    'type' => 'image_upload',
                    'description' => 'Please upload a photo to be used for profile.',
                ),
                'public_name' => array(
                    'title'       => 'Public Name',
                    'type'        => 'text',
                    'description' => '',
                    'default'     => '',
                ),
                'currency' => array(
                    'title' => 'Currency',
                    'type' => 'select',
                    'description' => 'Currency that will be used for this plugin',
                    'default' => 'usd',
                    'options' => array_column($currencies, 'name', 'id'),
                    'desc_tip' => true,
                ),
                'merchant_wallet' => array(
                    'title'       => 'Merchant wallet address',
                    'type'        => 'text',  
                ),
                // 'title' => array(
                //     'title'       => 'Title',
                //     'type'        => 'text',
                //     'description' => 'This controls the title which the user sees during checkout.',
                //     'default'     => 'xWallet by xUU Labs',
                //     'desc_tip'    => true,
                // ),
                // 'description' => array(
                //     'title'       => 'Description',
                //     'type'        => 'textarea',
                //     'description' => 'This controls the description which the user sees during checkout.',
                //     'default'     => 'Scan and pay with xWallet mobile payment app',
                // ), 
            );
	 	}

		public function payment_fields() {
            if ( $this->description ) {
                echo wpautop( wp_kses_post( $this->description ) );
            }
        
		}

		public function payment_scripts() {
            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
                return;
            }

            if ( 'no' === $this->enabled ) {
                return;
            }

            if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
                return;
            }

            if ( ! $this->testmode && ! is_ssl() ) {
                return;
            }

            // wp_enqueue_script( 'misha_js', 'https://www.mishapayments.com/api/token.js' );

            // wp_register_script( 'woocommerce_misha', plugins_url( 'misha.js', __FILE__ ), array( 'jquery', 'misha_js' ) );

            // wp_localize_script( 'woocommerce_misha', 'misha_params', array(
            //     'publishableKey' => $this->publishable_key
            // ) );

            // wp_enqueue_script( 'woocommerce_misha' );
	 	}

		public function validate_fields() {
            return true;
		}

		public function process_payment( $order_id ) {
            global $woocommerce;
            $order = wc_get_order( $order_id ); 

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );

 
            // we need it to get any order detailes
         
            /*
             * Array with parameters for API interaction
             */
            $args = array();
         
            /*
             * Your API interaction could be built with wp_remote_post()
            */
            $response = wp_remote_post( '{payment processor endpoint}', $args );
         
         
            if( !is_wp_error( $response ) ) {
         
                $body = json_decode( $response['body'], true );
         
                // it could be different depending on your payment processor
                if ( $body['response']['responseCode'] == 'APPROVED' ) {
         
                    // we received the payment
                    $order->payment_complete();
                    $order->reduce_order_stock();
         
                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
         
                    // Empty cart
                    $woocommerce->cart->empty_cart();
         
                    // Redirect to the thank you page
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url( $order )
                    );
         
                 } else {
                    wc_add_notice(  'Please try again.', 'error' );
                    return;
                }
         
            } else {
                wc_add_notice(  'Connection error.', 'error' );
                return;
            }
	 	}

		public function webhook() {
            $order = wc_get_order( $_GET['id'] );
            $order->payment_complete();
            $order->reduce_order_stock();
        
            update_option('webhook_debug', $_GET);
        }
        
        public function generate_image_upload_html($key, $field) {
            $value = $this->get_option($key);
            $image_url = wp_get_attachment_url($value);
        
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php echo esc_html($field['title']); ?></th>
                <td class="forminp">
                    <div class="image-upload-field">
                        <input type="hidden" name="woocommerce_<?php echo $this->id; ?>_<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                        <input type="button" class="button image-upload-button" value="Upload Image">
                        <div class="image-preview">
                            <?php if (!empty($image_url)) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="Preview Image">
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        } 

        public function process_admin_options() {
            parent::process_admin_options();
    
            $settings = $this->get_form_fields();
            $updated_settings = [
                'title' => 'xWallet by xUU Labs',
                'description' => 'Scan and pay with xWallet mobile payment app'
            ];
            
            foreach ($settings as $key => $setting) {
                $k = 'woocommerce_'.$this->id . '_'. $key;
                if ($setting['type'] === 'image_upload') {
                    $image_id = isset($_POST[$k]) ? absint($_POST[$k]) : '';
                    $updated_settings[$key] = $image_id;
                } else {
                    if ($key == 'enabled') {
                        $updated_settings[$key] = isset($_POST[$k]) ? wc_clean($_POST[$k] ? 'yes' : 'no') : '';
                    } else {
                        $updated_settings[$key] = isset($_POST[$k]) ? wc_clean($_POST[$k]) : '';
                    }
                }
            }
            
            // print_r([$_POST, $settings, $updated_settings]);exit;
            $this->settings = $updated_settings;
            update_option($this->plugin_id . $this->id . '_settings', $updated_settings);
        } 

        function add_qrcode_section($order_id) {
            global $currencies;
            require_once('phpqrcode-master/qrlib.php');
            // Get the order object
            $order = wc_get_order($order_id);
            
            if (extension_loaded('gd') && function_exists('gd_info')) {
                $curr = null;
                foreach ($currencies as $currency) {
                    if ($currency['id'] == $this->currency) {
                        $curr = $currency;
                        break;
                    }
                }

                $text = 'ethereum:'.$curr['token_contract'];
                $text .= '@137/transfer?address='.$this->merchant_wallet;
                $text .= '&unit256='.$order->get_total();
                $text .= 'e'.$curr['token_decimals'];

                $path = plugin_dir_path(__FILE__) . 'output.png';
                QRcode::png($text, $path, QR_ECLEVEL_H, 6);
            } else {
                echo 'GD library is not enabled.';
            } 
            
        ?>
			<h2>xWallet payment request</h2>
            <div class="qrcode-section-wrapper">
                <div class="qrcode-section">
                    <div class="banner">
                        <img src="<?php echo wp_get_attachment_url($this->banner_photo); ?>" alt="banner" />
                    </div>
                    <img class="avatar" src="<?php echo wp_get_attachment_url($this->profile_photo); ?>" alt="profile" />
                    <div style="text-align: center;">
                        <div class="public-name"><?php echo $this->public_name; ?></div>
                        <div class="price-text"><?php echo $order->get_formatted_order_total(); ?></div>
                    </div>
                    <div class="qrcode">
                        <img src="<?php echo plugin_dir_url(__FILE__) . 'output.png'; ?>" alt="QR Code">
                        <div class="qrcode-logo">
                            <img src="<?php echo plugin_dir_url( __FILE__ ); ?>logo.png" alt="logo">
                        </div>
                        <div class="qrcode-scan">SCAN & PAY</div>
                    </div>
                    <div class="buttons">
                        <input type="hidden" id="order_id_inp" value="<?php echo $order->id; ?>"/>
                        <button class="btn_action" id="done">Done</button>
                        <button class="btn_action" id="cancel">Cancel Order</button>
                    </div>
                </div>
            </div>
        <?php 
        }
 	}
}

 
add_action('wp_ajax_change_order_status', 'change_order_status');
add_action('wp_ajax_nopriv_change_order_status', 'change_order_status');
function change_order_status() {
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = $_POST['order_id'];
        $status = sanitize_text_field($_POST['status']);
        $order = wc_get_order($order_id);

        // Update the order status
        $order->update_status($status);
        wp_safe_redirect(get_permalink(wc_get_page_id('shop')));
    }
    wp_die();
}