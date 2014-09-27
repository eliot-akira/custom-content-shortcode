<?php

/*====================================================================================================
 *
 * Comment shortcodes - [comment form] form/template/count
 *
 *====================================================================================================*/

new CCS_Comments;

class CCS_Comments {

	public static $state;

	function __construct() {

		add_shortcode('comment', array($this, 'comment_shortcode') );
		add_shortcode('comments', array($this, 'comment_shortcode') );

		self::$state['is_comments_loop'] = false;
	}

	function comment_shortcode( $atts, $content, $tag ) {

		extract(shortcode_atts(array(
			'template' => '',
			'id' => '',
			'count' => '',
			'format' => '',
			'date_format' => '',
			'words' => '',
			'more' => '&hellip;',
			'length' => '',
		), $atts));

		// In a comments loop?

		$in_loop = isset(self::$state['is_comments_loop']) ? self::$state['is_comments_loop'] : false;

		if ( $in_loop ) {

			// Display comment fields

			$out = null;
			$comment = self::$state['current_comment'];

			if (empty($comment)) return;

			$fields = array(
				'ID', 'post_ID', 'author', 'author_email', 'author_url', 'date',
				'content', 'user_id',
				'title', 'url', 'title_link', 'author_link'
			);

			if( is_array( $atts ) )
				$atts = array_flip( $atts ); // check for parameters without value

			$post_id = $comment->comment_post_ID;

			foreach ($fields as $field) {

				$arg_field = strtolower($field);
				$arg_field = str_replace('_', '-', $field);

				if ($arg_field=='user-id')
					$field = 'user_id';
				else
					$field = 'comment_'.$field; // name of property in comment object

				// Check first parameter [comment ~]

				if (isset($atts[$arg_field])) {

					switch ($arg_field) {
						case 'title':
							$out = get_the_title($post_id);
							break;
						case 'url':
							$out = get_permalink($post_id);
							break;
						case 'title-link':
							$title = get_the_title($post_id);
							$url = get_permalink($post_id);
							// $out = '<a href="'.$url.'">'.$title.'</a>';
							break;
						case 'author-link':
							$title = isset($comment->comment_author) ? $comment->comment_author : null;
							$url = isset($comment->comment_author_url) ? $comment->comment_author_url : null;
							// $out = '<a href="'.$url.'">'.$title.'</a>';
							break;
						case 'content':
							if (isset($comment->{$field}))
								$out = $comment->{$field};
							if (empty($format)) $format='true'; // Format content by default
							break;
						default:
							if (isset($comment->{$field}))
								$out = $comment->{$field};
							break;
					}
				}

			}

			if (!empty($words)) {
				$out = wp_trim_words( $out, $words, $more );
			}
			if (!empty($length)) {
				$the_excerpt = $out;
				$the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
				$out = mb_substr($the_excerpt, 0, $length, 'UTF-8');
			}

			// Wrap in link after trimming?
			if (isset($atts['title-link']) || isset($atts['author-link'])) {
				if (!empty($title) && !empty($url))
					$out = '<a href="'.$url.'">'.$title.'</a>';
				elseif (!empty($title))
					$out = $title; // no link found
			}

			if ( !empty($out) && !empty($date_format) ) {
				$out = date($date_format, strtotime($out));
			} elseif ( $format=='true' ) {
				$out = apply_filters('the_content', $out);
			}

			return $out;
		}

		// Start a comments loop?
		if ( !empty($count) || !empty($id) ||
			 ( ($tag=='comments') && !empty($content) ) ) {

			$out = '';
			self::$state['is_comments_loop'] = true;
			if ((empty($count)) || ($count=='all')) $count = 999;
			$atts['number'] = $count;
			if ($id=='this') {
				$atts['post_id'] = get_the_ID();
			} elseif (!empty($id)) {
				$atts['post_id'] = $id;
			}

			// Pass arguments
			$defaults = array(
				'number' => '',
				'offset' => '',
				'orderby' => '',
				'order' => 'DESC',
				'parent' => '',
				'post_id' => 0,
				'post_author' => '',
				'post_name' => '',
				'post_parent' => '',
				'post_status' => '',
				'post_type' => '',
				'status' => 'approve',
				'type' => '',
				'user_id' => '',
			);

			$args = array();
			foreach ($defaults as $key => $value) {
				if (!empty($atts[$key])) {
					$args[$key] = $atts[$key];
				}
			}

			$comments = get_comments( $args );

			// Loop through each comment
			foreach ($comments as $comment) {
				self::$state['current_comment'] = $comment;
				$out .= do_shortcode( $content );
			}
			self::$state['is_comments_loop'] = false;
			return $out;
		}


		if( is_array( $atts ) )
			$atts = array_flip( $atts ); // check for parameters without value


		// Comments template?

		if ( ( ($tag=='comments') || isset( $atts['template'] ) || (!empty($template)))
			&& (empty($id))
			)

		{

			$dir = '';
	/*		if (isset($atts['dir'])) {
				$dir = do_shortcode('[url '.$atts['dir'].']/');
			}
	*/
			if (empty($template)) $template = '/comments.php';
			if (isset($template[0]) && ($template[0]!='/'))
				$template = '/'.$template;

			$file = $dir.$template;
	/*
			echo 'file: '.$file.'<br>';
	// filter 'comments_template' gets this value
			echo 'style: '.STYLESHEETPATH . $file.'<br>';
			echo 'template: '.TEMPLATEPATH . $file .'<br>';
	*/
			$content = self::return_comments_template($dir.$template);

			return $content;
		}

		if( isset( $atts['form'] ) ) {
			$content = self::return_comment_form();
			return $content;
		}
		if( isset( $atts['count'] ) ) {
			return get_comments_number();
		}
		if( isset( $atts['total'] ) ) {
			return CCS_Loop::$state['comment_count'];
		}
	}

	function return_comment_form() {

		ob_start();

		comment_form( $args = array(
			'id_form'           => 'commentform',  // that's the wordpress default value! delete it or edit it ;)
			'id_submit'         => 'commentsubmit',
			'title_reply'       => __( '' ),  // Leave a Reply - that's the wordpress default value! delete it or edit it ;)
			'title_reply_to'    => __( '' ),  // Leave a Reply to %s - that's the wordpress default value! delete it or edit it ;)
			'cancel_reply_link' => __( 'Cancel Reply' ),  // that's the wordpress default value! delete it or edit it ;)
			'label_submit'      => __( 'Post Comment' ),  // that's the wordpress default value! delete it or edit it ;)
				
			'comment_field' =>  '<p><textarea placeholder="" id="comment" class="form-control" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>', 
				
			'comment_notes_after' => ''
		));
		$form = ob_get_clean();
	    return $form;
	}

	function return_comments_template($file) {

		ob_start();
		comments_template($file);
		return ob_get_clean(); 
	}

}
