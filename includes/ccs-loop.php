<?php

/*====================================================================================================
 *
 * [loop] - Query posts and loop through each item
 *
 *====================================================================================================*/

class LoopShortcode {

	private static $query;

	private static $sort_posts;
	private static $sort_key;

	function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	function register() {

		/*========================================================================
		 *
		 * Define shortcodes
		 *
		 *=======================================================================*/

		add_shortcode( 'loop', array( $this, 'the_loop_shortcode' ) );
		add_shortcode( 'pass', array( $this, 'pass_shortcode' ) );

		add_shortcode( 'cache', array( $this, 'cache_shortcode') );
//		add_shortcode( 'debug-query', array( $this, 'debug_query_shortcode' ) );
//		add_shortcode( 'loop-count', array( $this, 'loop_count_shortcode' ) );


		/*========================================================================
		 *
		 * Set up loop filters and actions
		 *
		 *=======================================================================*/



		add_filter( 'ccs_loop_query', array( $this, 'ccs_loop_query_filter') );



		/*========================================================================
		 *
		 * Set up WP filters
		 *
		 *=======================================================================*/
		
		


		// Get settings - To do: get it from CCS init

		$settings = get_option( 'ccs_content_settings' );



		// Move wpautop filter

		$move_wpautop = isset( $settings['move_wpautop'] ) ?
			esc_attr( $settings['move_wpautop'] ) : 'off';

		// Doesn't work with cache

		if ($move_wpautop == 'on') {
			remove_filter( 'the_content', 'wpautop' );
			add_filter( 'the_content', 'wpautop' , 99);
			add_filter( 'the_content', 'shortcode_unautop',100 );
		}



		// Enable shortcodes in widget

		$shortcodes_in_widget = isset( $settings['shortcodes_in_widget'] ) ?
			esc_attr( $settings['shortcodes_in_widget'] ) : 'off';

		if ($shortcodes_in_widget == "on") {
			add_filter('widget_text', 'do_shortcode');
		}

		// Exempt [loop] from wptexturize()

		add_filter( 'no_texturize_shortcodes', array( $this, 'shortcodes_to_exempt_from_wptexturize') );
	}

	function shortcodes_to_exempt_from_wptexturize($shortcodes){
		$shortcodes[] = 'loop';
		return $shortcodes;
	}



	/*========================================================================
	 *
	 * Main function
	 * 
	 *=======================================================================*/

	function the_loop_shortcode( $atts, $template = null, $shortcode_name ) {

		/*========================================================================
		 *
		 * Initialize
		 *
		 *=======================================================================*/

		global $ccs_global_variable;

		$ccs_global_variable['is_loop'] = "true";
		$ccs_global_variable['current_loop_count'] = 0;
		$ccs_global_variable['current_gallery_name'] = '';
		$ccs_global_variable['current_gallery_id'] = '';
		$ccs_global_variable['is_gallery_loop'] = "false";
		$ccs_global_variable['is_attachment_loop'] = "false";
		$ccs_global_variable['is_repeater_loop'] = "false";
		$ccs_global_variable['total_comments'] = 0;


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


			'cache' => '',
			'expire' => '',

			// ?
			'if' => '', 'list' => '', 'posts_separator' => '',
			'variable' => '', 'var' => '',
			'content_limit' => 0,
			'thumbnail_size' => 'thumbnail',
			'title' => '', 'post_offset' => '',
			'keyname' => '', 
		);

		$all_args = shortcode_atts( $args , $atts, true );
		extract( $all_args );


/*========================================================================
 *
 * Set up query based on parameters
 *
 *=======================================================================*/



		/*========================================================================
		 *
		 * Merge parameters into query
		 *
		 *=======================================================================*/
/*
		$query = array_merge( $atts, $all_args );

		// filter out non-wpquery arguments
		foreach( $args as $key => $value ) {
			unset( $query[$key] );
		}
*/		$query = array();

		// $this->set_up_query( $atts );


		// Switch to blog on multi-site, restore at the end
		// To do: test it

		if (!empty($blog)) {
			switch_to_blog($blog);
		}





		/*========================================================================
		 *
		 * Aliases - To do: clean these up
		 *
		 *=======================================================================*/


		if(!empty($key)) $keyname=$key;
		if(!empty($offset)) $post_offset=$offset;
		if(!empty($strip)) $strip_tags=$strip;
		if(!empty($allow)) $strip_tags=$allow;

		if (!empty($var)) $variable=$var;

		if($f!='')	$field = $f;
		if($v!='')	$value = $v;
		if($c!='')	$compare = $c;
		if($f2!='')	$field_2 = $f2;
		if($v!='')	$value_2 = $v2;
		if($c!='')	$compare_2 = $c2;
		if($r!='')	$relation = $r;

		if (!empty($custom_fields)) $fields = $custom_fields; // expand multiple fields

		// name, custom field, query_field, query_value



		/*========================================================================
		 *
		 * Post status
		 *
		 *=======================================================================*/

		if (!empty($status)) {

			$query['post_status'] = explode(",", $status); // To do: clean up extra spaces if any

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
			$output = array();

			ob_start();

			while($x > 0) {

				$count++;
				$ccs_global_variable['current_loop_count'] = $count;
				$keywords = array(
					'COUNT' => $count
					);
				$out = $this->get_block_template( $template, $keywords );

				// First post found?
				if ($count == 1) {
					// search content for [if first]
					$start = '[if first]'; $end = '[/if]';
					$middle = self::getBetween($start, $end, $out);
					if ($middle) $out = str_replace($start.$middle.$end, $middle, $out);
				}

				// Last post found?
				if ($count == $max) {
					// search content for [if last]
					$start = '[if last]'; $end = '[/if]';
					$middle = self::getBetween($start, $end, $out);
					if($middle) $out = str_replace($start.$middle.$end, $middle, $out);
				}

				if ($clean == 'true') {
					$output[] = do_shortcode(custom_clean_shortcodes( $out ));
				} else {
					$output[] = do_shortcode($out);
				}
				$x--;
			}

			echo implode("", $output);

			// End hook

			$ccs_global_variable['is_loop'] = "false";

			if (!empty($blog)) {
				restore_current_blog();;
			}

			return ob_get_clean();
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

 			// Clean up extra spaces
			$tags = array_map("trim", array_filter(explode(',', $tag)));
			$tags = implode(",", $tags);

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
			// To do: clean up extra space if any

			$id_array = explode(",", $id);

			$query['post__in'] = $id_array;
			$query['orderby'] = 'post__in'; // Preserve ID order

		} elseif ( $exclude != '' ) {

			$id_array = explode(",", $exclude);

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

				$posts = get_posts( array('name' => $parent, 'post_type' => $type, 'posts_per_page' => 5,) );

				if ( $posts ) $parent_id = $posts[0]->ID;
				else {
					// End action
					$ccs_global_variable['is_loop']='false';
					return;
				}
			}

			$query['post_parent'] = $parent_id;

		} elseif (( $field == 'gallery' ) && ($shortcode_name != 'pass') ){

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

				$query['p'] = get_the_ID();
				$query['post_type'] = "any";
			}

			// To do: Is this necessary..?

			$posts = get_posts( $query );

			if( $posts ) {
				$ccs_global_variable['current_gallery_id'] = $posts[0]->ID;
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

			// To do: clean up extra spaces if any

			$terms = explode(",", $value);

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

			self::$sort_posts = explode(',', $series);
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

			// Last-minute adjustments

			if ( $field == "gallery" )
				$field = "_custom_gallery";


			/*========================================================================
			 *
			 * Get query results
			 *
			 *=======================================================================*/


			$query = apply_filters( 'ccs_loop_query', $query );


			// Cache the query here

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

			ob_start();

	/*========================================================================
	 *
	 * If any post found
	 *
	 *=======================================================================*/

			if( $posts->have_posts() ) {

				// Action: posts_init

				do_action( 'ccs_loop_start_all_posts' );

				$total_comment_count = 0;
				$current_count = 1;


				// Set up post data

				while ( $posts->have_posts() ) : $posts->the_post();

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

					$values = explode(",", $value);
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
					$values = explode(",", $value_2);
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
				 * Repeater field
				 *
				 *=======================================================================*/

				if ($repeater != '') {

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
							$output[] = do_shortcode($this->get_block_template( $template, $keywords ));
							$count++;
							endwhile;
						}
					}

					$ccs_global_variable['is_repeater_loop'] = "false";

				} else {

				/*========================================================================
				 *
				 * ACF gallery field
				 *
				 *=======================================================================*/

				if ($acf_gallery != '') {

					$ccs_global_variable['is_acf_gallery_loop'] = "true";
					$ccs_global_variable['current_loop_id'] = $current_id;

					if( function_exists('get_field') ) {

						$images = get_field($acf_gallery, $current_id);
						if( $images ) { // If images exist

							$count=1;

							$ccs_global_variable['current_image_ids'] = implode(',', get_field($acf_gallery, $current_id, false));

							if ($shortcode_name == 'pass') {

								// Pass details onto content shortcode

								$keywords = apply_filters( 'query_shortcode_keywords', array(
									'FIELD' => $ccs_global_variable['current_image_ids'],
								) );
								$output[] = do_shortcode($this->get_block_template( $template, $keywords ));
								
							} else { // For each image

								foreach( $images as $image ) :

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

								$output[] = do_shortcode($template);
								$count++;
								endforeach;
							} // End for each image
						}
					}

					$ccs_global_variable['is_acf_gallery_loop'] = "false";

				// End ACF gallery field

				} else {


					/*========================================================================
					 *
					 * Attachments..?
					 *
					 *=======================================================================*/

					if($field == "attachment") {
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

					} else {

					/*========================================================================
					 *
					 * Normal custom field
					 *
					 *=======================================================================*/

						$field_content = get_post_meta( $current_id, $field, true );
						$attachment_ids = get_post_meta( $current_id, '_custom_gallery', true );
					}





	/*========================================================================
	 *
	 * Filter each loop item
	 *
	 *=======================================================================*/
	

			$out = $template;
			$total_comment_count += get_comments_number(); // This is stored globally..


			$out = apply_filters( 'ccs_loop_each_out', $out );


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

	// Optimize this!



		// If the loop contains {...}

		if ( (strpos($out, '{')!==0) && (strpos($out, '}')!==0)  ) {

					// Expand fields=".., .."

					$extra_keywords = $this->get_field_keywords($fields);

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


	/*========================================================================
	 *
	 * End: each post found
	 *
	 *=======================================================================*/

			endwhile; // End each post

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

				$middle = self::getBetween($start, $end, $template);
				if ($middle)
					echo do_shortcode($middle); // then do it	
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

						foreach ($output as $each) {

							$trimmed = trim($each);

							if ( !empty( $trimmed ) ) {
								$col++;
								echo '<div class="column-1_of_'.$columns.'" style="width:'.$percent.'%;float:left;">';

								if (!empty($pad))
									echo '<div class="column-inner" style="padding:'.$pad.'">';

								echo $each;

								if (!empty($pad))
									echo '</div>';

								echo '</div>';
								if (($col%$columns)==0)
									echo $clear;
							}
						}
						if (($col%$columns)!=0) // Last row not filled
							echo $clear;

					} else {


						/*========================================================================
						 *
						 * Or echo
						 *
						 *=======================================================================*/

						$output = implode( "", $output );

						// Filter: trim final output

						if (!empty($trim)) {
							if ($trim=='true') $trim = null;
							$output = trim($output, " \t\n\r\0\x0B,".$trim);
						}

						echo $output; // to buffer

					}

				} else {

					// Move this to [if]

					if ( ($if=='all-no-comments') && ($total_comment_count==0) ) {
						echo $output[0];
					}

				}

			}


do_action( 'ccs_loop_before_return' );

			$ccs_global_variable['is_loop'] = "false";

			if (!empty($blog)) {
				restore_current_blog();
			}

			return ob_get_clean();

		} else {

















/*========================================================================
 *
 * Attachment loop: move to [attached] shortcode
 *
 *=======================================================================*/

			if ( $type == 'attachment' ) {

				$output = array();
				$attachment_ids = "";
				ob_start();

				$current_id = get_the_ID();

				if($category == '') {

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
						$output[] = do_shortcode( $template );

					} /** End for each attachment **/

				} // End: not empty post and attachments exist
				else $output = null;

				$ccs_global_variable['is_attachment_loop'] = "false";
				// wp_reset_query(); not necessary
				wp_reset_postdata();

				echo implode("", $output );
				$ccs_global_variable['is_loop'] = "false";
				if (!empty($blog)) {
					restore_current_blog();;
				}

				return ob_get_clean();

			} // End type="attachment"

			else {




/*========================================================================
 *
 * Gallery Loop: move somewhere else
 *
 *=======================================================================*/

				if( function_exists('custom_gallery_get_image_ids') ) {

					$output = array();

					if($ccs_global_variable['current_gallery_id'] == '') {
						$ccs_global_variable['current_gallery_id'] = $current_id;
					}

//				$posts = new WP_Query( $query );

					$current_id = get_the_ID();

					$attachment_ids = custom_gallery_get_image_ids();

					if ( $attachment_ids ) { 

						ob_start();

						$has_gallery_images = get_post_meta( $ccs_global_variable['current_gallery_id'], '_custom_gallery', true );
						if ( !$has_gallery_images ) {
							$ccs_global_variable['is_loop'] = "false";
							if (!empty($blog)) {
								restore_current_blog();;
							}

							return;
						}
						// convert string into array
						$has_gallery_images = explode( ',', get_post_meta( $ccs_global_variable['current_gallery_id'], '_custom_gallery', true ) );

						// clean the array (remove empty values)
						$has_gallery_images = array_filter( $has_gallery_images );

						$image = wp_get_attachment_image_src( get_post_thumbnail_id( $ccs_global_variable['current_gallery_id'] ), 'feature' );
						$image_title = esc_attr( get_the_title( get_post_thumbnail_id( $ccs_global_variable['current_gallery_id'] ) ) );

						$ccs_global_variable['is_gallery_loop'] = "true";

						foreach ( $attachment_ids as $attachment_id ) {

							$ccs_global_variable['current_attachment_id'] = $attachment_id;

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
						
							$output[] = do_shortcode( $this->get_block_template( $template, $keywords ) );
						} /** End for each attachment **/

						$ccs_global_variable['is_gallery_loop'] = "false";
						// wp_reset_query(); not necessary
						wp_reset_postdata();

						echo implode( "", $output );
						$ccs_global_variable['is_loop'] = "false";
						if (!empty($blog)) {
							restore_current_blog();;
						}

						return ob_get_clean();
			    	} // End if attachment IDs exist

				} // End if function exists 

				$ccs_global_variable['current_gallery_id'] = '';
				$ccs_global_variable['is_loop'] = "false";
				if (!empty($blog)) {
					restore_current_blog();;
				}

				return;

			} /* End of gallery loop */

		} /* End: attachment or gallery field */


	} /* End of function the_loop_shortcode */ 




	/*========================================================================
	 *
	 * Actions and filters
	 *
	 *=======================================================================*/

	function ccs_loop_query_filter( $query ) {
		self::$query = $query;
//		echo '<script>console.log("'.json_encode($query).'");</script>';
		return $query;
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
			$ks = array_map("trim", array_filter(explode(',', $fields))); // parse CSV to field keys
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
				$field_value = array_map("trim", array_filter(explode(',', $field_value)));
				$field_value = implode(",", $field_value);
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
					$field_values = array_map("trim", array_filter(explode(',', $field_values))); // Clean up extra spaces

				if ( empty($fields) )
					$default_keywords = self::get_default_field_keywords();

				$contents = null;

				foreach ($field_values as $field_value) {

					$keywords = array(
						'FIELD' => $field_value,
					);

					if ( empty($fields) ) {
						$keywords = array_merge($default_keywords, $keywords);
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

		if ($ccs_global_variable['is_loop']=="true") {
			return $ccs_global_variable['current_loop_count'];
		} else {
			return false;
		}

	}

	public static function array_flat($v){ return is_array($v) ? $v[0] : $v; }




	/*========================================================================
	 *
	 * Cache
	 *
	 *=======================================================================*/


	function cache_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'name' => '',
			'expire' => ''
		), $atts ) );

		if (empty($name)) return;

		$prefix = 'ccs_';
		$cache = $prefix.$name;

		$result = get_transient( $cache );

		if ( false === $result ) {

			$result = do_shortcode( $content );

			// Translate min, hour, day, year
			if ( empty($expire) ) $expire = 60; // Default

			set_transient( $cache, $result, $expire );
			return $result;
		} else {
			return do_shortcode('[direct]'.$result.'[/direct]');
		}

	}

}

new LoopShortcode;


	
	/*========================================================================
	 *
	 * [raw] - make it optional
	 *
	 *=======================================================================*/

	function raw_formatter($content) {

		$new_content = '';

		$pattern_full = '{(\[raw\].*?\[/raw\])}is';

		$pattern_contents = '{\[raw\](.*?)\[/raw\]}is';

		$pieces = preg_split($pattern_full, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($pieces as $piece) {

			if (preg_match($pattern_contents, $piece, $matches)) :

				$new_content .= $matches[1];

			else :

				$new_content .= wptexturize(wpautop($piece));

			endif;

		};

	return $new_content; }

	remove_filter('the_content', 'wpautop');
	remove_filter('the_content', 'wptexturize');
	add_filter('the_content', 'raw_formatter', 1);











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


	function custom_cleaner_shortcode( $atts, $content ) {

		$content = custom_strip_tag_list( $content, array('p','br') );

		return do_shortcode($content);
	}
	add_shortcode('clean', 'custom_cleaner_shortcode');


	function custom_direct_shortcode( $atts, $content ) {
		return $content;
	}
	add_shortcode('direct', 'custom_direct_shortcode');


if (!function_exists('undo_wptexturize')) {
	function undo_wptexturize($content) {
		$content = strip_tags($content);
		$content = htmlspecialchars($content, ENT_NOQUOTES);
		$content = str_replace("&amp;#8217;","'",$content);
		$content = str_replace("&amp;#8216;","'",$content);
		$content = str_replace("&amp;#8242;","'",$content);
		$content = str_replace("&amp;#8220;","\"",$content);
		$content = str_replace("&amp;#8221;","\"",$content);
		$content = str_replace("&amp;#8243;","\"",$content);
		$content = str_replace("&amp;#039;","'",$content);
		$content = str_replace("&#039;","'",$content);
		$content = str_replace("&amp;#038;","&",$content);
		$content = str_replace("&amp;gt;",'>',$content);
		$content = str_replace("&amp;lt;",'<',$content);
		$content = htmlspecialchars_decode($content);

		return $content;
	}
}

