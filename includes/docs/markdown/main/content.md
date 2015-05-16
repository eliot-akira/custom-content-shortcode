
## Content
---

Use `[content]` to display content or a field from a specific post.

*Display post content by name*

~~~
[content name="hello-world"]
~~~

*Display the featured image of a page*

~~~
[content type="page" name="about-me" field="image"]
~~~

*Display a field from a custom post type*

~~~
[content type="apartment" name="lux-suite-22" field="rent-per-day"]
~~~

---

#### With loop

When inside a loop, it can be used without parameter to show each post's content.

~~~
[loop type="post" count="3"]
  [field title]
  [content]
[/loop]
~~~


## Parameters
---

### Type, name, ID

> **type** - post type: *post*, *page*, or *custom post type*; default is *any*

> **name**, **title**, or **id** -  get post by *slug*, *title*, or *ID*; default is *current post*

---

### Field

> **field** - name of field to display; if empty, the default is the *post content*

>> You can display custom fields as well as [predefined fields](options-general.php?page=ccs_reference&tab=field#predefined-fields).

>> When displaying a field from the current post, you can use the [**[field]** shortcode](options-general.php?page=ccs_reference&tab=field).

---

### Format

> **format** - format with &lt;p&gt; and &lt;br&gt; tags; set to *true* or *false*

>> By default, post content is formatted, and fields are not.

> **words** or **length** - trim by number of words or characters

>> Trimmed content is not formatted by default; set *format="true"* if you need.

> **filter** - set *true* to apply *the_content* filter; this may be useful when using plugins that filter the post content, for example, [Page Builder](https://wordpress.org/plugins/siteorigin-panels).

> **embed** - set *true* to embed URLs like YouTube, Vimeo, etc. By default, such URLs in post content are auto-embedded.

> **class** - add &lt;div&gt; class to the output

> **currency** - format as currency; see [the field section](options-general.php?page=ccs_reference&tab=field#currency) for details

> **date_format** - use a custom date format
  
>> For example, "d.m.Y" will display as *18.11.2013*. Please refer to [the codex](http://codex.wordpress.org/Formatting_Date_and_Time) for the date format syntax.

>> Note: instead of backslash, use double slashes to escape characters:

>>> "Y/m/d //a//t g:i A" will show as *2013/11/17 at 11:06 PM*.

>> Use *in="timestamp"* to format a unix timestamp value.

---

### Read more

> **more** - set *true* to display content up to the &lt;!--more--&gt; tag, or an excerpt if the tag doesn't exist. This will add the text "Read More" at the end, with a link to the post. To change the text, use: *more="..."*

> **dots** - set *false* to disable dots at the end of excerpt

> **link** - set *false* to disable link to the post


---

### Image field

> **image** - display an image field; for example: *image="product_image"*

> **size** - size of image: *thumbnail*, *medium*, *large*, *full* (default) or [custom defined size](http://codex.wordpress.org/Function_Reference/add_image_size)

> **width**, **height** - set both to resize image by pixels; set *size* parameter with same proportion

> **image_class** - add class to the &lt;img&gt; tag

> **alt**, **title** - additional image attributes

> **in** - type of image field: *id* (default), *url*, or *object*

> **out** - image detail to output: *id*, *url*, *title*, *caption*, *description*

> **nopin** - set *nopin="nopin"* to prevent Pinterest pinning of image


---

### Taxonomy

> **taxonomy** - display *category*, *tag*, or custom taxonomy of the post: *taxonomy="product_type"*

> **field** - taxonomy field to display: *name* (default), *id*, *slug*, *description*, or custom taxonomy field

>> By default, terms are displayed as a list of term names, like "Category, Another Category". If you need the term slugs, set *field="slug"*.

> **image** - custom taxonomy image field; see description above for image field parameters

> **term** - get taxonomy term by ID or slug, regardless of current post

> **term_name** - get taxonomy term by name/label

>> When displaying terms from the current post, you can use the [**[taxonomy]** shortcode](options-general.php?page=ccs_reference&tab=taxonomy).


---

### Other field types

> **meta** - display author meta: *field="author" meta="user_email"*

>> See [the codex](http://codex.wordpress.org/Function_Reference/get_the_author_meta) for available author meta fields.

> **out="label"** - when displaying checkboxes or select fields made in ACF, this parameter isplays their labels instead of values


---

### Other content types

> **area** or **sidebar** - display a widget area/sidebar by *title*

> **menu** - display a menu list by *slug*, *title*, or *ID*; see also [Bootstrap tabs and navbar](options-general.php?page=ccs_reference&tab=bootstrap).

