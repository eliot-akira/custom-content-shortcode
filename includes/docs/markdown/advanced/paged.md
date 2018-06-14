# Pagination

---


To display the results of `[loop]` in pages, set the *paged* parameter.

~~~
[loop type=post paged=5]
  [field title]
  [content]
[/loop]
~~~

This defines the number of posts per page.

Optionally, set *maxpage* for the maximum number of pages.

---

Then, use `[loopage]` where you want to show the pagination.

~~~
[loopage]
~~~

There is no styling applied to the pagination. It's up to your theme's CSS.



### Parameters

> **class** - add a class to the pagination

> **anchor** - add anchor link to the page URLs

> **prev_next** - show previous/next links; default is *true*

> **prev_text** - link to previous page; default is *&amp;laquo;* or &laquo;

> **next_text** - link to next page; default is *&amp;raquo;* or &raquo;

> **show_all** - show all page numbers, instead of near the current page; default is *false*

> **end_size** - how many page numbers on start/end of pagination; default is 1

> **mid_size** - how many page numbers to either side of current page; default is 2

> **list** - set *true* to show pagination as a list; also compatible with Bootstrap

> **page** - manually set current page


### Custom query variable

The pagination URLs may conflict with existing permalinks. In that case, you can try using a custom query variable, with the *query* parameter on both `[loop]` and `[loopage]`.

~~~
[loop type=post paged=5 query=paged]
  [field title]
  [content]
[/loop]

[loopage query=paged]
~~~

The name of the variable should be something other than `page` or `p`.

### Current and total

You can display the current page and total number of pages, *after* the loop.

~~~
Current page: [loopage-now]
Total pages: [loopage-total]
~~~

The current page can also be displayed *before* the loop, using:

~~~
[loopage-now before]
~~~

### Complete example

~~~
[loop type=post paged=5]
  [field title]
[/loop]
Page [loopage-now] of [loopage-total]
[loopage prev_next=false show_all=true]
~~~

### Previous and next page

You can display links to previous/next page, separate from the page numbers.

~~~
[loopage-prev text='Go to previous page']
[loopage-next text='Go to next page']
~~~

These can only be used *after* the loop.

---

**First and last page**

If the current page is the first page, then `[loopage-prev]` will display nothing. If the current page is the last page, then `[loopage-next]` will display nothing. Set parameter *else* to display something when there is no previous/next page.

### Anchor links

If the loop is displayed further down the page, you might want to use anchor links.

~~~
<a name="start-loop"></a>
[loop type=post paged=5]
  [field title]
[/loop]
[loopage anchor=start-loop]
~~~

When you switch to another page, it will display starting at the anchor.

&nbsp;


## Permalink

To use permalinks with loop and pagination, there are three fields in the [settings](options-general.php?page=ccs_reference&tab=settings).

> 1. **Permalink slug** is the URL route to add pagination.

> 2. **Pagination slug** is added to the route above; for example, *page* will add `/page`.

> 2. When the permalink is rewritten, it overrides the **default query**. Provide a query string in the settings, to set the query to the correct template and content. For information on building a query string, see [Codex: Query Vars](https://codex.wordpress.org/WordPress_Query_Vars).

---

For example, if the loop is on a page named *hello-world*:

> 1. The permalink slug is: `hello-world`

> 2. The query string is: `pagename=hello-world`

> This will add permalinks like: *example.com/hello-world/page/2*

Multiple permalink slugs and query strings can be given, separated by comma.

---

Then, for the loop where you want pagination, add the parameter *query=default*.

~~~
[loop type=article paged=5 query=default]
  ...
[/loop]

[loopage]
~~~

---

Custom pagination permalinks do not work in archive pages, which are paginated by default.

This feature is still under testing and development. Please post feedback in the [support forum](http://wordpress.org/support/plugin/custom-content-shortcode).

&nbsp;

## Previous / next post

Use `[prev]` and `[next]` to get the previous/next post in the loop.

This can be used to display navigation links, without pagination.

~~~
[loop type=article orderby=title count=1]
  Current article: [field title]
  [prev]
    Previous: [field title-link]
  [/prev]
  [next]
    Next: [field title-link]
  [/next]
[/loop]
~~~

&nbsp;

### Order by date

Note that in the above example, the posts are ordered by title alphabetically, in ascending order. So `[prev]` means closer to "A" and `[next]` is closer to "Z".

However, by default, posts are ordered by date in descending order, from **new to old**. This means `[prev]` will get a *newer* post and `[next]` will get an *older* post, further down the loop. It's counter-intuitive, so to avoid this confusion, use `[older]` and `[newer]` when ordering by date.

```
[loop type=article]
  Current article: [field title]
  [older]
    Previous: [field title-link]
  [/older]
  [newer]
    Next: [field title-link]
  [/newer]
[/loop]
```

&nbsp;

### Outside the loop

When outside the loop, use `[prev-next]` to prepare previous/next posts.

```
[prev-next]
  [older]
    Previous: [field title-link]
  [/older]
  [newer]
    Next: [field title-link]
  [/newer]
[/prev-next]
```

Basically, `[prev-next]` creates a loop of the current post type, to make it possible to find the previous/next post in relation to the current post.

You can also pass it the same parameters as `[loop]`. This can used, for example, to get previous/next post in the same category, ordered by title.

```
[prev-next category=this orderby=title]
```
