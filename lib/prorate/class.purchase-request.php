<?php
/**
 * Prorate Gateway Purchase Request.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_Gateway_Prorate_Purchase_Request
 */
class ITE_Gateway_Prorate_Purchase_Request extends ITE_Gateway_Purchase_Request {

	/**
	 * ITE_Gateway_Prorate_Purchase_Request constructor.
	 *
	 * @param ITE_Gateway_Purchase_Request $original
	 */
	public function __construct( ITE_Gateway_Purchase_Request $original ) {
		parent::__construct( $original->get_cart(), $original->get_nonce(), $original->get_http_request() );

		foreach ( get_object_vars( $original ) as $var => $val ) {
			$this->$var = $val;
		}
	}

	/**
	 * Get prorate requests being used.
	 *
	 * @since 2.0.0
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
}