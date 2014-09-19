<?php
/*
Plugin Name: Custom Content Shortcode
Plugin URI: http://wordpress.org/plugins/custom-content-shortcode/
Description: Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas
Version: 1.2.2
Shortcodes: loop, content, field, taxonomy, if, for, each, comments, user, url, load...
Author: Eliot Akira
Author URI: eliotakira.com
License: GPL2
*/

define('CCS_PATH', dirname(__FILE__));
define('CCS_URL', untrailingslashit(plugins_url('/',__FILE__)));

require_once (CCS_PATH.'/includes/ccs-core.php');			// Core functions
require_once (CCS_PATH.'/includes/ccs-content.php');		// Content shortcode
require_once (CCS_PATH.'/includes/ccs-loop.php');			// Loop shortcode
require_once (CCS_PATH.'/includes/ccs-loop-attached.php');	// Attachment loop

require_once (CCS_PATH.'/includes/ccs-comments.php');		// Comments shortcode
require_once (CCS_PATH.'/includes/ccs-user.php');			// User shortcodes
require_once (CCS_PATH.'/includes/ccs-url.php');			// URL shortcode

require_once (CCS_PATH.'/includes/ccs-foreach.php');		// For/Each shortcode
require_once (CCS_PATH.'/includes/ccs-if.php');				// If shortcode

require_once (CCS_PATH.'/includes/ccs-docs.php');			// Documentation under Settings -> Custom Content

require_once (CCS_PATH.'/includes/ccs-wck.php'); 			// WCK support (if plugin exists)


// Future update

// Core needs patch to prevent PHP notice for get_widget()
// require_once (CCS_PATH.'/includes/ccs-widget.php');			// Widget shortcode


/*========================================================================
 *
 * Optional modules
 *
 *=======================================================================*/

$ccs_settings = get_option('ccs_content_settings');

if ($ccs_settings === false ) {
	// Default settings

	$ccs_settings['load_acf_module'] = 'on';
	$ccs_settings['load_bootstrap_module'] = 'on';
	$ccs_settings['load_file_loader'] = 'on';
	$ccs_settings['load_gallery_field'] = 'on';
	$ccs_settings['load_mobile_detect'] = 'on';
	$ccs_settings['shortcodes_in_widget'] = 'on';
	$ccs_settings['move_wpautop'] = 'off';
	$ccs_settings['shortcode_unautop'] = 'off';
	update_option( 'ccs_content_settings', $ccs_settings );
}

$load_acf_module = isset( $ccs_settings['load_acf_module'] ) ?
	$ccs_settings['load_acf_module'] : 'off';
$load_bootstrap_module = isset( $ccs_settings['load_bootstrap_module'] ) ?
	$ccs_settings['load_bootstrap_module'] : 'off';
$load_file_loader = isset( $ccs_settings['load_file_loader'] ) ?
	$ccs_settings['load_file_loader'] : 'off';
$load_gallery_field = isset( $ccs_settings['load_gallery_field'] ) ?
	$ccs_settings['load_gallery_field'] : 'off';
$load_mobile_detect = isset( $ccs_settings['load_mobile_detect'] ) ?
	$ccs_settings['load_mobile_detect'] : 'off';
$load_shortcode_unautop = isset( $ccs_settings['shortcode_unautop'] ) ?
	$ccs_settings['shortcode_unautop'] : 'off';

if ($load_gallery_field == "on")
	require_once (CCS_PATH.'/includes/ccs-gallery.php');		// Simple gallery
if ($load_acf_module == "on")
	require_once (CCS_PATH.'/includes/ccs-acf.php');			// Advanced Custom Fields support
if ($load_file_loader == "on")
	require_once (CCS_PATH.'/includes/ccs-load.php');			// Load HTML, CSS, JS fields
if ($load_mobile_detect == "on")
	require_once (CCS_PATH.'/includes/ccs-mobile.php'); 		// Mobile detect shortcodes
if ($load_bootstrap_module == "on")
	require_once (CCS_PATH.'/includes/ccs-bootstrap.php');		// Bootstrap support

if ($load_shortcode_unautop == "on")
	require_once (CCS_PATH.'/includes/ccs-unautop.php');		// Shortcode unautop
