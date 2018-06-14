<?php

/*---------------------------------------------
 *
 * [loop] - Query posts and loop through each one
 *
 * Filters:
 *
 * ccs_loop_add_defaults      Additional parameters to accept
 * ccs_loop_parameters        Process given parameters
 * ccs_loop_before_query      Before query - possible results
 * ccs_loop_before_run_query  Before run query
 * ccs_loop_after_run_query   After run query
 * ccs_loop_prepare_posts     Process found posts
 * ccs_loop_before_query      Before query
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
  private static $parameters;           // After merge with default
  private static $query;                // The query

  static $wp_query;              // WP_Query object for pagination
  static $state;                 // Loop state array
  static $previous_state;        // For nested loop

  function __construct() {

    self::init();

    add_ccs_shortcode( array(
      'loop' => array( $this, 'the_loop_shortcode'),
      '-loop' => array( $this, 'the_loop_shortcode'),
      '--loop' => array( $this, 'the_loop_shortcode'),
      'prev-next' => array( $this, 'prev_next_shortcode'),
      'loop-count' => array( $this, 'loop_count_shortcode'),
      'found-posts' => array( $this, 'found_posts_shortcode'),
      'search-keyword' => array( $this, 'search_keyword_shortcode'),
      'the-loop' => array( $this, 'default_loop_shortcode'), // Default query loop in template
      '-the-loop' => array( $this, 'default_loop_shortcode'), // Nested for good measure
      '--the-loop' => array( $this, 'default_loop_shortcode'),
      'note' => array($this, 'shortcode_comment')
    ));

    add_local_shortcode( 'loop', 'prev', array($this, 'prev_shortcode') );
    add_local_shortcode( 'loop', 'next', array($this, 'next_shortcode') );

    // newer/older - default order DESC (new to old)
    add_local_shortcode( 'loop', 'newer', array($this, 'prev_shortcode') );
    add_local_shortcode( 'loop', 'older', array($this, 'next_shortcode') );

    add_filter('ccs_loop_before_query', array($this, 'include_children'),
      $priority = 10, $accepted_args = 3 );
  }


  /*---------------------------------------------
   *
   * Initialize global
   *
   */

  static function init() {

    self::$state['is_loop'] = false;
    self::$state['loop_index'] = 0;
    self::$state['loop_count'] = 0;
    self::$state['is_nested_loop'] = false;
    self::$state['is_attachment_loop'] = false;
    self::$state['do_reset_postdata'] = false;
    self::$state['wp_query'] = null;
    self::$state['paged_index'] = 0;
    self::$state['current_post_id'] = 0;
    self::$state['append_children'] = false;
    self::$previous_state = array();
  }


  /*---------------------------------------------
   *
   * Loop shortcode: main actions
   *
   */

  static function the_loop_shortcode( $parameters = array(), $template ) {

    self::init_loop();

    // Store original parameters
    self::$original_parameters = $parameters;

    // Merge parameters with defaults
    $parameters = self::merge_with_defaults( $parameters );
    // Store merged parameters
    self::$state['parameters'] = $parameters;


    // Check cache - if loaded, return result
    if ( ($result = self::check_cache( $parameters )) !== false ) {
      self::close_loop();
      return $result;
    }

    // If there's already result based on parameters, return it
    $result = self::before_query( $parameters, $template );
    // Catch empty string too
    if ( $result !== null ) {
      self::close_loop();
      return $result;
    }


    // Set up query based on parameters
    $query = self::prepare_query( $parameters );

    // Dump query for debug
    if ($parameters['debug']=='true') {
      echo '<pre><code>';
      print_r($query);
      echo '</code></pre>';
    }

    if (!empty($query)) {

      if (!empty($parameters['the_posts'])) {

        // Inside [loop exists] and [the-loop]
        // Posts are passed from [loop exists]
        $query_result = $parameters['the_posts'];

      } else {

        // Get query result
        $query_result = self::run_query( $query );

        // [loop exists]
        if (is_array(self::$original_parameters)
          && in_array('exists', self::$original_parameters)) {

          // Posts can be filtered out by prepare_all_posts
          $check_posts = self::prepare_all_posts($query_result);
          $post_exists = false;

          foreach ($check_posts->posts as $check_post) {
            if (in_array($check_post->ID, self::$state['all_ids'])) {
              $post_exists = true;
              break;
            }
          }

          if ( ! $post_exists ) return self::handle_empty_query( $template );

          // Passed to [the-loop]
          self::$original_parameters['the_posts'] = $query_result;

          return do_ccs_shortcode( $template );
        }
      }

      // Process posts
      $posts = self::prepare_posts( $query_result );

      // Loop through each post and compile shortcode template
      $results = self::compile_templates( $posts, $template );

      // Combine results and process to final output
      $result = self::process_results( $results );

    } else {

      $result = self::handle_empty_query( $template );
    }

    self::close_loop();

    return $result;
  }


  static function handle_empty_query( $template ) {

    $results = self::compile_templates( null, $template, false );
    return self::process_results( $results );
  }


  /*---------------------------------------------
   *
   * Initialize loop state
   *
   */

  static function init_loop() {

    $state = self::$state;

    if (!isset($state['loop_index']))  $state['loop_index'] = 0;
    if (!isset($state['is_loop']))  $state['is_loop'] = false;

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

    $state['multiple_orderby']    = '';

    $state['current_post_id']    = 0;
    // Store ID of post that contains the loop
    $state['original_post_id']   = get_the_ID();

    $state['is_attachment_loop'] = false;

    // Support qTranslate Plus
    $state['current_lang']       = null;

    $state['alter_query'] = false;

    self::$state = $state;
  }


  /*---------------------------------------------
   *
   * Define all parameters
   *
   */

  static function merge_with_defaults( $parameters ){

    $defaults = array(

      'type' => '',
      'name' => '',
      'id' => '', 'exclude' => '',
      'sticky' => '',
      'status' => '',
      'include' => '', 'level' => '',

      'parent' => '',
      'parents' => '',
      'child' => '',

      'count' => '', 'offset' => '',
      'year' => '', 'month' => '', 'day' => '',
      'author' => '', 'author_exclude' => '',
      'comment_author' => '',
      'role' => '',

      // Field value

      'start' => '', // If field value starts with
      'field' => '', 'value' => '', 'compare' => '',
      'field_2' => '', 'value_2' => '', 'compare_2' => '',
      'field_3' => '', 'value_3' => '', 'compare_3' => '',
      'field_4' => '', 'value_4' => '', 'compare_4' => '',
      'field_5' => '', 'value_5' => '', 'compare_5' => '',
      'relation' => '',
      // Disambiguate from compare parameter for taxonomy..
      'field_compare' => '',
      'field_compare_2' => '',
      'field_compare_3' => '',
      'field_compare_4' => '',
      'field_compare_5' => '',


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
      'taxonomy_field' => '', // term_id, name, slug
      // Disambiguate from compare parameter for field..
      'tax_compare' => '',
      'tax_compare_2' => '',
      'tax_compare_3' => '',
      'tax_compare_4' => '',
      'tax_compare_5' => '',

      // Checkbox

      'checkbox' => '', 'checkbox_2' => '',

      // Sort

      'orderby' => '', 'key' => '', 'order' => '',
      'orderby_2' => '', 'key_2' => '', 'order_2' => '',
      'orderby_3' => '', 'key_3' => '', 'order_3' => '',
      'orderby_4' => '', 'key_4' => '', 'order_4' => '',
      'orderby_5' => '', 'key_5' => '', 'order_5' => '',

      'series' => '',
      'meta_type' => '',

      // Format

      'strip_tags' => '', 'strip' => '', 'allow' => '',
      'clean' => 'false', 'trim' => '', 'local' => 'true',
      'escape' => '', 'unescape' => '',

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

      'search' => '', // Search keyword

      'fields' => '', 'custom_fields' => '', // CSV list of custom field names to expand
      'blog' => '', // Multi-site (not tested)
      'x' => '', // Just loop X times, no query

      // Cache
      'cache' => 'false',
      'expire' => '10 min',
      'update' => 'false',

      // Timer
      'timer' => 'false',

      'paged' => '',
      'page' => '', // Manually set page
      'maxpage' => '',
      'query' => '', // Alter main query for pagination

      'exists' => '',
      'the_posts' => '', // Used internally for [loop exists]

      'display' => '', // Support The Events Calendar plugin (testing)

      'debug' => '',

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

  static function check_cache( $parameters ) {

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

  static function before_query( &$parameters, $template = null ) {

    $result = apply_filters( 'ccs_loop_before_query', null, $parameters, $template );

    if ( !empty($result) || $result === false ) return $result;

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
          do_ccs_shortcode( self::render_field_tags( $template, $parameters ) ),
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



    /*---------------------------------------------
     *
     * Child parameter
     *
     * child=this - loop through current post's parents from the top
     *
     */

    if ( !empty($parameters['child']) ) {

      $current_id = do_shortcode('[field id]');
      $parent_ids = array();

      // Include current post
      if ( !empty($parameters['include']) && $parameters['include']=='this' ) {
        $parent_ids[] = $current_id;
        unset($parameters['include']);
      }

      $index = 1;
      $max = !empty($parameters['count']) ? $parameters['count'] : 999;

      // Get all parents one by one
      while ($index <= $max && $pid = wp_get_post_parent_id( $current_id )) {
        $parent_ids[] = $current_id = $pid;
        $index++;
      }

      if (empty($parent_ids))
        return self::handle_empty_query( $template );

      // Start from top parent by default
      if ( empty($parameters['reverse']) )
        $parent_ids = array_reverse($parent_ids);

      $parameters['id'] = implode(',', $parent_ids);
      unset($parameters['child']);
    }


    // Continue to query
    return null;

  } // End: before_query


  /*---------------------------------------------
   *
   * Prepare query based on parameters
   *
   */

  static function prepare_query( $parameters ) {

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

    if (CCS_ForEach::$state['is_for_post_type_loop']) {
      $parameters['type'] = CCS_ForEach::$current_post_type['slug'];
    }

    if ( !empty($parameters['type']) ) {

      $query['post_type'] = CCS_Format::explode_list($parameters['type']);

    } else {

      $query['post_type'] = 'any';
    }

    /*---------------------------------------------
     *
     * Post ID, exclude ID, name
     *
     */

    if ( !empty($parameters['include']) ) {
      if ( !empty($parameters['id']) ) $parameters['id'] .= ',';
      $parameters['id'] .= $parameters['include'];
    }

    // Support query multiple slugs
    // $query['post_name__in'] is available in WP 4.4
    if (!empty($parameters['name']) && strpos($parameters['name'], ',')) {
      $parameters['id'] .= ( !empty($parameters['id']) ? ',' : '' ).$parameters['name'];
      unset($parameters['name']);
    }

    if ( !empty($parameters['id']) ) {

      $id_array = CCS_Format::explode_list($parameters['id']);

      foreach ($id_array as $key => $value) {

        // Include current post
        if ( $value=='this' ) {

          // ID of post that contains the loop
          $id_array[$key] = self::$state['original_post_id'];

        // Include child posts and descendants
        } elseif ( $value == 'children' ) {

          // NOTE: This applies only when list=true
          // Otherwise, it's handled by include_children()

          // Query for top-level posts first
          if (empty($parameters['parent'])) {
            $query['post_parent'] = 0;
          }

          // Then manually add children after each post
          self::$state['append_children'] = true;

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

    /*---------------------------------------------
     *
     * Exclude
     *
     */

    // Exclude children by default when sorting by child date
    if ($parameters['orderby']=='child-date'
        && empty($parameters['parent']) // Except when parent is set
        && empty($parameters['exclude'])) {

      $parameters['exclude'] = 'children';
    }

    if ( !empty($parameters['exclude']) ) {

      $id_array = CCS_Format::explode_list($parameters['exclude']);

      foreach ($id_array as $key => $value) {

        // Exclude current post
        if ( $value=='this' ) {

          // ID of post that contains the loop
          $id_array[$key] = self::$state['original_post_id'];

        // Exclude children: top-level parents only
        } elseif ( $value=='children' ) {

          unset($id_array[$key]);
          // Set to 0 to return only top-level entries
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

        $parents = CCS_Format::explode_list( $parent ); // Convert to array
        $parent_IDs = array();
        if (!is_array($parents)) $parents = array($parents);

        foreach ($parents as $each_parent) {

          if ( $each_parent=='this' ) {

            // Get children of current post
            $parent_IDs[] = do_shortcode('[field id]');

          } elseif ( $parent=='same' ) {

            // Get siblings of current post, if it has parent
            $parent_ID = wp_get_post_parent_id( do_shortcode('[field id]') );
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


    /*---------------------------------------------
     *
     * Sticky posts: ignore by default
     *
     */

    if ( empty($parameters['sticky']) ) {
      $query['ignore_sticky_posts'] = true;
    }


    /*---------------------------------------------
     *
     * Search keyword
     *
     */

    if ( !empty($parameters['search']) ) {
      $query['s'] = $parameters['search'];
    }

    /*---------------------------------------------
     *
     * User role
     *
     */

    if ( !empty($parameters['role']) ) {

      if ($parameters['role']=='this') {
        $parameters['role'] = do_shortcode('[user role out="slug"]');
      }

      $roles = CCS_Format::explode_list($parameters['role']);
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

      $authors = CCS_Format::explode_list( $parameters['author'] );

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

      $authors = CCS_Format::explode_list( $parameters['author_exclude'] );

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

      $query['post_status'] = CCS_Format::explode_list( $parameters['status'] );

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
     * Post count, offset, paged
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

        $paged = 1;
        if ( empty($parameters['query']) ) {
          // Get from query string
          $query_var = CCS_Paged::$prefix;
          if ( self::$state['paged_index'] == 0 )
            self::$state['paged_index'] = 1;
          else
            self::$state['paged_index']++;

          if (self::$state['paged_index']>1)
            $query_var .= self::$state['paged_index'];

          $paged = isset($_GET[$query_var]) ? $_GET[$query_var] : 1;
        } if ( $parameters['query']=='default' ) {
          // From permalink
          $paged = max( 1, get_query_var( CCS_Paged::$prefix ) );
        } else {
          $paged = isset($_GET[$parameters['query']]) ? $_GET[$parameters['query']] : 1;
        }

        $query['paged'] = $paged;
      }
    }

    if ( !empty($parameters['page']) ) {
      $query['paged'] = $parameters['page']; // Manually set page
    }

    // Work around if using both offset and pagination
    // Reference: http://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination

    if ( !empty($parameters['paged']) && $query['paged']>1 && !empty($query['offset']) ) {

      $query['offset'] = $query['offset'] + ( ($query['paged'] - 1) * $parameters['count']);

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
     * Category
     *
     */

    if ( !empty($parameters['category']) ) {

      // Category can be slug, ID, multiple

      $category = $parameters['category'];
      $categories = CCS_Format::explode_list( $category, ',+' );

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

        if ( empty($parameters['taxonomy'.$suffix]) ) continue;

        // Disambiguate from compare parameter for field..
        if ( !empty($parameters['tax_compare'.$suffix]) )
          $compare = $parameters['tax_compare'.$suffix];
        elseif ( !empty($parameters['compare'.$suffix]) )
          $compare = $parameters['compare'.$suffix];
        else $compare = 'IN';

        if ($index == 1) {
          $relation = !empty($parameters['relation']) ?
            strtoupper($parameters['relation']) : 'AND';
          $query['tax_query']['relation'] = $relation;
        }

        $taxonomy = $parameters['taxonomy'.$suffix];

        $term = '';
        if ( !empty($parameters['term'.$suffix]) )
          $term = $parameters['term'.$suffix];
        elseif ( !empty($parameters['value'.$suffix]) )
          $term = $parameters['value'.$suffix]; // Alias, if field value is not used

        // Post Format
        if ($taxonomy=='format') {
          $taxonomy = 'post_format';
          $term = 'post-format-'.$term;
        }

        $args = array(
          'taxonomy' => $taxonomy,
          'field' => !empty($parameters['taxonomy_field']) ?
            $parameters['taxonomy_field'] : 'term_id', // name or slug
          'term' => $term,
          'compare' => strtoupper($compare)
        );

        $query['tax_query'][] = self::prepare_tax_query( $args );

      } // End each taxonomy query

    } // End taxonomy query


    /*---------------------------------------------
     *
     * Order and orderby
     *
     */

    if ( !empty($parameters['comment_author']) && empty($parameters['orderby']) )  {
      $parameters['orderby'] = 'comment-date';
    }

    // Order by comment date
    if ( $parameters['orderby']=='comment-date' ) {

      // WP_Query doesn't support it, so sort after getting all posts
      self::$state['parameters']['orderby_comment_date'] = 'true';
      self::$state['parameters']['order_comment_date'] = !empty($parameters['order']) ?
        strtoupper($parameters['order']) : 'DESC';

      $parameters['orderby'] = '';

    // Order by most recently published children
    } elseif ( $parameters['orderby']=='child-date' ) {

      // Pass to process_posts
      self::$state['parameters']['orderby_child_date'] = 'true';
      self::$state['parameters']['order_child_date'] = !empty($parameters['order']) ?
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
      if ($orderby=='id') $orderby = 'ID'; // Must be capitalized
      elseif ($orderby=='field_num') $orderby = 'meta_value_num';
      elseif ($orderby=='field') $orderby = 'meta_value';
      elseif ($orderby=='menu') $orderby = 'menu_order';
      elseif ($orderby=='random') $orderby = 'rand';
      elseif ( !in_array(strtolower($orderby), $default_orderby) ) {

        // If not recognized, assume it's a field name

        if (empty($parameters['field'])) {
          $parameters['key'] = $orderby;
        }
        $orderby = 'meta_value'; // or meta_value_num?
      }

      $query['orderby'] = $orderby;

      if (in_array($orderby, array('meta_value', 'meta_value_num') )) {

        if ( !empty($parameters['key']) )
          $key = $parameters['key'];
        elseif ( !empty($parameters['field']) )
          // If no key is specified, order by field
          $key = $parameters['field'];
        else $key = ''; // No orderby key

        $query['meta_key'] = $key;

        if ( !empty($parameters['meta_type'])) {
          $query['meta_type'] = $parameters['meta_type'];
        }
      }

      if ( empty($parameters['order']) ) {

        // Default order

        if ( in_array($orderby,
          array('meta_value', 'meta_value_num', 'menu_order', 'title', 'name', 'id')
        )) {

          $query['order'] = 'ASC';

        } else {

          $query['order'] = 'DESC';
        }
      }
    }


    /*---------------------------------------------
     *
     * Multiple orderby
     *
     * Sort by multiple fields: orderby_2, orderby_3
     *
     */

    if ( !empty($parameters['orderby_2']) ) {

      $first_orderby = $query['orderby'];
      $to_num = $first_orderby=='meta_value_num' ? '+0' : '';

      // Start building orderby query for filter
      self::$state['multiple_orderby'] = 'mt1.meta_value'.$to_num.' '.$query['order'];

      // Include orderby fields in query
      $query['meta_query'] = array(
        'relation' => 'AND',
        // First orderby field
        array( 'key' => $query['meta_key'], 'compare' => 'EXISTS' ),
      );

      // Up to five orderby fields
      for ($i=2; $i <= 5; $i++) {

        if (empty($parameters['orderby_'.$i])) break;

        // Alias
        if ($parameters['orderby_'.$i] == 'field_num')
          $parameters['orderby_'.$i] = 'meta_value_num';

        if ($parameters['orderby_'.$i] == 'meta_value_num') {
          $next_orderby = $parameters['orderby_'.$i];
          $next_orderby_field = @$parameters['key_'.$i];
        } else {
          $next_orderby = 'meta_value';
          $next_orderby_field = $parameters['orderby_'.$i];
        }

          // Include additional orderby field in query
        $query['meta_query'][] = array( 'key' => $next_orderby_field, 'compare' => 'EXISTS' );

        $next_order = !empty($parameters['order_'.$i]) ?
          strtoupper($parameters['order_'.$i]) : 'ASC';

        $to_num = $next_orderby=='meta_value_num' ? '+0' : '';

        // Add orderby field to database query filter
        self::$state['multiple_orderby'] .= ', mt'.$i.'.meta_value'.$to_num.' '.$next_order;
      }

      if (!empty($next_orderby_field)) {
        add_filter('posts_orderby', array(__CLASS__, 'multiple_orderby_filter'));
      }
    }


    /*---------------------------------------------
     *
     * Sort by series
     *
     */

    if ( !empty($parameters['series']) ) {

      // TODO: Just use range()

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

      self::$state['sort_posts'] = CCS_Format::explode_list($series);
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
     * Published date
     *
     */

    if ( !empty($parameters['year']) || !empty($parameters['month']) ||
      !empty($parameters['day']) ) {

      $q = array();
      $today = getdate();

      if (!empty($parameters['year'])) {
        $year = $parameters['year'];
        if ( $year=='today' || $year=='this' ) $year=$today['year'];
        $q['year'] = $year;
      }
      if (!empty($parameters['month'])) {
        $month = $parameters['month'];
        if ( empty($parameters['year']) ) $q['year'] = $today['year'];
        if ( $month=='today' || $month=='this' ) $month=$today['mon'];
        $q['month'] = $month;
      }
      if (!empty($parameters['day'])) {
        $day = $parameters['day'];
        if ( empty($parameters['year']) ) $q['year'] = $today['year'];
        if ( empty($parameters['month']) ) $q['month'] = $today['mon'];
        if ( $day=='today' || $day=='this' ) $day=$today['mday'];
        $q['day'] = $day;
      }
      $query['date_query'] = array( $q );

    }

    /*---------------------------------------------
     *
     * Date query: field, value, before, after
     *
     */

    // Support multiple date field queries

    $max = 4;
    $now = current_time( 'timestamp' );

    for ($index=0; $index < $max; $index++) {

      if ( $index == 0 ) {
        $suffix = '';
      } else {
        $suffix = '_'.($index+1); // field_2, field_3, ...
      }

      if ( empty($parameters['field'.$suffix]) ) {

        if (!empty($parameters['before'.$suffix]) || !empty($parameters['after'.$suffix])) {

          $today = gmdate('Y-m-d', $now);
          if ( !isset($query['date_query']) ) $query['date_query'] = array();

          if ( !empty($parameters['before'.$suffix]) ) {
            if ($parameters['before'.$suffix]=='today') {
              $date_query = array(
                'before' => $today.' 00:00:00'
              );
            } else {
              $date_query = array(
                'before' => date('Y-m-d H:i:s', strtotime($parameters['before'.$suffix]))
              );
            }
          }

          if ( !empty($parameters['after'.$suffix]) ) {
            if ($parameters['after'.$suffix]=='today') {
              $date_query = array(
                'after' => $today.' 23:59:59'
              );
            } else {
              $date_query = array(
                'after' => date('Y-m-d', strtotime($parameters['after'.$suffix]))
              );
            }
          }

          $query['date_query'][] = $date_query;
//ccs_inspect($date_query);
        }

      } elseif (
          in_array($parameters['field'.$suffix], array('date', 'modified'))
          && !empty($parameters['value'.$suffix])
        ) {

        $today = gmdate('Y-m-d', $now);

        if ( !isset($query['date_query']) ) $query['date_query'] = array();

        if ( $parameters['value'.$suffix] == 'today' ) {
          $date_query = array(
            'after' => $today.' 00:00:00',
            'before' => $today.' 23:59:59'
          );
        } elseif ( $parameters['value'.$suffix] == 'past' ) {
          $date_query = array(
            'before' => $today .' 00:00:00',
          );
        } elseif ( $parameters['value'.$suffix] == 'past-time' ) {
          $date_query = array(
            'before' => gmdate('Y-m-d H:i:s', $now), // Before now
          );
        } elseif ( $parameters['value'.$suffix] == 'future' ) {
          $date_query = array(
            'after' => $today .' 00:00:00', // Includes today
          );
        } elseif ( $parameters['value'.$suffix] == 'future-time' ) {
          $date_query = array(
            'after' => gmdate('Y-m-d H:i:s', $now), // After now
          );
        } elseif ( $parameters['value'.$suffix] == 'future not today' ) {
          $date_query = array(
            'after' => $today .' 23:59:59', // Don't include today
          );
        } else {

          $f = ' date_format="'
            .(!empty($args['date_format']) ? $args['date_format'] : 'Y-m-d')
          .'"';

          $value = $parameters['value'.$suffix];
          if ( $value == 'published' ) {
            $value = do_ccs_shortcode('[field date'.$f.']');
          } elseif ( $value == 'modified' ) {
            $value = do_ccs_shortcode('[field modified'.$f.']');
          } else {
            $value = date('Y-m-d', strtotime($value));
          }

          $date_query = array(
            'after' => $value,
            'before' => $value,
            'inclusive' => true,
          );
        }

        if ($parameters['field'.$suffix]=='modified')
          $date_query['column'] = 'post_modified_gmt';

        $query['date_query'][] = $date_query;

        unset( $parameters['field'.$suffix] ); // Don't do field/value compare

      } else {

        // Other field

        if (
          !empty($parameters['value'.$suffix]) && $parameters['value'.$suffix]=='today-between' ) {

          $today = gmdate('Y-m-d', $now);

          $parameters['after'.$suffix]  = $today.' 00:00:00';
          $parameters['before'.$suffix]  = $today.' 23:59:59';

        }

        if (
          !empty($parameters['before'.$suffix]) && !empty($parameters['after'.$suffix]) ) {

          // Between before and after
          $parameters['value'.$suffix] = strtotime($parameters['after'.$suffix]. ' +0000', $now);
          $parameters['value'.$suffix] .= ',' . strtotime($parameters['before'.$suffix]. ' +0000', $now);

          $parameters['compare'.$suffix] = 'BETWEEN';

        } elseif ( !empty($parameters['before'.$suffix]) ) {

          $parameters['value'.$suffix] = strtotime($parameters['before'.$suffix]. ' +0000', $now);
          $parameters['compare'.$suffix] = !empty($parameters['compare'.$suffix]) ?
            $parameters['compare'.$suffix] : 'OLD';

        } elseif ( !empty($parameters['after'.$suffix]) ) {

          $parameters['value'.$suffix] = strtotime($parameters['after'.$suffix]. ' +0000', $now);
          $parameters['compare'.$suffix] = !empty($parameters['compare'.$suffix]) ?
            $parameters['compare'.$suffix] : 'NEW';

//echo 'AFTER: '.date('r',$parameters['value'.$suffix]).' '.$parameters['value'.$suffix].'<br>';
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

        // Needs both field and value
        if ( empty($parameters['field'.$suffix]) || empty($parameters['value'.$suffix]) ) continue;


        // Disambiguate from compare parameter for taxonomy..
        if ( !empty($parameters['field_compare'.$suffix]) )
          $compare = $parameters['field_compare'.$suffix];
        elseif ( !empty($parameters['compare'.$suffix]) )
          $compare = $parameters['compare'.$suffix];
        else $compare = '=';

        $args = array(
          'field' => $parameters['field'.$suffix],
          'compare' => strtoupper($compare),
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
          } elseif (!empty($parameters['field']) && $parameters['field'] == 'last_viewed' ) {
            $args['date_format'] = 'Y-m-d H:i:s';
          }
        }

        // Support value="1,2,3"
        if ($args['compare'] == 'BETWEEN')
          $values = array($parameters['value'.$suffix]); // Pass directly
        else
          $values = CCS_Format::explode_list($parameters['value'.$suffix]);
        $j = 0; $_args = array();
        if (count($values)>1) {
          $_args['relation'] = 'OR';
        }
        foreach ($values as $value) {
          $args['value'] = $value;
          $_args[] = self::prepare_meta_query( $args );
          $j++;
        }
        if (count($values)>1)
          $query['meta_query'][] = $_args;
        else $query['meta_query'][] = $_args[0];

      } // For each field

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



    if (!empty($parameters['display'])) {
      $query['eventDisplay'] = $parameters['display'];
    }

    return apply_filters( 'ccs_loop_query_filter', $query );

  } // End: prepare query



  static function prepare_meta_query( $args ) {

    $meta_query = array();

    $field = @$args['field'];
    $value = @$args['value'];
    $compare = @$args['compare'];
    $compare = strtoupper($compare);

    // Support for date values

    switch ($value) {
      case 'future':
        $value = 'today';
        $compare = '>=';
        break;
      case 'future not today': // Don't include today
        $value = 'today';
        $compare = '>';
        break;
      case 'future-time':
        $value = 'now';
        $compare = '>=';
        break;
      case 'past':
        $value = 'today';
        $compare = '<';
        break;
      case 'past and today': // Include today
        $value = 'today';
        $compare = '<=';
        break;
      case 'past-time':
        $value = 'now';
        $compare = '<=';
        break;
      case 'past-time':
        $value = 'now';
        $compare = '<=';
        break;
      default:
        // Pass value as it is
        break;
    }

    if ( empty($args['date_format']) ) $args['date_format'] = 'Ymd';
    if ( (isset($args['in']) && $args['in'] == 'timestamp') || ($value == 'now') )
      $args['date_format'] = 'U';

    if (empty($args['date_format'])) {

      // default date format
      if ($value == 'today')
        $args['date_format'] = 'Y-m-d'; // 2014-01-24
      elseif ($value == 'now')
        $args['date_format'] = 'Y-m-d H:i:s'; // 2014-01-24 13:05
    }

    if ( $value == 'today'  || $value == 'now' ) {

      $value = date($args['date_format'], current_time('timestamp') );

    } elseif ( $value == 'today-between' ) {
      $today = gmdate('Y-n-j', current_time('timestamp') );
      $value = date($args['date_format'], strtotime($today.' 00:00:00 +0000'));
      $value .= ' - ' . date($args['date_format'], strtotime($today.' 23:59:59 +0000'));
      $compare = 'BETWEEN';
    } else {

      // published/modified - compare field with current post

      $f = !empty($args['date_format']) ? ' date_format="'.$args['date_format'].'"' : '';
      if ( $value == 'published' ) {
        $value = do_ccs_shortcode('[field date'.$f.']');
      } elseif ( $value == 'modified' ) {
        $value = do_ccs_shortcode('[field modified'.$f.']');
      }
    }
//echo 'COMPARE VALUE:'.$value.'<br>';

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
        $value = CCS_Format::explode_list($value); // Comma-separated list
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

    $terms = CCS_Format::explode_list($term); // Multiple terms possible

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
   */

  static function run_query( $query ) {

    $query = apply_filters( 'ccs_loop_before_run_query', $query );

    self::$query = $query; // Store query parameters

    if (!self::$state['is_nested_loop']) {
      self::$state['do_reset_postdata'] = true; // Reset post data at the end of loop
    }


    // Work around if using both offset and pagination
    // Reference: http://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
    if (
      !empty(self::$state['parameters']['paged']) &&
      !empty(self::$state['parameters']['offset'])
    ) {

      self::$state['adjust_offset'] = self::$state['parameters']['offset'];
      add_filter('found_posts', array(__CLASS__, 'adjust_found_posts'), 1, 2 );
      $result = new WP_Query( $query );
      remove_filter('found_posts', array(__CLASS__, 'adjust_found_posts') );

    } else {

      $result = new WP_Query( $query );

    }

    self::$state['wp_query'] = apply_filters( 'ccs_loop_after_run_query', $result );

    if (self::$state['parameters']['query']=='default') {
     self::$state['alter_query'] = self::$state['wp_query'];
    }
    return self::$state['wp_query'];
  }



  static function adjust_found_posts( $found_posts, $query ) {
    $found_posts -= self::$state['adjust_offset'];
    return $found_posts;
  }



  static function prepare_posts( $posts ) {

    $parameters = self::$state['parameters'];

    // Sort by series

    if ( !empty($parameters['series']) ) {

      usort( $posts->posts, array(__CLASS__, 'sort_by_series') );

    // Random order
    } elseif ( $parameters['orderby'] == 'rand' ) {

      shuffle( $posts->posts );

    // Sort by comment date
    } elseif ( !empty($parameters['orderby_comment_date']) &&
      $parameters['orderby_comment_date']=='true' ) {

      usort( $posts->posts, array(__CLASS__, 'orderby_comment_date') );

    // Sort by most recently published children
    } elseif ( !empty($parameters['orderby_child_date']) &&
      $parameters['orderby_child_date']=='true' ) {

      usort( $posts->posts, array(__CLASS__, 'orderby_child_date') );
    }


    $posts = apply_filters( 'ccs_loop_prepare_posts', $posts );

    return $posts;
  }



  /*---------------------------------------------
   *
   * Loop through each post and compile template
   *
   */

  static function compile_templates( $posts, $template, $check_posts = true ) {


    // Store current post reference
    global $post;
    $prev_post = $post;


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

        if (empty($this_post)) continue;

        self::$state['loop_count']++;

        // Inform [content]
        $depth = ++CCS_Content::$state['depth'];
        CCS_Content::$state['current_ids'][ $depth ] = self::$state['current_post_id'];


        $this_template = self::render_template(
          self::prepare_each_template($template)
        );

        // Append children after their parent
        // when include=children and list=true
        if ( self::$state['append_children'] )
          $this_template .= self::append_children($template);

        $templates[] = $this_template;


        // Restore [content] depth
        CCS_Content::$state['depth']--;
        unset(CCS_Content::$state['current_ids'][ $depth ]);

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

    // Restore post reference
    $post = $prev_post;

    return $templates;
  }

  /*---------------------------------------------
   *
   * Append children when include=children and list=true
   *
   */

  static function append_children($template) {

    $this_template = '';

    $args = self::$original_parameters;

    // Store current descendant level
    $keep_state = $args;

    $args['parent'] = self::$state['current_post_id'];
    $args['include'] = 'children'; // Recursive loop

    // Remove limit to root post
    if (!empty($args['id'])) unset($args['id']);
    if (!empty($args['name'])) unset($args['name']);

    $args['level'] = isset($args['level']) ? intval($args['level']) : 999;

//ccs_inspect('Descendant level: '.$args['level'].' Parent: '.$args['parent']);

    // Subtract for next generation
    $args['level']--;

    if ($args['level'] > 0) {

      $this_template .= CCS_If::if_shortcode(
        array('children'), // has children
        self::the_loop_shortcode($args, $template)
      );
    }

    // Restore current descendant level
    self::$original_parameters = $keep_state;

    return $this_template;
  }

  /*---------------------------------------------
   *
   * Pre-process template: if first, last, empty
   *
   */

  static function pre_process_template( $template ) {

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

  static function extract_else( &$template ) {
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

  static function prepare_all_posts( $query_object ) {

    $parameters = self::$state['parameters'];
    $query = self::$query;
    $state =& self::$state; // Update global state directly

    $state['post_count'] = $query_object->post_count;
    $all_posts = $query_object->posts;

    $state['all_ids'] = array();
    foreach ($all_posts as $post) {
      $state['all_ids'][] = $post->ID;
    }

    if ( isset($query['meta_query'][0]) && isset($query['meta_query'][0]['compare'])) {
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

        } // field value start


        /*---------------------------------------------
         *
         * Checkbox query
         *
         */

        $skip_1 = false;

        if (!empty($parameters['checkbox']) && !empty($parameters['value'])) {

          $values = CCS_Format::explode_list($parameters['value']);
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

          $skip_2 = false;
          if ( !empty($parameters['checkbox_2']) && !empty($parameters['value_2']) ) {
            $values = CCS_Format::explode_list($parameters['value_2']);
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


  static function prepare_each_post( $post ) {

    $post_id = $post->ID;

    // Skip
    if ( in_array($post_id, self::$state['skip_ids']) ) {
      return null;
    }

    return apply_filters('ccs_loop_each_post', $post);
  }


  static function prepare_each_template( $template ) {

    $state = self::$state;
    $parameters = self::$state['parameters'];

    // Clean each template of <br> and <p>
    if ($parameters['clean']=='true') {
      $template = CCS_Format::clean_content( $template );
    }


    // Filter by comment author
    if ( !empty($parameters['comment_author']) ) {
      $template =
        '[----if comment_author='.$parameters['comment_author'].']'
          .$template
        .'[/----if]';
    }

    // Make sure to limit by count parameter

    if ( !empty(self::$state['parameters']['count']) &&
      ( $state['loop_count'] > $parameters['count']) )
      return null;

    return $template;
  }



  /*---------------------------------------------
   *
   * Render template: expand {FIELD} tags and shortcodes
   *
   */

  static function render_template( $template ) {

    $template = self::render_field_tags( $template, self::$state['parameters'] );

    $template = do_local_shortcode( 'loop', $template, false );

    if (self::$state['parameters']['local']=='true')
      $template = do_ccs_shortcode( $template );
    else
      $template = do_shortcode( $template );

    $template = apply_filters('ccs_loop_each_result', $template, self::$state['parameters'] );

    return $template;
  }


  /*---------------------------------------------
   *
   * Process results array to final output
   *
   */

  static function process_results( $results ) {

    $parameters = self::$state['parameters'];

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
     * Escape/unescape HTML
     *
     */

    if ( $parameters['escape'] == 'true' ) {
      $new_results = array();
      foreach ($results as $result) {
        $new_results[] = esc_html($result);
      }
      $results = $new_results;
    }

    if ( $parameters['unescape'] == 'true' ) {
      $new_results = array();
      foreach ($results as $result) {
        $new_results[] = htmlspecialchars_decode($result);
      }
      $results = $new_results;
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

      $list_tag = ($parameters['list']=='true') ? 'ul' : $parameters['list'];
      $list_class = !empty($parameters['list_class']) ?
        ' class="'
          .implode(' ', array_map('trim', explode(',', $parameters['list_class'])))
        .'"' : '';
      $list_style = !empty($parameters['list_style']) ?
        ' style="'.$parameters['list_style'].'"' : '';

      $item_tag = !empty($parameters['item']) ? $parameters['item'] : 'li';
      $item_class = !empty($parameters['item_class']) ?
        ' class="'
          .implode(' ', array_map('trim', explode(',', $parameters['item_class'])))
        .'"' : '';
      $item_style = !empty($parameters['item_style']) ?
        ' style="'.$parameters['item_style'].'"' : '';

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

  static function close_loop(){

    $state =& self::$state;
    $parameters = self::$state['parameters'];

    // Stop timer

    if ( self::$state['parameters']['timer'] == 'true' ) {
      echo CCS_Cache::stop_timer('<br><b>Loop result</b>: ');
    }

    // Multiple orderby filter

    if ( !empty($state['multiple_orderby']) ) {
      remove_filter('posts_orderby', array(__CLASS__, 'multiple_orderby_filter'));
    }

    // Reset postdata after WP_Query

    if (self::$state['do_reset_postdata']) {
      wp_reset_postdata();
      self::$state['do_reset_postdata'] = false;
      //global $post;
      //$post = $state['prev_post'];
    }

    // If blog was switched on multisite, retore original blog

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

  static function render_columns( $items, $column_count, $pad = null, $between_row ) {

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
          do_ccs_shortcode( $each_row ), self::$state['parameters']);
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

  static function render_field_tags( $template, $parameters ) {

    $post_id = !empty($parameters['id']) ? $parameters['id'] : get_the_ID();

    /*---------------------------------------------
     *
     * User defined fields
     *
     */

    if ( !empty($parameters['fields']) ) {

      $fields = CCS_Format::explode_list($parameters['fields']);

      foreach ( $fields as $this_field ) {

        if ( class_exists('CCS_To_ACF') &&
            CCS_To_ACF::$state['is_repeater_or_flex_loop'] ) {

          // Repeater or flexible content field: then get sub field
          if (function_exists('get_sub_field')) {
            $field_value = get_sub_field( $this_field );
          } else $field_value = null;

        } else {

          // Enable predefined fields
          $field_value = CCS_Content::get_prepared_field( $this_field, $post_id );

        }

        if (is_array($field_value)) {

          foreach ($field_value as $key => $value) {
            $template = str_replace('{'.strtoupper($this_field).':'.strtoupper($key).'}', $value, $template);
          }

          // For replacing {FIELD} with array
          $field_value = ucwords(implode(', ', $field_value));
        }
        $template = str_replace('{'.strtoupper($this_field).'}', $field_value, $template);
      }
    }

    return $template;
  }


  /*---------------------------------------------
   *
   * Multiple orderby filter
   *
   */

  static function multiple_orderby_filter( $orderby ) {

    if ( ! empty(self::$state['multiple_orderby']) ) {

//echo 'ORDERBY:'.self::$state['multiple_orderby'].'<br>';

      return self::$state['multiple_orderby'];
    }

    return $orderby;
  }




/*---------------------------------------------
 *
 * Helper functions
 *
 */

  // Get text between two strings

  static function get_between($start, $end, $text) {

    $middle = explode($start, $text);
    if (isset($middle[1])){
      $middle = explode($end, $middle[1]);
      $middle = $middle[0];
      return $middle;
    } else {
      return false;
    }
  }


  // Explode the list, trim each item and put it back together

  static function clean_list( $list, $delimiter = '' ) {

    if (empty($delimiter)) $delimiter = ',';
    $list = CCS_Format::explode_list($list, $delimiter);
    return implode($delimiter,$list);
  }


  /*---------------------------------------------
   *
   * Sort series
   *
   */

  static function sort_by_series( $a, $b ) {

    $apos = array_search( get_post_meta( $a->ID, self::$state['sort_key'], true ), self::$state['sort_posts'] );
    $bpos = array_search( get_post_meta( $b->ID, self::$state['sort_key'], true ), self::$state['sort_posts'] );

    return ( $apos < $bpos ) ? -1 : 1;
  }

  /*---------------------------------------------
   *
   * Orderby comment date
   *
   */

  static function orderby_comment_date( $a, $b ) {

    $parameters = self::$state['parameters'];
    $order = $parameters['order_comment_date'];

    $a_comment_date = self::get_comment_timestamp( $a );
    $b_comment_date = self::get_comment_timestamp( $b );

    // echo 'Compare '.$a_comment_date.' and '.$b_comment_date.'<br>';

    if ($order=='ASC') {
      return ( $a_comment_date < $b_comment_date ) ? -1 : 1;
    } else {
      return ( $a_comment_date > $b_comment_date ) ? -1 : 1;
    }
  }

  static function get_comment_timestamp( $post ) {
    $comments = get_comments(array(
      'post_id' => $post->ID, 'number' => 1, 'status' => 'approve'
    ));

    $comment_date = '';
    foreach ($comments as $comment) {
      $comment_date = $comment->comment_date;
    }
    return !empty($comment_date) ? strtotime($comment_date) : 0;
  }

  /*---------------------------------------------
   *
   * Orderby child publish date
   *
   */

  static function orderby_child_date( $a, $b ) {

    $parameters = self::$state['parameters'];
    $order = $parameters['order_child_date'];

    // Posts with no child have lowest priority
    // Ascending: give largest possible integer
    // Descending: give zero
    $minmax = $order=='ASC' ? PHP_INT_MAX : 0;

    $a_child_date = self::get_child_timestamp( $a, $minmax );
    $b_child_date = self::get_child_timestamp( $b, $minmax );

//echo 'Compare '.$a_child_date.' and '.$b_child_date.'<br>';

    if ($order=='ASC') {
      return ( $a_child_date < $b_child_date ) ? -1 : 1;
    } else {
      return ( $a_child_date > $b_child_date ) ? -1 : 1;
    }
  }

  static function get_child_timestamp( $post, $minmax ) {

    $parameters = self::$state['parameters'];

    $id = $post->ID;
    $timestamp = do_ccs_shortcode(
      '[loop parent='.$id.' orderby=date count=1][field date date_format=U][/loop]'
    );

    if (empty($timestamp)) {
      if ($parameters['parents']=='true') {
        // Skip
        self::$state['skip_ids'][] = $id;

      } elseif ($parameters['parents']=='equal') {
        // Compare by parent date
        $timestamp = do_ccs_shortcode(
          '[loop id='.$id.'][field date date_format=U][/loop]'
        );
        $timestamp = !empty($timestamp) ? intval($timestamp) : $minmax;
      } else {
        // Put them at the end
        $timestamp = $minmax;
      }
    } else {
      $timestamp = intval($timestamp);
    }

    return $timestamp;
  }


  // Get post slug from ID

  static function get_the_slug( $id = '' ) {

    global $post;

    $this_post = !empty($id) ? get_post($id) : $post;
    return !empty($this_post) ? $this_post->post_name : '';
  }

  static function get_post_id( $parameters ) {

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
   * [loop-count] - Display loop count
   *
   */

  static function loop_count_shortcode( $atts ) {

    // From current or last finished loop

    if (!is_array($atts)) $atts = array();

    if (count(array_filter($atts))==0 && !CCS_ForEach::$state['is_for_post_type_loop'])

      return CCS_Loop::$state['loop_count'];

    // From given query

    else {

      $result = CCS_Loop::the_loop_shortcode( $atts, '.' );
      return strlen($result);
    }
  }


  // Unused, undocumented - TODO: remove

  static function found_posts_shortcode() {
    global $wp_query;
    return !empty($wp_query) ? $wp_query->post_count : 0;
  }

  static function search_keyword_shortcode() {
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

  static function next_shortcode( $atts, $content, $tag ) {

    // Flip when looking for older and order is ASC (old to new)
    if ( $tag == 'older' && strtolower(self::$state['parameters']['order'])=='asc' ) {
        self::prev_shortcode( $atts, $content, $tag );
    }

    $current_id = self::$state['current_post_id'];
    $all_ids = self::$state['all_ids'];
    $result = '';

    if ( ($find_key = array_search($current_id, $all_ids)) !== false) {
      if (isset( $all_ids[$find_key + 1] )) { // Next in loop
        $prev_id = $all_ids[$find_key + 1];
        $result = do_ccs_shortcode( '[loop id='.$prev_id.']'.$content.'[/loop]' );
      }
    }
    return $result;
  }


  static function prev_shortcode( $atts, $content, $tag ) {

    // Flip when looking for newer and order is ASC (old to new)
    if ( $tag == 'newer' && strtolower(self::$state['parameters']['order'])=='asc' ) {
        self::next_shortcode( $atts, $content, $tag );
    }

    $current_id = self::$state['current_post_id'];

    $all_ids = self::$state['all_ids'];
    $result = '';

    if ( ($find_key = array_search($current_id, $all_ids)) !== false) {
      if (isset( $all_ids[$find_key - 1] )) { // Prev in loop
        $prev_id = $all_ids[$find_key - 1];
        $result = do_ccs_shortcode( '[loop id='.$prev_id.']'.$content.'[/loop]' );
      }
    }
    return $result;
  }


  static function prev_next_shortcode( $atts, $content ) {

    $content = '[if id=this]'.$content.'[/if]';
    if (!isset($atts['type'])) $atts['type'] = get_post_type();

    return self::the_loop_shortcode( $atts, $content );
  }


  function shortcode_comment( $atts, $content, $tag ) {
    if ($tag == '!' && !empty($content)) return '<!--'.do_shortcode($content).'-->';
  }


   /*---------------------------------------------
    *
    * Default query loop
    *
    */

  function default_loop_shortcode( $atts, $template ) {

    if (self::$state['is_loop']) {
      // Inside [loop exists]
      return self::the_loop_shortcode( self::$original_parameters, $template );
    }

    // If empty
    $if_empty = self::get_between('[if empty]', '[/if]', $template);
    $content = str_replace($if_empty, '', $template);

    $max = isset($atts['count']) ? $atts['count'] : 9999;
    $count = 0;

    ob_start();

    if ( have_posts() ) {
      while ( have_posts() ) {
        $count++;
        if ( $count > $max ) break;
        the_post(); // Set up post data
        echo do_ccs_shortcode( $content );
      }
    } elseif (!empty($if_empty)) {
        echo do_ccs_shortcode( $if_empty );
    }

    return ob_get_clean();
  }



  function include_children( $result, $atts, $content ) {

    // Get unfiltered parameters
    $atts = self::$original_parameters;

    if (
        (isset($atts['level']) || (isset($atts['include']) && $atts['include']=='children'))
        && !isset($atts['list']) // Handle list separately
      ) {

      self::$state['descendant_level'] = isset($atts['level']) ?
        intval($atts['level']) : 999; // Max level

      // Remove these from further queries
      $atts = self::clean_keys($atts, array('include','level'));

      // Get root post IDs
      $_atts = self::clean_keys($atts, array('list','column'));;
      if (!isset($atts['parent'])) {
        // Exclude children unless parent is given
        $_atts['exclude'] = (
          isset($atts['exclude']) ? $atts['exclude'].',' : ''
        ).'children';
      }

      $ids = self::the_loop_shortcode($_atts, '[field id],');
      $ids = array_filter(explode(',',$ids));

//ccs_inspect('Root posts', $_atts, $ids);

      // Get all children

      // Remove limit to root post if any
      $atts = self::clean_keys($atts, array('parent','name','id'));

      // Pass filtered shortcode atts
      $all_ids = self::get_children_recursive($ids,
        // Remove parameters that interfere with gathering IDs
        self::clean_keys($atts, array('list','column'))
      );


      // Compile all posts with original atts

      $atts['id'] = implode(',',$all_ids);
      $result = self::the_loop_shortcode($atts, $content);

//ccs_inspect('All IDs', $all_ids);

      if (empty($result)) $result = false;
      return $result;
    }
  }



  static function clean_keys($atts, $blacklist) {
    foreach ($blacklist as $key) {
      unset($atts[$key]);
    }
    return $atts;
  }

  static function get_children_recursive($ids, $atts) {

    self::$state['descendant_level']--;
    if (self::$state['descendant_level']<=0) return $ids; // Done

    $add_ids = array();

    foreach ($ids as $id) {

      $add_ids[] = $id;

      // Children
      $atts['parent'] = $id;
      $children_ids = self::the_loop_shortcode($atts, '[field id],');
      $children_ids = array_filter(explode(',',$children_ids));

//ccs_inspect('Children of '.$id.': ', $children_ids, $atts);

      $add_ids = array_unique(array_merge($add_ids, $children_ids));

      // Further descendants

      foreach ($children_ids as $child_id) {
        $atts['parent'] = $child_id;
        $current = self::$state['descendant_level'];

        $descendant_ids = self::get_children_recursive(array($child_id), $atts);

        self::$state['descendant_level'] = $current;

//ccs_inspect('Level: '.$current, 'Find children of '.$child_id,$atts, $descendant_ids);

        $add_ids = array_unique(array_merge($add_ids, $descendant_ids));
      }
    }

    return $add_ids;
  }

} // End CCS_Loop
