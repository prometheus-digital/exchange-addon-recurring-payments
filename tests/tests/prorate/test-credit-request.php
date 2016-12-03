<?php
/**
 * Test the Credit Request class.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class Test_Prorate_Credit_Request
 */
class Test_Prorate_Credit_Request extends IT_Exchange_UnitTestCase {

	/**
	 * @inheritDoc
	 */
	public function setUp() {
		parent::setUp();

		$GLOBALS['it_exchange']['session'] = new IT_Exchange_In_Memory_Session( null );
	}

	public function test_class_session_property_set() {

		$providing     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$providing->ID = 1;

		$receiving     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiving->ID = 2;

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		/** @var ITE_Prorate_Credit_Request $request */
		$request = $this->getMockForAbstractClass( 'ITE_Prorate_Credit_Request', array(
			$providing,
			$receiving,
			$customer
		) );

		$details = $request->get_additional_session_details();

		$this->assertArrayHasKey( '_class', $details );
		$this->assertEquals( get_class( $request ), $details['_class'] );
	}

	public function test_product_session_property_set() {

		$providing     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$providing->ID = 1;

		$receiving     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiving->ID = 2;

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		/** @var ITE_Prorate_Credit_Request $request */
		$request = $this->getMockForAbstractClass( 'ITE_Prorate_Credit_Request', array(
			$providing,
			$receiving,
			$customer
		) );

		$details = $request->get_additional_session_details();

		$this->assertArrayHasKey( '_prod', $details );
		$this->assertEquals( 1, $details['_prod'] );
	}

	public function test_getters_and_setters() {

		$providing     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$providing->ID = 1;

		$receiving     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiving->ID = 2;

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		/** @var ITE_Prorate_Credit_Request $request */
		$request = $this->getMockForAbstractClass( 'ITE_Prorate_Credit_Request', array(
			$providing,
			$receiving,
			$customer
		) );

		$this->assertEquals( $providing, $request->get_product_providing_credit(), 'Providing product not equal.' );
		$this->assertEquals( $receiving, $request->get_product_receiving_credit(), 'Receiving product not equal.' );
		$this->assertEquals( $customer, $request->get_customer(), 'Customer not equal.' );

		$this->assertNull( $request->get_credit(), 'Initial credit not null.' );
		$request->set_credit( 4.99 );
		$this->assertEquals( 4.99, $request->get_credit(), 'Credit failed.' );

		$this->assertNull( $request->get_free_days(), 'Initial free days not null.' );
		$request->set_free_days( 5 );
		$this->assertEquals( 5, $request->get_free_days(), 'Free days failed.' );

		$this->assertNull( $request->get_credit_type(), 'Initial upgrade type not null.' );
		$request->set_credit_type( 'days' );
		$this->assertEquals( 'days', $request->get_credit_type(), 'Upgrade type failed' );

		$request->set_additional_session_details( array() );
		$this->assertEquals( array(), $request->get_additional_session_details(), 'Set session details failed.' );
		$request->update_additional_session_details( array( 'prop1' => 'val1' ) );
		$this->assertEqualSets( array( 'prop1' => 'val1' ), $request->get_additional_session_details() );
		$request->update_additional_session_details( array( 'propa' => 'vala' ) );
		$this->assertEqualSets( array(
			'prop1' => 'val1',
			'propa' => 'vala'
		), $request->get_additional_session_details() );
		$request->update_additional_session_details( array( 'prop1' => 'val2' ) );
		$this->assertEqualSets( array(
			'prop1' => 'val2',
			'propa' => 'vala'
		), $request->get_additional_session_details() );
	}

	public function test_persist() {

		$providing     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$providing->ID = 1;

		$receiving     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiving->ID = 2;

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		/** @var ITE_Prorate_Credit_Request $request */
		$request = $this->getMockForAbstractClass( 'ITE_Prorate_Credit_Request', array(
			$providing,
			$receiving,
			$customer
		) );

		$request->set_additional_session_details( array( 'p1' => 'v1' ) );
		$request->set_credit( 29.740 );
		$request->set_free_days( 2 );
		$request->set_credit_type( 'days' );

		$cart = it_exchange_get_current_cart();

		$this->assertFalse( $cart->has_meta( ITE_Prorate_Credit_Request::META ) );

		$request->persist();
		$session = it_exchange_get_current_cart()->get_meta( ITE_Prorate_Credit_Request::META );

		$this->assertArrayHasKey( 2, $session, 'Session not updated.' );

		$this->assertArraySubset( array(
			'credit'       => 29.74,
			'free_days'    => 2,
			'upgrade_type' => 'days',
			'p1'           => 'v1'
		), $session[2] );
	}

	/**
	 * @depends test_persist
	 */
	public function test_fail() {

		$providing     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$providing->ID = 1;

		$receiving     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiving->ID = 2;

		$customer = $this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();

		/** @var ITE_Prorate_Credit_Request $request */
		$request = $this->getMockForAbstractClass( 'ITE_Prorate_Credit_Request', array(
			$providing,
			$receiving,
			$customer
		) );

		$request->persist();
		$request->fail();

		$session = it_exchange_get_session_data( 'updowngrade_details' );

		$this->assertArrayNotHasKey( 2, $session, "fail() call didn't remove request from session." );
	}
}