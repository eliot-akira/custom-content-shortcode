<?php

/*====================================================================================================
 *
 * Mobile detection shortcodes
 *
 *====================================================================================================*/


/*
 * Add device class to body
 *
 */

if ( !is_admin() ) {	
	add_filter('body_class','ccs_mobile_body_class');

	/**
	 * Set up mobile detect library
	 *
	 */

	if (!class_exists('Mobile_Detect')) {
		require_once (CCS_PATH.'/includes/Mobile_Detect.php');	
	}

	$detect = new Mobile_Detect();
	$device_type = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
}

/* .is_phone, .is_tablet, .is_mobile, .isnt_phone, .is_computer */

function ccs_mobile_body_class($classes) {

	global $device_type;

	if ( ( $device_type=='phone' ) || ( $device_type=='tablet') )
		$classes[] = 'is_mobile';
	if ( $device_type!='phone' )
		$classes[] = 'isnt_phone';
	$classes[] = 'is_' . $device_type;

	return $classes;
}

/* is_phone, is_tablet, is_mobile, isnt_phone, is_computer */

function ccs_is_mobile( $atts, $content ) {

	global $device_type;

	if ( ( $device_type=='phone' ) || ( $device_type=='tablet') ) return do_shortcode($content);

}
add_shortcode( 'is_mobile', 'ccs_is_mobile' );

function ccs_is_phone( $atts, $content ) {

	global $device_type;

	if ( $device_type=='phone' ) return do_shortcode($content);

}
add_shortcode( 'is_phone', 'ccs_is_phone' );

function ccs_isnt_phone( $atts, $content ) {

	global $device_type;

	if ( $device_type!='phone' ) return do_shortcode($content);

}
add_shortcode( 'isnt_phone', 'ccs_isnt_phone' );

function ccs_is_tablet( $atts, $content ) {

	global $device_type;

	if ( $device_type=='tablet' ) return do_shortcode($content);

}
add_shortcode( 'is_tablet', 'ccs_is_tablet' );

function ccs_is_computer( $atts, $content ) {

	global $device_type;

	if ( $device_type=='computer' ) return do_shortcode($content);

}
add_shortcode( 'is_computer', 'ccs_is_computer' );

/* Redirect to another page - use inside mobile condition */

function ccs_redirect( $atts, $content ) {
	echo "<script> window.location = '" . strip_tags( do_shortcode($content) ) . "'; </script>";
}
add_shortcode( 'redirect', 'ccs_redirect' );

