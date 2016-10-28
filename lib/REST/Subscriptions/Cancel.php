<?php
/**
 * Subscription Cancel Endpoint.
 *
 * @since   1.36.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\Subscriptions;

use iThemes\Exchange\REST\Postable;
use iThemes\Exchange\REST\Request;
use iThemes\Exchange\REST\Route\Base;

/**
 * Class Cancel
 *
 * @package iThemes\Exchange\RecurringPayments\REST\Subscriptions
 */
class Cancel extends Base implements Postable {

	/** @var Serializer */
	private $serializer;

	/**
	 * Subscription constructor.
	 *
	 * @param Serializer $serializer
	 */
	public function __construct( Serializer $serializer ) { $this->serializer = $serializer; }

	/**
	 * @inheritDoc
	 */
	public function handle_post( Request $request ) {

		$subscription = $this->get_subscription_from_id( $request->get_param( 'subscription_id', 'URL' ) );

		try {
			$subscription->cancel();
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'it_exchange_rest_unable_to_cancel_subscription',
				__( 'Unable to cancel subscription.', 'LION' ),
				array( 'status' => \WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		return new \WP_REST_Response( $this->serializer->serialize( $subscription ) );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_post( Request $request, \IT_Exchange_Customer $user = null ) {
		return true; // Cascades to read transaction. Customers can cancel their own subscriptions.
	}

	/**
	 * Get a subscription from an "ID".
	 *
	 * @since 1.36.0
	 *
	 * @param string $id
	 *
	 * @return \IT_Exchange_Subscription|\WP_Error
	 */
	protected function get_subscription_from_id( $id ) {
		$parts = explode( ':', $id );

		if ( count( $parts ) !== 2 ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_subscription',
				__( 'Invalid subscription id.', 'LION' ),
				array( 'status' => \WP_Http::BAD_REQUEST )
			);
		}

		$transaction = it_exchange_get_transaction( $parts[0] );
		$product     = it_exchange_get_product( $parts[1] );

		if ( ! $transaction || ! $product ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_subscription',
				__( 'Invalid subscription id.', 'LION' ),
				array( 'status' => \WP_Http::BAD_REQUEST )
			);
		}

		try {
			$subscription = \IT_Exchange_Subscription::from_transaction( $transaction, $product );
		} catch ( \InvalidArgumentException $e ) {

		}

		if ( ! isset( $subscription ) ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_subscription',
				__( 'Invalid subscription id.', 'LION' ),
				array( 'status' => \WP_Http::BAD_REQUEST )
			);
		}

		return $subscription;
	}

	/**
	 * @inheritDoc
	 */
	public function get_version() { return 1; }

	/**
	 * @inheritDoc
	 */
	public function get_path() { return 'cancel/'; }

	/**
	 * @inheritDoc
	 */
	public function get_query_args() { return array(); }

	/**
	 * @inheritDoc
	 */
	public function get_schema() { return $this->serializer->get_schema(); }
}