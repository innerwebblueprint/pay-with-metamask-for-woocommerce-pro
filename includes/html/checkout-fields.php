<?php
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('cpmw_settings', []);

// Check if custom networks are not set
if (!isset($options['custom_networks'])) {
    echo '<strong>Please save settings once</strong>';
    return false;
}

$cpmwp_settings = esc_url(admin_url('admin.php?page=cpmw-metamask-settings'));
$user_wallet = sanitize_text_field($options['user_wallet'] ?? '');
$compare_key = sanitize_text_field($options['crypto_compare_key'] ?? '');
$openex_key = sanitize_text_field($options['openexchangerates_key'] ?? '');
$select_currency = sanitize_text_field($options['currency_conversion_api'] ?? '');
$link_html = (current_user_can('manage_options')) ? '<a href="' . esc_url($cpmwp_settings) . '" target="_blank">' . esc_html("Click here", "cpmwp") . '</a>' . esc_html__(' to open settings', 'cpmwp') : "";

// Output error messages if conditions are not met
if (empty($user_wallet)) {
    echo '<strong>' . esc_html($const_msg['metamask_address']) . wp_kses_post($link_html) . '</strong>';
    return false;
}

if (!empty($user_wallet) && strlen($user_wallet) != "42") {
    echo '<strong>' . esc_html($const_msg['valid_wallet_address']) . wp_kses_post($link_html) . '</strong>';
    return false;
}

if ($select_currency == "cryptocompare" && empty($compare_key)) { // Fixed variable name here
    echo '<strong>' . esc_html($const_msg['required_fiat_key']) . wp_kses_post($link_html) . '</strong>';
    return false;
}

$select_currency_lbl = esc_html($options['select_a_currency'] ?? __('Please Select a Currency', 'cpmwp'));
$select_network_lbl = esc_html($options['select_a_network'] ?? __('Select Payment Network', 'cpmwp'));
$type = sanitize_text_field($options['currency_conversion_api'] ?? '');
$currency = get_woocommerce_currency();

// Set the logo URL
$logo_url = esc_url($options['payment_gateway_logo']['url'] ?? CPMWP_URL . 'assets/images/metamask.png');

// Set Metamask label colors
$metamask_label_color = sanitize_hex_color($options['cpmwp_login_btn_color'] ?? "");
$metamask_label_bg_color = sanitize_hex_color($options['cpmwp_login_btn_bg_color'] ?? "");

$total_price = $this->get_order_total();

$project_id = sanitize_text_field($options['project_id'] ?? '');

// Get required data for active currencies
$const_msg = $this->cpmwp_const_messages();
$get_required_data = $this->cpmwp_get_active_currencies();
$crypto_currency = $get_required_data['enabled_currency'];
$default_currency = $get_required_data['default_currency'];
$token_discount = $get_required_data['token_discount'];
$custom_price = $get_required_data['custom_price'];
$count_currency = $get_required_data['count_currency'];
$enabledCurrency = array();
// Create an array of enabled currencies with their symbol, price, and logo URL
$error = '';
foreach ($crypto_currency as $key => $value) {
    $image_url = esc_url($this->cpmwp_get_coin_logo($value));
    $custom_tokens_data = (isset($count_currency[$value]) && isset($custom_price[$value]) && $count_currency[$value] == 1) ? $custom_price[$value] : false;
    $token_discount_data = false;
    $in_crypto = $this->cpmwp_price_conversion($total_price, $value, $type, $custom_tokens_data, $token_discount_data);
    if (isset($in_crypto['restricted'])) {
        $error = esc_html($in_crypto['restricted']);
        break; // Exit the loop if the API is restricted.
    }
    $enabledCurrency[$value] = array('symbol' => $value, 'price' => $in_crypto, 'url' => $image_url);
}

$add_networkswagmi = $this->cpmwp_add_networks_wagmi();
$nonce = wp_create_nonce('cpmw_metamask_currency');
// Use glob to get an array of file names in the folder
$filePaths = glob(CPMWP_PATH . '/assets/pay-with-metamask/build/checkout' . '/*.php');
$fileName = pathinfo($filePaths[0], PATHINFO_FILENAME);
$jsbuildUrl = str_replace('.asset', '', $fileName);
$const_msg = $this->cpmwp_const_messages();

// Enqueue the connect wallet script
wp_enqueue_script('cpmw_connect_wallet', CPMWP_URL . 'assets/pay-with-metamask/build/checkout/' . $jsbuildUrl . '.js', array('wp-element'), CPMWP_VERSION, true);

// Localize the connect wallet script with required data
wp_localize_script('cpmw_connect_wallet', "connect_wallts", array(
    'total_price' => $total_price,
    'ccpw_wc_id' => $project_id,
    'logo_url' => $logo_url,
    'api_type' => $type,
    'nonce' => wp_create_nonce('wp_rest'),
    'restUrl' => get_rest_url() . 'pay-with-metamask/v1/',
    'currency_lbl' => $select_currency_lbl,
    'network_lbl' => $select_network_lbl,
    'const_msg' => $const_msg,
    'text_color' => $metamask_label_color,
    'label_bg_color' => $metamask_label_bg_color,
    'enabledCurrency' => $enabledCurrency,
    'enabledWallets' => $options['supported_wallets'],
));
// Enqueue the checkout stylesheet
wp_enqueue_style('cpmwp-checkout', CPMWP_URL . 'assets/css/checkout.css', null, CPMWP_VERSION);
do_action('woocommerce_cpmwp_form_start', $this->id);

if ($error) {
    echo esc_html($error);
}

// Output supported wallets if available
if (isset($options['supported_wallets']) && is_array($options['supported_wallets']) && !$error) {
    if ($this->description) {
        echo '<div class="cpmwp_gateway_desc">' . esc_html($this->description) . '</div>';
    }
    echo '<div class="cpmwp-supported-wallets-wrap">';
    echo '<div class="cpmwp-supported-wallets" id="cpmwp-connect-wallets">';
    echo '<div class="cegc-ph-item">';
    echo '<div class="cegc-ph-col-12">';
    echo '<div class="ph-row">';
    echo '<div class="cegc-ph-col-6 big"></div>';
    echo '<div class="cegc-ph-col-4  big"></div>';
    echo '<div class="cegc-ph-col-2 big"></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

?>


<?php
do_action('woocommerce_cpmw_form_end', $this->id);
?>
