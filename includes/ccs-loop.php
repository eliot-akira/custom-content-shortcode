<?php

/*====================================================================================================
 *
 * Loop shortcode: query posts and loop through it
 *
 *====================================================================================================*/

class LoopShortcode {

	private static $sort_posts;
	private static $sort_key;

	function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	function register() {

		add_shortcode( 'loop', array( $this, 'the_loop_shortcode' ) );
		add_shortcode( 'pass', array( $this, 'pass_shortcode' ) );
		add_shortcode( 'loop-count', array( $this, 'loop_count_shortcode' ) );

		/*========================================================================
		 *
		 * Set up WP filters
		 *
		 *=======================================================================*/

		// Get settings

		$settings = get_option( 'ccs_content_settings' );

		// Move wpautop filter?

		$move_wpautop = isset( $settings['move_wpautop'] ) ?
			esc_attr( $settings['move_wpautop'] ) : 'off';

		if ($move_wpautop == "on") {
			remove_filter( 'the_content', 'wpautop' );
			add_filter( 'the_content', 'wpautop' , 99);
			add_filter( 'the_content', 'shortcode_unautop',100 );
		}

		// Enable shortcodes in widget?

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
	 * Main function for [loop]
	 * 
	 * To do: organize into a group of functions, with filters
	 *
	 *=======================================================================*/

	function the_loop_shortcode( $atts, $template = null, $shortcode_name ) {

		global $ccs_global_variable;

		if( ! is_array( $atts ) ) return; // Needs at least one parameter

		$ccs_global_variable['is_loop'] = "true";
		$ccs_global_variable['current_loop_count'] = 0;
		$ccs_global_variable['current_gallery_name'] = '';
		$ccs_global_variable['current_gallery_id'] = '';
		$ccs_global_variable['is_gallery_loop'] = "false";
		$ccs_global_variable['is_attachment_loop'] = "false";
		$ccs_global_variable['is_repeater_loop'] = "false";
		$ccs_global_variable['total_comments'] = 0;

		// Non WP_Query arguments

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


		/*---------------
		 * Parameters
		 *-------------*/

		if (!empty($blog)) {
			switch_to_blog($blog);
		}
		if (!isset($custom_field))
			$custom_field = "";

		if( empty($type) ) $type = 'any';
		$custom_value = $value;
		if(!empty($key)) $keyname=$key;
		if(!empty($offset)) $post_offset=$offset;
		if(!empty($strip)) $strip_tags=$strip;
		if(!empty($allow)) $strip_tags=$allow;
		if(!empty($status))
			$status = explode(",", $status);
		else {
			if ($type=="attachment")
				$status = array("any");
			else
				$status = array("publish");
		}

		if(!isset($query_field)) $query_field='';

		$current_name = $name;
		if (!empty($var)) $variable=$var;


		/*
		 * Aliases
		 */

			if($f!='')	$field = $f;
			if($v!='')	$value = $v;
			if($c!='')	$compare = $c;
			if($f2!='')	$field_2 = $f2;
			if($v!='')	$value_2 = $v2;
			if($c!='')	$compare_2 = $c2;
			if($r!='')	$relation = $r;
			if (!empty($fields)) $custom_fields = $fields; // Alias

		if(( $field != 'gallery' ) && ($shortcode_name != 'pass') && ($value!='')) {

			$query_field = $field;
			$query_value = $value;

		} else {
			$custom_field = $field;
		}



		/*========================================================================
		 *
		 * Parameter x: loop x times, without query
		 *
		 *=======================================================================*/

		if($x != '') {

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

			$ccs_global_variable['is_loop'] = "false";
			if (!empty($blog)) {
				restore_current_blog();;
			}

			return ob_get_clean();
		}




		/*========================================================================
		 *
		 * Merge parameters into query
		 *
		 *=======================================================================*/

		$query = array_merge( $atts, $all_args );

		// filter out non-wpquery arguments
		foreach( $args as $key => $value ) {
			unset( $query[$key] );
		}


		/*---------------
		 * In a foreach loop?
		 *-------------*/

		if (isset($ccs_global_variable['for_loop']) &&
			($ccs_global_variable['for_loop']=='true')) {

			if ($ccs_global_variable['for_each']['type']=='taxonomy') {
				$taxonomy = $ccs_global_variable['for_each']['taxonomy'];
				$custom_value = $ccs_global_variable['for_each']['slug'];
			}
		}


		if ($shortcode_name=="pass") {
			$id = get_the_ID();
		}

		if( $category != '' ) {
			$query['category_name'] = $category;
		}
		if( $tag != '' ) {
			$query['tag'] = $tag;
		}
		if( $count != '' ) {

			if ($orderby=='rand') {
				$query['posts_per_page'] = '-1';
			} else {
				$query['posts_per_page'] = $count;
				$query['ignore_sticky_posts'] = true;
			}

		} else {

			if($post_offset!='')
				$query['posts_per_page'] = '9999'; // Show all posts (to make offset work)
			else
				$query['posts_per_page'] = '-1'; // Show all posts (normal method)

		}

		if($post_offset!='')
			$query['offset'] = $post_offset;

		if( $type == '' ) {
			$query['post_type'] = 'any';
		} else {
			$query['post_type'] = $type;
			if ( $custom_field != 'gallery' ){
				$query['p'] = '';
			}
		}

		if ( $id != '' ) {

			$id_array = explode(",", $id); // Multiple IDs possible

			$query['post__in'] = $id_array;
			$query['orderby'] = 'post__in'; // Preserve ID order

		} elseif ( $exclude != '' ) {

			$id_array = explode(",", $exclude);

			$query['post__not_in'] = $id_array;

		} elseif ( $current_name != '') {

			// Get ID from post slug

			$query['name']=$current_name; $query['post_type'] = $type;

		} elseif ( $parent != '') {

			// Parent post by name or ID

			if (is_numeric($parent))
				$parent_id = intval($parent);
			else {
				$posts = get_posts( array('name' => $parent, 'post_type' => $type) );
				if ( $posts ) $parent_id = $posts[0]->ID;
				else {
					$ccs_global_variable['is_loop']='false';
					return;
				}
			}

			$query['post_parent'] = $parent_id; $query['post_type'] = $type;

		} elseif (( $custom_field == 'gallery' ) && ($shortcode_name != 'pass') ){

			$gallery = 'true';

			if (!empty($current_name)) {
				$query['name']=$current_name;
				$ccs_global_variable['current_gallery_name'] = $current_name;
			} else {
				$query['p'] = get_the_ID(); $query['post_type'] = "any";
			}

			$posts = get_posts( $query );
			if( $posts ) { $ccs_global_variable['current_gallery_id'] = $posts[0]->ID;
			}

		}

		if(!isset($custom_field)) $custom_field='';

// Query by date

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

		if ( !empty($tax) ) $taxonomy = $tax;
		if ( !empty($taxonomy) ) {

			$terms = explode(",", $custom_value);

			if (!empty($compare)) {

				if ( $compare=='=' ) $operator = 'IN';
				elseif ( $compare=='!=' ) $operator = 'NOT IN';
				else {
					$compare = strtoupper($compare);
					if ( $compare == 'NOT' )
						$compare = 'NOT IN';
					$operator = $compare;

				}
			} else {
				$operator = 'IN';
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


		if($order!='')
			$query['order'] = $order;

		/*========================================================================
		 *
		 * Order by field value
		 *
		 *=======================================================================*/

		if( !empty($orderby)) {
				if ($orderby=="field") $orderby = 'meta_value';
				if ($orderby=="field_num") $orderby = 'meta_value_num';

				$query['orderby'] = $orderby;

				if(in_array($orderby, array('meta_value', 'meta_value_num') )) {
					$query['meta_key'] = $keyname;
				}
				if(empty($order)) {
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

		if($series!='') {

			// Remove white space
			$series = str_replace(' ', '', $series);

//			Expand range: 1-3 -> 1,2,3

			/* PHP 5.3+
				$series = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
				    return implode(',', range($m[1], $m[2]));
				}, $series);
			*/

			/* Compatible with older versions of PHP */

			$callback = create_function('$m', 'return implode(\',\', range($m[1], $m[2]));');
			$series = preg_replace_callback('/(\d+)-(\d+)/', $callback, $series);

			self::$sort_posts = explode(',', $series);

			self::$sort_key = $keyname;

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

		if( ($query_field!='') && ($query_value!='') ) {

			// Support for date values

			if ($query_value=="future") {
				$query_value = "now";
				$compare = ">";
			} elseif ($query_value=="past") {
				$query_value = "now";
				$compare = "<";
			}

			if (( isset($in) && ($in == "string") ) || (!empty($date_format)) ){
				if (empty($date_format)) {
					if ($query_value == "today")
						$date_format = "Y-m-d"; // Y-m-d h:i A
					if ($query_value == "now")
						$date_format = "Y-m-d h:i A"; 
				}

				if (($query_value == "today") || ($query_value == "now")){
					$query_value = date($date_format,time());
				}
			} else {
				if (($query_value == "today") || (($query_value == "now"))){
					$query_value = time();
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

			$query['meta_query'][] =
				array(
						'key' => $query_field,
						'value' => $query_value,
						'compare' => $compare
				);

			// Additional query by field value

			if( ($field_2!='') && ($value_2!='') ) {

				if($relation!='')
					$query['meta_query']['relation'] = strtoupper($relation);
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

		if( ( $gallery!="true" ) && ( $type != "attachment") ) {

			// Last-minute adjustments
			if( $custom_field == "gallery" )
				$custom_field = "_custom_gallery";
			$query['post_status'] = $status;
			remove_all_filters('posts_orderby');


			/*========================================================================
			 *
			 * Run the query
			 *
			 *=======================================================================*/

			$output = array();
			ob_start();
			$posts = new WP_Query( $query );

			// Re-order by series

			if($series!='') {
				usort($posts->posts, array($this, "custom_series_orderby_key"));
			}

			if($orderby=='rand') {
				shuffle($posts->posts);	// Randomize
				if ($count == '')
					$count = 9999;
			}

			$total_comment_count = 0;
			$current_count = 1;

			/*========================================================================
			 *
			 * For each post found
			 *
			 *=======================================================================*/

			if( $posts->have_posts() ) {

				while( $posts->have_posts() ) : $posts->the_post();

				$current_id = get_the_ID();
				$ccs_global_variable['current_loop_id']=$current_id;
				$ccs_global_variable['current_loop_count']=$current_count;
				$ccs_global_variable['total_comments']+=get_comments_number();


				/*========================================================================
				 *
				 * Filter posts by checkbox query
				 *
				 *=======================================================================*/

				$skip_1 = false;
				if ($checkbox!='') {
					$values = explode(",", $query_value);
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


				if( ! $skip ) {

				/*========================================================================
				 *
				 * Repeater field
				 *
				 *=======================================================================*/

				if($repeater != '') {

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

				if($acf_gallery != '') {

					$ccs_global_variable['is_acf_gallery_loop'] = "true";
					$ccs_global_variable['current_loop_id'] = $current_id;

					if( function_exists('get_field') ) {

						$images = get_field($acf_gallery, $current_id);
						if( $images ) { // If images exist

							$count=1;

							$ccs_global_variable['current_image_ids'] = implode(',', get_field($acf_gallery, $current_id, false));

							if($shortcode_name == 'pass') {

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

				} else { // Not gallery field


					/*========================================================================
					 *
					 * Attachments..?
					 *
					 *=======================================================================*/

					if($custom_field == "attachment") {
						$attachments =& get_children( array(
							'post_parent' => $current_id,
							'post_type' => 'attachment',
						) );
						if( empty($attachments) ) {
							$custom_field_content = null; $attachment_ids = null;
						} else {
							$attachment_ids = '';
							foreach( $attachments as $attachment_id => $attachment) {
								$attachment_ids .= $attachment_id . ",";
							}
							$attachment_ids = trim($attachment_ids, ",");
							$custom_field_content = $attachment_ids;
						}
					} else {

					/*========================================================================
					 *
					 * Normal custom field
					 *
					 *=======================================================================*/

						$custom_field_content = get_post_meta( $current_id, $custom_field, true );
						$attachment_ids = get_post_meta( $current_id, '_custom_gallery', true );
					}


				/*========================================================================
				 *
				 * Special tags: {FIELD}
				 *
				 *=======================================================================*/

					/* prepare custom fields to expand */
					/* post_meta fetching needs to be optimized? */
					$extra_keywords = array();
					if (!empty($fields)) {
						$ks = array_map("trim", array_filter(explode(',', $custom_fields))); // parse CSV to field keys
						$pm = get_post_meta($current_id);
						$extra_keywords =
							array_map(
								function($v){ return is_array($v) ? $v[0] : $v; }, // Flatten the values ( Array > Value )
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

					/*========================================================================
					 *
					 * Special tags: {TAG}
					 *
					 *=======================================================================*/
					$keywords = apply_filters( 'query_shortcode_keywords', array_merge($extra_keywords, array(

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
						'FIELD' => $custom_field_content,
						'VAR' => $variable,
						'VARIABLE' => $variable,
						'IDS' => $attachment_ids,
					)) );

					$out = $this->get_block_template( $template, $keywords ); // Process {KEYWORDS}

					$total_comment_count += get_comments_number();


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

					if ($clean == 'true') {
						$output[] = do_shortcode(custom_clean_shortcodes( $out ));
					} else {
						$output[] = do_shortcode($out);
					}

				} // End of not gallery field (just attachment or normal field)

			} // End of not repeater

			$current_count++;

			if ($orderby=='rand') {
				if ($current_count > $count) break;
			}

			} /* Not skip */

// End: loop for each post found

			endwhile; $nothing_found = false;

			} // End: if post found

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

			if (!$nothing_found) {

				if (empty($if)) {

					/*========================================================================
					 *
					 * Create simple columns
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
 * Final output (for not attachment or gallery)
 *
 *=======================================================================*/

						$output = implode( "", $output );

						// Trim final output

						if (!empty($trim)) {
							if ($trim=='true') $trim = null;
							$output = trim($output, " \t\n\r\0\x0B,".$trim);
						}

						echo $output; // to buffer
					}

				} else {
					if ( ($if=='all-no-comments') && ($total_comment_count==0) ) {
						echo $output[0];
					}
				}
			}

			$ccs_global_variable['is_loop'] = "false";
			if (!empty($blog)) {
				restore_current_blog();
			}

			return ob_get_clean();

		} else {


/*========================================================================
 *
 * Attachment loop: replaced by [attached] shortcode
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
				'FIELD' => get_post_meta( $current_id, $custom_field, $single=true ),
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
 * Gallery Loop
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

						'FIELD' => get_post_meta( $current_id, $custom_field, $single=true ),
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
 * Helper functions
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
			'field' => ''
			);
		extract( shortcode_atts( $args , $atts, true ) );

		if (!empty($field)) {
			$post_id = get_the_id();
			$field_value = get_post_meta( $post_id, $field, true );
			if (is_array($field_value))
				$field_value = implode(",", $field_value);

			$replace = array(
				'ID' => $post_id,
				'FIELD' => $field_value,
				);

			$content = self::get_block_template( $content, $replace );
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

}

$loop_shortcode = new LoopShortcode;




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

/*
		foreach ($tags as $tag) {
			$out = preg_replace('/<\/?' . $tag . '(.|\s)*?>/', '', $content);
//			$out = preg_replace('#</?'.$tag.'[^>]*>#is', '--', $content);
		}
*/
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

