<?php

/*====================================================================================================
 *
 * Load HTML, CSS and JS fields
 *
 *====================================================================================================*/


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



/*====================================================================================================
 *
 * Load shortcode - include files with HTML, PHP script, and shortcodes
 *
 *====================================================================================================*/


function custom_load_script_file( $atts ) {

	extract( shortcode_atts( array(
		'css' => null, 'js' => null, 'dir' => null,
		'file' => null,'format' => null, 'shortcode' => null,
		'gfonts' => null, 'cache' => 'true',
		'php' => 'true', 'debug' => 'false',
		), $atts ) );

	$root_path = ABSPATH;

//	$root_path = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
	$path = trailingslashit( $root_path );
	$site_url = trailingslashit( get_site_url() );
	$content_url = trailingslashit( content_url() );
	$content_path = trailingslashit( WP_CONTENT_DIR );

	if (
		(strpos($file, "http://") !== false) ||
		(strpos($file, "https://") !== false) ||
		(strpos($css, "http://") !== false) ||
		(strpos($css, "https://") !== false) ||
		(strpos($js, "http://") !== false) ||
		(strpos($js, "https://") !== false) ) {
			$dir = "web";
	}

	switch($dir) {
		case 'web' : $path = ""; break;
        case 'site' : $dir = trailingslashit( home_url() ); break; /* Site address */
		case 'wordpress' : $dir =  $site_url; break; /* WordPress directory */
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

//	echo "LOAD: $dir$file$css$js ($path$file$css$js)  $gfonts<br>";

	if($css != '') {

		if ($dir == 'web') {
			$dir = "";
			if ((strpos($css, "http://") === false) &&
				(strpos($css, "https://") === false))
				$dir = "http://";
		}

		$out .= '<link rel="stylesheet" type="text/css" href="';
		$out .= $dir . $css;

		if($cache=='false') {

			for ($i=0; $i<8; $i++) { 
				$tail .= rand(0,9) ; 
			} 

			$out .= '?' . $tail;
		}
		$out .= '" />';
	}
	if($gfonts != '') {
		$out .= '<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=';
		$out .= $gfonts . '" />';
	}
	if($js != '') {

		if ($dir == 'web') {
			$dir = "";
			if ((strpos($js, "http://") === false) &&
				(strpos($js, "https://") === false))
				$dir = "http://";
		}

		$out .= '<script type="text/javascript" src="' . $dir . $js . '"></script>';
	}
	if($file != '') {

		$output = '';

//		echo $path . $file;

		if ($dir != 'web') {

			ob_start();
			@include($path . $file);
			$output = ob_get_clean();
/*
			if (empty($output)) {
				// Try again
//				$output = @file_get_contents($path . $file);
				if (empty($output)) {
					// Try again
//					$output = @file_get_contents($dir . $file);

				}
			}
*/
		} else {

			// get external file

			if ((strpos($file, "http://") === false) &&
				(strpos($file, "https://") === false))
				$file = "http://".$file;

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

/*		if( empty($output) ) {
			$output = @file_get_contents($dir . $file);
			if( ($dir == 'web') && empty($output) ) {
*/
//				$url = $dir . $file;
/*			}
		} */

		if(!empty($output)) {
			if(($format == 'on')||($format == 'true')) { // Format?
				$output = wpautop( $output );
			}

			/* Put safe_eval here for executing PHP inside template files */

			if($php=='true') {
/*				$output = ccs_safe_eval( $output ); */
			}

			if(($shortcode != 'false')||($shortcode != 'off')) { // Shortcode?
				$output = do_shortcode( $output );
			}
			return $output;
		}
	}
	return $out;
}
add_shortcode('load', 'custom_load_script_file');




/*====================================================================================================
 *
 * Do shortcode file - include files with HTML, PHP script, and shortcodes
 *
 *====================================================================================================*/


function do_shortcode_file( $file, $dir = "" ) {

	$content_url = trailingslashit( content_url() );
	$content_path = trailingslashit( WP_CONTENT_DIR );
	$output = null;

	switch($dir) {
		case 'root' : 
		case 'wordpress' : $path = trailingslashit( ABSPATH ); break; /* WordPress directory */
		case 'content' : $path = $content_path; break;
		case 'layout' : $path = $content_path . 'layout/'; break;
		case 'views' : $path = $content_path . 'views/'; break;
		case 'child' : $path = trailingslashit(get_stylesheet_directory()); break;
			
		default:
			$path = trailingslashit(get_template_directory());
	}

	$file = $path . $file . '.html';

/*	$output = @file_get_contents( $file ); */


	if (@file_exists($file)) {
		ob_start();
		@include($file);
		$output = ob_get_clean();
	}

	if ( !empty($output) ) {

/*		$output = ccs_safe_eval( $output ); */
		$output = do_shortcode( $output );

		echo $output;
		return true;
	} else {
		return false;
	}
}


function do_short( $content )
{
	echo do_shortcode( $content );
}


/*====================================================================================================
 *
 * CSS field
 *
 *====================================================================================================*/


/** Load CSS field into header **/

add_action('wp_head', 'load_custom_css');
function load_custom_css() {
	global $wp_query;
	if(isset($wp_query->post)) {
		$custom_css = get_post_meta( $wp_query->post->ID, "css", $single=true );

	/*	if($custom_css == '') { 

		if ( ($ccs_content_template_loader == true)  { ... }
			$root_dir_soft = ABSPATH;

			$root_dir_soft = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/';
			$default_layout_dir = content_url() . '/layout/';
			$default_css = $default_layout_dir . 'style.css';

			if(file_exists($default_css))
				$custom_css .= '[load css="style.css" dir="layout"]';
		} */

		$custom_css = do_shortcode( $custom_css );
		if( !empty($custom_css) ) {
			echo $custom_css;
		}
	}
}

/** Load JS field into footer **/

add_action('wp_footer', 'load_custom_js');
function load_custom_js() {
	global $wp_query;
	if(isset($wp_query->post)) {
		$custom_js = get_post_meta( $wp_query->post->ID, "js", $single=true );

	/*	if($custom_js == '') { 

		if ( ($ccs_content_template_loader == true)  { ... }

			$root_dir_soft = ABSPATH;
			$root_dir_soft = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
			$default_layout_dir = content_url() . '/layout/';
			$default_js = $default_layout_dir . 'scripts.js';

			if(file_exists($default_js))
				$custom_js .= '[load js="scripts.js" dir="layout"]';
		} */

		$custom_js = do_shortcode( $custom_js );
		if( !empty($custom_js) ) {
			echo $custom_js;
		}
	}
}


/** Load HTML field instead of content **/

add_action('the_content', 'load_custom_html');
function load_custom_html($content) {

	global $ccs_global_variable;

	if(( $ccs_global_variable['is_loop'] == "false" ) &&
		!is_admin() ) {

		/*--- Template loader ---*/

		global $ccs_content_template_loader;
		global $wp_query;

		$html_field = get_post_meta( $wp_query->post->ID, "html", $single=true );

		$output = '';

		/* Set default layout filename */

		$root_dir_soft = ABSPATH . '/';
//		$root_dir_soft = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/';

		$default_layout_dir = content_url() . '/layout/';

		$default_header = 'header.html';

		$current_post_type = $wp_query->post->post_type;
		$current_post_slug = $wp_query->post->post_name;

		$default_current_post_type_template = $current_post_type . '-' . $current_post_slug . '.html';
		$default_post_type_template = $current_post_type . '.html';

		$default_current_page_template = 'page-' . $current_post_slug . '.html';
		$default_page_template = 'page.html';

		$default_footer = 'footer.html';

		// Load default header

		if ( ($ccs_content_template_loader == true) &&
			( file_exists( $default_layout_dir . $default_header ) ) ) {
			$output .= '[load file="'. $default_header . '" dir="layout"]';
		}

		if (!empty($html_field)) {
			$output .= $html_field;
		} elseif ( $ccs_content_template_loader == true ) {

			// Load default page template

/*
			echo 'Searching templates<br>';

			echo $default_layout_dir . $default_current_post_type_template . '<br>';
			echo $default_layout_dir . $default_post_type_template . '<br>';
			echo $default_layout_dir . $current_post_type . '/' . $current_post_slug . '.html' . '<br>';
			echo $default_layout_dir . $current_post_type . '/' . $default_post_type_template . '<br>';
*/

			/*----  post-example.html  ----*/ 

			/*----  home.html  ----*/ 

			if( (is_front_page()) && ( file_exists($default_layout_dir . 'home.html' ) ) ) {
				$output .= '[load file="home.html" dir="layout"]';
			}

			elseif( file_exists( $default_layout_dir . $default_current_post_type_template ) ) {
				$output .= '[load file="'. $default_current_post_type_template . '" dir="layout"]';
			}

			/*----  post.html  ----*/ 

			elseif( file_exists( $default_layout_dir . $default_post_type_template ) ) {
				$output .= '[load file="'. $default_post_type_template . '" dir="layout"]';
			}

			/*----  post/example.html  ----*/ 

			elseif( file_exists( $default_layout_dir . $current_post_type . '/' . $current_post_slug . '.html' ) ) {
				$output .= '[load file="'. $current_post_type . '/' . $current_post_slug . '.html' . '" dir="layout"]';
			}

			/*----  post/post.html  ----*/ 

			elseif( file_exists( $default_layout_dir . $current_post_type . '/' . $default_post_type_template ) ) {
				$output .= '[load file="'.  $current_post_type . '/' . $default_post_type_template . '" dir="layout"]';
			}


			/*----  page-example.html  ----*/ 

			elseif( ($current_post_type == 'page') &&
				( file_exists( $default_layout_dir . $default_current_page_template ) ) ) {
					$output .= '[load file="' . $default_current_page_template . '" dir="layout"]';
			}

			/*----  page.html  ----*/ 

			elseif( file_exists( $default_layout_dir . $default_page_template ) ) {
				$output .= '[load file="' . $default_page_template . '" dir="layout"]';
			}


			/*----  page/page-example.html  ----*/ 

			elseif( ($current_post_type == 'page') &&
				( file_exists( $default_layout_dir . 'page/' . $default_current_page_template ) ) ) {
					$output .= '[load file="' . 'page/' . $default_current_page_template . '" dir="layout"]';
			}

			/*----  page/page.html  ----*/ 

			elseif( file_exists( $default_layout_dir . 'page/' . $default_page_template ) ) {
				$output .= '[load file="' . 'page/' . $default_page_template . '" dir="layout"]';
			}

		}

		// Load default footer

		if ( ($ccs_content_template_loader == true) &&
			( file_exists( $default_layout_dir . $default_footer ) ) ) {
			$output .= '[load file="' . $default_footer . '" dir="layout"]';
		}

		$custom_html = do_shortcode( $output );
		if( $custom_html != '' ) {
			return $custom_html;
		} else {
			return $content;
		}
	}
	return $content;
}



function ccs_safe_eval($code) {
	ob_start();
	$code = '?>' . $code;
	eval($code);
	return ob_get_clean();
}

	/* Content passed to the shortcode is after wptexturize, so we have to reverse it.. 

if ( ! function_exists('undo_wptexturize')) {
	function undo_wptexturize($content) {
		$content = strip_tags($content);
		$content = preg_replace("/\[{1}([\/]*)([a-zA-z\/]{1}[a-zA-Z0-9]*[^\'\"])([a-zA-Z0-9 \!\"\£\$\%\^\&\*\*\(\)\_\-\+\=\|\\\,\.\/\?\:\;\@\'\#\~\{\}\¬\¦\`\<\>]*)([\/]*)([\]]{1})/ix","<$1$2$3>",$content,"-1");
		$content = htmlspecialchars($content, ENT_NOQUOTES);
		$content = str_replace("&amp;#8217;","'",$content);
		$content = str_replace("&amp;#8216;","'",$content);
		$content = str_replace("&amp;#8242;","'",$content);
		$content = str_replace("&amp;#8220;","\"",$content);
		$content = str_replace("&amp;#8221;","\"",$content);
		$content = str_replace("&amp;#8243;","\"",$content);
		$content = str_replace("&amp;#039;","'",$content);
		$content = str_replace("&#039;","'",$content);
		$content = str_replace("&amp;#038;","&",$content);
		$content = str_replace("&amp;gt;",'>',$content);
		$content = str_replace("&amp;lt;",'<',$content);
		$content = htmlspecialchars_decode($content);

		return $content;
	}
}

if ( ! shortcode_exists('php')) {

	function custom_php_shortcode($atts, $content) {
		ob_start();
		eval( undo_wptexturize( $content ) );
		return ob_get_clean();
	}
	add_shortcode( 'php', 'custom_php_shortcode' );
}
*/