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
		self::$state['do_cache'] 			= false;
		self::$state['blog'] 				= 0;

		self::$state['loop_count']			= 0;

		self::$state['current_post_id']		= 0;
		self::$state['posts_count']			= 0;
		self::$state['comments_count']		= 0;

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
			'columns' => '', 'pad' => '',
			'strip_tags' => '', 'strip' => '', 'allow' => '',
			'clean' => 'false', 'trim' => '',

			// Gallery

			'gallery' => '', 'acf_gallery' => '',
			'repeater' => '', // ACF repeater
			
			// Other

			'fields' => '', 'custom_fields' => '', // CSV list of custom field names to expand
			'blog' => '', // Multi-site (not tested)
			'x' => '', // Just loop X times, no query

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

		// Support aliases

//		if ( !empty($tax) ) $taxonomy = $tax;


		return $merged;
	}


	/*========================================================================
	 *
	 * Check cache based on parameters
	 * 
	 * If update is not true, returns cached result
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
//			$cache_num = substr($string, 0, 40); // Max number of characters

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
			if ( $parameters['type'] == 'attachment' )

				$query['post_status'] = array('any');
			else
				$query['post_status'] = array('publish');
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



		return apply_filters( 'ccs_loop_query_filter', $query );


	} // End prepare query


	/*========================================================================
	 *
	 * Run the prepared query and return posts
	 *
	 *=======================================================================*/

	function run_query( $query ) {

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
		







		$template = do_shortcode( $template );

		return $template;
	}


	/*========================================================================
	 *
	 * Process results to final output
	 *
	 *=======================================================================*/
	
	function process_results( $results ) {

		$parameters = self::$parameters;

		if ( is_array($results) ) {



			/*========================================================================
			 *
			 * Do [if last]
			 *
			 *=======================================================================*/

			// Find last template

			// Replace [if last]..[/if]



			// Combine results
			$result = implode('', $results);

		} else {

			$result = $results;
		}




		// Process final result

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

			$result = trim($result, " \t\n\r\0\x0B,".$trim);
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
		 * If blog was switched on multisite, retore original blog
		 *
		 *=======================================================================*/

		if ( self::$state['blog'] != 0 ) {
			restore_current_blog();
		}

		self::$state['is_loop'] = false;

	}











	/*========================================================================
	 *
	 * Helper functions
	 *
	 *=======================================================================*/


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
	 * [loop-index]
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

