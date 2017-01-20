<?php
/**
 * Proratable interface.
 *
 * @since 2.0.0
 * @license GPLv2
 */

/**
 * Interface ITE_Proratable
 */
interface ITE_Proratable {

	/**
	 * Get all available upgrades for this object.
	 *
	 * @since 2.0.0
	 *
	 * @return ITE_Prorate_Credit_Request[]
	 */
	public function get_available_upgrades();

	/**
	 * Get all available downgrades for this object.
	 *
	 * @since 2.0.0
	 *
	 * @return ITE_Prorate_Credit_Request[]
	 */
	public function get_available_downgrades();
}