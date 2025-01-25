<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'CPMWP_cronjob' ) ) {
	class CPMWP_cronjob {

		use CPMWP_HELPER;

		public function __construct() {
			// Register cron jobs
			add_filter( 'cron_schedules', array( $this, 'cpmwp_cron_schedules' ) );
			add_action( 'cpmwp_order_autoupdate', array( $this, 'pending_orders_autoupdater' ) );
			add_action( 'init', array( $this, 'cpmwp_schedule_events' ) );

		}

		public function cpmwp_schedule_events() {
			if ( ! wp_next_scheduled( 'cpmwp_order_autoupdate' ) ) {
				wp_schedule_event( time(), '5min', 'cpmwp_order_autoupdate' );
			}
		}

		/**
		 * Cron status schedule(s).
		 */
		public function cpmwp_cron_schedules( $schedules ) {
			// 5 minute schedule for grabbing all coins
			if ( ! isset( $schedules['5min'] ) ) {
				$schedules['5min'] = array(
					'interval' => 5 * 60,
					'display'  => __( 'Once every 5 minutes' ),
				);
			}
			return $schedules;
		}

		/*
		|-----------------------------------------------------------
		|   This will update the database after a specific interval
		|-----------------------------------------------------------
		|   Always use this function to update the database
		|-----------------------------------------------------------
		 */
		public function pending_orders_autoupdater() {
			$db                   = new cpmwp_database();
			$pending_transactions = $db->cpmwp_get_data_of_pending_transaction();
			if ( is_array( $pending_transactions ) && count( $pending_transactions ) >= 1 ) {

				foreach ( $pending_transactions as $key => $value ) {
					$pending_orderid = $value->order_id;
					$order_exits     = wc_get_order( $pending_orderid );
					$order_exits     = isset( $order_exits ) && $order_exits ? true : false;

					if ( ! $order_exits ) {
						continue;
					}

					$pendingorder = new WC_Order( $pending_orderid );
					$chain_id     = CPMWP_HELPER::cpmwp_chain_id( $value->chain_id );

					if ( $pendingorder->is_paid() == false && $chain_id ) {

						$amount = $pendingorder->get_meta( 'cpmwp_in_crypto' );
						$amount = str_replace( ',', '', $amount );

						$receipt = CPMWP_API_DATA::verify_transaction_info( $value->transaction_id, $chain_id, esc_html( $pending_orderid ), $amount );

						if ( $receipt['tx_status'] == '0x1' && $receipt['tx_amount_verify'] && ! isset( $receipt['tx_already_exists'] ) ) {
							$block_explorer = $this->cpmwp_get_explorer_url();
							$link_hash      = '<a href="' . esc_url( $block_explorer[ $value->chain_id ] . 'tx/' . $value->transaction_id ) . '" target="_blank">' . esc_html( $value->transaction_id ) . '</a>';
							$transection    = __( 'Payment Received via Pay with MetaMask - Transaction ID:', 'cpmwp' ) . $link_hash;
							$pendingorder->add_order_note( $transection );
							$pendingorder->payment_complete( $value->transaction_id );
							$db->update_fields_value( $pending_orderid, 'status', 'completed' );
						}
					}
				}
			}

		}

	}

	$cron_init = new CPMWP_cronjob();
}
