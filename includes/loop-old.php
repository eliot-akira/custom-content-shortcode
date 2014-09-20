<?php

/*====================================================================================================
 *
 * [loop] - Query posts and loop through each item
 *
 *====================================================================================================*/

new CCS_Loop;

class CCS_Loop {

	private static $args;
	private static $query;

	private static $state;			// Loop state
	private static $variable;		// Loop variables
/*
	private static $sort_posts;
	private static $sort_key;

	private static $do_cache;
	private static $cache_name;
	private static $cache_expire;
*/

	function __construct() {

		$this->define_shortcodes();
		$this->add_filters_and_actions();
	}


	/*========================================================================
	 *
	 * Define shortcodes
	 *
	 *=======================================================================*/

	function define_shortcodes() {

		add_shortcode( 'loop', array( $this, 'the_loop_shortcode' ) );
		add_shortcode( 'pass', array( $this, 'pass_shortcode' ) );

		add_shortcode( 'x', array( $this, 'x_shortcode' ) );
		add_shortcode( 'loop-count', array( $this, 'loop_count_shortcode' ) );
//		add_shortcode( 'debug-query', array( $this, 'debug_query_shortcode' ) );

	}


	/*========================================================================
	 *
	 * Set up loop filters and actions
	 *
	 *=======================================================================*/



	function add_actions_and_filters() {

	}



	/*========================================================================
	 *
	 * Main function
	 * 
	 *=======================================================================*/

	function the_loop_shortcode( $atts, $template ) {


		// Initialize loop state and variables

		self::init_loop();

		// Convert shortcode parameters to query

		$query = self::atts_to_query( $atts );

		// Check cache

		$output = self::check_cache($query);
		if ($output !== false) return $output;

		// Get results of query

		$posts = self::run_query($query);

		// Loop through each post and compile template

	}

	function init_loop() {

		self::$state['is_loop'] = 'true';
		self::$state['is_gallery_loop'] = 'false';
		self::$state['is_attachment_loop'] = 'false';
		self::$state['is_repeater_loop'] = 'false';

		self::$variable['current_loop_count'] = 0;
		self::$variable['current_gallery_name'] = '';
		self::$variable['current_gallery_id'] = '';
		self::$variable['total_comments'] = 0;
	}

	function check_cache( $query ) {

		return false;
	}

		/*========================================================================
		 *
		 * Get parameters
		 *
		 *=======================================================================*/

		$args = array(

			'type' => '', 'name' => '',

			'id' => '', 'exclude' => '',
			'status' => '', 'parent' => '', 
			'count' => '', 'offset' => '',
			'year' => '', 'month' => '', 'day' => '',

			// Taxonomy, field value, or checkbox

			'taxonomy' => '', 'tax' => '',
			'category' => '', 'tag' => '', 
			'field' => '', 'value' => '', 'compare' => '', 'in' => '',
			'field_2' => '', 'value_2' => '', 'compare_2' => '', 'relation' => '',
				'f' => '', 'v' => '', 'c' => '', 'f2' => '', 'v2' => '', 'c2' => '', 'r' => '', 
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




		extract( shortcode_atts($args, $atts, true) );

		/*========================================================================
		 *
		 * If cache, try to get it
		 *
		 *=======================================================================*/
		
		if ($cache=='true') {

			self::$do_cache = $cache;
			self::$cache_expire = $expire;

			$result = false;

			// Generate unique cache name from query parameters

			$cache_name = null;
			ksort($atts);
			foreach ($atts as $key => $value) {
				if ( ($key!='update') && ($key!='cache')) // skip these parameters
					$cache_name .= $key.$value;
			}

			$cache_num = substr($string, 0, 40); // Max number of characters

			self::$cache_name = $cache_name;

			if ($update!='true') {
				if (false !== ($result = CCS_Cache::get_transient($cache_name)))
					return $result;
			}
		} else {

		}

		/*========================================================================
		 *
		 * Parameter aliases
		 *
		 *=======================================================================*/
/*
		if (!empty($key)) $keyname=$key;
		if (!empty($offset)) $post_offset=$offset;
		if (!empty($strip)) $strip_tags=$strip;
		if (!empty($allow)) $strip_tags=$allow;

		if (!empty($var)) $variable=$var;

		if ($f!='')	$field = $f;
		if ($v!='')	$value = $v;
		if ($c!='')	$compare = $c;
		if ($f2!='') $field_2 = $f2;
		if ($v!='')	$value_2 = $v2;
		if ($c!='')	$compare_2 = $c2;
		if ($r!='')	$relation = $r;

		if (!empty($custom_fields)) $fields = $custom_fields; // expand multiple fields
*/


		/*========================================================================
		 *
		 * Set up query based on parameters
		 *
		 *=======================================================================*/


		$query = array();
		$output_items = array();
		$output_item = null;

		apply_filters( 'ccs_loop_query_init', $query, $atts );




		// Switch to blog on multi-site, restore at the end
		// To do: test it

		if (!empty($blog)) {
			switch_to_blog($blog);
		}




		/*========================================================================
		 *
		 * Post status
		 *
		 *=======================================================================*/

		if (!empty($status)) {

			$query['post_status'] = $this->explode_list($status);

		} else {

			// Default
			if ($type=="attachment")
				$query['post_status'] = array("any");
			else
				$query['post_status'] = array("publish");
		}



		/*========================================================================
		 *
		 * Parameter X: loop x times, without query
		 * 
		 * To do: in its own function
		 *
		 *=======================================================================*/

		if ( !empty($x) ) {

			$count = 0; $max = $x;

			while($x > 0) {

				$count++;
				$ccs_global_variable['current_loop_count'] = $count;

				$output_item = $template;

/*				$keywords = array(
					'COUNT' => $count
					);
				$out = $this->get_block_template( $content, $keywords );
*/
				// First post found?
				if ($count == 1) {
					// search content for [if first]
					$start = '[if first]'; $end = '[/if]';
					$middle = self::getBetween($start, $end, $output_item);
					if ($middle) $output_item = str_replace($start.$middle.$end, $middle, $output_item);
				}

				// Last post found?
				if ($count == $max) {
					// search content for [if last]
					$start = '[if last]'; $end = '[/if]';
					$middle = self::getBetween($start, $end, $output_item);
					if($middle) $output_item = str_replace($start.$middle.$end, $middle, $output_item);
				}

				if ($clean == 'true') {
					$output_items[] = do_shortcode( custom_clean_shortcodes( $output_item ));
				} else {
					$output_items[] = do_shortcode( $output_item );
				}
				$x--;
			}

			// End hook

			$ccs_global_variable['is_loop'] = "false";

			if (!empty($blog)) {
				restore_current_blog();;
			}

			return apply_filters( 'ccs_loop_final', $output_items );
		}




		/*========================================================================
		 *
		 * If inside a [for each] loop, get that taxonomy term
		 *
		 *=======================================================================*/

		if (isset($ccs_global_variable['for_loop']) &&
			($ccs_global_variable['for_loop']=='true')) {

			if ($ccs_global_variable['for_each']['type']=='taxonomy') {

				$taxonomy = $ccs_global_variable['for_each']['taxonomy'];
				$value = $ccs_global_variable['for_each']['slug'];

			}
		}





		/*========================================================================
		 *
		 * Category or tag
		 *
		 *=======================================================================*/


		if( $category != '' ) {
			$query['category_name'] = $category;
		}


		if( $tag != '' ) {

			// Remove extra space in a list
			$tags = $this->clean_list($tag);

			$query['tag'] = $tags;

		}


		/*========================================================================
		 *
		 * Post count
		 *
		 *=======================================================================*/

		if ( $count != '' ) {

			if ($orderby=='rand') {
				$query['posts_per_page'] = '-1';
			} else {
				$query['posts_per_page'] = $count;
				$query['ignore_sticky_posts'] = true;
			}

		} else {

			if ($post_offset!='')
				$query['posts_per_page'] = '9999'; // Show all posts (to make offset work)
			else
				$query['posts_per_page'] = '-1'; // Show all posts (normal method)

		}

		if($post_offset!='')
			$query['offset'] = $post_offset;




		/*========================================================================
		 *
		 * Post type
		 *
		 *=======================================================================*/

		if ( empty($type) ) {

			$query['post_type'] = 'any';

		} else {

			$query['post_type'] = $type;
		}


		/*========================================================================
		 *
		 * Post ID
		 *
		 *=======================================================================*/

		if ( $id != '' ) {

			// Multiple IDs possible

			$id_array = $this->explode_list($id);

			$query['post__in'] = $id_array;
			$query['orderby'] = 'post__in'; // Preserve ID order

		} elseif ( $exclude != '' ) {

			$id_array = $this->explode_list($exclude);

			$query['post__not_in'] = $id_array;

		} elseif ( $name != '') {


			/*========================================================================
			 *
			 * Post name/slug
			 *
			 *=======================================================================*/

			$query['name'] = $name; 

		} elseif ( $parent != '') {

			/*========================================================================
			 *
			 * Parent by ID or slug
			 *
			 *=======================================================================*/

			if ( is_numeric($parent) )

				$parent_id = intval( $parent );

			else {

				// Get parent by slug
				// To do: get only 1 post by that name

				$posts = get_posts( array('name' => $parent, 'post_type' => $type, 'posts_per_page' => 1,) );

				if ( $posts ) $parent_id = $posts[0]->ID;
				else {
					// End action

					$ccs_global_variable['is_loop']='false';

					return;
				}
			}

			$query['post_parent'] = $parent_id;

		} elseif ( $field == 'gallery' ){

			/*========================================================================
			 *
			 * Gallery ID or name
			 *
			 *=======================================================================*/

			$gallery = 'true';

			if (!empty($name)) {

				$query['name']=$name;
				$ccs_global_variable['current_gallery_name'] = $name;

			} else {
				$ccs_global_variable['current_gallery_id'] = get_the_ID();
				$query['p'] = $ccs_global_variable['current_gallery_id'];
				$query['post_type'] = "any";

			}

		}

		/*========================================================================
		 *
		 * Query: date
		 *
		 *=======================================================================*/

		if ( ($year!='') || ($month!='') || ($day!='') ) {

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
		 * Query: taxonomy
		 *
		 *=======================================================================*/

		if ( !empty($tax) ) $taxonomy = $tax; // Alias - To do: move to start

		if ( !empty($taxonomy) ) {

			$terms = $this->explode_list($value);

			if ( !empty($compare) ) {

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

			} else
				$operator = 'IN'; // Default

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
		 * Orderby
		 *
		 *=======================================================================*/

		if ( !empty($order) ) {
			
			$query['order'] = $order;

		}

		if ( !empty($orderby) ) {

				// Alias
				if ($orderby=="field") $orderby = 'meta_value';
				if ($orderby=="field_num") $orderby = 'meta_value_num';

				$query['orderby'] = $orderby;

				if (in_array($orderby, array('meta_value', 'meta_value_num') )) {
					$query['meta_key'] = $keyname;
				}

				if (empty($order)) {

					// Default order

					if (($orderby=='meta_value_num') || ($orderby=='menu_order')
						|| ($orderby=='title') || ($orderby=='name') )
						$query['order'] = 'ASC';	
					else
						$query['order'] = 'DESC';
				}				
		}


		/*========================================================================
		 *
		 * Get posts in a series of field values
		 *
		 *=======================================================================*/

		if (!empty($series)) {

			// Remove white space
			$series = str_replace(' ', '', $series);

			// Expand range: 1-3 -> 1,2,3

			/* PHP 5.3+
				$series = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
				    return implode(',', range($m[1], $m[2]));
				}, $series);
			*/

			/* Compatible with older versions of PHP */

			$callback = create_function('$m', 'return implode(\',\', range($m[1], $m[2]));');
			$series = preg_replace_callback('/(\d+)-(\d+)/', $callback, $series);

			// Store posts ID and key

			self::$sort_posts = $this->explode_list($series);
			self::$sort_key = $keyname;

			// Get the posts to be sorted later

			$query['meta_query'] = array(
				array(
					'key' => $keyname,
					'value' => self::$sort_posts,
					'compare' => 'IN'
				)
			);
		}


		/*========================================================================
		 *
		 * Query by field value
		 *
		 *=======================================================================*/

		if( !empty($field) && !empty($value) ) {

			// Support for date values

			if ($value=="future") {
				$value = "now";
				$compare = ">";
			} elseif ($value=="past") {
				$value = "now";
				$compare = "<";
			}

			if (( isset($in) && ($in == "string") ) || (!empty($date_format)) ){
				if (empty($date_format)) {
					if ($value == "today")
						$date_format = "Y-m-d"; // Y-m-d h:i A
					if ($value == "now")
						$date_format = "Y-m-d h:i A"; 
				}

				if (($value == "today") || ($value == "now")){
					$value = date($date_format,time());
				}
			} else {
				if (($value == "today") || (($value == "now"))){
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

			if ( !empty($field_2) && !empty($value_2) ) {

				if ($relation!='') {

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

				$query['meta_query'][] =
					array(
						'key' => $field_2,
						'value' => $value_2,
						'compare' => $compare_2
				);
			}
		}



/*====================================================================================
 *
 * Main loop
 * 
 *====================================================================================*/

		// To do: do this more elegantly

		if ( ( $gallery!="true" ) && ( $type != "attachment") ) {

			// Last-minute adjustment?

			if ( $field == "gallery" )
				$field = "_custom_gallery";


			/*========================================================================
			 *
			 * Do the query
			 *
			 *=======================================================================*/


			$query = apply_filters( 'ccs_loop_before_query', $query );

			$posts = new WP_Query( $query );


	/*========================================================================
	 *
	 * Sort results
	 *
	 *=======================================================================*/

			/*========================================================================
			 *
			 * Sort posts by series
			 *
			 *=======================================================================*/
			
			if (!empty($series)) {

				usort($posts->posts, array($this, "custom_series_orderby_key"));

			}

			/*========================================================================
			 *
			 * Randomize order
			 *
			 *=======================================================================*/

			if($orderby=='rand') {

				shuffle($posts->posts);
				if ($count == '')
					$count = 9999; // ??

			}



	/*========================================================================
	 *
	 * Start
	 *
	 *=======================================================================*/

		/*========================================================================
		 *
		 * If any post found
		 *
		 *=======================================================================*/

			if( $posts->have_posts() ) {

				// Action: posts_init

				do_action( 'ccs_loop_all_posts_init' );

				$total_comment_count = 0;
				$current_count = 1;


				// Set up post data

				while ( $posts->have_posts() ) : $posts->the_post();


					$output_item = $template;


					// Action: each_post_init

					/*========================================================================
					 *
					 * Store current post info
					 *
					 *=======================================================================*/

					do_action( 'ccs_loop_start_each_post' );

					$current_id = get_the_ID();
					$ccs_global_variable['current_loop_id']=$current_id;
					$ccs_global_variable['current_loop_count']=$current_count;

					$ccs_global_variable['total_comments']+=get_comments_number();


				// Filter: each_post

				/*========================================================================
				 *
				 * Filter posts by checkbox query
				 *
				 *=======================================================================*/

				$skip_1 = false;

				if ($checkbox!='') {

					$values = $this->explode_list($value);
					$check_field = get_post_meta( $current_id, $checkbox, $single=true );

					if (empty($compare)) $compare="or";
					elseif (empty($checkbox_2)) $compare = strtolower($compare);
					else $compare="or";

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
				if ($checkbox_2!='') {
					$values = $this->explode_list($value_2);
					$check_field = get_post_meta( $current_id, $checkbox_2, $single=true );

					if(! empty($compare_2)) $compare_2 = strtolower($compare_2);
					else $compare_2 = "or";

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

				if (!empty($checkbox_2)) {
					$relation = strtoupper($relation);
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


			if ( !$skip ) {

				/*========================================================================
				 *
				 * ACF repeater field
				 *
				 *=======================================================================*/

				if ( !empty($repeater) ) {

					$ccs_global_variable['is_repeater_loop'] = "true";
					$ccs_global_variable['current_loop_id'] = $current_id;

					if( function_exists('get_field') ) {

						if( get_field($repeater, $ccs_global_variable['current_loop_id']) ) { // If the field exists

							$count=1;

							while( has_sub_field($repeater) ) : // For each row

							// Pass details onto content shortcode

							$keywords = apply_filters( 'query_shortcode_keywords', array(
								'ROW' => $count,
							) );
							$ccs_global_variable['current_row'] = $count;
							$output_items[] = do_shortcode($this->get_block_template( $template, $keywords ));
							$count++;
							endwhile;
						}
					}

					$ccs_global_variable['is_repeater_loop'] = "false";


					// $output_items


				} else {

				/*========================================================================
				 *
				 * ACF gallery field
				 *
				 *=======================================================================*/

				if ( !empty($acf_gallery) ) {

					$ccs_global_variable['is_acf_gallery_loop'] = "true";
					$ccs_global_variable['current_loop_id'] = $current_id;

					if( function_exists('get_field') ) {

						$images = get_field($acf_gallery, $current_id);
						if( $images ) { // If images exist

							$count=1;

							$ccs_global_variable['current_image_ids'] = implode(',', get_field($acf_gallery, $current_id, false));
/*
							if ($shortcode_name == 'pass') {

								// Pass details onto content shortcode

								$keywords = apply_filters( 'query_shortcode_keywords', array(
									'FIELD' => $ccs_global_variable['current_image_ids'],
								) );

								$output_items[] = do_shortcode($this->get_block_template( $template, $keywords ));
								
							} else { // For each image
*/
								foreach( $images as $image ) {

									$ccs_global_variable['current_row'] = $count;

									$ccs_global_variable['current_image']['full'] = '<img src="' . $image['sizes']['full'] . '">';
									$image_sizes = get_intermediate_image_sizes();

									foreach ($image_sizes as $image_size) {
										$ccs_global_variable['current_image'][$image_size] = '<img src="' . $image['sizes'][$image_size] . '">';
									}

									$ccs_global_variable['current_image_id'] = $image['id'];
									$ccs_global_variable['current_attachment_id'] = $image['id'];
									$ccs_global_variable['current_attachment_link'] = get_attachment_link($image['id']);
									$ccs_global_variable['current_image_url'] = $image['url'];
									$ccs_global_variable['current_image_title'] = $image['title'];
									$ccs_global_variable['current_image_caption'] = $image['caption'];
									$ccs_global_variable['current_image_description'] = $image['description'];
									$ccs_global_variable['current_image_thumb'] = '<img src="' . $image['sizes']['thumbnail'] . '">';
									$ccs_global_variable['current_image_thumb_url'] = $image['sizes']['thumbnail'];
									$ccs_global_variable['current_image_alt'] = $image['alt'];

									$output_items[] = do_shortcode($template);
									$count++;

								} // End for each image
//							} 
						}
					}

					$ccs_global_variable['is_acf_gallery_loop'] = "false";


					// Here we already have $output_items..

				// End ACF gallery field

				} else {


					/*========================================================================
					 *
					 * Attachments..?
					 *
					 *=======================================================================*/
/*
					if ($field == "attachment") {
						$attachments =& get_children( array(
							'post_parent' => $current_id,
							'post_type' => 'attachment',
						) );
						if( empty($attachments) ) {
							$field_content = null; $attachment_ids = null;
						} else {
							$attachment_ids = '';
							foreach( $attachments as $attachment_id => $attachment) {
								$attachment_ids .= $attachment_id . ",";
							}
							$attachment_ids = trim($attachment_ids, ",");
							$field_content = $attachment_ids;
						}

					} else { */

					/*========================================================================
					 *
					 * Normal custom field
					 *
					 *=======================================================================*/
/*
						$field_content = get_post_meta( $current_id, $field, true );
						$attachment_ids = get_post_meta( $current_id, '_custom_gallery', true );
					}
*/




	/*========================================================================
	 *
	 * Filter each loop item
	 *
	 *=======================================================================*/
	

			$total_comment_count += get_comments_number(); // This is stored globally..



					/*========================================================================
					 *
					 * Strip tags
					 *
					 *=======================================================================*/
					
					if ($strip_tags!='') {

						if ($strip_tags=='true') {
							$out = wp_kses($out, array());
//							$output[] = strip_tags(html_entity_decode($out));
						} else {

							// This seems to work the best for allowing certain tags

							$out = strip_tags(html_entity_decode($out), $strip_tags);

//							$out = wp_kses($out, $strip_tags);
//							$output[] = strip_tags(html_entity_decode($out), $strip_tags);
//							print_r(wp_kses($this->get_block_template( $template, $keywords ), $strip_tags));
						}
					} 


					/*========================================================================
					 *
					 * First post found
					 *
					 *=======================================================================*/

					if ($current_count == 1) {

						// search content for [if last]

						$start = '[if first]';
						$end = '[/if]';

						$middle = self::getBetween($start, $end, $out);
						if ($middle) {
							$out = str_replace($start.$middle.$end, $middle, $out);
						}

					}


					/*========================================================================
					 *
					 * Last post found
					 *
					 *=======================================================================*/

					if ($current_count == $posts->post_count) {

						// search content for [if last]

						$start = '[if last]';
						$end = '[/if]';

						$middle = self::getBetween($start, $end, $out);
						if($middle) {
							$out = str_replace($start.$middle.$end, $middle, $out);
						}

					}



					/*========================================================================
					 *
					 * Clean
					 *
					 *=======================================================================*/

					if ($clean == 'true')
						$out = custom_clean_shortcodes( $out );

					if ( has_shortcode( $out, 'pass' ) ) {

						// If there's a [pass] shortcode, do it now

						$out = do_shortcode($out);
						$do_shortcode_later = false;

					} else {

						// or else do it later, after {FIELD} tags are replaced

						$do_shortcode_later = true;
					}


					/*========================================================================
					 *
					 * Process field tags: {FIELD}
					 *
					 *=======================================================================*/

					// Optimize this

					// If the loop contains {...}

					if ( (strpos($out, '{')!==0) && (strpos($out, '}')!==0)  ) {

						// Expand fields=".., .."

						$extra_keywords = $this->get_field_keywords($fields);
//						$out = $this->get_block_template( $out, $keywords ); // Process {KEYWORDS}

// Optimize this..

						$keywords = apply_filters( 'query_shortcode_keywords', array_merge( array(
							'QUERY' => serialize($query), // DEBUG purpose
							'COUNT' => $current_count,
							'URL' => get_permalink(),
							'ID' => $current_id,
							'TITLE' => get_the_title(),
							'AUTHOR' => get_the_author(),
							'AUTHOR_URL' => get_author_posts_url( get_the_author_meta( 'ID' ) ),
							'DATE' => get_the_date(),
							'THUMBNAIL' => get_the_post_thumbnail( null, $thumbnail_size ),
							'THUMBNAIL_URL' => wp_get_attachment_url(get_post_thumbnail_id($current_id)),
							'CONTENT' => ( $content_limit ) ? wp_trim_words( get_the_content(), $content_limit ) : get_the_content(),
							'EXCERPT' => get_the_excerpt(),
							'COMMENT_COUNT' => get_comments_number(),
							'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
							'IMAGE' => get_the_post_thumbnail(),
							'IMAGE_ID' => get_post_thumbnail_id($current_id),
							'IMAGE_URL' => wp_get_attachment_url(get_post_thumbnail_id($current_id)),
							'FIELD' => $field_content,
							'VAR' => $variable,
							'VARIABLE' => $variable,
							'IDS' => $attachment_ids,
						), $extra_keywords) );

						$out = $this->get_block_template( $out, $keywords ); // Process {KEYWORDS}
					}

					if ($do_shortcode_later) {
						$out = do_shortcode($out);
					}



					$output[] = $out;




					} // End of not gallery field (just attachment or normal field)


				} // End of not repeater



			$current_count++;

			if ($orderby=='rand') {
				if ($current_count > $count) break;
			}

		} /* Not skip */


		endwhile; // End each post

		/*========================================================================
		 *
		 * End: loop through each post
		 *
		 *=======================================================================*/



			// Action: all_posts_after

			$nothing_found = false;

		} // End: if posts found

			else {

			/*========================================================================
			 *
			 * No post found
			 *
			 *=======================================================================*/

				$nothing_found = true;

				// search content for [if empty]

				$start = '[if empty]';
				$end = '[/if]';

				$middle = self::getBetween($start, $end, $content);
				if ($middle)
					$out = do_shortcode($middle); // then do it	
			}

			wp_reset_postdata();

			do_action( 'ccs_loop_after_all_posts' );

			if (!$nothing_found) {

				if (empty($if)) {

/*========================================================================
 *
 * Final output (for not attachment or gallery)
 *
 *=======================================================================*/

					/*========================================================================
					 *
					 * Put them in columns
					 *
					 *=======================================================================*/
					
					if (!empty($columns)) { // Create simple columns

						$col = 0;
						$percent = 100 / (int)$columns;
						$clear = '<div style="clear:both;"><br></div>';

						$out = null;

						foreach ($output as $each) {

							$trimmed = trim($each);

							if ( !empty( $trimmed ) ) {
								$col++;
								$out .= '<div class="column-1_of_'.$columns.'" style="width:'.$percent.'%;float:left;">';

								if (!empty($pad))
									$out .= '<div class="column-inner" style="padding:'.$pad.'">';

								$out .= $each;

								if (!empty($pad))
									$out .= '</div>';

								$out .= '</div>';
								if (($col%$columns)==0)
									$out .= $clear;
							}
						}
						if (($col%$columns)!=0) // Last row not filled
							$out .= $clear;

					} else {


						/*========================================================================
						 *
						 * Or echo
						 *
						 *=======================================================================*/

						$out = implode( '', $out );

						// Filter: trim final output

						if (!empty($trim)) {
							if ($trim=='true') $trim = null;
							$out = trim($out, " \t\n\r\0\x0B,".$trim);
						}


					}

				} else {

					// Move this to [if]

					if ( ($if=='all-no-comments') && ($total_comment_count==0) ) {
						$out = $output[0];
					}

				}

			}


			do_action( 'ccs_loop_before_return' );

			$ccs_global_variable['is_loop'] = "false";

			if (!empty($blog)) {
				restore_current_blog();
			}

			return apply_filters( 'ccs_loop_final', $out );

/*========================================================================
 *
 * End of normal post loop
 *
 *=======================================================================*/

		} else {


/*========================================================================
 *
 * Attachment loop: move to [attached] shortcode
 *
 *=======================================================================*/

			if ( $type == 'attachment' ) {

				$output = array();
				$attachment_ids = '';
				$current_id = get_the_ID();

				if ($category == '') {

					$posts = get_posts( array (
						'post_parent' => $current_id,
						'post_type' => 'attachment',
						'post_status' => $status,
						'posts_per_page' => '-1' // Get all attachments
						) );

					foreach( $posts as $post ) {
						$attachment_id = $post->ID;
						$attachment_ids .= $attachment_id . " ";
					}

/*					$posts =& get_children( array (
						'post_parent' => $current_id,
						'post_type' => 'attachment',
						'post_status' => $status
						) );

					foreach( $posts as $attachment_id => $attachment ) {
						$attachment_ids .= $attachment_id . " ";
					}
*/

				} else { // Fetch posts by category, then attachments

					$cat = get_category_by_slug($category);

					if (isset($cat->term_id)) {
						$my_query = new WP_Query( array(
							'cat' => $cat->term_id, 
							'post_type' => $status,
						));

						$posts = array('');

						while ( $my_query->have_posts() ) {

							$my_query->the_post();

							$current_id = get_the_ID();

							$posts = get_posts( array (
								'post_parent' => $current_id,
								'post_type' => 'attachment',
								'post_status' => $status,
								'posts_per_page' => '-1' // Get all attachments

								) );

							foreach( $posts as $post ) {
								$attachment_id = $post->ID;
								$attachment_ids .= $attachment_id . " ";
							}

/*							$new_children = get_children( array (
								'post_parent' => get_the_ID(),
								'post_type' => 'attachment',
								'post_status' => $status
							) );

							foreach( $new_children as $attachment_id => $attachment ) {
								$attachment_ids .= $attachment_id . " ";
							}
*/
						}
					}

				} // End fetch attachments by category


				if ((!empty($posts)) && ($attachment_ids)) { 

					$attachment_ids = explode(" ", trim( $attachment_ids ) );
					$ccs_global_variable['is_attachment_loop'] = "true";

					foreach ( $attachment_ids as $attachment_id ) {
					// get original image

						$ccs_global_variable['current_attachment_id'] = $attachment_id;

						$image_link	= wp_get_attachment_image_src( $attachment_id, "full" );
						$image_link	= $image_link[0];	
										
						$ccs_global_variable['current_image']['full'] = wp_get_attachment_image( $attachment_id, "full" );

						$image_sizes = get_intermediate_image_sizes();

						foreach ($image_sizes as $image_size) {
							$ccs_global_variable['current_image'][$image_size] = wp_get_attachment_image( $attachment_id, $image_size );
						}

// Optimize this..

						$ccs_global_variable['current_image_url'] = $image_link;
						$ccs_global_variable['current_attachment_link'] = get_attachment_link($attachment_id);
						$ccs_global_variable['current_image_thumb'] = wp_get_attachment_image( $attachment_id, 'thumbnail', '', array( 'alt' => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) ) );
						$ccs_global_variable['current_image_thumb_url'] = wp_get_attachment_thumb_url( $attachment_id, 'thumbnail' ) ;
						$ccs_global_variable['current_image_caption'] = get_post( $attachment_id )->post_excerpt ? get_post( $attachment_id )->post_excerpt : '';
						$ccs_global_variable['current_image_title'] = get_post( $attachment_id )->post_title;
						$ccs_global_variable['current_image_description'] = get_post( $attachment_id )->post_content;
						$ccs_global_variable['current_image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

						$ccs_global_variable['current_image_ids'] = implode(" ", $attachment_ids);
						$ccs_global_variable['current_attachment_ids'] = $ccs_global_variable['current_image_ids'];
/*
			$keywords = apply_filters( 'query_shortcode_keywords', array(
				'URL' => get_permalink( $attachment_id ),
				'ID' => $attachment_id,
				'TITLE' => get_post( $attachment_id )->post_title,
				'CONTENT' => get_post( $attachment_id )->post_content,
				'CAPTION' => get_post( $attachment_id )->post_excerpt,
				'DESCRIPTION' => get_post( $attachment_id )->post_content,
				'IMAGE' => $ccs_global_variable['current_image'],
				'IMAGE_URL' => $ccs_global_variable['current_image_url'],
				'ALT' => $ccs_global_variable['current_image_alt'],
				'THUMBNAIL' => $ccs_global_variable['current_image_thumb'],
				'THUMBNAIL_URL' => $ccs_global_variable['current_image_thumb_url'],
				'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
				'FIELD' => get_post_meta( $current_id, $field, $single=true ),
				'IDS' => get_post_meta( $current_id, '_custom_gallery', true ),
			) );

						$output[] = do_shortcode( $this->get_block_template( $template, $keywords ) );
*/
						$out[] = do_shortcode( $template );

					} /** End for each attachment **/

				} // End: not empty post and attachments exist
				else $out = null;


				// End loop action

				wp_reset_postdata();

				$ccs_global_variable['is_attachment_loop'] = 'false';
				$ccs_global_variable['is_loop'] = 'false';

				if (!empty($blog)) {
					restore_current_blog();;
				}

				return implode('', $out );

			} // End type="attachment"

			else {


/*========================================================================
 *
 * Gallery Loop: move somewhere else
 *
 *=======================================================================*/

//				if ( function_exists('custom_gallery_get_image_ids') ) {

					$out = null;

					if($ccs_global_variable['current_gallery_id'] == '') {
						$ccs_global_variable['current_gallery_id'] = $current_id;
					}

					$attachment_ids = CCS_Gallery_Field::get_image_ids();

					if ( $attachment_ids ) { 

/*
						$image = wp_get_attachment_image_src( get_post_thumbnail_id( $ccs_global_variable['current_gallery_id'] ), 'feature' );
						$image_title = esc_attr( get_the_title( get_post_thumbnail_id( $ccs_global_variable['current_gallery_id'] ) ) );
*/
						$ccs_global_variable['is_gallery_loop'] = 'true';

						foreach ( $attachment_ids as $attachment_id ) {

							$ccs_global_variable['current_attachment_id'] = $attachment_id;

// Optimize this..

							// get original image

							$image_link	= wp_get_attachment_image_src( $attachment_id, 'full' );
							$image_link	= $image_link[0];	

							$ccs_global_variable['current_image']['full'] = wp_get_attachment_image( $attachment_id, "full" );

							$image_sizes = get_intermediate_image_sizes();

							foreach ($image_sizes as $image_size) {
								$ccs_global_variable['current_image'][$image_size] = wp_get_attachment_image( $attachment_id, $image_size );
							}

							$ccs_global_variable['current_image_url']=$image_link;
							$ccs_global_variable['current_attachment_link'] = get_attachment_link($attachment_id);
							$ccs_global_variable['current_image_thumb']=wp_get_attachment_image( $attachment_id, apply_filters( 'thumbnail_image_size', 'thumbnail' ), '', array( 'alt' => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) ) );
							$ccs_global_variable['current_image_thumb_url']= wp_get_attachment_thumb_url( $attachment_id ) ;
							$ccs_global_variable['current_image_caption']=get_post( $attachment_id )->post_excerpt ? get_post( $attachment_id )->post_excerpt : '';
							$ccs_global_variable['current_image_title'] = get_post( $attachment_id )->post_title;
							$ccs_global_variable['current_image_description'] = get_post( $attachment_id )->post_content;
							$ccs_global_variable['current_image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

							$ccs_global_variable['current_image_ids'] = implode(" ", $attachment_ids);
							$ccs_global_variable['current_attachment_ids'] = $ccs_global_variable['current_image_ids'];
/*
					$keywords = apply_filters( 'query_shortcode_keywords', array(
						'URL' => get_permalink( $attachment_id ),
						'ID' => $attachment_id,
						'TITLE' => get_post( $attachment_id )->post_title,
						'CONTENT' => get_post( $attachment_id )->post_content,
						'CAPTION' => get_post( $attachment_id )->post_excerpt,
						'DESCRIPTION' => get_post( $attachment_id )->post_content,
						'IMAGE' => $ccs_global_variable['current_image'],
						'IMAGE_URL' => $ccs_global_variable['current_image_url'],
						'ALT' => $ccs_global_variable['current_image_alt'],
						'THUMBNAIL' => $ccs_global_variable['current_image_thumb'],
						'THUMBNAIL_URL' => $ccs_global_variable['current_image_thumb_url'],
						'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
						'IMAGE' => get_the_post_thumbnail(),
						'IMAGE_URL' => wp_get_attachment_url(get_post_thumbnail_id($current_id)),

						'FIELD' => get_post_meta( $current_id, $field, $single=true ),
						'IDS' => get_post_meta( $current_id, '_custom_gallery', true ),
					) );
						
							$out[] = do_shortcode( $this->get_block_template( $template, $keywords ) );
*/

							$out[] = do_shortcode( $template );
						} /** End for each attachment **/

						$out = implode('', $out );

			    	} // End if attachment IDs exist

			    // Loop end action

				wp_reset_postdata();

				$ccs_global_variable['is_gallery_loop'] = 'false';
				$ccs_global_variable['current_gallery_id'] = '';
				$ccs_global_variable['is_loop'] = "false";

				if (!empty($blog)) {
					restore_current_blog();;
				}

				return $out;

			} /* End of gallery loop */

		} /* End: attachment or gallery field */


	} /* End of function the_loop_shortcode */ 






	function loop_final_filter( $out ) {

		if (is_array($out)) $out = implode('', $out);

		if (self::$cache == 'true') {
			CCS_Cache::set_transient( self::$cache_name, $out, self::$cache_expire );
		}

		return $out;
	}












	function debug_query_shortcode() {

		$query = self::$query;

		ob_start();
		print_r($query);
		return ob_get_clean();
	}


/*========================================================================
 *
 * Class helper functions
 *
 *=======================================================================*/

	/*
	 * Replaces {VAR} with $parameters['var'];
	 */

	public static function get_block_template( $string, $parameters ) {
		$searches = $replacements = null;


		// replace {KEYWORDS} with variable values
		foreach( $parameters as $find => $replace ) {
			$search = '{'.$find.'}';

			if( ! is_array($replace) ) {
				$string = str_replace( $search, $replace, $string );
			}
		}

		return $string;
	}

	public static function get_default_field_keywords() {

		// Optimize this..

		$keywords = array(
			'URL' => get_permalink(),
			'ID' => get_the_ID(),
			'TITLE' => get_the_title(),
			'AUTHOR' => get_the_author(),
			'AUTHOR_URL' => get_author_posts_url( get_the_author_meta( 'ID' ) ),
			'DATE' => get_the_date(),
			'THUMBNAIL' => get_the_post_thumbnail( null, 'thumbnail' ),
			'THUMBNAIL_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),
			'CONTENT' => get_the_content(),
			'EXCERPT' => get_the_excerpt(),
			'COMMENT_COUNT' => get_comments_number(),
			'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
			'IMAGE' => get_the_post_thumbnail(),
			'IMAGE_ID' => get_post_thumbnail_id(get_the_ID()),
			'IMAGE_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),
		);

		return $keywords;
	}


	public static function get_field_keywords( $fields ) {

		/* prepare custom fields to expand */

		$keywords = array();

		if (!empty($fields)) {
			$ks = self::explode_list($fields); // parse CSV to field keys
			$pm = get_post_meta(get_the_ID());
			$keywords =
				array_map(
					array(__CLASS__,'array_flat'), // Flatten the values ( Array > Value )
					array_change_key_case( // upper-case the keys for expansion later
						array_intersect_key( // include only specified fields' keys
							array_merge(
								array_fill_keys($ks, ''), // default value for non-existent fields
								$pm),
							array_change_key_case( // assume all field names are lower case
								array_flip($ks),
								CASE_LOWER)),
						CASE_UPPER));
		}
		return $keywords;
	}

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

	// Explode comma-separated list and remove extra space

	public static function explode_list( $list ) {
		return array_map("trim", array_filter(explode(',', $list)));
	}

	public static function clean_list( $list ) {
		$list = self::explode_list($list);
		return implode(',',$list);
	}

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


	/*============================================================================
	 *
	 * Sort series helper function
	 *
	 *===========================================================================*/

	public static function custom_series_orderby_key( $a, $b ) {

		$apos = array_search( get_post_meta( $a->ID, self::$sort_key, $single=true ), self::$sort_posts );
		$bpos = array_search( get_post_meta( $b->ID, self::$sort_key, $single=true ), self::$sort_posts );

		return ( $apos < $bpos ) ? -1 : 1;
	}



	/*============================================================================
	 *
	 * Pass shortcode
	 *
	 *===========================================================================*/

	public static function pass_shortcode( $atts, $content ) {

		$args = array(
			'field' => '',
			'fields' => '',
			'field_loop' => '', // Field is array or comma-separated list
			);

		extract( shortcode_atts( $args , $atts, true ) );


		if ( !empty($fields) ) {

			$keywords = self::get_field_keywords( $fields );

			$default_keywords = self::get_default_field_keywords();
			$content = self::get_block_template( $content, array_merge($keywords, $default_keywords) );
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

			$keywords = array(
				'FIELD' => $field_value,
				);

			if ( empty($fields) ) {
				$default_keywords = self::get_default_field_keywords();
				$keywords = array_merge($keywords, $default_keywords);
			}
			$content = self::get_block_template( $content, $keywords );

		} elseif (!empty($field_loop)) {

			$post_id = get_the_ID();
			$field_values = get_post_meta( $post_id, $field_loop, true );

			if (!empty($field_values)) {

				if (!is_array($field_values))
					$field_values = self::explode_list($field_values);

				if ( empty($fields) ) {
					$default_keywords = self::get_default_field_keywords();
				}
				$contents = null;

				foreach ($field_values as $field_value) {

					$keywords = array(
						'FIELD' => $field_value,
					);

					if ( empty($fields) ) {
						$keywords = array_merge($default_keywords,$keywords);
					}

					$contents[] = self::get_block_template( $content, $keywords );
				}

				$content = implode("", $contents);
			}
		}

		return do_shortcode( $content );
	}


	function loop_count_shortcode() {

		global $ccs_global_variable;

//		if ($ccs_global_variable['is_loop']=="true") {
			return $ccs_global_variable['current_loop_count'];
//		} else {
//			return false;
//		}

	}

	public static function array_flat($v){ return is_array($v) ? $v[0] : $v; }


}





/*========================================================================
 *
 * Clean up shortcodes
 *
 * To do: put these somewhere organized
 *
 *=======================================================================*/


	function custom_clean_shortcodes($content){   

	    $content = custom_strip_tag_list( $content, array('p','br') );

	    return $content;
	}

	function custom_strip_tag_list( $content, $tags ) {

		$tags = implode("|", $tags);
		$out = preg_replace('!<\s*('.$tags.').*?>((.*?)</\1>)?!is', '\3', $content); 

		return $out;
	}

	function custom_cleaner_shortcode( $atts, $content ) {

		$content = custom_strip_tag_list( $content, array('p','br') );

		return do_shortcode($content);
	}
	add_shortcode('clean', 'custom_cleaner_shortcode');

	function custom_br_shortcode( $atts, $content ) {
		return '<br>';
	}
	add_shortcode('br', 'custom_br_shortcode');


	function custom_p_shortcode( $atts, $content ) {
		return '<p>' . $content . '</p>';
	}
	add_shortcode('p', 'custom_p_shortcode');


	function custom_format_shortcode( $atts, $content ) {
		return do_shortcode(wpautop($content));
	}
	add_shortcode('format', 'custom_format_shortcode');




	function custom_direct_shortcode( $atts, $content ) {
		return $content;
	}
	add_shortcode('direct', 'custom_direct_shortcode');

