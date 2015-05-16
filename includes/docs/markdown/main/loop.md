
## Loop
---

Use `[loop]` to get posts and loop through each one.

*Display five most recent posts*

~~~
[loop type="post" count="5"]
  [field title]
  [field date]
  [field excerpt]
[/loop]
~~~

*Display posts from a custom post type and category*

~~~
[loop type="apartment" category="suite"]
  Apartment: [field title]
  Rent per day: [field price]
  Description: [content]
[/loop]
~~~

## Parameters

---

### Type, name, ID

> **type** - post type to include; default is *any*

> **name** - post slug; usually only one post will match

> **id** - post ID to include; for example: *id="1,2,3"*

> **exclude** - post ID to exclude
  - *this* - exclude current post
  - *children* - exclude child posts

> **count** - number of posts to show; default is all posts

> **offset** - offset the loop by a number of posts; for example: *offset="3"* to skip the first three

> **status** - display posts by status: *any, publish, pending, draft, future, private*

---

### Author

> **author** - show posts by author ID or login name
  - *this* - current user
  - *same* - same author as current post

> **author_exclude** - exclude posts by author ID or login name

> **role** - show posts by user role, such as *administrator*, *editor*, *subscriber*
  - *this* - current user's role

---

### Published date

> **year, month, day** - display posts by specific year, month, day
  - *today* - for example: *month="today"* will show posts from this month

> **before** - display posts before a relative or specific date
  - Example: *before="today"*, *before="2015-02-01"*

> **after** - display posts after a date

---

### Category, tags

> **category** - display posts from one or more categories; for example: *category="sports, fashion"*

> **tag** - display posts with one or more tags; for example: tag="apples, green"

> **compare** - for multiple categories/tags, set *compare="and"* to get posts that have all terms

---

### Taxonomies

> **taxonomy, term** - display posts by taxonomy term
  - Example: *taxonomy="product" term="book"*

> #### Multiple terms

> Multiple terms may be specified, such as *term="book, lamp"*.

> By default, this gets posts whose taxonomy contains *any* of the given terms.
  - Use *compare="and"* to get posts whose taxonomy contains all terms
  - Use *compare="not"* to get posts whose taxonomy does not contain the term(s)

> #### Multiple taxonomies

> **relation** - additional taxonomy query, where relation is *and* (default) or *or*

> **taxonomy_2, term_2, compare_2, taxonomy_3, term_3, compare_3, ...**


---

### Field value


> **field** - field name

> **value** - value to search for

> **start** - use instead of *value* to check only the beginning of field value

> **compare** - equal (default), not, more, less, or operator like &lt; and &gt;.

> **compare="between"** - query for a range of values; for example, value="0,100"

> #### Multiple fields

> **relation** - additional field query, where relation is *and* (default) or *or*

> **field_2, value_2, compare_2, field_3, value_3, compare_3...**


---

### Date field

> **field** - field name

> **value** - field value

>> You can specify exact value, or use the following predefined values.
  - *today* - compare to today
  - *now* - if your field contains date and time
  - *future* - today and after
  - *past* - before today
  - *past* and today - past including today

> **compare** - *equal* (default), *not*, *more*, *less*, or operator like &lt; and &gt;.

> **before, after** - used in place of *value* and *compare*; query for field values before/after a relative or specific date: *2 weeks ago*, or *2015-02-01*

> **date_format** - date format of the field value; default is "Y-m-d". For ACF date field, set it to "Ymd".

> #### Multiple date fields

> **field_2, value_2, compare_2, date_format_2, after_2, before_2...**

---

### Parent

> **parent** - display all children of a parent specified by ID or slug
  - *this* - get current post's children
  - *same* - get current post's siblings (posts which share the same parent)

> **exclude**
  - *this* - don't include current post
  - *children* - display top-level posts only
     
  


---

### Sorting and series


> **orderby** - order posts by *date* (default), *id*, *author*, *title*, *name*, *comment-date*, *rand* (random), *modified*, *menu_order*, *parent*, *field* (field value: string), or *field_num* (field value: number)

> **order** - *ASC* (ascending/alphabetical) or *DESC* (descending/from most recent date)

> **key** - when ordering by *field* or *field_num*, specify *key* as the name of the field to use

> **series, key** - order posts by a series of field values, where *key* is the name of the field; the series can include ranges, for example: *1-15, 30-40, 42, 44*.


---

### Checkboxes


> **checkbox, value** - display posts whose checkbox contains the value

>> Multiple values are possible: *value="first, second"*. This will return all posts with checkboxes containing any of the values, i.e., first *or* second.

>> Optionally, you can set *compare="and"*, which will return all posts with checkboxes containing all of the values, i.e., first *and* second.  See example section below.

> **relation** - additional query for checkbox value, where relation is *and* (default) or *or*

> **checkbox_2, value_2, compare_2, ...**

---

### Format

> **trim** - set *true* to remove extra white space or comma at the end

> **clean** - set *true* to remove all &lt;p&gt; and &lt;br&gt; tags

> **strip_tags** - set *true* to remove all HTML tags inside the loop

> **allow** - strip all HTML tags except allowed, for example: *allow="&lt;a&gt;&lt;li&gt;&lt;br&gt;"*


---

### List

> **list** - set *true* to create a list with &lt;ul&gt;, or specify tag like *ol* or *div*

> **list_class, list_style** - add class or style to the list

> **item** - tag to wrap each loop item; default is *li*, or specify tag like *span*

> **item_class, item_style** - add class or style to each item


---

### Pagination

> **paged** - number of posts per page

> **maxpage** - maximum number of pages

> These are used with the [[loopage] shortcode](options-general.php?page=ccs_reference&tab=paged) to create pagination.


---

### Cache

> **cache** - set *true* to cache the result of the loop; see [the reference page](options-general.php?page=ccs_reference&tab=cache) for details

> **expire** - how often the cache is updated: minutes, hours, days, years - default is *10 minutes*

> **update** - set *true* to force update the cache

> **timer** - set *true* to display resource info at the end of loop; see [here](options-general.php?page=ccs_reference&tab=cache#timer) for details


---

### Other

> **blog** - switch to given blog ID on multi-site

> **columns** - display output in columns; for example, *columns="3"*; for padding: *pad="0px 10px"*

> **x** - repeat the loop x times - no query


## Field tags

---

This is a feature to expand a list of fields to their values.

~~~
[loop type="post" fields="custom_field, another_field"]
  Display {CUSTOM_FIELD} and {ANOTHER_FIELD}
[/loop]
~~~

The `{FIELD}` tags are uppercased versions of the field names.

There are also predefined tags: COUNT, URL, ID, TITLE, AUTHOR, AUTHOR_URL, DATE, THUMBNAIL, THUMBNAIL_URL, CONTENT, EXCERPT, COMMENT_COUNT, CATEGORY, TAGS, IMAGE, IMAGE_ID, IMAGE_URL.

If you want to pass field values to the loop shortcode itself, use [the `[pass]` shortcode](options-general.php?page=ccs_reference&tab=pass).

