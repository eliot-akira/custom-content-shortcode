<?php

/*---------------------------------------------
 *
 * Related posts
 *
 */

new CCS_Related;

class CCS_Related {

	public static $state;

	function __construct() {

		$this->init();
		add_ccs_shortcode('related', array($this, 'loop_related_posts'));
	}

	function init() {

		self::$state['is_related_posts_loop'] = false;
		self::$state['current_related_post_id'] = 0;
	}

	static function loop_related_posts( $atts, $content ) {

		global $post;
		$outputs = array();
		$current_count = 0;

		if (CCS_Loop::$state['is_loop']) {
      $post_id = CCS_Loop::$state['current_post_id'];
			$post_type = do_ccs_shortcode( '[field post-type]', false );
		} elseif (!empty($post)) {
			$post_id = $post->ID;
			$post_type = $post->post_type;
		} else {
			$post_id = 0;
			$post_type = 'any';
		}

		extract( shortcode_atts( array(
			'type' => '',
			'taxonomy' => 'category', // Default
			'field' => '',
			'taxonomy_field' => '',
			'value' => '', // For future update: related post by field value
			'subfield' => '',
			'count' => '', 'offset' => '',
      'fill' => '', // Fill to count if result is less
      'id' => '', // Specify posts by ID; used by fill
			'children' => '', // Include child terms
			'order' => 'DESC',
			'orderby' => 'date',
      'status' => 'publish',
			'relation' => 'or',
			'operator' => 'in',
			'trim' => '' // Trim extra space and comma
		), $atts ) );

		if (!empty($type)) {
			$post_type = CCS_Format::explode_list($type);
		}

		if ( empty($field) && isset($atts[0]) ) $field = $atts[0];

		if ( !empty($taxonomy_field) ) {

			$terms = do_ccs_shortcode( '[field '.$taxonomy_field.']', false );
			$terms = CCS_Format::explode_list($terms);

			if (empty($terms) || count($terms)==0) return;

			$taxonomies = array();
			$term_objects = array();
			foreach ($terms as $term) {
				$term = self::get_term_by_id($term);
				$tax = $term->taxonomy;
				if (!in_array($tax, $taxonomies)) {
					$taxonomies[] = $term->taxonomy;
				}
				$term_objects[] = $term;
			}
			$taxonomy = implode(',', $taxonomies);
			$terms = $term_objects;
		}

		/*---------------------------------------------
		 *
		 * ACF relationship field
		 *
		 */

		if ( ( !empty($field) || !empty($subfield) ) && empty($value) ){
			if ( class_exists('CCS_To_ACF') && CCS_To_ACF::is_acf_active() )
				return CCS_To_ACF::loop_relationship_field( $atts, $content );
		}

		/*---------------------------------------------
		 *
		 * Related posts by taxonomy
		 *
		 */

		if (empty($count)) $count = 99999; // Hypothetical maximum number of posts
    if (empty($offset)) $offset = 0;

		if ( !empty($taxonomy) ) {

			self::$state['is_related_posts_loop'] = true;

			// Support multiple taxonomies

			$taxonomies = CCS_Format::explode_list($taxonomy);
      $status = CCS_Format::explode_list($status);
			$relation = strtoupper($relation);
			$tax_count = 0;

			$query = array(
				'post_type' => $post_type,
				'posts_per_page'   => -1,
				'order' => $order,
				'orderby' => $orderby,
        'post_status' => $status,
				'tax_query' => array()
			);

			$target_terms = array();

			foreach ($taxonomies as $current_taxonomy) {

				if ($current_taxonomy == 'tag')
					$current_taxonomy = 'post_tag';

				// Get current post's taxonomy terms - unless given
				if (!isset($terms)) {

					$term_objects = CCS_Content::get_the_terms_silently( $post_id, $current_taxonomy );

				} else {
					$term_objects = $terms;
				}

				if ( ! is_array($term_objects) || empty($term_objects) ) continue;

				foreach ($term_objects as $term) {
					$target_terms[$current_taxonomy][] = $term->term_id;
				}

				if (empty($target_terms[$current_taxonomy])) continue;

				if ($tax_count == 1) {
					$query['tax_query']['relation'] = $relation;
				}

				$query['tax_query'][] = array(
					'taxonomy' => $current_taxonomy,
					'field' => 'id',
					'terms' => $target_terms[$current_taxonomy],
					'operator' => strtoupper($operator),
					'include_children' => ($children == 'true'),
				);

				$tax_count++;
			}
			$terms = $target_terms;

			// print_r($query); echo '<br><br>';

			$posts = new WP_Query( $query );
      $foundIDs = array();

			if ($posts->have_posts()) {
				while ($posts->have_posts()) {

					// Set up post data
					$posts->the_post();

					// Skip current post
					if ($post->ID==$post_id) continue;

					// Manually filter out terms..
					// For some reason, WP_Query is returning more than we need

					$condition = false;

					$tax_count = 0;

					foreach ($taxonomies as $current_taxonomy) {

						if ($current_taxonomy == 'tag')
							$current_taxonomy = 'post_tag';

						// Include child terms
						if ($children == 'true' && isset($terms[$current_taxonomy])) {
							foreach ($terms[$current_taxonomy] as $this_term) {
								$child_terms = get_term_children( $this_term, $current_taxonomy );
								if (!empty($child_terms)) {
									foreach ($child_terms as $child_term) {
										if ( !in_array($child_term, $terms[$current_taxonomy]) )
											$terms[$current_taxonomy][] = $child_term;
									}
								}
							}
						}

						if ( isset($terms[$current_taxonomy]) ) {
							$tax_count++;

							if ($relation == 'AND') {

								if ( has_term( $terms[$current_taxonomy], $current_taxonomy )) {
									if ($condition || $tax_count == 1) {
										$condition = true;
									}
								}

							} else {
								if ( has_term( $terms[$current_taxonomy], $current_taxonomy )) {
									$condition = true;
								}
							}
						}
					} // For each taxonomy

					if (!$condition) continue;

					// OK, post fits the criteria
          $current = $post->ID;
					self::$state['current_related_post_id'] = $current;
          $foundIDs[] = $current;
					$current_count++;
					if (($current_count > $offset) && $current_count <= ($count + $offset)) {
						$outputs[] = do_ccs_shortcode( $content );
					}

				} // While posts
			} // If posts

			wp_reset_postdata();
			self::$state['is_related_posts_loop'] = false;

		} // If taxonomy

		$out = implode('', $outputs);

    // Fill if result is less

    if ($fill=='true' && $current_count < $count) {
      // Exclude current post
      $foundIDs[] = $post_id;
      $foundIDs = implode(',', $foundIDs);

      $num = $count - $current_count;

//      $out .= do_ccs_shortcode('[loop type='.$post_type.' orderby=random exclude='.$foundIDs.' count='.$num.']'.$content.'[/loop]');

      $out .= CCS_Loop::the_loop_shortcode(array(
        'type' => $post_type,
        'orderby' => 'random',
        'exclude' => $foundIDs,
        'count' => $num,
      ), $content, 'loop');

    }

		if (!empty($trim)) {
      $out = CCS_Format::trim($out, $trim);
		}

		return $out;

	}

	public static function get_term_by_id($term, $output = OBJECT, $filter = 'raw') {
	    global $wpdb;
	    $null = null;

	    if ( empty($term) ) return;

	    $_tax = $wpdb->get_row( $wpdb->prepare( "SELECT t.* FROM $wpdb->term_taxonomy AS t WHERE t.term_id = %s LIMIT 1", $term) );
	    $taxonomy = $_tax->taxonomy;

	    return get_term($term, $taxonomy, $output, $filter);

	}


	function change_key( $array, $old_key, $new_key) {

	    if( ! array_key_exists( $old_key, $array ) )
	        return $array;

	    $keys = array_keys( $array );
	    $keys[ array_search( $old_key, $keys ) ] = $new_key;

	    return array_combine( $keys, $array );
	}

}
