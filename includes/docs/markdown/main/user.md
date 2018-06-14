
# User

---



Use `[user]` to display current user's info.

~~~
ID: [user id]
Full name: [user]
Login name: [user name]
E-mail: [user email]
Author archive: [user archive-link]
~~~



### Fields

> **id** - ID

> **name** - login name

> **fullname** - display name (default)

> **email** - e-mail

> **url** - website URL

> **avatar** - avatar image

> **size** - avatar image size in pixels - default *96*, max *512*

> **registered** - registered date; optionally add parameter *format=relative*, or [date format](https://codex.wordpress.org/Formatting_Date_and_Time), for example "Y-m-d"

> **post-count** - user post count

> **archive-url** - user posts archive URL

> **archive-link** - display name linked to user posts archive

> **role** - user role

> **slug** - sanitized user name for use in URL; same as user "nicename"

> **edit-url** - user profile edit URL in the admin

> **edit-link** - user profile edit link; set *text* parameter for link text: default is "Edit Profile"

### Custom user field

You can also display a custom user field.

~~~
[user field_name]
~~~

For an image field, use the *image* parameter:

~~~
[user image=field_name size=thumbnail]
~~~

You can display other [attachment fields](options-general.php?page=ccs_reference&tab=attach#attachment-fields) from the image.

~~~
[user image=field_name field=url]
~~~


&nbsp;

## Users loop

Use `[users]` to loop through users.

*Make a list of admin users*

~~~
[users role=admin]
  Admin: [user]
  Contact: [user email]
[/users]
~~~


### Parameters

> **role** - *admin*, *editor*, *author*, *contributor*, *subscriber*; supports multiple

> **include**, **exclude** - include/exclude users by ID

> **orderby** - id, name, login (default), email, url, registered, post_count, field, field_num

> **order** - *ASC* - alphabetical (default) or *DESC* (new to old)

> **count** - limit number of returned results

> **offset** - offset results by a number

> **field** - custom field to query

> **value** - field value; multiple values possible depending on *compare*

> **compare** - *equal* (default), *not*, *in*, *not in*, *between*, *not between*, or operators like >= and <=.

> **search** - search for string match on user columns

> **search_columns** - one or more columns to search: *ID*, *login*, *nicename*, *email*, *url*

> **blog_id** - blog ID on a multisite

#### Users list

> **list** - set *true* to create a list with &lt;ul&gt;, or specify tag like *ol* or *div*

> **list_class, list_style** - add class or style to the list; classes can be separated by space or comma

> **item** - tag to wrap each loop item; default is *li*, or specify tag like *span*

> **item_class, item_style** - add class or style to each item



### Sort by user field

*Field value is string*

~~~
[users orderby=field field=twitter]
~~~

*Field value is number*

~~~
[users orderby=field_num field=position]
~~~

## User field value



Use `[if user_field]` to check if a user field has specific value, or is not empty.


*If user field has specific value*

~~~
[if user_field=school value='Home Town University']
  Hey, schoolmate!
[/if]
~~~

*If user field is not empty*

~~~
[if user_field=facebook_profile]
  <a href="[user facebook_profile]">Facebook profile</a>
[else]
  No Facebook profile
[/if]
~~~

*If user has posts*

~~~
[if user_field=post-count]
  Post count: [user post-count]
[else]
  No posts yet!
[/if]
~~~

## User condition



To display something based on user condition such as ID or role, use the [`[is]` shortcode](options-general.php?page=ccs_reference&tab=is).
