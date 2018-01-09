<?php
/*
 * Plugin Name: ExchangeWP - Recurring Payments Add-on
 * Version: 1.8.7
 * Description: Adds the recurring payments abilities to ExchangeWP
 * Plugin URI: https://exchangewp.com/downloads/recurring-payments/
 * Author: ExchangeWP
 * Author URI: https://exchangewp.com
 * ExchangeWP Package: exchange-addon-recurring-payments

 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 * 5. Add license key to settings page.
 *
*/

define( 'ITE_RECURRING_PAYMENTS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ITE_RECURRING_PAYMENTS_VERSION', '1.8.7' );

/**
 * This registers our plugin as a recurring payments addon
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_recurring_payments_addon() {
	$options = array(
		'name'              => __( 'Recurring Payments', 'LION' ),
		'description'       => __( 'This add-on turns on recurring payments for supporting payment gateways.', 'LION' ),
		'author'            => 'ExchangeWP',
		'author_url'        => 'https://exchangewp.com/downloads/recurring-payments/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/recurring50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'other',
		'settings-callback' => 'it_exchange_recurring_payments_addon_settings_callback',
	);
	it_exchange_register_addon( 'recurring-payments', $options );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_recurring_payments_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_recurring_payments_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'it_exchange_recurring_payments_set_textdomain' );

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

/**
 * Check for the new updater class
 *
 */
 function exchange_recurringpayments_plugin_updater() {

 	$license_check = get_transient( 'exchangewp_license_check' );

 	if ($license_check->license == 'valid' ) {
 		$license_key = it_exchange_get_option( 'exchangewp_licenses' );
 		$license = $license_key['exchange_license'];

 		$edd_updater = new EDD_SL_Plugin_Updater( 'https://exchangewp.com', __FILE__, array(
 				'version' 		=> '1.8.7', 				// current version number
 				'license' 		=> $license, 				// license key (used get_option above to retrieve from DB)
 				'item_id'		 	=> 355, 					  // name of this plugin
 				'author' 	  	=> 'ExchangeWP',    // author of this plugin
 				'url'       	=> home_url(),
 				'wp_override' => true,
 				'beta'		  	=> false
 			)
 		);
 	}

 }

 add_action( 'admin_init', 'exchange_recurringpayments_plugin_updater', 0 );
