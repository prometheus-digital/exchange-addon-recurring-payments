<?php
/**
 * Test the subscription credit request.
 *
 * @since   1.9
 * @license GPLv2
 */

use Mockery as m;

/**
 * Class Test_Prorate_Subscription_Request
 */
class Test_Prorate_Subscription_Request extends IT_Exchange_UnitTestCase {

	/**
	 * @inheritDoc
	 */
	public function setUp() {
		parent::setUp();

		$GLOBALS['it_exchange']['session'] = new IT_Exchange_Mock_Session();
	}

	public function test_accessors() {

		$receiving     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiving->ID = 1;

		$providing     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$providing->ID = 2;

		$transaction     = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->ID = 1;

		$subscription = $this->getMockBuilder( 'IT_Exchange_Subscription' )->disableOriginalConstructor()->getMock();
		$subscription->method( 'is_auto_renewing' )->willReturn( true );
		$subscription->method( 'get_product' )->willReturn( $providing );
		$subscription->method( 'get_transaction' )->willReturn( $transaction );
		$subscription->method( 'get_customer' )->willReturn( $this->getMock( 'IT_Exchange_Customer', array(), array(), '', false ) );

		$request = new ITE_Prorate_Subscription_Credit_Request( $subscription, $receiving );

		$details = $request->get_additional_session_details();
		$this->assertArrayHasKey( '_txn', $details );
		$this->assertEquals( 1, $details['_txn'] );

		$this->assertTrue( $request->is_provider_auto_renewing() );
		$this->assertEquals( $subscription, $request->get_subscription() );
	}

	public function test_retrieve_from_session() {

		$receiving = $this->product_factory->create_and_get();
		$providing = $this->product_factory->create_and_get( array(
			'recurring-payments' => 'on'
		) );
		$providing->update_feature( 'recurring-payments', 'month', array( 'setting' => 'interval' ) );
		$providing->update_feature( 'recurring-payments', '1', array( 'setting' => 'interval-count' ) );

		$transaction = $this->transaction_factory->create_and_get( array(
			'cart_object' => (object) array(
				'products' => array(
					"{$providing->ID}-hash" => array(
						'product_id' => $providing->ID,
					)
				)
			)
		) );

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $providing );

		$request = new ITE_Prorate_Subscription_Credit_Request( $subscription, $receiving );
		$request->persist();

		$retrieved = ITE_Prorate_Credit_Request::get( $receiving );

		$this->assertInstanceOf( 'ITE_Prorate_Subscription_Credit_Request', $request );
		$this->assertEquals( $transaction->ID, $retrieved->get_subscription()->get_transaction()->ID );
	}

}