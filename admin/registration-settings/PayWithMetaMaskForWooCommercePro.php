<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'PayWithMetaMaskForWooCommercePro' ) ) {
	class PayWithMetaMaskForWooCommercePro {
		public $plugin_file = __FILE__;
		public $responseObj;
		public $licenseMessage;
		public $showMessage = false;
		public $slug        = 'pay-with-metamask-for-woocommerce-pro';
		function __construct() {
			add_action( 'admin_print_styles', array( $this, 'SetAdminStyle' ) );
			$licenseKey = get_option( 'PayWithMetaMaskForWooCommercePro_lic_Key', '' );
			$liceEmail  = get_option( 'PayWithMetaMaskForWooCommercePro_lic_email', '' );
			PayWithMetaMaskForWooCommerceProBase::addOnDelete(
				function() {
					delete_option( 'PayWithMetaMaskForWooCommercePro_lic_Key' );
				}
			);
			if ( PayWithMetaMaskForWooCommerceProBase::CheckWPPlugin( $licenseKey, $liceEmail, $this->licenseMessage, $this->responseObj, __FILE__ ) ) {
				add_action( 'admin_menu', array( $this, 'ActiveAdminMenu' ), 99999 );
				add_action( 'admin_post_PayWithMetaMaskForWooCommercePro_el_deactivate_license', array( $this, 'action_deactivate_license' ) );
				// $this->licenselMessage=$this->mess;
				// ***Write you plugin's code here***

			} else {
				if ( ! empty( $licenseKey ) && ! empty( $this->licenseMessage ) ) {
					$this->showMessage = true;

				}

				add_action( 'admin_post_PayWithMetaMaskForWooCommercePro_el_activate_license', array( $this, 'action_activate_license' ) );
				add_action( 'admin_menu', array( $this, 'InactiveMenu' ), 99999 );
				add_action( 'admin_notices', array( $this, 'admin_registration_notice' ) );

			}

		}
		/*
		|----------------------------------------------------------------
		|   Admin registration notice for un-registered admin users only
		|----------------------------------------------------------------
		*/
		function admin_registration_notice() {
			if ( ! current_user_can( 'delete_posts' ) || ! empty( $licenseKey ) ) {
				return;
			}
			$current_user = wp_get_current_user();
			$user_name    = $current_user->display_name;
			?>
				<div class="license-warning notice notice-error is-dismissible">
					<p>Hi, <strong><?php echo ucwords( $user_name ); ?></strong>! Please <strong><a href="<?php echo esc_url( get_admin_url( null, 'admin.php?page=pay-with-metamask-for-woocommerce-pro' ) ); ?>">enter and activate</a></strong> your license key for <strong>Pay With MetaMask For WooCommerce Pro</strong> plugin for unrestricted and full access of all premium features.</p>
				</div>
			<?php
		}
		function SetAdminStyle() {
			wp_register_style( 'PayWithMetaMaskForWooCommerceProLic', plugins_url( 'style.css', $this->plugin_file ), 10 );
			wp_enqueue_style( 'PayWithMetaMaskForWooCommerceProLic' );
		}
		function ActiveAdminMenu() {

			// add_submenu_page('woocommerce', "Pay With MetaMask For WooCommerce Pro", "activate_plugins", $this->slug, [$this,"Activated"],106);
			add_submenu_page( 'woocommerce', 'PayWithMetaMaskForWooCommercePro License', '↳ License', 'activate_plugins', $this->slug, array( $this, 'Activated' ), 106 );

		}
		function InactiveMenu() {
			add_submenu_page( 'woocommerce', 'PayWithMetaMaskForWooCommercePro License', '↳ License', 'activate_plugins', $this->slug, array( $this, 'LicenseForm' ), 1007 );

		}
		function action_activate_license() {
			check_admin_referer( 'cpmwp-license' );
			$licenseKey   = ! empty( $_POST['el_license_key'] ) ? $_POST['el_license_key'] : '';
			$licenseEmail = ! empty( $_POST['el_license_email'] ) ? $_POST['el_license_email'] : '';
			update_option( 'PayWithMetaMaskForWooCommercePro_lic_Key', $licenseKey ) || add_option( 'PayWithMetaMaskForWooCommercePro_lic_Key', $licenseKey );
			update_option( 'PayWithMetaMaskForWooCommercePro_lic_email', $licenseEmail ) || add_option( 'PayWithMetaMaskForWooCommercePro_lic_email', $licenseEmail );
			update_option( '_site_transient_update_plugins', '' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug ) );
		}
		function action_deactivate_license() {
			check_admin_referer( 'cpmwp-license' );
			$message = '';
			if ( PayWithMetaMaskForWooCommerceProBase::RemoveLicenseKey( __FILE__, $message ) ) {
				update_option( 'PayWithMetaMaskForWooCommercePro_lic_Key', '' ) || add_option( 'PayWithMetaMaskForWooCommercePro_lic_Key', '' );
				update_option( '_site_transient_update_plugins', '' );
			}
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug ) );
		}
		function Activated() {
			?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="PayWithMetaMaskForWooCommercePro_el_deactivate_license"/>
		   <div class="cpmwp-license-container">
					<h3 class="cpmwp-license-title"> <?php _e( 'Pay With MetaMask For WooCommerce Pro - Premium License Status', $this->slug ); ?> </h3>
					<div class="cpmwp-license-content">
						<div class="cpmwp-license-form">
							<h3>Active License Status</h3>
							<ul class="cpmwp-license-info">
							<li>
								<div>
									<span class="cpmwp-license-info-title"><?php _e( 'License Status', $this->slug ); ?></span>

									<?php if ( $this->responseObj->is_valid ) : ?>
										<span class="cpmwp-license-valid"><?php _e( 'Valid', $this->slug ); ?></span>
									<?php else : ?>
										<span class="cpmwp-license-valid"><?php _e( 'Invalid', $this->slug ); ?></span>
									<?php endif; ?>
								</div>
							</li>

							<li>
								<div>
									<span class="cpmwp-license-info-title"><?php _e( 'License Type', $this->slug ); ?></span>
									<?php echo $this->responseObj->license_title; ?>
								</div>
							</li>

							<li>
								<div>
									<span class="cpmwp-license-info-title"><?php _e( 'License Expiry Date', $this->slug ); ?></span>
									<?php echo $this->responseObj->expire_date; ?>
								</div>
							</li>

							<li>
								<div>
									<span class="cpmwp-license-info-title"><?php _e( 'Support Expiry Date', $this->slug ); ?></span>
									<?php echo $this->responseObj->support_end; ?>
								</div>
							</li>
								<li>
									<div>
										<span class="cpmwp-license-info-title"><?php _e( 'Your License Key', $this->slug ); ?></span>
										<span class="cpmwp-license-key"><?php echo esc_attr( substr( $this->responseObj->license_key, 0, 9 ) . 'XXXXXXXX-XXXXXXXX' . substr( $this->responseObj->license_key, -9 ) ); ?></span>
									</div>
								</li>
							</ul>
							<div class="cpmwp-license-active-btn">
								<?php wp_nonce_field( 'cpmwp-license' ); ?>
								<?php submit_button( 'Deactivate License' ); ?>
							</div>
						</div>
						<div class="cpmwp-license-textbox">
						<h3>Important Points</h3>
						<ol>
							<li>Please deactivate your license first before moving your website or changing domain.</li>
													   
							<li>If you have any issue or query, please <a href="https://coolplugins.net/support/" target="_blank">contact support</a>.</li>
						</ol>
						<div class="el-pluginby">
							Plugin by<br/>
							<a href="https://coolplugins.net" target="_blank"><img src="<?php echo CPMWP_URL . '/assets/images/coolplugins-logo.png'; ?>"/></a>
						</div>
						</div>
					</div>
				</div>
		</form>
			<?php
		}

		function LicenseForm() {
			?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="PayWithMetaMaskForWooCommercePro_el_activate_license"/>
		<div class="cpmwp-license-container">
				<h3 class="cpmwp-license-title"> <?php _e( 'Pay With MetaMask For WooCommerce Pro - Premium License', $this->slug ); ?></h3>
				<div class="cpmwp-license-content">
					<div class="cpmwp-license-form">
						<h3>Activate Premium License</h3>
						<?php
						if ( ! empty( $this->showMessage ) && ! empty( $this->licenseMessage ) ) {
							?>
							<div class="notice notice-error is-dismissible">
								<p><?php echo _e( $this->licenseMessage, $this->slug ); ?></p>
							</div>
							<?php
						}
						?>
						<!--Enter License Key Here START-->
						<div class="cpmwp-license-field">
							<label for="el_license_key"><?php _e( 'Enter License code', $this->slug ); ?></label>
							<input type="text" class="regular-text code" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
						</div>
						<div class="cpmwp-license-field">
							<label for="el_license_key"><?php _e( 'Email Address', $this->slug ); ?></label>
							<?php
								$purchaseEmail = get_option( 'PayWithMetaMaskForWooCommercePro_lic_email' );
							?>
							<input type="text" class="regular-text code" name="el_license_email" size="50" value="<?php echo sanitize_email( $purchaseEmail ); ?>" placeholder="Enter Purchase Email" required="required">
							<div><small><?php _e( '✅ I agree to share my purchase code and email for plugin verification and to receive future updates notifications!', $this->slug ); ?></small></div>
						</div>
						<div class="cpmwp-license-active-btn">
							<?php wp_nonce_field( 'cpmwp-license' ); ?>
							<?php submit_button( 'Activate' ); ?>
						</div>
						<!--Enter License Key Here END-->
					</div>
					
					<div class="cpmwp-license-textbox">
						<div>
						<strong style="color:#e00b0b;">*Important Points</strong>
						<ol>
						<li> You can find license key inside your purchase order email or /my-account section in the website from where you purchased the plugin.</li>
						<li>Please deactivate your license first before moving your website or changing domain.</li>
													   
						<li>If you have any issue or query, please <a href="https://coolplugins.net/support/" target="_blank">contact support</a>.</li>
						</ol>
						</div>
						<div class="el-pluginby">
							Plugin by<br/>
							<a href="https://coolplugins.net" target="_blank"><img src="<?php echo CPMWP_URL . '/assets/images/coolplugins-logo.png'; ?>"/></a>
						</div>
					</div>
				</div>
			</div>
	</form>
			<?php
		}
	}

	new PayWithMetaMaskForWooCommercePro();
}
