<?PHP

class CpmwpRestApi {

	use CPMWP_HELPER;
	public static $instanceApi;
	const Rest_End_Point = 'pay-with-metamask/v1';
	public static function getInstance() {
		if ( ! isset( self::$instanceApi ) ) {
			self::$instanceApi = new self();
		}
		return self::$instanceApi;
	}

	public function __construct() {
		 add_action( 'rest_api_init', array( $this, 'registerRestApi' ) );
	}
	// Register all required rest roots
	public function registerRestApi() {
		$routes = array(
			'verify-transaction'      => 'verify_transaction_handler',
			'save-transaction'        => 'save_transaction_handler',
			'cancel-order'            => 'cpmwp_cancel_order',
			'sign-request'            => 'cpmwp_metamask_login',
			'selected-network'        => 'cpmwp_get_selected_network',
			'refund-save-transaction' => 'save_refund_transaction',
			'refund-order'            => 'refund_order',
			'update-price'            => 'update_price',
			'save-transaction-data'   => 'save_transaction_data',
			'prev-payment-status'     => 'prev_payment_status',
		);

		foreach ( $routes as $route => $callback ) {
			register_rest_route(
				self::Rest_End_Point,
				$route,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, $callback ),
					'permission_callback' => '__return_true',
				)
			);
		}

	}
	// Get network on selected coin base
	public function update_price( $request ) {
		$data = $request->get_json_params();
		// Verify the nonce
		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json_error( 'Nonce verification failed' );

		}
		$options           = get_option( 'cpmw_settings' );
		$type              = $options['currency_conversion_api'];
		$symbol            = ! empty( $data['symbol'] ) ? sanitize_text_field( $data['symbol'] ) : '';
		$total_price       = ! empty( $data['total_amount'] ) ? sanitize_text_field( $data['total_amount'] ) : '';
		$get_required_data = $this->cpmwp_get_active_currencies();
		$crypto_currency   = $get_required_data['enabled_currency'];
		$custom_price      = $get_required_data['custom_price'];
		$count_currency    = $get_required_data['count_currency'];
		$enabledCurrency   = array();

		foreach ( $crypto_currency as $key => $value ) {
			$image_url                 = $this->cpmwp_get_coin_logo( $value );
			$custom_tokens_data        = ( isset( $count_currency[ $value ] ) && isset( $custom_price[ $value ] ) && $count_currency[ $value ] == 1 ) ? $custom_price[ $value ] : false;
			$in_crypto                 = $this->cpmwp_price_conversion( $total_price, $value, $type, $custom_tokens_data, false );
			$enabledCurrency[ $value ] = array(
				'symbol' => $value,
				'price'  => $in_crypto,
				'url'    => $image_url,
			);
		}
		return new WP_REST_Response( $enabledCurrency );

	}

	// Get network on selected coin base
	public function cpmwp_get_selected_network( $request ) {
		$data = $request->get_json_params();
		// Verify the nonce
		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json_error( 'Nonce verification failed' );

		}
		$symbol        = ! empty( $data['symbol'] ) ? sanitize_text_field( $data['symbol'] ) : '';
		$total_price   = ! empty( $data['total_amount'] ) ? sanitize_text_field( $data['total_amount'] ) : '';
		$network_array = $this->cpmwp_get_active_networks_for_currency( $symbol, $total_price );
		return new WP_REST_Response( $network_array );

	}
	// Canel or fail Order
	public static function cpmwp_cancel_order( $request ) {
		 $data = $request->get_json_params();
		// Verify the nonce
		$order_id = (int) sanitize_text_field( $data['order_id'] );
		$nonce    = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json_error( 'Nonce verification failed' );

		}
		$canceled = sanitize_text_field( $data['canceled'] );
		$message  = __( 'Payment has been failed due to user rejection', 'cpmwp' );

		$order = new WC_Order( $order_id );
		$order->update_status( 'wc-failed', $message );
		$checkout_page = wc_get_checkout_url();

		$order->save_meta_data();
		return new WP_REST_Response(
			array(
				'error' => $message,
				'url'   => $canceled ? $checkout_page : '',
			),
			400
		);

	}
	// Wallet Login process handling
	public function cpmwp_metamask_login( $request ) {
		$data = $request->get_json_params();

		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error_message = __( 'Nonce verification failed.', 'cpmwp' );
			$log_entry     = '[FAILURE] ' . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );
			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		require_once CPMWP_PATH . 'includes/elliptic-php/ecrecover.php';
		$options      = get_option( 'cpmw_settings' );
		$sign_message = isset( $options['metamask_sign_message'] ) ? $options['metamask_sign_message'] : '';
		$redirect     = ( isset( $options['mmetamask_redirect'] ) && ! empty( $options['mmetamask_redirect'] ) ) ? $options['mmetamask_redirect'] : get_site_url() . '/my-account';
		$signature    = ! empty( $data['signature'] ) ? sanitize_text_field( $data['signature'] ) : '';
		$userwallet   = ! empty( $data['user'] ) ? sanitize_text_field( $data['user'] ) : '';
		$address      = ecrecover( $sign_message . $nonce, $signature );
		$address      = preg_replace( "/\n/", '', $address );
		if ( ! empty( $address ) && strtoupper( $address ) == strtoupper( $userwallet ) ) {

			$user = get_user_by( 'login', $address );

			if ( $user ) {
				wp_clear_auth_cookie();
				wp_set_auth_cookie( $user->ID );
				wp_send_json(
					array(
						'status' => 'success',
						'user'   => $address,
					)
				);

			} else {

				$random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
				$user_id         = wp_create_user( $address, $random_password, '' );
				if ( $user_id ) {
					$data = array(
						'ID'   => $user_id,
						'role' => isset( $options['metamask_user_role'] ) ? $options['metamask_user_role'] : 'subscriber',

					);
					wp_update_user( $data );
					wp_clear_auth_cookie();
					wp_set_auth_cookie( $user_id );
					wp_send_json(
						array(
							'status' => 'success',
							'user'   => $address,
						)
					);

				}
			}
		} else {
			wp_send_json(
				array(
					'status' => 'fail',
					'user'   => null,
				)
			);

		}

	}

	// On successfull payment handle order status & save transaction in database
	public function refund_order( $request ) {
		global $woocommerce;
		$data = $request->get_json_params();
		// Verify the nonce
		$order_id = (int) sanitize_text_field( $data['order_id'] );
		$nonce    = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error_message = __( 'Nonce verification failed.', 'cpmwp' );
			$log_entry     = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );
			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$order            = new WC_Order( $order_id );
		$options_settings = get_option( 'cpmw_settings' );
		$block_explorer   = $this->cpmwp_get_explorer_url();
		$trasn_id         = ! empty( $data['payment_processed'] ) ? sanitize_text_field( $data['payment_processed'] ) : '';
		$selected_network = ! empty( $data['selected_network'] ) ? sanitize_text_field( $data['selected_network'] ) : '';
		$sender           = ! empty( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '';
		$receiver         = ! empty( $data['receiver'] ) ? sanitize_text_field( $data['receiver'] ) : '';
		$token_address    = sanitize_text_field( $data['token_address'] );

		$networks = $this->cpmwp_supported_networks();

		$secret_key         = $this->cpmwp_get_secret_key();
		$signature          = ! empty( $data['signature'] ) ? $data['signature'] : '';
		$receve_tx_req_data = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $receiver ),
				'token_address'    => strtoupper( $token_address ),
				'tx_id'            => $trasn_id,
			)
		);

		$get_sign = hash_hmac( 'sha256', $receve_tx_req_data, $secret_key );
		// Verify signature
		if ( $get_sign !== $signature ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );
			$error_message = __( 'Signature verification failed', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] " . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );

		}

		try {

			$refund_amount = ! empty( $data['refund_amount'] ) ? $data['refund_amount'] : '';
			$refund_reason = ! empty( $data['refund_reason'] ) ? $data['refund_reason'] : '';

			if ( $trasn_id != 'false' && $order->is_paid() ) {
				$link_hash = '';

				// if ($order->get_remaining_refund_amount() >= $refund_cal) {
				$refund = wc_create_refund(
					array(
						'amount'         => number_format( $refund_amount, 2, '.', ',' ),
						'reason'         => $refund_reason,
						'order_id'       => $order_id,
						'refund_payment' => true,
					)
				);

				$link_hash = '<a href="' . $block_explorer[ $selected_network ] . 'tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';

				$order->add_meta_data( 'Refunded_amount', $refund_amount );
				// $order->add_meta_data( 'Refund_TransactionId', $trasn_id );
				$transection = __( 'Payment Refunded via Pay with MetaMask - Transaction ID:', 'cpmwp' ) . $link_hash;
				$order->add_order_note( $transection );
				$order->save_meta_data();
				$data = array(
					'is_refund'    => ( $trasn_id != 'false' ) ? true : false,
					'order_status' => $order->get_status(),
				);

				return new WP_REST_Response( $data );

			}

			// return $data;

		} catch ( Exception $e ) {
			return new WP_REST_Response( array( 'error' => $e ), 400 );

		}
		return new WP_REST_Response( array( 'error' => __( 'not a valid order_id', 'cpmwp' ) ), 400 );

	}
	// On successfull payment handle order status & save transaction in database
	public function verify_transaction_handler( $request ) {
		global $woocommerce;
		$data = $request->get_json_params();
		// Verify the nonce
		$order_id = (int) sanitize_text_field( $data['order_id'] );
		$nonce    = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error_message = __( 'Nonce verification failed.', 'cpmwp' );
			$log_entry     = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$order = new WC_Order( $order_id );
		if ( $order->is_paid() ) {

			$error_message = __( 'This order has already paid', 'cpmwp' );
			$log_entry     = "[Order #$order_id] [FAILURE] " . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$options_settings = get_option( 'cpmw_settings' );
		$block_explorer   = $this->cpmwp_get_explorer_url();
		$trasn_id         = ! empty( $data['payment_processed'] ) ? sanitize_text_field( $data['payment_processed'] ) : '';
		$payment_status_d = ! empty( $data['payment_status'] ) ? sanitize_text_field( $data['payment_status'] ) : '';
		$selected_network = ! empty( $data['selected_network'] ) ? sanitize_text_field( $data['selected_network'] ) : '';
		$sender           = ! empty( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '';
		$receiver         = ! empty( $data['receiver'] ) ? sanitize_text_field( $data['receiver'] ) : '';
		$token_address    = sanitize_text_field( $data['token_address'] );
		$amount           = ! empty( $data['amount'] ) ? $data['amount'] : '';
		$amount           = $this->cpmwp_format_number( $amount );
		$secret_code      = ! empty( $data['secret_code'] ) ? $data['secret_code'] : '';

		$networks = $this->cpmwp_supported_networks();

		$user_address         = $order->get_meta( 'cpmwp_user_wallet' );
		$total                = $order->get_meta( 'cpmwp_in_crypto' );
		$total                = str_replace( ',', '', $total );
		$transaction_local_id = $order->get_meta( 'transaction_id' );
		$dbnetwork            = $order->get_meta( 'cpmwp_network' );
		$secret_key           = $this->cpmwp_get_secret_key();
		$signature            = ! empty( $data['signature'] ) ? $data['signature'] : '';
		$receve_tx_req_data   = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $receiver ),
				'amount'           => str_replace( ',', '', $amount ),
				'token_address'    => strtoupper( $token_address ),
				'tx_id'            => $trasn_id,
			)
		);

		$get_sign = hash_hmac( 'sha256', $receve_tx_req_data, $secret_key );
		// Verify signature
		if ( $get_sign !== $signature ) {

			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );
			$error_message = __( 'Signature verification failed', 'cpmwp' );
			$original_data = json_encode(
				array(
					'order_id'         => $order_id,
					'selected_network' => $selected_network,
					'receiver'         => strtoupper( $order->get_meta( 'cpmwp_user_wallet' ) ),
					'amount'           => $total,
					'token_address'    => strtoupper( $order->get_meta( 'cpmwp_contract_address' ) ),
					'tx_id'            => $transaction_local_id,
				)
			);

			$log_entry = "[Order #$order_id] [FAILURE] [Original data]:-" . $original_data . '[Received data]' . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );

		}

		if ( $transaction_local_id != $trasn_id ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );
			$error_message = __( 'Transaction mismatch.', 'cpmwp' );
			$log_entry     = "[Order #$order_id] [FAILURE] " . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}
		$amount = str_replace( ',', '', $amount );
		if ( $amount != $total ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );

			$error_message = __( 'Order Information mismatch', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $total . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		// Chain Id verification.
		$chain_id = CPMWP_HELPER::cpmwp_chain_id( esc_html( $dbnetwork ) );
		if ( $chain_id === false ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to non-existent Chain ID', 'cpmwp' ) );

			$error_message = __( 'Chain ID does not exist', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $total . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		// // Transaction Hash verification.
		$tx_info = CPMWP_API_DATA::verify_transaction_info( esc_html( $trasn_id ), esc_html( $chain_id ), $order_id ,esc_html( $total ) );
		
		if(isset($tx_info['tx_not_exists']) && true === $tx_info['tx_not_exists']){
			$order->update_status( 'wc-failed', __( 'Transaction Id doesn\'t exists', 'cpmwp' ) );

			$error_message = __( 'Transaction Id doesn\'t exists', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $total . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}
		
		if(isset($tx_info['tx_already_exists']) && true === $tx_info['tx_already_exists']){
			$order->update_status( 'wc-failed', __( 'Order has been canceled because a same transaction already exists', 'cpmwp' ) );

			$error_message = __( 'A same transaction already exists in a previous order', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $total . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		if(isset($tx_info['receiver_failed']) && $tx_info['receiver_failed']){
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to an invalid transaction', 'cpmwp' ) );

			$error_message = __( 'Receiver are not same', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $total . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );	
		}

		if ( $tx_info['tx_status'] !== '0x1' ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to an invalid transaction ID', 'cpmwp' ) );

			$error_message = __( 'Transaction ID does not exist on the blockchain', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $total . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		if ( ! $tx_info['tx_amount_verify'] ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to amount doesn\'t matched', 'cpmwp' ) );

			$error_message = __( 'Amount does not match', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $total . $receve_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$transaction                      = array();
		$current_user                     = wp_get_current_user();
		$user_name                        = $current_user->user_firstname . ' ' . $current_user->user_lastname;
		$transaction['order_id']          = $order_id;
		$transaction['chain_id']          = $selected_network;
		$transaction['order_price']       = get_woocommerce_currency_symbol() . $order->get_total();
		$transaction['user_name']         = $user_name;
		$transaction['crypto_price']      = $order->get_meta( 'cpmwp_in_crypto' ) . ' ' . $order->get_meta( 'cpmwp_currency_symbol' );
		$transaction['selected_currency'] = $order->get_meta( 'cpmwp_currency_symbol' );
		$transaction['chain_name']        = $networks[ $selected_network ];

		try {
			if ( $trasn_id != 'false' ) {
				$link_hash = '';

				$link_hash = '<a href="' . $block_explorer[ $selected_network ] . 'tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';

				if ( $payment_status_d == 'default' ) {
					$order->add_meta_data( 'TransactionId', $trasn_id );
					$order->add_meta_data( 'Sender', $sender );
					$transection = __( 'Payment Received via Pay with MetaMask - Transaction ID:', 'cpmwp' ) . $link_hash;
					$order->add_order_note( $transection );
					$order->payment_complete( $trasn_id );
					// send email to costumer
					WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger( $order_id );
					// send email to admin
					WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order_id );
					// WC()->cart->empty_cart();

				} else {
					$order->add_meta_data( 'TransactionId', $trasn_id );
					$order->add_meta_data( 'Sender', $sender );
					$transection = __( 'Payment Received via Pay with MetaMask - Transaction ID:', 'cpmwp' ) . $link_hash;
					$order->add_order_note( $transection );
					$order->update_status( apply_filters( 'cpmwp_capture_payment_order_status', $payment_status_d ) );
					// send email to costumer
					WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger( $order_id );
					// send email to admin
					WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order_id );
					// WC()->cart->empty_cart();
				}
			}
			$db                            = new cpmwp_database();
			$transaction['status']         = 'completed';
			$transaction['sender']         = $sender;
			$transaction['transaction_id'] = ! empty( $trasn_id ) ? $trasn_id : 'false';
			$order->save_meta_data();
			$data = array(
				'is_paid'            => ( $order->get_status() == 'on-hold' && ! empty( $trasn_id ) ) ? true : $order->is_paid(),
				'order_status'       => $order->get_status(),
				'order_received_url' => $order->get_checkout_order_received_url(),
			);
			$order->save_meta_data();
			$db->cpmwp_insert_data( $transaction );

			return new WP_REST_Response( $data );
			// return $data;

		} catch ( Exception $e ) {
			return new WP_REST_Response( array( 'error' => $e ), 400 );

		}
		return new WP_REST_Response( array( 'error' => __( 'not a valid order_id', 'cpmwp' ) ), 400 );

	}

	// validate and save transation hash info inside transaction table and order
	public function save_transaction_handler( $request ) {
		global $woocommerce;
		$data     = $request->get_json_params();
		$order_id = (int) sanitize_text_field( $data['order_id'] );
		$nonce    = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error_message = __( 'Nonce verification failed.', 'cpmwp' );

			$log_entry = " [Order #$order_id] [FAILURE] $error_message" . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );

		}

		$tx_hash=sanitize_text_field( $data['transaction_id'] );
		$db             = new cpmwp_database();
		$tx_order_id = $db->cpmwp_get_tx_order_id( $tx_hash );
		$order         = new WC_Order( $order_id );

		if(count($tx_order_id) > 1 || (count($tx_order_id) > 0 && (int)$tx_order_id[0] !== $order_id)){
			$order->update_status( 'wc-failed', __( 'Order has been canceled because a same transaction already exists', 'cpmwp' ) );

			$error_message = __( 'A same transaction already exists in a previous order', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$amount        = sanitize_text_field( $data['amount'] );
		$amount        = $this->cpmwp_format_number( $amount );
		$receiver      = sanitize_text_field( $data['receiver'] );
		$signature     = sanitize_text_field( $data['signature'] );
		$sender        = ! empty( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '';
		$token_address = sanitize_text_field( $data['token_address'] );
		$order->add_meta_data( 'transactionverification', sanitize_text_field( $data['transaction_id'] ) );
		$order->save_meta_data();
		$selected_network   = $order->get_meta( 'cpmwp_network' );
		$secret_key         = $this->cpmwp_get_secret_key();
		$create_tx_req_data = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $receiver ),
				'amount'           => str_replace( ',', '', $amount ),
				'token_address'    => strtoupper( $token_address ),
			)
		);

		$get_sign     = hash_hmac( 'sha256', $create_tx_req_data, $secret_key );
		$saved_amount = $order->get_meta( 'cpmwp_in_crypto' );
		// Verify signature
		if ( $get_sign !== $signature ) {

			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );
			$error_message = __( 'Signature verification failed', 'cpmwp' );

			$original_data = json_encode(
				array(
					'order_id'         => $order_id,
					'selected_network' => $selected_network,
					'receiver'         => strtoupper( $order->get_meta( 'cpmwp_user_wallet' ) ),
					'amount'           => str_replace( ',', '', $saved_amount ),
					'token_address'    => strtoupper( $order->get_meta( 'cpmwp_contract_address' ) ),
				)
			);

			$log_entry = " [Order #$order_id] [FAILURE] [Original data]:-" . $original_data . '[Received data]' . $create_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$tx_db_id = $order->get_meta( 'transaction_id' );
		$trasn_id = ! empty( $data['transaction_id'] ) ? sanitize_text_field( $data['transaction_id'] ) : '';

		if ( ! empty( $tx_db_id ) && ( $tx_db_id !== $trasn_id ) ) {

			$order->update_status( 'wc-failed', __( 'Order canceled: Transaction already exists.', 'cpmwp' ) );
			$error_message = __( 'Order canceled: Transaction already exists..', 'cpmwp' );
			$log_entry     = " [Order #$order_id] [FAILURE] " . $create_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );
			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$saved_receiver = $order->get_meta( 'cpmwp_user_wallet' );
		$nonce          = ! empty( $data['nonce'] ) ? sanitize_text_field( $data['nonce'] ) : '';

		$block_explorer = $this->cpmwp_get_explorer_url();

		$networks     = $this->cpmwp_supported_networks();
		$transaction  = array();
		$current_user = wp_get_current_user();
		$user_name    = $current_user->user_firstname . ' ' . $current_user->user_lastname;
		$order->update_meta_data( 'transaction_id', $trasn_id );
		$order->add_meta_data( 'Sender', $sender );
		$saved_token_address              = $order->get_meta( 'cpmwp_contract_address' );
		$db_currency_symbol               = $order->get_meta( 'cpmwp_currency_symbol' );
		$transaction['order_id']          = $order_id;
		$transaction['chain_id']          = $selected_network;
		$transaction['order_price']       = get_woocommerce_currency_symbol() . $order->get_total();
		$transaction['user_name']         = $user_name;
		$transaction['crypto_price']      = $order->get_meta( 'cpmwp_in_crypto' ) . ' ' . $db_currency_symbol;
		$transaction['selected_currency'] = $db_currency_symbol;
		$transaction['chain_name']        = $networks[ $selected_network ];
		$transaction['status']            = 'awaiting';
		$transaction['sender']            = $sender;
		$transaction['transaction_id']    = ! empty( $trasn_id ) ? $trasn_id : 'false';
		$order->save_meta_data();
		$db = new cpmwp_database();

		$pass_tx_req_data = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $saved_receiver ),
				'amount'           => str_replace( ',', '', $saved_amount ),
				'token_address'    => strtoupper( $saved_token_address ),
				'tx_id'            => $trasn_id,
			)
		);
		$signature        = hash_hmac( 'sha256', $pass_tx_req_data, $secret_key );
		$db->cpmwp_insert_data( $transaction );
		// save transation
		$data = array(
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'signature' => $signature,
			'order_id'  => $order_id,
		);
		return new WP_REST_Response( $data );

		die();
	}

	// save transaction hash and sender id in order page
	public function save_transaction_data( $request ) {
		 global $woocommerce;
		$data     = $request->get_json_params();
		$order_id = (int) sanitize_text_field( $data['order_id'] );
		$nonce    = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error_message = __( 'Nonce verification failed.', 'cpmwp' );

			$log_entry = " [Order #$order_id] [FAILURE] $error_message" . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );

		}

		$tx_hash=sanitize_text_field( $data['transaction_id'] );
		$db             = new cpmwp_database();
		$tx_order_id = $db->cpmwp_get_tx_order_id( $tx_hash );
		$order         = new WC_Order( $order_id );

		if(count($tx_order_id) > 1 || (count($tx_order_id) > 0 && (int)$tx_order_id[0] !== $order_id)){
			$order->update_status( 'wc-failed', __( 'Order has been canceled because a same transaction already exists', 'cpmwp' ) );

			$error_message = __( 'A same transaction already exists in a previous order', 'cpmwp' );

			$log_entry = "[Order #$order_id] [FAILURE] Amount:-" . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}


		$amount        = sanitize_text_field( $data['amount'] );
		$amount        = $this->cpmwp_format_number( $amount );
		$receiver      = sanitize_text_field( $data['receiver'] );
		$signature     = sanitize_text_field( $data['signature'] );
		$sender        = ! empty( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '';
		$token_address = sanitize_text_field( $data['token_address'] );
		$order->add_meta_data( 'transactionverification', sanitize_text_field( $data['transaction_id'] ) );
		$order->save_meta_data();
		$selected_network   = $order->get_meta( 'cpmwp_network' );
		$secret_key         = $this->cpmwp_get_secret_key();
		$create_tx_req_data = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $receiver ),
				'amount'           => str_replace( ',', '', $amount ),
				'token_address'    => strtoupper( $token_address ),
			)
		);
		$get_sign           = hash_hmac( 'sha256', $create_tx_req_data, $secret_key );
		$saved_amount       = $order->get_meta( 'cpmwp_in_crypto' );

		// Verify signature
		if ( $get_sign !== $signature ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );
			$error_message = __( 'Signature verification failed', 'cpmwp' );

			$original_data = json_encode(
				array(
					'order_id'         => $order_id,
					'selected_network' => $selected_network,
					'receiver'         => strtoupper( $order->get_meta( 'cpmwp_user_wallet' ) ),
					'amount'           => str_replace( ',', '', $saved_amount ),
					'token_address'    => strtoupper( $order->get_meta( 'cpmwp_contract_address' ) ),
				)
			);

			$log_entry = " [Order #$order_id] [FAILURE] [Original data]:-" . $original_data . '[Received data]' . $create_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$tx_db_id    = $order->get_meta( 'transaction_id' );
		$return_data = array();
		if ( empty( $tx_db_id ) ) {
			$trasn_id = ! empty( $data['transaction_id'] ) ? sanitize_text_field( $data['transaction_id'] ) : '';
			$order->update_meta_data( 'transaction_id', $trasn_id );
			$order->add_meta_data( 'Sender', $sender );
			$order->save_meta_data();

			$current_user       = wp_get_current_user();
			$user_name          = $current_user->user_firstname . ' ' . $current_user->user_lastname;
			$db_currency_symbol = $order->get_meta( 'cpmwp_currency_symbol' );
			$networks           = $this->cpmwp_supported_networks();

			$transaction['order_id']          = $order_id;
			$transaction['chain_id']          = $selected_network;
			$transaction['order_price']       = get_woocommerce_currency_symbol() . $order->get_total();
			$transaction['user_name']         = $user_name;
			$transaction['crypto_price']      = $order->get_meta( 'cpmwp_in_crypto' ) . ' ' . $db_currency_symbol;
			$transaction['selected_currency'] = $db_currency_symbol;
			$transaction['chain_name']        = $networks[ $selected_network ];
			$transaction['status']            = 'awaiting';
			$transaction['sender']            = $sender;
			$transaction['transaction_id']    = ! empty( $trasn_id ) ? $trasn_id : 'false';
			$db                               = new cpmwp_database();
			$db->cpmwp_insert_data( $transaction );

			// save transation
			$return_data = array(
				'order_id'    => $order_id,
				'hash_status' => 'saved',
			);
		} else {
			$return_data = array(
				'order_id'    => $order_id,
				'hash_status' => 'exist',
			);
		}

		return new WP_REST_Response( $return_data );

		die();
	}

	// save transaction hash and sender id in order page
	public function prev_payment_status( $request ) {
		 global $woocommerce;
		$data     = $request->get_json_params();
		$order_id = (int) sanitize_text_field( $data['order_id'] );
		$nonce    = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error_message = __( 'Nonce verification failed.', 'cpmwp' );

			$log_entry = " [Order #$order_id] [FAILURE] $error_message" . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );

		}

		$amount             = sanitize_text_field( $data['amount'] );
		$amount             = $this->cpmwp_format_number( $amount );
		$receiver           = sanitize_text_field( $data['receiver'] );
		$signature          = sanitize_text_field( $data['signature'] );
		$sender             = ! empty( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '';
		$token_address      = sanitize_text_field( $data['token_address'] );
		$order              = new WC_Order( $order_id );
		$selected_network   = $order->get_meta( 'cpmwp_network' );
		$secret_key         = $this->cpmwp_get_secret_key();
		$create_tx_req_data = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $receiver ),
				'amount'           => str_replace( ',', '', $amount ),
				'token_address'    => strtoupper( $token_address ),
			)
		);

		$get_sign     = hash_hmac( 'sha256', $create_tx_req_data, $secret_key );
		$saved_amount = $order->get_meta( 'cpmwp_in_crypto' );

		// Verify signature
		if ( $get_sign !== $signature ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );
			$error_message = __( 'Signature verification failed', 'cpmwp' );

			$original_data = json_encode(
				array(
					'order_id'         => $order_id,
					'selected_network' => $selected_network,
					'receiver'         => strtoupper( $order->get_meta( 'cpmwp_user_wallet' ) ),
					'amount'           => str_replace( ',', '', $saved_amount ),
					'token_address'    => strtoupper( $order->get_meta( 'cpmwp_contract_address' ) ),
				)
			);

			$log_entry = " [Order #$order_id] [FAILURE] [Original data]:-" . $original_data . '[Received data]' . $create_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		$tx_id          = $order->get_meta( 'transaction_id' );
		$sender_id      = $order->get_meta( 'Sender' );
		$payment_status = $order->get_status();

		$return_data = array();
		if ( ! empty( $tx_id ) && ! empty( $sender_id ) && $sender_id === $sender && in_array( $payment_status, array( 'pending', 'failed' ) ) ) {
			$saved_receiver      = $order->get_meta( 'cpmwp_user_wallet' );
			$saved_token_address = $order->get_meta( 'cpmwp_contract_address' );
			$pass_tx_req_data    = json_encode(
				array(
					'order_id'         => $order_id,
					'selected_network' => $selected_network,
					'receiver'         => strtoupper( $saved_receiver ),
					'amount'           => str_replace( ',', '', $saved_amount ),
					'token_address'    => strtoupper( $saved_token_address ),
					'tx_id'            => $tx_id,
				)
			);
			$signature           = hash_hmac( 'sha256', $pass_tx_req_data, $secret_key );

			$return_data = array(
				'transaction_status' => esc_html( $payment_status ),
				'sender_id'          => esc_html( $sender_id ),
				'transaction_id'     => esc_html( $tx_id ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'signature'          => $signature,
				'order_id'           => $order_id,
			);
		} else {
			$return_data = array(
				'transaction_status' => false,
			);
		}

		return new WP_REST_Response( $return_data );

		die();
	}

	// validate and save transation info inside transaction table and order
	public function save_refund_transaction( $request ) {
		global $woocommerce;
		$data     = $request->get_json_params();
		$order_id = (int) sanitize_text_field( $data['order_id'] );
		$nonce    = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error_message = __( 'Nonce verification failed.', 'cpmwp' );
			$log_entry     = " [Order #$order_id] [FAILURE] $error_message" . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );
			return new WP_REST_Response( array( 'error' => $error_message ), 400 );

		}

		$receiver      = sanitize_text_field( $data['receiver'] );
		$signature     = sanitize_text_field( $data['signature'] );
		$sender        = ! empty( $data['sender'] ) ? sanitize_text_field( $data['sender'] ) : '';
		$token_address = sanitize_text_field( $data['token_address'] );
		// $verifyRequest = stripslashes($tx_req_data);
		$tx_data_arr = json_decode( $verifyRequest, true ); // Decode JSON to associative array
		$order       = new WC_Order( $order_id );
		$trasn_id    = sanitize_text_field( $data['transaction_id'] );
		$order->add_meta_data( 'refund_transaction_verification', $trasn_id );
		$order->save_meta_data();
		$selected_network   = $order->get_meta( 'cpmwp_network' );
		$secret_key         = $this->cpmwp_get_secret_key();
		$create_tx_req_data = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $receiver ),
				'token_address'    => strtoupper( $token_address ),
			)
		);
		$get_sign           = hash_hmac( 'sha256', $create_tx_req_data, $secret_key );
		// Verify signature
		if ( $get_sign !== $signature ) {
			$order->update_status( 'wc-failed', __( 'Order has been canceled due to Order Information mismatch', 'cpmwp' ) );
			$error_message = __( 'Signature verification failed', 'cpmwp' );
			$log_entry     = "[Order #$order_id] [FAILURE] " . $create_tx_req_data . $error_message . PHP_EOL;
			$this->cpmwpsaveErrorLogs( $log_entry );

			return new WP_REST_Response( array( 'error' => $error_message ), 400 );
		}

		// if (is_array($tx_data_arr)) {

		$order->update_meta_data( 'refund_transaction_id', $trasn_id );

		$order->save_meta_data();

		$pass_tx_req_data = json_encode(
			array(
				'order_id'         => $order_id,
				'selected_network' => $selected_network,
				'receiver'         => strtoupper( $receiver ),
				'token_address'    => strtoupper( $token_address ),
				'tx_id'            => $trasn_id,
			)
		);
		$signature        = hash_hmac( 'sha256', $pass_tx_req_data, $secret_key );
		// save transation
		$data = array(
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'signature' => $signature,
			'order_id'  => $order_id,
		);
		return new WP_REST_Response( $data );
		// }
		die();
	}

}

CpmwpRestApi::getInstance();
