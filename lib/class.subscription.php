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

	const STATUS_ACTIVE = 'active';
	const STATUS_SUSPENDED = 'suspended';
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_DEACTIVATED = 'deactivated';

	/**
	 * @var IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * @var IT_Exchange_Recurring_Profile
	 */
	private $recurring_profile;

	/**
	 * @var IT_Exchange_Recurring_Profile
	 */
	private $trial_profile;

	/**
	 * IT_Exchange_Subscription constructor.
	 *
	 * @param IT_Exchange_Transaction $transaction
	 */
	public function __construct( IT_Exchange_Transaction $transaction ) {
		$this->transaction = $transaction;

		$type  = $transaction->get_transaction_meta( 'interval' );
		$count = $transaction->get_transaction_meta( 'interval_count' );

		if ( $count > 0 ) {
			$this->recurring_profile = new IT_Exchange_Recurring_Profile( $type, $count );

			if ( $transaction->get_transaction_meta( 'has_trial' ) ) {
				$this->trial_profile = new IT_Exchange_Recurring_Profile( $type, $count );
			}
		}
	}

	/**
	 * Create the Subscription data.
	 *
	 * @since 1.8
	 *
	 * @param IT_Exchange_Transaction $transaction
	 *
	 * @return IT_Exchange_Subscription
	 */
	public static function create( IT_Exchange_Transaction $transaction ) {

		foreach ( $transaction->get_products() as $product ) {

			$product = it_exchange_get_product( $product['product_id'] );

			break;
		}

		if ( ! isset( $product ) ) {
			throw new UnexpectedValueException();
		}

		if ( ! $product->supports_feature( 'recurring-payments' ) ) {
			throw new UnexpectedValueException();
		}

		if ( $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {

			$trial_enabled  = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
			$auto_renew     = $product->get_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) );
			$interval       = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) );
			$interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) );

			$transaction->update_transaction_meta( 'has_trial', $trial_enabled );
			$transaction->update_transaction_meta( 'is_auto_renewing', $auto_renew );
			$transaction->update_transaction_meta( 'interval', $interval );
			$transaction->update_transaction_meta( 'interval_count', $interval_count );
		}

		return new self( $transaction );
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
	 * @return IT_Exchange_Recurring_Profile|null
	 */
	public function get_recurring_profile() {
		return $this->recurring_profile;
	}

	/**
	 * Get the trial profile, if any.
	 *
	 * @since 1.8
	 *
	 * @return IT_Exchange_Recurring_Profile|null
	 */
	public function get_trial_profile() {
		return $this->trial_profile;
	}

	/**
	 * Check if the subscription is currently in its trial period.
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function is_trial_period() {

		if ( ! $this->get_trial_profile() ) {
			return false;
		}

		return ! $this->get_transaction()->has_children();
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
	 * Get the subscription status.
	 *
	 * @since 1.8
	 *
	 * @param bool $label
	 *
	 * @return string
	 */
	public function get_status( $label = false ) {

		$status = $this->get_transaction()->get_transaction_meta( 'subscriber_status' );

		if ( $label ) {
			$labels = self::get_statuses();

			return $labels[ $status ];
		}

		return $status;
	}

	/**
	 * Set the subscriber status.
	 *
	 * @since 1.8
	 *
	 * @param string $new_status
	 *
	 * @return string
	 */
	public function set_status( $new_status ) {

		if ( $new_status === $this->get_status() ) {
			throw new InvalidArgumentException( '$new_status === $old_status' );
		}

		$subscriber_id = $this->get_subscriber_id();

		$this->get_transaction()->update_transaction_meta( 'subscriber_status', $new_status );
		$subscriptions = $this->get_customer()->get_customer_meta( 'subscription_ids' );

		$old_status = isset( $subscriptions[ $subscriber_id ]['status'] ) ? $subscriptions[ $subscriber_id ]['status'] : '';

		$subscriptions[ $this->get_subscriber_id() ]['status'] = $new_status;
		$this->get_customer()->update_customer_meta( 'subscription_ids', $subscriptions );

		/**
		 * Fires when a subscription's status is changed.
		 *
		 * @since 1.8
		 *
		 * @param string                   $new_status
		 * @param string                   $old_status
		 * @param IT_Exchange_Subscription $this
		 */
		do_action( 'it_exchange_transition_subscription_status', $new_status, $old_status, $this );

		return $old_status;
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

	public static function get_statuses() {
		return array(
			self::STATUS_ACTIVE      => __( 'Active', 'LION' ),
			self::STATUS_SUSPENDED   => __( 'Suspended', 'LION' ),
			self::STATUS_DEACTIVATED => __( 'Deactivated', 'LION' ),
			self::STATUS_CANCELLED   => __( 'Cancelled', 'LION' )
		);
	}
}