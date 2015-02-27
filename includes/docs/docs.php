<?php

/*========================================================================
 *
 * Create documentation under Settings -> Custom Content
 *
 */

class CCS_Docs {

	function __construct() {

		global $ccs_settings_saved;
		$ccs_settings_saved = false;

		// Create custom user settings menu
		add_action('admin_menu', array($this, 'content_settings_create_menu'));

		// Add settings link on plugin page
		add_filter( 'plugin_action_links', array($this, 'plugin_settings_link'), 10, 4 );

		// Remove "Settings saved" message on admin page
		add_action( 'admin_notices', array($this, 'validation_notice'));

		// Documentation CSS
		add_action('admin_head', array($this, 'docs_admin_css'));

		// Register doc sections
		add_action( 'admin_init', array($this, 'register_content_settings' ));

	}

	function content_settings_create_menu() {

		global $ccs_settings_page_hook, $ccs_content_overview_page_hook;

		$ccs_settings_page_hook = add_options_page('Custom Content Shortcode - Documentation', 'Custom Content', 'manage_options', 'ccs_reference', array($this, 'content_settings_page'));
		$ccs_content_overview_page_hook = add_dashboard_page( 'Content', 'Content', 'edit_dashboard', 'content_overview',  array($this, 'dashboard_content_overview') );
	}

	function validation_notice(){
		global $pagenow;
		global $ccs_settings_saved;
		$page = isset($_GET['page']) ? $_GET['page'] : null;
		if ($pagenow == 'options-general.php' && $page ==
			'ccs_reference') { 

			if ( (isset($_GET['updated']) && $_GET['updated'] == 'true') ||
				(isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') ) {
		      //this will clear the update message "Settings Saved" totally
				unset($_GET['settings-updated']);
				$ccs_settings_saved = true;
			}
		}
	}

	function plugin_settings_link( $links, $file ) {
		$plugin_file = 'custom-content-shortcode/custom-content-shortcode.php';
		//make sure it is our plugin we are modifying
		if ( $file == $plugin_file ) {
			$settings_link = '<a href="' .
				admin_url( 'admin.php?page=ccs_reference' ) . '">' .
				__( 'Reference', 'ccs_reference' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}


	function content_settings_section_page() {
		/* echo '<p>Main description</p>'; */
	}


	/*========================================================================
	 *
	 * Settings: input
	 *
	 */


	function content_settings_field() {

		$settings = get_option( 'ccs_content_settings' );

		$load_acf_module = isset( $settings['load_acf_module'] ) ?
			esc_attr( $settings['load_acf_module'] ) : 'off';

		$load_bootstrap_module = isset( $settings['load_bootstrap_module'] ) ?
			esc_attr( $settings['load_bootstrap_module'] ) : 'off';

		$load_file_loader = isset( $settings['load_file_loader'] ) ?
			esc_attr( $settings['load_file_loader'] ) : 'off';

		$load_gallery_field = isset( $settings['load_gallery_field'] ) ?
			esc_attr( $settings['load_gallery_field'] ) : 'off';

		$load_mobile_detect = isset( $settings['load_mobile_detect'] ) ?
			esc_attr( $settings['load_mobile_detect'] ) : 'off';

		$shortcodes_in_widget = isset( $settings['shortcodes_in_widget'] ) ?
			esc_attr( $settings['shortcodes_in_widget'] ) : 'off';

		$raw_shortcode = isset( $settings['raw_shortcode'] ) ?
			esc_attr( $settings['raw_shortcode'] ) : 'off';

		$shortcode_unautop = isset( $settings['shortcode_unautop'] ) ?
			esc_attr( $settings['shortcode_unautop'] ) : 'off';

		$move_wpautop = isset( $settings['move_wpautop'] ) ?
			esc_attr( $settings['move_wpautop'] ) : 'off';

		?>

		<tr>
			<td>
				<input type="checkbox" value="on" name="ccs_content_settings[load_gallery_field]"
					<?php checked( $load_gallery_field, 'on' ); ?>
				/>
				&nbsp;&nbsp;<a href="options-general.php?page=ccs_reference&tab=gallery"><b>Gallery Field</b></a> module
			</td>
		</tr>

		<tr>
			<td>
				<input type="checkbox" value="on" name="ccs_content_settings[load_file_loader]"
					<?php checked( $load_file_loader, 'on' ); ?>
				/>
				&nbsp;&nbsp;<a href="options-general.php?page=ccs_reference&tab=load"><b>File Loader</b></a> module
			</td>
		</tr>

		<tr>
			<td>
				<input type="checkbox" value="on" name="ccs_content_settings[load_acf_module]"
					<?php checked( $load_acf_module, 'on' ); ?>
				/>
				&nbsp;&nbsp;<a href="options-general.php?page=ccs_reference&tab=acf"><b>ACF</b></a> shortcodes
			</td>
		</tr>
		<tr>
			<td>
				<input type="checkbox" value="on" name="ccs_content_settings[load_bootstrap_module]"
					<?php checked( $load_bootstrap_module, 'on' ); ?>
				/>
				&nbsp;&nbsp;<a href="options-general.php?page=ccs_reference&tab=other#bootstrap-navbar"><b>Bootstrap</b></a> shortcodes
			</td>
		</tr>

		<tr>
			<td>
				<input type="checkbox" value="on" name="ccs_content_settings[load_mobile_detect]"
					<?php checked( $load_mobile_detect, 'on' ); ?>
				/>
				&nbsp;&nbsp;<a href="options-general.php?page=ccs_reference&tab=mobile"><b>Mobile Detect</b></a> module
			</td>
		</tr>

		<tr>
			<td>
				<hr style="margin-bottom:20px">
				<input type="checkbox" value="on" name="ccs_content_settings[raw_shortcode]"
					<?php checked( $raw_shortcode, 'on' ); ?>
				/>
				&nbsp;&nbsp;Enable <a href="options-general.php?page=ccs_reference&tab=other#raw"><b>[raw]</b></a> shortcode</i>
			</td>
		</tr>

		<tr>
			<td>
				<input type="checkbox" value="on" name="ccs_content_settings[shortcodes_in_widget]"
					<?php checked( $shortcodes_in_widget, 'on' ); ?>
				/>
				&nbsp;&nbsp;Enable shortcodes inside Text widget
			</td>
		</tr>
<!--
		<tr>
			<td class="grey">
				<hr class="setting-section">
				<input type="checkbox" value="on" name="ccs_content_settings[shortcode_unautop]"
					<?php checked( $shortcode_unautop, 'on' ); ?>
				/>
				&nbsp;&nbsp;Use <i>shortcode unautop</i> to remove &lt;p&gt; tags around shortcodes
			</td>
		</tr>
		<tr>
			<td class="grey">
				<input type="checkbox" value="on" name="ccs_content_settings[move_wpautop]"
					<?php checked( $move_wpautop, 'on' ); ?>
				/>
				&nbsp;&nbsp;Move <i>wp_autop</i> to after shortcodes - <i>No longer recommended</i></i>
			</td>
		</tr>
-->
		<?php

	}



	function content_settings_field_validate($input) {
		// Validate somehow
		return $input;
	}





	function is_current_plugin_screen( $hook = null ) {

		global $ccs_settings_page_hook;

		$screen = get_current_screen();
		$check_hook = empty($hook) ? $ccs_settings_page_hook : $hook;

		if (is_object($screen) && $screen->id == $check_hook) {  
	        return true;  
	    } else {  
	        return false;  
	    }  
	}


	function docs_admin_css() {

		global $ccs_content_overview_page_hook;

		if ( $this->is_current_plugin_screen() ) {
			wp_enqueue_style( "ccs-docs", CCS_URL."/includes/docs/docs.css");
		} elseif ( $this->is_current_plugin_screen($ccs_content_overview_page_hook) ) {
			wp_enqueue_style( "ccs-docs", CCS_URL."/includes/overview/content-overview.css");
		}
	}



/* Unused

	function ccs_get_all_fields_from_post_type( $post_type ) {

		$args = $args = array(
			'post_status' => array('publish','draft','pending','future'),
			'post_type' => $post_type,
			'posts_per_page' => -1,
		);

		$allposts = get_posts($args);

	    foreach ( $allposts as $post ) : setup_postdata($post);
	        $post_id = $post->ID;
	        $fields = get_post_custom_keys($post_id);    // all keys for post as values of array
	        if ($fields) {
	            foreach ($fields as $key => $value) {

	                if ($value[0] != '_') {              // exclude where added by plugin
	                    $customfields[$value] = isset($customfields[$value]) ? $customfields[$value] + 1 : 1;
	                }
	            }
	        }
	    endforeach; wp_reset_postdata();
	    return $customfields;
	}

*/

	function register_content_settings() {
		register_setting( 'ccs_content_settings_group', 'ccs_content_settings', array($this, 'content_settings_field_validate') );
		add_settings_section('ccs-settings-section', '<div class="remove-height"></div>', array($this, 'content_settings_section_page'), 'ccs_content_settings_section_page_name');
		add_settings_field('ccs-settings', '', array($this, 'content_settings_field'), 'ccs_content_settings_section_page_name', 'ccs-settings-section');
	}




	/*========================================================================
	 *
	 * Display each doc section
	 *
	 */
	

	function content_settings_page() {

		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'welcome';


		$all_tabs = array( 'welcome', 'content', 'loop', 'view', 'if', 'each',
							
							// 'widget',
							'attach', 'gallery', 'comment',
							'user', 'load', 
							// 'ACF', 'mobile',
							'other', 'settings' );

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

					?>
					<a href="?page=ccs_reference&tab=<?php echo $tab; ?>"
						class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>">
							<?php echo $tab_name; ?></a>
					<?php

					// if ($i==$middle) echo '<br>&nbsp;&nbsp;&nbsp;'; // Put section break
				}
			?>
			</h2>
			<div class="inner-wrap">
			<?php

				if ( $active_tab == 'settings' ) {

					// Settings Page

					?>
					<h3 align="center">Settings</h3>
					<hr>
					<form method="post" action="options.php">
					    <?php settings_fields( 'ccs_content_settings_group' ); ?>
					    <?php do_settings_sections( 'ccs_content_settings_section_page_name' ); ?>
					    <?php submit_button(); ?>
					</form>
					<?php
					global $ccs_settings_saved;
					if ($ccs_settings_saved) {
						echo '<div class="remove-height"></div><br><br>Settings saved.';
					}

				} else {

					// Show the doc file for active tab

          $doc_file = dirname(__FILE__) .'/html/' . strtolower($active_tab) . '.html';

          if ( ! file_exists($doc_file) ) {
            $doc_file = dirname(__FILE__) .'/html/welcome.html';
            $active_tab = 'welcome';
          }

          echo wpautop( @file_get_contents( $doc_file ) );
				}

				if ( $active_tab == 'welcome' ) {

				 	// Add footnote
				 	?>
					<br><hr>
					<div align="center" class="overview-logo-pad">
						<img src="<?php echo CCS_URL;?>/includes/docs/logo.png">
						<div class="overview-logo-pad"><b>Custom Content Shortcode</b> is developed by Eliot Akira.</div>
						Please visit the <a href="http://wordpress.org/support/plugin/custom-content-shortcode" target="_blank">plugin support forum</a> for questions or feedback.<br>
						If you'd like to contribute to this plugin, here is a <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y">donation link</a>.<br>
						For commercial development, contact <a href="mailto:me@eliotakira.com">me@eliotakira.com</a><br>
					</div>
					<hr><br>
					<?php
				}
					?>
					</div>
					</div><?php	/*-- End of .doc-style --*/

//				}
		?>
		</div>
		<?php
		/*-- End of .wrap --*/
	}


	function dashboard_content_overview() {
		?>
		<div class="wrap">
		<?php include( dirname(dirname(__FILE__)) .'/overview/content-overview.php'); ?>
		</div>
		<?php
	}

}

new CCS_Docs;

