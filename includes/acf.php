<?php

/*====================================================================================================
 *
 * Shortcodes for Advanced Custom Fields
 * 
 * gallery, repeater, flexible content
 *
 * To do: test with ACF 5
 * 
 *====================================================================================================*/

new CCS_To_ACF;

class CCS_To_ACF {

	public static $state;

	function __construct() {

		self::$state['is_relationship_loop'] = 'false';
		self::$state['is_repeater_or_flex_loop'] = 'false';

		if (!function_exists('get_field')) return; // If ACF is not installed

		add_shortcode('sub', array($this, 'sub_field'));
		add_shortcode('flex', array($this, 'loop_through_acf_field'));
		add_shortcode('repeater', array($this, 'loop_through_acf_field'));

		add_shortcode('acf_gallery', array($this, 'loop_through_acf_gallery_field'));
		add_shortcode('acf_image', array($this, 'get_image_details_from_acf_gallery'));
		add_shortcode('sub_image', array($this, 'get_image_details_from_acf_gallery')); // Alias
		add_shortcode('layout', array($this, 'if_get_row_layout'));

		add_shortcode('related', array($this, 'loop_relationship_field'));

		// Legacy - to be removed in a future update
		add_shortcode('live-edit', array($this, 'call_live_edit'));
	}

	public static function sub_field( $atts ) {

		extract(shortcode_atts(array(
			'field' => '',
			'format' => '',
			'image' => '',
			'in' => '',
			'size' => '',
		), $atts));

		if ($image!='') {

			$output = get_sub_field($image);

			if ( $output != '' ) {

				if ($size=='') $size='full';

				switch($in) {
					case 'id' : $output = wp_get_attachment_image( $output, $size ); break;
					case 'url' : $output = '<img src="' . $output . '">'; break;
					default : /* image object */
						if (is_array($output)) {
							$output = wp_get_attachment_image( $output['id'], $size );
						} else {
							$output = wp_get_attachment_image( $output, $size ); // Assume it's ID
						}
				}
			}

		} else {

			$output = get_sub_field($field);

			if ( ($format=='true') && ($output!='') ) {
				$output = wpautop($output);
			}
		}
		return $output;
	}

	public static function loop_through_acf_field( $atts, $content ) {

		/* For repeater and flexible content fields */

		extract( shortcode_atts( array(
			'field' => '',
			'count' => '',
			'start' => '',
			'columns' => '', 'pad' => '', 'between' => '', 
		), $atts ));

		if ( get_field( $field ) /* && ( strpos($content, '[sub ') !== FALSE ) */ ) {

			self::$state['is_repeater_or_flex_loop'] = 'true';
			$index_now = 0;
			$outputs = array();

			if ( $start == '' ) $start='1';

			while ( has_sub_field( $field ) ) {

				$index_now++;

				if ( $index_now >= $start ) { /* Start loop */

					if ( ( !empty($count) ) && ( $index_now >= ($start+$count) ) ) {
							/* If over count, continue empty looping for has_sub_field */
					} else {

						$outputs[] = do_shortcode(str_replace('{COUNT}', $index_now, $content));

					}
				}
			}
			self::$state['is_repeater_or_flex_loop'] = 'false';

		} else {
			return null;
		}
		if( !empty($outputs) && is_array($outputs)) {

			if (!empty($columns))
				$output = CCS_Loop::render_columns( $outputs, $columns, $pad, $between );
			else
				$output = implode( '', $outputs );
		}
		return $output;
	}

	public static function loop_through_acf_gallery_field( $atts, $content ) {

		extract( shortcode_atts( array(
			'field' => '',
			'count' => '',
			'start' => '',
			'subfield' => '',
			'sub' => '',
			'columns' => '', 'pad' => '', 'between' => '', 
		), $atts ));


		// If in repeater or flexible content, get subfield by default
		if ( self::$state['is_repeater_or_flex_loop']=='true' ) {
			$sub = 'true';
		}

		// Backward compatibility
		if (!empty($subfield)) {
			$field = $subfield;
			$sub = 'true';
		}

		if (empty($sub)) {
			$images = get_field( $field );
		} else {
			$images = get_sub_field( $field );
		}

		$outputs = array();

		if ( $images ) {

			$index_now = 0;
			if ( $start == '' ) $start='1';

			foreach ( $images as $image ) {

				self::$state['current_image'] = $image;
				$index_now++;

				if ( $index_now >= $start ) { /* Start loop */

					if ( ( $count!= '' ) && ( $index_now >= ($start+$count) ) ) {
							break;				/* If over count, break the loop */
					}

					$outputs[] = do_shortcode(str_replace('{COUNT}', $index_now, $content));
				}
			}
		}
		if( !empty($outputs) && is_array($outputs)) {

			if (!empty($columns))
				$output = CCS_Loop::render_columns( $outputs, $columns, $pad, $between );
			else
				$output = implode( '', $outputs );
		} else {
			$output = $outputs;
		}

		self::$state['current_image'] = '';
		return $output;
	}

	public static function get_image_details_from_acf_gallery( $atts ) {

		extract(shortcode_atts(array(
			'field' => '',
			'size' => '',
		), $atts));

		if ( $field!='' ) {
				$output = self::$state['current_image'][$field];
		} else {

			if ($size=='') {
				$image_url = self::$state['current_image']['url'];
			} else {
				$image_url = self::$state['current_image']['sizes'][$size];
			}

			$output = '<img src="' . $image_url . '">';

		}
		return $output;
	}

	public static function if_get_row_layout( $atts, $content ) {

		extract(shortcode_atts(array(
			'name' => '',
		), $atts));

		if( get_row_layout() == $name ) {
			return do_shortcode( $content );
		} else {
			return null;
		}
	}

	function loop_relationship_field( $atts, $content ) {

		extract( shortcode_atts( array(
			'field' => '',
			'subfield' => '',
		), $atts ) );

		$output = array();

		// If in repeater or flexible content, get subfield by default
		if ( self::$state['is_repeater_or_flex_loop']=='true' ) {
			if (empty($subfield)) {
				$subfield = $field;
				$field = null;
			}
		}

		if (!empty($field)) {
			$posts = get_field($field);
		} elseif (!empty($subfield)) {
			$posts = get_sub_field($subfield);
		} else return null;


		if ($posts) {

			self::$state['is_relationship_loop'] = 'true';

			$index_now = 0;

			foreach ($posts as $post) { // must be named $post

				$index_now++;

				self::$state['relationship_id'] = $post->ID;

				$replaced_content = do_shortcode($content);
				$output[] = str_replace('{COUNT}', $index_now, $replaced_content);
			}
		}

		self::$state['is_relationship_loop'] = 'false';

		if (is_array($output))
			$output = implode('', $output);
		return $output;
	}


	/*====================================================================================================
	 *
	 * Live Edit shortcode (legacy)
	 *
	 *====================================================================================================*/

	public static function call_live_edit($atts, $inside_content = null) {
		extract(shortcode_atts(array(
			'field' => '',
			'admin' => '',
			'editor' => '',
			'edit' => '',
			'only' => '',
			'content' => '',
			'title' => '',
			'all' => '',
		), $atts));

		if( (function_exists('live_edit') && ( (current_user_can('edit_posts')) || ($all=='true') ) &&
			($edit!='off')) ){

			$edit_field = '';

			if(($title!='false')&&($title!='off')) {
				$edit_field .= 'post_title,';	
			}
			if(($content!='false')&&($content!='off')) {
				$edit_field .= 'post_content,';	
			}

			if($admin!=''){
				if ( current_user_can( 'manage_options' ) ) { // Admin user
					$edit_field .= $admin;
				} else { // Editor
					if(($editor=='') && ($only=='')) { // Edit only for admin
						return do_shortcode($inside_content);
					}
					if($editor!='') {
						$edit_field .= $editor;
					}
					if($only != '') {
						$edit_field = $only;
					}
				}
			} else {			if($field != '') {
					$edit_field .= $field;
				}
				if($only != '') {
					$edit_field = $only;
				}
			}
			$edit_field = trim($edit_field, ',');
			$output = '<div ';
			$output .= live_edit($edit_field);
			$output .= '>';
			$output .= do_shortcode($inside_content) . '</div>';

			return $output;
		} else {
			return do_shortcode($inside_content);
		}
	}

}

