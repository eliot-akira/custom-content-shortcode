<?php


/*========================================================================
 *
 * Attached shortcode
 *
 *=======================================================================*/

new CCS_Attached;

class CCS_Attached {

	public static $state;

	function __construct() {
		add_shortcode( 'attached', array( $this, 'attached_shortcode' ) );
	}

	function attached_shortcode($atts, $content) {

		$args = array(
			'orderby' => '',
			'order' => '',
			'category' => '',
			'count' => '',
			'offset' => '',
		);
		extract( shortcode_atts( $args , $atts, true ) );		

		$attachment_ids = array();
		$out = array();
		$current_id = get_the_ID();

		$attach_args = array (
			'post_parent' => $current_id,
			'post_type' => 'attachment',
			'post_status' => 'any',
			'posts_per_page' => '-1' // Get all attachments
			);

		if (empty($orderby)) $orderby = 'date';
		$attach_args['orderby'] = $orderby;
		if (($orderby=='title')&&(empty($order)))
			$order='ASC'; // default for titles

		if (!empty($order)) $attach_args['order'] = $order;
		if (!empty($category)) $attach_args['category'] = $category;
		if (!empty($count)) $attach_args['posts_per_page'] = $count;
		if (!empty($offset)) $attach_args['offset'] = $offset;



		// Get attachments for current post

		$posts = get_posts($attach_args);

		foreach( $posts as $post ) {

			$attachment_id = $post->ID;
			$attachment_ids[] = $attachment_id;

		}

		if ((!empty($posts)) && (!empty($attachment_ids))) { 

			self::$state['is_attachment_loop'] = 'true';

//			if (strpos($content, 'image') !== false)
			$image_sizes = get_intermediate_image_sizes();

			foreach ( $attachment_ids as $attachment_id ) {

				self::$state['current_attachment_id'] = $attachment_id;

// Optimize this

// Only get each field if necessary
/*
				$attached = get_post( $attachment_id );

				foreach ($image_sizes as $image_size) {
					$ccs_global_variable['current_image'][$image_size] = wp_get_attachment_image( $attachment_id, $image_size );
				}
				$ccs_global_variable['current_image']['full'] = wp_get_attachment_image( $attachment_id, 'full' );

				$image_link	= wp_get_attachment_image_src( $attachment_id, 'full' );
				$image_link	= $image_link[0];	

				$ccs_global_variable['current_image_url'] = $image_link;
				$ccs_global_variable['current_attachment_file_url'] = wp_get_attachment_url($attachment_id);
				$ccs_global_variable['current_attachment_page_url'] = get_attachment_link($attachment_id);
				$ccs_global_variable['current_image_thumb'] = wp_get_attachment_image( $attachment_id, 'thumbnail', '', array( 'alt' => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) ) );
				$ccs_global_variable['current_image_thumb_url'] = wp_get_attachment_thumb_url( $attachment_id, 'thumbnail' ) ;
				$ccs_global_variable['current_image_caption'] = $attached->post_excerpt;
				$ccs_global_variable['current_image_title'] = $attached->post_title;
				$ccs_global_variable['current_image_description'] = $attached->post_content;
				$ccs_global_variable['current_image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

				$ccs_global_variable['current_image_ids'] = implode(' ', $attachment_ids);
				$ccs_global_variable['current_attachment_ids'] = $ccs_global_variable['current_image_ids'];
*/
				$out[] = do_shortcode( $content );

			} /** End for each attachment **/

			self::$state['is_attachment_loop'] = 'false';

		} // End: not empty post and attachments exist

		return implode('', $out);
	}

}
