<?php

/*========================================================================
 *
 * Organize core functions
 *
 *=======================================================================*/

new CCS_Core;

class CCS_Core {

	public static $settings;
	public static $state;
	public static $variable;

	function __construct() {

		/*========================================================================
		 *
		 * Global state and variables
		 * 
		 *=======================================================================*/

	}

}


/*========================================================================
 *
 * Global helper functions
 *
 *=======================================================================*/

if (!function_exists('do_short')) {
	function do_short($content) {
		echo do_shortcode($content);
	}
}

if (!function_exists('start_short')) {
	function start_short() {
		ob_start();
	}
}
if (!function_exists('end_short')) {
	function end_short() {
		$out = ob_get_clean();
		echo do_shortcode($out);
	}
}

