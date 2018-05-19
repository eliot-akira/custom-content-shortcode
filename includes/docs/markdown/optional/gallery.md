
# Gallery Field

---

Enable the Gallery Field module under [Settings](options-general.php?page=ccs_reference&tab=settings).

Then, select post types to use under [Admin Panel > Settings > Gallery Fields](options-general.php?page=ccs_gallery_field_settings).

---

You can add, order, edit and remove images in the field.


### Example

*Display images from the gallery field*

~~~
[attached gallery]
  Title: [field title]
  Full-size image: [field image]
  Caption: [field caption]
[/attached]
~~~

For each image, these fields can be displayed: *id*, *title*, *image*, *image-url*, *caption*, *description*,* thumbnail*, and* thumbnail-url*.

To sort by image title instead of gallery order, set parameter *orderby=title*.

&nbsp;

### In a loop

*Display gallery fields of each post in a loop*

~~~
[loop type=post category=colorful]
  Post Title: [field title]
  Description: [content]
  [attached gallery columns=4]
    [field thumbnail]
  [/attached]
[/loop]
~~~

&nbsp;

### Native or Bootstrap gallery


You can display all images in the gallery field using a native gallery or Bootstrap carousel.

*Display a native gallery or Bootstrap carousel*

~~~
[content gallery=native]
[content gallery=carousel]
~~~


You can pass the following parameters to the native gallery: *orderby*, *order*, *columns*, *size*, *link*, *include*, *exclude*. For details about the native `[gallery]` shortcode, please refer to [the codex](http://codex.wordpress.org/Gallery_Shortcode).

&nbsp;

### Individual image


The `[field]` shortcode can display individual images of the gallery field.

*Display the 3rd image in the gallery field*

~~~
[field gallery num=3]
~~~

*Get the first image's thumbnail URL*

~~~
[field gallery-url num=3 size=thumbnail]
~~~


&nbsp;

### Group

When using the `[loop]` to generate multiple Bootstrap carousels, the following will put images from each post in its own carousel.

~~~
[loop type=post fields=id]
  ...
  [content gallery=carousel group=gallery-{ID}]
[/loop]
~~~
