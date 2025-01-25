<?php
/**
 * Plugin Name: Pay With MetaMask For WooCommerce Pro
 * Description: Accept cryptocurrency payments on WooCommerce. Your customers can pay with USDT, ETH, BNB, and other crypto coins using web3 wallets like MetaMask, WalletConnect, Trust Wallet, and more.
 * Plugin URI: https://paywithcryptocurrency.net/wordpress-plugin/pay-with-metamask-for-woocommerce-pro/?utm_source=cpmwp_plugin&utm_medium=plugin_uri
 * Author: Cool Plugins
 * Author URI: https://coolplugins.net/?utm_source=cpmwp_plugin&utm_medium=author_uri
 * Version: 1.7.4
 * License: GPL3
 * Text Domain: cpmwp
 * Domain Path: /languages
 *
 * @package MetaMask
 */

/*
 * Copyright (C) 2023  Cool Plugins - https://coolplugins.net
 */

// Ensure direct access is not allowed
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'CPMWP_VERSION', '1.7.4' );
define( 'CPMWP_FILE', __FILE__ );
define( 'CPMWP_PATH', plugin_dir_path( CPMWP_FILE ) );
define( 'CPMWP_URL', plugin_dir_url( CPMWP_FILE ) );

if ( ! defined( 'CPMWP_DEMO_URL' ) ) {
	define( 'CPMWP_DEMO_URL', '?utm_source=cpmwp_plugin&utm_medium=plugin_link&utm_campaign=cpmwp_plugin_inside' );
}
/*** CPMWP_metamask_pay main class by CoolPlugins.net */
if ( ! class_exists( 'CPMWP_metamask_pay' ) ) {
	final class CPMWP_metamask_pay {


		/**
		 * The unique instance of the plugin.
		 */
		private static $instance;

		/**
		 * Gets an instance of our plugin.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {        }

		// register all hooks
		public function registers() {
			/*** Installation and uninstallation hooks */
			register_activation_hook( CPMWP_FILE, array( self::$instance, 'activate' ) );
			register_deactivation_hook( CPMWP_FILE, array( self::$instance, 'deactivate' ) );
			$this->cpmwp_installation_date();
			add_action( 'admin_init', array( $this, 'cpmwp_is_free_version_active' ) );
			add_action( 'plugins_loaded', array( self::$instance, 'cpmwp_load_files' ) );
			add_action( 'init', array( $this, 'cpmwp_load_text_domain' ) );
			add_filter( 'woocommerce_payment_gateways', array( self::$instance, 'cpmwp_add_gateway_class' ) );
			add_action( 'admin_enqueue_scripts', array( self::$instance, 'cmpw_admin_style' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( self::$instance, 'cpmwp_add_widgets_action_links' ) );
			add_action( 'admin_menu', array( $this, 'cpmwp_add_submenu_page' ), 99998 );
			add_action( 'init', array( $this, 'cpmwp_plugin_version_verify' ) );
			// add_action('csf_cpmwp_settings_save', array($this, 'cpmwp_delete_trainsient'));
			add_action( 'csf_cpmwp_settings_save_before', array( $this, 'cpmwp_delete_trainsient' ), 10, 2 );
			add_action( 'woocommerce_blocks_loaded', array( $this, 'woocommerce_gateway_block_support' ) );
			add_action( 'woocommerce_delete_order', array( $this, 'cpmwp_delete_transaction' ) );
		}

		public function cpmwp_delete_transaction( $order_id ) {
			if ( $order_id ) {
				$db = new cpmwp_database();
				$db->delete_transaction( $order_id );
			}
		}

		public function cpmwp_delete_trainsient( $request, $instance ) {
			// Sanitize inputs
			$opt_key        = sanitize_key( 'openexchangerates_key' );
			$crypto_compare = sanitize_key( 'crypto_compare_key' );

			// The saved options from framework instance
			$options = $instance->options;

			// Checking the option-key change or not.
			if ( isset( $options[ $opt_key ] ) && isset( $request[ $opt_key ] ) && ( $options[ $opt_key ] !== $request[ $opt_key ] ) || isset( $options[ $crypto_compare ] ) && isset( $request[ $crypto_compare ] ) && ( $options[ $crypto_compare ] !== $request[ $crypto_compare ] ) ) {

				delete_transient( 'cpmwp_openexchangerates' );
				delete_transient( 'cpmwp_binance_priceETHUSDT' );
				delete_transient( 'cpmwp_currencyUSDT' );
				delete_transient( 'cpmwp_currencyETH' );
				delete_transient( 'cpmwp_currencyBUSD' );
				delete_transient( 'cpmwp_currencyBNB' );

			}

		}

		public function cpmwp_add_submenu_page() {
			add_submenu_page( 'woocommerce', 'MetaMask Settings', '<strong>MetaMask Pro</strong>', 'manage_options', 'admin.php?page=wc-settings&tab=checkout&section=cpmw', false, 100 );

			add_submenu_page( 'woocommerce', 'MetaMask Transaction', '↳ Transaction', 'manage_options', 'cpmw-metamask', array( 'cpmwp_TRANSACTION_TABLE', 'cpmwp_transaction_table' ), 101 );
			add_submenu_page( 'woocommerce', 'Settings', '↳ Settings', 'manage_options', 'admin.php?page=cpmw-metamask-settings', false, 102 );

		}

		// custom links for add widgets in all plugins section
		public function cpmwp_add_widgets_action_links( $links ) {
			$cpmwp_settings = admin_url() . 'admin.php?page=cpmw-metamask-settings';
			$links[]        = '<a style="font-weight:bold" href="' . esc_url( $cpmwp_settings ) . '" target="_self">' . __( 'Settings', 'cpmwp' ) . '</a>';
			return $links;

		}

		public function cmpw_admin_style( $hook ) {
			 wp_enqueue_script( 'cpmwp-custom', CPMWP_URL . 'assets/js/admin.js', array( 'jquery' ), CPMWP_VERSION, true );
			wp_enqueue_style( 'cpmwp_admin_css', CPMWP_URL . 'assets/css/admin.css', array(), CPMWP_VERSION, null, 'all' );

			if ( $hook == 'woocommerce_page_cpmw-metamask-settings' ) {
				wp_enqueue_script( 'cpmwp-replace-labels', CPMWP_URL . 'assets/js/admin/replace.js', array( 'csf', 'jquery' ), CPMWP_VERSION, true );
				wp_localize_script(
					'cpmwp-replace-labels',
					'extradata',
					array(
						'url' => CPMWP_URL,

					)
				);
			}

		}

		public function cpmwp_add_gateway_class( $gateways ) {
			$gateways[] = 'WC_cpmwp_Gateway'; // your class name is here
			return $gateways;

		}
		/*** Load required files */
		public function cpmwp_load_files() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( $this, 'cpmwp_missing_wc_notice' ) );
				return;
			}

			/*** Include helpers functions*/
			require_once CPMWP_PATH . 'includes/api/class-api.php';

			require_once CPMWP_PATH . 'includes/helper/helper.php';
			require_once CPMWP_PATH . 'includes/class-payment-gateway.php';
			require_once CPMWP_PATH . 'includes/db/class-database.php';
			require_once CPMWP_PATH . 'includes/class-wallets-login.php';
			require_once CPMWP_PATH . 'includes/class-rest-api.php';

			if ( is_admin() ) {
				require_once CPMWP_PATH . 'admin/table/class-transaction-table.php';
				require_once CPMWP_PATH . 'admin/table/class-list-table.php';
				require_once CPMWP_PATH . 'admin/class-review-notice.php';
				require_once CPMWP_PATH . 'admin/codestar-framework/codestar-framework.php';
				require_once CPMWP_PATH . 'admin/class-settings.php';
				// licesne key regisration files
				require_once CPMWP_PATH . 'admin/registration-settings/PayWithMetaMaskForWooCommerceProBase.php';
				new PayWithMetaMaskForWooCommerceProBase( CPMWP_FILE );
				require_once CPMWP_PATH . 'admin/registration-settings/PayWithMetaMaskForWooCommercePro.php';
			}
			require_once CPMWP_PATH . 'includes/cron/class-cron.php';

		}

		/*
		|--------------------------------------------------------------------------
		|  make sure it always run to avoid conflict between free and pro version
		|--------------------------------------------------------------------------
		|  it must be fired on admin-area after plugins loaded
		|--------------------------------------------------------------------------
		 */
		public function cpmwp_is_free_version_active() {
			if ( is_plugin_active( 'cryptocurrency-payments-using-metamask-for-woocommerce/cryptocurrency-payments-using-metamask-for-woocommerce.php' ) ) {
				deactivate_plugins( 'cryptocurrency-payments-using-metamask-for-woocommerce/cryptocurrency-payments-using-metamask-for-woocommerce.php' );

				add_action(
					'admin_notices',
					function () {?>
				<style>div#message.updated {
					display: none;
				}</style>
					<div class="notice notice-error is-dismissible">
						<p>
						<?php
						_e( 'Pay With MetaMask For WooCommerce Pro: Cryptocurrency Payments Using MetaMask For WooCommerce is <strong>deactivated</strong> as you have already activated the pro version.', 'cpmwp' );
						?>
						</p>
					</div>

						<?php
					}
				);
			}
		}
		public function cpmwp_installation_date() {
			 $get_installation_time = strtotime( 'now' );
			add_option( 'cpmwp_activation_time', $get_installation_time );
		}
		public function cpmwp_missing_wc_notice() {
			 $installurl = admin_url() . 'plugin-install.php?tab=plugin-information&plugin=woocommerce';
			if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
				echo '<div class="error"><p>' . esc_html__( 'Pay With MetaMask For WooCommerce Pro requires WooCommerce to be active', 'cpmwp' ) . '</div>';
			} else {
				wp_enqueue_script( 'cpmwp-custom-notice', CPMWP_URL . 'assets/js/admin-notice.js', array( 'jquery' ), CPMWP_VERSION, true );
				echo '<div class="error"><p>' . sprintf( __( 'Pay With MetaMask For WooCommerce Pro requires WooCommerce to be installed and active. Click here to %s WooCommerce plugin.', 'cpmwp' ), '<button class="cpmwp_modal-toggle" >' . __( 'Install', 'cpmwp' ) . ' </button>' ) . '</p></div>';
				?>
				<div class="cpmwp_modal">
					<div class="cpmwp_modal-overlay cpmwp_modal-toggle"></div>
					<div class="cpmwp_modal-wrapper cpmwp_modal-transition">
					<div class="cpmwp_modal-header">
						<button class="cpmwp_modal-close cpmwp_modal-toggle"><span class="dashicons dashicons-dismiss"></span></button>
						<h2 class="cpmwp_modal-heading"><?php _e( 'Install WooCommerce', 'cpmwp' ); ?></h2>
					</div>
					<div class="cpmwp_modal-body">
						<div class="cpmwp_modal-content">
						<iframe  src="<?php echo esc_url( $installurl ); ?>" width="600" height="400" id="cpmwp_custom_cpmwp_modal"> </iframe>
						</div>
					</div>
					</div>
				</div>
				<?php
			}
		}

		// set settings on plugin activation
		public static function activate() {
			 require_once CPMWP_PATH . 'includes/db/class-database.php';
			update_option( 'cpmwp-v', CPMWP_VERSION );
			update_option( 'cpmwp-type', 'FREE' );
			update_option( 'cpmwp-installDate', date( 'Y-m-d h:i:s' ) );
			update_option( 'cpmwp-already-rated', 'no' );
			$db = new cpmwp_database();
			$db->create_table();
		}
		public static function deactivate() {
			// $db= new cpmwp_database();
			// $db->drop_table();
			delete_option( 'cpmwp-v' );
			delete_option( 'cpmwp-type' );
			delete_option( 'cpmwp-installDate' );
			delete_option( 'cpmwp-already-rated' );
			if ( wp_next_scheduled( 'cpmwp_order_autoupdate' ) ) {
				wp_clear_scheduled_hook( 'cpmwp_order_autoupdate' );
			}

		}
		/*
		|--------------------------------------------------------------------------
		|  Check if plugin is just updated from older version to new
		|--------------------------------------------------------------------------
		 */
		public function cpmwp_plugin_version_verify() {
			 $CPMWP_VERSION = get_option( 'CPMW_FREE_VERSION' );

			if ( ! isset( $CPMWP_VERSION ) || version_compare( $CPMWP_VERSION, CPMWP_VERSION, '<' ) ) {
				if ( ! get_option( 'wp_cpmwp_transaction_db_version' ) ) {
					$this->activate();
				}
				if ( isset( $CPMWP_VERSION ) && empty( get_option( 'cpmwp_migarte_settings' ) ) ) {
					$this->cpmwp_migrate_settings();
					update_option( 'cpmwp_migarte_settings', 'migrated' );
				}
				if ( ! wp_next_scheduled( 'cpmwp_order_autoupdate' ) ) {
					wp_schedule_event( time(), '2min', 'cpmwp_order_autoupdate' );
				}

				update_option( 'CPMW_FREE_VERSION', CPMWP_VERSION );

			}

		}

		// Migrate woocommerce settings to codestar
		protected function cpmwp_migrate_settings() {
			$woocommerce_settings = get_option( 'woocommerce_cpmwp_settings' );
			$codestar_options     = get_option( 'cpmw_settings' );
			if ( ! empty( $woocommerce_settings ) ) {
				$codestar_options['user_wallet']             = $woocommerce_settings['user_wallet'];
				$codestar_options['currency_conversion_api'] = $woocommerce_settings['currency_conversion_api'];
				$codestar_options['crypto_compare_key']      = $woocommerce_settings['crypto_compare_key'];
				$codestar_options['openexchangerates_key']   = $woocommerce_settings['openexchangerates_key'];
				$codestar_options['user_wallet']             = $woocommerce_settings['user_wallet'];
				$codestar_options['payment_status']          = $woocommerce_settings['payment_status'];
				$codestar_options['redirect_page']           = ( $woocommerce_settings['redirect_page'] == 'yes' ) ? 1 : 0;
				$codestar_options['payment_msg']             = $woocommerce_settings['payment_msg'];
				$codestar_options['confirm_msg']             = $woocommerce_settings['confirm_msg'];
				$codestar_options['payment_process_msg']     = $woocommerce_settings['payment_process_msg'];
				$codestar_options['rejected_message']        = $woocommerce_settings['rejected_message'];
				update_option( 'cpmwp_settings', $codestar_options );
			}

		}
		/*
		|--------------------------------------------------------------------------
		| Load Text domain
		|--------------------------------------------------------------------------
		 */
		public function cpmwp_load_text_domain() {
			load_plugin_textdomain( 'cpmwp', false, basename( dirname( __FILE__ ) ) . '/languages/' );
		}
		public function woocommerce_gateway_block_support() {
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

				require_once 'includes/blocks/class-payment-gateway-blocks.php';
				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
						$payment_method_registry->register( new WC_cpmwp_Gateway_Blocks_Support() );
					}
				);

			}
		}

	}

}
/*** CPMWP_metamask_pay main class - END */

/*** THANKS - CoolPlugins.net ) */
$cpmwp = CPMWP_metamask_pay::get_instance();
$cpmwp->registers();
