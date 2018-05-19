
# Menu

---

Use `[loop]` and the *menu* parameter to loop through menu items.

~~~
[loop menu='Menu Title']
  [field title-link]
[/loop]
~~~

### Parameters

> **menu** - get menu by title, slug or ID

> **list** - set *true* to create a list

>> **ul_class** - set `<ul>` class

>> **li_class** - set `<li>` class

### Fields

For each menu item, the following fields can be displayed.

> **title** - item title

> **title-link** - item title linked to the URL

> **url** - item URL

If the menu item is a post, you can also display other post fields.

&nbsp;

## Child menu items

Set *menu=children* to loop through child menu items.

~~~
[loop menu='Menu Title']
  Parent: [field title-link]
  [-loop menu=children]
    Child: [field title-link]
  [/-loop]
[/loop]
~~~

The child menu inherits the parent's parameters unless overridden.

## Conditions

~~~
[if first]The first menu item[/if]
[if last]The last menu item[/if]
[if children]Menu item has children[/if]
[if id=this]This menu item is the current page[/if]
~~~

## Menu item classes

You can add your own classes to the menu items.

~~~
[loop menu='Main Menu']
  <div class="page_item-[field id][if id=this] current_page_item[/if]">
    [field title-link]
  </div>
[/loop]
~~~
