<?php
/**
 * Subscription Serializer Endpoint.
 *
 * @since   1.36.0
 * @license GPLv2
 */

namespace iThemes\Exchange\RecurringPayments\REST\Subscriptions;

/**
 * Class Serializer
 *
 * @package iThemes\Exchange\RecurringPayments\REST\Subscriptions
 */
class Serializer {

	/**
	 * Serialize a subscription.
	 *
	 * @since 1.36.0
	 *
	 * @param \IT_Exchange_Subscription $subscription
	 *
	 * @return array
	 */
	public function serialize( \IT_Exchange_Subscription $subscription ) {

		$s  = $subscription;
		$id = "{$s->get_transaction()->get_ID()}:{$s->get_product()->ID}";

		return array(
			'id'                => $id,
			'product'           => $s->get_product()->ID,
			'auto_renewing'     => $s->is_auto_renewing(),
			'transaction'       => $s->get_transaction()->get_ID(),
			'recurring_profile' => $this->serialize_profile( $s->get_recurring_profile() ),
			'trial_profile'     => $this->serialize_profile( $s->get_trial_profile(), true ),
			'trial_period'      => $s->is_trial_period(),
			'customer'          => $s->get_customer() ? $s->get_customer()->ID : 0,
			'beneficiary'       => $s->get_beneficiary() ? $s->get_beneficiary()->ID : 0,
			'start_date'        => mysql_to_rfc3339( $s->get_start_date()->format( 'Y-m-d H:i:s' ) ),
			'expiry_date'       => $s->get_expiry_date() ? mysql_to_rfc3339( $s->get_expiry_date()->format( 'Y-m-d H:i:s' ) ) : null,
			'days_remaining'    => $s->get_days_left_in_period(),
			'subscriber_id'     => $s->get_subscriber_id(),
			'status'            => array(
				'slug'  => $subscription->get_status(),
				'label' => $subscription->get_status( true ),
			),
		);
	}

	/**
	 * Serialize a recurring profile.
	 *
	 * @since 1.36.0
	 *
	 * @param \IT_Exchange_Recurring_Profile|null $profile
	 * @param bool                                $trial
	 *
	 * @return array|null
	 */
	protected function serialize_profile( \IT_Exchange_Recurring_Profile $profile = null, $trial = false ) {

		if ( ! $profile ) {
			return null;
		}

		return array(
			'type'    => array(
				'slug'  => $profile->get_interval_type(),
				'label' => $profile->get_interval_type( true )
			),
			'count'   => $profile->get_interval_count(),
			'seconds' => $profile->get_interval_seconds(),
			'label'   => $profile->get_label( $trial ),
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
			'$schema'     => 'http://json-schema.org/draft-04/schema#',
			'title'       => 'subscription',
			'type'        => 'object',
			'definitions' => array(
				'recurring_profile' => array(
					'title'       => 'recurring_profile',
					'description' => __( 'Description of a recurring profile.', 'LION' ),
					'type'        => 'object',
					'properties'  => array(
						'type'    => array(
							'description' => __( 'The interval type.', 'LION' ),
							'type'        => 'object',
							'properties'  => array(
								'slug'  => array(
									'type'        => 'string',
									'description' => __( 'The interval type.', 'LION' ),
									'enum'        => array(
										\IT_Exchange_Recurring_Profile::TYPE_DAY,
										\IT_Exchange_Recurring_Profile::TYPE_WEEK,
										\IT_Exchange_Recurring_Profile::TYPE_MONTH,
										\IT_Exchange_Recurring_Profile::TYPE_YEAR,
									),
									'context'     => array( 'edit' )
								),
								'label' => array(
									'type'        => 'string',
									'description' => __( 'The interval type label.', 'LION' ),
									'context'     => array( 'view', 'edit' )
								),
							)
						),
						'count'   => array(
							'type'        => 'integer',
							'description' => __( 'The total number of intervals within the period.', 'LION' ),
							'minimum'     => 0,
							'readonly'    => true,
							'context'     => array( 'view', 'edit', 'embed' ),
						),
						'seconds' => array(
							'type'        => 'integer',
							'description' => __( 'The period represented in seconds.', 'LION' ),
							'readonly'    => true,
							'context'     => array( 'view', 'edit', 'embed' ),
						),
						'label'   => array(
							'type'        => 'string',
							'description' => __( 'The label of the profile.', 'LION' ),
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),
			),
			'properties'  => array(
				'id'                => array(
					'description' => __( 'The unique id for this subscription.', 'LION' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'product'           => array(
					'description' => __( 'The product ID this subscription grants access to.', 'LION' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' )
				),
				'auto_renewing'     => array(
					'description' => __( 'Does this subscription automatically renew.', 'LION' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit', 'embed' )
				),
				'transaction'       => array(
					'description' => __( 'The transaction ID used to purchase this transaction', 'LION' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' )
				),
				'recurring_profile' => array(
					'description' => __( 'The length and duration of a subscription period.', 'LION' ),
					'$ref'        => '#definitions/recurring_profile',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'trial_profile'     => array(
					'description' => __( 'The length and duration of a subscription trial period.', 'LION' ),
					'$ref'        => '#definitions/recurring_profile',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'trial_period'      => array(
					'description' => __( 'Is the subscription in the trial period.', 'LION' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'customer'          => array(
					'description' => __( 'The customer paying for the subscription.', 'LION' ),
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'beneficiary'       => array(
					'description' => __( 'The customer receiving the benefits of the subscription.', 'LION' ),
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'start_date'        => array(
					'description' => __( 'The date the subscription started.', 'LION' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' )
				),
				'expiry_date'       => array(
					'description' => __( 'The date the subscription expires.', 'LION' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' )
				),
				'days_remaining'    => array(
					'description' => __( 'The days remaining in the subscription.', 'LION' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' )
				),
				'subscriber_id'     => array(
					'description' => __( 'The gateway subscriber ID.', 'LION' ),
					'type'        => 'string',
					'context'     => array( 'edit' )
				),
				'status'            => array(
					'description' => __( 'The subscription status.', 'LION' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'slug'  => array(
							'description' => __( 'The subscription status slug.', 'LION' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
							'enum'        => array_keys( \IT_Exchange_Subscription::get_statuses() )
						),
						'label' => array(
							'description' => __( 'The subscription status label.', 'LION' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' )
						),
					),
				),
			)
		);
	}
}