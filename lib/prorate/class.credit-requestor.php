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
	 *
	 * @throws RuntimeException If no prorate credit provider is found.
	 */
	public function request_upgrade( ITE_Prorate_Credit_Request $request ) {
		$this->handle_request( $request, 'upgrade' );
	}

	/**
	 * Handle a downgrade request.
	 *
	 * Determines which credit provider to use.
	 *
	 * @since 1.9
	 *
	 * @param ITE_Prorate_Credit_Request $request
	 *
	 * @throws RuntimeException If no prorate credit provider is found.
	 */
	public function request_downgrade( ITE_Prorate_Credit_Request $request ) {
		$this->handle_request( $request, 'downgrade' );
	}

	/**
	 * Find and call the correct provider for a given request.
	 *
	 * @since 1.9
	 *
	 * @param ITE_Prorate_Credit_Request $request
	 * @param string                     $type Request type. 'upgrade' or 'downgrade'.
	 */
	protected function handle_request( ITE_Prorate_Credit_Request $request, $type ) {

		foreach ( $this->providers as $provider ) {
			if ( call_user_func( array( $provider, 'accepts_prorate_credit_request' ), $request ) ) {
				call_user_func( array( $provider, 'handle_prorate_credit_request' ), $request );

				return;
			}
		}

		throw new RuntimeException(
			sprintf( "No prorate credit provider found to handle the given %s request '%s'.", $type, get_class( $request ) )
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

		if ( ! in_array( 'ITE_Contract_Prorate_Credit_Provider', class_implements( $provider ) ) ) {
			throw new InvalidArgumentException(
				"The given provider, '$provider', must implement 'ITE_Contract_Prorate_Credit_Provider'."
			);
		}

		$this->providers[] = $provider;
	}
}