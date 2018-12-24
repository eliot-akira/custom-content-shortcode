
# Getting Started

---

Here is an example of how to display your content.

Let's imagine a bicycle shop.

1. Create a custom post type called *bicycle*. Add categories and fields such as *model*, *price*, and *description*.

  You can use plugins like [Custom Post Type UI](https://wordpress.org/plugins/custom-post-type-ui) and [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields) to create your own post types, taxonomies and fields. Themes and plugins often come with built-in content types. To find their exact names to use, see your site's [Content Overview](index.php?page=content_overview).

1. Add all bicycles as new posts.

1. Create a new page to display bicycles. A basic template could be:

~~~
[loop type=bicycle]
  [field image]
  Model: [field model]
  Price: [field price]
  Description: [field description]
[/loop]
~~~

Another section can display a list of freestyle bicycles.

~~~
<ul>
  [loop type=bicycle category=freestyle]
    <li>[field title-link] - [field model] - [field price]</li>
  [/loop]
</ul>
~~~

&nbsp;

### Editing

There are some points to keep in mind when using shortcodes.

1. The content of the post editor is **automatically formatted**, which can create unwanted paragraphs and breaklines with multi-line shortcodes. Wrap the section with the [[raw] shortcode](options-general.php?page=ccs_reference&tab=raw) to prevent formatting.

1. **The Visual mode is not suited for editing HTML**, because the code is invisible and easy to accidentally delete. This can be a challenge if the post content must be editable by a client.

  Possible solutions are:

  - Use [HTML Block shortcodes](options-general.php?page=ccs_reference&tab=block), so the tags are clearly visible
  - Disable the Visual mode per page or post type, with the plugin [Raw HTML](http://wordpress.org/plugins/raw-html)
  - Load the code from outside the post editor using one of the methods below

&nbsp;

### Loading

Shortcode templates can be loaded from a number of places.

---

**Custom post type**

~~~
[content type=template name=single-product import=true]
~~~

When loading another post's content (not field) as a template, set *import=true* to run the template in the context of the post that loads it.

---

**Custom field**

~~~
[field code_block]
~~~

---

**File**

~~~
[load dir=views file=recent-posts.html]
~~~

See [the [load] shortcode](options-general.php?page=ccs_reference&tab=load) for more details.

---

**Sidebar**: [Enable shortcodes inside Text widget](options-general.php?page=ccs_reference&tab=settings)

&nbsp;

### Shortcode parameters

The shortest way to define a parameter is without quotes.

~~~
[loop type=post count=3]
~~~

For multiple values, do not use a space after the comma.

~~~
[loop type=fruit category=apple,orange]
~~~


If you need to pass a value that contains a space, use single quotes.

~~~
[loop type=post field=something value='John Smith']
~~~

When using a shortcode in an HTML attribute and the value may include characters like "quotes" and &lt;brackets&gt;, use the *escape* parameter.

~~~
<a href="[field url]" title="[field title escape=true]">
~~~

&nbsp;

### Shortcode inside HTML attribute

This note is relevant when using shortcodes outside the post content, such as a widget of a page builder plugin.

For a shortcode in an HTML attribute *inside another shortcode*, use double square brackets to ensure that they run in the correct order.

~~~
[loop type=post count=3]
  <div class="post-[[field slug]]">
    [field title]
  </div>
[/loop]
~~~
