
# Math

---

Enable the Math module under [Settings](options-general.php?page=ccs_reference&tab=settings).

---

Use `[calc]` to perform safe, spreadsheet-like calculations.

~~~
Total: [calc][field price] * [field amount][/calc]
~~~

Numbers can be assigned to variables.

~~~
[calc]total = [field price] * [field amount][/calc]
~~~

When you assign a variable, it displays nothing.

These variables are shared with the `[get]` and `[set]` shortcodes described under [Advanced: Extras](options-general.php?page=ccs_reference&tab=extras#variables).

---

To use a variable:

~~~
Total: [calc]total[/calc]
Tax: [calc]total * 0.19[/calc]
~~~

The result can be formatted.

~~~
[format decimal=2][calc]total[/calc][/format]
~~~

For more information on the `[format]` shortcode, also see [Advanced: Extras](options-general.php?page=ccs_reference&tab=extras#format).
