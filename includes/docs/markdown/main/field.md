
## Field
---

Use `[field]` to display a field from the current post.

*Display fields*

~~~
[field title] by [field author] was written on [field date].
~~~

*Additional parameters*

~~~
[field title words="10"]
~~~

*Display an image field*

~~~
[field image="image_field" size="thumbnail"]
~~~

You can display custom fields as well as the following predefined fields.

## Predefined fields

---

### Post

> *id* - post ID

> *slug* - post slug

> *url* - post URL

> *edit-url* - post edit URL

> *excerpt* - post excerpt

> *post-type* - post type

> *post-status* - post status

---

### Title

> *title* - post title

> *title-link* - post title with link to the post

> *title-link-out* - post title with link to the post, in new tab: *target="_blank"*

---

### Date

> *date* - published date

> *modified* - last modified date

---

### Featured image

> *image* - featured image

> *image-url* - featured image URL

> *image-title* - featured image title

> *image-caption* - featured image caption

> *image-alt* - featured image alternate text

> *image-description* - featured image description

> *image-link* - featured image with link to the post

> *image-link-out* - featured image with link to the post, in new tab: *target="_blank"*

> *thumbnail* - featured image thumbnail

> *thumbnail-link* - featured image thumbnail with link to the post

---

### Author

> *author* - post author

> *author-id* - post author ID

> *author-url* - post author URL

> *avatar* - post author avatar


## Currency

---

To format a currency value, use these parameters.

> **decimals** - number of decimal points; default is 2

> **point** - separator for the decimal point; default is "."

> **thousands** - separator for thousands; default is ","

For example:

~~~
[field field_name point="," thousands="."]
~~~

This will display a number like: 2.500,00

---

To use a predefined currency format, use the parameter *currency*.

~~~
[field field_name currency="USD"]
~~~

Please note that the currency symbol is not included in the output.

Most <a target="_blank" href="http://en.wikipedia.org/wiki/ISO_4217#Active_codes">currency codes</a> are defined for your convenience.



## Array
---

Use `[array]` to loop through an array of key-value pairs stored in a field.

~~~
[array field_name]
  [field key_1]
  [field key_2]
[/array]
~~~

---

###Parameters

> **each** - set *true* to loop through multiple arrays of key-value pairs

> **debug** - set *true* to print the whole array and see how it's structured

> **global** - access global variable with given name

