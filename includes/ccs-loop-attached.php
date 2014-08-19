<?php


/*========================================================================
 *
 * Attached shortcode
 *
 *=======================================================================*/

class AttachedShortcode {

	function __construct() {
		add_shortcode( 'attached', array( $this, 'attached_shortcode' ) );
	}

	function attached_shortcode($atts, $content) {

		global $ccs_global_variable;

		ob_start();

		$attachment_ids = array();
		$out = array();
		$current_id = get_the_ID();

		// Get attachments for current post

		$posts = get_posts( array (
			'post_parent' => $current_id,
			'post_type' => 'attachment',
			'post_status' => 'any'
			) );

		foreach( $posts as $post ) {
			$attachment_id = $post->ID;
			$attachment_ids[] = $attachment_id;
		}

		if ((!empty($posts)) && (!empty($attachment_ids))) { 

			$ccs_global_variable['is_attachment_loop'] = "true";
			$image_sizes = get_intermediate_image_sizes();

			foreach ( $attachment_ids as $attachment_id ) {

				$ccs_global_variable['current_attachment_id'] = $attachment_id;

				$image_link	= wp_get_attachment_image_src( $attachment_id, "full" );
				$image_link	= $image_link[0];	

				$ccs_global_variable['current_image']['full'] = wp_get_attachment_image( $attachment_id, "full" );

				foreach ($image_sizes as $image_size) {
					$ccs_global_variable['current_image'][$image_size] = wp_get_attachment_image( $attachment_id, $image_size );
				}

				$ccs_global_variable['current_image_url'] = $image_link;
				$ccs_global_variable['current_attachment_link'] = get_attachment_link($attachment_id);
				$ccs_global_variable['current_image_thumb'] = wp_get_attachment_image( $attachment_id, 'thumbnail', '', array( 'alt' => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) ) );
				$ccs_global_variable['current_image_thumb_url'] = wp_get_attachment_thumb_url( $attachment_id, 'thumbnail' ) ;
				$ccs_global_variable['current_image_caption'] = get_post( $attachment_id )->post_excerpt ? get_post( $attachment_id )->post_excerpt : '';
				$ccs_global_variable['current_image_title'] = get_post( $attachment_id )->post_title;
				$ccs_global_variable['current_image_description'] = get_post( $attachment_id )->post_content;
				$ccs_global_variable['current_image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

				$ccs_global_variable['current_image_ids'] = implode(" ", $attachment_ids);
				$ccs_global_variable['current_attachment_ids'] = $ccs_global_variable['current_image_ids'];

				$out[] = do_shortcode( $content );

			} /** End for each attachment **/

			$ccs_global_variable['is_attachment_loop'] = "false";

		} // End: not empty post and attachments exist

		echo implode("", $out);

		return ob_get_clean();
	}

}

new AttachedShortcode;
