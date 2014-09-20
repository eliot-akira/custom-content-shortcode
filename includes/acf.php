<?php

/*====================================================================================================
 *
 * Shortcodes for Advanced Custom Fields: gallery, repeater, flexible content
 *
 * To do: test with ACF 5
 * 
 *====================================================================================================*/


new CCS_To_ACF;

class CCS_To_ACF {

	function __construct() {

		add_shortcode('sub', array($this, 'sub_field'));
		add_shortcode('flex', array($this, 'loop_through_acf_field'));
		add_shortcode('repeat', array($this, 'loop_through_acf_field'));
		add_shortcode('repeater', array($this, 'loop_through_acf_field'));

		add_shortcode('acf_gallery', array($this, 'loop_through_acf_gallery_field'));
		add_shortcode('sub_image', array($this, 'get_image_details_from_acf_gallery'));
		add_shortcode('acf_image', array($this, 'get_image_details_from_acf_gallery'));
		add_shortcode('layout', array($this, 'if_get_row_layout'));
		add_shortcode('live-edit', array($this, 'call_live_edit'));

		add_shortcode('related', array($this, 'loop_relationship_field'));
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

		/* For flex and repeater fields */

		extract( shortcode_atts( array(
			'field' => '',
			'count' => '',
			'start' => '',
		), $atts ));

		if ( get_field( $field ) /* && ( strpos($content, '[sub ') !== FALSE ) */ ) {

			$index_now = 0;
			if ( $start == '' ) $start="1";

			while ( has_sub_field( $field ) ) {

				$index_now++;

				if ( $index_now >= $start ) { /* Start loop */

					if ( ( $count!= '' ) && ( $index_now >= ($start+$count) ) ) {
							/* If over count, continue empty looping for has_sub_field */
					} else {

						$output[] = do_shortcode( $content );

					}
				}
			}
		} else {
			$output = $content;
		}
		if( $output != null)
			$output = implode( '', $output );
		return $output;
	}

	public static function loop_through_acf_gallery_field( $atts, $content ) {

		global $ccs_global_variable;

		extract( shortcode_atts( array(
			'field' => '',
			'count' => '',
			'start' => '',
			'sub' => '',
		), $atts ));

		if ($sub=='') {
			$images = get_field( $field );
		} else {
			$images = get_sub_field( $field );
		}

		if ( $images ) {

			$index_now = 0;
			if ( $start == '' ) $start="1";

			foreach ( $images as $image ) {

				$ccs_global_variable['current_image'] = $image;

				$index_now++;

				if ( $index_now >= $start ) { /* Start loop */

					if ( ( $count!= '' ) && ( $index_now >= ($start+$count) ) ) {
							break;				/* If over count, break the loop */
					}

					$output[] = do_shortcode( $content );
				}
			}
		}
		if( $output != null)
			$output = implode( '', $output );

		$ccs_global_variable['current_image'] = '';
		return $output;
	}

	public static function get_image_details_from_acf_gallery( $atts ) {

		global $ccs_global_variable;

		extract(shortcode_atts(array(
			'field' => '',
			'size' => '',
		), $atts));

		if ( $field!='' ) {
				$output = $ccs_global_variable['current_image'][$field];
		} else {

			if ($size=='') {
				$image_url = $ccs_global_variable['current_image']['url'];
			} else {
				$image_url = $ccs_global_variable['current_image']['sizes'][$size];
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


	/*====================================================================================================
	 *
	 * Shortcode support for Live Edit
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

		if( (function_exists('live_edit') && ( (current_user_can('edit_posts')) || ($all=="true") ) &&
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

	function loop_relationship_field( $atts, $content ) {

		global $ccs_global_variable;

		extract( shortcode_atts( array(
			'field' => '',
		), $atts ) );

		if ( (!function_exists('get_field')) && (!empty($field)) )return;

		$out = array();
		$posts = get_field($field);

		if ($posts) {

			$ccs_global_variable['is_relationship_loop'] = 'true';

			foreach ($posts as $post) { // must be named $post

				$ccs_global_variable['relationship_id'] = $post->ID;
//				setup_postdata( $post );
				$out[] = do_shortcode($content);
			}
		}
		$ccs_global_variable['is_relationship_loop'] = 'false';
//		wp_reset_postdata();
		return implode("", $out);
	}

}



