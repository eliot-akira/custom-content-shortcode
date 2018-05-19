<?php

/*---------------------------------------------
 *
 * WCK metabox and field shortcodes
 *
 */

new CCS_To_WCK;

class CCS_To_WCK {

	public static $state;

	function __construct() {

		self::$state['is_wck_loaded'] = false;

		self::$state['current_wck_metabox'] = '';
		self::$state['is_wck_metabox_loop'] = false;

		self::$state['is_wck_repeater'] = false;
		self::$state['wck_repeater_id'] = 0;

		self::$state['is_wck_post_field'] = false;
		self::$state['current_wck_post_id'] = 0;


		// Wait until all plugins are loaded
		add_action( 'plugins_loaded', array($this, 'wck_exists') );
	}

	function wck_exists() {

		// Is the WCK plugin loaded?

		if ( function_exists('get_cfc_field') && function_exists('get_cfc_meta') ) {

			self::$state['is_wck_loaded'] = true;

			add_ccs_shortcode(array(
				'metabox'=> array($this, 'wck_metabox_shortcode'),
				'wck-field'=> array($this, 'wck_field_shortcode'),
				'post-field'=> array($this, 'wck_field_shortcode'),
				'wck-repeat'=> array($this, 'wck_repeater_shortcode'),
				'repeater'=> array($this, 'general_repeater_shortcode'),
			));

		}
	}

	function wck_metabox_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'name' => '',
		), $atts ) );

		$out = null;

		if (!empty($name)) {

			self::$state['is_wck_metabox_loop'] = true;
			self::$state['current_wck_metabox'] = $name;

			$out = do_local_shortcode( 'ccs',  $content, true );

			self::$state['current_wck_metabox'] = '';
			self::$state['is_wck_metabox_loop'] = false;
		}
		return $out;
	}

	/*---------------------------------------------
	 *
	 * [wck-field] or [metabox][field][/metabox]
	 *
	 */

	public static function wck_field_shortcode( $atts, $content = null ) {

		extract( shortcode_atts( array(
			'metabox' => '',
			'meta' => '', // Alias to metabox

			'field' => '',
			'name' => '', // Alias to field

			'id' => '',
			'shortcode' => 'false',

			'image' => '',
			'size' => 'full',
			'icon' => '0',
			'attr' => '',
      'debug' => '',
		), $atts ) );

		if (!empty($metabox)) $meta = $metabox;
    $debug = ($debug=='true');

		if ( self::$state['is_wck_repeater'] ) {

			// Inside repeater field

			// Get meta key

			if (empty($meta)) {
				$meta = self::$state['wck_repeater_meta'];
			}

			$key = self::$state['wck_repeater_key'];
			if (!empty(self::$state['wck_repeater_id']))
				$id = self::$state['wck_repeater_id'];

		} elseif ( self::$state['is_wck_metabox_loop'] ) {

			// Inside metabox loop

			if (empty($meta)) {
				$meta = self::$state['current_wck_metabox'];
			}
		}

		if ( !empty($name) ) $field = $name;
		if ( !empty($image) ) $field = $image;
		if ( empty($field) && isset($atts) && ( !empty($atts[0]) ) ) {
			$field = $atts[0]; // First parameter [field field_name]
		}


		$out = null;

		if ( (!empty($meta)) && (!empty($field)) ) {

			ob_start(); // Store output of the_cfc_field in buffer

			// Remove nl2br formatting if shortcode is enabled
			if ($shortcode) {
				remove_filter( 'wck_output_get_field_textarea', 'wck_preprocess_field_textarea');
			}

			if ( self::$state['is_wck_repeater'] ) {

				// In a repeater loop

				if (!empty($id)) $out = get_cfc_field( $meta, $field, $id, $key );
				else $out = get_cfc_field( $meta, $field, false, $key );
			}
			else {

				// Single meta and field

				if (empty($id)) $id = get_the_ID();
        $out = get_cfc_field( $meta, $field, $id );

        if ($debug) ccs_inspect(
          'Post ID: '.$id, 'Meta: '.$meta, 'Field: '.$field, 'Result: '.$out
        );
			}


			/*---------------------------------------------
			 *
			 * Process field value
			 *
			 */

			if ( !empty($image) ) {
				if (isset($out['id'])) {
					// Image field
					$out = wp_get_attachment_image( $out['id'], $size, $icon, $attr );
				}
			}

			if ( is_a($out, 'WP_Post') ) {

				// Post object

				$this_post = $out;
				$out = null;

				// Needs content: [post-field]...[/post-field]
				if ( empty($content) ) {
					return null;
				}

				self::$state['is_wck_post_field'] = true;
				self::$state['current_wck_post_id'] = $this_post->ID;

				$out = do_local_shortcode( 'ccs', $content, true );

				self::$state['is_wck_post_field'] = false;
				self::$state['current_wck_post_id'] = 0;

			}

			if ( is_array($out) ) {
				// Array
				$out = implode(', ', $out);
			}

			if ($shortcode == 'true') {
				$out = do_local_shortcode( 'ccs',  $out, true );

				// Put back nl2br formatting
				add_filter( 'wck_output_get_field_textarea', 'wck_preprocess_field_textarea', 10);
			}
		}
		return $out;
	}

 	function general_repeater_shortcode( $atts, $content ) {

		if (
			( isset($atts['meta']) && !empty($atts['meta']) )
			|| ( isset($atts['metabox']) && !empty($atts['metabox']) )
		) {
			return self::wck_repeater_shortcode( $atts, $content );
		} else {
			if (class_exists('CCS_To_ACF')) {
				return CCS_To_ACF::loop_through_acf_field( $atts, $content );
			}
		}
 	}

	/*---------------------------------------------
	 *
	 * [wck-repeat]
	 *
	 */

	function wck_repeater_shortcode( $atts, $content ) {

		extract( shortcode_atts( array(
			'meta' => '',
			'metabox' => '', // Alias
			'id' => ''
		), $atts ) );

		if (!empty($metabox)) $meta = $metabox;
		if (empty($meta)) return; // Needs metabox name

		$out = null;

		self::$state['is_wck_repeater'] = true;
		self::$state['wck_repeater_meta'] = $meta;

		if (!empty($id)) {
			self::$state['wck_repeater_id'] = $id;
			$metas = get_cfc_meta( $meta, $id );
		}
		else {
			self::$state['wck_repeater_id'] = 0;
			$metas = get_cfc_meta( $meta );
		}

		// Loop through repeater

		foreach ( $metas as $key => $value ) {

			self::$state['wck_repeater_key'] = $key;
			$out[] = do_local_shortcode( 'ccs',  $content, true );
		}

		self::$state['is_wck_repeater'] = false;
		self::$state['wck_repeater_id'] = 0;

		return implode("", $out);
	}

}
