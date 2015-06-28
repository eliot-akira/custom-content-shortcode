
## Extras

---

### Today

To display today's date:

~~~
[today]
~~~

This uses the default date format, set in the admin panel under Settings -> General.

---

To use other formatting:

~~~
[today format="Y-m-d"]
~~~

This displays a date like: `2016-08-31`

You can use it to display the time also:

~~~
[today format="Y-m-d H:i"]
~~~

For details, see [the Codex: Formatting Date and Time](https://codex.wordpress.org/Formatting_Date_and_Time).

Note: shortcode parameters cannot handle a backslash, so use double slashes `//` to escape.

&nbsp;

---

### Comment

Use `[*]` to place a comment inside the visual editor.

~~~
[*] Here is a message for myself and others. [/*]
~~~

This shortcode does not display anything, it's just a placeholder.
