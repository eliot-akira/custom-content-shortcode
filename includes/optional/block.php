<?php

/*---------------------------------------------
 *
 * HTML block shortcodes
 *
 */

new CCS_Blocks;

class CCS_Blocks {

  function __construct() {

    $tags = array(
      'a',
      'aside',
      'b',
      // 'br',
      'button',
      'article',
      'block',
      'center',
      'code',
      'div',
      'dl', 'dt', 'dd',
      'em',
      'footer',
      'form',
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'header',
      'hr',
      'i',
      'input',
      'label',
      'li',
      'ol',
      // 'p',
      'pre',
      'section',
      'select',
      'option',
      'span',
      'table',
      'tbody',
      'td',
      'th',
      'thead',
      'tr',
      'textarea',
      'u',
      'ul',
    );

    foreach ($tags as $tag) {
      add_ccs_shortcode( $tag, array($this, 'block_shortcode') );
    }

    $nested = array('div','ol','li','ul','block');

    foreach ($nested as $tag) {
      for ($i=1; $i < 6; $i++) {
        $prefix = str_repeat('-', $i);
        add_ccs_shortcode( $prefix.$tag, array($this, 'block_shortcode') );
      }
    }
  }

  function block_shortcode( $atts, $content = '', $tag ) {
    if (!is_array($atts)) $atts = array($atts);

    // Remove prefix
    while (isset($tag[0]) && $tag[0]=='-') {
      $tag = substr($tag, 1);
    }

    if (isset($atts['tag'])) {
      $tag = $atts['tag'];
    } elseif ($tag == 'block') {
      $tag = 'div';
    }

    // Construct block
    $out = '<'.$tag;

    foreach ($atts as $key => $value) {

      if ($key=='tag') continue;

      if (is_numeric($key)) {
        $out .= ' '.$value; // Attribute with no value
      } else {

        // Attribute values can have shortcodes with syntax: <shortcode>
        $value = str_replace( array('<','>'), array('[',']'), $value);
        $out .= ' '.$key.'="'.do_ccs_shortcode($value).'"';
      }
    }
    $out .= '>';

    if (!empty($content)) {
      $out .= do_ccs_shortcode( $content );
      $out .= '</'.$tag.'>';
    }

    // Filter for extended features

    $data = array(
      'atts' => $atts,
      'content' => $content,
      'tag' => $tag,
      'out' => $out
    );

    $data = apply_filters( 'ccs_block_result', $data );

    return $data['out'];
  }

}
