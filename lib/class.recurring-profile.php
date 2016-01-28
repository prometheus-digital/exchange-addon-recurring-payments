<?php
/**
 * Recurring profile class.
 *
 * @since   1.8
 * @license GPLv2
 */

/**
 * Class IT_Exchange_Recurring_Profile
 */
class IT_Exchange_Recurring_Profile {

	const TYPE_DAY = 'day';
	const TYPE_WEEK = 'week';
	const TYPE_MONTH = 'month';
	const TYPE_YEAR = 'year';

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var int
	 */
	private $count;

	/**
	 * IT_Exchange_Recurring_Profile constructor.
	 *
	 * @param $type
	 * @param $count
	 */
	public function __construct( $type, $count ) {

		if ( $count <= 0 ) {
			throw new InvalidArgumentException( 'Usage: count > 0' );
		}

		$this->type  = $type;
		$this->count = $count;
	}

	/**
	 * Get the interval type.
	 *
	 * Eg. 2 _weeks_.
	 *
	 * @since 1.8
	 *
	 * @param bool $label
	 *
	 * @return string
	 */
	public function get_interval_type( $label = false ) {

		if ( $label ) {
			$labels = self::get_interval_type_labels();

			return $labels[ $this->type ];
		} else {
			return $this->type;
		}
	}

	/**
	 * Get the number of intervals.
	 *
	 * Eg. _2_ weeks.
	 *
	 * @since 1.8
	 *
	 * @return int
	 */
	public function get_interval_count() {
		return (int) $this->count;
	}

	/**
	 * Get the total seconds this interval represents.
	 *
	 * @since 1.8
	 *
	 * @return int
	 */
	public function get_interval_seconds() {

		switch ( $this->get_interval_type() ) {
			case self::TYPE_DAY:
				$seconds = DAY_IN_SECONDS;
				break;
			case self::TYPE_WEEK:
				$seconds = DAY_IN_SECONDS * 7;
				break;
			case self::TYPE_MONTH:
				$seconds = DAY_IN_SECONDS * 30;
				break;
			case self::TYPE_YEAR:
				$seconds = YEAR_IN_SECONDS;
				break;
			default:

				$type = $this->get_interval_type();

				$seconds = apply_filters( 'it_exchange_get_recurirng_profile_interval_seconds', 0, $type );
		}

		return $seconds * $this->get_interval_count();
	}

	/**
	 * Get the interval represented as a strtotime compatible string.
	 *
	 * Ideally, this would be a DateInterval object, but PHP 5.2
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function get_interval() {
		return "+ {$this->get_interval_count()} {$this->get_interval_type()}s";
	}

	/**
	 * Get a human readable display of this profile.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function __toString() {

		$interval_label = $this->get_interval_type( true );
		$interval_count = $this->get_interval_count();

		if ( $interval_count === 1 ) {
			$label = sprintf( __( 'every %s', 'LION' ), $interval_label );
		} else {
			$label = sprintf( __( 'every %d %s', 'LION' ), $interval_count, $interval_label . 's' );
		}

		return $label;
	}

	/**
	 * Get the interval type labels.
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	protected static function get_interval_type_labels() {

		$interval_types = array(
			'day'   => __( 'Day', 'LION' ),
			'week'  => __( 'Week', 'LION' ),
			'month' => __( 'Month', 'LION' ),
			'year'  => __( 'Year', 'LION' ),
		);

		return apply_filters( 'it_exchange_recurring_payments_interval_types_labels', $interval_types );
	}
}