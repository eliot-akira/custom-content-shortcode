<?php
/*---------------------------------------------
 *
 * [if] - Display content based on conditions
 *
 * @todo Add filters
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
    add_shortcode( '---if', array( $this, 'if_shortcode' ) );
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
      'id' => '',

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

      // field="date" comparison
      'before' => '',
      'after' => '',

      'pass' => '',
      'pass_empty' => 'true', // deprecated
		);

		extract( shortcode_atts( $args , $atts, true ) );

    $atts = CCS_Content::get_all_atts( $atts );

    if ( ( !empty($before) || !empty($after) ) && empty($field) ) {
      $field = 'date'; // Default for before/after parameter
    }
    if ( isset($atts['today']) ) {
      $field = 'date';
      $value = 'today';
    }
		if (!empty($no_flag)) $flag = $no_flag;

		$out = '';

		$condition = false;

		$compare = strtoupper($compare);

		// Get [else] block
		$if_else = self::get_if_else( $content, $shortcode_name );
		$content = $if_else['if'];
		$else = $if_else['else'];

		/*---------------------------------------------
		 *
		 * If we're inside loop shortcode
		 *
		 */



		/*---------------------------------------------
		 *
		 * Get global post info
		 *
		 */

		global $post;

//		if (empty($post)) return; // Make sure post exists

		$current_post_type = isset($post->post_type) ? $post->post_type : null;
		$current_post_name = isset($post->post_name) ? $post->post_name : null;
		$current_post_id = isset($post->ID) ? $post->ID : null;

    if ( CCS_Loop::$state['is_loop'] ) {
      $current_post_id = CCS_Loop::$state['current_post_id'];
    }



		/*---------------------------------------------
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

		if ( !empty($taxonomy) ) {

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
		 * Check if current term in [for] loop has children
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



		/*---------------------------------------------
		 *
		 * Field: field="field_slug" value="this,that"
		 *
		 */

		if ( !empty($field) || !empty($user_field) ) {

      // Post field
			if ( empty($user_field) ) {

        /*---------------------------------------------
         *
         * Published date
         *
         */
        
        if ( $field == 'date' || !empty($before) || !empty($after) ) {

          if ( $field == 'date' ) {
            // Get timestamps for publish date and today
            $check = strtotime( $post->post_date );
          } else {
            // Normal field
            $check = strtotime( CCS_Content::get_prepared_field( $field ) );
          }

          $today = strtotime('now'); // Lazy way

          if (!empty($before) && !empty($after)) {
            $value_1 = strtotime($after);
            $value_2 = strtotime($before);
            $compare = 'BETWEEN';
          } elseif (!empty($before)) {
            $value = strtotime($before);
            $compare = 'OLD';

          } elseif (!empty($after)) {

            $value = strtotime($after);
            $compare = 'NEW';

          } else {

            if ( $value == 'today' ) {
              $value = $today;
            } elseif ( substr($value,0,6)=='today ' ) {

              // Get difference, i.e., "+10 days"
              $diff = substr($value,6);

              // Add or subtract days
              $value = strtotime( $diff, $today );
            } else {
              $value = strtotime( $value ); // Try to convert other values to timestamp
            }
          }

          // Convert to format 20150311 so we can compare as number
          $check = date('Ymd',$check);

          if (!empty($before) && !empty($after)) {
            $value_1 = date('Ymd',$value_1);
            $value_2 = date('Ymd',$value_2);
            $value = $value_1 . ' - ' . $value_2;
          } else {
            $value = date('Ymd',$value);
          }

          // echo 'Check field: '.$field.' '.$check.' = '.$value.'<br>';

        } else {
          // Normal field
          $check = CCS_Content::get_prepared_field( $field );
        }

      // User field
			} else {

				$field = $user_field;
				$check = strtolower(CCS_User::get_user_field( $field ));
        $value = strtolower($value); // lowercase for user role
			}

			// start=".."
			if ( !empty($start) && ($start!='true') && empty($value) ) {
				$value = $start;
				$start = 'true';
			}

			if ( empty($check) || ( $check == false ) ) {

        // @todo What if field value is boolean, i.e., checkbox?

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

							if ($compare == 'AND') {

                $condition = ($this_value==$check_this) ? true : false;
                if (!$condition) break; // Every term must be found

							} else {

                switch ($compare) {
                  case 'MORE':
                  case 'NEW':
                  case 'NEWER':
                  case '>':
                    $condition = ($check_this > $this_value) ? true : $condition;
                  break;
                  case '>=':
                    $condition = ($check_this >= $this_value) ? true : $condition;
                  break;
                  case 'LESS':
                  case 'OLD':
                  case 'OLDER':
                  case '<':
                    $condition = ($check_this < $this_value) ? true : $condition;
                  break;
                  case '<=':
                    $condition = ($check_this <= $this_value) ? true : $condition;
                  break;
                  case 'BETWEEN':
                    $values = explode(' - ', $this_value);
                    if (isset($values[0]) && isset($values[1])) {
                      $condition = 
                        ($values[0] <= $check_this && $check_this <= $values[1]) ?
                          true : $condition;
                    }
                  break;
                  case 'EQUAL':
                  case '=':
                  default:
                    $condition = ($check_this == $this_value) ? true : $condition;
                  break;
                }

							} // End compare
						} // End for each check
					} // End for each value

				} else {
					// No value specified - just check that there is field value
          if ($empty=='true') {
            $condition = !empty($check) ? true : false;
          } else {
            $condition = false;
          }
				}
			} // End if check not empty

		} // End field value condition


		/*---------------------------------------------
		 *
		 * Post type, name, id
		 *
		 */

		if ( !empty($type) ) {

			$types = self::comma_list_to_array($type); // Enable comma-separated list
			$condition = in_array($current_post_type, $types) ? true : false;
		}

    if ( !empty($id) ) {

      $ids = self::comma_list_to_array($id); // Enable comma-separated list
      $condition = in_array($current_post_id, $ids) ? true : false;
    }

		if ( !empty($name) ) {

			$names = self::comma_list_to_array($name);

			foreach ($names as $each_name) {

				if ( $start == 'true' ) {

					// Only check beginning of string
					$this_value = substr($current_post_name, 0, strlen($each_name));

				} else {
					$this_value = $current_post_name;
				}

				$condition = ($this_value == $each_name) ? true : $condition;
			}
		}


		/*---------------------------------------------
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


		/*---------------------------------------------
		 *
		 * Attachments
		 *
		 */

		if ( isset($atts['attached']) ) {

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
		

    /*---------------------------------------------
     *
     * Has CCS gallery field
     *
     */

		if ( isset($atts['gallery']) && class_exists('CCS_Gallery_Field')) {
			$condition =  CCS_Gallery_Field::has_gallery();
		}		


		/*---------------------------------------------
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
     * Inside [loop]
     *
     */
   
    if ( CCS_Loop::$state['is_loop'] ) {


      /*---------------------------------------------
       *
       * Every X number of posts
       *
       */

      if ( !empty($every) ) {

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

      /*---------------------------------------------
       *
       * First and last post in loop
       *
       */

      $condition = isset($atts['first']) ?
        CCS_Loop::$state['loop_count'] == 1 : $condition;

      $condition = isset($atts['last']) ?
        CCS_Loop::$state['loop_count'] == CCS_Loop::$state['post_count'] : $condition;


    } // End: if inside [loop]


    /*---------------------------------------------
     *
     * Passed value
     *
     */
    
    if ( ( isset($atts['pass']) && empty($atts['pass']) && $empty!='true' ) ||
      ( $pass_empty!='true' && empty($pass) ) ) // @todo deprecated
    {

      // pass="{FIELD}" empty="false" -- pass is empty

      $condition = false;

    } elseif ( !empty($pass) && empty($value) && $empty!='true' ) {

      // pass="{FIELD}" empty="false" -- no value set

      $condition = true;

    } elseif ( !empty($pass) && !empty($value) ) {

      // pass="{FIELD}" value="something"

      $values = CCS_Loop::explode_list( $value ); // Support multiple values

      $condition = in_array( $pass, $values );
    }


    /*---------------------------------------------
     *
     * Check field as condition [if flag="field"]
     *
     * @todo To be deprecated
     *
     */

    if ( !empty($flag) ) {

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


		/*---------------------------------------------
		 *
		 * Not / else
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

