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

    self::$state['settings_saved'] = false;
    self::$state['settings_page_name'] = 'ccs_reference';

		// Create custom user settings menu
		add_action('admin_menu', array($this, 'content_settings_create_menu'));

    // Register doc sections
    add_action( 'admin_init', array($this, 'register_content_settings' ));

    // Override "Settings saved" message on admin page
    add_action( 'admin_notices', array($this, 'validation_notice'));

		// Documentation CSS
		add_action('admin_head', array($this, 'docs_admin_css'));

    // Add settings link on plugin page
    add_filter( 'plugin_action_links_'.CCS_PLUGIN_BASENAME,
      array($this, 'plugin_settings_link'), 10, 4 );
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
      '<div class="remove-height"></div>',
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

		      //this will clear the update message "Settings Saved" totally
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

    $settings_link = '<a href="' .
      admin_url( 'admin.php?page='.self::$state['settings_page_name'] ) . '">'
      . 'Reference</a>';
    array_unshift( $links, $settings_link );

    return $links;
  }

  /*---------------------------------------------
   *
   * Docs CSS
   *
   */
  
	function docs_admin_css() {

		if ( $this->is_current_plugin_screen() ) {

			wp_enqueue_style( 'ccs-docs', CCS_URL.'/includes/docs/docs.css',array(),'2.0.6');

		} elseif ( $this->is_current_plugin_screen(self::$state['overview_page_hook']) ) {

			wp_enqueue_style( 'ccs-docs', CCS_URL.'/includes/overview/content-overview.css',
        array(),'1.8.1');
		}
	}


	/*---------------------------------------------
	 *
	 * Display each doc section
	 *
	 */

	function content_settings_page() {

		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'welcome';

		$all_tabs = array(
      'welcome', 'start', 'content', 'loop', 'if', 'each',							
      'attach', 'gallery', 'comment', 'user', 'load', 'other', 'settings' 
			// 'ACF', 'mobile', 'widget',
    );

    ?>
    <div class="wrap" style="opacity:0">
      <h1 class="plugin-title">Custom Content Shortcode</h1>
    	<br>
    	<div class="doc-style">
    		<h2 class="nav-tab-wrapper">  
    		<?php
    			$i = 0; $middle = intval(count($all_tabs)/2);
    			foreach ($all_tabs as $tab) {

    				$i++;
    				$tab_name = ucwords(str_replace('-', ' ', $tab));

?><a href="?page=<?php echo self::$state['settings_page_name']; ?>&tab=<?php echo $tab; ?>"
		class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>">
<?php echo $tab_name; ?></a>&nbsp;<?php

    				// if ($i==$middle) echo '<br>&nbsp;&nbsp;&nbsp;'; // Put section break
    			}
    		?>
    		</h2>

    		<div class="inner-wrap"><?php

          // Settings Page
    			if ( $active_tab == 'settings' ) {

     				?><h3 align="center">Settings</h3>
    				<hr>
    				<form method="post" action="options.php">
    				    <?php settings_fields( 'ccs_content_settings_group' ); ?>
    				    <?php do_settings_sections( 'ccs_content_settings_section_page_name' ); ?>
    				    <?php submit_button(); ?>
    				</form><?php

    				if (self::$state['settings_saved']) {
    					echo '<div class="remove-height"></div><br><br>Settings saved.';
    				}

          // Show the doc file for active tab
    			} else {

            $doc_file = dirname(__FILE__) .'/html/' . strtolower($active_tab) . '.html';

            if ( ! file_exists($doc_file) ) {
              $doc_file = dirname(__FILE__) .'/html/welcome.html';
              $active_tab = 'welcome';
            }

            echo wpautop( @file_get_contents( $doc_file ) );
    			}

    			if ( $active_tab == 'welcome' ) {

    			 	// Add footnote
    			 	?><br><hr>
    				<div align="center" class="overview-notice overview-logo-pad">
    					<img src="<?php echo CCS_URL;?>/includes/docs/logo.png">
    					<div class="overview-logo-pad"><b>Custom Content Shortcode</b> is developed by Eliot Akira.</div>
    					Please visit the <a href="http://wordpress.org/support/plugin/custom-content-shortcode" target="_blank">plugin support forum</a> for questions or feedback.<br>
    					If you'd like to contribute to this plugin, here is a <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y">donation link</a>.<br>
    					For commercial development, contact <a href="mailto:me@eliotakira.com">me@eliotakira.com</a><br>
    				</div>
    				<hr><br><?php
    			}

    		?>
    		</div><?php  /*-- End of .inner-wrap --*/ ?>
    	</div><?php	/*-- End of .doc-style --*/ ?>
    </div><?php	/*-- End of .wrap --*/

	}

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

          echo $def['text'];

          if (!empty($def['tab'])) {
            ?>&nbsp;<a href="options-general.php?page=ccs_reference&tab=<?php echo $def['tab']; ?>">
            <span class="dashicons dashicons-visibility" title="Info"></span>
            </a><?php
          } ?>

        </td>
      </tr>
      <?php
    } // Each setting
  } //Settings section

} // CCS_Docs
