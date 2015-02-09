<?php

/*========================================================================
 *
 * User shortcodes: users, user, is/isnt
 * 
 * @todo Move these elsewhere
 * Other shortcodes: list_shortcodes, search_form, blog
 *
 */

new CCS_User;

class CCS_User {

	public static $state;
	
	function __construct() {

		add_shortcode('users', array($this, 'users_shortcode'));
		add_shortcode('user', array($this, 'user_shortcode'));

		self::$state['is_users_loop'] = false;

		add_shortcode('is', array($this, 'is_shortcode'));
		add_shortcode('isnt', array($this, 'is_shortcode'));
		add_shortcode('blog', array($this, 'blog_shortcode'));
		add_shortcode('list_shortcodes', array($this, 'list_shortcodes'));
		add_shortcode('search_form', array($this, 'search_form_shortcode'));
	}


	/*========================================================================
	 *
	 * Users loop
	 *
	 */
	
	function users_shortcode( $atts, $content ) {

		self::$state['is_users_loop'] = true;

		$outputs = array();

		/*========================================================================
		 *
		 * Prepare parameters
		 *
		 */

		$args = array();

		// Just pass these
		$pass_args = array('orderby','search','number','offset','meta_key');

		// Order by field value

		$sort_field_num = false;
		if ( isset($atts['orderby']) && isset($atts['field'])
			&& ( $atts['orderby']=='field' || $atts['orderby']=='field_num' ) ) {

			if ( $atts['orderby']=='field' ) {
				$atts['orderby'] = 'meta_value';
				$atts['meta_key'] = $atts['field'];
			} else {
				// Sort by field value number
				$sort_field_num = $atts['field'];
			}

			unset($atts['orderby']);
			unset($atts['field']);
		}


		foreach ($pass_args as $arg) {
			if (isset($atts[$arg]))
				$args[$arg] = $atts[$arg];
		}

		if (isset($atts['role'])) {
			if ($atts['role']=='admin') $atts['role'] = 'Administrator';
			$args['role'] = ucwords($atts['role']); // Capitalize word
		}

		if (isset($atts['order']))
			$args['order'] = strtoupper($atts['order']);
		if (isset($atts['include']))
			$args['include'] = CCS_Loop::explode_list($atts['include']);
		if (isset($atts['exclude']))
			$args['exclude'] = CCS_Loop::explode_list($atts['exclude']);

		if (isset($atts['blog_id']))
			$args['blog_id'] = intval($atts['blog_id']);

		if (isset($atts['search_columns']))
			$args['search_columns'] = CCS_Loop::explode_list($atts['search_columns']);

		if (isset($atts['field']) && isset($atts['value'])) {

			$compare = isset($atts['compare']) ? strtoupper($atts['compare']) : '=';

			switch ($compare) {
				case 'EQUAL': $compare = '='; break;
				case 'NOT':
				case 'NOT EQUAL': $compare = '!='; break;
				case 'MORE': $compare = '>'; break;
				case 'LESS': $compare = '<'; break;
			}

			$multiple = array('IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN');

			if ( in_array($compare,$multiple) ) {
				$value = CCS_Loop::explode_list($atts['value']);
			} else {
				$value = $atts['value'];
			}

			$args['meta_query'][] = array(
				'key' => $atts['field'],
				'value' => $atts['value'],
				'compare' => $compare,
			);

			// Additional query
			if (isset($atts['relation']) && isset($atts['field']) && isset($atts['value'])) {

				$args['meta_query']['relation'] = strtoupper($atts['relation']);

				$args['meta_query'][] = array(
					'key' => $atts['field_2'],
					'value' => $atts['value_2'],
					'compare' => isset($atts['compare_2']) ? strtoupper($atts['compare_2']) : '=',
				);
			}
		}

		$users = get_users( $args );

		
		/*---------------------------------------------
		 *
		 * Filter results
		 *
		 */
		
		// Sort by field value number
		if ( $sort_field_num !== false ) {
			// This is necessary because get_users doesn't do meta_value_num query
			$new_users = array();

			foreach ( $users as $user ) {

				$key = $user->get( $sort_field_num );
				$new_users[] = array(
					'user' => $user,
					'key' => $key
				);
			}

			usort($new_users, array(__CLASS__, 'sortByFieldNum'));

			if (isset($args['order']) && $args['order'] == 'DESC')
				$new_users = array_reverse($new_users);

			$users = array();
			foreach ( $new_users as $user_array ) {
				$users[] = $user_array['user'];
			}
			
		}


		// Users Loop
		foreach ( $users as $user ) {
			self::$state['current_user_object'] = $user;
			$outputs[] = do_shortcode( $content );
		}

		self::$state['is_users_loop'] = false;
		return implode('', $outputs);
	}

	public static function sortByFieldNum($a, $b) {
	    return intval( $a['key'] ) - intval( $b['key'] );
	}


	/*========================================================================
	 *
	 * [user]
	 *
	 */

	public static function user_shortcode( $atts ) {

		if ( self::$state['is_users_loop'] ) {

			$current_user = self::$state['current_user_object'];

		} else {

			global $current_user;
			get_currentuserinfo();
			self::$state['current_user_object'] = $current_user;
		}

		extract(shortcode_atts(array(
			'field' => '',
			'meta' => '', // Alias
			'size' => ''
		), $atts));

		if(empty($current_user)) return; // no current user


		// Get field specified

		if( !empty($meta) ) $field=$meta;

		if ( empty($field) ) {
			// or just get the first parameter
			$field = isset($atts[0]) ? $atts[0] : null;
		}

		switch ( $field ) {
			case '':
			case 'fullname':
				return $current_user->display_name;
				break;
			case 'name':
				return $current_user->user_login;
				break;
			case 'id':
				return $current_user->ID;
				break;
			case 'email':
				return $current_user->user_email;
				break;
			case 'url':
				return $current_user->user_url;
				break;
			case 'avatar':
				return get_avatar( $current_user->ID, !empty($size) ? $size : 96);
				break;
			case 'fullname':
				return $current_user->display_name;
				break;
			case 'post-count':
				return strval( count_user_posts( $current_user->ID ) );
				break;
			case 'role':
				return rtrim(implode(',',array_map('ucwords', $current_user->roles)),',');
				break;
			default:
				return get_user_meta( $current_user->ID, $field, true );
				break;
		}

/*
		if ( is_array( $atts ) ) $atts = array_flip( $atts );
		if ( isset( $atts['name'] ) ) 
		if ( isset( $atts['id'] ) ) return $current_user->ID;
		if ( isset( $atts['email'] ) ) return $current_user->user_email;
		if ( isset( $atts['fullname'] ) ) return $current_user->display_name;
		if ( isset( $atts['avatar'] ) ) return get_avatar( $current_user->ID );
		if ( isset( $atts['role'] ) ) return rtrim(implode(',',$current_user->roles),',');
		// Or else just get the user field by name
		return get_user_meta( $current_user->ID, $field, true );
*/
	}

	public static function get_user_field( $field ) {
		return self::user_shortcode( array( 'field' =>  $field ) );
	}


	/*========================================================================
	 *
	 * [is]
	 *
	 */

	function is_shortcode( $atts, $content, $tag ) {

		global $post, $current_user;

		extract(shortcode_atts(array(
			'user' => '',
			'format' => 'false',
			'shortcode' => 'true',
			'role' => '',
			'capable' => '',
			'compare' => 'OR',
		), $atts));

		$condition = false;


		// Load user info to $current_user

		if ( self::$state['is_users_loop'] && isset(self::$state['current_user_object']))
			$current_user = self::$state['current_user_object'];
		else get_currentuserinfo();


		// Get [else] if it exists

		$content_array = explode('[else]', $content);
		$content = $content_array[0];
		if (count($content_array)>1) {
			$else = $content_array[1];
		} else {
			$else = null;
		}

		if (!empty($user)) {

			$user_array = explode(',', $user);

			foreach ($user_array as $this_user) {
				$this_user = trim($this_user);

				if ( $this_user == ($current_user->user_login) )
					$condition = true;
				elseif ( is_numeric($this_user) &&
					$this_user == ($current_user->ID) ) // User ID
						$condition = true;
			}
		}

		if ( !empty($role) ) {

			$current_roles = $current_user->roles; // an array of roles
			$condition = false;

			// check each role
			$check_roles = explode(',', $role);
			foreach ($check_roles as $check_role) {
				$check_role = trim($check_role);

				if (in_array($check_role, $current_roles)) {
					$condition = true;
				}
				elseif ($compare == 'AND') {
					$condition = false;
				}
			}

		}

		if (!empty($capable)) {

			$capables = explode(',', $capable);

			foreach ($capables as $capability) {

				$check_capable = trim($capability);

				if (current_user_can( $check_capable )) {
					$condition = true;
				}
				elseif ($compare == 'AND') {
					$condition = false;
				}
			}
		}


		if (is_array($atts)) $atts = array_flip( $atts );

		if (( isset( $atts['admin'] ) && current_user_can( 'manage_options' ) ) ||
			( isset( $atts['login'] ) && is_user_logged_in() ) ||
			( isset( $atts['logout'] ) && !is_user_logged_in() ) ) {

			$condition = true;
		}

		if ( isset( $atts['author'] ) ) {
			// If user is the author of current post
			if (!empty($post)) {

				if ($post->post_author == $current_user->ID)
					$condition = true;
			}
		}


		if ( ($tag=='isnt') || (isset($atts['not'])) )
			$condition = !$condition;

		if ( !$condition ) {
			$content = $else; // Everything after [else]
		}

		if ($format == 'true') // Format?
			$content = wpautop( $content );
		if ($shortcode != 'false') // Shortcode?
			$content = do_shortcode( $content );

		return $content;
	}


	/*========================================================================
	 *
	 * [blog]
	 *
	 */

	function blog_shortcode( $atts, $content ){

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


	/*========================================================================
	 *
	 * [list_shortcodes]
	 *
	 */

	function list_shortcodes( ) {
		global $shortcode_tags;
		ksort($shortcode_tags); // Alphabetical sort

		$out = '';

		foreach ( $shortcode_tags as $key => $value ) {

			if(is_array($value)) $value='Class object';

			$out .= $key . ' = ' . $value . '<br>';

		}
		return $out;
	}


	/**
	 *
	 * [search_form]
	 *
	 * @param type Search only this post type
	 *
	 */

	function search_form_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'type' => '',
		), $atts ) );

		$out = get_search_form(false);

		if ( !empty($type) ) {

			$filter = '<input type="hidden" value="'.$type.'" name="post_type" id="post_type" />';
			$end = '</form>';

			// Insert it before the end of form
			$out = str_replace($end, $filter.$end, $out);
		}

		return $out;
	}

}


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