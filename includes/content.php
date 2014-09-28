<?php

/*====================================================================================================
 *
 * [content] - Get a field or post content
 *
 *====================================================================================================*/

new CCS_Content;

class CCS_Content {

	public static $original_parameters; // Before merge with defaults
	public static $parameters; // with defaults
	public static $state;

	function __construct() {

		add_shortcode( 'content', array($this, 'content_shortcode') );
		add_shortcode( 'field', array($this, 'field_shortcode') );
		add_shortcode( 'taxonomy', array($this, 'taxonomy_shortcode') );
	}


	/*========================================================================
	 *
	 * Main function
	 *
	 *=======================================================================*/

	function content_shortcode( $parameters ) {

		$parameters = $this->merge_with_defaults( $parameters );
		self::$parameters = $parameters;

		$result = $this->before_query( $parameters );

		if (empty($result)) {

			$result = $this->run_query( $parameters );
		}

		$result = $this->process_result( $result, self::$parameters );

		return $result;
	}

	/*========================================================================
	 *
	 * Merge parameters with defaults
	 *
	 *=======================================================================*/

	function merge_with_defaults( $parameters ) {

		self::$original_parameters = $parameters;

		$defaults = array(

			'type' => 'any',
			'status' => 'publish',
			'name' => '',
			'id' => '',

			// Field value
			'field' => '',

			'page' => '',

			// Taxonomy value

			'taxonomy' => '',
			'term' => '', 'term_name' => '',
			'out' => '', // out="slug" taxonomy slug

			// Image field
			'image' => '',
			'size' => 'full', // Default
			'in' => '', // ID, url or object
			'return' => '',
			'alt' => '', 'title' => '',
			'height' => '', 'width' => '', 
			'image_class' => '',
			'url' => '', // Option for image-link

			// Author meta
			'meta' => '',

			// Checkbox value
			'checkbox' => '',

			// Sidebar/widget area
			'area' => '', 'sidebar' => '', 

			// Menu
			'menu' => '', 'ul' => '',

			// ACF gallery
			'row' => '', 'sub' => '',
			'acf_gallery' => '', 'num' => '',

			// Gallery
			'gallery' => 'false', 'group' => '',

			// Native gallery options

			'orderby' => '', 'order' => '', 'columns' => '',
			 'include' => '', 'exclude' => '',

			// Read more
			'more' => '', 'link' => 'true', 'dots' => 'true',
			'between' => 'false',


			// Fomatting

			'format' => '', 'shortcode' => '',
			'embed' => '',
			'align' => '', 'class' => '', 'height' => '',
			'words' => '', 'len' => '', 'length' => '',
			'date_format' => '', 'timestamp' => '',
		);

		
		/*========================================================================
		 *
		 * Pre-process parameters
		 *
		 *=======================================================================*/
		
		if ( isset($parameters['type']) && ($parameters['type']=='attachment') ) {
			if (!isset($parameters['status'])) {
				$parameters['status'] = 'any'; // Default for attachment
			}
		}

		// Default size for featured image and image link

		$image_fields = array('image','image-link','thumbnail','thumbnail-link');

		if ( isset($parameters['field']) && in_array($parameters['field'],$image_fields)) {
			$parameters['size'] = isset($parameters['size']) ? $parameters['size'] : 'thumbnail';
		}



		// Merge with defaults

		$parameters = shortcode_atts($defaults, $parameters);


		/*========================================================================
		 *
		 * Post-process parameters
		 *
		 *=======================================================================*/
		
		// Get page by name
		if (!empty($parameters['page'])) {

			$parameters['type'] = 'page';
			$parameters['name'] = $parameters['page'];
		}
		
		// Post status

		if (!empty($parameters['status'])) {
			$parameters['status'] = CCS_Loop::explode_list($parameters['status']); // multiple values
		}

		// Image field

		if (!empty($parameters['image'])) {
			$parameters['field'] = $parameters['image'];
		}

		// Image size alias
		if ($parameters['size']=='middle')
			$parameters['size'] = 'medium';

		// Checkbox
		if (!empty($parameters['checkbox']))
			$parameters['field'] = $parameters['checkbox'];



		// Relationship loop

		if (class_exists('CCS_To_ACF') && CCS_To_ACF::$state['is_relationship_loop']=='true') {

			$parameters['id'] = CCS_To_ACF::$state['relationship_id']; // Get related post
		}

		return $parameters;
	}



	/*========================================================================
	 *
	 * Before query: if return is not null, there is result already
	 *
	 *=======================================================================*/

	function before_query( $parameters ) {

		if (empty($parameters['id'])) {
			$post_id = get_the_ID();
		} else {
			$post_id = $parameters['id'];
		}

		self::$state['current_post_id'] = $post_id;

		$result = '';


		/*========================================================================
		 *
		 * Menu
		 *
		 *=======================================================================*/

		if (!empty($parameters['menu'])) {

			$menu_args = array (
				'menu' => $parameters['menu'],
				'echo' => false,
				'menu_class' => $parameters['ul'],
			);

			$result = wp_nav_menu( $menu_args );

			if(empty($parameters['class'])) {
				return $result;
			} else {
				return '<div class="' . $parameters['class'] . '">' . $result . '</div>';
			}

		} elseif ( !empty($parameters['sidebar']) || !empty($parameters['area']) ) {

		/*========================================================================
		 *
		 * Sidebar or widget area
		 *
		 *=======================================================================*/

			if (!empty($parameters['sidebar']))
				$sidebar = $parameters['sidebar'];
			else $sidebar = $parameters['area'];

			$result =  '<div id="sidebar-' . str_replace( " ", "_", strtolower($sidebar)) . '"';


			if(!empty($parameters['class']))
				$result .=  ' class="' . $parameters['class'].'"';

			$result .= '>';

			ob_start();
			if ( function_exists('dynamic_sidebar') )
				dynamic_sidebar($parameters['sidebar']);
			$result .= ob_get_clean();
			$result .= "</div>";

			return $result;
		}






		/*========================================================================
		 *
		 * Native gallery
		 *
		 *=======================================================================*/

		elseif ( $parameters['gallery'] == 'native') {

			$result = '[gallery " ';

			if(!empty($parameters['name'])) {
				$result .= 'name="' . $parameters['name'] . '" ';
			}

			$result .= 'ids="';

			if (!empty($parameters['acf_gallery'])) {
				if( function_exists('get_field') ) {
					$result .= implode(',', get_field($parameters['acf_gallery'], $post_id, false));
				}
			} else {
				$result .= get_post_meta( $post_id, '_custom_gallery', true );
			}
			$result .= '"';

			/* Additional parameters */

			$native_gallery_options = array(
				'orderby' => $parameters['orderby'],
				'order' => $parameters['order'],
				'columns' => $parameters['columns'],
				'size' => $parameters['size'],
				'link' => $parameters['link'],
				'include' => $parameters['include'],
				'exclude' => $parameters['exclude']
			);

			if (!empty($parameters['columns']))
				$parameters['columns'] = ''; // prevent CCS columns

			foreach ($native_gallery_options as $option => $value) {

				if (!empty($value)) {
					$result .= ' ' . $option . '="' . $value . '"';
				}
			}

			$result .= ']';

			if(!empty($parameters['class']))
				$result = '<div class="' . $parameters['class'] . '">' . $result . '</div>';

			return do_shortcode( $result );

		} elseif ( $parameters['gallery'] == 'carousel' ) {


			/*========================================================================
			 *
			 * Gallery Bootstrap carousel
			 *
			 *=======================================================================*/

			$result = '[gallery type="carousel" ';

			if (!empty($parameters['name'])) {
				$result .= 'name="' . $parameters['name'] . '" ';
			}
			if (!empty($parameters['height'])!='') {
				$result .= 'height="' . $parameters['height'] . '" ';	
			}
			$result .= 'ids="';

			if(!empty($parameters['acf_gallery'])) {
				if( function_exists('get_field') ) {
					$result .= implode(',', get_field($parameters['acf_gallery'], $post_id, false));
				}
			} else {
				$result .= get_post_meta( $post_id, '_custom_gallery', true );
			}
			$result .= '" ]';

			if (!empty($parameters['class']))
				$result = '<div class="' . $class . '">' . $result . '</div>';
			
			return do_shortcode( $result );
		}


		return $result;
	}

	/*========================================================================
	 *
	 * Get the post
	 *
	 *=======================================================================*/

	function prepare_post( $parameters ) {
		
		// Get post from ID

		if (!empty($parameters['id'])) {

			$this_post = get_post( $parameters['id'] );

			if (empty($this_post)) return false; // No post by that ID

			self::$state['current_post'] = $this_post;
			self::$state['current_post_id'] = $parameters['id'];

		} elseif (!empty($parameters['name'])) {

			// Get post from name

			$args=array(
				'name' => $parameters['name'],
				'post_type' => $parameters['type'],
				'post_status' => $parameters['status'], // Default is publish, or any for attachment
				'posts_per_page' => '1',
	  		);

			$posts = get_posts($args);

			if ( $posts ) {

				self::$state['current_post'] = $posts[0];
				self::$state['current_post_id'] = $posts[0]->ID; // ID of the post

			} else {

				return false; // No post by that name
			}

		} else {

			// Current post

			self::$state['current_post'] = get_post();
			self::$state['current_post_id'] = get_the_ID();
		}

		return true;
	}


	/*========================================================================
	 *
	 * Main query
	 *
	 *=======================================================================*/

	function run_query( $parameters ) {

		$result = '';

		if (self::prepare_post( $parameters ) == false) {

			return null; // No post by those parameters
		}


		/*========================================================================
		 *
		 * Image field
		 *
		 *=======================================================================*/

		elseif (!empty($parameters['image'])) {

			$result = self::get_image_field( $parameters );

		}

		/*========================================================================
		 *
		 * Taxonomy
		 *
		 *=======================================================================*/

		elseif (!empty($parameters['taxonomy'])) {

			$results = array();
			
			if ($parameters['taxonomy'] == 'tag') {
				$taxonomy='post_tag'; // Alias
			} else {
				$taxonomy = $parameters['taxonomy'];
			}

			// Get taxonomy term by ID, slug or name

			if (!empty($parameters['term'])) {
				if (is_numeric($parameters['term'])) {
					// By term ID
					$terms = get_term_by('id', $parameters['term'], $taxonomy);
				} else {
					// By term slug
					$terms = get_term_by('slug', $parameters['term'], $taxonomy);
				}
				$terms = array($terms); // Single term
			} elseif (!empty($parameters['term_name'])) {
					// By term name
					$terms = get_term_by('name', $parameters['term_name'], $taxonomy);
					$terms = array($terms); // Single term
			} else {

				// Default: get all taxonomy terms of current post

				$terms = get_the_terms( self::$state['current_post_id'], $taxonomy );
			}

			if ( !empty( $terms ) ) {

				foreach ($terms as $term) {

					$slugs[] = $term->slug;

					if (!empty($parameters['field'])) {

						// Get taxonomy field

						switch ($parameters['field']) {
							case 'id':
								$results[] = $term->term_id;
								break;
							case 'slug':
								$results[] = $term->slug;
								break;
							case 'name':
								$results[] = $term->name;
								break;
							case 'description':
								$results[] = $term->description;
								break;
							default:

								// Support custom taxonomy meta fields
								 
								if (function_exists('get_tax_meta')) {
									$field_value = get_tax_meta($term->term_id,$parameters['field']);
									if (!empty($field_value)) {
										$results[] = $field_value;
									}
								}

								break;
						}
					} else {
						$results[] = $term->name; // Default: taxonomy name
					}

				} // End for each term

				if ( $parameters['out'] == 'slug') { // Backward compatibility
					$result = implode(' ', $slugs);
					$result = trim($result);
				} else {
					$result = implode(', ', $results);
					$result = trim($result, " \t\n\r\0\x0B,");
				}
			} else {
				return null; // No terms found
			}

		}


		/*========================================================================
		 *
		 * ACF checkbox/select label
		 *
		 *=======================================================================*/
		
		elseif ( !empty($parameters['field']) && ($parameters['out']=='label') ) {

			if (function_exists('get_field_object')) {

				$all_selected = self::get_the_field( $parameters );
				$out = array();

				if (!empty($all_selected)) {

					$field = get_field_object($parameters['field']); 

					if (!is_array($all_selected)) {
 						// One selection
						$out = isset($field['choices'][$all_selected]) ?  $field['choices'][$all_selected] : null;
					} else {
						foreach($all_selected as $selected){
							$out[] = $field['choices'][ $selected ]; /* Multiple */
						}
						$out = implode(', ', $out);
					}
				}
				$result = $out;
			}
		}



		/*========================================================================
		 *
		 * Field
		 *
		 *=======================================================================*/
		
		elseif (!empty($parameters['field'])) {

			$result = self::get_the_field( $parameters );
		
		} else {

		/*========================================================================
		 *
		 * Show post content - [content]
		 * 
		 *=======================================================================*/

			// Make sure no parameters are set
//			if (count(self::$original_parameters)==0) {

				$result = self::$state['current_post']->post_content;
//			}

		}
		return $result;
	}


	function process_result( $result, $parameters ) {

		// If it's an array, make it a list

		if (is_array($result))
			$result = implode(', ', $result);



		/*========================================================================
		 *
		 * Time/date
		 *
		 *=======================================================================*/
		
		if (!empty($parameters['timestamp']) && ($parameters['timestamp']=='ms') ) {
			$result = $result / 1000;
		}

		if ( !empty($parameters['date_format']) && !empty($parameters['field'])
			&& ($parameters['field']!='date') && ($parameters['field']!='modified') ) {

			// Date format for custom field

			if ( !empty($parameters['in']) && ($parameters['in']=="timestamp") ) {
				// Check if it's really a timestamp
				if (is_numeric($result)) {
					$result = gmdate("Y-m-d H:i:s", $result);
				}
			}

			if ($parameters['date_format']=='true') 
				$parameters['date_format'] = get_option('date_format');

			$result = mysql2date($parameters['date_format'], $result);

		}


		/*========================================================================
		 *
		 * Trim by words or characters
		 *
		 *=======================================================================*/

		if (!empty($parameters['words'])) {

			if (!empty($parameters['dots'])) {
				if ($parameters['dots']=='false')
					$parameters['dots'] = false;
				elseif ($parameters['dots']=='true')
					$parameters['dots'] = '&hellip;'; // default

				$result = wp_trim_words( $result, $parameters['words'], $parameters['dots'] );
			}
			else
				$result = wp_trim_words( $result, $parameters['words'] );

		}

		if (!empty($parameters['length'])) {

			$result = strip_tags(strip_shortcodes($result)); //Strips tags and images

			// Support multi-byte character code
			$result = mb_substr($result, 0, $parameters['length'], 'UTF-8');
		}

		/*========================================================================
		 *
		 * Wrap in link
		 *
		 *=======================================================================*/

		$post_id = isset(self::$state['current_post_id']) ? self::$state['current_post_id'] : get_the_ID();
		
		switch ($parameters['field']) {

			case "edit-link":
				$result = '<a target="_blank" href="' . get_edit_post_link( $post_id ) . '">' . $result . '</a>';
				break;

			case "image-link":				// Link image to post
			case "thumbnail-link":			// Link thumbnail to post
			case "title-link":				// Link title to post

				$result = '<a href="' . post_permalink( $post_id ) . '">' . $result . '</a>';
				break;

			case "image-post-link-out":		// Link image to post
			case "thumbnail-post-link-out":	// Link thumbnail to post
			case "title-link-out": 			// Open link in new tab

				$result = '<a target="_blank" href="' . post_permalink( $post_id ) . '">' . $result . '</a>';
				break;

			case "image-link-self":
			case "thumbnail-link-self": // Link to image attachment page
				$url = get_attachment_link( get_post_thumbnail_id( $post_id ) );
//				$url = wp_get_attachment_url( get_post_thumbnail_id($custom_id) );
				$result = '<a href="' . $url . '">' . $result . '</a>';
				break;

		}

		// Class

		if (!empty($parameters['class']))
			$result = '<div class="' . $parameters['class'] . '">' . $result . '</div>';

		// Shortcode

		if ($parameters['shortcode'] != 'false') {		// Shortcode
			$result = do_shortcode( $result );
		}

		// Auto-embed links

		if ($parameters['embed'] == 'true') {					// Then auto-embed
			if(isset($GLOBALS['wp_embed'])) {
				$wp_embed = $GLOBALS['wp_embed'];
				$result = $wp_embed->autoembed($result);
			}
		}

		// Then format

		if ($parameters['format'] == 'true') {		// Then format
			$result = wpautop( $result );
		}
		
		
		/*========================================================================
		 *
		 * Read more tag
		 *
		 *=======================================================================*/

		if (!empty($parameters['more'])) {

			$until_pos = strpos($result, '<!--more-->');
			if ($until_pos!==false) {
				$result = substr($result, 0, $until_pos); // Get content until tag
			} elseif (empty($parameters['field'])) {

				// If post content has no read-more tag, trim it

				if (empty($parameters['words']) && empty($parameters['length'])) {
					// It hasn't been trimmed yet
					if (!empty($parameters['dots'])) {
						if ($parameters['dots']=='false')
							$parameters['dots'] = false;
						elseif ($parameters['dots']=='true')
							$parameters['dots'] = '&hellip;'; // default

						$result = wp_trim_words( $result, 25, $parameters['dots'] );
					}
					else
						$result = wp_trim_words( $result, 25 );
				}
			}

			if ($parameters['more']=='true') {
				$more = 'Read more';
			} else {
				$more = $parameters['more'];
			}

			if ($more!='none') {

				if ($parameters['link'] != 'false') {
//					if ($parameters['field']=='excerpt') {

						if (empty($parameters['between']))
							$result .= '<br>';
						elseif ($parameters['between']!='false')
							$result .= $parameters['between'];

	/*				if ((substr($out, -3)!='</p>') && (substr($out, -4)!='</br>'))
						$out .= '<br>';
	*/
						$result .= '<a class="more-tag" href="'. get_permalink($post_id) . '">'
							. $more . '</a>';

//					}
				} else {
					$result .= $more;
				}
			}
		}
		
		return $result;
	}


/*========================================================================
 *
 * Helper functions
 *
 *=======================================================================*/


	
	/*========================================================================
	 *
	 * Image field
	 *
	 *=======================================================================*/

	function get_image_field( $parameters ) {

		$result = '';

		$post_id = self::$state['current_post_id'];

		$field = get_post_meta( $post_id, $parameters['image'], true );


		/*========================================================================
		 *
		 * Prepare image attributes
		 *
		 *=======================================================================*/

		$attr = array();
		if (!empty($parameters['width']) || !empty($parameters['height']))
			$parameters['size'] = array($parameters['width'], $parameters['height']);
		if (!empty($parameters['image_class']))
			$attr['class'] = $parameters['image_class'];
		if (!empty($parameters['alt']))
			$attr['alt'] = $parameters['alt'];
		if (!empty($parameters['title']))
			$attr['title'] = $parameters['title'];

		switch($parameters['in']) {

			case 'object' : // ACF image object

				if (is_array( $field )) {
					$image_id = $field['id'];
				} else {
					$image_id = $field; // Assume it's ID
				}

				$result = wp_get_attachment_image( $image_id , $parameters['size'], $icon=0, $attr );

				break;

			case 'url' :

				if ( $parameters['return']=='url' ) {

					$result = $field;

				} else {

					$result = '<img src="' . $field . '"';
					if (!empty($parameters['image_class']))
						$result .= ' class="' . $parameters['image_class'] . '"';
					if (!empty($parameters['alt']))
						$result .= ' alt="' . $parameters['alt'] . '"';
					if (!empty($parameters['height']))
						$result .= ' height="' . $parameters['height'] . '"';
					if (!empty($parameters['width']))
						$result .= ' width="' . $parameters['width'] . '"';
					$result .= '>';
				}
				break;
			case 'id' : 
			default :

				$image_id = $field;
				$result = wp_get_attachment_image( $image_id, $parameters['size'], $icon=0, $attr );
				break;
		}

		if ($parameters['return']=='url') {

			$image_info = wp_get_attachment_image_src( $image_id, 'full' );
			return isset($image_info) ? $image_info[0] : null;

		} else {

			if (!empty($parameters['class'])) {
				$result = '<div class="' . $parameters['class'] . '">' . $result . '</div>';
			}

			return $result;
		}

	}


	/*========================================================================
	 *
	 * Field
	 *
	 *=======================================================================*/
	
	
	function get_the_field( $parameters ) {

		if ( $parameters['type']=='attachment' ||
			CCS_Loop::$state['is_attachment_loop'] ||
			CCS_Attached::$state['is_attachment_loop'] ) {

			return self::get_the_attachment_field( $parameters );
		}
/*
		$post_id = !empty($parameters['id']) ? $parameters['id'] : get_the_ID();

		if ($post_id == self::$state['current_post_id']) {
			$post = self::$state['current_post'];
		} else {
			$post = get_post($post_id);
			self::$state['current_post'] = $post;
			self::$state['current_post_id'] = $post_id;
		}
*/
		$post = self::$state['current_post'];
		$post_id = self::$state['current_post_id'];

		$field = $parameters['field'];
		$result = '';


		/*========================================================================
		 *
		 * Prepare image attributes
		 *
		 *=======================================================================*/
		
		$image_fields = array('image','image-full','image-link','image-link-self',
			'thumbnail','thumbnail-link','thumbnail-link-self','gallery');

		if ($field=='thumbnail')
			$parameters['size'] = 'thumbnail';

		$attr = array();

		if (in_array($field, $image_fields)) {

			if (!empty($parameters['width']) || !empty($parameters['height']))
				$parameters['size'] = array((int)$parameters['width'], (int)$parameters['height']);
			if (!empty($parameters['image_class']))
				$attr['class'] = $parameters['image_class'];
			if (!empty($parameters['alt']))
				$attr['alt'] = $parameters['alt'];
			if (!empty($parameters['title']))
				$attr['title'] = $parameters['title'];
		}


		/*========================================================================
		 *
		 * Pre-defined fields
		 *
		 *=======================================================================*/

		switch ($field) {

			case 'id': $result = $post_id; break;
			case 'url': $result = post_permalink( $post_id ); break;
			case 'edit-url': $result = get_edit_post_link( $post_id ); break;
			case 'edit-link': $result = apply_filters( 'the_title', $post->post_title ); break;
			case 'slug': $result = $post->post_name; break;

			case 'title-link':
			case 'title-link-out':
			case 'title': $result = apply_filters( 'the_title', $post->post_title ); break;

			case 'author':

				$author_id = $post->post_author;
				$user = get_user_by( 'id', $author_id);

				if ( !empty($parameters['meta']) )
					$result = get_the_author_meta( $parameters['meta'], $author_id );
				else
					$result = $user->display_name;
				break;

			case 'author-id':

				$result = $post->post_author; break;

			case 'author-url':

				$result = get_author_posts_url($post->post_author); break;

			case 'avatar': 
				if( !empty($parameters['size']) )
					$result = get_avatar($post->post_author, $parameters['size']);
				else
					$result = get_avatar($post->post_author);
				break;

			case 'date':

				if (!empty($parameters['date_format'])) {
					$result = mysql2date($parameters['date_format'], $post->post_date);
				}
				else { // Default date format under Settings -> General
					$result = mysql2date(get_option('date_format'), $post->post_date);
				}
				break;

			case 'modified':

				if (!empty($parameters['date_format'])) {
					$result = get_post_modified_time( $parameters['date_format'], $gmt=false, $post_id, $translate=true );
				}
				else { // Default date format under Settings -> General
					$result = get_post_modified_time( get_option('date_format'), $gmt=false, $post_id, $translate=true );
				}
				break;

			case 'image-full':
				$parameters['size'] = 'full';
			case 'image':				// image
			case 'image-link':			// image with link to post
			case 'image-link-self':		// image with link to attachment page

				$result = get_the_post_thumbnail( $post_id, $parameters['size'], $attr );
				break;
				
			case 'image-url':
				$result = wp_get_attachment_url(get_post_thumbnail_id($post_id));
				break;

			case 'thumbnail':			// thumbnail
			case 'thumbnail-link':		// thumbnail with link to post
			case 'thumbnail-link-self':	// thumbnail with link to attachment page

				$result = get_the_post_thumbnail( $post_id, $parameters['size'], $attr );
				break;

			case 'thumbnail-url':
				$src = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'thumbnail' );
				$result = $src['0'];
				break;

			case 'tags':
				$result = implode(' ', wp_get_post_tags( $post_id, array( 'fields' => 'names' ) ) );
				break;

			case 'gallery' :

				// Get specific image from gallery field

				if (class_exists('CCS_Gallery_Field')) { // Check if gallery field is enabled

					$attachment_ids = CCS_Gallery_Field::get_image_ids( $post_id );

					if (empty($parameters['num']))
						$parameters['num'] = 1;
					if (empty($parameters['size']))
						$parameters['size'] = 'full';

					$result = wp_get_attachment_image( $attachment_ids[$parameters['num']-1], $parameters['size'], $icon=0, $attr );
				}

				break;

			case 'excerpt' :

				// Get excerpt

//				$result = get_the_excerpt();
				$result = $post->post_excerpt;

				if( empty($result) ) { // If empty, get it from post content
					$result = $post->post_content;
					if (empty($parameters['words']) && empty($parameters['length'])) {
						self::$parameters['words'] = 25;
					}

				}
				break;

			default :

				/*========================================================================
				 *
				 * Custom field
				 *
				 *=======================================================================*/

				$result = get_post_meta($post_id, $field, true);

				break;
		}


		return $result;

	} // End get_the_field



	/*========================================================================
	 *
	 * Attachment fields
	 *
	 *=======================================================================*/

	function get_the_attachment_field( $parameters ) {


		if (!empty($parameters['id'])) {
			$post_id = $parameters['id'];
		} elseif (CCS_Loop::$state['is_attachment_loop']) {
			$post_id = CCS_Loop::$state['current_post_id'];
		} elseif (CCS_Attached::$state['is_attachment_loop']) {
			$post_id = CCS_Attached::$state['current_attachment_id'];
		} /* else {
			$post_id = self::$state['current_post_id'];
		} */

		if (empty($post_id)) return; // Needs attachment ID
/*
		if ($post_id == self::$state['current_post_id']) {
			$post = self::$state['current_post'];
		} else {
			$post = get_post($post_id);
		}
*/
		$post = get_post($post_id);

		if (empty($parameters['size']))
			$parameters['size'] = 'full';

		$field = $parameters['field'];
		$result = '';


		/*========================================================================
		 *
		 * Prepare image attributes
		 *
		 *=======================================================================*/
		
		$image_fields = array('image','thumbnail');

		$attr = array();

		if (in_array($field, $image_fields)) {
			if (!empty($parameters['width']) && !empty($parameters['height']))
				$parameters['size'] = array($parameters['width'], $parameters['height']);
			if (!empty($parameters['image_class']))
				$attr['class'] = $parameters['image_class'];
			if (!empty($parameters['alt']))
				$attr['alt'] = $parameters['alt'];
			if (!empty($parameters['title']))
				$attr['title'] = $parameters['title'];
		}

		switch ($field) {
			case 'id': $result = $post_id; break;
			case 'alt': $result = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
				break;
			case 'caption' : $result = $post->post_excerpt;
				break;
			case 'description' : $result = $post->post_content;
				break;
			case 'url' :
			case 'href' : $result = get_permalink( $post_id );
				break;
			case 'src' : $result = $post->guid;
				break;
			case 'title' : $result = $post->post_title;
				break;
			case 'image' : $result = wp_get_attachment_image( $post_id, $parameters['size'], $attr );
				break;
			case 'image-url' :
				$src = wp_get_attachment_image_src( $post_id, $parameters['size'] );
				$result = $src[0];
				break;
			case 'thumbnail' : $result = wp_get_attachment_image( $post_id, 'thumbnail', $icon = 0, $attr );;
				break;
			case 'thumbnail-url' : $result = wp_get_attachment_thumb_url( $post_id ) ;
				break;
			default:
				break;
		}

		return $result;
	}











/*========================================================================
 *
 * Other shortcodes
 *
 *=======================================================================*/


	/*========================================================================
	 *
	 * [field]
	 *
	 *=======================================================================*/

	public static function field_shortcode($atts) {

		$out = null; $rest='';

		if (isset($atts) && ( !empty($atts[0]) || !empty($atts['image'])) ) {

			if (!empty($atts['image'])) {
				$field_param = 'image="'.$atts['image'].'"';
			} else {
				$field_param = 'field="'.$atts[0].'"';
			}

			if (count($atts)>1) { // Combine additional parameters
				$i=0;
				foreach ($atts as $key => $value) {
					$rest .= ' ';
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
		$out = null; $rest='';
		if (isset($atts) && !empty($atts[0])) {

			if (count($atts)>1) {
				$i=0; $rest='';
				foreach ($atts as $key => $value) {
					$rest .= ' ';
					if ($i>0) $rest .= $key.'="'.$value.'"';
					$i++;
				}
			}
			$out = do_shortcode('[content taxonomy="'.$atts[0].'"'.$rest.']');
		}
		return $out;
	}



} // End CCS_Content
