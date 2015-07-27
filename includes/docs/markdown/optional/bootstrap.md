
# Bootstrap

---

### Tabs

To display a menu in Bootstrap tabs or pills, use the *ul* parameter.

*Display a menu in stacked pills*

~~~
[content menu='Side Menu' ul=nav-pills-stacked]
~~~

The available values are: *nav-tabs*, *nav-pills* or *nav-pills-stacked*.

&nbsp;

### Navbar

To display a menu in a Bootstrap navbar, use the `[navbar]` shortcode.

~~~
[navbar menu='Main Menu']
  Brand
[/navbar]
~~~

The *menu* parameter is the title of the menu to be displayed. You can put text or image for the brand.

Optionally, you can set the *navclass* parameter to: *top-nav*, *navbar-fixed-top*, *navbar-fixed-bottom*, *navbar-static-top*. The default is *top-nav*. Please refer to the [Bootstrap documentation](http://getbootstrap.com/components/#navbar) for details.
