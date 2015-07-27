
# URL

---


Use `[url]` to generate relative URLs.

*Display an image from a relative location*

~~~
<img src="[url uploads]/assets/logo.png">
~~~

This could be useful when you're migrating sites, for example, from local server to public.

Replace absolute URLs with the `[url]` shortcode, then the link doesn't depend on where the site is located.



### Parameters

> **site** - site address

> **wordpress** - WordPress directory

> **content** - *wp-content*

> **uploads** - *wp-content/uploads*

> **views** - *wp-content/views*

> **theme** - *wp-content/theme* - theme directory

> **child** - *wp-content/child_theme* - child theme directory

&nbsp;

## Login / logout links


Use the `[url]` shortcode to display login and logout links.

*Display a login link*

~~~
<a href="[url login]">User login</a>
~~~

*Display a logout link with redirect to home*

~~~
<a href="[url logout go=home]">Logout</a>
~~~



### Parameters

> **login** - login link

> **logout** - logout link

> **go** - redirect after login/logout; specify URL, post slug, or *home*



Here is an example using both `[is]` and `[url]` to show a login/logout link based on user status.

~~~
[is logout]
  <a href="[url login go=user-profile]">Login</a>
[else]
  <a href="[url logout go=home]">Logout Link</a>
[/is]
~~~


## Redirect


The `[redirect]` shortcode redirects the user to another URL.

*Redirect if visitor is not logged in*

~~~
[is not login]
  [redirect go='http://example.com/guest/']
[/is]
~~~



### Parameters

> **go** - redirect to URL, post slug, or *home*

> **after** - redirect after specified time; for example: *1000 ms*, *30 sec*



You can also specify a relative URL by wrapping it inside.

~~~
[is login]
  [redirect][url site]/user-area/[/redirect]
[/is]

~~~

The best place to do this is at the top of the page.
