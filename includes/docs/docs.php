<?php

/*---------------------------------------------
 *
 * Create documentation under Settings -> Custom Content
 *
 */

new CCS_Docs;

class CCS_Docs {

  private static $state;

  function __construct() {

    self::$state['markdown_folder'] = dirname(__FILE__).'/markdown';

    self::$state['settings_saved'] = false;
    self::$state['settings_page_name'] = 'ccs_reference';
    self::$state['settings_page_hook'] = '';

    // Create custom user settings menu
    add_action('admin_menu', array($this, 'content_settings_create_menu'));

    // Register doc sections
    add_action( 'admin_init', array($this, 'register_content_settings' ));

    // Override "Settings saved" message on admin page
    add_action( 'admin_notices', array($this, 'validation_notice'));

    // Documentation CSS
    add_action('admin_head', array($this, 'docs_admin_css'));
    // Documentation JS
    add_action('admin_footer', array($this, 'docs_admin_js'));

    // Add settings link on plugin page
    add_filter( 'plugin_action_links_'.CCS_PLUGIN_BASENAME,
      array($this, 'plugin_settings_link'), 10, 4 );

    add_action( 'init', array($this, 'maybe_paged_permalink') );
  }


  /*---------------------------------------------
   *
   * Set up settings page
   *
   */

  function content_settings_create_menu() {

    self::$state['settings_page_hook'] = add_options_page('Custom Content Shortcode - Documentation', 'Custom Content', 'manage_options', self::$state['settings_page_name'], array($this, 'content_settings_page'));

    self::$state['overview_page_hook'] = add_dashboard_page( 'Content', 'Content', 'edit_dashboard', 'content_overview',  array($this, 'dashboard_content_overview') );
  }


  function register_content_settings() {

    register_setting(
      'ccs_content_settings_group',
      'ccs_content_settings',
      array($this, 'content_settings_field_validate')
    );

    add_settings_section(
      'ccs-settings-section',
      '',
      array($this, 'content_settings_section_page'),
      'ccs_content_settings_section_page_name');

    add_settings_field(
      'ccs-settings', '',
      array($this, 'ccs_settings_section'),
      'ccs_content_settings_section_page_name',
      'ccs-settings-section'
    );
  }

  // Override default notices

  function validation_notice(){

    global $pagenow;

    $page = isset($_GET['page']) ? $_GET['page'] : null;

    if ( $pagenow == 'options-general.php' && $page == self::$state['settings_page_name'] ) {

      if ( (isset($_GET['updated']) && $_GET['updated'] == 'true') ||
        (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') ) {

        // This will clear the update message "Settings Saved" totally
        unset($_GET['settings-updated']);

        self::$state['settings_saved'] = true;
      }
    }
  }

  function content_settings_section_page() {
    /* echo '<p>Main description</p>'; */
  }

  function content_settings_field_validate($input) {
    // Validate somehow
    return $input;
  }

  function is_current_plugin_screen( $hook = null ) {

    $screen = get_current_screen();
    $check_hook = empty($hook) ? self::$state['settings_page_hook'] : $hook;

    if (is_object($screen) && $screen->id == $check_hook) {
          return true;
      } else {
          return false;
      }
  }

  // Add link to settings page in plugins list

  function plugin_settings_link( $links ) {

    $url = admin_url( 'options-general.php?page='.self::$state['settings_page_name'] );
    $settings_link = '<a href="'.$url.'">Reference</a>';

    array_unshift( $links, $settings_link );
    return $links;
  }


  /*---------------------------------------------
   *
   * Docs style and script
   *
   */

  function docs_admin_css() {

    if (is_network_admin()) return;

    if ( $this->is_current_plugin_screen() ) {

      wp_enqueue_style( 'ccs-docs', CCS_URL.'/includes/docs/docs.css',array(),'2.0.7');

      self::load_markdown_and_prism();

    } elseif ( $this->is_current_plugin_screen(self::$state['overview_page_hook']) ) {

      wp_enqueue_style( 'ccs-docs', CCS_URL.'/includes/overview/content-overview.css',
        array(),'1.8.1');
    }
  }


  static function load_markdown_and_prism() {

    wp_enqueue_style( 'prism',
        CCS_URL.'/includes/docs/lib/prism/css/prism.css', array(), '0.0.1' );
    wp_enqueue_script( 'prism',
        CCS_URL.'/includes/docs/lib/prism/js/prism.min.js', array(), '0.0.1', true );

    if (!class_exists('Markdown_Module')) {
      include('lib/markdown/markdown.php');
    }
  }


  /*---------------------------------------------
   *
   * Display each doc section
   *
   */

  function content_settings_page() {

    $default_tab = 'overview';

    $active_tab = isset( $_GET['tab'] ) ? strtolower($_GET['tab']) : $default_tab;

    $lang = isset( $_GET['lang'] ) ? strtolower($_GET['lang']) : ''; // default: English
    $langq = !empty($lang) ? '&lang='.$lang : '';

    // Menu structure

    $all_tabs = array(

      'overview' => 'Overview',
      'start' => 'Getting Started',

      'main' => array(
        'title' => 'Main Features',
        'menu' => array(
          'loop' => '',
          'content' => '',
          'field' => '',
          'taxonomy' => '',
          'posttype' => 'Post type',
          'attach' => 'Attachment',
          'comment' => '',
          'user' => '',
          'menu' => '',
          'url' => 'URL',
        )
      ),

      'advanced' => array(
        'title' => 'Advanced',
        'menu' => array(
          'if' => 'If post..',
          'is' => 'Is user..',
          'paged' => 'Pagination',
          'cache' => '',
          'raw' => '',
          'load' => '',
          'pass' => '',
          'php' => 'PHP',
          'extras' => '',
        )
      ),

      'optional' => array(
        'title' => 'Optional',
        'menu' => array(
          'acf' => 'ACF',
          'gallery' => 'Gallery Field',
          'mobile' => 'Mobile Detect',
          'math' => 'Math',
          'meta-shortcodes' => 'Meta Shortcodes',
          'block' => 'HTML Blocks',
          'bootstrap' => 'Bootstrap',
          'wck' => 'WCK',
        )
      ),

      'settings' => 'Settings',
    );

    // Folders to find markdown files

    $tab_folders = array(
      '/' => array(
        'overview',
        'start',
        'settings'
      ),
    );

    $folders = array('main', 'advanced', 'optional');
    foreach ($folders as $folder) {
      $tab_folders[$folder] = array();
      foreach ($all_tabs[$folder]['menu'] as $slug => $title) {
        $tab_folders[$folder][] = $slug;
      }
    }

    // TODO: Separate this into another file

    ?>
    <div class="wrap" style="opacity:0">

      <h1 class="plugin-title">Custom Content Shortcode</h1>

      <div class="doc-style">
        <h2 class="nav-tab-wrapper">
        <?php

          foreach ($all_tabs as $tab => $tab_title) {

            if ( !is_array($tab_title) ) {

              /*---------------------------------------------
               *
               * Single top menu item
               *
               */

              if (empty($tab_title)) {
                $tab_title = ucwords(str_replace('-', ' ', $tab));
              }

              $active = $active_tab == $tab ? ' nav-tab-active' : '';

              $link =
                '<a href="?page='
                  .self::$state['settings_page_name']
                  .'&tab='.$tab.$langq.'"'
                  .' class="nav-tab'.$active.'">'
                  .$tab_title
                .'</a>';

              echo $link;

            } else {

              /*---------------------------------------------
               *
               * Menu with dropdown
               *
               */

              $sub = $tab_title;
              $sub_menu_items = $sub['menu'];

              $tab_title = $sub['title'];
              if (empty($tab_title)) {
                $tab_title = ucwords(str_replace('-', ' ', $tab));
              }

              $active = isset($sub_menu_items[$active_tab]) ? ' nav-tab-active' : '';

              $link =
                '<a href="?page='
                  .self::$state['settings_page_name']
                  .'&tab='.$tab.$langq.'"'
                  .' class="nav-tab'.$active.'">'
                  .$tab_title
                .'</a>';


              echo '<div class="menu-wrap">';

              echo $link;

              // Dropdown menu

              if (count($sub_menu_items) > 0) {
                echo '<div class="sub-menu">';
                foreach ($sub_menu_items as $submenu => $submenu_title) {
                  if (empty($submenu_title)) {
                    $submenu_title = ucwords(str_replace('-', ' ', $submenu));
                  }
                  echo '<a href="?page='.self::$state['settings_page_name'].'&tab='.$submenu.$langq.'" class="sub-menu-item';
                  echo $submenu == $active_tab ? ' nav-tab-active' : '';
                  echo '">';
                  echo $submenu_title;
                  echo '</a>';
                }
                echo '</div>';
              }

              echo '</div>'; // .menu-wrap

            }

            //echo '&nbsp;'; // Between menu items

          }
        ?>
        </h2>

        <div class="inner-wrap tab-<?php echo $active_tab; ?>"><?php

          // Settings Page
          if ( $active_tab == 'settings' ) {

            ?>
            <div style="">

            <h3>Settings</h3>

            <div style="margin-bottom: -35px"></div>
            <form method="post" action="options.php">
              <?php
                settings_fields( 'ccs_content_settings_group' );
                do_settings_sections( 'ccs_content_settings_section_page_name' );

                self::more_settings();

                submit_button();

                if (self::$state['settings_saved']) {
                  echo '<div class="remove-height"></div><br><br>'
                    .'<center>Settings saved.</center><br>';
                }
              ?>
            </form><?php

            ?>
            </div>
            <?php

          // Changelog
          } elseif ( $active_tab == 'changelog' ) {

            ?>
            <div style="max-width:600px;margin: 0 auto;">
              <h1>Change log</h1>
              <hr>
              <?php
                $changelog = @file_get_contents( CCS_PATH . '/readme.txt' );
                $parts = explode('== Changelog ==', $changelog);
                $changelog = @$parts[1];
                echo Markdown_Module::render( $changelog, false, false );
               ?>
            </div>
            <?php

          // Show the doc file for active tab
          } else {

            foreach ($tab_folders as $folder => $files) {

              if ( in_array($active_tab, $files) ) {

                if ($folder == '/') $folder = '';
                else $folder .= '/';

                $doc_file = self::$state['markdown_folder'].'/'.$folder.$active_tab.'.md';

                if ( ! file_exists($doc_file) ) {
                  $doc_file = self::$state['markdown_folder'].'/'.$default_tab.'.md';
                  $active_tab = $default_tab;
                }

                break;
              }
            }

            if ( $active_tab == 'overview' ) {

              // Escape HTML
              echo Markdown_Module::render( @file_get_contents( $doc_file ), false, true );
            } else {
              echo Markdown_Module::render( @file_get_contents( $doc_file ), false, false );
            }

            // echo wpautop( @file_get_contents( $doc_file ) );

          }

          // if ( $active_tab == $default_tab ) {
            ?>

            <?php
             // Add footnote
            ?><hr>
            <div align="center" class="footer-notice logo-pad">
              <img src="<?php echo CCS_URL;?>/includes/docs/logo.png">
              <p class="no-margin-top">
                <b>Custom Content Shortcode</b> is built by <a href="http://eliotakira.com" target="_blank">Eliot Akira</a>
              </p>
              <p>
                Visit the <a class="bold" href="http://wordpress.org/support/plugin/custom-content-shortcode" target="_blank">plugin forum</a> for complementary support.
                <br>
                Here is a <a class="bold" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y">donation link</a> to contribute to development.
              </p>
            </div>
            <?php

          // }

        ?>
        </div><?php  /*-- End of .inner-wrap --*/ ?>
      </div><?php	/*-- End of .doc-style --*/ ?>
    </div><?php	/*-- End of .wrap --*/

  } // End content_settings_page



  // Content overview section

  function dashboard_content_overview() {

    ?><div class="wrap">
    <?php include( dirname(dirname(__FILE__)) .'/overview/content-overview.php'); ?>
    </div><?php
  }


  /*---------------------------------------------
   *
   * Settings section
   *
   */

  function ccs_settings_section() {

    $settings = get_option( CCS_Plugin::$settings_name );

    foreach (CCS_Plugin::$settings_definitions as $option_name => $def) {

      if (!isset($settings[$option_name])) {
        $settings[$option_name] = 'off';
      }
      ?>
      <tr>
        <td>
          <input
            type="checkbox"
            value="on"
            name="ccs_content_settings[<?php echo $option_name; ?>]"
            <?php checked( $settings[$option_name], 'on' ); ?>
          />&nbsp;&nbsp;
          <?php

          if (!empty($def['tab'])) {

            echo $def['text'];

            ?>&nbsp;
            <a href="options-general.php?page=ccs_reference&tab=<?php echo $def['tab']; ?>" title="Info">
            <span class="dashicons dashicons-format-status"></span>
            </a><?php

          } else {

            echo $def['text'];

          }
          ?>
        </td>
      </tr>
      <?php
    } // Each setting




  } //Settings section


  static function more_settings() {

    $settings = get_option( CCS_Plugin::$settings_name );
    if (!isset($settings['paged_permalink_slug']))
      $settings['paged_permalink_slug'] = '';
    if (!isset($settings['paged_pagination_slug']))
      $settings['paged_pagination_slug'] = '';
    if (!isset($settings['paged_permalink_query']))
      $settings['paged_permalink_query'] = '';
    if (!isset($settings['disable_shortcodes']))
      $settings['disable_shortcodes'] = '';
    ?>
    <h3 class="margin-top-half">Advanced</h3>
    <div class="margin-top-half" style="font-size:12px;margin-top:10px">

      <div class="advanced-field">
        Deactivate shortcodes
        <input type="text"
          name="ccs_content_settings[disable_shortcodes]"
          value="<?php echo $settings['disable_shortcodes']; ?>"
          placeholder="shortcode1, shortcode2, ..."
          >
      </div>
      <hr>
      <div class="advanced-field">
        Permalink slug for <a href="options-general.php?page=ccs_reference&tab=paged#permalink">pagination</a>
        <input type="text"
          name="ccs_content_settings[paged_permalink_slug]"
          value="<?php echo $settings['paged_permalink_slug']; ?>"
          placeholder="post-name, page-name.."
          >
      </div>

      <div class="advanced-field">
        Pagination slug
        <input type="text"
          name="ccs_content_settings[paged_pagination_slug]"
          value="<?php echo $settings['paged_pagination_slug']; ?>"
          placeholder="page"
          >
      </div>

      <div class="advanced-field">
        Default query string
        <input type="text"
          name="ccs_content_settings[paged_permalink_query]"
          value="<?php echo $settings['paged_permalink_query']; ?>"
          placeholder="name=post-name, pagename=page-name.."
          >
      </div>
      <?php
      if ( self::$state['settings_saved'] && !empty($settings['paged_permalink_slug']) ) {
        echo '<b>Saved rewrite rules</b>';
        flush_rewrite_rules();
      }
      ?>
    </div>
    <?php
  }



  static function maybe_paged_permalink() {

    $settings = get_option( CCS_Plugin::$settings_name );
    if ( empty($settings['paged_permalink_slug']) ) return;

    $slugs = explode(',', $settings['paged_permalink_slug']);
    $slugs = array_map( 'trim', $slugs  );
//debug_array($slugs);

    $pagination_slug = empty($settings['paged_pagination_slug']) ?
      'page' : $settings['paged_pagination_slug'];

    if ( !empty($settings['paged_permalink_query']) ) {
      $add_queries = explode(',', $settings['paged_permalink_query']);
      $add_queries = array_map( 'trim', $add_queries  );

    } else $add_queries = array();
//debug_array($add_queries);

    $i = 0;
    foreach ($slugs as $slug) {
      $this_query = isset($add_queries[$i]) ? '&'.$add_queries[$i] : '';
//debug_array(array('^'.$slug.'/page/([0-9]+)?/?$','index.php?lpage=$matches[1]'.$this_query,'top'));
      if (empty($slug)) continue;
      if ($slug=='/') $slug = '';
      add_rewrite_rule(
        '^'.$slug.'/'.$pagination_slug.'/([0-9]+)?/?$', // /page
        'index.php?'.CCS_Paged::$prefix.'=$matches[1]'.$this_query,
        'top'
      );
      $i++;
    }
    add_rewrite_tag('%'.CCS_Paged::$prefix.'%', '([^&]+)');
  }



  /*---------------------------------------------
   *
   * Bit of JS for dropdown menu and section anchors
   *
   */

  function docs_admin_js() {

  if ( !is_network_admin() && $this->is_current_plugin_screen() ) {

?>
<script>
(function($) {

  var $menus = $('.menu-wrap > a');

  $('html').click(function() {
    $menus.parent().removeClass('menu-open');
  });

  $menus.on('click', function( event ) {

    event.stopPropagation();

    var el = this;

    $menus
      .filter(function() { return el !== this })
      .parent().removeClass('menu-open');

    $(this).parent().toggleClass('menu-open');
    return false;
  });

  // Open external links in new tab

  $(document.links).filter(function() {
      return this.hostname != window.location.hostname;
  }).attr('target', '_blank');

  // Automatic anchors
  $('.inner-wrap h3, .inner-wrap h2').each(function() {
    var $el = $(this),
        title = $el.text().toLowerCase().replace(/:|\,|\[|\]/g, '').replace(/\ |\//g, '-');
    $el.before('<a name="'+title+'" class="anchor-with-top-pad">');
  });

})(jQuery);
</script>
<?php

    }

  } // End docs_admin_js

} // CCS_Docs
