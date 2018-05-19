
# Meta Shortcodes

---

This is a feature to create shortcodes that run other shortcodes.

It could be useful for making templates, or simplified shortcodes for use by clients.

When the module is enabled under [Settings](options-general.php?page=ccs_reference&tab=settings), a post type called `shortcode` is created.

### Name

Each post of this type becomes a shortcode whose name is the title of the post.

For example, a post called `staff` will create a `[staff]` shortcode.

When the shortcode is called, the content of the post is displayed without formatting.

### Parameters

Parameters can be passed to the shortcode as usual.

~~~
[staff position=manager]
~~~

You can use that parameter in the `shortcode` post content like this:

~~~
[loop type=staff taxonomy=position term={POSITION}]
  [field title]
[/loop]  
~~~

Shortcode parameters are passed as `{TAG}`, with an uppercased version of the parameter name.

---

To check if a parameter was passed:

~~~
[if exists]{PARAMETER}[show]The parameter was passed
[else]The parameter wasn't passed[/if]
~~~

### Content

If the shortcode is called with open/close tags:

~~~
[staff position=manager]
  [field date]
[/staff]
~~~

..the inner content is passed as `{CONTENT}`.

~~~
[loop type=staff taxonomy=position term={POSITION}]
  [field title]
  {CONTENT}
[/loop]  
~~~

### Editor

The `shortcode` post type uses a simple code editor.

In addition to code highlighting and auto-indent, there is tab auto-completion for common shortcodes and HTML tags like `[loop]` and `<div>`. For example, type `loop` and press tab to produce the both opening and closing tags.

### Global shortcodes

For efficiency, only this plugin's shortcodes are enabled inside a `shortcode` post. To use shortcodes from a theme or other plugins, surround them with `[global]..[/global]`.