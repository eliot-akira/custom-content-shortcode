<?php

/*---------------------------------------------
 *
 * Widget shortcode
 *
 * ** WP core needs patch to prevent PHP notice for get_widget()
 *
 */

new CCS_Widget;

class CCS_Widget {

	function __construct() {
		add_ccs_shortcode( 'widget', array($this, 'do_widget_shortcode') );
	}

	function do_widget_shortcode( $atts ) {

		$default_args = array(
			'name' => '',
			'class' => '',
			'instance' => '',
			'args' => ''
		);

		extract( shortcode_atts( $default_args, $atts ) );
/*
        if ( is_array( $atts ) ) {
            $atts = CCS_Content::get_all_atts( $atts );
        }
*/
		if (empty($instance)) {

			$widget_default_instance = array(
				'title' => '',
				'count' => '',				// Archives widget
				'dropdown' => '',			// Archives, Categories widget
				'hierarchical' => '',		// Categories widget
				'category' => '',			// Links widget
				'description' => '',		// Links widget
				'rating' => '',				// Links widget
				'images' => '',				// Links widget
				'name' => '',				// Links widget
				'sortby' => '',				// Pages widget
				'exclude' => '',			// Pages widget
				'number' => '',				// Recent Posts, Recent Comments widget
				'url' => '',				// RSS widget
				'items' => '',				// RSS widget
				'show_summary' => '',		// RSS widget
				'show_author' => '',		// RSS widget
				'show_date' => '',			// RSS widget
				'taxonomy' => '',			// Tag Cloud widget
				'text' => '',				// Text widget
				'filter' => '',				// Text widget
			);

			// Extract instance parameters

			$instance = array();

			foreach ($widget_default_instance as $key => $value) {

				if (!empty($atts[$key])) {
					switch ($key) {
						case 'show_summary' :
						case 'show_author' :
						case 'show_date' :
							if ($atts[$key]=="true")
								$instance[$key] = "1";
							break;
						default:
							$instance[$key] = $atts[$key];
							break;
					}
				} else {
					// Default values necessary

					if ($name=="rss") {
						if (empty($instance['item']))
							$instance['items'] = 5;
					}



				}
			}
		}

		if (empty($args)) {

			$widget_default_args = array(
				'before_widget' => '',
				'after_widget' => '',
				'before_title' => '',
				'after_title' => '',
			);

			$args = array();

			foreach ($widget_default_args as $key => $value) {
				if (!empty($atts[$key])) {
					$args[$key] = $atts[$key];
				}
			}
		}

		if (!empty($name)) {

			// Get widget class name

			$widget = "WP_Widget_";

			if ($name == "rss") $name = "RSS";
			$widget .= str_replace(" ", "_", ucwords($name)); // Uppercase each word, with underscore between

			ob_start();
			the_widget( $widget, $instance, $args );
			return ob_get_clean();
		}
	}

}
