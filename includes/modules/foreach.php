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
	private static $index; // Support nested loop
	private static $current_term;

	function __construct() {

		self::$index = 0;
		self::$state['is_for_loop'] = false;

		add_action( 'init', array( $this, 'register' ) );
	}

	function register() {

		add_shortcode( 'for', array( $this, 'for_shortcode' ) );
		add_shortcode( 'each', array( $this, 'each_shortcode' ) );

		// Nested shortcodes
		add_shortcode( '-for', array( $this, 'for_shortcode' ) );
		add_shortcode( '--for', array( $this, 'for_shortcode' ) );
	}

	function for_shortcode( $atts, $content = null, $shortcode_name ) {

		$args = array(
			'each' => '',
			'orderby' => 'name',
			'order' => '',
			'count' => '',
			'parent' => '',
			'parents' => '', // Don't return term children
			'current' => '',
			'trim' => '',
			'empty' => 'true', // Show taxonomy terms with no post
			'exclude' => ''
		);

		extract( shortcode_atts( $args , $atts, true ) );

		// Top parent loop
		if ( ! self::$state['is_for_loop'] ) {

			self::$state['is_for_loop'] = true;

		// Nested loop
		} else {

			$parent_term = self::$current_term[ self::$index ];

			// Same taxonomy as parent
			if ( $each=='child' && isset( $parent_term['taxonomy'] ) )
				$each = $parent_term['taxonomy'];

			// Get parent term unless specified
			if ( empty( $parent ) && isset( $parent_term['id'] ) )
				$parent = $parent_term['id'];
			// Nest index
			self::$index++;
		}

		if ($each=='tag') $each='post_tag';
		$out = '';

		// Get [else] block
		$if_else = CCS_If::get_if_else( $content, $shortcode_name );
		$content = $if_else['if'];
		$else = $if_else['else'];


		// Get terms according to parameters
		// @todo Refactor - keep it DRY
		// @todo Consolidate to CCS_Content::get_taxonomies

		$query = array(
			'orderby' => $orderby,
			'order' => $order,
			'number' => $count,
			'parent' => ( $parents=='true' ) ? 0 : '', // Exclude children or not
			'hide_empty' => ( $empty=='true' ) ? 0 : 1,
		);

		if ( CCS_Loop::$state['is_loop'] || ($current=="true")) {

			if ($current=="true") $post_id = get_the_ID();
			else $post_id = CCS_Loop::$state['current_post_id']; // Inside [loop]

			$taxonomies = wp_get_post_terms( $post_id, $each, $query );

			// Current and parent parameters together

			if ( !empty($parent) ) {
				
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

				if ( !empty($parent_term_id) ) {
					/* Filter out terms that do not have the specified parent */
					foreach($taxonomies as $key => $term) {
						if ($term->parent != $parent_term_id) {
							unset($taxonomies[$key]);
						}
					}

				}
			}

		} else {

			if ( empty($parent) ) {

				$taxonomies = get_terms( $each, $query );

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

					$query['parent'] = $parent_term_id;
					$taxonomies = get_terms( $each, $query );

				} else $taxonomies = null; // No parent found

			}
		}

		// Array and not empty
		if ( is_array($taxonomies) && ( $taxonomies != array() ) ) {

			$each_term = array();
			$each_term['taxonomy'] = $each; // Taxonomy name

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

				if ( $condition ) {

					$each_term['id'] = $term_object->term_id;
					$each_term['name'] = $term_object->name;
					$each_term['slug'] = $term_object->slug;
					$each_term['description'] = $term_object->description;

					$term_link = get_term_link( $term_object );
					if ( is_wp_error( $term_link ) ) $term_link = null;

					$each_term['url'] = $term_link;
					$each_term['link'] = '<a href="'.$each_term['url'].'">'
						. $each_term['name'] . '</a>';
					// Alias for backward compatibility
					$each_term['name-link'] = $each_term['link'];

					// Replace {TAGS}

					// @todo Use a general-purpose function in CCS_Loop for replacing tags

					$replaced_content = str_replace('{TERM}',
						$each_term['slug'], $content);
					$replaced_content = str_replace('{TERM_ID}',
						$each_term['id'], $replaced_content);
					$replaced_content = str_replace('{TERM_NAME}',
						$each_term['name'], $replaced_content);

					// Make term data available to [each]
					self::$current_term[ self::$index ] = $each_term;

					$out .= do_shortcode($replaced_content);
				}
			}
		} else {
			$out .= do_shortcode($else);
		}

		// Trim final output

		if (!empty($trim)) {
			if ($trim=='true') $trim = null;
			$out = trim($out, " \t\n\r\0\x0B,".$trim);
		}

		// Return to parent loop
		if ( self::$index > 0 ) self::$index--;
		// Or finished
		else self::$state['is_for_loop'] = false;

		return $out;
	}


	function each_shortcode( $atts, $content = null, $shortcode_name ) {

		if ( !self::$state['is_for_loop'] )
				return; // Must be inside a for loop

		$field = isset($atts[0]) ? $atts[0] : 'name'; // Default: name

		// Get term data for current nest level
		$term = self::$current_term[ self::$index ];
        $out = isset( $term[$field] ) ? $term[$field] : null;

        return $out;
	}

}

