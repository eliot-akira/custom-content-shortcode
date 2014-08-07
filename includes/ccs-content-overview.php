
<div style="max-width:960px;">

	<h2  style="padding-left:10px">Content Overview</h2>

	<div style="height:10px"></div>

	<hr>

	<h3 style="padding-left:10px">Post types and fields</h3>
	<div style="height:10px"></div>

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th><b>Post type</b></th>
				<th><b></b></th>
				<th><b>Taxonomy</b></th>
				<th><b></b></th>
				<th><b>Default</b></th>
				<th><b>Fields</b></th>
				<th class="column-author"><b>Count</b></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><b>Post type</b></th>
				<th><b></b></th>
				<th><b>Taxonomy</b></th>
				<th><b></b></th>
				<th><b>Default</b></th>
				<th><b>Fields</b></th>
				<th class="column-author"><b>Count</b></th>
			</tr>
		</tfoot>
		<tbody id="the-list">
		<?php

			/* Generate list of post types */

			$post_type_objects = get_post_types( array('public' => true), 'objects' ); 

				$exclude_types = array( 'revision', 'plugin_filter', 'plugin_group' );
/*
				foreach ($exclude_types as $exclude_type) {
					unset($post_types[$exclude_type]);
				}
			Or..array('public' => true)

*/


			foreach ($post_type_objects as $post_type_object) {

				$label = $post_type_object->labels->singular_name; if(empty($label)) $label=$post_type_object->labels->name;
				$labels[] = $label;
				$sorted_post_objects[$label] = $post_type_object;

			}

			sort( $labels );

			/* Add these to the top */

			$key = array_search('Page', $labels);
			unset( $labels[$key] );
			array_unshift($labels, 'Page');

			$key = array_search('Post', $labels);
			unset( $labels[$key] );
			array_unshift($labels, 'Post');

/*			$post_types = array('page' => $post_types['page']) + $post_types;
			$post_types = array('post' => $post_types['post']) + $post_types;
*/

			$alternate = '';

			foreach ( $labels as $label ) {

				if(isset($sorted_post_objects[$label])) {
					$post_type_object = $sorted_post_objects[$label];
					$post_type = $post_type_object->name;
				} else {
					$post_type = '_undefined';
				}

				$all_supports = array();
				$support_types = array(
					'title',
					'author',
					'thumbnail',
					'excerpt');

				foreach ($support_types as $support_type) {
					if (post_type_supports($post_type, $support_type))
					$all_supports[] = $support_type;
				}

				$alternate = ( $alternate == '' ) ? 'class="alternate"' : '';

					?>

					<tr <?php echo $alternate; ?>>


						<td style="vertical-align:top;" class="column-title">

							<?php

								if ( in_array( $post_type, $exclude_types ) ) {

									$edit_url = '';

								} elseif ( $post_type == 'post' ) {

									$edit_url = admin_url( 'edit.php' );

								} elseif ( $post_type == 'attachment' ) {

									$edit_url = admin_url( 'upload.php' );

								} elseif ( $post_type == 'nav_menu_item' ) {

									$edit_url = admin_url( 'nav-menus.php' );

								} else {

									$edit_url = admin_url( 'edit.php?post_type=' . $post_type );

								}

								if ( $edit_url != '' ) {
									echo '<a class="row-title" href="' . $edit_url . '">';
									echo $label . '</a><br>';
								} else {
									echo $label . '<br>';
								}

							?>

						</td>

						<td style="vertical-align:top">

							<?php  echo $post_type . '<br>'; ?>

						</td>

			<?php

				/* Generate list of taxonomies and fields */

				if ( $post_type == 'attachment' ) {

					$args = array(
						'post_type' => $post_type,
						'posts_per_page' => -1,
					);
					$allposts = get_posts( $args );
					$num_posts = count( $allposts );

				} else {

					$args = array(
						'post_status' => array('any'),
						'post_type' => $post_type,
						'posts_per_page' => 2,			// To make sure we get all fields..
					);
					$allposts = get_posts($args);
					$num_posts = wp_count_posts( $post_type );

					if( is_object($num_posts) && isset($num_posts->publish) &&
						isset($num_posts->draft) && isset($num_posts->future) &&
							isset($num_posts->pending)) {

								$num_posts = $num_posts->publish + $num_posts->draft +
											$num_posts->future + $num_posts->pending;
					}
					else $num_posts = 0;

				}

				$post_count[ $post_type ] = $num_posts;

/*				$post_count[ $post_type ] = count($allposts); */

				$all_fields = null;
				$all_taxonomies = null;

			    foreach ( $allposts as $post ) : setup_postdata($post);

			        $post_id = $post->ID;

			        $fields = get_post_custom_keys($post_id);    // all keys for post as values of array

			        if ($fields) {
			            foreach ($fields as $key => $value) {

			                if ($value[0] != '_') {              // exclude where added by plugin
			                    $all_fields[$value] = isset($customfields[$value]) ? $customfields[$value] + 1 : 1;
			                }
			            }
			        }

			    endforeach; wp_reset_postdata();


		/* List taxonomies, fields, post count */

		?>



		<td style="vertical-align:top">
				<?php
			        $taxonomies = get_object_taxonomies($post_type);

			        foreach ($taxonomies as $row => $taxonomy) {

			        	$the_tax = get_taxonomy( $taxonomy );

						echo '<a href="' . admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) . '">';
						echo $the_tax->labels->name . '</a><br>';
					}
					/*		echo implode(', ', $taxonomies ); */
				?>
		</td>
		<td style="vertical-align:top">
				<?php
			        foreach ($taxonomies as $row => $taxonomy) {

			        	$the_tax = get_taxonomy( $taxonomy );
						echo $taxonomy . '<br>';
					}
				?>
		</td>





		<td style="vertical-align:top">

		<?php

			$default_supports = array('id', 'date', 'url', 'slug', );

			$all_supports = array_merge($default_supports, $all_supports);
/*
			if (in_array('author', $all_supports)) {
//				$add_supports = array('avatar'); 
//				$add_supports = array('author-id', 'author-url', 'avatar');
				$all_supports = array_merge($add_supports, $all_supports);
			}
*/
			if (in_array('thumbnail', $all_supports)) {
				$add_supports = array('image'); 
/*				$add_supports = array('image', 'image-url', 'thumbnail-url'); */
				$all_supports = array_merge($add_supports, $all_supports);
			}

			if ( empty( $all_supports ) ) {
				echo '<br>'; // Prevent cell from collapsing
			} else {

				sort( $all_supports );
				echo implode(', ', $all_supports);
/*
				foreach ( $all_supports as $key ) {
					echo $key . '<br>';
				}
*/
			}
		?>


		</td>
		<td style="vertical-align:top">
			<?php
				if ( empty( $all_fields) ) {
					echo '<br>'; // Prevent cell from collapsing
				} else {

					ksort( $all_fields );

					foreach ( $all_fields as $key => $value ) {
						echo $key . '<br>';
					}
				}
/*
			echo implode(', ', array_keys($all_fields) );
*/			?>
		
		</td>



		<td style="vertical-align:top;" class="column-author">

		<?php
			echo $post_count[ $post_type ] . '<br>';
		?>

		</td>




		</tr>

		<?php
		
		} /* For each post type */

		?>

	</tbody>
	</table>

	<div style="height:40px"></div>
	<hr>
	<h3 style="padding-left:10px">Taxonomies</h3>
	<div style="height:10px"></div>

	<div style="max-width:960px;">
	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th><b>Taxonomy</b></th>
				<th><b></b></th>
				<th><b>Terms</b></th>
				<th><b></b></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><b>Taxonomy</b></th>
				<th><b></b></th>
				<th><b>Terms</b></th>
				<th><b></b></th>
			</tr>
		</tfoot>
		<tbody id="the-list">

				<?php

				$post_types = get_post_types( array('public' => true), 'names' ); 
		        $done = array();

				foreach ($post_types as $post_type) {
				
					$taxonomies = get_object_taxonomies($post_type);

			        foreach ($taxonomies as $row => $taxonomy) {

			        	if ( ! in_array($taxonomy, $done) ) {	// Duplicate?

			        	$done[] = $taxonomy;
						$alternate = ( $alternate == '' ) ? 'class="alternate"' : '';

						?>
						<tr <?php echo $alternate; ?>>

							<td style="vertical-align:top">

								<?php

						        	$the_tax = get_taxonomy( $taxonomy );

									echo '<a class="row-title" href="' . admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) . '">';
									echo $the_tax->labels->name . '</a><br>';

								?>

							</td>
							<td style="vertical-align:top">

								<?php

						        	$the_tax = get_taxonomy( $taxonomy, 'hide_empty=0' );

									echo $taxonomy . '<br>';

								?>

							</td>
							<td style="vertical-align:top">
								<?php

								$terms = get_terms( $taxonomy );

								foreach ( $terms as $term ) {
									echo $term->name . '<br>';
								}
								?>
							</td>
							<td style="vertical-align:top">
								<?php

								foreach ( $terms as $term ) {
									echo $term->slug . '<br>';
								}
								?>
							</td>
						</tr>
						<?php

						} // If not done already


					}	// Each taxonomy

				}	// Each post type

				?>

		</tbody>
	</table>
	</div>

	<div style="height:40px"></div>
	<hr>
	<h3 style="padding-left:10px">Registered shortcodes</h3>
	<div style="height:10px"></div>

	<div style="max-width:960px;">

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th><b>Shortcode</b></th>
				<th><b>Function</b></th>
				<th><b>Shortcode</b></th>
				<th><b>Function</b></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><b>Shortcode</b></th>
				<th><b>Function</b></th>
				<th><b>Shortcode</b></th>
				<th><b>Function</b></th>
			</tr>
		</tfoot>
		<tbody id="the-list">

				<?php

				global $shortcode_tags;
				ksort($shortcode_tags); // Alphabetical sort

				$row_num = 0;
				$alternate = '';

				foreach ( $shortcode_tags as $key => $value ) {

					if ($row_num % 2 == 0) {

						$alternate = ( $alternate == '' ) ? 'class="alternate"' : '';
						echo '<tr ' . $alternate . '>';

					}

					?>
						<td style="vertical-align:top">
								<?php
									echo '<a class="row-title">[' . $key . ']</a><br>';
								?>
						</td>

						<td style="vertical-align:top">
							<?php
								if (! is_array($value)) {
									echo $value . '<br>';
								} else {
									if (is_object($value)) {
										$class_name = get_class($value);
									} else {
										$class_name = '';
									}
									if ($class_name == '') {

										if (is_object($value[0])) {
											$class_name = get_class($value[0]);
										} else {
											$class_name = '';
										}

										if ($class_name == '') {
											print_r($value[0]);
										}
										else
											echo $class_name . '<br>';

									}
									else {
										if (is_object($value))
											$value = get_class($value);
										else
											$value = 'Unknown';
										echo $value . '<br>';
									}
								}

							?>
						</td>

					<?php

					if ($row_num % 2 == 1) {
						echo '</tr>';
					}
					$row_num++;
				}	// Each shortcode

				?>

		</tbody>
	</table>
	</div>
	<div style="height:40px"></div>

	<div style="padding-left:5px;">
		<a href="options-general.php?page=ccs_content_shortcode_help"><em>Reference: Custom Content Shortcode</em></a>
	</div>
</div>