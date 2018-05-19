
# Loop

---

Use `[loop]` to get posts and loop through each one.

*Display five most recent posts*

~~~
[loop type=post count=5]
  [field title]
  [field date]
  [field excerpt]
[/loop]
~~~

*Display posts from a custom post type and category*

~~~
[loop type=apartment category=suite]
  Apartment: [field title]
  Rent per day: [field price]
  Description: [content]
[/loop]
~~~

&nbsp;

## Parameters

### Type, name, ID

> **type** - post type to include; if not specified, default is *any*; multiple post types possible

> ~~~
> [loop type=article,news orderby=date]
> ~~~

> **name** - post name, or "slug"; can specify multiple with comma-separated list

> **id** - post ID to include; for example: *id=1,2,3*

> **exclude** - post ID to exclude
  - *this* - exclude current post
  - *children* - exclude child posts; display top-level posts only

> **count** - number of posts to show; default is all posts found

> **offset** - offset the loop by a number of posts; for example: *offset=3* to skip the first three

> **status** - display posts by status: *any, publish, pending, draft, future, private*

> **sticky** - set *true* to include sticky posts


### Author

> **author** - show posts by author ID or login name
  - *this* - current user
  - *same* - same author as current post

> **author_exclude** - exclude posts by author ID or login name

> **role** - show posts by user role, such as *administrator*, *editor*, *subscriber*
  - *this* - current user's role

> **comment_author** - show posts which have comments by this author ID or name
  - *this* - current user
  - *same* - same author as current post

### Published date

> **year, month, day** - display posts by specific year, month, day
  - *this* - for example: *month=this* will show posts from this month

> **before** - display posts before a relative or specific date
  - Example: *before=today*, *before=2015-02-01*

> **after** - display posts after a date



### Category, tags

> **category** - display posts from one or more categories; for example: *category=sports,fashion*

> - *this* - find posts in the same category as current post

> **tag** - display posts with one or more tags; for example: tag=apples,green

> - *this* - find posts with the same tag as current post

> **compare** - for multiple categories/tags, set *compare=and* to get posts that have all terms



### Taxonomies

> **taxonomy, term** - display posts by taxonomy term

> ~~~
> [loop type=product taxonomy=product-type term=book]
>   [field title]
> [/loop]
> ~~~

> **compare** - set to *not* to exclude posts by taxonomy term; if using field compare at the same time, use *tax_compare*

---

> #### Post format

> ~~~
> [loop type=post taxonomy=format term=audio]
> ~~~

---

> #### Multiple terms

> Multiple terms may be specified, such as *term=book,lamp*.

> By default, this gets posts whose taxonomy contains *any* of the given terms.
  - Use *compare=and* to get posts whose taxonomy contains all terms
  - Use *compare=not* to get posts whose taxonomy does not contain the term(s)

---

> #### Multiple taxonomies

> ~~~
> [loop taxonomy=color term=blue relation=or taxonomy_2=size term_2=small]
> ~~~

> **relation** - additional taxonomy query, where relation is *and* (default) or *or*

> **taxonomy_2, term_2, compare_2, taxonomy_3, term_3, compare_3, ...**

### Field value

> **field** - field name

> **value** - field value(s) - if multiple, will match any: *value=this,that*

> **start** - use instead of *value* to check only the beginning of field value

> **compare** - *equal* (default), *not*, *more*, *less*, or operator like &lt; and &gt;. If using taxonomy compare at the same time, use *field_compare*

> **compare=between** - query for a range of values; for example, *value=0,100*

---

> #### Multiple fields

> ~~~
> [loop field=color value=blue relation=or field_2=size value_2=small]
> ~~~

> **relation** - additional field query, where relation is *and* (default) or *or*

> **field_2, value_2, compare_2, field_3, value_3, compare_3...**


### Date field

> **field** - field name

> **value** - field value

>> You can specify exact value, or use the following predefined values.
  - *today* - compare to today
  - *today-between* - when using date-and-time field, compare today as range (00:00:00~23:59:59)
  - *now* - if your field contains date and time
  - *future* - today and after
  - *future-time* - after now
  - *'future not today'* - from tomorrow
  - *past* - before today
  - *past-time* - before now
  - *'past and today'* - past including today

> **compare** - *equal* (default), *not*, *more*, *less*, or operator like &lt; and &gt;.

> **before, after** - used in place of *value* and *compare*; query for field values before/after a relative or specific date: *2 weeks ago*, or *2015-02-01*

> **date_format** - date format of the field value; default is 'Ymd' - for a date-and-time field, set it to 'U' or use *in=timestamp*

> #### Multiple date fields

> **field_2, value_2, compare_2, date_format_2, after_2, before_2...**


### Parent

> **parent** - display all children of a parent specified by ID or slug
  - *this* - get current post's children

>> This will include all first-level children of the current page.
>> ~~~
>> [loop type=page parent=this]
>>   [field title]
>> [/loop]
>> ~~~

  - *same* - get current post's siblings (posts which share the same parent)

### Children

> **include=children** - include child posts and descendants

>> This will include all descendants of the current page.
>> ~~~
>> [loop type=page parent=this include=children]
>>   [field title]
>> [/loop]
>> ~~~
>>
>> Children are included together with their parents. When *list=true*, they will be appended after their parent.

> **level** - set maximum level of descendants to include

>> *level=1* will get only top parents, *level=2* will include their direct children, and so on.
>>
>> ~~~
>> [loop type=page level=2]
>>   [field title]
>> [/loop]
>> ~~~
>>
>> When you set a level, there's no need to add *include=children*.

> ---
> **parent=this**
>
>> You can also use a nested loop to get each descendant level separately.
>> ~~~
>> [loop type=page orderby=title]
>>   [field title]
>>   [-loop parent=this]
>>     Child page: [field title]
>>     [--loop parent=this]
>>       Grandchild page: [field title]
>>     [/--loop]
>>   [/-loop]
>> [/loop]
>> ~~~
>> Query parameters (except *id*, *name* and *parent*) of the top loop apply to inner loops. In the example above, all loops are ordered by title.

---

> **child=this** - loop through current post's parents from the top
  - *include=this* - include current post
  - *reverse=true* - reverse order: start from current post and go up

>> For example, if the current post is a grandchild page:
>> ~~~
>> [loop child=this include=this]
>>   [if not first] > [/if][field title]
>> [/loop]
>> ~~~

>> This will display: Parent > Child > Grandchild


### Order by child date

Use *orderby=child-date* to sort posts by the most recently published children.

~~~
[loop type=page orderby=child-date]

  Parent: [field title]

  [-loop parent=this orderby=date count=1]
    Most recent child: [field title] - [field date]<br>
  [/-loop]
[/loop]
~~~

---

The following parameters are available:

> **order**

>> *DESC* - descending; new to old (default)

>> *ASC* - ascending; old to new

> **parents**

>> *true* - exclude posts which have no children; by default they are placed at the end of the result

>> *equal* - posts which have no children will be compared by their publish date



### Sorting and series

> **orderby** - order posts

> - *date* - published date (default)
> - *id*
> - *author*
> - *title*
> - *name* - post slug
> - *parent*
> - *modified*
> - *comment-date* - sort by comment date
> - *child-date* - sort by most recently published children (see above section)
> - *rand* or *random* - random order
> - *menu* - menu order
> - *field* - field value as string
> - *field_num* - field value as number

>> If the value isn't any of these, it's assumed to be the field name.

> **order** - *ASC* (ascending/alphabetical) or *DESC* (default: descending/from most recent date)

> **key** - when ordering by *field* or *field_num*, specify *key* as the name of the field to use

> **orderby_2**, **order_2**, **key_2**, ... - Order by multiple fields, up to 5; custom and default fields are supported, except *comment-date*, *rand*, *menu*, *parent*

> ---

> **series, key** - order posts by a series of field values, where *key* is the name of the field; the series can include ranges, for example: *1-15, 30-40, 42, 44*.




### Checkboxes


> **checkbox, value** - display posts whose checkbox contains the value

>> Multiple values are possible: *value=first,second*. This will return all posts with checkboxes containing any of the values, i.e., first *or* second.

>> Optionally, you can set *compare=and*, which will return all posts with checkboxes containing all of the values, i.e., first *and* second.  See example section below.

> **relation** - additional query for checkbox value, where relation is *and* (default) or *or*

> **checkbox_2, value_2, compare_2, ...**



### Format

> **trim** - set *true* to remove extra white space or comma at the end

> **clean** - set *true* to remove all &lt;p&gt; and &lt;br&gt; tags

> **strip_tags** - set *true* to remove all HTML tags inside the loop

> **allow** - strip all HTML tags except allowed, for example: *allow='&lt;a&gt;&lt;li&gt;&lt;br&gt;'*




### List

> **list** - set *true* to create a list with &lt;ul&gt;, or specify tag like *ol* or *div*

> **list_class, list_style** - add class or style to the list; classes can be separated by space or comma

> **item** - tag to wrap each loop item; default is *li*, or specify tag like *span*

> **item_class, item_style** - add class or style to each item




### Pagination

> **paged** - number of posts per page

> **maxpage** - maximum number of pages

> **page** - manually set current page

> These are used with the [[loopage] shortcode](options-general.php?page=ccs_reference&tab=paged) to create pagination.




### Cache

> **cache** - set *true* to cache the result of the loop; see [Advanced: Cache](options-general.php?page=ccs_reference&tab=cache) for details

> **expire** - how often the cache is updated: minutes, hours, days, years - default is *10 minutes*

> **update** - set *true* to force update the cache

> **timer** - set *true* to display resource info at the end of loop; see [here](options-general.php?page=ccs_reference&tab=cache#timer) for details




### Other

> **blog** - switch to given blog ID on multi-site

> **columns** - display output in columns; for example, *columns=3*; for padding: *pad='0px 10px'*

> **x** - repeat the loop x times - no query

---

When querying the *tribe_events* post type from [The Events Calendar](https://wordpress.org/support/plugin/the-events-calendar) plugin, you can use the following:

> **display** - *custom* (all events), *past* (past events), *list* (default: all future events)



## Loop exists

This is a feature to perform a query first, to check if any post matches the given parameters. It displays nothing if no post is found.

~~~
[loop exists type=post category=important orderby=title]
  <h1>Important posts</h1>
  [the-loop]
    [field title-link] by [field author]
  [/the-loop]
[/loop]
~~~

Use `[the-loop]` inside to loop through the result.

---

To display something when no post is found, use `[if empty]`.

~~~
[loop exists type=post category=important]
  ...
  [if empty]<h1>No important posts</h1>[/if]
[/loop]
~~~

## Loop count

Use `[loop-count]` to display the current index of the loop, starting from 1.

This can be useful, for example, to create unique element ID or class to wrap each post.

The shortcode can also be used to display total post count after a loop is finished.

---

If you provide query parameters, it will count the result.

~~~
Total number of posts by current user: [loop-count type=post author=this]
~~~


## Field tags

This is a feature to expand a list of fields to their values.

~~~
[loop type=post fields=title,custom_field]
  Display {TITLE} and {CUSTOM_FIELD}
[/loop]
~~~

The `{FIELD}` tags are uppercased versions of the field names. You can use [predefined fields](options-general.php?page=ccs_reference&tab=field#predefined-fields) or custom fields that you've created.

If you want to pass field values to the loop shortcode itself, use [the `[pass]` shortcode](options-general.php?page=ccs_reference&tab=pass).
