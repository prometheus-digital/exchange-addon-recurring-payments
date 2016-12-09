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
class IT_Exchange_Subscription implements ITE_Contract_Prorate_Credit_Provider {

	const E_NO_PROD = 1;
	const E_NOT_RECURRING = 2;

	const STATUS_ACTIVE = 'active';
	const STATUS_SUSPENDED = 'suspended';
	const STATUS_PAUSED = 'suspended';
	const STATUS_PAYMENT_FAILED = 'payment-failed';
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_DEACTIVATED = 'deactivated';
	const STATUS_COMPLIMENTARY = 'complimentary';
	const STATUS_PENDING_CANCELLATION = 'pending-cancellation';

	/**
	 * @var IT_Exchange_Transaction
	 */
	private $transaction;

	/**
	 * @var IT_Exchange_Product
	 */
	private $product;

	/**
	 * @var IT_Exchange_Recurring_Profile
	 */
	private $recurring_profile;

	/**
	 * @var IT_Exchange_Recurring_Profile
	 */
	private $trial_profile;

	/** @var bool */
	private $is_pausing = false;

	/** @var bool */
	private $is_resuming = false;

	/** @var bool */
	private $is_cancelling = false;

	/**
	 * IT_Exchange_Subscription constructor.
	 *
	 * @param IT_Exchange_Transaction $transaction
	 * @param IT_Exchange_Product     $product
	 */
	private function __construct( IT_Exchange_Transaction $transaction, IT_Exchange_Product $product ) {
		$this->transaction = $transaction;
		$this->product     = $product;

		if ( ! $this->meta_exists( 'interval' ) ) {

			if ( ! $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				throw new InvalidArgumentException();
			}

			$interval = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) );
			$this->update_meta( 'interval', $interval );
		}

		if ( ! $this->meta_exists( 'interval_count' ) ) {
			$interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) );
			$this->update_meta( 'interval_count', $interval_count );
		}

		if ( ! $this->meta_exists( 'has_trial' ) ) {
			$has_trial = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
			$this->update_meta( 'has_trial', $has_trial );
		}

		$type  = $this->get_meta( 'interval', true );
		$count = $this->get_meta( 'interval_count', true );

		if ( $count > 0 ) {
			$this->recurring_profile = new IT_Exchange_Recurring_Profile( $type, $count );

			if ( $this->get_meta( 'has_trial', true ) ) {

				if ( ! $this->meta_exists( 'trial_interval' ) ) {
					$trial_interval = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval' ) );
					$this->update_meta( 'trial_interval', $trial_interval );
				}

				if ( ! $this->meta_exists( 'trial_interval_count' ) ) {
					$trial_interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );
					$this->update_meta( 'trial_interval_count', $trial_interval_count );
				}

				$type  = $this->get_meta( 'trial_interval', true );
				$count = $this->get_meta( 'trial_interval_count', true );

				$this->trial_profile = new IT_Exchange_Recurring_Profile( $type, $count );
			}
		}
	}

	/**
	 * Construct a subscription from a transaction.
	 *
	 * @since 1.8
	 *
	 * @param IT_Exchange_Transaction $transaction
	 * @param IT_Exchange_Product     $product
	 *
	 * @return IT_Exchange_Subscription
	 */
	public static function from_transaction( IT_Exchange_Transaction $transaction, IT_Exchange_Product $product ) {
		return new self( $transaction, $product );
	}

	/**
	 * Get a subscription by its ID.
	 *
	 * @since 1.9.0
	 *
	 * @param string $id
	 *
	 * @return IT_Exchange_Subscription|null
	 */
	public static function get( $id ) {
		$parts = explode( ':', $id );

		if ( count( $parts ) !== 2 ) {
			throw new InvalidArgumentException( 'Invalid subscription ID.' );
		}

		$transaction = it_exchange_get_transaction( $parts[0] );
		$product     = it_exchange_get_product( $parts[1] );

		if ( ! $transaction || ! $product ) {
			return null;
		}

		try {
			return self::from_transaction( $transaction, $product );
		} catch ( \InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Create the Subscription data.
	 *
	 * @since 1.8
	 *
	 * @param IT_Exchange_Transaction  $transaction
	 * @param IT_Exchange_Product|null $product     Non-auto-renewing products can be purchased simultaneously,
	 *                                              use this to specify which subscription should be returned.
	 *
	 * @return IT_Exchange_Subscription
	 *
	 * @throws InvalidArgumentException If which subscription to return is ambiguous.
	 * @throws UnexpectedValueException If invalid product.
	 */
	public static function create( IT_Exchange_Transaction $transaction, IT_Exchange_Product $product = null ) {

		$found = false;

		if ( $product ) {
			foreach ( $transaction->get_products() as $cart_product ) {

				if ( $cart_product['product_id'] == $product->ID ) {
					$found = true;

					break;
				}
			}
		} elseif ( count( $transaction->get_products() ) === 1 ) {

			$cart_products = $transaction->get_products();
			$cart_product  = reset( $cart_products );
			$found         = true;
			$product       = it_exchange_get_product( $cart_product['product_id'] );

		} else {
			throw new InvalidArgumentException( 'Ambiguous product to generate subscriptions for.' );
		}

		if ( ! $found ) {
			throw new UnexpectedValueException( 'Product not found.', self::E_NO_PROD );
		}

		if ( ! $product->supports_feature( 'recurring-payments' ) ) {
			throw new UnexpectedValueException( 'Product does not support recurring payments.', self::E_NOT_RECURRING );
		}

		if ( ! $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			throw new UnexpectedValueException( 'Product does not have recurring enabled.', self::E_NOT_RECURRING );
		}

		$trial_enabled        = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
		$auto_renew           = $product->get_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) );
		$interval             = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) );
		$interval_count       = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) );
		$trial_interval       = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval' ) );
		$trial_interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );

		if ( $trial_enabled && function_exists( 'it_exchange_is_customer_eligible_for_trial' ) ) {
			$customer      = it_exchange_get_transaction_customer( $transaction );
			$trial_enabled = it_exchange_is_customer_eligible_for_trial( $product, $customer );
		}

		$transaction->update_meta( 'has_trial_' . $product->ID, $trial_enabled );
		$transaction->update_meta( 'is_auto_renewing_' . $product->ID, $auto_renew );
		$transaction->update_meta( 'subscription_autorenew_' . $product->ID, $auto_renew === 'on' );
		$transaction->update_meta( 'interval_' . $product->ID, $interval );
		$transaction->update_meta( 'interval_count_' . $product->ID, $interval_count );

		if ( $trial_enabled ) {
			$transaction->update_meta( 'trial_interval_' . $product->ID, $trial_interval );
			$transaction->update_meta( 'trial_interval_count_' . $product->ID, $trial_interval_count );
		}

		$subscription = new self( $transaction, $product );

		if ( $max = $product->get_feature( 'recurring-payments', array( 'setting' => 'max-occurrences' ) ) ) {

			if ( ! $subscription->is_trial_period() ) {
				$max -= 1;
			}

			$subscription->update_meta( 'remaining_occurrences', $max );
		}

		/**
		 * Fires when a subscription is created.
		 *
		 * @since 1.8.4
		 *
		 * @param IT_Exchange_Subscription $subscription
		 */
		do_action( 'it_exchange_subscription_created', $subscription );

		return $subscription;
	}

	/**
	 * Get the subscription ID.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_id() {
		return "{$this->get_transaction()->get_ID()}:{$this->get_product()->ID}";
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
		return $this->product;
	}

	/**
	 * Is this subscription auto-renewing.
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function is_auto_renewing() {

		if ( ! $this->meta_exists( 'subscription_autorenew' ) ) {

			$auto_renew = $this->get_product()->get_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) ) === 'on';

			$this->update_meta( 'subscription_autorenew', $auto_renew );
		}

		return (bool) $this->get_meta( 'subscription_autorenew', true );
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
	 * Get the start date of this subscription.
	 *
	 * @since 1.8
	 *
	 * @return DateTime
	 */
	public function get_start_date() {
		return new DateTime( $this->get_transaction()->get_date( true ), new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Get the expiry date of this subscription.
	 *
	 * @since 1.8
	 *
	 * @return DateTime
	 */
	public function get_expiry_date() {

		$expires = $this->get_meta( 'subscription_expires', true );

		if ( ! $expires ) {
			$expires = $this->get_meta( 'subscription_expired', true );
		}

		if ( $expires ) {
			return new DateTime( "@$expires", new DateTimeZone( 'UTC' ) );
		}

		return null;
	}

	/**
	 * Set the expiration date.
	 *
	 * @since 1.8
	 *
	 * @param DateTime|null $date Setting the expiry date to null will allow the subscription to last forever.
	 */
	public function set_expiry_date( DateTime $date = null ) {

		$previous = $this->get_expiry_date();
		$now      = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		if ( $date === null || $now < $date ) {
			$this->update_meta( 'subscription_expires', $date ? $date->format( 'U' ) : '' );
			$this->delete_meta( 'subscription_expired' );
		} else {
			$this->update_meta( 'subscription_expired', $date->format( 'U' ) );
			$this->delete_meta( 'subscription_expires' );
		}

		/**
		 * Fires when a subscription's expiry date has been updated.
		 *
		 * @since 1.8.4
		 *
		 * @param IT_Exchange_Subscription $this
		 * @param DateTime|null            $previous
		 */
		do_action( 'it_exchange_subscription_set_expiry_date', $this, $previous );
	}

	/**
	 * Get the subscriber ID.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function get_subscriber_id() {
		return $this->get_transaction()->get_meta( 'subscriber_id' );
	}

	/**
	 * Set the subscriber ID.
	 *
	 * @since 1.8
	 *
	 * @param string $new_id
	 */
	public function set_subscriber_id( $new_id ) {

		$old_id = $this->get_subscriber_id();
		$this->get_transaction()->update_meta( 'subscriber_id', $new_id );

		$customer_subscription_ids = $this->get_customer()->get_customer_meta( 'subscription_ids' );

		if ( ! empty( $old_id ) ) {
			unset( $customer_subscription_ids[ $old_id ] );
		}

		$customer_subscription_ids[ $new_id ]['txn_id'] = $this->get_transaction()->ID;
		$customer_subscription_ids[ $new_id ]['status'] = $this->get_status();
		$this->get_customer()->update_customer_meta( 'subscription_ids', $customer_subscription_ids );

		/**
		 * Fires when a subscription's subscriber ID has been updated.
		 *
		 * @since 1.8.4
		 *
		 * @param IT_Exchange_Subscription $this
		 * @param string                   $old_id
		 */
		do_action( 'it_exchange_subscription_set_subscriber_id', $this, $old_id );
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

		$status = $this->get_meta( 'subscriber_status', true );

		if ( empty( $status ) ) {
			$status = $this->get_transaction()->get_meta( 'subscriber_status' );

			if ( $status ) {
				$this->update_meta( 'subscriber_status_' . $this->get_product()->ID, $status );
			}
		}

		if ( $label ) {
			$labels = self::get_statuses();

			return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
		}

		return $status;
	}

	/**
	 * Is the subscription's status one of the following.
	 *
	 * @since 1.9.0
	 *
	 * @param string|array $status,...
	 *
	 * @return bool
	 */
	public function is_status( $status ) {

		if ( ! is_array( $status ) ) {
			$status = func_get_args();
		}

		$current = $this->get_status();

		foreach ( $status as $stati ) {
			if ( $stati === $current ) {
				return true;
			}
		}

		return false;
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

		$old_status = $this->get_status();

		if ( $new_status === $old_status ) {
			throw new InvalidArgumentException( '$new_status === $old_status' );
		}

		$this->update_meta( 'subscriber_status', $new_status );
		$this->get_transaction()->update_meta( 'subscriber_status', $new_status ); // back-compat

		$subscriptions = $this->get_customer()->get_customer_meta( 'subscription_ids' );

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

		if ( $new_status === self::STATUS_COMPLIMENTARY && $old_status === self::STATUS_ACTIVE && $this->is_auto_renewing() ) {
			$this->cancel( null, __( 'Original subscription cancelled during complimentary transition.', 'LION' ), false );
		}

		return $old_status;
	}

	/**
	 * Bump the expiration date.
	 *
	 * @since 1.8
	 *
	 * @param DateTime $from
	 */
	public function bump_expiration_date( DateTime $from = null ) {

		if ( $this->is_trial_period() ) {
			$profile = $this->get_trial_profile();
		} else {
			$profile = $this->get_recurring_profile();
		}

		if ( $from ) {
			$now = (int) $from->format( 'U' );
		} else {
			$now = time();
		}

		$time = strtotime( $profile->get_interval(), $now );

		if ( $this->is_auto_renewing() && $this->get_status() !== self::STATUS_COMPLIMENTARY ) {
			$time += DAY_IN_SECONDS;
		}

		/**
		 * Filter the new expiration date.
		 *
		 * @since 1.8
		 *
		 * @param int                      $time
		 * @param IT_Exchange_Subscription $this
		 */
		$time = apply_filters( 'it_exchange_bump_subscription_new_expiration_date', $time, $this );

		if ( ! $time ) {
			return;
		}

		$this->set_expiry_date( new DateTime( "@$time", new DateTimeZone( 'UTC' ) ) );

		/**
		 * Fires when a subscription's expiration date is bumped.
		 *
		 * @since 1.8
		 *
		 * @param IT_Exchange_Subscription
		 */
		do_action( 'it_exchange_bump_subscription_expiration_date', $this );
	}

	/**
	 * Mark this subscription as expired.
	 *
	 * This does not toggle the status, but changed the date storage.
	 *
	 * @since 1.8
	 */
	public function mark_expired() {

		$expires = $this->get_expiry_date();

		$this->delete_meta( 'subscription_expires' );

		if ( $expires ) {
			$this->update_meta( 'subscription_expired', $expires->format( 'U' ) );
		}
	}

	/**
	 * Are the occurrences of this subscription limited.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function are_occurrences_limited() {
		return $this->meta_exists( 'remaining_occurrences' );
	}

	/**
	 * Get the remaining occurrences.
	 *
	 * @since 1.9.0
	 *
	 * @return int|null
	 *
	 * @throws UnexpectedValueException If the subscription is not occurrence limited.
	 */
	public function get_remaining_occurrences() {

		if ( ! $this->are_occurrences_limited() ) {
			throw new UnexpectedValueException( 'This subscription does not have a limited number of occurrences.' );
		};

		return (int) $this->get_meta( 'remaining_occurrences', true );
	}

	/**
	 * Decrement the remaining occurrences.
	 *
	 * If the decrement makes it 0, the subscription will be cancelled.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 *
	 * @throws UnexpectedValueException If the subscription is not occurrence limited, or
	 *                                  the remaining occurrences are aleady 0.
	 */
	public function decrement_remaining_occurrences() {

		if ( ! $this->are_occurrences_limited() ) {
			throw new UnexpectedValueException( 'This subscription does not have a limited number of occurrences.' );
		}

		$remaining = $this->get_remaining_occurrences();

		if ( $remaining === 0 ) {
			throw new UnexpectedValueException( 'This subscription has already reached its occurrences limit.' );
		}

		$remaining -= 1;

		if ( $remaining === 0 ) {
			$this->set_expiry_date( null );
			$this->cancel( null, __( 'Number of occurrences reached.', 'LION' ), false );

			/**
			 * Fires when a subscription's occurrences limit is reached.
			 *
			 * @since 1.9.0
			 *
			 * @param IT_Exchange_Subscription $this
			 */
			do_action( 'it_exchange_subscription_occurrences_limit_reached', $this );
		}

		return (bool) $this->update_meta( 'remaining_occurrences', $remaining );
	}

	/**
	 * The total number of days left in this subscription period.
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	public function get_days_left_in_period() {

		$now     = new DateTime( date( 'Y-m-d' ) . ' 00:00:00' );
		$expires = $this->get_expiry_date();

		if ( $expires <= $now ) {
			return 0;
		}

		if ( method_exists( $now, 'diff' ) ) {
			$diff = $now->diff( $expires );
			$days = $diff->days;
		} else {
			// this is inaccurate, DateTime::diff handles daylight saving, etc...
			$diff = (int) $expires->format( 'U' ) - (int) $expires->format( 'U' );
			$days = floor( $diff / DAY_IN_SECONDS );
		}

		if ( $this->is_auto_renewing() && $this->get_status() !== self::STATUS_COMPLIMENTARY ) {
			$days -= 1;
		}

		return max( $days, 0 );
	}

	/**
	 * Add meta.
	 *
	 * @since 1.9.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $unique
	 *
	 * @return false|int
	 */
	public function add_meta( $key, $value, $unique = false ) {
		return $this->get_transaction()->add_meta( $this->transform_meta_key( $key ), $value, $unique );
	}

	/**
	 * Update metadata.
	 *
	 * @since 1.9.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|int
	 */
	public function update_meta( $key, $value ) {
		return $this->get_transaction()->update_meta( $this->transform_meta_key( $key ), $value );
	}

	/**
	 * Get metadata.
	 *
	 * @since 1.9.0
	 *
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	public function get_meta( $key = '', $single = false ) {
		return $this->get_transaction()->get_meta( $this->transform_meta_key( $key ), $single );
	}

	/**
	 * Delete a meta key.
	 *
	 * @since 1.9.0
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public function delete_meta( $key, $value = '' ) {
		return $this->get_transaction()->delete_meta( $this->transform_meta_key( $key ), $value );
	}

	/**
	 * Does meta with the given key exist.
	 *
	 * @since 1.9.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function meta_exists( $key ) {
		return $this->get_transaction()->meta_exists( $this->transform_meta_key( $key ) );
	}

	/**
	 * Transform a meta key.
	 *
	 * @since 1.9.0
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected final function transform_meta_key( $key ) {
		return "{$key}_{$this->get_product()->ID}";
	}

	/**
	 * @inheritDoc
	 *
	 * @param ITE_Prorate_Subscription_Credit_Request $request
	 */
	public static function handle_prorate_credit_request( ITE_Prorate_Credit_Request $request, ITE_Daily_Price_Calculator $calculator ) {

		if ( ! self::accepts_prorate_credit_request( $request ) ) {
			throw new DomainException( "This credit request can't be handled by this provider." );
		}

		$sub = $request->get_subscription();
		$for = $request->get_product_providing_credit();

		if ( $for->ID != $sub->get_product()->ID ) {
			throw new InvalidArgumentException(
				"Given product with ID '$for->ID' does not match subscription product '{$sub->get_product()->ID}'."
			);
		}

		$amount_paid = $sub->calculate_recurring_amount_paid();
		$daily_price = $calculator->calculate( $sub->get_recurring_profile(), $amount_paid );
		$days_left   = $sub->get_days_left_in_period();

		$credit = min( $daily_price * $days_left, $amount_paid );

		$request->set_credit( $credit );

		$request->update_additional_session_details( array(
			'old_transaction_id'     => $sub->get_transaction()->get_ID(),
			'old_transaction_method' => $sub->get_transaction()->get_method(),
		) );

		if ( $sub->get_subscriber_id() && $sub->is_auto_renewing() ) {
			$request->update_additional_session_details( array(
				'old_subscriber_id' => $sub->get_subscriber_id()
			) );
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function accepts_prorate_credit_request( ITE_Prorate_Credit_Request $request ) {
		return $request instanceof ITE_Prorate_Subscription_Credit_Request;
	}

	/**
	 * Calculate the amount being paid for this subscription.
	 *
	 * This will use the latest child payment and attempt to ignore coupons
	 * to try and be as accurate as possible.
	 *
	 * @since 1.9
	 *
	 * @return float
	 */
	public function calculate_recurring_amount_paid() {

		$children = $this->get_transaction()->get_children( array(
			'numberposts' => 1
		), true );

		if ( $children ) {
			$transaction = reset( $children );
		} else {
			$transaction = $this->get_transaction();
		}

		$product_id = $this->get_product()->ID;

		/** @var ITE_Cart_Product $cart_product */
		$cart_product = $transaction->get_items( 'product' )->filter( function ( ITE_Cart_Product $product ) use ( $product_id ) {
			return $product->get_product() && $product->get_product()->ID == $product_id;
		} )->first();

		// auto-renewing subscriptions only can be purchased individually
		if ( $this->is_auto_renewing() ) {
			$amount_paid = $transaction->get_total( false );

			if ( $amount_paid <= 0 && $cart_product ) {
				$amount_paid -= $cart_product->get_line_items()->with_only( 'fee' )
				                             ->filter( function ( ITE_Fee_Line_Item $fee ) { return ! $fee->is_recurring(); } )
				                             ->total();
			}

		} else {

			if ( ! $cart_product ) {
				throw new UnexpectedValueException( 'Could not determine the amount paid for the subscription.' );
			}

			$amount_paid = $cart_product->get_amount() * $cart_product->get_quantity();

			if ( $transaction->get_total( false ) < $amount_paid ) {
				$amount_paid = $transaction->get_total( false );
			}
		}

		return (float) $amount_paid;
	}

	/**
	 * Can the subscription be paused.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function can_be_paused() {

		if ( ! $this->get_subscriber_id() ) {
			return false;
		}

		$gateway = $this->get_transaction()->get_gateway();

		if ( ! $gateway || ! $gateway->can_handle( 'pause-subscription' ) ) {
			return false;
		}

		$can = $this->is_status( self::STATUS_ACTIVE );

		/**
		 * Filter whether the subscription can be paused.
		 *
		 * @since 1.9.0
		 *
		 * @param bool                     $can
		 * @param IT_Exchange_Subscription $this
		 */
		return apply_filters( 'it_exchange_subscription_can_be_paused', $can, $this );
	}

	/**
	 * Pause the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @param IT_Exchange_Customer|null $paused_by
	 *
	 * @return bool
	 */
	public function pause( IT_Exchange_Customer $paused_by = null ) {

		if ( ! $this->can_be_paused() ) {
			return false;
		}

		$factory = new ITE_Gateway_Request_Factory();
		$request = $factory->make( 'pause-subscription', array_filter( array(
			'subscription' => $this,
			'paused_by'    => $paused_by,
		) ) );

		$this->is_pausing = true;

		$r = $this->get_transaction()->get_gateway()->get_handler_for( $request )->handle( $request );

		if ( $r ) {
			$this->set_status( self::STATUS_PAUSED );

			/**
			 * Fires when a subscription has been paused.
			 *
			 * @since 1.9.0
			 *
			 * @param \IT_Exchange_Subscription $this
			 */
			do_action( 'it_exchange_pause_subscription', $this );
		}

		$this->is_pausing = false;

		return $r;
	}

	/**
	 * Retrieve the user who paused the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @return IT_Exchange_Customer|null
	 */
	public function get_paused_by() {

		$id = $this->get_meta( 'subscription_paused_by', true );

		if ( ! $id ) {
			return null;
		}

		return it_exchange_get_customer( $id ) ?: null;
	}

	/**
	 * Set the person who paused the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @param IT_Exchange_Customer|null $customer
	 *
	 * @return bool
	 */
	public function set_paused_by( IT_Exchange_Customer $customer = null ) {
		return (bool) $this->update_meta( 'subscription_paused_by', $customer ? $customer->id : null );
	}

	/**
	 * Is the subscription currently being paused.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_pausing() { return $this->is_pausing; }

	/**
	 * Can the subscription be resumed.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function can_be_resumed() {

		if ( ! $this->get_subscriber_id() ) {
			return false;
		}

		$gateway = $this->get_transaction()->get_gateway();

		if ( ! $gateway || ! $gateway->can_handle( 'resume-subscription' ) ) {
			return false;
		}

		if ( ! $this->is_status( self::STATUS_PAUSED ) ) {
			return false;
		}

		/**
		 * Filter whether the subscription can be resumed.
		 *
		 * @since 1.9.0
		 *
		 * @param bool                     $can
		 * @param IT_Exchange_Subscription $this
		 */
		return apply_filters( 'it_exchange_subscription_can_be_resumed', true, $this );
	}

	/**
	 * Resume the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @param IT_Exchange_Customer|null $resumed_by
	 *
	 * @return bool
	 */
	public function resume( IT_Exchange_Customer $resumed_by = null ) {

		if ( ! $this->can_be_resumed() ) {
			return false;
		}

		$factory = new ITE_Gateway_Request_Factory();
		$request = $factory->make( 'resume-subscription', array_filter( array(
			'subscription' => $this,
			'resumed_by'   => $resumed_by,
		) ) );

		$this->is_resuming = true;

		$r = $this->get_transaction()->get_gateway()->get_handler_for( $request )->handle( $request );

		if ( $r ) {
			$this->set_status( self::STATUS_ACTIVE );

			/**
			 * Fires when a subscription has been resumed.
			 *
			 * @since 1.9.0
			 *
			 * @param \IT_Exchange_Subscription $this
			 */
			do_action( 'it_exchange_resume_subscription', $this );
		}

		$this->is_resuming = false;

		return $r;
	}

	/**
	 * Retrieve the user who resumed the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @return IT_Exchange_Customer|null
	 */
	public function get_resumed_by() {

		$id = $this->get_meta( 'subscription_resumed_by', true );

		if ( ! $id ) {
			return null;
		}

		return it_exchange_get_customer( $id ) ?: null;
	}

	/**
	 * Set the person who resumed the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @param IT_Exchange_Customer|null $customer
	 *
	 * @return bool
	 */
	public function set_resumed_by( IT_Exchange_Customer $customer = null ) {
		return (bool) $this->update_meta( 'subscription_resumed_by', $customer ? $customer->id : null );
	}

	/**
	 * Is the subscription currently being resumed.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_resuming() { return $this->is_resuming; }

	/**
	 * Can the subscription be cancelled.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function can_be_cancelled() {

		if ( ! $this->get_subscriber_id() ) {
			return false;
		}

		if ( ! $this->is_status( self::STATUS_ACTIVE, self::STATUS_PAUSED, self::STATUS_PAYMENT_FAILED ) ) {
			return false;
		}

		$gateway = $this->get_transaction()->get_gateway();

		if ( ! $gateway || ! $gateway->can_handle( 'cancel-subscription' ) ) {
			return false;
		}

		return apply_filters( 'it_exchange_subscription_can_be_cancelled', true, $this );
	}

	/**
	 * Cancel this subscription.
	 *
	 * @since 1.8
	 *
	 * @param IT_Exchange_Customer $cancelled_by
	 * @param string               $reason
	 * @param bool                 $set_status
	 *
	 * @return bool
	 */
	public function cancel( IT_Exchange_Customer $cancelled_by = null, $reason = '', $set_status = true ) {

		$gateway = ITE_Gateways::get( $this->get_transaction()->get_method() );

		if ( $gateway && $gateway->can_handle( 'cancel-subscription' ) ) {

			$factory = new ITE_Gateway_Request_Factory();
			$request = $factory->make( 'cancel-subscription', array_filter( array(
				'subscription' => $this,
				'reason'       => $reason,
				'cancelled_by' => $cancelled_by,
				'set_status'   => $set_status
			) ) );

			$this->is_cancelling = true;
			$handled             = $gateway->get_handler_for( $request )->handle( $request );

			if ( ! $handled ) {
				return false;
			}

			/**
			 * Fires when a subscription has been cancelled.
			 *
			 * This is different from listening for when the status has been updated to cancelled.
			 *
			 * @since 1.9.0
			 *
			 * @param \IT_Exchange_Subscription $this
			 */
			do_action( 'it_exchange_cancel_subscription', $this );

			$this->is_cancelling = false;

			return true;
		} else {

			$method = $this->get_transaction()->get_method();

			do_action( "it_exchange_cancel_{$method}_subscription", array(
				'old_subscriber_id' => $this->get_subscriber_id(),
				'customer'          => $this->get_customer(),
				'subscription'      => $this
			) );

			return true;
		}
	}

	/**
	 * Is this subscription being cancelled.
	 *
	 * @since 1.9.0
	 *
	 * @return boolean
	 */
	public function is_cancelling() {
		return $this->is_cancelling;
	}

	/**
	 * Retrieve the user who cancelled the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @return IT_Exchange_Customer|null
	 */
	public function get_cancelled_by() {

		$id = $this->get_meta( 'subscription_cancelled_by', true );

		if ( ! $id ) {
			return null;
		}

		return it_exchange_get_customer( $id ) ?: null;
	}

	/**
	 * Set the person who cancelled the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @param IT_Exchange_Customer $customer
	 *
	 * @return bool
	 */
	public function set_cancelled_by( IT_Exchange_Customer $customer ) {
		return (bool) $this->update_meta( 'subscription_cancelled_by', $customer->id );
	}

	/**
	 * Get the cancellation reason.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_cancellation_reason() {
		return $this->get_meta( 'subscription_cancellation_reason', true );
	}

	/**
	 * Get the cancellation reason.
	 *
	 * @since 1.9.0
	 *
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function set_cancellation_reason( $reason ) {
		return (bool) $this->update_meta( 'subscription_cancellation_reason', $reason );
	}

	/**
	 * Get all available upgrades for this subscription.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Prorate_Credit_Request[]
	 */
	public function get_available_upgrades() {

		$all_parents = it_exchange_get_all_subscription_product_parents( $this->get_product()->ID );

		if ( ! $all_parents ) {
			return array();
		}

		$requests = array();

		foreach ( $all_parents as $parent_id ) {
			$parent = it_exchange_get_product( $parent_id );

			if ( $parent ) {
				$requests[] = new ITE_Prorate_Subscription_Credit_Request( $this, $parent );
			}
		}

		return $requests;
	}

	/**
	 * Get all available downgrades for this subscription.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Prorate_Credit_Request[]
	 */
	public function get_available_downgrades() {

		$all_children = it_exchange_get_all_subscription_product_children( $this->get_product()->ID );

		if ( ! $all_children ) {
			return array();
		}

		$requests = array();

		foreach ( $all_children as $child_id ) {
			$child = it_exchange_get_product( $child_id );

			if ( $child ) {
				$requests[] = new ITE_Prorate_Subscription_Credit_Request( $this, $child );
			}
		}

		return $requests;
	}

	/**
	 * Record a gateway cancellation while the subscription is complimentary.
	 *
	 * @since      1.8.4
	 *
	 * @deprecated 1.9.0
	 *
	 * @param string $gateway
	 */
	public function record_gateway_cancellation_while_complimentary( $gateway ) {

		_deprecated_function( __FUNCTION__, '1.9.0' );

		$builder = new IT_Exchange_Txn_Activity_Builder( $this->get_transaction(), 'status' );
		$builder->set_description( __( 'Original recurring payment has been cancelled.', 'LION' ) );
		$builder->set_actor( new IT_Exchange_Txn_Activity_Gateway_Actor( it_exchange_get_addon( $gateway ) ) );
		$builder->build( it_exchange_get_txn_activity_factory() );
	}

	/**
	 * Can the payment source be updated.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function can_payment_source_be_updated() {

		if ( ! $this->is_auto_renewing() ) {
			return false;
		}

		if ( ! $this->get_subscriber_id() ) {
			return false;
		}

		if ( ! ( $g = $this->get_transaction()->get_gateway() ) || ! $g->can_handle( 'update-subscription-payment-method' ) ) {
			return false;
		}

		$can = $this->is_status( self::STATUS_ACTIVE, self::STATUS_PAYMENT_FAILED );

		/**
		 * Can the subscription's payment source be updated.
		 *
		 * @since 1.9.0
		 *
		 * @param bool                      $can
		 * @param \IT_Exchange_Subscription $this
		 */
		return apply_filters( 'it_exchange_can_subscription_payment_source_be_updated', $can, $this );
	}

	/**
	 * Get the payment source used for this subscription.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Gateway_Payment_Source|null
	 */
	public function get_payment_source() {

		if ( $t = $this->get_payment_token() ) {
			return $t;
		}

		return $this->get_card();
	}

	/**
	 * Get the card used for payment.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Gateway_Card|null
	 */
	public function get_card() {

		if ( $this->meta_exists( 'subscription_payment_card' ) ) {

			$card = $this->get_meta( 'subscription_payment_card', true );

			if ( ! $card ) {
				return null;
			}

			return new ITE_Gateway_Card( $card['number'], $card['year'], $card['month'], 0 );
		}

		return $this->get_transaction()->get_card();
	}

	/**
	 * Get the card used for payment.
	 *
	 * @since 1.9.0
	 *
	 * @param ITE_Gateway_Card|null $card
	 *
	 * @return bool
	 */
	public function set_card( ITE_Gateway_Card $card = null ) {

		if ( $card ) {
			return (bool) $this->update_meta( 'subscription_payment_card', array(
				'number' => $card->get_redacted_number(),
				'year'   => $card->get_expiration_year(),
				'month'  => $card->get_expiration_month(),
			) );
		}

		return (bool) $this->update_meta( 'subscription_payment_card', null );
	}

	/**
	 * Get the payment token used for the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Payment_Token|null
	 */
	public function get_payment_token() {

		if ( $this->meta_exists( 'subscription_payment_token' ) ) {

			$token_id = $this->get_meta( 'subscription_payment_token', true );

			if ( ! $token_id ) {
				return null;
			}

			return ITE_Payment_Token::get( $token_id );
		}

		return $this->get_transaction()->payment_token;
	}

	/**
	 * Set the payment token to use for this subscription.
	 *
	 * @since 1.9.0
	 *
	 * @param \ITE_Payment_Token|null $token
	 *
	 * @return bool
	 */
	public function set_payment_token( ITE_Payment_Token $token = null ) {

		/**
		 * Fires when the payment token is updated for a subscription.
		 *
		 * @since 1.9.0
		 *
		 * @param \ITE_Payment_Token|null   $new
		 * @param \ITE_Payment_Token|null   $old
		 * @param \IT_Exchange_Subscription $this
		 */
		do_action( 'it_exchange_update_subscription_payment_token', $token, $this->get_payment_token(), $this );

		return (bool) $this->update_meta( 'subscription_payment_token', $token ? $token->ID : 0 );
	}

	/**
	 * Get possible subscription statuses.
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			self::STATUS_ACTIVE               => __( 'Active', 'LION' ),
			self::STATUS_COMPLIMENTARY        => __( 'Complimentary', 'LION' ),
			self::STATUS_PAUSED               => __( 'Paused', 'LION' ),
			self::STATUS_PAYMENT_FAILED       => __( 'Payment Failed', 'LION' ),
			self::STATUS_DEACTIVATED          => __( 'Deactivated', 'LION' ),
			self::STATUS_CANCELLED            => __( 'Cancelled', 'LION' ),
			self::STATUS_PENDING_CANCELLATION => __( 'Pending Cancellation', 'LION' )
		);
	}
}