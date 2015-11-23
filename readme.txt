=== Custom Content Shortcode ===
Contributors: miyarakira
Author: Eliot Akira
Author URI: eliotakira.com
Plugin URI: wordpress.org/plugins/custom-content-shortcode/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y
Tags: loop, query, content, shortcode, post type, field, attachment, comment, sidebar, taxonomy
Requires at least: 3.6
Tested up to: 4.3.3
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

* Overview of your site's **content structure**
* **Pagination** for post loops
* Display **comments** and **attachments**
* **User info** and content based on user status
* **Relative URLs** for links and images
* **Cache** the result of a query
* Optional: **Gallery field**, **Mobile Detect**

Support for other plugins:

* [Advanced Custom Fields](http://wordpress.org/plugins/advanced-custom-fields/)
* [WCK Fields and Post Types](http://wordpress.org/plugins/wck-custom-fields-and-custom-post-types-creator/)


== Installation ==

1. Install & activate from *Plugins -> Add New*
1. See: *Settings -> Custom Content*


== Screenshots ==

1. Documentation and examples
2. Content overview page
3. Gallery field


== Upgrade Notice ==


== Changelog ==

= 3.1.9 =

* [if] - Add parameter *and* for multiple conditions
* [if each] - Support multiple terms; matches any of given
* [field] - Support multiple classes separated by comma
* [field] - Add parameter *glue* to use as separator when field is array; default is a comma with space after it
* [switch], [when] - New: Check condition against multiple values
* [url current] - New: Return current URL

= 3.1.8 =

* [if check] - Condition to check passed value
* [if exists] - Condition to check if enclosed content is not empty
* [get-blog] - Improve shortcode processing

= 3.1.6 =

* [is login] - Keep current user when used inside [users] loop

= 3.1.5 =

* [loop] - WP_Query needs uppercase "ID" for *orderby=id*

= 3.1.4 =

* [if before/after] - Add parameter *field_2* to use as reference for relative date

= 3.1.2 =

This update includes a number of changes to correct date/time field comparisons, adjusting for differences between WordPress and PHP time functions. This changes existing behavior, especially regarding timezone offset. If you're doing date comparisons, please test your code before using it in production.

* [loop], [if] - Correct date/time field comparisons
* [attached] - Improve use inside nested loops

= 3.0.4 =

* [attached field] - Get attachment ID from field
* [if field contains] - Improve loose keyword search
* [field date] - Add *date_format=relative*
* [loop] - Fix compare between numeric field values
* [pass] - Add parameter *date_format*
* Add settings for loop pagination permalink

= 3.0.2 =

* [if] - Add parameters *query* and *route*
* [field] - Add predefined fields: *parent-id*, *parent-slug*

= 3.0.1 =

* [content], [field] - When trimmed by *length* parameter, add *html=true* to keep HTML tags
* [if field] - Add *contains* parameter to search for keywords; support searching multiple fields
* [the-pagination] - Use same function and parameters as [loopage] for paginating default query

= 3.0.0 =

* [loop] - Support *offset* and *paged* together

= 2.9.9 =

* [array-count] - Display index inside array loop; starts from 1
* [content area] - Fix: now works the same as parameter *sidebar*
* [field slugify] - Create sanitized slug from field value
* [if comment_author] - Optimize checking for comment author when in loop
* [get-blog] - Changed name from `blog` - undocumented shortcode to switch blogs on multi-site

= 2.9.7 =

* [loop], [if] - Add parameter *comment_author*

= 2.9.6 =

* [content], [field] - Improve parameter *custom=true*
* [for] - Add documentation about displaying parent term's name inside nested loop

= 2.9.5 =

* [array] - Support nested arrays
* [content], [field] - Set *custom=true* for custom field with same name as a predefined field
* [link] - Set *mail=true* to add `mailto:` before link address

= 2.9.4 =

* [comment] - Improve handling of parameters when displaying comment content with no field set
* [field] - Parameters *class*, *link_id*: set element class/ID for predefined field that is a link
* [user slug] - Sanitized user name for use in URL; same as user *nicename*
* [user edit-link], [user edit-url] - Link or URL to user profile edit page in admin

= 2.9.3 =

* [if every] - Set *first* or *last* parameter to *true*, to include first/last post
* [link] - Add parameters *id* and *name*
* [pass array] - Pass values from a field stored as array of key-value pairs
* Documentation - Note about displaying Google map field in Optional -> ACF

= 2.9.1 =

* [field image] - Add parameter *return* to display: *url, id, title, caption, description*
* [load] - Improve compatibility for shortcodes in HTML attributes
* [loop menu] - Loop through menu items
* HTML Blocks module - Add: *dl, dt, dd*

= 2.9.0 =

* Settings - Option to enable shortcodes in `the_excerpt()`

= 2.8.8 =

* [array] - Field value stored as JSON object/array: *json=true*
* [field] - Featured image and thumbnail have *alt* attribute by default
* [if first/last] - Check index of multiple arrays when used with [array]
* [if each_field] - Check taxonomy term field inside for/each loop
* Settings - Option to deactivate specific shortcodes
* Add documentation about shortcode use in PHP template
* Improve compatibility for shortcodes in sidebar widgets

= 2.8.6 =

* [field] - Display ACF field label: *out=field-label*

= 2.8.5 =

* [content], [field] - Add *texturize* parameter to apply text transformations like smart quotes, apostrophes, dashes, ellipses
* [loop taxonomy] - Support post format, for example: *taxonomy=format term=audio*
* [loopage anchor] - Add anchor link to the paged URLs
* [loopage-prev], [loopage-next] - Display links to previous/next page in loop
* [raw], [related] - Minor improvements for reliability

= 2.8.4 =

* Compatibility with WP 4.3 for shortcodes in Visual Editor

= 2.8.3 =

* [user archive-url] - User posts archive URL
* [user archive-link] - Display name linked to user posts archive

= 2.8.2 =

* [if count] - Check current index in loop
* [field post-format] - Display post format for current post
* [if format] - Check current post format
* [image] - Display an image element with URL using shortcodes

= 2.8.0 =

* [if author] - Check current post by author ID or user name; set to *this* for current user
* [the-loop] and [the-pagination] - Default query loop and pagination, for use in templates

= 2.7.9 =

* [for each] - Add *children* parameter to get all descendants when using *term* or *parent*
* [if] - Fix when ACF module is not enabled
* [related] - Fix getting posts related by multiple taxonomies
* [taxonomy] - Fix predefined field *link*

= 2.7.8 =

* [field] - Support [ACF Image Crop](https://wordpress.org/plugins/acf-image-crop-add-on/) field; see documentation under Optional -> ACF

= 2.7.7 =

* [comments] - Add parameter *author* to get comments on posts by author ID or user name
* [link] - Generate a link based on field value; see documentation under Main Features -> Field

= 2.7.6 =

* [raw] - Improve compatibility with Wistia video embed

= 2.7.5 =

* [attached] - Improve getting current attachment ID
* [if] - Add parameters *first* and *every* for ACF repeater field
* [related] - Set parameter *children=true* to include posts related by child terms

= 2.7.4 =

* Continued efforts to improve compatibility for all shortcodes

= 2.5.8 =

* [for] - Fix *count* parameter to limit number of terms
* [today] - Use `date_i18n` for date internationalization

= 2.5.6 =

* [loop], [field] - Add parameters *escape* and *unescape*
* [loop-count] - Output current index of the loop, starting from 1

= 2.5.2 =

* [field] - Add predefined field *link* and parameter *link_text*, to encourage not using shortcodes in HTML attributes

= 2.5.1 =

* Addressing issue with shortcodes inside HTML attributes after WP 4.2.3 update

= 2.4.8 =

* [if sticky] - Check if post is sticky

= 2.4.7 =

* [array] - Support looping through multiple selections and available choices for ACF checkbox/select/radio field

= 2.4.6 =

* [if] - Check if post has excerpt: `[if field=excerpt]`
* [loop] - Include sticky post: *sticky=true*

= 2.4.4 =

* [content], [field] - Trim to last sentence: *sentence=true*
* [random] - Display a random integer, for example: `[random 0-99]`
* [pass random] - Pass a random integer to a shortcode parameter, for example: `[pass random=0-99]`
* [pass global=query] - Pass query variables from the URL

= 2.4.1 =

* [loop] - Fix parameter *start* to get posts whose field value starts with string
* [related] - Get posts related by a taxonomy field: *taxonomy_field=field_name*

= 2.4.0 =

* [content words] - Display content after X words: *words=-15*
* [loop] - Multiple values for single field query: *value=1,2,3*

= 2.3.9 =

* [comment avatar] - Get comment author's avatar from e-mail; thanks @indrayn!

= 2.3.8 =

* [content] - Removed infinite loop detection

= 2.3.6 =

* [pass] - Pass current URL route and its parts: *global="route"*

= 2.3.5 =

* [repeater] - Display a random row from a repeater field: *row="rand"*

= 2.3.4 =

* [content] - Detect and prevent infinite loop if showing current post
* [flex] - Support nested ACF Flexible Content field

= 2.3.3 =

* [loop] - Get previous/next post in the loop with [prev] and [next]
* [today] - Display today's date
* [*] - Place comment in the visual editor
* [br], [p] - These will work without HTML Blocks module enabled

= 2.3.0 =

* [comment template] - Load *comments.php* from theme
* [loop fields] - Only field tags specified by the *fields* parameter will be rendered, same as the [pass] shortcode

...
