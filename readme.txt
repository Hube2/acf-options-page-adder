=== Advanced Custom Fields: Options Page Adder ===
Contributors: Hube2
Tags: Options Page, ACF
Requires at least: 3.5
Tested up to: 4.6
Stable tag: 3.1.4
Donate link: 
License: 
License URI: 

Allows easy creation of options pages using Advanced Custom Fields Pro without needing to do any PHP coding. Requires that ACF5 Pro is installed.

== Description ==


== Installation ==

1. Extract files to folder named acf-options-page-adder in your plugin folder (usually /wp-content/plugins/acf-options-page-adder)
2. Upload files
3. Activate it from the Plugins Page


== Screenshots ==


== Frequently Asked Questions == 


== Changelog ==

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
