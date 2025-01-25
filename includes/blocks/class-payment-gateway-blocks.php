<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * cpmw Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_cpmwp_Gateway_Blocks_Support extends AbstractPaymentMethodType {

	use CPMWP_HELPER;

	/**
	 * The gateway instance.
	 *
	 * @var WC_cpmwp_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'cpmw';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_cpmw_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$filePaths         = glob( CPMWP_PATH . '/assets/pay-with-metamask/build/block' . '/*.php' );
		$fileName          = pathinfo($filePaths[0], PATHINFO_FILENAME);
		$jsbuildUrl        = str_replace('.asset', '', $fileName);
		$script_path       = 'assets/pay-with-metamask/build/block/' . $jsbuildUrl . '.js';
		$script_asset_path = CPMWP_PATH . 'assets/pay-with-metamask/build/block/' . $jsbuildUrl . '.asset.php';
		$script_asset      = file_exists($script_asset_path)
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => CPMWP_VERSION,
		);
		$script_url        = CPMWP_URL . $script_path;

		wp_register_script(
			'wc-cpmw-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_enqueue_style('cpmwp-checkout', CPMWP_URL . 'assets/css/checkout.css', null, CPMWP_VERSION);
		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('wc-cpmw-payments-blocks', 'woocommerce-gateway-cpmw', CPMWP_PATH . 'languages/');
		}

		return array('wc-cpmw-payments-blocks');
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$options = get_option('cpmw_settings');

		// Check if custom networks are not set
		if (!isset($options['custom_networks'])) {
			echo ('<strong>Please save settings once</strong>');
			return false;
		}

		$select_currency_lbl = isset($options['select_a_currency']) && !empty($options['select_a_currency']) ? esc_html($options['select_a_currency']) : esc_html__('Please Select a Currency', 'cpmwp');
		$select_network_lbl = isset($options['select_a_network']) && !empty($options['select_a_network']) ? esc_html($options['select_a_network']) : esc_html__('Select Payment Network', 'cpmwp');
		$type = sanitize_text_field($options['currency_conversion_api']);
		$currency = get_woocommerce_currency();

		// Set the logo URL
		$logo_url = isset($options['payment_gateway_logo']['url']) && !empty($options['payment_gateway_logo']['url']) ? esc_url($options['payment_gateway_logo']['url']) : CPMWP_URL . 'assets/images/pay-with-crypto.png';

		$total_price = isset(WC()->cart->subtotal) ? WC()->cart->subtotal : null;

		$project_id = isset($options['project_id']) ? sanitize_text_field($options['project_id']) : '';

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
			$image_url = $this->cpmwp_get_coin_logo($value);
			$custom_tokens_data = (isset($count_currency[$value]) && isset($custom_price[$value]) && $count_currency[$value] == 1) ? $custom_price[$value] : false;
			$token_discount_data = false;
			$in_crypto = $this->cpmwp_price_conversion($total_price, $value, $type, $custom_tokens_data, $token_discount_data);
			if (isset($in_crypto['restricted'])) {
				$error = esc_html($in_crypto['restricted']);
				break; // Exit the loop if the API is restricted.
			}
			$enabledCurrency[$value] = array(
				'symbol' => esc_html($value),
				'price' => $in_crypto,
				'url' => esc_url($image_url),
			);
		}

		$add_networkswagmi = $this->cpmwp_add_networks_wagmi();
		$nonce = wp_create_nonce('cpmw_metamask_currency');
		// Use glob to get an array of file names in the folder
		$filePaths = glob(CPMWP_PATH . '/assets/pay-with-metamask/build/checkout' . '/*.php');
		$fileName = pathinfo($filePaths[0], PATHINFO_FILENAME);
		$jsbuildUrl = str_replace('.asset', '', $fileName);
		$const_msg = $this->cpmwp_const_messages();
		$title = $this->get_setting('title');
		return array(
			'title' => '' !== $title ? esc_html($title) : esc_html__('Pay With Cryptocurrency', 'cpmwp'),
			'description' => $this->get_setting('custom_description'),
			'supports' => array_filter($this->gateway->supports, array($this->gateway, 'supports')),
			'total_price' => $total_price,
			'error' => $error,
			'ccpw_wc_id' => $project_id,
			'logo_url' => $logo_url,
			'api_type' => $type,
			'nonce' => wp_create_nonce('wp_rest'),
			'restUrl' => get_rest_url() . 'pay-with-metamask/v1/',
			'currency_lbl' => $select_currency_lbl,
			'network_lbl' => $select_network_lbl,
			'const_msg' => $const_msg,
			'enabledCurrency' => $enabledCurrency,
			'enabledWallets' => $options['supported_wallets'],
			'order_button_text' => (isset($options['place_order_button']) && !empty($options['place_order_button'])) ? esc_html($options['place_order_button']) : esc_html__('Pay With Crypto Wallets', 'cpmwp'),

		);
	}
}
