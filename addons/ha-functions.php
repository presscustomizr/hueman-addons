<?php


//@return an array of unfiltered options
//=> all options or a single option val
function ha_get_raw_option( $opt_name = null, $opt_group = null ) {
    $alloptions = wp_cache_get( 'alloptions', 'options' );
    $alloptions = maybe_unserialize($alloptions);
    if ( ! is_null( $opt_group ) && isset($alloptions[$opt_group]) ) {
      $alloptions = maybe_unserialize($alloptions[$opt_group]);
    }
    if ( is_null( $opt_name ) )
      return $alloptions;
    return isset( $alloptions[$opt_name] ) ? maybe_unserialize($alloptions[$opt_name]) : false;
}


//Determine if the current ajax request is a selective partial refresh
//Checks if the ajax query contains either : renderQueryVar 'wp_customize_render_partials', declared in class-wp-customize-selective-refresh
//or the 'partials' param
function ha_is_partial_ajax_request() {
  return isset( $_POST['wp_customize_render_partials'] ) || ( isset($_POST['partials']) && ! empty( $_POST['partials'] ) );
}


/**
* Check whether the plugin is active by checking the active_plugins list.
* copy of is_plugin_active declared in wp-admin/includes/plugin.php
*
*
* @param string $plugin Base plugin path from plugins directory.
* @return bool True, if in the active plugins list. False, not in the list.
*/
function ha_is_plugin_active( $plugin ) {
  return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || ha_is_plugin_active_for_network( $plugin );
}

/**
* Check whether the plugin is active for the entire network.
* copy of is_plugin_active_for_network declared in wp-admin/includes/plugin.php
*
*
* @param string $plugin Base plugin path from plugins directory.
* @return bool True, if active for the network, otherwise false.
*/
function ha_is_plugin_active_for_network( $plugin ) {
  if ( ! is_multisite() )
    return false;

  $plugins = get_site_option( 'active_sitewide_plugins');
  if ( isset($plugins[$plugin]) )
    return true;

  return false;
}