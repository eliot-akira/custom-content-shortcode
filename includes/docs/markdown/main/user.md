
## User

---

Use `[user]` to display current user's info.

~~~
ID: [user id]
Full name: [user]
E-mail: [user email]
~~~

---

### Fields

> **id** - ID

> **name** - login name

> **email** - e-mail

> **url** - website URL

> **avatar** - avatar image

> **size** - avatar image size in pixels - default *96*, max *512*

> **url** - website URL

> **role** - user role

> **post-count** - user post count

---

You can also specify a custom user field.

~~~
[user field_name]
~~~

## Users loop

---

Use `[users]` to loop through users.

*Make a list of admin users*

~~~
[users role="admin"]
  Admin: [user]
  Contact: [user email]
[/users]
~~~

---

### Parameters

> **role** - *admin*, *editor*, *author*, *contributor*, *subscriber*

> **include** - include users by ID

> **exclude** - exclude users by ID

> **orderby** - ID, display_name, name, login, email, url, registered, post_count, field, field_num

> **order** - *ASC* - alphabetical (default) or *DESC* (new to old) 

> **number** - maximum number of returned results

> **offset** - offset the results by a number

> **field** - custom field name to query

> **value** - field value; multiple values possible depending on *compare*

> **compare** - *equal* (default), *not*, *in*, *not in*, *between*, *not between*, or operators like >= and <=.

> **search** - search for string match on user columns

> **search_columns** - one or more columns to search: *ID*, *login*, *nicename*, *email*, *url*

> **blog_id** - blog ID on a multisite

---

### Sort by user field

*Field value is string*

~~~
[users orderby="field" field="twitter"]
~~~

*Field value is number*

~~~
[users orderby="field_num" field="position"]
~~~

## User field value

---

Use `[if user_field]` to check if a user field has specific value, or is not empty.


*If user field has specific value*

~~~
[if user_field="school" value="Home Town University"]
  Hey, schoolmate!
[/if]
~~~

*If user field is not empty*

~~~
[if user_field="facebook_profile"]
  <a href="[user facebook_profile]">Facebook profile</a>
[else]
  No Facebook profile
[/if]
~~~

*If user has posts*

~~~
[if user_field="post-count"]
  Post count: [user post-count]
[else]
  No posts yet!
[/if]
~~~

## User condition

---

To display something based on user condition such as ID or role, use the [`[is]` shortcode](options-general.php?page=ccs_reference&tab=is).
