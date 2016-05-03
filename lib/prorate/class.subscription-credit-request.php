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
}