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
class ITE_Prorate_Credit_Request {

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
	 * @var float
	 */
	protected $credit = null;

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
}