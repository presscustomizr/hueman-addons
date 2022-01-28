=== Hueman Addons ===
Contributors: nikeo, d4z_c0nf
Author URI: https://presscustomizr.com
Plugin URI: https://wordpress.org/plugins/hueman-addons/
Tags: hueman theme, hueman, customizer, contextualizer
Requires at least: 4.7
Requires PHP: 5.4
Tested up to: 5.9
Stable tag: 2.3.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

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
= 2.3.2 July 20th 2021 =
* 100% compatible with WordPress 5.9

= 2.3.1 April 23rd 2021 =
* updated Font Awesome to v5.15.2
* successfully tested with WP 5.7.1

= 2.3.0 November 30th 2020 =
* fixed : [performance][Sharrre buttons] include js assets only when relevant, on single 'post' type
* fixed : [WP5.6][WP5.7] remove jquery-migrate dependencies

= 2.2.9 August 26, 2020 =
* updated : various minor updates following WP5.5

= 2.2.8 August 15, 2020 =
* fixed : [WP 5.5] javascript error in the customizer

= 2.2.7 July 18th, 2019 =
* fixed : LinkedIn sharre icon was not displayed because of a javascript error

= 2.2.6 May 8th, 2019 =
* replaced broken sharre count by "+" sign
* removed deprecated Google Plus

= 2.2.5 April 5th, 2019 =
* added : a new checkbox style in the customizer

= 2.2.4 February 13th, 2019 =
* added : support for rgb alpha colors in the contextualizer

= 2.2.3 February 10th, 2019 =
* fixed : deactivate the plugin when previewing Hueman Pro

= 2.2.2 December 19th, 2018 =
* fixed : unwanted error log displayed. reported here https://wordpress.org/support/topic/error-in-log-10/

= 2.2.1 December 17th, 2018 =
* fixed : the contextualizer select is not properly initialized

= 2.2.0 December 16th, 2018 =
* improved : compatibility with multilanguage plugins. Deprecation of theme mod 'skp__post_page_home' to allow the creation of a home page by language.

= 2.1.2 November 27th, 2018 =
* improved : customizer compatibility with network installs

= 2.1.0 June 27th 2018 =
* Improvements : Performance and UX enhancements made to the live customizer. Added the contextualizer feature.
* Added : the custom css can now be customized on each pages independently

= 2.0.16 February 14th 2018 =
* Fix : Multisite compatibility problem
* Fix : Title/Subtitle polylang plugin translation doesn't appear
* Added : new option to control the visibility of the sharre counters

= 2.0.15 November 21st 2017 =
* Fix : WP 4.9 Code Editor issue could impact the custom css customizer option when checking errors in the code

= 2.0.14 November 12 2017 =
* Fix : error when previewing theme in the customizer. Fixes #42.

= 2.0.13 November 11th 2017 =
* Fix : admin bar style printed when user not logged in
* Fix : polylang compat => exclude nav_menu_locations, blogname and blogdescription from "by page customization" when polylang is active. Fixes #34. Fix presscustomizr/hueman#377
* added : a set of shared functions in a new separated file : addons/ha-functions.php
* Fix : remove unused skop _dev files
* Improved : compatibility with WordPress 4.9, release target date November 14th 2017

= 2.0.12 October 14th 2017 =
* fix : customizer preview not working in hueman pro when the hueman addons plugin activated. fixes #35.
* improved : various minor improvements in the customizer control javascript
* added : a search doc field to the welcome page

= 2.0.11 May 8th 2017 =
* improved : better initialization process for the customizer preview when fired from appearance > themes

= 2.0.10 April 28th 2017 =
* added : customizer help blocks
* improved : customizer performance on load
* improved : sharre bar behaviour for mobile devices and on scroll

= 2.0.9 March 2nd 2017 =
* fixed : Google Plus button disappeared in v2.0.8

= 2.0.8 February 26th 2017 =
* improved : better customizer user interface
* improved : javascript error handling in the customizer

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
