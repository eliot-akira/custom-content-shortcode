<?php

/*---------------------------------------------
 *
 * Comment shortcodes
 *
 * [comments] - Loop through comments
 * [comment] - Show comment field
 *
 */

new CCS_Comments;

class CCS_Comments {

  public static $state;

  function __construct() {

    add_local_shortcode( 'ccs', 'comments', array($this, 'comments_shortcode'), true );
    add_local_shortcode( 'ccs', 'comment', array($this, 'comment_shortcode'), true );

    add_local_shortcode( 'ccs', 'comment-form', array($this, 'comment_form_shortcode'), true );

    add_local_shortcode( 'comment-form',
      'input', array($this, 'comment_form_input_shortcode') );
    add_local_shortcode( 'comment-form',
      'option', array($this, 'comment_form_input_shortcode') );

    self::$state['inputs'] = array(
      'author', 'email', 'url',
      'comment',
      'submit','cancel','reply','reply_to','login','logged-in','before','after'
    );

    self::$state['is_comments_loop'] = false;
    self::$state['current_comment'] = '';

  }


  /*---------------------------------------------
   *
   * [comments] - Loop through comments
   *
   */

  function comments_shortcode( $atts, $content ) {

    if ( empty($content) ) return;

    extract(shortcode_atts(array(
      'type' => '',
      'count' => 'all',
      'status' => '',
      'id' => '',
      'include' => '',
      'exclude' => '',
      'format' => '',
      'words' => '',
      'length' => '',
      'category' => '',
      'tag' => '',
      'taxonomy' => '',
      'term' => '',

      'offset' => '',
      'orderby' => '',
      'order' => 'DESC',
      'parent' => '',

      'author' => '',
      'name' => '',
      'status' => 'approve',

      'user_id' => ''

    ), $atts));


    // Prepare comments query

    $args = array();

    // Comments count

    $args['number'] = $count=='all' ? 9999 : $count;

    // By post ID

    // If inside [loop] then target current post
    if ( empty($id) && CCS_Loop::$state['is_loop'] ) $id = 'this';

    if ( !empty($id) ) {

      if ( $id=='this' ) {
        $id = get_the_ID();
        if ( empty( $id ) ) return; // No current post ID
      }

      $args['post_id'] = $id;
    }

    // By post type

    if ( !empty( $type ) ) {
      $args['post_type'] = CCS_Loop::explode_list( $type );
    }
    if ( !empty( $include ) ) {
      $args['post__in'] = CCS_Loop::explode_list( $include );
    }
    if ( !empty( $exclude ) ) {
      $args['post__not_in'] = CCS_Loop::explode_list( $exclude );
    }

    // Hmm..
    if ( !empty( $offset ) ) $args['offset'] = $offset;
    if ( !empty( $orderby ) ) $args['orderby'] = $orderby;
    if ( !empty( $order ) ) $args['order'] = $order;
    if ( !empty( $parent ) ) $args['post_parent'] = CCS_Loop::explode_list( $parent );
    if ( !empty( $author ) ) $args['post_author'] = CCS_Loop::explode_list( $author );
    if ( !empty( $name ) ) $args['name'] = $name;
    if ( !empty( $status ) && $status != 'all' ) $args['status'] = $status;
    if ( !empty( $user_id ) ) $args['user_id'] = CCS_Loop::explode_list( $user_id );

    // Filter by taxonomy

    $taxonomy_filter = false;

    if ( !empty($category) ) {
      $taxonomy = 'category';
      $term = $category;
    } elseif ( !empty($tag) ) {
      $taxonomy = 'tag';
      $term = $tag;
    }

    if ( !empty($taxonomy) && !empty($term) ) {
      $taxonomy_filter = true;
      $terms = CCS_Loop::explode_list($term);
    }


    /*---------------------------------------------
     *
     * Init loop
     *
     */

    self::$state['is_comments_loop'] = true;
    $index = 0;
    $max = $args['number'];
    $out = '';

    if ( isset($atts['debug']) && $atts['debug']=='true' ) {
      ob_start();
      echo 'Comment query: <pre>';
      print_r($args);
      echo '</pre>';
      $out = ob_get_clean();
    }

    // Get comments
    $comments = get_comments( $args );

    // Loop through each comment

    foreach ($comments as $comment) {

      if ( $index > $max ) break;

      $matches = true;

      if ( $taxonomy_filter ) {
        $matches = false;
        $pid = $comment->comment_post_ID;
        $post_tax = do_shortcode('[taxonomy '.$taxonomy.' id="'.$pid.'" out="slug"]');
        $post_tax = explode(' ', $post_tax); // Convert to array
        foreach ($terms as $term) {
          if ( in_array( $term, $post_tax ) ) {
            $matches = true;
          }
        }
      }

      if ( $matches ) {
        $index++;
        self::$state['current_comment'] = $comment;
        $out .= do_local_shortcode( 'ccs', $content, true  );
      }
    }


    // Close loop

    self::$state['is_comments_loop'] = false;
    self::$state['current_comment'] = '';
    return $out;

  } // comments_shortcode



  /*---------------------------------------------
   *
   * [comment] - Show comment field
   *
   */

  function comment_shortcode( $atts, $content ) {

    extract(shortcode_atts(array(
      'template' => '',
      // 'id' => '',
      'format' => '',
      'date_format' => '',
      'words' => '',
      'more' => '&hellip;',
      'length' => '',
      'size' => '96' // default avatar size
    ), $atts));


    /*---------------------------------------------
     *
     * Inside a comments loop
     *
     */

    if ( self::$state['is_comments_loop'] ) {

      $comment = self::$state['current_comment'];
      if ( empty($comment) ) return;

      $out = '';

      if ( empty($atts) || ( is_array($atts) && count($atts)==0 ) ) {
        $atts = array('content'); // Default field
      }

      // Check for parameters without value
      $atts = CCS_Content::get_all_atts( $atts );
      $post_id = $comment->comment_post_ID;

      // Display comment fields

      $fields = array(
        'ID', 'post_ID', 'author', 'author_email', 'author_url', 'date',
        'content', 'content-link', 'user_id', 'avatar', 'count', 'counted',
        'title', 'url', 'post-url', 'title_link', 'author_link', 'link',
        'reply-link'
      );

      foreach ($fields as $field) {

        $arg_field = strtolower( $field );
        $arg_field = str_replace( '_', '-', $arg_field );

        if ( $arg_field == 'user-id' ) {
          $field = 'user_id';
        } else {
          $field = 'comment_'.$field; // name of property in comment object
        }

        // Check first parameter [comment ~]

        if ( isset($atts[$arg_field]) ) {

          switch ($arg_field) {
            case 'id' :
              $out = $comment->comment_ID;
            break;

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
              //$author_id = $comment->user_id;
              $author_id = get_comment_author_email($comment->comment_ID);
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

            case 'reply-link':

              $comment_id = $comment->comment_ID;

              //get the setting configured in the admin panel under settings discussions "Enable threaded (nested) comments  levels deep"
              $max_depth = get_option('thread_comments_depth');
              //add max_depth to the array and give it the value from above and set the depth to 1
              $args = array(
                'add_below'  => 'comment',
                'respond_id' => 'respond',
                'reply_text' => __('Reply'),
                'login_text' => __('Log in to Reply'),
                'depth'      => 1,
                'before'     => '',
                'after'      => '',
                'max_depth'  => $max_depth
              );

              wp_enqueue_script( 'comment-reply' ); // comment-reply.js
              $out = get_comment_reply_link( $args, $comment_id, $post_id );

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

    } // Comment field when inside loop

    else {

      // Outside comments loop

      // Check for parameters without value, i.e., [comment count]
      $atts = CCS_Content::get_all_atts( $atts );

      if ( isset( $atts['count'] ) ) {
        return get_comments_number();
      }
      if ( isset( $atts['counted'] ) ) {
        $count = get_comments_number();
        if ($count == 0) return 'No comments';
        if ($count == 1) return '1 comment';
        return $count.' comments';
      }

      if ( isset( $atts['total'] ) ) {
        return CCS_Loop::$state['comment_count'];
      }

    }

    if ( isset( $atts['form'] ) ) {
      return self::comment_form_shortcode( $atts, $content );
    }

    // Comments template

    if ( !empty($template) || isset($atts['template']) ) {

      $dir = '';

      if ( empty($template) ) $template = '/comments.php';
      if ( isset($template[0]) && $template[0]!='/' ) {
        $template = '/'.$template;
      }

      $file = $dir.$template;

// Maybe necessary
//      global $withcomments;
//      $withcomments = "1";

      // Return comments template
      ob_start();
      comments_template( $file );
      return ob_get_clean();
    }

  } // comment_shortcode


  /*---------------------------------------------
   *
   * Comment form inputs and options
   *
   */

  function comment_form_input_shortcode( $atts, $content ) {

    if (!isset($atts[0])) return;

    $inputs = self::$state['inputs'];

    if (in_array($atts[0], $inputs)) {
      self::$state['comment_form_fields'][ $atts[0] ] = do_shortcode($content);
    }
  }

  /*---------------------------------------------
   *
   * Comment form
   *
   */

  function comment_form_shortcode( $atts, $content ) {

    extract(shortcode_atts(array(
      'fields' => 'author, email, url',
    ), $atts));

    $enabled_fields = CCS_Loop::explode_list($fields);

    self::$state['comment_form_fields'] = array();

    $content = do_local_shortcode( 'comment-form', $content, true );

    // Prepare arguments

    $commenter = wp_get_current_commenter();
    $req = get_option( 'require_name_email' );
    $aria_req = ( $req ? " aria-required='true'" : '' );

    /*---------------------------------------------
     *
     * Inputs
     *
     */

    $options = array();
    $rendered_fields = array();
    $defined_inputs = self::$state['comment_form_fields'];

    foreach ( self::$state['inputs'] as $each_input ) {

      $do_define = isset( $defined_inputs[ $each_input ] );

      // Input fields
      if ( in_array( $each_input, array('author','email','url') ) &&
        // If enabled
        in_array( $each_input, $enabled_fields ) ) {

        if ( $do_define ) {

          $rendered_fields[ $each_input ] = $defined_inputs[ $each_input ];

        } elseif ( $each_input == 'author' ) {

          $rendered_fields[ $each_input ] =
            '<p class="comment-form-author"><label for="author">'
              . 'Name' . '</label> '
              . ( $req ? '<span class="required">*</span>' : '' )
              . '<input id="author" name="author" type="text" value="'
                . esc_attr( $commenter['comment_author'] )
              . '" size="30"' . $aria_req . ' /></p>';

        } elseif ( $each_input == 'email' ) {

          $rendered_fields[ $each_input ] =
            '<p class="comment-form-email"><label for="email">' . __( 'Email', 'domainreference' ) . '</label> ' .
            ( $req ? '<span class="required">*</span>' : '' ) .
            '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) .
            '" size="30"' . $aria_req . ' /></p>';

        } elseif ( $each_input == 'url' ) {

          $rendered_fields[ $each_input ] =
            '<p class="comment-form-url"><label for="url">' . __( 'Website', 'domainreference' ) . '</label>' .
            '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) .
            '" size="30" /></p>';

        }

      // Comment form options

      } elseif ( in_array( $each_input, array(
          'comment', 'reply', 'reply_to', 'cancel', 'submit', 'login', 'logged-in', 'before', 'after'
        ) ) ) {


        if ( $each_input == 'login' ) {
          if ( $do_define ) {
            $template = $defined_inputs[ $each_input ];
          } else {
            $template =
              '<p class="must-log-in">'
                .'You must be <a href="%s">logged in</a> to post a comment.'
              .'</p>';
          }

          $options[ $each_input ] = sprintf( $template,
            wp_login_url( apply_filters( 'the_permalink', get_permalink() ) )
          );

        } elseif ( $each_input == 'logged-in' ) {

          if ( $do_define ) {
            $template = $defined_inputs[ $each_input ];
          } else {
            $template =
              '<p class="logged-in-as">'
                .'Logged in as <a href="%1$s">%2$s</a>. '
                .'<a href="%3$s" title="Log out of this account">Log out?</a>'
              .'</p>';
          }

          $user_identity = do_shortcode('[user]');
          if ( !empty( $user_identity ) ) {

            $options[ $each_input ] = sprintf( $template,
              admin_url( 'profile.php' ),
              $user_identity,
              wp_logout_url( apply_filters( 'the_permalink', get_permalink( ) ) )
            );
          } else {
            $options[ $each_input ] = '';
          }

        } elseif ( $do_define ) {

          // Option is set by a local shortcode
          $options[ $each_input ] = $defined_inputs[ $each_input ];

        } else {
          if ( $each_input == 'comment' ) {

            // Default comment textarea

            $options[ $each_input ] =
              '<p><textarea placeholder="" id="comment" class="form-control" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>';

          } elseif ( $each_input == 'reply' ) {
            $options[ $each_input ] = '';  // Leave a Reply
          } elseif ( $each_input == 'reply_to' ) {
            $options[ $each_input ] = '';  // Leave a Reply to %s
          } elseif ( $each_input == 'cancel' ) {
            $options[ $each_input ] = 'Cancel Reply';
          } elseif ( $each_input == 'submit' ) {
            $options[ $each_input ] = 'Post Comment';
          } elseif ( $each_input == 'before' ) {
            $options[ $each_input ] = '';
          } elseif ( $each_input == 'after' ) {
            $options[ $each_input ] = '';
          }
        }
      } // End comment form options

    } // For each field

    $args = array(
      'fields' => apply_filters( 'comment_form_default_fields', $rendered_fields ),
      'id_form'           => 'commentform',
      'id_submit'         => 'commentsubmit',
      'title_reply'       => $options['reply'],
      'title_reply_to'    => $options['reply_to'],
      'cancel_reply_link' => $options['cancel'],
      'label_submit'      => $options['submit'],
      'comment_field'     =>  $options['comment'],
      'must_log_in'      => $options['login'],
      'logged_in_as'      => $options['logged-in'],
      'comment_notes_before' => $options['before'],
      'comment_notes_after' => $options['after']
    );

    // Return comment form

    ob_start();
    comment_form( $args );
    return ob_get_clean();
  }

}
