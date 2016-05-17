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

	public function test_recurring_profile_set_from_transaction_meta() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta' ) )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );
		$transaction->method( 'get_meta' )->willReturnMap( array(
			array( 'interval_1', true, 'month' ),
			array( 'interval_count_1', true, 2 ),
			array( 'has_trial_1', true, true ),
			array( 'trial_interval_1', true, 'week' ),
			array( 'trial_interval_count_1', true, 3 ),
		) );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );

		$this->assertEquals( 'month', $subscription->get_recurring_profile()->get_interval_type() );
		$this->assertEquals( 2, $subscription->get_recurring_profile()->get_interval_count() );

		$this->assertEquals( 'week', $subscription->get_trial_profile()->get_interval_type() );
		$this->assertEquals( 3, $subscription->get_trial_profile()->get_interval_count() );
	}

	public function test_get_start_date() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta' ) )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );
		$transaction->post_date_gmt = '2016-01-01 12:00:00';

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$this->assertEquals( '2016-01-01 12:00:00', $subscription->get_start_date()->format( 'Y-m-d H:i:s' ) );
	}

	public function test_get_expiry_date_past() {

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', true );

		$date = strtotime( 'last month' );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->set_expiry_date( new DateTime( "@$date", new DateTimeZone( 'UTC' ) ) );
		$this->assertEquals( $date, $subscription->get_expiry_date()->getTimestamp() );

		$this->assertArrayHasKey( 'subscription_expired_1', $manager->meta );
		$this->assertContains( $date, $manager->meta['subscription_expired_1'] );
	}

	/**
	 * @depends test_get_expiry_date_past
	 */
	public function test_get_expiry_date_future() {

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', true );

		$date = strtotime( 'next month' );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->set_expiry_date( new DateTime( 'last month' ) );
		$subscription->set_expiry_date( new DateTime( "@$date", new DateTimeZone( 'UTC' ) ) );
		$this->assertEquals( $date, $subscription->get_expiry_date()->getTimestamp() );

		$this->assertArrayHasKey( 'subscription_expires_1', $manager->meta );
		$this->assertArrayNotHasKey( 'subscription_expired_1', $manager->meta );
		$this->assertContains( $date, $manager->meta['subscription_expires_1'] );
	}

	public function test_bump_expiration_date_on_trial_non_auto_renewing() {

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', 'week' );
		$manager->add_meta( 'interval_count_1', 1 );
		$manager->add_meta( 'trial_interval_1', 'day' );
		$manager->add_meta( 'trial_interval_count_1', 3 );
		$manager->add_meta( 'has_trial_1', true );
		$manager->add_meta( 'subscription_autorenew_1', false );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->bump_expiration_date();

		$this->assertEquals( $subscription->get_expiry_date()->getTimestamp(), time() + ( 3 * DAY_IN_SECONDS ), '', 5 );
	}

	public function test_bump_expiration_date_off_trial_non_auto_renewing() {

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', 'week' );
		$manager->add_meta( 'interval_count_1', 1 );
		$manager->add_meta( 'has_trial_1', false );
		$manager->add_meta( 'subscription_autorenew_1', false );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->bump_expiration_date();

		$this->assertEquals( $subscription->get_expiry_date()->getTimestamp(), time() + ( 7 * DAY_IN_SECONDS ), '', 5 );
	}

	public function test_bump_expiration_date_on_trial_auto_renewing() {

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', 'week' );
		$manager->add_meta( 'interval_count_1', 1 );
		$manager->add_meta( 'trial_interval_1', 'day' );
		$manager->add_meta( 'trial_interval_count_1', 3 );
		$manager->add_meta( 'has_trial_1', true );
		$manager->add_meta( 'subscription_autorenew_1', true );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->bump_expiration_date();

		$this->assertEquals( $subscription->get_expiry_date()->getTimestamp(), time() + ( 4 * DAY_IN_SECONDS ), '', 5 );
	}

	public function test_bump_expiration_date_off_trial_auto_renewing() {

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', 'week' );
		$manager->add_meta( 'interval_count_1', 1 );
		$manager->add_meta( 'has_trial_1', false );
		$manager->add_meta( 'subscription_autorenew_1', true );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->bump_expiration_date();

		$this->assertEquals( $subscription->get_expiry_date()->getTimestamp(), time() + ( 8 * DAY_IN_SECONDS ), '', 5 );
	}

	public function test_mark_expired() {

		$date = strtotime( 'last month' );

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', true );
		$manager->add_meta( 'subscription_expires_1', $date );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->mark_expired();
		$this->assertEquals( $date, $subscription->get_expiry_date()->getTimestamp() );

		$this->assertArrayHasKey( 'subscription_expired_1', $manager->meta );
		$this->assertArrayNotHasKey( 'subscription_expires_1', $manager->meta );
		$this->assertContains( $date, $manager->meta['subscription_expired_1'] );
	}

	public function test_get_days_left_in_period_non_auto_renewing() {

		$date = strtotime( '+40 days' );

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', true );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->set_expiry_date( new DateTime( "@$date", new DateTimeZone( 'UTC' ) ) );

		$this->assertEquals( 40, $subscription->get_days_left_in_period() );
	}

	public function test_get_days_left_in_period_auto_renewing() {

		$date = strtotime( '+40 days' );

		$manager = new Mock_Meta_Manager();
		$manager->add_meta( 'interval_1', true );
		$manager->add_meta( 'subscription_autorenew_1', true );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'add_meta', 'update_meta', 'delete_meta' ) )
		                    ->enableProxyingToOriginalMethods()
		                    ->setProxyTarget( $manager )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$subscription->set_expiry_date( new DateTime( "@$date", new DateTimeZone( 'UTC' ) ) );

		$this->assertEquals( 39, $subscription->get_days_left_in_period() );
	}

	public function test_calculate_recurring_amount_paid_for_auto_renewing_with_no_child_payments() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'get_children', 'get_total' ) )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );
		$transaction->method( 'get_meta' )->willReturnMap( array(
			array( 'subscription_autorenew_1', true, true ),
			array( 'interval_1', true, 'month' ),
			array( 'interval_count_1', true, 1 ),
		) );
		$transaction->method( 'get_children' )->willReturn( array() );
		$transaction->method( 'get_total' )->with( false )->willReturn( 5.00 );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$this->assertEquals( 5, $subscription->calculate_recurring_amount_paid() );
	}

	public function test_calculate_recurring_amount_paid_for_auto_renewing_with_child_payments() {

		$child = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_total' ) )
		              ->getMock();
		$child->method( 'get_total' )->with( false )->willReturn( 5.00 );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array( 'meta_exists', 'get_meta', 'get_children', 'get_total' ) )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );
		$transaction->method( 'get_meta' )->willReturnMap( array(
			array( 'subscription_autorenew_1', true, true ),
			array( 'interval_1', true, 'month' ),
			array( 'interval_count_1', true, 1 ),
		) );
		$transaction->method( 'get_children' )->willReturn( array( $child ) );
		$transaction->method( 'get_total' )->with( false )->willReturn( 0 );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$this->assertEquals( 5, $subscription->calculate_recurring_amount_paid() );
	}

	public function test_calculate_recurring_amount_paid_for_non_auto_renewing_with_no_child_payments() {

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array(
			                    'meta_exists',
			                    'get_meta',
			                    'get_children',
			                    'get_total',
			                    'get_products'
		                    ) )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );
		$transaction->method( 'get_meta' )->willReturnMap( array(
			array( 'subscription_autorenew_1', true, false ),
			array( 'interval_1', true, 'month' ),
			array( 'interval_count_1', true, 1 ),
		) );
		$transaction->method( 'get_children' )->willReturn( array() );
		$transaction->method( 'get_total' )->with( false )->willReturn( 7.50 );
		$transaction->method( 'get_products' )->willReturn( array(
			'1-hash' => array(
				'product_id'       => 1,
				'product_subtotal' => 5.00
			)
		) );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$this->assertEquals( 5, $subscription->calculate_recurring_amount_paid() );
	}

	public function test_calculate_recurring_amount_paid_for_non_auto_renewing_with_no_child_payments_coupon() {

		$this->markTestSkipped( "Coupons aren't properly handled yet." );

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( array(
			                    'meta_exists',
			                    'get_meta',
			                    'get_children',
			                    'get_total',
			                    'get_products'
		                    ) )
		                    ->getMock();
		$transaction->method( 'meta_exists' )->willReturn( true );
		$transaction->method( 'get_meta' )->willReturnMap( array(
			array( 'subscription_autorenew_1', true, false ),
			array( 'interval_1', true, 'month' ),
			array( 'interval_count_1', true, 1 ),
		) );
		$transaction->method( 'get_children' )->willReturn( array() );
		$transaction->method( 'get_total' )->with( false )->willReturn( 10.80 );
		$transaction->method( 'get_products' )->willReturn( array(
			'1-hash' => array(
				'product_id'       => 1,
				'product_subtotal' => 5.00
			),
			'2-hash' => array(
				'product_id'       => 2,
				'product_subtotal' => 7.00
			)
		) );

		$product     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$product->ID = 1;

		$subscription = IT_Exchange_Subscription::from_transaction( $transaction, $product );
		$this->assertEquals( 4.50, $subscription->calculate_recurring_amount_paid() );
	}

	public function test_handle_prorate_credit_request() {

		$provider     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$provider->ID = 1;

		$receiving     = $this->getMockBuilder( 'IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$receiving->ID = 1;

		$transaction = $this->getMockBuilder( 'IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$transaction->ID                 = 1;
		$transaction->transaction_method = 'method';

		$profile = new IT_Exchange_Recurring_Profile( 'month', 1 );

		$subscription = $this->getMockBuilder( 'IT_Exchange_Subscription' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array(
			                     'get_product',
			                     'get_customer',
			                     'get_transaction',
			                     'calculate_recurring_amount_paid',
			                     'get_days_left_in_period',
			                     'get_subscriber_id',
			                     'get_recurring_profile',
			                     'is_auto_renewing'
		                     ) )
		                     ->getMock();
		$subscription->method( 'get_product' )->willReturn( $provider );
		$subscription->method( 'get_transaction' )->willReturn( $transaction );
		$subscription->method( 'get_customer' )->willReturn(
			$this->getMockBuilder( 'IT_Exchange_Customer' )->disableOriginalConstructor()->getMock()
		);
		$subscription->method( 'calculate_recurring_amount_paid' )->willReturn( 5.00 );
		$subscription->method( 'get_days_left_in_period' )->willReturn( 15 );
		$subscription->method( 'get_subscriber_id' )->willReturn( 'sub-id' );
		$subscription->method( 'get_recurring_profile' )->willReturn( $profile );
		$subscription->method( 'is_auto_renewing' )->willReturn( true );

		$calculator = $this->getMock( 'ITE_Daily_Price_Calculator' );
		$calculator->expects( $this->once() )->method( 'calculate' )->with( $profile, 5.00 )->willReturn( 0.10 );

		$request = new ITE_Prorate_Subscription_Credit_Request( $subscription, $receiving );

		IT_Exchange_Subscription::handle_prorate_credit_request( $request, $calculator );

		$this->assertEquals( 1.5, $request->get_credit() );

		$this->assertArraySubset( array(
			'old_transaction_id'     => 1,
			'old_transaction_method' => 'method',
			'old_subscriber_id'      => 'sub-id'
		), $request->get_additional_session_details() );
	}
}

class Mock_Meta_Manager {

	public $meta = array();

	public function add_meta( $key, $value, $unique = false ) {

		if ( $unique && isset( $this->meta[ $key ] ) ) {
			return false;
		}

		$this->meta[ $key ][] = $value;

		return true;
	}

	public function get_meta( $key, $single = false ) {

		$value = isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : array();

		if ( $single ) {
			return reset( $value );
		} else {
			return $value;
		}
	}

	public function update_meta( $key, $value ) {
		$this->meta[ $key ] = array();

		$this->meta[ $key ][] = $value;

		return true;
	}

	public function delete_meta( $key, $value = '' ) {

		if ( ! isset( $this->meta[ $key ] ) ) {
			return true;
		}

		if ( $value ) {
			$i = array_search( $key, $this->meta[ $key ], true );

			if ( $i === false ) {
				return false;
			}

			unset( $this->meta[ $key ][ $i ] );
		} else {
			unset( $this->meta[ $key ] );
		}

		return true;
	}

	public function meta_exists( $key ) {
		return isset( $this->meta[ $key ] );
	}

}