<?php
/*---------------------------------------------
 *
 * Mobile detect
 *
 */

// Only on frontend
if ( !is_admin() ) :

if (!class_exists('Mobile_Detect')) {
  require_once (CCS_PATH.'/includes/optional/lib/mobile-detect.php');
}

// Extend Mobile Detect to get specific device & browser
class CCS_Mobile_Detect_Extended extends Mobile_Detect {

  function getDevice() {

    $this->setDetectionType(self::DETECTION_TYPE_MOBILE);

    foreach (self::$phoneDevices as $device => $regex) {
      if ($this->match($regex, $userAgent = null)) {
        return $device;
      }
    }
    foreach (self::$tabletDevices as $device => $regex) {
      if ($this->match($regex, $userAgent = null)) {
        return $device;
      }
    }

    // No match for device
    return $this->isMobile() ? 'Phone' : 'Computer';
  }

  function getBrowser() {

    // Mobile browsers
    if ($this->isMobile()) {
      foreach (self::$browsers as $browser => $regex) {
        if ($this->match($regex, $userAgent = null)) {
          return $browser;
        }
      }
    }
    // Rough detect
    if ( !isset($_SERVER["HTTP_USER_AGENT"]) ) return 'Other';

    $agent = strtolower($_SERVER["HTTP_USER_AGENT"]);

    if (strpos($agent, 'msie')) return 'IE';
    elseif (strpos($agent, 'presto')) return 'Opera';
    elseif (strpos($agent, 'chrome')) return 'Chrome';
    elseif (strpos($agent, 'safari')) return 'Safari';
    elseif (strpos($agent, 'firefox')) return 'Firefox';
    else return 'Other';
  }
}

/*---------------------------------------------
 *
 * Shortcodes and body class
 *
 */

class CCS_Mobile_Detect {

  public static $detect;
	public static $device_type;
  public static $device;
  public static $browser;
  public static $is_mobile;

	function __construct() {

		add_filter( 'body_class', array($this, 'mobile_body_class') );

		self::$detect = new CCS_Mobile_Detect_Extended();
    self::$is_mobile = self::$detect->isMobile();
		self::$device_type = self::$is_mobile ?
      (self::$detect->isTablet() ? 'tablet' : 'phone') : 'computer';

    self::$device = self::$detect->getDevice();
    self::$browser = self::$detect->getBrowser();

    // @todo Use [is] shortcode instead and deprecate these

		add_ccs_shortcode( 'is_mobile', array($this, 'is_mobile') );
		add_ccs_shortcode( 'is_phone', array($this, 'is_phone') );
		add_ccs_shortcode( 'isnt_phone', array($this, 'isnt_phone') );
		add_ccs_shortcode( 'is_tablet', array($this, 'is_tablet') );
    add_ccs_shortcode( 'is_computer', array($this, 'is_computer') );
    add_ccs_shortcode( 'isnt_computer', array($this, 'isnt_computer') );
	}


	/* .is_phone, .is_tablet, .is_mobile, .isnt_phone, .is_computer, .isnt_computer */

	function mobile_body_class($classes) {

		if ( ( self::$device_type=='phone' ) || ( self::$device_type=='tablet') )
			$classes[] = 'is_mobile';
    if ( self::$device_type!='phone' )
      $classes[] = 'isnt_phone';
    if ( self::$device_type!='computer' )
      $classes[] = 'isnt_computer';
		$classes[] = 'is_' . self::$device_type;

		return $classes;
	}

	/* is_phone, is_tablet, is_mobile, isnt_phone, is_computer, isnt_computer */

	function is_mobile( $atts, $content ) {

		if ( ( self::$device_type=='phone' ) || ( self::$device_type=='tablet') )
      return do_shortcode($content);
	}

	function is_phone( $atts, $content ) {

		if ( self::$device_type=='phone' ) return do_shortcode($content);
	}

	function isnt_phone( $atts, $content ) {

		if ( self::$device_type!='phone' ) return do_shortcode($content);
	}

	function is_tablet( $atts, $content ) {

		if ( self::$device_type=='tablet' ) return do_shortcode($content);
	}

  function is_computer( $atts, $content ) {

    if ( self::$device_type=='computer' ) return do_shortcode($content);
  }

  function isnt_computer( $atts, $content ) {

    if ( self::$device_type!='computer' ) return do_shortcode($content);
  }

}

new CCS_Mobile_Detect;

endif; // Only on frontend
