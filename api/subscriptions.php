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
				$subscription = new IT_Exchange_Subscription( $transaction, $product );

				break;
			}
		}
	} elseif ( count( $transaction->get_products() ) === 1 ) {

		$cart_products = $transaction->get_products();
		$cart_product  = reset( $cart_products );

		$subscription = new IT_Exchange_Subscription( $transaction, it_exchange_get_product( $cart_product['product_id'] ) );
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
	 */
	return apply_filters( 'it_exchange_get_subscription', $subscription );
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

		try {
			$subs[] = it_exchange_get_subscription_by_transaction( $transaction, it_exchange_get_product( $product['product_id'] ) );
		}
		catch ( Exception $e ) {

		}
	}

	return $subs;
}