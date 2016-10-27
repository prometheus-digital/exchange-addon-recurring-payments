<?php
/**
 * Test the subscription provider.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class Test_Subscription_Prorate_Credit_Provider
 */
class Test_Subscription_Prorate_Credit_Provider extends IT_Exchange_UnitTestCase {

	public function test_accepts_prorate_credit_request() {

		$subscription_request = $this->getMockBuilder( 'ITE_Prorate_Subscription_Credit_Request' )
		                             ->disableOriginalConstructor()->getMock();

		$request = $this->getMockBuilder( 'ITE_Prorate_Credit_Request' )->disableOriginalConstructor()->getMock();

		$this->assertTrue( IT_Exchange_Subscription::accepts_prorate_credit_request( $subscription_request ) );
		$this->assertFalse( IT_Exchange_Subscription::accepts_prorate_credit_request( $request ) );
	}
	
}