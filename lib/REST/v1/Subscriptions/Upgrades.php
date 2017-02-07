<?php
/**
 * Prorate Upgrades.
 *
 * @since   2.0.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\v1\Subscriptions;

use iThemes\Exchange\REST\Getable;
use iThemes\Exchange\REST\Postable;
use iThemes\Exchange\REST\Request;
use iThemes\Exchange\REST\Route\Base;
use iThemes\Exchange\REST\Route\v1\Cart\Item;

class Upgrades extends Base implements Getable, Postable {

	/** @var ProrateSerializer */
	private $serializer;

	/** @var \ITE_Prorate_REST_Helper */
	private $helper;

	/**
	 * Downgrades constructor.
	 *
	 * @param ProrateSerializer       $serializer
	 * @param \ITE_Prorate_REST_Helper $helper
	 */
	public function __construct( ProrateSerializer $serializer, \ITE_Prorate_REST_Helper $helper ) {
		$this->serializer = $serializer;
		$this->helper     = $helper;
	}

	/**
	 * @inheritDoc
	 */
	public function handle_get( Request $request ) {
		return $this->helper->details( $request, 'upgrade' );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_get( Request $request, \IT_Exchange_Customer $user = null ) {
		return $this->helper->permissions( $request );
	}

	/**
	 * @inheritDoc
	 */
	public function handle_post( Request $request ) {
		return $this->helper->accept( $request, 'upgrade' );
	}

	/**
	 * @inheritDoc
	 */
	public function user_can_post( Request $request, \IT_Exchange_Customer $user = null ) {
		return true;
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