<?php

/*========================================================================
 *
 * Shortcodes:
 * 
 * user, is/isnt
 * list_shortcodes, search_form, blog
 *
 *=======================================================================*/


function custom_user_shortcode( $atts, $content ) {

	global $current_user;
	get_currentuserinfo();

	extract(shortcode_atts(array(
		'field' => '',
		'meta' => ''
	), $atts));

	if(!empty($meta))
		$field=$meta;

	$out = null;

	if (!empty($field)) {
		return get_user_meta( $current_user->ID, $field, true );
	}

	if( is_array( $atts ) )
		$atts = array_flip( $atts );

	if( isset( $atts['name'] ) )
		return $current_user->user_login;

	if( isset( $atts['id'] ) )
		return $current_user->ID;

	if( isset( $atts['email'] ) )
		return $current_user->user_email;

	if( isset( $atts['fullname'] ) )
		return $current_user->display_name;

	if( isset( $atts['avatar'] ) )
		return get_avatar( $current_user->ID );

}
add_shortcode('user', 'custom_user_shortcode');


/*========================================================================
 *
 * [is] shortcode - combine with [if]...
 *
 *=======================================================================*/

function custom_is_shortcode( $atts, $content, $tag ) {

	global $current_user;

	extract(shortcode_atts(array(
		'user' => '',
		'format' => 'false',
		'shortcode' => 'true',
	), $atts));

	if ($format == 'true') { // Format?
		$content = wpautop( $content );
	}
	if ($shortcode != 'false') { // Shortcode?
		$content = do_shortcode( $content );
	}

	if($user!='') {
		get_currentuserinfo();
		$is_it = false;

		$user_array = explode(",", $user);

		foreach ($user_array as $this_user) {
			if ( $this_user == ($current_user->user_login) )
				$is_it = true;
			if ( ( $this_user == ($current_user->ID) ) &&
				ctype_digit($this_user) ) // $user is a number?
					$is_it = true;
		}
		if($tag=="isnt")
			$is_it = !$is_it;
		if($is_it)
			return $content;
		return null;
	}

	if( is_array( $atts ) ) {
		$atts = array_flip( $atts );
	}

	if 	( ($tag=='is') &&
		( 
		( isset( $atts['admin'] ) && current_user_can( 'manage_options' ) ) ||
		( isset( $atts['login'] ) && is_user_logged_in() ) ||
		( isset( $atts['logout'] ) && !is_user_logged_in() )
		) ) {
			return $content;
	}
	if 	( ($tag=='isnt') &&
		( 
		( isset( $atts['admin'] ) && !current_user_can( 'manage_options' ) ) ||
		( isset( $atts['login'] ) && !is_user_logged_in() ) ||
		( isset( $atts['logout'] ) && is_user_logged_in() )
		) ) {
			return $content;
	}

	return null;
}
add_shortcode('is', 'custom_is_shortcode');
add_shortcode('isnt', 'custom_is_shortcode');

function custom_blog_shortcode( $atts, $content ){

	extract(shortcode_atts(array(
		'id' => '',
	), $atts));

	$out = $content;

	if ( empty($id) || !blog_exists($id))
		return;

	switch_to_blog($id);
	$out = do_shortcode($out);
	restore_current_blog();

	return $out;
}
add_shortcode('blog', 'custom_blog_shortcode');



function custom_list_shortcodes( ) {
	global $shortcode_tags;
	ksort($shortcode_tags); // Alphabetical sort

	$out = '';

	foreach ( $shortcode_tags as $key => $value ) {

		if(is_array($value)) $value='Class object';

		$out .= $key . ' = ' . $value . '<br>';

	}
	return $out;
}
add_shortcode('list_shortcodes', 'custom_list_shortcodes');


function custom_search_form_shortcode() {

	ob_start();
	get_search_form(true);
	$out = ob_get_contents();
	ob_end_clean();
	
	return "$out";

}
add_shortcode('search_form', 'custom_search_form_shortcode');

if ( ! function_exists( 'blog_exists' ) ) {

    /**
     * Checks if a blog exists and is not marked as deleted.
     *
     * @link   http://wordpress.stackexchange.com/q/138300/73
     * @param  int $blog_id
     * @param  int $site_id
     * @return bool
     */
    function blog_exists( $blog_id, $site_id = 0 ) {

        global $wpdb;
        static $cache = array ();

        $site_id = (int) $site_id;

       	if (!function_exists('get_current_site'))
       		return false;

        if ( 0 === $site_id ) {
        	$current_site = get_current_site();
            $site_id = $current_site->id;
        }

        if ( empty ( $cache ) or empty ( $cache[ $site_id ] ) ) {

            if ( wp_is_large_network() ) // we do not test large sites.
                return TRUE;

            $query = "SELECT `blog_id` FROM $wpdb->blogs
                    WHERE site_id = $site_id AND deleted = 0";

            $result = $wpdb->get_col( $query );

            // Make sure the array is always filled with something.
            if ( empty ( $result ) )
                $cache[ $site_id ] = array ( 'do not check again' );
            else
                $cache[ $site_id ] = $result;
        }

        return in_array( $blog_id, $cache[ $site_id ] );
    }
}