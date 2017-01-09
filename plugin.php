<?php
/**
 * Load the main plugin.
 *
 * @since 1.9.0
 * @license GPLv2
 */

define( 'ITE_RECURRING_PAYMENTS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ITE_RECURRING_PAYMENTS_VERSION', '1.9.0' );

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
		'author'            => 'iThemes',
		'author_url'        => 'http://ithemes.com/exchange/recurring-payments/',
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