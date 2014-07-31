<?php
/*
Plugin Name: Custom Content Shortcode
Plugin URI: http://wordpress.org/plugins/custom-content-shortcode/
Description: Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas
Version: 1.0.2
Author: Eliot Akira
Author URI: eliotakira.com
License: GPL2

*/

define('CCS_PATH', dirname(__FILE__));
define('CCS_URL', untrailingslashit(plugins_url('/',__FILE__)));

require_once (CCS_PATH.'/includes/ccs-core.php');			// Core functions
require_once (CCS_PATH.'/includes/ccs-content.php');		// Content shortcode
require_once (CCS_PATH.'/includes/ccs-loop.php');			// Loop shortcode

require_once (CCS_PATH.'/includes/ccs-foreach.php');		// For/Each shortcode
require_once (CCS_PATH.'/includes/ccs-if.php');				// If shortcode

require_once (CCS_PATH.'/includes/ccs-gallery.php');		// Simple gallery
require_once (CCS_PATH.'/includes/ccs-bootstrap.php');		// Bootstrap support
require_once (CCS_PATH.'/includes/ccs-acf.php');			// Advanced Custom Fields support

require_once (CCS_PATH.'/includes/ccs-user.php');			// Miscellaneous user shortcodes
require_once (CCS_PATH.'/includes/ccs-load.php');			// Load HTML, CSS, JS fields
require_once (CCS_PATH.'/includes/ccs-mobile.php'); 		// Mobile detect shortcodes
require_once (CCS_PATH.'/includes/ccs-docs.php');			// Documentation under Settings -> Custom Content

