=== Advanced Custom Fields: Options Page Adder ===
Contributors: Hube2
Tags: Options Page, ACF
Requires at least: 3.5
Tested up to: 4.0
Stable tag: 2.1.0
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