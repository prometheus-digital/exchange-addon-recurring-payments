<?php
/**
 * Contains the upgrade routine for Zero Sum checkout.
 *
 * @since   1.8.4
 * @license GPLv2
 */

/**
 * Class IT_Exchange_Recurring_Payments_Zero_Sum_Checkout_Upgrade
 */
class IT_Exchange_Recurring_Payments_Zero_Sum_Checkout_Upgrade implements IT_Exchange_UpgradeInterface {

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
		return 'Zero Sum Checkout Complimentary';
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'zero-sum-checkout-complimentary';
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return __( 'Convert active Zero Sum Checkout subscriptions to complimentary status.', 'LION' );
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

		$args = array(
			'posts_per_page'     => $number,
			'paged'              => $page,
			'post_parent'        => 0,
			'transaction_method' => 'zero-sum-checkout'
		);

		if ( $ids ) {
			$args['fields'] = 'ids';
		}

		return it_exchange_get_transactions( $args );
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

			if ( empty( $status ) || $status === $sub::STATUS_ACTIVE ) {

				try {
					$sub->set_status( $sub::STATUS_COMPLIMENTARY );
				}
				catch ( Exception $e ) {
					$skin->warn( "Exception while setting subscription ({$sub->get_product()->ID}) status: {$e->getMessage()} for txn {$transaction->ID}." );
				}

				if ( $verbose ) {
					$skin->debug( "Updated subscription ({$sub->get_product()->ID}) status from '$status' to complimentary." );
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