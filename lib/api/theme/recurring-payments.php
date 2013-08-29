<?php
/**
 * Restricted Content class for THEME API in Membership Add-on
 *
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
	 * @var array
	 * @since 0.4.0
	*/
	public $_transaction = false;
	
	/**
	 * Maps api tags to methods
	 * @var array $_tag_map
	 * @since 1.0.0
	*/
	public $_tag_map = array(
		'unsubscribe' => 'unsubscribe',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Theme_API_Recurring_Payments () {
		$this->_transaction = empty( $GLOBALS['it_exchange']['transaction'] ) ? false : $GLOBALS['it_exchange']['transaction'];
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
			'label' => apply_filters( 'it_exchange_recurring_payments_addon_unsubscribe_label', __( 'Cancel this subscription', 'LION' ) ),
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
		$output = '';
		if ( it_exchange_get_recurring_payments_addon_transaction_subscription_id( $this->_transaction ) ) {
			$transaction_method = it_exchange_get_transaction_method( $this->_transaction );
			$output .= $options['before'];
			$output .= apply_filters( 'it_exchange_' . $transaction_method . '_unsubscribe_action', '', $options );
			$output .= $options['after'];
		}
		return $output;
	}
}
