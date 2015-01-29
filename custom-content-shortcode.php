<?php
/*
Plugin Name: Custom Content Shortcode
Plugin URI: http://wordpress.org/plugins/custom-content-shortcode/
Description: Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas
Version: 1.7.3
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
	 */

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
	 */

	function load_module( $module ) {

		require_once (CCS_PATH.'/includes/'.$module.'.php');

	}

	function load_main_modules() {

		$modules = array(
			'core/content',			// Content shortcode
			'core/loop',			// Loop shortcode
			'docs/docs',			// Documentation under Settings -> Custom Content
			'modules/attached',		// Attachment loop
			'modules/cache',		// Cache shortcode
			'modules/comments',		// Comments shortcode
			'modules/foreach',		// For/each loop
			'modules/format',		// Format shortcodes: br, p, x, clean, direct, format
			'modules/if',			// If shortcode
			'modules/related',		// Related posts loop
			'modules/url',			// URL shortcode
			'modules/user',			// User shortcodes
			'optional/wck',			// WCK support
		//	'widget'				// Widget shortcode (not ready)
		);

		foreach ($modules as $module) {

			$this->load_module( $module );
		}
	}

	/*========================================================================
	 *
	 * Optional modules
	 *
	 */

	function load_optional_modules() {

		$optional_modules = array(

			// Option name => module name

			'load_acf_module'		=> 'optional/acf',			// Advanced Custom Fields support
			'load_bootstrap_module'	=> 'optional/bootstrap',	// Bootstrap support
			'load_gallery_field'	=> 'optional/gallery',		// Gallery field
			'load_file_loader'		=> 'optional/load',			// Load HTML, CSS, JS fields
			'load_mobile_detect'	=> 'optional/mobile',		// Mobile detect shortcodes
			'raw_shortcode'			=> 'optional/raw',			// [raw]
			'shortcode_unautop'		=> 'optional/unautop',		// Shortcode unautop
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
	 */
	
	function setup_wp_filters() {

		$settings = self::$settings;

		/*========================================================================
		 *
		 * Enable shortcodes in widget
		 *
		 */

		if ( isset( $settings['shortcodes_in_widget'] ) &&
			($settings['shortcodes_in_widget'] == "on") ) {
				
			add_filter('widget_text', 'do_shortcode');
		}

		// Exempt [loop] from wptexturize()
		add_filter( 'no_texturize_shortcodes', array( $this, 'shortcodes_to_exempt_from_wptexturize') );


		/*========================================================================
		 *
		 * Move wpautop filter to after shortcode processing (deprecated)
		 * 
		 * User feedback suggests some themes/plugins don't work well with the
		 * filter moved, because they assume default priority. Instead, use [raw]
		 * or edit code outside of post editor.
		 *
		 */

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
 */

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

