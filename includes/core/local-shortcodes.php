<?php
/**
 *
 * Local Shortcodes
 * @version  0.0.6
 *
 * Adapted from WordPress core:
 * https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
 *
 * ---
 * WordPress API for creating bbcode like tags or what WordPress calls
 * "shortcodes." The tag and attribute parsing or regular expression code is
 * based on the Textpattern tag parser.
 *
 * A few examples are below:
 *
 * [shortcode /]
 * [shortcode foo="bar" baz="bing" /]
 * [shortcode foo="bar"]content[/shortcode]
 *
 * Shortcode tags support attributes and enclosed content, but does not entirely
 * support inline shortcodes in other shortcodes. You will have to call the
 * shortcode parser in your function to account for that.
 *
 * {@internal
 * Please be aware that the above note was made during the beta of WordPress 2.6
 * and in the future may not be accurate. Please update the note when it is no
 * longer the case.}}
 *
 * To apply shortcode tags to content:
 *
 * <code>
 * $out = do_local_shortcode( $context, $content );
 * </code>
 *
 * @link http://codex.wordpress.org/Shortcode_API
 *
 * @package WordPress
 * @subpackage Shortcodes
 * @since 2.5.0
 */

// Declare only once
if ( ! function_exists('add_local_shortcode') ) {

/**
 * Container for storing local shortcode function hooks, by context and tag
 *
 * @since 2.5.0
 *
 * @name $local_shortcode_tags
 * @var array
 * @global array $local_shortcode_tags
 * @example $local_shortcode_tags['context']['tag']
 *
 */

$local_shortcode_tags = array();

// Current context: support nested local shortcodes by restoring parent namespace
$current_local_shortcode_context = '';

$doing_local_shortcode = false;

/**
 * Add hook for shortcode tag.
 *
 * There can only be one hook for each shortcode. Which means that if another
 * plugin has a similar shortcode, it will override yours or yours will override
 * theirs depending on which order the plugins are included and/or ran.
 *
 * Simplest example of a shortcode tag using the API:
 *
 * <code>
 * // [footag foo="bar"]
 * function footag_func($atts) {
 * 	return "foo = {$atts[foo]}";
 * }
 * add_local_shortcode('footag', 'footag_func');
 * </code>
 *
 * Example with nice attribute defaults:
 *
 * <code>
 * // [bartag foo="bar"]
 * function bartag_func($atts) {
 * 	$args = shortcode_atts(array(
 * 		'foo' => 'no foo',
 * 		'baz' => 'default baz',
 * 	), $atts);
 *
 * 	return "foo = {$args['foo']}";
 * }
 * add_local_shortcode('bartag', 'bartag_func');
 * </code>
 *
 * Example with enclosed content:
 *
 * <code>
 * // [baztag]content[/baztag]
 * function baztag_func($atts, $content='') {
 * 	return "content = $content";
 * }
 * add_local_shortcode('baztag', 'baztag_func');
 * </code>
 *
 * @since 2.5.0
 *
 * @uses $local_shortcode_tags
 *
 * @param string $tag Shortcode tag to be searched in post content.
 * @param callable $func Hook to run when shortcode is found.
 */
function add_local_shortcode($global_tag, $tag, $func, $add_global=false) {
	global $local_shortcode_tags;

	if ( is_callable($func) ) {
		$local_shortcode_tags[$global_tag][$tag] = $func;
		if ($add_global) add_shortcode($tag, $func);
	} else {
    // Not callable
  }
}

/**
 * Removes hook for shortcode.
 *
 * @since 2.5.0
 *
 * @uses $local_shortcode_tags
 *
 * @param string $tag shortcode tag to remove hook for.
 */
function remove_local_shortcode($global_tag, $tag) {
	global $local_shortcode_tags;

	unset($local_shortcode_tags[$global_tag][$tag]);
}

/**
 * Clear all shortcodes.
 *
 * This function is simple, it clears all of the shortcode tags by replacing the
 * shortcodes global by a empty array. This is actually a very efficient method
 * for removing all shortcodes.
 *
 * @since 2.5.0
 *
 * @uses $local_shortcode_tags
 */
function remove_all_local_shortcodes($global_tag) {
	global $local_shortcode_tags;

	$local_shortcode_tags[$global_tag] = array();
}

/**
 * Whether a registered shortcode exists named $tag
 *
 * @since 3.6.0
 *
 * @global array $local_shortcode_tags
 * @param string $tag
 * @return boolean
 */
function local_shortcode_exists( $global_tag, $tag ) {
	global $local_shortcode_tags;
	return array_key_exists( $tag, $local_shortcode_tags[$global_tag] );
}

/**
 * Whether the passed content contains the specified shortcode
 *
 * @since 3.6.0
 *
 * @global array $local_shortcode_tags
 * @param string $tag
 * @return boolean
 */
function has_local_shortcode( $content, $tag, $global_tag ) {
	if ( false === strpos( $content, '[' ) ) {
		return false;
	}

	if ( local_shortcode_exists( $global_tag, $tag ) ) {
		preg_match_all( '/' . get_local_shortcode_regex($global_tag) . '/s', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) )
			return false;

		foreach ( $matches as $shortcode ) {
			if ( $tag === $shortcode[2] ) {
				return true;
			} elseif ( ! empty( $shortcode[5] ) && has_local_shortcode( $shortcode[5], $global_tag, $tag ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Search content for shortcodes and filter shortcodes through their hooks.
 *
 * If there are no shortcode tags defined, then the content will be returned
 * without any filtering. This might cause issues when plugins are disabled but
 * the shortcode will still show up in the post or content.
 *
 * @since 2.5.0
 *
 * @uses $local_shortcode_tags
 * @uses get_shortcode_regex() Gets the search pattern for searching shortcodes.
 *
 * @param string $content Content to search for shortcodes
 * @return string Content with shortcodes filtered out.
 */
function do_local_shortcode($global_tag, $content, $do_global = false) {
	global $local_shortcode_tags;
	global $current_local_shortcode_context;
	global $doing_local_shortcode;

	$doing_local_shortcode = false;

	// No shortcode in content, or no local shortcode registered in this namespace
	if ( false === strpos( $content, '[' )  || !isset($local_shortcode_tags[$global_tag])) {
		return $content;
	}

	$current_local_shortcodes = $local_shortcode_tags[$global_tag];

	if ( empty( $current_local_shortcodes ) || ! is_array( $current_local_shortcodes ) ) {
		return $content;
	}

	$doing_local_shortcode = true;

	// Store previous namespace and declare current one
	$previous_context = $current_local_shortcode_context;
	$current_local_shortcode_context = $global_tag;


	$pattern = get_local_shortcode_regex($global_tag);

	$content = preg_replace_callback( "/$pattern/s", 'do_local_shortcode_tag', $content );


	// Restore previous namespace
	$current_local_shortcode_context = $previous_context;

	$doing_local_shortcode = false;

	if ($do_global) {
		return do_shortcode($content);
	} else {
		return $content;
	}
}

/**
 * Send additional variable to shortcode filters.
 *
 * Purpose: To populate attributes with field values.
 */
function do_local_shortcode_with($global_tag, $content, $post) {
	global $local_shortcode_tags;
	global $current_local_shortcode_context;

	// No shortcode in content, or no local shortcode registered in this namespace
	if ( false === strpos( $content, '[' )  || !isset($local_shortcode_tags[$global_tag]))
		return $content;

	$current_local_shortcodes = $local_shortcode_tags[$global_tag];

	if ( empty( $current_local_shortcodes ) || ! is_array( $current_local_shortcodes ) )
		return $content;

	// Store previous namespace and declare current one
	$previous_context = $current_local_shortcode_context;
	$current_local_shortcode_context = $global_tag;


	$pattern = get_local_shortcode_regex($global_tag);

	// Expanded preg_match callbacks
	if ( ! preg_match_all( "/$pattern/s", $content, $match_all ) )
		return $content;

	// convert arrays to what preg_replace_callback uses
	// because that is what do_local_shortcode_tag expects
	$new_matches = array();
	foreach ( $match_all as $m_key => $matches ) {
		foreach ( $matches as $match_key => $match ) {
			// assemble match array
			$new_matches[$match_key][$m_key] = $match;
		}
	}

	// process shortcodes and concatenate results
	$output_string = '';
	foreach ( $new_matches as $m ) {
		$output_string .= do_local_shortcode_tag_with( $m , $post );
	}


	// Restore previous namespace
	$current_local_shortcode_context = $previous_context;

	return $output_string;
}

/**
 * Like do_shortcode_tag with passing an extra variable to shortcode function.
 */
function do_local_shortcode_tag_with( $m, $post = null ) {
	global $local_shortcode_tags;
	global $current_local_shortcode_context;

	$context = $current_local_shortcode_context;

	// allow [[foo]] syntax for escaping a tag
	if ( $m[1] == '[' && $m[6] == ']' ) {
		return substr($m[0], 1, -1);
	}

	$tag = $m[2];
	$attr = shortcode_parse_atts( $m[3] );

	if ( isset( $m[5] ) ) {
		// enclosing tag - extra parameter
		return $m[1] . call_user_func( $local_shortcode_tags[$context][$tag], $attr, $m[5], $tag, $post ) . $m[6];
	} else {
		// self-closing tag
		return $m[1] . call_user_func( $local_shortcode_tags[$context][$tag], $attr, null,  $tag, $post ) . $m[6];
	}
}

/**
 * Regular Expression callable for do_local_shortcode() for calling shortcode hook.
 * @see get_shortcode_regex for details of the match array contents.
 *
 * @since 2.5.0
 * @access private
 * @uses $local_shortcode_tags
 *
 * @param array $m Regular expression match array
 * @return mixed False on failure.
 */
function do_local_shortcode_tag( $m ) {
	global $local_shortcode_tags;
	global $current_local_shortcode_context;

	$context = $current_local_shortcode_context;

	// allow [[foo]] syntax for escaping a tag
	if ( $m[1] == '[' && $m[6] == ']' ) {
		return substr($m[0], 1, -1);
	}

	$tag = $m[2];
	$attr = shortcode_parse_atts( $m[3] );

	if ( isset( $m[5] ) ) {
		// enclosing tag - extra parameter
		return $m[1] . call_user_func( $local_shortcode_tags[$context][$tag], $attr, $m[5], $tag ) . $m[6];
	} else {
		// self-closing tag
		return $m[1] . call_user_func( $local_shortcode_tags[$context][$tag], $attr, null,  $tag ) . $m[6];
	}
}

/**
 * Retrieve the shortcode regular expression for searching.
 *
 * The regular expression combines the shortcode tags in the regular expression
 * in a regex class.
 *
 * The regular expression contains 6 different sub matches to help with parsing.
 *
 * 1 - An extra [ to allow for escaping shortcodes with double [[]]
 * 2 - The shortcode name
 * 3 - The shortcode argument list
 * 4 - The self closing /
 * 5 - The content of a shortcode when it wraps some content.
 * 6 - An extra ] to allow for escaping shortcodes with double [[]]
 *
 * @since 2.5.0
 *
 * @uses $shortcode_tags
 *
 * @return string The shortcode search regular expression
 */
function get_local_shortcode_regex($global_tag) {
	global $local_shortcode_tags;
	$tagnames = array_keys($local_shortcode_tags[$global_tag]);
	$tagregexp = join( '|', array_map('preg_quote', $tagnames) );

	// WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
	// Also, see shortcode_unautop() and shortcode.js.
	return
		  '\\['                              // Opening bracket
		. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
		. "($tagregexp)"                     // 2: Shortcode name
		. '(?![\\w-])'                       // Not followed by word character or hyphen
		. '('                                // 3: Unroll the loop: Inside the opening shortcode tag
		.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
		.     '(?:'
		.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
		.         '[^\\]\\/]*'               // Not a closing bracket or forward slash
		.     ')*?'
		. ')'
		. '(?:'
		.     '(\\/)'                        // 4: Self closing tag ...
		.     '\\]'                          // ... and closing bracket
		. '|'
		.     '\\]'                          // Closing bracket
		.     '(?:'
		.         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
		.             '[^\\[]*+'             // Not an opening bracket
		.             '(?:'
		.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
		.                 '[^\\[]*+'         // Not an opening bracket
		.             ')*+'
		.         ')'
		.         '\\[\\/\\2\\]'             // Closing shortcode tag
		.     ')?'
		. ')'
		. '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
}

/**
 * Combine user attributes with known attributes and fill in defaults when needed.
 *
 * The pairs should be considered to be all of the attributes which are
 * supported by the caller and given as a list. The returned attributes will
 * only contain the attributes in the $pairs list.
 *
 * If the $atts list has unsupported attributes, then they will be ignored and
 * removed from the final returned list.
 *
 * @since 2.5.0
 *
 * @param array $pairs Entire list of supported attributes and their defaults.
 * @param array $atts User defined attributes in shortcode tag.
 * @param string $shortcode Optional. The name of the shortcode, provided for context to enable filtering
 * @return array Combined and filtered attribute list.
 */
function local_shortcode_atts( $pairs, $atts, $shortcode = '', $post = null ) {
	$atts = (array)$atts;
	$out = array();
	foreach($pairs as $name => $default) {
		if ( array_key_exists($name, $atts) )
			$out[$name] = $atts[$name];
		else
			$out[$name] = $default;
	}
	/**
	 * Filter a shortcode's default attributes.
	 *
	 * If the third parameter of the shortcode_atts() function is present then this filter is available.
	 * The third parameter, $shortcode, is the name of the shortcode.
	 *
	 * @since 3.6.0
	 *
	 * @param array $out The output array of shortcode attributes.
	 * @param array $pairs The supported attributes and their defaults.
	 * @param array $atts The user-defined shortcode attributes.
	 * @param object $post The post used in shortcode output filter.
	 */
	if ( $shortcode )
		$out = apply_filters( "shortcode_atts_{$shortcode}", $out, $pairs, $atts, $post );

	return $out;
}

/**
 * Remove all shortcode tags from the given content.
 *
 * @since 2.5.0
 *
 * @uses $local_shortcode_tags
 *
 * @param string $content Content to remove shortcode tags.
 * @return string Content without shortcode tags.
 */
function strip_local_shortcode( $global_tag, $content ) {
	global $local_shortcode_tags;

	if ( false === strpos( $content, '[' ) ) {
		return $content;
	}

	if (empty($local_shortcode_tags) || !is_array($local_shortcode_tags))
		return $content;

	$pattern = get_local_shortcode_regex($global_tag);

	return preg_replace_callback( "/$pattern/s", 'strip_shortcode_tag', $content );
}

// add_filter('the_content', 'do_local_shortcode', 12); // AFTER wpautop()
}
