<?php

/*---------------------------------------------
 *
 * Pagination with [loop]
 *
 */

new CCS_Paged;

class CCS_Paged {

  // Query parameter name and value for CCS_Loop
  public static $prefix = 'lpage';
  public static $current;

  function __construct() {

    add_ccs_shortcode( array(
      'loopage' => array($this, 'loopage_shortcode'),
      'the-pagination' => array($this, 'loopage_shortcode'),
      'loopage-now' => array($this, 'loopage_now_shortcode'),
      'loopage-total' => array($this, 'loopage_total_shortcode'),
      'loopage-prev' => array($this, 'loopage_prev_next_shortcode'),
      'loopage-next' => array($this, 'loopage_prev_next_shortcode'),
      'page-now' => array($this, 'page_now_shortcode'),
      'page-total' => array($this, 'page_total_shortcode'),
    ));


    // Pagination permalink

    $settings = get_option( CCS_Plugin::$settings_name );

    if (isset($settings['paged_permalink_slug']))
      self::$prefix = empty($settings['paged_pagination_slug']) ?
        'page' : $settings['paged_pagination_slug'];
  }


  static function loopage_now_shortcode( $atts = array(), $content = '' ) {
    extract( shortcode_atts( array(
      'id' => '',
    ), $atts ) );

    if (empty($id)) {
      $id = CCS_Loop::$state['loop_index'];
      if (isset($atts[0]) && $atts[0]=='before') {
        $id++; // Used before [loop]
      }
    }

    if (intval($id)==1) $id = '';
    $query_var = self::$prefix.$id;
    return isset($_GET[$query_var]) ? $_GET[$query_var] : 1;
  }


  static function loopage_total_shortcode( $atts = array(), $content = '' ) {

    if (!empty(CCS_Loop::$state['wp_query'])) {
      $max = CCS_Loop::$state['wp_query']->max_num_pages;

      if (!empty(CCS_Loop::$state['maxpage']) &&
        CCS_Loop::$state['maxpage']<$max) {

        $max = CCS_Loop::$state['maxpage'];
      }
      return $max;
    }
  }


  static function page_now_shortcode() {
    return max( 1, get_query_var('paged') );
  }

  static function page_total_shortcode() {
    global $wp_query;
    return $wp_query->max_num_pages;
  }



  static function get_paged_url( $query_var, $now, $text, $anchor ) {

    $current_url = add_query_arg( $query_var, $now ) . (!empty($anchor) ? '#'.$anchor : '');

    return '<a href="'.$current_url.'">'.$text.'</a>';
  }

  function loopage_prev_next_shortcode( $atts, $content = '', $tag ) {

    extract( shortcode_atts( array(
      'text' => 'Previous',
      'else' => '',
      'anchor' => '',
      'id' => '',
    ), $atts ) );

    if (empty($id)) $id = CCS_Loop::$state['loop_index'];
    if (intval($id)==1) $id = '';
    $query_var = self::$prefix.$id;

    $now = self::loopage_now_shortcode();

    if ( $tag == 'loopage-prev' ) {

      if (--$now > 0) return self::get_paged_url( $query_var, $now, $text, $anchor );

    } elseif ( $tag == 'loopage-next' ) {

      if (empty($text)) $text = 'Next';

      $max = self::loopage_total_shortcode();
      if (++$now <= $max) return self::get_paged_url( $query_var, $now, $text, $anchor );
    }

    return $else;
  }


  function loopage_shortcode( $atts, $content, $shortcode_name ) {

    global $wp;
    global $wp_query;
    $current_baseurl = trailingslashit( home_url( $wp->request ) );

    extract( shortcode_atts( array(
      'id' => '',
      'total' => 'false',
      'max' => '',
      'list' => 'false',
      'class' => '',
      'item_class' => '',
      'active_class' => 'active',
      'disabled_class' => '',
      'next_text' => '&raquo;',
      'prev_text' => '&laquo;',
      'show_all' => 'false',
      'end_size' => '1',
      'mid_size' => '2',
      'prev_next' => 'true',
      'anchor' => '',
      'current' => '', 'page' => '',
      'slug' => '',
    ), $atts ) );

    $pagination_return = '';

    if ( $shortcode_name === 'loopage' && !empty(CCS_Loop::$state['wp_query']) &&
      empty(CCS_Loop::$state['alter_query']) ) {

      $query = CCS_Loop::$state['wp_query'];

      $id = CCS_Loop::$state['paged_index'];

      if (empty($max) && !empty(CCS_Loop::$state['maxpage'])) {
        $max = CCS_Loop::$state['maxpage'];
      }

      $max = !empty($max) && $max < $query->max_num_pages ?
        $max : $query->max_num_pages;

      if (isset($atts['query'])) {
        $query_var = $atts['query'];
        $id = $query_var; // For loopage ID below
      } else {
        if ($id == 0) return;
        if (intval($id)==1) $id = '';
        $query_var = self::$prefix.$id;
      }

      // Allow manually setting current page

      if (!empty($page)) $current = $page; // Alias
      if (empty($current)) {
        $current = isset($_GET[$query_var]) ? $_GET[$query_var] : 1;
      }

      if ($current > $query->max_num_pages) $current = $query->max_num_pages;


      $base = $current_baseurl;

      if (!empty($slug)) {

        // Generate page links with permalink slug

        $base = explode('/'.$slug.'/', $current_baseurl);
        $base = trailingslashit( $base[0] );
        $args = array(
          'base' => $base.$slug.'/%#%' . (!empty($anchor) ? '#'.$anchor : ''),
        );
      } else {

        // Page links with query variable
        $args = array(
          'base' => $base.'?'.$query_var.'=%#%' . (!empty($anchor) ? '#'.$anchor : ''),
        );
      }

    } else {

      if ( !empty(CCS_Loop::$state['alter_query']) ) {

        // Custom permalink

        $query = CCS_Loop::$state['alter_query'];

        $settings = get_option( CCS_Plugin::$settings_name );

        $pagination_slug = empty($settings['paged_pagination_slug']) ?
          'page' : $settings['paged_pagination_slug'];


        $current = max( 1, get_query_var( $pagination_slug ) ); // self::$prefix

        // TODO: Support changing /page slug

        $big = 999999999; // need an unlikely integer
        $base = str_replace( $big, '%#%', esc_url(
          self::get_custom_pagenum_link( $big, $pagination_slug ) ) );

        $format = '?paged=%#%'; // If not using permalink ??

      } else {

        $query = $wp_query; // Default loop
        $current = max( 1, get_query_var('paged') );

        $big = 999999999; // need an unlikely integer
        $base = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
        $format = '?paged=%#%'; // If not using permalink ??
      }

      $max = !empty($max) && $max < $query->max_num_pages ?
        $max : $query->max_num_pages;

      if ($current > $query->max_num_pages) $current = $query->max_num_pages;

      if (!empty($anchor)) $base .= '#'.$anchor;

      $args = array(
        'base' => $base,
        'format' => $format
      );
    }

    $args['current'] = $current;
    $args['total'] = $max;

    $args['next_text'] = $next_text;
    $args['prev_text'] = $prev_text;
    $args['prev_next'] = ($prev_next=='true');
    $args['show_all'] = ($show_all=='true');
    $args['end_size'] = intval($end_size);
    $args['mid_size'] = intval($mid_size);

    if ($list=='true') {
      $args['type'] = 'array';
    }

    $pagination_return = paginate_links( $args );

    if ( $max > 1 && !empty( $pagination_return ) ) {

      ob_start();

      if (!empty($id)) $id = '_'.$id;
      echo '<div id="loopage'.$id.'" class="pagination-wrap">';

      if ($total!='false') {
        if ($total=='true') {
          $total = 'Page %1$s of %2$s';
        } else {
          $total = str_replace(array('#1','#2'), array('%1$s','%2$s'), $total);
        }
        echo '<div class="total-pages">';
        printf( $total, $current, $max );
        echo '</div>';
      }


      if (!is_array($pagination_return)) {

        // Default

        if (!empty($class)) echo '<div class="'.$class.'">';
        echo $pagination_return;
        if (!empty($class)) echo '</div>';

      } else {

        // List - e.g., Bootstrap

        if (!empty($class)) $class = ' '.$class;

        echo '<ul class="pagination'.$class.'">';
        foreach ( $pagination_return as $page ) {
          if ( strpos($page, 'current') !== false ) {
              echo '<li class="'.$active_class.' '.$item_class.'">' . $page . '</li>';
          } else {
              echo
                '<li'
                  . (!empty($item_class) ? ' class="'.$item_class.'"' : '')
                .'>' . $page . '</li>';
          }
        }
        echo '</ul>';
      }

      echo '</div>';

      return ob_get_clean();
    }
  }


  static function get_custom_pagenum_link( $pagenum, $slug = 'page' ) {

    global $wp_rewrite;

    $pagenum = (int) $pagenum;

    $request = remove_query_arg( 'paged' ); // Or..?

    $home_root = parse_url(home_url());
    $home_root = ( isset($home_root['path']) ) ? $home_root['path'] : '';
    $home_root = preg_quote( $home_root, '|' );

    $request = preg_replace('|^'. $home_root . '|i', '', $request);
    $request = preg_replace('|^/+|', '', $request);

    if ( !$wp_rewrite->using_permalinks() || is_admin() ) {
        $base = trailingslashit( get_bloginfo( 'url' ) );

        if ( $pagenum > 1 ) {
            $result = add_query_arg( 'paged', // Or..?
              $pagenum, $base . $request );
        } else {
            $result = $base . $request;
        }
    } else {
        $qs_regex = '|\?.*?$|';
        preg_match( $qs_regex, $request, $qs_match );

        if ( !empty( $qs_match[0] ) ) {
            $query_string = $qs_match[0];
            $request = preg_replace( $qs_regex, '', $request );
        } else {
            $query_string = '';
        }

        $request = preg_replace( "|$slug/\d+/?$|", '', $request);
        $request = preg_replace( '|^' . preg_quote( $wp_rewrite->index, '|' ) . '|i', '', $request);
        $request = ltrim($request, '/');

        $base = trailingslashit( get_bloginfo( 'url' ) );

        if ( $wp_rewrite->using_index_permalinks() && ( $pagenum > 1 || '' != $request ) )
            $base .= $wp_rewrite->index . '/';

        if ( $pagenum > 1 ) {
            $request = ( ( !empty( $request ) ) ? trailingslashit( $request ) : $request ) . user_trailingslashit( $slug . "/" . $pagenum, 'paged' // Or..?
            );
        }

        $result = $base . $request . $query_string;
    }
    return $result;
  }

}
