<?php

/*---------------------------------------------
 *
 * User shortcodes: users, user, is/isnt
 *
 * @todo Move these elsewhere
 * Other shortcodes: list_shortcodes, search_form, blog
 *
 */

new CCS_User;

class CCS_User {

  public static $state;

  function __construct() {

    add_ccs_shortcode( array(
      'users' => array($this, 'users_shortcode'),
      'user' => array($this, 'user_shortcode'),
      'is' => array($this, 'is_shortcode'),
      '-is' => array($this, 'is_shortcode'),
      '--is' => array($this, 'is_shortcode'),
      'isnt' => array($this, 'is_shortcode'),
      'get-blog' => array($this, 'blog_shortcode'),
      'list_shortcodes' => array($this, 'list_shortcodes'),
      'search_form' => array($this, 'search_form_shortcode'),
    ) );

    self::$state['is_users_loop'] = false;
  }


  /*---------------------------------------------
   *
   * Users loop
   *
   */

  function users_shortcode( $atts, $content ) {

    self::$state['user_query'] = '';

    /*---------------------------------------------
     *
     * [if empty]
     *
     */


    // If empty
    $middle = CCS_Loop::get_between('[if empty]', '[/if]', $content);
    $content = str_replace($middle, '', $content);
    $else = CCS_Loop::extract_else( $middle );
    self::$state['if_empty'] = $middle;
    self::$state['if_empty_else'] = $else;



    $outputs = array();

    /*---------------------------------------------
     *
     * Prepare parameters
     *
     */

    $args = array();

    // Just pass these
    $pass_args = array('orderby','search','number','offset','meta_key');


    // Order by field value

    $sort_field_num = false;
    if ( isset($atts['orderby']) ) {
      if ( $atts['orderby']=='id' ) $atts['orderby'] = 'ID';
      elseif ( isset($atts['field']) &&
        ( $atts['orderby']=='field' || $atts['orderby']=='field_num' ) ) {

        if ( $atts['orderby']=='field' ) {
          $atts['orderby'] = 'meta_value';
          $atts['meta_key'] = $atts['field'];
        } else {
          // Sort by field value number
          $sort_field_num = $atts['field'];
          unset($atts['orderby']);
        }
        unset($atts['field']);
      }
    }

    foreach ($pass_args as $arg) {
      if (isset($atts[$arg]))
        $args[$arg] = $atts[$arg];
    }

    if (isset($atts['count']))
      $args['number'] = $atts['count'];
    if (isset($atts['order']))
      $args['order'] = strtoupper($atts['order']);
    if (isset($atts['include']))
      $args['include'] = CCS_Format::explode_list($atts['include']);
    if (isset($atts['exclude']))
      $args['exclude'] = CCS_Format::explode_list($atts['exclude']);

    if (isset($atts['blog_id']))
      $args['blog_id'] = intval($atts['blog_id']);

    if (isset($atts['search_columns']))
      $args['search_columns'] = CCS_Format::explode_list($atts['search_columns']);

    if (isset($atts['field']) && isset($atts['value'])) {

      $compare = isset($atts['compare']) ? strtoupper($atts['compare']) : '=';

      switch ($compare) {
        case 'EQUAL': $compare = '='; break;
        case 'NOT':
        case 'NOT EQUAL': $compare = '!='; break;
        case 'MORE': $compare = '>'; break;
        case 'LESS': $compare = '<'; break;
      }

      $multiple = array('IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN');

      if ( in_array($compare,$multiple) ) {
        $value = CCS_Format::explode_list($atts['value']);
      } else {
        $value = $atts['value'];
      }

      $args['meta_query'][] = array(
        'key' => $atts['field'],
        'value' => $atts['value'],
        'compare' => $compare,
      );

      // Additional query
      if (isset($atts['relation']) && isset($atts['field_2']) && isset($atts['value_2'])) {

        $args['meta_query']['relation'] = strtoupper($atts['relation']);

        $args['meta_query'][] = array(
          'key' => $atts['field_2'],
          'value' => $atts['value_2'],
          'compare' => isset($atts['compare_2']) ? strtoupper($atts['compare_2']) : '=',
        );
      }
    }

    // Alter query to extend search function
    if (isset($args['search'])) {
      self::$state['user_query'] = $args['search'];
      add_action( 'pre_user_query', array(__CLASS__, 'extend_search') );
    }


    // Main action

    if (isset($atts['id'])) {

      $users = array( get_user_by( 'id', $atts['id'] ) );

    } elseif (isset($atts['slug'])) {

      $users = array( get_user_by( 'slug', $atts['slug'] ) );

    } elseif (isset($atts['role'])) {

      // Support query by multiple roles, because get_users() doesn't

      $users = array();

      $roles = CCS_Format::explode_list($atts['role']);

      foreach ($roles as $role) {

        if ($role=='admin') $role = 'Administrator';
        else $role = ucwords($role); // Capitalize word

        $args['role'] = $role;

        $matching_users = get_users( $args );

        $users = array_merge($users, $matching_users);
      }

    } else {

      $users = get_users( $args );
    }



    if (isset($args['search'])) {
      self::$state['user_query'] = '';
      remove_action( 'pre_user_query', array(__CLASS__, 'extend_search') );
    }

    /*---------------------------------------------
     *
     * Filter results
     *
     */

    // Sort by field value number
    if ( $sort_field_num !== false ) {

      // This is necessary because get_users doesn't do meta_value_num query

      $new_users = array();

      foreach ( $users as $user ) {

        $key = $user->get( $sort_field_num );
        $new_users[] = array(
          'user' => $user,
          'key' => $key
        );
      }

      usort($new_users, array(__CLASS__, 'sortByFieldNum'));

      if (isset($args['order']) && $args['order'] == 'DESC')
        $new_users = array_reverse($new_users);

      $users = array();
      foreach ( $new_users as $user_array ) {
        $users[] = $user_array['user'];
      }

    }

    self::$state['is_users_loop'] = true;

    /*---------------------------------------------
     *
     * Users Loop
     *
     */

    foreach ( $users as $user ) {

      self::$state['current_user_object'] = $user;

      // Support tags

      $content = str_replace('{USER_ID}', do_shortcode('[user id]'), $content);
      $content = str_replace('{USER_ROLE}', do_shortcode('[user role out="slug"]'), $content);

      $outputs[] = do_ccs_shortcode( $content );
    }

    // [if empty]..[else]..[/if]
    if (count($users) == 0 && isset(self::$state['if_empty'])) {
      $outputs[] = do_ccs_shortcode( self::$state['if_empty'] );
    } elseif (isset(self::$state['if_empty_else']) && count($users) > 0) {
      $outputs[] = do_ccs_shortcode( self::$state['if_empty_else'] );
    }

    // Make a list

    // TODO: Add new function: CCS_Format::make_list()

    if (isset($atts['list'])) {

      $outerTag = $atts['list']=='true' ? 'ul' : $atts['list'];

      $innerTag = isset($atts['item']) ? $atts['item'] : 'li';

      $result =
        '<'.$outerTag
          .( isset($atts['list_class']) ? ' class="'.implode(' ', array_map('trim', explode(',', $atts['list_class']))).'"' : '' )
          .( isset($atts['list_style']) ? ' style="'.$atts['list_style'].'"' : '' )
        .'>';

      foreach ($outputs as $o) {

        $result .=
          '<'.$innerTag
            .( isset($atts['item_class']) ? ' class="'.implode(' ', array_map('trim', explode(',', $atts['item_class']))).'"' : '' )
            .( isset($atts['item_style']) ? ' style="'.$atts['item_style'].'"' : '' )
          .'>'.$o.'</'.$outerTag.'>';
      }

      $result .= '</'.$outerTag.'>';

    } else {
      $result = implode('', $outputs);
    }


    if (isset($atts['trim'])) {

      $trim = $atts['trim'];
      if ($trim=='true') $trim = null;
      $result = trim($result, " \t\n\r\0\x0B,".$trim);
    }


    self::$state['is_users_loop'] = false;

    return $result;
  }


  public static function sortByFieldNum($a, $b) {
      return intval( $a['key'] ) - intval( $b['key'] );
  }


  static function extend_search( $query ) {

    global $wpdb;

    if (!empty(self::$state['user_query'])) {

      $display_name = self::$state['user_query'];

      $query->query_where .= $wpdb->prepare(
        " OR $wpdb->users.display_name LIKE %s", '%'
        .$wpdb->esc_like($display_name).'%');
    }
    return $query;
  }



  /*---------------------------------------------
   *
   * [user]
   *
   */

  public static function user_shortcode( $atts ) {

    if ( self::$state['is_users_loop'] ) {

      $u = self::$state['current_user_object'];

    } else {

      //global $current_user;
      //get_currentuserinfo();
      $current_user = wp_get_current_user();
      self::$state['current_user_object'] = $current_user;
      $u = $current_user;
    }

    extract(shortcode_atts(array(
      'field' => '',
      'meta' => '', // Alias
      'image' => '',
      'size' => '',
      'text' => '', // For author archive link
      'out' => '',
      'format' => '', // For user registered date
    ), $atts));

    if(empty($u)) return; // no current user


    // Get field specified

    if( !empty($meta) ) $field=$meta;
    if( !empty($image) ) {
      if (!empty($field)) {
        $image_field = $field;
      } else {
        $image_field = 'image';
      }
      $field=$image;
    }

    if ( empty($field) ) {
      // or just get the first parameter
      $field = isset($atts[0]) ? $atts[0] : null;
    }

    switch ( $field ) {
      case '':
      case 'fullname':
        return $u->display_name;
        break;
      case 'name':
        return $u->user_login;
        break;
      case 'slug':
      case 'nicename':
        return $u->user_nicename;
        break;
      case 'id':
        return $u->ID;
        break;
      case 'email':
        return $u->user_email;
        break;
      case 'url':
        return $u->user_url;
        break;
      case 'archive-url':
        return get_author_posts_url( $u->ID );
        break;
      case 'archive-link':
        $url = get_author_posts_url( $u->ID );
        $text = !empty($text) ? $text : $u->display_name;
        return '<a href="'.$url.'">'.$text.'</a>';
        break;
      case 'avatar':
        return get_avatar( $u->ID, !empty($size) ? $size : 96);
        break;
      case 'fullname':
        return $u->display_name;
        break;
      case 'post-count':
        return strval( count_user_posts( $u->ID ) );
        break;
      case 'role':
        if ($out=='slug') {
          return rtrim(implode(',', $u->roles),',');
        } else {
          return rtrim(implode(',',array_map('ucwords', $u->roles)),',');
        }
        break;
      case 'registered':
        $date = $u->user_registered;
        if (empty($format)) $format = 'default';
        $date = CCS_Format::format_date(array(
          'date' => $format,
        ), $date);
        return $date;
      break;

      case 'edit-url':
        return admin_url( 'user-edit.php?user_id=' . $u->ID );
      break;

      case 'edit-link':
        if (empty($text)) $text = 'Edit Profile';
        $url = admin_url( 'user-edit.php?user_id=' . $u->ID );
        return '<a href="'.$url.'">'.$text.'</a>';

      case 'agent':
        return $_SERVER["HTTP_USER_AGENT"];
        break;
      case 'device':
        if (class_exists('CCS_Mobile_Detect')) {
          return CCS_Mobile_Detect::$device;
        } else return null;
        break;
      case 'device-type':
        if (class_exists('CCS_Mobile_Detect')) {
          return CCS_Mobile_Detect::$device_type;
        } else return null;
        break;
      case 'browser':
        if (class_exists('CCS_Mobile_Detect')) {
          return CCS_Mobile_Detect::$browser;
        } else return null;
        break;
      default:
        // Custom user field
        $result = get_user_meta( $u->ID, $field, true );
        break;
    }

    // Post-process

    // Attachment
    if (!empty($image)) {
      $params = '';
      if (!empty($size)) $params .= ' size="'.$size.'"';

      $result = do_ccs_shortcode(
        '[attached id='.$result.']'
          .'[field '.$image_field.$params.']'
        .'[/attached]'
      );
    }

    // Array

    return $result;
  }

  public static function get_user_field( $field ) {
    return self::user_shortcode( array( 'field' =>  $field ) );
  }


  /*---------------------------------------------
   *
   * [is]
   *
   */

  function is_shortcode( $atts, $content, $tag ) {

    global $post;

    extract(shortcode_atts(array(
      'user' => '',
      'format' => 'false',
      'shortcode' => 'true',
      'role' => '',
      'capable' => '',
      'compare' => 'OR',
      'device' => '',
      'debug' => '',
    ), $atts));

    $condition = false;
    $debug = ($debug == 'true');

    if (is_array($atts)) $atts = CCS_Content::get_all_atts( $atts );

    // Get [else] if it exists
    $content_array = explode('[else]', $content);
    $content = $content_array[0];
    if (count($content_array)>1) {
      $else = $content_array[1];
    } else {
      $else = null;
    }

    $logged_in = is_user_logged_in();

    if ($logged_in) {

      // Load user info

      if ( self::$state['is_users_loop'] && isset(self::$state['current_user_object'])) {
        $u = self::$state['current_user_object'];
      } else {

        // NOTE: Don't use get_currentuserinfo(),
        // it alters current user when in loop

        $current_user = wp_get_current_user();
        $u = $current_user;
      }

      if (!empty($user)) {

        $user_array = explode(',', $user);

        foreach ($user_array as $this_user) {

          $this_user = trim($this_user);

          if ( $this_user == ($u->user_login) )
            $condition = true;
          elseif ( is_numeric($this_user) && $this_user == ($u->ID) ) // User ID
              $condition = true;
        }
      }

      if (!empty($role) ) {

        $current_roles = $u->roles; // an array of roles
        $translate_roles = array(
          'admin' => 'administrator',
        );
        $condition = false;


        if ($debug) ccs_inspect('Current roles', $current_roles);


        // check each role
        $check_roles = explode(',', $role);

        foreach ($check_roles as $check_role) {

          $check_role = trim($check_role);

          if (isset($translate_roles[$check_role])) {
            $check_role = $translate_roles[$check_role];
          }

          if (in_array($check_role, $current_roles)) {
            $condition = true;
          }
          elseif ($compare == 'AND') {
            $condition = false;
          }
        }
      }

      if (!empty($capable)) {

        $capables = explode(',', $capable);

        foreach ($capables as $capability) {

          $check_capable = trim($capability);

          if ( user_can( $u, $check_capable ) ) {
            $condition = true;
          }
          elseif ($compare == 'AND') {
            $condition = false;
          }
        }
      }

      if ( ( isset( $atts['admin'] ) && user_can( $u, 'manage_options' ) ) ) {
        $condition = true;
      }

      if ( isset( $atts['author'] ) ) {
        // If user is the author of current post
        if (!empty($post)) {

          if ($post->post_author == $u->ID)
            $condition = true;
        }
      }

      if (isset($atts['login'])) {
        $condition = true;
      }

      // End: Is logged in
    } elseif (isset($atts['logout'])) $condition = true;


    if (class_exists('CCS_Mobile_Detect')) {
      if ( !empty($device) ) {
        $condition = (strtolower(CCS_Mobile_Detect::$device) == strtolower($device));
      } elseif ( isset( $atts['mobile'] ) ) {
        $condition = CCS_Mobile_Detect::$is_mobile;
      } elseif ( isset( $atts['phone'] ) ) {
        $condition = (CCS_Mobile_Detect::$device_type == 'phone');
      } elseif ( isset( $atts['tablet'] ) ) {
        $condition = (CCS_Mobile_Detect::$device_type == 'tablet');
      } elseif ( isset( $atts['computer'] ) ) {
        $condition = (CCS_Mobile_Detect::$device_type == 'computer');
      }
    }

    if ( ($tag=='isnt') || (isset($atts['not'])) )
      $condition = !$condition;

    if ( !$condition ) {
      $content = $else; // Everything after [else]
    }

    if ($format == 'true') // Format?
      $content = wpautop( $content );
    if ($shortcode != 'false') // Shortcode?
      $content = do_ccs_shortcode( $content );

    return $content;
  }


  /*---------------------------------------------
   *
   * [blog]
   *
   */

  function blog_shortcode( $atts, $content ){

    extract(shortcode_atts(array(
      'id' => '',
    ), $atts));

    $out = $content;

    if ( empty($id) || !blog_exists($id))
      return;

    switch_to_blog($id);
    $out = do_ccs_shortcode($out);
    restore_current_blog();

    return $out;
  }


  /*---------------------------------------------
   *
   * [list_shortcodes]
   *
   */

  function list_shortcodes( ) {
    global $shortcode_tags;
    ksort($shortcode_tags); // Alphabetical sort

    $out = '';

    foreach ( $shortcode_tags as $key => $value ) {

      if(is_array($value)) $value='Class object';

      $out .= $key . ' = ' . $value . '<br>';

    }
    return $out;
  }


  /*---------------------------------------------
   *
   * [search_form]
   *
   * @param type Search only this post type
   *
   */


  function search_form_shortcode( $atts, $content ) {

    extract( shortcode_atts( array(
      'type' => '',
    ), $atts ) );

    $out = get_search_form(false);

    if ( !empty($type) ) {

      $filter = '<input type="hidden" value="'.$type.'" name="post_type" id="post_type" />';
      $end = '</form>';

      // Insert it before the end of form
      $out = str_replace($end, $filter.$end, $out);
    }

    return $out;
  }

}


/*---------------------------------------------
 *
 * Utility
 *
 */

if ( ! function_exists( 'blog_exists' ) ) {

    /**
     * Checks if a blog exists and is not marked as deleted.
     *
     * @link   http://wordpress.stackexchange.com/q/138300/73
     * @param  int $blog_id
     * @param  int $site_id
     * @return bool
     */
    function blog_exists( $blog_id, $site_id = 0 ) {

        global $wpdb;
        static $cache = array ();

        $site_id = (int) $site_id;

         if (!function_exists('get_current_site'))
           return false;

        if ( 0 === $site_id ) {
          $current_site = get_current_site();
            $site_id = $current_site->id;
        }

        if ( empty ( $cache ) or empty ( $cache[ $site_id ] ) ) {

            if ( wp_is_large_network() ) // we do not test large sites.
                return TRUE;

            $query = "SELECT `blog_id` FROM $wpdb->blogs
                    WHERE site_id = $site_id AND deleted = 0";

            $result = $wpdb->get_col( $query );

            // Make sure the array is always filled with something.
            if ( empty ( $result ) )
                $cache[ $site_id ] = array ( 'do not check again' );
            else
                $cache[ $site_id ] = $result;
        }

        return in_array( $blog_id, $cache[ $site_id ] );
    }
}
