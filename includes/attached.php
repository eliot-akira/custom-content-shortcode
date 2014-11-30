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
		self::$state['is_attachment_loop'] = false;
	}

	function attached_shortcode($atts, $content) {

		$args = array(
			'orderby' => '',
			'order' => '',
			'category' => '',
			'count' => '',
			'offset' => '',
			'trim' => '',
			'columns' => '', 'pad' => '', 'between' => ''
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

		if ( isset($atts[0]) && ($atts[0]=='gallery') ){

			// Get attachment IDs from gallery field
			$gallery_field_ids = CCS_Gallery_Field::get_image_ids( $current_id );

			if (count($gallery_field_ids)==0) {

				return null; // No images in gallery field
			}

			unset($attach_args['post_parent']);

			$attach_args['post__in'] = $gallery_field_ids;
			$attach_args['orderby'] = 'post__in'; // Preserve ID order

		} else {
			if (empty($orderby)) $orderby = 'date';
			$attach_args['orderby'] = $orderby;
			if (($orderby=='title')&&(empty($order)))
				$order='ASC'; // default for titles
		}

		if (!empty($order)) $attach_args['order'] = $order;
		if (!empty($category)) $attach_args['category'] = $category;
		if (!empty($count)) $attach_args['posts_per_page'] = $count;
		if (!empty($offset)) $attach_args['offset'] = $offset;

		// Get attachments for current post

		$posts = get_posts($attach_args);

		$index = 0;
		foreach( $posts as $post ) {

			$attachment_id = $post->ID;
			$attachment_ids[$index] = $attachment_id; // Keep it in order
			$index++;
		}

		if ((!empty($posts)) && (!empty($attachment_ids))) { 

			self::$state['is_attachment_loop'] = true;

//			if (strpos($content, 'image') !== false)
//			$image_sizes = get_intermediate_image_sizes();

			foreach ( $attachment_ids as $index => $attachment_id ) {

				self::$state['current_attachment_id'] = $attachment_id;

				$out[] = do_shortcode( $content );

			}

		} // End: not empty post and attachments exist

		if (!empty($columns)) {
			$out = CCS_Loop::render_columns( $out, $columns, $pad, $between );
		} else {
			$out = implode('', $out);

			if ( $trim == 'true' ) {
				$out = trim($out, " \t\n\r\0\x0B,");
			}
		}

		self::$state['is_attachment_loop'] = false;
		return $out;
	}

}
