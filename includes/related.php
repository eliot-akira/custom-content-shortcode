<?php 

/*========================================================================
 *
 * Related posts
 *
 *=======================================================================*/

new CCS_Related;

class CCS_Related {

	public static $state;

	function __construct() {

		$this->init();
		add_shortcode('related', array($this, 'loop_related_posts'));
/*
		$array = array(
			array(
				'key_1' => 'Value 1',
				'key_2' => 'Value 2',
				'key_3' => 'Value 3'
			),
			array(
				'key_1' => 'Value 4',
				'key_2' => 'Value 5',
				'key_3' => 'Value 6'
			),
			array(
				'key_1' => 'Value 7',
				'key_2' => 'Value 8',
				'key_3' => 'Value 9'
			),
		);

		update_post_meta( 170, 'array_field', $array ); */
	}

	function init() {

		self::$state['is_related_posts_loop'] = 'false';
		self::$state['current_related_post_id'] = 0;
	}

	function loop_related_posts( $atts, $content ) {

		global $post;
		$outputs = array();
		$current_count = 0;

		if (!empty($post)) {
			$post_id = $post->ID;
			$post_type = $post->post_type;
		} else {
			$post_id = 0;
			$post_type = 'any';
		}

		extract( shortcode_atts( array(
			'taxonomy' => 'category', // Default
			'field' => '', 
			'value' => '', // For future update: related post by field value
			'subfield' => '',
			'count' => '',
			'children' => 'true',
			'order' => 'DESC',
			'orderby' => 'date',
			'relation' => 'or',
			'trim' => '' // Trim extra space and comma
		), $atts ) );


		/*========================================================================
		 *
		 * ACF relationship field
		 *
		 *=======================================================================*/

		if ( ( !empty($field) || !empty($subfield) ) && (empty($value)) ){
			if (class_exists('CCS_To_ACF')) {
				return CCS_To_ACF::loop_relationship_field( $atts, $content );
			}
		}



		/*========================================================================
		 *
		 * Related posts by taxonomy
		 *
		 *=======================================================================*/
		
		if (empty($count)) $count = 99999; // Maximum number of posts

		if ( !empty($taxonomy) ) {

			self::$state['is_related_posts_loop'] = 'true';

			// Support multiple taxonomies

			$taxonomies = CCS_Loop::explode_list($taxonomy);
			$relation = strtoupper($relation);
			$tax_count = 0;

			$query = array(
				'post_type' => $post_type,
				'posts_per_page'   => -1,
				'order' => $order,
				'orderby' => $orderby,
				'include_children' => $children == 'true' ? true : false,
				'tax_query' => array ()
			);

			$terms = array();

			foreach ($taxonomies as $current_taxonomy) {

				if ($current_taxonomy == 'tag')
					$current_taxonomy = 'post_tag';

				// Get current post's taxonomy terms
				$term_objects = get_the_terms( $post_id, $current_taxonomy );

				if (is_array($term_objects)) {

					foreach ($term_objects as $term) {

						$terms[$current_taxonomy][] = $term->term_id;
					}

					if ($tax_count == 1) {
						$query['tax_query']['relation'] = $relation;
					}

					$query['tax_query'][] = array(
						'taxonomy' => $current_taxonomy,
						'field' => 'id',
						'terms' => $terms[$current_taxonomy],
						'operator' => 'IN'
					);

					$tax_count++;
				}
			}

			$posts = new WP_Query( $query );

			if ( $posts->have_posts() ) {

				while ( $posts->have_posts() ) {

					// Set up post data
					$posts->the_post();

					// Skip current post
					if ($post->ID != $post_id) {

						// Manually filter out terms..

						// For some reason, WP_Query is returning more than we need

						$condition = false;

						$tax_count = 0;
						foreach ($taxonomies as $current_taxonomy) {

							if ($current_taxonomy == 'tag')
								$current_taxonomy = 'post_tag';

							if (isset($terms[$current_taxonomy])) {
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
						}

						if ( $condition ) {

							// OK, post fits the criteria

							self::$state['current_related_post_id'] = $post->ID;
							$current_count++;
							if ($current_count<=$count) {
								$outputs[] = do_shortcode( $content );
							}
						}
					}
				}
			}

			wp_reset_postdata();
			self::$state['is_related_posts_loop'] = 'false';
		}

		$out = implode('', $outputs);

		if (!empty($trim)) {
			if ($trim=='true') $trim = null;
			$out = trim($out, " \t\n\r\0\x0B,".$trim);
		}

		return $out;

	}

	function change_key( $array, $old_key, $new_key) {

	    if( ! array_key_exists( $old_key, $array ) )
	        return $array;

	    $keys = array_keys( $array );
	    $keys[ array_search( $old_key, $keys ) ] = $new_key;

	    return array_combine( $keys, $array );
	}

}
