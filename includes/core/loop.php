<?php

/*---------------------------------------------
 *
 * [loop] - Query posts and loop through each one
 *
 * TODO: Modularize function areas for better management
 *
 * Filters:
 *
 * ccs_loop_add_defaults      Additional parameters to accept
 * ccs_loop_parameters        Process given parameters
 * ccs_loop_prepare_posts     Process found posts
 * ccs_loop_each_post         Each found post
 * ccs_loop_each_result       Each compiled template result
 * ccs_loop_each_row          Each row (if columns option is set)
 * ccs_loop_all_results       Results array
 * ccs_loop_final_result      Final combined result
 *
 */

new CCS_Loop;

class CCS_Loop {

  private static $original_parameters;  // Shortcode parameters
  private static $parameters;       // After merge with default
  private static $query;          // The query
  public static $wp_query;          // WP_Query object for pagination
  public static $state;         // Loop state array
  public static $previous_state;      // For nested loop

  function __construct() {

    self::init();

    add_shortcode( 'loop', array($this, 'the_loop_shortcode') );
    add_shortcode( '-loop', array($this, 'the_loop_shortcode') );
    add_shortcode( '--loop', array($this, 'the_loop_shortcode') );

    add_local_shortcode( 'loop', 'prev', array($this, 'prev_shortcode') );
    add_local_shortcode( 'loop', 'next', array($this, 'next_shortcode') );

    // newer/older - default order DESC (new to old)
    add_local_shortcode( 'loop', 'newer', array($this, 'prev_shortcode') );
    add_local_shortcode( 'loop', 'older', array($this, 'next_shortcode') );

    add_shortcode( 'prev-next', array($this, 'prev_next_shortcode') );

    add_shortcode( 'loop-count', array($this, 'loop_count_shortcode') );
    add_shortcode( 'found-posts', array($this, 'found_posts_shortcode') );
    add_shortcode( 'search-keyword', array($this, 'search_keyword_shortcode') );

    add_shortcode( '*', array($this, 'shortcode_comment') );
    add_shortcode( '!', array($this, 'shortcode_comment') );
  }


  /*---------------------------------------------
   *
   * Initialize global
   *
   */

  public static function init() {

    self::$state['is_loop'] = false;
    self::$state['loop_index'] = 0;
    self::$state['is_nested_loop'] = false;
    self::$state['is_attachment_loop'] = false;
    self::$state['do_reset_postdata'] = false;
    self::$previous_state = array();
    self::$wp_query = null;
  }


  /*---------------------------------------------
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

        // Process posts
        $posts = self::prepare_posts( $posts );

        // Loop through each post and compile shortcode template
        $results = self::compile_templates( $posts, $template );

        // Combine results and process to final output
        $result = self::process_results( $results );

      } else {

        // Query is empty

        $results = self::compile_templates( null, $template, false );
        $result = self::process_results( $results );
      }

    self::close_loop();

    return $result;
  }



  /*---------------------------------------------
   *
   * Initialize loop state
   *
   */

  public static function init_loop() {

    $state = self::$state;
    $state['loop_index']++; // Starts with 1

    global $post;
    $state['prev_post'] = $post;

    if ( $state['is_loop'] ) {

      // If nested, save previous state so it can be restored in close_loop

      self::$previous_state[]      = $state;
      $state['is_nested_loop']     = true;

    } else {

      $state['is_loop']            = true;
      $state['is_nested_loop']     = false;
    }

    $state['do_reset_postdata']  = false;
    $state['do_cache']           = false;
    $state['blog']               = 0;

    $state['loop_count']         = 0;
    $state['post_count']         = 0;
    $state['skip_ids']           = array();
    $state['maxpage']            = 0;
    $state['include_descendants'] = false;

    $state['current_post_id']    = 0;
    // Store ID of post that contains the loop
    $state['original_post_id']   = get_the_ID();

    $state['comment_count']      = 0;
    $state['is_attachment_loop'] = false;

    // Support qTranslate Plus
    $state['current_lang']       = null;

    self::$state = $state;
  }


  /*---------------------------------------------
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
      'author' => '', 'author_exclude' => '', 'role' => '',

      // Field value

      'start' => '', // If field value starts with
      'field' => '', 'value' => '', 'compare' => '',
      'field_2' => '', 'value_2' => '', 'compare_2' => '',
      'field_3' => '', 'value_3' => '', 'compare_3' => '',
      'field_4' => '', 'value_4' => '', 'compare_4' => '',
      'field_5' => '', 'value_5' => '', 'compare_5' => '',
      'relation' => '',

      // Date field query
      'after' => '', 'before' => '',
      'after_2' => '', 'before_2' => '',
      'after_3' => '', 'before_3' => '',
      'after_4' => '', 'before_4' => '',
      'after_5' => '', 'before_5' => '',

      'date_format' => '',
      'date_format_2' => '',
      'date_format_3' => '',
      'date_format_4' => '',
      'date_format_5' => '',
      'in' => '', // in="string" - Date field stored as string
      'acf_date' => '',

      // Taxonomy

      'category' => '', 'tag' => '',
      'taxonomy' => '', 'term' => '',
      'taxonomy_2' => '', 'term_2' => '',
      'taxonomy_3' => '', 'term_3' => '',
      'taxonomy_4' => '', 'term_4' => '',
      'taxonomy_5' => '', 'term_5' => '',

      // Checkbox

      'checkbox' => '', 'checkbox_2' => '',

      // Sort

      'orderby' => '', 'order' => '',
      'series' => '', 'key' => '',
      'meta_type' => '',

      // Format

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

      'paged' => '', 'maxpage' => '',

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

    return $merged;
  }


  /*---------------------------------------------
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

      $cache_name = substr($cache_name, 0, 40); // Limit max number of characters

      self::$state['cache_name'] = $cache_name;

      if ($parameters['update']!='true') {

        $result = CCS_Cache::get_transient( $cache_name );
      }
    }
    return $result;
  }


  /*---------------------------------------------
   *
   * Action before running query
   *
   * If return is not null, there is already result
   *
   */

  public static function before_query( $parameters, $template = null ) {


    /*---------------------------------------------
     *
     * Start timer
     *
     */

    if ( $parameters['timer'] == 'true' ) {

      CCS_Cache::start_timer();

    }


    /*---------------------------------------------
     *
     * The X parameter - run loop X times, no query
     *
     */

    if (!empty($parameters['x'])) {

      $outs = array();

      $x = $parameters['x'];
      for ($i=0; $i <$x ; $i++) {
        self::$state['loop_count']++;

        $outs[] = apply_filters( 'ccs_loop_each_result',
          do_shortcode( self::render_field_tags( $template, $parameters ) ),
          $parameters
        );

      }

      if (!empty($parameters['columns'])) {
        $out = self::render_columns( $outs, $parameters['columns'], $parameters['pad'], $parameters['between'] );
      } else $out = implode('', $outs);

      return apply_filters('ccs_loop_final_result', $out, $parameters );
    }


    /*---------------------------------------------
     *
     * Switch to blog on multisite - restore during close_loop()
     *
     */

    if ( !empty($parameters['blog']) ) {
      $result = switch_to_blog($parameters['blog']);
      if ($result) {
        self::$state['blog'] = $parameters['blog'];
      }
    }

    return null;

  } // End: before_query


  /*---------------------------------------------
   *
   * Prepare query based on parameters
   *
   */

  public static function prepare_query( $parameters ) {

    $query = array();


    /*---------------------------------------------
     *
     * field="gallery"
     *
     */

    if ( $parameters['field'] == 'gallery' && class_exists('CCS_Gallery_Field') ) {

      // Gallery field

      $parameters['type'] = 'attachment';
      // $query['post_parent'] = get_the_ID();
      self::$state['is_attachment_loop'] = true;

      $parameters['id'] = implode(',', CCS_Gallery_Field::get_image_ids( get_the_ID() ) );
      $parameters['field'] = '';

    }

    /*---------------------------------------------
     *
     * Post type
     *
     */

    if ( !empty($parameters['type']) ) {

      $query['post_type'] = self::explode_list($parameters['type']);

    } else {

      $query['post_type'] = 'any';
    }

    /*---------------------------------------------
     *
     * Post ID, exclude ID, name
     *
     */

    if ( !empty($parameters['include']) ) {
      if ( !empty($parameters['id']) ) {
        $parameters['id'] .= ',';
      }

      $parameters['id'] .= $parameters['include'];
    }

    if ( !empty($parameters['id']) ) {

      $id_array = self::explode_list($parameters['id']);

      foreach ($id_array as $key => $value) {

        // Include current post
        if ( $value=='this' ) {

          // ID of post that contains the loop
          $id_array[$key] = self::$state['original_post_id'];

        // Include child posts and descendants
        } elseif ( $value == 'children' ) {

          // Query for top-level posts first
          if (empty($parameters['parent'])) {
            $query['post_parent'] = 0;
          }

          // Then manually get descendants after each post
          self::$state['include_descendants'] = true;

          unset($id_array[$key]);

        // Include by post slug
        } elseif ( !is_numeric($value) ) {

          $get_id = self::get_post_id(array(
            'name' => $value,
            'type' => $parameters['type'],
          ));
          unset($id_array[$key]);
          if (!empty($get_id)) {
            $id_array[$key] = $get_id;
          } else {
            $id_array[$key] = 99999; // Prevent empty
          }
        }
      }

      if (count($id_array)>0) {
        $query['post__in'] = $id_array;
        $query['orderby'] = 'post__in'; // Preserve ID order
      }
    }

    if ( !empty($parameters['exclude']) ) {

      $id_array = self::explode_list($parameters['exclude']);

      foreach ($id_array as $key => $value) {

        // Exclude current post
        if ( $value=='this' ) {

          // ID of post that contains the loop
          $id_array[$key] = self::$state['original_post_id'];

        // Top-level posts only
        } elseif ( $value=='children' ) {

          unset($id_array[$key]);
          $query['post_parent'] = 0;

        // Exclude by post slug
        } elseif ( !is_numeric($value) ) {

          $get_id = self::get_post_id(array(
            'name' => $value,
            'type' => $parameters['type'],
          ));

          unset($id_array[$key]);
          if (!empty($get_id)) $id_array[$key] = $get_id;
        }
      }

      if (count($id_array)) {
        $query['post__not_in'] = $id_array;
      }

    } elseif ( !empty($parameters['name']) ) {

      $query['name'] = $parameters['name'];

    }

    /*---------------------------------------------
     *
     * Parent
     *
     */

    if ( !empty($parameters['parent']) ) {

      $parent = $parameters['parent'];
/*
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
*/
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

        if (count($parent_IDs)==0) return null; // No parent

        $query['post_parent__in'] = $parent_IDs;
/*
      } // End single/multiple
*/


    } // End if parent pameter

    $query['ignore_sticky_posts'] = true;



    /*---------------------------------------------
     *
     * User role
     *
     */

    if ( !empty($parameters['role']) ) {

      if ($parameters['role']=='this') {
        $parameters['role'] = do_shortcode('[user role out="slug"]');
      }

      $roles = self::explode_list($parameters['role']);
      foreach ($roles as $role) {

        // Make a list of authors in this user role
        $authors = do_shortcode('[users role="'.$role.'" trim="true"][user id],[/users]');

        if (!empty($parameters['author'])) {
          $parameters['author'] .= ',';
        }
        $parameters['author'] .= $authors;
      }
    }

    /*---------------------------------------------
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
            } else {
              // No author by that name: use an arbitrary ID
              $query['author__in'][] = 9999;
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



    /*---------------------------------------------
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


    /*---------------------------------------------
     *
     * Post count and offset
     *
     */

    if ( !empty($parameters['offset']) ) {
      $query['offset'] = $parameters['offset'];
    }

    if ( !empty($parameters['paged']) ) {

      if (class_exists('CCS_Paged')) {

        if (!empty($parameters['maxpage'])) {
          self::$state['maxpage'] = $parameters['maxpage'];
        }

        if (is_numeric($parameters['paged'])) {
          $parameters['count'] = $parameters['paged'];
        } else {
          $parameters['count'] = 5;
        }

        // Get from query var
        $query_var = CCS_Paged::$prefix;
        if (self::$state['loop_index']>1)
          $query_var .= self::$state['loop_index'];
        $query['paged'] = isset($_GET[$query_var]) ? $_GET[$query_var] : 1;
      }
    }

    if ( !empty($parameters['page']) ) {
      $query['paged'] = $parameters['page']; // Manually set
    }

    if ( !empty($parameters['count']) ) {
      if ($parameters['orderby']=='rand') {
        $query['posts_per_page'] = '-1'; // For random, get all posts and count later
      } else {
        $query['posts_per_page'] = $parameters['count'];
      }
    } else {
      if (!empty($query['offset'])) {
        $query['posts_per_page'] = '99999'; // Show all posts (to make offset work)
      } else {
        $query['posts_per_page'] = '-1'; // Show all posts (normal method)
      }
    }


    /*---------------------------------------------
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


    /*---------------------------------------------
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

      if ( is_numeric($check_category) ) {
        $query['cat'] = $category; // ID(s)
      } elseif ( $category == 'this' ) {
        $category = do_shortcode('[taxonomy category field="id"]');

        if (empty($category)) return;

        if (!empty($parameters['compare']) && strtoupper($parameters['compare'])=='AND') {
          $category = str_replace(',', '+', $category);
        }
        $query['cat'] = $category;
      } else {
        $query['category_name'] = $category; // Slug(s)
      }
    }


    /*---------------------------------------------
     *
     * Tag
     *
     */

    if( !empty($parameters['tag']) ) {

      // Remove extra space in a list

      $tags = self::clean_list( $parameters['tag'] );
      if ($tags == 'this') {
        $tags = do_shortcode('[taxonomy tag field="slug"]');

        if (empty($tags)) return;

        $tags = str_replace(' ', ',', $tags);
      }

      if (!empty($parameters['compare']) && strtoupper($parameters['compare'])=='AND') {
        $tags = str_replace(',', '+', $tags);
      }
      $query['tag'] = $tags;
    }


    /*---------------------------------------------
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

      $query['tax_query'] = array();

      // Support multiple taxonomy queries

      $max = 4;

      for ($index=0; $index < $max; $index++) {

        if ( $index == 0 ) {
          $suffix = '';
        } else {
          $suffix = '_'.($index+1); // field_2, field_3, ...
        }

        if (!empty($parameters['taxonomy'.$suffix])) {

          if ($index == 1) {
            $relation = !empty($parameters['relation']) ?
              strtoupper($parameters['relation']) : 'AND';
            $query['tax_query']['relation'] = $relation;
          }

          $taxonomy = $parameters['taxonomy'.$suffix];

          if ( !empty($parameters['term'.$suffix]) )
            $term = $parameters['term'.$suffix];
          else
            $term = $parameters['value'.$suffix]; // Alias, if field value is not used

          $args = array(
            'taxonomy' => $taxonomy,
            'term' => $term,
            'compare' => !empty($parameters['compare'.$suffix]) ?
              $parameters['compare'.$suffix] : 'IN'
          );

          $query['tax_query'][] = self::prepare_tax_query( $args );
        }

      } // End each taxonomy query

    } // End taxonomy query


    /*---------------------------------------------
     *
     * Order and orderby
     *
     */

    // Order by comment date
    if ( !empty($parameters['orderby']) && $parameters['orderby']=='comment-date' ) {

      // WP_Query doesn't support it, so sort after getting all posts
      self::$parameters['orderby_comment_date'] = 'true';
      self::$parameters['order_comment_date'] = !empty($parameters['order']) ?
        strtoupper($parameters['order']) : 'DESC';

      $parameters['orderby'] = '';

    } elseif ( !empty($parameters['order']) ) {

      $query['order'] = $parameters['order'];

    }

    if ( !empty($parameters['orderby']) ) {

      $orderby = $parameters['orderby'];

      $default_orderby = array(
        'none', 'id', 'author', 'title', 'name', 'type', 'date', 'modified',
        'parent', 'rand', 'comment_count', 'menu_order', 'meta_value', 'meta_value_num'
      );

      // Alias
      if ($orderby=="field_num") $orderby = 'meta_value_num';
      elseif ($orderby=="field") $orderby = 'meta_value';
      elseif ($orderby=="menu") $orderby = 'menu_order';
      elseif ( !in_array(strtolower($orderby), $default_orderby) ) {

        // If not default orderby value, assume field name

        $orderby = 'meta_value';
        if (empty($parameters['field'])) {
          $parameters['field'] = $orderby;
        } else {
          $parameters['key'] = $orderby;
        }
      }

      $query['orderby'] = $orderby;

      if (in_array($orderby, array('meta_value', 'meta_value_num') )) {

        if ( !empty($parameters['key']) )
          $key = $parameters['key'];
        elseif ( !empty($parameters['field']) )
          $key = $parameters['field']; // If no key is specified, order by field

        $query['meta_key'] = $key;

        if ( !empty($parameters['meta_type'])) {
          $query['meta_type'] = $parameters['meta_type'];
        }
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


    /*---------------------------------------------
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

        /* Compatible with PHP 5.2 and below */

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

    // Support multiple date field queries

    $max = 4;

    for ($index=0; $index < $max; $index++) {

      if ( $index == 0 ) {
        $suffix = '';
      } else {
        $suffix = '_'.($index+1); // field_2, field_3, ...
      }

      // Published date
      if ( empty($parameters['field'.$suffix]) || $parameters['field'.$suffix]=='date' ) {

        if ( !empty( $parameters['before'.$suffix]) ) {
          if ( !isset($query['date_query']) ) {
            $query['date_query'] = array();
          }
          $query['date_query'][] = array(
            'before' => $parameters['before'.$suffix]
          );
        }

        if ( !empty( $parameters['after'.$suffix]) ) {
          if ( !isset($query['date_query']) ) {
            $query['date_query'] = array();
          }
          $query['date_query'][] = array(
            'after' => $parameters['after'.$suffix]
          );
        }

      } else {

        // Other field

        if ( !empty( $parameters['before'.$suffix]) ) {
          $parameters['value'.$suffix] = strtotime($parameters['before'.$suffix]);
          $parameters['compare'.$suffix] =
            !empty($parameters['compare'.$suffix]) ?
              $parameters['compare'.$suffix] : 'OLD';
        }

        if ( !empty( $parameters['after'.$suffix]) ) {
          $parameters['value'.$suffix] = strtotime($parameters['after'.$suffix]);
          $parameters['compare'.$suffix] =
            !empty($parameters['compare'.$suffix]) ?
              $parameters['compare'.$suffix] : 'NEW';
        }
      }

    } // End each date field query


    /*---------------------------------------------
     *
     * Field value
     *
     */

    if( !empty($parameters['field']) && !empty($parameters['value']) ) {

      if ( !isset($query['meta_query']) ) {
        $query['meta_query'] = array();
      }

      // Support multiple field value queries

      $max = 4;
      $suffix = '';

      for ($index=0; $index < $max; $index++) {

        if ( $index > 0 ) {

          $suffix = '_'.($index+1); // field_2, field_3, ...

          if ( $index == 1 ) {
            // Only one relation possible..?
            $relation = $parameters['relation'];
            $relation = !empty($relation) ? strtoupper($relation) : 'AND';
            $query['meta_query']['relation'] = $relation;
          }
        }

        if ( !empty($parameters['field'.$suffix]) && !empty($parameters['value'.$suffix]) ) {

          $args = array(
            'field' => $parameters['field'.$suffix],
            'value' => $parameters['value'.$suffix],
            'compare' => $parameters['compare'.$suffix],
          );

          if ( !empty($parameters['in'.$suffix]) ) {
            $args['in'] = $parameters['in'.$suffix];
          }
          if ( !empty($parameters['date_format'.$suffix]) ) {
            $args['date_format'] = $parameters['date_format'.$suffix];
          } else {
            // If any date format set, apply it by default
            if (!empty($parameters['date_format'])) {
              $args['date_format'] = $parameters['date_format'];
            }
          }

          $query['meta_query'][] = self::prepare_meta_query( $args );
        }
      }

    } // End field value query


    /*---------------------------------------------
     *
     * Sort by multiple custom fields
     *
     */

    if( !empty($parameters['sort_field']) ) {

      if ( !isset($query['meta_query']) ) {
        $query['meta_query'] = array();
      }

      $query['meta_query'][] = self::prepare_meta_query( $args );

    }


    return apply_filters( 'ccs_loop_query_filter', $query );

  } // End: prepare query



  static function prepare_meta_query( $args ) {

    $meta_query = array();

    $field = $args['field'];
    $value = $args['value'];
    $compare = strtoupper($args['compare']);

    // Support for date values

    switch ($value) {
      case 'future':
        $value = 'today';
        $compare = empty($compare) ? '>=' : $compare;
        break;
      case 'future not today': // Don't include today
        $value = 'today';
        $compare = empty($compare) ? '>' : $compare;
        break;
      case 'future-time':
        $value = 'now';
        $compare = empty($compare) ? '>=' : $compare;
        break;
      case 'past':
        $value = 'today';
        $compare = empty($compare) ? '<' : $compare;
        break;
      case 'past and today': // Include today
        $value = 'today';
        $compare = empty($compare) ? '<=' : $compare;
        break;
      case 'past-time':
        $value = 'now';
        $compare = empty($compare) ? '<=' : $compare;
        break;
      default:
        // Pass value as it is
        break;
    }

    if ( isset($args['in']) && $args['in'] == 'string' ||
      !empty($args['date_format']) ) {

      if (empty($args['date_format'])) {

        // default date format
        if ($value == 'today')
          $args['date_format'] = 'Y-m-d'; // 2014-01-24
        elseif ($value == 'now')
          $args['date_format'] = 'Y-m-d H:i'; // 2014-01-24 13:05
      }

      if ( $value == 'today'  || $value == 'now' ) {
        $value = date($args['date_format'],time());
      } else {
        $value = date($args['date_format'],$value);
      }

    } else {

      if ( $value == 'today' || $value == 'now' ) {
        // It's a timestamp so today/now is the same
        $value = time();
      }
    }

    switch ($compare) {
      case '':
      case '=':
      case 'EQUAL': $compare = '='; break;
      case 'NOT':
      case '!=':
      case 'NOT EQUAL': $compare = '!='; break;
      case 'MORE':
      case 'NEW':
      case 'NEWER':
      case '&gt;': $compare = '>'; break;
      case '&gt;=':
      case '&gt;&#61;': $compare = '>='; break;
      case 'LESS':
      case 'OLD':
      case 'OLDER':
      case '&lt;': $compare = '<'; break;
      case '&lt;=':
      case '&lt;&#61;': $compare = '<='; break;
      case 'BETWEEN' :
        $value = self::explode_list($value); // Comma-separated list
      break;
    }

    $meta_query = array(
      'key' => $field,
      'compare' => $compare
    );

    if ( $compare!='EXISTS' && $compare!='NOT EXISTS') {

      $meta_query['value'] = $value;

    } elseif ($compare!='NOT EXISTS') {

      $meta_query['value'] = ' '; // NOT EXISTS needs some value

    } else {
      // $compare=='EXISTS' then no value
    }

    if ($field == 'date') {
      $meta_query['type'] = 'DATE';
    } elseif ( $compare == 'BETWEEN' && empty($args['type'])) {
      $meta_query['type'] = 'NUMERIC';
    }

    return $meta_query;

  } // End prepare_meta_query


  static function prepare_tax_query( $args ) {

    $taxonomy = $args['taxonomy'];
    $term = $args['term'];
    $compare = strtoupper($args['compare']);

    if ($taxonomy == 'tag') $taxonomy = 'post_tag';

    $terms = self::explode_list($term); // Multiple terms possible

    switch ( $compare ) {
      case '=':
        $compare = 'IN';
      break;
      case '!=':
      case 'NOT':
        $compare = 'NOT IN';
      break;
        $compare = 'NOT IN';
      break;
    }

    return array(
      'taxonomy' => $taxonomy,
      'field' => 'slug',
      'terms' => $terms,
      'operator' => $compare
    );
  }




  /*---------------------------------------------
   *
   * Run the prepared query and return posts (WP_Query object)
   *
   * @todo Option to use get_query to alter main loop for pagination..?
   *
   */

  public static function run_query( $query ) {

    self::$query = $query; // Store query parameters

    if (!self::$state['is_nested_loop']) {
      self::$state['do_reset_postdata'] = true; // Reset post data at the end of loop
    }

    return self::$wp_query = new WP_Query( $query );
  }


  public static function prepare_posts( $posts ) {

    $parameters = self::$parameters;

    // Sort by series

    if ( !empty($parameters['series']) ) {

      usort( $posts->posts, array($this, 'sort_by_series') );

    // Random order
    } elseif ( $parameters['orderby'] == 'rand' ) {

      shuffle( $posts->posts );

    } elseif ( !empty($parameters['orderby_comment_date']) &&
      $parameters['orderby_comment_date']=='true' ) {

      usort( $posts->posts, array(__CLASS__, 'orderby_comment_date') );
    }

    $posts = apply_filters( 'ccs_loop_prepare_posts', $posts );

    return $posts;
  }



  /*---------------------------------------------
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

          $this_template = self::render_template(
            self::prepare_each_template($template)
          );

          // Append all descendants
          if ( self::$state['include_descendants'] ) {
            $args = self::$original_parameters;
            $args['parent'] = self::$state['current_post_id'];
            $args['include'] = 'children';
            if (!empty($args['id'])) unset($args['id']);
            if (!empty($args['name'])) unset($args['name']);

            $params = '';
            foreach ($args as $key => $value) {
              $params .= ' '.$key.'="'.$value.'"';
            }

            $this_template .= do_shortcode(
              '[if children]'
                .'[loop'.$params.']'
                  .$template
                .'[/loop]'
              .'[/if]'
            );
          }

          $templates[] = $this_template;

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


  /*---------------------------------------------
   *
   * Pre-process template: if first, last, empty
   *
   */

  public static function pre_process_template( $template ) {

    $state =& self::$state; // Update global state

    // If empty
    $middle = self::get_between('[if empty]', '[/if]', $template);
    if (empty($middle)) $middle = self::get_between('[-if empty]', '[/-if]', $template);
    $template = str_replace($middle, '', $template);
    $else = self::extract_else( $middle ); // Remove and return what's after [else]
    $state['if_empty'] = $middle;
    $state['if_empty_else'] = $else;

    return $template;
  }


  /*---------------------------------------------
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



  /*---------------------------------------------
   *
   * Prepare all posts
   *
   * Takes and returns a WP_Query object
   *
   */

  public static function prepare_all_posts( $query_object ) {

    $parameters = self::$parameters;
    $query = self::$query;
    $state =& self::$state; // Update global state directly

    $state['post_count'] = $query_object->post_count;
    $all_posts = $query_object->posts;

    $state['all_ids'] = array();
    foreach ($all_posts as $post) {
      $state['all_ids'][] = $post->ID;
    }

    if ( isset($query['meta_query'][0]) ) {
      $compare = $query['meta_query'][0]['compare'];
      $key = $query['meta_query'][0]['key'];
    } else {
      $compare = '';
      $key = '';
    }

    // Check for skipped post

    if (
      $compare=='EXISTS' ||
      $compare=='NOT EXISTS' ||
      !empty($parameters['checkbox']) ||
      !empty($parameters['start']) ) {

      $all_ids_filtered = $state['all_ids'];

      foreach ( $state['all_ids'] as $index => $current_id ) {
//      foreach ($all_posts as $post) {
//        $current_id = $post->ID;

        $skip = false;

        /*---------------------------------------------
         *
         * If field value exists or not
         *
         */

        if (isset($query['meta_query'][0]) && isset($query['meta_query'][0]['key'])) {

          $field_value = get_post_meta( $current_id, $key, true );

          if (!empty($field_value) && is_array($field_value)) {
            $field_value = implode('', $field_value);
          }

          $field_value = trim($field_value);

          if ( ($field_value==false) || empty($field_value) ) {

            if ($compare=='EXISTS') {
              $skip = true; // value is empty, then skip
            }
          } elseif ($compare=='NOT EXISTS') {
              $skip = true; // value is not empty, then skip
          }
        }


        /*---------------------------------------------
         *
         * If field value starts with
         *
         */

        if (!empty($parameters['start'])) {

          $field_value = CCS_Content::get_prepared_field( $parameters['field'], $current_id );

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
        }


        /*---------------------------------------------
         *
         * Checkbox query
         *
         */

        $skip_1 = false;

        if (!empty($parameters['checkbox']) && !empty($parameters['value'])) {

          $values = self::explode_list($parameters['value']);
          $check_field = get_post_meta( $current_id, $parameters['checkbox'], $single=true );

          if (empty($parameters['compare'])) $compare="or";
//          elseif (empty($parameters['checkbox_2']))
//            $compare = strtolower($parameters['compare']);
//          else $compare="or";
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
          unset($all_ids_filtered[$index]);
        }

      } // End for each post

/*
      // Manually limit number of posts, regardless of query
      if (!empty($parameters['max'])) {
        $i = 0;
        $all_posts = $query_object->posts;

        foreach ($all_posts as $post) {
          $current_id = $post->ID;

          if ( !in_array($current_id, $state['skip_ids']) ) {
            $i++;

            if ($i > $parameters['max']) {
              $state['skip_ids'][] = $current_id;
            }
          }
        }
      }
*/
      // Subtract skipped posts from post count

      $state['post_count'] = $state['post_count'] - count($state['skip_ids']);
      $state['all_ids'] = $all_ids_filtered;

    } // End check for skipped posts

    return $query_object;
  }

  public static function prepare_each_post( $post ) {

    $post_id = $post->ID;

    // Skip

    if ( in_array($post_id, self::$state['skip_ids']) ) {
      return null;
    }

    self::$state['comment_count'] += get_comments_number();

    return apply_filters('ccs_loop_each_post', $post);
  }


  public static function prepare_each_template( $template ) {

    $state = self::$state;
    $parameters = self::$parameters;

    /*---------------------------------------------
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



  /*---------------------------------------------
   *
   * Render template: expand {FIELD} tags and shortcodes
   *
   */

  public static function render_template( $template ) {

    $post_id = self::$state['current_post_id'];

    /*---------------------------------------------
     *
     * Expand {FIELD} tags
     *
     */

    $template = self::render_field_tags( $template, self::$parameters );

    return apply_filters('ccs_loop_each_result',
      do_local_shortcode( 'loop', $template, true ), self::$parameters );
  }


  /*---------------------------------------------
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

  /*---------------------------------------------
   *
   * Process the combined result
   *
   */

    /*---------------------------------------------
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


    /*---------------------------------------------
     *
     * Trim
     *
     */

    if ( !empty($parameters['trim']) ) {

      $trim = $parameters['trim'];

      if ( empty($parameters['columns']) && $parameters['list']!='true' ) {

        $result = CCS_Format::trim($result, $trim);

      } else {

        // Trim each item for columns or list
        $new_results = array();
        foreach ($results as $result) {
          $new_results[] = CCS_Format::trim($result, $trim);
        }
        $results = $new_results;
      }
    }

    /*---------------------------------------------
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

        if ( !empty($parameters['paginate']) ) {
          $item = '<'.$list_tag.$list_class.$list_style.'>'.$item.'</'.$list_tag.'>';
        }

        $new_results .= apply_filters( 'ccs_loop_each_item', $item, $parameters );
      }

      if ( empty($parameters['paginate']) ) {
        $result = '<'.$list_tag.$list_class.$list_style.'>'.$new_results.'</'.$list_tag.'>';
      }
      else $result = $new_results;
    }



    /*---------------------------------------------
     *
     * Cache the final result
     *
     */

    if ( self::$state['do_cache'] == 'true' ) {
      CCS_Cache::set_transient( self::$state['cache_name'], $result, $parameters['expire'] );
    }


    return apply_filters('ccs_loop_final_result', $result, $parameters );
  }



  /*---------------------------------------------
   *
   * Close the loop
   *
   */

  public static function close_loop(){

    $state =& self::$state;
    $parameters = self::$parameters;

    /*---------------------------------------------
     *
     * Stop timer
     *
     */

    if ( self::$parameters['timer'] == 'true' ) {

      echo CCS_Cache::stop_timer('<br><b>Loop result</b>: ');

    }


    /*---------------------------------------------
     *
     * Reset postdata after WP_Query
     *
     */

    if (self::$state['do_reset_postdata']) {
      wp_reset_postdata();
      self::$state['do_reset_postdata'] = false;
      global $post;
      $post = $state['prev_post'];
    }

    /*---------------------------------------------
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







  /*---------------------------------------------
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

    return $out;
  }



  /*---------------------------------------------
   *
   * Process {FIELD} tags
   *
   */

  public static function render_field_tags( $template, $parameters ) {

    $post_id = !empty($parameters['id']) ? $parameters['id'] : get_the_ID();

    /*---------------------------------------------
     *
     * User defined fields
     *
     */

    if ( !empty($parameters['fields']) ) {

      $fields = self::explode_list($parameters['fields']);

      foreach ($fields as $key) {

        $search = '{'.strtoupper($key).'}';

        if (strpos($template, $search)!==false) {

          if ( class_exists('CCS_To_ACF') &&
              CCS_To_ACF::$state['is_repeater_or_flex_loop']=='true' ) {

            // Repeater or flexible content field: then get sub field
            if (function_exists('get_sub_field')) {
              $field_value = get_sub_field( $key );
            } else $field_value = null;

          } else {

            // Enable predefined fields
            $field_value = CCS_Content::get_prepared_field( $key, $post_id );

          }

          if (is_array($field_value)) {
            $field_value = ucwords(implode(', ', $field_value));
          }

          $template = str_replace($search, $field_value, $template);
        }
      }
    }

    // @todo Deprecate below for better performance
/*
    // Render default tags later, to allow custom fields to take priority
    if ( strpos($template, '{') !== false ) {
      $template = self::render_default_field_tags( $template );
    }
*/
    return $template;
  }

  // ------------ Deprecated ----------

  public static function render_default_field_tags( $template ) {

    /*---------------------------------------------
     *
     * Predefined field tags
     *
     */

    $keywords = array(
      'URL', 'SLUG', 'ID', 'COUNT', 'TITLE', 'AUTHOR', 'DATE',
      'CONTENT', 'EXCERPT', 'COMMENT_COUNT', 'TAGS', 'CATEGORY',
      'THUMBNAIL', 'THUMBNAIL_URL', 'IMAGE', 'IMAGE_ID', 'IMAGE_URL',
      'PAGED' // ??
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
          case 'CATEGORY':
            $replace = do_shortcode('[taxonomy category out="slug"]');
            $replace = str_replace(' ', ',', $replace);
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




/*---------------------------------------------
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

  public static function explode_list( $list, $delimiter = '' ) {

    // Support multiple delimiters

    $delimiter .= ','; // default
    $delimiters = str_split($delimiter); // convert to array

    $list = str_replace($delimiters, $delimiters[0], $list); // change all delimiters to same

    // explode list and trim each item

    return array_map( 'trim', explode($delimiters[0], $list) );
  }

  // Explode the list, trim each item and put it back together

  public static function clean_list( $list, $delimiter = '' ) {

    if (empty($delimiter)) $delimiter = ',';
    $list = self::explode_list($list, $delimiter);
    return implode($delimiter,$list);
  }


  /*---------------------------------------------
   *
   * Sort series
   *
   */

  public static function sort_by_series( $a, $b ) {

    $apos = array_search( get_post_meta( $a->ID, self::$state['sort_key'], true ), self::$state['sort_posts'] );
    $bpos = array_search( get_post_meta( $b->ID, self::$state['sort_key'], true ), self::$state['sort_posts'] );

    return ( $apos < $bpos ) ? -1 : 1;
  }

  /*---------------------------------------------
   *
   * Orderby comment date
   *
   */

  public static function orderby_comment_date( $a, $b ) {

    $parameters = self::$parameters;
    $order = self::$parameters['order_comment_date'];

    $a_comment_date = self::get_comment_timestamp( $a );
    $b_comment_date = self::get_comment_timestamp( $b );

    // echo 'Compare '.$a_comment_date.' and '.$b_comment_date.'<br>';

    if ($order=='ASC') {
      return ( $a_comment_date < $b_comment_date ) ? -1 : 1;
    } else {
      return ( $a_comment_date > $b_comment_date ) ? -1 : 1;
    }
  }

  public static function get_comment_timestamp( $post ) {
    $comments = get_comments(array(
      'post_id' => $post->ID, 'number' => 1, 'status' => 'approve'
    ));

    $comment_date = '';
    foreach ($comments as $comment) {
      $comment_date = $comment->comment_date;
    }
    return !empty($comment_date) ? strtotime($comment_date) : 0;
  }


  // Get post slug from ID

  public static function get_the_slug( $id = '' ) {

    global $post;

    $this_post = !empty($id) ? get_post($id) : $post;
    return !empty($this_post) ? $this_post->post_name : '';
  }

  public static function get_post_id( $parameters ) {

    // Get post from name

    $args=array(
      'name' => @$parameters['name'],
      'post_type' => @$parameters['type'],
      'post_status' => @$parameters['status'], // Default is publish, or any for attachment
      'posts_per_page' => '1',
      );

    $posts = get_posts($args);

    if ( $posts ) {
      return $posts[0]->ID; // ID of the post
    } else {
      return 0;
    }
  }


  /*---------------------------------------------
   *
   * [loop-count] - Display current loop count
   *
   */

  public static function loop_count_shortcode() {

    return CCS_Loop::$state['loop_count'];
  }

  public static function found_posts_shortcode() {
    global $wp_query;
    return !empty($wp_query) ? $wp_query->post_count : 0;
  }

  public static function search_keyword_shortcode() {
    global $wp_query;

    if (!empty($wp_query)) {
      $vars = $wp_query->query_vars;
      if (isset($vars['s']))
        return $vars['s'];
    }
  }


  /*---------------------------------------------
   *
   * Prev/next post
   *
   */

  public static function next_shortcode( $atts, $content, $tag ) {

    // Flip when looking for older and order is ASC (old to new)
    if ( $tag == 'older' && strtolower(self::$parameters['order'])=='asc' ) {
        self::prev_shortcode( $atts, $content, $tag );
    }

    $current_id = self::$state['current_post_id'];
    $all_ids = self::$state['all_ids'];
    $result = '';

    if ( ($find_key = array_search($current_id, $all_ids)) !== false) {
      if (isset( $all_ids[$find_key + 1] )) { // Next in loop
        $prev_id = $all_ids[$find_key + 1];
        self::$state['current_post_id'] = $prev_id;
        $result = do_shortcode($content);
        self::$state['current_post_id'] = $current_id; // Restore
      }
    }
    return $result;
  }


  public static function prev_shortcode( $atts, $content, $tag ) {

    // Flip when looking for newer and order is ASC (old to new)
    if ( $tag == 'newer' && strtolower(self::$parameters['order'])=='asc' ) {
        self::next_shortcode( $atts, $content, $tag );
    }

    $current_id = self::$state['current_post_id'];
    $all_ids = self::$state['all_ids'];
    $result = '';

    if ( ($find_key = array_search($current_id, $all_ids)) !== false) {
      if (isset( $all_ids[$find_key - 1] )) { // Prev in loop
        $prev_id = $all_ids[$find_key - 1];
        self::$state['current_post_id'] = $prev_id;
        $result = do_shortcode($content);
        self::$state['current_post_id'] = $current_id; // Restore
      }
    }
    return $result;
  }

  public static function prev_next_shortcode( $atts, $content ) {

    $content = '[if id="this"]'.$content.'[/if]';
    if (!isset($atts['type'])) $atts['type'] = get_post_type();

    return self::the_loop_shortcode( $atts, $content );
  }


  function shortcode_comment( $atts, $content, $tag ) {
    if ($tag == '!' && !empty($content)) return '<!--'.do_shortcode($content).'-->';
  }

} // End CCS_Loop
