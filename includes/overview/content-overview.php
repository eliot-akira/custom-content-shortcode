
<div id="content-overview-page">

	<h2>Content Overview</h2>

	<hr>

<?php

/*---------------------------------------------
 *
 * Post types and fields
 *
 */

?>
	<h3>Post types and fields</h3>

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th width="12%">Post type</th>
				<th width="12%"></th>
				<th width="12%">Taxonomy</th>
				<th width="12%"></th>
				<th width="15%">Fields</th>
				<th>Default</th>
				<th width="7%">Count</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Post type</th>
				<th></th>
				<th>Taxonomy</th>
				<th></th>
				<th>Fields</th>
				<th>Default</th>
				<th>Count</th>
			</tr>
		</tfoot>
		<tbody id="the-list">
		<?php

			/* Generate list of post types */

			$post_type_objects = get_post_types( array('public' => true), 'objects' );

      $exclude_types = array( 'revision', 'plugin_filter', 'plugin_group' );
/*			foreach ($exclude_types as $exclude_type) {
				unset($post_types[$exclude_type]);
			}
*/

			foreach ($post_type_objects as $post_type_object) {

				$label = $post_type_object->labels->singular_name;
        if(empty($label)) $label = $post_type_object->labels->name;
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

					<tr <?php echo esc_html($alternate); ?>>


						<td class="column-title">

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
									echo '<a class="row-title" href="' . esc_attr($edit_url) . '">';
									echo esc_html($label) . '</a><br>';
								} else {
									echo esc_html($label) . '<br>';
								}

							?>

						</td>

						<td>

							<?php  echo esc_html($post_type) . '<br>'; ?>

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
                  $all_fields[$value] = isset($customfields[$value]) ?
                    $customfields[$value] + 1 : 1;
                }
	            }
		        }

			    endforeach; wp_reset_postdata();


		/* List taxonomies, fields, post count */

		?>



		<td>
				<?php
			        $taxonomies = get_object_taxonomies($post_type);

			        foreach ($taxonomies as $row => $taxonomy) {

			        	$the_tax = get_taxonomy( $taxonomy );

						echo '<a href="' . esc_attr(admin_url( 'edit-tags.php?taxonomy=' . $taxonomy )) . '">';
						echo esc_html($the_tax->labels->name) . '</a><br>';
					}
					/*		echo esc_html(implode(', ', $taxonomies )); */
				?>
		</td>
		<td>
				<?php
			        foreach ($taxonomies as $row => $taxonomy) {

			        	$the_tax = get_taxonomy( $taxonomy );
						echo esc_html($taxonomy) . '<br>';
					}
				?>
		</td>





		<td>
			<?php
				if ( empty( $all_fields) ) {
					echo '<br>'; // Prevent cell from collapsing
				} else {

					ksort( $all_fields );

					foreach ( $all_fields as $key => $value ) {
						echo esc_html($key) . '<br>';
					}
				}
/*
			echo esc_html(implode(', ', array_keys($all_fields)));
*/			?>
		</td>

		<td>
		<?php

// Default fields


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
				echo esc_html(implode(', ', $all_supports));
/*
				foreach ( $all_supports as $key ) {
					echo esc_html($key) . '<br>';
				}
*/
			}
		?>


		</td>

		<td class="column-author">

		<?php
			echo esc_html($post_count[ $post_type ]) . '<br>';
		?>

		</td>




		</tr>

		<?php

		} /* For each post type */

		?>

	</tbody>
	</table>

	<hr>
<?php

/*---------------------------------------------
 *
 * Taxonomies
 *
 */

?>
	<h3>Taxonomies</h3>

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th width="15%">Taxonomy</th>
				<th width="35%"></th>
				<th>Terms</th>
				<th></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Taxonomy</th>
				<th></th>
				<th>Terms</th>
				<th></th>
			</tr>
		</tfoot>
		<tbody id="the-list">

				<?php

				$post_types = get_post_types( array('public' => true), 'names' );
		        $done = array();

				foreach ($post_types as $post_type) {

					$taxonomies = get_object_taxonomies($post_type);

			        foreach ($taxonomies as $row => $taxonomy) {

			        	if (( !in_array($taxonomy, $done) ) && ($taxonomy!="post_format") ) { // Duplicate?

			        	$done[] = $taxonomy;
						$alternate = ( $alternate == '' ) ? 'class="alternate"' : '';

						?>
						<tr <?php echo esc_html($alternate); ?>>

							<td class="row-title">

								<?php

						        	$the_tax = get_taxonomy( $taxonomy, 'hide_empty=0' );

						        	if (($taxonomy=="category")||($taxonomy=="post_tag")) {
										echo '<a href="' . esc_attr(admin_url( 'edit-tags.php?taxonomy=' . $taxonomy )) . '">';
										echo esc_html($the_tax->labels->name) . '</a><br>';
						        	} else {
										echo esc_html($the_tax->labels->name) . '<br>';
						        	}
								?>

							</td>
							<td>

								<?php

//						        	$the_tax = get_taxonomy( $taxonomy, 'hide_empty=0' );

									echo esc_html($taxonomy) . '<br>';

								?>

							</td>
							<td>
								<?php

								$terms = get_terms( $taxonomy, array('hide_empty'=>0) ); // Show empty terms

								foreach ( $terms as $term ) {
									echo esc_html($term->name) . '<br>';
								}
								?>
							</td>
							<td>
								<?php

								foreach ( $terms as $term ) {
									echo esc_html($term->slug) . '<br>';
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

	<a href="#" id="user-roles"></a>
	<hr>
<?php

/*---------------------------------------------
 *
 * User meta
 *
 */

?>
	<h3 class="dropdown-action" data-id="user-meta-table">User meta</h3>

	<div id="user-meta-table" class="dropdown-body">

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th>Fields</th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
		</thead>
		<tbody id="the-list">

			<?php

			// Show all user fields

			$all_meta = get_user_meta( 1 );
			ksort($all_meta);

			echo '<tr>';
			$count = 0;
			$max = count($all_meta);
			$break = round($max / 4);

			if ($break==0) echo '<td>';

			foreach ($all_meta as $key => $value) {

				$count++;

				if ( ($break>0) && ($count % $break == 1) ) echo '<td>';
				echo esc_html($key).'<br>';
				if ( ($break>0) && ($count % $break == 0) ) echo '</td>';
			}

			if ( ($break==0) || ($count % $break != 0)) echo '</td>';
			echo '</tr>';

			?>
		</tbody>
	</table>
	</div>

	<hr>
<?php

/*---------------------------------------------
 *
 * User roles and capabilities
 *
 */

?>
	<h3 class="dropdown-action" data-id="user-roles-table">User roles</h3>

	<div id="user-roles-table" class="dropdown-body">

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th width="15%">Role</th>
				<th width="15%"></th>
				<th>Capabilities</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Role</th>
				<th></th>
				<th>Capabilities</th>
			</tr>
		</tfoot>
		<tbody id="the-list">

				<?php

				// Show all roles

				global $wp_roles;

				// get a role based on role name, does the same thing as get_role()

				$roles = $wp_roles->roles;
/*
				echo '<pre>';
				print_r($wp_roles);
				echo '</pre>';
*/
//				ksort($roles); // Alphabetical sort


				$row_num = 0;
				$alternate = '';

				foreach ( $roles as $role_slug => $role ) {

					if ($row_num % 2 == 0) {
						$alternate = ( $alternate == '' ) ? 'class="alternate"' : '';
					}
					echo '<tr ' . esc_html($alternate) . '>';

					?>
						<td class="row-title">
								<?php
									echo esc_html($role['name']) . '<br>';
								?>
						</td>
						<td class="text-left">
								<?php
									echo esc_html($role_slug) . '<br>';
								?>
						</td>

						<td>
							<?php

							$capabilities = array();
							$capabilities_list = $role['capabilities'];
							ksort($capabilities_list);

							foreach ($capabilities_list as $capability => $value) {
								$capabilities[] = $capability;
							}

							echo esc_html(implode(", ", $capabilities)) . '<br>';

							?>
						</td>

					<?php

					echo '</tr>';

					$row_num++;
				}	// Each shortcode

				?>

		</tbody>
	</table>
	</div>

	<hr>
<?php

/*---------------------------------------------
 *
 * Registered shortcodes
 *
 */

?>
	<h3 class="dropdown-action" data-id="registered-shortcodes-table">Registered shortcodes</h3>

	<div id="registered-shortcodes-table" class="dropdown-body">

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th>Shortcode</th>
				<th>Function</th>
				<th>Shortcode</th>
				<th>Function</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Shortcode</th>
				<th>Function</th>
				<th>Shortcode</th>
				<th>Function</th>
			</tr>
		</tfoot>
		<tbody id="the-list">

				<?php

				global $shortcode_tags;
				ksort($shortcode_tags); // Alphabetical sort

				$row_num = 0;
				$alternate = '';

				foreach ( $shortcode_tags as $key => $value ) {

          if ($key[0]==="-") continue; // Skip prefixed shortcodes

					if ($row_num % 2 == 0) {

						$alternate = ( $alternate == '' ) ? 'class="alternate"' : '';
						echo '<tr ' . esc_html($alternate) . '>';

					}

					?>
						<td style="vertical-align:top" class="row-title">
								<?php
									echo esc_html($key) . '<br>';
								?>
						</td>

						<td style="vertical-align:top">
							<?php
              // Get function name

              $name = '';
              if (!is_object($value) && !is_callable($value, false, $name)) {
                // Empty
              } else {
                try {
                  if (is_array($value)) {
                    $f = new ReflectionMethod($value[0], $value[1]);
                    $name = $f->class.'::'.$f->name;
                  }
                  if (is_object($value)) {
                    if ($value instanceof Closure) {
                      $f = new ReflectionFunction($value);
                      $name = 'Anonymous function';
                    }
                    $f = new ReflectionClass($value);
                    $name = ($f->isAnonymous() ? (
                      isset( $value->name ) ? 'Object ' . $value->name
                        : 'Anonymous class'
                    ) : 'Class '.$f->name);
                  }
                  $f = new ReflectionFunction($value);
                  $name = $f->name;
                } catch (\Throwable $th) {}

                echo esc_html($name);
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
<hr>

	<div style="margin:20px 0 0 5px;">
		<a href="options-general.php?page=ccs_reference"><em>Reference: Custom Content Shortcode</em></a>
	</div>
</div>
<script type="text/javascript">

// Dropdown action

jQuery(document).ready(function($){

	$('.dropdown-action').on('click',function(){
		var dropID = $(this).data('id');
		$('#'+dropID).slideToggle('fast');

		var $toggled = $(this).children('.toggled');

		if ($toggled.html()==' + ')
			$toggled.html(' - ');
		else if ($toggled.html()==' - ')
			$toggled.html(' + ');
	});

});

</script>
