
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


&nbsp;

## Auto-load fields

There are special fields that are automatically loaded into the page.

### Head - CSS

Create a custom field named *css*, and the field will be included in the document head.

Inside this field, you can load a CSS file, direct styles, or meta tags.

*Load a page-specific stylesheet*

~~~
[load css=landing-page.css]
~~~

By default, it looks for the stylesheet in the *css* folder of the current theme directory.

*Load a stylesheet from a different location*

~~~
[load dir=content css=includes/css/font-awesome.min.css]
~~~

*Direct CSS*

~~~
[css]
.entry-content {
  background-color: black;
}
[/css]
~~~


&nbsp;

### Google Fonts

Use the *gfonts* parameter to include fonts from Google Fonts.

*Include fonts and apply them to page elements*

~~~
[load gfonts=Lato|Lora:400,700]
[css]
h1, h2 { font-family: Lora, serif; }
p { font-family: Lato, sans-serif; }
[/css]
~~~

This should be placed in the *css* field.


&nbsp;

### Foot - JS
Create a custom field named *js*, and the field will be included at the foot of document body.

Inside this field, you can load JS file or script.

*Load a JavaScript file*

~~~
[load js=slider.js]
~~~

By default, it looks for the file in the *js* folder of the current theme directory.

*Direct JS*

~~~
[js]
jQuery(document).ready(function($){
  console.log('Here I am!');
});
[/js]
~~~

You can also load the script in the header by placing it in the *css* field.

&nbsp;

### HTML

Create a custom field named *html*, and the field will be displayed *instead of* the post content.

This can be useful for pulling the post editor's content into a shortcode template.

*Wrap the content*

~~~
<h1>[field title]</h1>
[field author] - [field date]
[content]
~~~
