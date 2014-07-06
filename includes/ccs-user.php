<?php

/*====================================================================================================
 *
 * Relative URL shortcodes - [url site/theme/child/views/content/uploads]
 *
 *====================================================================================================*/


class urlShortcode
{
    public static function userSettings()
    {
        $blogurl_settings = array();

        $blogurl_settings['home'] = get_option( 'home' );
        $blogurl_settings['wordpress'] = get_option( 'siteurl' );
        $blogurl_settings['content'] = get_option( 'siteurl' ) . '/' . 'wp-content';
        $blogurl_settings['templateurl'] = get_bloginfo( 'template_directory' );
        $blogurl_settings['childtemplateurl'] = get_bloginfo( 'stylesheet_directory' );
        
        $blogurl_settings['insertslash'] = false;
        
        return $blogurl_settings;
    }
    
    public static function custom_url( $attributes )
    {
        $blogurl_settings = urlShortcode::getSettings();

		extract(shortcode_atts(array(
			'login' => '',
			'logout' => '',
			'go' => '',
		), $attributes));

        if ( is_array( $attributes ) ) {
            $attributes = array_flip( $attributes );
        }

		if ( ($go=='here') || (isset($attributes['logout']) && empty($go)) ) {
			global $wp;
			$go = home_url( $wp->request );
		} elseif($go!='') {
			if($go=='home')
				$go = $blogurl_settings['home'];
			elseif( (isset( $attributes['login'] )) || (isset( $attributes['logout'] )) )
				if( !strpos ($go,"." ) )
					$go = do_shortcode('[content name="'.$go.'" field="url"]');
		}


        if( isset( $attributes['wordpress'] ) )
        {
            $return_blogurl = $blogurl_settings['wordpress'];
        }
        elseif( isset( $attributes['uploads'] ) )
        {
            $return_blogurl = $blogurl_settings['uploads'];
        }
        elseif( isset( $attributes['content'] ) )
        {
            $return_blogurl = $blogurl_settings['content'];
        }
        elseif( isset( $attributes['layout'] ) )
        {
            $return_blogurl = $blogurl_settings['content'] . '/layout/';
        }
        elseif( isset( $attributes['views'] ) )
        {
            $return_blogurl = $blogurl_settings['content'] . '/views/';
        }
        elseif( isset( $attributes['theme'] ) )
        {
            $return_blogurl = $blogurl_settings['templateurl'];
        }
        elseif( isset( $attributes['child'] ) )
        {
            $return_blogurl = $blogurl_settings['childtemplateurl'];
        }
        elseif( isset( $attributes['login'] ) )
        {
        	$return_blogurl = wp_login_url( $go );
        }
        elseif( isset( $attributes['logout'] ) )
        {
			$return_blogurl = wp_logout_url( $go );
        }
        else
        {
            $return_blogurl = $blogurl_settings['home'];
        }

        if( isset( $attributes['slash'] ) || ( $blogurl_settings['insertslash'] && !isset( $attributes['noslash'] ) ) )
        {
            $return_blogurl .= '/';
        }

        return $return_blogurl;
    }
    
    public static function getSettings()
    {
        $blogurl_settings = urlShortcode::userSettings();
        $upload_dir = wp_upload_dir();
        
        if( !$upload_dir['error'] )
        {
            $blogurl_settings['uploads'] = $upload_dir['baseurl'];
        }
        elseif( '' != get_option( 'upload_url_path' ) )
        {
            // Prior to WordPress 3.5, this was set in Settings > Media > Full URL path to files
            // In WordPress 3.5+ this is now hidden
            $blogurl_settings['uploads'] = get_option( 'upload_url_path' );
        }
        else
        {
            $blogurl_settings['uploads'] = $blogurl_settings['wordpress'] . '/' . get_option( 'upload_path' );
        }

        return $blogurl_settings;
    }
}

add_shortcode( 'url', array( 'urlShortcode', 'custom_url' ) );


/*====================================================================================================
 *
 * Comment shortcodes - [comment form] form/template/count
 *
 *====================================================================================================*/


function ccs_return_comment_form() {
	ob_start();
	comment_form( $args = array(
		'id_form'           => 'commentform',  // that's the wordpress default value! delete it or edit it ;)
		'id_submit'         => 'commentsubmit',
		'title_reply'       => __( '' ),  // Leave a Reply - that's the wordpress default value! delete it or edit it ;)
		'title_reply_to'    => __( '' ),  // Leave a Reply to %s - that's the wordpress default value! delete it or edit it ;)
		'cancel_reply_link' => __( 'Cancel Reply' ),  // that's the wordpress default value! delete it or edit it ;)
		'label_submit'      => __( 'Post Comment' ),  // that's the wordpress default value! delete it or edit it ;)
			
		'comment_field' =>  '<p><textarea placeholder="" id="comment" class="form-control" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>', 
			
		'comment_notes_after' => ''
	));
	$form = ob_get_contents();
    ob_end_clean();
    return $form;
}

function ccs_return_comments_template($file) {
	ob_start();
	comments_template($file);
	$form = ob_get_contents();
    ob_end_clean();
    return $form;
}

function custom_comment_shortcode( $atts, $content, $tag ) {

	global $ccs_global_variable;

	if( is_array( $atts ) ) {
		$atts = array_flip( $atts );
	}

	if( ($tag=='comments') || isset( $atts['template'] ) ) {
		$content = ccs_return_comments_template($atts['template']);
		return $content;
	}

	if( isset( $atts['form'] ) ) {
		$content = ccs_return_comment_form();
		return $content;
	}
	if( isset( $atts['count'] ) ) {
		return get_comments_number();
	}
	if( isset( $atts['total'] ) ) {
		return $ccs_global_variable['total_comments'];
	}
}
add_shortcode('comment', 'custom_comment_shortcode');
add_shortcode('comments', 'custom_comment_shortcode');



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
	restore_current_blog();;

	return $out;
}
add_shortcode('blog', 'custom_blog_shortcode');

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