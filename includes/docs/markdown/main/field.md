
# Field

---


Use `[field]` to display a field from the current post.

*Display fields*

~~~
[field title] by [field author] was written on [field date].
~~~

You can display custom fields as well as [predefined fields](#predefined-fields).

---

Additional parameters can be placed after the field name.

~~~
[field title words=10]
~~~

*Display an image field*

~~~
[field image=image_field size=thumbnail]
~~~

For available parameters, refer to [`[content]`](options-general.php?page=ccs_reference&tab=content#field).


&nbsp;

## Predefined fields

### Post

> *id* - post ID

> *slug* - post slug

> *url* - post URL

> *link* - link to post URL; set parameter *link_text* to change link text from post title (default) to, for example, "Read More"

> *post-type* - post type

> *post-status* - post status

> *excerpt* - post excerpt; if excerpt doesn't exist, it will display post content with *words=25*

> *edit-url* - post edit URL

> *edit-link* - post title with link to edit URL; set parameter *link_text* to change link text from post title to, for example, "Edit"


### Title

> *title* - post title

> *title-link* - post title with link to the post

> *title-link-out* - post title with link to the post, in new tab: *target=_blank*


### Date

> *date* - published date

> *modified* - last modified date



### Featured image

> *image* - featured image

> *image-url* - featured image URL

> *image-title* - featured image title

> *image-caption* - featured image caption

> *image-alt* - featured image alternate text

> *image-description* - featured image description

> *image-link* - featured image with link to the post

> *image-link-out* - featured image with link to the post, in new tab: *target=_blank*

> *thumbnail* - featured image thumbnail

> *thumbnail-link* - featured image thumbnail with link to the post



### Author

> *author* - post author

> *author-id* - post author ID

> *author-url* - post author URL

> *avatar* - post author avatar

### Previous / Next

> *prev-link* - previous post in the loop (title with link)

> *next-link* - next post in the loop


&nbsp;

## Currency


To format a currency value, you can use the following parameters.

> **decimals** - number of decimal points; for example, 2

> **point** - separator for the decimal point; for example, "."

> **thousands** - separator for thousands; for example, ","

---

*Format a field value as currency*

~~~
[field field_name point=, thousands=.]
~~~

This will display a number like: 2.500,00

---

To use a predefined currency format, use the parameter *currency*.

~~~
[field field_name currency=USD]
~~~

Please note that the currency symbol is not included in the output.

Most [currency codes](http://en.wikipedia.org/wiki/ISO_4217#Active_codes) are defined for your convenience.



## Array


Use `[array]` to loop through an array of key-value pairs stored in a field.

~~~
[array field_name]
  [field key_1]
  [field key_2]
[/array]
~~~



### Parameters

> **each** - set *true* to loop through multiple arrays of key-value pairs

> **debug** - set *true* to print the whole array and see how it's structured

> **global** - access global variable with given name
