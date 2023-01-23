<?php


//@return an array of unfiltered options
//=> all options or a single option val
//@param $report_error is used only when invoking HU_utils::set_option() to avoid a potential theme option reset
//=> prevent issue https://github.com/presscustomizr/hueman/issues/571
function ha_get_raw_option( $opt_name = null, $opt_group = null, $from_cache = true, $report_error = false ) {
    $alloptions = wp_cache_get( 'alloptions', 'options' );
    $alloptions = maybe_unserialize( $alloptions );

    //prevent issue https://github.com/presscustomizr/hueman/issues/492
    //prevent issue https://github.com/presscustomizr/hueman/issues/571
    if ( $report_error ) {
        if ( ! is_array( $alloptions ) || empty( $alloptions ) ) {
            return new WP_Error( 'wp_options_not_cached', '' );
        }
    }

    $alloptions = ! is_array( $alloptions ) ? array() : $alloptions;//prevent issue https://github.com/presscustomizr/hueman/issues/492

    //is there any option group requested ?
    if ( ! is_null( $opt_group ) && array_key_exists( $opt_group, $alloptions ) ) {
      $alloptions = maybe_unserialize( $alloptions[ $opt_group ] );
    }
    //shall we return a specific option ?
    if ( is_null( $opt_name ) ) {
        return $alloptions;
    } else {
        $opt_value = array_key_exists( $opt_name, $alloptions ) ? maybe_unserialize( $alloptions[ $opt_name ] ) : false;//fallback on cache option val
        //do we need to get the db value instead of the cached one ? <= might be safer with some user installs not properly handling the wp cache
        //=> typically used to checked the template name for czr_fn_isprevdem()
        if ( ! $from_cache ) {
            global $wpdb;
            //@see wp-includes/option.php : get_option()
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $opt_name ) );
            if ( is_object( $row ) ) {
                $opt_value = $row->option_value;
            }
        }
        return maybe_unserialize( $opt_value );
    }
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


//@return bool
function ha_isprevdem() {
    global $wp_customize;
    $is_dirty = false;

    if ( is_object( $wp_customize ) && method_exists( $wp_customize, 'unsanitized_post_values' ) ) {
        $real_cust            = $wp_customize -> unsanitized_post_values( array( 'exclude_changeset' => true ) );
        $_preview_index       = array_key_exists( 'customize_messenger_channel' , $_POST ) ? $_POST['customize_messenger_channel'] : '';
        $_is_first_preview    = false !== strpos( $_preview_index ,'-0' );
        $_doing_ajax_partial  = array_key_exists( 'wp_customize_render_partials', $_POST );
        //There might be cases when the unsanitized post values contains old widgets infos on initial preview load, giving a wrong dirtyness information
        $is_dirty             = ( ! empty( $real_cust ) && ! $_is_first_preview ) || $_doing_ajax_partial;
    }
    // was formerly using is_child_theme() but it has been dropped because of https://github.com/presscustomizr/hueman-addons/issues/46#issuecomment-366235847
    // => this function can't be invoked too early from a plugin because TEMPLATEPATH and STYLESHEETPATH may not be defined yet
    return apply_filters( 'hu_isprevdem', ! $is_dirty && ha_get_raw_option( 'template', null, false ) != get_template() && ! ( defined('HU_IS_PRO') && HU_IS_PRO ) );
}



// @return bool
// skop shall not be activated when previewing the theme from the customizer or when using a version of wp < 4.7
function ha_is_skop_on() {
    global $wp_version;
    if( ! defined( 'HA_SKOP_ON' ) ) {
        //'enable-skope' option can take two string values : "yes" and "no".
        //If the option is not set yet, which is the most common case, it means that it is enabled ( @see default value == "yes" when registering the setting )
        $_skope_enable_val = ha_get_raw_option( 'enable-skope' , 'hu_theme_options');
        define( 'HA_SKOP_ON' , ! is_string( $_skope_enable_val ) || 'yes' == $_skope_enable_val );
    }
    return apply_filters( 'ha_is_skop_on', HA_SKOP_ON && ! ha_isprevdem() && version_compare( $wp_version, '4.7', '>=' ) );
}







function sek_error_log( $title, $content = null ) {
    if ( is_null( $content ) ) {
        error_log( '<' . $title . '>' );
    } else {
        error_log( '<' . $title . '>' );
        error_log( print_r( $content, true ) );
        error_log( '</' . $title . '>' );
    }
}