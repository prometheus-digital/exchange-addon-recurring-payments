<?php
/**
 * iThemes Exchange Recurring Payments Add-on
 *
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */

require_once( dirname( __FILE__ ) . '/lib/class.recurring-profile.php' );
require_once( dirname( __FILE__ ) . '/lib/class.subscription.php' );
require_once( dirname( __FILE__ ) . '/lib/class.daily-price-calculator.php' );
require_once( dirname( __FILE__ ) . '/lib/class.expiry-date-activity.php' );

require_once( dirname( __FILE__ ) . '/lib/prorate/class.credit-request.php' );
require_once( dirname( __FILE__ ) . '/lib/prorate/class.forever-credit-request.php' );
require_once( dirname( __FILE__ ) . '/lib/prorate/class.subscription-credit-request.php' );
require_once( dirname( __FILE__ ) . '/lib/prorate/class.purchase-request.php' );

require_once( dirname( __FILE__ ) . '/lib/prorate/class.credit-requestor.php' );

require_once( dirname( __FILE__ ) . '/lib/requests/class.cancel.php' );
require_once( dirname( __FILE__ ) . '/lib/requests/class.update-payment-method.php' );

require_once( dirname( __FILE__ ) . '/lib/upgrades/class.zero-sum-checkout.php' );
require_once( dirname( __FILE__ ) . '/lib/upgrades/class.non-auto-renewing.php' );

require_once( dirname( __FILE__ ) . '/lib/REST/load.php' );

require_once ( dirname( __FILE__ ) ) . '/lib/deprecated.php';

/**
 * New API functions.
 */
require_once( dirname( __FILE__ ) . '/api/load.php' );

/**
 * New Product Features.
 */
require_once( dirname( __FILE__ ) . '/lib/product-features/load.php' );

/**
 * Exchange Add-ons require several hooks in order to work properly.
 * We've placed them all in one file to help add-on devs identify them more easily
 */
require_once( dirname( __FILE__ ) . '/lib/required-hooks.php' );

/**
 * Exchange will build your add-on's settings page for you and link to it from our add-on
 * screen. You are free to link from it elsewhere as well if you'd like... or to not use our API
 * at all. This file has all the functions related to registering the page, printing the form, and saving
 * the options. This includes the wizard settings. Additionally, we use the Exchange storage API to
 * save / retreive options. Add-ons are not required to do this.
 */
require_once( dirname( __FILE__ ) . '/lib/addon-settings.php' );

/**
 * The following file contains utility functions specific to our recurring payments add-on
 * If you're building your own addon, it's likely that you will
 * need to do similar things.
 */
require_once( dirname( __FILE__ ) . '/lib/addon-functions.php' );

$current_version = get_option( 'exchange_recurring_payments_version', '1.8.3' );

if ( $current_version != ITE_RECURRING_PAYMENTS_VERSION ) {

	/**
	 * Runs when the version upgrades.
	 *
	 * @since 1.8.4
	 *
	 * @param string $current_version
	 * @param string $new_version
	 */
	do_action( 'it_exchange_addon_recurring_payments_upgrade', $current_version, ITE_RECURRING_PAYMENTS_VERSION );

	update_option( 'exchange_recurring_payments_version', ITE_RECURRING_PAYMENTS_VERSION );

	if ( it_exchange_make_upgrader()->get_available_upgrades() ) {
		update_option( 'it_exchange_show_upgrades_nag', true );
	}
}

require_once( dirname( __FILE__ ) . '/lib/class.email.php' );