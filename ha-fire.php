<?php
/**
* Plugin Name: Hueman Addons
* Plugin URI: http://presscustomizr.com
* Description: Hueman Theme Addons
* Version: 2.0.8
* Text Domain: hueman-addons
* Author: Press Customizr
* Author URI: http://presscustomizr.com
* License: GPLv2 or later
*/

/**
* helper ( can be already defined in the hueman theme)
* Check if we are really on home, all cases covered
* @return  bool
*/
if ( ! function_exists( 'hu_is_real_home') ) {
  function hu_is_real_home() {
    return ( is_home() && ( 'posts' == get_option( 'show_on_front' ) || '__nothing__' == get_option( 'show_on_front' ) ) )
    || ( 0 == get_option( 'page_on_front' ) && 'page' == get_option( 'show_on_front' ) )//<= this is the case when the user want to display a page on home but did not pick a page yet
    || is_front_page();
  }
}

require_once( plugin_dir_path( __FILE__ ) . 'addons/ha-init.php' );