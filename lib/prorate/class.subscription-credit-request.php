<?php
/**
 * Subscription Credit Request object.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class ITE_Prorate_Subscription_Credit_Request
 */
class ITE_Prorate_Subscription_Credit_Request extends ITE_Prorate_Credit_Request {

	/**
	 * @var IT_Exchange_Subscription
	 */
	protected $subscription;

	/**
	 * ITE_Prorate_Subscription_Credit_Request constructor.
	 *
	 * @param IT_Exchange_Subscription $subscription
	 * @param IT_Exchange_Product      $receiving
	 */
	public function __construct( IT_Exchange_Subscription $subscription, IT_Exchange_Product $receiving ) {
		parent::__construct( $subscription->get_product(), $receiving, $subscription->get_customer() );

		$this->subscription = $subscription;

		$this->update_additional_session_details( array(
			'_txn' => $subscription->get_transaction()->get_ID()
		) );
	}

	/**
	 * Helper method for reconstructing the credit request from the session.
	 *
	 * @since 2.0
	 *
	 * @param IT_Exchange_Product $receiving_product
	 * @param array               $session
	 *
	 * @return ITE_Prorate_Subscription_Credit_Request|null
	 */
	protected static function _get( IT_Exchange_Product $receiving_product, $session ) {

		if ( ! isset( $session['_txn'], $session['_prod'] ) ) {
			return null;
		}

		return new self( IT_Exchange_Subscription::from_transaction(
			it_exchange_get_transaction( $session['_txn'] ),
			it_exchange_get_product( $session['_prod'] )
		), $receiving_product );
	}

	/**
	 * @inheritDoc
	 */
	public function is_provider_auto_renewing() {
		return $this->get_subscription()->is_auto_renewing();
	}

	/**
	 * Get the subscription.
	 *
	 * @since 1.9
	 *
	 * @return IT_Exchange_Subscription
	 */
	public function get_subscription() {
		return $this->subscription;
	}

	/**
	 * @inheritDoc
	 */
	public function cancel_provider() {

		if ( $this->get_prorate_type() === 'upgrade' ) {
			$reason = __( 'Cancelled during upgrade.', 'LION' );
		} elseif ( $this->get_prorate_type() === 'downgrade' ) {
			$reason = __( 'Cancelled during downgrade.', 'LION' );
		} else {
			$reason = __( 'Cancelled during prorate.', 'LION' );
		}

		return $this->get_subscription()->cancel( null, $reason );
	}
}