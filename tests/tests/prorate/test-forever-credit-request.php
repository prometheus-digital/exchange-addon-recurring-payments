<?php
/**
 * Test the Forever Credit Request class.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class Test_Prorate_Forever_Credit_Request
 */
class Test_Prorate_Forever_Credit_Request extends IT_Exchange_UnitTestCase {

	public function test_retrieve_from_session() {

		$providing = $this->product_factory->create_and_get();
		$receiving = $this->product_factory->create_and_get();

		$transaction = $this->transaction_factory->create_and_get( array(
			'cart_object' => (object) array(
				'products' => array(
					"{$providing->ID}-hash" => array(
						'product_id' => $providing->ID,
					)
				)
			)
		) );

		$request = new ITE_Prorate_Forever_Credit_Request( $providing, $receiving, $transaction );

		$request->persist();

		$retrieved = ITE_Prorate_Credit_Request::get( $receiving );

		$this->assertEquals( $transaction->ID, $retrieved->get_transaction()->ID );
	}
}