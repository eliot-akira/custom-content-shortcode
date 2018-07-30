<?php

/*---------------------------------------------
 *
 * Shortcodes for Advanced Custom Fields
 *
 * Gallery, repeater, flexible content, relationship/post object..
 *
 */

new CCS_To_ACF;

class CCS_To_ACF {

  public static $state;

  function __construct() {

    self::$state['is_relationship_loop'] = false;
    self::$state['is_repeater_or_flex_loop'] = false;
    self::$state['is_gallery_loop'] = false;
    self::$state['repeater_index'] = 0;

    // add_action( 'init', array($this, 'init') ); // Wait until plugins and theme loaded

    // Available to themes

    add_ccs_shortcode( array(
      'acf_sub' => array( $this, 'acf_sub_field'),
      'flex' => array( $this, 'loop_through_acf_field'),
      '-flex' => array( $this, 'loop_through_acf_field'),
      '--flex' => array( $this, 'loop_through_acf_field'),

      'repeater' => array( $this, 'loop_through_acf_field'),
      '-repeater' => array( $this, 'loop_through_acf_field'), // Nested repeater

      'acf_gallery' => array( $this, 'loop_through_acf_gallery_field'),
      'acf_image' => array( $this, 'get_image_details_from_acf_gallery'),
   // Alias
      'layout' => array( $this, 'if_get_row_layout'),
      '-layout' => array( $this, 'if_get_row_layout'),
      '--layout' => array( $this, 'if_get_row_layout'),
    ));

    // This will be called by [repeater] if not inside WCK metabox
    // add_local_shortcode( 'ccs', 'repeater', array($this, 'loop_through_acf_field'));
    // add_local_shortcode( 'ccs', 'sub_image', array($this, 'get_image_details_from_acf_gallery'));    // This will be called by [related] when relationship field is specified
    // add_local_shortcode( 'ccs', 'related', array($this, 'loop_relationship_field'));

    add_filter( 'ccs_loop_parameters', array($this, 'acf_date_parameters_for_loop') );
  }

  function init() {
    if ( ! self::is_acf_active('acf') ) return;
  }

  static function is_acf_active() {
    if (!class_exists('acf')) return false; // If ACF is not installed
    return true;
  }

  public static function acf_sub_field( $atts ) {

    extract(shortcode_atts(array(
      'field' => '',
      'format' => '',
      'image' => '',
      'in' => '',
      'size' => '',
    ), $atts));

    if (empty($field) && isset($atts[0])) $field = $atts[0];

    if ($image!='') {

      $output = get_sub_field($image);

      if ( $output != '' ) {

        if ($size=='') $size='full';

        switch($in) {
          case 'id' : $output = wp_get_attachment_image( $output, $size ); break;
          case 'url' : $output = '<img src="' . $output . '">'; break;
          default : /* image object */
            if (is_array($output)) {
              $output = wp_get_attachment_image( $output['id'], $size );
            } else {
              $output = wp_get_attachment_image( $output, $size ); // Assume it's ID
            }
        }
      }

    } else {

      $output = do_ccs_shortcode( get_sub_field($field) );

      if ( ($format=='true') && ($output!='') ) {
        $output = wpautop($output);
      }
    }
    // if (is_array($output)) $output=implode(', ', $output);
    return $output;
  }

  public static function loop_through_acf_field( $atts, $content ) {

    /* For repeater and flexible content fields */

    extract( shortcode_atts( array(
      'field' => '',
      'count' => '',
      'offset' => '', // same as start, except 1 means start=2
      'start' => '',
      'num' => '',
      'row' => '',
      'sub' => '',
      'sub_image' => '',
      'size' => '',
      'format' => '',
      'trim' => '',

      'columns' => '',
      'pad' => '',
      'between' => '',

      'option' => '',

    ), $atts ));

    if ( !empty($row) ) $num = $row; // Alias
    if ( !empty($num) && $num != 'rand' ) {
      $start = $num;
      $count = 1;
    }
    if (!empty($offset)) $start = $offset + 1;

    if (empty($field) && isset($atts[0])) $field = $atts[0];

    if ( empty($content) && (!empty($sub) || !empty($sub_image))) {

      if (!empty($sub_image))
        $content = '[acf_sub image="'.$sub_image.'"';
      else
        $content = '[acf_sub field="'.$sub.'"'; // Display sub field

      if (!empty($size))
        $content .= ' size= "'.$size.'"';
      if (!empty($format))
        $content .= ' format= "'.$format.'"';

      $content .= ']';
    }

    // Support getting field from option page
    $option = ($option == 'true') ? 'option' : false;


    if ( have_rows( $field, $option ) ) {

      $index_now = 0;
      self::$state['repeater_index'] = 0;

      $outputs = array();

      if ( $start == '' ) $start='1';

      while ( have_rows( $field, $option ) ) {

        // Keep true for each row in case nested
        self::$state['is_repeater_or_flex_loop'] = true;

        the_row(); // Move index forward

        $index_now++;
        self::$state['repeater_index']++;

        if ( $index_now >= $start ) { /* Start loop */

          if ( ( !empty($count) ) && ( $index_now >= ($start+$count) ) ) {

              // If over count, continue empty looping for has_sub_field

          } else {

            $prev_state = self::$state; // Store current state

            $outputs[] = str_replace( '{COUNT}', $index_now, do_ccs_shortcode( $content ) );

            self::$state = $prev_state; // Restore current state
          }
        }
      }

      self::$state['is_repeater_or_flex_loop'] = false;
      self::$state['repeater_index'] = 0;

    } else {
      return null;
    }

    if ( $num == 'rand' ) {
      shuffle( $outputs );
      $item = array_pop($outputs);
      $outputs = array($item);
    }

    $output = '';

    if( !empty($outputs) && is_array($outputs)) {

      if (!empty($columns)) {

        $output = CCS_Loop::render_columns( $outputs, $columns, $pad, $between );

      } else {

        $output = implode( '', $outputs );

        if (!empty($trim)) $output = CCS_Format::trim( $output, $trim );
      }
    }

    return $output;
  }



  public static function loop_through_acf_gallery_field( $atts, $content ) {

    extract( shortcode_atts( array(
      'field' => '',
      'count' => '',
      'start' => '',
      'subfield' => '',
      'sub' => '',
      'columns' => '',
      'pad' => '',
      'between' => '',
      'option' => '',
    ), $atts ));


    if (empty($field) && isset($atts[0])) $field = $atts[0];

    // If in repeater or flexible content, get subfield by default
    if ( self::$state['is_repeater_or_flex_loop'] ) {
      $sub = 'true';
    }

    // Backward compatibility
    if (!empty($subfield)) {
      $field = $subfield;
      $sub = 'true';
    }

    // Support getting field from option page
    $option = ($option == 'true') ? 'option' : false;

    if (empty($sub)) {
      $images = get_field( $field, $option );
    } else {
      $images = get_sub_field( $field ); // Gets option from the_row()
    }


    $outputs = array();

    if ( $images ) {

      $index_now = 0;
      if ( $start == '' ) $start='1';

      self::$state['is_gallery_loop'] = true;
      self::$state['gallery_index'] = 0;

      foreach ( $images as $image ) {

        self::$state['current_image'] = $image;
        $index_now++;
        self::$state['gallery_index'] = $index_now;

        if ( $index_now >= $start ) {

          if ( ( $count!= '' ) && ( $index_now >= ($start+$count) ) ) {
            break;
          }

          $outputs[] = str_replace( '{COUNT}', $index_now, do_ccs_shortcode( $content ) );
        }
      }

      self::$state['is_gallery_loop'] = false;
    }
    if( is_array($outputs)) {

      if (!empty($columns))
        $output = CCS_Loop::render_columns( $outputs, $columns, $pad, $between );
      else
        $output = implode( '', $outputs );
    } else {
      $output = $outputs;
    }

    self::$state['current_image'] = '';


    return $output;
  }



  public static function get_image_details_from_acf_gallery( $atts ) {

    extract(shortcode_atts(array(
      'field' => '',
      'size' => '',
      'class' => ''
    ), $atts));

    if ( empty($field) && isset($atts[0]) ) $field = $atts[0];

    $image_url = self::$state['current_image']['url'];
    if ( !empty($size) && isset(self::$state['current_image']['sizes'][$size]) ) {
      $image_url = self::$state['current_image']['sizes'][$size];
    }

    $output = '';

    if ( empty($field) || $field == 'image' ) {

      $output = '<img ';
      if (!empty($class)) $output .= ' class="'.$class.'"';
      $output .= 'src="' . $image_url . '">';

    } elseif ($field == 'url') {

      $output = $image_url;

    } else {

      $output = @self::$state['current_image'][$field];
    }

    return $output;
  }

  public static function if_get_row_layout( $atts, $content ) {

    extract(shortcode_atts(array(
      'name' => '',
    ), $atts));

    if (empty($name) && isset($atts[0])) $name = $atts[0];

    $names = CCS_Format::explode_list($name);
    $layout = get_row_layout();

    if ( $name == 'default' || in_array($layout, $names) ) {
      return do_ccs_shortcode( $content );
    } else {
      return null;
    }
  }

  public static function loop_relationship_field( $atts, $content ) {

    if ( ! self::is_acf_active() ) return;

    extract( shortcode_atts( array(
      'field' => '',
      'subfield' => '',
      'sub' => '', // Alias
      'count' => -1,
      'offset' => '', // same as start, except 1 means start=2
      'start' => 1,
      'trim' => '',
      'option' => '',
    ), $atts ) );

    $output = array();

    if (empty($field) && isset($atts[0])) $field = $atts[0];

    // If in repeater or flexible content, get subfield by default
    if ( self::$state['is_repeater_or_flex_loop'] ) {
      if (empty($subfield)) {
        $subfield = $field;
        $field = null;
      }
    }

    // Support getting field from option page
    $option = ($option == 'true') ? 'option' : false;

    if (!empty($field)) {
      $posts = get_field( $field, $option );
    } elseif (!empty($subfield)) {
      $posts = get_sub_field( $subfield ); // Gets option from the_row()
    } else return null;

    if (!empty($offset)) $start = $offset + 1;

    if ($posts) {

      self::$state['is_relationship_loop'] = true;

      $index_now = 0;

      if ( ! is_array($posts) ) {
        $posts = array( $posts ); // Single post
      }

      foreach ($posts as $post) {

        $index_now++;

        if ($index_now < $start) continue;
        if (($count !== -1) && ($index_now >= ($start+$count))) break;

        self::$state['relationship_id'] = $post->ID;

        $output[] = str_replace('{COUNT}', $index_now, do_ccs_shortcode( $content ));
      }
    }

    self::$state['is_relationship_loop'] = false;

    $output = implode('', $output);

    if (!empty($trim)) {
      $output = CCS_Format::trim($output, $trim);
    }

    return $output;
  }


  function acf_date_parameters_for_loop( $parameters ) {

    // ACF date field query
    if ( !empty($parameters['acf_date']) && !empty($parameters['value'])) {
      $parameters['field'] = $parameters['acf_date'];
      if ( empty($parameters['date_format']) )
        $parameters['date_format'] = 'Ymd';
      if ( empty($parameters['in']) )
        $parameters['in'] = 'string';
      unset($parameters['acf_date']);
    }
    return $parameters;
  }

}
