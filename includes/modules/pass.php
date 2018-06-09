<?php

/*---------------------------------------------
 *
 * Pass shortcode - pass values
 *
 */

new CCS_Pass;

class CCS_Pass {

  static $vars = array();
  static $shorts = array();

  function __construct() {

    add_ccs_shortcode( array(
      'pass' => array($this, 'pass_shortcode'),
      '-pass' => array($this, 'pass_shortcode'),
      '--pass' => array($this, 'pass_shortcode'),
      '---pass' => array($this, 'pass_shortcode'),
      'set' => array( $this, 'set_shortcode' ),
      'get' => array( $this, 'get_shortcode' ),
      'short' => array( $this, 'short_shortcode' ),
    ));
  }

  static function pass_shortcode( $atts, $content, $shortcode_name ) {

    $args = array(

      'field' => '',
      'date_format' => '',
      'in' => '', // in=timestamp

      'fields' => '',
      'array' => '',
      'debug' => '', // For inspecting array
      'field_loop' => '',     // Field is array or comma-separated list
      'taxonomy_loop' => '',    // Loop through each term in taxonomy
      'list' => '',         // Loop through an arbitrary list of items
      'acf_gallery' => '',    // Pass image IDs from ACF gallery field

      'current' => '',
      'orderby' => '',      // Default: order by taxonomy term name
      'order' => '',
      'hide_empty' => 'false',

      'pre_render' => 'false',  // do_shortcode before replacing tags?
      'post_render' => 'true',  // do_shortcode at the end

      'trim' => 'false',
      'escape' => '', // Escape field value

      'count' => '99999',      // Max number of taxonomy terms

      'user_field' => '',
      'user_fields' => '', // Multiple

      'global' => '',
      'sub' => '',
      'random' => '',
    );


    $atts = self::replace_variables( $atts );

    extract( shortcode_atts( $args , $atts, true ) );

    // Shortcuts
    if ( isset($atts[0]) ) {
      if ( $atts[0]==='route' ) $global = 'route';
      elseif ( $atts[0]==='vars' ) $global = 'vars';
    }


    if ( $pre_render == 'true' ) $content = do_ccs_shortcode( $content );

    // This should get the current post in all contexts
    $post_id = do_shortcode('[field id]');

    // Support nested
    $prefix = CCS_Format::get_minus_prefix($shortcode_name);

    if ( !empty($date_format) ) {
      // Date format: allow escape via "//" because "\" disappears in shortcode parameters
      $date_format = str_replace("//", "\\", $date_format );
    }


    /*---------------------------------------------
     *
     * Pass single field to {FIELD}
     *
     */

    if ( !empty($global) && empty($field) && $field!='0' ) $field = 'this';
    if ( !empty($array) ) $field = $array;

    if ( !empty($field) || $field=='0' ) {

      $is_formatted = true;
      if ($field=='gallery') $field = '_custom_gallery'; // Support CCS gallery field

      // Pass global variable
      if ( !empty($global) ) {

        $field_value = '';

        if ( $global=='route' ) {

          // Parsed URL route
          $request = CCS_URL::get_route();
          $requests = CCS_URL::get_routes();

          if ($field=='this') {

            $field_value = $request; // whole thing

            for ($i=0; $i < 6; $i++) {
              $part = '';
              if (isset($requests[$i])) {
                $part = $requests[$i];
              }
              $tag = '{'.$prefix.'ROUTE_'.($i+1).'}';
              $content = str_replace($tag, $part, $content);
              // Deprecate
              $tag = '{'.$prefix.'FIELD_'.($i+1).'}';
              $content = str_replace($tag, $part, $content);
            }

          } else {
            if (isset($requests[ intval($field) ]))
              $field_value = $requests[ intval($field) ];
          }

        } elseif ( $global=='query' ) {

          // Parsed query string
          $field_value = CCS_URL::get_query();
          $query_array = CCS_URL::get_queries();

          foreach ($query_array as $key => $value) {
            $tag = '{'.$prefix.(strtoupper($key)).'}';
            $content = str_replace($tag, $value, $content);
          }
          if (!empty($fields)) {
            // Remove what was not rendered
            $fields = CCS_Format::explode_list($fields);
            foreach ($fields as $key) {
              $tag = '{'.$prefix.(strtoupper($key)).'}';
              $content = str_replace($tag, '', $content);
            }
            $fields = '';
          } elseif (!empty($field)) {
            $tag = '{'.$prefix.'FIELD'.'}';
            $value = isset($query_array[$field]) ? $query_array[$field] : '';
            $content = str_replace($tag, $value, $content);
          }

        } elseif ( $global=='vars' ) {

          foreach (self::$vars as $key => $value) {
            $tag = '{'. $prefix . strtoupper($key) . '}';
            $content = str_replace($tag, $value, $content);
          }

        } else {
          if ( $field == 'this' && isset($GLOBALS[$global]) ) {
            $field_value = $GLOBALS[$global];
          } elseif ( !empty($sub) && isset($GLOBALS[$global][$field][$sub]) ) {
            $field_value = $GLOBALS[$global][$field][$sub];
          } elseif (isset($GLOBALS[$global][$field])) {
            $field_value = $GLOBALS[$global][$field];
          }
        }
      // end global

      // Repeater or flexible content field
      } elseif (class_exists('CCS_To_ACF') && CCS_To_ACF::$state['is_repeater_or_flex_loop'] ) {
        if ($field == 'index'){
          $field_value = CCS_To_ACF::$state['repeater_index'];
        } elseif (function_exists('get_sub_field')) {
          $field_value = get_sub_field( $field );
        } else $field_value = null;

      // Predefined or custom field
      } else {
        //$field_value = CCS_Content::get_prepared_field( $field, $post_id );
        // Slower but will get date format, etc.
        $field_value = CCS_Content::content_shortcode($atts);
        $is_formatted = true;
      }

      if (is_array($field_value)) {

        if (!empty($array)) {

          if ($debug=='true') {

            ob_start();
            echo '<pre><code>';
            print_r($field_value);
            echo '</code></pre>';
            return ob_get_clean();
          }

          // array parameter
          foreach ($field_value as $key => $value) {
            $content = str_replace('{'.strtoupper($key).'}', $value, $content);
          }
        } else {
          // field parameter
          foreach ($field_value as $key => $value) {
            $content = str_replace('{'.$prefix.'FIELD:'.strtoupper($key).'}', $value, $content);
          }
        }

        //$field_value = '(Array)';
        $field_value = implode(",", $field_value);

      } else {

        // Clean extra spaces if it's a list
        $field_value = CCS_Loop::clean_list($field_value);

        // Escape value
        if ($escape=='true') {
          $field_value = CCS_Format::escape_shortcode(
            array('html' => 'true'), $field_value
          );
        } /* elseif ($special=='true') {
          $field_value = CCS_Format::escape_shortcode(
            array('special' => 'true'), $field_value
          );
        } */
      }


      if ( !$is_formatted && !empty($date_format) ) {
        // Date format for values other than predefined or custom field
        if ( !empty($in) && ($in=='timestamp') && is_numeric($field_value) ) {
          $field_value = gmdate("Y-m-d H:i:s", $field_value);
        }
        if ($date_format=='true') $date_format = get_option('date_format');
        $field_value = mysql2date($date_format, $field_value);
      }

      // Replace it

      $content = str_replace('{'.$prefix.'FIELD}', $field_value, $content);

//introspect($field_value, $content);

    /*---------------------------------------------
     *
     * Pass each item in a list stored in a field
     *
     */

    } elseif (!empty($field_loop)) {

      if ( $field_loop=='gallery' && class_exists('CCS_Gallery_Field')) {

        // Support gallery field

        $field_values = CCS_Gallery_Field::get_image_ids();

      } else {

        $field_values = get_post_meta( $post_id, $field_loop, true );
      }


      if (!empty($field_values)) {

        if (!is_array($field_values))
          $field_values = CCS_Format::explode_list($field_values); // Get comma-separated list of values

        $contents = null;

        // Loop for the number of field values

        foreach ($field_values as $field_value) {

          $contents[] = str_replace('{'.$prefix.'FIELD}', $field_value, $content);
        }

        $content = implode('', $contents);
      }

    /*---------------------------------------------
     *
     * Pass image IDs from ACF gallery
     *
     */

    } elseif (!empty($acf_gallery)) {

      if ( function_exists('get_field') && function_exists('get_sub_field') ) {
        $field = $acf_gallery;
        $images = get_field($acf_gallery, $post_id, false);
        if (empty($field_value)) {
          // Try sub field
          $images = get_sub_field($acf_gallery, $post_id, false);
        }
        if (!empty($images)) {

          $ids = array();
          foreach ($images as $image) {
            $ids[] = $image['id'];
          }
          if (is_array($ids))
            $replace = implode(',', $ids);
          else $replace = $ids;
          $content = str_replace('{'.$prefix.'FIELD}', $replace, $content);
        }
      }


    /*---------------------------------------------
     *
     * Pass each taxonomy term
     *
     */

    } elseif (!empty($taxonomy_loop)) {

      if ( $current=='true' ) {

        if ( empty($orderby) && empty($order) ) {

          // Doesn't accept order/orderby parameters - but it's cached
          $terms = get_the_terms( $post_id, $taxonomy_loop );
        } else {

          $terms = wp_get_object_terms( $post_id, $taxonomy_loop, array(
            'orderby' => empty($orderby) ? 'name' : $orderby,
            'order' => empty($order) ? 'ASC' : strtoupper($order),
          ));

        }

      } else {

        // Get all terms: not by post ID
        $terms = get_terms( $taxonomy_loop, array(
          'orderby' => empty($orderby) ? 'name' : $orderby,
          'order' => empty($order) ? 'ASC' : strtoupper($order),
          'hide_empty' => ($hide_empty=='true') // Boolean
        ));
      }

      $contents = '';

      // Loop through each term

      if ( !empty( $terms ) ) {

        $i = 0;

        foreach ($terms as $term) {

          if ($i++ >= $count) break;

          $slug = $term->slug;
          $id = $term->term_id;
          $name = $term->name;

          $replaced_content = str_replace('{'.$prefix.'TERM}',
            $slug, $content);
          $replaced_content = str_replace('{'.$prefix.'TERM_ID}',
            $id, $replaced_content);
          $replaced_content = str_replace('{'.$prefix.'TERM_NAME}',
            $name, $replaced_content);

          $contents .= $replaced_content;
        }
      }

      $content = $contents;
    }


    /*---------------------------------------------
     *
     * Pass a list of items
     *
     */

    if (!empty($list)) {

      $items = CCS_Format::explode_list($list); // Comma-separated list -> array

      // Create range
      $new_items = array();
      foreach ($items as $item) {
        if ( strpos($item, '~') !== false ) {
          $pair = CCS_Format::explode_list($item, '~');
          $list = range( $pair[0], $pair[1] );
          foreach ($list as $list_item) {
            $new_items[] = $list_item;
          }
        } else {
          $new_items[] = $item;
        }
      }
      $items = $new_items;


      $contents = '';
      $item_index = 0;

      foreach ($items as $item) {

        $item_index++; // Item index starts at 1

        $replaced_content = $content;

        // Multiple items per loop
        if ( strpos($item, ':') !== false ) {

          $parts = explode(':', $item);
          $count = count($parts);
          for ($i=0; $i < $count; $i++) {

            $this_item = trim($parts[$i]);

            // Subitem index starts at ITEM_1
            $replaced_content = str_replace(
              '{'.$prefix.'ITEM_'.($i+1).'}', $this_item, $replaced_content);

            // Capitalized item
            $replaced_content = str_replace(
              '{'.$prefix.'Item_'.($i+1).'}', ucfirst($this_item), $replaced_content);
          }

        } else {
          $replaced_content = str_replace('{'.$prefix.'ITEM}',
            $item, $replaced_content);
          $replaced_content = str_replace('{'.$prefix.'Item}',
            ucfirst($item), $replaced_content );
        }

        $replaced_content = str_replace(
          '{'.$prefix.'INDEX}', $item_index, $replaced_content);

        $contents .= $replaced_content;
      }

      $content = $contents;
    }


    /*---------------------------------------------
     *
     * Pass user field(s)
     *
     */

    if (!empty($user_field)) {
      $user_field_value = do_shortcode('[user '.$user_field.' out="slug"]');
      // Replace it
      $content = str_replace('{'.$prefix.'USER_FIELD}', $user_field_value, $content);
    }

    if (!empty($user_fields)) {
      $user_fields_array = CCS_Format::explode_list($user_fields);

      foreach ($user_fields_array as $this_field) {
        $user_field_value = do_shortcode('[user '.$this_field.' out="slug"]');
        // Replace {FIELD_NAME}
        $content = str_replace('{'.$prefix.strtoupper($this_field).'}', $user_field_value, $content);
      }
    }

    if ( !empty($random) ) {
      $content = str_replace('{'.$prefix.'RANDOM}', do_shortcode('[random '.$random.']'), $content);
    }


    if ( !empty($fields) ) {

      if ( !empty($global) ) {

        $fields = CCS_Format::explode_list($fields);

        foreach ($fields as $this_field) {
          $tag = '{'.$prefix.strtoupper($this_field).'}';
          $value = '';
          if (isset($GLOBALS[$global][$this_field])) {
            $value = $GLOBALS[$global][$this_field];
          }
          $content = str_replace($tag, $value, $content);
        }

      } else {

        // Replace these fields (+default)
        $content = CCS_Loop::render_field_tags( $content, array('fields' => $fields) );
      }
    } else {
      $content = CCS_Loop::render_field_tags( $content, array() );
    }

    if ( $post_render == 'true' ) $content = do_ccs_shortcode($content);

    // Trim trailing white space and comma
    if ( $trim != 'false' ) {

      if ($trim=='true') $trim = null;
      $content = rtrim($content, " \t\n\r\0\x0B,".$trim);
    }

    return $content;

  } // End pass shortcode




  static function set_shortcode( $atts, $content = '' ) {

    if (!empty($atts['short'])) {
      self::$shorts[$atts['short']] = array(
        'template' => $content,
        'optional' => !empty($atts['optional'])
          ? CCS_Format::explode_list($atts['optional'])
          : array()
      );
      return;
    }

    $var = !empty($atts['var']) ? $atts['var']
      : ( !empty($atts[0]) ? $atts[0] : '');

    if (empty($var)) return;

    // Do shortcode by default
    if ( empty($atts['shortcode']) ) $content = do_ccs_shortcode($content);

    if ( !empty($atts['trim']) ) {
      if ($atts['trim']==='all') {
        // Strip white space, tabs, carriage returns, etc.
        $content = preg_replace(
          "/(\t|\n|\v|\f|\r| |\xC2\x85|\xc2\xa0|\xe1\xa0\x8e|\xe2\x80[\x80-\x8D]|\xe2\x80\xa8|\xe2\x80\xa9|\xe2\x80\xaF|\xe2\x81\x9f|\xe2\x81\xa0|\xe3\x80\x80|\xef\xbb\xbf)+/", '', $content);
      } else {
        $trim = ($atts['trim'] == 'true' ? '' : $atts['trim']);
        $content = trim($content, " \t\n\r\0\x0B,".$trim);
      }
    }

    self::$vars[ $var ] = $content;
  }

  static function get_shortcode( $atts ) {

    if ( empty($atts[0]) || !isset(self::$vars[ $atts[0] ]) ) return;

    return self::$vars[ $atts[0] ];
  }

  static function replace_variables( $atts ) {

    if (!isset($atts) || !is_array($atts)) return;

    foreach ($atts as $key => $value) {

      if ( empty($value[0]) || $value[0]!=='$' ) continue;

      $value = substr($value, 1); // Remove the '$'
      $atts[$key] = self::get_shortcode( array( $value ));
    }

    return $atts;
  }

  function short_shortcode( $atts, $content = '' ) {

    if (isset($atts[0])) {
      $block = $atts[0];
      unset($atts[0]);
    }

    if (empty($block) || !isset(self::$shorts[$block])) return;

    $atts['content'] = $content;

    foreach (self::$shorts[$block]['optional'] as $key) {
      if (!isset($atts[$key])) $atts[$key] = '';
    }

    return do_ccs_shortcode(
      self::render_template(self::$shorts[$block]['template'], $atts),
      $global = false
    );
  }

  function render_template($template, $vars) {
    foreach ($vars as $key => $value) {
      $key = '{'.strtoupper($key).'}';
      $template = str_replace($key, $value, $template);
    }
    return $template;
  }

} // End CCS_Pass
