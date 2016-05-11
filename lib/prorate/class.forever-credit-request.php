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

		$this->update_additional_session_details( array(
			'_txn'   => $transaction->ID,
			'_class' => get_class()
		) );
	}

	/**
	 * Helper method for reconstructing the credit request from the session.
	 *
	 * @since 2.0
	 *
	 * @param IT_Exchange_Product $receiving_product
	 * @param array               $session
	 *
	 * @return ITE_Prorate_Forever_Credit_Request|null
	 */
	protected static function _get( IT_Exchange_Product $receiving_product, $session ) {

		if ( ! isset( $session['_txn'], $session['_prod'] ) ) {
			return null;
		}

		return new self(
			it_exchange_get_product( $session['_prod'] ),
			$receiving_product,
			it_exchange_get_transaction( $session['_txn'] )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function is_provider_auto_renewing() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function is_provider_recurring() {
		return false;
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