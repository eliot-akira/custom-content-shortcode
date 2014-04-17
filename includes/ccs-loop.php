<?php

/*====================================================================================================
 *
 * Query loop shortcode
 *
 *====================================================================================================*/

class LoopShortcode {

	function __construct() {
		add_action( 'init', array( &$this, 'register' ) );
	}

	function register() {
		add_shortcode( 'loop', array( &$this, 'simple_query_shortcode' ) );
		add_shortcode( 'pass', array( &$this, 'simple_query_shortcode' ) );
//		add_filter( 'the_content', 'wpautop', 20 );  // change priority: wpautop after shortcode
	}

	function simple_query_shortcode( $atts, $template = null, $shortcode_name ) {

		global $ccs_global_variable;
		global $sort_posts;
		global $sort_key;

		$ccs_global_variable['is_loop'] = "true";
		$ccs_global_variable['current_gallery_name'] = '';
		$ccs_global_variable['current_gallery_id'] = '';
		$ccs_global_variable['is_gallery_loop'] = "false";
		$ccs_global_variable['is_attachment_loop'] = "false";
		$ccs_global_variable['is_repeater_loop'] = "false";
		$ccs_global_variable['total_comments'] = 0;

		if( ! is_array( $atts ) ) return;

		// non-wp_query arguments
		$args = array(
			'type' => '',
			'category' => '',
			'count' => '',
			'content_limit' => 0,
			'thumbnail_size' => 'thumbnail',
			'posts_separator' => '',
			'gallery' => '',
			'acf_gallery' => '',
			'id' => '',
			'name' => '',
			'field' => '', 'value' => '', 'compare' => '',
			'f' => '', 'v' => '', 'c' => '', 
			'field_2' => '', 'value_2' => '', 'compare_2' => '', 'relation' => '',
			'f2' => '', 'v2' => '', 'c2' => '', 'r' => '', 
			'repeater' => '',
			'x' => '',
			'taxonomy' => '', 'tax' => '', 'value' => '',
			'orderby' => '', 'keyname' => '', 'order' => '',
			'series' => '', 'key' => '',
			'post_offset' => '', 'offset' => '',
			'strip_tags' => '', 'strip' => '',
			'clean' => 'false',
			'title' => '', 'if' => '',
			'variable' => '', 'var' => '',
			'year' => '', 'month' => '', 'day' => '',
			'list' => '',
			'allow' => '', 'checkbox' => '', 'checkbox_2' => '', 
			'status' => '', 'parent' => '', 'exclude' => '',
			'columns' => '', 'tag' => '', 'pad' => '',
			'blog' => ''
		);

		$all_args = shortcode_atts( $args , $atts, true );
		extract( $all_args );


		/*---------------
		 * Parameters
		 *-------------*/

		if (!empty($blog)) {
			switch_to_blog($blog);
		}
		if (!isset($custom_field))
			$custom_field = "";

		if( $type == '' ) $type = 'any';
		$custom_value = $value;
		if($key!='') $keyname=$key;
		if($offset!='') $post_offset=$offset;
		if($strip!='') $strip_tags=$strip;
		if($allow!='') $strip_tags=$allow;
		if($status != null)
			$status = explode(",", $status);
		else
			$status = array("publish");

		if(!isset($query_field)) $query_field='';

		$current_name = $name;
		if ($var!='') $variable=$var;


		/*
		 * Meta query parameters
		 */

			if($f!='')
				$field = $f;
			if($v!='')
				$value = $v;
			if($c!='')
				$compare = $c;
			if($f2!='')
				$field_2 = $f2;
			if($v!='')
				$value_2 = $v2;
			if($c!='')
				$compare_2 = $c2;
			if($r!='')
				$relation = $r;
/*			if($checkbox!='')
				$field = $checkbox;
			if($checkbox_2!='')
				$field_2 = $checkbox_2;
*/

		if(( $field != 'gallery' ) && ($shortcode_name != 'pass') && ($value!='')) {

			$query_field = $field;
			$query_value = $value;

		} else
			$custom_field = $field;


		if($x != '') { // Simple loop without query

			$count = 0;
			$output = array();
			ob_start();

			while($x > 0) {
				$count++;
				$keywords = array(
					'COUNT' => $count
					);
				echo do_shortcode( $this->get_block_template( $template, $keywords ) );
				$x--;
			}

			$ccs_global_variable['is_loop'] = "false";
			if (!empty($blog)) {
				restore_current_blog();;
			}

			return ob_get_clean();
		}

		$query = array_merge( $atts, $all_args );

		// filter out non-wpquery arguments
		foreach( $args as $key => $value ) {
			unset( $query[$key] );
		}


		/*----------------
		 * Alter query
		 *---------------*/


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

		} elseif ( $exclude != '' ) {

			$id_array = explode(",", $exclude);

			$query['post__not_in'] = $id_array;

		} elseif ( $current_name != '') {

			// Get ID from post slug

			$query['name']=$current_name; $query['post_type'] = $type;

		} elseif ( $parent != '') {

			// Parent post by name

			$posts = get_posts( array('name' => $parent, 'post_type' => $type) );

			if ( $posts ) $parent_id = $posts[0]->ID;
			else return;

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

// Taxonomy query

		if($tax!='') $taxonomy=$tax;
		if($taxonomy!='') {

			$terms = explode(",", $custom_value);

			$compare = strtoupper($compare);

			if (!empty($compare)) {

				if ($compare=='NOT')
					$compare = 'NOT IN';

				$operator = $compare;

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

// Orderby

		if( $orderby != '') {

				$query['orderby'] = $orderby;
				if(in_array($orderby, array('meta_value', 'meta_value_num') )) {
					$query['meta_key'] = $keyname;
				}
				if($order=='') {
					if (($orderby=='meta_value_num') || ($orderby=='menu_order'))
						$query['order'] = 'ASC';	
					else
						$query['order'] = 'DESC';
				}				
		}

// Get posts in a series

		if($series!='') {

//			Expand range: 1-3 -> 1,2,3

			/* PHP 5.3+
				$series = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
				    return implode(',', range($m[1], $m[2]));
				}, $series);
			*/

			/* Compatible with older versions of PHP */

			$callback = create_function('$m', 'return implode(\',\', range($m[1], $m[2]));');
			$series = preg_replace_callback('/(\d+)-(\d+)/', $callback, $series);


			$sort_posts = explode(',', $series);

			$sort_key = $keyname;

				$query['meta_query'] = array(
						array(
							'key' => $keyname,
							'value' => $sort_posts,
							'compare' => 'IN'
						)
					);

		}


		/*---------------------
		 * Custom field query
		 *--------------------*/


			if( ($query_field!='') && ($query_value!='') ) {

				$query_value = html_entity_decode($query_value);
				$value_2 = html_entity_decode($value_2);

				$compare = strtoupper($compare);
				switch ($compare) {
					case '':
					case 'EQUAL': $compare = "LIKE"; break;
					case 'NOT':
					case 'NOT EQUAL': $compare = 'NOT LIKE'; break;
					default: break;
				}

				$query['meta_query'][] =
					array(
							'key' => $query_field,
							'value' => $query_value,
							'compare' => $compare
					);

				if( ($field_2!='') && ($value_2!='') ) {

					if($relation!='')
						$query['meta_query']['relation'] = strtoupper($relation);
					else
						$query['meta_query']['relation'] = 'AND';

					$compare_2 = strtoupper($compare_2);

					switch ($compare_2) {
						case '':
						case 'EQUAL': $compare_2 = 'LIKE'; break;
						case 'NOT':
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

		/*--------------
		 * Put a hook here?
		 *-------------*/

		if( ( $gallery!="true" ) && ( $type != "attachment") ) {

			if( $custom_field == "gallery" ) {
				$custom_field = "_custom_gallery";
			}

			$query['post_status'] = $status;

			remove_all_filters('posts_orderby');

			$output = array();
			ob_start();

			$posts = new WP_Query( $query );

	// Re-order by series

			if($series!='') {
				usort($posts->posts, "series_orderby_key");
			}

			if($orderby=='rand') {
				shuffle($posts->posts);	// Randomize
				if ($count == '')
					$count = 9999;
			}

			$total_comment_count = 0;

			$current_count = 1;

			/*====================================================================================
			 *
			 * For each post found
			 * 
			 */ 

			if( $posts->have_posts() ) : while( $posts->have_posts() ) : $posts->the_post();

				$ccs_global_variable['total_comments']+=get_comments_number();

				// Filter by checkbox..

				$skip_1 = false;
				if ($checkbox!='') {
					$values = explode(",", $query_value);
					$check_field = get_post_meta( get_the_ID(), $checkbox, $single=true );

					if(empty($compare)) $compare="or";
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
					$check_field = get_post_meta( get_the_ID(), $checkbox_2, $single=true );

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

				/*********
				 * Repeater field
				 */

				if($repeater != '') {
					$ccs_global_variable['is_repeater_loop'] = "true";
					$ccs_global_variable['current_loop_id'] = get_the_ID();

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

				/*********
				 * ACF Gallery field
				 */

				if($acf_gallery != '') {
					$ccs_global_variable['is_acf_gallery_loop'] = "true";
					$ccs_global_variable['current_loop_id'] = get_the_ID();

					if( function_exists('get_field') ) {

						$images = get_field($acf_gallery, get_the_ID());
						if( $images ) { // If images exist

							$count=1;

							$ccs_global_variable['current_image_ids'] = implode(',', get_field($acf_gallery, get_the_ID(), false));

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

				} else {

				// Not gallery field

					// Attachments?

					if($custom_field == "attachment") {
						$attachments =& get_children( array(
							'post_parent' => get_the_ID(),
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

					// Normal custom fields

						$custom_field_content = get_post_meta( get_the_ID(), $custom_field, $single=true );
						$attachment_ids = get_post_meta( get_the_ID(), '_custom_gallery', true );
					}

					$keywords = apply_filters( 'query_shortcode_keywords', array(
						'QUERY' => serialize($query), // DEBUG purpose
						'URL' => get_permalink(),
						'ID' => get_the_ID(),
						'TITLE' => get_the_title(),
						'AUTHOR' => get_the_author(),
						'AUTHOR_URL' => get_author_posts_url( get_the_author_meta( 'ID' ) ),
						'DATE' => get_the_date(),
						'THUMBNAIL' => get_the_post_thumbnail( null, $thumbnail_size ),
						'THUMBNAIL_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),
						'CONTENT' => ( $content_limit ) ? wp_trim_words( get_the_content(), $content_limit ) : get_the_content(),
						'EXCERPT' => get_the_excerpt(),
						'COMMENT_COUNT' => get_comments_number(),
						'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
						'IMAGE' => get_the_post_thumbnail(),
						'IMAGE_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),
						'FIELD' => $custom_field_content,
						'VAR' => $variable,
						'VARIABLE' => $variable,
						'IDS' => $attachment_ids,
					) );

					$total_comment_count += get_comments_number();

					$out = $this->get_block_template( $template, $keywords ); // Process {KEYWORDS}

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

					if ($clean == 'true') {
						$output[] = do_shortcode(custom_clean_shortcodes( $out ));
					} else {
						$output[] = do_shortcode($out);
					}

				} // End of not gallery field (just attachment or normal field)

			} // End of not repeater

			$current_count++;

			if($orderby=='rand') {
				if ($current_count > $count) break;
			}

			} /* Not skip */

			endwhile; endif; // End loop for each post

			wp_reset_query();
			wp_reset_postdata();

			if (empty($if)) {

				if (!empty($columns)) { // Create simple columns

					$col = 0;
					$percent = 100 / (int)$columns;
					$clear = '<div style="clear:both;"><br></div>';

					foreach ($output as $each) {
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
					if (($col%$columns)!=0) // Last row not filled
						echo $clear;

				} else {
					echo implode( "", $output );
				}

			} else {
				if ( ($if=='all-no-comments') && ($total_comment_count==0) ) {
					echo $output[0];
				}
			}

			$ccs_global_variable['is_loop'] = "false";
			if (!empty($blog)) {
				restore_current_blog();;
			}

			return ob_get_clean();

		} else {

	// Loop for attachments

			if( $type == 'attachment' ) {

				$output = array();
				ob_start();

				if($category == '') {
					$posts =& get_children( array (
					'post_parent' => get_the_ID(),
					'post_type' => 'attachment',
					'post_status' => $status
					) );

					foreach( $posts as $attachment_id => $attachment ) {
						$attachment_ids .= $attachment_id . " ";
					}

				} else { // Fetch posts by category, then attachments

					$my_query = new WP_Query( array(
				    	'cat' => get_category_by_slug($category)->term_id, 
						'post_type' => $status,
					));
					if( $my_query->have_posts() ) {
						$posts = array('');
						while ( $my_query->have_posts() ) {
							$my_query->the_post();

							$new_children =& get_children( array (
								'post_parent' => get_the_ID(),
								'post_type' => 'attachment',
								'post_status' => $status
							) );

							foreach( $new_children as $attachment_id => $attachment ) {
								$attachment_ids .= $attachment_id . " ";
							}
						}
					}
				} // End fetch attachments by category

				if( empty($posts) ) {
					$output = null;
				} else {

					$attachment_ids = explode(" ", trim( $attachment_ids ) );

					if ( $attachment_ids ) { 

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
					'FIELD' => get_post_meta( get_the_ID(), $custom_field, $single=true ),
					'IDS' => get_post_meta( get_the_ID(), '_custom_gallery', true ),
				) );

							$output[] = do_shortcode( $this->get_block_template( $template, $keywords ) );
						} /** End for each attachment **/
					}
					$ccs_global_variable['is_attachment_loop'] = "false";
					wp_reset_query();
					wp_reset_postdata();

					echo implode( "", $output );
					$ccs_global_variable['is_loop'] = "false";
					if (!empty($blog)) {
						restore_current_blog();;
					}

					return ob_get_clean();
				}
			} // End type="attachment"

			else {

				/*********************
				 *
				 * Gallery Loop
				 *
				 */

				if( function_exists('custom_gallery_get_image_ids') ) {

					$output = array();
					ob_start();

					if($ccs_global_variable['current_gallery_id'] == '') {
						$ccs_global_variable['current_gallery_id'] = get_the_ID();
					}
					$posts = new WP_Query( $query );
					$attachment_ids = custom_gallery_get_image_ids();

					if ( $attachment_ids ) { 
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
						'IMAGE_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),

						'FIELD' => get_post_meta( get_the_ID(), $custom_field, $single=true ),
						'IDS' => get_post_meta( get_the_ID(), '_custom_gallery', true ),
					) );
						
							$output[] = do_shortcode( $this->get_block_template( $template, $keywords ) );
						} /** End for each attachment **/

						$ccs_global_variable['is_gallery_loop'] = "false";
						wp_reset_query();
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

	} /* End of function simple_query_shortcode */ 

	/*
	 * Replaces {VAR} with $parameters['var'];
	 */

	function get_block_template( $string, $parameters ) {
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

}

$loop_shortcode = new LoopShortcode;

/*--------------------------------------*/
/*    Clean up Shortcodes
/*--------------------------------------*/


	function custom_clean_shortcodes($content){   
/*	    $array = array (
	        '<p>[' => '[', 
	        ']</p>' => ']', 
	        ']<br />' => ']',
	        ']<br/>' => ']',
	        ']<br>' => ']',
	        '<br />[' => '[',
	        '<br/>[' => '[',
	        '<br>[' => '[',
	        '<br />' => '', // remove all
	        '<br/>' => '',
	        '<br>' => '',
	        '<p>' => '',
	        '</p>' => ''
	    );
	    $content = strtr($content, $array);
*/

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