<?php 

/*========================================================================
 *
 * Cache
 *
 *=======================================================================*/

new CCS_Cache_Shortcode;

class CCS_Cache_Shortcode {

	function __construct() {

		add_shortcode( 'cache', array( $this, 'cache_shortcode') );
	}
	
	function cache_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'name' => '',
			'expire' => '',
			'update' => 'false'
		), $atts ) );

		if (empty($name)) return; // Needs a transient name

		$prefix = 'ccs_';
		$cache = $prefix.$name;

		if ($update == 'true')
			$result = false;
		else
			$result = get_transient( $cache );

		// If no cache or update="true", store the content

		if ( false === $result ) {

			$result = do_shortcode( $content );

			// Get expire time in seconds

			if ( empty($expire) ) $expire = 30 * 60; // Default: 30 min
			else {

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
			}

			set_transient( $cache, $result, $expire_sec );
		}

		return $result;
	}
}

