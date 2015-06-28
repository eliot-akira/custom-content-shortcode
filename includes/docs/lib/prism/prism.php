<?php

// ------------ Unused ----------

new Module_Prism_Admin;

class Module_Prism_Admin {

	function __construct() {

    add_action( 'admin_enqueue_scripts', array( $this, 'load' ) );
//    add_action( 'wp_enqueue_scripts', array( $this, 'load' ) );
//		add_shortcode( 'prism', array($this, 'prism_shortcode') );
	}

	function load() {
		$url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, dirname(__FILE__));
		wp_enqueue_style( 'prism-admin', $url.'/css/prism.css', array(), '0.0.1' );
    wp_enqueue_script( 'prism-admin', $url.'/js/prism.min.js', array(), '0.0.1', true );
	}


	function prism_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'lang' => 'markup',
		), $atts ) );

		if (isset($atts[0]) && !empty($atts[0])) {
			$lang = $atts[0];
		}

		if ($lang=='js') $lang='javascript';

		// Get rid of any leading space or new line, and escape HTML
		$content = esc_html__(trim($content));

		$out = '<pre><code class="language-'.$lang.'">'.$content.'</code></pre>';
		return $out;
	}

}
