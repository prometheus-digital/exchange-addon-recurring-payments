<?php
/**
 * ProrateSerializer Serializer Endpoint.
 *
 * @since   1.36.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\Subscriptions;

/**
 * Class ProrateSerializer
 *
 * @package iThemes\Exchange\RecurringPayments\REST\Subscriptions
 */
class ProrateSerializer {

	/**
	 * Serialize a subscription.
	 *
	 * @since 1.36.0
	 *
	 * @param \ITE_Prorate_Credit_Request $request
	 *
	 * @return array
	 */
	public function serialize( \ITE_Prorate_Credit_Request $request ) {

		$r = $request;

		return array(
			'product'       => $r->get_product_receiving_credit()->ID,
			'title'         => $r->get_product_receiving_credit()->post_title,
			'description'   => $r->get_product_receiving_credit()->get_feature( 'description' ),
			'amount'        => $r->get_product_receiving_credit()->get_feature( 'base-price' ),
			'amount_label'  => it_exchange_recurring_payments_addon_recurring_label(
				$r->get_product_receiving_credit()->ID, false
			),
			'prorate'       => array(
				'type'   => $r->get_credit_type(),
				'amount' => $r->get_credit_type() === 'days' ? $r->get_free_days() : $r->get_credit()
			),
			'prorate_label' => $r->get_label(),
		);
	}

	/**
	 * Get the subscription schema.
	 *
	 * @since 1.36.0
	 *
	 * @return array
	 */
	public function get_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'prorate-request',
			'type'       => 'object',
			'properties' => array(
				'product'       => array(
					'description' => __( 'The product ID being prorated to.', 'LION' ),
					'type'        => 'integer',
					'required'    => true,
				),
				'title'         => array(
					'description' => __( 'The product title being prorated to.', 'LION' ),
					'type'        => 'string',
				),
				'description'   => array(
					'description' => __( 'The short description of the product being prorated to.', 'LION' ),
					'type'        => 'string',
				),
				'amount'        => array(
					'description' => __( 'The regular cost of the product being prorated to.', 'LION' ),
					'type'        => 'number',
				),
				'amount_label'  => array(
					'description' => __( 'A human readable label of the amount.', 'LION' ),
					'type'        => 'string',
				),
				'prorate'       => array(
					'description' => __( 'The amount being prorated.', 'LION' ),
					'type'        => 'object',
					'properties'  => array(
						'type'   => array(
							'description' => __( 'The prorate type.', 'LION' ),
							'type'        => 'string',
							'enum'        => array( 'days', 'credit' ),
						),
						'number' => array(
							'description' => __( 'The number of days or amount of credit.', 'LION' ),
							'type'        => 'integer'
						),
					),
				),
				'prorate_label' => array(
					'description' => __( 'A human readable label of the prorate total.', 'LION' ),
					'type'        => 'string',
				),
			)
		);
	}
}