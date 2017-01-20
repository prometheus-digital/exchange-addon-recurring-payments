<?php
/**
 * Prorate Helper class.
 *
 * @since   2.0.0
 * @license GPLv2
 */

use iThemes\Exchange\RecurringPayments\REST\Subscriptions\ProrateSerializer;
use iThemes\Exchange\REST\Manager;
use iThemes\Exchange\REST\Request;

/**
 * Class ITE_Prorate_REST_Helper
 */
class ITE_Prorate_REST_Helper {

	const UPGRADE = 'upgrade';
	const DOWNGRADE = 'downgrade';

	/** @var \ITE_Object_Type */
	private $object_type;

	/** @var \ITE_Prorate_Credit_Requestor */
	private $requestor;

	/** @var Manager */
	private $manager;

	/** @var ProrateSerializer */
	private $serializer;

	/** @var string */
	private $param;

	/**
	 * ProrateHelper constructor.
	 *
	 * @param \ITE_Object_Type              $object_type
	 * @param \ITE_Prorate_Credit_Requestor $requestor
	 * @param Manager                       $manager
	 * @param ProrateSerializer             $serializer
	 * @param string                        $param REST URL parameter that contains the object ID.
	 */
	public function __construct(
		\ITE_Object_Type $object_type,
		\ITE_Prorate_Credit_Requestor $requestor,
		Manager $manager,
		ProrateSerializer $serializer,
		$param
	) {
		$this->object_type = $object_type;
		$this->requestor   = $requestor;
		$this->manager     = $manager;
		$this->serializer  = $serializer;
		$this->param       = $param;
	}

	/**
	 * Handle the user_can_get request.
	 *
	 * @since 2.0.0
	 *
	 * @param Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function permissions( Request $request ) {

		$object = $this->object_type->get_object_by_id(
			rawurldecode( $request->get_param( $this->param, 'URL' ) )
		);

		if ( ! $object instanceof \ITE_Proratable ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_prorate_request',
				sprintf( __( '%s is not eligible for prorating.', 'LION' ), $object->__toString() ),
				array( 'status' => \WP_Http::BAD_REQUEST )
			);
		}

		return true; // Cascades to accessing a membership or subscription
	}

	/**
	 * List the available upgrades/downgrades available to a proratable object.
	 *
	 * @since 2.0.0
	 *
	 * @param Request $request
	 * @param string  $type Either 'upgrade', or 'downgrade'.
	 *
	 * @return \WP_REST_Response
	 */
	public function details( Request $request, $type ) {

		/** @var ITE_Proratable $object */
		$object = $this->object_type->get_object_by_id(
			rawurldecode( $request->get_param( $this->param, 'URL' ) )
		);

		/** @var \ITE_Prorate_Credit_Request[] $options */
		$options = $object->{"get_available_{$type}s"}();
		$data    = array();

		foreach ( $options as $option ) {
			if ( $this->requestor->{"request_{$type}"}( $option, false ) ) {
				$data[] = $this->serializer->serialize( $option );
			}
		}

		return new \WP_REST_Response( $data );
	}

	/**
	 * Accept either an upgrade/downgrade request.
	 *
	 * @since 2.0.0
	 *
	 * @param Request $request
	 * @param string  $type Either 'upgrade' or 'downgrade'.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function accept( Request $request, $type ) {

		/** @var ITE_Proratable $object */
		$object = $this->object_type->get_object_by_id(
			rawurldecode( $request->get_param( $this->param, 'URL' ) )
		);

		if ( ! $object instanceof \ITE_Proratable ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_prorate_request',
				__( 'Invalid prorate request.', 'LION' ),
				array( 'status' => \WP_Http::BAD_REQUEST )
			);
		}

		$product_id = $request['product'];

		/** @var \ITE_Prorate_Credit_Request[] $all_available */
		$all_available   = $object->{"get_available_{$type}s"}();
		$prorate_request = null;
		$found           = false;

		foreach ( $all_available as $available ) {

			if ( $available->get_product_receiving_credit()->ID == $product_id ) {
				$prorate_request = $available;
				$found           = true;
				break;
			}
		}

		if ( ! $found ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_prorate_request',
				__( 'Invalid prorate request. That product cannot be switched to.', 'LION' ),
				array( 'status' => \WP_Http::BAD_REQUEST )
			);
		}

		if ( $request['cart_id'] ) {
			$cart = it_exchange_get_cart( $request['cart_id'] );

			if ( ! $cart ) {
				return new \WP_Error(
					'it_exchange_rest_cart_not_found',
					__( 'Unable to retrieve a cart with the given id.', 'LION' ),
					array( 'status' => \WP_Http::BAD_REQUEST )
				);
			}
		} else {
			$cart = it_exchange_create_cart_and_session(
				it_exchange_get_current_customer(),
				false,
				new \DateTime( '+ 1 hour', new \DateTimeZone( 'UTC' ) )
			);
		}

		if ( ! $item = $cart->get_items( 'product' )->filter( function ( \ITE_Cart_Product $cart_product ) use ( $product_id ) {
			return $cart_product->get_product()->ID == $product_id;
		} )->first()
		) {
			$item = \ITE_Cart_Product::create( it_exchange_get_product( $product_id ) );
			$cart->add_item( $item );
		}

		$prorate_request->set_cart( $cart );

		if ( ! $this->requestor->{"request_{$type}"}( $prorate_request ) ) {
			return new \WP_Error(
				'it_exchange_rest_unable_to_prorate',
				__( 'Unable to complete the prorate request.', 'LION' ),
				array( 'status' => \WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		$cart_route = $this->manager->get_first_route( 'iThemes\Exchange\REST\Route\Cart\Cart' );
		$url        = \iThemes\Exchange\REST\get_rest_url( $cart_route, array(
			'cart_id' => $cart->get_id()
		) );

		if ( $request['_embed'] ) {
			$url = add_query_arg( '_embed', '1', $url );
		}

		$response = new \WP_REST_Response( null, \WP_Http::SEE_OTHER );
		$response->header( 'Location', $url );
		$response->header( 'X-Item-Id', $item->get_id() );

		return $response;
	}
}