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

    add_ccs_shortcode( array(
      //'direct' => array($this, 'direct_shortcode'),
      'format' => array($this, 'format_shortcode'),
      '-format' => array($this, 'format_shortcode'),
      '--format' => array($this, 'format_shortcode'),
      'clean' => array($this, 'clean_shortcode'),
      'br' => array($this, 'br_shortcode'),
      'p' => array($this, 'p_shortcode'),
      'link' => array($this, 'link_shortcode'),
      'image' => array($this, 'image_shortcode'),
      'slugify' => array($this, 'slugify_shortcode'),
      'unslugify' => array($this, 'unslugify_shortcode'),
      'today' => array($this, 'today_shortcode'),
      'http' => array($this, 'http_shortcode'),
      'embed' => array($this, 'embed_shortcode'),
      'escape' => array($this, 'escape_shortcode'),
      'random' => array($this, 'random_shortcode'),
      'x' => array($this, 'x_shortcode'),
      'global' => array($this, 'global_shortcode'),
    ) );
    add_shortcode('direct', array($this, 'direct_shortcode'));
    self::$state['x_loop'] = 0;
  }


  function global_shortcode( $atts, $content ) {
    return do_ccs_shortcode($content);
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
      $out .= do_ccs_shortcode( $content );
      $out .= '</'.$tag.'>';
    }
    return $out;
  }


  function format_shortcode( $atts, $content = '' ) {

    $content = do_ccs_shortcode( $content );

    if ( isset($atts[0]) ) {
      switch ($atts[0]) {
        case 'slugify':
          return self::slugify( $content );
        break;
        case 'unslugify':
          return self::unslugify( $content );
        break;
        case 'ucfirst':
          return ucfirst( $content );
        break;
        case 'ucwords':
          return ucwords( $content );
        break;
        case 'plural':
          return self::pluralize( $content );
        break;
      }
    }

    if (!empty($atts['date']) ) {
      return self::format_date( $atts, $content );
    }

    if (!empty($atts['split'])) {
      $parts = self::explode_list($content, $atts['split'], true);
      if (isset($atts['part'])) {
        $index = (int)$atts['part'] - 1; // "part" starts from 1
        return @$parts[ $index ];
      } else {
        return implode(',', $parts);
      }
    }

    if ( !empty($atts['currency']) || !empty($atts['decimals'])
      || !empty($atts['point']) || !empty($atts['thousands'])) {

      return self::format_number( $atts, $content );
    }

  }

  static function format_date( $atts, $content = '' ) {

    // Convert input
    if ( !empty($atts['in']) ) {
      if ($atts['in']=='timestamp') {
        $content = gmdate("Y-m-d H:i:s", intval($content));
      } else {
        // Other formats?
      }
    }


    $format = $atts['date'];

    if ($format=='relative') {
      $content = self::get_relative_date( $content );
    } else {

      if ($format=='default') {
        $format = get_option('date_format');
      } else {
        // allow escape via "//" because "\" disappears in shortcode parameters
        $format = str_replace("//", "\\", $format);
      }

      $content = mysql2date($format, $content);
    }
    return $content;
  }


  static function format_number( $atts, $content ) {

    $currency = !empty($atts['currency']) ? $atts['currency'] : '';
    $decimals = !empty($atts['decimals']) ? $atts['decimals'] : 0;
    $point = !empty($atts['point']) ? $atts['point'] : '.';
    $thousands = !empty($atts['thousands'])
      ? ($thousands==='false' ? '' : $atts['thousands']) : '';

    $content = CCS_Format::getCurrency(
      floatval($content), $currency, $decimals, $point, $thousands
    );

    return $content;
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
      $out .= do_ccs_shortcode( $rendered );
    }

    self::$state['x_loop'] = 0;
    return $out;
  }

  // Convert title to slug
  // The Example Title -> the_example_title

  function slugify_shortcode( $atts, $content ) {
    $content = do_ccs_shortcode($content);
    return self::slugify( $content );
  }

  static function slugify( $content ) {
    return strtolower( str_replace(array(' ','-'), '_', self::alphanumeric(
      ltrim(str_replace('/', '-', $content), '-')
    )));
  }

  // Convert slug to title
  // the-example-title -> The Example Title

  function unslugify_shortcode( $atts, $content ) {
    $content = do_ccs_shortcode($content);
    return self::unslugify( $content );
  }

  static function unslugify( $content ) {
    return ucwords( str_replace( array('_','-'), ' ', $content) );
  }



  public static function alphanumeric( $str ) {

    // Remove any character that is not alphanumeric, white-space, hyphen or underscore
      return preg_replace("/[^a-z0-9\s\-_]/i", "", $str );
  }


  /*---------------------------------------------
   *
   * Explode comma-separated list and remove extra space from each item
   *
   */

  public static function explode_list( $list, $delimiter = '', $exclude_comma = false ) {

    if (!is_string($list)) {
      if (is_array($list)) return $list;
      // Assume integer or float
      return array($list);
    }

    // Support multiple delimiters

    if (!$exclude_comma) {
      $delimiter .= ','; // default
    }

    $delimiters = str_split($delimiter); // convert to array
    $list = str_replace($delimiters, $delimiters[0], $list); // change all delimiters to same

    // explode list and trim each item

    return array_map( 'trim', explode($delimiters[0], $list) );
  }





  /*---------------------------------------------
   *
   * Strip an array of tags from content
   *
   */

  static function strip_tag_list( $content, $tags ) {
    $tags = implode("|", $tags);
    $out = preg_replace('!<\s*('.$tags.').*?>((.*?)</\1>)?!is', '\3', $content);
    return $out;
  }

  static function clean_content($content){
      $content = self::strip_tag_list( $content, array('p','br') );
      return $content;
  }

  function clean_shortcode( $atts, $content ) {
    $content = self::strip_tag_list( $content, array('p','br') );
    return do_ccs_shortcode( $content );
  }

  static function trim( $content, $trim = '' ) {
    if ($trim=='true') $trim = '';
    return trim($content, " \t\n\r\0\x0B,".$trim);
  }



  // Don't run shortcodes inside
  static function direct_shortcode( $atts, $content ) {
    return $content;
  }


  // Protect JS inside content **Unused**
  static function protect_script( $content, $global = true ) {

    $begin = '<script';
    $end = '</script>';

    $pre = '[direct]';
    $pre .= $begin;
    $post = $end;
    $post .= '[/direct]';

    $content = str_replace( $begin, $pre, $content );
    $content = str_replace( $end, $post, $content );

    return $content;
  }



  /*---------------------------------------------
   *
   * Add http:// if necessary
   *
   * [http]..[/http]
   *
   */

  function http_shortcode( $atts, $content ) {
    return self::maybe_add_http( do_shortcode($content) );
  }

  static function maybe_add_http( $content ) {
    if ( !empty($content) && substr($content, 0, 4) !== 'http' ) {
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

    // Expand field if any
    $content = do_shortcode($content);
    return wp_oembed_get($content);
/*
    if (isset($GLOBALS['wp_embed'])) {
//      $content = $GLOBALS['wp_embed']->autoembed($content);
      // Run [audio], [video] in embed
      //$content = do_shortcode($content);
    }

    return $content;
*/
  }

  static function escape_shortcode( $atts, $content ) {
    if ( @$atts['shortcode']=='true' )
      $content = do_ccs_shortcode( $content );
    if ( @$atts['html']=='true' ) {

      //$content = esc_html($content);

      // TODO: This gets &amp; and so on..but is it the expected behavior?
      $content = htmlentities(esc_html($content));
    }
/*
    if( @$atts['special']=='true' ) {
      $content = htmlentities($content);
//echo '<pre><code>'.$content.'</code></pre>';
    }
*/
    return str_replace(array('[',']'), array('&#91;','&#93;'), $content);
  }

  function random_shortcode( $atts, $content ) {
    if (!isset($atts[0])) $atts[0] = '0-99'; // default
    $range = explode('-', $atts[0]);
    $min = $range[0];
    $max = @$range[1];

    return rand($min, $max);
  }

  function link_shortcode( $atts, $content ) {

    extract( shortcode_atts( array(
      'field' => '',
      'custom' => '',
      'url' => '', // URL directly; overrides parameter field
      'alt' => '',
      'title' => '',
      'target' => '',
      'open' => '', // new
      'http' => '', // true/false
      'https' => '', // true/false
      'id' => '',
      'type' => '',
      'name' => '',
      'link_id' => '',
      'class' => '',
      'mail' => '',
      'protocol' => '',
      'before' => '',
      'after' => '',
      'escape' => 'true',
    ), $atts ) );

    if (empty($field)) $field = isset($atts[0]) ? $atts[0] : 'url'; // default

    if ( !empty($url) ) $value = $url;
    else {
      $x = '[field '.$field;
      if (!empty($id)) $x .= ' id='.$id;
      elseif (!empty($name)) $x .= ' name=\''.$name.'\'';
      if (!empty($type)) $x .= ' type='.$type;
      if (!empty($custom)) $x .= ' custom='.$custom;
      $x .= ']';

      $value = do_shortcode($x);
      if ($escape=='true') $value = esc_html( $value );
    }

    if ($mail=='true') $before = "mailto:";
    elseif ($http=='true') $protocol = 'http';
    elseif ($https=='true') $protocol = 'https';

    if ( !empty($protocol) && !empty($value)
      && substr($value, 0, strlen($protocol)) !== $protocol ) {

      $before = $protocol.'://';
    }

    $value = $before . $value . $after;

    $out = '<a href="'.$value.'"';
    if (!empty($alt)) $out .= ' alt="'.$alt.'"';
    if (!empty($class)) $out .= ' class="'.$class.'"';
    if (!empty($link_id)) $out .= ' id="'.$link_id.'"';
    if (!empty($title)) $out .= ' title="'.$title.'"';
    if ($open=='new') $target = '_blank';
    if (!empty($target)) $out .= ' target="'.$target.'"';
    $out .= '>';

    $out .= do_ccs_shortcode($content);

    $out .= '</a>';

    return $out;
  }



  function image_shortcode( $atts, $content ) {

    extract( shortcode_atts( array(
      'alt' => '',
      'width' => '',
      'height' => '',
      'class' => '',
      'http' => '' // true/false
    ), $atts ) );

    $src = do_ccs_shortcode($content);

    if ($http=='true') $src = self::maybe_add_http( $src );

    $out = '<img src="'.$src.'"';
    if (!empty($alt)) $out .= ' alt="'.$alt.'"';
    if (!empty($width)) $out .= ' width="'.$width.'"';
    if (!empty($height)) $out .= ' height="'.$height.'"';
    if (!empty($class)) $out .= ' class="'.$class.'"';
    $out .= '>';
    return $out;
  }



  static function get_minus_prefix( $shortcode_name ) {

    for ($i=5; $i > 0; $i--) {
      $prefix = str_repeat('-', $i);
      if ( substr($shortcode_name, 0, $i)==$prefix ) {
        return $prefix;
      }
    }

    return '';
  }



  static function trim_with_tags( $text, $length = 100, $ending = '' ) {

    // if the plain text is shorter than the maximum length, return the whole text
    if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
      return $text;
    }
    // splits all html-tags to scanable lines
    preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
    $total_length = strlen($ending);
    $open_tags = array();
    $truncate = '';
    foreach ($lines as $line_matchings) {
      // if there is any html-tag in this line, handle it and add it (uncounted) to the output
      if (!empty($line_matchings[1])) {
        // if it's an "empty element" with or without xhtml-conform closing slash
        if ( preg_match(
          '/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1]) ) {
          // do nothing
        // if tag is a closing tag
        } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
          // delete tag from $open_tags list
          $pos = array_search($tag_matchings[1], $open_tags);
          if ($pos !== false) {
            unset($open_tags[$pos]);
          }
        // if tag is an opening tag
        } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
          // add tag to the beginning of $open_tags list
          array_unshift($open_tags, strtolower($tag_matchings[1]));
        }
        // add html-tag to $truncate'd text
        $truncate .= $line_matchings[1];
      }
      // calculate the length of the plain text part of the line; handle entities as one character
      $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
      if ($total_length+$content_length> $length) {
        // the number of characters which are left
          $left = $length - $total_length;
        $entities_length = 0;
          // search for html entities
        if (preg_match_all(
          '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i',
          $line_matchings[2], $entities, PREG_OFFSET_CAPTURE) ) {
          // calculate the real length of all entities in the legal range
          foreach ($entities[0] as $entity) {
            if ($entity[1]+1-$entities_length <= $left) {
              $left--;
              $entities_length += strlen($entity[0]);
            } else {
              // no more characters left
              break;
            }
          }
        }
        $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
        // maximum lenght is reached, so get off the loop
        break;
      } else {
        $truncate .= $line_matchings[2];
        $total_length += $content_length;
      }
      // if the maximum length is reached, get off the loop
      if($total_length>= $length) {
        break;
      }
    }

    $truncate .= $ending;

    // close all unclosed html-tags
    foreach ($open_tags as $tag) {
      $truncate .= '</' . $tag . '>';
    }

    return $truncate;
  }



  static function trim_words_with_tags( $text, $num_words = 55, $more = null ) {

    if ( null === $more ) $more = __( '&hellip;' );

    /* translators: If your word count is based on single characters (East Asian characters),
       enter 'characters'. Otherwise, enter 'words'. Do not translate into your own language. */
    if ( 'characters' == _x( 'words', 'word count: words or characters?' ) && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {

        $text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
        preg_match_all( '/./u', $text, $words_array );
        $words_array = array_slice( $words_array[0], 0, $num_words + 1 );
        $sep = '';
    } else {
        $words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
        $sep = ' ';
    }
    if ( count( $words_array ) > $num_words ) {
        array_pop( $words_array );
        $text = implode( $sep, $words_array );
        $text = $text . $more;
    } else {
        $text = implode( $sep, $words_array );
    }
    return $text;
  }


  // Display current date
  function today_shortcode( $atts, $content ) {
    if ( isset($atts['format']) ) {
      $format = str_replace("//", "\\", $atts['format']);
    } else {
      $format = get_option('date_format');
    }
    return date_i18n($format);
  }


  static function get_relative_date( $date ) {

    // TODO: Just use human_time_diff()

    $current_time = current_time('timestamp');
    $date_today_time = gmdate('j-n-Y H:i:s', $current_time);
    $compare_date_time = mysql2date('j-n-Y H:i:s', $date, false);
    $date_today = gmdate('j-n-Y', $current_time);
    $compare_date = mysql2date('j-n-Y', $date, false);
    $time_diff = (strtotime($date_today_time) - strtotime($compare_date_time));
    $format_ago = mysql2date(get_option('date_format'), $date);

    if($time_diff < 60) { // < 1 minute
      $format_ago = sprintf(_n('%s second ago', '%s seconds ago', $time_diff, 'domain'), number_format_i18n($time_diff));
    } elseif ($time_diff < 3600) { // < 1 hour
      $format_ago = sprintf(_n('%s minute ago', '%s minutes ago', intval($time_diff/60), 'domain'), number_format_i18n(intval($time_diff/60)));
    } elseif ($time_diff < 86400) { // < 24 hours
      $format_ago = sprintf(_n('%s hour ago', '%s hours ago', intval($time_diff/3600), 'domain'), number_format_i18n(intval($time_diff/3600)));
    } elseif ($time_diff < 604800) { // < 7 days
      $format_ago = sprintf(_n('Yesterday', '%s days ago', intval($time_diff/86400), 'domain'), number_format_i18n(intval($time_diff/86400)));
    } elseif ($time_diff < 2592000) { // < 30 days
      $format_ago = sprintf(_n('%s week ago', '%s weeks ago', intval($time_diff/604800), 'domain'), number_format_i18n(intval($time_diff/604800)));
    }

    return $format_ago;
  }




  static function sort_array_of_array( &$array, $subfield, $order = 'asc' ) {

    $sortarray = array();
    foreach ($array as $key => $row) {
      $sortarray[$key] = $row[$subfield];
    }
    $order = strtoupper($order);
    array_multisort($sortarray, ($order=='ASC' ? SORT_ASC : SORT_DESC), $array);
  }



  static function normalize_alphabet( $string ) {
    $pattern = array("'é'", "'è'", "'ë'", "'ê'", "'É'", "'È'", "'Ë'", "'Ê'", "'á'", "'à'", "'ä'", "'â'", "'å'", "'Á'", "'À'", "'Ä'", "'Â'", "'Å'", "'ó'", "'ò'", "'ö'", "'ô'", "'Ó'", "'Ò'", "'Ö'", "'Ô'", "'í'", "'ì'", "'ï'", "'î'", "'Í'", "'Ì'", "'Ï'", "'Î'", "'ú'", "'ù'", "'ü'", "'û'", "'Ú'", "'Ù'", "'Ü'", "'Û'", "'ý'", "'ÿ'", "'Ý'", "'ø'", "'Ø'", "'œ'", "'Œ'", "'Æ'", "'ç'", "'Ç'");

    $replace = array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'I', 'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'y', 'y', 'Y', 'o', 'O', 'a', 'A', 'A', 'c', 'C');

    $string = preg_replace($pattern, $replace, $string);

    return $string;
  }




	// Single to plural English word
	public static function pluralize( $word ) {

		$plural = array(
			'/(quiz)$/i'               => "$1zes",
			'/^(ox)$/i'                => "$1en",
			'/([m|l])ouse$/i'          => "$1ice",
			'/(matr|vert|ind)ix|ex$/i' => "$1ices",
			'/(x|ch|ss|sh)$/i'         => "$1es",
			'/([^aeiouy]|qu)y$/i'      => "$1ies",
			'/(hive)$/i'               => "$1s",
			'/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
			'/(shea|lea|loa|thie)f$/i' => "$1ves",
			'/sis$/i'                  => "ses",
			'/([ti])um$/i'             => "$1a",
			'/(tomat|potat|ech|her|vet)o$/i'=> "$1oes",
			'/(bu)s$/i'                => "$1ses",
			'/(alias)$/i'              => "$1es",
			'/(octop)us$/i'            => "$1i",
			'/(ax|test)is$/i'          => "$1es",
			'/(us)$/i'                 => "$1es",
			'/s$/i'                    => "s",
			'/$/'                      => "s"
		);

		$uncountable = array(
      'audio',
      'info',
			'sheep',
			'fish',
			'deer',
			'series',
			'species',
			'money',
			'rice',
			'information',
			'equipment'
		);

		$irregular = array(
			'move'   => 'moves',
			'foot'   => 'feet',
			'goose'  => 'geese',
			'sex'    => 'sexes',
			'child'  => 'children',
			'man'    => 'men',
			'tooth'  => 'teeth',
			'person' => 'people'
		);

		$lowercased_word = strtolower($word);

		foreach ($uncountable as $_uncountable){
			if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
				return $word;
			}
		}

		foreach ($irregular as $_plural => $_singular){
			if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
				return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
			}
		}

		foreach ($plural as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}

		return false;
	}


  // TODO: Separate below into optional modules

  /*---------------------------------------------
   *
   * Currency format
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

  // Format Indian Rupees!!!
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


  /*---------------------------------------------
   *
   * Unused
   *
   * TODO: Confirm and remove
   *
   */

  // set the PHP timezone to match WordPress
  static function correct_php_timezone() {
    $gofs = get_option( 'gmt_offset' ); // get WordPress offset in hours
    $tz = date_default_timezone_get(); // get current PHP timezone
    date_default_timezone_set('Etc/GMT'.(($gofs < 0)?'+':'').-$gofs);
    self::$state['php_timezone'] = $tz;
  }

  // set the PHP timezone back the way it was
  static function restore_php_timezone() {
    $tz = date_default_timezone_get(); // get current PHP timezone
    date_default_timezone_set( self::$state['php_timezone'] );
  }

}
