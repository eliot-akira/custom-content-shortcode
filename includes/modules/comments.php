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

    add_ccs_shortcode( array(
      'comments' => array( $this, 'comments_shortcode'),
      '-comments' => array( $this, 'comments_shortcode'),
      'comment' => array( $this, 'comment_shortcode'),
      '-comment' => array( $this, 'comment_shortcode'),
      'comment-form' => array( $this, 'comment_form_shortcode'),
    ));

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
    self::$state['comments_loop_index'] = 0;
    self::$state['comments_loop_count'] = 0;
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
      'term_id' => '',

      'offset' => '',
      'orderby' => '',
      'order' => 'DESC',
      'parent' => '',

      'author' => '', // comments by *post author*
      'name' => '',
      'status' => 'approve',

      'user_id' => '',
      'user' => '' // comments by *comment author*

    ), $atts));


    // Prepare comments query

    $args = array();

    // Comments count

    // Get all and filter later
    // $args['number'] = $count=='all' ? 9999 : $count;
    $args['number'] = 9999;

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
      $args['post_type'] = CCS_Format::explode_list( $type );
    }
    if ( !empty( $include ) ) {
      $args['post__in'] = CCS_Format::explode_list( $include );
    }
    if ( !empty( $exclude ) ) {
      $args['post__not_in'] = CCS_Format::explode_list( $exclude );
    }

    if ( !empty( $offset ) ) $args['offset'] = $offset;
    if ( !empty( $orderby ) ) $args['orderby'] = $orderby;
    if ( !empty( $order ) ) $args['order'] = $order;
    if ( !empty( $parent ) ) $args['post_parent'] = CCS_Format::explode_list( $parent );

    // Comments by post author or comment author (user)
    if ( !empty( $author ) ||  !empty( $exclude_author ) ||
      !empty( $user ) ||  !empty( $exclude_user ) ) {

      if (  !empty( $author ) ||  !empty( $exclude_author ) ) {
        $authors = CCS_Format::explode_list( $author );
      } else $authors = CCS_Format::explode_list( $user );

      $author_ids = array();
      foreach ($authors as $this_author) {

        if ( $this_author=='this' ) {
          // current author ID
          $author_ids[] = do_shortcode('[user id]');
        } elseif (is_numeric( $this_author )) {
          $author_ids[] = $this_author;
        } else {
          // get author ID from user name
          $author_ids[] = do_shortcode('[users search='.$this_author.' search_column=login][user id][/users]');
        }
      }

      if ( !empty( $author ) ) $args['post_author__in'] = $author_ids;
      elseif ( !empty( $user ) ) $args['author__in'] = $author_ids;
      elseif ( !empty( $exclude_author ) ) $args['post_author__not_in'] = $author_ids;
      elseif ( !empty( $exclude_user ) ) $args['author__not_in'] = $author_ids;
    }

    if ( !empty( $name ) ) $args['name'] = $name;
    if ( !empty( $status ) && $status != 'all' ) $args['status'] = $status;
    if ( !empty( $user_id ) ) $args['user_id'] = CCS_Format::explode_list( $user_id );

    // Filter by taxonomy

    $taxonomy_filter = false;

    if ( !empty($category) ) {
      $taxonomy = 'category';
      $term = $category;
    } elseif ( !empty($tag) ) {
      $taxonomy = 'tag';
      $term = $tag;
    }

    if ( !empty($taxonomy) ) {
      $taxonomy_filter = true;
      $terms = array();
      $term_ids = array();
      if (!empty($term)) $terms = CCS_Format::explode_list($term);
      if (!empty($term_id)) $term_ids = CCS_Format::explode_list($term_id);
    }


    // If empty
    $if_empty = CCS_Loop::get_between('[if empty]', '[/if]', $content);
    $content = str_replace($if_empty, '', $content);


    /*---------------------------------------------
     *
     * Init loop
     *
     */

    $index = 0;
    $max = $count=='all' ? 9999 : $count;
    $out = '';

    $prev_state = self::$state;

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

    self::$state['comments_loop_index'] = 0;
    self::$state['comments_loop_count'] = count($comments);

    foreach ($comments as $comment) {

      if ( $index >= $max ) break;
      self::$state['comments_loop_index']++; // Starts with 1

      $matches = true;

      if ( $taxonomy_filter ) {
        $matches = false;
        $pid = $comment->comment_post_ID;
        if (!empty($terms)) {
          $post_tax = do_shortcode('[taxonomy '.$taxonomy.' id="'.$pid.'" field=slug]');
          // Term slugs are separated by space
          $post_tax = explode(' ', $post_tax);
          foreach ($terms as $each_term) {
            if ( in_array( $each_term, $post_tax ) ) {
              $matches = true;
            }
          }
        }
        if (!empty($term_ids)) {
          $post_tax_ids = do_shortcode('[taxonomy '.$taxonomy.' id="'.$pid.'" field=id]');
          // Term IDs are separated by comma..
          $post_tax_ids = CCS_Format::explode_list($post_tax_ids);
          foreach ($term_ids as $each_term_id) {
            if ( in_array( $each_term_id, $post_tax_ids ) ) {
              $matches = true;
            }
          }
        }
      }

      if ( $matches ) {

        self::$state['is_comments_loop'] = true; // Keep it true in case nested
        self::$state['current_comment'] = $comment;
        $result = do_ccs_shortcode( $content, false );

        $check = trim($result);
        if ( ! empty($check) ) $index++;
        $out .= $result;
      }
    }


    // Close loop

    self::$state = $prev_state;

//    self::$state['is_comments_loop'] = false;
//    self::$state['current_comment'] = '';
    return $out;

  } // comments_shortcode



  /*---------------------------------------------
   *
   * [comment] - Show comment field
   *
   */

  function comment_shortcode( $atts = array(), $content ) {

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

      // Display comment fields 

      $fields = array(
        'ID', 'author', 'author-id', 'author-email', 'author-link', 'author-url', 'avatar',
        'content', 'content-link', 'count', 'counted', 'date',
        'title', 'title-link', 'link', 'post-ID', 'post-title', 'post-link', 'post-url',
        'reply-link', 'url', 'user-id'
      );

      if ( !isset($atts[0]) ) $atts[0] = 'content';

      $post_id = $comment->comment_post_ID;

      $field = strtolower( $atts[0] );
      $arg_field = $field;

      if ( $arg_field == 'user-id' ) {
        $field = 'user_id';
      } else {
        $field = str_replace( '-', '_', $arg_field );
        $field = 'comment_'.$field; // name of property in comment object
      }

      // Check first parameter [comment ~]

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

        case 'author-id':
          $out = $comment->user_id;
        break;

        case 'avatar':
          $author_email= get_comment_author_email($comment->comment_ID);
          $out = get_avatar( $author_email, $size );
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
          if (isset($comment->{$field})) $out = $comment->{$field};
        break;
      }



      // Allow check for parameters without value
      $atts = CCS_Content::get_all_atts( $atts );


      if (!empty($words)) {
        if ( $more=='false' ) $more = '';
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

      } elseif ( isset( $atts['counted'] ) ) {

        $count = get_comments_number();
        if ($count == 0) return 'No comments';
        if ($count == 1) return '1 comment';
        return $count.' comments';

      } elseif ( isset( $atts['total'] ) ) {

        // Get total comment count

        $all_ids = CCS_Loop::$state['all_ids'];
        $current_index = CCS_Loop::$state['loop_count'];
        $total = 0;

        for ($i=0; $i < $current_index; $i++) {
          $total += get_comments_number(intval($all_ids[$i]));
        }

        return $total;

      } elseif ( isset( $atts['form'] ) ) {

        return self::comment_form_shortcode( $atts, $content );

      } elseif ( !empty($template) || isset($atts['template']) ) {

        // Comments template

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
    } // Outside comments loop

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
      self::$state['comment_form_fields'][ $atts[0] ] = do_ccs_shortcode($content);
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

    $enabled_fields = CCS_Format::explode_list($fields);

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
