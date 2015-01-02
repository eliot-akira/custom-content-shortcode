<?php

/*========================================================================
 *
 * For each taxonomy
 * 
 * [for each="category"]
 * [each name,id,slug]
 * 
 */


new CCS_ForEach;

class CCS_ForEach {

	public static $state;

	function __construct() {

		self::$state['is_for_loop'] = false;

		add_action( 'init', array( $this, 'register' ) );
	}

	function register() {
		add_shortcode( 'for', array( $this, 'for_shortcode' ) );
		add_shortcode( 'each', array( $this, 'each_shortcode' ) );
		add_shortcode( 'for-loop', array( $this, 'for_loop_status' ) );
	}

	function for_shortcode( $atts, $content = null, $shortcode_name ) {

		$args = array(
			'each' => '',
			'orderby' => '',
			'order' => '',
			'count' => '',
			'parent' => '',
			'current' => '',
			'trim' => '',
			'empty' => 'true', // Show taxonomy terms with no post
			'exclude' => ''
		);

		extract( shortcode_atts( $args , $atts, true ) );

		self::$state['is_for_loop'] = true;
		if ($each=='tag') $each='post_tag';
		$out = '';

		// Loop through taxonomies

		if ((CCS_Loop::$state['is_loop']=="true") || ($current=="true")) {

			if ($current=="true") {

				$post_id = get_the_ID();

			} else {

				// Inside [loop]
				$post_id = CCS_Loop::$state['current_post_id'];
			}

			$taxonomies = wp_get_post_terms(
				$post_id,
				$each, array(
				'orderby' => $orderby,
				'order' => $order,
				'number' => $count,
				'hide_empty' => ($empty=='true' ? 0 : 1),
				) );

			// Current and parent parameters together

			if (!empty($parent)) {
				
				if ( is_numeric($parent) ) {

					/* Get parent term ID */
					$parent_term_id = $parent;

				} else {

					/* Get parent term ID from slug */
					$term = get_term_by( 'slug', $parent, $each );
					if (!empty($term))
						$parent_term_id = $term->term_id;
					else $parent_term_id = null;
				}

				if (!empty($parent_term_id)) {
					/* Filter out terms that do not have the specified parent */
					foreach($taxonomies as $key => $term) {
						if ($term->parent != $parent_term_id) {
							unset($taxonomies[$key]);
						}
					}

				}
			}

		} else {

			if (empty($parent)) {

				$taxonomies = get_terms( $each, array(
					'orderby' => $orderby,
					'order' => $order,
					'number' => $count,
					'hide_empty' => ($empty=='true' ? 0 : 1),
					) );

			} else {

				/* Get parent term ID from slug */

				if ( is_numeric($parent) ) {

					$parent_term_id = $parent;

				} else {
					$term = get_term_by( 'slug', $parent, $each );
					if (!empty($term))
						$parent_term_id = $term->term_id;
					else $parent_term_id = null;
				}

				if (!empty($parent_term_id)) {
					/* Get direct children */
					$taxonomies = get_terms( $each, array(
						'orderby' => $orderby,
						'order' => $order,
						'number' => $count,
						'parent' => $parent_term_id,
						'hide_empty' => ($empty=='true' ? 0 : 1),
						) );

				} else { /* No parent found */
					$taxonomies = null;
				}
			}
		}


		if (is_array($taxonomies)) {

			self::$state['each']['type']='taxonomy';
			self::$state['each']['taxonomy']=$each;

			$excludes = CCS_Loop::explode_list( $exclude );

			foreach ($taxonomies as $term_object) {

				// Exclude IDs or slugs

				$condition = true;
				foreach ($excludes as $exclude) {
					if ( is_numeric($exclude) ) {
						 // Exclude ID
						if ( $exclude == $term_object->term_id ) {
							$condition = false;
						}
					} else {
						 // Exclude slug
						if ( $exclude == $term_object->slug ) {
							$condition = false;
						}
					}
				}

				if ($condition) {
					self::$state['each']['id'] = $term_object->term_id;
					self::$state['each']['name'] = $term_object->name;
					self::$state['each']['slug'] = $term_object->slug;
					self::$state['each']['description'] = $term_object->description;

					$term_link = get_term_link( $term_object );
					if ( is_wp_error( $term_link ) ) $term_link = null;

					self::$state['each']['url'] = $term_link;

					self::$state['each']['name-link'] = '<a href="'.self::$state['each']['url'].'">'
						. self::$state['each']['name'].'</a>';

					// Replace {TAGS}

					$replaced_content = str_replace('{TERM}',
						self::$state['each']['slug'], $content);
					$replaced_content = str_replace('{TERM_ID}',
						self::$state['each']['id'], $replaced_content);
					$replaced_content = str_replace('{TERM_NAME}',
						self::$state['each']['name'], $replaced_content);

					$out .= do_shortcode($replaced_content);
				}
			}
		}

		// Trim final output

		if (!empty($trim)) {
			if ($trim=='true') $trim = null;
			$out = trim($out, " \t\n\r\0\x0B,".$trim);
		}

		self::$state['is_for_loop'] = false;
		self::$state['each'] = null;

		return $out;
	}

	function each_shortcode( $atts, $content = null, $shortcode_name ) {

		if ( !self::$state['is_for_loop'] )
				return; // Must be inside a for loop

		$field = isset($atts[0]) ? $atts[0] : 'name'; // Default: name
        $out = isset( self::$state['each'][$field] ) ? self::$state['each'][$field] : null;

        return $out;
	}

}

