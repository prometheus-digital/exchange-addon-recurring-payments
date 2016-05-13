<?php
/**
 * Test the Credit Requestor class.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class Test_Prorate_Credit_Requestor
 */
class Test_Prorate_Credit_Requestor extends IT_Exchange_UnitTestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_register_provider_rejects_invalid_classes() {

		$requestor = new ITE_Prorate_Credit_Requestor( new ITE_Daily_Price_Calculator() );

		$requestor->register_provider( 'stdClass' );
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function test_exception_throwed_if_no_providers_found() {

		$receiver = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiver->method( 'get_feature' )->with( 'recurring-payments' )->willReturn( true );

		$request = $this->getMockBuilder( 'ITE_Prorate_Credit_Request' )->disableOriginalConstructor()
		                ->setMethods( array( 'get_product_receiving_credit' ) )
		                ->getMockForAbstractClass();
		$request->method( 'get_product_receiving_credit' )->willReturn( $receiver );

		$requestor = new ITE_Prorate_Credit_Requestor( new ITE_Daily_Price_Calculator() );
		$requestor->request_upgrade( $request );
	}

	public function test_upgrade_or_downgrade_fails_for_non_recurring_to_recurring() {

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		$provider     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$provider->ID = 1;

		$receiver = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiver->method( 'get_feature' )->with( 'recurring-payments' )->willReturn( true );
		$receiver->ID = 1;

		$request = $this->getMockBuilder( 'ITE_Prorate_Credit_Request' )->setMethods( array(
			'is_provider_recurring',
			'fail'
		) )->setConstructorArgs( array( $provider, $receiver, $customer ) )->getMockForAbstractClass();
		$request->method( 'is_provider_recurring' )->willReturn( false );
		$request->expects( $this->exactly( 2 ) )->method( 'fail' );

		$requestor = new ITE_Prorate_Credit_Requestor( new ITE_Daily_Price_Calculator() );
		$requestor->request_upgrade( $request );
		$requestor->request_downgrade( $request );
	}

	public function test_upgrade_or_downgrade_fails_if_no_credit_or_free_days() {

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		$provider     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$provider->ID = 1;

		$receiver = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiver->method( 'get_feature' )->willReturnMap( array(
			array( 'recurring-payments', array(), true ),
			array( 'base-price', array(), '5.00' ),
			array( 'recurring-payments', array( 'setting' => 'interval' ), 'month' ),
			array( 'recurring-payments', array( 'setting' => 'interval-count' ), 1 )
		) );
		$receiver->ID = 1;

		$request = $this->getMockBuilder( 'ITE_Prorate_Credit_Request' )->setMethods( array(
			'fail'
		) )->setConstructorArgs( array( $provider, $receiver, $customer ) )->getMockForAbstractClass();
		$request->expects( $this->exactly( 2 ) )->method( 'fail' );

		$requestor = new ITE_Prorate_Credit_Requestor( new ITE_Daily_Price_Calculator() );
		$requestor->register_provider( 'Mock_Prorate_Credit_Provider' );
		$requestor->request_upgrade( $request );
		$requestor->request_downgrade( $request );
	}

	public function test_free_days_calculated_from_credit() {

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		$provider     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$provider->ID = 1;

		$receiver = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiver->method( 'get_feature' )->willReturnMap( array(
			array( 'recurring-payments', array(), true ),
			array( 'base-price', array(), '5.00' ),
			array( 'recurring-payments', array( 'setting' => 'interval' ), 'month' ),
			array( 'recurring-payments', array( 'setting' => 'interval-count' ), 1 )
		) );
		$receiver->ID = 1;

		/** @var ITE_Prorate_Credit_Request|PHPUnit_Framework_MockObject_MockObject $request */
		$request = $this->getMockBuilder( 'ITE_Prorate_Credit_Request' )->setMethods( array(
			'fail'
		) )->setConstructorArgs( array( $provider, $receiver, $customer ) )->getMockForAbstractClass();
		$request->expects( $this->never() )->method( 'fail' );

		$request->set_credit( 4 );

		$calculator = $this->getMock( 'ITE_Daily_Price_Calculator', array( 'calculate' ) );
		$calculator->method( 'calculate' )->with( $this->callback( function ( IT_Exchange_Recurring_Profile $profile ) {
			return $profile->get_interval_type() === 'month' && $profile->get_interval_count() == 1;
		} ), '5.00' )->willReturn( 2 );

		$requestor = new ITE_Prorate_Credit_Requestor( $calculator );
		$requestor->register_provider( 'Mock_Prorate_Credit_Provider' );
		$requestor->request_upgrade( $request );

		$this->assertEquals( 2, $request->get_free_days() );
	}

}

class Mock_Prorate_Credit_Provider implements ITE_Contract_Prorate_Credit_Provider {

	public static function handle_prorate_credit_request( ITE_Prorate_Credit_Request $request, ITE_Daily_Price_Calculator $calculator ) {
		return;
	}

	public static function accepts_prorate_credit_request( ITE_Prorate_Credit_Request $request ) {
		return true;
	}
}