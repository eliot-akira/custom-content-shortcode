<?php

/*---------------------------------------------
 *
 * Relative URL shortcodes - [url site/theme/child/views/content/uploads]
 *
 */

new CCS_URL;

class CCS_URL {

  static $urls; // Store URLs

  static $route = '';
  static $routes = array();
  static $query = '';
  static $queries = array();

  function __construct() {

    self::$urls = array();

    add_ccs_shortcode( array(
      'url' => array($this, 'url_shortcode'),
      'redirect' => array($this, 'redirect_shortcode'),
      'query' => array($this, 'query_shortcode'),
      'route' => array($this, 'route_shortcode')
    ));

    add_action( 'wp', array($this, 'init') );
  }


  static function init() {

    global $wp;

    // Get routes
    self::$route = $wp->request;
    self::$routes = explode('/', self::$route);

    // Get queries: direct method
    $request_url = untrailingslashit( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
    $url = parse_url( $request_url );
    $query_string = isset($url['query']) ? $url['query'] : '';
    parse_str( $query_string, $query_array ); // Create array from query string

    self::$query = $query_string;
    self::$queries = array_filter($query_array); // Remove any empty keys
  }

  static function get_routes() {
    return self::$routes;
  }

  static function get_route( $index = 0 ) {
    // index starts at 1
    if ( empty($index) ) return self::$route;
    if ( $index == 'last' ) $index = count(self::$routes);
    return isset(self::$routes[$index - 1]) ? self::$routes[$index - 1] : '';
  }

  static function get_queries() {
    return self::$queries;
  }

  static function get_query( $name = '' ) {
    if (empty($name)) return self::$query;
    return isset(self::$queries[$name]) ? self::$queries[$name] : '';
  }

  function query_shortcode() {
    return self::get_query( isset($atts[0]) ? $atts[0] : '' );
  }

  function route_shortcode() {
    return self::get_route( isset($atts[0]) ? $atts[0] : '' );
  }


  /*---------------------------------------------
   *
   * URL
   *
   */

  static function url_shortcode( $atts ) {

    $urls = self::$urls;
    $url = null;

    extract(shortcode_atts(array(
      'login' => '',
      'logout' => '',
      'go' => '',
    ), $atts));

    if (empty($atts)) $atts[0] = 'site';

    $arg = $atts[0];

    // Allow checking empty parameters
    if ( is_array($atts) ) $atts = CCS_Content::get_all_atts( $atts );

        // Find where to go after login/out

    if ( ($go=='here') || (isset($atts['logout']) && empty($go)) ) {

      $go = home_url( self::$route ); // Current page URL

    } elseif ( !empty($go) ) {

      if ($go=='home') {

        $go = isset($urls['home']) ? $urls['home'] : ($urls['home'] = get_option('home'));

      } elseif( ($arg=='login') || ($arg=='logout') ) {

        if( !strpos ($go,"." ) ) {

          // Go to page URL by slug
          $go = do_shortcode('[content name="'.$go.'" field="url"]');

        } elseif ( substr($go, 0, 4) !== 'http' ) {

          // Make sure URL has http://
          $go = 'http://'.$go;
        }
      }
    }

    if (isset($urls[$arg])) {
      // cached
      return untrailingslashit( $urls[$arg] );
    }


    switch ($arg) {

      case 'wordpress':
        $url = ($urls[$arg] = get_option('siteurl'));
      break;

      case 'content':
        $url = ($urls[$arg] = content_url());
      break;

      case 'admin':
        $url = ($urls[$arg] = admin_url());
      break;

      case 'parent': // Parent Theme
      case 'theme':
        $url = ($urls[$arg] = get_bloginfo('template_directory'));
      break;

      case 'child':
        $url = ($urls[$arg] = get_bloginfo('stylesheet_directory'));
      break;

      case 'uploads':

        // Get uploads directory
        $upload_dir = wp_upload_dir();
        if( !$upload_dir['error'] ) {
          $url = ($urls[$arg] = $upload_dir['baseurl']);
        } elseif ( $url = get_option( 'upload_url_path' ) ) {
          // Prior to WordPress 3.5, this was set in Settings > Media > Full URL path to files
          // In WordPress 3.5+ this is now hidden
          $urls[$arg] = $url;
        } else {
          $url = ($urls[$arg] = get_option('siteurl').'/'.get_option('upload_path') );
        }
      break;

      case 'layout':
        $url = ($urls[$arg] = content_url().'/'.$arg);
      break;
      case 'views':
        if (defined('VIEWS_URL')) {
          $url = VIEWS_URL;
        } else {
          $url = ($urls[$arg] = content_url().'/'.$arg);
        }
      break;

       // Don't cache login/logout/register, as redirect is dynamic
      case 'login':
        $url = wp_login_url( $go );
      break;

      case 'logout':
        $url = wp_logout_url( $go );
      break;

      case 'register':
        $url = wp_login_url( $go );
        $url = add_query_arg('action', 'register', $url);
      break;

      case 'current':
        // Current page URL
        $url = ($urls[$arg] = home_url( self::$route ));
      break;

      case 'home':
      default:
        $url = ($urls[$arg] =  get_option('home'));
      break;
    }

    self::$urls = $urls; // Store it for later

    return untrailingslashit( $url );

  }


  /* Redirect to another page */

  function redirect_shortcode( $atts, $content ) {

    extract(shortcode_atts(array(
      'after' => ''
    ), $atts));

    if (empty($content)) {
      // First argument as URL
      $content = isset($atts['go']) ? $atts['go'] : '';
    }

    $content = strip_tags( do_shortcode(trim($content)) );

    if ( empty($content) ) return;

    if ( $content == 'home' ) {

      $content = get_option('home');

    } elseif ( !strpos($content, '.') ) {

        // Go to page by slug
        $content = do_shortcode('[content name="'.$content.'" field="url"]');

    } elseif ( substr($content, 0, 4) !== 'http' ) {

      $content = 'http://'.$content;
    }

    if ( !empty( $after ) ) {

      $after = self::get_expire_time_ms( $after );
      echo "<script> setTimeout(function(){window.location='"
          .$content."';},".$after."); </script>";

    } else {

      echo "<script> window.location = '" . $content . "'; </script>";
    }

  }


  public static function get_expire_time_ms( $expire ) {

    $expire = explode(" ", $expire);

    $ms = 1000;
    $expire_ms = $expire[0];

    if (count($expire)>1) {

      switch ($expire[1]) {
        case 'minute':
        case 'minutes':
        case 'mins':
        case 'min': $expire_ms *= 60 * $ms; break;
        case 'hours':
        case 'hour': $expire_ms *= 60 * 60 * $ms; break;
        case 'days':
        case 'day': $expire_ms *= 60 * 60 * 24 * $ms; break;
        case 'months':
        case 'month': $expire_ms *= 60 * 60 * 24 * 30 * $ms; break;
        case 'years':
        case 'year': $expire_ms *= 60 * 60 * 24 * 30 * 365 * $ms; break;
        case 'milliseconds':
        case 'ms':
        default:
          // In ms already
        break;
      }
    }

    return $expire_ms;
  }


}
