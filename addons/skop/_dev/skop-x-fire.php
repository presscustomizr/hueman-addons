<?php

/* ------------------------------------------------------------------------- *
 *  LOAD
/* ------------------------------------------------------------------------- */
function HA_SKOP_OPT() {
    return HA_Skop_Option::ha_skop_opt_instance();
}


// skop-0-init-base.php
//  - registers the czr_skope_opt post type and creates a skope post if not yet created => this skope post will be used to store our skope data as post meta
//  - declares option excluded, protected from skoping
//  - declares helpers to
//      x prepare skope changeset for front end
//      x get skope post id
//      x get skope db data
//      x get skope data
//      x get skope default model
//      x handle the "skopification" of previous Hueman layout options
//
if ( defined('CZR_DEV') && true === CZR_DEV ) {
    require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-0-init-base.php' );
}


// "hu_hueman_loaded" is triggered in hueman/functions/init-core.php when all setup classes have been loaded and actions hooks scheduled
// this hook fires after 'plugins_loaded' at 'setup_theme', before 'after_setup_theme' ( and therefore 'init' )
// skop-options-base.php
//  - handles the skope option caching
//  - helpers :
//    x get skope option
//  - setup filters to get options : for preview, for frontend
//
// skop-options-front-end-value.php :
// - apply the skope inheritance to return the relevant value
//
// skop-options-preview-value.php :
// - get the sanitized preview val => for each level recursively take the customized, then db val, and fallback on default.
//
// skop-options-x-final.php :
// - compute the multidimensional preview val
//
// skop-options-x-final.php
// - register skope specific customizer setting-controls : show-skop-infos
add_action('hu_hueman_loaded', 'ha_load_skop_options');
function ha_load_skop_options() {
    if ( defined('CZR_DEV') && true === CZR_DEV ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-base.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-front-end-value.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-preview-value.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-x-final.php' );
    }
    HA_SKOP_OPT();
}

// skop-customize-register.php
//  - re-instantiate the 'header_image_data' and 'header_image' with a custom setting Class
//  - filter czr_js_customizer_control_params with additional localized params
if ( defined('CZR_DEV') && true === CZR_DEV ) {
    require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-customize-register.php' );
}
// Loads the various tmpl used by skope
require_once( HA_BASE_PATH . 'addons/skop/tmpl/skope-tmpls.php' );

new HA_Skop_Cust_Register();



// Customizer Ajax : we must for Hueman to be loaded (some Hueman constants are used)
add_action('hu_hueman_loaded', 'ha_load_skop_ajax');
// hook : 'hu_hueman_loaded'
// skop-ajax-changeset-base.php + publish + save :
//   - determine if it's a publish case or a "save for later" changeset ( draft or schedule )
//      x if published => save as a post meta of the skope post => update_post_meta( $skope_post_id, $skope_meta_key, $data );
//      x if saved => save as post meta of the current changeset post => update_post_meta( $changeset_post_id, $skope_meta_key, $data );
// skop-ajax-reset.php :
//  - reset published and drafted options
function ha_load_skop_ajax() {
    if ( defined('CZR_DEV') && true === CZR_DEV ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-base.php' );
        //<@4.9compat>
        // Now handled in skop-x-fire when the wp customize_changeset post is transitionning to "publish"
        //require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-publish.php' );
        //</@4.9compat>
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-save.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-reset.php' );
    }
    new HA_Skop_Chset_Reset();
}
if ( defined('CZR_DEV') && true === CZR_DEV ) {
    if ( apply_filters('ha_print_skope_logs' , false ) ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/_dev_skop-logs.php' );
        function ha_instantiate_dev_logs() {
            if ( class_exists( 'HA_skop_dev_logs') ) {
                new HA_skop_dev_logs(
                    array(
                        'hook' => '__header_after_container_inner',
                        'display_header' => true,
                        'tested_option' => 'header_image'
                    )

                );
            }
        }
        add_action('hu_hueman_loaded', 'ha_instantiate_dev_logs', 100 );
    }
}


// - print server skope data
// => will be send to control panel by /addons/assets/czr/fmk/js/czr-preview-base.js with
// api.preview.bind( 'sync', function( events ) {
//       api.preview.send( 'czr-skopes-synced', {
//             czr_skopes : _wpCustomizeSettings.czr_skopes || [],
//             isChangesetDirty : _wpCustomizeSettings.isChangesetDirty || false,
//             skopeGlobalDBOpt : _wpCustomizeSettings.skopeGlobalDBOpt || [],
//       } );
// });
add_action('init', 'ha_load_skop_customizer_preview' );
function ha_load_skop_customizer_preview() {
    //CUSTOMIZE PREVIEW : export skope data
    if ( HU_AD() -> ha_is_customize_preview_frame() ) {
        if ( defined('CZR_DEV') && true === CZR_DEV ) {
            require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-customize-preview.php' );
        }
        new HA_Skop_Cust_Prev();
    }
}



// WP publishes changeset when the customize_changeset post type transitions to "publish"
// This is done with add_action( 'transition_post_status', '_wp_customize_publish_changeset', 10, 3 );
// in wp-includes/default-filters.php
// Let's use the same logic to publish our skope metas
add_action( 'transition_post_status', 'ha_publish_skope_changeset_metas_on_post_status_transition', 0, 3 );
add_action( 'transition_post_status', 'ha_trash_skope_changeset_metas_on_post_status_transition', 0, 3 );

/**
 * hook : 'transition_post_status'
 * Inspired of _wp_customize_publish_changeset in wp-includes/theme.php
 * Publishes a snapshot's changes.
 *
 *
 * @global wpdb                 $wpdb         WordPress database abstraction object.
 * @global WP_Customize_Manager $wp_customize Customizer instance.
 *
 * @param string  $new_status     New post status.
 * @param string  $old_status     Old post status.
 * @param WP_Post $changeset_post Changeset post object.
 */
function ha_publish_skope_changeset_metas_on_post_status_transition( $new_status, $old_status, $changeset_post ) {
    global $wp_customize, $wpdb;

    $is_publishing_changeset = (
      'customize_changeset' === $changeset_post->post_type
      &&
      'publish' === $new_status
      &&
      'publish' !== $old_status
    );

    // ha_error_log('<TRANSITION POST STATUS CB>');
    // ha_error_log( 'Old Status ' . $old_status );
    // ha_error_log( 'New Status ' . $new_status );
    // ha_error_log('</TRANSITION POST STATUS CB>');

    if ( ! $is_publishing_changeset ) {
      return;
    }

    if ( empty( $wp_customize ) ) {
      require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
      $wp_customize = new WP_Customize_Manager( array(
        'changeset_uuid' => $changeset_post->post_name,
        'settings_previewed' => false,
      ) );
    }

    // The theme name is used to prefix the kope meta_key. Ex : hueman_czr_post_page_home
    $theme_name = ha_get_skope_theme_name();

    // DECLARE THE ARRAY THAT WILL COLLECT ERRORS
    $response = array();

    // GET THE TRANSITIONNING CHANGESET POST ID
    $changeset_post_id = $changeset_post->ID;

    // PREPARE THE CHANGESET DATA FOR PUBLICATION
    // [hueman_czr_all_page] => Array
    //     (
    //         [blogdescription] => Array
    //             (
    //                 [value] => Just another WordPress site
    //                 [type] => option
    //                 [user_id] => 1
    //             )

    //         [skope_infos] => Array
    //             (
    //                 [skope_id] => group_all_page
    //                 [level_id] => all_page
    //                 [skope] => group
    //                 [obj_id] =>
    //                 [meta_key] => hueman_czr_all_page
    //             )

    //     )

    // [hueman_czr_post_page_home] => Array
    //     (
    //         [blogdescription] => Array
    //             (
    //                 [value] => Home tag
    //                 [type] => option
    //                 [user_id] => 1
    //             )
    //          [hueman::nav_menu_locations[topbar]] => Array
    //             (
    //                  [value] => 177
              //        [type] => theme_mod
              //        [user_id] => 1
              //   )

              // [hueman::nav_menu_locations[mobile]] => Array
              //   (
              //        [value] => 179
              //         [type] => theme_mod
    //                   [user_id] => 1
    //             )
    //         [skope_infos] => Array
    //             (
    //                 [skope_id] => local_post_page_home
    //                 [level_id] => post_page_home
    //                 [skope] => local
    //                 [obj_id] => home
    //                 [meta_key] => hueman_czr_post_page_home
    //             )
    //     )
    $raw_changeset_data = get_post_meta( $changeset_post_id );
    $raw_changeset_data = is_array( $raw_changeset_data ) ? $raw_changeset_data : array();

    $unserialized_changeset_data = array();
    foreach ( $raw_changeset_data as $meta_key => $meta_value ) {
        // a skope meta key must start with the theme name
        if ( ! is_string( $meta_key ) || $theme_name != substr( $meta_key, 0 , strlen( $theme_name ) ) )
          continue;

        if ( is_array( $meta_value ) && 1 == count( $meta_value ) ) {
            $unserialized_changeset_data[ $meta_key ] = maybe_unserialize( $meta_value[0] );
        } else {
            $unserialized_changeset_data[ $meta_key ] = array_map( 'maybe_unserialize', $meta_value );
        }
    }

    // error_log('<CHANGESET RAW DATA>');
    // error_log( print_r( $unserialized_changeset_data, true ) );
    // error_log('</CHANGESET RAW DATA>');

    // PREPARE CUSTOMIZE_DATA
    // Array
    // (
    //     [blogdescription] => Array
    //         (
    //             [value] => Site tagline
    //         )

    //     [blogname] => Array
    //         (
    //             [value] => Site name
    //         )
    //    [hueman::nav_menu_locations[topbar]] => Array
    //        (
    //             [value] => 177
    //        )
    // )
    //
    // Should become
    // array(
    //  [blogdescription] => Site tagline
    //  [blogname] => Site name
    //  [nav_menu_locations] => array(
    //      [topbar] => 177
    //  )
    // )
    $changeset_candidate_data = array();
    foreach ( $unserialized_changeset_data as $skope_meta_key => $customized_data ) {
        $changeset_candidate_data[$skope_meta_key] = array_key_exists( $skope_meta_key, $changeset_candidate_data ) ? $changeset_candidate_data[$skope_meta_key] : array();
        foreach ( $customized_data as $raw_setting_id => $setting_data ) {
            if ( ! is_array( $setting_data ) || ! array_key_exists( 'value', $setting_data ) ) {
              //ha_error_log( ' sent are not well formed for skope : ' . $skope_id );
              continue;
            }

            $setting_id = $raw_setting_id;
            // If theme_mod type, get rid of the theme name prefix
            if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {
                $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                  $setting_id = $matches['setting_id'];
                }
            }
            $changeset_candidate_data[$skope_meta_key][$setting_id] = $setting_data;
        }
    }



    // error_log('<CHANGESET CANDIDATE DATA>');
    // error_log( print_r( $changeset_candidate_data, true ) );
    // error_log('</CHANGESET CANDIDATE DATA>');


    // GET THE ALREADY PUBLISHED DATA
    $skope_post_id  = get_option('skope-post-id');
    if ( false === $skope_post_id || empty( $skope_post_id ) ) {
        wp_send_json_error( 'missing skope_post_id when attempting to publish the meta changeset' );
    }

    if ( ! $skope_post_id ) {
        wp_send_json_error( 'NO SKOPE CHANGESET POST ID' );
        return;
    }


    //GET AND PREPROCESS ALL PUBLISHED SKOPE METAS
    $raw_published_data = get_post_meta( $skope_post_id );
    $raw_published_data = is_array( $raw_published_data ) ? $raw_published_data : array();
    $unserialized_published_data = array();
    foreach ( $raw_published_data as $meta_key => $meta_value ) {
        if ( is_array( $meta_value ) && 1 == count( $meta_value ) ) {
            $unserialized_published_data[ $meta_key ] = maybe_unserialize( $meta_value[0] );
        } else {
            $unserialized_published_data[ $meta_key ] = array_map( 'maybe_unserialize', $meta_value );
        }
    }


    // error_log('<PUBLISHED RAW DATA>');
    // error_log( print_r( $unserialized_published_data, true ) );
    // error_log('</PUBLISHED RAW DATA>');

    if ( is_wp_error( $unserialized_published_data ) ) {
        $response['publish_skope_changeset_failure'] = $unserialized_published_data -> get_error_code();
        return new WP_Error( 'publish_skope_changeset_failure', '', $response );
    }

    //in publish context, the saved data looks like
    // [hu_theme_options[copyright]] => copyright SAMPLE 7
    //
    // in changeset update context, the saved data looks like
    // [hu_theme_options[copyright]] => Array
    // (
    //     [value] => copyright SAMPLE
    //     [type] => option
    //     [user_id] => 1
    // )
    $changesetified_published_data = array();
    // An option like nav_menu_locations is saved as :
    //  [nav_menu_locations] => Array
    // (
    //     [footer] => 2
    //     [topbar] => 4
    //     [header] => 3
    // )
    // => it must be turned into changeset compatible settings looking like :
    // nav_menu_locations[footer] = array( 'value' => 2 )
    // nav_menu_locations[topbar] = array( 'value' => 4 )
    // nav_menu_locations[header] = array( 'value' => 3 )
    foreach ( $unserialized_published_data as $skope_meta_key => $options_data ) {
        // a skope meta key must start with the theme name
        if ( ! is_string( $skope_meta_key ) || $theme_name != substr( $skope_meta_key, 0 , strlen( $theme_name ) ) )
          continue;

        $changesetified_published_data[ $skope_meta_key ] = array();
        foreach ( $options_data as $_setid => $_value ) {
            //Keep the skope infos as is
            if ( 'skope_infos' == $_setid ) {
                $changesetified_published_data[ $skope_meta_key ][$_setid] = $_value;
            } else if ( _ha_is_wp_setting_multidimensional( $_setid ) ) {
                $to_merge = _ha_build_multidimensional_db_option( $_setid, $_value );
                foreach ( $to_merge as $_id => $val ) {
                    $changesetified_published_data[ $skope_meta_key ][$_id] = array( 'value' => $val );
                }
            } else {
                $changesetified_published_data[ $skope_meta_key ][$_setid] = array( 'value' => $_value );
            }
        }
    }

    // error_log('<CHANSETIFIED PUBLISHED DATA>');
    // error_log( print_r( $changesetified_published_data, true ) );
    // error_log('</CHANSETIFIED PUBLISHED DATA>');

    // Obtain/merge data for changeset.
    $publication_candidate_data = $changesetified_published_data;
    if ( is_wp_error( $publication_candidate_data ) ) {
      $publication_candidate_data = array();
    }


    ///////////////////////////////////
    /// Ensure that all changeset values are included in the published data.
    foreach ( $changeset_candidate_data as $skope_meta_key => $skope_values ) {
        // a skope meta key must start with the theme name
        if ( ! is_string( $skope_meta_key ) || $theme_name != substr( $skope_meta_key, 0 , strlen( $theme_name ) ) )
          continue;

        if ( ! array_key_exists( $skope_meta_key, $publication_candidate_data ) ) {
            $publication_candidate_data[$skope_meta_key] = array();
        }
        foreach ( $skope_values as $setting_id => $set_value ) {
            if ( ! array_key_exists( $setting_id, $publication_candidate_data[$skope_meta_key] ) ) {
                $publication_candidate_data[$skope_meta_key][ $setting_id ] = array();
            }
            if ( ! array_key_exists( 'value', $set_value ) ) {
                continue;
                ha_error_log( 'A setting value is not well formed for setting : ' . $setting_id );
            }

            $publication_candidate_data[$skope_meta_key][ $setting_id ]['value'] = $set_value['value'];
        }//foreach()
    }



    // error_log('<$publication_candidate_data BEFORE PREPARATION>');
    // error_log( print_r( $publication_candidate_data, true ) );
    // error_log('</$publication_candidate_data BEFORE PREPARATION>');


    ///////////////////////////////////
    /// BUILD DATA TO BE SAVED
    foreach ( $publication_candidate_data as $skope_meta_key => $options_data ) {
        foreach ( $options_data as $setting_id => $setting_params ) {
            if ( 'skope_infos' == $setting_id )
              continue;
            $setting = $wp_customize->get_setting( $setting_id );
            if ( ! $setting || ! $setting->check_capabilities() ) {
                ha_error_log( 'In _publish_skope_changeset_metas, ' . $setting_id . ' is not registered in $wp_customize.' );
                continue;
            }
        }//foreach()

        //////////////////////////////////////
        /// PREPARE DATA FOR FRONT END :
        /// 1) Keep only the value
        /// 2) Handle multidim theme_mod type
        $publication_candidate_data[$skope_meta_key] = ha_prepare_skope_changeset_for_front_end( $publication_candidate_data[$skope_meta_key] );

        if ( is_wp_error( $publication_candidate_data[$skope_meta_key] ) || ! is_array( $publication_candidate_data[$skope_meta_key] ) ) {
            $response[$skope_meta_key] = 'skope data not valid';
            return new WP_Error( 'publish_skope_changeset_failure', '', $response );
        }

    }//foreach()

    // error_log('<$publication_candidate_data AFTER PREPARATION>');
    // error_log( print_r( $publication_candidate_data, true ) );
    // error_log('</$publication_candidate_data AFTER PREPARATION>');

    //////////////////////////////////////
    /// PUBLISH
    foreach ( $publication_candidate_data as $skope_meta_key => $skope_option_values ) {
        $r = update_post_meta( $skope_post_id, $skope_meta_key, $skope_option_values );
        if ( is_wp_error( $r ) ) {
            $response['changeset_post_save_failure'] = $r->get_error_code();
            return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
        } else {
            ha_clean_skope_changeset_metas_after_publish( $changeset_post_id );
        }
    }

    return $response;
}//_publish_skope_changeset_metas


/**
 * hook : 'transition_post_status'
 * Inspired of _wp_customize_publish_changeset in wp-includes/theme.php
 * Publishes a snapshot's changes.
 *
 *
 * @global wpdb                 $wpdb         WordPress database abstraction object.
 * @global WP_Customize_Manager $wp_customize Customizer instance.
 *
 * @param string  $new_status     New post status.
 * @param string  $old_status     Old post status.
 * @param WP_Post $changeset_post Changeset post object.
 */
function ha_trash_skope_changeset_metas_on_post_status_transition( $new_status, $old_status, $changeset_post ) {
    $is_trashing_changeset = (
      'customize_changeset' === $changeset_post->post_type
      &&
      'trash' === $new_status
      &&
      'publish' !== $old_status
    );

    // error_log('<TRANSITION POST STATUS CB>');
    // error_log( 'Old Status ' . $old_status );
    // error_log( 'New Status ' . $new_status );
    // error_log('</TRANSITION POST STATUS CB>');

    if ( ! $is_trashing_changeset ) {
      return;
    }

    // GET THE TRANSITIONNING CHANGESET POST ID
    ha_clean_skope_changeset_metas_after_publish( $changeset_post->ID );
}


//add_action( 'wp_ajax_czr_clean_skope_changeset_metas_after_publish',  'ha_ajax_clean_skope_changeset_metas_after_publish' );
// hook : 'wp_ajax_czr_clean_skope_changeset_metas_after_publish'
// This is fired when the skope metas have been published => a save action with changesetStatus = 'publish'
// The goal is to remove the temporary post_metas used for skope and attached to the $wp_customize->changeset_post_id();

// @see czr fmk => api.czr_skopeSave.cleanSkopeChangesetMetas
// Warning : we don't want to send any json success or error here.
// => because on save it will be caught by the save wp $.ajax request as the server response and generate problem in customizer-control.js
function ha_clean_skope_changeset_metas_after_publish( $changeset_post_id ) {
    if ( ! $changeset_post_id )
      return;

    $all_skope_changeset_metas = get_post_meta( $changeset_post_id );
    $all_skope_changeset_metas = is_array( $all_skope_changeset_metas ) ? $all_skope_changeset_metas : array();

    //ha_error_log( print_R( $all_skope_changeset_metas, true ) );

    foreach ( $all_skope_changeset_metas as $meta_key => $val ) {
        $r = delete_post_meta( $changeset_post_id, $meta_key );
        if ( is_wp_error( $r ) ) {
            //wp_send_json_error( $r->get_error_message() );
            break;
        }
    }
    //wp_send_json_success();
}
?>