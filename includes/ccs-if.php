<?php


class IfShortcode {

	function __construct() {

		global $ccs_global_variable;
		$ccs_global_variable['if_flag'] = '';

		add_action( 'init', array( &$this, 'register' ) );
	}

	function register() {
		add_shortcode( 'if', array( &$this, 'if_shortcode' ) );
		add_shortcode( 'flag', array( &$this, 'flag_shortcode' ) );
	}

	function if_shortcode( $atts, $content = null, $shortcode_name ) {

		global $ccs_global_variable;

		$args = array(
			'flag' => '',
			'no_flag' => '',
			'every' => '',
			'type' => '',
			'name' => '',
			'category' => '',
			'taxonomy' => '',
			'term' => '',
			'not' => '',
		);

		extract( shortcode_atts( $args , $atts, true ) );

		if (is_array($atts)) $atts = array_flip($atts); /* To allow check for parameters with no value set */
/*
//		if ( (empty($flag))&&(empty($no_flag)) || (isset($atts['empty']))) return;
		if ((isset($atts['empty'])) || (isset($atts['last'])) ) return; // [if empty] [if last] is processed by [loop]
*/
		if (!empty($no_flag)) $flag = $no_flag;
		$out = '';
		$condition = false;

		// If we're inside loop shortcode

		if ($ccs_global_variable['is_loop']=="true") {

			if (!empty($every)) {

				$count = $ccs_global_variable['current_loop_count'];

				if (substr($every,0,4)=='not ') {
					$every = substr($every, 4); // Remove first 4 letters

					// not Modulo
 					$condition = ($every==0) ? false : (($count % $every)!=0);
				} else {

					// Modulo
					$condition = ($every==0) ? false : (($count % $every)==0);
				}

				if ($condition) {
					$out = do_shortcode( $content );
				}

				return $out;

			} elseif (!empty($flag)) {

				// Check custom field as flag

				$current_id = $ccs_global_variable['current_loop_id'];
				$check = get_post_meta( $current_id, $flag, true );

				if ((!empty($check)) && (!empty($no_flag))) return;
				if ((empty($check)) && (empty($no_flag))) return;
				else {
					$ccs_global_variable['if_flag'] = $check;
					$out = do_shortcode( $content );
					$ccs_global_variable['if_flag'] = '';
					return $out;
				}

			}

		}

		global $post;

		$current_post_type = isset($post->post_type) ? $post->post_type : null;
		$current_post_name = isset($post->post_name) ? $post->post_name : null;

		if(isset($atts['home'])){
			$condition = is_front_page();
		}

		if ( ($type == $current_post_type) || ($name == $current_post_name) ) {
			$condition = true;
		}

		if(isset($atts['not'])){
			$condition = (! $condition);
		}

		if ($condition) {
			return do_shortcode( $content );
		}
	}

	function flag_shortcode() {
		global $ccs_global_variable;
		return $ccs_global_variable['if_flag'];
	}

}
new IfShortcode;

