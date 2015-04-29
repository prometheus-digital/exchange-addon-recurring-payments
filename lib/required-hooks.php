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
		wp_enqueue_script( 'it-exchange-recurring-payments-addon-transaction-details-js', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/edit-transaction.js', array( 'jquery', 'jquery-ui-datepicker' ) );
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
		
		if ( it_exchange_product_supports_feature( $product->ID, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			if ( it_exchange_product_has_feature( $product->ID, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				if ( it_exchange_product_supports_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
					if ( it_exchange_product_has_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
						return false; //multi-cart should be disabled if product has auto-renewing feature
					}
				}
			}
		}
	}
	
	$cart = it_exchange_get_cart_products();
	
	if ( !empty( $cart ) ) {
		foreach( $cart as $product ) {
			if ( it_exchange_product_supports_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				if ( it_exchange_product_has_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
					if ( it_exchange_product_supports_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
						if ( it_exchange_product_has_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
							return false;
						}
					}
				}
			}
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
	$transaction_method = it_exchange_get_transaction_method( $transaction->ID );
	$ancestors = get_post_ancestors( $transaction->ID );
	if ( !empty( $ancestors ) ) {
		foreach( $ancestors as $ancestor_id ) { //should only be one
			$cart_object = get_post_meta( $ancestor_id, '_it_exchange_cart_object', true );
			$transaction = it_exchange_get_transaction( $ancestor_id );
			break;
		}
	} else {
		$cart_object = get_post_meta( $transaction->ID, '_it_exchange_cart_object', true );
	}
	
	foreach ( $cart_object->products as $product ) {
		if ( it_exchange_product_supports_feature( $product['product_id'], 'recurring-payments' ) ) {			
			if ( it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				$trial_enabled = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
				$auto_renew = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'auto-renew' ) );
				$interval = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'interval' ) );
				$interval_count = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'interval-count' ) );
			
				if ( $trial_enabled && empty( $GLOBALS['it_exchange']['child_transaction'] ) ) {
					$trial_interval_count = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );
					if ( 0 < $trial_interval_count ) { //This product has a trial period associated with it
						$trial_interval = it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'trial-interval' ) );
						if ( empty( $ancestors ) ) { //This is the first and it's a trial period
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
					$transaction->delete_transaction_meta( 'subscription_expired_' . $product['product_id'] );
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

/**
 * Returns the transaction customer's Recurring Payments Autorenewal details
 *
 * @since CHANGEME
 *
 * @param WP_Post|int|IT_Exchange_Transaction $transaction ID or object
 *
 * @return string
*/
function it_exchange_recurring_payments_after_payment_details_recurring_payments_autorenewal_details( $transaction ) {
	$cart_object = get_post_meta( $transaction->ID, '_it_exchange_cart_object', true );
	$transaction = it_exchange_get_transaction( $transaction->ID );
	if ( !empty( $cart_object->products ) ) {
		foreach ( $cart_object->products as $product ) {
			if ( it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				//This is a recurring product...
				$dateformat = get_option( 'date_format' );
				$jquery_date_format = it_exchange_php_date_format_to_jquery_datepicker_format( $dateformat );
				$subscriber_status = $transaction->get_transaction_meta( 'subscriber_status', true );
				$expires = $transaction->get_transaction_meta( 'subscription_expires_' . $product['product_id'], true );
				$expired = $transaction->get_transaction_meta( 'subscription_expired_' . $product['product_id'], true );
				if ( empty( $expires ) ) {
					if ( !empty( $expired ) ) {
						$expires = date_i18n( $dateformat, $expired );
					} else {
						$expires = '';
					}
				} else {
					$expires = date_i18n( $dateformat, $expires );
				}
				?>
				<div class="transaction-recurring-options clearfix spacing-wrapper">
					<div class="recurring-options left">
						<h3><?php _e( 'Subscription Settings', 'LION' ); ?></h3>
						<form action="" method="POST">
							<?php
							$autorenews = $transaction->get_transaction_meta( 'subscription_autorenew_' . $product['product_id'] );
							if ( $autorenews ) {
							$subscriber_id = $transaction->get_transaction_meta( 'subscriber_id', true );
							?>
							<p>
							<label for="recurring-payment-subscriber-id"><?php _e( 'Subscription ID', 'LION' ); ?> <span class="tip" title="<?php _e( 'This is the Subscription ID from the Payment Processor.', 'LION' ); ?>">i</span></label>
							<input type="text" id="recurring-payment-subscriber-id" name="recurring-payment-subscriber-id" value="<?php echo $subscriber_id; ?>" />
							</p>
							<?php
							}
							?>
							
							<p>
							<label for="recurring-payment-subscriber-status"><?php _e( 'Subscription Status', 'LION' ); ?> <span class="tip" title="<?php _e( 'This is the status of the subscription in Exchange, not the transaction. It will not change the status in the Payment gateway.', 'LION' ); ?>">i</span></label>
							<select id="recurring-payment-subscriber-status" name="recurring-payment-subscriber-status">
								<option value="active" <?php selected( 'active', $subscriber_status, true ); ?>><?php _e( 'Active', 'LION' ); ?></option>
								<option value="suspended" <?php selected( 'suspended', $subscriber_status, true ); ?>><?php _e( 'Suspended', 'LION' ); ?></option>
								<option value="cancelled" <?php selected( 'cancelled', $subscriber_status, true ); ?>><?php _e( 'Cancelled', 'LION' ); ?></option>
								<option value="deactivated" <?php selected( 'deactivated', $subscriber_status, true ); ?>><?php _e( 'Deactivated', 'LION' ); ?></option>
							</select>
							</p>
							
							<p>
							<label for="recurring-payment-subscriber-expires"><?php _e( 'Subscription Expiration', 'LION' ); ?> <span class="tip" title="<?php _e( 'Set this to change what Exchange sees as the customer expiration date, the Payment processor will still send webhooks if the payment expires or if new payments come through.', 'LION' ); ?>">i</span></label>
							<input type="text" id="recurring-payment-subscriber-expires" class="datepicker" name="recurring-payment-subscriber-expires" value="<?php esc_attr_e( $expires ); ?>" />
							<input type="hidden" name="it_exchange_recurring-payment_date_picker_format" value="<?php echo $jquery_date_format; ?>" />
							</p>
							<p class="description">
							<?php _e( "Warning:  Changes to these settings can potentially remove this customer's access to their products.", 'LION' ); ?>
							</p>
							<?php submit_button( 'Save Subscription Settings', 'secondary-button', 'recurring-payments-save' ); ?>
							<?php wp_nonce_field( 'transaction-recurring-options', 'transaction-recurring-options-nonce', true ) ?>
						</form>
					</div>
				</div>
				<?php
			}
		}
	}
}
add_action( 'it_exchange_after_payment_details', 'it_exchange_recurring_payments_after_payment_details_recurring_payments_autorenewal_details' );

function it_exchange_recurring_payments_save_transaction_post( $post_id, $post, $update ) {
	if ( !empty( $_POST['transaction-recurring-options-nonce'] ) ) {
		
		if ( wp_verify_nonce( $_POST['transaction-recurring-options-nonce'], 'transaction-recurring-options' ) ) {
			
			$cart_object = get_post_meta( $post_id, '_it_exchange_cart_object', true );
			$transaction = it_exchange_get_transaction( $post_id );
			if ( !empty( $cart_object->products ) ) {
				foreach ( $cart_object->products as $product ) {
					if ( it_exchange_get_product_feature( $product['product_id'], 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
						$autorenews = $transaction->get_transaction_meta( 'subscription_autorenew_' . $product['product_id'] );
						if ( $autorenews ) {
							if ( !empty( $_POST['recurring-payment-subscriber-id'] ) ) {
								$transaction->update_transaction_meta( 'subscriber_id', $_POST['recurring-payment-subscriber-id'] );
							}
						}
						
						if ( !empty( $_POST['recurring-payment-subscriber-status'] ) ) {
							$transaction->update_transaction_meta( 'subscriber_status', $_POST['recurring-payment-subscriber-status'] );
						}
						
						if ( !empty( $_POST['recurring-payment-subscriber-expires'] ) ) {
							$expires = strtotime( $_POST['recurring-payment-subscriber-expires'] );
							if ( time() < $expires ) {
								$transaction->update_transaction_meta( 'subscription_expires_' . $product['product_id'], $expires );
								$transaction->delete_transaction_meta( 'subscription_expired_' . $product['product_id'] );
							} else {
								$transaction->delete_transaction_meta( 'subscription_expires_' . $product['product_id'] );
								$transaction->update_transaction_meta( 'subscription_expired_' . $product['product_id'], $expires );
							}
						}
					}
				}
			}
			
		} else {
			
            if ( $wp_error ) {
                return new WP_Error( 'invalid_nonce', __( 'Unable to verify security none.', 'LION' ) );
            }
            return 0;
			
		}
		
	}
}
add_action( 'save_post_it_exchange_tran', 'it_exchange_recurring_payments_save_transaction_post', 10, 3 );
