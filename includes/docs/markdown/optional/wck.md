
# WCK Fields and Post Types

---

## Single field

For WCK fields, use the `[field]` shortcode and specify a metabox name.


~~~
[field field-name metabox=metabox-name]
~~~

### Parameters

> **image** - display an image field: *image=field-name*

> **size** - size of image: *thumbnail*, *medium*, *large*, *full* (default) or custom defined size

> **shortcode** - set *true* to enable shortcodes inside the field.

>> Usually WCK formats a text area to create break lines, but it doesn't work well with shortcodes. When shortcodes are enabled, you'll need to insert &lt;br&gt; tags manually.


## Multiple fields

For multiple fields from the same metabox, you can use the `[metabox]` shortcode.


~~~
[metabox name=metabox-name]
  [field field-name]
  [field another-field]
[/metabox]

~~~


&nbsp;

## Repeater

Use `[repeater]` to display a repeating metabox.

*Display repeater fields*

~~~
[repeater metabox=metabox-name]
  [field field-name]
  [field another-field]
[/repeater]
~~~



### Parameters

> **metabox** - name of metabox

> **id** - post id (default is current post)

### Inside loop

*Display repeater fields from five recent posts*

~~~
[loop type=post_type count=5]
  Post Title: [field title]
  [repeater metabox=metabox-name]
    [field field-name]
    [field another-field]
  [/repeater]
[/loop]
~~~

## Conditions

Use [the `[if]` shortcode](options-general.php?page=ccs_reference&tab=if#field-value) to compare field value.

Specify the *metabox* parameter when outside a metabox or repeater loop.

~~~
[if field=field-name metabox=metabox-name value=123]
~~~
