<?php

/*====================================================================================================
 *
 * Create help page under Settings -> Content Shortcodes
 *
 *====================================================================================================*/


// create custom user settings menu
add_action('admin_menu', 'ccs_content_settings_create_menu');

function ccs_content_settings_create_menu() {

	global $ccs_settings_page_hook;

	$ccs_settings_page_hook = add_options_page('Custom Content Shortcode - Documentation', 'Custom Content', 'manage_options', 'ccs_content_shortcode_help', 'ccs_content_settings_page');
	add_dashboard_page( 'Content', 'Content', 'edit_dashboard', 'content_overview',  'ccs_dashboard_content_overview' );
}


global $ccs_settings_saved;
$ccs_settings_saved = false;

/** Remove "Settings saved" message on admin page **/

add_action( 'admin_notices', 'ccs_validation_notice');

function ccs_validation_notice(){
	global $pagenow;
	global $ccs_settings_saved;
	if ($pagenow == 'options-general.php' && $_GET['page'] ==
		'ccs_content_shortcode_help') { 

		if ( (isset($_GET['updated']) && $_GET['updated'] == 'true') ||
			(isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') ) {
	      //this will clear the update message "Settings Saved" totally
			unset($_GET['settings-updated']);
			$ccs_settings_saved = true;
		}
	}
}



function ccs_content_settings_section_page() {
	/* echo '<p>Main description</p>'; */
}

function ccs_content_settings_field() {

	$settings = get_option( 'ccs_content_settings' );

	$move_wpautop = isset( $settings['move_wpautop'] ) ?
		esc_attr( $settings['move_wpautop'] ) : 'off'; // If no setting, then default

	$load_acf_module = isset( $settings['load_acf_module'] ) ?
		esc_attr( $settings['load_acf_module'] ) : 'off'; // If no setting, then default

	$load_bootstrap_module = isset( $settings['load_bootstrap_module'] ) ?
		esc_attr( $settings['load_bootstrap_module'] ) : 'off'; // If no setting, then default

	$load_file_loader = isset( $settings['load_file_loader'] ) ?
		esc_attr( $settings['load_file_loader'] ) : 'off'; // If no setting, then default

	$load_gallery_field = isset( $settings['load_gallery_field'] ) ?
		esc_attr( $settings['load_gallery_field'] ) : 'off'; // If no setting, then default

	$load_mobile_detect = isset( $settings['load_mobile_detect'] ) ?
		esc_attr( $settings['load_mobile_detect'] ) : 'off'; // If no setting, then default

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
			<input type="checkbox" value="on" name="ccs_content_settings[move_wpautop]"
				<?php checked( $move_wpautop, 'on' ); ?>
			/>
			&nbsp;&nbsp;Move post content formatting (wp_autop) to <em>after</em> shortcodes
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



function ccs_content_settings_field_validate($input) {
	// Validate somehow
	return $input;
}


function ccs_is_current_plugin_screen() {
	global $ccs_settings_page_hook;
	$screen = get_current_screen();
	if (is_object($screen) && $screen->id == $ccs_settings_page_hook) {  
        return true;  
    } else {  
        return false;  
    }  
}

function ccs_docs_admin_css() {

	if ( ccs_is_current_plugin_screen() ) {
		wp_enqueue_style( "ccs-docs", CCS_URL."/includes/ccs-docs.css");
	}
}
add_action('admin_head', 'ccs_docs_admin_css');



/*====================================================================================================
 *
 * Display page under Settings -> Custom Content
 *
 *====================================================================================================*/


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
/*
                $customfields[$value] = isset($customfields[$value]) ? $customfields[$value] + 1 : 1;
*/
            }
        }
    endforeach; wp_reset_postdata();
    return $customfields;
}

add_action( 'admin_init', 'ccs_content_settings_register_settings' );
function ccs_content_settings_register_settings() {
	register_setting( 'ccs_content_settings_group', 'ccs_content_settings', 'ccs_content_settings_field_validate' );
	add_settings_section('ccs-settings-section', '<div class="remove-height"></div>', 'ccs_content_settings_section_page', 'ccs_content_settings_section_page_name');
	add_settings_field('ccs-settings', '', 'ccs_content_settings_field', 'ccs_content_settings_section_page_name', 'ccs-settings-section');
}



function ccs_content_settings_page() {

	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'overview';


	$all_tabs = array( 'overview', 'content', 'loop', 'views', 'if', 'each',
						'comment',
						// 'widget',
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
				<br><hr><br>
				<div align="center">
					<img src="<?php echo plugins_url();?>/custom-content-shortcode/docs/logo/logo.png"><br><br>
					<b>Custom Content Shortcode</b> is developed by Eliot Akira.<br><br>
					Please visit the <a href="http://wordpress.org/support/plugin/custom-content-shortcode" target="_blank">WordPress plugin support forum</a> for general questions.<br>
					For commercial support and development, contact <a href="mailto:me@eliotakira.com">me@eliotakira.com</a><br>
				</div>
				<?php
			}

			if ( $active_tab == 'your site' ) {
				?>
				</div>
				<br><hr>
				<?php include('ccs-docs-site-overview.php');
			} else {
				?>
				</div>
				<?php

			/*-- End of .doc-style --*/

			}
	?>
	</div>
	<?php
	/*-- End of .wrap --*/
}


/* Add settings link on plugin page */

add_filter( "plugin_action_links", 'ccs_plugin_settings_link', 10, 4 );
 
function ccs_plugin_settings_link( $links, $file ) {
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


function ccs_dashboard_content_overview() {
	?>
	<div class="wrap">
	<?php include('ccs-content-overview.php'); ?>
	</div>
	<?php
}
