<?php

/*========================================================================
 *
 * WCK field shortcode
 *
 *=======================================================================*/

class WCKFieldShortcode {

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

		global $ccs_global_variable;

		extract( shortcode_atts( array(
			'meta' => '',
			'name' => '',
			'id' => '',
		), $atts ) );

		/* Inside repeater? */

		if ( $ccs_global_variable['is_wck_repeater']=='true' ) {

			if (empty($meta))
				$meta = $ccs_global_variable['wck_repeater_meta'];
		
			$key = $ccs_global_variable['wck_repeater_key'];
			if (!empty($ccs_global_variable['wck_repeater_id']))
				$id = $ccs_global_variable['wck_repeater_id'];
		}

		$out = null;
		if ( (!empty($meta)) && (!empty($name)) ) {

			ob_start(); // Store output of the_cfc_field in buffer

			if ($ccs_global_variable['is_wck_repeater']=='true') {

				// In a repeater loop

				if (!empty($id)) the_cfc_field( $meta, $name, $id, $key );
				else the_cfc_field( $meta, $name, false, $key );
			}
			else {

				// Single meta and field

				if (!empty($id)) the_cfc_field( $meta, $name, $id );
				else the_cfc_field( $meta, $name );
			}

			$out = ob_get_clean();
		}
		return $out;
	}


	/*========================================================================
	 *
	 * [wck-repeat]
	 *
	 *=======================================================================*/

	function wck_repeater_shortcode( $atts, $content ) {

		global $ccs_global_variable;

		extract( shortcode_atts( array(
			'meta' => '',
			'id' => ''
		), $atts ) );

		if (empty($meta)) return;

		$out = null;
		$ccs_global_variable['is_wck_repeater']='true';

		$ccs_global_variable['wck_repeater_meta'] = $meta;

		if (!empty($id)) {
			$ccs_global_variable['wck_repeater_id'] = $id;
			$metas = get_cfc_meta( $meta, $id );
		}
		else {
			$ccs_global_variable['wck_repeater_id'] = 0;
			$metas = get_cfc_meta( $meta );
		}

		// Loop through repeater

		foreach ( $metas as $key => $value ) {

			$ccs_global_variable['wck_repeater_key'] = $key;
			$out[] = do_shortcode( $content );
		}

		$ccs_global_variable['is_wck_repeater']='false';
		$ccs_global_variable['wck_repeater_id'] = 0;

		return implode("", $out);
	}

}
new WCKFieldShortcode;


