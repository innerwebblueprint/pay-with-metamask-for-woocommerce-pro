<?php
if (!defined('ABSPATH')) {
    exit;
}

$const_msg = $this->cpmwp_const_messages();
$options = get_option('cpmw_settings');
$cpmwp_settings = admin_url() . 'admin.php?page=cpmw-metamask-settings';
$user_wallet = $options['user_wallet'];
$compare_key = $options['crypto_compare_key'];
$openex_key = $options['openexchangerates_key'];
$select_currency = $options['currency_conversion_api'];
$link_html = (current_user_can('manage_options')) ? '.<a href="' . esc_url($cpmwp_settings) . '" target="_blank">' . __("Click here", "cpmwp") . '</a>' . __('to open settings', 'cpmwp') : "";

// Check if custom networks are not set
if (empty($options['custom_networks'])) {
    wc_add_notice('<strong>' . esc_html__("Please save settings once", "cpmwp") . '</strong>', 'error');
    return false;
}

// Check if user wallet is empty
if (empty($user_wallet)) {
    wc_add_notice('<strong>' . esc_html($const_msg['metamask_address']) . wp_kses_post($link_html) . '</strong>', 'error');
    return false;
}

// Check if user wallet address length is not 42
if (!empty($user_wallet) && strlen($user_wallet) != "42") {
    wc_add_notice('<strong>' . esc_html($const_msg['valid_wallet_address']) . wp_kses_post($link_html) . '</strong>', 'error');
    return false;
}

// Check for OpenExchangeRates API key if selected
if ($select_currency == "openexchangerates" && empty($openex_key)) {
    wc_add_notice('<strong>' . esc_html($const_msg['required_fiat_key']) . wp_kses_post($link_html) . '</strong>', 'error');
    return false;
}

// Check if crypto coin is empty
if (empty($_POST['cpmwp_crypto_coin'])) {
    wc_add_notice('<strong>' . esc_html($const_msg['required_currency']) . '</strong>', 'error');
    return false;
}

// Check if payment network is empty
if (empty($_POST['cpmw_payment_network'])) {
    wc_add_notice('<strong>' . esc_html($const_msg['required_network_check']) . '</strong>', 'error');
    return false;
}

$network = sanitize_text_field($_POST['cpmw_payment_network'] ?? "");
$symbol = sanitize_text_field($_POST['cpmwp_crypto_coin'] ?? "");
$total_price = $this->get_order_total();
$get_required_data = $this->cpmwp_get_active_networks_for_currency($symbol, $total_price);
$type = sanitize_text_field($options['currency_conversion_api'] ?? '');
$custom_price = $get_required_data['custom_price'] ?? [];
$active_networks = $get_required_data['active_network'] ?? [];
$custom_tokens_data = isset($custom_price[hexdec($network)]) ? $custom_price[hexdec($network)] : false;
$token_discount_data = isset($get_required_data['token_discount'][$network]) ? $get_required_data['token_discount'][$network] : false;
$in_crypto = $this->cpmwp_price_conversion($total_price, $symbol, $type, $custom_tokens_data, $token_discount_data);

// Check if current balance is less than the required amount to pay
if (isset($_POST['current_balance']) && floatval($_POST['current_balance']) < floatval(str_replace(',', '', $in_crypto))) {
    wc_add_notice('<strong>' . esc_html__('Current Balance:', 'cpmwp') . sanitize_text_field($_POST['current_balance']) . ' ' . esc_html__('Required amount to pay:', 'cpmwp') . esc_html($in_crypto) . '</strong>', 'error');
    return false;
}

// Check if current balance is not set (Wallet not connected)
if (!isset($_POST['current_balance'])) {
    wc_add_notice('<strong>' . esc_html__('Please connect Wallet first', 'cpmwp') . '</strong>', 'error');
    return false;
}

// Check if the selected network is supported
if (!isset($active_networks[$network])) {
    wc_add_notice('<strong>' . esc_html__('Network not supported in this server', 'cpmwp') . '</strong>', 'error');
    return false;
}

return true;
