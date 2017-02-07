<?php
/**
 * iThemes Exchange Recurring Payments Add-on
 * Required Hooks
 *
 * @package exchange-addon-recurring-payments
 * @since   1.0.0
 */

add_action( 'it_exchange_register_object_types', function ( ITE_Object_Type_Registry $registry ) {
	$registry->register( new ITE_Subscription_Object_Type() );
} );

/**
 * Shows the nag when needed.
 *
 * @since CHANGEME
 *
 * @return void
 */
function it_exchange_recurring_payments_addon_show_membership_version_nag() {
	if ( is_plugin_active( 'exchange-addon-membership/exchange-addon-membership.php' ) ) {
		if ( ! function_exists( 'it_exchange_membership_addon_get_all_the_children' ) ) {
			?>
			<div id="it-exchange-add-on-min-version-nag" class="it-exchange-nag">
				<?php printf( __( 'Your version of the Membership add-on is not compatible with your Recurring Payments add-on. Please update to version 1.2.16 or greater. %sClick here to upgrade the Membership add-on%s.', 'LION' ), '<a href="' . admin_url( 'update-core.php' ) . '">', '</a>' ); ?>
			</div>
			<script type="text/javascript">
				jQuery( document ).ready( function () {
					if ( jQuery( '.wrap > h2' ).length == '1' ) {
						jQuery( "#it-exchange-add-on-min-version-nag" ).insertAfter( '.wrap > h2' ).addClass( 'after-h2' );
					}
				} );
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
 *
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type   WordPress Post Type
 */
function it_exchange_recurring_payments_addon_admin_wp_enqueue_styles( $hook_suffix, $post_type ) {
	global $wp_version;

	if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );

		if ( $wp_version <= 3.7 ) {
			wp_enqueue_style( 'it-exchange-recurring-payments-addon-add-edit-product-pre-3-8', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product-pre-3-8.css' );
		}
	} else if ( isset( $post_type ) && 'it_exchange_tran' === $post_type ) {
		wp_enqueue_script( 'it-exchange-recurring-payments-addon-transaction-details-js', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/edit-transaction.js', array(
			'jquery',
			'jquery-ui-datepicker'
		) );
		wp_enqueue_style( 'it-exchange-recurring-payments-addon-transaction-details-css', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/transaction-details.css' );
		wp_localize_script( 'it-exchange-recurring-payments-addon-transaction-details-js', 'EXCHANGE_RP', array(
			'user' => get_current_user_id()
		) );
	}

}

add_action( 'it_exchange_admin_wp_enqueue_styles', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_styles', 10, 2 );

/**
 * Enqueues javascript for Recurring Payments pages
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type   WordPress Post Type
 */
function it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts( $hook_suffix, $post_type ) {
	if ( empty( $post_type ) || 'it_exchange_prod' != $post_type ) {
		return;
	}
	$deps = array(
		'post',
		'jquery-ui-sortable',
		'jquery-ui-droppable',
		'jquery-ui-tabs',
		'jquery-ui-tooltip',
		'jquery-ui-datepicker',
		'autosave'
	);
	wp_enqueue_script( 'it-exchange-recurring-payments-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
}

add_action( 'it_exchange_admin_wp_enqueue_scripts', 'it_exchange_recurring_payments_addon_admin_wp_enqueue_scripts', 10, 2 );

add_action( 'wp_enqueue_scripts', function() {
	if ( it_exchange_is_page( 'purchases' ) ) {
		add_filter( 'it_exchange_preload_cart_item_types', '__return_true' );
	}
}, 0 );

/**
 * Register backbone REST dependencies.
 *
 * @since 2.0.0
 *
 * @param array $dependencies
 *
 * @return array
 */
function it_exchange_recurring_payments_backbone_dependencies( $dependencies ) {

	$dependencies['recurring-payments'] = plugin_dir_url( __FILE__ ) . 'js/rest.js';

	return $dependencies;
}

add_filter( 'it_exchange_rest_backbone_addon_libs', 'it_exchange_recurring_payments_backbone_dependencies' );

/**
 * Enqueue the purchases script.
 *
 * @since 2.0.0
 */
function it_exchange_recurring_payments_enqueue_purchases() {

	if ( ! it_exchange_is_page( 'purchases' ) ) {
		return;
	}

	wp_enqueue_style( 'it-exchange-recurring-payments-purchases', plugin_dir_url( __FILE__ ) . '/styles/purchases.css' );

	wp_enqueue_script(
		'it-exchange-recurring-payments-purchases',
		plugin_dir_url( __FILE__ ) . '/js/purchases.js',
		array( 'it-exchange-rest', 'jquery.payment' ),
		'2.0.0',
		true
	);

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/checkout.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/token-selector.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/visual-cc.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-recurring-payments-purchases',
		include dirname( __FILE__ ) . '/js/templates/update-payment-method.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-recurring-payments-purchases',
		include dirname( __FILE__ ) . '/js/templates/renew-subscription.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-recurring-payments-purchases',
		include dirname( __FILE__ ) . '/js/templates/change-my-subscription.html'
	);

	it_exchange_preload_schemas( array(
		'subscription',
		'payment-token',
		'customer',
		'cart',
		'cart-item-product',
		'cart-item-coupon',
		'cart-purchase',
		'address',
	) );
}

add_action( 'wp_enqueue_scripts', 'it_exchange_recurring_payments_enqueue_purchases' );

/**
 * Localize the purchases.
 *
 * @since 2.0.0
 */
function it_exchange_recurring_payments_localize_purchases() {

	if ( ! it_exchange_is_page( 'purchases' ) ) {
		return;
	}

	$subscriptions  = $upgrades = $downgrades = array();
	$serializer     = new \iThemes\Exchange\RecurringPayments\REST\v1\Subscriptions\Serializer();
	$prorate        = new \iThemes\Exchange\RecurringPayments\REST\v1\Subscriptions\ProrateSerializer();
	$prorate_schema = $prorate->get_schema();
	$filterer       = new \iThemes\Exchange\REST\Helpers\ContextFilterer();
	$schema         = $serializer->get_schema();

	foreach ( IT_Theme_API_Transactions::$transactions as $transaction ) {

		try {
			$s = it_exchange_get_subscription_by_transaction( $transaction );

			if ( $s ) {
				$subscriptions[] = $filterer->filter( $serializer->serialize( $s ), 'view', $schema );

				$upgrades[ $s->get_ID() ]   = array();
				$downgrades[ $s->get_ID() ] = array();

				foreach ( $s->get_available_upgrades() as $offer ) {
				    $upgrades[ $s->get_ID() ][] = $filterer->filter( $prorate->serialize( $offer ), 'view', $prorate_schema );
                }

				foreach ( $s->get_available_downgrades() as $offer ) {
					$downgrades[ $s->get_ID() ][] = $filterer->filter( $prorate->serialize( $offer ), 'view', $prorate_schema );
				}
			}
		} catch ( Exception $e ) {

		}
	}

	wp_localize_script( 'it-exchange-recurring-payments-purchases', 'ITExchangeRecurringPayments', array(
		'i18n'          => array(
			'updateSource' => __( 'Update Payment Method', 'LION' ),
			'save'         => __( 'Save', 'LION' ),
			'cancel'       => __( 'Cancel', 'LION' ),
			'cancelling'   => __( 'Cancelling', 'LION' ),
			'cannotCancel' => __( 'This subscription cannot be cancelled.', 'LION' ),
			'pausing'      => __( 'Pausing', 'LION' ),
			'cannotPause'  => __( 'This subscription cannot be paused.', 'LION' ),
			'resuming'     => __( 'Resuming', 'LION' ),
			'cannotResume' => __( 'This subscription cannot be resumed.', 'LION' ),
			'renew'        => __( 'Renew', 'LION' ),
			'reactivate'   => __( 'Reactivate', 'LION' ),
			'renewing'     => __( 'Renewing', 'LION' ),
			'cannotRenew'  => __( 'This subscription cannot be renewed.', 'LION' ),
            'changeMySubscription' => __( 'Change My Subscription', 'LION' ),
            'upgrade'      => __( 'Upgrade', 'LION' ),
            'downgrade'    => __( 'Downgrade', 'LION' ),
            'prorate'      => __( 'Prorate', 'LION' ),
		),
		'subscriptions' => $subscriptions,
        'upgrades'      => $upgrades,
        'downgrades'    => $downgrades,
	) );
}

add_action( 'wp_print_footer_scripts', 'it_exchange_recurring_payments_localize_purchases', 1 );

add_filter( 'it_exchange_using_child_transactions', '__return_true' );

/**
 * Function to modify the default purchases fields elements
 *
 * @since 1.0.0
 *
 * @param array $elements Elements being loaded by Theme API
 *
 * @return array $elements Modified elements array
 */
function it_exchange_recurring_payments_addon_content_purchases_fields_elements( $elements ) {
	$elements[] = 'payments';
	$elements[] = 'update-payment';
	$elements[] = 'pause-resume';
	$elements[] = 'renew';
	$elements[] = 'change-subscription';
	$elements[] = 'unsubscribe';
	$elements[] = 'expiration';

	return $elements;
}

add_filter( 'it_exchange_get_content_purchases_fields_elements', 'it_exchange_recurring_payments_addon_content_purchases_fields_elements' );

/**
 * Adds Recurring Payments templates directory to iThemes Exchange template path array
 *
 * @since 1.0.0
 *
 * @param array $possible_template_paths iThemes Exchange's template paths to check for templates
 * @param mixed $template_names          iThemes Exchange's template names
 *
 * @return array $possible_template_paths Modified iThemes Exchange's template paths to check for templates array
 */
function it_exchange_recurring_payments_addon_template_path( $possible_template_paths, $template_names ) {
	$possible_template_paths[] = dirname( __FILE__ ) . '/templates/';

	return $possible_template_paths;
}

add_filter( 'it_exchange_possible_template_paths', 'it_exchange_recurring_payments_addon_template_path', 10, 2 );

/**
 * Add fees when a product is added to the cart.
 *
 * @since 2.0.0
 *
 * @param ITE_Cart_Product $item
 * @param ITE_Cart         $cart
 */
function it_exchange_recurring_payments_on_add_product_to_cart( ITE_Cart_Product $item, ITE_Cart $cart ) {

	$product = $item->get_product();

	if ( ! $product->has_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
		return;
	}

	if ( ! $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
		return;
	}

	$trial_enabled = $product->get_feature( 'recurring-payments', array( 'setting' => 'trial-enabled' ) );

	if ( ! $trial_enabled ) {
		return;
	}

	if ( $product instanceof IT_Exchange_Membership && function_exists( 'it_exchange_is_customer_eligible_for_trial' ) ) {
		if ( ! it_exchange_is_customer_eligible_for_trial( $product, $cart->get_customer() ) ) {
			return;
		}
	}

	$fee = ITE_Fee_Line_Item::create(
		__( 'Free Trial', 'LION' ),
		$item->get_total() * - 1,
		true,
		false
	);
	$fee->set_param( 'is_free_trial', true );

	$item->add_item( $fee );
	$cart->get_repository()->save( $item );
}

add_action( 'it_exchange_add_product_to_cart', 'it_exchange_recurring_payments_on_add_product_to_cart', 10, 2 );

/**
 * Add fees when the prorate meta has been updated.
 *
 * @since 2.0.0
 *
 * @param string   $key
 * @param array    $value
 * @param ITE_Cart $cart
 */
function it_exchange_recurring_payments_add_credit_fees_on_meta( $key, $value, ITE_Cart $cart ) {

	if ( $key !== ITE_Prorate_Credit_Request::META ) {
		return;
	}

	foreach ( $value as $product_id => $_ ) {

		$item = $cart->get_items( 'product' )->filter( function ( \ITE_Cart_Product $cart_product ) use ( $product_id ) {
			return $cart_product->get_product()->ID == $product_id;
		} )->first();

		if ( $item ) {
			it_exchange_recurring_payments_add_credit_fees( $item, $cart );
			it_exchange_recurring_payments_add_free_days_fees( $item, $cart );
		}
	}
}

add_action( 'it_exchange_set_cart_meta', 'it_exchange_recurring_payments_add_credit_fees_on_meta', 10, 3 );

/**
 * Set child of for renewals.
 *
 * @since 2.0.0
 *
 * @param ITE_Gateway_Purchase_Request $request
 *
 * @return ITE_Gateway_Purchase_Request
 */
function it_exchange_recurring_payments_set_child_of_for_renewal( ITE_Gateway_Purchase_Request $request ) {

	$cart = $request->get_cart();

	if ( $cart->get_items( 'product' )->count() > 1 ) {
		return $request;
	}

	$item = $cart->get_items( 'product' )->having_param( 'is_manual_renewal' )->first();

	if ( ! $item ) {
		return $request;
	}

	$subscription_id = $item->get_param( 'is_manual_renewal' );

	if ( ! $subscription_id || ! $subscription = IT_Exchange_Subscription::get( $subscription_id ) ) {
		return $request;
	}

	$request->set_child_of( $subscription->get_transaction() );

	return $request;
}

add_filter( 'it_exchange_make_purchase_gateway_request', 'it_exchange_recurring_payments_set_child_of_for_renewal' );

/**
 * Disables multi item carts if viewing product with auto-renew enabled
 * because you cannot mix auto-renew prices with non-auto-renew prices in
 * payment gateways
 *
 * @since 1.0.0
 *
 * @param bool      $allowed Current status of multi-cart being allowed
 * @param \ITE_Cart $cart
 *
 * @return bool True or False if multi-cart is allowed
 */
function it_exchange_recurring_payments_multi_item_cart_allowed( $allowed, ITE_Cart $cart = null ) {
	if ( ! $allowed ) {
		return $allowed;
	}

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

	if ( ! $cart ) {
		return true;
	}

	foreach ( $cart->get_items( 'product' ) as $product ) {
		if ( $product->get_product()->supports_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			if ( $product->get_product()->has_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
				if ( $product->get_product()->supports_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
					if ( $product->get_product()->has_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) ) ) {
						return false;
					}
				}
			}
		}
	}

	return $allowed;
}

add_filter( 'it_exchange_multi_item_cart_allowed', 'it_exchange_recurring_payments_multi_item_cart_allowed', 10, 2 );

/**
 * Disables multi item products if viewing product with auto-renew enabled
 * because you cannot mix auto-renew prices with non-auto-renew prices in
 * payment gateways
 *
 * @since 1.0.0
 *
 * @param bool $allowed    Current status of multi-item-product being allowed
 * @param int  $product_id Product ID to check
 *
 * @return bool True or False if multi-item-product is allowed
 */
function it_exchange_recurring_payments_multi_item_product_allowed( $allowed, $product_id ) {
	if ( ! $allowed ) {
		return $allowed;
	}

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
 *
 * @param int            $transaction_id iThemes Exchange Transaction ID
 * @param \ITE_Cart|null $cart
 *
 * @return void
 */
function it_exchange_recurring_payments_addon_add_transaction( $transaction_id, ITE_Cart $cart = null ) {
	$transaction = it_exchange_get_transaction( $transaction_id );

	foreach ( $transaction->get_products() as $product ) {

		$product = it_exchange_get_product( $product['product_id'] );

		if ( $product->get_feature( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) ) ) {
			IT_Exchange_Subscription::create( $transaction, $product );
		}
	}

	$from = $transaction->order_date;
	it_exchange_recurring_payments_addon_update_expirations( $transaction, $from );

	if ( $cart && $cart->has_meta( ITE_Prorate_Credit_Request::META ) ) {
		foreach ( $cart->get_meta( ITE_Prorate_Credit_Request::META ) as $product_id => $_ ) {
			$product = it_exchange_get_product( $product_id );

			if ( $product && $request = ITE_Prorate_Credit_Request::get( $product, $cart ) ) {
				$request->cancel_provider();
			}
		}
	}
}

add_action( 'it_exchange_add_transaction_success', 'it_exchange_recurring_payments_addon_add_transaction', 0, 2 );

/**
 * Bump expirations when a child transaction occurs.
 *
 * @since 1.8
 *
 * @param int $transaction_id
 */
function it_exchange_recurring_payments_bump_expiration_on_child_transaction( $transaction_id ) {

	$transaction = it_exchange_get_transaction( $transaction_id );

	if ( ! $transaction ) {
		return;
	}

	$parent = $transaction->parent;

	if ( ! $parent ) {
		return;
	}

	if ( $transaction->is_cleared_for_delivery() ) {
		it_exchange_recurring_payments_addon_update_expirations( $parent );
	}

	try {
		$subscription = it_exchange_get_subscription_by_transaction( $parent );

		if ( $subscription && $subscription->are_occurrences_limited() ) {
			$subscription->decrement_remaining_occurrences();
		}
	} catch ( Exception $e ) {

	}

}

add_action( 'it_exchange_add_child_transaction_success', 'it_exchange_recurring_payments_bump_expiration_on_child_transaction' );

/**
 * Set non-auto-renewing subscriptions to active when they are created.
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_set_non_auto_renewing_subscriptions_to_active( IT_Exchange_Subscription $subscription ) {

	if ( it_exchange_transaction_is_cleared_for_delivery( $subscription->get_transaction() ) && ! $subscription->is_auto_renewing() ) {

		$method = $subscription->get_transaction()->transaction_method;

		/**
		 * Filter whether to auto-activate non auto-renewing subscriptions.
		 *
		 * The dynamic portion of this hook, `$method`, refers to the transaction method slug.
		 * For example, offline-payments.
		 *
		 * @param bool                     $activate
		 * @param IT_Exchange_Subscription $subscription
		 */
		if ( apply_filters( "it_exchange_auto_activate_non_renewing_{$method}_subscriptions", true, $subscription ) ) {
			add_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
			$subscription->set_status( IT_Exchange_Subscription::STATUS_ACTIVE );
			remove_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
		}
	}
}

add_action( 'it_exchange_subscription_created', 'it_exchange_recurring_payments_set_non_auto_renewing_subscriptions_to_active' );

/**
 * Mark subscriptions as active when the transaction is marked as cleared for delivery.
 *
 * @since 1.35.4
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string                  $old_status
 * @param bool                    $old_cleared
 */
function it_exchange_recurring_payments_set_non_auto_renewing_subscriptions_as_active_on_clear( $transaction, $old_status, $old_cleared ) {

	$new_cleared = it_exchange_transaction_is_cleared_for_delivery( $transaction );
	$method      = it_exchange_get_transaction_method( $transaction );

	if ( $new_cleared && ! $old_cleared ) {

		$subs = it_exchange_get_transaction_subscriptions( $transaction );

		foreach ( $subs as $subscription ) {
			$sub_status = $subscription->get_status();

			if ( empty( $sub_status ) ) {

				// This filter is documented in lib/required-hooks.php
				if ( apply_filters( "it_exchange_auto_activate_non_renewing_{$method}_subscriptions", true, $subscription ) ) {
					add_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
					$subscription->set_status( IT_Exchange_Subscription::STATUS_ACTIVE );
					remove_filter( 'it_exchange_subscriber_status_activity_use_gateway_actor', '__return_true' );
				}
			}
		}
	}

}

add_action( 'it_exchange_update_transaction_status', 'it_exchange_zero_sum_mark_subscriptions_as_active_on_clear', 10, 3 );

/**
 * Update the status when the status hook is fired.
 *
 * This really is for BC as IT_Exchange_Subscription::set_status() should always be used.
 *
 * @since 1.8
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string                  $sub_id
 * @param string                  $subscriber_status
 */
function it_exchange_recurring_payments_update_status( $transaction, $sub_id, $subscriber_status ) {

	$subscription = it_exchange_get_subscription_by_transaction( it_exchange_get_transaction( $transaction ) );

	try {
		$subscription->set_status( $subscriber_status );
	} catch ( InvalidArgumentException $e ) {

	}
}

add_action( 'it_exchange_update_transaction_subscription_status', 'it_exchange_recurring_payments_update_status', 10, 3 );

/**
 * Update all subscription's associated with a transaction when the status changes.
 *
 * @since 1.8
 *
 * @param IT_Exchange_Transaction $transaction
 * @param string                  $old_status
 * @param bool                    $old_status_cleared
 * @param string                  $new_status
 */
function it_exchange_update_subscription_status_on_transaction_status_change( $transaction, $old_status, $old_status_cleared, $new_status ) {

	$new_cleared = it_exchange_transaction_is_cleared_for_delivery( $transaction );

	if ( $new_cleared && ! $old_status_cleared ) {
		$sub_status = IT_Exchange_Subscription::STATUS_ACTIVE;
	} elseif ( ! $new_cleared && $old_status_cleared ) {
		$sub_status = IT_Exchange_Subscription::STATUS_CANCELLED;
	} else {
		return;
	}

	$subs = it_exchange_get_transaction_subscriptions( $transaction );

	foreach ( $subs as $sub ) {
		if ( $sub->get_status() !== $sub_status ) {
			$sub->set_status( $sub_status );
		}
	}
}

add_action( 'it_exchange_update_transaction_status', 'it_exchange_update_subscription_status_on_transaction_status_change', 10, 4 );

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
 * @since  1.0.0
 *
 * @params array $args get_posts Arguments
 *
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
		$wpdb->prepare( "
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE meta_key LIKE %s
			  AND meta_value != '' AND meta_value < %d",
			'_it_exchange_transaction_subscription_expires_%', time() )
	);

	IT_Exchange_Recurring_Payments_Email::batch();

	foreach ( $results as $result ) {

		$product_id  = str_replace( '_it_exchange_transaction_subscription_expires_', '', $result->meta_key );
		$transaction = it_exchange_get_transaction( $result->post_id );

		if ( ! $transaction ) {
			continue;
		}

		if ( $expired = apply_filters( 'it_exchange_recurring_payments_handle_expired', true, $product_id, $transaction ) ) {

			$s = it_exchange_get_subscription_by_transaction( $transaction, it_exchange_get_product( $product_id ) );

			if ( $s->is_status( $s::STATUS_ACTIVE ) ) {
				$s->set_status( $s::STATUS_DEACTIVATED );
				$s->mark_expired();
			} elseif ( $s->is_status( $s::STATUS_COMPLIMENTARY, $s::STATUS_PAUSED ) && $s->is_auto_renewing() ) {
				$s->bump_expiration_date();
			} else {
				$s->mark_expired();
			}
		}
	}

	IT_Exchange_Recurring_Payments_Email::batch( false );
}

/**
 * Add an activity item when a subscription has been paused.
 *
 * @since 2.0.0
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_add_activity_on_pause( IT_Exchange_Subscription $subscription ) {

	$message = __( 'Subscription paused.', 'LION' );

	if ( $subscription->get_paused_by() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( $subscription->get_paused_by()->wp_user );
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} else {
		$actor = null;
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'status' );
	$builder->set_description( $message );

	if ( $actor ) {
		$builder->set_actor( $actor );
	}

	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_pause_subscription', 'it_exchange_recurring_payments_add_activity_on_pause' );

/**
 * Add an activity item when a subscription has been resumed.
 *
 * @since 2.0.0
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_add_activity_on_resume( IT_Exchange_Subscription $subscription ) {

	$message = __( 'Subscription resumed.', 'LION' );

	if ( $subscription->get_resumed_by() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( $subscription->get_resumed_by()->wp_user );
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} else {
		$actor = null;
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'status' );
	$builder->set_description( $message );

	if ( $actor ) {
		$builder->set_actor( $actor );
	}

	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_resume_subscription', 'it_exchange_recurring_payments_add_activity_on_resume' );

/**
 * Add an activity item when a subscription has been cancelled.
 *
 * @since 2.0.0
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_add_activity_on_cancellation( IT_Exchange_Subscription $subscription ) {

	$message = __( 'Subscription cancelled.', 'LION' );

	if ( $subscription->get_cancellation_reason() ) {
		/* translators: %s user-provided cancellation reason */
		$message .= ' ' . sprintf( __( 'Reason: %s', 'LION' ), $subscription->get_cancellation_reason() );
	}

	if ( $subscription->is_pausing() ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( $subscription->get_transaction()->get_gateway()->get_addon() );
	} elseif ( $subscription->get_cancelled_by() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( $subscription->get_cancelled_by()->wp_user );
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} else {
		$actor = null;
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'status' );
	$builder->set_description( $message );

	if ( $actor ) {
		$builder->set_actor( $actor );
	}

	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_cancel_subscription', 'it_exchange_recurring_payments_add_activity_on_cancellation' );

/**
 * Add an activity item when a subscription has been compled.
 *
 * @since 2.0.0
 *
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_add_activity_on_comp( IT_Exchange_Subscription $subscription ) {

	$message = __( 'Subscription comped.', 'LION' );

	if ( $subscription->get_comp_reason() ) {
		/* translators: %s user-provided comp reason */
		$message .= ' ' . sprintf( __( 'Reason: %s', 'LION' ), $subscription->get_comp_reason() );
	}

	if ( $subscription->get_comped_by() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( $subscription->get_comped_by()->wp_user );
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} else {
		$actor = null;
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'status' );
	$builder->set_description( $message );

	if ( $actor ) {
		$builder->set_actor( $actor );
	}

	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_comp_subscription', 'it_exchange_recurring_payments_add_activity_on_comp' );

/**
 * Add an activity item when the subscriber status changes.
 *
 * @since 1.8.0
 *
 * @param string                   $status
 * @param string                   $old_status
 * @param IT_Exchange_Subscription $subscription
 */
function it_exchange_recurring_payments_add_activity_on_subscriber_status( $status, $old_status, IT_Exchange_Subscription $subscription ) {

	if ( $status === $old_status ) {
		return;
	}

	if ( $subscription->is_cancelling() || $subscription->is_resuming() || $subscription->is_pausing() || $subscription->is_comping() ) {
		return;
	}

	$labels = IT_Exchange_Subscription::get_statuses();

	$status_label     = isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Unknown', 'LION' );
	$old_status_label = isset( $labels[ $old_status ] ) ? $labels[ $old_status ] : __( 'Unknown', 'LION' );

	if ( $old_status ) {
		$message = sprintf( __( 'Subscriber status changed from %s to %s.', 'LION' ),
			$old_status_label, $status_label
		);
	} else {
		$message = sprintf( __( 'Subscriber status changed to %s.', 'LION' ), $status_label );
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'status' );
	$builder->set_description( $message );

	/**
	 * Filter whether to force using a gateway actor.
	 *
	 * @since 1.8.4
	 *
	 * @param bool                     $use_gateway
	 * @param IT_Exchange_Subscription $subscription
	 * @param string                   $status
	 * @param string                   $old_status
	 */
	$use_gateway = apply_filters( 'it_exchange_subscriber_status_activity_use_gateway_actor', false, $subscription, $status, $old_status );

	if ( $use_gateway ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( it_exchange_get_addon( $subscription->get_transaction()->get_method() ) );
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} elseif ( ( $wh = it_exchange_doing_webhook() ) && ( $addon = it_exchange_get_addon( $wh ) ) ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( $addon );
	} else {
		$actor = new IT_Exchange_Txn_Activity_Site_Actor();
	}

	$builder->set_actor( $actor );
	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_transition_subscription_status', 'it_exchange_recurring_payments_add_activity_on_subscriber_status', 10, 3 );

if ( has_action( 'it_exchange_recurring_payments_addon_update_transaction_subscriber_status', 'it_exchange_add_activity_on_subscriber_status' ) ) {
	remove_action(
		'it_exchange_recurring_payments_addon_update_transaction_subscriber_status',
		'it_exchange_add_activity_on_subscriber_status', 10
	);
}

/**
 * Add an activity item when the subscription
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Subscription $subscription
 * @param DateTime                 $previous
 */
function it_exchange_recurring_payments_add_activity_on_expiration_date( IT_Exchange_Subscription $subscription, DateTime $previous = null ) {

	$format  = get_option( 'date_format' );
	$current = $subscription->get_expiry_date();

	if ( $previous && $current ) {
		$message = sprintf(
			__( 'Subscription expiration date updated to %s from %s.', 'LION' ),
			date_i18n( $format, $current->format( 'U' ) ),
			date_i18n( $format, $previous->format( 'U' ) )
		);
	} elseif ( $previous )  {
		$message = sprintf(
			__( 'Subscription expiration date updated to never from %s.', 'LION' ),
			date_i18n( $format, $previous->format( 'U' ) )
		);
	} elseif ( $current ) {
		$message = sprintf(
			__( 'Subscription expiration date updated to %s.', 'LION' ),
			date_i18n( $format, $current->format( 'U' ) )
		);
	} else {
		return;
	}

	$builder = new IT_Exchange_Txn_Activity_Builder( $subscription->get_transaction(), 'subscription-expiry' );
	$builder->set_description( $message );

	/**
	 * Filter whether to force using a gateway actor.
	 *
	 * @since 1.8.4
	 *
	 * @param bool                     $use_gateway
	 * @param IT_Exchange_Subscription $subscription
	 * @param DateTime|null            $previous
	 */
	$use_gateway = apply_filters( 'it_exchange_subscriber_expiration_date_activity_use_gateway_actor', false, $subscription, $previous );

	if ( $use_gateway ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( it_exchange_get_addon( $subscription->get_transaction()->transaction_method ) );
	} elseif ( doing_action( 'it_exchange_add_transaction_success' ) ) {
		$actor = new IT_Exchange_Txn_Activity_Site_Actor();
	} elseif ( is_user_logged_in() ) {
		$actor = new IT_Exchange_Txn_Activity_User_Actor( wp_get_current_user() );
	} elseif ( ( $wh = it_exchange_doing_webhook() ) && ( $addon = it_exchange_get_addon( $wh ) ) ) {
		$actor = new IT_Exchange_Txn_Activity_Gateway_Actor( $addon );
	} else {
		$actor = new IT_Exchange_Txn_Activity_Site_Actor();
	}

	$builder->set_actor( $actor );
	$builder->build( it_exchange_get_txn_activity_factory() );
}

add_action( 'it_exchange_subscription_set_expiry_date', 'it_exchange_recurring_payments_add_activity_on_expiration_date', 10, 2 );

/**
 * Register custom activity types.
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Txn_Activity_Factory $factory
 */
function it_exchange_recurring_payments_register_activity_types( IT_Exchange_Txn_Activity_Factory $factory ) {

	$factory->register( 'subscription-expiry', __( 'Subscription Expiry', 'LION' ), array(
		'IT_Exchange_Txn_Subscription_Expiry_Activity',
		'make'
	) );
}

add_action( 'it_exchange_get_txn_activity_factory', 'it_exchange_recurring_payments_register_activity_types' );

/**
 * Modifies the Transaction Payments screen for recurring payments
 * Adds recurring type to product title
 *
 * @since 1.0.1
 *
 * @param object $post    Post Object
 * @param array  $product Cart Product
 *
 * @return void
 */
function it_exchange_recurring_payments_transaction_print_metabox_after_product_feature_title( $post, $product ) {

	$transaction = it_exchange_get_transaction( $post->ID );

	try {
		$subscription = it_exchange_get_subscription_by_transaction( $transaction, it_exchange_get_product( $product['product_id'] ) );
	} catch ( Exception $e ) {

		$time = __( 'forever', 'LION' );
		echo '<span class="recurring-product-type">' . $time . '</span>';

		return;
	}

	if ( $subscription && $subscription->is_auto_renewing() ) {
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
	$transaction        = it_exchange_get_transaction( $post->ID );
	$transaction_method = it_exchange_get_transaction_method( $transaction->ID );

	if ( ( $gateway = ITE_Gateways::get( $transaction_method ) ) && $gateway->can_handle( 'cancel-subscription' ) ) {
		return;
	}

	do_action( 'it_exchange_after_payment_details_cancel_url_for_' . $transaction_method, $transaction );
}

add_action( 'it_exchange_after_payment_details', 'it_exchange_recurring_payments_addon_after_payment_details' );

/**
 * Returns base price with recurring label
 *
 * @since CHANGEME
 *
 * @param string $base_price
 * @param int    $product_id iThemes Exchange Product ID
 *
 * @return string iThemes Exchange recurring label
 */
function it_exchange_recurring_payments_api_theme_product_base_price( $base_price, $product_id ) {
	return it_exchange_recurring_payments_addon_recurring_label( $product_id, true, $base_price ) ?: $base_price;
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

	if ( ! $transaction || $transaction->has_parent() ) {
		return;
	}

	$subs = it_exchange_get_transaction_subscriptions( $transaction );

	if ( ! $subs ) {
		return;
	}

	$df        = 'Y-m-d';
	$jquery_df = it_exchange_php_date_format_to_jquery_datepicker_format( $df );
	?>

	<div class="transaction-recurring-options clearfix spacing-wrapper bottom-border">

		<h3><?php _e( 'Subscription Settings', 'LION' ); ?></h3>

		<?php foreach ( $subs as $subscription ) :

			$pid = $subscription->get_product()->ID;

			$sub_id = $subscription->get_subscriber_id();
			$status = $subscription->get_status();

			$expires = $subscription->get_expiry_date();
			$expires = $expires ? $expires->format( $df ) : '';
			$route   = rest_url( "it_exchange/v1/subscriptions/{$subscription->get_transaction()->ID}:{$subscription->get_product()->ID}/" );
			$route   = wp_nonce_url( $route, 'wp_rest' );
			?>

			<div class="recurring-options" data-route="<?php echo esc_attr( $route ); ?>"
			     data-product="<?php echo $subscription->get_product()->ID; ?>">

				<?php if ( count( $subs ) > 1 ): ?>
					<h4><?php echo $subscription->get_product()->post_title; ?></h4>
				<?php endif; ?>

				<?php if ( $subscription->requires_subscriber_id() ): ?>
					<p>
						<label for="rp-sub-id-<?php echo $pid; ?>">
							<?php _e( 'Subscription ID', 'LION' ); ?>
							<span class="tip"
							      title="<?php _e( 'This is the Subscription ID from the Payment Processor.', 'LION' ); ?>">i</span>
						</label>

						<input type="text" id="rp-sub-id-<?php echo $pid; ?>" name="rp-sub-id[<?php echo $pid; ?>]"
						       value="<?php echo $sub_id; ?>"/>
					</p>
				<?php endif; ?>

				<?php if ( $subscription->are_occurrences_limited() ) : ?>
					<p>
						<?php printf( __( 'Remaining Occurrences: %d', 'LION' ), $subscription->get_remaining_occurrences() ); ?>
					</p>
				<?php endif; ?>

				<p>
					<label for="rp-status-<?php echo $pid; ?>">
						<?php _e( 'Subscription Status', 'LION' ); ?>
						<span class="tip"
						      title="<?php _e( 'This is the status of the subscription in Exchange, not the transaction. It will not change the status in the Payment gateway.', 'LION' ); ?>">i</span>
					</label>

					<select id="rp-status-<?php echo $pid; ?>" name="rp-status[<?php echo $pid; ?>]" class="rp-status">

						<option value=""></option>

						<?php foreach ( IT_Exchange_Subscription::get_statuses() as $slug => $label ): ?>
							<option value="<?php echo $slug; ?>" <?php selected( $slug, $status ); ?> <?php disabled( $subscription->can_status_be_manually_toggled_to( $slug ), false ); ?>>
								<?php echo $label; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<label for="rp-expires-<?php echo $pid; ?>">
						<?php _e( 'Subscription Expiration', 'LION' ); ?>
						<span class="tip"
						      title="<?php _e( 'Set this to change what Exchange sees as the customer expiration date, the Payment processor will still send webhooks if the payment expires or if new payments come through.', 'LION' ); ?>">i</span>
					</label>

					<input type="text" id="rp-expires-<?php echo $pid; ?>" class="datepicker rp-expires"
					       name="rp-expires[<?php echo $pid; ?>]" value="<?php echo $expires; ?>"/>
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
 * Render the Pause, Resume, and Cancel subscription buttons.
 *
 * @since 2.0.0
 *
 * @param IT_Exchange_Transaction $transaction
 */
function it_exchange_recurring_payments_render_admin_subscription_actions( IT_Exchange_Transaction $transaction ) {

	try {
		$subscription = it_exchange_get_subscription_by_transaction( $transaction );
	} catch ( Exception $e ) {
		return;
	}

	$sub_id = $subscription->get_ID();

	// Set context to 'edit' so raw subscription status is returned in response.
	$pause  = add_query_arg( 'context', 'edit', wp_nonce_url( rest_url( "it_exchange/v1/subscriptions/{$sub_id}/pause" ), 'wp_rest' ) );
	$resume = add_query_arg( 'context', 'edit', wp_nonce_url( rest_url( "it_exchange/v1/subscriptions/{$sub_id}/resume" ), 'wp_rest' ) );
	$cancel = add_query_arg( 'context', 'edit', wp_nonce_url( rest_url( "it_exchange/v1/subscriptions/{$sub_id}/cancel" ), 'wp_rest' ) );
	$comp   = add_query_arg( 'context', 'edit', wp_nonce_url( rest_url( "it_exchange/v1/subscriptions/{$sub_id}/comp" ), 'wp_rest' ) );

	if ( $subscription->can_be_paused() ) : ?>
		<button class="button button-secondary right" id="pause-subscription"
		        data-route="<?php echo esc_attr( $pause ); ?>">
			<?php _e( 'Pause', 'LION' ); ?>
		</button>
	<?php endif;

	if ( $subscription->can_be_resumed() ) : ?>
		<button class="button button-secondary right" id="resume-subscription"
		        data-route="<?php echo esc_attr( $resume ); ?>">
			<?php _e( 'Resume', 'LION' ); ?>
		</button>
	<?php endif;

	if ( $subscription->can_be_cancelled() ) : ?>
		<button class="button button-secondary right" id="cancel-subscription"
		        data-route="<?php echo esc_attr( $cancel ); ?>">
			<?php _e( 'Cancel', 'LION' ); ?>
		</button>
	<?php endif;

	if ( $subscription->can_be_comped() ) : ?>
        <button class="button button-secondary right" id="comp-subscription"
                data-route="<?php echo esc_attr( $comp ); ?>">
			<?php _e( 'Comp', 'LION' ); ?>
        </button>
	<?php endif;
}

add_action( 'it_exchange_after_payment_refund', 'it_exchange_recurring_payments_render_admin_subscription_actions' );

/**
 * Render admin subscription actions detail.
 *
 * @since 2.0.0
 *
 * @param IT_Exchange_Transaction $transaction
 */
function it_exchange_recurring_payments_render_admin_subscription_actions_detail( IT_Exchange_Transaction $transaction ) {

	?>
	<div class="hidden spacing-wrapper bottom-border clearfix" id="subscription-pause-manager"
	     style="background: #F5F5F5;">

		<button class="button button-secondary left" id="cancel-pause-subscription">
			<?php _e( 'Back', 'it-l10n-ithemes-exchange' ); ?>
		</button>

		<button class="button button-primary right" id="confirm-pause-subscription" style="margin-left: 10px;">
			<?php _e( 'Pause Subscription', 'it-l10n' ) ?>
		</button>
	</div>

	<div class="hidden spacing-wrapper bottom-border clearfix" id="subscription-resume-manager"
	     style="background: #F5F5F5;">

		<button class="button button-secondary left" id="cancel-resume-subscription">
			<?php _e( 'Back', 'it-l10n-ithemes-exchange' ); ?>
		</button>

		<button class="button button-primary right" id="confirm-resume-subscription" style="margin-left: 10px;">
			<?php _e( 'Resume Subscription', 'it-l10n' ) ?>
		</button>
	</div>

	<div class="hidden spacing-wrapper bottom-border clearfix" id="subscription-cancellation-manager"
	     style="background: #F5F5F5;">

		<button class="button button-secondary left" id="cancel-cancel-subscription">
			<?php _e( 'Back', 'it-l10n-ithemes-exchange' ); ?>
		</button>

		<button class="button button-primary right" id="confirm-cancel-subscription" style="margin-left: 10px;">
			<?php _e( 'Cancel Subscription', 'it-l10n' ) ?>
		</button>

		<input type="text" placeholder="<?php esc_attr_e( 'Reason (Optional)', 'LION' ); ?>"
		       id="cancel-subscription-reason"
		       class="right" style="text-align: left"/>
	</div>

    <div class="hidden spacing-wrapper bottom-border clearfix" id="subscription-comp-manager"
         style="background: #F5F5F5;">

        <button class="button button-secondary left" id="cancel-comp-subscription">
			<?php _e( 'Back', 'it-l10n-ithemes-exchange' ); ?>
        </button>

        <button class="button button-primary right" id="confirm-comp-subscription" style="margin-left: 10px;">
			<?php _e( 'Comp Subscription', 'it-l10n' ) ?>
        </button>

        <input type="text" placeholder="<?php esc_attr_e( 'Reason (Optional)', 'LION' ); ?>"
               id="comp-subscription-reason"
               class="right" style="text-align: left"/>
    </div>
	<?php
}

add_action( 'it_exchange_after_payment_actions', 'it_exchange_recurring_payments_render_admin_subscription_actions_detail' );

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
 * Register upgrade routines.
 *
 * @since 1.8.4
 *
 * @param IT_Exchange_Upgrader $upgrader
 */
function it_exchange_recurring_payments_register_upgrades( IT_Exchange_Upgrader $upgrader ) {
	$upgrader->add_upgrade( new IT_Exchange_Recurring_Payments_Zero_Sum_Checkout_Upgrade() );
	$upgrader->add_upgrade( new IT_Exchange_Recurring_Payments_Non_Auto_Renewing() );
}

add_action( 'it_exchange_register_upgrades', 'it_exchange_recurring_payments_register_upgrades' );

/**
 * AJAX to add new member relatives
 *
 * @since 2.0.0
 */
function it_exchange_recurring_payments_addon_ajax_add_subscription_child() {

	$return = '';

	if ( ! empty( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['product_id'] ) ) {
		$child_ids = array();

		if ( ! empty( $_REQUEST['child_ids'] ) ) {
			foreach ( $_REQUEST['child_ids'] as $child_id ) {
				if ( 'it-exchange-subscription-child-ids[]' === $child_id['name'] ) {
					$child_ids[] = $child_id['value'];
				}
			}
		}

		if ( ! in_array( $_REQUEST['product_id'], $child_ids ) ) {
			$child_ids[] = $_REQUEST['product_id'];
		}

		$return = it_exchange_recurring_payments_addon_display_subscription_hierarchy( $child_ids, array( 'echo' => false ) );
	}

	die( $return );
}

add_action( 'wp_ajax_it-exchange-recurring-payments-addon-add-subscription-child', 'it_exchange_recurring_payments_addon_ajax_add_subscription_child' );

/**
 * AJAX to add new member relatives
 *
 * @since 2.0.0
 */
function it_exchange_recurring_payments_addon_ajax_add_subscription_parent() {

	$return = '';

	if ( ! empty( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['product_id'] ) ) {
		$parent_ids = array();
		if ( ! empty( $_REQUEST['parent_ids'] ) ) {
			foreach ( $_REQUEST['parent_ids'] as $parent_id ) {
				if ( 'it-exchange-subscription-parent-ids[]' === $parent_id['name'] ) {
					$parent_ids[] = $parent_id['value'];
				}
			}
		}

		if ( ! in_array( $_REQUEST['product_id'], $parent_ids ) ) {
			$parent_ids[] = $_REQUEST['product_id'];
		}

		$return .= '<ul>';
		foreach ( $parent_ids as $parent_id ) {
			$return .= '<li data-parent-id="' . $parent_id . '">';
			$return .= '<div class="inner-wrapper">' . get_the_title( $parent_id ) . ' <a data-membership-id="' . $parent_id . '" class="it-exchange-subscription-addon-delete-subscription-parent it-exchange-remove-item">x</a>';
			$return .= '<input type="hidden" name="it-exchange-subscription-parent-ids[]" value="' . $parent_id . '" /></div>';
			$return .= '</li>';
		}
		$return .= '</ul>';
	}

	die( $return );
}

add_action( 'wp_ajax_it-exchange-recurring-payments-addon-add-subscription-parent', 'it_exchange_recurring_payments_addon_ajax_add_subscription_parent' );

/**
 * Make a pause subscription request.
 *
 * @since 2.0.0
 *
 * @param null  $_
 * @param array $args
 *
 * @return ITE_Gateway_Request
 */
function it_exchange_recurring_payments_make_pause_subscription_request( $_, array $args ) {

	if ( empty( $args['subscription'] ) || ! $args['subscription'] instanceof IT_Exchange_Subscription ) {
		throw new InvalidArgumentException( 'Invalid `subscription` option.' );
	}

	if ( empty( $args['paused_by'] ) ) {
		$paused_by = it_exchange_get_current_customer() ?: null;
	} else {
		$paused_by = it_exchange_get_customer( $args['paused_by'] );

		if ( ! $paused_by ) {
			throw new InvalidArgumentException( 'Invalid `paused_by` option.' );
		}
	}

	$request = new ITE_Pause_Subscription_Request( $args['subscription'], $paused_by );

	return $request;
}

add_filter( 'it_exchange_make_pause-subscription_gateway_request', 'it_exchange_recurring_payments_make_pause_subscription_request', 10, 2 );

/**
 * Make a resume subscription request.
 *
 * @since 2.0.0
 *
 * @param null  $_
 * @param array $args
 *
 * @return ITE_Gateway_Request
 */
function it_exchange_recurring_payments_make_resume_subscription_request( $_, array $args ) {

	if ( empty( $args['subscription'] ) || ! $args['subscription'] instanceof IT_Exchange_Subscription ) {
		throw new InvalidArgumentException( 'Invalid `subscription` option.' );
	}

	if ( empty( $args['resumed_by'] ) ) {
		$resumed_by = it_exchange_get_current_customer() ?: null;
	} else {
		$resumed_by = it_exchange_get_customer( $args['resumed_by'] );

		if ( ! $resumed_by ) {
			throw new InvalidArgumentException( 'Invalid `resumed_by` option.' );
		}
	}

	$request = new ITE_Resume_Subscription_Request( $args['subscription'], $resumed_by );

	return $request;
}

add_filter( 'it_exchange_make_resume-subscription_gateway_request', 'it_exchange_recurring_payments_make_resume_subscription_request', 10, 2 );

/**
 * Make a cancel subscription request.
 *
 * @since 2.0.0
 *
 * @param null  $_
 * @param array $args
 *
 * @return ITE_Gateway_Request
 */
function it_exchange_recurring_payments_make_cancel_subscription_request( $_, array $args ) {

	if ( empty( $args['subscription'] ) || ! $args['subscription'] instanceof IT_Exchange_Subscription ) {
		throw new InvalidArgumentException( 'Invalid `subscription` option.' );
	}

	$reason = empty( $args['reason'] ) ? '' : trim( $args['reason'] );

	if ( empty( $args['cancelled_by'] ) ) {
		$canceled_by = it_exchange_get_current_customer() ?: null;
	} else {
		$canceled_by = it_exchange_get_customer( $args['cancelled_by'] );

		if ( ! $canceled_by ) {
			throw new InvalidArgumentException( 'Invalid `cancelled_by` option.' );
		}
	}

	$request = new ITE_Cancel_Subscription_Request( $args['subscription'], $reason, $canceled_by );

	if ( isset( $args['at_period_end'] ) ) {
		$request->set_at_period_end( $args['at_period_end'] );
	}

	if ( isset( $args['set_status'] ) ) {
		$request->do_set_status( (bool) $args['set_status'] );
	}

	return $request;
}

add_filter( 'it_exchange_make_cancel-subscription_gateway_request', 'it_exchange_recurring_payments_make_cancel_subscription_request', 10, 2 );

/**
 * Make an update subscription payment method request.
 *
 * @since 2.0.0
 *
 * @param null                        $_
 * @param array                       $args
 * @param string                      $__
 * @param ITE_Gateway_Request_Factory $factory
 *
 * @return ITE_Update_Subscription_Payment_Method_Request
 */
function it_exchange_recurring_payments_make_update_subscription_payment_method_request( $_, $args, $__, ITE_Gateway_Request_Factory $factory ) {

	if ( isset( $args['subscription'] ) ) {
		if ( is_scalar( $args['subscription'] ) ) {
			$subscription = IT_Exchange_Subscription::get( $args['subscription'] );
		} else {
			$subscription = $args['subscription'];
		}
	}

	if ( ! isset( $subscription ) || ! $subscription instanceof IT_Exchange_Subscription ) {
		throw new InvalidArgumentException( 'Invalid `subscription` option' );
	}

	$request = new ITE_Update_Subscription_Payment_Method_Request( $subscription );

	if ( ! empty( $args['card'] ) ) {
		$card = $args['card'];

		if ( is_array( $card ) ) {
			$card = $factory->build_card( $card );
		}

		if ( ! $card instanceof ITE_Gateway_Card ) {
			throw new InvalidArgumentException( 'Invalid `card` option.' );
		}

		$request->set_card( $args['card'] );
	}

	if ( ! empty( $args['token'] ) ) {
		$token = $args['token'];

		if ( is_int( $token ) ) {
			$token = ITE_Payment_Token::get( $token );
		}

		if ( ! $token instanceof ITE_Payment_Token ) {
			throw new InvalidArgumentException( 'Invalid `token` option.' );
		}

		$request->set_payment_token( $token );
	}

	if ( ! empty( $args['tokenize'] ) ) {

		if ( ! is_object( $args['tokenize'] ) ) {
			$tokenize = $factory->make( 'tokenize', array(
				'source'   => $args['tokenize'],
				'customer' => $subscription->get_customer()
			) );
		} else {
			$tokenize = $args['tokenize'];
		}

		if ( ! $tokenize instanceof ITE_Gateway_Tokenize_Request ) {
			throw new InvalidArgumentException( 'Invalid `tokenize` option.' );
		}

		$request->set_tokenize( $tokenize );
	}

	return $request;
}

add_filter(
	'it_exchange_make_update-subscription-payment-method_gateway_request',
	'it_exchange_recurring_payments_make_update_subscription_payment_method_request',
	10,
	4
);

/**
 * Decorate the purchase request to add prorate information.
 *
 * @since 2.0.0
 *
 * @param \ITE_Gateway_Purchase_Request $request
 *
 * @return \ITE_Gateway_Purchase_Request
 */
function it_exchange_recurring_payments_decorate_purchase_request( ITE_Gateway_Purchase_Request $request ) {
	return new ITE_Gateway_Prorate_Purchase_Request( $request );
}

add_filter( 'it_exchange_make_purchase_gateway_request', 'it_exchange_recurring_payments_decorate_purchase_request' );

/**
 * Map meta capabilities.
 *
 * @since 2.0.0
 *
 * @param array  $caps    Primitive capabilities required.
 * @param string $cap     Meta capability requested.
 * @param int    $user_id User ID testing against.
 * @param array  $args    Additional arguments. `$args[0]` typically contains the object ID.
 *
 * @return array
 */
function it_exchange_recurring_payments_map_meta_cap( $caps, $cap, $user_id, $args ) {

	switch ( $cap ) {
		case 'it_pause_subscription':

			if ( empty( $args[0] ) ) {
				return array( 'do_not_allow' );
			}

			$s = it_exchange_get_subscription( $args[0] );

			if ( ! $s->can_be_paused() ) {
				return array( 'do_not_allow' );
			}

			$txn_id = $s->get_transaction()->get_ID();

			if ( user_can( $user_id, 'read_it_transaction', $txn_id ) ) {
				if ( ! it_exchange_allow_customers_to_pause_subscriptions() ) {
					return array( 'do_not_allow' );
				}

				$limit = it_exchange_customer_pause_subscription_limit();

				if ( $limit !== false && $limit <= $s->get_number_of_pauses() ) {
					return array( 'do_not_allow' );
				}

				return array();
			}

			if ( user_can( $user_id, 'edit_it_transaction', $txn_id ) ) {
				return array();
			}

			return array( 'do_not_allow' );

		case 'it_resume_subscription':

			if ( empty( $args[0] ) ) {
				return array( 'do_not_allow' );
			}

			$s = it_exchange_get_subscription( $args[0] );

			if ( ! $s->can_be_resumed() ) {
				return array( 'do_not_allow' );
			}

			$txn_id = $s->get_transaction()->get_ID();

			if ( it_exchange_allow_customers_to_pause_subscriptions() && user_can( $user_id, 'read_it_transaction', $txn_id ) ) {
				return array();
			} elseif ( user_can( $user_id, 'edit_it_transaction', $txn_id ) ) {
				return array();
			}

			return array( 'do_not_allow' );

		case 'it_cancel_subscription':

			if ( empty( $args[0] ) ) {
				return array( 'do_not_allow' );
			}

			$s = it_exchange_get_subscription( $args[0] );

			if ( ! $s->can_be_cancelled() ) {
				return array( 'do_not_allow' );
			}

			$txn_id = $s->get_transaction()->get_ID();

			if ( user_can( $user_id, 'read_it_transaction', $txn_id ) ) {
				return array();
			}

			return array( 'do_not_allow' );

		case 'it_comp_subscription':

			if ( empty( $args[0] ) ) {
				return array( 'do_not_allow' );
			}

			$s = it_exchange_get_subscription( $args[0] );

			if ( ! $s->can_be_comped() ) {
				return array( 'do_not_allow' );
			}

			if ( user_can( $user_id, 'edit_it_transaction', $s->get_transaction() ) ) {
				return array();
			}

			return array( 'do_not_allow' );
	}

	return $caps;
}

add_filter( 'map_meta_cap', 'it_exchange_recurring_payments_map_meta_cap', 10, 4 );