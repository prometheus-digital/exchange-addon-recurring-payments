<?php

function it_exchange_recurring_payments_addon_admin_wp_enqueue_styles( $hook_suffix, $post_type ) {
	if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );
	}
}
add_action( 'it_exchange_admin_wp_enqueue_styles', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_styles', 10, 2 );

function it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts( $hook_suffix, $post_type ) {
	$deps = array( 'post', 'jquery-ui-sortable', 'jquery-ui-droppable', 'jquery-ui-tabs', 'jquery-ui-tooltip', 'jquery-ui-datepicker', 'autosave' );
	wp_enqueue_script( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
}
add_action( 'it_exchange_admin_wp_enqueue_scripts', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts', 10, 2 );

function it_exchange_recurring_payments_addon_content_purchases_fields_elements( $elements ) {
	$elements[] = 'unsubscribe';
	$elements[] = 'expiration';
	return $elements;	
}
add_filter( 'it_exchange_get_content_purchases_fields_elements', 'it_exchange_recurring_payments_addon_content_purchases_fields_elements' );

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
 * Disables multi item carts if viewing product with auto-renew enabled
 * because you cannot mix auto-renew prices with non-auto-renew prices in
 * payment gateways
 *
 * @since 1.0.0
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

function it_exchange_recurring_payments_addon_add_transaction( $transaction_id ) {
	$cart_object = get_post_meta( $transaction_id, '_it_exchange_cart_object', true );
		
	$customer_id = get_post_meta( $transaction_id, '_it_exchange_customer_id', true );
	$customer = new IT_Exchange_Customer( $customer_id );
	$recurring_payments = $customer->get_customer_meta( 'recurring_payments' );
	
	foreach ( $cart_object->products as $product ) {
		
		if ( it_exchange_product_supports_feature( $product['product_id'], 'recurring-payments' ) ) {
			$time = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'time' ) );
			$renew = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'auto-renew' ) );
			
			switch( $time ) {
				
				case 'forever':
					$expires = false;
					break;
			
				case 'yearly':
					$expires = strtotime( '+1 Year' );
					break;
			
				case 'monthly':
				default:
					$expires = strtotime( '+1 Month' );
					break;
				
			}
			$expires = apply_filters( 'it_exchange_recurring_payments_addon_expires_time', $expires, $time );
			
			if ( $expires ) {
				$autorenews = ( 'on' === $renew ) ? true : false;
				$recurring_payments[$product['product_id']] = array( 'expires' => $expires, 'auto-renews' => $autorenews );
			}
			
		}
		
	}
	$customer->update_customer_meta( 'recurring_payments', $recurring_payments );
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_recurring_payments_addon_add_transaction' );