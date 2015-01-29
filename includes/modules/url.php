<?php

/*========================================================================
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

        if ( is_array($atts ) ) $atts = array_flip( $atts ); // Allow checking empty parameters

        // Find where to go after login/out

		if ( ($go=='here') || (isset($atts['logout']) && empty($go)) ) {

			global $wp; $go=home_url($wp->request); // Current page URL

		} elseif ( !empty($go) ) {

			if ($go=='home') {

				$go = isset($urls['home']) ? $urls['home'] : ($urls['home'] = get_option('home'));

			} elseif( ($arg=='login') || ($arg=='logout') ) {

				if( !strpos ($go,"." ) ) {

					// Go to page URL by slug
					$go = do_shortcode('[content name="'.$go.'" field="url"]');
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
			case 'views':
				$url = isset($urls[$arg]) ? $urls[$arg] : ($urls[$arg] = content_url().'/'.$arg);
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

		if (empty($content)) {
			// First argument as URL
			$content = isset($atts['go']) ? $atts['go'] : null;
			if (!empty($content))
				echo "<script> window.location = '" . strip_tags( $content ) . "'; </script>";
		} else {
			// Shortcode content as URL
			echo "<script> window.location = '" . strip_tags( do_shortcode(trim($content)) ) . "'; </script>";
		}
	}

}
