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
        $blogurl_settings['content'] = content_url();
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
            $return_blogurl = $blogurl_settings['content'] . '/layout';
        }
        elseif( isset( $attributes['views'] ) )
        {
            $return_blogurl = $blogurl_settings['content'] . '/views';
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

