=== Hueman Addons ===
Contributors: nikeo
Author URI: http://presscustomizr.com
Plugin URI: https://wordpress.org/plugins/hueman-addons/
Tags: hueman theme, hueman
Requires at least: 3.4
Tested up to: 4.7
Stable tag: 2.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lightweight addons plugin for the Hueman WordPress theme.

== Description ==
**Addons for the Hueman WordPress theme.**
The Hueman Addons is a WordPress plugin including some cool features like a social share bar and useful shortcodes.
The plugin has been designed specifically for the Hueman WordPress theme. Lightweight and safe.


== Installation ==
1. Install the plugin right from your WordPress admin in plugins > Add New. 
1-bis. Download the plugin, unzip the package and upload it to your /wp-content/plugins/ directory
2. Activate the plugin


== Screenshots ==
1. Responsive columns shortcode
2. Share Bar example


==  How to use the Shortcodes ==
[Documentation here](http://docs.presscustomizr.com/article/246-hueman-addons-how-to-use-the-shortcodes)


==  How to use the Share Bar options ==
[Documentation here](http://docs.presscustomizr.com/article/242-hueman-addons-how-to-set-the-share-bar-options)


== Changelog ==
= 2.0.7 January 5th 2017 =
* fixed : new customizer interface not loaded for multisites

= 2.0.6 January 4th 2017 =
* fixed : customizer not loading when deprecated link widget is enabled

= 2.0.5 : December 28th, 2016 =
* fixed : customizer panel doesn't scroll down fully
* fixed : customizer freezing on Safari 10.0.2

= 2.0.4 : December 21st, 2016 =
* fixed : it was not possible to set static front page and post page layout independently
* fixed : removed anonymous callback assigned to "hu_hueman_loaded" used to print dev logs
* fixed : undefined hu_is_customize_preview_frame() function

= 2.0.3 : December 19th, 2016 =
* fixed : retro-compatibility with php version 5.3, removed anonymous callback in action hook.

= 2.0.2 : December 19th, 2016 =
* improved : featured-posts-include is displayed in the customizer only when is_home() context
* fixed : customizer frozen in an infinite load in some specific cases
* fixed : replace hu_is_customize_preview_frame() by HU_AD() -> ha_is_customize_preview_frame()

= 2.0.1 : December 18th, 2016 =
* fixed : php compatibility issue

= 2.0.0 : December 16th, 2016 =
* added : new customizer interface. Requires WP 4.7+ and Hueman v3.3.0

= 1.0.9 : December 6th, 2016 =
* updated : customizer compatibility with Hueman version 3.2.11

= 1.0.8 : September 15th, 2016 =
* fixed typos

= 1.0.7 : September 14th, 2016 =
* fixed : Facebook counter not working due to API change
* fixed : Twitter counter not working due to API change

= 1.0.6 : September 9th, 2016 =
* fixed : added the text domain for internationalization

= 1.0.5 : September 9th, 2016 =
* Tested up to WP v4.6.1
* Improved documentation

= 1.0.4 : May 30th, 2016 =
* Fix : disapppearing sharebar, added function scope to jQuerySharre.js to avoid 3rd party plugin conflicts

= 1.0.3 : May 5th, 2016 =
* added : lang domain for plugin translation

= 1.0.2 : May 5th, 2016 =
* fixed : Share bar blocking view in mobile
* fixed : Add Share Class Only When Sharebar Active
* fixed : when the sticky option is enabled, the bar can be on top of the attachments slideshow
* fixed : disabled social share buttons : there is still block area stolen from the content
* added : LinkedIn share button
* added : options to select which button(s) to activate
* removed : grunt reload script

= 1.0.1 : April 14th, 2016 =
* updated : sharrre Jquery plugin to the latest version (2.0.1) https://github.com/Julienh/Sharrre
* fixed : undefined var _gaq in sharre
* fixed : Google Plus sharing button not showing up

= 1.0.0 : April 13th, 2016 =
* First offical release
