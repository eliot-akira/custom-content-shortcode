
# Raw

---


Use `[raw]` to protect a code block in the post editor from default formatting.

~~~
[raw]
  You can safely use multi-line shortcodes in this area.
  It will not be automatically formatted with <p> or <br> tags.
[/raw]
~~~

This feature can be enabled under [Settings](options-general.php?page=ccs_reference&tab=settings).

The [Raw HTML](http://wordpress.org/plugins/raw-html) plugin has the same feature, so if you have it installed, please disable this module.

&nbsp;

### How it works

The `[raw]` shortcode works differently than a normal shortcode. When the module is enabled, it works like this:

- It removes the *wpautop* and *wptexturize* filters from *the_content*
- It adds a filter called *ccs_raw_filter*, which applies *wpautop* and *wptexturize* to the content, except for sections surrounded by `[raw]..[/raw]`

And that's how it protects the section from getting automatically formatted.

&nbsp;

### Note

Themes and plugins which override the default behavior of *the_content* - for example, a visual page builder - may not be compatible with this module.

In that case, disable the module, and load the shortcode section from outside the post editor, using one of the methods described in [Getting Started](options-general.php?page=ccs_reference&tab=start#loading).
