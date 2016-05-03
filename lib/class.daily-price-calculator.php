<?php
/**
 * Contains the daily price calculator.
 *
 * @since   1.9
 * @license GPLv2
 */

/**
 * Class ITE_Daily_Price_Calculator
 */
class ITE_Daily_Price_Calculator {

	/**
	 * Calculate the daily price of a subscription given a profile.
	 *
	 * @since 1.9
	 *
	 * @param IT_Exchange_Recurring_Profile $profile Main recurring profile to use.
	 * @param float                         $cost    Cost of the subscription per profile period.
	 *
	 * @return float
	 */
	public function calculate( IT_Exchange_Recurring_Profile $profile, $cost ) {

		switch ( $profile->get_interval_type() ) {
			case IT_Exchange_Recurring_Profile::TYPE_WEEK:
				$cost /= 7;
				break;
			case IT_Exchange_Recurring_Profile::TYPE_MONTH:
				$cost /= 30;
				break;
			case IT_Exchange_Recurring_Profile::TYPE_YEAR:
				$cost /= (int) date_i18n( 'z', mktime( 0, 0, 0, 12, 31, date_i18n( 'Y' ) ) );
				break;
		}

		$cost /= $profile->get_interval_count();

		$days_this_year = date_i18n( 'z', mktime( 0, 0, 0, 12, 31, date_i18n( 'Y' ) ) );

		return $cost / $days_this_year;
	}

}