<?php
/**
 * Single Subscription Endpoint.
 *
 * @since   1.36.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\Subscriptions;

use iThemes\Exchange\REST\Getable;
use iThemes\Exchange\REST\Putable;
use iThemes\Exchange\REST\Request;
use iThemes\Exchange\REST\Route\Base;

/**
 * Class Subscription
 *
 * @package iThemes\Exchange\RecurringPayments\REST\Subscriptions
 */
class Subscription extends Base implements Getable, Putable {

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
	public function handle_get( Request $request ) {
		$subscription = $this->get_subscription_from_id( $request->get_param( 'subscription_id', 'URL' ) );

		return new \WP_REST_Response( $this->serializer->serialize( $subscription ) );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_get( Request $request, \IT_Exchange_Customer $user = null ) {

		if ( ! $user ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_context',
				__( 'Sorry, you are not allowed to access this subscription.', 'LION' ),
				array( 'status' => \WP_Http::UNAUTHORIZED )
			);
		}

		$sub_id       = $request->get_param( 'subscription_id', 'URL' );
		$subscription = $this->get_subscription_from_id( $sub_id );

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		$cap = $request['context'] === 'edit' ? 'edit_it_transaction' : 'read_it_transaction';

		if ( ! user_can( $user->wp_user, $cap, $subscription->get_transaction()->ID ) ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_context',
				__( 'Sorry, you are not allowed to access this subscription.', 'LION' ),
				array( 'status' => \WP_Http::FORBIDDEN )
			);
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function handle_put( Request $request ) {

		$sub = $this->get_subscription_from_id( $request->get_param( 'subscription_id', 'URL' ) );

		$status = is_array( $request['status'] ) ? $request['status']['slug'] : $request['status'];

		if ( $status && $status !== $sub->get_status() ) {
			$sub->set_status( $status );
		}

		$new_expires = new \DateTime( $request['expiry_date'], new \DateTimeZone( 'UTC' ) );
		$old_expires = $sub->get_expiry_date();

		if ( $new_expires && ( ! $old_expires || $new_expires->getTimestamp() !== $old_expires->getTimestamp() ) ) {
			$sub->set_expiry_date( $new_expires );
		}

		if ( $request['subscriber_id'] && $sub->is_auto_renewing() && $request['subscriber_id'] !== $sub->get_subscriber_id() ) {
			$sub->set_subscriber_id( $request['subscriber_id'] );
		}

		return new \WP_REST_Response( $this->serializer->serialize( $sub ) );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_put( Request $request, \IT_Exchange_Customer $user = null ) {

		if ( ! $user ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_context',
				__( 'Sorry, you are not allowed to access this subscription.', 'LION' ),
				array( 'status' => \WP_Http::UNAUTHORIZED )
			);
		}

		$sub_id       = $request->get_param( 'subscription_id', 'URL' );
		$subscription = $this->get_subscription_from_id( $sub_id );

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		if ( ! user_can( $user->wp_user, 'edit_it_transaction', $subscription->get_transaction()->ID ) ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_context',
				__( 'Sorry, you are not allowed to access this subscription.', 'LION' ),
				array( 'status' => \WP_Http::FORBIDDEN )
			);
		}

		return true;
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
	public function get_path() { return 'subscriptions/(?P<subscription_id>\d+\:\d+)/'; }

	/**
	 * @inheritDoc
	 */
	public function get_query_args() { return array(); }

	/**
	 * @inheritDoc
	 */
	public function get_schema() { return $this->serializer->get_schema(); }
}