<?php

/*====================================================================================================
 *
 * Load file, CSS, JS
 *
 *====================================================================================================*/



/*====================================================================================================
 *
 * Load shortcode - include files with HTML, PHP script, and shortcodes
 *
 *====================================================================================================*/


function custom_load_script_file( $atts ) {

	global $post;

	extract( shortcode_atts( array(
		'css' => null, 'js' => null, 'dir' => null,
		'file' => null,'format' => null, 'shortcode' => null,
		'gfonts' => null, 'cache' => 'true',
		'php' => 'true', 'debug' => 'false',
		'view' => ''
		), $atts ) );


		/*========================================================================
		 *
		 * Load view template: [load view]
		 *
		 *=======================================================================*/

		if (isset($atts[0]) && $atts[0]=='view') {

			$dir = 'views';

			if (!empty($view)) {

				$file = $view;
				$out = do_shortcode_file($file,$dir,true);

			} else {

				$current_post_type = $post->post_type;
				$current_post_slug = $post->post_name;

				// Do post_type/post_slug.html

				$file = $current_post_type.'/'.$current_post_slug;
				$out = do_shortcode_file($file,$dir,true);

				if (!$out) {

					// If not, post_slug.html

					$out = do_shortcode_file($current_post_slug,$dir,true);
				}
			}
			return $out;
		}



	/*========================================================================
	 *
	 * Set up paths
	 *
	 *=======================================================================*/

	$root_path = ABSPATH;
	$path = trailingslashit( $root_path );
	$site_url = trailingslashit( get_site_url() );
	$content_url = trailingslashit( content_url() );
	$content_path = trailingslashit( WP_CONTENT_DIR );

	if ((strpos($file, 'http://') !== false) ||	(strpos($file, 'https://') !== false) ||
		(strpos($css, 'http://') !== false) || (strpos($css, 'https://') !== false) ||
		(strpos($js, 'http://') !== false) || (strpos($js, 'https://') !== false) ) {

			$dir = 'web';
	}

	switch ($dir) {
		case 'web' : $path = ''; break;
        case 'site' : $dir = trailingslashit( home_url() ); break; // Site address
		case 'wordpress' : $dir =  $site_url; break; // WordPress directory
		case 'content' :
			$dir = $content_url;
			$path = $content_path;
			break;
		case 'layout' :
			$dir = $content_url . 'layout/';
			$path = $content_path . 'layout/';
		break;
		case 'views' :
			$dir = $content_url . 'views/';
			$path = $content_path . 'views/';
			break;
		case 'child' :
			$dir = trailingslashit(get_stylesheet_directory_uri());
			$path = trailingslashit(get_stylesheet_directory());
			break;
		default:

			if(($dir=='theme')||($dir=='template')) {

				$dir = trailingslashit(get_template_directory_uri());
				$path = trailingslashit(get_template_directory());

			} else {

				$dir = trailingslashit(get_stylesheet_directory_uri());
				$path = trailingslashit(get_stylesheet_directory());

				if($css != '') {
					$dir .= 'css/';
				}
				if($js != '') {
					$dir .= 'js/';
				}
			}
	}

	$out = '';


	/*========================================================================
	 *
	 * Load CSS - [load css]
	 *
	 *=======================================================================*/

	if (!empty($css)) {

		if ($dir == 'web') {
			$dir = '';
			if ((strpos($css, 'http://') === false) &&
				(strpos($css, 'https://') === false))
				$dir = 'http://';
		}

		$out = '<link rel="stylesheet" type="text/css" href="'.$dir.$css;

		if ($cache=='false') {

			// Generate random string to prevent caching
			for ($i=0; $i<8; $i++) { 
				$tail .= rand(0,9) ; 
			} 
			$out .= '?' . $tail;
		}
		$out .= '" />';

		return $out;
	}


	/*========================================================================
	 *
	 * Load Google Fonts - [load gfonts]
	 *
	 *=======================================================================*/

	if (!empty($gfonts != '')) {

		return '<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family='.$gfonts.'" />';
	}


	/*========================================================================
	 *
	 * Load JS - [load js]
	 *
	 *=======================================================================*/

	if (!empty($js)) {

		if ($dir == 'web') {
			$dir = '';
			if ((strpos($js, 'http://') === false) && (strpos($js, 'https://') === false))
				$dir = 'http://';
		}

		return '<script type="text/javascript" src="' . $dir . $js . '"></script>';
	}


	/*========================================================================
	 *
	 * Load file - [load file]
	 *
	 *=======================================================================*/

	if (!empty($file) ) {

		if ($dir != 'web') {

			// Include file

			ob_start();
			@include($path . $file);
			$out = ob_get_clean();

		} else {

			// Get external file

			if ((strpos($file, 'http://') === false) && (strpos($file, 'https://') === false))
				$file = 'http://'.$file;

			$url = $file;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($status == 200) {
				$output = $data; // Success
			}
		}

		if (!empty($output)) {

			if (($format == 'on') || ($format == 'true')) { // Format only if specified
				$output = wpautop( $output );
			}

			if (($shortcode != 'false') && ($shortcode != 'off')) { // Do shortcode by default
				$output = do_shortcode( $output );
			}
			return $output;
		}
	}


}
add_shortcode('load', 'custom_load_script_file');




/*====================================================================================================
 *
 * Do shortcode file - include files with HTML and shortcodes
 *
 *====================================================================================================*/

function do_shortcode_file( $file, $dir = null, $return = false ) {

	$content_url = trailingslashit( content_url() );
	$content_path = trailingslashit( WP_CONTENT_DIR );
	$output = null;

	switch($dir) {
		case 'root' : 
		case 'wordpress' : $path = trailingslashit( ABSPATH ); break;
		case 'content' : $path = $content_path; break;
		case 'layout' : $path = $content_path . 'layout/'; break;
		case 'views' : $path = $content_path . 'views/'; break;
		case 'child' : $path = trailingslashit(get_stylesheet_directory()); break;
		default:
			$path = trailingslashit(get_template_directory()); // Theme
	}

	$file = $path . $file . '.html';

//	if (@file_exists($file)) {
		ob_start();
		@include($file);
		$output = ob_get_clean();
//	}

	if ( !empty($output) ) {

		$output = do_shortcode( $output );

		if ($return) {
			return $output;
		} else {

			echo $output;
			return true;
		}

	} else {
		return false;
	}
}



/*====================================================================================================
 *
 * Special fields
 *
 *====================================================================================================*/


/** Load CSS field into header **/

add_action('wp_head', 'load_custom_css');
function load_custom_css() {

	global $post;

	if (!empty($post)) {

		$custom_css = get_post_meta( $post->ID, 'css', true );

		if( !empty($custom_css) ) {
			echo do_shortcode( $custom_css );
		}
	}
}

/** Load JS field into footer **/

add_action('wp_footer', 'load_custom_js');
function load_custom_js() {

	global $post;

	if (!empty($post)) {

		$custom_js = get_post_meta( $post->ID, 'js', true );

		if( !empty($custom_js) ) {
			echo do_shortcode( $custom_js ); // print to footer
		}
	}
}


/** Load HTML field instead of content **/

add_action('the_content', 'load_custom_html');

function load_custom_html( $content ) {

	global $post;
	global $ccs_global_variable;

	if ( ($ccs_global_variable['is_loop']=='false') && !is_admin() && !empty($post) ) {

		$html_field = get_post_meta( $post->ID, 'html', true );

		if( !empty($html_field) ) {
			return do_shortcode($html_field);
		} else {
			return $content;
		}
	}
	return $content;
}



function custom_css_wrap($atts, $content = null) {
    $result = '<style type="text/css">';
    $result .= do_shortcode($content);
    $result .= '</style>';
    return $result;
}
add_shortcode('css', 'custom_css_wrap');

function custom_js_wrap( $atts, $content = null ) {
    $result = '<script type="text/javascript">';
    $result .= do_shortcode( $content );
    $result .= '</script>';
    return $result;
}
add_shortcode('js', 'custom_js_wrap');
