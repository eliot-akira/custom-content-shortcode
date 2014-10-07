<?php

/*========================================================================
 *
 * User shortcodes: user, is/isnt, list_shortcodes, search_form, blog
 *
 *=======================================================================*/

class CCS_Users {

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
	 *=======================================================================*/
	
	function users_shortcode( $atts, $content ) {

		self::$state['is_users_loop'] = true;

		$outputs = array();

		/*========================================================================
		 *
		 * Prepare parameters
		 *
		 *=======================================================================*/

		$args = array();

		// Just pass these
		$pass_args = array('orderby','search','number','offset');

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
				case 'EQUAL': $compare = "="; break;
				case 'NOT':
				case 'NOT EQUAL': $compare = "!="; break;
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


		/*========================================================================
		 *
		 * Custom query to filter results
		 *
		 *=======================================================================*/
		
		// Users Loop
		foreach ( $users as $user ) {
			self::$state['current_user_object'] = $user;
			$outputs[] = do_shortcode( $content );
		}

		self::$state['is_users_loop'] = false;
		return implode('', $outputs);
	}



	/*========================================================================
	 *
	 * [user]
	 *
	 *=======================================================================*/

	function user_shortcode( $atts, $content ) {

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
				return count_user_posts( $current_user->ID );
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


	/*========================================================================
	 *
	 * [is]
	 *
	 *=======================================================================*/

	function is_shortcode( $atts, $content, $tag ) {

		global $current_user;

		extract(shortcode_atts(array(
			'user' => '',
			'format' => 'false',
			'shortcode' => 'true',
			'role' => '',
			'capable' => '',
			'compare' => 'OR',
		), $atts));

		$condition = false;
		get_currentuserinfo(); // load user info to $current_user

		if (!empty($user)) {

			$user_array = explode(",", $user);

			foreach ($user_array as $this_user) {
				$this_user = trim($this_user);

				if ( $this_user == ($current_user->user_login) )
					$condition = true;
				if ( ( $this_user == ($current_user->ID) ) &&
					is_numeric($this_user) ) // $user is a number?
						$condition = true;
			}
		}

		if (!empty($role)) {

			$current_roles = $current_user->roles; // an array of roles
			$condition = false;

			// check each role
			$check_roles = explode(",", $role);
			foreach ($check_roles as $check_role) {
				$check_role = trim($check_role);

				if (in_array($check_role, $current_roles)) {
					$condition = true;
				}
				elseif ($compare == "AND") {
					$condition = false;
				}
			}

		}

		if (!empty($capable)) {

			$capables = explode(",", $capable);

			foreach ($capables as $capability) {

				$check_capable = trim($capability);

				if (current_user_can( $check_capable )) {
					$condition = true;
				}
				elseif ($compare == "AND") {
					$condition = false;
				}
			}
		}


		if (is_array($atts)) $atts = array_flip( $atts );

		if (( isset( $atts['admin'] ) && current_user_can( 'manage_options' ) ) ||
			( isset( $atts['login'] ) && is_user_logged_in() ) ||
			( isset( $atts['logout'] ) && !is_user_logged_in() )) {

			$condition = true;
		}

		if ( ($tag=="isnt") || (isset($atts['not'])) )
			$condition = !$condition;

		if ($format == 'true') // Format?
			$content = wpautop( $content );

		if ($shortcode != 'false') // Shortcode?
			$content = do_shortcode( $content );

		if ($condition)
			return $content;
		else
			return null;
	}


	/*========================================================================
	 *
	 * [blog]
	 *
	 *=======================================================================*/

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
	 *=======================================================================*/

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


	/*========================================================================
	 *
	 * [search_form]
	 *
	 *=======================================================================*/

	function search_form_shortcode() {

		ob_start();
		get_search_form(true);
		$out = ob_get_contents();
		ob_end_clean();
		
		return $out;
	}

}
new CCS_Users;




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