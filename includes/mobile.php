<?php

/*========================================================================
 *
 * Mobile detect shortcodes
 *
 */

new CCS_Mobile_Detect;

class CCS_Mobile_Detect {

	public static $detect;
	public static $device_type;

	function __construct() {

		// Only on frontend

		if ( !is_admin() ) {	

			add_filter( 'body_class', array($this, 'mobile_body_class') );

			/*========================================================================
			 *
			 * Set up mobile detect library
			 *
			 */

			if (!class_exists('Mobile_Detect')) {
				require_once (CCS_PATH.'/includes/Mobile_Detect.php');	
			}

			self::$detect = new Mobile_Detect();
			self::$device_type = (self::$detect->isMobile() ? (self::$detect->isTablet() ? 'tablet' : 'phone') : 'computer');

			add_shortcode( 'is_mobile', array($this, 'is_mobile') );
			add_shortcode( 'is_phone', array($this, 'is_phone') );
			add_shortcode( 'isnt_phone', array($this, 'isnt_phone') );
			add_shortcode( 'is_tablet', array($this, 'is_tablet') );
			add_shortcode( 'is_computer', array($this, 'is_computer') );
		}
	}



	/* .is_phone, .is_tablet, .is_mobile, .isnt_phone, .is_computer */

	function mobile_body_class($classes) {

		if ( ( self::$device_type=='phone' ) || ( self::$device_type=='tablet') )
			$classes[] = 'is_mobile';
		if ( self::$device_type!='phone' )
			$classes[] = 'isnt_phone';
		$classes[] = 'is_' . self::$device_type;

		return $classes;
	}

	/* is_phone, is_tablet, is_mobile, isnt_phone, is_computer */

	function is_mobile( $atts, $content ) {

		if ( ( self::$device_type=='phone' ) || ( self::$device_type=='tablet') ) return do_shortcode($content);

	}

	function is_phone( $atts, $content ) {

		if ( self::$device_type=='phone' ) return do_shortcode($content);

	}

	function isnt_phone( $atts, $content ) {

		if ( self::$device_type!='phone' ) return do_shortcode($content);

	}

	function is_tablet( $atts, $content ) {

		if ( self::$device_type=='tablet' ) return do_shortcode($content);

	}

	function is_computer( $atts, $content ) {

		if ( self::$device_type=='computer' ) return do_shortcode($content);

	}
	
}
