<?php

/*========================================================================
 *
 * Format functions
 *
 *=======================================================================*/

class CCS_Format {

	function __construct() {

		add_shortcode( 'x', array($this, 'x_shortcode') );
		add_shortcode( 'br', array($this, 'br_shortcode') );
		add_shortcode( 'p', array($this, 'p_shortcode') );
		add_shortcode('format', array($this, 'format_shortcode') );
		add_shortcode('direct', array($this, 'direct_shortcode') );
		add_shortcode('clean', array($this, 'clean_shortcode') );
	}


	/*========================================================================
	 *
	 * [x] - Repeat x times: [x 10]..[/x]
	 *
	 *=======================================================================*/

	function x_shortcode( $atts, $content ) {

		$out = '';

		if (isset($atts[0])) {
			$x = $atts[0];
			for ($i=0; $i <$x ; $i++) { 
				$out .= do_shortcode($content);
			}
		}
		return $out;
	}

	function br_shortcode( $atts, $content ) {
		return '<br>';
	}

	function p_shortcode( $atts, $content ) {
		return '<p>' . do_shortcode($content) . '</p>';
	}

	function format_shortcode( $atts, $content ) {
		return wpautop(do_shortcode($content)); // Do shortcode, then format
	}


	function direct_shortcode( $atts, $content ) {
		return $content; // Don't run shortcodes
	}



	/*========================================================================
	 *
	 * Strip an array of tags from content
	 *
	 *=======================================================================*/

	function strip_tag_list( $content, $tags ) {

		$tags = implode("|", $tags);
		$out = preg_replace('!<\s*('.$tags.').*?>((.*?)</\1>)?!is', '\3', $content); 

		return $out;
	}

	function clean_content($content){   

	    $content = self::strip_tag_list( $content, array('p','br') );

	    return $content;
	}

	function clean_shortcode( $atts, $content ) {

		$content = self::strip_tag_list( $content, array('p','br') );

		return do_shortcode($content);
	}


}
new CCS_Format;
