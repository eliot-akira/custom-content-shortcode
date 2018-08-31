
# Advanced Custom Fields

---

Enable the ACF module under [Settings](options-general.php?page=ccs_reference&tab=settings). The following field types are supported:

- Text, textarea, editor, [image](options-general.php?page=ccs_reference&tab=field#image-field)
- [Checkbox, select, radio](#checkbox-select-radio), [true/false](#true-false), [date](#date-field)
- [Page link](#page-link), [relationship/post object](#relationship), [taxonomy](#related-by-taxonomy-field)
- [Gallery](#gallery), [repeater](#repeater), [flexible content](#flexible-content)
- [File field](#field-stored-as-attachment-id), [cropped image](#cropped-image), [Google map](#google-map)
- [Fields from an option page](#option-page)


&nbsp;

## Field label

To display a field's label, set *out=field-label*.

~~~
[field field_name out=field-label]
~~~

## Field key

To display a field by key (starts with *field_*), use the *key* parameter.

~~~
[field key=field_5039a99716d1d]
~~~

## Checkbox/Select/Radio

### Selection label

To display the selection's label instead of value, use the following syntax.

~~~
[field select out=label]
~~~

### Multiple selections

Use `[array]` and `[field value]` to loop through multiple selections.

~~~
[array checkboxes]
  [field value]
[array]
~~~

### Choices

Use `[array choices]` to loop through available choices.

~~~
[array choices=checkbox_field]
  Option label: [field label] or {LABEL}
  Option value: [field value] or {VALUE}
[/array]
~~~

If the field is in another post type than the current post, set parameter *type* or *name*.

## True/false

To check the value of a true/false field, use the following syntax.

~~~
[if field=true_false value=1]
  It's true.
[else]
  It's false.
[/if]
~~~

## Date field



Use the *acf_date* parameter to query the date field.


~~~
[loop type=event acf_date=date_field value=future]
~~~


Use the *acf_date* parameter to display the date field with selected formatting.


~~~
[field acf_date=date_field]
~~~



### Date and time

For fields created with ACF Date & Time Picker, it works best if you save as timestamp and use the *field* parameter for loop.

~~~
[loop type=event field=date_and_time_field value=future]
~~~

To display the field formatted, you can still use the *acf_date* parameter.

~~~
[field acf_date=date_and_time_field]
~~~


## Page link


Use the *link* parameter to display a page link field. This will display the URL of a post or archive.

~~~
[field link=page_link]
~~~


## Cropped image

With [ACF Image Crop](https://wordpress.org/plugins/acf-image-crop-add-on/) add-on, use the *cropped* parameter to display a cropped image field.

*Display cropped image*

~~~
[field cropped=field_name]
~~~

*Display cropped image URL*

~~~
[field cropped=field_name return=url]
~~~

## Google map

To display a map based on a Google map field, you need a plugin or theme that provides a shortcode, for example: [Simple Google Maps Shortcode](https://wordpress.org/plugins/simple-google-maps-short-code).

The map field is stored as an array with keys: *address*, *lat*, *lng*.

*Display the values*

~~~
[array map_field]
  Address: [field address]
  Latitude: [field lat]
  Longitude: [field lng]
[/array]
~~~

*Display the map by passing the address to a shortcode*

~~~
[pass array=map_field]
  [pw_map address='{ADDRESS}']
[/pass]
~~~

## Relationship


Use `[related]` to loop through posts in a relationship field.

~~~
[related field_name]
  [field title]
  [field thumbnail]
[/related]
~~~

### Related by users

If the related posts are users, use the `[user]` shortcode instead of `[field]`.

To get posts related to a user, use the `user_field` parameter.

~~~
[related user_field=related_posts]
~~~

## Related by taxonomy field


Use `[related taxonomy_field]` to loop through posts related by a taxonomy field.

~~~
[related taxonomy_field=field_name]
  [field title]
  [field thumbnail]
[/related]
~~~

This excludes the current post by default.

If the taxonomy field contains multiple terms, the loop will include related posts with *any* of the terms. Set *operator=and* to display related posts that have *all* terms.

### Parameters

> **count** - maximum number of results

> **orderby** - order by* id*,* author*,* title*,* name*,* date* (default),* rand* (randomized)

> **order** - ASC (ascending/alphabetical) or DESC (descending/from most recent date)


## Repeater


Use `[repeater]` to loop through each row of a repeater field.


*Display repeater fields*

~~~
[repeater field_name]
  [field title]
  [field image=image]
  [field description format=true]
[/repeater]
~~~

For an image field inside, use the *image* parameter to display the field. You can set the *size* parameter to:* thumbnail*,* medium*,* large* - default is *full*. If the image field returns as URL, set *in=url*.



### Parameters

> **count** - how many rows to loop

> **start** - which row to start; default is 1

> **row** - a specific row from the repeater field: *row=3*

> **row=rand** - a randomly selected row


### If repeater is not empty

~~~
[if field=repeater_field]
  ..Repeater field has value..
[/if]
~~~


### Display a specific row

*Display the third row*

~~~
[repeater field_name row=3]
  [field title]
[/repeater]
~~~

*Display a random row*

~~~
[repeater field_name row=rand]
~~~

*Display specific sub-fields without looping*

~~~
[repeater field_name row=1 sub=title]
[repeater field_name row=2 sub=text]
[repeater field_name row=3 sub_image=image]
~~~

This displays a sub-field from a specific row. It's used by itself without a closing tag.


### Nested repeaters

~~~
[repeater field_name]
  [-repeater inner_field_name]
    ...
  [/-repeater]
[/repeater]
~~~

To display a repeater inside a repeater, use `[-repeater]`.  Please note that the inner repeater field must have a different name than its parent.

## Flexible content

~~~
[flex flexible_content]

  [layout title_text]
    [field title]
    [field text]
  [/layout]

  [layout title_image_text]
    [field title]
    [field image=image size=thumbnail]
    [field text]
  [/layout]

  [layout gallery]
    [acf_gallery gallery_field]
      [acf_image size=thumbnail]
    [/acf_gallery]
  [/layout]

[/flex]
~~~

Multiple layouts may be specified, separated by comma. Also, *default* layout will match all.

## Gallery


For gallery fields, use `[acf_gallery]`.

*Display images with title*

~~~
[acf_gallery gallery_field]
  [field image]
  [field title]
  [field image size=thumbnail]
[/acf_gallery]
~~~

The `[field]` shortcode can display these fields: *image*, *id*, *title*, *caption*, *alt*, *url*, and *description*. When displaying the image, you can also set the *size* parameter to: *thumbnail*, *medium*, *large*. Default is full-size.

### Pass to another shortcode

You can pass the image to another shortcode's parameter.

*Each image ID or URL*

~~~
[acf_gallery gallery_field]
  [pass fields=id,url]
    [shortcode param={ID} or param={URL}]
  [/pass]
[/acf_gallery]
~~~

*All IDs from gallery - comma separated list*

~~~
[pass acf_gallery=gallery_field]
  [isotope_gallery ids='{FIELD}']
[/pass]
~~~


## In a loop


Use `[loop]` to display ACF fields from other posts.

~~~
[loop name=hello-world]
  [repeater field_name]
    [field title]
    [field image=image]
    [field description format=true]
  [/repeater]
[/loop]

~~~


## Field stored as array


If the field value is stored as an array, you can use the [`[array]` shortcode](options-general.php?page=ccs_reference&tab=field#array) to access its contents.

~~~
[array field_name]
  [field title]
  [field description]
[/array]
~~~


## Field stored as attachment ID

If the field value is an attachment ID - for example, a file upload field - you can use the [`[attached]` shortcode](options-general.php?page=ccs_reference&tab=attach) to access its contents.

~~~
[attached field=file_upload]
  [field title]
  [field description]
  <a href="[field download-url]" download>Download Link</a>
[/attached]
~~~

If the attachment is a PDF file and has preview image, field *url* shows the image URL and *download-url* will get the actual file URL.

## Option Page

To get a field from an option page, set *option=true*.

~~~
[field option_field option=true]
~~~

This parameter can be used with: `field`, `repeater`, `flex` and `acf_gallery`.

~~~
[repeater field_name option=true]
  [field inner_field]
[/repeater]
~~~

Only the parent shortcode needs the *option* parameter.
