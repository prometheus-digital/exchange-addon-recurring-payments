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

		$s = $subscription;

		$is_cancelled = ( $s->get_status() === \IT_Exchange_Subscription::STATUS_CANCELLED );

		$payment_method = array(
			'method'   => array(
				'slug'  => $s->get_transaction()->get_method(),
				'label' => $s->get_transaction()->get_method( true ),
			),
			'editable' => $s->can_payment_source_be_updated(),
		);

		if ( $source = $s->get_payment_source() ) {
			$payment_method['source'] = array(
				'identifier' => $source->get_identifier(),
				'label'      => $source->get_label()
			);

			if ( $source instanceof \ITE_Payment_Token ) {
				$payment_method['token'] = $source->get_ID();
			} elseif ( $source instanceof \ITE_Gateway_Card ) {
				$payment_method['card'] = array(
					'number' => $source->get_number(),
					'month'  => $source->get_expiration_month(),
					'year'   => $source->get_expiration_year(),
				);
			}
		}

		return array(
			'id'                  => $s->get_id(),
			'product'             => $s->get_product()->ID,
			'auto_renewing'       => $s->is_auto_renewing(),
			'transaction'         => $s->get_transaction()->get_ID(),
			'recurring_amount'    => $s->calculate_recurring_amount_paid(),
			'recurring_profile'   => $this->serialize_profile( $s->get_recurring_profile() ),
			'trial_profile'       => $this->serialize_profile( $s->get_trial_profile(), true ),
			'trial_period'        => $s->is_trial_period(),
			'customer'            => $s->get_customer() ? $s->get_customer()->ID : 0,
			'beneficiary'         => $s->get_beneficiary() ? $s->get_beneficiary()->ID : 0,
			'start_date'          => \iThemes\Exchange\REST\format_rfc339( $s->get_start_date() ),
			'expiry_date'         => $s->get_expiry_date() ? \iThemes\Exchange\REST\format_rfc339( $s->get_expiry_date() ) : null,
			'days_remaining'      => $s->get_days_left_in_period(),
			'subscriber_id'       => $s->get_subscriber_id(),
			'status'              => array(
				'slug'  => $subscription->get_status(),
				'label' => $subscription->get_status( true ),
			),
			'cancellation_reason' => $is_cancelled ? $s->get_cancellation_reason() : '',
			'cancelled_by'        => $is_cancelled ? ( $s->get_cancelled_by() ? $s->get_cancelled_by()->id : 0 ) : 0,
			'payment_method'      => $payment_method,
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
				'id'                  => array(
					'description' => __( 'The unique id for this subscription.', 'LION' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'product'             => array(
					'description' => __( 'The product ID this subscription grants access to.', 'LION' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' )
				),
				'auto_renewing'       => array(
					'description' => __( 'Does this subscription automatically renew.', 'LION' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit', 'embed' )
				),
				'transaction'         => array(
					'description' => __( 'The transaction ID used to purchase this transaction', 'LION' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' )
				),
				'recurring_amount'    => array(
					'description' => __( 'The recurring payment amount.', 'LION' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' )
				),
				'recurring_profile'   => array(
					'description' => __( 'The length and duration of a subscription period.', 'LION' ),
					'$ref'        => '#/definitions/recurring_profile',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'trial_profile'       => array(
					'description' => __( 'The length and duration of a subscription trial period.', 'LION' ),
					'oneOf'       => array(
						array( '$ref' => '#/definitions/recurring_profile', ),
						array( 'type' => 'null' ),
					),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'trial_period'        => array(
					'description' => __( 'Is the subscription in the trial period.', 'LION' ),
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'customer'            => array(
					'description' => __( 'The customer paying for the subscription.', 'LION' ),
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
				),
				'beneficiary'         => array(
					'description' => __( 'The customer receiving the benefits of the subscription.', 'LION' ),
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'start_date'          => array(
					'description' => __( 'The date the subscription started.', 'LION' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' )
				),
				'expiry_date'         => array(
					'description' => __( 'The date the subscription expires.', 'LION' ),
					'oneOf'       => array(
						array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						array(
							'type' => 'string',
							'enum' => array( '' ),
						)
					),
					'context'     => array( 'view', 'edit' )
				),
				'days_remaining'      => array(
					'description' => __( 'The days remaining in the subscription.', 'LION' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' )
				),
				'subscriber_id'       => array(
					'description' => __( 'The gateway subscriber ID.', 'LION' ),
					'type'        => 'string',
					'context'     => array( 'edit' )
				),
				'status'              => array(
					'description' => __( 'The subscription status.', 'LION' ),
					'context'     => array( 'view', 'edit' ),
					'oneOf'       => array(
						array(
							'type'       => 'object',
							'context'    => array( 'view', 'edit' ),
							'properties' => array(
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
						array( 'type' => 'string' )
					),
				),
				'cancellation_reason' => array(
					'description' => __( 'The reason the subscription was cancelled.', 'LION' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' )
				),
				'cancelled_by'        => array(
					'description' => __( 'The customer who cancelled the subscription.', 'LION' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' )
				),
				'payment_method'      => array(
					'description' => __( 'The means by which the subscription is being paid for.', 'LION' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'method'   => array(
							'description' => __( 'The payment method used.', 'LION' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'slug'  => array(
									'description' => __( 'The payment method slug.', 'LION' ),
									'type'        => 'string',
									'readonly'    => true,
									'context'     => array( 'edit' ),
								),
								'label' => array(
									'description' => __( 'The payment method label.', 'LION' ),
									'type'        => 'string',
									'readonly'    => true,
									'context'     => array( 'view', 'edit' )
								),
							),
							'required'    => true,
						),
						'editable' => array(
							'description' => __( 'Is the payment source editable.', 'LION' ),
							'type'        => 'boolean',
							'readonly'    => true,
							'context'     => array( 'view', 'edit' )
						),
						'source'   => array(
							'description' => __( 'Payment source for this subscription.', 'LION' ),
							'type'        => 'object',
							'readonly'    => true,
							'properties'  => array(
								'identifier' => array(
									'type'        => 'string',
									'description' => __( 'Identifier for this payment source. Not necessarily globally unique.', 'LION' ),
									'readonly'    => true,
								),
								'label'      => array(
									'type'        => 'string',
									'description' => __( 'Human readable label for this payment source.', 'LION' ),
									'readonly'    => true,
								),
							),
						),
						'token'    => array(
							'description' => __( 'The payment token id.', 'LION' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'card'     => array(
							'$ref' => \iThemes\Exchange\REST\url_for_schema( 'card' ),
						)
					)
				)
			)
		);
	}
}