<?php
/**
 * Route to comp a subscription.
 *
 * @since   2.0.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\v1\Subscriptions;

use iThemes\Exchange\REST\Auth\AuthScope;
use iThemes\Exchange\REST\Postable;
use iThemes\Exchange\REST\Request;
use iThemes\Exchange\REST\Route\Base;

/**
 * Class Comp
 *
 * @package iThemes\Exchange\RecurringPayments\REST\v1\Subscriptions
 */
class Comp extends Base implements Postable {

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

		$subscription = \IT_Exchange_Subscription::get( rawurldecode( $request->get_param( 'subscription_id', 'URL' ) ) );
		$reason       = $request['reason'];

		if ( it_exchange_get_customer( $request['comped_by'] ) ) {
			$comped_by = it_exchange_get_customer( $request['comped_by'] );
		} elseif ( current_user_can( 'edit_it_transaction', $subscription->get_transaction()->ID ) ) {
			$comped_by = null;
		} else {
			$comped_by = it_exchange_get_current_customer();
		}

		try {
			$subscription->comp( $comped_by, $reason );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'it_exchange_rest_unable_to_comp_subscription',
				__( 'Unable to comp subscription.', 'LION' ),
				array( 'status' => \WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		return new \WP_REST_Response( $this->serializer->serialize( $subscription ) );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_post( Request $request, AuthScope $scope ) {

		$subscription = \IT_Exchange_Subscription::get( rawurldecode( $request->get_param( 'subscription_id', 'URL' ) ) );

		if ( ! $scope->can( 'it_comp_subscription', $subscription ) ) {
			return new \WP_Error(
				'it_exchange_rest_forbidden',
				__( 'You are not allowed to comp this subscription.', 'LION' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$associated = array();

		if ( $subscription->get_customer() ) {
			$associated[] = $subscription->get_customer()->id;
		}

		if ( $subscription->get_beneficiary() ) {
			$associated[] = $subscription->get_beneficiary()->id;
		}

		if ( $request['comped_by'] ) {
			foreach ( $associated as $user_id ) {
				if ( ! $scope->can( 'edit_user', $user_id ) ) {
					return new \WP_Error(
						'it_exchange_rest_forbidden_context',
						__( 'You are not allowed to specify who comped the subscription besides yourself.', 'LION' ),
						array( 'status' => 403 )
					);
				}
			}
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
	public function get_path() { return 'comp/'; }

	/**
	 * @inheritDoc
	 */
	public function get_query_args() { return array(); }

	/**
	 * @inheritDoc
	 */
	public function get_schema() { return $this->serializer->get_schema(); }
}