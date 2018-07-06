<?php

/*---------------------------------------------
 *
 * [content] - Display field or post content
 *
 */

new CCS_Content;

class CCS_Content {

  static $original_parameters; // Before merge with defaults
  static $parameters; // with defaults
  static $state;
  static $previous_state;

  function __construct() {

    add_ccs_shortcode( array(
      'content' => array( $this, 'content_shortcode'),
      'field' => array( $this, 'field_shortcode'),
      'taxonomy' => array( $this, 'taxonomy_shortcode'),
      'array' => array( $this, 'array_field_shortcode'),
      '-array' => array( $this, 'array_field_shortcode'),
      '--array' => array( $this, 'array_field_shortcode'),
      'array-count' => array( $this, 'array_count_shortcode'),
      'raw' => array( $this, 'do_raw'),
    ));

    self::$state = array();
    self::$state['depth'] = 0;
    self::$state['current_ids'] = array();
    self::$state['is_array_field'] = false;
  }


  /*---------------------------------------------
   *
   * Main function
   *
   */

  static function content_shortcode( $parameters ) {

    // Make these into filters
    // Use $parameters['result'] as a flag

    // TODO: apply_filter('ccs_content_before_anything')
    $result = self::before_anything( $parameters );
    if ( $result != false ) {
      return $result;
    }

    // TODO: apply_filter('ccs_content_parameters')
    $parameters = self::merge_with_defaults( $parameters );
    self::$parameters = $parameters;

    // TODO: apply_filter('ccs_content_before_query')
    $result = self::before_query( $parameters );


    // Main query
    if ( empty($result) ) {
      $result = self::run_query( $parameters );
    }

    // TODO: apply_filter('ccs_content_process_result')

    // Using self::$parameters, because it could have been modified above
    // TODO: unify self::$parameters and $parameters
    $result = self::process_result( $result, self::$parameters );

    return $result;
  }

  static function save_state() {
    self::$previous_state = self::$state;
  }

  static function restore_state() {
    self::$state = self::$previous_state;
  }


  /**
   *
   * Before anything, check for result
   *
   * @param   array   $parameters All shortcode parameters
   *
   * @return  false   Continue processing shortcode
   * @return  null    Exit shortcode with empty result
   * @return  string  Exit shortcode with result
   *
   */

  static function before_anything( $parameters ) {

    $out = false;

    //TODO: Move below to optional/wck.php

    if ( CCS_To_WCK::$state['is_wck_loaded'] ) {

      if (
        ( CCS_To_WCK::$state['is_wck_metabox_loop'] )
        ||  ( CCS_To_WCK::$state['is_wck_repeater'] )
        ||  (
            // Backward compatibility for WCK metabox parameter
            ( !empty($parameters['meta']) || !empty($parameters['metabox']) )
            && !empty($parameters['field'])
            && ($parameters['field'] !== 'author') // ??
          )
      ) {

        // For post field, get normal
        if ( ! CCS_To_WCK::$state['is_wck_post_field'] ) {

          // Get WCK field
          $out = CCS_To_WCK::wck_field_shortcode( $parameters );
          if ( $out == false ) {
            $out = null; // Force empty content
          }
        }
      }
    }

    return $out;
  }


  /*---------------------------------------------
   *
   * Merge parameters with defaults
   *
   */

  static function merge_with_defaults( $parameters ) {

    self::$original_parameters = $parameters;

    $defaults = array(

      'type' => 'any',
      'status' => 'publish',
      'name' => '',
      'id' => '',

      // Field value
      'field' => '',
      'field_key' => '', // ACF field key in the form: field_...
      'page' => '',
      'link_text' => '',
      'text' => '', // Alias for link_text..
      'custom' => '', // Skip predefined field names
      'glue' => ', ', // If field is array, implode with this separator

      // Taxonomy value

      'taxonomy' => '',
      'term' => '', 'term_name' => '',
      'out' => '', // out="slug" taxonomy slug

      // Image field
      'image' => '',
      'size' => 'full', // Default
      'in' => '', // ID, url or object
      'return' => '',
      'alt' => '',
      'title' => '',
      'height' => '', 'width' => '',
      'image_class' => '',
      'nopin' => '',
      'url' => '', // Option for image-link
      'cropped' => '', // ACF cropped image field

      // Author meta
      'meta' => '',

      // Checkbox value
      'checkbox' => '',

      // Sidebar/widget area
      'area' => '', 'sidebar' => '',

      // Menu
      'menu' => '', 'ul' => '', 'cb' => '', 'menu_slug' => '',

      // Gallery
      'gallery' => 'false', 'group' => '',

      // Native gallery options

      'orderby' => '', 'order' => '', 'columns' => '',
      'include' => '', 'exclude' => '',

      // ACF gallery
      'row' => '', 'sub' => '',
      'acf_gallery' => '', 'num' => '',

      // ACF date field
      'acf_date' => '',

      // ACF option page field
      'option' => '',

      // Site option field
      'site' => '',

      // Read more
      'more' => '', 'link' => '', 'dots' => 'false',
      'between' => 'false',

      // Get property from field object
      'property' => '',

      // Formatting

      'format' => '',
      'slugify' => '',
      'shortcode' => '',
      'escape' => '', 'unescape' => '', 'json' => '',
      'filter' => '',
      'texturize' => '',
      'import' => '',
      'embed' => '',
      'http' => '', // Add http:// if not there
      'https' => '', // Add https:// if not there
      'nl' => '', // Remove \r and \n
      'align' => '', 'class' => '', 'height' => '',

      'date_format' => '', 'timestamp' => '',
      'new' => '', // Set true to open link in new tab - currently only for download-link

      'words' => '', 'len' => '', 'length' => '', 'sentence' => '',
      'html' => '', // Set true to allow HTML tags when trimming by length
      'word' => '', // Set true to trim by length to last word

      'until' => '', // Trim until certain character(s)

      'markdown' => '',

      'currency' => '',
      'decimals' => '',
      'point' => '',
      'thousands' => ''
    );


    /*---------------------------------------------
     *
     * Pre-process parameters
     *
     */

    if ( isset($parameters['type']) && ($parameters['type']=='attachment') ) {
      if (!isset($parameters['status'])) {
        $parameters['status'] = 'any'; // Default for attachment
      }
    }

    // Default size for featured image thumbnail

    $image_fields = array('thumbnail','thumbnail-link');

    if ( isset($parameters['field']) && in_array($parameters['field'],$image_fields)) {
      $parameters['size'] = isset($parameters['size']) ? $parameters['size'] : 'thumbnail';
    }

    if (!empty($parameters['acf_date'])) {
      $parameters['field'] = $parameters['acf_date'];
    }



    // Merge with defaults

    $parameters = shortcode_atts($defaults, $parameters);




    /*---------------------------------------------
     *
     * Post-process parameters
     *
     */

    // Get page by name
    if (!empty($parameters['page'])) {

      $parameters['type'] = 'page';
      $parameters['name'] = $parameters['page'];
    }

    // Post status

    if (!empty($parameters['status'])) {
      $parameters['status'] = CCS_Format::explode_list($parameters['status']); // multiple values
    }

    // ACF page link
    if (!empty($parameters['link']) && empty($parameters['more'])) {
      $parameters['field'] = $parameters['link'];
      $parameters['return'] = 'page-link';
    }

    // Image field

    if (!empty($parameters['image'])) {
      $parameters['field'] = $parameters['image'];
    }

    // Image size alias
    if ($parameters['size']=='middle') {
      $parameters['size'] = 'medium';
    }

    // ACF cropped image field
    if (!empty($parameters['cropped'])) {
      $parameters['field'] = $parameters['cropped'];
    }


    // Checkbox
    if (!empty($parameters['checkbox'])) {
      $parameters['field'] = $parameters['checkbox'];
    }


    if ( !empty($parameters['id']) ) {

      // Manually set post ID

    } elseif ( CCS_Attached::$state['is_attachment_loop'] ) {

      $parameters['id'] = CCS_Attached::$state['current_attachment_id'];

    } elseif ( CCS_Related::$state['is_related_posts_loop'] ) {

      // Inside [related]
      $parameters['id'] = CCS_Related::$state['current_related_post_id'];

    }  elseif ( class_exists('CCS_To_ACF') && CCS_To_ACF::$state['is_relationship_loop'] ) {

      // Inside ACF Relationship field
      $parameters['id'] = CCS_To_ACF::$state['relationship_id'];

    } elseif ( class_exists('CCS_To_WCK') && CCS_To_WCK::$state['is_wck_post_field'] ) {

      // Inside WCK post field
      $parameters['id'] = CCS_To_WCK::$state['current_wck_post_id'];
    }



    // HTML escape
    if ( $parameters['escape'] == 'true' && empty($parameters['shortcode']) ) {
      $parameters['shortcode'] = 'false';
    }

    // Date format: allow escape via "//" because "\" disappears in shortcode parameters
    if ( !empty($parameters['date_format']) ) {
      $parameters['date_format'] = str_replace("//", "\\", $parameters['date_format']);
    }

    // Class - support multiple
    if (!empty($parameters['class'])) {
      $parameters['class'] = str_replace( ',', ' ', $parameters['class'] );
    }

    // Site option field
    if (!empty($parameters['site'])) {
      $parameters['field'] = $parameters['site'];
      $parameters['option'] = 'site';
    }

    return $parameters;
  }



  /*---------------------------------------------
   *
   * Before query: if return is not null, there is result already
   *
   */

  static function before_query( $parameters ) {

    if ( ! CCS_Loop::$state['is_loop'] ) {
      $orig_post = get_the_ID();
    } else {
      $orig_post = '';
    }


    // Get current post

    if (empty($parameters['id'])) {

      if ( CCS_Loop::$state['is_loop'] ) {

        $post_id = CCS_Loop::$state['current_post_id']; // Current post in loop

      } else {

        $post_id = get_the_ID(); // Current post by default
      }

    } else {
      $post_id = $parameters['id'];
    }

    self::$state['current_post_id'] = $post_id;


    $result = '';

    /*---------------------------------------------
     *
     * Menu
     *
     */

    if ( !empty($parameters['menu']) || !empty($parameters['menu_slug']) ) {

      $args = array (
        'echo' => false,
        'menu_class' => $parameters['ul'],
        'container' => false, // 'div' container will not be added
        // 'fallback_cb' => $parameters['cb'], // name of default function
      );

      if ( !empty($parameters['menu']) ) {
        $args['menu'] = $parameters['menu'];
        $menu = $args['menu'];
      } elseif ( !empty($parameters['menu_slug']) ) {
        $args['theme_location'] = $parameters['menu_slug'];
        $menu = $args['theme_location'];
      }

      $result = wp_nav_menu( $args );

      if (empty($result)) {
        return '<ul class="nav"><li>'.$menu.'</li></ul>'; // Default menu
      }
      if( empty($parameters['class']) && empty($parameters['id']) ) {
        return $result;
      } else {
        $out = '<div';
        if (!empty($parameters['id'])) $out .= ' id="'.$parameters['id'].'"';
        if (!empty($parameters['class'])) $out .= ' class="'.$parameters['class'].'"';
        $out .= '>' . $result . '</div>';

        return $out;
      }

    } elseif ( !empty($parameters['sidebar']) || !empty($parameters['area']) ) {


    /*---------------------------------------------
     *
     * Sidebar or widget area
     *
     */

      if (!empty($parameters['sidebar']))
        $sidebar = $parameters['sidebar'];
      else $sidebar = $parameters['area'];

      $result =  '<div id="sidebar-' . sanitize_title($sidebar) . '"';

      if(!empty($parameters['class']))
        $result .=  ' class="' . $parameters['class'].'"';

      $result .= '>';

      ob_start();
      if ( function_exists('dynamic_sidebar') )
        dynamic_sidebar( $sidebar );
      $result .= ob_get_clean();

      $result .= "</div>";

      return $result;
    }




    // TODO: Move this to after current post is determined

    /*---------------------------------------------
     *
     * Native gallery
     *
     */

    elseif ( $parameters['gallery'] == 'native' ) {

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

      return do_ccs_shortcode(  $result );

    } elseif ( $parameters['gallery'] == 'carousel' ) {


      /*---------------------------------------------
       *
       * Gallery Bootstrap carousel
       *
       */

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

      return do_ccs_shortcode(  $result );
    }




    return $result;
  }




  /*---------------------------------------------
   *
   * Get the post
   *
   */

  static function prepare_post( $parameters = array() ) {


    // Keep track of depth in nested posts/fields
    $depth = self::$state['depth'];


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

      // Determine current post

      // TODO: Is this really necessary..?

      if ( isset(self::$state['current_ids'][ $depth ]) ) {

        // Get it from current nesting depth
        $post_id = self::$state['current_post_id'] = self::$state['current_ids'][ $depth ];

        self::$state['current_post_id'] = $post_id;
        self::$state['current_post'] = get_post($post_id);

      } elseif ( CCS_Loop::$state['is_loop'] ) {
        $post_id = CCS_Loop::$state['current_post_id'];
        self::$state['current_post_id'] = $post_id;
        self::$state['current_post'] = get_post($post_id);

      } else {

        // In global loop
        global $post;
        if (!empty($post)) {
          self::$state['current_post'] = $post;
          self::$state['current_post_id'] = $post->ID;
        } else {
          // Resort to default
          self::$state['current_post'] = get_post();
          self::$state['current_post_id'] = get_the_ID();
        }
      }
    }



// echo '### Depth: '.self::$state['depth'].' - Current ID: '.self::$state['current_post_id'].'<br>';



    if ( !empty($parameters['exclude']) && ($parameters['exclude']=='this') ) {

      // Exclude if current post ID is the same as parent
      if ( isset(self::$state['current_post_id'][ $depth - 1 ])) {
        if ( self::$state['current_post_id'] == self::$state['current_post_id'][ $depth - 1 ] ) {
          return false;
        }
      } elseif ( self::$state['current_post_id'] == get_the_ID() ) {
        return false;
      }

    }

    return true;
  }


  /*---------------------------------------------
   *
   * Main query
   *
   */

  static function run_query( $parameters ) {

    $result = '';

    if (self::prepare_post( $parameters ) == false) {

      return null; // No post by those parameters
    }


    /*---------------------------------------------
     *
     * Taxonomy
     *
     */

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

        $terms = self::get_the_terms_silently( self::$state['current_post_id'], $taxonomy );
      }


      $tax_field = !empty($parameters['field']) ? $parameters['field'] : 'name';
      // Backward compatibility
      if ( !empty($parameters['out']) ) $tax_field = $parameters['out'];
      // Remove field parameter from global to avoid confusion with custom field
      if (!empty($parameters['field'])) self::$parameters['field'] = '';

      if ( !empty( $terms ) ) {

        $slugs = array();
        if (!empty($parameters['image'])) {
          $parameters['field'] = $parameters['image'];
        }

        foreach ($terms as $term) {

          if (!is_object($term)) continue; // Invalid taxonomy

          $slugs[] = $term->slug;

          // Get taxonomy field

          switch ( $tax_field ) {
            case 'id': $results[] = $term->term_id; break;
            case 'slug': $results[] = $term->slug; break;
            case 'name': $results[] = $term->name; break;
            case 'description': $results[] = $term->description; break;
            case 'url':
              $results[] = get_term_link( $term );
            break;
            case 'link':
              $url = get_term_link( $term );
              if (!is_wp_error($url))
                $results[] = '<a href="'.$url.'">'.$term->name.'</a>';
            break;
            default:

              // Support custom taxonomy fields

              $field_value = self::get_the_taxonomy_field(
                $taxonomy, $term->term_id, $parameters['field'], $parameters
              );

              if (!empty($field_value)) {
                $results[] = $field_value;
              }

            break;
          }

        } // End for each term

        if ( $tax_field=='slug' ) {
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


    /*---------------------------------------------
     *
     * Image field
     *
     * @note Must be after taxonomy, to allow custom taxonomy image field
     *
     */

    elseif (!empty($parameters['image'])) {

      $result = self::get_image_field( $parameters );

    }


    /*---------------------------------------------
     *
     * ACF label for selected checkbox/select values
     *
     */

    elseif ( !empty($parameters['field']) && $parameters['out']=='label' ) {

      if (function_exists('get_field_object')) {

        $out = '';

        $all_selected = self::get_the_field( $parameters );

        if (!empty($all_selected)) {

          $field = get_field_object( $parameters['field'], self::$state['current_post_id'] );

          if ( isset($field['choices']) ) {

            if ( is_array($all_selected) ) {
              // Multiple selections
              foreach( $all_selected as $selected ){
                $out[] = $field['choices'][ $selected ];
              }
              $out = implode(', ', $out);
            } else {
              // Single selection
              $out = isset($field['choices'][$all_selected]) ?
                $field['choices'][$all_selected] : null;
            }

          } // End: if choices

        } // End: field not empty

        $result = $out;
      }

    }

    /*---------------------------------------------
     *
     * ACF field label
     *
     */

    elseif ( !empty($parameters['field']) && $parameters['out']=='field-label' ) {
      if (function_exists('get_field_object')) {
        $obj = get_field_object( $parameters['field'] );
        if (isset($obj['label'])) $result = $obj['label'];
      }
    }

    /*---------------------------------------------
     *
     * Field
     *
     * NOTE: Must be after taxonomy, to allow custom taxonomy field
     *
     */

    elseif ( !empty($parameters['field']) || !empty($parameters['field_key'])) {

      if (!empty($parameters['field_key'])) {
        // ACF field key

        if (!function_exists('get_field_object')) return;

        $result = get_field_object( $parameters['field_key'], self::$state['current_post_id'] );

        // It returns an array
        // https://www.advancedcustomfields.com/resources/get_field_object/

        // Get field value by default
        if (empty($parameters['field'])) $result = $result['value'];
        else $result = $result[ $parameters['field'] ];

      } else {
        // Predefined or custom field
        $result = self::get_the_field( $parameters, self::$state['current_post_id'] );
      }

      // Do shortcode by default, except for some predefined fields
      $exceptions = array('title');
      if ( !in_array( $parameters['field'], $exceptions ) ) {
        self::$parameters['shortcode'] = empty(self::$parameters['shortcode']) ?
          'true' : self::$parameters['shortcode'];
      }

      // Import to current post by default when running shortcodes inside
      self::$parameters['import'] = empty(self::$parameters['import']) ?
        'true' : self::$parameters['import'];

    } elseif ( !empty(self::$state['current_post']) ) {

      /*---------------------------------------------
       *
       * Show post content - [content]
       *
       * TODO: How to detect and avoid infinite loop
       *
       */

      $result = self::$state['current_post']->post_content;

      // Do shortcode by default
      self::$parameters['shortcode'] = empty(self::$parameters['shortcode']) ?
        'true' : self::$parameters['shortcode'];

      // Format post content by default - except when trimmed
      if ( empty($parameters['words']) && empty($parameters['length']) ) {

        self::$parameters['format'] = empty(self::$parameters['format']) ?
          'true' : self::$parameters['format'];
      }
    }

    return $result;
  }


  /*---------------------------------------------
   *
   * Process each result: formatting, etc.
   *
   */

  static function process_result( $result, $parameters ) {

    // If it's an array, make it a string

    if ( is_array($result) ) {
      $result = implode( $parameters['glue'], $result);
    }

    // Support qTranslate Plus
    $result = self::check_translation( $result );


    if ( $parameters['slugify']=='true' ) {
      $result = sanitize_title($result);
    }



    /*---------------------------------------------
     *
     * Time/date
     *
     */

    // Format ACF date field

    if (!empty($parameters['acf_date'])) {
      if ( function_exists('get_field') ) {
        $result = get_field( $parameters['field'], $post_id = false, $format_value = false );
      }
    }

    if (!empty($parameters['timestamp']) && ($parameters['timestamp']=='ms') ) {
      $result = $result / 1000;
    }

    if ( !empty($parameters['date_format']) && !empty($parameters['field'])
      && ($parameters['field']!='date') && ($parameters['field']!='modified') ) {

      // Date format for custom field
      if ( !empty($parameters['in']) && ($parameters['in']=="timestamp") &&
        // Check if it's really a timestamp
        is_numeric($result) ) {
        $result = gmdate("Y-m-d H:i:s", $result);
      }

      if ($parameters['date_format']=='true')
        $parameters['date_format'] = get_option('date_format');

      $result = mysql2date($parameters['date_format'], $result);

    }

    // ACF cropped image field
    if (!empty($parameters['cropped'])) {

      // Get attachment ID
      $result = json_decode( $result, true );
      $result = $result['cropped_image'];

      // Attachment field
      $return = !empty($parameters['return']) ? $parameters['return'] : 'image';
      $result = do_shortcode('[attached-field '.$return.' id='.$result.']');
    }


    /*---------------------------------------------
     *
     * Trim by words or characters
     *
     */

    if ($parameters['dots']=='false') {
      $parameters['dots'] = false;
    } elseif ($parameters['dots']=='true') {
      $parameters['dots'] = '&hellip;';
    }

    if (!empty($parameters['words'])) {

      $result = self::process_shortcodes( $result, $parameters );
      $parameters['shortcode'] = 'false';

/*
      if ($parameters['html']=='true') {
        $result = CCS_Format::trim_with_tags(
          $result, $parameters['words'], $parameters['dots'], $words=true
        );
      } else {

        // TODO: Combine with above
*/
        if (intval($parameters['words']) < 0) {

          // Remove X words from beginning and return the rest

          // If format, do it before content gets trimmed
          if ( $parameters['format'] == 'true' || $parameters['html'] == 'true' ) {

            $whole_result = CCS_Format::trim_words_with_tags( $result, 9999, '' );

            if ( $parameters['format'] == 'true' ) $result = wpautop( $result );

            $result = CCS_Format::trim_words_with_tags(
              $result, 0 - $parameters['words'], $parameters['dots']
            );

          } else {
            $whole_result = wp_trim_words( $result, 9999, '' );
            $result = wp_trim_words( $result, 0 - $parameters['words'], '' );
          }

          // Offset and get the rest
          $result = substr($whole_result, strlen($result));

        } else {

          // If format, do it before content gets trimmed

          if ( $parameters['format'] == 'true' || $parameters['html'] == 'true' ) {

            if ( $parameters['format'] == 'true' ) $result = wpautop( $result );

            $result = CCS_Format::trim_words_with_tags(
              $result, $parameters['words'], $parameters['dots']
            );
          } else {

            $result = wp_trim_words( $result, $parameters['words'], $parameters['dots'] );

          }
        }

//      }

    }

    // Trim by length
    if ( !empty($parameters['length']) ) {

      $result = self::process_shortcodes( $result, $parameters );
      $parameters['shortcode'] = 'false';

      if ($parameters['html']=='true') {

        $result = CCS_Format::trim_with_tags( $result, $parameters['length'],
          $parameters['dots'] );

      } else {

        $result = strip_tags( $result );
        $add_dots = strlen($result) > intval($parameters['length']);

        // Support multi-byte character code
        $result = mb_substr($result, 0, $parameters['length'], 'UTF-8');

        if ($add_dots) $result .= $parameters['dots'];
      }
    }

    // Trim to last sentence or word
    if ( $parameters['sentence']=='true' || $parameters['word']=='true' ) {

      $len = strlen($result);
      $ends = array( '.', '?', '!' );
      if ($parameters['word']=='true') $ends = array_merge( $ends, array(' ', ',') );

      for ($i=$len-1; $i >= 0; $i--) {
        if ( in_array($result[$i], $ends) ) {
          break; // Found the end
        } else {
          // Trim each character
          $result = substr($result, 0, -1);
        }
      }
    }

    // Trim until certain characters
    if (!empty($parameters['until'])) {
      $parts = explode($parameters['until'], $result);
      $result = $parts[0];

      // Include delimiter if found
      $result .= isset($parts[1]) ? $parameters['until'] : '';
    }

    /*---------------------------------------------
     *
     * Escape/unescape HTML and shortcodes
     *
     */

    if ( $parameters['escape'] == 'true' ) {
      $result = esc_html($result);
      $result = str_replace( array('[',']'), array('&#91;','&#93;'), $result );
      if (empty($parameters['shortcode'])) $parameters['shortcode'] = 'false';
    }

    if ( $parameters['json'] == 'true' ) {
      $result = str_replace( array('"'), array('\\"','\\"'), $result );
    }

    if ( $parameters['markdown'] == 'true' && class_exists('Markdown_Module')) {
      $result = esc_html($result);
      $result = str_replace( array('[',']'), array('&#91;','&#93;'), $result );
      $result = Markdown_Module::render( $result );
      $parameters['shortcode'] = 'false';
      $parameters['format'] = 'false';
    }

    if ( $parameters['unescape'] == 'true' ) {
      $result = str_replace( array('&#91;','&#93;'), array('[',']'),
        htmlspecialchars_decode($result));
    }

    /*---------------------------------------------
     *
     * Wrap in link
     *
     */


    if ( $parameters['custom']=='true' ) {
      // Skip predefined fields
      $field = $parameters['field'];
      $parameters['field'] = 'custom';
    }

    $post_id = !empty(self::$state['current_post_id']) ? self::$state['current_post_id'] : get_the_ID();

    $link_text_fields = array(
      'link', 'edit-link', 'edit-link-self', 'title-link', 'title-link-out'
    );
    if ( in_array( $parameters['field'], $link_text_fields ) ) {
      if ( !empty($parameters['link_text']) )
        $parameters['text'] = $parameters['link_text'];
      if ( !empty($parameters['text']) ) {
        $result = $parameters['text'];
      }
    }

    $att = '';
    if ( !empty($parameters['link_id']) ) $att .= ' id="'.$parameters['link_id'].'"';
    if ( !empty($parameters['link_class']) ) $parameters['class'] = $parameters['link_class'];
    if ( !empty($parameters['class']) ) $att .= ' class="'.$parameters['class'].'"';

    switch ($parameters['field']) {

      case "edit-link":

        $url = isset(self::$state['current_link_url']) ?
          self::$state['current_link_url'] : get_edit_post_link( $post_id );

        $result = '<a '.$att.' target="_blank" href="' . $url . '">' . $result . '</a>';

      break;

      case "edit-link-self":

        $url = isset(self::$state['current_link_url']) ?
          self::$state['current_link_url'] : get_edit_post_link( $post_id );

        $result = '<a '.$att.' href="' . $url . '">' . $result . '</a>';

      break;

      case "image-link":        // Link image to post
      case "thumbnail-link":      // Link thumbnail to post
      case "title-link":        // Link title to post
      case "link":        // Link to post

        // Menu items are already links
        if ( ! CCS_Menu::$state['is_menu_loop'] ) {

          $url = isset(self::$state['current_link_url']) ?
            self::$state['current_link_url'] : get_permalink( $post_id );

          $label = $result;

          if (!empty($parameters['title'])) {
            $url .= '" title="'.esc_html( $parameters['title']=='true' ?
              get_the_title( $post_id ) : $parameters['title']);
          }

          $result = '<a '.$att.' href="' . $url . '">' . $label . '</a>';
        }

      break;

      case "image-post-link-out":   // Link image to post
      case "thumbnail-post-link-out": // Link thumbnail to post
      case "title-link-out":      // Open link in new tab

        $url = isset(self::$state['current_link_url']) ?
          self::$state['current_link_url'] : get_permalink( $post_id );

        $result = '<a '.$att.' target="_blank" href="' . $url . '">' . $result . '</a>';

      break;

      case "image-link-self":
      case "thumbnail-link-self": // Link to image attachment page

        $url = isset(self::$state['current_link_url']) ?
          self::$state['current_link_url'] :
          get_attachment_link( get_post_thumbnail_id( $post_id ) );

        $result = '<a '.$att.' href="' . $url . '">' . $result . '</a>';

      break;

      // Not a link
      default:

        if (!empty($parameters['class']))
          $result = '<div class="' . $parameters['class'] . '">' . $result . '</div>';

        if ( $parameters['http'] == 'true' ) {
          if ( !empty($result) && substr($result, 0, 4) !== 'http' )
            $result = 'http://'.$result;
        } elseif ( $parameters['https'] == 'true' ) {
          if ( !empty($result) && substr($result, 0, 4) !== 'http' )
            $result = 'https://'.$result;
        }
      break;
    }


    if ( $parameters['custom']=='true' ) {
      // Restore original
      $parameters['field'] = $field;
    }



    // Auto-embed
    if ($parameters['embed'] == 'true') {

      if (isset($GLOBALS['wp_embed'])) {
        $wp_embed = $GLOBALS['wp_embed'];
        $result = $wp_embed->autoembed($result);
        // Run [audio], [video] in embed
        $result = do_shortcode( $result );
      } else {
        // Doesn't work with URL to uploads
        $result = wp_oembed_get($result);
      }
    }


    // Do shortcode before formatting

    if ( $parameters['shortcode'] == 'true' ) {

      $result = self::process_shortcodes( $result, $parameters );

    } else {

      // Gets passed to global do_shortcode..

      // TODO: How to protect field value?
      // This won't work if it's inside HTML attribute..
      // $result = '[direct]'.$result.'[/direct]';
    }

    // Provide filter for external modules
    $result = apply_filters( 'ccs_content', $result );

    // Then the_content filter or format

    if ($parameters['filter']=='true') {

      // Attempt to support SiteOrigin Page Builder
      add_filter( 'siteorigin_panels_filter_content_enabled',
        array(__CLASS__, 'siteorigin_support') );

      $result = apply_filters( 'the_content', $result );

      // And clean up
      remove_filter( 'siteorigin_panels_filter_content_enabled',
        array(__CLASS__, 'siteorigin_support') );

    } else {

      if ($parameters['format'] == 'true' && empty($parameters['words'])) {

        if (function_exists('ccs_raw_format'))
          $result = ccs_raw_format( $result, false );
        else {
          $result = wpautop( $result );
        }
      }
      $result = str_replace( array('[raw]','[/raw]'), '', $result );
    }

    if ($parameters['texturize']=='true') {
      $result = wptexturize( $result );
    }
    if ($parameters['nl']=='true') {
      $result = trim(preg_replace('/\s+/', ' ', $result));
    }




    /*---------------------------------------------
     *
     * Read more tag
     *
     */

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

        if ($parameters['link'] == 'false') {

          $result .= $more;

        } else {
          if (empty($parameters['between']))
            $result .= '<br>';
          elseif ($parameters['between']!='false')
            $result .= $parameters['between'];

          $result .= '<a class="more-tag" href="'. get_permalink($post_id) . '">'
            . $more . '</a>';
        }
      }
    }


    // End

    return $result;
  }


  static function process_shortcodes( $content, $parameters ) {

    if ($parameters['shortcode'] == 'false') {
      return strip_shortcodes($content);
    }

    $depth = ++self::$state['depth'];
    if ( $parameters['import'] != 'true' ) {
      // Set post ID for shortcodes inside
      self::$state['current_ids'][$depth] = self::$state['current_post_id'];
    }

    if (!empty($content)) $content = do_ccs_shortcode( $content );

    self::$state['depth']--;
    if ( $parameters['import'] != 'true' ) {
      unset(self::$state['current_ids'][$depth]);
    }


    return $content;
  }



  /*---------------------------------------------
   *
   * Field
   *
   */

  static function get_the_field( $parameters, $id = null ) {

    $result = '';

    $field = $parameters['field'];

    if ( !empty($parameters['id']) ) {
      $id = $parameters['id'];
    }


    /*---------------------------------------------
     *
     * Option field
     *
     */

    if ( !empty($parameters['option']) ) {
      return self::get_option_field( $field, $parameters['option'] );
    }


    /*---------------------------------------------
     *
     * Attachment field
     *
     */

    if ( (!empty($parameters['type']) && $parameters['type']=='attachment') ||
      CCS_Loop::$state['is_attachment_loop'] || // gallery field
      CCS_Attached::$state['is_attachment_loop'] ) {

      return self::get_the_attachment_field( $parameters );

    /*---------------------------------------------
     *
     * Array field
     *
     */

    } elseif ( self::$state['is_array_field'] ) {

      $array = self::$state['current_field_value'];

      if (isset( $array[$field] ) ) {
        return $array[$field];
      } elseif ($field=='value') {
        if (is_array($array)) {
          // TODO: Use $glue to implode?
          $array = implode('', $array);
        }
        return $array;
      }


    // ACF gallery loop

    } elseif ( class_exists('CCS_To_ACF') &&
        CCS_To_ACF::$state['is_gallery_loop'] ) {


      return CCS_To_ACF::get_image_details_from_acf_gallery( $parameters );

    /*---------------------------------------------
     *
     * Repeater or flexible content loop
     *
     */

   } elseif ( empty( $parameters['id'] ) && empty( $parameters['name'] )
      && class_exists('CCS_To_ACF') && CCS_To_ACF::$state['is_repeater_or_flex_loop'] ) {

      // If not inside relationship loop
      if ( ! CCS_To_ACF::$state['is_relationship_loop'] ) {

        // Get sub field
        if (function_exists('get_sub_field')) {
          return get_sub_field( $field );
        } else return null;
      }

    /*---------------------------------------------
     *
     * Menu loop
     *
     */

    } elseif ( CCS_Menu::$state['is_menu_loop'] ) {

      if (isset(CCS_Menu::$state['current_menu_object'][$field])) {

        return CCS_Menu::$state['current_menu_object'][$field];

      } else {

        if ( ! isset(CCS_Menu::$state['current_menu_object']['id'])) return;

        // Get it from the post

        $id = CCS_Menu::$state['current_menu_object']['id'];
        CCS_Menu::$state['is_menu_loop'] = false;

        $result = do_shortcode('[field '.$field.' id='.$id.']');

        CCS_Menu::$state['is_menu_loop'] = true;
        return $result;
      }
    }

    if ( !empty( $id ) ) {
      $post_id = $id;
    } else {
      // Default
      $post_id = get_the_ID();
/*      global $post;
      if (!empty($post)) {
        $post_id = $post->ID;
      } */
    }

    if (empty($post_id)) return null; // No post ID

    $post = get_post($post_id);

    if (empty($post)) return null; // No post

    /*---------------------------------------------
     *
     * Prepare image attributes
     *
     */

    $image_fields = array('image','image-full','image-link','image-link-self',
      'thumbnail','thumbnail-link','thumbnail-link-self','gallery');

    if ( $field=='thumbnail' && empty($parameters['size']) ) {
      $parameters['size'] = 'thumbnail'; // Default thumbnail
    }

    $attr = array();

    if (in_array($field, $image_fields)) {

      if (!empty($parameters['width'])) {

        if (empty($parameters['size'])) {

          if (empty($parameters['height'])) $parameters['height'] = $parameters['width'];
          $parameters['size'] = array(
            intval($parameters['width']), intval($parameters['height'])
          );
        }
        else {

          // A workaround to support setting size and width/height separately
          // Example: size=large width=400

          $attr['style'] = 'width:'.$parameters['width']
            .( is_numeric($parameters['width']) ? 'px' : '' )
            .( ! empty($parameters['height']) ?
              '; height:'.$parameters['height']
                .( is_numeric($parameters['height']) ? 'px' : '' )
              : ''
            );
        }
      }
      if (!empty($parameters['image_class']))
        $attr['class'] = $parameters['image_class'];
      if (!empty($parameters['nopin']))
        $attr['nopin'] = $parameters['nopin'];
      if (!empty($parameters['alt']))
        $attr['alt'] = $parameters['alt'];
//      if (!empty($parameters['title']))
//        $attr['title'] = $parameters['title'];
    }

    // Custom field only?
    if (!empty($parameters['custom']) && $parameters['custom']=='true') {
      $custom = $field;
      $field = 'custom'; // Skip predefined fields
    }

    /*---------------------------------------------
     *
     * Pre-defined fields
     *
     */

    switch ($field) {

      case 'id': $result = $post_id; break;
      case 'url': $result = get_permalink( $post_id ); break;
      case 'edit-url': $result = get_edit_post_link( $post_id ); break;
      case 'edit-link':
        $result = $post->post_title; break;
      case 'edit-link-self':
        $result = $post->post_title; break;
      case 'slug': $result = $post->post_name; break;
      case 'post-type': $result = $post->post_type; break;
      case 'post-type-name': $post_type = $post->post_type;
                             $obj = get_post_type_object( $post_type );
                             $result = $obj->labels->singular_name; break;
      case 'post-type-plural': $post_type = $post->post_type;
                         $obj = get_post_type_object( $post_type );
                         $result = $obj->labels->name; break;
      case 'post-status':
        $result = $post->post_status;
        if ($parameters['out'] !== 'slug') {
          $result = ucwords($result);
        }
        break;

      case 'post-class':
        $result = implode(' ', get_post_class());
      break;

      case 'post-format':
        if (function_exists( 'get_post_format' )) $result = get_post_format($post_id);
      break;

      case 'post-format-name':
        if (function_exists( 'get_post_format' )) {
          $result = get_post_format($post_id);
          $result = ucwords($result);
        }
      break;
      case 'post-format-link':
        if (function_exists( 'get_post_format' )) {
          $result = get_post_format($post_id);
          $result = '<a href="'.get_post_format_link($result).'">'.ucwords($result).'</a>';
        }
      break;
      case 'post-format-url':
        if (function_exists( 'get_post_format' )) {
          $result = get_post_format_link(get_post_format($post_id));
        }
      break;


      case 'parent-id':
        $parent_id = isset($post->post_parent) ? $post->post_parent : 0;
        if (!empty($parent_id)) $result = $parent_id;
      break;

      case 'parent-slug':
        $parent_id = isset($post->post_parent) ? $post->post_parent : 0;
        if (!empty($parent_id)) {
          $post_data = get_post($parent_id);
          if (!empty($post_data)) {
            $result = isset($post_data->post_name) ? $post_data->post_name : '';
          }
        }
      break;


      case 'link':
      case 'title-link':
      case 'title-link-out':
      case 'title':
        $result = $post->post_title;
      break;

      case 'author':

        $author_id = $post->post_author;
        $user = get_user_by( 'id', $author_id);

        if ( !empty($parameters['meta']) )
          $result = get_the_author_meta( $parameters['meta'], $author_id );
        elseif (!empty($user)) $result = $user->display_name;

      break;

      case 'author-id':
        $result = $post->post_author;
      break;

      case 'author-url':
        $result = get_author_posts_url($post->post_author);
      break;

      case 'author-login':
        $author_id = $post->post_author;
        $result = do_shortcode('[users id='.$author_id.'][user name][/users]');
      break;

      case 'avatar':
        if( !empty($parameters['size']) )
          $result = get_avatar($post->post_author, $parameters['size']);
        else
          $result = get_avatar($post->post_author);
      break;

      case 'date':
        if (!empty($parameters['date_format'])) {
          if ($parameters['date_format']=='relative') {
            $result = CCS_Format::get_relative_date( $post->post_date );
          } else {
            $result = mysql2date($parameters['date_format'], $post->post_date);
          }
        }
        else { // Default date format under Settings -> General
          $result = mysql2date(get_option('date_format'), $post->post_date);
        }
      break;

      case 'modified':
        if (!empty($parameters['date_format'])) {
          if ($parameters['date_format']=='relative') {
            // format, gmt, id, translate
            $modified_date = get_post_modified_time( 'Y-m-d H:i:s', false, $post_id, false );
            $result = CCS_Format::get_relative_date( $modified_date );
          } else {
            $result = get_post_modified_time( $parameters['date_format'], false, $post_id, true );
          }
        }
        else {
          // Default date format under Settings -> General
          $result = get_post_modified_time( get_option('date_format'), false, $post_id, true );
        }
      break;

      case 'image-full':
        $parameters['size'] = 'full';
      case 'image':       // image
      case 'image-link':      // image with link to post
      case 'image-link-self':   // image with link to attachment page
        $parameters['size'] = (isset($parameters['size']) && !empty($parameters['size'])) ?
          $parameters['size'] : 'full';

        if (empty($attr['alt'])) {
          $attr['alt'] = self::wp_get_featured_image_field( $post_id, 'alt' );
        }

        $result = get_the_post_thumbnail( $post_id, $parameters['size'], $attr );
        break;

      case 'image-url':
        $parameters['size'] = (isset($parameters['size']) && !empty($parameters['size'])) ?
          $parameters['size'] : 'full';
        $src = wp_get_attachment_image_src(
          get_post_thumbnail_id($post_id),
          $parameters['size']
        );
        $result = $src['0'];
        // $result = wp_get_attachment_url(get_post_thumbnail_id($post_id));
      break;

      case 'image-title':
      case 'image-caption':
      case 'image-alt':
      case 'image-description':
        $image_field_name = substr($field, 6); // Remove "image-"
        $result = self::wp_get_featured_image_field( $post_id, $image_field_name );
      break;

      case 'thumbnail':     // thumbnail
      case 'thumbnail-link':    // thumbnail with link to post
      case 'thumbnail-link-self': // thumbnail with link to attachment page
        $parameters['size'] = (isset($parameters['size']) && !empty($parameters['size'])) ?
          $parameters['size'] : 'thumbnail';

        if (empty($attr['alt'])) {
          $attr['alt'] = self::wp_get_featured_image_field( $post_id, 'alt' );
        }

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
      case 'gallery-url' :

        // Get specific image from gallery field

        if (class_exists('CCS_Gallery_Field')) { // Check if gallery field is enabled

          $attachment_ids = CCS_Gallery_Field::get_image_ids( $post_id );

          if (empty($parameters['num'])) $parameters['num'] = 1;
          if (empty($parameters['size'])) $parameters['size'] = 'full';

          if (isset($attachment_ids[ $parameters['num']-1 ])) {
            $id = $attachment_ids[ $parameters['num']-1 ];

            if ($field == 'gallery-url') {
              $src = wp_get_attachment_image_src( $id, $parameters['size'] );
              $result = $src['0'];
            } else {

              $result = wp_get_attachment_image(
                $id, $parameters['size'], $icon=false, $attr
              );
            }
          }
        }

      break;

      case 'excerpt' :

        // Get excerpt

        //$result = get_the_excerpt();
        $result = $post->post_excerpt;

        if( empty($result) ) {

          // If empty, get it from post content

          // TODO: Is this necessary?

          $result = $post->post_content;

          if (empty($parameters['words']) && empty($parameters['length'])) {
            self::$parameters['words'] = 25;
          }
        }

        // Remove content after read more tag
        $parts = explode('<!--more-->', $result);
        $result = isset($parts[0]) ? $parts[0] : '';

        $result = apply_filters('get_the_excerpt', $result);

      break;

      case 'after-excerpt' :

        // Get content after read more tag

        $parts = explode('<!--more-->', $post->post_content);
        $result = isset($parts[1]) ? $parts[1] : '';
      break;

      case 'debug' :
        ob_start();
        echo '<pre>'; print_r( get_post_custom($post_id) ); echo '</pre>';
        if (function_exists('acf_get_fields_by_id')) {
          echo '<pre>'; print_r( acf_get_fields_by_id($post_id) ); echo '</pre>';
        }
        $result = ob_get_clean();
      break;


      case 'loop-count' :
        $result = CCS_Loop::$state['loop_count'];
      break;


      default :

        /*---------------------------------------------
         *
         * Custom field
         *
         */

        if (!empty($parameters['custom']) && $parameters['custom']=='true') {
          $field = $custom;
        }

        $result = get_post_meta($post_id, $field, true);

        if ( is_numeric($result) && !empty($parameters['return']) ) {

          if ($parameters['return']=='page-link') {

            // ACF page link: get URL from post ID
            $result = get_permalink( $result );

          } else {

            // Get attachment field

            $parameters['id'] = $result;
            $parameters['field'] = $parameters['return'];

            $result = self::get_the_attachment_field($parameters);
          }

        } elseif (!empty($parameters['property']) && is_object($result) ) {

          $result = self::get_object_property($result, $parameters['property']);

        } elseif (
          !empty($parameters['currency']) ||
          !empty($parameters['decimals']) ||
          !empty($parameters['point']) ||
          !empty($parameters['thousands'])) {

          $currency = !empty($parameters['currency']) ? $parameters['currency'] : '';
          $decimals = !empty($parameters['decimals']) ? $parameters['decimals'] : 2;
          $point = !empty($parameters['point']) ? $parameters['point'] : '.';
          $thousands = !empty($parameters['thousands']) ? $parameters['thousands'] : ',';

          $result = CCS_Format::getCurrency($result,
            $currency, $decimals, $point, $thousands);
        }


        break;
    }

    return $result;

  } // End get_the_field


  /*---------------------------------------------
   *
   * Attachment field
   *
   */

  static function get_the_attachment_field( $parameters ) {


    // TODO: Improve getting current post

    if (!empty($parameters['id'])) {
      $post_id = $parameters['id'];
    } elseif (CCS_Attached::$state['is_attachment_loop']) {
      $post_id = CCS_Attached::$state['current_attachment_id'];
    } elseif (CCS_Loop::$state['is_loop']) {
      $post_id = CCS_Loop::$state['current_post_id'];
    } elseif (isset(self::$state['current_post_id'])) {
      $post_id = self::$state['current_post_id'];
    } else {
      $post_id = get_the_ID();
    }

    if (empty($post_id)) return; // Needs attachment ID


    $post = get_post($post_id);


    if (empty($parameters['size'])) {
      $parameters['size'] = 'full';
    }

    $field = $parameters['field'];
    $result = '';


    /*---------------------------------------------
     *
     * Prepare image attributes
     *
     * @todo *** Refactor ***
     *
     */

    $image_fields = array('image','thumbnail');

    $attr = array();

    if (in_array($field, $image_fields)) {
      if (!empty($parameters['width']) && !empty($parameters['height']))
        $parameters['size'] = array($parameters['width'], $parameters['height']);
      if (!empty($parameters['image_class']))
        $attr['class'] = $parameters['image_class'];
      if (!empty($parameters['nopin']))
        $attr['nopin'] = $parameters['nopin'];
      if (!empty($parameters['alt']))
        $attr['alt'] = $parameters['alt'];
      if (!empty($parameters['title']))
        $attr['title'] = $parameters['title'];
    }

    switch ($field) {
      case 'id':
        $result = $post_id;
        break;
      case 'alt':
        $result = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
        break;
      case 'caption' :
        $result = $post->post_excerpt;
        break;
      case 'description' :
        $result = $post->post_content;
        break;
      case 'url' :
        $src = wp_get_attachment_image_src( $post_id, $parameters['size'] );
        if (isset($src[0]) && !empty($src[0])) {
          $result = $src[0];
        } else {
          $result = wp_get_attachment_url( $post_id );
        }
        break;
      case 'download-url' :
        $result = wp_get_attachment_url( $post_id );
        break;
      case 'download-link' :
        $target = '';
        if ( $parameters['new'] == 'true' ) {
          $target = 'target="_blank" ';
        }
        $result = '<a '.$target.'href="'.wp_get_attachment_url( $post_id ).'" download>'.$post->post_title.'</a>';
        break;
      case 'page-url' :
      case 'href' : $result = get_permalink( $post_id );
        break;
      case 'src' : $result = $post->guid;
        break;
      case 'title' : $result = $post->post_title;
        break;
      case 'title-link' :
      case 'title-link-out' :
        $src = wp_get_attachment_image_src( $post_id, $parameters['size'] );
        if (isset($src[0]) && !empty($src[0])) {
          $result = $src[0];
        } else {
          $result = wp_get_attachment_url( $post_id );
        }
        self::$state['current_link_url'] = $result;
        $result = $post->post_title;
      break;
      case 'image' :
        $result = wp_get_attachment_image(
          $post_id, $parameters['size'], $icon = false, $attr
        );
        break;
      case 'image-url' :
        $src = wp_get_attachment_image_src( $post_id, $parameters['size'] );
        if (isset($src[0]) && !empty($src[0])) {
          $result = $src[0];
        } else {
          $result = wp_get_attachment_url( $post_id );
        }
        break;
      case 'thumbnail' :
        $result = wp_get_attachment_image(
          $post_id, 'thumbnail', $icon = false, $attr
        );
        break;
      case 'thumbnail-url' : $result = wp_get_attachment_thumb_url( $post_id ) ;
        break;
      default:
        break;
    }

    return $result;
  }




  /*---------------------------------------------
   *
   * Image field
   *
   */

  static function get_image_field( $parameters ) {

    $result = '';

    $post_id = self::$state['current_post_id'];
    $image_id = 0;

    if (class_exists('CCS_To_ACF') && CCS_To_ACF::$state['is_repeater_or_flex_loop'] ) {

      // Repeater or flexible content field: then get sub field

      if (function_exists('get_sub_field')) {
        $field = get_sub_field( $parameters['image'] );
      } else return null;
    } else {
      $field = get_post_meta( $post_id, $parameters['image'], true );
    }

    /*---------------------------------------------
     *
     * Prepare image attributes
     *
     * @todo Refactor
     *
     */

    $attr = array();
    if (!empty($parameters['width']) || !empty($parameters['height']))
      $parameters['size'] = array($parameters['width'], $parameters['height']);
    if (!empty($parameters['image_class']))
      $attr['class'] = $parameters['image_class'];
    if (!empty($parameters['nopin']))
      $attr['nopin'] = $parameters['nopin'];
    if (!empty($parameters['alt']))
      $attr['alt'] = $parameters['alt'];
    if (!empty($parameters['title']))
      $attr['title'] = $parameters['title'];

    switch($parameters['in']) {

      case 'array' :
      case 'object' : // ACF image object

        if (is_array( $field )) {
          $image_id = $field['id'];
        } else {
          $image_id = $field; // Assume it's ID
        }

        $result = wp_get_attachment_image( $image_id , $parameters['size'], $icon=false, $attr );

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
          if (!empty($parameters['nopin']))
            $result .= ' nopin="' . $parameters['nopin'] . '"';
          $result .= '>';
        }
        break;
      case 'id' : // Default is attachment ID for the image
      default :

        if (is_array($field)) {
          $image_id = $field['id']; // If it's an array, assume image object
        } else {
          $image_id = $field;
        }
        $result = wp_get_attachment_image( $image_id, $parameters['size'], $icon=false, $attr );
        break;
    }

    if ( $parameters['return']=='url' ) {

      $size = !empty($parameters['size']) ? $parameters['size'] : 'full';
      $image_info = wp_get_attachment_image_src( $image_id, $size );
      return isset($image_info) ? $image_info[0] : null;

    } else if ( !empty($parameters['return']) ) {

      return self::wp_get_attachment_field( $image_id, $parameters['return'] );

    } else {

      if (!empty($parameters['class'])) {
        $result = '<div class="' . $parameters['class'] . '">' . $result . '</div>';
      }

      return $result;
    }

  }


  /*---------------------------------------------
   *
   * Taxonomy field
   *
   */

  static function get_the_taxonomy_field(
    $taxonomy, $term_id, $field, $parameters = array() ) {

    $value = '';

    // ACF
    if (function_exists('get_field')) {

      $value = get_field( $field, $taxonomy.'_'.$term_id );

      if (!isset($parameters['in'])) $parameters['in']='object';
      if (is_array($value)) {
        // Assume image..?
        $parameters['image'] = $field;
      }


    // Which plugin defines get_tax_meta..?
    } elseif (function_exists('get_tax_meta')) {

      $value = get_tax_meta( $term_id, $field );

      if (!isset($parameters['in'])) $parameters['in']='id';

    } /* elseif (function_exists('get_terms_meta')) {

      // https://wordpress.org/plugins/custom-taxonomy-category-and-term-fields/installation/

      $value = get_terms_meta($term_id, $field );
      if (is_array($value) && isset($value[0])) {
        // Assume image
        $parameters['image'] = $field;
        $parameters['in']='url';
        $value = $value[0];
      }
    } */

    // Image field
    if ( !empty($parameters['image']) ) {

      if ( empty($parameters['size']) ) $parameters['size']='full';

      switch($parameters['in']) {
        case 'id' :
          $parameters['id'] = $value;
          $value = wp_get_attachment_image( $value, $parameters['size'] ); break;
        case 'url' : $value = '<img src="' . $value . '">'; break;
        case 'object' : /* image object */
        default :
          if (is_array($value)) {

            $parameters['id'] = $value['id'];
            $value = wp_get_attachment_image( $value['id'], $parameters['size'] );
          } else {
            $value = wp_get_attachment_image( $value, $parameters['size'] ); // Assume it's ID
          }
      }

      if ( !empty($parameters['out']) && !empty($parameters['id'])) {

        $parameters['field'] = $parameters['out'];
        $value = self::get_the_attachment_field( $parameters );
      }
    }

    return $value;
  }

  static function get_option_field( $field, $option = 'site' ) {

    if ( ( $option=='true' || $option=='acf' ) &&
        function_exists('get_field') ) {
      // From ACF option page
      return get_field( $field, 'option' );
    }

    // Aliases
    if ($field == 'name' ) $field = 'blogname';
    elseif ($field == 'description' ) $field = 'blogdescription';

    return get_option( $field );
  }



/*---------------------------------------------
 *
 * Other shortcodes
 *
 */


  /*---------------------------------------------
   *
   * [field]
   *
   */

  static function field_shortcode($atts) {

    $rest='';

    if (!isset($atts) || !is_array($atts)) return;

    foreach ($atts as $key => $value) {
      // Coerce number key to string to avoid false match
      if (is_numeric($key)) $key = strval($key);

      switch ($key) {
        case 'site':
          $atts['field'] = $value;
          $atts['option'] = 'site';
          unset($atts[$key]);
        break;
        case 'key':
          $atts['field_key'] = $value;
          unset($atts[$key]);
        break;
        case '0': // First param with no value
          $atts['field'] = $value;;
          unset($atts[$key]);
        break;
      }
    }

    return self::content_shortcode($atts);
  }


  /*---------------------------------------------
   *
   * [taxonomy]
   *
   */

  static function taxonomy_shortcode($atts) {
    $out = '';
    $rest = '';
    if (isset($atts) && !empty($atts[0])) {

      if (count($atts)>1) {
        $i=0; $rest='';
        foreach ($atts as $key => $value) {
          $rest .= ' ';
          if ($i>0) $rest .= $key.'="'.$value.'"';
          $i++;
        }
      }
      $out = do_ccs_shortcode( '[content taxonomy="'.$atts[0].'"'.$rest.']' );
    }
    return $out;
  }


  /*---------------------------------------------
   *
   * [array]
   *
   */

  static function array_field_shortcode( $atts, $content, $shortcode_name ) {

    $out = null;
    $array = null;
    $prev_state = self::$state;
    $prefix = CCS_Format::get_minus_prefix($shortcode_name);

    extract( shortcode_atts( array(
      'field' => '',
      'user_field' => '',
      'each'  => 'false', // Loop through each array
      'debug' => 'false', // Print array for debug purpose
      'global' => '',
      'json' => '', // json object/array
      'choices' => '', // Get choices of ACF field
      'type' => '',
      'name' => '', // Needed for choices
      'trim' => '',
      'slugify' => '',
      'glue' => '',
    ), $atts ) );

    if (!empty($global)) {
      $field = 'GLOBAL';
    } elseif (!empty($choices)) {
      $field = $choices;
    } elseif ( isset($atts) && !empty($atts[0]) ) {
      $field = $atts[0];
    }

    // Inside ACF repeater/flex
    if ( class_exists('CCS_To_ACF') &&
      CCS_To_ACF::$state['is_repeater_or_flex_loop'] &&
//      ! CCS_To_ACF::$state['is_relationship_loop' ) &&
      $field != 'GLOBAL' ) {

      // Get sub field
      if (function_exists('get_sub_field'))
        $array = get_sub_field( $field );

    } else {

      if ( $field == 'GLOBAL' ) {

        $array = $GLOBALS[$global];
        if (!is_array($array)) {
          $array = array('value'=>$array);
        }

      } elseif ( !empty($choices) ) {

        // ACF checkbox/select/radio choices

        // Needs field key

        if ( substr($choices, 0, 6) == 'field_' ) {
          $key = $choices;
        } else {
          $cmd = '[loop';
          if (!empty($name)) {
            $cmd .= ' name="'.$name.'"';
          }
          else {
            if (empty($type)) {
              $type = get_post_type();
              if (!$type) $type = 'post';
            }
            $cmd .= ' type='.$type;
          }

          $key = do_ccs_shortcode(  $cmd.' count=1][field _'.$choices.'][/loop]');
        }

        $field = get_field_object( $key );

        if ($debug=='true') {
          $array = $field;
        } else {

          $array = array();
          if ( $field ) {
            foreach ($field['choices'] as $key => $value) {
              $array[] = array(
                'value' => $key,
                'label' => $value
              );
            }
            $each = 'true';
          }
        }

      // User field
      } elseif (!empty($user_field)) {

        $array = CCS_User::get_user_field( $user_field );

      // Normal field
      } else {

        if ( self::$state['is_array_field'] ) {
          // Nested array
          $array = self::$state['current_field_value'];
          $array = isset($array[ $field ]) ? $array[ $field ] : '';
        } else {
          $id = do_shortcode('[field id]');
          $array = get_post_meta( $id, $field, true );
        }
      }

      // Not array
      if ( !empty($array) && !is_array($array)) {
        // See if it's an ACF field
        if (function_exists('get_field')) {
          $array = get_field( $field );
        }
      }
    }

    if ( $json == 'true' ) {
      $array = json_decode($array, true);
    }

    if ( $debug == 'true' ) {
      $out = self::print_array($array, false);
    }


    if ( !empty($array) && is_array($array) ) {

      self::$state['is_array_field'] = true;
      self::$state['array_field_index'] = 0;

      if ( $each != 'true' ) {
        $array = array($array); // Create a single array
      }

      self::$state['array_field_count'] = count($array);

      foreach ( $array as $each_array ) {

        self::$state['current_field_value'] = $each_array;
        self::$state['array_field_index']++; // Starts from 1

        $this_content = $content;

        // Replace {TAG}

        // TODO: Deprecate in favor of explicitly using [pass]

        if ( !empty($choices) ) {
          $this_content = str_replace('{VALUE}', @$each_array['value'], $content);
          $this_content = str_replace('{LABEL}', @$each_array['label'], $this_content);
        }
        $this_content = str_replace(
          '{'.$prefix.'ARRAY_INDEX}', self::$state['array_field_index'], $this_content);


        if ($slugify=='true') $this_content = '[slugify]'.$this_content.'[/slugify]';

        $out .= do_ccs_shortcode(  $this_content );

        if ( !empty($glue)
            // not last item
            && self::$state['array_field_index'] < self::$state['array_field_count'] )
          $out .= $glue;
      }

      self::$state['is_array_field'] = false;

    } else {

      $out = $array; // Empty or not array
    }

    if (is_array($out)) {
      $out = '[ Array ]';
    } elseif ( $trim == 'true' ) {
      $out = trim($out, " \t\n\r\0\x0B,");
    }

    self::$state = $prev_state;

    return $out;
  }




  function array_count_shortcode() {

    return self::$state['array_field_index'];
  }



  /*---------------------------------------------
   *
   * Utilities
   *
   */

  static function wp_get_attachment_array( $attachment_id ) {

    $attachment = get_post( $attachment_id );
    return array(
      'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
      'caption' => $attachment->post_excerpt,
      'description' => $attachment->post_content,
      'href' => get_permalink( $attachment->ID ),
      'src' => $attachment->guid,
      'title' => $attachment->post_title
    );
  }

  static function wp_get_attachment_field( $attachment_id, $field_name ) {

    if (empty($attachment_id)) return null;

    $attachment = get_post( $attachment_id );
    $attachment_array = self::wp_get_attachment_array( $attachment_id );

    if (isset($attachment_array[$field_name])) {
      return $attachment_array[$field_name];
    } else {
      return null;
    }
  }

  static function wp_get_featured_image_field( $post_id, $field_name ) {

    // Get featured image ID from post ID
    $attachment_id = get_post_thumbnail_id( $post_id );
    return self::wp_get_attachment_field( $attachment_id, $field_name );
  }




  // Helper for getting property from field object
  static function get_object_property($object, $prop_string, $delimiter = '->') {
    $prop_array = explode($delimiter, $prop_string);
    foreach ($prop_array as $property) {
      if (isset($object->{$property}))
        $object = $object->{$property};
      else
        return;
    }
    return $object;
  }

  // Helper for getting field including predefined
  static function get_prepared_field( $field, $id = null ) {

    return self::get_the_field( array('field' => $field), $id );
  }


  // For debug purpose: Print an array in a human-readable format
  static function print_array( $array, $echo = true ) {

    if ( !$echo ) ob_start();
    echo '<pre>';
      print_r( $array );
    echo '</pre>';
    if ( !$echo ) return ob_get_clean();
  }


	// Compatibility with WP 4.4

	// Prevent get_the_terms() from throwing notices/warnings when
	// the taxonomy is not valid
  static function get_the_terms_silently( $post_id = 0, $taxonomy = '' ) {

    if ( empty($post_id) ) $post_id = get_the_ID();
    if ( empty($post_id) || empty($taxonomy) ) return false;

		$terms = get_object_term_cache( $post_id, $taxonomy );
		if ( false === $terms ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy );
			if ( $terms instanceOf WP_Error )
				return false;
		}

    return get_the_terms( $post_id, $taxonomy );
  }




  /*---------------------------------------------
   *
   * Get all shortcode attributes including empty
   *
   * [shortcode param]
   * [shortcode param="value"]
   *
   */

  static function get_all_atts( $atts ) {
    $new_atts = array();
    $indexes = array();
    if (is_array($atts) && count($atts)>0) {
      foreach ($atts as $key => $value) {
        if (is_numeric($key)) {
          if ( ! isset($new_atts[$value]) )
            $new_atts[$value] = true;
          else {
            if ( !isset($indexes[$value]) )
              $indexes[$value] = 2;
            else $indexes[$value]++;
            $new_atts[ $value . '__' . $indexes[$value] ] = true;
          }
        } else {
          $new_atts[$key] = $value;
        }
      }
    }
    return $new_atts;
  }


  /*---------------------------------------------
   *
   * Support qTranslate Plus
   *
   */

  static function check_translation( $text ) {

    if ( !isset(self::$state['ppqtrans_exists']) ) {
      // Check only once and store result
      self::$state['ppqtrans_exists'] = function_exists('ppqtrans_use');
    }

    if ( self::$state['ppqtrans_exists'] ) {
      global $q_config;
      return ppqtrans_use($q_config['language'], $text, false);
    }

    return $text;
  }


  static function siteorigin_support() { return true; }

  function noop(){}
  function do_raw( $attr, $content ){ return do_ccs_shortcode($content); }

} // End CCS_Content
