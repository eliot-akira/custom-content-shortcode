<?php

if (!class_exists('CodeEdit'))
  include 'lib/code-edit/code-edit.php';

class Meta_Shortcodes {

  public $shortcodes;
  public $locals = array('php');

  function __construct() {
    add_action('wp_loaded', array($this, 'init') );
  }

  function init() {
    $this->register_post_type();
    $this->create_shortcodes();
}


  function add_locals() {
    foreach ($this->locals as $name) {
      add_ccs_shortcode( $name, array($this, $name.'_shortcode'), false );
    }
  }

  function remove_locals() {
    foreach ($this->locals as $name) {
      remove_ccs_shortcode( $name );
    }
  }

  function php_shortcode($atts, $content) {
    ob_start();
    $vars = CCS_Pass::$vars;
    // This is restricted to users who can edit this post type
    eval($content);
    CCS_Pass::$vars = $vars;
    return ob_get_clean();
  }

  function register_post_type() {

    $single = 'Shortcode';
    $plural = 'Shortcodes';

  	$labels = array(
  		'name'                  => $plural,
  		'singular_name'         => $single,
  		'menu_name'             => $plural,
  		'name_admin_bar'        => $single,
  		'archives'              => $single.' Archives',
  		'parent_item_colon'     => 'Parent '.$single.':',
  		'all_items'             => 'All '.$plural.'',
  		'add_new_item'          => 'Add New '.$single,
  		'add_new'               => 'Add New',
  		'new_item'              => 'New '.$single,
  		'edit_item'             => 'Edit '.$single,
  		'update_item'           => 'Update '.$single,
  		'view_item'             => 'View '.$single,
  		'search_items'          => 'Search '.$single,
  		'not_found'             => 'Not found',
  		'not_found_in_trash'    => 'Not found in Trash',
  		'featured_image'        => 'Featured Image',
  		'set_featured_image'    => 'Set featured image',
  		'remove_featured_image' => 'Remove featured image',
  		'use_featured_image'    => 'Use as featured image',
  		'insert_into_item'      => 'Insert into '.$single,
  		'uploaded_to_this_item' => 'Uploaded to this '.strtolower($single),
  		'items_list'            => $plural.' list',
  		'items_list_navigation' => $plural.' list navigation',
  		'filter_items_list'     => 'Filter '.strtolower($plural).' list',
  	);

  	$args = array(
  		'label'                 => $single,
  		'description'           => 'Meta Shortcodes',
  		'labels'                => $labels,
  		'supports'              => array('title', 'editor'),
  		'hierarchical'          => false,
  		'public'                => true,
  		'show_ui'               => true,
  		'show_in_menu'          => true,
  		'menu_position'         => 20,
  		'show_in_admin_bar'     => false,
  		'show_in_nav_menus'     => false,
  		'can_export'            => true,
  		'has_archive'           => false,
  		'exclude_from_search'   => true,
  		'publicly_queryable'    => false,
  		'capability_type'       => 'page',
      'menu_icon'             => 'dashicons-editor-code',
  	);

  	register_post_type( 'shortcode', $args );
  }


  function create_shortcodes() {

    $posts = get_posts(array(
      'post_type' => 'shortcode',
      'posts_per_page' => -1,
    ));

    $callback = array($this, 'meta_shortcode');

    foreach ($posts as $post) {

      $name = $post->post_title; // post_name

      // Already exists
      if ( isset($this->shortcodes[ $name ]) ) continue;

      $this->shortcodes[ $name ] = $post->post_content;

      add_ccs_shortcode( $name, $callback );
    }
  }


  function meta_shortcode( $atts, $content, $name ) {

    if ( ! isset($this->shortcodes[ $name ]) ) return;

    $template = $this->shortcodes[ $name ];

    $atts['content'] = $content;

    foreach ($atts as $key => $value) {
      $tag = '{'.strtoupper($key).'}';
      $template = str_replace($tag, $value, $template);
    }
    $this->add_locals();
    $template = do_ccs_shortcode($template, false);
    $this->remove_locals();
    return $template;
  }

}

new Meta_Shortcodes;
