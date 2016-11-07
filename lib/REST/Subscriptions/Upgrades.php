<?php
/**
 * Prorate Upgrades.
 *
 * @since   1.9.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\Subscriptions;

use iThemes\Exchange\REST\Getable;
use iThemes\Exchange\REST\Request;
use iThemes\Exchange\REST\Route\Base;

class Upgrades extends Base implements Getable {

	/** @var ProrateSerializer */
	private $serializer;

	/** @var \ITE_Prorate_Credit_Requestor */
	private $requestor;

	/**
	 * Upgrades constructor.
	 *
	 * @param ProrateSerializer             $serializer
	 * @param \ITE_Prorate_Credit_Requestor $requestor
	 */
	public function __construct( ProrateSerializer $serializer, \ITE_Prorate_Credit_Requestor $requestor ) {
		$this->serializer = $serializer;
		$this->requestor  = $requestor;
	}

	/**
	 * @inheritDoc
	 */
	public function handle_get( Request $request ) {

		$subscription = it_exchange_get_subscription_from_rest_id( $request->get_param( 'subscription_id', 'URL' ) );

		$options = $subscription->get_available_upgrades();
		$data    = array();

		foreach ( $options as $option ) {
			if ( $this->requestor->request_upgrade( $option, false ) ) {
				$data[] = $this->serializer->serialize( $option );
			}
		}

		return new \WP_REST_Response( $data );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_get( Request $request, \IT_Exchange_Customer $user = null ) {
		return true; // Cascades to accessing a subscription
	}

	/**
	 * @inheritDoc
	 */
	public function get_version() { return 1; }

	/**
	 * @inheritDoc
	 */
	public function get_path() { return 'upgrades/'; }

	/**
	 * @inheritDoc
	 */
	public function get_query_args() { return array(); }

	/**
	 * @inheritDoc
	 */
	public function get_schema() { return $this->serializer->get_schema(); }
}