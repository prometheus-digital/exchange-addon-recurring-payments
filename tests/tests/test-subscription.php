<?php
/**
 * Test the subscription class.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class Test_Subscription
 */
class Test_Subscription extends IT_Exchange_UnitTestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_create_throws_exception_if_transaction_has_multiple_products_and_none_specified() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->method( 'get_products' )->willReturn( array(
			'1-hash' => array(
				'product_id' => 1
			),
			'2-hash' => array(
				'product-id' => 2
			)
		) );

		IT_Exchange_Subscription::create( $transaction );
	}

	/**
	 * @expectedException UnexpectedValueException
	 */
	public function test_create_throws_exception_if_given_product_not_found_in_transaction() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->method( 'get_products' )->willReturn( array(
			'1-hash' => array(
				'product_id' => 1
			)
		) );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 2;

		IT_Exchange_Subscription::create( $transaction, $product );
	}

	/**
	 * @expectedException UnexpectedValueException
	 * @expectedExceptionCode 2
	 */
	public function test_create_throws_exception_if_product_doesnt_support_recurring_payments() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->method( 'get_products' )->willReturn( array(
			'1-hash' => array(
				'product_id' => 1
			)
		) );
		$transaction->method( 'supports_feature' )->with( 'recurring-payments' )->willReturn( false );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		IT_Exchange_Subscription::create( $transaction, $product );
	}

	/**
	 * @expectedException UnexpectedValueException
	 * @expectedExceptionCode 2
	 */
	public function test_create_throws_exception_if_product_doesnt_have_recurring_payments_enabled() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->method( 'get_products' )->willReturn( array(
			'1-hash' => array(
				'product_id' => 1
			)
		) );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;
		$product->method( 'supports_feature' )->with( 'recurring-payments' )->willReturn( true );
		$product->method( 'get_feature' )->with( 'recurring-payments', array( 'setting' => 'recurring-enabled' ) )
		        ->willReturn( false );

		IT_Exchange_Subscription::create( $transaction, $product );
	}

	public function test_transaction_meta_updated_from_recurring_product_feature() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->method( 'get_products' )->willReturn( array(
			'1-hash' => array(
				'product_id' => 1
			)
		) );
		$transaction->method( 'update_meta' )->withConsecutive(
			array( 'has_trial_1', true ),
			array( 'is_auto_renewing_1', 'on' ),
			array( 'subscription_autorenew_1', true ),
			array( 'interval_1', 'month' ),
			array( 'interval_count_1', 2 ),
			array( 'trial_interval_1', 'week' ),
			array( 'trial_interval_count_1', 3 )
		);

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;
		$product->method( 'supports_feature' )->with( 'recurring-payments' )->willReturn( true );
		$product->method( 'get_feature' )->willReturnMap( array(
			array( 'recurring-payments', array( 'setting' => 'recurring-enabled' ), true ),
			array( 'recurring-payments', array( 'setting' => 'trial-enabled' ), true ),
			array( 'recurring-payments', array( 'setting' => 'auto-renew' ), 'on' ),
			array( 'recurring-payments', array( 'setting' => 'interval' ), 'month' ),
			array( 'recurring-payments', array( 'setting' => 'interval-count' ), 2 ),
			array( 'recurring-payments', array( 'setting' => 'trial-interval' ), 'week' ),
			array( 'recurring-payments', array( 'setting' => 'trial-interval-count' ), 3 ),
		) );

		IT_Exchange_Subscription::create( $transaction, $product );
	}


}