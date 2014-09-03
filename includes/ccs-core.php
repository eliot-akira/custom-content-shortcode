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
		global $ccs_content_template_loader;

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

			'is_relationship_loop' => 'false',

			'is_wck_repeater' => 'false',
			'is_wck_repeater_id' => 0,
			'is_wck_repeater_meta' => '',
			'is_wck_repeater_key' => '',
		);

		$ccs_content_template_loader = false; // Set true to enable template loader (under development)

	}

}
new CCSGlobal;

if (!function_exists('do_short')) {
	function do_short($content) {
		echo do_shortcode($content);
	}
}
if (!function_exists('start_short')) {
	function start_short() {
		ob_start();
	}
}
if (!function_exists('end_short')) {
	function end_short() {
		$out = ob_get_clean();
		echo do_shortcode($out);
	}
}

