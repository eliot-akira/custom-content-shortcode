<?php




/*====================================================================================================
 *
 * Relative URL shortcodes - [url site/theme/child/content/uploads]
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

        if( is_array( $attributes ) )
        {
            $attributes = array_flip( $attributes );
        }
        

		if($go!='') {
			if($go=='home')
				$go = $blogurl_settings['home'];
			elseif( (isset( $attributes['login'] )) || (isset( $attributes['logout'] )) )
				if( !strpos ($go,"." ) )
					$go = custom_content_shortcode(array('name'=>$go, 'field'=>'url'));
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
}
add_shortcode('comment', 'custom_comment_shortcode');
add_shortcode('comments', 'custom_comment_shortcode');

function custom_is_shortcode( $atts, $content, $tag ) {
	global $current_user;

	extract(shortcode_atts(array(
		'user' => '',
		'format' => '',
		'shortcode' => '',
	), $atts));

	if($format == 'true') { // Format?
		$content = wpautop( $content );
	}
	if($shortcode != 'false') { // Shortcode?
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

function custom_user_shortcode( $atts, $content ) {

	global $current_user;

	get_currentuserinfo();

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


function custom_br_shortcode( $atts, $content ) {
	return '<br>';
}
add_shortcode('br', 'custom_br_shortcode');


function custom_p_shortcode( $atts, $content ) {
	return '<p>' . $content . '</p>';
}
add_shortcode('p', 'custom_p_shortcode');


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

