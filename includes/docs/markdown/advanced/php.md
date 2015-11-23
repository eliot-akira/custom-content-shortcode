
# PHP

---

There are some helper functions for use in PHP templates.


> - *do_short* - quick way to write `echo do_shortcode`
> - *start_short* - start a block of shortcodes
> - *end_short* - end the block and run it

### Examples

*Run a line of shortcodes*

~~~php
do_short('Current user: [user name]');
~~~

*Run a block of shortcodes*

~~~
<?php start_short(); ?>

[loop type=post count=3]
  [field title-link]<br>
[/loop]

<?php end_short(); ?>
~~~

&nbsp;

## The loop

Use `[the-loop]` to display posts in the default query loop.

~~~
[the-loop]
  [field title]
  [content]
[/the-loop]
~~~

---

Use `[the-pagination]` to display the default pagination.

~~~
[the-pagination]
~~~

It takes the same parameters as [the `[loopage]` shortcode](options-general.php?page=ccs_reference&tab=paged).

---

To display the current page number and total page count:

~~~
Page [page-now] of [page-total]
~~~
