<?php
/**
 * Prorate credit requestor.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class ITE_Prorate_Credit_Requestor
 */
class ITE_Prorate_Credit_Requestor {

	/**
	 * @var ITE_Daily_Price_Calculator
	 */
	protected $calculator;

	/**
	 * @var array
	 */
	protected $providers = array();

	/**
	 * ITE_Prorate_Credit_Requestor constructor.
	 *
	 * @param ITE_Daily_Price_Calculator $calculator
	 */
	public function __construct( ITE_Daily_Price_Calculator $calculator ) {
		$this->calculator = $calculator;
	}

	/**
	 * Handle an upgrade request.
	 *
	 * Determines which credit provider to use.
	 *
	 * @since 1.9
	 *
	 * @param ITE_Prorate_Credit_Request $request
	 * @param bool                       $persist
	 *
	 * @return boolean
	 *
	 * @throws RuntimeException If no prorate credit provider is found.
	 */
	public function request_upgrade( ITE_Prorate_Credit_Request $request, $persist = true ) {

		$receiver = $request->get_product_receiving_credit();

		// Life to recurring is not allowed
		if ( ! $request->is_provider_recurring() && $receiver->get_feature( 'recurring-payments' ) ) {

			if ( $persist ) {
				$request->fail();
			}

			return false;
		}

		$this->handle_request( $request, 'upgrade' );

		if ( ! $request->get_free_days() && $receiver->get_feature( 'recurring-payments' ) ) {
			$this->calculate_free_days_from_credit( $request );
		}

		// If we don't have any credit, or free days, we can just stop here
		if ( ! $request->get_credit() && ! $request->get_free_days() ) {

			if ( $persist ) {
				$request->fail();
			}

			return false;
		}

		if ( $this->product_auto_renews( $receiver ) ) {
			$upgrade_type = 'days';
		} else {
			$upgrade_type = 'credit';
		}

		$request->set_upgrade_type( $upgrade_type );

		if ( $persist ) {
			$request->persist();
		}

		return true;
	}

	/**
	 * Handle a downgrade request.
	 *
	 * Determines which credit provider to use.
	 *
	 * @since 1.9
	 *
	 * @param ITE_Prorate_Credit_Request $request
	 * @param bool                       $persist
	 *
	 * @return boolean
	 *
	 * @throws RuntimeException If no prorate credit provider is found.
	 */
	public function request_downgrade( ITE_Prorate_Credit_Request $request, $persist = true ) {

		$receiver = $request->get_product_receiving_credit();

		if ( ! $request->is_provider_recurring() && $receiver->get_feature( 'recurring-payments' ) ) {

			if ( $persist ) {
				$request->fail();
			}

			return false;
		}

		$this->handle_request( $request, 'downgrade' );

		if ( ! $request->get_free_days() && $receiver->get_feature( 'recurring-payments' ) ) {
			$this->calculate_free_days_from_credit( $request );
		}

		// If we don't have any credit, or free days, we can just stop here
		if ( ! $request->get_credit() && ! $request->get_free_days() ) {

			if ( $persist ) {
				$request->fail();
			}

			return false;
		}

		if ( $this->product_auto_renews( $receiver ) ) {
			$upgrade_type = 'days';
		} else {
			$upgrade_type = 'credit';
		}

		$request->set_upgrade_type( $upgrade_type );

		if ( $persist ) {
			$request->persist();
		}

		return true;
	}

	/**
	 * Calculate the available number of free days from the given credit.
	 *
	 * @since 1.9
	 *
	 * @param ITE_Prorate_Credit_Request $request
	 */
	protected function calculate_free_days_from_credit( ITE_Prorate_Credit_Request $request ) {

		$daily_cost_of_upgrade = $this->get_daily_cost_for_product( $request->get_product_receiving_credit() );

		if ( empty( $daily_cost_of_upgrade ) ) {
			$free_days = 0;
		} else {
			$free_days = max( round( $request->get_credit() / $daily_cost_of_upgrade ), 0 );
		}

		if ( $free_days ) {
			$request->set_free_days( $free_days );
		}
	}

	/**
	 * Find and call the correct provider for a given request.
	 *
	 * @since 1.9
	 *
	 * @param ITE_Prorate_Credit_Request $request
	 * @param string                     $type Request type. 'upgrade' or 'downgrade'.
	 *
	 * @throws \RuntimeException
	 */
	protected function handle_request( ITE_Prorate_Credit_Request $request, $type ) {

		$called = false;

		foreach ( $this->providers as $provider ) {
			if ( call_user_func( array( $provider, 'accepts_prorate_credit_request' ), $request ) ) {
				call_user_func( array( $provider, 'handle_prorate_credit_request' ), $request, $this->calculator );

				$called = true;
			}
		}

		if ( ! $called ) {
			throw new RuntimeException(
				sprintf( "No prorate credit provider found to handle the given %s request '%s'.", $type, get_class( $request ) )
			);
		}
	}

	/**
	 * Get the daily cost for a product.
	 *
	 * @since 1.9
	 *
	 * @param IT_Exchange_Product $product
	 *
	 * @return float
	 */
	protected function get_daily_cost_for_product( IT_Exchange_Product $product ) {

		$price   = $product->get_feature( 'base-price' );
		$profile = new IT_Exchange_Recurring_Profile(
			$product->get_feature( 'recurring-payments', array( 'setting' => 'interval' ) ),
			$product->get_feature( 'recurring-payments', array( 'setting' => 'interval-count' ) )
		);

		return $this->calculator->calculate( $profile, $price );
	}

	/**
	 * Check if a product auto-renews.
	 *
	 * @since 1.9
	 *
	 * @param IT_Exchange_Product $product
	 *
	 * @return bool
	 */
	protected function product_auto_renews( IT_Exchange_Product $product ) {
		return in_array(
			$product->get_feature( 'recurring-payments', array( 'setting' => 'auto-renew' ) ),
			array( 'on', 'yes' ),
			true
		);
	}

	/**
	 * Register a credit provider.
	 *
	 * @since 1.9
	 *
	 * @param string $provider
	 */
	public function register_provider( $provider ) {

		if ( ! in_array( 'ITE_Contract_Prorate_Credit_Provider', class_implements( $provider ), true ) ) {
			throw new InvalidArgumentException(
				"The given provider, '$provider', must implement 'ITE_Contract_Prorate_Credit_Provider'."
			);
		}

		$this->providers[] = $provider;
	}
}