ACF Options Page Admin
==========================================

Allows creation of options pages using Advanced Custom Fields Pro without needing to do any 
PHP coding.

**Requires that ACF5 Pro is installed.**

This plugin will not provide any functionality if ACF5 Pro is not installed and active

##Installation

* Download and decompess plugin
* Create a folder in your plugins folder named "acf-options-page-adder"
* Upload files to the folder you just created
* Activate plugin

##Options Pages

Allows adding options pages though an admin interface. Supports all the features of 
ACF Options Pages. For more information see 
http://www.advancedcustomfields.com/resources/options-page/

##Change capability
The capability required to add/edit options pages was changed to "manage_options" in version 3.3.0.
This can be altered by adding a filter
```
add_filter('acf-options-page-adder/capability', 'my_acf_options_page_adder_cap');
function my_acf_options_page_adder_cap($cap) {
  $cap = 'edit_published_posts';
  return $cap;
}
```

##Get Options Page Save to ID
Added in version 3.5.0 functions to get the options save to value ($post_id), also added a filter. This
function or filter will return the current "post_id" setting of for an "menu_slug". It will return either
"options" or a post ID if the options page is set to save values to a post object.
Example of use
```
// example 1: get_options_page_id()

// get the post_id of an options page
$post_id = get_options_page_id('my_options_page_slug');
// get a value using $post_id
$value = get_field('my_option_field', $post_id);
```

```
// example 2: by filter
$default = 'option',
$slug = 'my_options_page_slug';
$post_id = apply_filters('get_option_page_id_filter', $default, $slug);
$value = get_field('my_option_field', $post_id);

// or like this
$value = get_field('my_option_field', apply_filters('get_option_page_id_filter', $default, $slug));
```

There is a condition where you will get the incorrect post id. This condition is created by having a
top level redirect page that is set to redirect to the first sub options page. If there is no sub options
page that exists then it will return the value for the top level options page. If you later create a
sub options page it will return the new value from the sub options page. This is why I have see the
default value of redirect to false. If you want the top level page to redirect the you need to be aware
that it can cause you issues later down the road if you haven't created a sub option page. You should also specifically set the order of sub options pages so that these do not change at some point in the future
because adding a new options page with the same order as the existing top level page will alter the save
and get location to the new options page. There's noting I can do about this, it the way it works. When
setting up ACF options pages to save to a post instead of options you must be more precise in with the
options page arguments.

##Warning
If you have created ACF options pages manually in code then there is a condition where this plungin will
not correctly detect the top level options page in a group and will not be able to successfully add
sub options pages to that group. This will happen if you have the top level options page set to redirect
to the first sub options page. This problem can be avoided if
```
$menu_slug == strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $page_title), '-'));
```
In other words, the slug must be all lower case and contain only letters, numbers and dashes 
(hyphens -) and that there is never 2 or more consectutive dashes.

Options page groups added using this plugin work correctly as this problem is dealt with internally.

##Saving options page values to a post ID
ACF v5.2.7 added the ability to save options page fields to a post ID, so here's a hint.
Why not save your values to the options page post that is created when you set up an options page using
this plugin? The post type is already created and it really is a perfect place to store the values. Here's
a bonus, if you delete the options page then all of those values will be deleted right long with it.
You can even use `get_fields($options_page_id)` without needing to worry about getting the fields for
the options page itself. Why? because all the fields used for creating the options page start with an
underscore `_` and will not be returned by `get_fields()`. The only thing you need to be careful of is not
using any of the field names used by this plugin, which should be extremely easy since they all start with
`_acfop_`.

### Donations
If you find my work useful and you have a desire to send me money, which will give me an incentive to continue
offering and maintaining the plugins I've made public in my many repositories, I'm not going to turn it down
and whatever you feel my work is worth will be greatly appreciated. You can send money through paypal to
hube02[AT]earthlink[dot]net. 

#### Automatic Updates
Github updater support has been removed. This plugin has been published to WordPress.Org here
https://wordpress.org/plugins/options-page-admin-for-acf/. If you are having problems updating please
try installing from there. 

#### Remove Nag
You may notice that I've started adding a little nag to my plugins. It's just a box on some pages that lists my
plugins that you're using with a request do consider making a donation for using them. If you want to disable them
add the following filter to your functions.php file.
```
add_filter('remove_hube2_nag', '__return_true');
```