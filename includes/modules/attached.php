<?php

/*---------------------------------------------
 *
 * Attached shortcode
 *
 */

new CCS_Attached;

class CCS_Attached {

  public static $state;

  function __construct() {

    add_ccs_shortcode( array(
      'attached' => array( $this, 'attached_shortcode' ),
      '-attached' => array( $this, 'attached_shortcode' ),
      'attached-field' => array( $this, 'attached_field_shortcode' ),
    ));

    self::$state['is_attachment_loop'] = false;
  }

  public static function attached_shortcode( $atts, $content, $tag ) {

    $args = array(
      'orderby' => '',
      'order' => '',
      'category' => '',
      'count' => 999,
      'offset' => '',
      'trim' => '',
      'id' => '', // Specific attachment ID
      'field' => '', // Get ID from field
      'columns' => '', 'pad' => '', 'between' => ''
    );

    extract( shortcode_atts( $args , $atts, true ) );

    /*---------------------------------------------
     *
     * Get attachments
     *
     */
    $attachment_ids = array();

    if ( !empty($id) ) {
      $parent_id = 0; // Any post
    } elseif ( !empty($field) ) {
      // Specific attachment ID from field
      $id = do_shortcode('[field '.$field.']');
      if (empty($id)) return;
      $parent_id = 0; // Any post
    } else {
      $parent_id = do_shortcode('[field id]'); // Attachments of current post
      if (empty($parent_id)) return;
    }

    if ( isset($atts[0]) && ($atts[0]=='gallery') ){

      // Get attachment IDs from gallery field
      $attachment_ids = CCS_Gallery_Field::get_image_ids( $parent_id );

      // Support for orderby title
      if ( $orderby=='title' ) {
        usort($attachment_ids, array($this, 'sort_gallery_by_title'));
      }
    } else {

      $attach_args = array (
        'post_parent' => $parent_id,
        'post_type' => 'attachment',
        'post_status' => 'any',
        'posts_per_page' => '-1' // Get all attachments
      );

      // default orderby
      $attach_args['orderby'] = empty($orderby) ? 'date' : $orderby;

      // default for titles
      if ( $orderby == 'title' ) $order = empty($order) ? 'ASC' : $order;

      if (!empty($order)) $attach_args['order'] = $order;
      if (!empty($category)) $attach_args['category'] = $category;
      if (!empty($count)) $attach_args['posts_per_page'] = $count;
      if (!empty($offset)) $attach_args['offset'] = $offset;
      if (!empty($id)) {
        $attach_args['post__in'] = CCS_Format::explode_list($id);
        $attach_args['orderby'] = empty($orderby) ? 'post__in' : $orderby;
        unset($attach_args['post_parent']);
      }

      // Get attachments for current post

      $posts = get_posts($attach_args);

      $index = 0;
      foreach( $posts as $post ) {
        $attachment_ids[$index] = $post->ID; // Keep it in order
        $index++;
      }
    }

    // If no images in gallery field
    if (count($attachment_ids)==0) return null;

    if ($orderby=='random') shuffle($attachment_ids);

    /*---------------------------------------------
     *
     * Compile template
     *
     */

    $out = array();


    // if nested, save previous state
    if ($tag[0]=='-') $prev_state = self::$state;

    self::$state['is_attachment_loop'] = true;

    foreach ( $attachment_ids as $index => $attachment_id ) {
      self::$state['current_attachment_id'] = $attachment_id;
      $out[] = do_ccs_shortcode( $content );
      if ($index>=($count-1)) break;
    }

    self::$state['is_attachment_loop'] = false;

    // if nested, restore previous state
    if ($tag[0]=='-') self::$state = $prev_state;




    /*---------------------------------------------
     *
     * Post-process
     *
     * TODO: Combine this with loop and others
     *
     */

    if (!empty($columns)) {
      $out = CCS_Loop::render_columns( $out, $columns, $pad, $between );
    } else {
      $out = implode('', $out);

      if ( $trim == 'true' ) {
        $out = trim($out, " \t\n\r\0\x0B,");
      }
    }




    return $out;
  }


  public static function sort_gallery_by_title( $a, $b ) {

    $a_title = CCS_Content::wp_get_attachment_field( $a, 'title' );
    $b_title = CCS_Content::wp_get_attachment_field( $b, 'title' );

    return ( $a_title < $b_title ) ? -1 : 1;
  }

  // Field from a specific attachment
  public static function attached_field_shortcode( $atts ) {

    if ( !isset($atts['offset']) ) $atts['offset'] = '0';
    if ( !isset($atts['count']) ) $atts['count'] = '1';

    $content = '[field '.@$atts[0].']';

    return self::attached_shortcode( $atts, $content, $tag = '-attached' );
  }

}
