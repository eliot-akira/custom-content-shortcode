
# Field

---

Use `[field]` to display a field from the current post.

*Display fields*

~~~
[field title] by [field author] was written on [field date].
~~~

---

Additional parameters can be placed after the field name. Most [parameters for `[content]`](options-general.php?page=ccs_reference&tab=content#field) can be used.

~~~
[field title words=10]
~~~


&nbsp;

## Predefined fields

### Post

> *id* - post ID

> *slug* - post slug

> *url* - post URL

> *link* - link to post URL; set parameter *text* to change link text from post title (default) to, for example, "Read More"

> *excerpt* - post excerpt; if excerpt doesn't exist, it will display post content with *words=25*. Excerpts are unformatted, and HTML tags are stripped. See also the *words* and *length* parameter for the content shortcode.

> *after-excerpt* - content after the read more tag

> *edit-url* - post edit URL

> *edit-link* - post title with link to edit URL; set parameter *text* to change link text from post title to, for example, "Edit"

> *post-status* - post status

> *post-class* - post class

> *post-type* - post type

>> *post-type-name* - singular label

>> *post-type-plural* - plural label

> *post-format* - post format

>> *post-format-name* - post format label

>> *post-format-link* - post format label with link to archive

>> *post-format-url* - post format archive URL


### Title

> *title* - post title

> *title-link* - post title with link to the post

> *title-link-out* - post title with link to the post, in new tab: *target=_blank*


### Date

> *date* - published date

> *modified* - last modified date



### Featured image

> *image* - featured image

> *image-url* - image URL

> *image-title* - image title

> *image-caption* - image caption

> *image-alt* - alternate text

> *image-description* - image description

> *image-link* - image with link to the post

> *image-link-out* - image with link to the post, in new tab: *target=_blank*

> *thumbnail* - featured image thumbnail

> *thumbnail-url* - thumbnail URL

> *thumbnail-link* - thumbnail with link to the post

> *title=true* - set *title* attribute to post title when using *image-link* and *thumbnail-link*

### Author

> *author* - post author

> *author-id* - post author ID

> *author-url* - post author URL

> *avatar* - post author avatar

### Previous / Next

> *prev-link* - previous post in the loop (title with link)

> *next-link* - next post in the loop

## Custom field

You can display custom fields as well as predefined fields listed above. If your custom field has the same name as a predefined field, set *custom=true*.


## Image field

*Display an image field*

~~~
[field image=image_field size=thumbnail]
~~~

*Display an image field (stored as URL)*

~~~
[field image=image_field in=url]
~~~

### Parameters

> **image** - display an image field; for example: *image=product_image*

> **in** - type of image field: *id* (default), *url*, or *object*

> **size** - image size: *thumbnail*, *medium*, *large*, *full* (default) or custom size name

>> Create a custom image size with a plugin like [Simple Image Sizes](https://wordpress.org/plugins/simple-image-sizes/), or [`add_image_size()`](http://codex.wordpress.org/Function_Reference/add_image_size)

> **width**, **height** - set both to resize image by pixels; set *size* parameter with same proportion

> **image_class** - add class to the &lt;img&gt; tag

> **alt**, **title** - additional image attributes

> **return** - display image field meta: *url, id, title, caption, description*


## Link to field value

Use `[link]` to create a link using field value.

~~~
[link field=image-url]
  Click here to see [field image-title].
[/link]
~~~

The default field is URL of the current post.

~~~
[loop type=post count=3]
  [link][title][/link] is the same as [field title-link]
[/loop]
~~~

### Parameters

> **id** - link to another post by ID

> **name** - link to another post by slug; set *type* to specify post type for faster query

> **field** - name of predefined or custom field; default is *url*

> **custom** if the custom field has the same name as a predefined field, set *custom=true*

> **url** - use direct URL instead of field value; can also be used with `[pass]`

> **alt**, **title**, **target**, **class** - set link attributes

> **open=new** - same as *target=_blank*

> **protocol** - add *http*, *https*, or *telnet*, plus `://` before field value, if it's not there already

> **http**, **https** - set *true* to add `http://` or `https://`

> **download** - set *true* or file name

> **mail** - set *true* to add `mailto:` before field value

## Array

Use `[array]` to loop through an array of key-value pairs stored in a field.

~~~
[array field_name]
  [field key_1]
  [field key_2]
[/array]
~~~

### Parameters

> **each** - set *true* to loop through multiple arrays of key-value pairs

> **debug** - set *true* to print the whole array and see how it's structured

> **global** - access global variable with given name

> **json** - set *true* if field value is stored as JSON string

---

Nested arrays are supported by using the minus prefix.

~~~
[array field_name]
  [-array inner_array]
    [field key_1]
  [/-array]
[/array]
~~~

For an array that is not a key-value pair, use `[field value]` to display each value.
