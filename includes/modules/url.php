<?php

/*---------------------------------------------
 *
 * Relative URL shortcodes - [url site/theme/child/views/content/uploads]
 *
 */

new CCS_URL;

class CCS_URL {

  public static $urls; // Store URLs

  function __construct() {

    self::$urls = array();

    add_shortcode( 'url', array($this, 'url_shortcode') );
    add_shortcode( 'redirect', array($this, 'redirect_shortcode') );
  }
    
  public static function url_shortcode( $atts ) {

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

      global $wp; $go = home_url($wp->request); // Current page URL

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

    switch ($arg) {

      case 'wordpress':
        $url = isset($urls[$arg]) ? $urls[$arg] : ($urls[$arg] = get_option('siteurl'));
        break;

      case 'content':
        $url = isset($urls[$arg]) ? $urls[$arg] : ($urls[$arg] = content_url());
        break;

      case 'admin':
        $url = isset($urls[$arg]) ? $urls[$arg] : ($urls[$arg] = admin_url());
        break;

      case 'parent': // Parent Theme
      case 'theme':
        $url = isset($urls[$arg]) ? $urls[$arg] : ($urls[$arg] = get_bloginfo('template_directory'));
        break;

      case 'child':
        $url = isset($urls[$arg]) ? $urls[$arg] : ($urls[$arg] = get_bloginfo('stylesheet_directory'));
        break;

      case 'uploads':

        if (isset($urls[$arg]))
          $url = $urls[$arg];
        else {

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
        }
        break;

      case 'layout':
        $url = isset($urls[$arg]) ? $urls[$arg] : ($urls[$arg] = content_url().'/'.$arg);
        break;
      case 'views':
        if (defined('VIEWS_URL')) {
          $url = VIEWS_URL;
        } else {
          $url = isset($urls[$arg]) ?
            $urls[$arg] : ($urls[$arg] = content_url().'/'.$arg);
        }
        break;

      case 'login':
        $url = wp_login_url( $go ); // Don't store this, as go parameter could be different
        break;

      case 'logout':
        $url = wp_logout_url( $go );
        break;

      case 'home':
      default:
        $url = isset($urls['home']) ? $urls['home'] : get_option('home');
        break;
    }

    self::$urls = $urls; // Store it for later

        // unslash?

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
