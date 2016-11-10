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
		$subscription = \IT_Exchange_Subscription::get( $request->get_param( 'subscription_id', 'URL' ) );

		$response = new \WP_REST_Response( $this->serializer->serialize( $subscription ) );

		if ( $subscription->get_transaction() ) {
			$route = $this->get_manager()->get_first_route( 'iThemes\Exchange\REST\Route\Transaction\Transaction' );
			$link  = \iThemes\Exchange\REST\get_rest_url( $route, array(
				'transaction_id' => $subscription->get_transaction()->ID,
			) );
			$response->add_link( 'transaction', $link, array( 'embeddable' => true ) );
		}


		if ( $subscription->get_payment_token() ) {
			$route = $this->get_manager()->get_first_route( 'iThemes\Exchange\REST\Route\Customer\Token\Token' );
			$link  = \iThemes\Exchange\REST\get_rest_url( $route, array(
				'customer_id' => $subscription->get_payment_token()->customer->ID,
				'token_id'    => $subscription->get_payment_token()->ID
			) );
			$response->add_link( 'token', $link, array( 'embeddable' => true ) );
		}

		return $response;
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

		$subscription = \IT_Exchange_Subscription::get( $request->get_param( 'subscription_id', 'URL' ) );

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

		$s = \IT_Exchange_Subscription::get( $request->get_param( 'subscription_id', 'URL' ) );

		if ( $request['payment_token'] && $s->get_payment_token() && $s->get_payment_token()->ID != $request['payment_token'] ) {
			$payment_token = \ITE_Payment_Token::get( $request['payment_token'] );

			if ( ! $payment_token || $payment_token->gateway->get_slug() !== $s->get_payment_token()->gateway->get_slug() ) {
				return new \WP_Error(
					'it_exchange_rest_invalid_payment_token',
					__( 'The payment token is invalid.', 'LION' ),
					array( 'status' => \WP_Http::BAD_REQUEST )
				);
			}

			$s->set_payment_token( $payment_token );
		}

		$status = is_array( $request['status'] ) ? $request['status']['slug'] : $request['status'];

		if ( $status && $status !== $s->get_status() ) {
			$s->set_status( $status );
		}

		$new_expires = new \DateTime( $request['expiry_date'], new \DateTimeZone( 'UTC' ) );
		$old_expires = $s->get_expiry_date();

		if ( $new_expires && ( ! $old_expires || $new_expires->getTimestamp() !== $old_expires->getTimestamp() ) ) {
			$s->set_expiry_date( $new_expires );
		}

		if ( $request['subscriber_id'] && $s->is_auto_renewing() && $request['subscriber_id'] !== $s->get_subscriber_id() ) {
			$s->set_subscriber_id( $request['subscriber_id'] );
		}

		return new \WP_REST_Response( $this->serializer->serialize( $s ) );
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
		$subscription = \IT_Exchange_Subscription::get( $sub_id );

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