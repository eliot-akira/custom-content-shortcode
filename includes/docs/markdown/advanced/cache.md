
# Cache

---

Use `[cache]` to cache page sections.

~~~
[cache name=unique_name expire='1 day']
  ...
[/cache]
~~~

It can be useful for saving query loop results to improve page load speed.

---

This feature uses the [Transients API](http://codex.wordpress.org/Transients_API) to store the content with an expiration time. When the cache expires, it is updated the next time the page is displayed.

Please note that the cache only stores the HTML output. Shortcodes with JavaScript functionality (sliders for example) may not work when cached.

### Parameters

> **name** - unique name for the cache: use lowercase alphabets, no spaces; max 40 characters

> **expire** - how often the cache is updated: *minutes*, *hours*, *days*, *years*; default is *10 minutes*

> **update** - set *true* to force update the cache

>> Note: if update is always set *true*, it will update every time and never load content from cache. Set it once, display the page to update the cache, then remove the parameter.


&nbsp;

### Cache a loop

~~~
[loop type=post count=5 cache=true expire='1 day']
  [field title-link]
  [field thumbnail]
[/loop]

~~~

A unique cache name is automatically generated based on the query parameters.

&nbsp;

## Timer

This is a little tool to measure performance.

~~~
[timer start]
  ...
[timer stop]
~~~

You can see the time it takes to render a page section, number of database queries and amount of memory used.

---

Here are the commands available:

> **start** - start the timer (displays nothing)

> **stop** - stop the timer and display time, memory used and number of queries

> **info** - display current resource info from top of page

---

You can time a loop directly.

~~~
[loop type=post timer=true]
  ...
[/loop]
~~~

This shows the info at the end of the loop.

---

#### Note

If you need more extensive measurements, [Query Monitor](https://wordpress.org/plugins/query-monitor) is recommended.