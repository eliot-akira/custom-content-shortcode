
# Load

---


Use `[load]` to include a file into a page.

*Load a shortcode template*

~~~
[load file=template/sidebar.html]
~~~

By default, it looks for the file in the current theme directory.

*Load a file from a different location*

~~~
[load dir=views file=product/new-products.html]
~~~



### Parameters

> **dir** - load from a directory

> - *web* - http://

> - *site* - site address

> - *wordpress* - WordPress directory

> - *content* - *wp-content*

> - *theme* - *wp-content/theme*

> - *child* - *wp-content/child_theme*

> - *views* - *wp-content/views*

> **format** - set *true* to format the file with line breaks and paragraph tags

> **shortcode** - set *false* to disable shortcodes

