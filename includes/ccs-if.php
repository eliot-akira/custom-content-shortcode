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
			'every' => '',
			'flag' => '',
			'no_flag' => '',

			'type' => '',
			'name' => '',

			'category' => '',
			'tag' => '',
			'taxonomy' => '',
			'term' => '',
			'compare' => 'OR',

			'field' => '',
			'value' => '',

			'not' => '',
		);

		extract( shortcode_atts( $args , $atts, true ) );

		if (is_array($atts)) $atts = array_flip($atts); /* To allow check for parameters with no value set */
/*
//		if ( (empty($flag))&&(empty($no_flag)) || (isset($atts['empty']))) return;
		if ((isset($atts['empty'])) || (isset($atts['last'])) ) return; // [if empty] [if last] is processed by [loop]
*/
		if (!empty($no_flag)) $flag = $no_flag;
		$compare = strtoupper($compare);

		$out = '';
		$condition = false;

		/*========================================================================
		 *
		 * If we're inside loop shortcode
		 *
		 *=======================================================================*/

		if ($ccs_global_variable['is_loop']=="true") {

			if (!empty($every)) {

				/*========================================================================
				 *
				 * Every X number of posts in [loop]
				 *
				 *=======================================================================*/

				$count = $ccs_global_variable['current_loop_count'];

				if (substr($every,0,4)=='not ') {
					$every = substr($every, 4); // Remove first 4 letters

					// not Modulo
 					$condition = ($every==0) ? false : (($count % $every)!=0);
				} else {

					// Modulo
					$condition = ($every==0) ? false : (($count % $every)==0);
				}

				if (isset($atts['not'])) {
					$condition = !$condition;
				}

				if ($condition) {
					$out = do_shortcode( $content );
				}

				return $out;

			} elseif (!empty($flag)) {

				/*========================================================================
				 *
				 * Check field as condition [if flag="field"]
				 *
				 *=======================================================================*/

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

		} // End [loop] only conditions

		/*========================================================================
		 *
		 * Get global post info
		 *
		 *=======================================================================*/

		global $post;

		if (empty($post)) return; // Make sure post exists

		$current_post_type = isset($post->post_type) ? $post->post_type : null;
		$current_post_name = isset($post->post_name) ? $post->post_name : null;
		$current_post_id = isset($post->ID) ? $post->ID : null;


		/*========================================================================
		 *
		 * Taxonomy: category, tags, ..
		 *
		 *=======================================================================*/
		
		if (!empty($category)) {
			$taxonomy = "category";
			$term = $category;
		}

		if (!empty($tag)) {
			$taxonomy = "post_tag";
			$term = $tag;
		}

		if (!empty($taxonomy)) {

			$taxonomies = wp_get_post_terms(
				$current_post_id,
				$taxonomy, array() );

			$post_tax_array = array();
			foreach ($taxonomies as $term_object) {
				$post_tax_array[] = $term_object->slug;
			}

			$terms = comma_list_to_array($term);

//			echo "Find $term in ";
//			print_r($post_tax_array);

			foreach ($terms as $term) {

				if ($compare == "OR") {
					$condition = in_array($term, $post_tax_array) ? true : $condition;
				} else {
					// AND
					$condition = in_array($term, $post_tax_array) ? true : false;
					if (!$condition) break; // Every term must be found
				}

			}

		} // End taxonomy conditions


		/*========================================================================
		 *
		 * Field: field="field_slug" value="this,that"
		 *
		 *=======================================================================*/
		

		if (!empty($field)) {

			$check = get_post_meta( $current_post_id, $field, true );
			if (!is_array($check)) $check = array($check);

			$values = comma_list_to_array($value);

			foreach ($values as $this_value) {

				if ($compare == "OR") {
					$condition = in_array($this_value, $check) ? true : $condition;
				} else { // AND
					$condition = in_array($this_value, $check) ? true : false;
					if (!$condition) break; // Every term must be found
				}
			}
		}		


		/*========================================================================
		 *
		 * Post type, name
		 *
		 *=======================================================================*/

		if (!empty($type)) {
			$types = comma_list_to_array($types); // Enable comma-separated list
			$condition = in_array($current_post_type, $types) ? true : false;
		}

		if (!empty($name)) {
			$names = comma_list_to_array($name);
			$condition = in_array($current_post_name, $names) ? true : false;
		}



		/*========================================================================
		 *
		 * Template: home, archive, single..
		 *
		 *=======================================================================*/
		
		$condition = isset($atts['home']) ? is_front_page() : $condition;
		$condition = isset($atts['archive']) ? is_archive() : $condition;
		$condition = isset($atts['single']) ? is_single() : $condition;

		$condition = isset($atts['not']) ? !$condition : $condition;

		return $condition ? do_shortcode( $content ) : null;
	}

	function flag_shortcode() {
		global $ccs_global_variable;
		return $ccs_global_variable['if_flag'];
	}

}
new IfShortcode;

