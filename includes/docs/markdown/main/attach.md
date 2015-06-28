
## Attachment
---

Use `[attached]` to loop through attachments.

*Display all attachments of the current post*

~~~
[attached orderby="title"]
  [field title]
  [field image] or [content]
  [field caption]
[/attached]
~~~

---

### Parameters

> **count** - number of attachments to show; default is *all*

> **offset** - offset the loop by a number; for example: skip the first 3 attachments

> **orderby** - *date* (default), *title*, *rand* (random)

> **order** - *ASC* (ascending/alphabetical) or *DESC* (descending/from most recent date)


&nbsp;

### Attachment fields

The following fields are available for each attachment.

> **id** - ID

> **title** - title

> **caption** - caption

> **description** - description

> **url** - URL to attachment file

> **page-url** - URL to attachment page

> **image** - display image: *&lt;img src="~"&gt;*

> **size** - image size: *thumbnail*, *medium*, *large*, *full* (default) or custom defined size

> **thumbnail** - thumbnail

> **thumbnail-url** - thumbnail URL

> **title-link** - attachment title linked to file

> **title-link-out** - attachment title linked to file in new tab



&nbsp;

### Attachments in loop

Use `[attached]` inside a loop to display attachments of specific posts.

*Display attachment thumbnails of all posts in a category*

~~~
[loop type="post" category="special"]
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
[loop type="post"]
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
[attached-field url offset="1"]
~~~

The first parameter is the field name. You can use additional parameters, which are the same as `[attached]` - for example, *offset*, *order* and *orderby*.  If you don't specify *offset*, the first attachment will be chosen.

