=== Custom Content Shortcode ===
Contributors: miyarakira
Author: Eliot Akira
Author URI: eliotakira.com
Plugin URI: wordpress.org/plugins/custom-content-shortcode/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y
Tags: loop, query, content, shortcode, post type, field, attachment, comment, sidebar, taxonomy
Requires at least: 3.6
Tested up to: 4.4
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

3.3.2
---

* [loop] - Sort by multiple fields: orderby_2, orderby_3..
* [loop] - Improve menu title links
* [if], [switch] - Add parameters *today* and *day_of_week*
* [user] - Support custom user image fields
* [block] - Support nested blocks
* Multisite compatibility (in progress) - Doc menus
* Thanks to everyone who contributed feedback and suggestions

3.2.9
---

* [content] - silently catch *get_term_link* error when the term does not exist

3.2.8
---

* [loopage] - Correct default text for next post
* [pass] - Add feature to create a range: list=1~10, list=A~Z
* [pass array] - Clarify documentation for array as a series of values
* WP 4.4 compatibility - Adapt to admin style changes
* PHP 7 compatibility - Adapt to syntax changes

3.2.5
---

* [field] - Add parameters: *site=name* and *site=description*
* [field] - Enable getting value from another post when inside ACF repeater or flexible content

3.2.3
---

* [attached] - Correct getting field by specific attachment ID

3.2.2
---

* [field], [if], [pass] - Better support for ACF gallery field
* [field] - Parameter *id* takes priority even inside repeater/flex field
* Support getting fields from ACF option pages: *option=true*
* Add change log to documentation

3.2.0
---

* [if] - Add parameter *and* for multiple conditions
* [if] - Add parameter *decode=true* when comparing a value that is URL encoded
* [if each] - Support multiple terms; matches any of given
* [field] - Support multiple classes separated by comma
* [field] - Add parameter *glue* to use as separator when field is array; default is a comma with space after it
* [switch], [when] - **New** - Check condition against multiple values
* [url current] - Return current URL

3.1.8
---

* [if check] - Condition to check passed value
* [if exists] - Condition to check if enclosed content is not empty
* [get-blog] - Improve shortcode processing

3.1.6
---

* [is login] - Keep current user when used inside [users] loop

3.1.5
---

* [loop] - WP_Query needs uppercase "ID" for *orderby=id*

3.1.4
---

* [if before/after] - Add parameter *field_2* to use as reference for relative date

3.1.2
---

This update includes a number of changes to correct date/time field comparisons, adjusting for differences between WordPress and PHP time functions. This changes existing behavior, especially regarding timezone offset. If you're doing date comparisons, please test your code before using it in production.

* [loop], [if] - Correct date/time field comparisons
* [attached] - Improve use inside nested loops

3.0.4
---

* [attached field] - Get attachment ID from field
* [if field contains] - Improve loose keyword search
* [field date] - Add *date_format=relative*
* [loop] - Correct compare between numeric field values
* [pass] - Add parameter *date_format*
* Add settings for loop pagination permalink

3.0.2
---

* [if] - Add parameters *query* and *route*
* [field] - Add predefined fields: *parent-id*, *parent-slug*

3.0.1
---

* [content], [field] - When trimmed by *length* parameter, add *html=true* to keep HTML tags
* [if field] - Add *contains* parameter to search for keywords; support searching multiple fields
* [the-pagination] - Use same function and parameters as [loopage] for paginating default query

3.0.0
---

* [loop] - Support *offset* and *paged* together
