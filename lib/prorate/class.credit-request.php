<?php
/**
 * Contains the class definition for ITE_Prorate_Credit_Request
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class ITE_Prorate_Credit_Request
 */
abstract class ITE_Prorate_Credit_Request {

	/**
	 * @var IT_Exchange_Product
	 */
	protected $providing;

	/**
	 * @var IT_Exchange_Product
	 */
	protected $receiving;

	/**
	 * @var IT_Exchange_Customer
	 */
	protected $customer;

	/**
	 * @var array
	 */
	protected $session_details = array();

	/**
	 * @var float
	 */
	protected $credit = null;

	/**
	 * @var int
	 */
	protected $free_days = null;

	/**
	 * ITE_Prorate_Credit_Request constructor.
	 *
	 * @param IT_Exchange_Product  $providing
	 * @param IT_Exchange_Product  $receiving
	 * @param IT_Exchange_Customer $customer
	 */
	public function __construct( IT_Exchange_Product $providing, IT_Exchange_Product $receiving, IT_Exchange_Customer $customer ) {
		$this->providing = $providing;
		$this->receiving = $receiving;
		$this->customer  = $customer;
	}

	/**
	 * Get the product providing the credit.
	 *
	 * @since 1.9
	 *
	 * @return IT_Exchange_Product
	 */
	public function get_product_providing_credit() {
		return $this->providing;
	}

	/**
	 * Get the product receiving credit.
	 *
	 * @since 1.9
	 *
	 * @return IT_Exchange_Product
	 */
	public function get_product_receiving_credit() {
		return $this->receiving;
	}

	/**
	 * Get the customer receiving the credit.
	 *
	 * @since 1.9
	 *
	 * @return IT_Exchange_Customer
	 */
	public function get_customer() {
		return $this->customer;
	}

	/**
	 * Is the credit provider auto-renewing.
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public abstract function is_provider_auto_renewing();

	/**
	 * Is the credit provider a recurring product.
	 *
	 * By default, this is true.
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_provider_recurring() {
		return true;
	}

	/**
	 * Set the credit the customer is eligible for.
	 *
	 * @since 1.9
	 *
	 * @param float $credit
	 */
	public function set_credit( $credit ) {
		$this->credit = $credit;
	}

	/**
	 * Get the total amount of credit the customer is eligible for.
	 *
	 * @since 1.9
	 *
	 * @return float|null Null if the credit has not been set yet.
	 */
	public function get_credit() {
		return $this->credit;
	}

	/**
	 * Get the total amount of free days available from this credit.
	 *
	 * @since 1.9
	 *
	 * @return int|null Null if the free days has not been set yet.
	 */
	public function get_free_days() {
		return $this->free_days;
	}

	/**
	 * Set the amount of free days available from this credit.
	 *
	 * @since 1.9
	 *
	 * @param int $free_days
	 */
	public function set_free_days( $free_days ) {
		$this->free_days = $free_days;
	}

	/**
	 * Get the details to be added to the `updowngrade` session data.
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_session_details() {
		return $this->session_details;
	}

	/**
	 * Set the details that are added to the `updowngrade` session data.
	 *
	 * @since 1.9
	 *
	 * @param array $session_details
	 */
	public function set_session_details( array $session_details ) {
		$this->session_details = $session_details;
	}

	/**
	 * Update the details that are added to the `updowngrade` session data.
	 * 
	 * @since 1.9
	 * 
	 * @param array $session_details
	 */
	public function update_session_details( array $session_details ) {
		$this->session_details = wp_parse_args( $session_details, $this->get_session_details() );
	}
}