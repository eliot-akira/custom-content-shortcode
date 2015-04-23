<?php

/*---------------------------------------------
 *
 * Pagination with [loop]
 *
 */

new CCS_Paged;

class CCS_Paged {

  public static $prefix;
  public static $current;

  function __construct() {

    self::$prefix = 'lpage';
    add_shortcode( 'loopage', array($this, 'loopage_shortcode') );
    add_shortcode( 'loopage-now', array($this, 'loopage_now_shortcode') );
    add_shortcode( 'loopage-total', array($this, 'loopage_total_shortcode') );
  }

  function loopage_now_shortcode( $atts, $content ) {
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

  function loopage_total_shortcode( $atts, $content ) {

    if (!empty(CCS_Loop::$wp_query)) {
      $max = CCS_Loop::$wp_query->max_num_pages;

      if (!empty(CCS_Loop::$state['maxpage']) &&
        CCS_Loop::$state['maxpage']<$max) {

        $max = CCS_Loop::$state['maxpage'];
      }
      return $max;
    }
  }

  function loopage_shortcode( $atts, $content ) {

    global $wp;
    global $wp_query;
    $current_baseurl = trailingslashit( home_url( $wp->request ) );

    extract( shortcode_atts( array(
      'id' => '',
      'total' => 'false',
      'max' => '',
      'list' => 'false',
      'class' => '',
      'next_text' => '&raquo;',
      'prev_text' => '&laquo;',
      'show_all' => 'false',
      'end_size' => '1',
      'mid_size' => '2',
      'prev_next' => 'true'
    ), $atts ) );

    $pagination_return = '';

    if (class_exists('CCS_Loop') && !empty(CCS_Loop::$wp_query)) {

      $query = CCS_Loop::$wp_query;
      $id = CCS_Loop::$state['loop_index'];
      if (empty($max) && !empty(CCS_Loop::$state['maxpage'])) {
        $max = CCS_Loop::$state['maxpage'];
      }

      $max = !empty($max) && $max < $query->max_num_pages ?
        $max : $query->max_num_pages;

      if (intval($id)==1) $id = '';
      $query_var = self::$prefix.$id;

      $current = isset($_GET[$query_var]) ? $_GET[$query_var] : 1;
      if ($current > $query->max_num_pages) $current = $query->max_num_pages;

      $args = array(
        'base' => $current_baseurl.'?'.$query_var.'=%#%',
      );

    } else {
      $query = $wp_query; // The loop
      $max = !empty($max) && $max < $query->max_num_pages ?
        $max : $query->max_num_pages;

      $current = max( 1, get_query_var('paged') );
      if ($current > $query->max_num_pages) $current = $query->max_num_pages;
      $big = 999999999; // need an unlikely integer

      $args = array(
        'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
        'format' => '?paged=%#%',
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

        // Bootstrap

        if (!empty($class)) $class = ' '.$class;

        echo '<ul class="pagination'.$class.'">';
        foreach ( $pagination_return as $page ) {
          if ( strpos($page, 'current') > -1 ) {
              echo '<li class="active">' . $page . '</li>';
          } else {
              echo '<li>' . $page . '</li>';
          }
        }
        echo '</ul>';
      }


      echo '</div>';

      return ob_get_clean();
    }
  }  

}
