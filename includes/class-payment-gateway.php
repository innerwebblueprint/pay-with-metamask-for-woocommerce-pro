<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_cpmwp_Gateway extends WC_Payment_Gateway
{
    use CPMWP_HELPER;
    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {
        $optionss = get_option('cpmw_settings');

        $this->id = 'cpmw'; // payment gateway plugin ID
        $this->icon = (isset($optionss['payment_gateway_logo']['url']) && !empty($optionss['payment_gateway_logo']['url'])) ? $optionss['payment_gateway_logo']['url'] : CPMWP_URL . 'assets/images/pay-with-crypto.png'; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = __('MetaMask Pay', 'cpmwp');
        $this->method_description = __('Cryptocurrency Payments Using MetaMask For WooCommerce', 'cpmwp'); // will be displayed on the options page
        // Method with all the options fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = !empty($this->get_option('title')) ? $this->get_option('title') : "MetaMask Pay";      
        $this->description = $this->get_option('custom_description');    
        $this->order_button_text =(isset($optionss['place_order_button']) && !empty($optionss['place_order_button'])) ? $optionss['place_order_button']: __('Pay With Crypto Wallets', 'cpmwp');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'pay_order_page'));
     
        if (!$this->is_valid_for_use()) {
            $this->enabled = 'no';
            add_action('admin_notices',array($this,'notice_for_Not_Supported_Currency'));
        }
        $this->supports = array(
            'products',
            'subscriptions',
            'refunds',
        );
        add_action('woocommerce_order_item_add_action_buttons', array($this, 'wc_order_item_add_action_buttons_callback'), 10, 1);
        //add_action('woocommerce_order_status_changed', array($this, 'check_payment_gateway_status'), 10, 3);

    }

   
    public function check_payment_gateway_status($order_id, $old_status, $new_status)
    {
        $payment_gateway_id = $this->id; // Replace with your payment gateway ID


        $order = wc_get_order($order_id);
        $payment_method = $order->get_payment_method();

        // Check if the payment gateway matches and the order status changed to the required status
        if ($payment_method === $payment_gateway_id) {
            $db=new cpmwp_database();
            $db->update_fields_value($order_id,'status',$new_status);
            // Do something here, like sending an email notification
        }
    }

    public function notice_for_Not_Supported_Currency()
    {
        ?>
                <style>div#message.updated {
                    display: none;
                }</style>
                    <div class="notice notice-error is-dismissible">
                        <p><?php
                _e(' Current WooCommerce store  currency is not supported by Pay With MetaMask For WooCommerce Pro', 'cpmwp');
                ?>
                        </p>
                    </div>

                <?php
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return true;
    }
    public function wc_order_item_add_action_buttons_callback($order)
    {
        $options = get_option('cpmw_settings');
        $user_wallet = $order->get_meta('Sender');
        $user_wallet = isset($user_wallet) ? $user_wallet : "";
        if ($order->get_status() == "refunded" || empty($user_wallet) || ($order->get_payment_method() != "cpmw") || $options['enable_refund'] != 1) {
            return;
        }
       
        $label = (isset($options['refund_order_btn'])&&!empty($options['refund_order_btn']) ) ? $options['refund_order_btn'] : esc_html__('Refund via Crypto wallet', 'cpmwp');
        $slug = 'metamask-pay';
        $const_msg = $this->cpmwp_const_messages();
        $order_id = $order->get_id();
        $options = get_option('cpmw_settings');
        $wallets_address = $this->cpmwp_get_wallet_address();
        $network_name = $this->cpmwp_supported_networks();
        $payment_msg = !empty($options['payment_msg']) ? $options['payment_msg'] : __('Payment Completed Successfully', 'cpmwp');
        $confirm_msg = !empty($options['confirm_msg']) ? $options['confirm_msg'] : __('Confirm Payment in your wallet', 'cpmwp');
        $process_msg = !empty($options['payment_process_msg']) ? $options['payment_process_msg'] : __('Payment in process', 'cpmwp');
        $rejected_msg = !empty($options['rejected_message']) ? $options['rejected_message'] : __('Transaction Rejected ', 'cpmwp');
        $network = $order->get_meta('cpmwp_network');
        $sig_token_address = $order->get_meta('cpmwp_contract_address');
        $redirect = !empty($options['redirect_page']) ? $options['redirect_page'] : '';
        $total = $order->get_total();
        $nonce = wp_create_nonce('cpmwp_metamask_pay');
        $currency_symbol = $order->get_meta('cpmwp_currency_symbol');
        $selected_wallet = $order->get_meta('cpmwp_selected_wallet');
        $selected_wallet = !empty($selected_wallet) ? $selected_wallet : 'ethereum';
        $type = $options['currency_conversion_api'];
        // $in_crypto = $this->cpmwp_price_conversion($total, $currency_symbol, $type);

        $in_crypto = $order->get_meta('cpmwp_in_crypto');
        $currency = $order->get_currency();
        $current_price = CPMWP_API_DATA::cpmwp_crypto_compare_admin_api($currency, $currency_symbol);
        $current_price_array = (array) $current_price;
        $lastprice = isset($current_price_array[$currency]) ? $current_price_array[$currency] : 1;
        $payment_status = $order->get_status();
        $add_networks = $this->cpmwp_add_networks();
        $add_networks = isset($add_networks[$network]) ? json_encode($add_networks[$network]) : '';
        $add_tokens = $this->cpmwp_add_tokens();
        $token_address = isset($add_tokens[$network][$currency_symbol]) ? $add_tokens[$network][$currency_symbol] : '';
        $transaction_id = (!empty($order->get_meta('TransactionId'))) ? $order->get_meta('TransactionId') : '';
        $get_default_currency = $this->cpmwp_get_default_currency();
        $block_explorer = $this->cpmwp_get_explorer_url();
        $wallet_logos = array('ethereum' => CPMWP_URL . 'assets/images/metamask.png', 'trustwallet' => CPMWP_URL . 'assets/images/trustwallet.png', 'BinanceChain' => CPMWP_URL . 'assets/images/binancewallet.png', 'wallet_connect' => CPMWP_URL . 'assets/images/walletconnect.png');
        $image_url = $wallet_logos[$selected_wallet];
        $infura_id = isset($options['infura_project_id']) ? $options['infura_project_id'] : "";
        $project_id = isset($options['project_id']) ? $options['project_id'] : '';
        $filePaths = glob(CPMWP_PATH . '/assets/pay-with-metamask/build/refund' . '/*.php');
        $fileName = pathinfo($filePaths[0], PATHINFO_FILENAME);
        $jsbuildUrl=str_replace('.asset','',$fileName);
    $secret_key = $this->cpmwp_get_secret_key();
    $tx_req_data = json_encode(
        array(
            'order_id' => (int)$order_id,
            'selected_network' => $network,
            'receiver' => strtoupper($user_wallet),           
            'token_address' => strtoupper($sig_token_address)
        )
    );
    $signature = hash_hmac('sha256', $tx_req_data, $secret_key);
        wp_enqueue_script( 'cpmwp_custom', CPMWP_URL . 'assets/pay-with-metamask/build/refund/'.$jsbuildUrl.'.js', array( 'wp-element' ), CPMWP_VERSION, true );
        wp_localize_script(
            'cpmwp_custom',
            'extradata',
            array(
                'url' => CPMWP_URL,
                'last_price' => $lastprice,
                'label'=>$label,
                'restUrl' => get_rest_url() . 'pay-with-metamask/v1/',
                'selected_wallet' => $selected_wallet,
                'fiat_symbol' => get_woocommerce_currency_symbol($currency),
                'in_fiat' => $order->get_remaining_refund_amount(),
                'network_name' => isset($network_name[$network])?$network_name[$network]:'',
                'default_currency' =>isset($get_default_currency[$network])? $get_default_currency[$network]:"",
                'token_address' => $token_address,
                'block_explorer' => $block_explorer[$network],
                'network_data' => $add_networks,
                'transaction_id' => $transaction_id,
                'const_msg' => $const_msg,
                'currency_symbol' => $currency_symbol,
                'confirm_msg' => $confirm_msg,
                'network' => $network,
                'decimalchainId' => isset($network) ? hexdec($network) : false,
                'wallet_image' => $image_url,
                'is_paid' => $order->is_paid(),
                'nonce' => wp_create_nonce('wp_rest'),
                'process_msg' => $process_msg,
                'payment_msg' => $payment_msg,
                'rejected_msg' => $rejected_msg,
                'in_crypto' => $in_crypto,
                'recever' => $user_wallet,               
                'order_status' => $payment_status,
                'id' => $order_id,
                'signature' => $signature,
                "ccpw_wc_id" => $project_id,
                "rpc_urls" => $this->cpmwp_get_settings('rpcUrls'),
                "wallet_logos" => $wallet_logos,               
                'payment_status' => $options['payment_status'],
            )
        );
        wp_enqueue_style('cpmwp_custom_css', CPMWP_URL . 'assets/css/refund.css', array(), CPMWP_VERSION, null, 'all');
        ?>
        <span class="cpmwp_refund_btn" id="cpmwp_refund_btn"></span>
    
    <?php

    }

    public function is_valid_for_use()
    {
        if (in_array(get_woocommerce_currency(), apply_filters('cpmwp_supported_currencies', $this->cpmwp_supported_currency()))) {
            return true;
        }

        return false;
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable MetaMask Pay',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'yes',
            ),

            'title' => array(
                'title' => __('Title', 'cpmwp'),
                'type' => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', 'cpmwp'),
                'default' => __('Pay With Cryptocurrency','cpmwp'),
                'desc_tip' => false,
            ),
            'custom_description' => array(
                'title' => 'Description',
                'type' => 'text',
                'description' => 'Add custom description for checkout payment page',         

            ),

        );

    }

    public function payment_fields()
    {
        require_once CPMWP_PATH . 'includes/html/checkout-fields.php';
    }

    public function validate_fields()
    {
        require_once CPMWP_PATH . 'includes/html/validate-fields.php';
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        try {
            $order = new WC_Order($order_id);
            $settings_obj = get_option('cpmw_settings');
            $crypto_wallet = !empty($_POST['cpmwp_crypto_wallets']) ? sanitize_text_field($_POST['cpmwp_crypto_wallets']) : 'ethereum';
            $crypto_currency = !empty($_POST['cpmwp_crypto_coin']) ? sanitize_text_field($_POST['cpmwp_crypto_coin']) : '';
            $selected_network = !empty($_POST['cpmw_payment_network']) ? sanitize_text_field($_POST['cpmw_payment_network']) : '';
            $total = $order->get_total();
            $type = $settings_obj['currency_conversion_api'];
            $get_required_data = $this->cpmwp_get_custom_price($selected_network);
            $DecChainId = isset($selected_network) ? hexdec($selected_network) : false;
            $wallets_address      = $this->cpmwp_get_wallet_address();
            $add_tokens           = $this->cpmwp_add_tokens();
            $custom_price = $get_required_data['custom_price'];
            $token_discount = $get_required_data["token_discount"];
            $token_discount_data = isset($token_discount[$crypto_currency]) ? $token_discount[$crypto_currency] : false;
            $custom_tokens_data = isset($custom_price[$crypto_currency]) ? $custom_price[$crypto_currency] : false;
            $token_address        = isset( $add_tokens[ $selected_network][ $crypto_currency] ) ? $add_tokens[ $selected_network][ $crypto_currency] :$crypto_currency;
            $in_crypto = $this->cpmwp_price_conversion($total, $crypto_currency, $type, $custom_tokens_data, $token_discount_data,$DecChainId);
            $wihout_discount=$token_discount_data?$this->cpmwp_price_conversion($total, $crypto_currency, $type, $custom_tokens_data, false,$DecChainId):false;
            $user_wallet =( isset( $wallets_address[ $selected_network ] ) && ! empty( $wallets_address[ $selected_network ] ) ) ? $wallets_address[ $selected_network] : $settings_obj['user_wallet'];
            $order->update_meta_data('cpmwp_selected_wallet', $crypto_wallet);

            $tx_exists=$order->get_meta('transaction_id');
  
            if('' === $tx_exists){
                $order->update_meta_data('cpmwp_in_crypto', str_replace( ',', '',$in_crypto));
            }

            if($wihout_discount){
                $order->update_meta_data('without_discount_price', str_replace( ',', '', $wihout_discount));
            }else{
                $order->delete_meta_data('without_discount_price');
            }         
            $order->update_meta_data('cpmwp_currency_symbol', $crypto_currency);
            $order->update_meta_data('cpmwp_user_wallet', $user_wallet);
            $order->update_meta_data('cpmwp_network', $selected_network);
            $order->update_meta_data('cpmwp_contract_address',  $token_address);
            $order->save_meta_data();       
          //  $woocommerce->cart->empty_cart();
            $url = $order->get_checkout_payment_url(true);
            return array(
                'result' => 'success',
                'redirect' => $url,//$this->get_return_url($order)
            );

        } catch (Exception $e) {
            wc_add_notice(__('Payment error:', 'cpmwp') . 'Unknown coin', 'error');
            return null;
        }
        wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again', 'cpmwp'), 'error');
        return null;
    }

    public function pay_order_page($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order->is_paid()) {
            wp_redirect($order->get_checkout_order_received_url());
        } else {
            require_once CPMWP_PATH . 'includes/html/process-order.php';
        }
      

    }

   

}
