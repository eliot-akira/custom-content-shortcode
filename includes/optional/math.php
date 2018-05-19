<?php

/*---------------------------------------------
 *
 * Math module
 *
 * Safely evaluate mathematical expressions
 *
 */

if (!class_exists('GoodMath'))
  require_once (CCS_PATH.'/includes/optional/lib/math.php');

new CCS_Math;

class CCS_Math {

  private static $math;

  function __construct() {

    self::$math = new GoodMath;
    add_ccs_shortcode( 'calc', array($this, 'calc_shortcode') );
  }

  function calc_shortcode( $atts, $content ) {

    $m = self::$math;

    // Don't throw parse error unless in debug mode
    $m->suppress_errors = empty($atts['debug']);

    $m->vars( CCS_Pass::$vars ); // Sync with get/set

    $result = $m->evaluate( do_ccs_shortcode($content) );

    CCS_Pass::$vars = $m->vars();

    // Success/error displays nothing
    if ( is_bool($result) ) return;

    return $result;
  }
}