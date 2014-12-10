<?php
/*
Plugin Name: Custom Content Shortcode
Plugin URI: http://wordpress.org/plugins/custom-content-shortcode/
Description: Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas
Version: 1.5.7
Shortcodes: loop, content, field, taxonomy, if, for, each, comments, user, url, load
Author: Eliot Akira
Author URI: eliotakira.com
License: GPL2
*/

define('CCS_PATH', dirname(__FILE__));
define('CCS_URL', untrailingslashit(plugins_url('/',__FILE__)));

new CCS_Plugin;

class CCS_Plugin {

	public static $settings;

	function __construct() {

		$this->load_settings();
		$this->load_main_modules();
		$this->load_optional_modules();
		$this->setup_wp_filters();
	}


	/*========================================================================
	 *
	 * Load settings
	 *
	 *=======================================================================*/

	function load_settings() {

		$option_key = 'ccs_content_settings';

		self::$settings = get_option( $option_key );

		if ( self::$settings === false ) {

			// Default settings

			self::$settings['load_acf_module'] = 'on';
			self::$settings['load_bootstrap_module'] = 'on';
			self::$settings['load_file_loader'] = 'on';
			self::$settings['load_gallery_field'] = 'on';

			self::$settings['load_mobile_detect'] = 'off';

			self::$settings['shortcodes_in_widget'] = 'on';

			self::$settings['raw_shortcode'] = 'off';
			self::$settings['move_wpautop'] = 'off';
			self::$settings['shortcode_unautop'] = 'off';

			update_option( $option_key, self::$settings );
		}
	}


	/*========================================================================
	 *
	 * Load main and optional modules
	 *
	 *=======================================================================*/

	function load_module( $module ) {

		require_once (CCS_PATH.'/includes/'.$module.'.php');

	}

	function load_main_modules() {

		$main_modules = array(
			'content',		// Content shortcode
			'loop',			// Loop shortcode
			'attached',		// Attachment loop
			'comments',		// Comments shortcode
			'related',		// Related posts loop
			'user',			// User shortcodes
			'url',			// URL shortcode
			'foreach',		// For/each loop
			'if',			// If shortcode
			'docs',			// Documentation under Settings -> Custom Content
			'cache',		// Cache shortcode
			'format',		// Format shortcodes: br, p, x, clean, direct, format
			'wck',			// WCK support
		//	'widget'		// Widget shortcode (not ready)
		);

		foreach ($main_modules as $module) {

			$this->load_module( $module );
		}
	}

	/*========================================================================
	 *
	 * Optional modules
	 *
	 *=======================================================================*/

	function load_optional_modules() {

		$optional_modules = array(

			// Option name => module name

			'load_gallery_field'	=> 'gallery',		// Gallery field
			'load_acf_module'		=> 'acf',			// Advanced Custom Fields support
			'load_file_loader'		=> 'load',			// Load HTML, CSS, JS fields
			'load_mobile_detect'	=> 'mobile',		// Mobile detect shortcodes
			'load_bootstrap_module'	=> 'bootstrap',		// Bootstrap support
			'shortcode_unautop'		=> 'unautop',		// Shortcode unautop
			'raw_shortcode'			=> 'raw',			// [raw]
		);

		foreach ($optional_modules as $option => $module) {

			if ( isset(self::$settings[ $option ]) && self::$settings[ $option ]=='on' ) {

				$this->load_module( $module );
			}
		}
	}


	/*========================================================================
	 *
	 * Set up WP filters
	 *
	 *=======================================================================*/
	
	function setup_wp_filters() {

		$settings = self::$settings;

		/*========================================================================
		 *
		 * Enable shortcodes in widget
		 *
		 *=======================================================================*/

		if ( isset( $settings['shortcodes_in_widget'] ) &&
			($settings['shortcodes_in_widget'] == "on") ) {
				
			add_filter('widget_text', 'do_shortcode');
		}

		// Exempt [loop] from wptexturize()
		add_filter( 'no_texturize_shortcodes', array( $this, 'shortcodes_to_exempt_from_wptexturize') );


		/*========================================================================
		 *
		 * Move wpautop filter to after shortcode processing (legacy)
		 * 
		 * User feedback suggests some themes/plugins don't work well with the
		 * filter moved, because they assume default priority. Instead, use [raw]
		 * or edit code outside of post editor.
		 *
		 *=======================================================================*/

		if ( isset( $settings['move_wpautop'] ) &&
			($settings['move_wpautop'] == "on") ) {

			remove_filter( 'the_content', 'wpautop' );
			add_filter( 'the_content', 'wpautop' , 99);
			add_filter( 'the_content', 'shortcode_unautop',100 );
		}

	}

	function shortcodes_to_exempt_from_wptexturize($shortcodes){
		$shortcodes[] = 'loop';
		return $shortcodes;
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
		do_short($out);
	}
}

