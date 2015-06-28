#### Custom Content Shortcode

This repository contains the latest development version.

For production use, please see the [offical WordPress plugin page](https://wordpress.org/plugins/custom-content-shortcode/).

---

#### In progress

* [repeater] - Random output
* [loop] - Order by multiple fields
* [comment form] - Comment replies and reply forms
* [menu] - Nested menu loop

function customorderby($orderby) {
  echo $orderby.'<br>';
//  return 'mt1.meta_value, mt2.meta_value, mt3.meta_value ASC';
  global $wpdb;
  return " {$wpdb->postmeta}.mt1.meta_value";
}
add_filter('posts_orderby','customorderby');

$wp_query = new WP_Query(array(
    'post_type'    => 'shop',
    'meta_key'     => 'meta_1',
    'meta_query'  => array(
        array(
            'key' => 'meta_1',
        ),
        array(
            'key' => 'meta_2',
        ),
        array(
            'key' => 'meta_3',
        )
    )
));
remove_filter('posts_orderby','customorderby');
