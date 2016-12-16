<?php

/**
 * Restricted Content class for THEME API in Membership Add-on
 *
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */
class IT_Theme_API_Recurring_Payments implements IT_Theme_API {

	/**
	 * API context
	 *
	 * @var string $_context
	 * @since 1.0.0
	 */
	private $_context = 'recurring-payments';

	/**
	 * The current transaction
	 *
	 * @var IT_Exchange_Transaction
	 * @since 1.0.0
	 */
	public $_transaction = false;

	/**
	 * The current _transaction_product
	 *
	 * @var array
	 * @since 1.0.0
	 */
	public $_transaction_product = false;

	/**
	 * The current customer
	 *
	 * @var array
	 * @since 1.0.0
	 */
	public $_customer = false;

	/**
	 * Maps api tags to methods
	 *
	 * @var array $_tag_map
	 * @since 1.0.0
	 */
	public $_tag_map = array(
		'unsubscribe'   => 'unsubscribe',
		'expiration'    => 'expiration',
		'payments'      => 'payments',
		'updatepayment' => 'update_payment',
		'pauseresume'   => 'pause_resume',
		'renew'         => 'renew',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function __construct() {
		$this->_transaction         = empty( $GLOBALS['it_exchange']['transaction'] ) ? false : $GLOBALS['it_exchange']['transaction'];
		$this->_transaction_product = empty( $GLOBALS['it_exchange']['transaction_product'] ) ? false : $GLOBALS['it_exchange']['transaction_product'];
		if ( is_user_logged_in() ) {
			$this->_customer = it_exchange_get_current_customer();
		}
	}

	/**
	 * Deprecated Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function IT_Theme_API_Recurring_Payments() {
		self::__construct();
	}

	/**
	 * Returns the context. Also helps to confirm we are an iThemes Exchange theme API class
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function get_api_context() {
		return $this->_context;
	}

	/**
	 * @since 1.0.0
	 * @return string
	 */
	function unsubscribe( $options = array() ) {
		$defaults = array(
			'before' => '',
			'after'  => '',
			'class'  => 'it-exchange-recurring-payments-unsubscribe',
			'label'  => apply_filters( 'it_exchange_recurring_payments_addon_unsubscribe_label', __( 'Cancel this subscription', 'LION' ) ),
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );
		$output   = '';

		$subscriptions = it_exchange_get_transaction_subscriptions( $this->_transaction );

		if ( count( $subscriptions ) !== 1 ) {
			return '';
		}

		$s = reset( $subscriptions );

		if ( ! $s->get_status() ) {
			return '';
		}

		$output .= $options['before'];

		if ( $s->is_status( IT_Exchange_Subscription::STATUS_ACTIVE ) ) {
			if ( $s->can_be_cancelled() ) {
				$output .= $this->get_cancel_api_request_link( $options );
			} else {
				$output .= apply_filters( "it_exchange_{$s->get_transaction()->get_method()}_unsubscribe_action", '', $options, $this->_transaction );
			}
		} else {
			$output .= sprintf( __( 'Subscription Status: %s', 'LION' ), $s->get_status( true ) );
		}

		$output .= $options['after'];

		return $output;
	}

	/**
	 * Get the cancel API link.
	 *
	 * @since 1.9.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	protected function get_cancel_api_request_link( array $options ) {

		if ( ! $this->_transaction instanceof IT_Exchange_Transaction ) {
			return '';
		}

		$subscription = it_exchange_get_subscription_by_transaction( $this->_transaction );

		if ( ! current_user_can( 'it_cancel_subscription', $subscription ) ) {
			return '';
		}

		$label = $options['label'];
		$class = $options['class'];

		ob_start();
		?>

		<p><a href="javascript:"
		   id="it-exchange-cancel-subscription-api-<?php echo $subscription->get_transaction()->ID ?>"
		   class="it-exchange-cancel-subscription-api <?php echo esc_attr( $class ); ?>"
		   data-id="<?php echo $subscription->get_id(); ?>"
		>
			<?php echo $label; ?>
		</a></p>

		<?php

		return ob_get_clean();
	}

	/**
	 * Display the update payment method form if necessary.
	 *
	 * @since 1.9.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function update_payment( $options = array() ) {

		if ( ! $this->_transaction instanceof IT_Exchange_Transaction ) {
			return '';
		}

		try {
			$s = it_exchange_get_subscription_by_transaction( $this->_transaction );
		} catch ( Exception $e ) {
			return '';
		}

		if ( ! $s->can_payment_source_be_updated() ) {
			return '';
		}

		return "<div class='it-exchange-update-subscription-payment-method-container'" .
		       " id='it-exchange-update-subscription-payment-method-container-{$s->get_transaction()->ID}' data-ID='{$s->get_id()}'>" .
		       "</div>";
	}

	/**
	 * Output a Pause or Resume button.
	 *
	 * @since 1.9.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function pause_resume( $options = array() ) {
		if ( ! $this->_transaction instanceof IT_Exchange_Transaction ) {
			return '';
		}

		try {
			$s = it_exchange_get_subscription_by_transaction( $this->_transaction );
		} catch ( Exception $e ) {
			return '';
		}

		if ( ! current_user_can( 'it_pause_subscription', $s ) && ! current_user_can( 'it_resume_subscription' ) ) {
			return '';
		}

		$options = ITUtility::merge_defaults( $options, array(
			'pause_label'  => __( 'Pause', 'LION' ),
			'resume_label' => __( 'Resume', 'LION' ),
		) );

		if ( $s->can_be_paused() ) {

			$label = $options['pause_label'];

			return "<button class='it-exchange-pause-subscription-payment' data-id='{$s->get_id()}'>{$label}</button>";
		}

		if ( $s->can_be_resumed() ) {

			$label = $options['resume_label'];

			return "<button class='it-exchange-resume-subscription-payment' data-id='{$s->get_id()}'>{$label}</button>";
		}

		return '';
	}

	/**
	 * Output a renewal button.
	 *
	 * @since 1.9.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function renew( $options = array() ) {

		if ( ! $this->_transaction instanceof IT_Exchange_Transaction ) {
			return '';
		}

		try {
			$s = it_exchange_get_subscription_by_transaction( $this->_transaction );
		} catch ( Exception $e ) {
			return '';
		}

		if ( $s->is_auto_renewing() ) {
			return '';
		}

		$txn_id = $s->get_transaction()->get_ID();
		$prod_id = $s->get_product()->get_ID();

		$html = "<div class=\"it-exchange-renew-subscription-container\" id=\"it-exchange-renew-subscription-{$txn_id}-{$prod_id}\"></div>";

		return $html;
	}

	/**
	 * @since 1.0.0
	 * @return string
	 */
	function expiration( $options = array() ) {
		$defaults   = array(
			'date_format'      => get_option( 'date_format' ),
			'before'           => '',
			'after'            => '',
			'class'            => 'it-exchange-recurring-payments-expiration',
			'label'            => apply_filters( 'it_exchange_recurring_payments_addon_expiration_label', __( 'Expires', 'LION' ) ),
			'show_auto_renews' => false,
		);
		$options    = ITUtility::merge_defaults( $options, $defaults );
		$output     = '';
		$product_id = $this->_transaction_product['product_id'];
		$expire     = $this->_transaction->get_transaction_meta( 'subscription_expires_' . $product_id, true );
		$arenew     = $this->_transaction->get_transaction_meta( 'subscription_autorenew_' . $product_id, true );
		if ( ! empty( $expire ) ) {
			if ( $options['show_auto_renews'] || ! $arenew ) {
				$output = $options['label'] . ': ' . date_i18n( $options['date_format'], $expire );
			}
		}

		return $output;
	}

	/**
	 * @since 1.0.0
	 * @return string
	 */
	function payments( $options = array() ) {
		$defaults = array(
			'date_format'     => get_option( 'date_format' ),
			'format_currency' => true,
			'before'          => '',
			'after'           => '',
			'class'           => 'it-exchange-recurring-payments-payments',
			'label'           => apply_filters(
				'it_exchange_recurring_payments_addon_payments_label',
				__( 'Payment of %s on %s', 'LION' )
			),
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );
		$output   = $remaining = '';

		try {
			$subscription = it_exchange_get_subscription_by_transaction( $this->_transaction );

			if ( $subscription && $subscription->are_occurrences_limited() ) {
				$remaining = sprintf( __( '%d Remaining Payments', 'it-l10n-ithemes-exchange' ), $subscription->get_remaining_occurrences() );
			}
		} catch ( Exception $e ) {

		}

		if ( strpos( $options['label'], '%s' ) === false ) {
			$sprintf = false;
			_doing_it_wrong(
				"it_exchange( 'recurring-payments', 'payments' )",
				'`label` option should use sprintf placeholders for transalation.',
				'1.9.0'
			);
		} else {
			$sprintf = true;
		}

		if ( $this->_transaction->children->count() ) {
			$payment_transactions = $this->_transaction->children;

			$output .= $options['before'];
			$output .= $remaining;
			$output .= '<ul class="' . $options['class'] . '">';
			foreach ( $payment_transactions as $transaction ) {
				$output .= '<li>';

				if ( $sprintf ) {
					$output .= sprintf(
						$options['label'],
						it_exchange_get_transaction_total( $transaction, $options['format_currency'] ),
						it_exchange_get_transaction_date( $transaction, $options['date_format'] )
					);
				} else {
					$output .= $options['label'] . ' ' . __( 'of', 'LION' ) . ' ' .
					           it_exchange_get_transaction_total( $transaction, $options['format_currency'] )
					           . ' on ' .
					           it_exchange_get_transaction_date( $transaction, $options['date_format'] );
				}

				$output .= ' - ' . it_exchange_get_transaction_status_label( $transaction );
			}
			$output .= '</ul>';
			$output .= $options['after'];
		} elseif ( ! $output ) {
			return $remaining;
		}

		return $output;
	}
}
