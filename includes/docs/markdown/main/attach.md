
# Attachment

---

Use `[attached]` to loop through attachments.

*Display all attachments of the current post*

~~~
[attached orderby=title]
  [field title]
  [field image] or [content]
  [field caption]
[/attached]
~~~

*Display 3 random thumbnails from attached images*

~~~
[attached orderby=rand count=3]
  [field thumbnail]
[/attached]
~~~


### Parameters

> **count** - number of attachments to show; default is *all*

> **offset** - offset the loop by a number; for example: skip the first 3 attachments

> **orderby** - *date* (default), *title*, *rand* (random)

> **order** - *ASC* (ascending/alphabetical) or *DESC* (descending/from most recent date)

> **id** - get attachments by attachment ID(s), even if it's not attached to current post

> **field** - get attachment ID(s) from field

&nbsp;

### Attachment fields

The following fields are available for each attachment.

> **id** - ID

> **title** - title

> **caption** - caption

> **description** - description

> **url** - URL to attachment file

> **download-url** - If the attachment is a PDF file and has preview image, field *url* shows the image URL and *download-url* will get the actual file URL

> **page-url** - URL to attachment page

> **image** - display image: *&lt;img src="~"&gt;*

> **size** - image size: *thumbnail*, *medium*, *large*, *full* (default) or custom size name

>> Create a custom image size with a plugin like [Simple Image Sizes](https://wordpress.org/plugins/simple-image-sizes/), or [`add_image_size`](http://codex.wordpress.org/Function_Reference/add_image_size)

> **thumbnail** - thumbnail

> **thumbnail-url** - thumbnail URL

> **title-link** - attachment title linked to file

> **title-link-out** - attachment title linked to file in new tab



&nbsp;

### Attachments in loop

Use `[attached]` inside a loop to display attachments of specific posts.

*Display attachment thumbnails of all posts in a category*

~~~
[loop type=post category=special]
  [field title]
  [attached]
    [field thumbnail]
  [/attached]
[/loop]
~~~



&nbsp;

### If post has attachment

Use `[if attached]` to display something if the post has any attachments.

~~~
[loop type=post]
  [field title]
  [if attached]
    This post has attachments:
    [attached][field thumbnail][/attached]
  [else]
    This post has no attachments.
  [/if]
[/loop]
~~~


&nbsp;

### Specific attachment field

Use `[attached-field]` to display a single field from a specific attachment.

*Display the URL of the second attachment*

~~~
[attached-field url offset=1]
~~~

The first parameter is the field name. You can use additional parameters, which are the same as `[attached]` - for example, *offset*, *order* and *orderby*.  If you don't specify *offset*, the first attachment will be chosen.
