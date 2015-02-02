<?php 

new CCS_Shortcake;

class CCS_Shortcake {

	function __construct() {
		add_action( 'plugins_loaded', array($this, 'ui') );
	}

	function ui() {

		if (function_exists('shortcode_ui_register_for_shortcode')) {


			// Get all public post types' labels
			$types = array();

			$type_objects = get_post_types(
				array( 'public' => true ),
				$output = 'objects'
			);

			foreach ( $type_objects as $type_object ) {
				$slug = $type_object->name;
				$label = $type_object->labels->singular_name;

				$types[$slug] = $label;
			}

			shortcode_ui_register_for_shortcode(
				'loop',
				array(

				    // Display label. String. Required.
				    'label' => 'Loop',

				    // Icon/image for shortcode. Optional. src or dashicons-$icon. Defaults to carrot.
				    'listItemImage' => 'dashicons-share-alt',

				    // Available shortcode attributes and default values. Required. Array.
				    // Attribute model expects 'attr', 'type' and 'label'
				    // Supported field types: text, checkbox, textarea, radio, select, email, url, number, and date.
				    'attrs' => array(
				        array(
				            'label'       => 'Parameters',
				            'attr'        => 'type',
				            'type'        => 'select',
				            'options'     => $types,
				        ),
				        array(
				            'label' => 'Template',
				            'attr'  => 'content',
				            'type'  => 'textarea',
				        ),
				    ),
				)
			);
		}
	}
	
}
