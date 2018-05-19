
# Content

---


Use `[content]` to display content or a field from a specific post.

*Display post content by name*

~~~
[content name=hello-world]
~~~

*Display the featured image of a page*

~~~
[content type=page name=about-me field=image]
~~~

*Display a field from a custom post type*

~~~
[content type=apartment name=lux-suite-22 field=rent-per-day]
~~~



#### With loop

When inside a loop, it can be used without parameter to show each post's content.

~~~
[loop type=post count=3]
  [field title]
  [content]
[/loop]
~~~

&nbsp;

## Parameters

### Type, name, ID

> **type** - post type: *post*, *page*, or *custom post type*; default is *any*

> **name**, **title**, or **id** -  get post by *slug*, *title*, or *ID*; default is *current post*



### Field

> **field** - name of field to display; if empty, the default is the *post content*

>> You can display custom fields as well as [predefined fields](options-general.php?page=ccs_reference&tab=field#predefined-fields).

>> When displaying a field from the current post, you can use [`[field]`](options-general.php?page=ccs_reference&tab=field) as a shortcut.


### Image field

> **image** - display an image field; for example: *image=product_image*

> **in** - type of image field: *id* (default), *url*, or *object*

> **size** - size of image: *thumbnail*, *medium*, *large*, *full* (default) or [custom defined size](http://codex.wordpress.org/Function_Reference/add_image_size)

> **width**, **height** - set both to resize image by pixels; set *size* parameter with same proportion

> **image_class** - add class to the &lt;img&gt; tag

> **alt**, **title** - additional image attributes

> **out** - if field is stored as attachment ID, output image detail: *id*, *url*, *title*, *caption*, *description*

> **nopin** - set *nopin=nopin* to prevent Pinterest pinning of image


### Author field

> **meta** - display author meta: *field=author meta=user_email*

>> Author meta fields include: *user_login, user_email, display_name, first_name, last_name, description*. See [the codex](http://codex.wordpress.org/Function_Reference/get_the_author_meta) for more.


### Taxonomy

> **taxonomy** - display *category*, *tag*, or custom taxonomy of the post: *taxonomy=product_type*

>> When displaying terms of the current post, you can use [`[taxonomy]`](options-general.php?page=ccs_reference&tab=taxonomy) as a shortcut.

>> If there is more than one term, you can use [`[for/each]`](options-general.php?page=ccs_reference&tab=taxonomy#for--each) to loop through a list of terms.

> **field** - taxonomy field to display: *name* (default), *id*, *slug*, *description*, *url*, *link*, or custom taxonomy field

> **image** - custom taxonomy image field; see description above for [image field parameters](#image-field)

> **term** - get taxonomy term by ID or slug, regardless of current post

> **term_name** - get taxonomy term by name/label



### Format

> **format** - format with `<p>` and `<br>` tags; set to *true* or *false*

>> By default, post content is formatted, and fields are not.

> **texturize** - apply text transformations like smart quotes, apostrophes, dashes, ellipses, etc. See [the Codex: wptexturize](https://codex.wordpress.org/Function_Reference/wptexturize#Notes) for details.

> **words** or **length** - trim by number of words or characters

>> Trimmed content is not formatted by default; set *format=true* if you need. Also, HTML tags are stripped from trimmed content by default; you can set *html=true* to keep HTML tags - however, trimming content with HTML tags may have unexpected results.

>> **sentence** - set *true* to trim to last sentence

>> **word** - set *true* to trim to last word

>> **until** - trim to specified characters, for example: *until=...*

> **class** - add `<div>` class to the output

> **slugify** - set *true* to create a sanitized slug from field value

> **glue** - if field is array, implode with given separator; default is a comma with space after

> **escape** or **unescape** - Escape/unescape HTML special characters; this also sets *shortcode=false* unless specified otherwise

> **shortcode** - set *true/false* for shortcodes inside post content or field

> **http** - set *true* to add `http://` in front of field value, if it's not there already

> **https** - set *true* to add `https://`

> **embed** - set *true* to embed URLs like YouTube, Vimeo, etc. By default, such URLs in post content are auto-embedded.

> **filter** - set *true* to apply *the_content* filter; this may be useful when using plugins that filter the post content, for example, [Page Builder](https://wordpress.org/plugins/siteorigin-panels).

> **currency** - format as currency; see [the field section](options-general.php?page=ccs_reference&tab=field#currency) for details

> **date_format** - use a custom date format

>> For example, "d.m.Y" will display as *18.11.2013*. Please refer to [the codex](http://codex.wordpress.org/Formatting_Date_and_Time) for the date format syntax.

>> Note: instead of backslash, use double slashes to escape characters:

>>> "Y/m/d //a//t g:i A" will show as *2013/11/17 at 11:06 PM*.

>> Use *in=timestamp* to format a unix timestamp value.

### Read more

> **more** - set *true* to display content up to the &lt;!--more--&gt; tag, or an excerpt if the tag doesn't exist. This will add the text "Read More" at the end, with a link to the post. To change the text, use: *more=...*

> **dots** - set *false* to disable dots at the end of excerpt

> **link** - set *false* to disable link to the post


### Template

> **import** - set *true* to use another post's content as a template and run its shortcodes in the context of the current post; true by default when displaying a field


### Other content types

> **area** or **sidebar** - display a widget area/sidebar by *slug*, *title*, or *ID*

> **menu** - display a menu list by *slug*, *title*, or *ID*; see also [Menu loop](options-general.php?page=ccs_reference&tab=menu) and [Bootstrap tabs and navbar](options-general.php?page=ccs_reference&tab=bootstrap).
