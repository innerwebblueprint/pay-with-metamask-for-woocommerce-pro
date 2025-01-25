<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

trait CPMWP_HELPER {
	

	public function __construct() {
	}

	// Generate a dynamic secret key for hash_hmac
	public function cpmwp_get_secret_key() {
		if ( get_option( 'cpmwp_secret_key' ) == false ) {
			update_option( 'cpmwp_secret_key', wp_generate_password( 4, true, true ) );
		}
		return get_option( 'cpmwp_secret_key' );
	}

	// Price conversion API start

	protected function cpmwp_price_conversion( $total, $crypto, $type, $custom_tokens, $discount ) {
		global $woocommerce;
		$lastprice    = '';
		$currency     = get_woocommerce_currency();
		$settings_obj = get_option( 'cpmw_settings' );

		if ( $type == 'cryptocompare' ) {
			$api = ! empty( $settings_obj['crypto_compare_key'] ) ? sanitize_text_field( $settings_obj['crypto_compare_key'] ) : '';
			if ( empty( $api ) ) {
				return 'no_key';
			}

			if ( $custom_tokens != false ) {
				$price_list = CPMWP_API_DATA::cpmwp_openexchangerates_api();
				if ( isset( $price_list->error ) && $currency != 'USD' ) {
					return 'error';
				}

				$price_arryay = $price_list ? (array) $price_list->rates : null;
				$current_rate = ( $currency != 'USD' ) ? $price_arryay[ $currency ] : 1;
				$custom_price = ( $custom_tokens['type'] == 'custom_token_price' ) ? $custom_tokens['value'] : CPMWP_API_DATA::cpmwp_get_coinbrain_api( $crypto, $custom_tokens['value'], hexdec( $custom_tokens['chainId'] ) );
				$lastprice    = isset( $custom_price ) ? $custom_price : '';

				if ( $custom_tokens['type'] == 'custom_token_price' ) {
					$cal = ( ! empty( $current_rate ) && ! empty( $lastprice ) ) ? ( $total * $lastprice ) : '';

				} else {
					$cal = ( ! empty( $current_rate ) && ! empty( $lastprice ) ) ? ( $total / $current_rate ) / $lastprice : '';

				}

				if ( isset( $discount ) && $discount != false ) {
					$discount_rate = ( $cal * $discount ) / 100;
					return $this->cpmwp_format_number( $cal - $discount_rate );
				}
				return $this->cpmwp_format_number( $cal );

			} else {
				$current_price       = CPMWP_API_DATA::cpmwp_crypto_compare_api( $currency, $crypto );
				$current_price_array = (array) $current_price;

				if ( isset( $current_price_array['Response'] ) ) {
					return;
				}
				$crypto_price = isset( $current_price_array[ $crypto ] ) ? ( $current_price_array[ $crypto ] ) * $total : null;
				$in_crypto    = ! empty( $crypto_price ) ? $crypto_price : '';
				if ( isset( $discount ) && $discount != false ) {
					$discount_rate = ( $in_crypto * $discount ) / 100;
					return $this->cpmwp_format_number( $in_crypto - $discount_rate );
				}

				return $this->cpmwp_format_number( $in_crypto );
			}
		} else {
			$price_list = CPMWP_API_DATA::cpmwp_openexchangerates_api();
			if ( isset( $price_list->error ) && $currency != 'USD' ) {
				return 'error';
			}

			$price_arryay = ( $currency != 'USD' ) ? (array) $price_list->rates : '';
			$current_rate = ( $currency != 'USD' ) ? $price_arryay[ $currency ] : 1;

			if ( $custom_tokens != false ) {
				$DecChainId   = isset( $custom_tokens['chainId'] ) ? hexdec( $custom_tokens['chainId'] ) : false;
				$custom_price = ( $custom_tokens['type'] == 'custom_token_price' ) ? $custom_tokens['value'] : CPMWP_API_DATA::cpmwp_get_coinbrain_api( $crypto, $custom_tokens['value'], $DecChainId );

				$lastprice = isset( $custom_price ) ? $custom_price : '';
				if ( $custom_tokens['type'] == 'custom_token_price' ) {
					$cal = ( ! empty( $current_rate ) && ! empty( $lastprice ) ) ? ( $total * $lastprice ) : '';

				} else {
					$cal = ( ! empty( $current_rate ) && ! empty( $lastprice ) ) ? ( $total / $current_rate ) / $lastprice : '';

				}

				if ( isset( $discount ) && $discount != false ) {
					$discount_rate = ( $cal * $discount ) / 100;
					return $this->cpmwp_format_number( $cal - $discount_rate );
				}
				return $this->cpmwp_format_number( $cal );

			} elseif ( $crypto == 'USDT' || $crypto == 'USDC' || $crypto == 'BUSD' ) {
				$current_price_USDT       = CPMWP_API_DATA::cpmwp_crypto_compare_api( $currency, $crypto );
				$current_price_array_USDT = (array) $current_price_USDT;
				if ( isset( $current_price_array_USDT['Response'] ) ) {
					return;
				}
				$in_crypto_USDT = ! empty( ( $current_price_array_USDT[ $crypto ] ) * $total ) ? ( $current_price_array_USDT[ $crypto ] ) * $total : '';
				if ( isset( $discount ) && $discount != false ) {
					$discount_rate = ( $in_crypto_USDT * $discount ) / 100;
					return $this->cpmwp_format_number( $in_crypto_USDT - $discount_rate );
				}
				return $this->cpmwp_format_number( $in_crypto_USDT );
			} else {
				$binance_price = CPMWP_API_DATA::cpmwp_binance_price_api( '' . $crypto . 'USDT' );
				if ( isset( $binance_price->lastPrice ) ) {

					if ( floatval($binance_price->lastPrice) != 0 && $binance_price->firstId != -1 && $binance_price->lastId != -1 && $binance_price->count != 0 )  {
						$lastprice = isset( $binance_price->lastPrice ) ? $binance_price->lastPrice : '';

						$cal = ( ! empty( $current_rate ) && ! empty( $lastprice ) ) ? ( $total / $current_rate ) / $lastprice : '';

						if ( isset( $discount ) && $discount != false ) {
							$discount_rate = ( $cal * $discount ) / 100;
							return $this->cpmwp_format_number( $cal - $discount_rate );
						}
						return $this->cpmwp_format_number( $cal );

					} else {
						return array( 'restricted' => __( 'Binance API Is Restricted In Your region, Please Switch With CryptoCompare API.', 'cpmw' ) );
					}
				} else {
					if ( current_user_can( 'manage_options' ) ) {
						return isset( $binance_price->msg ) ? array( 'restricted' => __( 'Binance API Is Restricted In Your region, Please Switch With CryptoCompare API.', 'cpmw' ) ) : 'error';
					}
				}
			}
		}
	}

	protected function cpmwp_format_number( $n ) {
		if ( is_numeric( $n ) ) {
			if ( $n >= 25 ) {
				return $formatted = number_format( $n, 2, '.', ',' );
			} elseif ( $n >= 0.50 && $n < 25 ) {
				return $formatted = number_format( $n, 3, '.', ',' );
			} elseif ( $n >= 0.01 && $n < 0.50 ) {
				return $formatted = number_format( $n, 4, '.', ',' );
			} elseif ( $n >= 0.001 && $n < 0.01 ) {
				return $formatted = number_format( $n, 5, '.', ',' );
			} elseif ( $n >= 0.0001 && $n < 0.001 ) {
				return $formatted = number_format( $n, 6, '.', ',' );
			} else {
				return $formatted = number_format( $n, 8, '.', ',' );
			}
		}
	}

	// Price conversion API end here

	protected function cpmwp_supported_currency() {
		 $oe_currency = array( 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTC', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLF', 'CLP', 'CNH', 'CNY', 'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MRU', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'SSP', 'STD', 'STN', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VES', 'VND', 'VUV', 'WST', 'XAF', 'XAG', 'XAU', 'XCD', 'XDR', 'XOF', 'XPD', 'XPF', 'XPT', 'YER', 'ZAR', 'ZMW', 'ZWL' );
		return $oe_currency;
	}

	// Add blockchain networks
	protected function cpmwp_add_networks() {
		$options = get_option( 'cpmw_settings' );
		$data    = array();
		foreach ( $options['custom_networks'] as $key => $value ) {

			if ( $value['enable'] == '1' ) {
				$data[ $value['chainId'] ] = array(
					'chainId'           => $value['chainId'],
					'chainName'         => $value['chainName'],
					'nativeCurrency'    => array(
						'name'     => $value['nativeCurrency']['name'],
						'symbol'   => $value['nativeCurrency']['symbol'],
						'decimals' => (int) $value['nativeCurrency']['decimals'],
					),
					'rpcUrls'           => array( $value['rpcUrls'] ),
					'blockExplorerUrls' => array( $value['blockExplorerUrls'] ),
				);

			}
		}

		return $data;
	}
	// Add custom tokens for networks
	protected function cpmwp_add_tokens() {
		 $tokens = array();

		$options = get_option( 'cpmw_settings' );
		foreach ( $options['custom_networks'] as $key => $value ) {
			$array = array();
			if ( isset( $value['currencies'] ) && is_array( $value['currencies'] ) ) {
				foreach ( $value['currencies'] as $currency_key => $currency_val ) {

					if ( $currency_val['enable'] == '1' ) {
						$array[ $currency_val['symbol'] ] = $currency_val['contract_address'];
					}
				}
			}
			$tokens[ $value['chainId'] ] = $array;
		}

		return $tokens;

	}

	// Add network names here
	protected function cpmwp_supported_networks() {
		 $networks = array();
		$options   = get_option( 'cpmw_settings' );
		foreach ( $options['custom_networks'] as $key => $value ) {
			$networks[ $value['chainId'] ] = $value['chainName'];
		}
		return $networks;
	}

	// Add all constant messages
	protected function cpmwp_const_messages() {
		 $messages = '';

		$messages = array(
			// Checkout&validate fields static messages start here
			'metamask_address'          => __( 'Please enter your  Payment address', 'cpmwp' ),
			'valid_wallet_address'      => __( 'Please enter valid  Payment address', 'cpmwp' ),
			'required_fiat_key'         => __( 'Please enter price conversion API key', 'cpmwp' ),
			'valid_fiat_key'            => __( 'Please enter valid price conversion API key', 'cpmwp' ),
			'required_currency'         => __( 'Please select a currency', 'cpmwp' ),
			'required_network_check'    => __( 'Please select a payment network', 'cpmwp' ),
			'payment_network'           => __( 'Select Payment Network', 'cpmwp' ),
			'switch_network_msg'        => __( 'Please switch to the network below inside your wallet to complete this payment.', 'cpmwp' ),
			'switch_network_mobile_msg' => __( 'Your wallet doesn’t support network switching within this app. Please switch networks directly in your wallet.', 'cpmwp' ),
			'connected_to'              => __( 'Connected to', 'cpmwp' ),
			'disconnect'                => __( 'Disconnect Wallet', 'cpmwp' ),
			'wallet'                    => __( 'Wallet', 'cpmwp' ),
			'network'                   => __( 'Network', 'cpmwp' ),
			'insufficent'               => __( 'Insufficient balance in your wallet for this order. Try different network, coin, or wallet.', 'cpmwp' ),
			'payment_notice'            => __( 'Please proceed with the payment below.', 'cpmwp' ),
			'already_paid'              => sprintf(
				'%s<%s/>%s',
				__( 'Thank you for your payment. The transaction with hash', 'cpmwp' ),
				'transaction_hash',
				__( 'is currently being processed on the blockchain. Your order will be completed automatically once confirmed.', 'cpmwp' )
			),
			// Checkout&validate fields static messages end here
			// Process order fields static messages start here
			'notice_msg'                => __( 'Please dont change the payment amount in your wallet, it could lead to order failure.', 'cpmwp' ),
			'payment_notice_msg'        => __( 'Please wait while we check your transaction confirmation on the block explorer. Do not change the gas fee until the transaction is complete to avoid order failure.', 'cpmwp' ),
			'cancel_order'              => __( 'If you want to pay with a different cryptocurrency, network, or wallet, please', 'cpmwp' ),
			'cancel_this_order'         => __( 'cancel this order', 'cpmwp' ),
			'create_new_one'            => __( 'and create a new one.', 'cpmwp' ),
			'to_complete'               => __( 'to complete this order', 'cpmwp' ),
			'through'                   => __( 'through ', 'cpmwp' ),
			'processing'                => __( 'Processing', 'cpmwp' ),
			'process_request'           => __( 'Process This Request From Wallet', 'cpmwp' ),
			'confirm_this_request'      => __( 'Confirm To Process This Request', 'cpmwp' ),
			'insufficient_balance'      => __( 'Insufficient Balance:', 'cpmwp' ),
			'metamask_connect'          => __( 'Connect MetaMask Wallet ', 'cpmwp' ),
			'binance_connect'           => __( 'Connect Binance Wallet', 'cpmwp' ),
			'trustwallet_connect'       => __( 'Connect Trust Wallet ', 'cpmwp' ),
			'connect_wallet_connect'    => __( 'Connect Wallet Connect', 'cpmwp' ),
			'connnected_wallet'         => __( 'Connected wallet:', 'cpmwp' ),
			'metamask'                  => __( 'MetaMask', 'cpmwp' ),
			'binance'                   => __( 'Binance', 'cpmwp' ),
			'active_chain'              => __( 'Active chain:', 'cpmwp' ),
			'connected'                 => __( 'Connected', 'cpmwp' ),
			'not_connected'             => __( 'Not Connected', 'cpmwp' ),
			'order_price'               => __( 'Order price: ', 'cpmwp' ),
			'pay_with'                  => __( 'Please pay', 'cpmwp' ),
			'connection_establish'      => __( 'Please wait while connection established', 'cpmwp' ),
			'required_network'          => __( 'Currently you have not selected the required network', 'cpmwp' ),
			'switch_network'            => __( 'Click ok to switch on ', 'cpmwp' ),
			'switch_bnb_network'        => __( 'Please Switch to ', 'cpmwp' ),
			'confirm_order'             => __( 'Confirm Order Payment', 'cpmwp' ),
			'ext_not_detected'          => __( 'MetaMask Wallet extention not detected !', 'cpmwp' ),
			'bnb_not_detected'          => __( 'Binance Wallet extention not detected !', 'cpmwp' ),
			'refund_amount_notice'      => __( 'Amount cannot be greater then original amount ', 'cpmwp' ),
			'metamask_wallet'           => __( ' MetaMask Wallet', 'cpmwp' ),
			'trust_wallet'              => __( 'Trust Wallet', 'cpmwp' ),
			'binance_wallet'            => __( 'Binance Wallet', 'cpmwp' ),
			'wallet_connect'            => __( ' Wallet Connect', 'cpmwp' ),
			'infura_msg'                => __( 'Project id is required for WalletConnect to work', 'cpmwp' ),
			'extention_not_detected'    => __( ' Extention not detected', 'cpmwp' ),
			'user_rejected_the_request' => __( 'User rejected the request', 'cpmwp' ),
			'end_session'               => __( 'End Session', 'cpmwp' ),
			'payment_status'            => __( 'Payment Status', 'cpmwp' ),
			'in_process'                => __( 'In Process...', 'cpmwp' ),
			'pending'                   => __( 'Pending', 'cpmwp' ),
			'failed'                    => __( 'Failed', 'cpmwp' ),
			'completed'                 => __( 'Completed', 'cpmwp' ),
			'invalid'                   => __( 'Invalid', 'cpmwp' ),
			'enter_amount'              => __( 'To proceed, please provide a valid amount.', 'cpmwp' ),
			'already_refunded'          => __( 'Your order has already been refunded.', 'cpmwp' ),

			'check_in_explorer'         => __( 'Check in explorer', 'cpmwp' ),
			'rejected_msg'              => __( 'Your payment has been rejected. Please try to make payment again.', 'cpmwp' ),
			'confirmed_payments_msg'    => __( 'Thank you for making the payment. Your transaction has been confirmed by the explorer.', 'cpmwp' ),
			// Process order fields static messages end here
			// Metamask login messages
			'YouMustSing'               => __( 'You must sign to enter!', 'cpmwp' ),
			'SomethingWentWrong'        => __( 'Something Went Wrong Please Try Again.', 'cpmwp' ),
			'account_created'           => __( 'Account Created Successfully', 'cpmwp' ),
			'logged_in'                 => __( 'Logged in successfully ', 'cpmwp' ),
			'connect_wallet'            => __( 'Connect Wallet', 'cpmwp' ),
			'select_wallet'             => __( 'Select Payment Wallet', 'cpmwp' ),
			'select_currency'           => __( 'Select a Currency', 'cpmwp' ),
			'select_cryptocurrency'     => __( 'Select Cryptocurrency..', 'cpmwp' ),
			'choose_network_chain'      => __( 'Choose network or chain..', 'cpmwp' ),

		);
		return $messages;

	}

	// Add network names here
	protected function cpmwp_get_coin_logo( $value ) {
		$coin_svg     = CPMWP_PATH . 'assets/images/' . $value . '.svg';
		$coin_png     = CPMWP_PATH . 'assets/images/' . $value . '.png';
		$coin_svg_img = CPMWP_URL . 'assets/images/' . $value . '.svg';
		$coin_png_img = CPMWP_URL . 'assets/images/' . $value . '.png';
		$image_url    = '';

		$options       = get_option( 'cpmw_settings' );
		$network_array = array();
		$upload_img    = '';

		foreach ( $options['custom_networks'] as $key => $values ) {

			if ( $values['nativeCurrency']['enable'] == '1' && $values['nativeCurrency']['symbol'] == $value ) {

				$upload_img = ! empty( $values['nativeCurrency']['image'] ) ? $values['nativeCurrency']['image'] : '';

			}

			if ( isset( $values['currencies'] ) ) {
				foreach ( $values['currencies'] as $currency_key => $currency_val ) {

					if ( $currency_val['enable'] == '1' && $currency_val['symbol'] == $value ) {
						$upload_img = ! empty( $currency_val['image'] ) ? $currency_val['image'] : '';

					}
				}
			}
		}

		if ( file_exists( $coin_svg ) ) {

			$image_url = $coin_svg_img;

		} elseif ( file_exists( $coin_png ) ) {
			$image_url = $coin_png_img;
		} elseif ( ! empty( $upload_img ) ) {
			$image_url = $upload_img;
		} else {
			$image_url = CPMWP_URL . 'assets/images/default-logo.png';
		}
		return $image_url;

	}

	/**
	 * Get default network currency
	 */
	protected function cpmwp_get_default_currency() {
		$options          = get_option( 'cpmw_settings' );
		$default_currency = array();
		foreach ( $options['custom_networks'] as $key => $value ) {
			if ( $value['nativeCurrency']['enable'] == '1' ) {
				$default_currency[ $value['chainId'] ] = $value['nativeCurrency']['symbol'];

			}
		}
		return $default_currency;

	}

	/**
	 * Get explorer url
	 */
	protected function cpmwp_get_explorer_url() {
		$options      = get_option( 'cpmw_settings' );
		$explorer_url = array();
		$rpc_url      = array();

		foreach ( $options['custom_networks'] as $key => $value ) {
			if ( $value['enable'] == '1' ) {
				$explorer_url[ $value['chainId'] ] = $value['blockExplorerUrls'];

			}
		}
		return $explorer_url;

	}

	/**
	 * Get explorer url
	 */
	protected function cpmwp_get_settings( $type ) {
		$options          = get_option( 'cpmw_settings' );
		$explorer_url     = array();
		$rpc_url          = array();
		$default_currency = array();
		$networks_name    = array();

		foreach ( $options['custom_networks'] as $key => $value ) {
			if ( $value['enable'] == '1' ) {
				$explorer_url[ $value['chainId'] ]            = $value['blockExplorerUrls'];
				$rpc_url[ hexdec( $value['chainId'] ) ]       = $value['rpcUrls'];
				$default_currency[ $value['chainId'] ]        = $value['nativeCurrency']['symbol'];
				$networks_name[ hexdec( $value['chainId'] ) ] = $value['chainName'];

			}
		}
		if ( $type == 'rpcUrls' ) {
			return $rpc_url;
		} elseif ( $type == 'block_explorer' ) {
			return $explorer_url;

		} elseif ( $type == 'default_currency' ) {
			return $default_currency;

		} elseif ( $type == 'network_name' ) {
			return $networks_name;

		}
		return false;
	}

	/**
	 * Get wallet address by chain id
	 */
	protected function cpmwp_get_wallet_address() {
		 $options = get_option( 'cpmw_settings' );
		$wallets  = array();
		foreach ( $options['custom_networks'] as $key => $value ) {
			if ( $value['enable'] == '1' ) {
				$wallets[ $value['chainId'] ] = $value['recever_wallet'];

			}
		}
		return $wallets;

	}

	protected function cpmwp_get_custom_price( $get_network ) {
		 $options        = get_option( 'cpmw_settings' );
		$custom_price    = array();
		$token_discount  = array();
		$crypto_currency = array();
		foreach ( $options['custom_networks'] as $key => $value ) {
			if ( $value['chainId'] == $get_network ) {
				if ( $value['nativeCurrency']['enable'] == '1' ) {
					$crypto_currency[]      = $value['nativeCurrency']['symbol'];
					$enabled_discount_price = isset( $value['nativeCurrency']['currency_discount'] ) && ! empty( $value['nativeCurrency']['currency_discount'] ) ? $value['nativeCurrency']['currency_discount'] : '';
					if ( $enabled_discount_price ) {
						$token_discount[ $value['nativeCurrency']['symbol'] ] = $enabled_discount_price;
					}
					if ( $value['nativeCurrency']['enable_custom'] == '1' ) {
						if ( isset( $value['nativeCurrency']['custom_native_price'] ) ) {
							$custom_price[ $value['nativeCurrency']['symbol'] ] = array(
								'type'  => 'custom_token_price',
								'value' => $value['nativeCurrency']['custom_native_price'],
							);
						}
					}
				}
				if ( isset( $value['currencies'] ) ) {
					foreach ( $value['currencies'] as $currency_key => $currency_val ) {

						if ( $currency_val['enable'] == '1' ) {
							if ( ! empty( $currency_val['symbol'] ) && ! empty( $currency_val['contract_address'] ) ) {
								$crypto_currency[] = ! empty( $currency_val['symbol'] ) ? $currency_val['symbol'] : '';
								if ( isset( $currency_val['token_discount'] ) && ! empty( $currency_val['token_discount'] ) ) {
									$token_discount[ $currency_val['symbol'] ] = $currency_val['token_discount'];
								}
								if ( $currency_val['enable_custom_price'] == '1' ) {
									if ( $currency_val['custom_token'] == 'custom_token_price' ) {
										if ( isset( $currency_val['token_price'] ) ) {
											$custom_price[ $currency_val['symbol'] ] = array(
												'type'  => $currency_val['custom_token'],
												'value' => $currency_val['token_price'],
											);
										}
									} elseif ( $currency_val['custom_token'] == 'pancake_swap' ) {
										if ( isset( $currency_val['contract_address'] ) ) {
											$custom_price[ $currency_val['symbol'] ] = array(
												'type'  => $currency_val['custom_token'],
												'value' => $currency_val['contract_address'],
											);
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return array(
			'custom_price'     => $custom_price,
			'token_discount'   => $token_discount,
			'enabled_currency' => $crypto_currency,
		);

	}

	protected function cpmwp_get_active_currencies() {
		$options            = get_option( 'cpmw_settings' );
		$custom_prices      = array();
		$token_discounts    = array();
		$crypto_currencies  = array();
		$default_currencies = array();

		foreach ( $options['custom_networks'] as $network ) {

			if ( $network && $network['enable'] == '1' ) {
				$nativeCurrency = $network['nativeCurrency'];
				if ( $nativeCurrency['enable'] == '1' ) {
					$crypto_currencies[] = $nativeCurrency['symbol'];
					if ( isset( $network['nativeCurrency']['enable_custom'] ) && $network['nativeCurrency']['enable_custom'] == '1' ) {
						if ( isset( $network['nativeCurrency']['custom_native_price'] ) ) {
							$custom_native_price                        = array(
								'type'    => 'custom_token_price',
								'chainId' => $network['chainId'],
								'value'   => $network['nativeCurrency']['custom_native_price'],
							);
							$custom_prices[ $nativeCurrency['symbol'] ] = $custom_native_price;
						}
					}
				}

				if ( isset( $network['currencies'] ) ) {
					foreach ( $network['currencies'] as $currency ) {
						if ( $currency['enable'] == '1' && ! empty( $currency['symbol'] ) && ! empty( $currency['contract_address'] ) ) {
							$crypto_currencies[] = $currency['symbol'];
							if ( ! empty( $currency['token_discount'] ) ) {
								$token_discounts[ $currency['symbol'] ] = $currency['token_discount'];
							}

							if ( $currency['enable_custom_price'] == '1' ) {
								$custom_prices[ $currency['symbol'] ] = array(
									'type'    => $currency['custom_token'],
									'chainId' => $network['chainId'],
									'value'   => $currency['custom_token'] == 'custom_token_price'
									? $currency['token_price']
									: ( $currency['custom_token'] == 'pancake_swap' ? $currency['contract_address'] : null ),
								);
							}
						}
					}
				}
			}
		}
		// Initialize an empty associative array to store the counts
		$valueCounts = array();

		// Iterate through the input array
		foreach ( $crypto_currencies as $value ) {
			// If the value exists in the associative array, increment its count; otherwise, initialize it to 1
			if ( isset( $valueCounts[ $value ] ) ) {
				$valueCounts[ $value ]++;
			} else {
				$valueCounts[ $value ] = 1;
			}
		}

		return array(
			'custom_price'     => $custom_prices,
			'token_discount'   => $token_discounts,
			'enabled_currency' => array_unique( $crypto_currencies ),
			'count_currency'   => $valueCounts,
			'default_currency' => $default_currencies,
		);
	}

	protected function cpmwp_get_active_networks_for_currency( $currencySymbol, $total ) {
		$options               = get_option( 'cpmw_settings' );
		$activeNetworks        = array();
		$decimalactiveNetworks = array();
		$custom_prices         = array();
		$token_discounts       = array();
		$contract_address      = array();
		$finalprice            = array();
		$discount              = '';
		$custom_native_price   = '';
		$type                  = $options['currency_conversion_api'];
		$all_networkswagmi     = $this->cpmwp_add_networks_wagmi();
		$add_networkswagmi     = array();
		foreach ( $options['custom_networks'] as $network ) {
			if ( $network && $network['enable'] == '1' ) {

				if ( $network['nativeCurrency']['enable'] == '1' && $network['nativeCurrency']['symbol'] === $currencySymbol ) {
					$activeNetworks[ $network['chainId'] ]                  = $network['chainName'];
					$decimalactiveNetworks[ hexdec( $network['chainId'] ) ] = $network['chainName'];
					$add_networkswagmi[ $network['chainId'] ]               = isset( $all_networkswagmi[ $network['chainId'] ] ) ? json_encode( $all_networkswagmi[ $network['chainId'] ] ) : '';

					$enabled_discount_price = isset( $network['nativeCurrency']['currency_discount'] ) && ! empty( $network['nativeCurrency']['currency_discount'] ) ? $network['nativeCurrency']['currency_discount'] : '';
					// $finalprice[$network['chainId']]=$this->cpmwp_price_conversion( $total, $currencySymbol, $type,false, false);
					if ( $enabled_discount_price ) {
						$discount = $enabled_discount_price;
						$token_discounts[ hexdec( $network['chainId'] ) ] = $discount;
						$finalprice[ $network['chainId'] ]                = $this->cpmwp_price_conversion( $total, $currencySymbol, $type, false, $discount );
					}
					if ( isset( $network['nativeCurrency']['enable_custom'] ) && $network['nativeCurrency']['enable_custom'] == '1' ) {
						if ( isset( $network['nativeCurrency']['custom_native_price'] ) && ! empty( $network['nativeCurrency']['custom_native_price'] ) ) {
							$custom_native_price                            = array(
								'type'    => 'custom_token_price',
								'chainId' => $network['chainId'],
								'value'   => $network['nativeCurrency']['custom_native_price'],
							);
							$custom_prices[ hexdec( $network['chainId'] ) ] = $custom_native_price;
							$finalprice[ $network['chainId'] ]              = $this->cpmwp_price_conversion( $total, $currencySymbol, $type, $custom_native_price, false );
						}
					}
					if ( isset( $network['nativeCurrency']['custom_native_price'] ) && ! empty( $network['nativeCurrency']['custom_native_price'] ) && ! empty( $enabled_discount_price ) ) {
						$finalprice[ $network['chainId'] ] = $this->cpmwp_price_conversion( $total, $currencySymbol, $type, $custom_native_price, $discount );

					}
				}

				if ( isset( $network['currencies'] ) ) {
					$custom = '';
					foreach ( $network['currencies'] as $currency ) {
						if ( $currency['enable'] == '1' && ! empty( $currency['symbol'] ) && $currency['symbol'] === $currencySymbol ) {
							$add_networkswagmi[ $network['chainId'] ]               = isset( $all_networkswagmi[ $network['chainId'] ] ) ? json_encode( $all_networkswagmi[ $network['chainId'] ] ) : '';
							$activeNetworks[ $network['chainId'] ]                  = $network['chainName'];
							$decimalactiveNetworks[ hexdec( $network['chainId'] ) ] = $network['chainName'];
							$contract_address[ hexdec( $network['chainId'] ) ]      = $currency['contract_address'];
							// $finalprice[$network['chainId']]=$this->cpmwp_price_conversion( $total, $currencySymbol, $type,false,false);
							if ( ! empty( $currency['token_discount'] ) ) {
								$discount = $currency['token_discount'];
								$token_discounts[ hexdec( $network['chainId'] ) ] = $currency['token_discount'];
								$finalprice[ $network['chainId'] ]                = $this->cpmwp_price_conversion( $total, $currencySymbol, $type, false, $discount );
							}
							if ( $currency['enable_custom_price'] == '1' ) {
								$custom = array(
									'type'    => $currency['custom_token'],
									'chainId' => $network['chainId'],
									'value'   => $currency['custom_token'] == 'custom_token_price'
									? $currency['token_price']
									: ( $currency['custom_token'] == 'pancake_swap' ? $currency['contract_address'] : null ),
								);
								$custom_prices[ hexdec( $network['chainId'] ) ] = $custom;
								if ( $currency['custom_token'] == 'custom_token_price' ) {
									$finalprice[ $network['chainId'] ] = $this->cpmwp_price_conversion( $total, $currencySymbol, $type, $custom, false );
								}
							}
							if ( ! empty( $currency['token_discount'] ) && $currency['enable_custom_price'] == '1' ) {
								$discount                          = $currency['token_discount'];
								$custom                            = array(
									'type'    => $currency['custom_token'],
									'chainId' => $network['chainId'],
									'value'   => $currency['custom_token'] == 'custom_token_price'
									? $currency['token_price']
									: ( $currency['custom_token'] == 'pancake_swap' ? $currency['contract_address'] : null ),
								);
								$finalprice[ $network['chainId'] ] = $this->cpmwp_price_conversion( $total, $currencySymbol, $type, $custom, $discount );

							}
						}
					}
				}
			}
		}
		return array(
			'custom_price'     => $custom_prices,
			'final_price'      => $finalprice,
			'token_discount'   => $token_discounts,
			'contract_address' => $contract_address,
			'active_network'   => $activeNetworks,
			'decimal_networks' => $decimalactiveNetworks,
			'network_settings' => $add_networkswagmi,

		);

	}

	// Add blockchain networks
	protected function cpmwp_add_networks_wagmi() {
		 $options = get_option( 'cpmw_settings' );
		$data     = array();
		foreach ( $options['custom_networks'] as $key => $value ) {

			if ( $value['enable'] == '1' ) {
				$data[ $value['chainId'] ] = array(
					'id'                => isset( $value['chainId'] ) ? hexdec( $value['chainId'] ) : false,
					'name'              => $value['chainName'],
					'network'           => $value['chainName'],
					'nativeCurrency'    => array(
						'name'     => $value['nativeCurrency']['name'],
						'symbol'   => $value['nativeCurrency']['symbol'],
						'decimals' => (int) $value['nativeCurrency']['decimals'],
					),
					'rpcUrls'           => array(
						'public'  => array( 'http' => array( $value['rpcUrls'] ) ),
						'default' => array( 'http' => array( $value['rpcUrls'] ) ),
					),
					'blockExplorerUrls' => array(
						array(
							'etherscan' => array(
								'url'  => $value['blockExplorerUrls'],
								'name' => $value['chainName'],
							),
							'default'   => array(
								'url'  => $value['blockExplorerUrls'],
								'name' => $value['chainName'],
							),
						),
					),
					'contracts'         => array(
						'multicall3' => array(
							'address'      => '0xca11bde05977b3631167028862be2a173976ca11',
							'blockCreated' => '11_907_934',
						),
					),
				);

			}
		}

		return $data;
	}
	public function cpmwpsaveErrorLogs( $log_entry ) {
		$settings = get_option( 'cpmw_settings' );
		if ( ! isset( $settings['enable_debug_log'] ) || $settings['enable_debug_log'] == '1' ) {
			$logger = wc_get_logger();
			$logger->error( $log_entry, array( 'source' => 'pay_with_metamask' ) );
		}
	}

	public static function cpmwp_chain_id( $id_hx_value ) {
		$walletconnectNetworks = array(
			'0x1'        => 1,
			'0xa'        => 10,
			'0x38'       => 56,
			'0x61'       => 97,
			'0x89'       => 137,
			'0x64'       => 100,
			'0x12c'      => 300,
			'0x144'      => 324,
			'0x44d'      => 1101,
			'0x1388'     => 5000,
			'0x1389'     => 5001,
			'0x2019'     => 8217,
			'0x2105'     => 8453,
			'0x4268'     => 17000,
			'0xa4b1'     => 42161,
			'0xa4ec'     => 42220,
			'0xa869'     => 43113,
			'0xa86a'     => 43114,
			'0xe708'     => 59144,
			'0x14a34'    => 84532,
			'0x66eee'    => 421614,
			'0x76adf1'   => 7777777,
			'0xaa36a7'   => 11155111,
			'0xaa37dc'   => 11155420,
			'0x3b9ac9ff' => 999999999,
			'0x4e454152' => 1313161554,
			'0x4e454153' => 1313161555,
		);

		
		$options = get_option( 'cpmw_settings' );
		if ( isset( $options['custom_networks'] ) && is_array( $options['custom_networks'] ) ) {
			foreach ( $options['custom_networks'] as $key => $value ) {
				if ( isset( $value['chainId'] ) && ! array_key_exists( trim( sanitize_text_field( $value['chainId'] ) ), $walletconnectNetworks ) ) {
					$chain_id = trim( sanitize_text_field( $value['chainId'] ) );
					if ( ctype_xdigit( ltrim( $chain_id, '0x' ) ) ) {
						$walletconnectNetworks[ $chain_id ] = hexdec( $chain_id );
					}
				}
			}
		}
		
		if ( array_key_exists( trim( sanitize_text_field( $id_hx_value ) ), $walletconnectNetworks ) ) {
			return $walletconnectNetworks[ trim( sanitize_text_field( $id_hx_value ) ) ];
		} else {
			return false;
		}
	}

}
