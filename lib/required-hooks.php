<?php

function it_exchange_admin_wp_enqueue_styles( $hook_suffix, $post_type ) {
	if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );
	}
}
add_action( 'it_exchange_admin_wp_enqueue_styles', 'it_exchange_admin_wp_enqueue_styles', 10, 2 );

function it_exchange_admin_wp_enqueue_scripts( $hook_suffix, $post_type ) {
	$deps = array( 'post', 'jquery-ui-sortable', 'jquery-ui-droppable', 'jquery-ui-tabs', 'jquery-ui-tooltip', 'jquery-ui-datepicker', 'autosave' );
	wp_enqueue_script( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
}
add_action( 'it_exchange_admin_wp_enqueue_scripts', 'it_exchange_admin_wp_enqueue_scripts', 10, 2 );