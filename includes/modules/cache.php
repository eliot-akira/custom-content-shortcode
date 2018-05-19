<?php

/*---------------------------------------------
 *
 * [cache] - Store anything in transient cache
 * [timer] - Simple timer to measure queries and memory consumption
 *
 */

new CCS_Cache;

class CCS_Cache {

	private static $start; // Time at [timer start]
	private static $mem; // Memory usage at [timer start]

	private static $num_queries; // Number of queries at init
	private static $transient_prefix; // Cache name prefix

	function __construct() {

    add_ccs_shortcode( array(
			'cache' => array( $this, 'cache_shortcode'),
			'timer' => array( $this, 'timer_shortcode'),
      '' => '',
		));

		self::$transient_prefix = 'ccs_';
		self::$num_queries = get_num_queries(); // Number of queries at init
	}

	function cache_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'name' => '',
			'expire' => '10 min', // Default
			'update' => 'false'
		), $atts ) );

		if (empty($name)) return; // Needs a transient name

		$result = false;
		if (is_array($atts)) $atts = CCS_Content::get_all_atts($atts);
		$update = (isset($atts['update'])) ? 'true' : $update;

		if ($update != 'true') {
			$result = self::get_transient( $name ); // get cache if update not true
		}

		if ( false === $result ) {

			$result = do_ccs_shortcode( $content );

			self::set_transient( $name, $result, $expire );
		}

		return $result;
	}

	public static function get_expire_time( $expire ) {

		$expire = explode(" ", $expire);

		$expire_sec = $expire[0];

		if (count($expire)>1) {

			switch ($expire[1]) {
				case 'minute':
				case 'minutes':
				case 'mins':
				case 'min': $expire_sec *= 60; break;
				case 'hours':
				case 'hour': $expire_sec *= 60 * 60; break;
				case 'days':
				case 'day': $expire_sec *= 60 * 60 * 24; break;
				case 'months':
				case 'month': $expire_sec *= 60 * 60 * 24 * 30; break;
				case 'years':
				case 'year': $expire_sec *= 60 * 60 * 24 * 30 * 365; break;
			}
		}
		return $expire_sec;
	}

	public static function get_transient( $name ) {

		return get_transient( self::$transient_prefix.$name );
	}

	public static function set_transient( $name, $value, $expire ) {

		return set_transient( self::$transient_prefix.$name, $value, self::get_expire_time( $expire ) );
	}




	/*---------------------------------------------
	 *
	 * Timer
	 *
	 */


	function timer_shortcode( $atts ) {

		if (empty($atts)) $atts[0] = 'start';
		$x = count($atts);
		$out = null;

		for ($i=0; $i < $x; $i++) {
			$action = isset($atts[$i]) ? $atts[$i] : null;
			switch ($action) {
				case 'stop':
				case 'end':
					$out = self::stop_timer();
					break;
				case 'start':
					$out = self::start_timer();
					break;
				case 'total':
				default:
					$out = self::total_info();
					break;
			}
		}
		return $out;
	}

	public static function start_timer() {
		self::$start = microtime(true); // start timer
		self::$mem = memory_get_peak_usage(TRUE); // current memory usage
		self::$num_queries = get_num_queries(); // Number of queries at timer start
	}


	public static function stop_timer( $message = null ) {

		$now_queries = get_num_queries();

		if (empty($message)) $message = '<b>Timer stop</b>: ';

		return sprintf(
			'%s%s - %4.2f Mb - %d queries',
			$message,
			self::human_time(microtime(true) - self::$start),
			(memory_get_peak_usage(TRUE) - self::$mem) / 1048576,
			( $now_queries - self::$num_queries )
		);
	}

	public static function total_info() {
		return sprintf(
			'<b>Current total</b>: %.3f sec - %4.2f Mb - %d queries',
			timer_stop(0),
//			self::human_time(microtime(true) - self::$total_start),
			(memory_get_peak_usage(TRUE)) / 1048576,
			get_num_queries()
		);
	}

	public static function human_time($time) {

		$ms = round($time * 1000);
		$sec = $ms / 1000; // Round off to thousandth
		return $sec . ' sec';

/*		$times = array(
			'hour' => 3600000,
			'minute' => 60000,
			'second' => 1000
		);

		$ms = round($time * 1000);
		foreach ($times as $unit => $value) {
			if ($ms >= $value) {
				$time = floor($ms / $value * 1000.0) / 1000.0;
				return $time . ' ' . ($time == 1 ? $unit : $unit . 's');
			}
		}
		return $ms/1000 . ' sec'; */
	}
}
