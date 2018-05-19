
# Comment

---


Use `[comments]` and `[comment]` to display recent comments, or those of the current post.

*Display five most recent comments*

~~~
[comments count=5]
  [comment] by [comment author] on [comment date]
[/comments]
~~~

*Display recent comments for the current post*

~~~
[comments count=3 id=this]
  [comment] by [comment author] on [comment date]
[/comments]
~~~


&nbsp;

### Parameters

Available parameters for `[comments]` are:

> **id** - set to *this* to display comments from the current post only

> **type** - post type(s) to include; default is *all*

> **count** - number of comments to show, or set to *all*

> **author** - get comments on posts by author ID or user name; set to *this* for current user

> **status** - *approve* (default), *hold* (unapproved) or set to *all*

> **exclude** - exclude post ID

> **format** - set to *false* to prevent formatting comment content

> **words/length** - trim by number of words or characters (for comment content or post title)

> **category**, **tag** - filter by category or tag

> **taxonomy**, **term**/**term_id** - filter by taxonomy term slug or ID


&nbsp;

### Fields

Inside the comments loop, use `[comment]` to display the following fields.

> **content** - comment content; default if not parameter is specified

> **date** - date of comment

> **date_format** - format the comment date, e.g., "Y-m-d". See [the codex](http://codex.wordpress.org/Formatting_Date_and_Time) for date format syntax.

> **url** - URL of the comment

> **link** - the post title linked to the comment

> **post-url** - URL of the post where the comment is

> **title** - title of the post where the comment is

> **title-link** - post title with link to the post itself

> **author** - author name

> **author-url** - author URL

> **author-link** - author name with link to URL; if no URL, then displays just name

> **avatar** - author avatar image; optional *size* parameter (default 96, max 512)

> **count** - number of comments the post has

> **counted** - shows "No comments", "1 comment" or "X comments"

> **total** - total comment count; use after the loop is finished



&nbsp;

### Count and input form

*Display comment input form*

~~~
[comment form]
~~~

*Display number of comments for the current post*

~~~
Number of comments: [comment count]
~~~

You can also display the total comment count *after* a loop is finished:

~~~
Total number of comments: [comment total]
~~~



&nbsp;

### If post has comment

Use `[if comment]` to display something if the current post has any comments or not.

*Display comment count if the post has comment*

~~~
[loop type=post category=news]
  [if comment]
    [comment count]
  [else]
    No comment yet.
  [/if]
[/loop]
~~~


&nbsp;

### If comment author

Use `[if comment_author]` to check current comment's author in a comments loop.

Combined with a post loop, you can list newest comment by *other users* on a post that the current user commented on.

~~~
[loop type=post comment_author=this]
  [comments count=1]
    [if not comment_author=this]
      New comment on [field title] by [comment author] ([comment date])
    [/if]
  [/comments]
[/loop]
~~~

&nbsp;

### Template

You can also display comments of the current post using the theme's comment template.

*Display comment list using template*

~~~
Comment list: [comment template]
~~~

By default, the comments list is displayed by *comments.php* in the theme directory. If you want to specify a different template:

~~~
[comment template=short-comments.php]
~~~

This will look for the comments template in the child theme first, then in the parent theme.
