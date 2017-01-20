<?php
/**
 * Subscription Object Type.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_Subscription_Object_Type
 */
class ITE_Subscription_Object_Type implements ITE_Object_Type, ITE_Object_Type_With_Meta {

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'subscription';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label() {
		return __( 'Subscription', 'LION' );
	}

	/**
	 * @inheritDoc
	 */
	public function create_object( array $attributes ) {
		throw new BadMethodCallException( 'create_object not supported.' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_object_by_id( $id ) {
		return IT_Exchange_Subscription::get( $id );
	}

	/**
	 * @inheritDoc
	 */
	public function get_objects( \Doctrine\Common\Collections\Criteria $criteria = null ) {
		throw new BadMethodCallException( 'get_objects() not supported.' );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_object_by_id( $id ) {
		throw new BadMethodCallException( 'delete_object_by_id not supported.' );
	}

	/**
	 * @inheritDoc
	 */
	public function supports_meta() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function is_restful() {
		return false; // For now
	}

	/**
	 * @inheritDoc
	 */
	public function add_meta( $object_id, $key, $value, $unique = false ) {

		/** @var IT_Exchange_Subscription $subscription */
		$subscription = $this->get_object_by_id( $object_id );

		return $subscription->add_meta( $key, $value, $unique );
	}

	/**
	 * @inheritDoc
	 */
	public function update_meta( $object_id, $key, $value, $prev_value = '' ) {

		/** @var IT_Exchange_Subscription $subscription */
		$subscription = $this->get_object_by_id( $object_id );

		return $subscription->update_meta( $key, $value );
	}

	/**
	 * @inheritDoc
	 */
	public function get_meta( $object_id, $key = '', $single = true ) {

		/** @var IT_Exchange_Subscription $subscription */
		$subscription = $this->get_object_by_id( $object_id );

		return $subscription->get_meta( $key, $single );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_meta( $object_id, $key, $value = '', $delete_all = false ) {

		/** @var IT_Exchange_Subscription $subscription */
		$subscription = $this->get_object_by_id( $object_id );

		return $subscription->delete_meta( $key, $value );
	}
}