<?php
/**
 * Renewal Endpoint.
 *
 * @since   2.0.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\Subscriptions;

use iThemes\Exchange\REST\Postable;
use iThemes\Exchange\REST\Request;
use iThemes\Exchange\REST\Route\Base;
use iThemes\Exchange\REST\Route\Cart\Item;

class Renew extends Base implements Postable {
	/**
	 * @inheritDoc
	 */
	public function handle_post( Request $request ) {

		$subscription = \IT_Exchange_Subscription::get( rawurldecode( $request->get_param( 'subscription_id', 'URL' ) ) );

		if ( ! $subscription->can_be_manually_renewed() ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_request',
				__( 'This subscription cannot be manually renewed.', 'LION' ),
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

		$product = $subscription->get_product();

		/** @var \ITE_Cart_Product $original_item */
		$original_item = $subscription->get_transaction()->get_items( 'product' )->filter( function ( \ITE_Cart_Product $item ) use ( $product ) {
			return $product->get_ID() === $item->get_product()->get_ID();
		} )->first();

		if ( ! $original_item ) {
			return new \WP_Error(
				'it_exchange_rest_item_not_found',
				__( 'Unable to find original subscription item.', 'LION' ),
				array( 'status' => \WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		$item = $original_item->clone_with_new_id( false );

		if ( ! $subscription->is_auto_renewing() ) {
			$item->set_param( 'is_manual_renewal', $subscription->get_id() );
		}

		$cart->add_item( $item );

		if ( ! $subscription->is_auto_renewing() ) {
			it_exchange_recurring_payments_add_credit_fees( $item, $cart );
		}

		/** @var Item $route */
		foreach ( $this->get_manager()->get_routes_by_class( 'iThemes\Exchange\REST\Route\Cart\Item' ) as $route ) {
			if ( $route->get_type()->get_type() === 'product' ) {

				$response = new \WP_REST_Response( null, \WP_Http::SEE_OTHER );
				$url      = \iThemes\Exchange\REST\get_rest_url( $route, array(
					'cart_id' => $cart->get_id(),
					'item_id' => $item->get_id()
				) );
				$response->header( 'Location', $url );

				return $response;
			}
		}

		return new \WP_REST_Response( null, \WP_Http::NO_CONTENT );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_post( Request $request, \IT_Exchange_Customer $user = null ) {
		return true; // Cascades up
	}

	/**
	 * @inheritDoc
	 */
	public function get_version() { return 1; }

	/**
	 * @inheritDoc
	 */
	public function get_path() { return 'renew/'; }

	/**
	 * @inheritDoc
	 */
	public function get_query_args() { return array(); }

	/**
	 * @inheritDoc
	 */
	public function get_schema() {
		return array();
	}
}