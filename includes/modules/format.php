<?php

/*---------------------------------------------
 *
 * Format functions
 *
 */

new CCS_Format;

class CCS_Format {

	public static $state;

	function __construct() {

    add_local_shortcode( 'ccs', 'direct', array($this, 'direct_shortcode'), true  );
    add_local_shortcode( 'ccs', 'format', array($this, 'format_shortcode'), true  );
		add_local_shortcode( 'ccs', 'clean', array($this, 'clean_shortcode'), true  );
		add_local_shortcode( 'ccs', 'br', array($this, 'br_shortcode'), true  );
		add_local_shortcode( 'ccs', 'p', array($this, 'p_shortcode'), true  );
    add_local_shortcode( 'ccs', 'slugify', array($this, 'slugify_shortcode'), true  );
    add_local_shortcode( 'ccs', 'today', array($this, 'today_shortcode'), true  );
    add_local_shortcode( 'ccs', 'http', array($this, 'http_shortcode'), true  );
    add_local_shortcode( 'ccs', 'embed', array($this, 'embed_shortcode'), true  );
    add_local_shortcode( 'ccs', 'escape', array($this, 'escape_shortcode'), true  );
    add_local_shortcode( 'ccs', 'random', array($this, 'random_shortcode'), true  );
		add_local_shortcode( 'ccs', 'x', array($this, 'x_shortcode') );
		self::$state['x_loop'] = 0;
	}


  // Don't run shortcodes inside
  function direct_shortcode( $atts, $content ) {
    return $content;
  }

	function br_shortcode() { return '<br />'; }

	function p_shortcode( $atts, $content ) {

		$tag = 'p';

    // Construct block
    $out = '<'.$tag;

		if (!empty($atts)) {
	    foreach ($atts as $key => $value) {
	      if (is_numeric($key)) {
	        $out .= ' '.$value; // Attribute with no value
	      } else {
	        $out .= ' '.$key.'="'.$value.'"';
	      }
	    }
		}

    $out .= '>';

    if (!empty($content)) {
      $out .= do_local_shortcode( 'ccs', $content, true );
      $out .= '</'.$tag.'>';
    }
		return $out;
	}


	// Do shortcode, then format
  function format_shortcode( $atts, $content ) {
    return wpautop(do_local_shortcode( 'ccs', $content, true ));
  }

  // Repeat x times: [x 10]..[/x]
	function x_shortcode( $atts, $content ) {

		if (!isset($atts[0])) return $content;

		$x = $atts[0];
		$out = '';

		// Start index from 1
		for ($i=1; $i <= $x; $i++) {
			self::$state['x_loop'] = $i;
			$rendered = str_replace('{X}', $i, $content);
			$out .= do_local_shortcode( 'ccs', $rendered, true );
		}

		self::$state['x_loop'] = 0;
		return $out;
	}

	// Display current date
	function today_shortcode( $atts, $content ) {
		if ( isset($atts['format']) ) {
			$format = str_replace("//", "\\", $atts['format']);
		} else {
			$format = get_option('date_format');
		}

		return date($format);
	}

	// Convert title to slug

	function slugify_shortcode( $atts, $content ) {

  	// The Example Title -> the_example_title
    return strtolower( str_replace(array(' ','-'), '_', self::alphanumeric(do_shortcode($content)) ) );
	}

	public static function alphanumeric( $str ) {

		// Remove any character that is not alphanumeric, white-space, or a hyphen
    	return preg_replace("/[^a-z0-9\s\-_]/i", "", $str );
	}

	/*---------------------------------------------
	 *
	 * Strip an array of tags from content
	 *
	 */

	function strip_tag_list( $content, $tags ) {
		$tags = implode("|", $tags);
		$out = preg_replace('!<\s*('.$tags.').*?>((.*?)</\1>)?!is', '\3', $content);
		return $out;
	}

	function clean_content($content){
	    $content = self::strip_tag_list( $content, array('p','br') );
	    return $content;
	}

	function clean_shortcode( $atts, $content ) {
		$content = self::strip_tag_list( $content, array('p','br') );
		return do_local_shortcode( 'ccs', $content, true );
	}

  static function trim( $content, $trim = '' ) {
    if ($trim=='true') $trim = '';
    return trim($content, " \t\n\r\0\x0B,".$trim);
  }



  /*---------------------------------------------
   *
   * Add http:// if necessary
   *
   * [http]..[/http]
   *
   */

  function http_shortcode( $atts, $content ) {
    $content = do_shortcode($content);
    if ( substr($content, 0, 4) !== 'http' ) {
      $content = 'http://'.$content;
    }
    return $content;
  }


  /*---------------------------------------------
   *
   * Embed audio/video link from field
   *
   */

  function embed_shortcode( $atts, $content ) {

    $content = do_shortcode($content);

    if (isset($GLOBALS['wp_embed'])) {
      $wp_embed = $GLOBALS['wp_embed'];
      $content = $wp_embed->autoembed($content);
      // Run [audio], [video] in embed
      $content = do_shortcode($content);
    }

    return $content;
  }

  function escape_shortcode( $atts, $content ) {
		if ($atts['shortcode']=='true')
			$content = do_local_shortcode( 'ccs',  $content, true );
		return str_replace(array('[',']'), array('&#91;','&#93;'), esc_html($content));
	}

	function random_shortcode( $atts, $content ) {
		if (!isset($atts[0])) $atts[0] = '0-99'; // default
	  $range = explode('-', $atts[0]);
		$min = $range[0];
		$max = @$range[1];

		return rand($min, $max);
	}






  /*---------------------------------------------
   *
   * Currency format
   *
   * TODO: Make this optional
   *
   * @param flatcurr  float integer to convert
   * @param curr  string of desired currency format
   * @return formatted number
   */

  static function getCurrency(
    $floatcurr, $curr = '',
    $decimals = 2, $point = '.', $thousands = ',' ) {

    $currencies = array(

      'ARS' => array(2,',','.'),      //  Argentine Peso
      'AMD' => array(2,'.',','),      //  Armenian Dram
      'AWG' => array(2,'.',','),      //  Aruban Guilder
      'AUD' => array(2,'.',' '),      //  Australian Dollar
      'BSD' => array(2,'.',','),      //  Bahamian Dollar
      'BHD' => array(3,'.',','),      //  Bahraini Dinar
      'BDT' => array(2,'.',','),      //  Bangladesh, Taka
      'BZD' => array(2,'.',','),      //  Belize Dollar
      'BMD' => array(2,'.',','),      //  Bermudian Dollar
      'BOB' => array(2,'.',','),      //  Bolivia, Boliviano
      'BAM' => array(2,'.',','),      //  Bosnia and Herzegovina, Convertible Marks
      'BWP' => array(2,'.',','),      //  Botswana, Pula
      'BRL' => array(2,',','.'),      //  Brazilian Real
      'BND' => array(2,'.',','),      //  Brunei Dollar
      'CAD' => array(2,'.',','),      //  Canadian Dollar
      'KYD' => array(2,'.',','),      //  Cayman Islands Dollar
      'CLP' => array(0,'','.'),     //  Chilean Peso
      'CNY' => array(2,'.',','),      //  China Yuan Renminbi
      'COP' => array(2,',','.'),      //  Colombian Peso
      'CRC' => array(2,',','.'),      //  Costa Rican Colon
      'HRK' => array(2,',','.'),      //  Croatian Kuna
      'CUC' => array(2,'.',','),      //  Cuban Convertible Peso
      'CUP' => array(2,'.',','),      //  Cuban Peso
      'CYP' => array(2,'.',','),      //  Cyprus Pound
      'CZK' => array(2,'.',','),      //  Czech Koruna
      'DKK' => array(2,',','.'),      //  Danish Krone
      'DOP' => array(2,'.',','),      //  Dominican Peso
      'XCD' => array(2,'.',','),      //  East Caribbean Dollar
      'EGP' => array(2,'.',','),      //  Egyptian Pound
      'SVC' => array(2,'.',','),      //  El Salvador Colon
      'ATS' => array(2,',','.'),      //  Euro
      'BEF' => array(2,',','.'),      //  Euro
      'DEM' => array(2,',','.'),      //  Euro
      'EEK' => array(2,',','.'),      //  Euro
      'ESP' => array(2,',','.'),      //  Euro
      'EUR' => array(2,',','.'),      //  Euro
      'FIM' => array(2,',','.'),      //  Euro
      'FRF' => array(2,',','.'),      //  Euro
      'GRD' => array(2,',','.'),      //  Euro
      'IEP' => array(2,',','.'),      //  Euro
      'ITL' => array(2,',','.'),      //  Euro
      'LUF' => array(2,',','.'),      //  Euro
      'NLG' => array(2,',','.'),      //  Euro
      'PTE' => array(2,',','.'),      //  Euro
      'GHC' => array(2,'.',','),      //  Ghana, Cedi
      'GIP' => array(2,'.',','),      //  Gibraltar Pound
      'GTQ' => array(2,'.',','),      //  Guatemala, Quetzal
      'HNL' => array(2,'.',','),      //  Honduras, Lempira
      'HKD' => array(2,'.',','),      //  Hong Kong Dollar
      'HUF' => array(0,'','.'),     //  Hungary, Forint
      'ISK' => array(0,'','.'),     //  Iceland Krona
      'INR' => array(2,'.',','),      //  Indian Rupee
      'IDR' => array(2,',','.'),      //  Indonesia, Rupiah
      'IRR' => array(2,'.',','),      //  Iranian Rial
      'JMD' => array(2,'.',','),      //  Jamaican Dollar
      'JPY' => array(0,'',','),     //  Japan, Yen
      'JOD' => array(3,'.',','),      //  Jordanian Dinar
      'KES' => array(2,'.',','),      //  Kenyan Shilling
      'KWD' => array(3,'.',','),      //  Kuwaiti Dinar
      'LVL' => array(2,'.',','),      //  Latvian Lats
      'LBP' => array(0,'',' '),     //  Lebanese Pound
      'LTL' => array(2,',',' '),      //  Lithuanian Litas
      'MKD' => array(2,'.',','),      //  Macedonia, Denar
      'MYR' => array(2,'.',','),      //  Malaysian Ringgit
      'MTL' => array(2,'.',','),      //  Maltese Lira
      'MUR' => array(0,'',','),     //  Mauritius Rupee
      'MXN' => array(2,'.',','),      //  Mexican Peso
      'MZM' => array(2,',','.'),      //  Mozambique Metical
      'NPR' => array(2,'.',','),      //  Nepalese Rupee
      'ANG' => array(2,'.',','),      //  Netherlands Antillian Guilder
      'ILS' => array(2,'.',','),      //  New Israeli Shekel
      'TRY' => array(2,'.',','),      //  New Turkish Lira
      'NZD' => array(2,'.',','),      //  New Zealand Dollar
      'NOK' => array(2,',','.'),      //  Norwegian Krone
      'PKR' => array(2,'.',','),      //  Pakistan Rupee
      'PEN' => array(2,'.',','),      //  Peru, Nuevo Sol
      'UYU' => array(2,',','.'),      //  Peso Uruguayo
      'PHP' => array(2,'.',','),      //  Philippine Peso
      'PLN' => array(2,'.',' '),      //  Poland, Zloty
      'GBP' => array(2,'.',','),      //  Pound Sterling
      'OMR' => array(3,'.',','),      //  Rial Omani
      'RON' => array(2,',','.'),      //  Romania, New Leu
      'ROL' => array(2,',','.'),      //  Romania, Old Leu
      'RUB' => array(2,',','.'),      //  Russian Ruble
      'SAR' => array(2,'.',','),      //  Saudi Riyal
      'SGD' => array(2,'.',','),      //  Singapore Dollar
      'SKK' => array(2,',',' '),      //  Slovak Koruna
      'SIT' => array(2,',','.'),      //  Slovenia, Tolar
      'ZAR' => array(2,'.',' '),      //  South Africa, Rand
      'KRW' => array(0,'',','),     //  South Korea, Won
      'SZL' => array(2,'.',', '),     //  Swaziland, Lilangeni
      'SEK' => array(2,',','.'),      //  Swedish Krona
      'CHF' => array(2,'.','\''),     //  Swiss Franc
      'TZS' => array(2,'.',','),      //  Tanzanian Shilling
      'THB' => array(2,'.',','),      //  Thailand, Baht
      'TOP' => array(2,'.',','),      //  Tonga, Paanga
      'AED' => array(2,'.',','),      //  UAE Dirham
      'UAH' => array(2,',',' '),      //  Ukraine, Hryvnia
      'USD' => array(2,'.',','),      //  US Dollar
      'VUV' => array(0,'',','),     //  Vanuatu, Vatu
      'VEF' => array(2,',','.'),      //  Venezuela Bolivares Fuertes
      'VEB' => array(2,',','.'),      //  Venezuela, Bolivar
      'VND' => array(0,'','.'),     //  Viet Nam, Dong
      'ZWD' => array(2,'.',' ')      //  Zimbabwe Dollar

    );

    $curr = strtoupper($curr);

    if ($curr == "INR"){
      return self::formatinr($floatcurr);
    } else {

      if (!empty($curr)) {

        if (!isset($currencies[$curr])) return $floatcurr;

        $decimals = $currencies[$curr][0];
        $point = $currencies[$curr][1];
        $thousands = $currencies[$curr][2];
      }

      return number_format($floatcurr,$decimals,$point,$thousands);
    }
  }

  // Format Indian Rupees!
  static function formatinr($input){
    //CUSTOM FUNCTION TO GENERATE ##,##,###.##
    $dec = "";
    $pos = strpos($input, ".");
    if ($pos === false){
      //no decimals
    } else {
      //decimals
      $dec = substr(round(substr($input,$pos),2),1);
      $input = substr($input,0,$pos);
    }
    $num = substr($input,-3); //get the last 3 digits
    $input = substr($input,0, -3); //omit the last 3 digits already stored in $num
    while(strlen($input) > 0) //loop the process - further get digits 2 by 2
    {
      $num = substr($input,-2).",".$num;
      $input = substr($input,0,-2);
    }
    return $num . $dec;
  }

}
