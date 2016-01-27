<?php
/**
 * Subscription class.
 *
 * @since   1.8
 * @license GPLv2
 */

/**
 * Class IT_Exchange_Subscription
 */
class IT_Exchange_Subscription {

	/**
	 * @var IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * IT_Exchange_Subscription constructor.
	 *
	 * @param IT_Exchange_Transaction $transaction
	 */
	public function __construct( IT_Exchange_Transaction $transaction ) {
		$this->transaction = $transaction;
	}

	/**
	 * Get the original transaction for this subscription.
	 *
	 * @since 1.8
	 *
	 * @return IT_Exchange_Transaction
	 */
	public function get_transaction() {
		return $this->transaction;
	}

	/**
	 * Get the product being subscribed to.
	 *
	 * @since 1.8
	 *
	 * @return IT_Exchange_Product
	 */
	public function get_product() {

		foreach ( $this->get_transaction()->get_products() as $product ) {
			return it_exchange_get_product( $product['product_id'] );
		}

		return null;
	}

	/**
	 * Get the recurring profile.
	 *
	 * @since 1.8
	 *
	 * @return IT_Exchange_Recurring_Profile
	 */
	public function get_recurring_profile() {

	}

	/**
	 * Get the trial profile, if any.
	 *
	 * @since 1.8
	 *
	 * @return IT_Exchange_Recurring_Profile|null
	 */
	public function get_trial_profile() {
	}

	/**
	 * Check if the subscription is currently in its trial period.
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function is_trial_period() {

	}

	/**
	 * Get the start date of this subscription.
	 *
	 * @since 1.8
	 *
	 * @return DateTime
	 */
	public function get_start_date() {
		return new DateTime( $this->get_transaction()->post_date_gmt, new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Get the expiry date of this subscription.
	 *
	 * @since 1.8
	 *
	 * @return DateTime
	 */
	public function get_expiry_date() {

		$expires = $this->get_transaction()->get_transaction_meta( 'subscription_expires_' . $this->get_product()->ID );

		if ( $expires ) {
			return new DateTime( "@$expires", new DateTimeZone( 'UTC' ) );
		}

		return null;
	}

	/**
	 * Get the subscriber ID.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function get_subscriber_id() {
		return $this->get_transaction()->get_transaction_meta( 'subscriber_id' );
	}

	/**
	 * Get the customer paying for this subscription.
	 *
	 * @since 1.8
	 *
	 * @return IT_Exchange_Customer
	 */
	public function get_customer() {
		return it_exchange_get_transaction_customer( $this->get_transaction() );
	}

	/**
	 * Get the beneficiary of this subscription.
	 *
	 * This will be the same as the customer, except for gifting.
	 */
	public function get_beneficiary() {
		return $this->get_customer();
	}

	/**
	 * Is this subscription auto-renewing.
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function is_auto_renewing() {
		return (bool) $this->get_transaction()->get_transaction_meta( 'subscription_autorenew_' . $this->get_product()->ID );
	}

	/**
	 * Cancel this subscription.
	 *
	 * @since 1.8
	 *
	 * @param bool $at_period_end Cancel this subscription at the end of the current period, or immediately.
	 */
	public function cancel( $at_period_end = false ) {

		$method = $this->get_transaction()->transaction_method;

		do_action( "it_exchange_cancel_{$method}_subscription", array(
			'old_subscriber_id' => $this->get_subscriber_id()
		) );
	}

	/**
	 * Transfer ownership of this subscription to someone.
	 *
	 * @since 1.8
	 *
	 * @param IT_Exchange_Customer $customer
	 */
	public function transfer( IT_Exchange_Customer $customer ) {
	}

	/**
	 * Is this subscription an installment.
	 *
	 * An installment means this subscription is limited to a set number of payments.
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function has_installment_plan() {
	}

	/**
	 * Get the total number of installments allocated for this subscription.
	 *
	 * @since 1.8
	 *
	 * @return int
	 *
	 * @throws UnexpectedValueException If this subscription does not have an installment plan.
	 */
	public function get_number_installments() {
	}

	/**
	 * Get the total number of installments remaining.
	 *
	 * @since 1.8
	 *
	 * @return int
	 *
	 * @throws UnexpectedValueException If this subscription does not have an installment plan.
	 */
	public function get_number_installments_remaining() {
	}
}