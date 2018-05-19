
# If post..

---

Use `[if]` to display content based on post conditions.

~~~
[if category=recommend]
  Must watch!
[/if]
~~~

&nbsp;

## Parameters

### Post

> **type** - post type

> **name** - post name/slug

> **author** - post author ID or user name; set to *this* for current user

> **comment_author** - comment by author ID or name; use inside comments loop

> **parent** - slug or ID of parent

> **format** - post format; if no value is set, checks if any post format exists

### Category, tag, taxonomy

> **category** - if post is in category

> **tag** - if post has tag(s)

> **taxonomy** - name of taxonomy to query

> **term** - if post has specific taxonomy term(s); if no term is set, checks if any term exists


### Field value

> ~~~
> [if field=product_color value=green]
> ~~~

> **field** - name of field to query

> **value** - if post has value(s) in the specified field; if no value is set, it will check if any field value exists

> **start/end** - use instead of **value** to check only the beginning or end of field value

> **lowercase** - set to *true* to compare lowercased version of field value

> **empty** - set to *false* when using dynamic values which could be empty, for example, with `[pass]`

> **compare** - *or* (default: any of the given values), *and* (all values), *not*

### Search field value

> ~~~
> [if field=title,content contains='some keywords']
> ~~~

> **field** - name of field(s) to search

> **contains** - words to search inside the field

> By default, it checks if *all* words exist in the field value, regardless of order or case.

> **compare=or** - search for *any* of the words

> **exact=true** - search for exact phrase

> **case=true** - search case sensitive

### Date field

~~~
[if field=date value=today]..[/if]
[if field=date before='1 week ago']..[/if]
~~~

> **value** - predefined values: today, future, past, 'future not today', 'past and today'

>> For date/time fields: now, future-time, past-time

> **before**/**after** - if field value is before/after a relative or specific date: *+10 days*, *2 weeks ago*, or *2015-02-01*

>> **field_2** - when using *before* or *after* with a relative date, you can set another field to be the reference; default is now

>> **compare** - optionally set to '<=' (before including reference) or '>=' (after)

> **date_format** - for a custom date field, default is 'Ymd'

> **in=timestamp** - compare custom date field as timestamp; same as *date_format=U*


### User field

> **user_field** - name of user field to query

> **value, start, compare** - see above for field value



### Multiple values

For category, tag, taxonomy, field, user field, or post format, you can query for multiple values.

*Science fiction **or** comedy*

~~~
[if category=sci-fi,comedy]
~~~

*Science fiction **and** comedy*

~~~
[if category=sci-fi,comedy compare=and]
~~~


### If it exists

~~~
[if attached]
  There are attachments.
[/if]
~~~

> **attached** - if the post has any attachments

> **comment** - if the post has any comments

> **image** - if the post has a featured image

> **sticky** - if post is sticky

> **gallery** - if the post has any image in the gallery field

> **field** - if the post has any value in this field

> **field=excerpt** - if the post has excerpt

> **taxonomy** - if the post has any term in this taxonomy


### Loop conditions

Use these inside the loop.

~~~
[if empty]Nothing found[/if]
[if first]First post[/if]
[if last]Last post[/if]
[if every=3]Every 3 posts[/if]
[if count=3 compare=more]After 3rd post[/if]
~~~

> **empty** - if the loop is empty

> **first, last** - if it's the first or last post found

> **count** - check current loop count; optionally set *compare* parameter: *more*, *less*, `>=`, `<=`

> **total** - check total post count, optionally with *compare* parameter

> **every** - for every number of posts in the loop; set *first* or *last* to *true* to include first/last post

>> This can be used, for example, to group four posts at a time.
>>
>> ~~~
>> [loop type=post]
>>   [if every=5 first=true]<div class="group-container">[/if]
>>   [field thumbnail]
>>   [field title]
>>   [if every=4 last=true]</div>[/if]
>> [/loop]
>> ~~~


### Not

> **not** - when the condition is not true, for example: `[if not first]`


### And

> **and** - when multiple conditions must be met

> ~~~
> [if category=apple and field=status value=ready]
> ~~~


### Else

Use `[else]` to display something when the condition is false.

~~~
[if tag=discount]
  On Sale!
[else]
  Regular Price
[/if]
~~~


## Nested

For nested conditions, use the minus prefix.

~~~
[loop type=product]
  [if category=books]
    The book [field title-link] is
    [-if field=status value=in-stock]
      in stock.
      [-else]
      not available.
    [/-if]
  [else]
    [field title-link] is not a book.
  [/if]
[/loop]
~~~

You can nest up to 5 levels.


## Other conditions

### If a field value exists

To check if a field has any value, use the *field* parameter without specifying a value.

*Display only products that have serial numbers*

~~~
[loop type=product]
  [if field=serial_number]
    Product: [field title]
    Serial #: [field serial_number]
  [/if]
[/loop]
~~~

If you specify the *value* parameter, it will check for that specific value only.



### If a taxonomy term exists

To check if there's any term in a given taxonomy, use the *taxonomy* parameter without specifying a term.

*Display tags if any exists*

~~~
[loop type=book]
  Book: [field title]
  Author: [field author_name]
  [if taxonomy=tag]
    Tags:
    [for each=tag trim=true]
      [each name-link],
    [/for]
  [else]
    There's no tag.
  [/if]
[/loop]
~~~



### Passed value

> **pass** - the value being passed

> **value** - the value to compare

> **empty=false** - return false if *pass* parameter is empty

This is for checking values passed with the `[pass]` shortcode.

~~~
[pass global=query fields=tag]
  [if pass='{TAG}' value=news empty=false]
    The value "news" was passed.
  [/if]
[/pass]
~~~

If you don't specify the value, it will check if the pass value is not empty.

~~~
[if pass='{TAG}']
  Some value was passed.
[else]
  No value was passed.
[/if]
~~~

### Variable

This is for checking variables used by `[get]`, `[set]` and `[calc]` shortcodes.

> **var** - variable to check

> **value** - the value to check

### If enclosed content exists

~~~
[if exists]
  [field optional_field]
[else]
  [field default_field]
[/if]
~~~

This will display the enclosed content if it's not empty, otherwise display the else clause.

### URL route

If the current URL is: `example.com/article/category/special`

~~~
[if route=article/category/special]
  This is a category archive of special articles.
[/if]
[if route_1=article]This is an archive of articles.[/if]
[if route_2=category]This is a category archive.[/if]
~~~

The route value supports wildcards and negatives.


> \* - will match any value

> \*\* - will match the rest of the route

> **!** - start a value with ! to match if it's not equal

---

You can also check the server host name:

~~~
[if host=example.com] Public site [/if]
[if host=staging.example.com] Staging site [/if]
[if host=localhost] Local development site [/if]
~~~

### Day of week

~~~
[if day_of_week=1]
  Today is Monday.
[/if]
~~~

Check which day of the week it is: 1~7 is Monday~Sunday.

## Switch/when

Use the following syntax to check an `[if]` condition against several values.

~~~
[switch type]
  [when post]This is a post[/when]
  [when page]This is a page[/when]
[/switch]
~~~

### Switch

The `[switch]` shortcode takes one parameter.

~~~
[switch category]
[switch tag]
[switch format]
~~~


For field or taxonomy:

~~~
[switch field=field_name]
[switch taxonomy=tax_name]
~~~

### When

Use `[when default]` for when none of the other values match.

~~~
[when default]
  This is some other post type
[/when]
~~~

To match multiple values, separate with `or`. This will check for any of them.

~~~
[when post or page]
  This is a post or page
[/when]
~~~

To check the start of a value, use *start=value*.

### Switch route

You can use switch to achieve a basic URL routing.

~~~
[switch route]
  [when product or service] Product or service archive [/when]
  [when product/* or service/*] Single product or service [/when]
  [when default] Other [/when]
[/switch]
~~~
