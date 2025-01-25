<?php
if (!defined('ABSPATH')) {
    exit;
}

// Initialize constant messages and options
$const_msg = $this->cpmwp_const_messages();
$options = get_option('cpmw_settings');
$wallets_address = $this->cpmwp_get_wallet_address();
$network_name = $this->cpmwp_supported_networks();

// Get order and payment details
$order = new WC_Order($order_id);
$payment_msg = !empty($options['payment_msg']) ? sanitize_text_field($options['payment_msg']) : __('Payment Completed Successfully', 'cpmwp');
$confirm_msg = !empty($options['confirm_msg']) ? sanitize_text_field($options['confirm_msg']) : __('Confirm Payment Inside Your Wallet!', 'cpmwp');
$process_msg = !empty($options['payment_process_msg']) ? sanitize_text_field($options['payment_process_msg']) : __('Payment in process', 'cpmwp');
$rejected_msg = !empty($options['rejected_message']) ? sanitize_text_field($options['rejected_message']) : __('Transaction Rejected ', 'cpmwp');
$network = sanitize_text_field($order->get_meta('cpmwp_network'));
$redirect = !empty($options['redirect_page']) ? esc_url($options['redirect_page']) : '';
$total = wc_format_decimal($order->get_total());
$nonce = wp_create_nonce('cpmwp_metamask_pay' . $order_id);
$user_wallet = (isset($wallets_address[$network]) && !empty($wallets_address[$network])) ? sanitize_text_field($wallets_address[$network]) : sanitize_text_field($order->get_meta('cpmwp_user_wallet'));
$in_crypto = sanitize_text_field($order->get_meta('cpmwp_in_crypto'));
$currency_symbol = sanitize_text_field($order->get_meta('cpmwp_currency_symbol'));
$selected_wallet = sanitize_text_field($order->get_meta('cpmwp_selected_wallet'));
$without_discount_price = sanitize_text_field($order->get_meta('without_discount_price'));
$payment_status = sanitize_text_field($order->get_status());
$add_networks = $this->cpmwp_add_networks_wagmi();
$add_networks = isset($add_networks[$network]) ? wp_json_encode($add_networks[$network]) : '';
$add_tokens = $this->cpmwp_add_tokens();
$token_address = isset($add_tokens[$network][$currency_symbol]) ? sanitize_text_field($add_tokens[$network][$currency_symbol]) : '';
$sig_token_address = sanitize_text_field($order->get_meta('cpmwp_contract_address'));
$transaction_id = sanitize_text_field($order->get_meta('TransactionId'));
$get_default_currency = $this->cpmwp_get_default_currency();
$place_order_button = (isset($options['place_order_button']) && !empty($options['place_order_button'])) ? sanitize_text_field($options['place_order_button']) : __('Pay With Crypto Wallets', 'cpmwp');

// Define wallet logos securely
$wallet_logos = array(
    'ethereum' => esc_url(CPMWP_URL . 'assets/images/metamask.png'),
    'trustwallet' => esc_url(CPMWP_URL . 'assets/images/trustwallet.png'),
    'BinanceChain' => esc_url(CPMWP_URL . 'assets/images/Binance-wallet.png'),
    'wallet_connect' => esc_url(CPMWP_URL . 'assets/images/walletconnect.png'),
);

// Get additional configuration options securely
$infura_id = isset($options['infura_project_id']) ? sanitize_text_field($options['infura_project_id']) : '';
$project_id = isset($options['project_id']) ? sanitize_text_field($options['project_id']) : '';
$block_explorer = $this->cpmwp_get_explorer_url();
$image_url = $wallet_logos[$selected_wallet];
$connect_btn_text = '';
$connected_wallet = '';
$WalletCenabled = (isset($options['supported_wallets']['wallet_connect']) && $options['supported_wallets']['wallet_connect'] == '1') ? true : false;
$shop_page_url = esc_url(get_permalink(wc_get_page_id('shop')));

// Set wallet-specific texts
switch ($selected_wallet) {
    case 'ethereum':
        $connect_btn_text = sanitize_text_field($const_msg['metamask_connect']);
        $connected_wallet = sanitize_text_field($const_msg['metamask_wallet']);
        break;
    case 'wallet_connect':
        $connect_btn_text = sanitize_text_field($const_msg['connect_wallet_connect']);
        $connected_wallet = sanitize_text_field($const_msg['wallet_connect']);
        break;
    default:
        $connect_btn_text = sanitize_text_field($const_msg['metamask_connect']);
        $connected_wallet = sanitize_text_field($const_msg['metamask']);
        break;
}

// Get secret key and create transaction request data
$secret_key = $this->cpmwp_get_secret_key();
$tx_req_data = wp_json_encode(
    array(
        'order_id' => (int)$order_id,
        'selected_network' => $network,
        'receiver' => strtoupper($user_wallet),
        'amount' => str_replace(',', '', $in_crypto),
        'token_address' => strtoupper($sig_token_address)
    )
);
$signature = hash_hmac('sha256', $tx_req_data, $secret_key);
$filePaths = glob(CPMWP_PATH . '/assets/pay-with-metamask/build/main' . '/*.php');
$fileName = pathinfo($filePaths[0], PATHINFO_FILENAME);
$jsbuildUrl = str_replace('.asset', '', $fileName);
// Enqueue scripts and localize data securely
wp_enqueue_script('cpmwp_react_widget', CPMWP_URL . 'assets/pay-with-metamask/build/main/' . $jsbuildUrl . '.js', array('wp-element'), CPMWP_VERSION, true);
wp_localize_script(
    'cpmwp_react_widget',
    'extradataRest',
    array(
        'url' => esc_url(CPMWP_URL),
        'gatewayTitle' => sanitize_text_field($this->title),
        'restUrl' => esc_url(get_rest_url() . 'pay-with-metamask/v1/'),
        'fiatSymbol' => get_woocommerce_currency_symbol(),
        'ccpw_wc_id' => $project_id,
        'totalFiat' => $total,
        'connectedWallet' => $connected_wallet,
        'connectButton' => $connect_btn_text,
        'WCenabled' => $WalletCenabled,
        'supported_networks' => $network_name,
        'selected_wallet' => $selected_wallet,
        'block_explorer' => esc_url($block_explorer[$network]),
        'network_name' => $network_name[$network],
        'default_currency' => isset($get_default_currency[$network]) ? $get_default_currency[$network] : '',
        'token_address' => $token_address,
        'network_data' => $add_networks,
        'transaction_id' => $transaction_id,
        'const_msg' => $const_msg,
        'redirect' => $redirect,
        'wallet_image' => $image_url,
        'currency_symbol' => $currency_symbol,
        'currency_logo' => $this->cpmwp_get_coin_logo($currency_symbol),
        'confirm_msg' => $confirm_msg,
        'network' => $network,
        'decimalchainId' => isset($network) ? hexdec($network) : false,
        'is_paid' => $order->is_paid(),
        'process_msg' => $process_msg,
        'payment_msg' => $payment_msg,
        'rejected_msg' => $rejected_msg,
        'place_order_btn' => $place_order_button,
        'in_crypto' => str_replace(',', '', $in_crypto),
        'without_discount' => $without_discount_price ?? false,
        'recever' => $user_wallet,
        'ajax' => admin_url('admin-ajax.php'),
        'order_status' => $payment_status,
        'id' => $order_id,
        'infura_id' => $infura_id,
        'rpc_urls' => $this->cpmwp_get_settings('rpcUrls'),
        'nonce' => wp_create_nonce('wp_rest'),
        'wallet_logos' => $wallet_logos,
        'payment_status' => $options['payment_status'],
        'shop_page' => $shop_page_url,
        'signature' => $signature,
        'enabledWallets' => $options['supported_wallets']
    )
);

// Enqueue custom CSS
wp_enqueue_style('cpmwp_custom_css', CPMWP_URL . 'assets/css/style.css', null, CPMWP_VERSION);

$trasn_id = sanitize_text_field($order->get_meta('TransactionId'));

// Display payment information
echo '<div class="cmpw_meta_connect" id="cmpw_meta_connect">
<div class="ccpwp-card">
<div class="ccpwp-card__image ccpwp-loading"></div>
<div class="ccpwp-card__title ccpwp-loading"></div>
<div class="ccpwp-card__description ccpwp-loading"></div>
</div>
</div>';
?>

<section class="cpmwp-woocommerce-woocommerce-order-details">
<h2 class="woocommerce-order-details__title"><?php echo esc_html(__('Crypto payment details', 'cpmwp')); ?></h2>
<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
    <tbody>
        <tr>
            <th scope="row"> <?php echo esc_html(__('Price:', 'cpmwp')); ?></th>
            <td><?php echo esc_html($in_crypto . ' ' . $currency_symbol); ?></td>
        </tr>
        <tr>
            <th scope="row"> <?php echo esc_html(__('Payment Status', 'cpmwp')); ?></th>
            <td class="cpmwp_statu_<?php echo esc_attr($order->get_status()); ?>"><?php echo esc_html($order->get_status()); ?></td>
        </tr>
        <?php
        if (!empty($trasn_id) && $trasn_id != 'false') {
        ?>
            <tr>
                <th scope="row"> <?php echo esc_html(__('Transaction id:', 'cpmwp')); ?></th>
                <td><?php echo '<a href="' . esc_url($block_explorer[$network]) . 'tx/' . esc_attr($trasn_id) . '" target="_blank">' . esc_html($trasn_id) . '</a>'; ?></td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>
</section>
