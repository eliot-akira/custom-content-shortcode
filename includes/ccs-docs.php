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




add_action( 'admin_init', 'ccs_content_settings_register_settings' );
function ccs_content_settings_register_settings() {
	register_setting( 'ccs_content_settings_field', 'ccs_content_settings', 'ccs_content_settings_field_validate' );
	add_settings_section('ccs_content_settings_section', '', 'ccs_content_settings_section_page', 'ccs_content_settings_section_page_name');
	add_settings_field('ccs_content_settings_field_string', 'Custom content settings field', 'ccs_content_settings_field_input', 'ccs_content_settings_section_page_name', 'ccs_content_settings_section');
}

function ccs_content_settings_section_page() {
/*	echo '<p>Main description</p>';  */
}



function ccs_content_settings_field_input() {
/*

	$settings = get_option( 'ccs_content_settings');

		$registration_enabled = isset( $settings['registration'] ) ?
			esc_attr( $settings['registration'] ) : 'on'; // If no setting, then default
	?>

	<tr>
		<td width="200px">
			<input type="checkbox" name="ccs_content_settings[registration]"
				<?php checked( $settings['registration'], 'on' ); ?>
			/>

			<?php echo '&nbsp;&nbsp;Nová registrace'; ?>
		</td>
	</tr>

<?php 

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
 ?>

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

		echo '<style type="text/css">
					.doc-style {
						max-width: 760px; /*margin: 0 auto;*/
						padding-top:10px;
						padding-left:10px;
					}
					.doc-style, .doc-style p {
						font-size: 16px;
						line-height: 1.4em; 
					}
					.doc-style a {
						text-decoration: none;
						color: #000;
					}
					.doc-style a:hover {
						color: #0074A2;
					}
					.doc-style code {
						font-size: 16px;
						padding: 10px 15px;
					line-height: 24px;
					display: block;
					}
					.doc-style h4 {
						font-weight:normal;
						font-style:italic;
					}
					.doc-style ul {
						list-style:disc; padding-left:40px;
					}
		     </style>';
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



function ccs_content_settings_page() {

	/* -- For later, in case an option form is needed
	?>
		<div class="wrap">
		<h2>Form title</h2>
		<form method="post" action="options.php">
		    <?php settings_fields( 'ccs_content_settings_field' ); ?>
		    <?php do_settings_sections( 'ccs_content_settings_section_page_name' ); ?>
		    <?php submit_button(); ?>
		</form>
		</div>
	<?php
	*/

	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'overview';


	$all_tabs = array( 'overview', 'content', 'loop', 'views', 'each', 'gallery',
						'user', 'load', 'mobile', 'ACF', 'etc' );

	?>
		<div class="wrap">
		<h2>Custom Content Shortcode</h2>
		<br>
		<h2 class="nav-tab-wrapper">  
		
		<?php

			foreach ($all_tabs as $tab) {

				$tab_name = ucwords(str_replace('-', ' ', $tab));

				?>
				<a href="?page=ccs_content_shortcode_help&tab=<?php echo $tab; ?>"
					class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>">
						<?php echo $tab_name; ?></a>
				<?php

			}
		?>

		</h2>  

		<div class="doc-style">

			<?php

			/*--- Show the doc file for active tab ---*/

			echo wpautop(
				@file_get_contents(
						dirname(dirname(__FILE__)) .'/docs/' . strtolower($active_tab) . '.html'
					)
			);

			if ( $active_tab == 'overview' ) {

			 	/* Footnote */

			 	?>
				<br><hr><br>

				<div align="center">
					<img src="<?php echo plugins_url();?>/custom-content-shortcode/docs/logo/logo.png"><br><br>
					<b>Custom Content Shortcode</b> is developed by Eliot Akira.<br><br>
					For general questions, please visit the <a href="http://wordpress.org/support/plugin/custom-content-shortcode" target="_blank">WordPress plugin support forum</a>.<br>
					For commercial support and other inquiries, contact <a href="mailto:me@eliotakira.com">me@eliotakira.com</a><br>
				</div>

				<?php

			}

			/*-- End of .doc-style --*/
	?>


	<?php

			if ( $active_tab == 'your site' ) {

				?>

				</div>
				<br><hr>

				<?php include('ccs-docs-site-overview.php');

			} else {


				?>
					</div>
				<?php
				/*-- End of .wrap --*/

			 }
	?>
	</div>
	<?php
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
	<?php

		include('ccs-content-overview.php');

	?>
		</div>
	<?php


}
