<?php
/**
 * Cancel Subscription Request.
 *
 * @since   1.9.0
 * @license GPLv2
 */

/**
 * Class ITE_Cancel_Subscription_Request
 */
class ITE_Cancel_Subscription_Request implements ITE_Gateway_Request {

	/** @var IT_Exchange_Subscription */
	private $subscription;

	/** @var string */
	private $reason = '';

	/** @var IT_Exchange_Customer|null $canceled_by */
	private $cancelled_by;

	/** @var bool */
	private $at_period_end = false;

	/**
	 * ITE_Cancel_Subscription_Request constructor.
	 *
	 * @param IT_Exchange_Subscription  $subscription
	 * @param string                    $reason
	 * @param IT_Exchange_Customer|null $cancelled_by
	 */
	public function __construct( IT_Exchange_Subscription $subscription, $reason = '', IT_Exchange_Customer $cancelled_by = null ) {
		$this->subscription = $subscription;
		$this->reason       = $reason;
		$this->cancelled_by = $cancelled_by;
	}

	/**
	 * Get the subscription to cancel.
	 *
	 * @since 1.36.0
	 *
	 * @return IT_Exchange_Subscription
	 */
	public function get_subscription() {
		return $this->subscription;
	}

	/**
	 * Get the user-provided reason the subscription was cancelled.
	 *
	 * @since 1.36.0
	 *
	 * @return string
	 */
	public function get_reason() {
		return $this->reason;
	}

	/**
	 * Get the person who initiated the cancellation request.
	 *
	 * @since 1.9.0
	 *
	 * @return IT_Exchange_Customer|null
	 */
	public function get_cancelled_by() {
		return $this->cancelled_by;
	}

	/**
	 * Should the subscription be cancelled at the end of the current period, or immediately.
	 *
	 * @since 1.9.0
	 *
	 * @return boolean
	 */
	public function is_at_period_end() {
		return $this->at_period_end;
	}

	/**
	 * Set whether the subscription should be cancelled at the period end.
	 *
	 * @since 1.9.0
	 *
	 * @param boolean $at_period_end
	 *
	 * @return $this
	 */
	public function set_at_period_end( $at_period_end ) {
		$this->at_period_end = (bool) $at_period_end;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function get_customer() { return $this->subscription->get_customer(); }

	/**
	 * @inheritDoc
	 */
	public static function get_name() { return 'cancel-subscription'; }
}
