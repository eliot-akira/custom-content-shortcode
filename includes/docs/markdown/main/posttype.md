
# Post type

---

Use `[for]` to loop through post types.

~~~
[for type=all]
  Post type: [each name]
[/for]
~~~

### Parameters

> **type** - Required: set to *all*, or a comma-separated list of post types

> **public** - Set to *true* to get only public post types

> **default** - Set to *false* to exclude default post types: post, page

> **exclude** - Exclude one or more post types by slug

&nbsp;

## Each

The `[each]` shortcode is used to display post type label or slug.

Specify the field like `[each name]` with the field name as first parameter without value.

The following fields are available.

> **name** - Post type label (singular)

> **plural** - Post type label (plural)

> **slug** - Post type slug

> **prefix** - Post type rewrite slug for use in URLs

> **url** - Post type archive URL

### Parameters

> **lower** - Set to *true* to make singular/plural name into lowercase

## Single field

There is a shortcut for displaying a single field from a post type.

*Display the archive URL of a post type*

~~~
[for type=custom-type field=url /]
~~~

The `/` before the end is a short way to close the loop.

## Conditions

You can use `[if]` for conditions inside a `[for type]` loop.

Supported conditions are: *first*, *last*, *every*, *archive* (if archive exists), and *prefix* (if rewrite slug exists).

## Loop

For each post type, you can display a post loop.

~~~
[for type=post,page,product]

  Recent [each plural lower=true]

  [loop count=3]
    [field title-link]
  [/loop]
[/for]
~~~
