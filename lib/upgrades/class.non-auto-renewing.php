<?php
/**
 * Contains the upgrade routine for Zero Sum checkout.
 *
 * @since   1.8.4
 * @license GPLv2
 */

/**
 * Class IT_Exchange_Recurring_Payments_Non_Auto_Renewing
 */
class IT_Exchange_Recurring_Payments_Non_Auto_Renewing implements IT_Exchange_UpgradeInterface {

	/**
	 * @inheritDoc
	 */
	public function get_version() {
		return '1.8.4';
	}

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return 'Non-Auto-Renewing Subscriptions';
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'non-auto-renewing-subscriptions';
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return __( "Repair non-auto-renewing subscriptions that don't have a status.", 'LION' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_group() {
		return 'Recurring Payments';
	}

	/**
	 * @inheritDoc
	 */
	public function get_total_records_to_process() {
		return count( $this->get_transactions( - 1, 1, true ) );
	}

	/**
	 * Get transactions to upgrade.
	 *
	 * @since 1.8.4
	 *
	 * @param int  $number
	 * @param int  $page
	 * @param bool $ids
	 *
	 * @return IT_Exchange_Transaction[]
	 */
	protected function get_transactions( $number = - 1, $page = 1, $ids = false ) {

		/** @var \wpdb $wpdb */
		global $wpdb;

		$sql = "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} 
	LEFT JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ) 
	LEFT JOIN {$wpdb->postmeta} AS mt1 ON ({$wpdb->posts}.ID = mt1.post_id AND mt1.meta_key = '_it_exchange_transaction_subscriber_status' ) 
WHERE 1=1  AND ( 
  {$wpdb->postmeta}.meta_key LIKE '_it_exchange_transaction_subscription_expires%' AND 
  mt1.post_id IS NULL
) ";

		if ( $number != - 1 ) {

			$offset = $number * ( $page - 1 );

			$sql .= "LIMIT {$offset}, {$number}";
		}

		$results = $wpdb->get_results( $sql );

		if ( $ids ) {
			return $results;
		}

		$transactions = array();

		foreach ( $results as $result ) {
			$transactions[] = it_exchange_get_transaction( $result->ID );
		}

		return $transactions;
	}

	/**
	 * @inheritDoc
	 */
	public function get_suggested_rate() {
		return 30;
	}

	/**
	 * Upgrade an individual transaction.
	 *
	 * @since 1.8.4
	 *
	 * @param IT_Exchange_Transaction           $transaction
	 * @param IT_Exchange_Upgrade_SkinInterface $skin
	 * @param bool                              $verbose
	 */
	protected function upgrade_transaction( IT_Exchange_Transaction $transaction, IT_Exchange_Upgrade_SkinInterface $skin, $verbose ) {

		if ( $verbose ) {
			$skin->debug( 'Upgrading Txn: ' . $transaction->ID );
		}

		if ( it_exchange_get_transaction_method( $transaction ) === 'zero-sum-checkout' ) {

			if ( $verbose ) {
				$skin->debug( 'Skipped Txn: ' . $transaction->ID . '. Zero Sum Transaction.' );
				$skin->debug( '' );
			}

			return;
		}

		$subs = it_exchange_get_transaction_subscriptions( $transaction );

		if ( ! $subs ) {

			if ( $verbose ) {
				$skin->debug( 'Skipped Txn: ' . $transaction->ID . '. No subscriptions found.' );
				$skin->debug( '' );
			}

			return;
		}

		foreach ( $subs as $sub ) {
			$status = $sub->get_status();

			if ( empty( $status ) && ! $sub->is_auto_renewing() ) {

				try {
					$sub->set_status( $sub::STATUS_ACTIVE );
				}
				catch ( Exception $e ) {
					$skin->warn( "Exception while setting subscription ({$sub->get_product()->ID}) status: {$e->getMessage()} for txn {$transaction->ID}." );
				}

				if ( $verbose ) {
					$skin->debug( "Updated subscription ({$sub->get_product()->ID}) status to active." );
				}
			}
		}

		if ( $verbose ) {
			$skin->debug( 'Upgraded Txn: ' . $transaction->ID );
			$skin->debug( '' );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function upgrade( IT_Exchange_Upgrade_Config $config, IT_Exchange_Upgrade_SkinInterface $skin ) {

		$transactions = $this->get_transactions( $config->get_number(), $config->get_step() );

		foreach ( $transactions as $transaction ) {
			$this->upgrade_transaction( $transaction, $skin, $config->is_verbose() );
			$skin->tick();
		}
	}
}