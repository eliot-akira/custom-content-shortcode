<?php

/*---------------------------------------------
 *
 * [raw]..[/raw]
 *
 * Protect shortcode content from wpautop and wptexturize
 *
 */

function ccs_raw_format( $content ) {

	$new_content = null;
	$pattern_full = '{(\[raw\].*?\[/raw\])}is';
	$pattern_contents = '{\[raw\](.*?)\[/raw\]}is';
	$pieces = preg_split($pattern_full, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
	foreach ($pieces as $piece) {
		if (preg_match($pattern_contents, $piece, $matches)) {
			$new_content .= $matches[1];
		} else {
			$new_content .= wptexturize(wpautop($piece));
		}
	}
	return do_shortcode($new_content);
}

remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');
add_filter('the_content', 'ccs_raw_format', 1);
