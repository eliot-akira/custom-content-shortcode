<?php

new CCS_Menu;

class CCS_Menu {

	static $state;

  function __construct() {
		add_action( 'wp_loaded', array( $this, 'register' ) );
    self::$state = array();
		self::$state['is_menu_loop'] = false;
		self::$state['depth'] = 0;
		self::$state['current_menu_names'] = array();
		self::$state['current_menu_item_ids'] = array();
		self::$state['current_menu_object'] = '';
  }

  function register() {
    add_ccs_shortcode( array(
			'loop-menu' => array( $this, 'loop_menu_shortcode' ),
		));

		for ($i=0; $i < 5; $i++) {
			add_local_shortcode('ccs_menu',
				str_repeat('-',$i).'children', array( $this, 'loop_menu_children' ));
		}

		add_filter( 'ccs_loop_add_defaults', array( $this, 'loop_menu_filter_parameters' ) );
		add_filter( 'ccs_loop_before_query', array( $this, 'loop_menu_filter' ),
			$priority = 10, $accepted_args = 3 );
  }


	function loop_menu_filter_parameters( $defaults ) {
	  $defaults['menu'] = '';
		return $defaults;
	}

	function loop_menu_filter( $result, $parameters, $template ) {

		if ( empty($parameters['menu']) ) return $result;
		$parameters = array_filter($parameters);
		$parameters['name'] = $parameters['menu'];
		if ($parameters['name']=='children') {
			$parameters['name'] = self::$state['current_menu_names'][ self::$state['depth'] ];
			$parameters['parent'] = self::$state['current_menu_item_ids'][ self::$state['depth'] ];
			$parameters = array_merge(self::$state['current_menu_parameters'][ self::$state['depth'] ],
				$parameters);
//debug_array($parameters);
		}
		$result = self::loop_menu_shortcode( $parameters, $template );
		if (empty($result)) return false;
		return $result;
	}

	function loop_menu_children( $atts, $content ) {
	  return do_ccs_shortcode('[loop menu=children]'.$content.'[/loop]');
	}

  static function loop_menu_shortcode( $atts, $content ) {

    extract( shortcode_atts( array(
      'name' => '',
      'order' => 'ASC',
      'orderby' => 'menu',
			'count' => '',
      'list' => '',
      'ul_class' => '',
      'li_class' => '',
      'ul_id' => '',
      'li_id' => '',
			'parent' => 'true',
    ), $atts ) );


		if ( empty($name) ) return;
    if ( $orderby == 'menu' ) $orderby = 'menu_order';
		if ( empty($count) ) $count = 99999; // All

    $args = array(
      'order'                  => strtoupper( $order ),
      'orderby'                => $orderby,
      'post_type'              => 'nav_menu_item',
      'post_status'            => 'publish',
      'output'                 => ARRAY_A,
      'output_key'             => 'menu_order',
      'nopaging'               => true,
      'update_post_term_cache' => false
    );

    $items = wp_get_nav_menu_items( $name, $args );

		if ( empty($items) ) return;

    $result = '';

		self::$state['depth']++;
		self::$state['current_menu_names'][ self::$state['depth'] ] = $name;

		$index = 0;
		$final_items = array();

    foreach ($items as $key => $menu_item) {

      $id = @$menu_item->object_id;
			$type = @$menu_item->type;
			$menu_types = array('custom','taxonomy');
			if (in_array($type, $menu_types)) {
				if ($type == 'taxonomy')
					$id = @$menu_item->ID;

				$type = 'type=nav_menu_item ';
			} else {
				$type = '';
			}

			$skip = false;

      if ( empty($id) ) $skip = true;

			if ( !empty($parent) ) {
				$pid = @$menu_item->menu_item_parent;
//debug_array('CHECK PARENT:'.$parent.' == '.$pid.'<br>');
				if ( $parent == 'true' ) {
					if ( $pid != 0 ) $skip = true;
				} elseif ( $pid != $parent ) {
					$skip = true;
				}
			}

			if ( !$skip ) {
				$final_items[] = $menu_item;
      }
    }

		$max = count($final_items);

		foreach ($final_items as $menu_item) {

			$index++;
			if ($index>$count) continue;

			$prevo = self::$state['current_menu_object'];
//debug_array($menu_item);
			$url = $menu_item->url;
			$title = $menu_item->title;
			self::$state['current_menu_object'] = array(
				'title' => $title,
				'title-link' => '<a href="'.$url.'">'.$title.'</a>',
				'url' => $url,
				'id' => $menu_item->object_id,
			);
			self::$state['total_menu_count'][ self::$state['depth'] ] = $max;
			self::$state['menu_index'][ self::$state['depth'] ] = $index;
			self::$state['current_menu_parameters'][ self::$state['depth'] ] = $atts;
			self::$state['current_menu_item_ids'][ self::$state['depth'] ] = $menu_item->object_id;
			self::$state['is_menu_loop'] = true;

      $item_result = do_ccs_shortcode( $content );

			self::$state['current_menu_object'] = $prevo;

      if ($list=='true') {
        $item_result_wrap = '<li';
        if (!empty($li_id)) $item_result_wrap .= ' id="'.$li_id.'"';
        if (!empty($li_class)) $item_result_wrap .= ' class="'.$li_class.'"';
        $item_result_wrap .= '>';

        $item_result = $item_result_wrap . $item_result . '</li>';
      }
      $result .= $item_result;
		}


    if ( $list=='true' && !empty($result) ) {
      $begin = '<ul';
      if (!empty($ul_id)) $begin .= ' id="'.$ul_id.'"';
      if (!empty($ul_class)) $begin .= ' class="'.$ul_class.'"';
      $begin .= '>';

			$result = $begin . $result . '</ul>';
    }


		self::$state['is_menu_loop'] = false;
		self::$state['depth']--;
    return $result;
  }

}
