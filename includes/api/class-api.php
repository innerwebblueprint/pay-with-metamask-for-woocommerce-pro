<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
if ( ! class_exists( 'CPMWP_API_DATA' ) ) {
	class CPMWP_API_DATA {

		// Constants for transient times
		const CRYPTOCOMPARE_TRANSIENT    = 10 * MINUTE_IN_SECONDS;
		const OPENEXCHANGERATE_TRANSIENT = 120 * MINUTE_IN_SECONDS;
		const BINANCE_TRANSIENT          = 10 * MINUTE_IN_SECONDS;

		// Constants for API endpoints
		const PANCACKE_SWAP                 = 'https://api.pancakeswap.info/api/v2/tokens/';
		const CRYPTOCOMPARE_API             = 'https://min-api.cryptocompare.com/data/price?fsym=';
		const BINANCE_API_COM               = 'https://api.binance.com/api/v3/ticker/24hr?symbol=';
		const BINANCE_API_US                = 'https://api.binance.us/api/v3/ticker/24hr?symbol=';
		const OPENEXCHANGERATE_API_ENDPOINT = 'https://openexchangerates.org/api/latest.json?app_id=';
		const WALLETCONNECT_API_ENDPOINT    = 'https://rpc.walletconnect.com/v1';

		// Function to get crypto compare API data
		public static function cpmwp_crypto_compare_api( $fiat, $crypto_token ) {
			$settings_obj = get_option( 'cpmw_settings' );
			$api          = ! empty( $settings_obj['crypto_compare_key'] ) ? $settings_obj['crypto_compare_key'] : '';
			$transient    = get_transient( 'cpmwp_currency' . $fiat . $crypto_token );
			if ( empty( $transient ) || $transient === '' ) {
				$response = wp_remote_post(
					esc_url_raw(self::CRYPTOCOMPARE_API . $fiat . '&tsyms=' . $crypto_token . '&api_key=' . $api),
					array(
						'timeout'   => 120,
						'sslverify' => true,
					)
				);
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					return $error_message;
				}
				$body      = wp_remote_retrieve_body( $response );
				$data_body = json_decode( $body );
				set_transient( 'cpmwp_currency' . $fiat . $crypto_token, $data_body, self::CRYPTOCOMPARE_TRANSIENT );
				return $data_body;
			} else {
				return $transient;
			}
		}

		// Function to get crypto compare admin API data
		public static function cpmwp_crypto_compare_admin_api( $fiat, $crypto_token ) {
			 $transient = get_transient( 'cpmwp_admin_currency' . $fiat . $crypto_token );
			if ( empty( $transient ) || $transient === '' ) {
				$response = wp_remote_post(
					esc_url_raw(self::CRYPTOCOMPARE_API . $crypto_token . '&tsyms=' . $fiat),
					array(
						'timeout'   => 120,
						'sslverify' => true,
					)
				);
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					return $error_message;
				}
				$body      = wp_remote_retrieve_body( $response );
				$data_body = json_decode( $body );
				set_transient( 'cpmwp_admin_currency' . $fiat . $crypto_token, $data_body, self::CRYPTOCOMPARE_TRANSIENT );
				return $data_body;
			} else {
				return $transient;
			}
		}

		// Function to get open exchange rates API data
		public static function cpmwp_openexchangerates_api() {
			$settings_obj = get_option( 'cpmw_settings' );
			$api          = ! empty( $settings_obj['openexchangerates_key'] ) ? $settings_obj['openexchangerates_key'] : '';
			if ( empty( $api ) ) {
				return;
			}
			$transient = get_transient( 'cpmwp_openexchangerates' );
			if ( empty( $transient ) || $transient === '' ) {
				$response = wp_remote_post(
					self::OPENEXCHANGERATE_API_ENDPOINT . $api . '',
					array(
						'timeout'   => 120,
						'sslverify' => true,
					)
				);
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					return $error_message;
				}
				$body      = wp_remote_retrieve_body( $response );
				$data_body = json_decode( $body );
				if ( isset( $data_body->error ) ) {
					return (object) array(
						'error'       => true,
						'message'     => $data_body->message,
						'description' => $data_body->description,
					);
				}
				set_transient( 'cpmwp_openexchangerates', $data_body, self::OPENEXCHANGERATE_TRANSIENT );
				return $data_body;
			} else {
				return $transient;
			}
		}

		// Function to get binance price API data
		public static function cpmwp_binance_price_api( $symbol ) {
			 $trans_name = 'cpmwp_binance_price' . $symbol;
			$transient   = get_transient( $trans_name );
			if ( empty( $transient ) || $transient === '' ) {
				$response = wp_remote_get(
					esc_url_raw(self::BINANCE_API_COM . $symbol),
					array(
						'timeout'   => 120,
						'sslverify' => true,
					)
				);
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					return $error_message;
				}
				$body      = wp_remote_retrieve_body( $response );
				$data_body = json_decode( $body );
				if ( isset( $data_body->msg ) ) {
					$response = wp_remote_get(
						esc_url_raw(self::BINANCE_API_US . $symbol),
						array(
							'timeout'   => 120,
							'sslverify' => true,
						)
					);
					if ( is_wp_error( $response ) ) {
						$error_message = $response->get_error_message();
						return $error_message;
					}
					$body      = wp_remote_retrieve_body( $response );
					$data_body = json_decode( $body );

				}
				set_transient( $trans_name, $data_body, self::BINANCE_TRANSIENT );
				return $data_body;
			} else {
				return $transient;
			}
		}

		// Function to get live price from coinbrain
		public static function cpmwp_get_coinbrain_api( $crypto_token, $contract_address, $DecChainId ) {
			$trans_name = 'cpmw_currency' . $crypto_token;
			$transient  = get_transient( $trans_name );
			if ( empty( $transient ) || $transient === '' ) {
				$headers                 = array();
				$headers['Content-Type'] = 'application/json';
				$json_request            = array( $DecChainId => array( $contract_address ) );
				$args                    = array(
					'method'    => 'POST',
					'timeout'   => 120,
					'sslverify' => true,
					'headers'   => $headers,
					'body'      => json_encode( $json_request ),
				);
				$requests_response       = wp_remote_post( esc_url_raw('https://api.coinbrain.com/public/coin-info'), $args );

				if ( is_wp_error( $requests_response ) ) {
					$error_message = $requests_response->get_error_message();
					return $error_message;
				}
				$body      = wp_remote_retrieve_body( $requests_response );
				$data_body = json_decode( $body, true );

				$data = isset( $data_body[0]['priceUsd'] ) ? $data_body[0]['priceUsd'] : '';
				set_transient( $trans_name, $data, self::BINANCE_TRANSIENT );
				return $data;
			} else {
				return $transient;
			}
		}

		/**
		 * Verify transaction info and return status along with verification result.
		 *
		 * @param string $txHash The transaction hash.
		 * @param int    $network The network ID.
		 * @param int    $order_id The order ID.
		 * @param string $amount The amount to verify.
		 * @return array
		 */
		public static function verify_transaction_info( $txHash, $network = 1, $order_id = 0, $amount = false ) {
			$options   = get_option( 'cpmw_settings' );
			$infura_id = isset( $options['project_id'] ) ? $options['project_id'] : '';
			$apiKey    = $infura_id; // replace with your Infura Project ID
			if ( empty( $apiKey ) ) {
				return;
			}

			$db               = new cpmwp_database();
			$tx_order_id      = $db->cpmwp_get_tx_order_id( $txHash );
			$reciever_address = ! empty( $options['user_wallet'] ) ? strtolower( sanitize_text_field( $options['user_wallet'] ) ) : '';

			if ( ! empty( $options['custom_networks'] ) && is_array( $options['custom_networks'] ) ) {
				foreach ( $options['custom_networks'] as $value ) {
					if ( ! empty( $value['chainId'] ) ) {
						if ( isset( $value['chainId'] ) ) {
							$chain_id = hexdec( trim( $value['chainId'] ) );
							if ( ctype_xdigit( ltrim( $chain_id, '0x' ) ) && $chain_id == $network ) {
								if ( isset( $value['recever_wallet'] ) && ! empty( $value['recever_wallet'] ) ) {
									$reciever_address = strtolower( sanitize_text_field( trim( $value['recever_wallet'] ) ) );
								}
								break; // Exit loop once the matching network is found
							}
						}
					}
				}
			}
			
			if ( count( $tx_order_id ) === 0 ) {
				return array(
					'tx_not_exists' => true,
				);
			}

			if ( count( $tx_order_id ) > 1 ) {
				return array(
					'tx_already_exists' => true,
				);
			}

			if ( count( $tx_order_id ) > 0 && $tx_order_id[0] != $order_id ) {
				return array(
					'tx_already_exists' => true,
				);
			}

			$url = esc_url_raw(self::WALLETCONNECT_API_ENDPOINT . "?chainId=eip155:$network&projectId=$apiKey");

			$data = array(
				array(
					'jsonrpc' => '2.0',
					'id'      => $network,
					'method'  => 'eth_getTransactionReceipt',
					'params'  => array( $txHash ),
				),
				array(
					'jsonrpc' => '2.0',
					'id'      => $network,
					'method'  => 'eth_getTransactionByHash',
					'params'  => array( $txHash ),
				),
			);

			$options = array(
				'body'    => json_encode( $data ),
				'timeout' => 120,
			);

			$result = wp_remote_post( $url, $options );
			$json   = json_decode( wp_remote_retrieve_body( $result ), true );

			$return_data = array(
				'tx_status'        => false,
				'tx_amount_verify' => false,
			);

			! defined( 'CPMWP_TX_INFO' ) && define( 'CPMWP_TX_INFO', true );
			require_once CPMWP_PATH . 'includes/tx_info/class-cpmwp-tx-info.php';

			$tx_verifier = CPMWP_TX_INFO::get_instance();

			foreach ( $json as $data ) {
				if ( ! empty( $reciever_address )) {
					$reciever_address = trim( $reciever_address );

					if ( isset( $data['result']['status'] ) ) {
						$return_data['tx_status'] = $data['result']['status'];
					}

					if ( isset( $data['result']['value'] ) ) {
						$tx_result=$tx_verifier->cpmwp_tx_verification( $data['result'], $amount,$reciever_address );
						if($tx_result === 'receiver are not same'){
							$return_data['receiver_failed'] = true;
						}else{
							$return_data['tx_amount_verify'] = $tx_result;
						}
					}
				}
			}

			return $return_data;
		}
	}
}
