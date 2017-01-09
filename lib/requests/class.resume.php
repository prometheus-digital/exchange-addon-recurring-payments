<?php
/**
 * Resume Subscription Request.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_Resume_Subscription_Request
 */
class ITE_Resume_Subscription_Request implements ITE_Gateway_Request {

	/** @var IT_Exchange_Subscription */
	private $subscription;

	/** @var IT_Exchange_Customer|null */
	private $resumed_by;

	/**
	 * ITE_Resume_Subscription_Request constructor.
	 *
	 * @param IT_Exchange_Subscription  $subscription
	 * @param IT_Exchange_Customer|null $resumed_by
	 */
	public function __construct( IT_Exchange_Subscription $subscription, IT_Exchange_Customer $resumed_by = null ) {
		$this->subscription = $subscription;
		$this->resumed_by   = $resumed_by;
	}

	/**
	 * Get the subscription to be resumed.
	 *
	 * @since 2.0.0
	 *
	 * @return IT_Exchange_Subscription
	 */
	public function get_subscription() {
		return $this->subscription;
	}

	/**
	 * Get the person who resumed the subscription.
	 *
	 * @since 2.0.0
	 *
	 * @return IT_Exchange_Customer|null
	 */
	public function get_resumed_by() {
		return $this->resumed_by;
	}

	/**
	 * @inheritDoc
	 */
	public function get_customer() { return $this->subscription->get_customer(); }

	/**
	 * @inheritDoc
	 */
	public static function get_name() { return 'resume-subscription'; }
}