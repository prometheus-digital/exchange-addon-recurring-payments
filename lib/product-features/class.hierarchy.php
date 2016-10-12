<?php
/**
 * Recurring Payments Hierarchy Product Feature.
 *
 * @since   1.9.0
 * @license GPLv2
 */

/**
 * Class IT_Exchange_Subscription_Hierarchy_Product_Feature
 */
class IT_Exchange_Subscription_Hierarchy_Product_Feature extends IT_Exchange_Product_Feature_Abstract {

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct( array(
			'slug'          => 'subscription-hierarchy',
			'description'   => __( 'Allows you to define an upgrade or downgrade hierarchy for subscriptions.', 'LION' ),
			'metabox_title' => __( 'Subscription Hierarchy', 'LION' )
		) );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.9.0
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function print_metabox( $post ) {

		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'   => '_it-exchange-product-recurring-enabled',
				'value' => 'on',
			),
		);

		if ( it_exchange_get_product_type( $post ) === 'membership-product-type' ) {
			$meta_query[] = array(
				'key'   => '_it_exchange_product_type',
				'value' => 'membership-product-type',
			);
		}

		$subscriptions = get_posts( array(
			'numberposts' => - 1,
			'meta_query'  => $meta_query,
			'post_type'   => 'it_exchange_prod',
		) );

		// Grab the iThemes Exchange Product object from the WP $post object
		$product = it_exchange_get_product( $post );

		echo '<p>' . __( 'View and edit subscription relationships below. You can add child subscriptions to include the content and files from another subscription with this subscription. You can also add and remove parent subscriptions that include this subscription.', 'LION' ) . '</p>';

		$child_ids  = it_exchange_get_product_feature( $product->ID, 'subscription-hierarchy', array( 'setting' => 'children' ) );
		$parent_ids = it_exchange_get_product_feature( $product->ID, 'subscription-hierarchy', array( 'setting' => 'parents' ) );

		echo '<p><label for="it-exchange-subscription-child-id" class="it-exchange-subscription-label it-exchange-subscription-child-label">' . __( 'Child Subscriptions', 'LION' ) . ' <span class="tip" title="' . __( "A Parent gets all of its own access, plus all of it's Child(ren)'s access.", 'LION' ) . '">i</span></label></p>';
		echo '<p>' . __( 'Additional subscriptions available to owners of this subscription level.', 'LION' ) . '</p>';

		echo '<div class="it-exchange-subscription-child-ids-list-div">';
		it_exchange_recurring_payments_addon_display_subscription_hierarchy( $child_ids );
		echo '</div>';

		echo '<div class="it-exchange-subscription-hierarchy-add it-exchange-subscription-hierarchy-add-child">';
		echo '<select class="it-exchange-subscription-child-id" name="it-exchange-subscription-child-id">';
		echo '<option value="">' . __( 'Select a Subscription', 'LION' ) . '</option>';
		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->ID !== $post->ID ) {
				echo '<option value="' . $subscription->ID . '">' . get_the_title( $subscription->ID ) . '</option>';
			}
		}
		echo '</select>';
		echo '<a href class="button">' . __( 'Add Child Subscription', 'LION' ) . '</a>';
		echo '</div>';

		echo '<p><label for="it-exchange-subscription-parent-id" class="it-exchange-subscription-label it-exchange-subscription-parent-label">' . __( 'Parent Subscriptions', 'LION' ) . ' <span class="tip" title="' . __( "A Parent gets all of its own access, plus all of it's Child(ren)'s access.", 'LION' ) . '">i</span></label></p>';
		echo '<p>' . __( 'Subscriptions that include content and benefits from this subscription and all children of it.', 'LION' ) . '</p>';

		echo '<div class="it-exchange-subscription-parent-ids-list-div">';
		echo '<ul>';
		foreach ( $parent_ids as $parent_id ) {
			if ( false !== get_post_status( $parent_id ) ) {
				echo '<li data-parent-id="' . $parent_id . '">';
				echo '<div class="inner-wrapper">' . get_the_title( $parent_id ) . ' <a href data-subscription-id="' . $parent_id . '" class="it-exchange-subscription-addon-delete-subscription-parent it-exchange-remove-item">&times;</a>';
				echo '<input type="hidden" name="it-exchange-subscription-parent-ids[]" value="' . $parent_id . '" /></div>';
				echo '</li>';
			}
		}
		echo '</ul>';
		echo '</div>';

		echo '<div class="it-exchange-subscription-hierarchy-add it-exchange-subscription-hierarchy-add-parent">';
		echo '<select class="it-exchange-subscription-parent-id" name="it-exchange-subscription-parent-id">';
		echo '<option value="">' . __( 'Select a Subscription', 'LION' ) . '</option>';
		foreach ( $subscriptions as $subscription ) {
			if ( $subscription->ID !== $post->ID ) {
				echo '<option value="' . $subscription->ID . '">' . get_the_title( $subscription->ID ) . '</option>';
			}
		}
		echo '</select>';
		echo '<a href class="button">' . __( 'Add Parent Subscription', 'LION' ) . '</a>';
		echo '</div>';
	}

	/**
	 * This saves the value
	 *
	 * @since 1.9.0
	 *
	 * @param object $post wp post object
	 *
	 * @return void
	 */
	public function save_feature_on_product_save() {
		// Abort if we can't determine a product type
		if ( ! $product_type = it_exchange_get_product_type() ) {
			return;
		}

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id ) {
			return;
		}

		// Abort if this product type doesn't support this feature
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'subscription-hierarchy' ) ) {
			return;
		}

		$child_ids  = empty( $_POST['it-exchange-subscription-child-ids'] ) ? array() : $_POST['it-exchange-subscription-child-ids'];
		$parent_ids = empty( $_POST['it-exchange-subscription-parent-ids'] ) ? array() : $_POST['it-exchange-subscription-parent-ids'];

		it_exchange_update_product_feature( $product_id, 'subscription-hierarchy', $child_ids, array( 'setting' => 'children' ) );
		it_exchange_update_product_feature( $product_id, 'subscription-hierarchy', $parent_ids, array( 'setting' => 'parents' ) );
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.9.0
	 *
	 * @param mixed $existing the values passed in by the WP Filter API. Ignored here.
	 * @param       integer   product_id the WordPress post ID
	 *
	 * @return string product feature
	 */
	public function save_feature( $product_id, $new_value, $options = array() ) {
		switch ( $options['setting'] ) {

			case 'children':
				$child_ids = get_post_meta( $product_id, '_it-exchange-subscription-child-id' );
				if ( empty( $new_value ) ) {
					delete_post_meta( $product_id, '_it-exchange-subscription-child-id' );
					foreach ( $child_ids as $child_id ) {
						delete_post_meta( $child_id, '_it-exchange-subscription-parent-id', $product_id );
					}
				} else {
					foreach ( $child_ids as $child_id ) {
						if ( ! in_array( $child_id, $new_value, true ) ) {
							delete_post_meta( $product_id, '_it-exchange-subscription-child-id', $child_id );
							delete_post_meta( $child_id, '_it-exchange-subscription-parent-id', $product_id );
						}
					}

					foreach ( $new_value as $child_id ) {
						if ( ! in_array( $child_id, (array) $child_ids, true ) ) {
							add_post_meta( $product_id, '_it-exchange-subscription-child-id', $child_id );
						}

						$parent_ids = get_post_meta( $child_id, '_it-exchange-subscription-parent-id' );
						if ( ! in_array( $product_id, (array) $parent_ids, true ) ) {

							add_post_meta( $child_id, '_it-exchange-subscription-parent-id', $product_id );
						}
					}
				}
				break;

			case 'parents':
				$parent_ids = get_post_meta( $product_id, '_it-exchange-subscription-parent-id' );
				if ( empty( $new_value ) ) {
					delete_post_meta( $product_id, '_it-exchange-subscription-parent-id' );
					foreach ( $parent_ids as $parent_id ) {
						delete_post_meta( $parent_id, '_it-exchange-subscription-child-id', $product_id );
					}
				} else {
					foreach ( $parent_ids as $parent_id ) {
						if ( ! in_array( $parent_id, $new_value, true ) ) {
							delete_post_meta( $product_id, '_it-exchange-subscription-parent-id', $parent_id );
							delete_post_meta( $parent_id, '_it-exchange-subscription-child-id', $product_id );
						}
					}

					foreach ( $new_value as $parent_id ) {
						if ( ! in_array( $parent_id, (array) $parent_ids, true ) ) {
							add_post_meta( $product_id, '_it-exchange-subscription-parent-id', $parent_id );
						}

						$child_ids = get_post_meta( $parent_id, '_it-exchange-subscription-child-id' );
						if ( ! in_array( $product_id, (array) $child_ids, true ) ) {
							add_post_meta( $parent_id, '_it-exchange-subscription-child-id', $product_id );
						}
					}
				}
				break;

		}

		return true;
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.9.0
	 *
	 * @param mixed $existing the values passed in by the WP Filter API. Ignored here.
	 * @param       integer   product_id the WordPress post ID
	 *
	 * @return string product feature
	 */
	public function get_feature( $existing, $product_id, $options = array() ) {
		switch ( $options['setting'] ) {

			case 'children':
				$test = get_post_meta( $product_id, '_it-exchange-subscription-child-id' );

				return $test;
			case 'parents':
				return get_post_meta( $product_id, '_it-exchange-subscription-parent-id' );

		}

		return false;
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.9.0
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 *
	 * @return boolean
	 */
	public function product_has_feature( $result, $product_id, $options = array() ) {
		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id, $options ) ) {
			return false;
		}

		// If it does support, does it have it?
		return (boolean) $this->get_feature( false, $product_id, $options );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.9.0
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 *
	 * @return boolean
	 */
	public function product_supports_feature( $result, $product_id, $options = array() ) {
		// Does this product type support this feature?
		$product_type = it_exchange_get_product_type( $product_id );
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'subscription-hierarchy' ) ) {
			return false;
		}

		return true;
	}
}