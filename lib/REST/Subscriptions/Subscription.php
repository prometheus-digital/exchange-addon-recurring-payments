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

	/** @var \ITE_Gateway_Request_Factory */
	private $request_factory;

	/**
	 * Subscription constructor.
	 *
	 * @param Serializer                   $serializer
	 * @param \ITE_Gateway_Request_Factory $request_factory
	 */
	public function __construct( Serializer $serializer, \ITE_Gateway_Request_Factory $request_factory ) {
		$this->serializer      = $serializer;
		$this->request_factory = $request_factory;
	}

	/**
	 * @inheritDoc
	 */
	public function handle_get( Request $request ) {

		$subscription = \IT_Exchange_Subscription::get( rawurldecode( $request->get_param( 'subscription_id', 'URL' ) ) );
		$response     = new \WP_REST_Response( $this->serializer->serialize( $subscription ) );

		if ( $subscription->get_customer() ) {
			$response->add_link(
				'customer',
				\iThemes\Exchange\REST\get_rest_url(
					$this->get_manager()->get_first_route( 'iThemes\Exchange\REST\Route\Customer\Customer' ),
					array( 'customer_id' => $subscription->get_customer()->get_ID() )
				),
				array( 'embeddable' => true )
			);
		}

		if ( $subscription->get_beneficiary() ) {
			$response->add_link(
				'beneficiary',
				\iThemes\Exchange\REST\get_rest_url(
					$this->get_manager()->get_first_route( 'iThemes\Exchange\REST\Route\Customer\Customer' ),
					array( 'customer_id' => $subscription->get_beneficiary()->get_ID() )
				),
				array( 'embeddable' => true )
			);
		}

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

		try {
			$subscription = \IT_Exchange_Subscription::get( rawurldecode( $request->get_param( 'subscription_id', 'URL' ) ) );
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_subscription',
				__( 'Invalid subscription id.', 'LION' ),
				array( 'status' => 400 )
			);
		}

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

		$s = \IT_Exchange_Subscription::get( rawurldecode( $request->get_param( 'subscription_id', 'URL' ) ) );

		$updating_source = $this->updating_payment_source( $request, $s );

		if ( $updating_source ) {

			$gateway = $s->get_transaction()->get_gateway();

			try {
				$update_request = $this->request_factory->make( 'update-subscription-payment-method', $updating_source );
			} catch ( \InvalidArgumentException $e ) {
				return new \WP_Error(
					'it_exchange_rest_invalid_param',
					$e->getMessage(),
					array( 'status' => \WP_Http::BAD_REQUEST )
				);
			}

			if ( ! $gateway->get_handler_for( $update_request )->handle( $update_request ) ) {
				return new \WP_Error(
					'it_exchange_rest_cannot_update_subscription_payment_source',
					__( "Unable to update the subscription's payment source.", 'LION' ),
					array( 'status' => 400 )
				);
			}
		}

		if ( current_user_can( 'edit_it_transaction', $s->get_transaction()->get_ID() ) ) {
			$status = is_array( $request['status'] ) ? $request['status']['slug'] : $request['status'];

			if ( $status && $status !== $s->get_status() ) {
				$s->set_status( $status );
			}

			$new_expires = new \DateTime( $request['expiry_date'], new \DateTimeZone( 'UTC' ) );
			$this->handle_expiry_update( $s, $new_expires );

			if ( $request['subscriber_id'] && $s->is_auto_renewing() && $request['subscriber_id'] !== $s->get_subscriber_id() ) {
				$s->set_subscriber_id( $request['subscriber_id'] );
			}
		}

		return new \WP_REST_Response( $this->serializer->serialize( $s ) );
	}

	/**
	 * Handle updating the expiration date.
	 *
	 * @since 1.9.0
	 *
	 * @param \IT_Exchange_Subscription $s
	 * @param \DateTime                 $new
	 *
	 * @return null
	 */
	protected function handle_expiry_update( \IT_Exchange_Subscription $s, \DateTime $new ) {

		$old = $s->get_expiry_date();

		if ( ! $old ) {
			return $s->set_expiry_date( $new );
		}

		$hours   = (int) $new->format( 'H' );
		$minutes = (int) $new->format( 'i' );
		$seconds = (int) $new->format( 's' );

		if ( ! $hours && ! $minutes && ! $seconds ) {
			$hours   = (int) $old->format( 'H' );
			$minutes = (int) $old->format( 'i' );
			$seconds = (int) $old->format( 's' );

			$new = new \DateTime( "{$new->format( 'Y-m-d')} $hours:$minutes:$seconds", new \DateTimeZone( 'UTC' ) );
		}

		if ( $new->getTimestamp() !== $old->getTimestamp() ) {
			return $s->set_expiry_date( $new );
		}

		return null;
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

		try {
			$subscription = \IT_Exchange_Subscription::get( rawurldecode( $request->get_param( 'subscription_id', 'URL' ) ) );
		} catch ( \InvalidArgumentException $e ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_subscription',
				__( 'Invalid subscription id.', 'LION' ),
				array( 'status' => 400 )
			);
		}

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		$updating_source = $this->updating_payment_source( $request, $subscription );

		if ( is_wp_error( $updating_source ) ) {
			return $updating_source;
		} elseif ( $updating_source ) {

			if ( ! user_can( $user->wp_user, 'read_it_transaction', $subscription->get_transaction()->ID ) ) {
				return new \WP_Error(
					'it_exchange_rest_invalid_context',
					__( 'Sorry, you are not allowed to access this subscription.', 'LION' ),
					array( 'status' => \WP_Http::FORBIDDEN )
				);
			}

			if ( isset( $updating_source['token'] ) && ! user_can( $user->wp_user, 'it_use_payment_token', $updating_source['token'] ) ) {
				return new \WP_Error(
					'it_exchange_rest_invalid_payment_token',
					__( 'Sorry, you are not allowed to use this payment token.', 'LION' ),
					array( 'status' => \WP_Http::UNAUTHORIZED )
				);
			}
		} elseif ( ! user_can( $user->wp_user, 'edit_it_transaction', $subscription->get_transaction()->ID ) ) {
			return new \WP_Error(
				'it_exchange_rest_invalid_context',
				__( 'Sorry, you are not allowed to access this subscription.', 'LION' ),
				array( 'status' => \WP_Http::FORBIDDEN )
			);
		}


		return true;
	}

	/**
	 * Are we updating the payment source for the subscription.
	 *
	 * @since 1.9.0
	 *
	 * @param Request                   $request
	 * @param \IT_Exchange_Subscription $s
	 *
	 * @return array|\WP_Error|false
	 */
	protected function updating_payment_source( Request $request, \IT_Exchange_Subscription $s ) {

		$token = isset( $request['payment_method']['token'] ) ? (int) $request['payment_method']['token'] : 0;
		$card  = isset( $request['payment_method']['card'] ) ? $request['payment_method']['card'] : array();

		$update_payment_method_args = array( 'subscription' => $s );

		if ( $token && $s->get_payment_token() && $token !== $s->get_payment_token()->get_ID() ) {
			$update_payment_method_args['token'] = $token;
		} elseif (
			is_array( $card ) &&
			isset( $card['number'] ) &&
			strlen( $card['number'] > 4 ) &&
			strpos( strtolower( $card['number'] ), 'x' ) === false &&
			$s->get_card()
		) {
			$update_payment_method_args['card'] = $card;
		}

		if ( count( $update_payment_method_args ) > 1 ) {
			if ( ! $s->can_payment_source_be_updated() ) {
				return new \WP_Error(
					'it_exchange_rest_cannot_update_subscription_payment_source',
					__( 'Sorry, this subscription cannot have its payment information updated.', 'LION' ),
					array( 'status' => \WP_Http::BAD_REQUEST )
				);
			}

			return $update_payment_method_args;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function get_version() { return 1; }

	/**
	 * @inheritDoc
	 */
	public function get_path() { return 'subscriptions/(?P<subscription_id>\d+(?:\:|\%3A)\d+)/'; }

	/**
	 * @inheritDoc
	 */
	public function get_query_args() { return array(); }

	/**
	 * @inheritDoc
	 */
	public function get_schema() { return $this->serializer->get_schema(); }
}