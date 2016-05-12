<?php
/**
 * Test the daily price calculator.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class Test_Daily_Price_Calculator
 */
class Test_Daily_Price_Calculator extends IT_Exchange_UnitTestCase {

	/**
	 * @var ITE_Daily_Price_Calculator
	 */
	protected static $calculator;

	/**
	 * Do custom setup.
	 */
	public static function setUpBeforeClass() {

		self::$calculator = new ITE_Daily_Price_Calculator();

		return parent::setUpBeforeClass();
	}

	/**
	 * @dataProvider _data_provider
	 *
	 * @param $period
	 * @param $count
	 * @param $price
	 * @param $expected
	 */
	public function test( $period, $count, $price, $expected ) {
		$this->assertEquals( $expected, self::$calculator->calculate( new IT_Exchange_Recurring_Profile( $period, $count ), $price ) );
	}

	public function _data_provider() {
		return array(
			array( 'day', 1, 10, 10 ),
			array( 'day', 2, 10, 5 ),
			array( 'day', 5, 10, 2 ),
			array( 'week', 1, 70, 10 ),
			array( 'week', 2, 70, 5 ),
			array( 'month', 1, 300, 10 ),
			array( 'month', 2, 300, 5 ),
			array( 'year', 1, 3650, 10 ),
			array( 'year', 5, 3650, 2 ),
		);
	}
}