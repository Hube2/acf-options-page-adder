=== ACF Options Page Admin ===
Contributors: Hube2
Tags: Options Page, ACF
Requires at least: 3.5
Tested up to: 4.8
Stable tag: 3.8.5
Donate link: 
License: 
License URI: 

Allows easy creation of options pages using Advanced Custom Fields Pro without needing to do any PHP coding. Requires that ACF5 Pro is installed (or ACF5 + Options Page Add On)

== Description ==

This is an add on plugin for Advanced Custom Fields (ACF) 5 + the Options Page Add On (or ACF Pro).
***This plugin will not provide any functionality unless ACF 5 and the Options Page Add On (or ACF5 Pro)
is installed***

This plugin provides an admin interface for adding options pages in ACF including all options for ACF
options pages. Most options are selectable, for example the menu location, capability and where to save
field values to.

For more information see
[Other Notes](https://wordpress.org/plugins/options-page-admin-for-acf/other_notes/) and
[Screenshots](https://wordpress.org/plugins/options-page-admin-for-acf/screenshots/)

== Installation ==

1. Extract files to folder named acf-options-page-adder in your plugin folder (usually /wp-content/plugins/options-page-admin-for-acf/)
2. Upload files
3. Activate it from the Plugins Page


== Screenshots ==

1. Options Page Admin List
2. Options Page Basic Settings
3. Options Sub Page Basic Settings
4. Options Page Advanced Settings
5. Options Sub Page Advanced Settings
6. Options Page Content Customization

== Frequently Asked Questions == 

None Yet

== Other Notes ==

== Github Repository ==

This plugin is also on GitHub 
[https://github.com/Hube2/acf-options-page-adder](https://github.com/Hube2/acf-options-page-adder)

== Change Capability ==

The capability required to add/edit options page settings is "manage_options". This capability can be changed by adding a filter.
`
add_filter('acf-options-page-adder/capability', 'my_acf_options_page_adder_cap');
function my_acf_options_page_adder_cap($cap) {
  $cap = 'edit_published_posts';
  return $cap;
}
`

== Saving Values to the Options Page Post ==

ACF v5.2.7 added the ability to save options page fields to a post ID. This plugin will let you save the options to the same post ID of the post created when adding an options page using this plugin. You can even use get_fields($options_page_id) without needing to worry about getting the fields for the options page itself. Why? because all the fields used for creating the options page start with an underscore _ and will not be returned by get_fields(). The only thing you need to be careful of is not using any of the field names used by this plugin, which should be extremely easy since they all start with _acfop_.

== Get Post ID for Options Page ==

A function and a filter are available for getting the correct ACF $post_id value to use for getting
values from the options page. This function/filter will return 'options' for options pages stored in
options or will return the correct post ID if options are saved to a post. The correct ID is returned
based on the "menu_slug" value of the options page.

`
/ example 1: function get_options_page_id()

// get the post_id of an options page
$post_id = get_options_page_id('my_options_page_slug');
// get a value using $post_id
$value = get_field('my_option_field', $post_id);

// or it can be combined like this
$value = get_field('my_option_field', get_options_page_id('my_options_page_slug'));
`

`
// example 2: by filter
$default = 'option',
$slug = 'my_options_page_slug';
$post_id = apply_filters('get_option_page_id_filter', $default, $slug);
$value = get_field('my_option_field', $post_id);

// or it can be combined like this
$value = get_field('my_option_field', apply_filters('get_option_page_id_filter', $default, $slug));
`

*There is a condition where you will get the incorrect post id. This condition is created by having a top level redirect page that is set to redirect to the first sub options page. If there is no sub options page that exists then it will return the value for the top level options page. If you later create a sub options page it will return the new value from the sub options page. This is why I have see the default value of redirect to false. If you want the top level page to redirect the you need to be aware that it can cause you issues later down the road if you haven't created a sub option page. You should also specifically set the order of sub options pages so that these do not change at some point in the future because adding a new options page with the same order as the existing top level page will alter the save and get location to the new options page. There's noting I can do about this, it the way it works. When setting up ACF options pages to save to a post instead of options you must be more precise in with the options page arguments.*

== Font Awesome Support ==

Please note that this plugin does not enqueue or include Font Awswsome in the admin of your site.
If you include Font Awsome in your admin then you can use Font Awesome Icons for the icons of
top level options page. For example if you wanted to use [Address Book Icon](http://fontawesome.io/icon/address-book/) then all you need to do is add `fa fa-address-book`
into the Icon field when adding or editing the options page.

== Filter Options Page Header/Footer/Content ==

Version 3.8.0 of this plugin added the ability to customize the ACF options page by adding header and footer content. In addtion to the WYSIWYG fields that have been added to the options page admin editor you can also customize these sections, or the entire options page using filters.

Version 3.8.2 of this plugin adds a toggle to enable this feature. This feature can cause an out of
memory fatal error on options pages that have large numbers of fields. If content was entered into this field before the toggle to turn in on then the toggle will be automatically turned on. Otherwise the default for this option will be off.

**Header Content**

`
add_filter('acf-options-page-adder/page-header', 'my_custom_options_page_header', 10, 2);

function my_custom_options_page_header($content, $hook) {
  // $content = content, by default it is '' or the value of the WYSIWYG editor
	// $hook = the current options page hook that is being called
	$content = '<p>My Custom Header Content</p>';
	return $content;
}
`

**Footer Content**

`
add_filter('acf-options-page-adder/page-footer', 'my_custom_options_page_footer', 10, 2);

function my_custom_options_page_footer($content, $hook) {
  // $content = content, by default it is '' or the value of the WYSIWYG editor
	// $hook = the current options page hook that is being called
	$content = '<p>My Custom Footer Content</p>';
	return $content;
}
`

**Filter Entire Options Page**

`
add_filter('acf-options-page-adder/page-content', 'my_custom_options_page_filter', 10, 2);

function my_custom_options_page_filter($content, $hook) {
  // $content = entire content or options page, including all ACF fields
	// $hook = the current options page hook that is being called
	// caution should be taken when making modification to the page content
	return $content;
}
`

== Remove Nag ==

If you would like to remove my little nag that appears on some admin pages add the following to your functions.php file
`
add_filter('remove_hube2_nag', '__return_true');
`


== Changelog ==

= 3.8.5 =
* removed ACF from disallowed parent parent menus

= 3.8.4 =
* translation updates

= 3.8.3 =
* correcting some minor issues/erros introduced in 3.8.2

= 3.8.2 =
* added toggle to turn customize features on/off

= 3.8.1 =
* corrected bug in menu position
* corrected bug in hook setting

= 3.8.0 =
* replaced register_field_group() call with acf_add_local_field_group() - #41
* added tabs for basic/advanced settings
* added page content customization options + new filters - #40
* added performance optimization - #42
* added internal correction for attaching files - #39
* minor modifications

= 3.7.5 =
* more updates to Russian translation [@antonvyukov](https://wordpress.org/support/users/antonvyukov/)

= 3.7.4 =
* updated Russian translation [@antonvyukov](https://wordpress.org/support/users/antonvyukov/)

= 3.7.3 =
* added more missing text domains
* completed adding Portuguese (pt_PT) translation
* converted True/False radio fields to ACF true/false UI fields
* added Russian (ru_RU) translations - thanks [@antonvyukov](https://wordpress.org/support/users/antonvyukov/)

= 3.7.2 =
* Added missing text domain
* Typos corrected
* Add Portuguese (pt_PT) translation - thanks [@pedro-mendonca](https://wordpress.org/support/users/pedromendonca)

= 3.7.1 =
* removed github updater support

= 3.7.0 =
* Added support for Font Awesome icons [See Other Notes](https://wordpress.org/plugins/options-page-admin-for-acf/other_notes/)

= 3.6.1 =
* First release to wordpress.org

= 3.6.0 =
* Added filter to use page tiles instead of menu title for location setting when editing ACF field group
* Corrected some bugs
* Removed fieldset duplicator

= 3.5.3 =
* correction to when filter is added to prevent multiple additions of filter

= 3.5.2 =
* ?

= 3.5.1 =
* fixed bug, titles being removed from all other post types

= 3.5.0 =
* Changed default redirect value to false
* Added admin column for "Save To"
* Added function get_option_page_id() and get_options_page_id()

= 3.4.0 =
* removed post title
* added required title field
* slug field now required
* added validation to slug field
* removed no longer needed message field about post title
* updated instructions and messages for several fields

= 3.3.0 =
* changed capability to "manage_options"
* added filter to allow changing of capability

= 3.2.1 =
* corrected conditional logic on slug field

= 3.2.0 = 
* added remove nag filter

= 3.1.6 = 
* corrected white-space error

= 3.1.5 = 
* added donation box

= 3.1.4 =
* added support for github updater

= 3.1.3 ==
* Removed comment from field group duplicator

= 3.1.2 =
* Corrects Issue #17

= 3.1.1 =
* Added checks to ensure that ACF5 Pro is installed and active

= 3.1.0 =
* Corrected bug, duplicated field groups not showing on options pages
* Added support for save/loading to/from post objects added in ACF v5.2.7

= 3.0.0 =
* Added support for autoload option added in ACF v5.2.8

= 2.2.0 =
* Added field group duplicator

= 2.1.0 =
* Added support for redirect
* Added support for menu position
* Added support for icon (dashicons)
* Added sorting of sub options pages

= 2.0.0 =
* Corrected bug in adding top level option page
* Corrected bug in display of location
* Corrected bug in options page slugs not being saved correctly
* Removed support for ACF4
* Changed default value of location to "None"

= 1.1.1 =
* Removed $post global in function admin_columns_content(), not needed, post_id is passed by hook.
* Added code to prevent plugin from running if not included by WP.
* Reworked function acf_add_options_sub_page() to not use have_posts() function so it's less likely to interfere with other queries.
* Other minor code changes that don't effect operation

= 1.1.0 =
* Updated to be compatable with ACF-Pro (continues to work on ACF4)

= 1.0.0 =
* Initial Stable Version

= 0.0.1 =
* initial release

== Upgrade Notice ==
