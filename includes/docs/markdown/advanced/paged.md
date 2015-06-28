## Pagination
---

To display the results of `[loop]` in pages, set the *paged* parameter.

~~~
[loop type="post" paged="5"]
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

---

### Parameters

> **class** - add a class to the pagination

> **prev_next** - show previous/next links; default is *true*

> **prev_text** - link to previous page; default is *&amp;laquo;* or &laquo;

> **next_text** - link to next page; default is *&amp;raquo;* or &raquo;

> **show_all** - show all page numbers, instead of near the current page; default is *false*

> **end_size** - how many page numbers on start/end of pagination; default is 1

> **mid_size** - how many page numbers to either side of current page; default is 2

> **list** - show pagination as a list; compatible with Bootstrap

---

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

---

### Complete example

~~~
[loop type="post" paged="5"]
  [field title]
[/loop]
Page [loopage-now] of [loopage-total]
[loopage prev_next="false" show_all="true"]
~~~

&nbsp;

## Previous / next

---

Use `[prev]` and `[next]` to get the previous/next post in the loop.

This can be used to display navigation links.

~~~
[loop type="article" orderby="title" count="1"]
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

Note that in the above example, the posts are ordered by title in ascending order, alphabetically. So `[prev]` means closer to "A" and `[next]` is closer to "Z".

However, by default, posts are ordered by date in descending order, from **new to old**. This means `[prev]` will get a *newer* post and `[next]` will get an *older* post, further down the loop. It's counter-intuitive, so to avoid this confusion, use `[older]` and `[newer]` when ordering by date.

```
[loop type="article"]
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
  Current post: [field title]
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
[prev-next category="this" orderby="title"]
```
