<?php
/**
 * Contains the email class.
 *
 * @since   1.8.3
 * @license GPLv2
 */

/**
 * Class IT_Exchange_Recurring_Payments_Email
 */
class IT_Exchange_Recurring_Payments_Email {

	/**
	 * @var bool
	 */
	private static $batching = false;

	/**
	 * @var IT_Exchange_Sendable[]
	 */
	private static $queue = array();

	/**
	 * IT_Exchange_Recurring_Payments_Email constructor.
	 */
	public function __construct() {
		add_action( 'it_exchange_transition_subscription_status', array( $this, 'send_status_notifications' ), 10, 3 );
		add_action( 'it_exchange_register_email_notifications', array( $this, 'register_notifications' ) );
		add_action( 'it_exchange_email_notifications_register_tags', array( $this, 'register_tags' ) );
	}

	/**
	 * Set batch mode.
	 *
	 * For use in CRON jobs or other similar applications
	 * where multiple emails will be going out in one request.
	 *
	 * @since 1.8.3
	 *
	 * @param bool $batch
	 */
	public static function batch( $batch = true ) {
		self::$batching = $batch;

		if ( ! $batch && self::$queue ) {

			it_exchange_send_email( self::$queue );

			self::$queue = array();
		}
	}

	/**
	 * Send status notifications.
	 *
	 * @since 1.8.3
	 *
	 * @param string                   $new_status
	 * @param string                   $old_status
	 * @param IT_Exchange_Subscription $subscription
	 */
	public function send_status_notifications( $new_status, $old_status, IT_Exchange_Subscription $subscription ) {

		switch ( $new_status ) {
			case IT_Exchange_Subscription::STATUS_DEACTIVATED:
				$notification = it_exchange_email_notifications()->get_notification( 'recurring-payment-deactivated' );
				break;
			case IT_Exchange_Subscription::STATUS_CANCELLED:
				$notification = it_exchange_email_notifications()->get_notification( 'recurring-payment-cancelled' );
				break;
			default:
				return;
		}

		if ( ! $notification->is_active() ) {
			return;
		}

		$customer = $subscription->get_customer();

		$email = new IT_Exchange_Email( new IT_Exchange_Email_Recipient_Customer( $customer ), $notification, array(
			'customer'     => $customer,
			'subscription' => $subscription
		) );

		if ( self::$batching ) {
			self::$queue[] = $email;
		} else {
			it_exchange_send_email( $email );
		}
	}

	/**
	 * Register email notifications.
	 *
	 * @since 1.8.3
	 *
	 * @param IT_Exchange_Email_Notifications $notifications
	 */
	public function register_notifications( IT_Exchange_Email_Notifications $notifications ) {
		$notifications
			->register_notification( new IT_Exchange_Customer_Email_Notification(
				__( 'Recurring Payment Cancelled', 'it-l10n-ithemes-exchange' ), 'recurring-payment-cancelled', null, array(
					'defaults' => array(
						'subject' => __( 'Cancellation Notification', 'LION' ),
						'body'    => sprintf( __( "Hello %s, \r\n\r\n Your recurring payment for %s has been cancelled.\r\n\r\nThank you.\r\n\r\n%s", 'LION' ),
							'[it_exchange_email show=first_name]', '[it_exchange_email show=subscription_product]', '[it_exchange_email show=company_name]' )
					),
					'group'    => __( 'Recurring Payments', 'it-l10n-ithemes-exchange' )
				)
			) )
			->register_notification( new IT_Exchange_Customer_Email_Notification(
				__( 'Recurring Payment Expired', 'it-l10n-ithemes-exchange' ), 'recurring-payment-deactivated', null, array(
					'defaults' => array(
						'subject' => __( 'Expiration Notification', 'LION' ),
						'body'    => sprintf( __( "Hello %s, \r\n\r\n Your recurring payment for %s has expired.\r\n\r\n You can renew your subscription here: %s \r\n\r\nThank you.\r\n\r\n%s", 'LION' ),
							'[it_exchange_email show=first_name]', '[it_exchange_email show=subscription_product]', '[it_exchange_email show=subscription_product_link]', '[it_exchange_email show=company_name]' )
					),
					'group'    => __( 'Recurring Payments', 'it-l10n-ithemes-exchange' )
				)
			) );
	}

	/**
	 * Register custom email tags.
	 *
	 * @since 1.8.3
	 *
	 * @param IT_Exchange_Email_Tag_Replacer $replacer
	 */
	public function register_tags( IT_Exchange_Email_Tag_Replacer $replacer ) {

		$tags = array(
			'subscription_product'      => array(
				'name'    => __( 'Subscription Product', 'it-l10n-ithemes-exchange' ),
				'desc'    => __( 'The name of the product subscribed to.', 'it-l10n-ithemes-exchange' ),
				'context' => array( 'subscription' )
			),
			'subscription_product_link' => array(
				'name'    => __( 'Subscription Product Link', 'it-l10n-ithemes-exchange' ),
				'desc'    => __( 'A link to the subscription product page.', 'it-l10n-ithemes-exchange' ),
				'context' => array( 'subscription' )
			),
		);

		foreach ( $tags as $tag => $config ) {

			$obj = new IT_Exchange_Email_Tag_Base( $tag, $config['name'], $config['desc'], array( $this, $tag ) );

			foreach ( $config['context'] as $context ) {
				$obj->add_required_context( $context );
			}

			foreach ( array( 'recurring-payment-cancelled', 'recurring-payment-deactivated' ) as $notification ) {
				$obj->add_available_for( $notification );
			}

			$replacer->add_tag( $obj );
		}
	}

	/**
	 * Replace the subscription product tag.
	 *
	 * @since 1.8.3
	 *
	 * @param array $context
	 *
	 * @return string
	 */
	public function subscription_product( $context ) {
		return $context['subscription']->get_product()->post_title;
	}

	/**
	 * Replace the subscription product link.
	 *
	 * @since 1.8.3
	 *
	 * @param array $context
	 *
	 * @return string
	 */
	public function subscription_product_link( $context ) {
		return get_permalink( $context['subscription']->get_product()->ID );
	}
}

new IT_Exchange_Recurring_Payments_Email();