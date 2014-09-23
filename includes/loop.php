<?php

/*====================================================================================================
 *
 * [loop] - Query posts and loop through each item
 *
 *====================================================================================================*/

new CCS_Loop;

class CCS_Loop {

	private static $original_parameters;	// Shortcode parameters
	private static $parameters;				// After merge with default
	private static $query;					// The query
	public static $state;					// Loop state array

	function __construct() {


		// Initialize global
		$this->init();


	/*========================================================================
	 *
	 * Define shortcodes
	 *
	 *=======================================================================*/

		add_shortcode( 'loop', array($this, 'the_loop_shortcode') );
		add_shortcode( 'pass', array($this, 'pass_shortcode') );

		add_shortcode( 'loop-count', array($this, 'loop_count_shortcode') );
		add_shortcode( 'x', array($this, 'x_shortcode') );
	}




	/*========================================================================
	 *
	 * Initialize global
	 *
	 *=======================================================================*/

	function init() {

		self::$state['is_loop'] = false;
		self::$state['is_attachment_loop'] = false;
		self::$state['do_reset_postdata'] = false;
	}



	/*========================================================================
	 *
	 * Loop shortcode: main actions
	 * 
	 *=======================================================================*/

	function the_loop_shortcode( $parameters, $template ) {

		// Initialize loop state
		$this->init_loop();

			// Store original parameters
			self::$original_parameters = $parameters;

			// Merge parameters with defaults
			$parameters = $this->merge_with_defaults( $parameters );
			// Store merged parameters
			self::$parameters = $parameters;


				// Check cache - if loaded, return result
				if ( ($result = $this->check_cache( $parameters )) !== false ) {
					$this->close_loop();
					return $result;
				}

				// If there's already result based on parameters, return it
				$result = $this->before_query( $parameters );
				if ( !empty( $result ) ) {
					$this->close_loop();
					return $result;
				}


			// Set up query based on parameters
			$query = $this->prepare_query( $parameters );

				// Get posts from query
				$posts = $this->run_query( $query );
			
				// Pre-process posts
				$posts = $this->prepare_posts( $posts );

				// Loop through each post and compile shortcode template
				$results = $this->compile_templates( $posts, $template );

			// Combine results and process to final output
			$result = $this->process_results( $results );

		$this->close_loop();

		return $result;
	}



	/*========================================================================
	 *
	 * Initialize loop state
	 *
	 *=======================================================================*/

	function init_loop() {

		self::$state['is_loop'] 			= true;
		self::$state['do_reset_postdata'] 	= false;
		self::$state['do_cache'] 			= false;
		self::$state['blog'] 				= 0;

		self::$state['loop_count']			= 0;

		self::$state['current_post_id']		= 0;
		self::$state['posts_count']			= 0;
		self::$state['comments_count']		= 0;

		self::$state['is_attachment_loop'] = false;
	}


	/*========================================================================
	 *
	 * Define all parameters
	 *
	 *=======================================================================*/

	function merge_with_defaults( $parameters ){

		$defaults = array(

			'type' => '',
			'name' => '',
			'id' => '', 'exclude' => '',
			'status' => '',
			'parent' => '', 
			'count' => '', 'offset' => '',
			'year' => '', 'month' => '', 'day' => '',

			// Taxonomy

			'taxonomy' => '', 'term' => '',
			'tax' => '', // Alias

			'category' => '', 'tag' => '', 

			// Field value

			'field' => '', 'value' => '',
			'compare' => '',
			'in' => '', // ??
			// Additional field value query
			'field_2' => '', 'value_2' => '', 'compare_2' => '', 'relation' => '',
			'f' => '', 'v' => '', 'c' => '', 'f2' => '', 'v2' => '', 'c2' => '', 'r' => '', // Alias

			// Checkbox

			'checkbox' => '', 'checkbox_2' => '', 

			// Sort

			'orderby' => '', 'order' => '',
			'series' => '', 'key' => '',

			// Format

			'date_format' => '',
			'strip_tags' => '', 'strip' => '', 'allow' => '',
			'clean' => 'false', 'trim' => '',

			// Columns

			'columns' => '', 'pad' => '', 'between' => '',

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

			// ?
			'if' => '', 'list' => '', 'posts_separator' => '',
			'variable' => '', 'var' => '',
			'content_limit' => 0,
			'thumbnail_size' => 'thumbnail',
			'title' => '', 'post_offset' => '',
			'keyname' => '', 
		);

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
	 *=======================================================================*/

	function check_cache( $parameters ) {

		$result = false;

		if ($parameters['cache']=='true') {

			self::$state['do_cache'] = 'true';

			// Generate unique cache name from the original shortcode parameters

			$cache_name = null;

			ksort(self::$original_parameters); // Alphabetical sort

			foreach (self::$original_parameters as $key => $value) {

				if ( ($key!='update') && ($key!='cache')) // skip these parameters
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
	 *=======================================================================*/

	function before_query( $parameters ) {


		/*========================================================================
		 *
		 * The X parameter - run loop X times, no query
		 *
		 *=======================================================================*/
		
		


		


		/*========================================================================
		 *
		 * Switch to blog on multisite - restore during close_loop
		 * 
		 * ** Not tested **
		 *
		 *=======================================================================*/

		self::$state['blog'] = 0;

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
	 *=======================================================================*/

	function prepare_query( $parameters ) {

		$query = array();


		/*========================================================================
		 *
		 * field="gallery"
		 *
		 *=======================================================================*/

		if ( $parameters['field'] == 'gallery' ) {

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
		 *=======================================================================*/
		
		
		if ( !empty($parameters['type']) ) {

			$query['post_type'] = $parameters['type'];

		} else {

			$query['post_type'] = 'any';
		}

		/*========================================================================
		 *
		 * Post ID, exclude ID, name, or parent(s)
		 *
		 *=======================================================================*/
		
		if ( !empty($parameters['id']) ) {

			$id_array = $this->explode_list($parameters['id']);

			$query['post__in'] = $id_array;
			$query['orderby'] = 'post__in'; // Preserve ID order

		} elseif ( !empty($parameters['exclude']) ) {

			$id_array = $this->explode_list($parameters['exclude']);

			$query['post__not_in'] = $id_array;

		} elseif ( !empty($parameters['name']) ) {

			$query['name'] = $parameters['name']; 

		} elseif ( !empty($parameters['parent']) ) {

			$parent = $parameters['parent'];

			if ( is_numeric($parent) )

				$query['post_parent'] = intval( $parent ); // Single parent ID

			else {

				$parents = $this->explode_list( $parent ); // Convert to array

				// Multiple IDs

				if ( is_numeric($parents[0]) ) {

					$parent_IDs = $parents;

				} else {

					// Get parent ID(s) by slug (pretty expensive query)

					$parent_IDs = array();

					foreach ($parents as $parent_slug) {

						$posts = get_posts( array('name' => $parent_slug, 'post_type' => $query['type'], 'posts_per_page' => 1) );

						if ( $posts ) $parent_IDs[] = $posts[0]->ID;
					}

				}

				$query['post_parent__in'] = $parent_IDs;

			} // End single/multiple

		} // End if parent pameter

		$query['ignore_sticky_posts'] = true;


		/*========================================================================
		 *
		 * Post status
		 *
		 *=======================================================================*/

		if ( !empty($parameters['status']) ) {

			$query['post_status'] = $this->explode_list( $parameters['status'] );

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
		 *=======================================================================*/

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

				$query['posts_per_page'] = '9999'; // Show all posts (to make offset work)

			else
				$query['posts_per_page'] = '-1'; // Show all posts (normal method)
		}




		/*========================================================================
		 *
		 * Date
		 *
		 *=======================================================================*/

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
		 *=======================================================================*/

		if ( !empty($parameters['category']) ) {

			// Category can be slug, ID, multiple

			$category = $parameters['category'];
			$categories = $this->explode_list( $category, ',+' );

			$check_category = array_pop($categories); // Check one item

			if ( is_numeric($check_category) )
				$query['cat'] = $category; // ID(s)
			else
				$query['category_name'] = $category; // Slug(s)

		}


		/*========================================================================
		 *
		 * Tag
		 *
		 *=======================================================================*/

		if( !empty($parameters['tag']) ) {

			// Remove extra space in a list

			$tags = $this->clean_list( $parameters['tag'] );
			$query['tag'] = $tags;
		}


		/*========================================================================
		 *
		 * Taxonomy
		 *
		 *=======================================================================*/

		if ( !empty($parameters['taxonomy']) ) {

			$taxonomy = $parameters['taxonomy'];

			if ( !empty($parameters['term']) )
				$term = $parameters['term'];
			else
				$term = $parameters['value']; // Alias, if field value is not used

			$terms = $this->explode_list($term); // Multiple terms possible

			if ( !empty($parameters['compare']) ) {

				$compare = $parameters['compare'];

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

			$query['tax_query'] = array (
				array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $terms,
					'operator' => $operator
				)
			);
		}




		/*========================================================================
		 *
		 * Order and orderby
		 *
		 *=======================================================================*/

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
					$key = $parameters['field']; // Alias

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
		 *=======================================================================*/
		
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

			self::$state['sort_posts'] = $this->explode_list($series);
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



		/*========================================================================
		 *
		 * Query by field value
		 *
		 *=======================================================================*/

		if( !empty($parameters['field']) && !empty($parameters['value']) ) {


			$field = $parameters['field'];
			$value = $parameters['value'];
			$compare = $parameters['compare'];

			// Support for date values

			if ($value=='future') {
				$value = 'now';
				$compare = '>';
			} elseif ($value=='past') {
				$value = 'now';
				$compare = '<';
			}

			if ( ($parameters['in'] == 'string') || (!empty($parameters['date_format'])) ) {

				if (empty($parameters['date_format'])) {

					// default date format
					if ($value == 'today')
						$parameters['date_format'] = 'Y-m-d'; // Y-m-d h:i A
					if ($value == 'now')
						$parameters['date_format'] = 'Y-m-d h:i A'; 
				}

				if (($value == 'today') || ($value == 'now')){
					$value = date($parameters['date_format'],time());
				}
			} else {
				if (($value == 'today') || (($value == 'now'))){
					$value = time();
				}
			}

			$compare = strtoupper($compare);

			switch ($compare) {
				case '':
				case '=':
				case 'EQUAL': $compare = "LIKE"; break;
				case 'NOT':
				case '!=':
				case 'NOT EQUAL': $compare = 'NOT LIKE'; break;
				default: break;
			}

			// To do: shouldn't this be $query['meta_query']..?

			$query['meta_query'][] =
				array(
						'key' => $field,
						'value' => $value,
						'compare' => $compare
				);

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
						case 'EQUAL': $compare_2 = 'LIKE'; break;
						case 'NOT':
						case '!=':
						case 'NOT EQUAL': $compare_2 = 'NOT LIKE'; break;
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
	 * Run the prepared query and return posts
	 *
	 *=======================================================================*/

	function run_query( $query ) {

		self::$state['do_reset_postdata'] = true;

		return new WP_Query( $query );
	}


	function prepare_posts( $posts ) {

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
	 *=======================================================================*/

	function compile_templates( $posts, $template ) {

		global $post;

		$templates = array();

		$posts = apply_filters( 'ccs_loop_posts_before_compile', $posts);

		if ( $posts->have_posts() ) {

			$posts = $this->prepare_all_posts( $posts );

			while ( $posts->have_posts() ) {

				// Set up post data
				$posts->the_post();

				self::$state['current_post_id'] = get_the_ID();

				$this_post = $this->prepare_each_post( $post );

				if (!empty($this_post)) {

					self::$state['loop_count']++;

					$this_template = $this->prepare_each_template($template);

					$templates[] = $this->render_template($this_template);

				} // End: if this post not empty

			} // End: while loop through each post

		} else {

			// No posts found

			$this_template = null;

			// Do [if empty]




			$templates[] = $this->render_template($this_template);
		}

		return $templates;
	}



	/*========================================================================
	 *
	 * Prepare all posts: takes and returns a WP_Query class instance
	 *
	 *=======================================================================*/
	
	function prepare_all_posts( $posts ) {


		/*========================================================================
		 *
		 * Filter posts by checkbox query
		 *
		 *=======================================================================*/
		
		
		self::$state['post_count'] = $posts->post_count;
		self::$state['skip_ids'] = array();


		return $posts;

	}

	function prepare_each_post( $post ) {

		$post_id = $post->ID;

		// Skip?
		if ( in_array($post_id, self::$state['skip_ids']) ) {
			return null;
		}

		return $post;
	}


	function prepare_each_template( $template ) {


		/*========================================================================
		 *
		 * Do [if first]
		 *
		 *=======================================================================*/
		
		if ( self::$state['loop_count'] == 1 ) {

		}

		/*========================================================================
		 *
		 * Do [if last]
		 *
		 *=======================================================================*/
		
		if (self::$state['loop_count'] == self::$state['post_count'] ) {

		}


		/*========================================================================
		 *
		 * Clean
		 *
		 *=======================================================================*/
		
		
		// Over the limit, then skip
		if ( !empty(self::$parameters['count']) &&
			(self::$state['loop_count'] > self::$parameters['count']))
			return null;


		return $template;		
	}

	function render_template( $template ) {

		$post_id = self::$state['current_post_id'];

		/*========================================================================
		 *
		 * Expand {FIELD} tags
		 *
		 *=======================================================================*/
		
		if (strpos($template, '{') !== false) {

			$template = self::render_field_tags( $template, self::$parameters );

		}

		$template = do_shortcode( $template );

		return $template;
	}


	/*========================================================================
	 *
	 * Process results array to final output
	 *
	 *=======================================================================*/
	
	function process_results( $results ) {

		$parameters = self::$parameters;

		if ( is_array($results) ) {



			/*========================================================================
			 *
			 * Do [if last]..?
			 *
			 *=======================================================================*/

			// Find last template
			// Replace [if last]..[/if]


			/*========================================================================
			 *
			 * Combine results
			 *
			 *=======================================================================*/
			
			$result = implode('', $results);

		} else {

			$result = $results;
		}



	/*========================================================================
	 *
	 * Process the combined result
	 *
	 *=======================================================================*/

		/*========================================================================
		 *
		 * Strip tags
		 *
		 *=======================================================================*/
					
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
		 *=======================================================================*/

		if ( !empty($parameters['trim']) ) {

			$trim = $parameters['trim'];
			if ($trim=='true') $trim = null;

			if (empty($parameters['columns'])) {
				$result = trim($result, " \t\n\r\0\x0B,".$trim);
			} else {

				// Trim each item for columns
				$new_results = array();
				foreach ($results as $result) {
					$new_results[] = trim($result, " \t\n\r\0\x0B,".$trim);
				}
				$results = $new_results;
			}
		}

		/*========================================================================
		 *
		 * Finally, columns
		 *
		 *=======================================================================*/

		if ( !empty($parameters['columns']) ) {

			$result = self::render_columns( $results, $parameters['columns'], $parameters['pad'], $parameters['between'] );
		}



		/*========================================================================
		 *
		 * Cache the final result?
		 *
		 *=======================================================================*/

		if ( self::$state['do_cache'] == 'true' ) {

			CCS_Cache::set_transient( self::$state['cache_name'], $result, self::$state['cache_name'] );
		}

		return $result;
	}



	/*========================================================================
	 *
	 * Close the loop
	 *
	 *=======================================================================*/

	function close_loop(){


		/*========================================================================
		 *
		 * Reset postdata after WP_Query
		 *
		 *=======================================================================*/

		if (self::$state['do_reset_postdata']) {
			wp_reset_postdata();
			self::$state['do_reset_postdata'] = false;
		}

		/*========================================================================
		 *
		 * If blog was switched on multisite, retore original blog
		 *
		 *=======================================================================*/

		if ( self::$state['blog'] != 0 ) {
			restore_current_blog();
		}

		self::$state['is_loop'] = false;
		self::$state['is_attachment_loop'] = false;

	}







	/*========================================================================
	 *
	 * Columns: takes an array of items, puts them in columns and returns string
	 *
	 *=======================================================================*/
	
	public static function render_columns( $items, $per_row, $pad = null, $between_row = null ) {

		$column_index = 0;

		$percent = 100 / (int)$per_row; // Percentage-based width for each item

		if ( empty($between_row) ) {
			$between_row = '<br>';
		} elseif ($between_row == 'false') {
			$between_row = '';
		}
		$clear = '<div style="clear:both;">'.$between_row.'</div>';

		$out = null;

		foreach ($items as $each_item) {

			$trimmed = trim($each_item); // Avoid empty columns

			if ( !empty( $trimmed ) ) {

				$column_index++;

				$out .= '<div class="column-1_of_'.$per_row.'" style="width:'.$percent.'%;float:left;">';

				// Wrap in padding?
				if (!empty($pad)) {
					$out .= '<div class="column-inner" style="padding:'.$pad.'">'.$each_item.'</div>';
				} else {
					$out .= $each_item;
				}

				$out .= '</div>';

				if ( ($column_index % $per_row) == 0 ) {

					// The row is full, then clear float
					$out .= $clear;
				}
			}
		}

		if ( ($column_index % $per_row) != 0 ) {

			// if last row was not full
			$out .= $clear;
		}

		return $out;
	}



	/*========================================================================
	 *
	 * Pass shortcode - pass field values
	 *
	 *=======================================================================*/

	function pass_shortcode( $atts, $content ) {

		$args = array(
			'field' => '',
			'fields' => '',
			'field_loop' => '', // Field is array or comma-separated list
			);

		extract( shortcode_atts( $args , $atts, true ) );

		$content = self::render_default_field_tags( $content );

		if ( !empty($fields) ) {

			// $fields = self::explode_list($fields);

			// Replace these fields

			$content = self::render_field_tags( $content, array('fields' => $fields) );
		} 

		if ( !empty($field) ) {

			$post_id = get_the_ID();

			if ($field=='gallery') $field = '_custom_gallery'; // Support gallery field

			$field_value = get_post_meta( $post_id, $field, true );

			if (is_array($field_value)) {

				$field_value = implode(",", $field_value);

			} else {

				// Clean extra spaces if it's a list
				$field_value = self::clean_list($field_value);
			}

			// Replace it

			$content = str_replace('{FIELD}', $field_value, $content);

		} elseif (!empty($field_loop)) {

			$post_id = get_the_ID();

			if ($field_loop=='gallery') {

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

					$contents[] = str_replace('{FIELD}', $field_value, $content);
				}

				$content = implode('', $contents);
			}
		}

		return do_shortcode( $content );

	} // End pass shortcode



	/*========================================================================
	 *
	 * Process {FIELD} tags
	 *
	 *=======================================================================*/



	function render_field_tags( $template, $parameters ) {

		$template = self::render_default_field_tags( $template );
		$post_id = !empty($parameters['id']) ? $parameters['id'] : get_the_ID();

		/*========================================================================
		 *
		 * User defined fields
		 *
		 *=======================================================================*/

		if (!empty($parameters['fields'])) {

			$fields = self::explode_list($parameters['fields']);

			foreach ($fields as $key) {

				$search = '{'.strtoupper($key).'}';

				if (strpos($template, $search)!==false) {

					$replace = get_post_meta( $post_id, $key, true );

					$template = str_replace($search, $replace, $template);
				}
			}
		}


		return $template;
	}




	function render_default_field_tags( $template ) {

		/*========================================================================
		 *
		 * Predefined field tags
		 *
		 *=======================================================================*/

		$keywords = array(
			'URL', 'ID', 'COUNT', 'TITLE', 'AUTHOR', 'DATE', 'THUMBNAIL', 'THUMBNAIL_URL',
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
 *=======================================================================*/

	// Get text between two strings

	public static function getBetween($start, $end, $text) {

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

	public static function clean_list( $list ) {
		$list = self::explode_list($list);
		return implode(',',$list);
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



	/*========================================================================
	 *
	 * [loop-count] - Display current loop count
	 *
	 *=======================================================================*/
	
	function loop_count_shortcode() {

		return CCS_Loop::$state['loop_count'];
	}


	/*========================================================================
	 *
	 * [x] - Repeat x times: [x 10]..[/x]
	 *
	 *=======================================================================*/

	function x_shortcode( $atts, $content ) {

		$out = '';

		if (isset($atts[0])) {
			$x = $atts[0];
			for ($i=0; $i <$x ; $i++) { 
				$out .= do_shortcode($content);
			}
		}
		return $out;
	}










} // End CCS_Loop

