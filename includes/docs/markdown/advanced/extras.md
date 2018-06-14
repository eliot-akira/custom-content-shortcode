
# Extras

---

## Today

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

## Format

Use `[format]` to format a number, date, or string.

### Number

~~~
€ [format decimals=2 point=, thousands=.] [field product_price] [/format]
~~~

At least one of the following parameters must be set.

> **decimals** - number of decimal points to include; default is 0

> **point** - separator for the decimal point; default is "."

> **thousands** - separator for thousands; default is ","

> **currency** - [currency code](http://en.wikipedia.org/wiki/ISO_4217#Active_codes) (*EUR*, *USD*, ...) to use a predefined format for the above three parameters. The currency symbol is not included in the output.

> ~~~
> € [format currency=EUR][field price][/format]
> ~~~

### Date

> **date** - *default*, *relative*, or [date format](https://codex.wordpress.org/Formatting_Date_and_Time), for example *Y-m-d*

> **in=timestamp** - if the input value is a timestamp

### String

> **slugify** - The Example Title -> the_example_title

> **unslugify** - the_example_title -> The Example Title

> **ucfirst** - Uppercase first letter

> **ucwords** - Uppercase words

> **plural** - Make an English word into plural form

## Variables

This stores content in a variable with a given name:

~~~
[set any_name]Content of variable[/set]
~~~

To display it:

~~~
[get any_name]
~~~

To pass it:

~~~
[pass vars]{ANY_NAME}[/pass]
~~~

---

If you have the Math module enabled under [Settings](options-general.php?page=ccs_reference&tab=settings), these variables are shared with the `[calc]` shortcode.

## Site name and description

~~~
[field site=name]
[field site=description]
~~~

## Comment

Use `[note]` to place a comment inside the visual editor.

~~~
[note] Here is a message for myself and others. [/note]
~~~

This shortcode does not display anything, it's just a placeholder.

## Random number

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
