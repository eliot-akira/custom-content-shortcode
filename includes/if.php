<?php


/*========================================================================
 *
 * [if] - Display content based on conditions
 *
 *=======================================================================*/

new CCS_If;

class CCS_If {

	public static $if_flag;

	function __construct() {

		self::$if_flag = '';

		add_action( 'init', array( $this, 'register' ) );
	}

	function register() {
		add_shortcode( 'if', array( $this, 'if_shortcode' ) );
		add_shortcode( 'flag', array( $this, 'flag_shortcode' ) );
	}

	function if_shortcode( $atts, $content = null, $shortcode_name ) {

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

			'parent' => '',

			'field' => '',
			'value' => '',

			'not' => '',
			'start' => '',
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
		$compare = strtoupper($compare);

		// Get [else] if it exists

		$content_array = explode('[else]', $content);
		$content = $content_array[0];
		if (count($content_array)>1) {
			$else = $content_array[1];
		} else {
			$else = null;
		}

		/*========================================================================
		 *
		 * If we're inside loop shortcode
		 *
		 *=======================================================================*/

		if (CCS_Loop::$state['is_loop']=="true") {

			if (!empty($every)) {

				/*========================================================================
				 *
				 * Every X number of posts in [loop]
				 *
				 *=======================================================================*/

				$count = CCS_Loop::$state['loop_count'];

				if (substr($every,0,4)=='not ') {
					$every = substr($every, 4); // Remove first 4 letters

					// not Modulo
 					$condition = ($every==0) ? false : (($count % $every)!=0);
				} else {

					// Modulo
					$condition = ($every==0) ? false : (($count % $every)==0);
				}

			}

		} // End [loop] only conditions

		/*========================================================================
		 *
		 * Get global post info
		 *
		 *=======================================================================*/

		global $post;

//		if (empty($post)) return; // Make sure post exists

		$current_post_type = isset($post->post_type) ? $post->post_type : null;
		$current_post_name = isset($post->post_name) ? $post->post_name : null;
		$current_post_id = isset($post->ID) ? $post->ID : null;

		if (!empty($flag)) {

			/*========================================================================
			 *
			 * Check field as condition [if flag="field"]
			 *
			 *=======================================================================*/

			if (CCS_Loop::$state['is_loop']=="true") {
				$current_id = CCS_Loop::$state['current_post_id'];
			} else {
				$current_id = $current_post_id;
			}
			if ($flag!="image")
				$check = get_post_meta( $current_id, $flag, true );
			else
				$check = has_post_thumbnail( $current_id );

			if ((!empty($check)) && (!empty($no_flag))) $condition = false;
			if ((empty($check)) && (empty($no_flag))) $condition = false;
			else {
				$condition = true;
				self::$if_flag = $check;
			}
		}


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

			$terms = self::comma_list_to_array($term);

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
			if (empty($check) || ($check==false))
				$condition = false;
			else {
				if (!is_array($check)) $check = array($check);
				$values = self::comma_list_to_array($value);

				foreach ($values as $this_value) {

					foreach ($check as $check_this) {

						if ($start=='true') {
							// Only check beginning of field value
							$check_this = substr($check_this, 0, strlen($this_value));
						}

						if ($compare == 'OR') {
							$condition = ($this_value==$check_this) ? true : $condition;
						} else { // AND
							$condition = ($this_value==$check_this) ? true : false;
							if (!$condition) break; // Every term must be found
						}
					}
				}

			}
		}


		/*========================================================================
		 *
		 * Post type, name
		 *
		 *=======================================================================*/

		if (!empty($type)) {
			$types = self::comma_list_to_array($type); // Enable comma-separated list
			$condition = in_array($current_post_type, $types) ? true : false;
		}

		if (!empty($name)) {
			$names = self::comma_list_to_array($name);

			foreach ($names as $each_name) {
				if ($start=='true') {
					// Only check beginning of string
					$this_value = substr($current_post_name, 0, strlen($each_name));
				} else {
					$this_value = $current_post_name;
				}
				$condition = ($this_value == $each_name) ? true : $condition;
			}
		}


		/*========================================================================
		 *
		 * Post parent
		 *
		 *=======================================================================*/
		
		if (!empty($parent)) {

			$current_post_parent = isset($post->post_parent) ? $post->post_parent : 0;

			if ($current_post_parent == 0) {
				// Current post has no parent
				$condition = false;
			} else {

				$current_post_parent_slug = self::slug_from_id($current_post_parent);
				$parents = self::comma_list_to_array($parent);

				foreach ($parents as $check_parent) {

					if (is_numeric($check_parent)) {
						// compare to parent id

						if ($compare == "OR") {
							$condition = ($check_parent==$current_post_parent) ? true : $condition;
						} else { // AND
							$condition = ($check_parent==$current_post_parent) ? true : false;
							if (!$condition) break; // Every term must be found
						}
					} else {
						// compare to parent slug

						if ($start=='true') {
							// Only check beginning of string
							$check_this = substr($current_post_parent_slug, 0, strlen($check_parent));
						} else {
							$check_this = $current_post_parent_slug;
						}

						if ($compare == 'OR') {
							$condition = ($check_parent==$check_this) ? true : $condition;
						} else { // AND
							$condition = ($check_parent==$check_this) ? true : false;
							if (!$condition) break; // Every term must be found
						}
					}
				}
			}
		}
		
		/*========================================================================
		 *
		 * Template: home, archive, single..
		 * [if comment] - current post has comment
		 *
		 *=======================================================================*/
		
		$condition = isset($atts['home']) ? is_front_page() : $condition;
		$condition = isset($atts['comment']) ? (get_comments_number($current_post_id)>0) : $condition;
		$condition = isset($atts['image']) ? has_post_thumbnail() : $condition;

		if ( isset($atts['gallery']) && class_exists('CCS_Gallery_Field')) {

			$condition =  CCS_Gallery_Field::has_gallery();
		}

/* test these */
		$condition = isset($atts['loop']) ? (CCS_Loop::$state['is_loop']=='true') : $condition;
		$condition = isset($atts['archive']) ? is_archive() : $condition;
		$condition = isset($atts['single']) ? is_single() : $condition;


		if (isset($atts['attached'])) {

			// Does the current post have any attachments?

			$current_id = get_the_ID();
			$posts = get_posts( array (
				'post_parent' => $current_id,
				'post_type' => 'attachment',
				'post_status' => 'any',
				'posts_per_page' => 1,
				) );
			if (!empty($posts)) $condition = true;
			else $condition = false;
		}

		$condition = isset($atts['not']) ? !$condition : $condition;

		$out = $condition ? do_shortcode( $content ) : do_shortcode( $else ); // [if]..[else]..[/if]

		self::$if_flag = '';

		return $out;
	}

	function flag_shortcode() {

		return self::$if_flag;
	}


	function comma_list_to_array( $string ) {

		// Explode comma-separated list and trim white space

		return array_map("trim", explode(",", $string));
	}

	function slug_from_id( $id ) {
		$post_data = get_post($id);
		if (!empty($post_data)) {
			return isset($post_data->post_name) ? $post_data->post_name : null;
		} else return null;
	}

}

