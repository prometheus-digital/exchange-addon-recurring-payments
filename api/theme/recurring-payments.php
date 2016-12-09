<?php
/**
 * Restricted Content class for THEME API in Membership Add-on
 *
 * @package exchange-addon-recurring-payments
 * @since 1.0.0
*/

class IT_Theme_API_Recurring_Payments implements IT_Theme_API {

	/**
	 * API context
	 * @var string $_context
	 * @since 1.0.0
	*/
	private $_context = 'recurring-payments';

	/**
	 * The current transaction
	 * @var IT_Exchange_Transaction
	 * @since 1.0.0
	*/
	public $_transaction = false;

	/**
	 * The current _transaction_product
	 * @var array
	 * @since 1.0.0
	*/
	public $_transaction_product = false;

	/**
	 * The current customer
	 * @var array
	 * @since 1.0.0
	*/
	public $_customer = false;

	/**
	 * Maps api tags to methods
	 * @var array $_tag_map
	 * @since 1.0.0
	*/
	public $_tag_map = array(
		'unsubscribe'   => 'unsubscribe',
		'expiration'    => 'expiration',
		'payments'      => 'payments',
		'updatepayment' => 'update_payment',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function __construct () {
		$this->_transaction         = empty( $GLOBALS['it_exchange']['transaction'] ) ? false : $GLOBALS['it_exchange']['transaction'];
		$this->_transaction_product = empty( $GLOBALS['it_exchange']['transaction_product'] ) ? false : $GLOBALS['it_exchange']['transaction_product'];
		if ( is_user_logged_in() )
			$this->_customer = it_exchange_get_current_customer();
	}

	/**
	 * Deprecated Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Theme_API_Recurring_Payments () {
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
	function unsubscribe( $options=array() ) {
		$defaults = array(
			'before' => '',
			'after'  => '',
			'class'  => 'it-exchange-recurring-payments-unsubscribe',
			'label'  => apply_filters( 'it_exchange_recurring_payments_addon_unsubscribe_label', __( 'Cancel this subscription', 'LION' ) ),
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
		$output = '';

		$subscriptions = it_exchange_get_transaction_subscriptions( $this->_transaction );

		if ( count( $subscriptions ) !== 1 ) {
			return '';
		}

		$s = reset( $subscriptions );

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

		if ( ! $subscription->can_be_cancelled() ) {
			return '';
		}

		$label = $options['label'];
		$class = $options['class'];

		$sub_id  = "{$subscription->get_transaction()->ID}:{$subscription->get_product()->ID}";
		$url     = rest_url( "it_exchange/v1/subscriptions/{$sub_id}/cancel" );
		$url     = wp_nonce_url( $url, 'wp_rest' );

		ob_start();
		?>

		<a href="javascript:"
		   id="it-exchange-cancel-subscription-api-<?php echo $subscription->get_transaction()->ID ?>"
		   class="it-exchange-cancel-subscription-api <?php echo esc_attr( $class ); ?>"
		   data-subscription-endpoint="<?php echo $url; ?>"
		>
			<?php echo $label; ?>
		</a>

		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {

				var cancelling = '<?php echo esc_js( __( 'Cancelling', 'LION' ) ); ?>';

				$( '.it-exchange-cancel-subscription-api').click( function( e ) {
					e.preventDefault();

					var $this = $( this );

					if ( $this.data( 'processing' ) ) {
						return;
					}

					$this.attr( 'disabled', true );
					$this.data( 'processing', true );

					var original_text = $this.text();
					$this.text( cancelling );

					var i = 0;
					setInterval( function() {
						i = ++i % 4;
						var arr = new Array( i + 1 );
						$this.text( cancelling + arr.join( '.' ) );
					}, 500 );

					$.ajax({
						type: 'POST',
						url: $this.data( 'subscription-endpoint' ),
						data: {
							cancelled_by: <?php echo esc_js( get_current_user_id() ); ?>
						},
						success: function( data ) {
							$this.replaceWith( $( '<span class="it-exchange-cancel-subscription-api-done"></span>' ).text( data.status.label ) );
						},
						error: function( xhr ) {

							var data = $.parseJSON( xhr.responseText );

							if ( data.message ) {
								alert( data.message );
							} else {
								alert( 'Error' );
							}

							$this.removeAttr( 'disabled' );
							$this.data(' processing', false );
							$this.text( original_text );
						}
					});
				} );
			});
		</script>

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

		$gateway = $s->get_transaction()->get_gateway();

		if ( ! $gateway || ! $gateway->can_handle( 'update-subscription-payment-method' ) ) {
			return '';
		}

		return "<div class='it-exchange-update-subscription-payment-method-container'" .
		       " id='it-exchange-update-subscription-payment-method-container-{$s->get_transaction()->ID}' data-ID='{$s->get_id()}'>" .
		       "</div>";
	}

	/**
	 * @since 1.0.0
	 * @return string
	*/
	function expiration( $options=array() ) {
		$defaults = array(
			'date_format'      => get_option( 'date_format' ),
			'before'           => '',
			'after'            => '',
			'class'            => 'it-exchange-recurring-payments-expiration',
			'label'            => apply_filters( 'it_exchange_recurring_payments_addon_expiration_label', __( 'Expires', 'LION' ) ),
			'show_auto_renews' => false,
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
		$output = '';
		$product_id = $this->_transaction_product['product_id'];
		$expire = $this->_transaction->get_transaction_meta( 'subscription_expires_' . $product_id, true );
		$arenew = $this->_transaction->get_transaction_meta( 'subscription_autorenew_' . $product_id, true );
		if ( !empty( $expire ) ) {
			if ( $options['show_auto_renews'] || !$arenew ) {
				$output = $options['label'] . ': ' . date_i18n( $options['date_format'], $expire );
			}
		}
		return $output;
	}

	/**
	 * @since 1.0.0
	 * @return string
	*/
	function payments( $options=array() ) {
		$defaults = array(
			'date_format'      => get_option( 'date_format' ),
			'format_currency'  => true,
			'before'           => '',
			'after'            => '',
			'class'            => 'it-exchange-recurring-payments-payments',
			'label'            => apply_filters(
				'it_exchange_recurring_payments_addon_payments_label',
				__( 'Payment of %s on %s', 'LION' )
			),
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
		$output = $remaining = '';

		try {
			$subscription = it_exchange_get_subscription_by_transaction( $this->_transaction );

			if ( $subscription && $subscription->are_occurrences_limited() ) {
				$remaining = sprintf( __( '%d Remaining Payments', 'it-l10n-ithemes-exchange' ), $subscription->get_remaining_occurrences() );
			}
		} catch ( Exception $e ) {

		}

		if ( strpos( $options['label'], '%s') === false ) {
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
