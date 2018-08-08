<?php
/*
Plugin Name: Custom Content Shortcode
Plugin URI: https://wordpress.org/plugins/custom-content-shortcode/
Description: Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas
Version: 3.7.8
Shortcodes: loop, content, field, taxonomy, if, for, each, comments, user, url, load
Author: Eliot Akira
Author URI: https://eliotakira.com
License: GPL2
*/

if (!class_exists('CCS_Plugin')) :

define('CCS_PATH', dirname(__FILE__));
define('CCS_URL', untrailingslashit(plugins_url('/',__FILE__)));
define('CCS_PLUGIN_BASENAME', plugin_basename(__FILE__));

class CCS_Plugin {

  static $settings;
  static $settings_name;
  static $settings_definitions;
  static $state = array(
    'doing_ccs_shortcode' => false,
  );

  function __construct() {

    $this->load_settings();
    $this->load_main_modules();
    $this->load_optional_modules();

    self::$state['original_post_id'] = 0;

    add_action('init',array($this,'init'));
  }

  function init() {
    $this->setup_wp_filters();
  }


  /*---------------------------------------------
   *
   * Load settings
   *
   */

  function load_settings() {

    self::$settings_name = 'ccs_content_settings';
    self::$settings = get_option( self::$settings_name );

    self::$settings_definitions = array(

      'load_acf_module' => array(
        'module' => 'acf',
        'default' => 'on',
        'tab' => 'acf',
        'text' => '<b>ACF</b> shortcodes',
      ),
      'load_bootstrap_module' => array(
        'module' => 'bootstrap',
        'default' => 'off',
        'tab' => 'bootstrap',
        'text' => '<b>Bootstrap</b> shortcodes',
      ),
      'load_file_loader' => array(
        'module' => 'load',
        'default' => 'on',
        'tab' => 'load',
        'text' => '<b>File Loader</b> module',
      ),
      'load_gallery_field' => array(
        'default' => 'on',
        'module' => 'gallery',
        'tab' => 'gallery',
        'text' => '<b>Gallery Field</b> module',
      ),
      'block_shortcode' => array(
        'default' => 'off',
        'module' => 'block',
        'tab' => 'block',
        'text' => '<b>HTML block</b> shortcodes',
      ),
      'load_math_module' => array(
        'default' => 'off',
        'module' => 'math',
        'tab' => 'math',
        'text' => '<b>Math</b> module',
      ),
      'load_meta_shortcodes_module' => array(
        'default' => 'off',
        'module' => 'meta-shortcodes',
        'tab' => 'meta-shortcodes',
        'text' => '<b>Meta Shortcodes</b> module',
      ),
      'load_mobile_detect' => array(
        'default' => 'off',
        'module' => 'mobile',
        'tab' => 'mobile',
        'text' => '<b>Mobile Detect</b> module',
      ),
      'raw_shortcode' => array(
        'default' => 'off',
        'module' => 'raw',
        'tab' => 'raw',
        'text' => '<b>[raw]</b> shortcode',
      ),
      'shortcodes_in_widget' => array(
        'default' => 'on',
        'module' => '',
        'tab' => '',
        'text' => 'Enable shortcodes in Text widget',
      ),
      'shortcodes_in_widget_title' => array(
        'default' => 'off',
        'module' => '',
        'tab' => '',
        'text' => 'Enable shortcodes in widget title',
      ),
      'shortcodes_in_excerpt' => array(
        'default' => 'off',
        'module' => '',
        'tab' => '',
        'text' => 'Enable shortcodes in <code>the_excerpt()</code>',
      ),
    );

    if ( self::$settings === false ) {

      self::$settings = array();

      foreach (self::$settings_definitions as $option_name => $def) {
        self::$settings[$option_name] = $def['default'];
      }

      update_option( self::$settings_name, self::$settings );
    }

    if ( isset(self::$settings['disable_shortcodes']) ) {
      self::$state['disabled_shortcodes'] =
        array_map( 'trim', explode(',', self::$settings['disable_shortcodes']) );
    } else {
      self::$state['disabled_shortcodes'] = array();
    }
  }


  /*---------------------------------------------
   *
   * Load main and optional modules
   *
   */

  function load_module( $module ) {

    include_once ( CCS_PATH.'/includes/'.$module.'.php' );
  }

  function load_main_modules() {

    $modules = array(
      'core/global',        // Global helper functions
      'core/local-shortcodes', // Local shortcodes
      'core/content',       // Content shortcode
      'core/loop',          // Loop shortcode
      'docs/docs',          // Documentation under Settings -> Custom Content
      'modules/attached',   // Attachment loop
      'modules/cache',      // Cache shortcode
      'modules/comments',   // Comments shortcode
      'modules/foreach',    // For/each loop
      'modules/format',     // Format shortcodes: br, p, x, clean, direct, format
      'modules/if',         // If shortcode
      'modules/menu',       // Loop menu
      'modules/paging',     // Pagination shortcode
      'modules/pass',       // Pass shortcode
      'modules/related',    // Related posts loop
      'modules/url',        // URL shortcode
      'modules/user',       // User shortcodes
      'optional/wck',       // WCK support

      //'optional/widget'     // Widget shortcode
    );

    foreach ($modules as $module) {
      $this->load_module( $module );
    }
  }

  /*---------------------------------------------
   *
   * Optional modules
   *
   */

  function load_optional_modules() {

    foreach (self::$settings_definitions as $option_name => $def) {

      if (
          !empty($def['module']) &&
          isset(self::$settings[ $option_name ]) &&
          self::$settings[ $option_name ]=='on'
        ) {

        $this->load_module( 'optional/'.$def['module'] );
      }
    }
  }


  /*---------------------------------------------
   *
   * Set up WP filters
   *
   */

  function setup_wp_filters() {

    $settings = self::$settings;


    // Render plugin shortcodes after wpautop but before do_shortcode
    // Added after WP 4.2.3 changed Shortcode API
    add_filter( 'the_content', array($this, 'ccs_content_filter'), 11 );
    remove_filter( 'the_content', 'do_shortcode', 11 );
    add_filter( 'the_content', 'do_shortcode', 12 );
    add_action( 'doing_it_wrong_run', array($this, 'disable_nonexistent_shortcode_error'), 99 );


    /*---------------------------------------------
     *
     * Enable shortcodes in Text widget
     *
     */

    if ( isset( $settings['shortcodes_in_widget'] ) &&
      $settings['shortcodes_in_widget'] == "on" ) {

      // Before 10, in case of other theme or plugin
      add_filter('widget_text', array($this, 'ccs_content_filter'), 9 );
      add_filter('widget_text', 'do_shortcode', 11 );
    }

    // Enable shortcodes in widget title

    if ( isset( $settings['shortcodes_in_widget_title'] ) &&
      $settings['shortcodes_in_widget_title'] == "on" ) {

      // Before 10, in case of other theme or plugin
      add_filter('widget_title', array($this, 'ccs_content_filter'), 9 );
      add_filter('widget_title', 'do_shortcode', 11 );
    }

    /*---------------------------------------------
     *
     * Enable shortcodes in the_excerpt()
     *
     */

    if ( isset( $settings['shortcodes_in_excerpt'] ) &&
      $settings['shortcodes_in_excerpt'] == "on" ) {

      remove_filter( 'get_the_excerpt', 'wp_trim_excerpt' );
      add_filter( 'get_the_excerpt', array($this, 'do_shortcode_before_excerpt') );
    }

    // Exempt [loop] from wptexturize()
    add_filter( 'no_texturize_shortcodes',
      array( $this, 'shortcodes_to_exempt_from_wptexturize') );

    // Support for Beaver Themer - TODO: Separate to its own module
    add_filter('fl_theme_builder_before_parse_shortcodes', array($this, 'ccs_content_filter'), 9);

  }


  function do_shortcode_before_excerpt($text = '') {
    $raw_excerpt = $text;
    if ( empty($text) ) {
      $text = wp_trim_words(
        apply_filters('the_content', get_the_content()),
        apply_filters('excerpt_length', 55),
        apply_filters('excerpt_more', ' ' . '[...]')
      );
    }
    return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
  }

  function disable_nonexistent_shortcode_error( $function ) {
    if ($function == 'do_shortcode_tag') {
      add_filter('doing_it_wrong_trigger_error', array($this, 'return_false'), 99 );
    } else {
      remove_filter('doing_it_wrong_trigger_error', array($this, 'return_false'), 99 );
    }
  }

  function return_false() { return false; }

  function shortcodes_to_exempt_from_wptexturize($shortcodes){
    $shortcodes[] = 'loop';
    return $shortcodes;
  }


  static function ccs_content_filter( $content ) {

    // Save reference to global post
    // Better support for filtering content of other plugins
    global $post; $_ = $post;

    $content = do_ccs_shortcode($content, $global = false, $filter = true);

    $post = $_; // Restore reference

    // This gets passed to do_shortcode
    return $content;
  }

  static function add( $tag, $func = null, $global = true ) {
    add_ccs_shortcode( $tag, $func, $global );
  }

}

new CCS_Plugin;

endif;
