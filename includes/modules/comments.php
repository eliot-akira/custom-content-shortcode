<?php

/*---------------------------------------------
 *
 * Comment shortcodes
 * 
 * [comments] - Loop through comments
 * [comment] - Show comment field
 * [comment form] form/template/count
 *
 */

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
			'size' => '96' // default avatar size
		), $atts));

		// In a comments loop?

		$in_loop = isset(self::$state['is_comments_loop']) ? self::$state['is_comments_loop'] : false;

		if ( $in_loop ) {

			// Display comment fields

			$out = null;
			$comment = isset(self::$state['current_comment']) ? self::$state['current_comment'] : null;

			if (empty($comment)) return;

			$fields = array(
				'ID', 'post_ID', 'author', 'author_email', 'author_url', 'date',
				'content', 'content-link', 'user_id', 'avatar', 'count', 'counted',
				'title', 'url', 'post-url', 'title_link', 'author_link', 'link'
			);

			if (empty($atts)) {
				$atts = array('content'); // Default field
			}

      // Check for parameters without value
			if(is_array($atts))	$atts = CCS_Content::get_all_atts( $atts );

			$post_id = $comment->comment_post_ID;

			foreach ($fields as $field) {

				$arg_field = strtolower($field);
				$arg_field = str_replace('_', '-', $field);

				if ($arg_field=='user-id') {
					$field = 'user_id';
        }	else {
					$field = 'comment_'.$field; // name of property in comment object
        }

				// Check first parameter [comment ~]

				if (isset($atts[$arg_field])) {

					switch ($arg_field) {
						case 'title':
							$out = get_the_title($post_id);
						break;

            case 'url':
              $comment_id = $comment->comment_ID;
              $out = get_permalink($post_id).'#comment-'.$comment_id; // Add anchor to comment
            break;

            case 'link':
              $title = get_the_title($post_id);
              $comment_id = $comment->comment_ID;
              $url = get_permalink($post_id).'#comment-'.$comment_id; // Add anchor to comment
            break;

            case 'post-url':
              $out = get_permalink($post_id); // Just the post URL
            break;

            case 'title-link':
            case 'post-link':
							$title = get_the_title($post_id);
							$url = get_permalink($post_id);
							// $out = '<a href="'.$url.'">'.$title.'</a>';
						break;

						case 'author-link':
							$title = isset($comment->comment_author) ? $comment->comment_author : null;
							$url = isset($comment->comment_author_url) ? $comment->comment_author_url : null;
							// $out = '<a href="'.$url.'">'.$title.'</a>';
						break;

						case 'avatar':
							$author_id = $comment->user_id;
							$out = get_avatar( $author_id, $size );
						break;

            case 'count':
              $out = get_comments_number( $post_id );
            break;
            case 'counted':
              $count = get_comments_number( $post_id );
              if ($count == 0) return 'No comments';
              if ($count == 1) return '1 comment';
              $out = $count.' comments';
            break;

						case 'content':
							if (isset($comment->{$field}))
								$out = $comment->{$field};
							if (empty($format)) $format='true'; // Format content by default
						break;


            case 'content-link':
              $comment_id = $comment->comment_ID;
              $url = get_permalink($post_id).'#comment-'.$comment_id; // Add anchor to comment

              if (isset($comment->comment_content)) {
                $out = $comment->comment_content;
              } else {
                $out = '';
              }
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

      if (isset($atts['date'])) {

        if (!empty($date_format)) {
          $out = mysql2date($date_format, $out);
        }
        else { // Default date format under Settings -> General
          $out = mysql2date(get_option('date_format'), $out);
        }
      } elseif ( !empty($out) && !empty($date_format) ) {
				$out = mysql2date($date_format, $out); // date($date_format, strtotime($out));
			} elseif ( $format=='true' ) {
				$out = apply_filters('the_content', $out);
			}

      // Wrap in link after trimming and format
      if (
            isset($atts['title-link']) ||
            isset($atts['post-link']) ||
            isset($atts['author-link']) ||
            isset($atts['link']) )
      {
        if (!empty($title) && !empty($url))
          $out = '<a href="'.$url.'">'.$title.'</a>';
        elseif (!empty($title))
          $out = $title; // no link found
      } elseif (isset($atts['content-link']) && !empty($out) && !empty($url) ) {
        $out = '<a href="'.$url.'">'.$out.'</a>';
      }

			return $out;
		}


    /*---------------------------------------------
     *
     * Comments loop
     *
     */

		if ( !empty($count) || !empty($id) ||
			 ( ($tag=='comments') && !empty($content) ) ) {

			$out = '';
			self::$state['is_comments_loop'] = true;
			if ((empty($count)) || ($count=='all')) $count = 999;
			$atts['number'] = $count;

			if ( CCS_Loop::$state['is_loop'] && empty($id) ) {
				$id = 'this';
			}


			if ($id=='this') {

				$atts['post_id'] = get_the_ID();
				if (empty($atts['post_id'])) return; // No current post ID

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
				'post_id' => '',
				'post_author' => '',
				'post_name' => '',
				'post_parent' => '',
				'post_status' => '',
				'post_type' => '',
				'status' => 'approve',
        'post__not_in' => '',
				'type' => '',
				'user_id' => '',
			);

			$args = array();

      // Aliases
      if ( isset($atts['type']) ) {
        $atts['post_type'] = CCS_Loop::explode_list($atts['type']);
        unset($atts['type']);
      }
      if ( isset($atts['exclude']) ) {
        $atts['post__not_in'] = CCS_Loop::explode_list($atts['exclude']);
        unset($atts['exclude']);
      }

      $taxonomy_filter = false;
      if ( isset($atts['category']) ) {
        $atts['taxonomy'] = 'category';
        $atts['term'] = $atts['category'];
        unset($atts['category']);
      } elseif ( isset($atts['tag']) ) {
        $atts['taxonomy'] = 'tag';
        $atts['term'] = $atts['tag'];
        unset($atts['tag']);
      }

      $max = 999;
      if ( isset($atts['taxonomy']) && isset($atts['term']) ) {
        $taxonomy_filter = true;
        $max = $atts['number'];
        $atts['number'] = $max; // Max posts
        unset($atts['number']);
        $taxonomy = $atts['taxonomy'];
        $terms = CCS_Loop::explode_list($atts['term']);
        unset($atts['taxonomy']);
        unset($atts['term']);
      }

			foreach ($defaults as $key => $value) {
				if (!empty($atts[$key])) {
					if ($key=='status' && $atts[$key]=='all') {
						// Don't set status value
					} else {
						$args[$key] = $atts[$key];
					}
				} elseif (!empty($value)) {
					$args[$key] = $value;
				}
			}

			$comments = get_comments( $args );

      $index = 0;
			// Loop through each comment
			foreach ($comments as $comment) {

        if ($index > $max) break;

        $matches = true;
        if ($taxonomy_filter) {
          $matches = false;
          $pid = $comment->comment_post_ID;
          $post_tax = do_shortcode('[taxonomy '.$taxonomy.' id="'.$pid.'" out="slug"]');
          $post_tax = explode(' ', $post_tax); // Convert to array
          foreach ($terms as $term) {
            if (in_array($term, $post_tax)) {
              $matches = true;
              $index++;
            }
          }
        }

        if ($matches) {
          self::$state['current_comment'] = $comment;
          $out .= do_shortcode( $content );
        }
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
		if( isset( $atts['counted'] ) ) {
			$count = get_comments_number();
			if ($count == 0) return 'No comments';
			if ($count == 1) return '1 comment';
			return $count.' comments';
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
