<?php

/*---------------------------------------------
 *
 * Bootstrap carousel
 *
 * @todo Separate add-on?
 *
 */

add_filter( 'post_gallery', 'custom_carousel_gallery_shortcode', 10, 4 );

function custom_carousel_gallery_shortcode( $output = '', $atts, $content = false, $tag = false ) {

	/* Define data by given attributes. */
	$shortcode_atts = shortcode_atts( array(
		'ids' => false,
		'type' => '',
		'name' => 'custom-carousel', /* Any name. String will be sanitize to be used as HTML ID. Recomended when you want to have more than one carousel in the same page. Default: custom-carousel. */
		'width' => '',  /* Carousel container width, in px or % */
		'height' => '', /* Carousel item height, in px or % */
		'indicators' => 'before-inner',  /* Accepted values: before-inner, after-inner, after-control, false. Default: before-inner. */
		'control' => 'true', /* Accepted values: true, false. Default: true. */
		'interval' => 5000,  /* The amount of time to delay between automatically cycling an item. If false, carousel will not automatically cycle. */
		'pause' => 'hover', /* Pauses the cycling of the carousel on mouseenter and resumes the cycling of the carousel on mouseleave. */
		'titletag' => 'h4', /* Define tag for image title. Default: h4. */
		'title' => 'false', /* Show or hide image title. Set false to hide. Default: true. */
		'text' => 'false', /* Show or hide image text. Set false to hide. Default: true. */
		'wpautop' => 'true', /* Auto-format text. Default: true. */
		'containerclass' => '', /* Extra class for container. */
		'itemclass' => '', /* Extra class for item. */
		'captionclass' => '' /* Extra class for caption. */
	), $atts );

	extract( $shortcode_atts );

	$name = sanitize_title( $name );

	/* Validate for necessary data */
	if ( isset( $ids )
		and ( ( isset( $type ) and 'carousel' == $type )
			or ( 'carousel-gallery' == $tag )
		)
	) :

		/* Obtain HTML. */
		$output = custom_carousel_get_html_from( $shortcode_atts );

	/* If attributes could not be validated, execute default gallery shortcode function */
	else : $output = '';

	endif;

	return $output;

}



function custom_carousel_get_html_from( $shortcode_atts ) {

	/* Obtain posts array by given ids. Then construct HTML. */

	extract( $shortcode_atts );

	$images = custom_carousel_make_array( $ids );
	$output = '';

	if ( is_array( $images ) and !empty( $images ) ) : $posts = array();

		foreach ( $images as $image_id ) :
			$posts[] = get_post( intval( $image_id ) , ARRAY_A );
		endforeach;

		if ( is_array( $posts ) and !empty( $posts ) ) :
			$output = custom_carousel_make_html_from( $shortcode_atts , $posts );
		endif;

	endif;

	return $output;
}


function custom_carousel_make_html_from( $shortcode_atts , $posts ) {

	extract( $shortcode_atts );

	/* Define width of carousel container */
	$container_style = '';
	if ( $width ) :
		$container_style = 'style="';
		if ( $width ) : $container_style .= 'width:' . $width . ';' ; endif;
		$container_style .= '"';
	endif;

	/* Define height of carousel item */
	$item_style = '';
	if ( $height ) :
		$item_style = 'style="';
		if ( $height ) : $item_style .= 'height:' . $height . ';' ; endif;
		$item_style .= '"';
	endif;

	/* Initialize carousel HTML. */
	$output = '<div id="' . $name . '" class="carousel slide ' . $containerclass . '" ' . $container_style . ' align="center">';

	/* Try to obtain indicators before inner. */
	$output .= ( $indicators == 'before-inner' ) ? custom_carousel_make_indicators_html_from( $posts , $name ) : '' ;

	/* Initialize inner. */
	$output .= '<div class="carousel-inner">';

	/* Start counter. */
	$i = 0;

	/* Process each item into $posts array and obtain HTML. */
	foreach ( $posts as $post ) :

		if ( $post['post_type'] == 'attachment' ) : /* Make sure to include only attachments into the carousel */

			$image = wp_get_attachment_image_src( $post['ID'] , 'full' );

			$class = ( $i == 0 ) ? 'active ' : '';

			$output .= '<div class="' . $class . 'item ' . $itemclass . '" data-slide-no="' . $i . '" ' . $item_style . '>';

			$output .= '<img alt="' . $post['post_title'] . '" src="' . $image[0] . '" />';

			if ( $title != 'false' or $text != 'false' ) :

				$output .= '<div class="carousel-caption ' . $captionclass . '">';

				if ( $title != 'false' ) : $output .= '<'. $titletag .'>' . $post['post_title'] . '</' . $titletag . '>'; endif;

				if ( $text != 'false' ) : $output .= ( $wpautop != 'false' ) ? wpautop( $post['post_excerpt'] ) : $post['post_excerpt'] ; endif;

				$output .= '</div>';

			endif;

			$output .= '</div>';

			$i++;

		endif;

	endforeach;

	/* End inner. */
	$output .= '</div>';

	/* Try to obtain indicators after inner. */
	$output .= ( $indicators == 'after-inner' ) ? custom_carousel_make_indicators_html_from( $posts , $name ) : '' ;

	$output .= ( $control != 'false' ) ? custom_carousel_make_control_html_with( $name ) : '' ;

	/* Try to obtain indicators after control. */
	$output .= ( $indicators == 'after-control' ) ? custom_carousel_make_indicators_html_from( $posts , $name ) : '' ;

	/* End carousel HTML. */
	$output .= '</div>';

	/* Obtain javascript for carousel. */
	$output .= '<script type="text/javascript">// <![CDATA[
jQuery(document).ready( function() { jQuery(\'#' . $name . '\').carousel( { interval : ' . $interval . ' , pause : "' . $pause . '" } ); } );
// ]]></script>';

	return $output;

}


/* Obtain indicators from $posts array. */
function custom_carousel_make_indicators_html_from( $posts , $name ) {

	$output = '<ol class="carousel-indicators">';

	$i = 0;

	foreach ( $posts as $post ) :

		if ( $post['post_type'] == 'attachment' ) : /* Make sure to include only attachments into the carousel */

			$class = ( $i == 0 ) ? 'active' : '';

			$output .= '<li data-target="#' . $name . '" data-slide-to="' . $i . '" class="' . $class . '"></li>';

			$i++;

		endif;

	endforeach;

	$output .= '</ol>';

	return $output;

}


/* Obtain control links. */
function custom_carousel_make_control_html_with( $name ) {

	$output = '<div class="carousel-controls"><a class="carousel-control left" href="#' . $name . '" data-slide="prev">&lsaquo;</a>';
	$output .= '<a class="carousel-control right" href="#' . $name . '" data-slide="next">&rsaquo;</a></div>';

	return $output;

}

/* Obtain array of id given comma-separated values in a string. */
function custom_carousel_make_array( $string ) {

	$array = explode( ',' , $string );
	return $array;

}


/**********************************
 *
 * Bootstrap nav walker
 *
 */


class ccs_bootstrap_navwalker extends Walker_Nav_Menu {

	/**
	 * @see Walker::start_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul role=\"menu\" class=\" dropdown-menu\">\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		/**
		 * Dividers, Headers or Disabled
	         * =============================
		 * Determine whether the item is a Divider, Header, Disabled or regular
		 * menu item. To prevent errors we use the strcasecmp() function to so a
		 * comparison that is not case sensitive. The strcasecmp() function returns
		 * a 0 if the strings are equal.
		 */
		if (strcasecmp($item->attr_title, 'divider') == 0 && $depth === 1) {
			$output .= $indent . '<li role="presentation" class="divider">';
		} else if (strcasecmp($item->title, 'divider') == 0 && $depth === 1) {
			$output .= $indent . '<li role="presentation" class="divider">';
		} else if (strcasecmp($item->attr_title, 'dropdown-header') == 0 && $depth === 1) {
			$output .= $indent . '<li role="presentation" class="dropdown-header">' . esc_attr( $item->title );
		} else if (strcasecmp($item->attr_title, 'disabled') == 0) {
			$output .= $indent . '<li role="presentation" class="disabled"><a href="#">' . esc_attr( $item->title ) . '</a>';
		} else {

			$class_names = $value = '';

			$classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;

			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );

			if($args->has_children) {	$class_names .= ' dropdown'; }
			if(in_array('current-menu-item', $classes)) { $class_names .= ' active'; }

			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

			$output .= $indent . '<li' . $id . $value . $class_names .'>';

			$atts = array();
			$atts['title']  = ! empty( $item->title ) 	   ? $item->title 	   : '';
			$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
			$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';

			//If item has_children add atts to a
			if($args->has_children && $depth === 0) {
				$atts['href']   		= '#';
				$atts['data-toggle']	= 'dropdown';
				$atts['class']			= 'dropdown-toggle';
			} else {
				$atts['href'] = ! empty( $item->url ) ? $item->url : '';
			}

			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( ! empty( $value ) ) {
					$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			$item_output = $args->before;

			/*
			 * Glyphicons
			 * ===========
			 * Since the the menu item is NOT a Divider or Header we check the see
			 * if there is a value in the attr_title property. If the attr_title
			 * property is NOT null we apply it as the class name for the glyphicon.

			if(! empty( $item->attr_title )){
				$item_output .= '<a'. $attributes .'><span class="glyphicon ' . esc_attr( $item->attr_title ) . '"></span>&nbsp;';
			} else {
				$item_output .= '<a'. $attributes .'>';
			}
			 */

			$item_output .= '<a'. $attributes .'>';
			$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
			$item_output .= ($args->has_children && $depth === 0) ? ' <span class="caret"></span></a>' : '</a>';
			$item_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}

	/**
	 * Traverse elements to create list from elements.
	 *
	 * Display one element if the element doesn't have any children otherwise,
	 * display the element and its children. Will only traverse up to the max
	 * depth and no ignore elements under that depth.
	 *
	 * This method shouldn't be called directly, use the walk() method instead.
	 *
	 * @see Walker::start_el()
	 * @since 2.5.0
	 *
	 * @param object $element Data object
	 * @param array $children_elements List of elements to continue traversing.
	 * @param int $max_depth Max depth to traverse.
	 * @param int $depth Depth of current element.
	 * @param array $args
	 * @param string $output Passed by reference. Used to append additional content.
	 * @return null Null on failure with no changes to parameters.
	 */

	function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        if ( !$element ) {
            return;
        }

        $id_field = $this->db_fields['id'];

        //display this element
        if ( is_object( $args[0] ) ) {
           $args[0]->has_children = ! empty( $children_elements[$element->$id_field] );
        }

        parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
    }
}

/*
 * Bootstrap navwalker shortcode
 *
 */

function custom_bootstrap_navbar( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'menu' => null, 'navclass' => null,
		), $atts ) );

	$menu_args = array (
			'menu' => $menu,
			'echo' => false,
			'depth' => 2,
			'container' => false,
			'menu_class' => 'nav navbar-nav',
			'fallback_cb' => 'ccs_bootstrap_navwalker::fallback',
			'walker' => new ccs_bootstrap_navwalker(),
		);

		if( $navclass=='' ) {
			$navclass = "top-nav";
		}

		$output = '<nav class="navbar navbar-default '
				. $navclass . '" role="navigation">';

		// Brand and toggle get grouped for better mobile display -->
		$output .= '
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>';
		if($content!='') {
			$output .= '<a class="navbar-brand" href="' . get_site_url() . '">' . do_shortcode($content) .
			'</a>';
		}
		$output .= '</div>

		<div class="collapse navbar-collapse navbar-ex1-collapse">';

		$output .= wp_nav_menu( $menu_args ) . '</div></nav>';

    return $output;
}

add_ccs_shortcode('navbar', 'custom_bootstrap_navbar');
