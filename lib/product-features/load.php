<?php
/**
 * Includes all of our recurring payments product features
 * @since 1.0.0
 * @package exchange-addon-recurring-payments
*/

require_once dirname( __FILE__ ) . '/class.hierarchy.php';
require_once dirname( __FILE__ ) . '/class.recurring-payments.php';
require_once dirname( __FILE__ ) . '/class.recurring-payments-info.php';

new IT_Exchange_Subscription_Hierarchy_Product_Feature();