<?php

/*---------------------------------------------
 *
 * Markdown module
 *
 * v0.0.3
 *
 */

new Markdown_Module;

class MarkDown_Module {

	public static $parsedown;

	function __construct() {

		require(dirname(__FILE__).'/lib/parsedown.php');
		self::$parsedown = new Parsedown();
		add_shortcode( 'md', array($this, 'markdown_shortcode') );
	}

	function markdown_shortcode($atts, $content) {

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

	public static function render( $content, $do_shortcode = false ) {
		if ($do_shortcode)
			return self::$parsedown->text(trim(do_shortcode( $content )) );
		else
			return self::$parsedown->text(trim($content)); // do_shortcode()

	}

	static function unescape( $out ) {
		return str_replace(array('&#91;','&#93;'), array('[',']'), html_entity_decode($out));
	}


}
