=== Custom Content Shortcode ===
Contributors: miyarakira
Author: Eliot Akira
Author URI: eliotakira.com
Plugin URI: wordpress.org/plugins/custom-content-shortcode/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y
Tags: loop, query, content, shortcode, post type, field, taxonomy
Requires at least: 3.6
Tested up to: 4.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display posts, pages, custom post types, custom fields, files, images, comments, attachments, menus, or widget areas

== Description ==

= Overview =  
<br />
From a single field to entire pages, Custom Content Shortcode is a set of commands to display content where you need.

The **[content]** shortcode displays any of the following: *posts, pages, custom post types, custom fields, files, images, attachments, menus,* or *widget areas*.

The **[loop]** shortcode performs query loops. It can display, for example, available products in a category, or excerpts from the 5 most recent posts. You can query by parameters such as: *post type, category, custom taxonomy, date,* and *custom field values*.

There is a reference page under Settings -> Custom Content.


= Included =  
<br />
You'll find many useful features:

* **Overview** of your site's content structure
* Simple **gallery field** for any post type
* Include **HTML/PHP/CSS/JavaScript** files
* **Relative URLs** for links and images
* Display content for **admin, specific user, login status**
* User name, ID, **login/logout links** with redirect
* **Comments** list, input form or comment count
* Display content based on mobile detect

Support for other plugins:

* [Advanced Custom Fields](http://wordpress.org/plugins/advanced-custom-fields/) - Image, relationship, gallery, repeater, flexible content
* [WCK Fields and Post Types](http://wordpress.org/plugins/wck-custom-fields-and-custom-post-types-creator/) - Text, select, checkbox, radio, upload, repeater
* [Bootstrap](http://getbootstrap.com/) Carousel, navbar menu, pills, stacked


== Installation ==

1. Install from *Plugins -> Add New*
1. Or download the .zip and extract to *wp-content/plugins*
1. Activate the plugin from the *Plugins* menu
1. See: *Settings -> Custom Content*


== Screenshots ==

1. Documentation and examples
2. Content overview page
3. Gallery field

== Frequently Asked Questions ==

**Q:** How do I remove unwanted formatting inside shortcodes?

**A:** WordPress auto-formats the post content using the wp_autop filter. This can cause unwanted `<p>` and `<br>` tags around line breaks. To prevent this, go to Settings -> Custom Content, and under the settings tab, enable: Move wp_autop to *after* shortcodes.

**Q:** Switching from text to visual editor breaks my HTML.

**A:** The visual editor (TinyMCE) sometimes misinterprets HTML code placed in the text editor. There are several ways to address this issue.

* Disable the visual editor for certain posts or post types, with the [Raw HTML](http://wordpress.org/plugins/raw-html/) plugin. However, if the post needs to be editable by the client, this won't be ideal.

* Put the code in a custom field, then include it in the post. For example: *[content field="code_block"]*

* Put the code in a file, then include it into the post. For example: *[load dir="views" file="recent-posts.html"]*

* You can create a custom field called *html*. This special field is displayed **instead of** the post content. All your HTML and shortcodes can be put there, then place *[content]* where you need the content of the visual editor to appear.

* Put your code in a text widget, and use a plugin like [Widgets on Pages](http://wordpress.org/plugins/widgets-on-pages/).


== Upgrade Notice ==



== Changelog ==

= 1.3.0 =

* Refactor all modules for better code organization and performance
* [cache] - Cache page partials
* [timer] - Tool to measure performance
* Gallery field - Improve UI and gallery field loop

= 1.2.2 =

* [each] - Assume *name* by default
* [loop tag] - Clean up any extra spaces in tag list
* [load view] - Load a template from *views* folder

= 1.2.1 =

* **[pass]** - Add *fields* parameter: pass multiple fields
* **[pass]** - Add *field_loop* parameter: loop through a comma-separated list stored in a field
* **[field image]** - Check with ACF 5
* **Settings** - Enable *shortcode unautop* to remove `<p>` tags around shortcodes
* **Content Overview** - Show available user meta fields
* Improve documentation

= 1.1.9 =

* Fix compatibility for PHP older than 5.3

= 1.1.8 =

* **[loop], [for]** - Add *trim* parameter to remove space or comma at the end
* **[loop fields]** - Expand a list of fields to replace {FIELD} tags
* **[field image]** - Display image field: *image="field_name"*
* **[wck-field]** - Enable shortcodes in field

= 1.1.7 =

* **[wck-field], [wck-repeat]** - Support WCK Fields/Post Types

= 1.1.6 =

* **[field image-link]** - Featured image with link to post
* **[field thumbnail-link]** - Featured image thumbnail with link to post
* **[loop id]** - Preserve post ID order if multiple values given
* **[loop columns]** - Skip empty columns

= 1.1.5 =

* **[related]** - Loop through ACF relationship field
* **[if image]** - If current post has a featured image
* **Settings** - Enable shortcodes inside Text widget
* Test compatibility with WordPress 4.0
* Add donate link on plugin page
* Improve documentation

= 1.1.3 =

* **[attached]** - Make sure to get all attachments
* **[attached]** - Add parameters: *count, offset, orderby, order*

= 1.1.2 =

* **[if parent]** - If parent matches ID or slug
* **[else]** - Display if condition is not met

= 1.1.1 =

* **[attached]** - Display URL to attachment file: [field url]
* **[attached]** - Display link to attachment page: [field page-url]

= 1.1.0 =

* **[is role]** - Display based on user role
* **[is capable]** - Display based on user capability
* **[user role]** - Display user role
* **[field image]** - *image_class* - add class to the `<img>` tag
* **[loop]** - Improve query by date field value; *value="future"* or *"past"*

= 1.0.9 =

* **[attached]** - Loop through attachments in current post or queried posts
* **[if attached]** - If post has attachment
* **[content]** - Display image by default in attachment loop

= 1.0.8 =

* **[loop]** - Parent page by ID: *parent="2"*
* **[if flag]** - Check if featured image exists: *flag="image"*

= 1.0.7 =

* **[comments]** - Fix comment loop with *id* parameter

= 1.0.6 =

* **New plugin settings** - Enable/disable non-essential modules; option to move wp_autop filter to *after* shortcodes, to avoid unwanted formatting
* **[if flag]** - Enable outside loop

= 1.0.4 =

* **[comments]** - Loop through recent comments, and display comment fields
* **[if comment]** - If current post in a query loop has any comments

= 1.0.2 =

* **[loop]** - Query by custom date field, for example: *field="event_date" compare=">" value="now"*
* **[load]**, **[url]** - Make sure to return correctly if wp-content folder is renamed

= 1.0.1 =

* **[field title-link]** - Return the link correctly when limiting by word or length
* **[if not every="X"]** - When the post is *not* every X in the loop
* **[content]** - Make sure to minimize queries when inside loop

= 1.0.0 =

* **[loop]** - Test and improve sort by timestamp
* **[content field="title-link"]** - Post title wrapped in a link to post
* **[field]** - Shorter version of [content field=""] - for example, [field title]
* **[comment template]** - Make sure to look in child theme first, if it exists

= 0.9.9 =

* Mobile detect - Re-include module: back by demand

= 0.9.8 =

* **[if]** - Add parameters: *type, slug, category, taxonomy, term, field, value*
* **[load]** - Improve loading CSS or JS from external site: either specify dir="web" or use `http://` in the file name
* Organize and simplify: remove mobile detect library

= 0.9.6 =

* **[loop]** - Move wpautop filter back to before shortcode; will add an option if this solves formatting issue for some people

= 0.9.5 =

* **[if every]** - For every X number of posts: *every="3"*
* **[if first]** - For the first post
* **[if last]** - For the last post
* **[loop]** - Include sticky posts for parameter *count*
* **[loop]** - Reset query when [loop] is inside another loop
* **[loop]** - Process shortcode *before* wpautop filter to prevent unwanted formatting

= 0.9.4 =

* **[for each]** - For each category/tag/taxonomy of current post: *current="true"*
* Added a note in the documentation about using [loop] to create multiple Bootstrap carousels

= 0.9.3 =

* **[for each]** - For each child category, by parent slug; *each="category" parent="products"*
* **[content field]** - Add *edit-url*; only shows when user is logged in and can edit posts

= 0.9.2 =

* **[pass]** - Correctly pass when field value is an array (for example, post IDs)

= 0.9.1 =

* **[if empty]** - Display something when there is no query result
* **[url login], [url logout]** - Update *go* parameter; by default, return to the same page


= 0.9.0 =

* **[if flag]** - If a field has value, then display something
* **[if no_flag]** - If a field is empty, then display something
* **[for each]** - Now able to use inside loop; display for each category, tag or taxonomy
* Content Overview - Display all taxonomy terms, even unused ones

= 0.8.8 =

* **[content]** - Display multiple vales from checkbox or selector field
* **[repeater]** - ACF: repeater field (correct shortcode name)
* **[loop]** - get a post by name or ID, for repeater field to target

= 0.8.7 =

* Fixed compatibility with a theme

= 0.8.6 =

* Fixed compatibility with older versions of PHP

= 0.8.5 =

* **[loop]** - Improved parameter *clean*
* **[loop]** - Testing parameter *blog* for multisite: *blog="2"*
* **[content]** - Added parameter *meta* for displaying author meta
* **[content]** - Enabled parameter *date_format* for custom field
* **[user]** - Added parameter *field* to display user meta
* **[clean], [format]** - Added format shortcodes

= 0.8.3 =

* **[load]** - Improve performance

= 0.8.2 =

* **[loop]** - Improve formatting parameters: *clean*, *strip_tags*, *allow*

= 0.8.0 =

* **[loop]** - Field and taxonomy queries: make *compare* and *relation* parameters case-insensitive

= 0.7.9 =

* **[loop]** - Taxonomy query - multiple values possible: *value="apple, green"*
* **[loop]** - Taxonomy query - add parameter *compare="AND"*, *compare="NOT"*
* **[loop]** - Correctly display posts with tag(s): *tag="tag1,tag2"*
* **[loop]** - Add parameter *pad* for column padding: *pad="0 10px"*

= 0.7.7 =

* **[for]** - Create loops for each category, tag, or taxonomy; see reference page
* **[loop]** - Improve simple columns

= 0.7.6 =

* **[content]** - Improve *more* tag display: *[content more="true"]*

= 0.7.3 =

* **[loop]** - Add parameter *columns* for simple columns feature: *columns="3"*
* **[content]** - Add parameter *embed* to autoembed URLs: *embed="true"*; it's enabled by default for post content, i.e., [content] inside a loop

= 0.7.2 =

* **[loop]** - Enable multiple values for post ID: *id="1,3,7"*
* **[loop]** - Add parameter *exclude* by post ID: *exclude="7,15,21"*
* **[content]** - Add parameter *more* to display content up to the more tag
* **[content]** - Add field *attach-link* to display image attachment page link
* **[content]** - Process content in correct order: do_shortcode, then wpautop
* **[comment total]** - New parameter to display total comment count of last loop
* **[load]** - Return output instead of echo
* **Gallery Field** - Add all image sizes for parameter *size*

= 0.7.1 =

* **[loop]** - Improved parameter *checkbox* to query by checkbox value(s)
* **[is user]** - Enable multiple values, i.e., *user="1,3,7,guest"*

= 0.7.0 =

* **[content]** - Display correct author name
* **[content]** - Added field *modified* to display date of last post update
* **[loop]** - Added field *parent* (by slug) to display children
* **[loop]** - Improved *orderby="menu_order"*
* **[loop]** - Improved *orderby="modified"*
* Fixed compatibility with a theme

= 0.6.9 =

* **[user]** - User name, id, e-mail, full name, avatar
* **[loop]** - Added parameter *clean="true"* to remove extra *p* and *br* tags
* **[content]** - Added field *title-length*
* **[content]** - Display correct image sizes
* Other minor improvements: performance and content overview page

= 0.6.8 =

* No change in function; improved code so there are no PHP notices when debug is on

= 0.6.5 =

* **[content]** - Add *out=“slug”* to output post taxonomy slug
* **[content]** - Improved check for published status
* **[content]** - Added *post* and *page* parameter, for example: *[content page=“about”]*

= 0.6.4 =

* **[loop]**, **[content]** - Added parameter *status* to filter by post status: *any, publish, pending, draft, future, private*; the default is *publish*

= 0.6.3 =

* Fixed documentation

= 0.6.2 =

* **[loop]** - Added parameter *checkbox* and *checkbox_2*, to query checkbox values
* **[content]** - Added parameter *checkbox* to display checked values

= 0.6.1 =

* **[load]** - Added parameter *dir=“web”*

= 0.6.0 =

* **[content]** - Added *return=“url”* parameter, to return URL of an image; this can be used to set a background image according to a field
* **[content]** - Added *in* parameter, to specify if the image field contains an attachment ID, URL, or object; default is ID
* **[content]** - Added *size* parameter for image size; depending on the theme, you can set *thumbnail*, *medium*, *large*, or custom size

= 0.5.9 =

* Fixed display of shortcode functions in content overview

= 0.5.8 =

* **[content]** - Added *allow* parameter - strips all HTML tags except allowed

= 0.5.7 =

* **[loop]** - Fixed query when field value includes ampersand symbol

= 0.5.6 =

* Content overview: added list of default fields and registered shortcodes
* Reference page: fixed logo when *wp-content* folder has been renamed

= 0.5.5 =

* Content overview: fixed display when there are no fields found

= 0.5.4 =

* **[content]** - post URL field now returns clean permalink structure

= 0.5.3 =

* Improved performance of content overview page

= 0.5.1 =

* Added an overview of site content structure: *Dashboard -> Content*

= 0.5.0 =

* **Mobile Detect** - display content based on device type: *is_phone, isnt_phone, is_tablet, is_mobile, is_computer*
* **[redirect]** - redirect user to another URL: based on login status, device type, etc.
* **[load]** - now able to include files with HTML, PHP script, and shortcodes
* **[content]** - added author ID, URL, avatar

= 0.4.9 =

* Fixed compatibility issue with a theme

= 0.4.8 =

* **[loop]** - Added filter by date: *year*, *month*, *day*

= 0.4.7 =

* Better support for Advanced Custom Fields: gallery, repeater and flexible content - *flex, repeat, layout, sub, sub_image, and acf_gallery*
* Added new pages to reference section
* Fixed display of bullet points in the admin panel

= 0.4.6 =

* Improved reference page under *Settings -> Custom Content*, and simplified *readme.txt* to avoid duplicate content
* **[content]** - Added native gallery parameters: *orderby*, *order*, *columns*, *size*, *link*, *include*, *exclude*

= 0.4.5 =

* **[pass]** - Added *varible* parameter - displayed using {VAR} or {VARIABLE}
* **[loop]** - Made *title* parameter case-insensitive

= 0.4.4 =

* **[loop]** - Added *title* parameter; Added conditional statement: *if="all-no-comments"*
* Better code management (on-going)
* Started reference page

= 0.4.2 =

* **[list_shortcodes]** - Display a list of shortcodes defined
* Fixed compatibility issue with a theme

= 0.3.9 =

* **[loop]** - Added meta query parameters: field, compare, value, relation, field_2, compare_2, value_2
* **[loop]** - Added *strip_tags* parameter to remove `<p>` and `<br>` tags inside the loop
* Added **[p]** and **[br]** shortcodes to manually create paragraphs and break lines

= 0.3.8 =

* Added *offset* parameter to offset the query loop by a number of posts, for example: start from the 3rd most recent post

= 0.3.7 =

* Added *date_format* parameter to display post dates in a custom format

= 0.3.6 =

* Fixed one line to be compatible with older versions (less than 5.3) of PHP

= 0.3.5 =

* Added *series* parameter to order posts by a series of custom field values

= 0.3.4 =

* Added *taxonomy*, *value*, *orderby*, *order*, *meta_key*
* Added *align* parameter - left, center, right
* Fixed fetching repeater subfield from post other than current

= 0.3.3 =

* Changed *format* parameter - only post content is formatted (paragraph tags and line breaks) by default

= 0.3.2 =

* Added *words* and *length* parameters to limit number of words/characters

= 0.3.1 =

* Changed *class* parameter to work on all fields
* Added *ul* parameter to **[content menu]** - ul class to allow Bootstrap or other customization
* Moved **gallery field** settings from Plugins to Settings
* Added ability to override post content with the *html* field

= 0.2.8 =

* Created documentation page
* No change in code

= 0.2.7 =

* Added **[is]** shortcode - display content when user is administrator, non-administrator, logged in, or logged out
* Added *login* and *logout* parameter to **[url]** shortcode - display login/logout link url, also possible to redirect
* Improved the way *css* and *js* fields are loaded when outside the loop

= 0.2.6 =

* Added **[comment]** shortcode for displaying comment count, input form and template

= 0.2.5 =

* Added *gfonts* parameter for loading Google Fonts

= 0.2.4 =

* Added **[url]** shortcode
* Added a few parameters to **[load]** and **[live-edit]** shortcodes
* Added Bootstrap carousel support for *acf_gallery*
* Fixed live-edit when not logged in
* Support for older version of PHP

= 0.2.3 =

* Added support for Advanced Custom Fields: *acf_gallery*
* Added *admin* and *editor* parameters for Live Edit

= 0.2.2 =

* Added **[live-edit]**

= 0.2.1 =

* **[loop]** - Added *x* parameter - repeat content x times
* Added support for Advanced Custom Fields: *repeater*

= 0.2.0 =

* **[load]** - Added *dir* parameter to choose directory
* **[content]** - Added *image* parameter for image fields
* **[content]** - Get specific image from gallery field


= 0.1.9 =

* Added **[navbar]** - Bootstrap navbar menu

= 0.1.8 =

* Cleaned code
* Load *css* and *js* fields into the header/footer
* Added shortcodes: **[css]**, **[js]**, and **[load]**
* Fixed attachment image showing only thumbnail size

= 0.1.7 =

* Better documentation

= 0.1.6 =

* **[content]** - Added menu and sidebar content
* **[loop]** - Pass a field content as parameter to another shortcode

= 0.1.5 =

* Added simple gallery fields
* Added attachment type and fields

= 0.1.4 =

* Added **[loop]** shortcode for query loops
* Format post content using the_content filter

= 0.1.3 =

* Changed shortcode to **[content]**
* Added banner image to Wordpress plugin page

= 0.1.2 =

* Better documentation

= 0.1.1 =

* Simplified code, added a few parameters

= 0.1 =

* First release




