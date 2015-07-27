
# HTML Blocks

---



The `[block]` shortcode is used as placeholders for HTML tags.

The advantage is that the tags will be visible and protected in the post editor in Visual mode.

You can enable this module under [Settings](options-general.php?page=ccs_reference&tab=settings). *Thanks to @szepeviktor for suggesting this feature.*

---

By default, it creates a `<div>`.

~~~
[block]
  ...
[/block]
~~~

You can set the *tag* parameter to create other HTML tags.

~~~
[block tag=article]
  ...
[/block]
~~~


&nbsp;

### Attributes

Any HTML attribute can be specified as parameter.

~~~
[block id=left-block class=col-md-6 style='margin-left:0']
  ...
[/block]
[block id=right-block class=col-md-6 style='margin-right:0']
  ...
[/block]
~~~


### Nested

To make nested blocks, use the minus prefix.

~~~
[block]
  [-block]
    [--block]
      ...
    [/--block]
  [/-block]
[/block]
~~~


### HTML shortcodes

Shortcodes are also provided for all major HTML tags.

~~~
[article]
  [section]
    [h1]Title[/h1]
  [/section]
  [section]
    [h1]Title[/h1]
  [/section]
[/article]
~~~
