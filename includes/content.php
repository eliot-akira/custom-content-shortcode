<?php

/*====================================================================================================
 *
 * [content] shortcode
 *
 * Get a field or post content
 *
 *====================================================================================================*/

new CCS_Content;

class CCS_Content {

	function __construct() {

		add_shortcode('content', array($this, 'content_shortcode'));
		add_shortcode('field', array($this, 'field_shortcode'));
		add_shortcode('taxonomy', array($this, 'taxonomy_shortcode'));
	}

	public static function content_shortcode( $atts ) {

		global $post;

		$current_post = $post;

		extract(shortcode_atts(array(

			'type' => null,
			'name' => null,
			'field' => null,
			'id' => null,

			'page' => null,
//			'status' => null,

			'taxonomy' => null, 'checkbox' => null, 'out' => null,

			'align' => null, 'class' => null, 'height' => null,
			'words' => null, 'len' => null, 'length' => null,
			'date_format' => null, 'timestamp' => null,
			'image' => null, 'in' => null, 'return' => null,
			'image_class' => null, 
			'more' => '', 'dots' => '...',

			'meta' => '', // Author meta

			'embed' => null, 'format' => null, 'shortcode' => null,

			'area' => null, 'sidebar' => null, 
			'menu' => null, 'ul' => null,

			'row' => null, 'sub' => null,
			'acf_gallery' => null, 'num' => null,

			'gallery' => 'false', 'group' => null,

			'url' => null, // Optional for image-link

			/* Native gallery options: orderby, order, columns, size, link, include, exclude */

			'orderby' => null, 'order' => null, 'columns' => null, 'size' => 'full',
			'link' => null, 'include' => null, 'exclude' => null

		), $atts));


		/*========================================================================
		 *
		 * Set up query parameters
		 *
		 *=======================================================================*/

		$custom_post_type = $type;
		$custom_post_name = $name;

		if(!empty($page)) {
			$custom_post_type = 'page';
			$custom_post_name = $page;
		}

		$custom_menu_name = $menu;
		$custom_field = $field;
		$custom_id = $id;
		$content_format = $format;
		$shortcode_option = $shortcode;
		$custom_gallery_type = $gallery;
		$custom_gallery_name = $group;
		if ($size=="middle") $size = "medium";
		if (!isset($image_class)) $image_class = "";
		$custom_area_name = $area;
		if(!empty($len)) $length=$len;
		if ( ($taxonomy != '') && ($out != '') ) {
			$taxonomy_out = $out;
			$out = null;
		}

	/*	if ((!empty($more)) && (empty($field)))
			$words="55";
	*/

		if (!empty($checkbox))
			$custom_field = $checkbox;

		/* For displaying ACF labels for checkbox or select field */

		if (!empty($out) && ($out=="label")) {
			$acf_label_out = true;
			$out = null;
		} else {
			$acf_label_out = false;
		}


		if(!empty($status))
			$status = explode(",", $status);
		else
			$status = array("publish");

		$out = null;
		if($image != null) {
			$custom_field = $image; // Search for the image field
		}

		if( $custom_post_type == '' ) { // If no post type is specified, then default is any
			$custom_post_type = 'any';
		}


		/*========================================================================
		 *
		 * In an attachment or gallery loop
		 *
		 *=======================================================================*/

		// Get each field as requested

/*
		if( ( CCS_Loop::$state['is_gallery_loop'] == 'true') || 
			( CCS_Loop::$state['is_attachment_loop'] == 'true' ) || 
			 ( CCS_Loop::$state['is_acf_gallery_loop'] == 'true' ) ) {

			if (empty($custom_field)) $custom_field = 'image'; // show attachment image by default [content]

			switch($custom_field) {

				case "image":
					if (empty($size)) {
						$out = $ccs_global_variable['current_image']['full'];
					} else {
						$out = $ccs_global_variable['current_image'][$size];
					}
					break;
				case "image-url": $out = $ccs_global_variable['current_image_url']; break;
				case "url":
					$out = $ccs_global_variable['current_attachment_file_url']; break;
				case "page-url":
					$out = $ccs_global_variable['current_attachment_page_url']; break;
				case "attach-link":
					$out = $ccs_global_variable['current_attachment_link']; break;
				case "thumbnail": $out = $ccs_global_variable['current_image_thumb']; break;
				case "thumbnail-url": $out = $ccs_global_variable['current_image_thumb_url']; break;
				case "caption": $out = $ccs_global_variable['current_image_caption']; break;
				case "id": $out = $ccs_global_variable['current_attachment_id']; break;
				case "title": $out = $ccs_global_variable['current_image_title']; break;
				case "description": $out = $ccs_global_variable['current_image_description']; break;
				case "alt": $out = $ccs_global_variable['current_image_alt']; break;
				case "count": $out = $ccs_global_variable['current_row']; break;
			}

			if (!empty($image_class)) {
				$out = str_replace('class="', 'class="'.$image_class.' ', $out);
			}

			if (!empty($class))
				return '<div class="' . $class . '">' . $out . '</div>';
			else return $out;

		} // End attachment or gallery loop
*/

		/*========================================================================
		 *
		 * Sidebar/widget area
		 *
		 *=======================================================================*/

		if( $sidebar != '') {
			$custom_area_name = $sidebar;
		}
		if( $custom_area_name != '') {
			$back =  '<div id="' . str_replace( " ", "_", $custom_area_name ) . '" class="sidebar';
			if(!empty($class))
				$back .=  ' ' . $class;

			$back .= '">';

			ob_start();
			if ( function_exists('dynamic_sidebar') )
				dynamic_sidebar($custom_area_name);
			$back .= ob_get_clean();
			$back .= "</div>";
			return $back;
		}


		/*========================================================================
		 *
		 * Menu
		 *
		 *=======================================================================*/

		if( $custom_menu_name != '' ) {

			$menu_args = array (
				'menu' => $custom_menu_name,
				'echo' => false,
				'menu_class' => $ul,
			);

			$output = wp_nav_menu( $menu_args );

			if(empty($class)) {
				return $output;
			} else {
				return '<div class="' . $class . '">' . $output . '</div>';
			}
		}



		/*========================================================================
		 *
		 * Get post ID
		 *
		 *=======================================================================*/


		// Relationship loop

		if (CCS_Loop::$state['is_relationship_loop']=='true') {
			$custom_id = CCS_Loop::$state['relationship_id'];
		}




		// If post name/slug is defined, find its ID

		if($custom_post_name != '') {
			$args=array(
				'name' => $custom_post_name,
				'post_type' => $custom_post_type,
				'post_status' => $status,
				'posts_per_page' => '1',
	  		);

			$my_posts = get_posts($args);
			if ( $my_posts ) {
				$custom_id=$my_posts[0]->ID;
				$current_post = $my_posts[0];
			} else {
				return null; // No post found by that name
			}
		}
		else {

			if (!empty($custom_id)) {
				$current_post = get_post($custom_id); // Get post by ID
			} else {
				// If no name or id, then current post
				$custom_id = get_the_ID();
			}

		}


		/*========================================================================
		 *
		 * If ACF repeater field loop then get sub field
		 *
		 *=======================================================================
		
		if( CCS_Loop::$state['is_repeater_loop'] == 'true') {

			$custom_id = $ccs_global_variable['current_loop_id'];
			if (empty($size)) $size='full';

			if($custom_field=='row') {
				return $ccs_global_variable['current_row'];
			}
			if( function_exists('the_sub_field') ) {

				$out = get_sub_field($custom_field, $custom_id);
				switch($in) {
					case 'id' : $out = wp_get_attachment_image( $out, $size ); break;
					case 'url' : $out = '<img src="' . $out . '">'; break;
					default : if (is_array($out)) {
						$out = wp_get_attachment_image( $out['id'], $size );
					} else {
						$out = wp_get_attachment_image( $out, $size ); 
					}
				}
				if ($custom_field == 'id') {
					$out = $ccs_global_variable['current_loop_id'];
				}
			} else {
				$out = get_post_meta($custom_id, $custom_field, $single=true);
			}
			if(($class!='') || ($align!='')) {
				$pre = '<div';
				if(!empty($class))
					$pre .= ' class="' . $class . '"';
				if(!empty($align))
					$pre .= ' align="' . $align . '"';
				$pre .= '>' . $out . '</div>';
				return $pre;
			}
			else return $out;
		}
	
		// Repeater field subfield

		if ($sub != '') {
			$out = null;
			if( function_exists('get_field') ) {
				if (empty($size)) $size='full';
				$rows = get_field($custom_field, $custom_id); // Get all rows
				$row = $rows[$row-1]; // Get the specific row (first, second, ...)
				$out = $row[$sub]; // Get the subfield
				switch($in) {
					case 'id' : $out = wp_get_attachment_image( $out, $size ); break;
					case 'url' : $out = '<img src="' . $out . '">'; break;
					default : if (is_array($out)) {
						$out = wp_get_attachment_image( $out['id'], $size );
					} else {
						$out = wp_get_attachment_image( $out, $size ); 
					}
				}
			}
			if ( (!empty($class)) || (!empty($align)) ) {
				$pre = '<div';
				if($class!='')
					$pre .= ' class="' . $class . '"';
				if($align!='')
					$pre .= ' align="' . $align . '"';
				$pre .= '>' . $out . '</div>';
				return $pre;
			}
			else return $out;
		}
		*/


		/*========================================================================
		 *
		 * Gallery types - native or carousel
		 *
		 *=======================================================================*/

		if( $custom_gallery_type == "carousel") {
			$out = '[gallery type="carousel" ';
			if($custom_gallery_name != '') {
				$out .= 'name ="' . $custom_gallery_name . '" ';
			}
			if($height!='') {
				$out .= 'height ="' . $height . '" ';	
			}
			$out .= 'ids="';

			if($acf_gallery!='') {
				if( function_exists('get_field') ) {
					$out .= implode(',', get_field($acf_gallery, $custom_id, false));
				}
			} else {
				$out .= get_post_meta( $custom_id, '_custom_gallery', true );
			}
			$out .= '" ]';

			if(!empty($class))
				$out = '<div class="' . $class . '">' . $out . '</div>';
			
			return do_shortcode( $out );

		} else {

			if( $custom_gallery_type == "native") {

				$out = '[gallery " ';
				if($custom_gallery_name != '') {
					$out .= 'name ="' . $custom_gallery_name . '" ';
				}
				$out .= 'ids="';

				if ($acf_gallery!='') {
					if( function_exists('get_field') ) {
						$out .= implode(',', get_field($acf_gallery, $custom_id, false));
					}
				} else {
					$out .= get_post_meta( $custom_id, '_custom_gallery', true );
				}
				$out .= '"';

				/* Additional parameters */

				$native_gallery_options = array(
					'orderby' => $orderby,
					'order' => $order,
					'columns' => $columns,
					'size' => $size,
					'link' => $link,
					'include' => $include,
					'exclude' => $exclude
				);

				foreach ($native_gallery_options as $option => $value) {

					if (!empty($value)) {
						$out .= ' ' . $option . '="' . $value . '"';
					}
				}

				$out .= ']';
				if(!empty($class))
					$out = '<div class="' . $class . '">' . $out . '</div>';
				return do_shortcode( $out );
			}	
		}


		/*========================================================================
		 *
		 * Image field
		 *
		 *=======================================================================*/

		if (!empty($image)) {

			$image_field = get_post_meta( $custom_id, $image, true );

			switch($in) {

				case 'object' :
					if (is_array( $image_field )) {
						$image_id = $image_field['id'];
					} else {
						$image_id = $image_field; // Assume it's ID
					}
					$out = wp_get_attachment_image( $image_id , $size );
					break;
				case 'url' : $out = '<img src="' . $out . '">'; break;
				case 'id' : 
				default :
					$image_id = $image_field;
					$out = wp_get_attachment_image( $image_field, $size ); break;
			}

			if ($return=='url') {

				if ( $in=='url') return $out;

				$image_info = wp_get_attachment_image_src( $image_id, 'full' );
				$image_url = $image_info[0];
				return $image_url;

			} else {

				$image_return = $out;
	/*
				$image_return = wp_get_attachment_image( $image_id, 'full' );
	*/			if(!empty($class))
					$image_return = '<div class="' . $class . '">' . $image_return . '</div>';

				return $image_return;
			}

		}


		/*========================================================================
		 *
		 * If no field is specified
		 *
		 *=======================================================================*/

		if ($custom_field == '') { 

			if ($taxonomy != '') { // Taxonomy query?

			    // Get taxonomy terms related to post

				if ($taxonomy == "tag")
					$taxonomy="post_tag";

			    $terms = get_the_terms( $custom_id, $taxonomy );

			    if ( !empty( $terms ) ) {
			    	foreach ($terms as $term) {
			    		$out_all[] = $term->name;
			    		$slugs_all[] = $term->slug;
			    	}

			    	if ( (isset($taxonomy_out)) && ($taxonomy_out == 'slug')) {
				    	$out = implode(" ", $slugs_all);
			    	} else {
				    	$out = implode(", ", $out_all);
			    	}
			    } else {
			    	$out = null;
			    }

		    } else { // no field or taxonomy, then just return post content

//				$out = $current_post;
//				$current_post = get_post( $custom_id );
				$out = $current_post->post_content;
				if($content_format=='')
					$content_format = 'true';
				if($embed=='')
					$embed = 'true';
			}

		} else { // else return specified field



			/*========================================================================
			 *
			 * Predefined fields
			 *
			 *=======================================================================*/


			switch($custom_field) {
				case "id": $out = $custom_id; break;
				case "url": $out = post_permalink( $custom_id ); break;
				case "edit-url": $out = get_edit_post_link( $custom_id ); break;
				case "edit-link": $out = apply_filters( 'the_title', $current_post->post_title ); break;
					
				case "slug": $out = $current_post->post_name; break;

				case "title-link":
				case "title-link-out":
				case "title": $out = apply_filters( 'the_title', $current_post->post_title ); break;

				case "title-length": $out = strlen(apply_filters( 'the_title', $current_post->post_title )); break;

				case "author":

					$author_id = $current_post->post_author;
					$user = get_user_by( 'id', $author_id);

					if ( !empty($meta) )
						$out = get_the_author_meta( $meta, $author_id );
					else
						$out = $user->display_name;
					break;

				case "author-id":

					$author_id = $current_post->post_author;
					$out = $author_id; break;

				case "author-url":

					$author_id = $current_post->post_author;
					$out = get_author_posts_url($author_id); break;

				case "avatar": 

					$author_id = $current_post->post_author;
					if( $size=='' )
						$out = get_avatar($author_id);
					else
						$out = get_avatar($author_id, $size);
					break;

				case "date":

					if($date_format!='') {
						$out = mysql2date($date_format, $current_post->post_date); break;
					}
					else { // Default date format under Settings -> General
						$out = mysql2date(get_option('date_format'), $current_post->post_date); break;
					}

				case "modified":

					if($date_format!='') {
						$out = get_post_modified_time( $date_format, $gmt=false, $custom_id, $translate=true ); break;
					}
					else { // Default date format under Settings -> General
						$out = get_post_modified_time( get_option('date_format'), $gmt=false, $custom_id, $translate=true ); break;
					}

				case "image":				// image
				case "image-link":			// image with link to post
				case "image-link-self":		// image with link to attachment page

					$out = get_the_post_thumbnail( $custom_id, $size ); break;

				case "image-full": $out = get_the_post_thumbnail( $custom_id, 'full' ); break;
				case "image-url": $out = wp_get_attachment_url(get_post_thumbnail_id($custom_id)); break;

				case "thumbnail":			// thumbnail
				case "thumbnail-link":		// thumbnail with link to post
				case "thumbnail-link-self":	// thumbnail with link to attachment page

					$out = get_the_post_thumbnail( $custom_id, 'thumbnail' ); break;

				case "thumbnail-url": $res = wp_get_attachment_image_src( get_post_thumbnail_id($custom_id), 'thumbnail' ); $out = $res['0']; break;

				case "tags": $out = implode(' ', wp_get_post_tags( $custom_id, array( 'fields' => 'names' ) ) ); break;

				case 'gallery' :

					// Get specific image from gallery field

					$attachment_ids = get_post_meta( $custom_id, '_custom_gallery', true );
					$attachment_ids = array_filter( explode( ',', $attachment_ids ) );

					if($num == null) { $num = '1'; }
						$out = wp_get_attachment_image( $attachment_ids[$num-1], 'full' );
					break;

				case 'excerpt' :

					$out = $current_post;

					// Get excerpt
					$excerpt = $current_post->post_excerpt;
					if( ($excerpt=='') || (is_wp_error($excerpt)) ) {
						$out = $out->post_content;
						if(($words=='') && ($length==''))
							$words='35';
					} else {
						$out = $excerpt; 
					}
					break;

				default :

					// Get other fields

					$out = get_post_meta($custom_id, $custom_field, $single=true);
					break;

			}

			if (!empty($image_class)) {
				$out = str_replace('class="', 'class="'.$image_class.' ', $out);
			}

		}

		if ($timestamp == "ms") {
			$out = $out / 1000;
		}
		if (($date_format!='') && ($custom_field!="date") && ($custom_field!="modified")) {

			// Date format for custom field

			if ($in=="timestamp") {
				$out = gmdate("Y-m-d H:i:s", $out);
			}

			if ($date_format=="true") {
				$out = mysql2date(get_option('date_format'), $out);
			} else {
				$out = mysql2date($date_format, $out);
			}
		}
/*
		if ($checkbox != '') {
			if(! empty($out) )
				$out = implode(", ", $out);
			else $out = '';
		}
*/

		/* Display labels from ACF checkbox/select field */

		if ($acf_label_out) {
			if ((!empty($out)) && function_exists('get_field_object')) {
				$all_selected = $out;
				$out = array();

				$field = get_field_object($custom_field); 

				if (!is_array($all_selected)) {
					$out = $field['choices'][ $all_selected ]; /* One selection */
				} else {
					foreach($all_selected as $selected){
						$out[] = $field['choices'][ $selected ]; /* Multiple */
					}
				}
			}
		}

		/* If output is array, just implode it to string */

		if ((!empty($out)) && is_array($out)) {
			$out = implode(", ", $out);
		}


		/*========================================================================
		 *
		 * Trim by words or characters
		 *
		 *=======================================================================*/

		if($words!='') {
			$out = wp_trim_words( $out, $words );
		}

		if($length!='') {

			$the_excerpt = $out;
			$the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images

			// Support multi-byte character code
			$out = mb_substr($the_excerpt, 0, $length, 'UTF-8');
		}


		/*========================================================================
		 *
		 * If wrapping title in link, do it after word/length trim
		 *
		 *=======================================================================*/

		switch ($custom_field) {
			case "edit-link":
				$out = '<a target="_blank" href="' . get_edit_post_link( $custom_id ) . '">' . $out . '</a>'; break;

			case "image-link":				// Link image to post
			case "thumbnail-link":			// Link thumbnail to post
			case "title-link":				// Link title to post

				$out = '<a href="' . post_permalink( $custom_id ) . '">' . $out . '</a>'; break;

			case "image-post-link-out":		// Link image to post
			case "thumbnail-post-link-out":	// Link thumbnail to post
			case "title-link-out": 			// Open link in new tab

				$out = '<a target="_blank" href="' . post_permalink( $custom_id ) . '">' . $out . '</a>'; break;

			case "image-link-self":
			case "thumbnail-link-self": // Link to image attachment page
				$url = get_attachment_link( get_post_thumbnail_id($custom_id) );
//				$url = wp_get_attachment_url( get_post_thumbnail_id($custom_id) );
				$out = '<a href="' . $url . '">' . $out . '</a>'; break;

		}		
		
		if (!empty($class))
			$out = '<div class="' . $class . '">' . $out . '</div>';

		if ($shortcode_option != 'false') {		// Shortcode
			$out = do_shortcode( $out );
		}

		if ($embed == 'true') {					// Then auto-embed
			if(isset($GLOBALS['wp_embed'])) {
				$wp_embed = $GLOBALS['wp_embed'];
				$out = $wp_embed->autoembed($out);
			}
		}

		if ($content_format == 'true') {		// Then format
			$out = wpautop( $out );
		}

		/*========================================================================
		 *
		 * Read more tag
		 *
		 *=======================================================================*/

		if (!empty($more)) {

			$until_pos = strpos($out, '<!--more-->');
			if ($until_pos!==false) {
				$out = substr($out, 0, $until_pos);
			}

			if ($more=='true') {
				$more = 'Read more';
			}

			if ($more!='none') {

				if ($link != 'false') {
					if ($field=='excerpt')
						$out .= '<br>';
	/*				if ((substr($out, -3)!='</p>') && (substr($out, -4)!='</br>'))
						$out .= '<br>';
	*/				$out .= '<a class="more-tag" href="'. get_permalink($custom_id) . '">'
							. $more . '</a>';
				} else {
					$out .= $more;
				}
			}
		}

		/* Not needed? Loop handles post status already
		if ( $status!=array("any") ) {
			$post_status = get_post_status($custom_id);
			if ( ! in_array($post_status, $status) ) {
				$out = null;
			}
		} */

		return $out;
	}


	/*========================================================================
	 *
	 * [field]
	 *
	 *=======================================================================*/

	public static function field_shortcode($atts) {

		$out = null; $rest="";

		if (isset($atts) && ( !empty($atts[0]) || !empty($atts['image'])) ) {

			if (!empty($atts['image'])) {
				$field_param = 'image="'.$atts['image'].'"';
			} else {
				$field_param = 'field="'.$atts[0].'"';
			}

			if (count($atts)>1) { // Combine additional parameters
				$i=0;
				foreach ($atts as $key => $value) {
					$rest .= " ";
					if ($i>0) $rest .= $key.'="'.$value.'"'; // Skip the first parameter
					$i++;
				}
			}

			// Pass it to [content]
			$out = do_shortcode('[content '.$field_param.$rest.']');
		}

		return $out;
	}


	/*========================================================================
	 *
	 * [taxonomy]
	 *
	 *=======================================================================*/

	public static function taxonomy_shortcode($atts) {
		$out = null; $rest="";
		if (isset($atts) && !empty($atts[0])) {

			if (count($atts)>1) {
				$i=0; $rest="";
				foreach ($atts as $key => $value) {
					$rest .= " ";
					if ($i>0) $rest .= $key.'="'.$value.'"';
					$i++;
				}
			}
			$out = do_shortcode('[content taxonomy="'.$atts[0].'"'.$rest.']');
		}
		return $out;
	}

}
