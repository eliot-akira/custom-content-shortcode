<?php 

/*========================================================================
 *
 * Cache
 *
 *=======================================================================*/

new CCS_Cache;

class CCS_Cache {

	private static $start;			// Time at [timer start]
	private static $mem;			// Memory usage at [timer start]

	private static $num_queries;	// Number of queries at init

	private static $transient_prefix;

	function __construct() {

		self::$transient_prefix = 'ccs_';
		add_shortcode( 'cache', array( $this, 'cache_shortcode') );

		self::$num_queries = get_num_queries();
		add_shortcode( 'timer', array( $this, 'timer_shortcode') );
	}
	
	function cache_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'name' => '',
			'expire' => '10 min',
			'update' => 'false'
		), $atts ) );

		if (empty($name)) return; // Needs a transient name

		$result = false;

		if ($update != 'true')
			$result = self::get_transient( $name ); // get cache if update not true

		if ( false === $result ) {

			$result = do_shortcode( $content );

			self::set_transient( $name, $result, $expire );
		}

		return $result;
	}

	public static function get_expire_time( $expire ) {

		$expire = explode(" ", $expire);

		$expire_sec = $expire[0];

		if (count($expire)>1) {

			switch ($expire[1]) {
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




	/*========================================================================
	 *
	 * Timer
	 *
	 *=======================================================================*/


	function timer_shortcode( $atts ) {

		$x = count($atts);
		$out = null;

		for ($i=0; $i < $x; $i++) { 
			$action = isset($atts[$i]) ? $atts[$i] : null;
			switch ($action) {
				case 'start':
					self::$start = microtime(true); // start timer
					self::$mem = memory_get_peak_usage(TRUE); // current memory usage
					break;
				case 'end':
				case 'info':
					$out = self::info();
					break;
				case 'total':
					$out = self::total_info();
			}
		}
		return $out;
	}


	function info() {
		$now_queries = get_num_queries();
		return sprintf(
			'Time: %s, Memory: %4.2fMb - Queries: %d',
			self::human_time(microtime(true) - self::$start),
			(memory_get_peak_usage(TRUE) - self::$mem) / 1048576,
			( $now_queries - self::$num_queries )
		);
	}

	function total_info() {
		return sprintf(
			'Total time: %.3f sec, Total Memory: %4.2fMb, Total Queries: %d',
			timer_stop(0),
//			self::human_time(microtime(true) - self::$total_start),
			(memory_get_peak_usage(TRUE)) / 1048576,
			get_num_queries()
		);
	}

	function human_time($time) {
		$times = array(
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
		return $ms . ' ms';
	}
}

