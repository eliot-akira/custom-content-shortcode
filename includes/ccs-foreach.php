<?php

class ForShortcode {

	function __construct() {

		global $ccs_global_variable;
		$ccs_global_variable['for_loop'] = 'false';

		add_action( 'init', array( &$this, 'register' ) );
	}

	function register() {
		add_shortcode( 'for', array( &$this, 'for_shortcode' ) );
		add_shortcode( 'each', array( &$this, 'each_shortcode' ) );
		add_shortcode( 'for-loop', array( &$this, 'for_loop_status' ) );
	}

	function for_shortcode( $atts, $content = null, $shortcode_name ) {

		global $ccs_global_variable;

		$args = array(
			'each' => '',
			'orderby' => '',
			'order' => '',
			'count' => ''
		);

		extract( shortcode_atts( $args , $atts, true ) );

		$out = '';

		$ccs_global_variable['for_loop'] = 'true';

		if ($each=='tag')
			$each='post_tag';

		/* Loop through taxonomies */

		$taxonomies = get_terms( $each, array(
			'orderby' => $orderby,
			'order' => $order,
			'number' => $count,
			) );

		if (is_array($taxonomies)) {

			$ccs_global_variable['for_each']['type']='taxonomy';
			$ccs_global_variable['for_each']['taxonomy']=$each;

			foreach ($taxonomies as $term_object) {

				$ccs_global_variable['for_each']['id']=$term_object->term_id;
				$ccs_global_variable['for_each']['name']=$term_object->name;
				$ccs_global_variable['for_each']['slug']=$term_object->slug;

				$out .= do_shortcode($content);

			}
		}

		$ccs_global_variable['for_loop'] = 'false';
		$ccs_global_variable['for_each'] = '';

		return $out;
	}

	function each_shortcode( $atts, $content = null, $shortcode_name ) {

		global $ccs_global_variable;

		if (!isset($ccs_global_variable['for_loop']) ||
			($ccs_global_variable['for_loop']=='false'))
				return; // Must be inside a for loop

        if( is_array( $atts ) )
            $atts = array_flip( $atts );

        $out = '';

        if (isset( $atts['id'] ))
        	$out = $ccs_global_variable['for_each']['id'];
        if (isset( $atts['name'] ))
        	$out = $ccs_global_variable['for_each']['name'];
        if (isset( $atts['slug'] ))
        	$out = $ccs_global_variable['for_each']['slug'];

        return $out;
	}

}

$for_shortcode = new ForShortcode;
