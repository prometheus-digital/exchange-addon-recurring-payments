<?php
/**
 * Update Payment Method Request.
 *
 * @since   1.9.0
 * @license GPLv2
 */

/**
 * Class ITE_Update_Subscription_Payment_Method_Request
 */
class ITE_Update_Subscription_Payment_Method_Request implements ITE_Gateway_Request {

	/** @var IT_Exchange_Subscription */
	private $subscription;

	/** @var ITE_Payment_Token */
	private $payment_token;

	/** @var ITE_Gateway_Card */
	private $card;

	/** @var ITE_Gateway_Tokenize_Request */
	private $tokenize;

	/**
	 * ITE_Update_Subscription_Payment_Method_Request constructor.
	 *
	 * @param IT_Exchange_Subscription $subscription
	 */
	public function __construct( IT_Exchange_Subscription $subscription ) { $this->subscription = $subscription; }

	/**
	 * Get the subscription that should have its payment method updated.
	 *
	 * @since 1.9.0
	 *
	 * @return IT_Exchange_Subscription
	 */
	public function get_subscription() {
		return $this->subscription;
	}

	/**
	 * @inheritDoc
	 */
	public function get_customer() { return $this->subscription->get_customer(); }

	/**
	 * Get the new payment token to use.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Payment_Token|null
	 */
	public function get_payment_token() {
		return $this->payment_token;
	}

	/**
	 * Set the payment token to use.
	 *
	 * @since 1.9.0
	 *
	 * @param ITE_Payment_Token $payment_token
	 */
	public function set_payment_token( ITE_Payment_Token $payment_token ) {
		$this->payment_token = $payment_token;
	}

	/**
	 * Get the new card to use.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Gateway_Card|null
	 */
	public function get_card() {
		return $this->card;
	}

	/**
	 * Set the card to use.
	 *
	 * @since 1.9.0
	 *
	 * @param ITE_Gateway_Card $card
	 */
	public function set_card( $card ) {
		$this->card = $card;
	}

	/**
	 * Get the tokenize request.
	 *
	 * The resulting token should be used as the new payment method.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Gateway_Tokenize_Request|null
	 */
	public function get_tokenize() {
		return $this->tokenize;
	}

	/**
	 * Set the tokenize request.
	 *
	 * @since 1.9.0
	 *
	 * @param ITE_Gateway_Tokenize_Request $tokenize
	 */
	public function set_tokenize( ITE_Gateway_Tokenize_Request $tokenize ) {
		$this->tokenize = $tokenize;
	}

	/**
	 * @inheritDoc
	 */
	public static function get_name() { return 'update-subscription-payment-method'; }
}