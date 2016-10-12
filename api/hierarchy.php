<?php
/**
 * Subscription Hierarchy Functions.
 *
 * @since   1.9.0
 * @license GPLv2
 */


/**
 * For hierarchical subscriptions, retrieve an array of all the product's parents
 *
 * @since 1.2.0
 *
 * @param int   $product_id product ID of membership
 * @param array $parent_ids array of of current parent_ids
 *
 * @return array|bool
 */
function it_exchange_get_all_subscription_product_parents( $product_id, $parent_ids = array() ) {
	$parents = it_exchange_get_product_feature( $product_id, 'subscription-hierarchy', array( 'setting' => 'parents' ) );
	if ( ! empty( $parents ) ) {
		foreach ( $parents as $parent_id ) {
			if ( false !== get_post_status( $parent_id ) ) {
				$parent_ids[] = $parent_id;
				if ( false !== $results = it_exchange_get_all_subscription_product_parents( $parent_id ) ) {
					$parent_ids = array_merge( $parent_ids, $results );
				}
			}
		}
	} else {
		return false;
	}

	return $parent_ids;
}

/**
 * For hierarchical subscriptions, retrieve an array of all the product's children.
 *
 * @since 1.2.16
 *
 * @param int   $product_id product ID of membership
 * @param array $child_ids  array of of current child_ids
 *
 * @return array|bool
 */
function it_exchange_get_all_subscription_product_children( $product_id, $child_ids = array() ) {
	$children = it_exchange_get_product_feature( $product_id, 'subscription-hierarchy', array( 'setting' => 'children' ) );
	if ( ! empty( $children ) ) {
		foreach ( $children as $child_id ) {
			if ( false !== get_post_status( $child_id ) ) {
				$child_ids[] = $child_id;
				if ( false !== $results = it_exchange_get_all_subscription_product_children( $child_id ) ) {
					$child_ids = array_merge( $child_ids, $results );
				}
			}
		}
	} else {
		return false;
	}

	return $child_ids;
}