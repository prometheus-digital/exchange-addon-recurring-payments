<?php
/**
 * Loads APIs for iThemes Exchange - Recurring Payments Add-on
 *
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */

if ( is_admin() ) {
	// Admin only
} else {
	// Frontend only
	require_once( dirname( __FILE__ ) . '/theme.php' );
}

// Transaction Add-ons
require_once( dirname( __FILE__ ) . '/transactions.php' );
require_once( dirname( __FILE__ ) . '/subscriptions.php' );