# Hueman Addons #
* Contributors: nikeo
* Author URI: http://presscustomizr.com
* Plugin URI: https://github.com/presscustomizr/hueman-addons
* Tags: hueman theme, hueman
* Requires at least: 3.4
* Tested up to: 4.5
Stable tag: 1.0.3
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Addons for the Hueman WordPress theme

## Description ##

**Addons for the Hueman WordPress theme.**
The plugin includes the Share bar and shorcodes for the Hueman theme.

## Download link ##
[hueman-addons.zip](https://github.com/presscustomizr/hueman-addons/releases/download/v1.0.3/hueman-addons.zip)


## How to use the shortcodes ##

**Divider Line**
```
[hr]
```
    
**Highlight Text**
```
[highlight]My highlighted text[/highlight]
```
    
**Dropcap (large first letter)**
```
[dropcap]A[/dropcap]nother dropcap here.
```
    
*Note: If you add the dropcap in the beginning of the article, it will disappear from the excerpt. To fix this, when editing the post, click Screen Options top right. Then enable Excerpt and you can write your own custom excerpt in the content box below the main text field.

**Pullquote Left**
```
[pullquote-left]Pullquote text[/pullquote-left]
```
    
**Pullquote Right**
```
[pullquote-right]Pullquote text[/pullquote-right]
```
    
**Responsive Columns**
```
[column size="one-half"]...[/column]
[column size="one-half" last="true"]...[/column]

[column size="one-third"]...[/column]
[column size="one-third"]...[/column]
[column size="one-third" last="true"]...[/column]

[column size="two-third"]...[/column]
[column size="one-third" last="true"]...[/column]

[column size="one-fourth"]...[/column]
[column size="one-fourth"]...[/column]
[column size="one-fourth"]...[/column]
[column size="one-fourth" last="true"]...[/column]

[column size="three-fourth"]...[/column]
[column size="one-fourth" last="true"]...[/column]

[column size="one-fifth"]...[/column]
[column size="one-fifth"]...[/column]
[column size="one-fifth"]...[/column]
[column size="one-fifth"]...[/column]
[column size="one-fifth" last="true"]...[/column]
```    


## Credits ##



## Translations ##



## Installation ##

1. Install the plugin right from your WordPress admin in plugins > Add New. 
1-bis. Download the plugin, unzip the package and upload it to your /wp-content/plugins/ directory
2. Activate the plugin

## Frequently Asked Questions ##


## Screenshots ##



## Changelog ##
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
