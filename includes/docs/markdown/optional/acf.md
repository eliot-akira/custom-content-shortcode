
## Advanced Custom Fields
---

In addition to text and image fields, the following are supported: [date](#date-field), [page link](#page-link), [relationship](#relationship), [gallery](#gallery), [repeater](#repeater), and [flexible content](#flexible-content).

## Date field

---

Use the *acf_date* parameter to query the date field.


~~~
[loop type="event" acf_date="date_field" value="future"]
~~~


Use the *acf_date* parameter to display the date field with selected formatting.


~~~
[field acf_date="date_field"]
~~~

#### Date and time field

For fields created with ACF Date & Time Picker, it works best if you save as timestamp and use the *field* parameter for loop.

~~~
[loop type="event" field="date_and_time_field" value="future"]
~~~

To display the field formatted, you can still use the *acf_date* parameter.

~~~
[field acf_date="date_and_time_field"]
~~~


## Page link
---

Use the *link* parameter to display a page link field. This will display the URL of a post or archive.

~~~
[field link="page_link"]
~~~


## Relationship
---

Use `[related]` to loop through a relationship field.

*Display titles and thumbnails of related posts*

~~~
[related field="relationship"]
  [field title]
  [field thumbnail]
[/related]
~~~

You can use the same shortcode for a Post Object field.

## Repeater
---

Use `[repeater]` to loop through a repeater field.


*Display repeater fields*

~~~
[repeater field="boxes"]
  [field title]
  [field image="image"]
  [field description format="true"]
[/repeater]
~~~


For an image field inside, use the *image* parameter to display the field. You can set the *size* parameter to:* thumbnail*,* medium*,* large* - default is full-size. If the image field returns as URL, set *in="url"*.

---

### Display a specific repeater field

~~~
[repeater field="boxes" num="3"]
~~~

*Display specific sub-fields without looping*

~~~
[repeater field="boxes" num="1" sub="title"]
[repeater field="boxes" num="2" sub="text"]
[repeater field="boxes" num="3" sub_image="image"]
~~~

This is a quick way to display a sub-field from a specific repeater field. It's used by itself, without a closing tag.

---

### Nested repeaters

~~~
[repeater field="boxes"]
  [-repeater field="inner_boxes"]
    ...
  [/-repeater]
[/repeater]
~~~

To display a repeater inside a repeater, use `[-repeater]`.  Please note that the inner repeater field must have a different name than its parent.

## Flexible content
---

~~~
[flex field="flexible_content"]

  [layout name="title_text"]
    [field title]
    [field text]
  [/layout]

  [layout name="title_image_text"]
    [field title]
    [field image="image" size="thumbnail"]
    [field text]
  [/layout]

  [layout name="gallery"]
    [acf_gallery field="gallery"]
      [acf_image size="thumbnail"]
    [/acf_gallery]
  [/layout]

[/flex]
~~~


## Gallery
---

For gallery fields, use `[acf_gallery]`.

*Display images with title*

~~~
[acf_gallery field="images"]
  [acf_image]
  [acf_image field="title"]
  [acf_image size="thumbnail"]
[/acf_gallery]
~~~

`[acf_image]` displays each image in the gallery field. It can also display these fields: *id*,* title*,* caption*,* alt*,* url*, and* description*. You can set the *size* parameter to:* thumbnail*,* medium*,* large*. The default is full-size.

---

You can pass the image IDs to another shortcode.
*Pass the images to another shortcode*

~~~
[pass acf_gallery="images"]
  [isotope_gallery ids="{FIELD}"]
[/pass]
~~~


## In a loop
---

Display ACF fields from other posts, using the loop.

~~~
[loop name="hello-world"]
  [repeater field="boxes"]
    [field title]
    [field image="image"]
    [field description format="true"]
  [/repeater]
[/loop]

~~~


## Field stored as array
---

If the field value is stored as an array - for example, a file field - you can use the [`[array]` shortcode](options-general.php?page=ccs_reference&tab=field#array) to access its contents.


~~~
[array file_field]
  [field title]
  [field description]
  <a href="[field url]" download>Download Link</a>
[/array]

~~~


## Columns
---

You can use the *columns* parameter for gallery, repeater, or flexible content. For details, please see its description in [`[loop]` under *Parameters: Other*](options-general.php?page=ccs_reference&tab=loop).

