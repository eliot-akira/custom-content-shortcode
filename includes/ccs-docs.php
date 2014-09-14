<?php

/*====================================================================================================
 *
 * Create documentation under Settings -> Content Shortcodes
 *
 *====================================================================================================*/


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

		global $ccs_settings_page_hook;

		$ccs_settings_page_hook = add_options_page('Custom Content Shortcode - Documentation', 'Custom Content', 'manage_options', 'ccs_content_shortcode_help', array($this, 'content_settings_page'));
		add_dashboard_page( 'Content', 'Content', 'edit_dashboard', 'content_overview',  array($this, 'dashboard_content_overview') );
	}

	function validation_notice(){
		global $pagenow;
		global $ccs_settings_saved;
		$page = isset($_GET['page']) ? $_GET['page'] : null;
		if ($pagenow == 'options-general.php' && $page ==
			'ccs_content_shortcode_help') { 

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
				admin_url( 'admin.php?page=ccs_content_shortcode_help' ) . '">' .
				__( 'Reference', 'ccs_content_shortcode_help' ) . '</a>';
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
	 *=======================================================================*/


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

		$move_wpautop = isset( $settings['move_wpautop'] ) ?
			esc_attr( $settings['move_wpautop'] ) : 'off';

		$shortcode_unautop = isset( $settings['shortcode_unautop'] ) ?
			esc_attr( $settings['shortcode_unautop'] ) : 'off';


		?>
		<tr>
			<td width="760px">
				<input type="checkbox" value="on" name="ccs_content_settings[load_acf_module]"
					<?php checked( $load_acf_module, 'on' ); ?>
				/>
				&nbsp;&nbsp;Load <b>ACF</b> shortcodes
			</td>
		</tr>
		<tr>
			<td width="760px">
				<input type="checkbox" value="on" name="ccs_content_settings[load_bootstrap_module]"
					<?php checked( $load_bootstrap_module, 'on' ); ?>
				/>
				&nbsp;&nbsp;Load <b>Bootstrap</b> shortcodes
			</td>
		</tr>
		<tr>
			<td width="760px">
				<input type="checkbox" value="on" name="ccs_content_settings[load_file_loader]"
					<?php checked( $load_file_loader, 'on' ); ?>
				/>
				&nbsp;&nbsp;Load <b>File Loader</b> module
			</td>
		</tr>
		<tr>
			<td width="760px">
				<input type="checkbox" value="on" name="ccs_content_settings[load_gallery_field]"
					<?php checked( $load_gallery_field, 'on' ); ?>
				/>
				&nbsp;&nbsp;Load <b>Gallery Field</b> module
			</td>
		</tr>	<tr>
			<td width="760px">
				<input type="checkbox" value="on" name="ccs_content_settings[load_mobile_detect]"
					<?php checked( $load_mobile_detect, 'on' ); ?>
				/>
				&nbsp;&nbsp;Load <b>Mobile Detect</b> module
			</td>
		</tr>
		<tr>
			<td width="760px">
				<hr class="setting-section">
				<input type="checkbox" value="on" name="ccs_content_settings[move_wpautop]"
					<?php checked( $move_wpautop, 'on' ); ?>
				/>
				&nbsp;&nbsp;Move post content formatting (wp_autop) to <em>after</em> shortcodes
			</td>
		</tr>
		<tr>
			<td>
				<input type="checkbox" value="on" name="ccs_content_settings[shortcode_unautop]"
					<?php checked( $shortcode_unautop, 'on' ); ?>
				/>
				&nbsp;&nbsp;Use <i>shortcode unautop</i> to remove &lt;p&gt; tags around shortcodes
			</td>
		</tr>

		<tr>
			<td width="760px">
				<hr class="setting-section">
				<input type="checkbox" value="on" name="ccs_content_settings[shortcodes_in_widget]"
					<?php checked( $shortcodes_in_widget, 'on' ); ?>
				/>
				&nbsp;&nbsp;Enable shortcodes inside Text widget
			</td>
		</tr>
	<?php

	/*
		<tr>
			<td width="200px">
				<input type="checkbox" name="ccs_content_settings[option2]"
					<?php checked( $settings['option2'], 'on' ); ?>
				/>

				<?php echo '&nbsp;&nbsp;Něco dalšího'; ?>
			</td>
		</tr>

			<td width="200px">
				<input type="text" size="1"
					id="ampl_settings_field_max_limit"
					name="ampl_settings[max_limit][<?php echo $key; ?>]"
					value="<?php echo $max_number; ?>" />
			</td>
			<td width="200px">
				<input type="radio" value="date" name="ampl_settings[orderby][<?php echo $key; ?>]" <?php checked( 'date', $post_orderby ); ?>/>
				<?php echo 'date&nbsp;&nbsp;'; ?>
				<input type="radio" value="title" name="ampl_settings[orderby][<?php echo $key; ?>]" <?php checked( 'title', $post_orderby ); ?>/>
				<?php echo 'title&nbsp;&nbsp;'; ?>
				<input type="radio" value="menu_order" name="ampl_settings[orderby][<?php echo $key; ?>]" <?php checked( 'menu_order', $post_orderby ); ?>/>
				<?php echo 'menu&nbsp;&nbsp;'; ?>
			</td>
		<?php
	*/
	}



	function content_settings_field_validate($input) {
		// Validate somehow
		return $input;
	}


	function is_current_plugin_screen() {
		global $ccs_settings_page_hook;
		$screen = get_current_screen();
		if (is_object($screen) && $screen->id == $ccs_settings_page_hook) {  
	        return true;  
	    } else {  
	        return false;  
	    }  
	}


	function docs_admin_css() {

		if ( $this->is_current_plugin_screen() ) {
			wp_enqueue_style( "ccs-docs", CCS_URL."/includes/ccs-docs.css");
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
	 *=======================================================================*/
	

	function content_settings_page() {

		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'overview';


		$all_tabs = array( 'overview', 'content', 'loop', 'views', 'if', 'each',
							'comment',
							// 'widget',
							'attach',
							'user', 'load', 'gallery',
							// 'ACF', 'mobile',
							'etc', 'settings' );

		?>
			<div class="wrap">
			<h2 class="plugin-title">Custom Content Shortcode</h2>
			<br>

			<div class="doc-style">

			<h2 class="nav-tab-wrapper">  
			<?php
				$i = 0; $middle = intval(count($all_tabs)/2);
				foreach ($all_tabs as $tab) {

					$i++;
					$tab_name = ucwords(str_replace('-', ' ', $tab));

					?>
					<a href="?page=ccs_content_shortcode_help&tab=<?php echo $tab; ?>"
						class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>">
							<?php echo $tab_name; ?></a>
					<?php

					// if ($i==$middle) echo '<br>&nbsp;&nbsp;&nbsp;'; // Put section break
				}
			?>
			</h2>
			<?php

				if ( $active_tab == 'settings' ) {

					// Settings Page

					?>
					<h3>Settings</h3>
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

					/*--- Show the doc file for active tab ---*/

					echo wpautop(
						@file_get_contents(
								dirname(dirname(__FILE__)) .'/docs/' . strtolower($active_tab) . '.html'
							)
					);
				}

				if ( $active_tab == 'overview' ) {

				 	// Add footnote
				 	?>
					<br><hr>
					<div align="center" class="overview-logo-pad">
						<img src="<?php echo plugins_url();?>/custom-content-shortcode/docs/logo/logo.png">
						<div class="overview-logo-pad"><b>Custom Content Shortcode</b> is developed by Eliot Akira.</div>
						Please visit the <a href="http://wordpress.org/support/plugin/custom-content-shortcode" target="_blank">WordPress plugin support forum</a> for general questions.<br>
						Here is a <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y">donation link</a>, if you'd like to contribute to this plugin.<br>
						For commercial development, contact <a href="mailto:me@eliotakira.com">me@eliotakira.com</a><br>
					</div>
					<hr><br>
					<?php
				}
/*
				if ( $active_tab == 'your site' ) {
					?>
					</div>
					<br><hr>
					<?php include('ccs-docs-site-overview.php');
				} else {
*/
					?>
					</div>
					<?php

				/*-- End of .doc-style --*/

//				}
		?>
		</div>
		<?php
		/*-- End of .wrap --*/
	}


	function dashboard_content_overview() {
		?>
		<div class="wrap">
		<?php include('ccs-content-overview.php'); ?>
		</div>
		<?php
	}

}

new CCS_Docs;

