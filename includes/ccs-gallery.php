<?php

/*====================================================================================================
 *
 * Simple gallery field
 *
 *====================================================================================================*/


/**** Functions ****/

/*
 * Is gallery
 */

function custom_is_gallery() {
	$attachment_ids = get_post_meta( get_the_ID(), '_custom_gallery', true );
	if ( $attachment_ids )
		return true;
}

/*
 * Check the current post for the existence of a short code
 */

function custom_gallery_has_shortcode( $shortcode = '' ) {
	global $post;
	$found = false;

	if ( !$shortcode ) {
		return $found;
	}
	if (  is_object( $post ) && stripos( $post->post_content, '[' . $shortcode ) !== false ) {
		$found = true; // we have found the short code
	}
	return $found;
}

/*
 * Has linked images
 */

function custom_gallery_has_linked_images() {
	$link_images = get_post_meta( get_the_ID(), '_custom_gallery_link_images', true );

	if ( 'on' == $link_images ) return true;
}


/*
 * Get list of post types for populating the checkboxes on the admin page
 */

function custom_gallery_get_post_types() {

	$args = array( 'public' => true	);

	$post_types = get_post_types( $args );

	// remove attachment
	unset( $post_types[ 'attachment' ] );

	return apply_filters( 'custom_gallery_get_post_types', $post_types );

}

/*
 * Retrieve the allowed post types from the option row
 *
 */

function custom_gallery_allowed_post_types() {
	
/*	$defaults['post_types']['post'] = '';
	$defaults['post_types']['page'] = '';
*/
	// get the allowed post type from the DB
	$settings = ( array ) get_option( 'custom-gallery', $default = false );
	$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : '';

	// post types don't exist, bail
	if ( ! $post_types )
		return;

	return $post_types;
}


/*
 * Is the currently viewed post type allowed?
 * For use on the front-end when loading scripts etc
 */

function custom_gallery_allowed_post_type() {

	// post and page defaults
/*	$defaults['post_types']['post'] = '';
	$defaults['post_types']['page'] = '';
*/
	// get currently viewed post type
	$post_type = ( string ) get_post_type();

	//echo $post_type; exit; // download

	// get the allowed post type from the DB
	$settings = ( array ) get_option( 'custom-gallery', $defaults );
	$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : '';

	// post types don't exist, bail
	if ( ! $post_types )
		return;

	// check the two against each other
	if ( array_key_exists( $post_type, $post_types ) )
		return true;
}


/**
 * Retrieve attachment IDs
 */

function custom_gallery_get_image_ids() {

	global $ccs_global_variable;

	if($ccs_global_variable['current_gallery_id'] == '') {
		global $post;
		if( ! isset( $post->ID) )
			return;
		$attachment_ids = get_post_meta( $post->ID, '_custom_gallery', true );
	} else {
		$attachment_ids = get_post_meta( $ccs_global_variable['current_gallery_id'], '_custom_gallery', true );
	}

	$attachment_ids = explode( ',', $attachment_ids );

	return array_filter( $attachment_ids );
}


/*
 * Shortcode

function ccs_gallery_shortcode() {

	// return early if the post type is not allowed to have a gallery
	if ( ! custom_gallery_allowed_post_type() )
		return;

	return custom_gallery();
}

add_shortcode( 'custom_gallery', 'ccs_gallery_shortcode' );
 */



/*
 * Count number of images in array
 */

function custom_gallery_count_images() {

	$images = get_post_meta( get_the_ID(), '_custom_gallery', true );
	$images = explode( ',', $images );

	$number = count( $images );

	return $number;
}

/*
 * Output gallery
 *
function custom_gallery() { // No output without shortcode
}
 */


/*
 * CSS for admin
 */

function custom_gallery_admin_css() { ?>

	<style>
		.attachment.details .check div {
			background-position: -60px 0;
		}

		.attachment.details .check:hover div {
			background-position: -60px 0;
		}

		.gallery_images .details.attachment {
			box-shadow: none;
		}

		.eig-metabox-sortable-placeholder {
			background: #DFDFDF;
		}

		.gallery_images .attachment.details > div {
			width: 150px;
			height: 150px;
			box-shadow: none;
		}

		.gallery_images .attachment-preview .thumbnail {
			 cursor: move;
		}

		.attachment.details div:hover .check {
			display:block;
		}

        .gallery_images:after,
        #gallery_images_container:after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }

        .gallery_images > li {
            float: left;
            cursor: move;
            margin: 0 20px 20px 0;
        }

        .gallery_images li.image img {
            width: 150px;
            height: auto;
        }

        .add_gallery_images { margin-top: -15px; float:left; }

    </style>

<?php }
add_action( 'admin_head', 'custom_gallery_admin_css' );


/***** Metabox *****/


/*
 * Add meta boxes to selected post types
 */

function custom_gallery_add_meta_box() {

    $post_types = custom_gallery_allowed_post_types();

    if ( ! $post_types )
        return;

    foreach ( $post_types as $post_type => $status ) {
        add_meta_box( 'custom_gallery', apply_filters( 'custom_gallery_meta_box_title', __( 'Gallery', 'custom-gallery' ) ), 'custom_gallery_metabox', $post_type, apply_filters( 'custom_gallery_meta_box_context', 'normal' ), apply_filters( 'custom_gallery_meta_box_priority', 'low' ) );
    }

}
add_action( 'add_meta_boxes', 'custom_gallery_add_meta_box' );


/*
 * Render gallery metabox
 */

function custom_gallery_metabox() {

    global $post;
?>

    <div id="gallery_images_container">
        <ul class="gallery_images">
    	<?php
    		$image_gallery = get_post_meta( $post->ID, '_custom_gallery', true );
		    $attachments = array_filter( explode( ',', $image_gallery ) );

		    if ( $attachments )
		        foreach ( $attachments as $attachment_id ) {
		            echo '<li class="image attachment details" data-attachment_id="'
		            	. $attachment_id
		            	. '"><div class="attachment-preview"><div class="thumbnail">'
		            	. wp_get_attachment_image( $attachment_id, 'thumbnail' )
		            	. '</div><a href="#" class="delete check" title="'
		            	. __( 'Remove image', 'custom-gallery' )
		            	. '"><div class="media-modal-icon"></div></a></div></li>';
        		}
		?>
        </ul>

        <input type="hidden" id="image_gallery" name="image_gallery"
        	value="<?php echo esc_attr( $image_gallery ); ?>" />
        <?php wp_nonce_field( 'custom_gallery', 'custom_gallery' ); ?>

    </div>

    <p class="add_gallery_images hide-if-no-js">
        <a href="#"><?php _e( 'Add images', 'custom-gallery' ); ?></a>
    </p>

    <?php 	// options don't exist yet, set to checked by default
    	if ( ! get_post_meta( get_the_ID(), '_custom_gallery_link_images', true ) )
	        $checked = ' checked="checked"';
    	else
        	$checked = custom_gallery_has_linked_images() ? checked( get_post_meta( get_the_ID(), '_custom_gallery_link_images', true ), 'on', false ) : '';
	?>

    <?php
    /*
     * Image ordering and removing routine
     */
	?>
    <script type="text/javascript">
        jQuery(document).ready(function($){

            // Uploading files
            var image_gallery_frame;
            var $image_gallery_ids = $('#image_gallery');
            var $gallery_images = $('#gallery_images_container ul.gallery_images');

            jQuery('.add_gallery_images').on( 'click', 'a', function( event ) {

                var $el = $(this);
                var attachment_ids = $image_gallery_ids.val();

                event.preventDefault();

                // If the media frame already exists, reopen it.
                if ( image_gallery_frame ) {
                    image_gallery_frame.open();
                    return;
                }

                // Create the media frame.
                image_gallery_frame = wp.media.frames.downloadable_file = wp.media({
                    // Set the title of the modal.
                    title: '<?php _e( 'Add Images to Gallery', 'custom-gallery' ); ?>',
                    button: {
                        text: '<?php _e( 'Add to gallery', 'custom-gallery' ); ?>',
                    },
                    multiple: true
                });

                // When an image is selected, run a callback.
                image_gallery_frame.on( 'select', function() {

                    var selection = image_gallery_frame.state().get('selection');

                    selection.map( function( attachment ) {

                        attachment = attachment.toJSON();

                        if ( attachment.id ) {
                            attachment_ids = attachment_ids ? attachment_ids + "," + attachment.id : attachment.id;

                             $gallery_images.append('\
                                <li class="image attachment details" data-attachment_id="' + attachment.id + '">\
                                    <div class="attachment-preview">\
                                        <div class="thumbnail">\
                                            <img src="' + attachment.url + '" />\
                                        </div>\
                                       <a href="#" class="delete check" title="<?php _e( 'Remove image', 'custom-gallery' ); ?>"><div class="media-modal-icon"></div></a>\
                                    </div>\
                                </li>');

                        }

                    } );

                    $image_gallery_ids.val( attachment_ids );
                });

                // Finally, open the modal.
                image_gallery_frame.open();
            });

            // Image ordering
            $gallery_images.sortable({
                items: 'li.image',
                cursor: 'move',
                scrollSensitivity:40,
                forcePlaceholderSize: true,
                forceHelperSize: false,
                helper: 'clone',
                opacity: 0.65,
                placeholder: 'eig-metabox-sortable-placeholder',
                start:function(event,ui){
                    ui.item.css('background-color','#f6f6f6');
                },
                stop:function(event,ui){
                    ui.item.removeAttr('style');
                },
                update: function(event, ui) {
                    var attachment_ids = '';

                    $('#gallery_images_container ul li.image').css('cursor','default').each(function() {
                        var attachment_id = jQuery(this).attr( 'data-attachment_id' );
                        attachment_ids = attachment_ids + attachment_id + ',';
                    });

                    $image_gallery_ids.val( attachment_ids );
                }
            });

            // Remove images
            $('#gallery_images_container').on( 'click', 'a.delete', function() {

                $(this).closest('li.image').remove();

                var attachment_ids = '';

                $('#gallery_images_container ul li.image').css('cursor','default').each(function() {
                    var attachment_id = jQuery(this).attr( 'data-attachment_id' );
                    attachment_ids = attachment_ids + attachment_id + ',';
                });

                $image_gallery_ids.val( attachment_ids );

                return false;
            } );

        });
    </script>
    <?php
}


/*
 * Save function
 *
 */

function custom_gallery_save_post( $post_id ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    $post_types = custom_gallery_allowed_post_types();

    // check user permissions
/*    if ( isset( $_POST[ 'post_type' ] ) && !array_key_exists( $_POST[ 'post_type' ], $post_types ) ) {
        if ( !current_user_can( 'edit_page', $post_id ) )
            return;
    }
    else { */
        if ( !current_user_can( 'edit_post', $post_id ) )
            return;
/*    } */

    if ( ! isset( $_POST[ 'custom_gallery' ] ) || ! wp_verify_nonce( $_POST[ 'custom_gallery' ], 'custom_gallery' ) )
        return;

    if ( isset( $_POST[ 'image_gallery' ] ) && !empty( $_POST[ 'image_gallery' ] ) ) {
        $attachment_ids = sanitize_text_field( $_POST['image_gallery'] );
        $attachment_ids = explode( ',', $attachment_ids ); // turn comma separated values into array
        $attachment_ids = array_filter( $attachment_ids  ); // clean the array
        $attachment_ids =  implode( ',', $attachment_ids ); // return back to comma separated list with no trailing comma. This is common when deleting the images
        update_post_meta( $post_id, '_custom_gallery', $attachment_ids );
    } else {
        delete_post_meta( $post_id, '_custom_gallery' );
    }

    // link to larger images
    if ( isset( $_POST[ 'custom_gallery_link_images' ] ) )
        update_post_meta( $post_id, '_custom_gallery_link_images', $_POST[ 'custom_gallery_link_images' ] );
    else
        update_post_meta( $post_id, '_custom_gallery_link_images', 'off' );

    do_action( 'custom_gallery_save_post', $post_id );
}
add_action( 'save_post', 'custom_gallery_save_post' );


/***** Admin page *****/

function custom_gallery_menu() {
	add_options_page( __( 'Gallery Fields', 'custom-gallery' ), __( 'Gallery Fields', 'custom-gallery' ), 'manage_options', 'custom-gallery', 'custom_gallery_admin_page' );
}
add_action( 'admin_menu', 'custom_gallery_menu' );

/*
 * Admin page
 *
 */

function custom_gallery_admin_page() {
	?>
    <div class="wrap">
    	 <?php /* screen_icon( 'plugins' ); */ ?>
        <h2><?php _e( 'Gallery Fields', 'custom-gallery' ); ?></h2>

        <form action="options.php" method="POST">
            <?php settings_fields( 'my-settings-group' ); ?>
            <?php do_settings_sections( 'custom-gallery-settings' ); ?>
            <?php submit_button(); ?>
        </form>
	<div style="padding-left:5px;">
		<a href="options-general.php?page=ccs_content_shortcode_help&tab=gallery"><em>Reference: Custom Content Shortcode</em></a>
	</div>

    </div>
<?php
}


/*
 * Admin init
 */

function custom_gallery_admin_init() {
	register_setting( 'my-settings-group', 'custom-gallery', 'custom_gallery_settings_sanitize' );
	// sections
	add_settings_section( 'general', __( '', 'custom-gallery' ), '', 'custom-gallery-settings' );
	// settings
	add_settings_field( 'post-types', __( '<b>Select post types</b>', 'custom-gallery' ), 'post_types_callback', 'custom-gallery-settings', 'general' );
}
add_action( 'admin_init', 'custom_gallery_admin_init' );

/*
 * Post Types callback
 */

function post_types_callback() {

	// post and page defaults
/*	$defaults['post_types']['post'] = '';
	$defaults['post_types']['page'] = '';
*/
	$settings = (array) get_option( 'custom-gallery', $default = false );

	 foreach ( custom_gallery_get_post_types() as $key => $label ) {
		$post_types = isset( $settings['post_types'][ $key ] ) ? esc_attr( $settings['post_types'][ $key ] ) : '';

		?><p>
			<input type="checkbox" id="<?php echo $key; ?>" name="custom-gallery[post_types][<?php echo $key; ?>]" <?php checked( $post_types, 'on' ); ?>/><label for="<?php echo $key; ?>"> <?php echo $label; ?></label>
		</p><?php
	} 
}


/**
 * Sanitization
 *
 */

function custom_gallery_settings_sanitize( $input ) {

	// Create our array for storing the validated options
	$output = array();

	// post types
	$post_types = isset( $input['post_types'] ) ? $input['post_types'] : '';

	// only loop through if there are post types in the array
	if ( $post_types ) {
		foreach ( $post_types as $post_type => $value )
			$output[ 'post_types' ][ $post_type ] = isset( $input[ 'post_types' ][ $post_type ] ) ? 'on' : '';	
	}
	
	return apply_filters( 'validate_input_examples', $output, $input );
}


/**
 * Action Links
 */

function custom_gallery_plugin_action_links( $links ) {

	$settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/plugins.php?page=custom-gallery">'. __( 'Settings', 'custom-gallery' ) .'</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

