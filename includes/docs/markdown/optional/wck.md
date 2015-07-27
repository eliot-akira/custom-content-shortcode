
# WCK Fields and Post Types

---


### Single field

For WCK fields, use the `[field]` shortcode and specify a metabox name.


~~~
[field field_name metabox=metabox_name]
~~~



### Parameters

> **image** - display an image field: *image=field_name*

> **size** - size of image: *thumbnail*, *medium*, *large*, *full* (default) or custom defined size

> **shortcode** - set *true* to enable shortcodes inside the field.

>> Usually WCK formats a text area to create break lines, but it doesn't work well with shortcodes. When shortcodes are enabled, you'll need to insert &lt;br&gt; tags manually.




### Multiple fields

For multiple fields from the same metabox, you can use the `[metabox]` shortcode.


~~~
[metabox name=metabox_name]
  [field field_name]
  [field another_field]
[/metabox]

~~~


&nbsp;

## Repeater

Use `[repeater]` to display a repeating metabox.

*Display repeater fields*

~~~
[repeater metabox=metabox_name]
  [field field_name]
  [field another_field]
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
  [repeater metabox=metabox_name]
    [field field_name]
    [field another_field]
  [/repeater]
[/loop]
~~~
