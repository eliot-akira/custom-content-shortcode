<?php
/*
Plugin Name: Custom Content Shortcode
Plugin URI: http://wordpress.org/plugins/custom-content-shortcode/
Description: Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas
Version: 1.0.8
Author: Eliot Akira
Author URI: eliotakira.com
License: GPL2

*/

define('CCS_PATH', dirname(__FILE__));
define('CCS_URL', untrailingslashit(plugins_url('/',__FILE__)));

require_once (CCS_PATH.'/includes/ccs-core.php');			// Core functions
require_once (CCS_PATH.'/includes/ccs-content.php');		// Content shortcode
require_once (CCS_PATH.'/includes/ccs-loop.php');			// Loop shortcode

require_once (CCS_PATH.'/includes/ccs-comments.php');		// Comments shortcode
require_once (CCS_PATH.'/includes/ccs-user.php');			// User shortcodes
require_once (CCS_PATH.'/includes/ccs-url.php');			// URL shortcode

require_once (CCS_PATH.'/includes/ccs-foreach.php');		// For/Each shortcode
require_once (CCS_PATH.'/includes/ccs-if.php');				// If shortcode

require_once (CCS_PATH.'/includes/ccs-docs.php');			// Documentation under Settings -> Custom Content

// Future update

// Core needs patch to prevent PHP notice for get_widget()
// require_once (CCS_PATH.'/includes/ccs-widget.php');			// Widget shortcode


/*========================================================================
 *
 * Optional modules
 *
 *=======================================================================*/

	$settings = get_option('ccs_content_settings');

	if ($settings === false ) {
		// Default settings

		$settings['move_wpautop'] = 'off';
		$settings['load_acf_module'] = 'on';
		$settings['load_bootstrap_module'] = 'on';
		$settings['load_file_loader'] = 'on';
		$settings['load_gallery_field'] = 'on';
		$settings['load_mobile_detect'] = 'on';
		update_option( 'ccs_content_settings', $settings );
	}

	$load_acf_module = isset( $settings['load_acf_module'] ) ?
		esc_attr( $settings['load_acf_module'] ) : 'off';
	$load_bootstrap_module = isset( $settings['load_bootstrap_module'] ) ?
		esc_attr( $settings['load_bootstrap_module'] ) : 'off';
	$load_file_loader = isset( $settings['load_file_loader'] ) ?
		esc_attr( $settings['load_file_loader'] ) : 'off';
	$load_gallery_field = isset( $settings['load_gallery_field'] ) ?
		esc_attr( $settings['load_gallery_field'] ) : 'off';
	$load_mobile_detect = isset( $settings['load_mobile_detect'] ) ?
		esc_attr( $settings['load_mobile_detect'] ) : 'off';


if ($load_gallery_field == "on")
	require_once (CCS_PATH.'/includes/ccs-gallery.php');		// Simple gallery
if ($load_bootstrap_module == "on")
	require_once (CCS_PATH.'/includes/ccs-bootstrap.php');		// Bootstrap support
if ($load_acf_module == "on")
	require_once (CCS_PATH.'/includes/ccs-acf.php');			// Advanced Custom Fields support
if ($load_file_loader == "on")
	require_once (CCS_PATH.'/includes/ccs-load.php');			// Load HTML, CSS, JS fields
if ($load_mobile_detect == "on")
	require_once (CCS_PATH.'/includes/ccs-mobile.php'); 		// Mobile detect shortcodes

