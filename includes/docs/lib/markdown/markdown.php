<?php

/*---------------------------------------------
 *
 * Markdown module
 *
 * v0.0.6
 *
 */

new Markdown_Module;

class Markdown_Module {

	public static $parsedown;

	function __construct() {

		if (!class_exists('Parsedown')) require(dirname(__FILE__).'/lib/parsedown.php');

		self::$parsedown = new Parsedown();
		add_ccs_shortcode( 'md', array($this, 'markdown_shortcode') );
	}

	function markdown_shortcode( $atts, $content ) {

		$args = array();
		extract( shortcode_atts( array(
			'shortcode' => 'false',
      'escape' => 'false',
      'direct' => '',
		) , $atts, true ) );

    $out = self::render($content, $shortcode=='true', $escape=='true');

    if ($shortcode=='later') $out = do_ccs_shortcode($out);

    if ($direct=='true') $out = '[direct]'.$out.'[/direct]';

		return $out;
	}

	public static function render( $content, $do_shortcode = false, $escape = false ) {

    if ( $do_shortcode ) $content = do_ccs_shortcode( $content );
    if ( $escape ) {
      $content = htmlspecialchars($content);
    }

    $result = self::$parsedown->text(trim($content));

    if ( $escape ) $result = str_replace( '&amp;', '&', $result ); // Maybe better..?

    if ( $escape ) $result = self::unescape($result); // ??
    if ( ! $do_shortcode )
      $result = str_replace(array('[',']'), array('&#91;','&#93;'), $result);

		return $result;
	}

	static function unescape( $out ) {
    return htmlspecialchars_decode($out);
	}


}
