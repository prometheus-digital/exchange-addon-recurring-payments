<?php
/**
 * Forever prorate credit request.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class ITE_Prorate_Forever_Credit_Request
 */
class ITE_Prorate_Forever_Credit_Request extends ITE_Prorate_Credit_Request {

	/**
	 * @var IT_Exchange_Transaction
	 */
	protected $transaction;

	/**
	 * ITE_Prorate_Forever_Credit_Request constructor.
	 *
	 * @param IT_Exchange_Product     $providing
	 * @param IT_Exchange_Product     $receiving
	 * @param IT_Exchange_Transaction $transaction
	 */
	public function __construct( IT_Exchange_Product $providing, IT_Exchange_Product $receiving, IT_Exchange_Transaction $transaction ) {
		parent::__construct( $providing, $receiving, it_exchange_get_transaction_customer( $transaction ) );

		$this->transaction = $transaction;
	}

	/**
	 * Get the transaction that purchased the forever membership.
	 *
	 * @since 1.9
	 *
	 * @return IT_Exchange_Transaction
	 */
	public function get_transaction() {
		return $this->transaction;
	}
}