<?php

/*========================================================================
 *
 * Organize core functions
 *
 *=======================================================================*/


class CCSGlobal {

	function __construct() {

		/*========================================================================
		 *
		 * Global state
		 * 
		 *=======================================================================*/

		global $ccs_global_variable;

		$ccs_global_variable = array(
			'is_loop' => 'false',
			'for_loop' => 'false',
			'is_gallery_loop' => 'false',
			'is_attachment_loop' => 'false',
			'is_repeater_loop' => 'false',
			'is_acf_gallery_loop' => 'false',
			'current_loop_id' => '',
			'current_row' => '',
			'current_image' => '',
			'current_image_url' => '',
			'current_image_thumb' => '',
			'current_image_thumb_url' => '',
			'current_image_caption' => '',
			'current_image_title' => '',
			'current_image_description' => '',
			'current_image_alt' => '',
			'current_image_ids' => '',
			'current_gallery_name' => '',
			'current_gallery_id' => '',
			'current_attachment_id' => '',
			'current_attachment_ids' => '',
			'current_script' => '',
			'current_post' => '',
		);

		$ccs_content_template_loader = false; // Set true to enable template loader (under development)

	}
}
new CCSGlobal;


/*========================================================================
 *
 * Helper functions
 *
 *=======================================================================*/

function comma_list_to_array( $string ) {

	// Explode comma-separated list and trim white space

	return array_map("trim", explode(",", $string));
}


