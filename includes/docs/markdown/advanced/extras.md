
# Extras

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
[today format=Y-m-d]
~~~

This displays a date like: `2016-08-31`

You can use it to display the time also:

~~~
[today format='Y-m-d H:i']
~~~

For details, see [the Codex: Formatting Date and Time](https://codex.wordpress.org/Formatting_Date_and_Time).

Note: shortcode parameters cannot handle a backslash, so use double slashes `//` to escape.

&nbsp;

### Comment

Use `[note]` to place a comment inside the visual editor.

~~~
[note] Here is a message for myself and others. [/note]
~~~

This shortcode does not display anything, it's just a placeholder.

&nbsp;

### Random number

Use `[random]` to display a random number in a chosen range.

*Show a random number between 1 and 8*

~~~
[random 1-8]
~~~

Use `[pass]` if you need to pass a random number to a shortcode parameter.

~~~
[pass random=1-8]
  [shortcode parameter='example-{RANDOM}.jpg']
[/pass]
~~~
