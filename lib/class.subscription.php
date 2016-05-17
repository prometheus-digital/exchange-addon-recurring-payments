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
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_DEACTIVATED = 'deactivated';
	const STATUS_COMPLIMENTARY = 'complimentary';

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

	/**
	 * IT_Exchange_Subscription constructor.
	 *
	 * @param IT_Exchange_Transaction $transaction
	 * @param IT_Exchange_Product     $product
	 */
	private function __construct( IT_Exchange_Transaction $transaction, IT_Exchange_Product $product ) {
		$this->transaction = $transaction;
		$this->product     = $product;

		if ( ! $transaction->meta_exists( 'interval_' . $product->ID ) ) {

			if ( ! $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				throw new InvalidArgumentException();
			}

			$interval = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) );
			$transaction->update_meta( 'interval_' . $product->ID, $interval );
		}

		if ( ! $transaction->meta_exists( 'interval_count_' . $product->ID ) ) {
			$interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) );
			$transaction->update_meta( 'interval_count_' . $product->ID, $interval_count );
		}

		if ( ! $transaction->meta_exists( 'has_trial_' . $product->ID ) ) {
			$has_trial = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
			$transaction->update_meta( 'has_trial_' . $product->ID, $has_trial );
		}

		$type  = $transaction->get_meta( 'interval_' . $product->ID );
		$count = $transaction->get_meta( 'interval_count_' . $product->ID );

		if ( $count > 0 ) {
			$this->recurring_profile = new IT_Exchange_Recurring_Profile( $type, $count );

			if ( $transaction->get_meta( 'has_trial_' . $product->ID ) ) {

				if ( ! $transaction->meta_exists( 'trial_interval_' . $product->ID ) ) {
					$trial_interval = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval' ) );
					$transaction->update_meta( 'trial_interval_' . $product->ID, $trial_interval );
				}

				if ( ! $transaction->meta_exists( 'trial_interval_count_' . $product->ID ) ) {
					$trial_interval_count = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );
					$transaction->update_meta( 'trial_interval_count_' . $product->ID, $trial_interval_count );
				}

				$type  = $transaction->get_meta( 'trial_interval_' . $product->ID );
				$count = $transaction->get_meta( 'trial_interval_count_' . $product->ID );

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
			$customer = it_exchange_get_transaction_customer( $transaction );
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

		if ( ! $this->get_transaction()->meta_exists( 'subscription_autorenew_' . $this->get_product()->ID ) ) {

			$auto_renew = $this->get_product()->get_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) ) === 'on';

			$this->get_transaction()->update_meta( 'subscription_autorenew_' . $this->get_product()->ID, $auto_renew );
		}

		return (bool) $this->get_transaction()->get_meta( 'subscription_autorenew_' . $this->get_product()->ID );
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

		$expires = $this->get_transaction()->get_meta( 'subscription_expired_' . $this->get_product()->ID );

		if ( ! $expires ) {
			$expires = $this->get_transaction()->get_meta( 'subscription_expires_' . $this->get_product()->ID );
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
	 * @param DateTime $date
	 */
	public function set_expiry_date( DateTime $date ) {

		$previous = $this->get_expiry_date();
		$now      = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		if ( $now < $date ) {
			$this->get_transaction()->update_meta( 'subscription_expires_' . $this->get_product()->ID, $date->format( 'U' ) );
			$this->get_transaction()->delete_meta( 'subscription_expired_' . $this->get_product()->ID );
		} else {
			$this->get_transaction()->delete_meta( 'subscription_expires_' . $this->get_product()->ID );
			$this->get_transaction()->update_meta( 'subscription_expired_' . $this->get_product()->ID, $date->format( 'U' ) );
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

		$status = $this->get_transaction()->get_meta( 'subscriber_status_' . $this->get_product()->ID );

		if ( empty( $status ) ) {
			$status = $this->get_transaction()->get_meta( 'subscriber_status' );

			if ( $status ) {
				$this->get_transaction()->update_meta( 'subscriber_status_' . $this->get_product()->ID, $status );
			}
		}

		if ( $label ) {
			$labels = self::get_statuses();

			return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
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

		$old_status = $this->get_status();

		if ( $new_status === $old_status ) {
			throw new InvalidArgumentException( '$new_status === $old_status' );
		}

		$this->get_transaction()->update_meta( 'subscriber_status_' . $this->get_product()->ID, $new_status );
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
			$this->cancel();
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

		$this->transaction->delete_meta( 'subscription_expires_' . $this->get_product()->ID );

		if ( $expires ) {
			$this->transaction->update_meta( 'subscription_expired_' . $this->get_product()->ID, $expires->format( 'U' ) );
		}
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
	 * @inheritDoc
	 */
	public static function handle_prorate_credit_request( ITE_Prorate_Credit_Request $request, ITE_Daily_Price_Calculator $calculator ) {

		if ( ! self::accepts_prorate_credit_request( $request ) ) {
			throw new DomainException( "This credit request can't be handled by this provider." );
		}

		/** @var IT_Exchange_Subscription $sub */
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
			'old_transaction_id'     => $sub->get_transaction()->ID,
			'old_transaction_method' => $sub->get_transaction()->transaction_method,
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
		) );

		if ( $children ) {
			$transaction = $children[0];
		} else {
			$transaction = $this->get_transaction();
		}

		// auto-renewing subscriptions only can be purchased individually
		if ( $this->is_auto_renewing() ) {
			$amount_paid = $transaction->get_total( false );
		} else {
			foreach ( $transaction->get_products() as $product ) {
				if ( $product['product_id'] == $this->get_product()->ID ) {
					$amount_paid = $product['product_subtotal'];
				}
			}

			if ( ! isset( $amount_paid ) ) {
				throw new UnexpectedValueException( 'Could not determine the amount paid for the subscription.' );
			}

			if ( $transaction->get_total( false ) < $amount_paid ) {
				$amount_paid = $transaction->get_total( false );
			}
		}

		return (float) $amount_paid;
	}

	/**
	 * Cancel this subscription.
	 *
	 * @since 1.8
	 */
	public function cancel() {

		$method = $this->get_transaction()->transaction_method;

		do_action( "it_exchange_cancel_{$method}_subscription", array(
			'old_subscriber_id' => $this->get_subscriber_id(),
			'customer'          => $this->get_customer(),
			'subscription'      => $this
		) );
	}

	/**
	 * Record a gateway cancellation while the subscription is complimentary.
	 *
	 * @since 1.8.4
	 *
	 * @param string $gateway
	 */
	public function record_gateway_cancellation_while_complimentary( $gateway ) {

		$builder = new IT_Exchange_Txn_Activity_Builder( $this->get_transaction(), 'status' );
		$builder->set_description( "Original recurring payment has been cancelled." );
		$builder->set_actor( new IT_Exchange_Txn_Activity_Gateway_Actor( it_exchange_get_addon( $gateway ) ) );
		$builder->build( it_exchange_get_txn_activity_factory() );
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
			self::STATUS_ACTIVE        => __( 'Active', 'LION' ),
			self::STATUS_COMPLIMENTARY => __( 'Complimentary', 'LION' ),
			self::STATUS_SUSPENDED     => __( 'Suspended', 'LION' ),
			self::STATUS_DEACTIVATED   => __( 'Deactivated', 'LION' ),
			self::STATUS_CANCELLED     => __( 'Cancelled', 'LION' )
		);
	}
}
