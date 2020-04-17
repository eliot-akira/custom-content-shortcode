=== Custom Content Shortcode ===
Contributors: miyarakira
Author: Eliot Akira
Author URI: eliotakira.com
Plugin URI: wordpress.org/plugins/custom-content-shortcode/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y
Tags: loop, query, content, shortcode, post type, field, attachment, comment, sidebar, taxonomy
Requires at least: 4.6
Tested up to: 5.4
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display posts, pages, custom post types, fields, attachments, comments, images, users, menus, sidebars

== Description ==

= Overview =
<br />
From a single field to entire pages, Custom Content Shortcode is a set of commands to display content where you need.

The **[content]** shortcode displays any of the following: *posts, pages, custom post types, fields, images, menus,* or *widget areas*.

The **[loop]** shortcode performs query loops. It can display, for example, available products in a category, or excerpts from the 5 most recent posts. You can query by parameters such as: *post type, taxonomy, date,* and *field values*.

There is a reference section under Settings -> Custom Content.

= Included =
<br />
Here are some of the included features:

* Wide range of **query parameters** to display site content
* **Conditional** content based on field value, login status, etc.
* Overview of your site's **content structure**
* **Relative URLs** for links and images
* **Cache** the result of a query
* Optional: **Gallery field**, **Mobile Detect**, **Math**

Support for other plugins: [Advanced Custom Fields](http://wordpress.org/plugins/advanced-custom-fields/), [WCK Fields and Post Types](http://wordpress.org/plugins/wck-custom-fields-and-custom-post-types-creator/)


== Installation ==

1. Install & activate from *Plugins -> Add New*
1. See: *Settings -> Custom Content*


== Screenshots ==

1. Documentation and examples
2. Content overview page
3. Gallery field


== Upgrade Notice ==


== Changelog ==

3.8.6
---

* Improve getting shortcode function names in Content Overview

3.8.5
---

* Correct output of ACF field label when multiple choices are selected

3.8.4
---

* Remove deprecation warning about non-static method CCS_Loop::include_children

3.8.3
---

* Improve compatibility with PHP 7.2
* Update to Parsedown library 1.7.3 for documentation pages
* Math module: Use variable function call for built-in functions

3.8.2
---

* Pass: Allow passing user fields that are arrays or objects - thanks to @jrevillini
* Improve compatibility with PHP 5.3

3.8.1
---

* Compatibility with WP 5.0.2 and PHP 7.2
* Improve support and documentation for shortcodes inside HTML attributes

3.8.0
---

* [link] - Add parameter *download*; set "true" or file name

3.7.9
---

* [if user_field contains] - Support searching multiple user fields
* [related] - Support ACF relational type users
* [related user_field] - Support related posts from a user field

3.7.8
---

* [if image] - Check correct current post when inside [related]

3.7.7
---

* [related] - Add parameter *offset* to skip the first X number of posts
* [if] - Improve logic to count repeater fields

3.7.6
---

* [each] - Add default field *count* for each term's post count

3.7.5
---

* [if] - Add parameter *count* for field value array, such as relationship fields
* [if] - Improve field=content,excerpt with parameter *contains*
* [is] - Allow nested
* Content overview: cleaner list of shortcodes

3.7.4
---

* [loop], [loopage] - Add parameter *query* to use custom query variable for pagination

3.7.3
---

* [user] - Add field *registered* and parameter *format* ("relative" or custom format)
* Settings - Add option to enable shortcodes in widget title

3.7.1
---

* [format] - Add parameters *split* and *part*; handle field values of number type in list
* [attached] - Add field *download-url*, to get URL to actual PDF file instead of generated preview image
* [url register] - URL to registration form under wp-login
* [pass] - Add parameter *trim=all* to remove all white space, new lines, tabs
* ACF [related] - Add parameters *start* and *count*
* Improve use of content filter with other plugins; support for Beaver Themer in progress

3.7.0
---

* Minor fixes in reference pages
