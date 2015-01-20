<?php
/**
 * iThemes Exchange Recurring Payments Add-on
 * Required Hooks
 * @package exchange-addon-recurring-payments
 * @since 1.0.0
*/

/**
 * Shows the nag when needed.
 *
 * @since CHANGEME
 *
 * @return void
*/
function it_exchange_recurring_payments_addon_show_membership_version_nag() {
	if ( is_plugin_active( 'exchange-addon-membership/exchange-addon-membership.php' ) ) {
		if ( !function_exists( 'it_exchange_membership_addon_get_all_the_children' ) ) {
			?>
			<div id="it-exchange-add-on-min-version-nag" class="it-exchange-nag">
				<?php printf( __( 'Your version of the Membership add-on is not compatible with your Recurring Payments add-on. Please update to version 1.2.16 or greater. %sClick here to upgrade the Membership add-on%s.', 'LION' ), '<a href="' . admin_url( 'update-core.php' ) . '">', '</a>' ); ?>
			</div>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					if ( jQuery( '.wrap > h2' ).length == '1' ) {
						jQuery("#it-exchange-add-on-min-version-nag").insertAfter('.wrap > h2').addClass( 'after-h2' );
					}
				});
			</script>
			<?php
		}
	}
}
add_action( 'admin_notices', 'it_exchange_recurring_payments_addon_show_membership_version_nag' );

/**
 * Enqueues styles for Recurring Payments pages
 *
 * @since 1.0.0
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type WordPress Post Type
*/
function it_exchange_recurring_payments_addon_admin_wp_enqueue_styles( $hook_suffix, $post_type ) {
	global $wp_version;
	
	if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );
		
		if ( $wp_version <= 3.7 ) {
			wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product-pre-3-8', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product-pre-3-8.css' );
		}
	} else if ( isset( $post_type ) && 'it_exchange_tran' === $post_type ) {
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-transaction-details-css', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/transaction-details.css' );
	}

}
add_action( 'it_exchange_admin_wp_enqueue_styles', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_styles', 10, 2 );

/**
 * Enqueues javascript for Recurring Payments pages
 *
 * @since 1.0.0
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type WordPress Post Type
*/
function it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts( $hook_suffix, $post_type ) {
	if ( empty( $post_type ) || 'it_exchange_prod' != $post_type )
		return;
	$deps = array( 'post', 'jquery-ui-sortable', 'jquery-ui-droppable', 'jquery-ui-tabs', 'jquery-ui-tooltip', 'jquery-ui-datepicker', 'autosave' );
	wp_enqueue_script( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
}
add_action( 'it_exchange_admin_wp_enqueue_scripts', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts', 10, 2 );

/**
 * Function to modify the default purchases fields elements
 *
 * @since 1.0.0
 * @param array $elements Elements being loaded by Theme API
 * @return array $elements Modified elements array
*/
function it_exchange_recurring_payments_addon_content_purchases_fields_elements( $elements ) {
	$elements[] = 'payments';
	$elements[] = 'unsubscribe';
	$elements[] = 'expiration';
	return $elements;	
}
add_filter( 'it_exchange_get_content_purchases_fields_elements', 'it_exchange_recurring_payments_addon_content_purchases_fields_elements' );

/**
 * Adds Recurring Payments templates directory to iThemes Exchange template path array
 *
 * @since 1.0.0
 * @param array $possible_template_paths iThemes Exchange's template paths to check for templates
 * @param mixed $template_names iThemes Exchange's template names
 * @return array $possible_template_paths Modified iThemes Exchange's template paths to check for templates array
*/
function it_exchange_recurring_payments_addon_template_path( $possible_template_paths, $template_names ) {
	$possible_template_paths[] = dirname( __FILE__ ) . '/templates/';
	return $possible_template_paths;
}
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_recurring_payments_addon_template_path', 10, 2 );

/**
 * Disables multi item carts if viewing product with auto-renew enabled
 * because you cannot mix auto-renew prices with non-auto-renew prices in
 * payment gateways
 *
 * @since 1.0.0
 * @param bool $allowed Current status of multi-cart being allowed
 * @return bool True or False if multi-cart is allowed
*/
function it_exchange_recurring_payments_multi_item_cart_allowed( $allowed ) {
	if ( !$allowed )
		return $allowed;
		
	global $post;
					
	if ( it_exchange_is_product( $post ) ) {
		$product = it_exchange_get_product( $post );
		
		if ( it_exchange_product_supports_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) )
			if ( it_exchange_product_has_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) )
				return false; //multi-cart should be disabled if product has auto-renewing feature
	}
	
	$cart = it_exchange_get_cart_products();
	
	if ( !empty( $cart ) ) {
		foreach( $cart as $product ) {
			if ( it_exchange_product_supports_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'auto-renew' ) ) )
				if ( it_exchange_product_has_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'auto-renew' ) ) )
					return false;
		}
	}
		
	return $allowed;
}
add_filter( 'it_exchange_multi_item_cart_allowed', 'it_exchange_recurring_payments_multi_item_cart_allowed' );

/**
 * Disables multi item products if viewing product with auto-renew enabled
 * because you cannot mix auto-renew prices with non-auto-renew prices in
 * payment gateways
 *
 * @since 1.0.0
 * @param bool $allowed Current status of multi-item-product being allowed
 * @param int $product_id Product ID to check
 * @return bool True or False if multi-item-product is allowed
*/
function it_exchange_recurring_payments_multi_item_product_allowed( $allowed, $product_id ) {
	if ( !$allowed )
		return $allowed;
	
	if ( it_exchange_product_supports_feature( $product_id, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) )
		if ( it_exchange_product_has_feature( $product_id, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) )
			return false; //multi-cart should be disabled if product has auto-renewing feature
		
	return $allowed;
}
add_filter( 'it_exchange_multi_item_product_allowed', 'it_exchange_recurring_payments_multi_item_product_allowed', 10, 2 );

/**
 * Adds necessary details to Exchange upon successfully completed transaction
 *
 * @since 1.0.0
 * @param int $transaction_id iThemes Exchange Transaction ID 
 * @return void
*/
function it_exchange_recurring_payments_addon_add_transaction( $transaction_id ) {
    $transaction = it_exchange_get_transaction( $transaction_id );
	it_exchange_recurring_payments_addon_update_expirations( $transaction );
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_recurring_payments_addon_add_transaction' );

/**
 * Updates Expirations dates upon successful payments of recurring products
 *
 * @since 1.0.0
 * @param int $transaction iThemes Exchange Transaction Object 
 * @return void
*/
function it_exchange_recurring_payments_addon_update_expirations( $transaction ) {
	$cart_object = get_post_meta( $transaction->ID, '_it_exchange_cart_object', true );
	$transaction_method = it_exchange_get_transaction_method( $transaction->ID );
	
	foreach ( $cart_object->products as $product ) {
		if ( it_exchange_product_supports_feature( $product['product_id'], 'recurring-payments' ) ) {			
			if ( it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				$trial_enabled = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
				$trial_interval = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'trial-interval' ) );
				$trial_interval_count = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );
				$auto_renew = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'auto-renew' ) );
				$interval = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'interval' ) );
				$interval_count = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'interval-count' ) );
				
				if ( $trial_enabled ) {
					if ( 0 < $trial_interval_count ) { //This product has a trial period associated with it
					
						if ( empty( get_post_ancestors( $transaction->ID ) ) ) { //This is the first and it's a trial period
							
							$interval = $trial_interval;
							$interval_count = $trial_interval_count;
							
						}
						
					}
				}
				$expires = strtotime( sprintf( '+%d %s', $interval_count, $interval ) ) + ( 60 * 60 * 24 ); //plus 1 day

				//The extra day is added just to be safe
				$expires = apply_filters( 'it_exchange_recurring_payments_addon_expires_time', $expires, $interval, $interval_count, $auto_renew );
				$expires = apply_filters( 'it_exchange_recurring_payments_addon_expires_time_' . $transaction_method, $expires, $interval, $interval_count, $auto_renew );
				if ( $expires ) {
					$autorenews = ( 'on' === $auto_renew ) ? true : false;
					$transaction->update_transaction_meta( 'subscription_expires_' . $product['product_id'], $expires );
					$transaction->update_transaction_meta( 'subscription_autorenew_' . $product['product_id'], $autorenews );
				}
			}

		}
		
	}
}

/**
 * Special hook that adds a filter to another hook at the right place in the theme API
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_recurring_payments_addon_content_purchases_before_wrap() {
	add_filter( 'it_exchange_get_transactions_get_posts_args', 'it_exchange_recurring_payments_addon_get_transactions_get_posts_args' );
}
add_action( 'it_exchange_content_purchases_before_wrap', 'it_exchange_recurring_payments_addon_content_purchases_before_wrap' );

/**
 * Used to modify the theme API for transaction listing on the Purchases page
 * to only get the post parents (not the child transactions)
 *
 * @since 1.0.0
 * @params array $args get_posts Arguments
 * @return array $args
*/
function it_exchange_recurring_payments_addon_get_transactions_get_posts_args( $args ) {
	$args['post_parent'] = 0;
	return $args;	
}

/**
 * Daily schedule use to call function for expired product purchases
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_recurring_payments_daily_schedule() {
	it_exchange_recurring_payments_handle_expired();
}
add_action( 'it_exchange_recurring_payments_daily_schedule', 'it_exchange_recurring_payments_daily_schedule' );

/**
 * Gets all transactions with an expired timestamp and expires them if appropriate
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_recurring_payments_handle_expired() {
	global $wpdb;
	
	$results = $wpdb->get_results( 
		$wpdb->prepare( '
			SELECT post_id, meta_key, meta_value
			FROM ' . $wpdb->postmeta . ' 
			WHERE meta_key LIKE %s 
			  AND meta_value < %d',
			'_it_exchange_transaction_subscription_expires_%', time() )
	);
	
	foreach ( $results as $result ) {
		
		$product_id = str_replace( '_it_exchange_transaction_subscription_expires_', '', $result->meta_key );
		$transaction = it_exchange_get_transaction( $result->post_id );
		if ( $expired = apply_filters( 'it_exchange_recurring_payments_handle_expired', true, $product_id, $transaction ) ) {
			$transaction->update_transaction_meta( 'subscription_expired_' . $product_id, $result->meta_value );
			$transaction->delete_transaction_meta( 'subscription_expires_' . $product_id );
			$customer = it_exchange_get_transaction_customer( $transaction->ID );
			it_exchange_recurring_payments_addon_update_transaction_subscription_status( $transaction, $customer->id, 'deactivated' );
		}
		
	}
	
}

/**
 * Modifies the Transaction Payments screen for recurring payments
 * Adds recurring type to product title
 *
 * @since 1.0.1
 * @param object $post Post Object
 * @param object $transaction_product iThemes Exchange Transaction Object
 * @return void
*/
function it_exchange_recurring_payments_transaction_print_metabox_after_product_feature_title( $post, $product ) {
	$transaction = it_exchange_get_transaction( $post->ID );
	$enabled = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'recurring-enabled' ) );	
	if ( empty( $enabled ) ) {
		$time = __( 'forever', 'LION' );
		echo '<span class="recurring-product-type">' . $time . '</span>';
	} else if ( $transaction->get_transaction_meta( 'subscription_autorenew_' . $product['product_id'], true ) ) {
		echo '<span class="recurring-product-autorenew"></span>';
	}
}
add_action( 'it_exchange_transaction_print_metabox_after_product_feature_title', 'it_exchange_recurring_payments_transaction_print_metabox_after_product_feature_title', 10, 2 );

/**
 * Modifies the Transaction Payments screen for recurring payments
 * Calls action for payment gateways to add their own cancel URL for auto-renewing payments
 *
 * @since 1.0.1
 * @return void
*/
function it_exchange_recurring_payments_addon_after_payment_details() {
	global $post;
	$transaction = it_exchange_get_transaction( $post->ID );
	$transaction_method = it_exchange_get_transaction_method( $transaction->ID );
	do_action( 'it_exchange_after_payment_details_cancel_url_for_' . $transaction_method, $transaction );	
}
add_action( 'it_exchange_after_payment_details', 'it_exchange_recurring_payments_addon_after_payment_details' );

/**
 * Returns base price with recurring label
 *
 * @since CHANGEME
 * @param int $product_id iThemes Exchange Product ID
 * @return string iThemes Exchange recurring label
*/
function it_exchange_recurring_payments_api_theme_product_base_price( $base_price, $product_id ) {
	if ( 'it_exchange_prod' === get_post_type() )
		return $base_price . it_exchange_recurring_payments_addon_recurring_label( $product_id );
	else
		return $base_price;
}
add_filter( 'it_exchange_api_theme_product_base_price', 'it_exchange_recurring_payments_api_theme_product_base_price', 10, 2 );
