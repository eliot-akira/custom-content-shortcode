## Pagination
---

To display the results of a `[loop]` in pages, set the *paged* parameter.

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


## Current and total

---

You can display the current page and total number of pages, *after* the loop.

~~~
Current page: [loopage-now]
Total pages: [loopage-total]
~~~

The current page can also be displayed *before* the loop, using:

~~~
[loopage-now before]
~~~

## Complete example

---

~~~
[loop type="post" paged="5"]
  [field title]
[/loop]
Page [loopage-now] of [loopage-total]
[loopage prev_next="false" show_all="true"]
~~~
