<?php
/*
Plugin Name: Custom Content Shortcode
Plugin URI: http://wordpress.org/plugins/custom-content-shortcode/
Description: Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas
Version: 0.8.0
Author: Eliot Akira
Author URI: eliotakira.com
License: GPL2

To do: display native read more tag

*/

define('CCS_PATH', dirname(__FILE__));
define('CCS_URL', untrailingslashit(plugins_url('/',__FILE__)));

$ccs_content_template_loader = false; // Set true to enable template loader

global $sort_posts; global $sort_key;

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
);


/**
 * Set up mobile detect library
 *
 */

if (!class_exists('Mobile_Detect')) {
	require_once (CCS_PATH.'/includes/Mobile_Detect.php');	
}

$detect = new Mobile_Detect();
$device_type = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

require_once (CCS_PATH.'/includes/ccs-mobile.php'); 		// Mobile detect shortcodes


require_once (CCS_PATH.'/includes/ccs-content.php');		// Content shortcode
require_once (CCS_PATH.'/includes/ccs-loop.php');			// Loop shortcode
require_once (CCS_PATH.'/includes/ccs-foreach.php');		// For/Each shortcode
require_once (CCS_PATH.'/includes/ccs-gallery.php');		// Simple gallery
require_once (CCS_PATH.'/includes/ccs-bootstrap.php');		// Bootstrap support
require_once (CCS_PATH.'/includes/ccs-load.php');			// Load HTML, CSS, JS fields
require_once (CCS_PATH.'/includes/ccs-acf.php');			// Advanced Custom Fields support
require_once (CCS_PATH.'/includes/ccs-user.php');			// Miscellaneous user shortcodes
require_once (CCS_PATH.'/includes/ccs-docs.php');			// Documentation under Settings -> Content Shortcodes


