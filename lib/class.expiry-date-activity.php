<?php
/**
 * File Description
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2016.
 */

/**
 * Class IT_Exchange_Txn_Status_Activity
 */
class IT_Exchange_Txn_Subscription_Expiry_Activity extends IT_Exchange_Txn_AbstractActivity {

	/**
	 * Retrieve a status activity item.
	 *
	 * This is used by the activity factory, and should not be called directly.
	 *
	 * @since 1.34
	 *
	 * @internal
	 *
	 * @param int                            $id
	 * @param IT_Exchange_Txn_Activity_Actor $actor
	 *
	 * @return IT_Exchange_Txn_Note_Activity|null
	 */
	public static function make( $id, IT_Exchange_Txn_Activity_Actor $actor = null ) {

		$post = get_post( $id );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		return new self( $post, $actor );
	}

	/**
	 * Get the type of the activity.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public function get_type() {
		return 'subscription-expiry';
	}
}