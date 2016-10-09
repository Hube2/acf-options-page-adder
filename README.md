Advanced Custom Fields: Options Page Adder
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

##Field Group Duplicators

Automatically duplicates field groups so that you can use the same field groups multiple 
times without the need to manually duplicate and modify all the field names in the group.

* Copy a field group to muliple options pages
* Copy a field group mulitple times to the same options page
* Copy a field group to a compound, tabbed field group

### Donations
If you find my work useful and you have a desire to send me money, which will give me an incentive to continue
offering and maintaining the plugins I've made public in my many repositories, I'm not going to turn it down
and whatever you feel my work is worth will be greatly appreciated. You can send money through paypal to
hube02[AT]earthlink[dot]net. 

#### Automatic Updates
Install [GitHub Updater](https://github.com/afragen/github-updater) on your site if you want to recieve automatic
updates for this plugin.

#### Remove Nag
You may notice that I've started adding a little nag to my plugins. It's just a box on some pages that lists my
plugins that you're using with a request do consider making a donation for using them. If you want to disable them
add the following filter to your functions.php file.
```
add_filter('remove_hube2_nag', '__return_true');
```