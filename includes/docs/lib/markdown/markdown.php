<?php

/*---------------------------------------------
 *
 * Markdown module
 *
 * v0.0.3
 *
 */

new MarkDown_Module;

class MarkDown_Module {

	public static $parsedown;

	function __construct() {

		if (!class_exists('Parsedown')) require(dirname(__FILE__).'/lib/parsedown.php');

		self::$parsedown = new Parsedown();
		add_shortcode( 'md', array($this, 'markdown_shortcode') );
	}

	function markdown_shortcode( $atts, $content ) {

		$args = array();
		extract( shortcode_atts( array(
			'shortcode' => 'true',
      'escape' => 'true'
		) , $atts, true ) );

    if ($shortcode=='true') $content = do_shortcode($content);
    if ($escape=='true') $content = htmlspecialchars($content);

    $out = self::render($content);
    $out = self::unescape($out);

    if ($shortcode=='later') $out = do_shortcode($out);

		return $out;
	}

	public static function render( $content, $do_shortcode = false, $escape = false ) {

    if ( $do_shortcode ) $content = do_shortcode( $content );
    if ( $escape ) $content = htmlspecialchars($content);

    $result = self::$parsedown->text(trim($content));

    if ( $escape ) $result = self::unescape($result);

		return $result;
	}

	static function unescape( $out ) {
		return str_replace(array('&#91;','&#93;'), array('[',']'), html_entity_decode($out));
	}


}
