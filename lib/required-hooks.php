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
		// no longer needed as of 1.6.1
		//wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );

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
			if ( !empty( $product['product_id'] ) ) {
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

	if ( 'membership-product-type' === it_exchange_get_product_type( $product_id ) ) {
		if ( it_exchange_product_supports_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			if ( it_exchange_product_has_feature( $product_id, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				if ( it_exchange_product_supports_feature( $product_id, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
					if ( it_exchange_product_has_feature( $product_id, 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
						return false; //multi-cart should be disabled if product has auto-renewing feature
					}
				}
			}
		}
	}

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

	foreach ( $transaction->get_products() as $product ) {

		$product = it_exchange_get_product( $product['product_id'] );

		if ( $product->get_feature( 'recurring-payments', array( 'setting' =>'recurring-enabled' ) ) ) {
			IT_Exchange_Subscription::create( $transaction, $product );
		}
	}

	it_exchange_recurring_payments_addon_update_expirations( $transaction );
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_recurring_payments_addon_add_transaction', 0 );

/**
 * Bump expirations when a child transaction occurs.
 *
 * @since 1.8
 *
 * @param int $transaction_id
 */
function it_exchange_recurring_payments_bump_expiration_on_child_transaction( $transaction_id ) {

	$parent = wp_get_post_parent_id( $transaction_id );

	it_exchange_recurring_payments_addon_update_expirations( it_exchange_get_transaction( $parent ) );
}

add_action( 'it_exchange_add_child_transaction_success', 'it_exchange_recurring_payments_bump_expiration_on_child_transaction' );

/**
 * Update the status when the status hook is fired.
 *
 * This really is for BC as IT_Exchange_Subscription::set_status() should always be used.
 *
 * @since 1.8
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string $sub_id
 * @param string $subscriber_status
 */
function it_exchange_recurring_payments_update_status( $transaction, $sub_id, $subscriber_status ) {

	$subscription = it_exchange_get_subscription( it_exchange_get_transaction( $transaction ) );
	$subscription->set_status( $subscriber_status );
}

add_action( 'it_exchange_update_transaction_subscription_status', 'it_exchange_recurring_payments_update_status', 10, 3 );

/**
 * Send status notifications whenever a subscription status changes.
 *
 * @since 1.8
 *
 * @param string                   $new_status
 * @param string                   $old_status
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_send_status_notifications( $new_status, $old_status, IT_Exchange_Subscription $subscription ) {

	$transaction = $subscription->get_transaction();
	$customer = $subscription->get_customer();

	switch ( $new_status ) {

		case 'deactivated' : //expired
			it_exchange_recurring_payments_customer_notification( $customer, 'deactivate', $transaction );
			break;

		case 'cancelled' :
			it_exchange_recurring_payments_customer_notification( $customer, 'cancel', $transaction );
			break;

	}
}

add_action( 'it_exchange_transition_subscription_status', 'it_exchange_recurring_payments_send_status_notifications', 10, 3 );

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

			$subscription = it_exchange_get_subscription( $transaction, it_exchange_get_product( $product_id ) );
			$subscription->set_status( IT_Exchange_Subscription::STATUS_DEACTIVATED );
		}
	}
}

/**
 * Modifies the Transaction Payments screen for recurring payments
 * Adds recurring type to product title
 *
 * @since 1.0.1
 * @param object $post Post Object
 * @param array $product Cart Product
 *
 * @return void
*/
function it_exchange_recurring_payments_transaction_print_metabox_after_product_feature_title( $post, $product ) {

	$transaction = it_exchange_get_transaction( $post->ID );

	try {
		$subscription = it_exchange_get_subscription( $transaction, it_exchange_get_product( $product['product_id'] ) );
	} catch ( Exception $e ) {

		$time = __( 'forever', 'LION' );
		echo '<span class="recurring-product-type">' . $time . '</span>';

		return;
	}

	if ( $subscription->is_auto_renewing() ) {
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
	return $base_price . it_exchange_recurring_payments_addon_recurring_label( $product_id );
}
add_filter( 'it_exchange_api_theme_product_base_price', 'it_exchange_recurring_payments_api_theme_product_base_price', 100, 2 );
add_filter( 'it_exchange_customer_pricing_product_price', 'it_exchange_recurring_payments_api_theme_product_base_price', 10, 2 );
add_filter( 'it_exchange_admin_product_list_price_column', 'it_exchange_recurring_payments_api_theme_product_base_price', 10, 2 );

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

	$transaction = it_exchange_get_transaction( $transaction->ID );

	$subs = it_exchange_get_transaction_subscriptions( $transaction );

	if ( ! $subs ) {
		return;
	}

	$df        = get_option( 'date_format' );
	$jquery_df = it_exchange_php_date_format_to_jquery_datepicker_format( $df );
	?>

	<div class="transaction-recurring-options clearfix spacing-wrapper">

		<h3><?php _e( 'Subscription Settings', 'LION' ); ?></h3>

		<?php foreach ( $subs as $subscription ) :

			$pid = $subscription->get_product()->ID;

			$status = $subscription->get_status();

			$expires = $subscription->get_expiry_date();
			$expires = $expires ? date_i18n( $df, $expires->format( 'U' ), true ) : '';
			?>

			<div class="recurring-options">

				<?php if ( count( $subs ) > 1 ): ?>
					<h4><?php echo $subscription->get_product()->post_title; ?></h4>
				<?php endif; ?>

				<?php if ( $sub_id = $subscription->get_subscriber_id() ) : ?>
					<p>
						<label for="rp-sub-id-<?php echo $pid; ?>">
							<?php _e( 'Subscription ID', 'LION' ); ?>
							<span class="tip" title="<?php _e( 'This is the Subscription ID from the Payment Processor.', 'LION' ); ?>">i</span>
						</label>

						<input type="text" id="rp-sub-id-<?php echo $pid; ?>" name="rp-sub-id[<?php echo $pid; ?>]" value="<?php echo $sub_id; ?>" />
					</p>
				<?php endif; ?>

				<p>
					<label for="rp-status-<?php echo $pid; ?>">
						<?php _e( 'Subscription Status', 'LION' ); ?>
						<span class="tip" title="<?php _e( 'This is the status of the subscription in Exchange, not the transaction. It will not change the status in the Payment gateway.', 'LION' ); ?>">i</span>
					</label>

					<select id="rp-status-<?php echo $pid; ?>" name="rp-status[<?php echo $pid; ?>]">
						<?php foreach ( IT_Exchange_Subscription::get_statuses() as $slug => $label ): ?>
							<option value="<?php echo $slug; ?>" <?php selected( $slug, $status ); ?>>
								<?php echo $label; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="rp-expires-<?php echo $pid; ?>">
						<?php _e( 'Subscription Expiration', 'LION' ); ?>
						<span class="tip" title="<?php _e( 'Set this to change what Exchange sees as the customer expiration date, the Payment processor will still send webhooks if the payment expires or if new payments come through.', 'LION' ); ?>">i</span>
					</label>

					<input type="text" id="rp-expires-<?php echo $pid; ?>" class="datepicker rp-expires" name="rp-expires[<?php echo $pid; ?>]" value="<?php echo $expires; ?>" />
				</p>
			</div>
		<?php endforeach; ?>

		<?php submit_button( 'Save Subscription Settings', 'secondary-button', 'recurring-payments-save', false ); ?>
		<?php wp_nonce_field( 'transaction-recurring-options', 'transaction-recurring-options-nonce', true ) ?>

		<p class="description">
			<?php _e( "Warning:  Changes to these settings can potentially remove this customer's access to their products.", 'LION' ); ?>
		</p>

		<input type="hidden" name="it_exchange_recurring-payment_date_picker_format" value="<?php echo $jquery_df; ?>">
	</div>
	<?php
}
add_action( 'it_exchange_after_payment_details', 'it_exchange_recurring_payments_after_payment_details_recurring_payments_autorenewal_details' );

/**
 * Save the subscription details.
 *
 * @param int $post_id
 */
function it_exchange_recurring_payments_save_transaction_post( $post_id ) {

	if ( empty( $_POST['transaction-recurring-options-nonce'] ) || empty( $_POST['recurring-payments-save'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['transaction-recurring-options-nonce'], 'transaction-recurring-options' ) ) {
		return;
	}

	$txn = it_exchange_get_transaction( $post_id );

	foreach ( it_exchange_get_transaction_subscriptions( $txn ) as $sub ) {

		$new_expires = new DateTime( $_POST['rp-expires'][ $sub->get_product()->ID ] );

		if ( ! $sub->get_expiry_date() || $new_expires->format( 'U' ) != $sub->get_expiry_date()->format( 'U' ) ) {
			$sub->set_expiry_date( $new_expires );
		}

		$new_status = $_POST['rp-status'][ $sub->get_product()->ID ];

		if ( $new_status !== $sub->get_status() ) {
			$sub->set_status( $new_status );
		}

		$new_id = isset( $_POST['rp-sub-id'][ $sub->get_product()->ID ] ) ? $_POST['rp-sub-id'][ $sub->get_product()->ID ] : '';

		if ( $new_id && $new_id !== $sub->get_subscriber_id() ) {
			$sub->set_subscriber_id( $new_id );
		}
	}
}

add_action( 'save_post_it_exchange_tran', 'it_exchange_recurring_payments_save_transaction_post', 10 );

/**
 * Shows the nag when needed.
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_addon_recurring_payments_show_version_nag() {
	if ( version_compare( $GLOBALS['it_exchange']['version'], '1.3.0', '<' ) ) {
		?>
		<div id="it-exchange-add-on-min-version-nag" class="it-exchange-nag">
			<?php printf( __( 'The Recurring Payments add-on requires iThemes Exchange version 1.3.0 or greater. %sPlease upgrade Exchange%s.', 'LION' ), '<a href="' . admin_url( 'update-core.php' ) . '">', '</a>' ); ?>
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
add_action( 'admin_notices', 'it_exchange_addon_recurring_payments_show_version_nag' );
