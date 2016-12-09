<?php
/**
 * Contains subscriptions API functions.
 *
 * @since   1.8
 * @license GPLv2
 */

/**
 * Get a subscription.
 *
 * @since 1.8
 *
 * @param IT_Exchange_Transaction $transaction
 * @param IT_Exchange_Product     $product Non-auto-renewing products can be purchased simultaneously,
 *                                         use this to specify which subscription should be returned.
 *
 * @return IT_Exchange_Subscription
 *
 * @throws InvalidArgumentException If which subscription to return is ambiguous.
 */
function it_exchange_get_subscription_by_transaction( IT_Exchange_Transaction $transaction, IT_Exchange_Product $product = null ) {

	if ( $product ) {
		foreach ( $transaction->get_products() as $cart_product ) {

			if ( $cart_product['product_id'] == $product->ID ) {
				$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );

				break;
			}
		}
	} elseif ( count( $transaction->get_products() ) === 1 ) {

		$cart_products = $transaction->get_products();
		$cart_product  = reset( $cart_products );

		$product = it_exchange_get_product( $cart_product['product_id'] );

		if ( $product ) {
			$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		}
	} else {
		throw new InvalidArgumentException( 'Ambiguous subscription requested.' );
	}

	$subscription = isset( $subscription ) ? $subscription : null;

	/**
	 * Filter the subscription object.
	 *
	 * @since 1.8
	 *
	 * @param IT_Exchange_Subscription|null $subscription
	 * @param IT_Exchange_Transaction       $transaction
	 * @param IT_Exchange_Product|null      $product
	 */
	return apply_filters( 'it_exchange_get_subscription_by_transaction', $subscription, $transaction, $product );
}

/**
 * Get all subscriptions for a transaction.
 *
 * @since 1.8
 *
 * @param IT_Exchange_Transaction $transaction
 *
 * @return IT_Exchange_Subscription[]
 */
function it_exchange_get_transaction_subscriptions( IT_Exchange_Transaction $transaction ) {

	$subs = array();

	foreach ( $transaction->get_products() as $product ) {

		$product = it_exchange_get_product( $product['product_id'] );

		if ( ! $product ) {
			continue;
		}

		try {
			$sub = it_exchange_get_subscription_by_transaction( $transaction, $product );

			if ( $sub ) {
				$subs[] = $sub;
			}
		} catch ( Exception $e ) {

		}
	}

	return $subs;
}

/**
 * Get a subscription by the subscriber ID.
 *
 * @since 1.9.0
 *
 * @param string $method
 * @param string $subscriber_id
 *
 * @return IT_Exchange_Subscription|null
 */
function it_exchange_get_subscription_by_subscriber_id( $method, $subscriber_id ) {

	$transactions = it_exchange_get_transactions( array(
		'transaction_method' => $method,
		'meta_query'         => array(
			array(
				'key'   => '_it_exchange_transaction_subscriber_id',
				'value' => $subscriber_id,
			)
		)
	) );

	if ( ! $transactions ) {
		return null;
	}

	$transaction = reset( $transactions );

	$subscriptions = it_exchange_get_transaction_subscriptions( $transaction );

	foreach ( $subscriptions as $subscription ) {
		if ( $subscription->get_subscriber_id() === $subscriber_id ) {
			return $subscription;
		}
	}

	return null;
}

/**
 * Add a subscription renewal payment.
 *
 * @since 1.9.0
 *
 * @param IT_Exchange_Transaction $parent
 * @param string                  $method_id
 * @param string                  $status
 * @param float                   $total
 * @param array                   $args
 *
 * @return bool|IT_Exchange_Transaction|null
 */
function it_exchange_add_subscription_renewal_payment( IT_Exchange_Transaction $parent, $method_id, $status, $total, $args = array() ) {

	$cart = ITE_Cart::create(
		new ITE_Line_Item_Cached_Session_Repository(
			new IT_Exchange_In_Memory_Session( null ),
			$parent->get_customer(),
			new ITE_Line_Item_Repository_Events()
		),
		$parent->get_customer()
	);

	foreach ( $parent->get_items() as $item ) {
		$cart->add_item( $item->clone_with_new_id() );
	}

	$cart->get_items()->flatten()->with_only( 'fee' )
	     ->filter( function ( ITE_Fee_Line_Item $fee ) { return ! $fee->is_recurring(); } )
	     ->delete();

	$transaction_object        = it_exchange_generate_transaction_object( $cart );
	$transaction_object->total = $total;

	$txn_id = it_exchange_add_child_transaction(
		$parent->get_method(),
		$method_id,
		$status,
		$cart,
		$parent->get_ID(),
		$transaction_object,
		$args
	);

	return $txn_id ? it_exchange_get_transaction( $txn_id ) : null;
}

/**
 * Get a subscription.
 *
 * @since 1.9.0
 *
 * @param string|IT_Exchange_Subscription $subscription
 *
 * @return IT_Exchange_Subscription|null
 */
function it_exchange_get_subscription( $subscription ) {
	if ( $subscription instanceof IT_Exchange_Subscription ) {
		return $subscription;
	}

	return IT_Exchange_Subscription::get( $subscription );
}