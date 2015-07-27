
# Mobile Detect

---


Enable the Mobile Detect under [Settings](options-general.php?page=ccs_reference&tab=settings).

Use `[is]` to display content based on device type.

~~~
[is mobile]
  User is on a phone or tablet.
[else]
  User is not on a mobile device.
[/is]
~~~

The parameters available are: *mobile*, *phone*, *tablet*, and *computer*.



### Examples

*Image sizes*

~~~
[is mobile]
  [field image size=medium]
[else]
  [field image size=large]
[/is]

~~~

*Stylesheets*

~~~
[is computer]
  [load css=style.css]
[else]
  [load css=style-mobile.css]
[/is]
~~~

*Redirect visitors on mobile*

~~~
[is mobile]
  [redirect][url site]/mobile/[/redirect]
[/is]
~~~

These last two would be placed in a custom field named *css*, to load in the head of the page.

&nbsp;

### Body class

There are CSS classes added to the &lt;body&gt; element, for styling purposes: *.is_phone, .isnt_phone, .is_tablet, .is_mobile,* *.is_computer* and *.isnt_computer*.

&nbsp;

### Library

Device detection is based on a lightweight PHP class, [Mobile Detect](http://mobiledetect.net) version 2.8.12.

Mobile Detect works with user-agent detection on the server side.

*It will not work if the page is cached.*
