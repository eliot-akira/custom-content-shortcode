
# If

---

Use `[if]` to display content based on post conditions.

~~~
[if category=recommend]
  Must watch!
[/if]
~~~

&nbsp;

## Parameters

### Post type and name

> **type** - post type

> **name** - post name/slug


### Parent

> **parent** - slug or ID of parent


### Category, tag, taxonomy

> **category** - if post is in category

> **tag** - if post has tag(s)

> **taxonomy** - name of taxonomy to query

> **term** - if post has specific taxonomy term(s); if no term is set, checks if any term exists


### Field value

> **field** - name of field to query

> **value** - if post has value(s) in the specified field; if no value is set, it will check if any field value exists

> **start** - use instead of **value** to check only the beginning of field value

> **lowercase** - set to *true* to compare lowercased version of field value

> **empty** - set to *false* if you're using dynamic values which could be empty, for example, when using `[pass]`


### Date field

> **before**/**after** - used in place of *value* and *compare*; query for field values before/after a relative or specific date: *10 days*, *2 weeks ago*, or *2015-02-01*



### User field

> **user_field** - name of user field to query

> **value, start, compare** - see above for field value



### Multiple values

> For category, tag, taxonomy, field or user field, you can query for multiple values: for example, *category=sci-fi,comedy*. This returns posts in *either* Sci-Fi or Comedy. If you want posts matching *both* categories, set *compare=and*.



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

> **empty** - if the loop is empty (no post found)

> **first, last** - if it's the first or last post found

> **every** - for every number of posts in the loop



### Passed value

> **pass** - the value being passed: *pass='{FIELD}'*

> **value** - the value to check: *value=this*

### Other

> **not** - when the condition is not true, for example: `[if not first]`


## Else


Use `[else]` to display something when the condition is false.


~~~
[if tag=discount]
  On Sale!
[else]
  Regular Price
[/if]
~~~

## Date field


You can use the parameters *before* and *after* to compare dates.

*If post was published in the last 2 weeks*

~~~
[if field=date after='2 weeks ago']
  New post
[else]
  Old post
[/if]
~~~

The value can be a specific date like *2015-02-01*, or a relative date such as *1 month ago*.

## Other conditions


### If there is no loop result

Use `[if empty]` to display something when there is no post matching the query.

*Display a message for no query result*

~~~
[loop type=events category=weekend]
  [field title]
  [field description]
  [if empty]
    There is no event for this category.
  [/if]
[/loop]
~~~

If there's no post found, the loop displays what's inside `[if empty]` only once.



### For every X number of posts

*Add an extra break line for every 3 posts*


~~~
[loop type=post count=9]
  [field title]
  [if every=3]
    <br>
  [/if]
[/loop]
~~~

*Display something for the first and last post*

~~~
[loop type=post]
  [field title]
  [if first]
    first post
  [/if]
  [if last]
    last post
  [/if]
[/loop]
~~~



### If a field value exists

To check if a field has any value, use the *field* parameter.

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

To check if the post has any term in a given taxonomy, use the *taxonomy* parameter without specifying the term.

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

You can add up to 3 prefixes.
