<?php

/*---------------------------------------------
 *
 * For each taxonomy
 *
 * [for each="category"]
 * [each name,id,slug]
 *
 */


new CCS_ForEach;

class CCS_ForEach {

  public static $state;
  public static $index; // Support nested loop
  public static $current_term;

  function __construct() {

    self::$index = 0;
    self::$state['is_for_loop'] = false;
    self::$state['for_count'] = 0;

    add_ccs_shortcode(array(
      'for' => array( $this, 'for_shortcode' ),
      'each' => array( $this, 'each_shortcode' ),

      // Nested shortcodes
      '-for' => array( $this, 'for_shortcode' ),
      '--for' => array( $this, 'for_shortcode' ),
      '-each' => array( $this, 'each_shortcode' ),
      '--each' => array( $this, 'each_shortcode' ),
    ));
  }

  function for_shortcode( $atts, $content = null, $shortcode_name ) {

    $args = array(
      'each' => '',
      'term' => '', 'terms' => '', // Alias
      'orderby' => '',
      'order' => '',
      'count' => '',
      'parent' => '',
      'parents' => '', // Don't return term children
      'children' => '', // Get all descendants if true
      'current' => '',
      'trim' => '',
      'empty' => 'true', // Show taxonomy terms with no post
      'exclude' => ''
    );

    extract( shortcode_atts( $args , $atts, true ) );

    // Top parent loop
    if ( ! self::$state['is_for_loop'] ) {

      self::$state['is_for_loop'] = true;

    // Nested loop
    } else {

      $parent_term = self::$current_term[ self::$index ];

      // Same taxonomy as parent
      if ( $each=='child' && isset( $parent_term['taxonomy'] ) )
        $each = $parent_term['taxonomy'];

      // Get parent term unless specified
      if ( empty( $parent ) && isset( $parent_term['id'] ) )
        $parent = $parent_term['id'];
      // Nest index
      self::$index++;
    }




    if ($each=='tag') $each='post_tag';
    $out = '';

    $prefix = CCS_Format::get_minus_prefix( $shortcode_name );

    // Get [else] block
    $if_else = CCS_If::get_if_else( $content, $shortcode_name, 'for-else' );
    $content = $if_else['if'];
    $else = $if_else['else'];

    // Get terms according to parameters
    // @todo Refactor - keep it DRY
    // @todo Consolidate with CCS_Content::get_taxonomies

    $query = array(
      'orderby' => !empty($orderby) ? $orderby : 'name',
      'order' => $order,
      'number' => $count, // Doesn't work?
      'parent' => ( $parents=='true' ) ? 0 : '', // Exclude children or not
      'hide_empty' => ( $empty=='true' ) ? 0 : 1,
    );

    $term_ids = array();

    if ( !empty($terms) ) $term = $terms; // Alias
    if ( !empty($term) ) {

      $terms = CCS_Loop::explode_list($term); // Multiple values support

      foreach ($terms as $this_term) {
        if ( is_numeric($this_term) ) {
          $term_ids[] = $this_term;
        } else {
          /* Get term ID from slug */
          $term_id = get_term_by( 'slug', $this_term, $each );
          if (!empty($term_id))
            $term_ids[] = $term_id->term_id;
        }
      }
      if (!empty($term_ids)) {
        $query['include'] = $term_ids;
      }
      else {
        // Nothing found

        // Return to parent loop
        if ( self::$index > 0 ) self::$index--;
        // Or finished
        else self::$state['is_for_loop'] = false;
        return do_ccs_shortcode( $else );
      }
    }

    // Inside loop, or current is true
    if ( ( CCS_Loop::$state['is_loop'] && $current!="false") || ($current=="true") ) {

      if ($current=="true") $post_id = get_the_ID();
      else $post_id = CCS_Loop::$state['current_post_id']; // Inside [loop]

      $taxonomies = wp_get_post_terms( $post_id, $each, $query );

      // Current and parent parameters together

      if ( !empty($parent) ) {

        if ( is_numeric($parent) ) {

          $parent_term_id = $parent;

        } else {

          // Get parent term ID from slug
          $term = get_term_by( 'slug', $parent, $each );
          if (!empty($term))
            $parent_term_id = $term->term_id;
          else $parent_term_id = null;
        }

        if ( !empty($parent_term_id) ) {

          // Filter out terms that do not have the specified parent

          // TODO: Why not set this as query for wp_get_post_terms above..?

          foreach($taxonomies as $key => $term) {

            // TODO: What about children parameter for all descendants..?

            if ($term->parent != $parent_term_id) {
              unset($taxonomies[$key]);
            }
          }
        }
      }

    // Not inside loop
    } else {

      if ( empty($parent) ) {

        $taxonomies = get_terms( $each, $query );

        if ( !empty($term) && $children=='true' ) {

          if (isset($query['include'])) unset($query['include']);

          // Get descendants of each term

          $new_taxonomies = $taxonomies;

          foreach ($taxonomies as $term_object) {
            $query['child_of'] = $term_object->term_id;
            $new_terms = get_terms( $each, $query );
            if (!empty($new_terms)) {
              $new_taxonomies += $new_terms;
              foreach ($new_terms as $new_term) {
                $term_ids[] = $new_term->term_id;
              }
            }
          }

          $taxonomies = $new_taxonomies;
        }

      // Get terms by parent
      } else {

        if ( is_numeric($parent) ) {

          $parent_term_id = $parent;

        } else {
          // Get parent term ID from slug
          $term = get_term_by( 'slug', $parent, $each );
          if (!empty($term))
            $parent_term_id = $term->term_id;
          else $parent_term_id = null;
        }

        if (!empty($parent_term_id)) {

          /* Get direct children */

          if ( $children !== 'true' ) {
            // Direct children only
            $query['parent'] = $parent_term_id;
          } else {
            // All descendants
            $query['child_of'] = $parent_term_id;
          }

          $taxonomies = get_terms( $each, $query );

        } else $taxonomies = null; // No parent found

      }
    }


    if ( count($term_ids) > 0 ) {

      $new_taxonomies = array();
      // Sort terms according to given ID order: get_terms doesn't do order by ID
      foreach ($term_ids as $term_id) {
        foreach ($taxonomies as $term_object) {
          if ($term_object->term_id == $term_id)
            $new_taxonomies[] = $term_object;
        }
      }
      $taxonomies = $new_taxonomies;
    }

    // Array and not empty
    if ( is_array($taxonomies) && count($taxonomies) > 0 ) {

      $each_term = array();
      $each_term['taxonomy'] = $each; // Taxonomy name

      $excludes = CCS_Loop::explode_list( $exclude );
      $index = 0;
      if (empty($count)) $count = 9999; // Show all

      foreach ($taxonomies as $term_object) {

        // Exclude IDs or slugs

        $condition = true;
        foreach ($excludes as $exclude) {
          if ( is_numeric($exclude) ) {
             // Exclude ID
            if ( $exclude == $term_object->term_id ) {
              $condition = false;
            }
          } else {
             // Exclude slug
            if ( $exclude == $term_object->slug ) {
              $condition = false;
            }
          }
        }

        if ( $condition && ++$index <= $count ) {

          $each_term['id'] = $term_object->term_id;
          $each_term['name'] = $term_object->name;
          $each_term['slug'] = $term_object->slug;
          $each_term['description'] = $term_object->description;

          $term_link = get_term_link( $term_object );
          if ( is_wp_error( $term_link ) ) $term_link = null;

          $each_term['url'] = $term_link;
          $each_term['link'] = '<a href="'.$each_term['url'].'">'
            . $each_term['name'] . '</a>';
          // Alias for backward compatibility
          $each_term['name-link'] = $each_term['link'];

          // Replace {TAGS}
          // @todo Use a general-purpose function in CCS_Loop for replacing tags

          $replaced_content = str_replace('{'.$prefix.'TERM}',
            $each_term['slug'], $content);
          $replaced_content = str_replace('{'.$prefix.'TERM_ID}',
            $each_term['id'], $replaced_content);
          $replaced_content = str_replace('{'.$prefix.'TERM_NAME}',
            $each_term['name'], $replaced_content);

          // Make term data available to [each]
          self::$current_term[ self::$index ] = $each_term;
          self::$state['for_count']++;

          $out .= do_ccs_shortcode( $replaced_content );
        }
      } // For each term

    } else {
      // No taxonomy found
      $out .= do_ccs_shortcode( $else );
    }

    // Trim final output

    if (!empty($trim)) {
      if ($trim=='true') $trim = null;
      $out = trim($out, " \t\n\r\0\x0B,".$trim);
    }

    // Return to parent loop
    if ( self::$index > 0 ) self::$index--;
    // Or finished
    else self::$state['is_for_loop'] = false;

    self::$state['for_count'] = 0;

    return $out;
  }


  function each_shortcode( $atts, $content = null, $shortcode_name ) {

    if ( !self::$state['is_for_loop'] )
        return; // Must be inside a for loop

    if (isset($atts['image'])) {
      $field = $atts['image'];
    } else {
      $field = isset($atts[0]) ? $atts[0] : 'name'; // Default: name
    }

    // Get term data for current nest level
    $term = self::$current_term[ self::$index ];

    $out = '';

    // Field value exists
    if ( isset( $term[$field] ) ) {

      $out = $term[$field];

    // Try custom taxonomy field
    } else {
      $out = CCS_Content::get_the_taxonomy_field(
        $term['taxonomy'], $term['id'], $field, $atts
      );
    }

    return $out;
  }

}
