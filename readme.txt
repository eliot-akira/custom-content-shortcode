=== Custom Content Shortcode ===
Contributors: miyarakira
Author: Eliot Akira
Author URI: eliotakira.com
Plugin URI: wordpress.org/plugins/custom-content-shortcode/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y
Tags: loop, query, content, shortcode, post type, field, attachment, comment, sidebar, taxonomy
Requires at least: 3.6
Tested up to: 4.9.6
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

3.6.7
---

* [attached] - Support parameter *count* for gallery field
* Meta Shortcodes - Improve code editor

3.6.5
---

* Improve compatibility with PHP <5.3
* Correct static function call to get_image_field()

3.6.3
---

* [attached] - Add parameter *orderby=random*
* [is] - Improve check for login/logout status
* [loop include=children] - When *list=true*, append children after their parent

3.6.0
---

This update includes a newly rewritten feature for [loop include=children]. Previously, children were appended after their parent wherever they occurred; now all descendants are included as equals and follow loop query parameters like *orderby*. If you were using this feature, please test before deploying to production.

* [loop] - Add parameter *level* to set descendant level (number of generations)
* [loop include=children] - Complete rewrite and update: improve handling of additional queries, internal [if] conditions, counting levels
* [pass] - Add support for *date_format*

3.5.8
---

* [loop] - Add predefined values *published* and *modified* for field query
* [loop], [comment] - Optimize getting total comment count
* [if field] - Support WCK fields: inside [metabox] loop, or add parameter *metabox*

3.5.7
---

* [for type] - Post type rewrite slug for use in URLs: [each prefix]
* [for type] - Post type archive URL: [each url]
* [if] - Support for post type loop: if *first*, *last*, *every*, *archive*, *prefix*

3.5.6
---

* [if] - Fix refactored code to handle default condition
* Pagination - Fix warning when permalink slug does not exist
* [if] - Optimize/refactor code
* [note] - Ignore note content completely: do nothing
* [for type] - Correctly pass post type slugs to [loop-count]
* [for type] - Add *debug=true* to inspect post type query
* [if] - Improve check for empty or zero value
* [loop] - Add *debug=true* to inspect query
* [pass] - Add documentation for nested levels
* Improve support for The Events Calendar plugin: *display=custom*

3.5.1
---

* [loop] - Add parameter *orderby=child-date*
* [for type] - Improve check for included post types
* [loop-count] - By default, perform query to count posts when inside [for type]

3.4.8
---

* [field] - Add predefined fields: *post-class*, *gallery-url*
* [field] - Add parameter *until* to trim value until specified character(s)
* [related] - Add parameter *fill=true* to include unrelated posts until post count is met
* [related] - Add parameter *status* to get future or draft posts
* [loop exists] - Support `[if empty]` to display something if no post exists
* [loop-count] - Display post count of query result if loop parameters are given
* [the-loop] - Allow nesting by `-` prefix
* [pass field] - Add parameter *escape=true* to escape HTML special characters

3.4.4
---

* [array] - Add parameters *slugify* and *glue*
* [calc] - Share variables with get/set shortcodes
* [field] - Add default field *after-excerpt* to show content after read more tag
* [field] - Add *https=true* to prepend protocol to field value
* [field excerpt] - Apply *get_the_excerpt* filter to result
* [format] - Add parameters: *ucfirst*, *ucwords*, *plural*
* [if empty] - Improve check for empty ACF repeaters
* [if every] - Improve when combined with *first* or *last* parameters
* [if total] - Condition to check total post count
* [if var] - Check variable set by [set] or [calc]
* [loop] - Add parameters *tax_compare* and *field_compare*
* [loop exists] - Improve check for empty query result
* [loop name] - Support query by multiple post names
* [pass list=$var] - Use variable as parameter value
* [repeater] - Improve handling of nested repeaters
* [set] - Add *trim=true* to trim spaces and new lines from start/end of value
* [users] - Support query by multiple roles, for example: role=admin,editor
* [when start] - Switch condition when value starts with a string
* Gallery field - Improve sort function and thumbnail display
* New optional module: Meta Shortcodes

3.4.2
---

* [loop] - Improve published and modified date query for specific value

3.4.1
---

* [field modified] - Support *date_format=relative*
* [loop child=this] - Correctly handle *include=this* when current post is top parent
* [if route], [switch route] - Support matching URL routes by wildcards
* [get], [set], [pass] - Get/set/pass variables; see Advanced > Extras > Variables
* Documentation - Update to Parsedown v1.6.0

3.4.0
---

* [loop child=this] - Loop through current post's parents from the top
* [comments] - Support filter by taxonomy term ID
* [if host] - Check server host name: can be used to distinguish between localhost or staging/public site

3.3.8
---

* [loop exists] - Perform a query first to check if any post matches the given parameters
* [format] - Format number or date; see Advanced: Extras
* [calc] - Perform spreadsheet-like calculations; see Optional: Math
* Documentation - Add a note about how to create a custom image size using [Simple Image Sizes](https://wordpress.org/plugins/simple-image-sizes/)

3.3.6
---

* [if] - Add parameter *compare=not*
* [loop] - Correctly handle *list_class* and *item_class* as before
* Improving multisite compatibility - thanks to @keyra

3.3.4
---

* [users] - Add *list* parameter
* [flex], [layout] - Add *default* layout
* [if] - Improve condition with *and*

3.3.3
---

* [loop] - Sort by multiple fields: orderby_2, orderby_3..
* [loop] - Improve menu title links
* [if], [switch] - Add parameters *today* and *day_of_week*
* [user] - Support custom user image fields
* [block] - Support nested blocks

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
