<?php

/**
 *
 * [loop] - Query posts and loop through each
 *
 * @filter ccs_loop_add_defaults 			Additional parameters to accept
 * @filter ccs_loop_parameters	 			Process given parameters
 * @filter ccs_loop_each_post				Each found post
 * @filter ccs_loop_each_result				Each compiled template result
 * @filter ccs_loop_each_row				Each row (if columns option is set)
 * @filter ccs_loop_all_results 			Results array
 * @filter ccs_loop_final_result 			Final combined result 
 * 
 */

new CCS_Loop;

class CCS_Loop {

	private static $original_parameters;	// Shortcode parameters
	private static $parameters;				// After merge with default
	private static $query;					// The query
	public static $state;					// Loop state array
	public static $previous_state;			// For nested loop

	function __construct() {


		// Initialize global
		self::init();


	/*========================================================================
	 *
	 * Define shortcodes
	 *
	 */

		add_shortcode( 'loop', array($this, 'the_loop_shortcode') );
		add_shortcode( 'pass', array($this, 'pass_shortcode') );

		// A workaround for nested shortcodes
		add_shortcode( '-loop', array($this, 'the_loop_shortcode') );
		add_shortcode( '--loop', array($this, 'the_loop_shortcode') );
		add_shortcode( '-pass', array($this, 'pass_shortcode') );
		add_shortcode( '--pass', array($this, 'pass_shortcode') );

		add_shortcode( 'loop-count', array($this, 'loop_count_shortcode') );
		add_shortcode( 'found-posts', array($this, 'found_posts_shortcode') );
		add_shortcode( 'search-keyword', array($this, 'search_keyword_shortcode') );
	}




	/*========================================================================
	 *
	 * Initialize global
	 *
	 */

	public static function init() {

		self::$state['is_loop'] = false;
		self::$state['is_nested_loop'] = false;
		self::$state['is_attachment_loop'] = false;
		self::$state['do_reset_postdata'] = false;
		self::$previous_state = array();
	}


	/*========================================================================
	 *
	 * Loop shortcode: main actions
	 * 
	 */

	public static function the_loop_shortcode( $parameters, $template ) {

		// Initialize loop state
		self::init_loop();

			// Store original parameters
			self::$original_parameters = $parameters;

			// Merge parameters with defaults
			$parameters = self::merge_with_defaults( $parameters );
			// Store merged parameters
			self::$parameters = $parameters;


			// Check cache - if loaded, return result
			if ( ($result = self::check_cache( $parameters )) !== false ) {
				self::close_loop();
				return $result;
			}

			// If there's already result based on parameters, return it
			$result = self::before_query( $parameters, $template );
			if ( !empty( $result ) ) {
				self::close_loop();
				return $result;
			}


			// Set up query based on parameters
			$query = self::prepare_query( $parameters );

			if (!empty($query)) {

				// Get posts from query
				$posts = self::run_query( $query );
			
				// Pre-process posts
				$posts = self::prepare_posts( $posts );

				// Loop through each post and compile shortcode template
				$results = self::compile_templates( $posts, $template );

				// Combine results and process to final output
				$result = self::process_results( $results );
			} else {
        $results = self::compile_templates( null, $template, false );
        $result = self::process_results( $results );
      }

		self::close_loop();

		return $result;
	}



	/*========================================================================
	 *
	 * Initialize loop state
	 *
	 */

	public static function init_loop() {

		$state = self::$state;

		if ( $state['is_loop'] ) {

			self::$previous_state[] = $state;
			$state['is_nested_loop']	= true;

		} else {

			$state['is_loop'] 			= true;
			$state['is_nested_loop']	= false;
		}

		$state['do_reset_postdata'] 	= false;
		$state['do_cache'] 				= false;
		$state['blog'] 					= 0;

		$state['loop_count']			= 0;
		$state['post_count'] 			= 0;
		$state['skip_ids'] 				= array();

		$state['current_post_id']		= 0;
		// Store ID of post that contains the loop
		$state['original_post_id']		= get_the_ID();

		$state['comment_count']			= 0;
		$state['is_attachment_loop']	= false;

		// Support qTranslate Plus
		$state['current_lang'] 			= null;

		self::$state = $state;
	}


	/*========================================================================
	 *
	 * Define all parameters
	 *
	 */

	public static function merge_with_defaults( $parameters ){

		$defaults = array(

			'type' => '',
			'name' => '',
			'id' => '', 'exclude' => '',
			'status' => '',
			'include' => '',
			'parent' => '',
			'count' => '', 'offset' => '',
			'year' => '', 'month' => '', 'day' => '',
			'author' => '', 'author_exclude' => '',

			// Taxonomy

			'taxonomy' => '', 'term' => '',
			'tax' => '', // Alias
			'taxonomy_2' => '', 'taxonomy_3' => '', 
			'value_2' => '', 'value_3' => '', 

			'category' => '', 'tag' => '', 

			// Field value

			'field' => '', 'value' => '',
			'compare' => '',
			'start' => '', // If field value starts with

			// Additional field value query
			'field_2' => '', 'compare_2' => '', 'relation' => '',
			'f' => '', 'v' => '', 'c' => '', 'f2' => '', 'v2' => '', 'c2' => '', 'r' => '', // Alias


      // Published date
      'after' => '',
      'before' => '',

			// Checkbox

			'checkbox' => '', 'checkbox_2' => '', 

			// Sort

			'orderby' => '', 'order' => '',
			'series' => '', 'key' => '',

			// Format

			'date_format' => '',
			'in' => '', // in="string" - Date field stored as string
			'acf_date' => '',

			'strip_tags' => '', 'strip' => '', 'allow' => '',
			'clean' => 'false', 'trim' => '',

			// Columns

			'columns' => '', 'pad' => '', 'between' => '',

			// List

			'list' => '',
			'list_class' => '', 'list_style' => '',
			'item' => '',
			'item_class' => '', 'item_style' => '',

			// Gallery

			'gallery' => '',
			'acf_gallery' => '',
			'repeater' => '', // ACF repeater
			
			// Other

			'fields' => '', 'custom_fields' => '', // CSV list of custom field names to expand
			'blog' => '', // Multi-site (not tested)
			'x' => '', // Just loop X times, no query

			// Cache
			'cache' => 'false',
			'expire' => '10 min',
			'update' => 'false',

			// Timer
			'timer' => 'false',


			// ? Clarify purpose
			'if' => '', 'posts_separator' => '',
			'variable' => '', 'var' => '',
			'content_limit' => 0,
			'thumbnail_size' => 'thumbnail',
			'title' => '', 'post_offset' => '',
			'keyname' => '', 
		);

		$add_defaults = apply_filters( 'ccs_loop_add_defaults', array() );
		$defaults = array_merge( $defaults, $add_defaults );
		$parameters = apply_filters( 'ccs_loop_parameters', $parameters );

		$merged = shortcode_atts($defaults, $parameters, true);

		// Support aliases?

//		if ( !empty($tax) ) $taxonomy = $tax;


		return $merged;
	}


	/*========================================================================
	 *
	 * Check cache based on parameters
	 * 
	 * If no cache, returns false
	 * If update is true, set do_cache for end of loop, and returns false
	 * If cache exists and update is not true, returns cached result
	 * 
	 */

	public static function check_cache( $parameters ) {

		$result = false;

		if ($parameters['cache']=='true') {

			self::$state['do_cache'] = 'true';

			// Generate unique cache name from the original shortcode parameters

			$cache_name = null;

			ksort(self::$original_parameters); // Alphabetical sort

			foreach (self::$original_parameters as $key => $value) {

				$skip_parameters = array('update','cache','expire');  // Skip cache parameters

				if ( ! in_array($key, $skip_parameters) )
					$cache_name .= $key.$value;
			}
			$cache_name = substr($cache_name, 0, 40); // Max number of characters

			self::$state['cache_name'] = $cache_name;

			if ($parameters['update']!='true') {

				$result = CCS_Cache::get_transient( $cache_name );
			}
		}
		return $result;
	}

	/*========================================================================
	 *
	 * Action before running query
	 * If returns not null, there is already result
	 *
	 */

	public static function before_query( $parameters, $template = null ) {



		/*========================================================================
		 *
		 * Start timer
		 *
		 */

		if ( $parameters['timer'] == 'true' ) {

			CCS_Cache::start_timer();

		}
		
		
		/*========================================================================
		 *
		 * The X parameter - run loop X times, no query
		 *
		 */
		
		if (!empty($parameters['x'])) {

			$outs = array();

			$x = $parameters['x'];
			for ($i=0; $i <$x ; $i++) { 
				self::$state['loop_count']++;

				$outs[] =
				apply_filters('ccs_loop_each_result',
					do_shortcode( self::render_field_tags( $template, $parameters ) ),
				$parameters );

			}

			if (!empty($parameters['columns'])) {
				$out = self::render_columns( $outs, $parameters['columns'], $parameters['pad'], $parameters['between'] );
			} else $out = implode('', $outs);

			return apply_filters('ccs_loop_final_result', $out, $parameters );
		}


		/*========================================================================
		 *
		 * Switch to blog on multisite - restore during close_loop
		 * 
		 * ** Not tested **
		 *
		 */

		if ( !empty($parameters['blog']) ) {
			$result = switch_to_blog($parameters['blog']);
			if ($result) {
				self::$state['blog'] = $parameters['blog'];
			}
		}

		return null;
	}


	/*========================================================================
	 *
	 * Prepare query based on parameters
	 *
	 */

	public static function prepare_query( $parameters ) {

		$query = array();


		/*========================================================================
		 *
		 * field="gallery"
		 *
		 */

		if ( ($parameters['field'] == 'gallery') && (class_exists('CCS_Gallery_Field')) ) {

			// Gallery field

			$parameters['type'] = 'attachment';
//			$query['post_parent'] = get_the_ID();
			self::$state['is_attachment_loop'] = true;

			$parameters['id'] = implode(',', CCS_Gallery_Field::get_image_ids( get_the_ID() ) );
			$parameters['field'] = '';

		}

		/*========================================================================
		 *
		 * Post type
		 *
		 */
		
		
		if ( !empty($parameters['type']) ) {

			$query['post_type'] = self::explode_list($parameters['type']);

		} else {

			$query['post_type'] = 'any';
		}

		/*========================================================================
		 *
		 * Post ID, exclude ID, name
		 *
		 */
		
		if ( !empty($parameters['id']) ) {

			$id_array = self::explode_list($parameters['id']);

			$query['post__in'] = $id_array;
			$query['orderby'] = 'post__in'; // Preserve ID order

		} elseif ( $parameters['include'] == 'children' ) {

			if ( $parameters['parent']=='0' ) {
				$parameters['parent'] = '';
			}

		} elseif ( !empty($parameters['exclude']) ) {

			$id_array = self::explode_list($parameters['exclude']);

			foreach ($id_array as $key => $value) {

        // Exclude current post
				if ($value=='this') {
					$id_array[$key] = self::$state['original_post_id']; // ID of post that contains the loop

        // Top-level posts only
				} elseif ($value=='children') {
          unset($id_array[$key]);
          $parameters['parent']='0';
        }
			}

			$query['post__not_in'] = $id_array;

		} elseif ( !empty($parameters['name']) ) {

			$query['name'] = $parameters['name'];

		}

    /*---------------------------------------------
     *
     * Parent
     *
     */

    if ( !empty($parameters['parent']) || $parameters['parent']=='0' ) {

			$parent = $parameters['parent'];

			if ( $parent=='this' ) {

        // Get children of current post

        $query['post_parent'] = get_the_ID();
        if (!$query['post_parent'])
          $query['post_parent'] = '-1'; // If no current post

      } elseif ( $parent=='same' ) {

        // Get siblings of current post

        $query['post_parent'] = wp_get_post_parent_id( get_the_ID() );

        if (!$query['post_parent'])
          $query['post_parent'] = '-1'; // If current post has no parent

      } elseif ( is_numeric($parent) ) {

				$query['post_parent'] = intval( $parent ); // Single parent ID

			} else {

				// Multiple IDs

				$parents = self::explode_list( $parent ); // Convert to array
				$parent_IDs = array();

				foreach ($parents as $each_parent) {

					if ( $each_parent=='this' ) {

						// Get children of current post
						$parent_IDs[] = get_the_ID();

					} elseif ( $parent=='same' ) {

            // Get siblings of current post, if it has parent
            $parent_ID = wp_get_post_parent_id( get_the_ID() );
            if ($parent_ID) $parent_IDs[] = $parent_ID;

          } elseif ( is_numeric($each_parent) ) {

						// by ID
						$parent_IDs[] = intval( $each_parent );

					} else {

						// by slug
						$posts = get_posts( array(
							'name' => $each_parent,
							'post_type' => $query['post_type'],
							'posts_per_page' => '1')
						);
						if ( $posts ) $parent_IDs[] = $posts[0]->ID;
					}
				}

				if (empty($parent_IDs)) return null; // No parent

				$query['post_parent__in'] = $parent_IDs;

			} // End single/multiple

		} // End if parent pameter

		$query['ignore_sticky_posts'] = true;


		/*========================================================================
		 *
		 * Post author
		 *
		 */
		
		if ( !empty($parameters['author']) ) {

			$authors = self::explode_list( $parameters['author'] );

			foreach ($authors as $author) {
				if (is_numeric($author)) {
					// Author ID
					$query['author__in'][] = $author;
				} else {

					if ( $author == 'this' ) {

            // Current user ID
            $query['author__in'][] = CCS_User::get_user_field('id');

          } elseif ( $author == 'same' ) {

            // Same author as current post
            $current_post = get_post( get_the_ID() );
            if ( $current_post ) {
              $query['author__in'][] = $current_post->post_author;
            }

          } else {
						// Get author ID from login name
						$author_data = get_user_by('login', $author);
						if ($author_data) {
							$query['author__in'][] = $author_data->ID;
						}
					}
				}
			}
		}

		if ( !empty($parameters['author_exclude']) ) {

			$authors = self::explode_list( $parameters['author_exclude'] );

			foreach ($authors as $author) {
				if (is_numeric($author)) {
					// Author ID
					$query['author__not_in'][] = $author;
				} else {
					if ( $author == 'this' ) {

						// Current user ID
						$query['author__not_in'][] = CCS_User::get_user_field('id');

					} else {

						// Get author ID from login name
						$author_data = get_user_by('login', $author);
						if ($author_data) {
							$query['author__not_in'][] = $author_data->ID;
						}
					}
				}
			}
		}



		/*========================================================================
		 *
		 * Post status
		 *
		 */

		if ( !empty($parameters['status']) ) {

			$query['post_status'] = self::explode_list( $parameters['status'] );

		} else {

			// Default
			if ( $parameters['type'] == 'attachment' ) {
				$query['post_status'] = array('any');
			} else {
				$query['post_status'] = array('publish');
			}
		}


		/*========================================================================
		 *
		 * Post count and offset
		 *
		 */

		if ( !empty($parameters['offset']) ) {

			$query['offset'] = $parameters['offset'];
		}

		if ( !empty($parameters['count']) ) {

			if ($parameters['orderby']=='rand') {

				$query['posts_per_page'] = '-1'; // For random, get all posts and count later

			} else {

				$query['posts_per_page'] = $parameters['count'];
			}

		} else {

			if (!empty($query['offset']))

				$query['posts_per_page'] = '99999'; // Show all posts (to make offset work)

			else
				$query['posts_per_page'] = '-1'; // Show all posts (normal method)
		}




		/*========================================================================
		 *
		 * Date
		 *
		 */

		if ( !empty($parameters['year']) || !empty($parameters['month']) ||
			!empty($parameters['day']) ) {

			$year = $parameters['year'];
			$month = $parameters['month'];
			$day = $parameters['day'];

			$today = getdate();

			if ($year=='today') $year=$today["year"];
			if ($month=='today') $month=$today["mon"];
			if ($day=='today') $day=$today["mday"];

			$query['date_query'] = array(
				array(
					'year' => $year,
					'month' => $month,
					'day' => $day,
				)
			);
		}


		/*========================================================================
		 *
		 * Category
		 *
		 */

		if ( !empty($parameters['category']) ) {

			// Category can be slug, ID, multiple

			$category = $parameters['category'];
			$categories = self::explode_list( $category, ',+' );

			$check_category = array_pop($categories); // Check one item

			if (!empty($parameters['compare']) && strtoupper($parameters['compare'])=='AND') {
				$category = str_replace(',', '+', $category);
			}

			if ( is_numeric($check_category) )
				$query['cat'] = $category; // ID(s)
			else
				$query['category_name'] = $category; // Slug(s)

		}


		/*========================================================================
		 *
		 * Tag
		 *
		 */

		if( !empty($parameters['tag']) ) {

			// Remove extra space in a list

			$tags = self::clean_list( $parameters['tag'] );
			if (!empty($parameters['compare']) && strtoupper($parameters['compare'])=='AND') {
				$tags = str_replace(',', '+', $tags);
			}
			$query['tag'] = $tags;
		}


		/*========================================================================
		 *
		 * Taxonomy
		 *
		 */

		// In a [for] loop, filter by each taxonomy term unless specified otherwise
		if ( CCS_ForEach::$state['is_for_loop'] ) {

			$parameters['taxonomy'] = empty($parameters['taxonomy']) ?
				CCS_ForEach::$current_term[ CCS_ForEach::$index ]['taxonomy'] :
					$parameters['taxonomy'];

			$parameters['term'] = empty($parameters['term']) ?
				CCS_ForEach::$current_term[ CCS_ForEach::$index ]['slug'] :
					$parameters['term'];

		}

		if ( !empty($parameters['taxonomy']) ) {

			$taxonomy = $parameters['taxonomy'];
			if ($taxonomy == 'tag') $taxonomy = 'post_tag';

			if ( !empty($parameters['term']) )
				$term = $parameters['term'];
			else
				$term = $parameters['value']; // Alias, if field value is not used

			$terms = self::explode_list($term); // Multiple terms possible

			if ( !empty($parameters['compare']) ) {

				$compare = $parameters['compare'];
				if ( $compare == '=' ) $operator = 'IN';
				elseif ( $compare == '!=' ) $operator = 'NOT IN';
				else {
					$compare = strtoupper($compare);
					if ( $compare == 'NOT' )
						$compare = 'NOT IN';
					if ( $compare == 'OR' )
						$compare = ''; // It's OR by default
					$operator = $compare;
				}

			} else {
				$operator = 'IN'; // Default
			}

			$query['tax_query'] = array (
				array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $terms,
					'operator' => $operator
				)
			);

			// Additional taxonomy query
			if ( !empty($parameters['taxonomy_2']) && !empty($parameters['value_2']) ) {

				$taxonomy = $parameters['taxonomy_2'];
				if ($taxonomy == 'tag') $taxonomy = 'post_tag';

				$relation = !empty($relation) ? strtoupper($relation) : 'AND';
				$query['tax_query']['relation'] = $relation;

				$terms = self::explode_list($parameters['value_2']); // Multiple terms possible

				if ( !empty($parameters['compare_2']) ) {

					$compare = $parameters['compare_2'];

					if ( $compare=='=' )
						$operator = 'IN';
					elseif ( $compare=='!=' )
						$operator = 'NOT IN';
					else {
						$compare = strtoupper($compare);
						if ( $compare == 'NOT' )
							$compare = 'NOT IN';
						$operator = $compare;
					}

				} else {
					$operator = 'IN'; // Default
				}

				$query['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $terms,
					'operator' => $operator
				);
			}

			// Additional taxonomy query

			// @todo Better way to combine taxonomy_2, 3, 4..

			if ( !empty($parameters['taxonomy_3']) && !empty($parameters['value_3']) ) {

				$taxonomy = $parameters['taxonomy_3'];
				if ($taxonomy == 'tag') $taxonomy = 'post_tag';

				$terms = self::explode_list($parameters['value_3']); // Multiple terms possible

				if ( !empty($parameters['compare_3']) ) {

					$compare = $parameters['compare_3'];

					if ( $compare=='=' )
						$operator = 'IN';
					elseif ( $compare=='!=' )
						$operator = 'NOT IN';
					else {
						$compare = strtoupper($compare);
						if ( $compare == 'NOT' )
							$compare = 'NOT IN';
						$operator = $compare;
					}

				} else {
					$operator = 'IN'; // Default
				}

				$query['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $terms,
					'operator' => $operator
				);
			}

		}




		/*========================================================================
		 *
		 * Order and orderby
		 *
		 */

		if ( !empty($parameters['order']) ) {
			
			$query['order'] = $parameters['order'];

		}

		if ( !empty($parameters['orderby']) ) {

			$orderby = $parameters['orderby'];

			// Alias
			if ($orderby=="field") $orderby = 'meta_value';
			if ($orderby=="field_num") $orderby = 'meta_value_num';

			$query['orderby'] = $orderby;

			if (in_array($orderby, array('meta_value', 'meta_value_num') )) {

				if ( !empty($parameters['key']) )
					$key = $parameters['key'];
				else
					$key = $parameters['field']; // If no key is specified, order by field

				$query['meta_key'] = $key;
			}

			if ( empty($parameters['order']) ) {

				// Default order

				if ( ($orderby=='meta_value') || ($orderby=='meta_value_num') ||
					($orderby=='menu_order') || ($orderby=='title') || ($orderby=='name') ) {

					$query['order'] = 'ASC';		

				} else {

					$query['order'] = 'DESC';
				}
			}	
		}




		/*========================================================================
		 *
		 * Sort by series
		 *
		 */
		
		if ( !empty($parameters['series']) ) {

			// Remove white space
			$series = str_replace(' ', '', $parameters['series']);

			// Expand range: 1-3 -> 1,2,3

				/* PHP 5.3+
					$series = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
					    return implode(',', range($m[1], $m[2]));
					}, $series);
				*/

				/* Compatible with older versions of PHP */

				$callback = create_function('$m', 'return implode(\',\', range($m[1], $m[2]));');
				$series = preg_replace_callback('/(\d+)-(\d+)/', $callback, $series);

			// Store posts IDs and key

			self::$state['sort_posts'] = self::explode_list($series);
			self::$state['sort_key'] = $parameters['key'];

			// Get the posts to be sorted later

			$query['meta_query'] = array(
				array(
					'key' => self::$state['sort_key'],
					'value' => self::$state['sort_posts'],
					'compare' => 'IN'
				)
			);
		}


    /*---------------------------------------------
     *
     * Date query: before and after
     *
     */
    
    if ( !empty($parameters['before']) ) {

      // Published date
      if ( empty($parameters['field']) || $parameters['field']=='date') {

        if ( !isset($query['date_query']) ) {
          $query['date_query'] = array();
        }
        $query['date_query'][] = array(
          'before' => $parameters['before']
        );

      } else {
        // Other field
        $parameters['value'] = strtotime($parameters['before']);
        $parameters['compare'] = 'OLD';
      }
    } 

    if (!empty( $parameters['after']) ) {

      if ( empty($parameters['field']) || $parameters['field']=='date') {

        if ( !isset($query['date_query']) ) {
          $query['date_query'] = array();
        }
        $query['date_query'][] = array(
          'after' => $parameters['after']
        );

      } else {
        // Other field
        $parameters['value'] = strtotime($parameters['after']);
        $parameters['compare'] = 'NEW';
      }
    }





		/*========================================================================
		 *
		 * Field value
		 *
		 */

		if( !empty($parameters['field']) &&	!empty($parameters['value']) ) {

			$field = $parameters['field'];
			$value = $parameters['value'];
			$compare = $parameters['compare'];

			// Support for date values

			if ($value == 'future') {
				$value = 'today';
				$compare = '>';
			} elseif ($value == 'future-time') {
				$value = 'now';
				$compare = '>';
			} elseif ($value == 'past') {
				$value = 'today';
				$compare = '<';
			} elseif ($value == 'past-time') {
				$value = 'now';
				$compare = '<';
			}


			if ( ($parameters['in'] == 'string') || (!empty($parameters['date_format'])) ) {

				if (empty($parameters['date_format'])) {

					// default date format
					if ($value == 'today')
						$parameters['date_format'] = 'Y-m-d'; // 2014-01-24
					elseif ($value == 'now')
						$parameters['date_format'] = 'Y-m-d H:i'; // 2014-01-24 13:05
				}

				if (($value == 'today') || ($value == 'now')){
					$value = date($parameters['date_format'],time());
				}

			} else {

				// It's a timestamp so today/now is the same

				if (($value == 'today') || (($value == 'now'))){
					$value = time();
				}
			}


			$compare = strtoupper($compare);

			switch ($compare) {
				case '':
				case '=':
				case 'EQUAL': $compare = '='; break;
				case 'NOT':
				case '!=':
				case 'NOT EQUAL': $compare = '!='; break;
				case 'MORE': 
        case 'NEW': 
        case 'NEWER': $compare = '>'; break;
				case 'LESS': 
        case 'OLD': 
        case 'OLDER': $compare = '<'; break;
				default: break;
			}

      if (!isset($query['meta_query'])) $query['meta_query'] = array();

			$query['meta_query'][] =
				array(
						'key' => $field,
//						'value' => $value,
						'compare' => $compare
				);

			if ( $compare!='EXISTS' && $compare!='NOT EXISTS') {
				$query['meta_query'][0]['value'] = $value;
			} elseif ($compare!='NOT EXISTS') {
				$query['meta_query'][0]['value'] = ' '; // NOT EXISTS needs some value
			} else {
				// $compare=='EXISTS' then no value
			}

      if ($field == 'date') {
        $query['meta_query'][0]['type'] = 'DATE';
      }



			// Additional query by field value

			if ( !empty($parameters['field_2']) && !empty($parameters['value_2']) ) {

				$field_2 = $parameters['field_2'];
				$value_2 = $parameters['value_2'];
				$relation = $parameters['relation'];
				$compare_2 = $parameters['compare_2'];

				if (!empty($relation)) {

					$relation = strtoupper($relation);

					// Alias
					switch ($relation) {
						case '&': $relation = 'AND'; break;
						case '|': $relation = 'OR'; break;
					}

					$query['meta_query']['relation'] = $relation;
				}
				else
					$query['meta_query']['relation'] = 'AND';

				if (!empty($compare_2)) {

					$compare_2 = strtoupper($compare_2);

					switch ($compare_2) {
						case '':
						case '=':
						case 'EQUAL': $compare_2 = '='; break;
						case 'NOT':
						case '!=':
						case 'NOT EQUAL': $compare_2 = '!='; break;
						case 'MORE': $compare_2 = '>'; break;
						case 'LESS': $compare_2 = '<'; break;
						default: break;
					}					
				}

				$query['meta_query'][] =
					array(
						'key' => $field_2,
						'value' => $value_2,
						'compare' => $compare_2
				);
			}
		}

		return apply_filters( 'ccs_loop_query_filter', $query );

	} // End prepare query


	/*========================================================================
	 *
	 * Run the prepared query and return posts (WP_Query object)
	 *
	 */

	public static function run_query( $query ) {

		self::$query = $query; // Store query parameters

		self::$state['do_reset_postdata'] = true; // Reset post data at the end of loop

		return new WP_Query( $query );
	}


	public static function prepare_posts( $posts ) {

		$parameters = self::$parameters;

		// Sort by series

		if ( !empty($parameters['series']) ) {

			usort( $posts->posts, array($this, 'sort_by_series') );
		}

		// Random order

		if ( $parameters['orderby'] == 'rand' ) {
			shuffle( $posts->posts );
		}

		return $posts;
	}



	/*========================================================================
	 *
	 * Loop through each post and compile template
	 *
	 */

	public static function compile_templates( $posts, $template, $check_posts = true ) {

		global $post;

		$templates = array();

		$posts = apply_filters( 'ccs_loop_posts', $posts );

		$template = self::pre_process_template($template);

		if ( $check_posts && $posts->have_posts() ) {

			$posts = self::prepare_all_posts( $posts );

			while ( $posts->have_posts() ) {

				// Set up post data
				$posts->the_post();

				self::$state['current_post_id'] = get_the_ID();

				$this_post = self::prepare_each_post( $post );

				if (!empty($this_post)) {

					self::$state['loop_count']++;

					$this_template = self::prepare_each_template($template);
					$templates[] = self::render_template($this_template);

				} // End: if this post not empty

			} // End: while loop through each post

			if (isset(self::$state['if_empty_else'])) {
				$this_template = self::prepare_each_template(self::$state['if_empty_else']);
				$templates[] = self::render_template($this_template);
			}				
		} else {

			// No post found: do [if empty]
			if (!empty(self::$state['if_empty'])) {
				$this_template = self::prepare_each_template(self::$state['if_empty']);
				$templates[] = self::render_template($this_template);
			}
		}

		return $templates;
	}


	/*========================================================================
	 *
	 * Pre-process template: if first, last, empty
	 *
	 */

	public static function pre_process_template( $template ) {

		$state = self::$state;

		// If empty

		$start = '[if empty]'; $end = '[/if]';
		$middle = self::get_between($start, $end, $template);
		$template = str_replace($middle, '', $template);
		$else = self::extract_else( $middle );

		$state['if_empty'] = $middle;
		$state['if_empty_else'] = $else;

		// If first

		$start = '[if first]'; $end = '[/if]';
		$middle = self::get_between($start, $end, $template);
		$else = self::extract_else( $middle );

		$state['if_first'] = $middle;
		$state['if_first_else'] = $else;


		// If last

		$start = '[if last]'; $end = '[/if]';
		$middle = self::get_between($start, $end, $template);
		$else = self::extract_else( $middle ); // Remove and return what's after [else]

		$state['if_last'] = $middle;
		$state['if_last_else'] = $else;


		self::$state = $state; // Update global state
		return $template;
	}


	/*========================================================================
	 *
	 * [if]..[else] - returns whatever is after [else] and removes it from original template
	 *
	 */

	public static function extract_else( &$template ) {
		// Get [else] if it exists
		$content_array = explode('[else]', $template);

		if (count($content_array)>1) {
			$after = $content_array[1]; // anything after [else]
			$template = str_replace('[else]'.$after, '', $template);
		} else {
			$after = null; // no [else]
		}

		return $after;
	}



	/*========================================================================
	 *
	 * Prepare all posts: takes and returns a WP_Query object
	 *
	 */
	
	public static function prepare_all_posts( $query_object ) {

		$parameters = self::$parameters;
		$query = self::$query;
		$state =& self::$state; // Update global state directly

		$state['post_count'] = $query_object->post_count;

		if ( isset($query['meta_query'][0]) ) {
			$compare = $query['meta_query'][0]['compare'];
			$key = $query['meta_query'][0]['key'];
		} else {
			$compare = '';
			$key = '';
		}

		// If we need to check for skipped post
		
		if ( $compare=='EXISTS' || $compare=='NOT EXISTS' ||
			!empty($parameters['checkbox']) || !empty($parameters['start']) ) {

			$all_posts = $query_object->posts;

			foreach ($all_posts as $post) {

				$current_id = $post->ID;

				/*========================================================================
				 *
				 * If field value exists or not
				 *
				 */

				if (isset($query['meta_query'][0]) && isset($query['meta_query'][0]['key'])) {

					$field_value = get_post_meta( $post->ID, $key, true );

					if (!empty($field_value) && is_array($field_value)) $field_value = implode('', $field_value);

					$field_value = trim($field_value);

					if ( ($field_value==false) || empty($field_value) ) {

						if ($compare=='EXISTS') {
							$state['skip_ids'][] = $current_id; // value is empty, then skip
						}
					} elseif ($compare=='NOT EXISTS') {
							$state['skip_ids'][] = $current_id; // value is not empty, then skip
					}
				}


				/*========================================================================
				 *
				 * If field value starts with
				 *
				 */
				
				if (!empty($parameters['start'])) {

					$field_value = CCS_Content::get_prepared_field( $parameters['field'], $post->ID );
//					$field_value = get_post_meta( $post->ID, $parameters['field'], true );

					$skip = true;
					if (!empty($field_value)) {
						// Get beginning of field value
						$beginning = substr($field_value, 0, strlen($parameters['start']));

						switch ($parameters['compare']) {
							case '':
							case 'equal':
								if ($beginning == $parameters['start'])
									$skip = false;
								break;
							case '>':
							case 'more':
								if ($beginning > $parameters['start'])
									$skip = false;
								break;
							case '<':
							case 'less':
								if ($beginning < $parameters['start'])
									$skip = false;
								break;
							case '>=':
								if ($beginning >= $parameters['start'])
									$skip = false;
								break;
							case '<=':
								if ($beginning <= $parameters['start'])
									$skip = false;
								break;
							case '!=':
							case 'not':
								if ($beginning != $parameters['start'])
									$skip = false;
								break;
						}
					} // End if there's field value

					if ($skip) {
						$state['skip_ids'][] = $current_id;
					}
				}		


				/*========================================================================
				 *
				 * Checkbox query
				 *
				 */

				$skip_1 = false;

				if (!empty($parameters['checkbox']) && !empty($parameters['value'])) {

					$values = self::explode_list($parameters['value']);
					$check_field = get_post_meta( $current_id, $parameters['checkbox'], $single=true );

					if (empty($parameters['compare'])) $compare="or";
//					elseif (empty($parameters['checkbox_2'])) $compare = strtolower($parameters['compare']);
//					else $compare="or";
					else $compare = strtolower($parameters['compare']);

					if ($compare == 'or') $skip_1 = true;

					foreach ($values as $value) {

						$in_array = in_array($value, (array)$check_field);

						if (($compare == 'or') && ( $in_array )) {
							$skip_1 = false;
							break;						
						}

						if (($compare == 'and') && ( ! $in_array )) {
							$skip_1 = true;
						}

					}
				}

				$skip_2 = false;
				if ( !empty($parameters['checkbox_2']) && !empty($parameters['value_2']) ) {
					$values = self::explode_list($parameters['value_2']);
					$check_field = get_post_meta( $current_id, $parameters['checkbox_2'], $single=true );

					if (!empty($parameters['compare_2'])) $compare_2 = strtolower($parameters['compare_2']);
					else $compare_2 = 'or';

					if ($compare_2 == 'or') $skip_2 = true;

					foreach ($values as $value) {

						$in_array = in_array($value, (array)$check_field);

						if (($compare_2 == 'or') && ( $in_array )) {
							$skip_2 = false;
							break;						
						}

						if (($compare_2 == 'and') && ( ! $in_array )) {
							$skip_2 = true;
						}
					}
				}

				if (!empty($parameters['checkbox_2'])) {

					if (!empty($parameters['relation'])) 
						$relation = strtoupper($parameters['relation']);
					else
						$relation = 'AND'; // default

					if ($relation=='OR') {
						if ( ( ! $skip_1 ) || ( ! $skip_2 ) )
							$skip = false;
						else
							$skip = true;
					} else {
						if ( ( ! $skip_1 ) && ( ! $skip_2 ) )
							$skip = false;
						else
							$skip = true;
					}
				} else {
					$skip = $skip_1;
				}

				if ($skip) {
					$state['skip_ids'][] = $current_id;
				}

			} // End for each post


			// Subtract skipped posts from post count

			$state['post_count'] = $state['post_count'] - count($state['skip_ids']);

		} // End if we need to check for skipped posts


		return $query_object;
	}

	public static function prepare_each_post( $post ) {

		$post_id = $post->ID;

		// Skip

		if ( in_array($post_id, self::$state['skip_ids']) ) {
			return null;
		}

		self::$state['comment_count']+=get_comments_number();

		return apply_filters('ccs_loop_each_post', $post);
	}


	public static function prepare_each_template( $template ) {

		$state = self::$state;
		$parameters = self::$parameters;

		/*========================================================================
		 *
		 * Do [if first]
		 *
		 */
		
		if ( $state['loop_count'] == 1 ) {

			if ($state['if_first']) {
				$else = isset($state['if_first_else']) ? '[else]'.$state['if_first_else'] : null;
				$template = str_replace('[if first]'.$state['if_first'].$else.'[/if]', $state['if_first'], $template);
			}
		}

		/*========================================================================
		 *
		 * Do [if last]
		 *
		 */
		
		if ( $state['loop_count'] == $state['post_count'] ) {

			if ($state['if_last']) {
				$else = isset($state['if_last_else']) ? '[else]'.$state['if_last_else'] : null;
				$template = str_replace('[if last]'.$state['if_last'].$else.'[/if]', $state['if_last'], $template);
			}
		}


		/*========================================================================
		 *
		 * Clean each template of <br> and <p>
		 *
		 */
		
		if ($parameters['clean']=='true') {

			$template = CCS_Format::clean_content( $template );

		}


		// Make sure to limit by count parameter

		if ( !empty(self::$parameters['count']) &&
			( $state['loop_count'] > $parameters['count']) )
			return null;

		return $template;		
	}



	/*========================================================================
	 *
	 * Render template: expand {FIELD} tags and shortcodes
	 *
	 */

	public static function render_template( $template ) {

		$post_id = self::$state['current_post_id'];

		/*========================================================================
		 *
		 * Expand {FIELD} tags
		 *
		 */
		
		if (strpos($template, '{') !== false) {

			$template = self::render_field_tags( $template, self::$parameters );

		}

		return apply_filters('ccs_loop_each_result', do_shortcode( $template ), self::$parameters );
	}


	/*========================================================================
	 *
	 * Process results array to final output
	 *
	 */
	
	public static function process_results( $results ) {

		$parameters = self::$parameters;

		if ( !is_array($results) ) {
			$results = array($results);
		}

		$results = apply_filters('ccs_loop_all_results', $results );

		$result = apply_filters('ccs_loop_preprocess_combined_result', implode('', $results) );

	/*========================================================================
	 *
	 * Process the combined result
	 *
	 */

		/*========================================================================
		 *
		 * Strip tags
		 *
		 */
					
		if ( !empty($parameters['strip']) ) {

			$strip_tags = $parameters['strip'];

			if ($strip_tags=='true') {

				$result = wp_kses($result, array());

			} else {

				// Allow certain tags

				$result = strip_tags(html_entity_decode($result), $strip_tags);
			}
		}		


		/*========================================================================
		 *
		 * Trim
		 *
		 */

		if ( !empty($parameters['trim']) ) {

			$trim = $parameters['trim'];
			if ($trim=='true') $trim = null;

			if ( empty($parameters['columns']) && $parameters['list']!='true' ) {
				$result = trim($result, " \t\n\r\0\x0B,".$trim);
			} else {

				// Trim each item for columns/list
				$new_results = array();
				foreach ($results as $result) {
					$new_results[] = trim($result, " \t\n\r\0\x0B,".$trim);
				}
				$results = $new_results;
			}
		}

		/*========================================================================
		 *
		 * Finally, columns or list
		 *
		 */

		if ( !empty($parameters['columns']) ) {

			$result = self::render_columns( $results, $parameters['columns'], $parameters['pad'], $parameters['between'] );

		} elseif ( !empty($parameters['list']) ) {

			// Wrap each list item
			$new_results = null;

			$item_tag = !empty($parameters['item']) ? $parameters['item'] : 'li';
			$item_class = !empty($parameters['item_class']) ?
				' class="'.$parameters['item_class'].'"' : null;
			$item_style = !empty($parameters['item_style']) ?
				' style="'.$parameters['item_style'].'"' : null;

			$list_tag = ($parameters['list']=='true') ? 'ul' : $parameters['list'];
			$list_class = !empty($parameters['list_class']) ?
				' class="'.$parameters['list_class'].'"' : null;
			$list_style = !empty($parameters['list_style']) ?
				' style="'.$parameters['list_style'].'"' : null;

			$parameters['item_count'] = count($results);

			foreach ($results as $result) {
				$item = '<'.$item_tag.$item_class.$item_style.'>'.$result.'</'.$item_tag.'>';

				if ( !empty($parameters['paginate']) )
					$item = '<'.$list_tag.$list_class.$list_style.'>'.$item.'</'.$list_tag.'>';

				$new_results .= apply_filters( 'ccs_loop_each_item', $item, $parameters );
			}

			if ( empty($parameters['paginate']) )
				$result = '<'.$list_tag.$list_class.$list_style.'>'.$new_results.'</'.$list_tag.'>';
			else $result = $new_results;
		}



		/*========================================================================
		 *
		 * Cache the final result?
		 *
		 */

		if ( self::$state['do_cache'] == 'true' ) {

			CCS_Cache::set_transient( self::$state['cache_name'], $result, $parameters['expire'] );
		}


		return apply_filters('ccs_loop_final_result', $result, $parameters );
	}



	/*========================================================================
	 *
	 * Close the loop
	 *
	 */

	public static function close_loop(){

		$state =& self::$state;
		$parameters = self::$parameters;

		/*========================================================================
		 *
		 * Stop timer
		 *
		 */

		if ( self::$parameters['timer'] == 'true' ) {

			echo CCS_Cache::stop_timer('<br><b>Loop result</b>: ');

		}


		/*========================================================================
		 *
		 * Reset postdata after WP_Query
		 *
		 */

		if (self::$state['do_reset_postdata']) {
			wp_reset_postdata();
			self::$state['do_reset_postdata'] = false;
		}

		/*========================================================================
		 *
		 * If blog was switched on multisite, retore original blog
		 *
		 */

		if ( self::$state['blog'] != 0 ) {
			restore_current_blog();
		}

		// If nested, restore previous state

		if ( self::$state['is_nested_loop'] ) {
			self::$state = array_pop(self::$previous_state);
		} else {
			self::$state['is_loop'] = false;
			self::$state['is_attachment_loop'] = false;
		}
	}







	/*========================================================================
	 *
	 * Columns: takes an array of items, puts them in columns and returns string
	 *
	 */
	
	public static function render_columns( $items, $column_count, $pad = null, $between_row ) {

		$row_count = ceil( count($items) / (int)$column_count ); // How many rows
		$percent = 100 / (int)$column_count; // Percentage-based width for each item

		if ( empty($between_row) ) $between_row = '';
		elseif ($between_row == 'true') $between_row = '<br>';

		$clear = '<div style="clear:both"></div>';

		$wrap_start = '<div class="column-1_of_'.$column_count
			.'" style="width:'.$percent.'%;float:left">';
		$wrap_end = '</div>';

		if (!empty($pad)) {
			$wrap_start .= '<div class="column-inner" style="padding:'.$pad.'">';
			$wrap_end .= '</div>';
		}


		$out = '';
		$index = 0;

		// Generate rows
		for ($i=0; $i < $row_count; $i++) { 

			$each_row = '';

			// Generate columns
			for ($j=0; $j < $column_count; $j++) { 

				// Avoid empty columns
				$trimmed = isset($items[ $index ]) ? trim($items[ $index ]) : '';
				if ( !empty( $trimmed ) ) {

					$each_row .= $wrap_start.$items[ $index ].$wrap_end;

				}
				$index++;
			}

			if (!empty($each_row)) {
				$each_row .= $clear;
				$each_row = apply_filters('ccs_loop_each_row',
					do_shortcode($each_row), self::$parameters);
				$out .= $each_row;
			}
		}

/*
		foreach ($items as $each_item) {

			$trimmed = trim($each_item); // Avoid empty columns
			if ( !empty( $trimmed ) ) {

				$column_index++;

				$out .= '<div class="column-1_of_'.$columns.'" style="width:'.$percent.'%;float:left">';

				// Wrap in padding?
				if (!empty($pad)) {
					$out .= '<div class="column-inner" style="padding:'.$pad.'">'.$each_item.'</div>';
				} else {
					$out .= $each_item;
				}

				$out .= '</div>';

				if ( ($column_index % $columns) == 0 ) {

					// The row is full, then clear float
					$out .= $clear;
				}
			}
		}

		if ( ($column_index % $columns) != 0 ) {

			// if last row was not full
			$out .= $clear;
		}
*/

		return $out;
	}



	/*========================================================================
	 *
	 * Pass shortcode - pass field values
	 *
	 */

	public static function pass_shortcode( $atts, $content, $shortcode_name ) {

		$args = array(
			'field' => '',
			'fields' => '',
			'field_loop' => '', 		// Field is array or comma-separated list
			'taxonomy_loop' => '', 		// Loop through each term in taxonomy
			'list' => '', 				// Loop through an arbitrary list of items
			'acf_gallery' => '', 		// Pass image IDs from ACF gallery field

			'current' => '',
			'orderby' => '',			// Default: order by taxonomy term name
			'order' => '',
			'hide_empty' => 'false',

			'pre_render' => 'false', 	// do_shortcode before replacing tags?
			'post_render' => 'true', 	// do_shortcode at the end

			'trim' => 'false',
			'count' => '9999',			// Max number of taxonomy terms

      'user_field' => '',
      'user_fields' => '', // Multiple

      'global' => '',
      'sub' => ''
		);

		extract( shortcode_atts( $args , $atts, true ) );

		if ( $pre_render == 'true' ) $content = do_shortcode($content);

		$post_id = get_the_ID();

		// Support nested

		$prefix = '';
		if (substr($shortcode_name,0,2)=='--') {
			$prefix = '--';
		} elseif (substr($shortcode_name,0,1)=='-') {
			$prefix = '-';
		}


    /*---------------------------------------------
     *
     * Pass single field to {FIELD}
     *
     */

		if ( !empty($field) ) {

			if ($field=='gallery') $field = '_custom_gallery'; // Support CCS gallery field

      if ( !empty($global) ) {

        $field_value = '';
        if ( $field == 'this' ) {
          $field_value = $GLOBALS[$global];
        } elseif ( !empty($sub) && isset($GLOBALS[$global][$field][$sub]) ) {
          $field_value = $GLOBALS[$global][$field][$sub];
        } elseif (isset($GLOBALS[$global][$field])) {
          $field_value = $GLOBALS[$global][$field];
        }

			} elseif (class_exists('CCS_To_ACF') && CCS_To_ACF::$state['is_repeater_or_flex_loop']=='true') {
				// Repeater or flexible content field: then get sub field
				if (function_exists('get_sub_field')) {
					$field_value = get_sub_field( $field );
				} else $field_value = null;

			} else {
				// Get normal field
				$field_value = get_post_meta( $post_id, $field, true );
			}

			if (is_array($field_value)) {

				$field_value = implode(",", $field_value);

			} else {

				// Clean extra spaces if it's a list
				$field_value = self::clean_list($field_value);
			}

			// Replace it

			$content = str_replace('{'.$prefix.'FIELD}', $field_value, $content);


		/*========================================================================
		 *
		 * Pass each item in a list stored in a field
		 *
		 */

		} elseif (!empty($field_loop)) {

			if ( $field_loop=='gallery' && class_exists('CCS_Gallery_Field')) {

				// Support gallery field

				$field_values = CCS_Gallery_Field::get_image_ids(); 

			} else {

				$field_values = get_post_meta( $post_id, $field_loop, true );
			}


			if (!empty($field_values)) {

				if (!is_array($field_values))
					$field_values = self::explode_list($field_values); // Get comma-separated list of values

				$contents = null;

				// Loop for the number of field values

				foreach ($field_values as $field_value) {

					$contents[] = str_replace('{'.$prefix.'FIELD}', $field_value, $content);
				}

				$content = implode('', $contents);
			}

		/*========================================================================
		 *
		 * Pass image IDs from ACF gallery
		 *
		 */

		} elseif (!empty($acf_gallery)) {

			if ( function_exists('get_field') && function_exists('get_sub_field') ) {
				$field = $acf_gallery;
				$images = get_field($acf_gallery, $post_id, false);
				if (empty($field_value)) {
					// Try sub field
					$images = get_sub_field($acf_gallery, $post_id, false);
				}
				if (!empty($images)) {

					$ids = array();
					foreach ($images as $image) {
						$ids[] = $image['id'];
					}
					if (is_array($ids))
						$replace = implode(',', $ids);
					else $replace = $ids;
					$content = str_replace('{'.$prefix.'FIELD}', $replace, $content);
				}
			}


		/*========================================================================
		 *
		 * Pass each taxonomy term
		 *
		 */

		} elseif (!empty($taxonomy_loop)) {

			if ( $current=='true' ) {

				if ( empty($orderby) && empty($order) ) {

					// Doesn't accept order/orderby parameters - but it's cached
					$terms = get_the_terms( $post_id, $taxonomy_loop );
				} else {

					$terms = wp_get_object_terms( $post_id, $taxonomy_loop, array(
						'orderby' => empty($orderby) ? 'name' : $orderby,
						'order' => empty($order) ? 'ASC' : strtoupper($order),
					));

				}

			} else {

				// Get all terms: not by post ID
				$terms = get_terms( $taxonomy_loop, array(
					'orderby' => empty($orderby) ? 'name' : $orderby,
					'order' => empty($order) ? 'ASC' : strtoupper($order),
					'hide_empty' => ($hide_empty=='true') // Boolean
				));
			}

			$contents = '';

			// Loop through each term

			if ( !empty( $terms ) ) {

				$i = 0;

				foreach ($terms as $term) {

					if ($i++ >= $count) break;

					$slug = $term->slug;
					$id = $term->term_id;
					$name = $term->name;

					$replaced_content = str_replace('{'.$prefix.'TERM}',
						$slug, $content);
					$replaced_content = str_replace('{'.$prefix.'TERM_ID}',
						$id, $replaced_content);
					$replaced_content = str_replace('{'.$prefix.'TERM_NAME}',
						$name, $replaced_content);

					$contents .= $replaced_content;
				}
			}

			$content = $contents;


		/*========================================================================
		 *
		 * Pass an arbitrary list of items
		 *
		 */
		
		} elseif (!empty($list)) {

			$items = self::explode_list($list); // Comma-separated list -> array

			$contents = '';

			foreach ($items as $item) {

				$replaced_content = $content;

				// Multiple items per loop
				if ( strpos($item, ':') !== false ) {

					$parts = explode(':', $item);
					$count = count($parts);
					for ($i=0; $i < $count; $i++) { 

						$this_item = trim($parts[$i]);

						// Index starts at ITEM_1
						$replaced_content = str_replace(
							'{'.$prefix.'ITEM_'.($i+1).'}', $this_item, $replaced_content);

						// Would this be useful?
						// $replaced_content = str_replace('{Item_'.$i.'}', ucfirst($this_item),
						//	$replaced_content);
					}

				} else {
					$replaced_content = str_replace('{'.$prefix.'ITEM}',
						$item, $replaced_content);
					$replaced_content = str_replace('{'.$prefix.'Item}',
						ucfirst($item), $replaced_content );
				}

				$contents .= $replaced_content;
			}

			$content = $contents;

		}


    /*========================================================================
     *
     * Pass user field(s)
     *
     */
    
    if (!empty($user_field)) {
      $user_field_value = do_shortcode('[user '.$user_field.']');
      // Replace it
      $content = str_replace('{'.$prefix.'USER_FIELD}', $user_field_value, $content);
    }

    if (!empty($user_fields)) {
      $user_fields_array = self::explode_list($user_fields);

      foreach ($user_fields_array as $this_field) {
        $user_field_value = do_shortcode('[user '.$this_field.']');
        // Replace {FIELD_NAME}
        $content = str_replace('{'.$prefix.strtoupper($this_field).'}', $user_field_value, $content);
      }
    }


		if ( !empty($fields) ) {
			// Replace these fields (+default)
			$content = self::render_field_tags( $content, array('fields' => $fields) );
		} else {
			$content = self::render_default_field_tags( $content );
		}

		if ( $post_render == 'true' ) $content = do_shortcode($content);

		// Trim trailing white space and comma
		if ( $trim != 'false' ) {

			if ($trim=='true') $trim = null;
			$content = rtrim($content, " \t\n\r\0\x0B,".$trim);
		}

		return $content;

	} // End pass shortcode



	/*========================================================================
	 *
	 * Process {FIELD} tags
	 *
	 */



	public static function render_field_tags( $template, $parameters ) {

		$post_id = !empty($parameters['id']) ? $parameters['id'] : get_the_ID();

		/*========================================================================
		 *
		 * User defined fields
		 *
		 */

		if (!empty($parameters['fields'])) {

			$fields = self::explode_list($parameters['fields']);

			foreach ($fields as $key) {

				$search = '{'.strtoupper($key).'}';

				if (strpos($template, $search)!==false) {

					if (class_exists('CCS_To_ACF') && CCS_To_ACF::$state['is_repeater_or_flex_loop']=='true') {
						// Repeater or flexible content field: then get sub field
						if (function_exists('get_sub_field')) {
							$field_value = get_sub_field( $key );
						} else $field_value = null;
					} else {
						$field_value = get_post_meta( $post_id, $key, true );

					}

					if (is_array($field_value)) {
						$field_value = ucwords(implode(', ', $field_value));
					}

					$template = str_replace($search, $field_value, $template);
				}
			}
		}

		// Render default tags later, to allow custom fields to take priority
		$template = self::render_default_field_tags( $template );

		return $template;
	}




	public static function render_default_field_tags( $template ) {

		/*========================================================================
		 *
		 * Predefined field tags
		 *
		 */

		$keywords = array(
			'URL', 'SLUG', 'ID', 'COUNT', 'TITLE', 'AUTHOR', 'DATE', 'THUMBNAIL', 'THUMBNAIL_URL',
			'CONTENT', 'EXCERPT', 'COMMENT_COUNT', 'TAGS', 'IMAGE', 'IMAGE_ID', 'IMAGE_URL',
		);

		foreach ($keywords as $key) {

			$search = '{'.$key.'}';

			if (strpos($template, $search)!==false) {

				$replace = $search;

				switch ($key) {
					case 'URL':
						$replace = get_permalink();
						break;
					case 'ID':
						$replace = get_the_ID();
						break;
					case 'SLUG':
						$replace = self::get_the_slug();
						break;
					case 'COUNT':
						$replace = self::$state['loop_count'];
						break;
					case 'TITLE':
						$replace = get_the_title();
						break;
					case 'AUTHOR':
						$replace = get_the_author();
						break;
					case 'AUTHOR_URL':
						$replace = get_author_posts_url( get_the_author_meta( 'ID' ) );
						break;
					case 'DATE':
						$replace = get_the_date();
						break;
					case 'THUMBNAIL':
						$replace = get_the_post_thumbnail( null, 'thumbnail' );
						break;
					case 'THUMBNAIL_URL':
						$replace = wp_get_attachment_url(get_post_thumbnail_id(get_the_ID()));
						break;
					case 'CONTENT':
						$replace = get_the_content();
						break;
					case 'EXCERPT':
						$replace = get_the_excerpt();
						break;
					case 'COMMENT_COUNT':
						$replace = get_comments_number();
						break;
					case 'TAGS':
						$replace = strip_tags( get_the_tag_list('',', ','') );
						break;
					case 'IMAGE':
						$replace = get_the_post_thumbnail();
						break;
					case 'IMAGE_ID':
						$replace = get_post_thumbnail_id(get_the_ID());
						break;
					case 'IMAGE_URL':
						$replace = wp_get_attachment_url(get_post_thumbnail_id(get_the_ID()));
						break;
					default:
						break;
				}

				$template = str_replace($search, $replace, $template);
			}

		}

		return $template;
	}




/*========================================================================
 *
 * Helper functions
 *
 */

	// Get text between two strings

	public static function get_between($start, $end, $text) {

		$middle = explode($start, $text);
		if (isset($middle[1])){
			$middle = explode($end, $middle[1]);
			$middle = $middle[0];
			return $middle;
		} else {
			return false;
		}
	}


	// Explode comma-separated list and remove extra space from each item

	public static function explode_list( $list, $delimiter = null ) {

 		// Support multiple delimiters

		$delimiter .= ','; // default
		$delimiters = str_split($delimiter); // convert to array
 
		$list = str_replace($delimiters, $delimiters[0], $list); // change all delimiters to same

		// explode list and trim each item 	

		return array_map('trim', array_filter(explode($delimiters[0], $list)));
	}

	// Explode the list, trim each item and put it back together

	public static function clean_list( $list, $delimiter = null ) {

		if (empty($delimiter)) $delimiter = ',';
		$list = self::explode_list($list, $delimiter);
		return implode($delimiter,$list);
	}


	/*============================================================================
	 *
	 * Sort series
	 *
	 *===========================================================================*/

	public static function sort_by_series( $a, $b ) {

		$apos = array_search( get_post_meta( $a->ID, self::$state['sort_key'], true ), self::$state['sort_posts'] );
		$bpos = array_search( get_post_meta( $b->ID, self::$state['sort_key'], true ), self::$state['sort_posts'] );

		return ( $apos < $bpos ) ? -1 : 1;
	}


	// Get post slug from ID

	public static function get_the_slug( $id = null ) {

		global $post;

		if ( !empty($id) ) {
			$this_post = get_post($id);
		} else {
			$this_post = $post;
		}

		if (!empty($this_post)) {
			return $this_post->post_name;
		} else {
			return null;
		}
	}


	/*========================================================================
	 *
	 * [loop-count] - Display current loop count
	 *
	 */
	
	public static function loop_count_shortcode() {

		return CCS_Loop::$state['loop_count'];
	}

	public static function found_posts_shortcode() {
		global $wp_query;

		if (!empty($wp_query))
			return $wp_query->post_count;
	}

	public static function search_keyword_shortcode() {
		global $wp_query;

		if (!empty($wp_query)) {
			$vars = $wp_query->query_vars;
			if (isset($vars['s']))
				return $vars['s'];
		}
	}


} // End CCS_Loop

