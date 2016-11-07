<?php
/**
 * Prorate Gateway Purchase Request.
 *
 * @since   1.9.0
 * @license GPLv2
 */

/**
 * Class ITE_Gateway_Prorate_Purchase_Request
 */
class ITE_Gateway_Prorate_Purchase_Request implements ITE_Gateway_Purchase_Request_Interface {

	/** @var ITE_Gateway_Purchase_Request_Interface */
	private $original;

	/**
	 * ITE_Gateway_Prorate_Purchase_Request constructor.
	 *
	 * @param ITE_Gateway_Purchase_Request_Interface $original
	 */
	public function __construct( ITE_Gateway_Purchase_Request_Interface $original ) { $this->original = $original; }

	/**
	 * Get prorate requests being used.
	 *
	 * @since 1.9.0
	 *
	 * @return ITE_Prorate_Credit_Request[]
	 */
	public function get_prorate_requests() {

		$cart = $this->get_cart();

		if ( ! $cart->has_meta( ITE_Prorate_Credit_Request::META ) ) {
			return array();
		}

		$requests = array();

		foreach ( $cart->get_meta( ITE_Prorate_Credit_Request::META ) as $product_id => $_ ) {
			$product = it_exchange_get_product( $product_id );

			if ( $request = ITE_Prorate_Credit_Request::get( $product, $cart ) ) {
				$requests[ $product_id ] = $request;
			}
		}

		return $requests;
	}

	/**
	 * @inheritDoc
	 */
	public function get_cart() { return $this->original->get_cart(); }

	/**
	 * @inheritDoc
	 */
	public function get_http_request() { return $this->original->get_http_request(); }

	/**
	 * @inheritDoc
	 */
	public function get_nonce() { return $this->original->get_nonce(); }

	/**
	 * @inheritDoc
	 */
	public function get_card() { return $this->original->get_card(); }

	/**
	 * @inheritDoc
	 */
	public function set_card( ITE_Gateway_Card $card ) { return $this->original->set_card( $card ); }

	/**
	 * @inheritDoc
	 */
	public function get_token() { return $this->original->get_token(); }

	/**
	 * @inheritDoc
	 */
	public function set_token( ITE_Payment_Token $token ) { return $this->original->set_token( $token ); }

	/**
	 * @inheritDoc
	 */
	public function get_tokenize() { return $this->original->get_tokenize(); }

	/**
	 * @inheritDoc
	 */
	public function set_tokenize( ITE_Gateway_Tokenize_Request $tokenize ) {
		return $this->original->set_tokenize( $tokenize );
	}

	/**
	 * @inheritDoc
	 */
	public function get_customer() { return $this->original->get_customer(); }

	/**
	 * @inheritDoc
	 */
	public static function get_name() { return 'purchase'; }
}