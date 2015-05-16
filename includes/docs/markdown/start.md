
## Getting Started
---

Here is an example of how to display your content.

Let's imagine a bicycle shop.

1. Create a custom post type called *bicycle*. Add categories and fields such as *model*, *price*, and *description*.

  You can use plugins like <a href="https://wordpress.org/plugins/custom-post-type-ui/" target="_blank">Custom Post Type UI</a> and <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields</a> to create your own post types, taxonomies and fields. Themes and plugins often come with built-in content types. To find their exact names to use, see your site's <a href="index.php?page=content_overview">Content Overview</a>.

1. Add all bicycles as new posts.

1. Create a new page to display bicycles. A basic template could be:

~~~
[loop type="bicycle"]
  [field image]
  Model: [field model]
  Price: [field price]
  Description: [field description]
[/loop]
~~~

Another section can display a list of freestyle bicycles.

~~~
<ul>
  [loop type="bicycle" category="freestyle"]
    <li>[field title-link] - [field model] - [field price]</li>
  [/loop]
</ul>
~~~


## Notes
---

### Editing

There are some points to keep in mind when using shortcodes.

1. The content of the post editor is **automatically formatted**, which can create unwanted paragraphs and breaklines with multi-line shortcodes. Wrap the section with the [[raw] shortcode](options-general.php?page=ccs_reference&tab=raw) to prevent formatting.

1. **The Visual mode is not suited for editing HTML**, because the code is invisible and easy to accidentally delete. This can be a challenge if the post content must be editable by a client.

  Possible solutions are:

  - Use <a href="options-general.php?page=ccs_reference&tab=block">HTML Block shortcodes</a>, so the tags are clearly visible
  - Disable the Visual mode per page or post type, with the plugin <a target="_blank" href="http://wordpress.org/plugins/raw-html/">Raw HTML</a>
  - Load the code from outside the post editor using one of the methods below

---

### Loading

Shortcode templates can be loaded from a number of places.

  - **Custom post type**: [content type="template" name="home-page"]
  - **Custom field**: [field code_block shortcode="true"]
  - **Sidebar**: <a href="options-general.php?page=ccs_reference&tab=settings">Enable shortcodes inside Text widget</a>
  - **File**: [load dir="views" file="recent-posts.html"]

---

### HTML attribute

When using a shortcode in an HTML attribute, use single quotes for passing parameters.

~~~
<div class="[taxonomy category field='slug']">
~~~

If a field value may include characters like "quotes" and &lt;brackets&gt;, use the *escape* parameter.

~~~
<a href="[field url]" title="[field title escape='true']">
~~~
