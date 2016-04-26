<?php
/**
 * iThemes Exchange Recurring Payments Add-on
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */

require_once( dirname( __FILE__ ) . '/lib/class.recurring-profile.php' );
require_once( dirname( __FILE__ ) . '/lib/class.subscription.php' );
require_once( dirname( __FILE__ ) . '/lib/class.expiry-date-activity.php' );
require_once( dirname( __FILE__ ) . '/lib/upgrades/class.zero-sum-checkout.php' );
require_once( dirname( __FILE__ ) . '/lib/upgrades/class.non-auto-renewing.php' );

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