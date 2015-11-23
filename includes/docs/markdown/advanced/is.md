
# Is user..

---


Use `[is]` to display content based on user status.

*Display user status*

~~~
[is admin]You are an administrator.[/is]
[is author]You are the author of current post.[/is]
[is login]You are logged in.[/is]
[is logout]You are logged out.[/is]
[is user=john]You are John.[/is]
[is role=subscriber]You are a subscriber.[/is]
~~~



### Parameters

> **admin** - user has admin capability (manage options)

> **author** - user is author of current post

> **login** - user is logged in

> **logout** - user is logged out

> **user** - user name or ID - multiple values are possible: 1,3,22

> **role** - user role - default roles are *administrator*, *editor*, *author*, *contributor*, or *subscriber*

> **capable** - user capability, for example: *capable=manage_options*

>> Multiple values are possible: *role=admin,subscriber* will be true if the user is *admin* or *subscriber*.

>> See the list of available user roles and capabilities under [Dashboard -> Content -> User Roles](index.php?page=content_overview#user-roles).



### Else or not

You can use `[else]` or `[is not]` to display something when the condition is not true.

*Logged in or logged out*

~~~
[is login]
  You are logged in.
[else]
  You are not logged in.
[/is]
~~~

*User is not admin*

~~~
[is not admin]
  You are not admin.
[/is]
~~~
