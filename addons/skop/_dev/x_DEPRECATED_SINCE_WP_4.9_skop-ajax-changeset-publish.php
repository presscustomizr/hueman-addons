<?php


/* ------------------------------------------------------------------------- *
 *  PUBLISH SKOPE CHANGESET
 *  => as a meta of the option changeset post if stats == "publish"
/* ------------------------------------------------------------------------- */
//Same as save_changeset_post() but for the skope customized values
//It would have been better to use the existing core method but it's not possible with the current version
// => there's no way to populate the $post_array with a filter to add the meta_input entry
// $args = array(
//  'status' => '',
//  'data' => json_decode( wp_unslash( $_POST['customize_changeset_data'] ), true )
// )
if ( ! class_exists( 'HA_Skop_Chset_Publish' ) ) :
    class HA_Skop_Chset_Publish extends HA_Skop_Chset_Base {
        function __construct() {
          parent::__construct();
          add_action( 'wp_ajax_czr_clean_skope_changeset_metas_after_publish',  array( $this, 'ha_ajax_clean_skope_changeset_metas_after_publish' ) );
        }

        function _publish_skope_changeset_metas( $args = array() ) {
            // ha_error_log( '//////////////// START ARGS PARAM ////////////////// ');
            // ha_error_log( print_R( $args, TRUE ) );
            // ha_error_log( '//////////////// END ARGS PARAM ////////////////// ');
            global $wp_customize;
            if ( 'publish' != $args['status'] ) {
                wp_send_json_error( '_publish_skope_changeset_metas : status must be set to publish' );
                return;
            }
            if ( 'global' == HA_SKOP_OPT() -> ha_get_current_customized_skope() ) {
                wp_send_json_error( '_publish_skope_changeset_metas() : the global skope can not be published this way' );
                return;
            }

            if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) ) {
                wp_send_json_error( '_publish_skope_changeset_metas() : Missing opt_name or skope_id or skopeCustomized' );
                return;
            }

            $skope_meta_key = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name');
            $skope_id       = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );

            $args = array_merge(
                array(
                  'status' => null,
                  'data' => array(),
                  'user_id' => get_current_user_id()
                ),
                $args
            );

            error_log('<RAW ARGS>');
            error_log( print_r( $args['data'], true ) );
            error_log('</RAW ARGS>');

            //We are publishing
            $skope_post_id  = get_option('skope-post-id');
            if ( false === $skope_post_id || empty( $skope_post_id ) ) {
                wp_send_json_error( 'missing skope_post_id when attempting to publish the meta changeset' );
            }

            if ( ! $skope_post_id ) {
                wp_send_json_error( 'NO SKOPE CHANGESET POST ID' );
                return;
            }

            $_already_published_data = ha_get_skope_db_data(
                array(
                  'post_id' => $skope_post_id,
                  'skope_meta_key' => $skope_meta_key,
                  'is_option_post' => true,
                  'level' => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope')
                )
            );

            if ( is_wp_error( $_already_published_data ) ) {
                $response['publish_skope_changeset_failure'] = $_already_published_data -> get_error_code();
                return new WP_Error( 'publish_skope_changeset_failure', '', $response );
            }

            // ha_error_log( '//////////////// START ORIGINAL CHANGESET DATA ////////////////// ');
            // ha_error_log( $skope_post_id );
            // ha_error_log( $skope_meta_key );
            // ha_error_log( print_R( $existing_changeset_data, TRUE ) );
            // ha_error_log( '//////////////// END ORIGINAL CHANGESET DATA////////////////// ');
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
            $normalized_published_data = array();
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
            foreach ( $_already_published_data as $_setid => $_value ) {
                if ( 'skope_infos' == $_setid ) {
                    $normalized_published_data[$_setid] = $_value;
                } else if ( _ha_is_wp_setting_multidimensional( $_setid ) ) {
                    $to_merge = _ha_build_multidimensional_db_option( $_setid, $_value );
                    foreach ( $to_merge as $_id => $val ) {
                        $normalized_published_data[$_id] = array( 'value' => $val );
                    }
                } else {
                    $normalized_published_data[$_setid] = array( 'value' => $_value );
                }
            }

            //ha_error_log( '//////////////// START ORIGINAL CHANGESET DATA ////////////////// ');
            // ha_error_log( $skope_post_id );
            // ha_error_log( $skope_meta_key );
            //ha_error_log( print_R( ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key, 'is_option_post' => true ) ), TRUE ) );
            // ha_error_log( print_R( HA_SKOP_OPT() -> ha_get_unsanitized_customized_values( $skope_id ) ), true );
            //ha_error_log( '//////////////// END ORIGINAL CHANGESET DATA////////////////// ');


            // The request was made via wp.customize.previewer.save().
            $update_transactionally = (bool) $args['status'];
            $allow_revision = (bool) $args['status'];
            //Default response
            $response = array(
              'setting_validities'  => array()
            );


            // The customized data are already structured like in the changeset.
            // With setting_id => array( 'value' => '...' )
            if ( isset( $args['data'] ) && is_array( $args['data'] ) ) {
                $customized_data = $args['data'];
            } else {
                $customized_data = HA_SKOP_OPT() -> ha_get_unsanitized_customized_values( $skope_id );
            }

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

            // )
            //
            // Should become
            // array(
            //  [blogdescription] => Site tagline
            //  [blogname] => Site name
            // )
            $customized_values = array();
            foreach ( $customized_data as $raw_setting_id => $setting_data ) {
                if ( ! is_array( $setting_data ) || ! array_key_exists( 'value', $setting_data ) ) {
                  ha_error_log( 'Problem in _publish_skope_changeset_metas, the setting_data of the customized_data sent are not well formed for skope : ' . $skope_id );
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
                $customized_values[$setting_id] = $setting_data['value'];
            }

            ha_error_log( '//////////////// START CUSTOMIZED DATA ////////////////// ');
            ha_error_log( $skope_id );
            ha_error_log( print_R( $customized_values , true ) );
            ha_error_log( '//////////////// END CUSTOMIZED DATA////////////////// ');

            // /*
            //  * Get list of IDs for settings that have values different from what is currently
            //  * saved in the changeset. By skipping any values that are already the same, the
            //  * subset of changed settings can be passed into validate_setting_values to prevent
            //  * an underprivileged modifying a single setting for which they have the capability
            //  * from being blocked from saving. This also prevents a user from touching of the
            //  * previous saved settings and overriding the associated user_id if they made no change.
            //  */
            // $changed_setting_ids = array();
            // foreach ( $customized_data as $setting_id => $setting_value ) {
            //     if ( ! isset( $setting_value['value'] ) )
            //       continue;

            //     $setting = $wp_customize->get_setting( $setting_id );

            //     if ( $setting && 'theme_mod' === $setting->type ) {
            //         $prefixed_setting_id = $wp_customize->get_stylesheet() . '::' . $setting->id;
            //     } else {
            //         $prefixed_setting_id = $setting_id;
            //     }

            //     $is_value_changed = (
            //         ! isset( $_already_published_data[ $prefixed_setting_id ] )
            //         ||
            //         ! array_key_exists( 'value', $_already_published_data[ $prefixed_setting_id ] )
            //         ||
            //       $_already_published_data[ $prefixed_setting_id ]['value'] !== $setting_value['value']
            //     );
            //     if ( $is_value_changed ) {
            //         $changed_setting_ids[] = $setting_id;
            //     }
            // }//foreach()
            // $customized_data = wp_array_slice_assoc( $customized_data, $changed_setting_ids );


            //AT THIS STAGE, ONLY THE CUSTOMIZED VALUES WITH A DIFFERENT VALUE OF THE ONE CURRENTLY SAVED ARE KEPT In $customized_data
            do_action( 'customize_save_validation_before', $wp_customize );

            ///////////////////////////////////
            /// VALIDATE AND SANITIZE
            $setting_validities = array();

            //setting validation has been implemented in WP 4.6
            //=> check if the feature exists in the user WP version
            if ( method_exists( $wp_customize, 'validate_setting_values' ) ) {
                // Validate settings.
                $setting_validities = $wp_customize -> validate_setting_values( $customized_values, array(
                    'validate_capability' => true,
                    'validate_existence' => true
                ) );

            } else { // WP version < 4.6
                $setting_validities = $this -> _ha_validate_setting_values( $customized_values, array(
                    'validate_capability' => true,
                    'validate_existence' => true
                ) );
            }

            $invalid_setting_count = count( array_filter( $setting_validities, 'is_wp_error' ) );


            /*
             * Short-circuit if there are invalid settings the update is transactional.
             * A changeset update is transactional when a status is supplied in the request.
             */
            if ( $update_transactionally && $invalid_setting_count > 0 ) {
                $response = array(
                  'setting_validities' => $setting_validities,
                  'message' => sprintf( _n( 'There is %s invalid setting.', 'There are %s invalid settings.', $invalid_setting_count, 'hueman-addons' ), number_format_i18n( $invalid_setting_count ) ),
                );
                return new WP_Error( 'transaction_fail', '', $response );
            }

            $response = array(
              'setting_validities'  => $setting_validities,
              'skope_meta_key'      => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name'),
              'skope_id'            => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' )
            );

            // Obtain/merge data for changeset.
            $data = $normalized_published_data;
            if ( is_wp_error( $data ) ) {
              $data = array();
            }
            // ha_error_log( '//////////////// START EXISTING DATA ////////////////// ');
            // ha_error_log( print_R( $data , true ) );
            // ha_error_log( '//////////////// END EXISTING DATA////////////////// ');
            ///////////////////////////////////
            /// Ensure that all customized values are included in the changeset data.
            foreach ( $customized_values as $setting_id => $cust_value ) {
              if ( ! isset( $data[ $setting_id ] ) ) {
                $data[ $setting_id ] = array();
              }
              $data[ $setting_id ]['value'] = $cust_value;
            }//foreach()


            ///////////////////////////////////
            /// BUILD DATA TO BE SAVED
            foreach ( $data as $setting_id => $setting_params ) {
                if ( 'skope_infos' == $setting_id )
                  continue;
                $setting = $wp_customize->get_setting( $setting_id );
                if ( ! $setting || ! $setting->check_capabilities() ) {
                    ha_error_log( 'In _publish_skope_changeset_metas, ' . $setting_id . ' is not registered in $wp_customize.' );
                    continue;
                }

                // Skip updating changeset for invalid setting values.
                if ( isset( $setting_validities[ $setting_id ] ) && is_wp_error( $setting_validities[ $setting_id ] ) ) {
                    continue;
                }

                // $changeset_setting_id = $setting_id;

                // if ( null === $setting_params ) {
                //     // Remove setting from changeset entirely.
                //     unset( $data[ $changeset_setting_id ] );
                // } else {
                //     // Merge any additional setting params that have been supplied with the existing params.
                //     if ( ! isset( $data[ $changeset_setting_id ] ) ) {
                //       $data[ $changeset_setting_id ] = array();
                //     }
                //     $data[ $changeset_setting_id ] = array_merge(
                //         $data[ $changeset_setting_id ],
                //         $setting_params,
                //         array(
                //           'type' => $setting->type,
                //           'user_id' => $args['user_id']
                //         )
                //     );
                // }
            }//foreach()

            // ha_error_log('////////////////// DATA BEFORE FILTER FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $data, true) );

            //////////////////////////////////////
            /// PREPARE DATA FOR FRONT END :
            /// 1) Keep only the value
            /// 2) Handle multidim theme_mod type
            $data = ha_prepare_skope_changeset_for_front_end( $data );
            if ( is_wp_error( $data ) || ! is_array( $data ) ) {
              $response['publish_skope_changeset_failure'] = 'skope data not valid';
              return new WP_Error( 'publish_skope_changeset_failure', '', $response );
            }

            //////////////////////////////////////
            /// ADD / UPDATE SKOPE INFOS IF NEEDED
            $data['skope_infos'] = array(
                'skope_id'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id'),
                'level_id'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'level_id'),
                'skope'     => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope'),
                'obj_id'    => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'obj_id'),
                'meta_key'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name')
            );

            // ha_error_log('////////////////// INFOS : ' . $skope_post_id );
            // ha_error_log( $skope_meta_key );

            // ha_error_log('////////////////// DATA AFTER FILTER AND BEFORE BEING SAVED FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $data, true) );

            //////////////////////////////////////
            /// PUBLISH
            $r = update_post_meta( $skope_post_id, $skope_meta_key, $data );

            if ( is_wp_error( $r ) ) {
              $response['changeset_post_save_failure'] = $r->get_error_code();
              return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
            }

            return $response;
        }//_publish_skope_changeset_metas



        // hook : 'wp_ajax_czr_clean_skope_changeset_metas_after_publish'
        // This is fired when the skope metas have been published => a save action with changesetStatus = 'publish'
        // The goal is to remove the temporary post_metas used for skope and attached to the $wp_customize->changeset_post_id();
        //
        // @see czr fmk => api.czr_skopeSave.cleanSkopeChangesetMetas
        function ha_ajax_clean_skope_changeset_metas_after_publish() {
            global $wp_customize;
            if ( ! is_user_logged_in() ) {
                wp_send_json_error( 'unauthenticated' );
            }
            if ( ! current_user_can( 'edit_theme_options' ) ) {
              wp_send_json_error('user_cant_edit_theme_options');
            }
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
                status_header( 405 );
                wp_send_json_error( 'bad_method' );
            }
            $action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            // ha_error_log('////////////////WHAT IS THE CURRENT CHANGESET POST ?');
            // ha_error_log( print_R( $_POST, true ) );
            // ha_error_log( $wp_customize->changeset_post_id() );
            // @4.9compat
            // the changeset_post_id might be the one of the autosave, which is not $wp_customize->changeset_post_id();
            $changeset_post_id = ha_get_real_wp_changeset_post_id();

            if ( ! $changeset_post_id )
              wp_send_json_error( 'no_changeset_post_id_in_ha_ajax_clean_skope_changeset_metas_after_publish' );

            $all_skope_changeset_metas = get_post_meta( $changeset_post_id );
            $all_skope_changeset_metas = is_array( $all_skope_changeset_metas ) ? $all_skope_changeset_metas : array();

            // ha_error_log( print_R( $all_skope_changeset_metas, true ) );

            foreach ( $all_skope_changeset_metas as $meta_key => $val ) {
                $r = delete_post_meta( $changeset_post_id, $meta_key );
                if ( is_wp_error( $r ) ) {
                    wp_send_json_error( $r->get_error_message() );
                    break;
                }
            }
            wp_send_json_success();
        }
    }//class
endif;

?>