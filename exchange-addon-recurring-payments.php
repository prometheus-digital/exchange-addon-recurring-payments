<?php
/*
 * Plugin Name: iThemes Exchange - Recurring Payments Add-on
 * Version: 2.0.0
 * Description: Adds the recurring payments abilities to iThemes Exchange
 * Plugin URI: http://ithemes.com/exchange/recurring-payments/
 * Author: iThemes
 * Author URI: http://ithemes.com
 * iThemes Package: exchange-addon-recurring-payments
 
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
*/

/**
 * Load the Recurring Payments plugin.
 *
 * @since 2.0.0
 */
function it_exchange_load_recurring_payments() {
	if ( ! function_exists( 'it_exchange_load_deprecated' ) || it_exchange_load_deprecated() ) {
		require_once dirname( __FILE__ ) . '/deprecated/exchange-addon-recurring-payments.php';
	} else {
		require_once dirname( __FILE__ ) . '/plugin.php';
	}
}

add_action( 'plugins_loaded', 'it_exchange_load_recurring_payments' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
 */
function ithemes_exchange_addon_recurring_payments_updater_register( $updater ) {
	$updater->register( 'exchange-addon-recurring-payments', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_recurring_payments_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );

/**
 * On activation, set a time, frequency and name of an action hook to be scheduled.
 *
 * @since 1.0.0
 */
function it_exchange_recurring_payments_activation() {
	wp_schedule_event( strtotime( 'Tomorrow 4AM' ), 'daily', 'it_exchange_recurring_payments_daily_schedule' );
}
register_activation_hook( __FILE__, 'it_exchange_recurring_payments_activation' );

/**
 * On deactivation, remove all functions from the scheduled action hook.
 *
 * @since 1.0.0
 */
function it_exchange_recurring_payments_deactivation() {
	wp_clear_scheduled_hook( 'it_exchange_recurring_payments_daily_schedule' );
}
register_deactivation_hook( __FILE__, 'it_exchange_recurring_payments_deactivation' );