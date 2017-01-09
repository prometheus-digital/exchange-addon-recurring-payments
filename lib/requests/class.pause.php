<?php
/**
 * Pause Subscription Request.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_Pause_Subscription_Request
 */
class ITE_Pause_Subscription_Request implements ITE_Gateway_Request {

	/** @var IT_Exchange_Subscription */
	private $subscription;

	/** @var IT_Exchange_Customer|null */
	private $paused_by;

	/**
	 * ITE_Pause_Subscription_Request constructor.
	 *
	 * @param IT_Exchange_Subscription  $subscription
	 * @param IT_Exchange_Customer|null $paused_by
	 */
	public function __construct( IT_Exchange_Subscription $subscription, IT_Exchange_Customer $paused_by = null ) {
		$this->subscription = $subscription;
		$this->paused_by    = $paused_by;
	}

	/**
	 * Get the subscription to be paused.
	 *
	 * @since 2.0.0
	 *
	 * @return IT_Exchange_Subscription
	 */
	public function get_subscription() {
		return $this->subscription;
	}

	/**
	 * Get the person who paused the subscription.
	 *
	 * @since 2.0.0
	 *
	 * @return IT_Exchange_Customer|null
	 */
	public function get_paused_by() {
		return $this->paused_by;
	}

	/**
	 * @inheritDoc
	 */
	public function get_customer() { return $this->subscription->get_customer(); }

	/**
	 * @inheritDoc
	 */
	public static function get_name() { return 'pause-subscription'; }
}