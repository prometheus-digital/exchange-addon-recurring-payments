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
abstract class ITE_Prorate_Credit_Request implements ITE_Cart_Aware {

	const META = 'updowngrade';

	/** @var ITE_Cart */
	private $cart;

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
	 * @var string
	 */
	protected $upgrade_type = null;

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

		$this->update_additional_session_details( array(
			'_prod'  => $providing->ID,
			'_class' => get_class( $this )
		) );
	}

	/**
	 * Retrieve a credit request from the session.
	 *
	 * @since 2.0
	 *
	 * @param IT_Exchange_Product $receiving_product
	 * @param \ITE_Cart           $cart
	 *
	 * @return static
	 */
	public static function get( IT_Exchange_Product $receiving_product, ITE_Cart $cart = null ) {

		$cart = $cart ?: it_exchange_get_current_cart();

		$session = $cart->has_meta( self::META ) ? $cart->get_meta( self::META ) : array();

		if ( ! isset( $session[ $receiving_product->ID ] ) ) {
			return null;
		}

		$data = $session[ $receiving_product->ID ];

		if ( ! isset( $data['_class'] ) ) {
			throw new UnexpectedValueException( '_class property not found in session.' );
		}

		$class = $data['_class'];

		$request = call_user_func( array( $class, '_get' ), $receiving_product, $data );
		$request->set_cart( $cart );

		return $request;
	}

	/**
	 * Get the price description.
	 *
	 * @since 1.36.0
	 *
	 * @return string
	 */
	public function get_price_description() {

		if ( $days = $this->get_free_days() ) {
			return sprintf( _n( '%s day free', '%s days free', $days, 'LION' ), $days );
		}

		if ( $credit = $this->get_credit() ) {
			return sprintf( __( ' %s upgrade credit, then regular price', 'LION' ), it_exchange_format_price( $credit ) );
		}

		return '';
	}

	/**
	 * Get the human readable label of this credit request.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		if ( $days = $this->get_free_days() ) {
			return sprintf( _n( '%d day free.', '%d days free', $days, 'LION' ), $days );
		}

		if ( $credit = $this->get_credit() ) {
			return sprintf( __( '%s off.', 'LION' ), it_exchange_format_price( $credit ) );
		}

		return '';
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
	 * Get the upgrade type used to fufill the credit request.
	 *
	 * @since 1.9
	 *
	 * @return string|null Null if the upgrade type has not been set yet.
	 */
	public function get_upgrade_type() {
		return $this->upgrade_type;
	}

	/**
	 * Set the upgrade type used to fufill the credit request.
	 *
	 * @since 1.9
	 *
	 * @param string $upgrade_type Accepts 'days' or 'credit'.
	 */
	public function set_upgrade_type( $upgrade_type ) {
		$this->upgrade_type = $upgrade_type;
	}

	/**
	 * Get the details to be added to the `updowngrade` session data.
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_additional_session_details() {
		return $this->session_details;
	}

	/**
	 * Set the details that are added to the `updowngrade` session data.
	 *
	 * @since 1.9
	 *
	 * @param array $session_details
	 */
	public function set_additional_session_details( array $session_details ) {
		$this->session_details = $session_details;
	}

	/**
	 * Update the details that are added to the `updowngrade` session data.
	 *
	 * @since 1.9
	 *
	 * @param array $session_details
	 */
	public function update_additional_session_details( array $session_details ) {
		$this->session_details = wp_parse_args( $session_details, $this->get_additional_session_details() );
	}

	/**
	 * @inheritDoc
	 */
	public function set_cart( ITE_Cart $cart ) {
		$this->cart = $cart;
	}

	/**
	 * Get the cart.
	 *
	 * @since 1.9.0
	 *
	 * @return \ITE_Cart
	 */
	public function get_cart() {

		if ( ! $this->cart ) {
			$this->cart = it_exchange_get_current_cart();
		}

		return $this->cart;
	}

	/**
	 * Persist this credit request to the session.
	 *
	 * @since 1.9
	 */
	public function persist() {

		$details = array(
			'credit'       => round( $this->get_credit(), 2 ),
			'free_days'    => $this->get_free_days(),
			'upgrade_type' => $this->get_upgrade_type(),
		);

		$details = array_merge( $details, $this->get_additional_session_details() );

		$data = $this->get_cart()->has_meta( self::META ) ? $this->get_cart()->get_meta( self::META ) : array();

		$data[ $this->get_product_receiving_credit()->ID ] = $details;

		$this->get_cart()->set_meta( self::META, $data );
	}

	/**
	 * Remove this credit request from the session.
	 *
	 * @since 1.9
	 */
	public function fail() {
		$data = $this->get_cart()->has_meta( self::META ) ? $this->get_cart()->get_meta( self::META ) : array();
		unset( $data[ $this->get_product_receiving_credit()->ID ] );

		if ( $data ) {
			$this->get_cart()->set_meta( self::META, $data );
		} else {
			$this->get_cart()->remove_meta( self::META );
		}
	}
}