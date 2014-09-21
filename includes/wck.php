<?php

/*========================================================================
 *
 * WCK field shortcode
 *
 *=======================================================================*/

new CCS_To_WCK;

class CCS_To_WCK {

	public static $state;

	function __construct() {

		// Wait until all plugins are loaded

		add_action( 'plugins_loaded', array($this, 'wck_exists') );
	}

	function wck_exists() {

		// Is the WCK plugin loaded?

		if (function_exists('the_cfc_field') && function_exists('get_cfc_meta')) {
			add_shortcode( 'wck-field', array($this, 'wck_field_shortcode') );
			add_shortcode( 'wck-repeat', array($this, 'wck_repeater_shortcode') );
		}

	}


	/*========================================================================
	 *
	 * [wck-field]
	 *
	 *=======================================================================*/

	function wck_field_shortcode( $atts ) {

		extract( shortcode_atts( array(
			'meta' => '',
			'name' => '',
			'id' => '',
			'shortcode' => 'false',
		), $atts ) );

		/* Inside repeater? */

		if ( self::$state['is_wck_repeater']=='true' ) {

			// Get meta key

			if (empty($meta))
				$meta = self::$state['wck_repeater_meta'];
		
			$key = self::$state['wck_repeater_key'];
			if (!empty(self::$state['wck_repeater_id']))
				$id = self::$state['wck_repeater_id'];
		}

		$out = null;
		if ( (!empty($meta)) && (!empty($name)) ) {

			ob_start(); // Store output of the_cfc_field in buffer

			// Remove nl2br formatting if shortcode is enabled
			if ($shortcode == 'true') {
				remove_filter( 'wck_output_get_field_textarea', 'wck_preprocess_field_textarea');
			}

			if (self::$state['is_wck_repeater']=='true') {

				// In a repeater loop

				if (!empty($id)) the_cfc_field( $meta, $name, $id, $key );
				else the_cfc_field( $meta, $name, false, $key );
			}
			else {

				// Single meta and field

				if (!empty($id)) the_cfc_field( $meta, $name, $id );
				else the_cfc_field( $meta, $name );
			}

			if ($shortcode == 'true') {
				$out = do_shortcode(ob_get_clean());

				// Put back nl2br formatting
				add_filter( 'wck_output_get_field_textarea', 'wck_preprocess_field_textarea', 10);
			}
			else {
				$out = ob_get_clean();
			}
		}
		return $out;
	}


	/*========================================================================
	 *
	 * [wck-repeat]
	 *
	 *=======================================================================*/

	function wck_repeater_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'meta' => '',
			'id' => ''
		), $atts ) );

		if (empty($meta)) return;

		$out = null;
		self::$state['is_wck_repeater']='true';

		self::$state['wck_repeater_meta'] = $meta;

		if (!empty($id)) {
			self::$state['wck_repeater_id'] = $id;
			$metas = get_cfc_meta( $meta, $id );
		}
		else {
			self::$state['wck_repeater_id'] = 0;
			$metas = get_cfc_meta( $meta );
		}

		// Loop through repeater

		foreach ( $metas as $key => $value ) {

			self::$state['wck_repeater_key'] = $key;
			$out[] = do_shortcode( $content );
		}

		self::$state['is_wck_repeater']='false';
		self::$state['wck_repeater_id'] = 0;

		return implode("", $out);
	}

}
