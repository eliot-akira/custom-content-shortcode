<?php


/*========================================================================
 *
 * [if] - Display content based on conditions
 *
 */

new CCS_If;

class CCS_If {

	public static $if_flag;

	function __construct() {

		self::$if_flag = '';

		add_action( 'init', array( $this, 'register' ) );
	}

	function register() {
		add_shortcode( 'if', array( $this, 'if_shortcode' ) );
		add_shortcode( '-if', array( $this, 'if_shortcode' ) );
		add_shortcode( '--if', array( $this, 'if_shortcode' ) );
		add_shortcode( 'flag', array( $this, 'flag_shortcode' ) );
	}

	function if_shortcode( $atts, $content = null, $shortcode_name ) {

		$atts_original = $atts;

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
			'tax_archive' => '',

			'parent' => '',

			'field' => '',
			'user_field' => '',
      'value' => '',
      'lowercase' => '',

      'empty' => 'true',

			'not' => '',
			'start' => '',

      'pass' => '',
      'pass_empty' => 'true',
		);

		extract( shortcode_atts( $args , $atts, true ) );

    $atts = CCS_Content::get_all_atts( $atts );

/*
//		if ( (empty($flag))&&(empty($no_flag)) || (isset($atts['empty']))) return;
		if ((isset($atts['empty'])) || (isset($atts['last'])) ) return; // [if empty] [if last] is processed by [loop]
*/
		if (!empty($no_flag)) $flag = $no_flag;

		$out = '';
		$condition = false;
		$compare = strtoupper($compare);

		// Get [else] block
		$if_else = self::get_if_else( $content, $shortcode_name );
		$content = $if_else['if'];
		$else = $if_else['else'];

		/*========================================================================
		 *
		 * If we're inside loop shortcode
		 *
		 */

		if ( CCS_Loop::$state['is_loop'] ) {

			if (!empty($every)) {

				/*========================================================================
				 *
				 * Every X number of posts in [loop]
				 *
				 */

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
		 */

		global $post;

//		if (empty($post)) return; // Make sure post exists

		$current_post_type = isset($post->post_type) ? $post->post_type : null;
		$current_post_name = isset($post->post_name) ? $post->post_name : null;
		$current_post_id = isset($post->ID) ? $post->ID : null;


		// @todo Combine with [if field] without value
		if ( !empty($flag) ) {

			/*========================================================================
			 *
			 * Check field as condition [if flag="field"]
			 *
			 */

			if ( CCS_Loop::$state['is_loop'] ) {
				$current_id = CCS_Loop::$state['current_post_id'];
			} else {
				$current_id = $current_post_id;
			}
			if ( $flag != 'image' )
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
		 */
		
		if (!empty($category)) {
			$taxonomy = "category";
			$term = $category;
		}

		if (!empty($tag)) {
			$taxonomy = "post_tag";
			$term = $tag;
		}

		// Check if current post has taxonomy term

		if ( !empty($taxonomy) && !empty($term) ) {

			if ($taxonomy == 'tag') $taxonomy = 'post_tag';

			$taxonomies = wp_get_post_terms(
				$current_post_id,
				$taxonomy, array() );

			$post_tax_array = array();
			foreach ($taxonomies as $term_object) {
				$post_tax_array[] = $term_object->slug;
			}

			$terms = self::comma_list_to_array($term);

			if ( empty($term) && count($post_tax_array) ) {

				// If no term query is set, then check if there's any term
				$condition = true;

			} else {
				foreach ($terms as $term) {

					if ($compare == "OR") {
						$condition = in_array($term, $post_tax_array) ? true : $condition;
					} else {
						// AND
						$condition = in_array($term, $post_tax_array) ? true : false;
						if (!$condition) break; // Every term must be found
					}
				}
			}

		}

		/*---------------------------------------------
		 *
		 * Check if current term in the for loop has children
		 *
		 */
		

		if ( isset($atts['children']) && CCS_ForEach::$state['is_for_loop'] ) {
			$current_term = CCS_ForEach::$current_term[ CCS_ForEach::$index ];
			$current_taxonomy = $current_term['taxonomy'];

			$terms = get_terms( $current_taxonomy, array('parent' => $current_term['id']) );

			if (!empty($terms) && $terms!=array())
				$condition = true;
			else $condition = false;
		}



		/*========================================================================
		 *
		 * Field: field="field_slug" value="this,that"
		 *
		 */

		if ( !empty($field) || !empty($user_field) ) {

			if ( empty($user_field) ) {

				// Post field
				$check = CCS_Content::get_prepared_field( $field );

			} else {

				// User field
				$field = $user_field;
				$check = CCS_User::get_user_field( $field );
			}

			// start=".."

			if ( !empty($start) && ($start!='true') && empty($value) ) {
				$value = $start;
				$start = 'true';
			}

			if ( empty($check) || ($check==false) ) {

				$condition = false;

      }	else {

				if ( !is_array($check) ) $check = array($check);

				if ( !empty($value) ) {

					$values = self::comma_list_to_array($value);

					foreach ($values as $this_value) {

						foreach ($check as $check_this) {

							if ( $start == 'true' ) {

								// Only check beginning of field value
								$check_this = substr($check_this, 0, strlen($this_value));
							}

              if ($lowercase == 'true') $check_this = strtolower($check_this);

							if ($compare == 'OR') {
								$condition = ($this_value==$check_this) ? true : $condition;
							} else { // AND
								$condition = ($this_value==$check_this) ? true : false;
								if (!$condition) break; // Every term must be found
							}
						}
					}

				} else {
					// No value specified - just check that there is field value
          if ($empty=='true') {
            $condition = !empty($check) ? true : false;
          } else {
            $condition = false;
          }
				}
			}
		}


		/*========================================================================
		 *
		 * Post type, name
		 *
		 */

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
		 */
		
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
		 * Attachments
		 *
		 */

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


		/*---------------------------------------------
		 *
		 * If child post exists
		 *
		 */
		
		if ( isset($atts['children']) && !CCS_ForEach::$state['is_for_loop'] ) {

			if (!empty($post)) {
				$children_array = get_children( array(
						'post_parent' => $post->ID,
						'posts_per_page' => '1',
						'post_status' => 'publish' )
				);
				$condition = ( count( $children_array ) > 0 );
			}
		}


		/*---------------------------------------------
		 *
		 * If exists
		 *
		 */

		if (isset($atts['exists'])) {

			$result = CCS_Loop::the_loop_shortcode($atts_original, '[if empty][else]Yes[/if]');
			$condition = !empty($result);
		}
		

		// Has CCS gallery field

		if ( isset($atts['gallery']) && class_exists('CCS_Gallery_Field')) {
			$condition =  CCS_Gallery_Field::has_gallery();
		}		

		/*========================================================================
		 *
		 * Template: home, archive, single..
		 * [if comment] - current post has comment
		 *
		 */
		
		$condition = isset($atts['home']) ? is_front_page() : $condition;
		$condition = isset($atts['comment']) ? (get_comments_number($current_post_id)>0) : $condition;
		$condition = isset($atts['image']) ? has_post_thumbnail() : $condition;
		$condition = isset($atts['loop']) ? ( CCS_Loop::$state['is_loop'] ) : $condition;
		$condition = isset($atts['archive']) ? is_archive() : $condition;
		$condition = isset($atts['single']) ? is_single() : $condition;
		$condition = isset($atts['search']) ? is_search() : $condition;
		$condition = isset($atts['404']) ? is_404() : $condition;

		$condition = isset($atts['none']) ? !have_posts() : $condition;

		if (isset($atts['tax_archive'])) {
			if ($tax_archive == 'true') $tax_archive = '';
			$condition = is_tax( $tax_archive );
		}


    /*---------------------------------------------
     *
     * Passed value
     *
     */
    
    if ( !empty($pass) || ($pass_empty!='true') ) {

      if ( ($pass_empty!='true') && empty($pass) ) {
          $condition = false;
      } elseif ( !empty($value) ) {
        $condition = ($pass == $value);
      } else {
        $condition = true;
      }
    }


		/*========================================================================
		 *
		 * Not
		 *
		 */

		// Not - also catches compare="not"
		$condition = isset($atts['not']) ? !$condition : $condition;

		$out = $condition ? do_shortcode( $content ) : do_shortcode( $else ); // [if]..[else]..[/if]

		self::$if_flag = '';

		return $out;
	}


	function flag_shortcode() {

		return self::$if_flag;
	}


	// Returns array with if and else blocks
	public static function get_if_else( $content, $shortcode_name = '', $else_name = '' ) {

		// Get [else] if it exists

		if ( substr($shortcode_name, 0, 2)=='--' ) {
			$prefix = '--';
		} elseif ( substr($shortcode_name, 0, 1)=='-' ) {
			$prefix = '-';
		} else
			$prefix = null; // Top level

		if (empty($else_name))
			$else_name = $prefix.'else';
		else
			$else_name = $prefix.$else_name;

		$content_array = explode('['.$else_name.']', $content);
		$content = $content_array[0];
		if ( count($content_array)>1 ) {
			$else = $content_array[1];
		} else {
			$else = '';
		}

		return array(
			'if' => $content,
			'else' => $else
		);
	}



	// @todo Put this in CCS_Loop or Content as general-purpose function
	function comma_list_to_array( $string ) {

		// Explode comma-separated list and trim white space

		return array_map("trim", explode(",", $string));
	}


	// @todo Put this in CCS_Loop or Content as general-purpose function
	function slug_from_id( $id ) {
		$post_data = get_post($id);
		if (!empty($post_data)) {
			return isset($post_data->post_name) ? $post_data->post_name : null;
		} else return null;
	}

}

