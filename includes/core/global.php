<?php

/*---------------------------------------------
 *
 * Global helper functions
 *
 */


function add_ccs_shortcode( $tag, $func = null, $global = true ) {

  if (is_array($tag)) {
    if ($func === false) $global = false;
    foreach ($tag as $this_tag => $this_func) {
      if ( ! in_array($this_tag, CCS_Plugin::$state['disabled_shortcodes']) )
        add_local_shortcode( 'ccs', $this_tag, $this_func, $global );
    }
  } else {
    if ( ! in_array($tag, CCS_Plugin::$state['disabled_shortcodes']) ) {
      add_local_shortcode( 'ccs', $tag, $func, $global );
    }
  }
}

function remove_ccs_shortcode( $tag ) {
  remove_local_shortcode( 'ccs', $tag );
}


function do_ccs_shortcode( $content, $global = true, $in_content_filter = false ) {

  // Store previous state
  $prev = CCS_Plugin::$state['doing_ccs_shortcode'];

  CCS_Plugin::$state['doing_ccs_shortcode'] = true;

  if (!$in_content_filter) {
    $content = apply_filters('doing_ccs_shortcode', $content);
  }

  //$content = CCS_Format::protect_script($content, $global);

  $content = do_local_shortcode( 'ccs', $content, false );

  if ($global) $content = do_shortcode($content);

  // Restore previous state
  CCS_Plugin::$state['doing_ccs_shortcode'] = $prev;

  return $content;
}


if ( function_exists('do_short') ) return;

function do_short( $content = '', $data = array() ) {
  echo get_short( $content, $data );
}

function start_short() { ob_start(); }

function end_short() { echo get_short(); }

function get_short( $content = '', $data = array() ) {

  // $data given as first argument
  if ( is_array($content) ) {
    $data = $content;
    $content = '';
  }

  // Use buffered content
  if ( empty($content) ) $content = ob_get_clean();

  // Pass data to shortcodes with {KEY}
  foreach ($data as $key => $value) {
    $tag = '{' . strtoupper( $key ) . '}';
    $content = str_replace( $tag, $value, $content );
  }

  return do_ccs_shortcode( $content );
}


function ccs_inspect() {

  // Get the name of caller function and class
  $e = new Exception();
  $trace = $e->getTrace();

  $caller = $trace[0];
  $file = (
    (!isset($caller['file'])
      ? ''
      : str_replace(ABSPATH, '', $caller['file'])
        .(isset($caller['line'])
          ? ' on line '.$caller['line'] : ''
        )
    )
  );

  //position 0 would be the line that called this function so we ignore it
  $caller = $trace[1];

  $function = (!empty($caller['class']) ? $caller['class'].'::' : '')
    .$caller['function'];

  echo '<b>'.$function.'</b> &nbsp;<small>'.$file.'</small><br>';

  $args = func_get_args();

  $find = array('[',']','<','>');
  $replace = array('&#91;','&#93;','&lt;','&gt;');

  ?><pre><code><?php
  foreach ($args as $obj) {
    $result = ccs_inspect_replace($find, $replace, $obj);
    if (is_string($result)) echo $result;
    else print_r($result);
  }
  ?></code></pre><?php
}

function ccs_inspect_replace($find, $replace, $obj) {
  if (is_string($obj)) return str_replace($find, $replace, $obj)."\n";
  if (is_bool($obj)) return ($obj?'TRUE':'FALSE')."\n";
  if (is_null($obj) ) return "NULL\n";
  if (is_numeric($obj) ) return $obj."\n";
  if (!is_array($obj) ) return $obj;

  $newObj = array();
  foreach ($obj as $key => $value) {
    $newObj[$key] = ccs_inspect_replace($find, $replace, $value);
  }
  return $newObj;
}
