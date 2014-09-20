<?php 

new CCS_Shortcode_Unautop;

class CCS_Shortcode_Unautop {

	function __construct() {

		// frontend only
		if ( is_admin() ) return;

		remove_filter( "the_content", "shortcode_unautop" );
		add_filter( "the_content", array($this,"better_shortcode_unautop") );
		remove_filter( "the_excerpt", "shortcode_unautop" );
		add_filter( "the_excerpt", array($this,"better_shortcode_unautop") );
	}

	/**
	 * Don't auto-p wrap shortcodes that stand alone
	 *
	 * Ensures that shortcodes are not wrapped in <<p>>...<</p>>.
	 *
	 * @since 2.9.0
	 *
	 * @param string $pee The content.
	 * @return string The filtered content.
	 */

	function better_shortcode_unautop( $pee ) {

	    global $shortcode_tags;

	    if ( empty( $shortcode_tags ) || !is_array( $shortcode_tags ) ) {
	        return $pee;
	    }

	    $tagregexp = join( '|', array_map( 'preg_quote', array_keys( $shortcode_tags ) ) );

	    $pattern =
	          '/'
	        . '<p>'                              // Opening paragraph
	        . '\\s*+'                            // Optional leading whitespace
	        . '('                                // 1: The shortcode
	        .     '\\[\\/?'                      // Opening bracket for opening or closing shortcode tag
	        .     "($tagregexp)"                 // 2: Shortcode name
	        .     '(?![\\w-])'                   // Not followed by word character or hyphen
	                                             // Unroll the loop: Inside the opening shortcode tag
	        .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
	        .     '(?:'
	        .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
	        .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
	        .     ')*?'
	        .     '[\\w\\s="\']*'                // Shortcode attributes
	        .     '(?:'
	        .         '\\s*+'                    // Optional leading whitespace, supports [footag /]
	        .         '\\/\\]'                   // Self closing tag and closing bracket
	        .     '|'
	        .         '\\]'                      // Closing bracket
	        .         '(?:'                      // Unroll the loop: Optionally, anything between the opening and closing shortcode tags
	        .             '(?!<\/p>)'            // Not followed by closing paragraph
	        .             '[^\\[]*+'             // Not an opening bracket, matches all content between closing bracket and closing shortcode tag
	        .             '(?:'
	        .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
	        .                 '[^\\[]*+'         // Not an opening bracket
	        .             ')*+'
	        .             '\\[\\/\\2\\]'         // Closing shortcode tag
	        .         ')?'
	        .     ')'
	        . ')'
	        . '\\s*+'                            // optional trailing whitespace
	        . '<\\/p>'                           // closing paragraph
	        . '/s';

	    return preg_replace( $pattern, '$1', $pee );
	}
}






