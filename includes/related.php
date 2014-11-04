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

			// Get current post's taxonomy terms
			$term_objects = get_the_terms( $post_id, $taxonomy );

			$terms = array();

			foreach ($term_objects as $term) {

				$terms[] = $term->term_id;
			}

			// Get posts with the same taxonomy term
			$posts = new WP_Query( array(
				'post_type' => $post_type,
				'posts_per_page'   => -1,
				'order' => $order,
				'orderby' => $orderby,
				'include_children' => $children == 'true' ? true : false,
				'tax_query' => array (
					array(
						'taxonomy' => $taxonomy,
						'field' => 'id',
						'terms' => $terms,
						'operator' => 'IN'
					)
				)
			));


			if ( $posts->have_posts() ) {

				while ( $posts->have_posts() ) {

					// Set up post data
					$posts->the_post();

					// Skip current post
					if ($post->ID != $post_id) {

						// Filter out terms manually
						// For some reason, WP_Query is returning more than we need

						if ( has_term( $terms, $taxonomy ) ) {
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

}
