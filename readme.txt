=== Custom Content Shortcode ===
Contributors: miyarakira
Author: Eliot Akira
Author URI: eliotakira.com
Plugin URI: wordpress.org/plugins/custom-content-shortcode/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=T3H8XVEMEA73Y
Tags: loop, query, content, shortcode, post type, field, attachment, comment, sidebar, taxonomy
Requires at least: 3.6
Tested up to: 4.2.3
Stable tag: 2.5.5
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

== Frequently Asked Questions ==

**Q:** How do I protect shortcodes from post content formatting?

**A:** Post content is automatically formatted, which can cause unwanted `<p>` and `<br>` tags inside multi-line shortcodes. To prevent this, wrap the section with the [raw] shortcode. You can enable it under Settings -> Custom Content.

**Q:** How do I protect HTML code from the visual editor?

**A:** The visual editor (TinyMCE) sometimes misinterprets HTML tags placed in the text editor. There are several ways to address this issue.

* Use the built-in HTML block shortcodes. See under Settings.

* Disable the visual editor for specific posts or post types, with the [Raw HTML](http://wordpress.org/plugins/raw-html/) plugin. However, if the post needs to be editable by the client, this won't be ideal.

* Put the code in a custom post type, then include it into the post. For example: *[content type="template" name="recent-posts"]*

* Put the code in a custom field, then include it in the post. For example: *[field code_block]*

* Put the code in a file, then include it into the post. For example: *[load dir="views" file="recent-posts.html"]*

* Put your code in a text widget, and use a plugin like [Widgets on Pages](http://wordpress.org/plugins/widgets-on-pages/).


== Upgrade Notice ==



== Changelog ==

= 2.5.5 =

* [field] - Add parameters *escape* and *unescape*
* [loop-count] - Output current index of the loop, starting from 1

= 2.5.4 =

* Further improve shortcodes for compatibility

= 2.5.2 =

* [field] - Add predefined field *link* and parameter *link_text*, to avoid using shortcodes in HTML attributes
* [url] - Improve compatibility

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
