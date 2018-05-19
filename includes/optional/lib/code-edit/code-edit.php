<?php
/*
Plugin Name: Code Edit
Plugin URI: http://mugen.media
Description:
Version: 0.0.3
Author: Eliot Akira
Author URI: eliotakira.com
*/

class CodeEdit {

  function __construct() {
    add_action('admin_init', array($this, 'admin_init'));
  }

  function admin_init() {

    $enabled = apply_filters( 'code_edit_post_types', array('shortcode') );

    if ( $this->do_editor($enabled) ) {
      add_action('admin_head', array($this, 'admin_head'));
      add_action('admin_footer', array($this, 'admin_footer'));
      add_filter('user_can_richedit', array($this, 'disable_visual_editor'));
    }
  }

  function disable_visual_editor($default) {
    return false;
/*    global $post;
    if ('shortcode' == get_post_type($post)) return false;
    return $default; */
  }

  function admin_head() {
    ?><style><?php

    include 'assets/prism.css';
    include 'assets/codeflask.css';

    ?>
    #postdivrich { display:none !important }
    </style><?php
  }

  function admin_footer() {
    ?><script><?php

    //include 'assets/prism.js';
    include CCS_PATH.'/includes/docs/lib/prism/js/prism.min.js';
    include 'assets/codeflask.js';

    ?>
;(function($) {
  var flask = new CodeFlask
  var $root = $('#postdivrich')
  var $orig = $root.find('textarea')

  // Insert Codeflask
  $root.after('<div id="flask"></div>')
  flask.run('#flask', { language: 'markup' })
  flask.update($orig.val())

  var $title = $('#titlewrap input')
  var $flask = $root.parent().find('#flask')
  var $code = $flask.find('code')
  var $pre = $flask.find('pre')
  var $textarea = $flask.find('textarea')
  var lastHeight = 0

  $textarea[0].spellcheck = false

  function focus(pos) {
    $textarea.focus()
    $textarea[0].selectionStart = pos || 0
    $textarea[0].selectionEnd = pos || 0
  }

  if ( $title.val() != '' ) focus()

  // On TAB from title to content
  $title.on('keydown', function(event) {
    if (event.keyCode===9) {
      focus()
    }
  })

  flask.onUpdate(function(content) {
    syncHeight()
    $orig.val(content)
  })

  $textarea.on('paste', function() {
    console.log('onpaste!')
//    syncHeight(true)
  })

  function syncHeight() {

    var h = $code.outerHeight()

    if (lastHeight===h) return
    lastHeight = h
    $pre.css({ height: h })
//console.log('pre height', h)

    $textarea.css({ height: h })
    $flask.css({ height: h })
//console.log('textarea height', h)
  }

  syncHeight()
})(jQuery)
    </script><?php

  }


  function do_editor( $check_type ) {

    // get_current_screen() doesn't work in all situations (inside admin_init)
    // so implement it manually

    global $pagenow, $_current_single_post_type;

    if (empty($_current_single_post_type)) {

      $post_type = '';

      if ( 'post.php' == $pagenow ) {
        if (!empty($_GET['post'])) {
          $this_post = get_post($_GET['post']);
          $post_type = $this_post->post_type;
        }
      } elseif ( 'post-new.php' == $pagenow ) {
        if (!empty($_GET['post_type'])) {
          $post_type = $_GET['post_type'];
        }
      }
      $_current_single_post_type = $post_type;
    }

    if (is_array($check_type))
      return in_array($_current_single_post_type, $check_type);
    else
      return $_current_single_post_type == $check_type;
  }
}

new CodeEdit;
