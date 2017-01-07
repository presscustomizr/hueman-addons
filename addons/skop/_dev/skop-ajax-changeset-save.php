<?php
/* ------------------------------------------------------------------------- *
 *  SAVE SKOPE CHANGESET
 *  => as a meta of the _temp changeset post if status != "publish"
/* ------------------------------------------------------------------------- */
// WHAT ARE THE ARGS ? array(
//    'status' => null or != than publish
//    'data' => $input_changeset_data = $_POST['customize_changeset_data'] == unsaved dirties == api.czr_skopeBase.getSkopeDirties( skope_id )
// )
//
// // 'customize_changeset_data' IS SENT AS A JSON, WHEN DECODED, LOOKS LIKE :
// Array(

//             'nav_menu_locations[footer]' => Array
//                 (
//                     'value' => 11
//                 )

//             'hu_theme_options[site-description]' => Array
//                 (
//                     'value' =>
//                 )

//             'header_image' => Array
//                 (
//                     'value' => http://wp-betas.dev/wp-content/uploads/2016/10/cropped-28efabf02ea881d89514f659c29d27c0.jpg
//                 )
// )

// WHAT SHOULD THIS METHOD DO ?
// 1) Get the existing changeset
// 2) Filter only the modified customized values
// 3) Sanitize and Validate
// 4) make sure theme_mods type are well prefixed nav_menu_locations[topbar] should become hueman::nav_menu_locations[topbar]
if ( ! class_exists( 'HA_Skop_Chset_Save' ) ) :
    class HA_Skop_Chset_Save extends HA_Skop_Chset_Publish {

        function __construct() {
          parent::__construct();
        }

        function _save_skope_changeset_metas( $args = array() ) {
            // ha_error_log( '//////////////// START ARGS PARAM ////////////////// ');
            // ha_error_log( print_R( $args, TRUE ) );
            // ha_error_log( '//////////////// END ARGS PARAM ////////////////// ');
            global $wp_customize;

            if ( 'global' == HA_SKOP_OPT() -> ha_get_current_customized_skope() )
              wp_send_json_error( '_save_or_publish_skope_changeset_metas() : the global skope can not be saved this way' );

            if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              wp_send_json_error( '_save_or_publish_skope_changeset_metas() : Missing opt_name or skope_id or skopeCustomized' );

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

            // We are customizing
            $changeset_post_id = $wp_customize->changeset_post_id();
            $existing_changeset_data = array();

            if ( ! $changeset_post_id )
                wp_send_json_error( 'No changet post id yet' );

            //Default response
            $response = array(
              'setting_validities'  => array()
            );

            $existing_changeset_data = array();
            if ( $changeset_post_id ) {
                $existing_changeset_data = ha_get_skope_db_data( array( 'post_id' => $changeset_post_id, 'skope_meta_key' => $skope_meta_key, 'is_option_post' => false ) );
            }

            if ( is_wp_error( $existing_changeset_data ) ) {
              $response['changeset_post_save_failure'] = $existing_changeset_data->get_error_code();
              return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
            }

            // The request was made via wp.customize.previewer.save().
            $update_transactionally = (bool) $args['status'];
            $allow_revision = (bool) $args['status'];

            // Amend post values with any supplied data.
            // foreach ( $args['data'] as $setting_id => $setting_params ) {
            //     if ( array_key_exists( 'value', $setting_params ) ) {
            //         $wp_customize->set_post_value( $setting_id, $setting_params['value'] ); // Add to post values so that they can be validated and sanitized.
            //     }
            // }

            // The customized data are already structured like in the changeset.
            // With setting_id => array( 'value' => '...' )
            $customized_data = $args['data'];

            /*
             * Get list of IDs for settings that have values different from what is currently
             * saved in the changeset. By skipping any values that are already the same, the
             * subset of changed settings can be passed into validate_setting_values to prevent
             * an underprivileged modifying a single setting for which they have the capability
             * from being blocked from saving. This also prevents a user from touching of the
             * previous saved settings and overriding the associated user_id if they made no change.
             */
            $changed_setting_ids = array();
            foreach ( $customized_data as $setting_id => $setting_value ) {
                if ( ! isset( $setting_value['value'] ) )
                  continue;

                $setting = $wp_customize->get_setting( $setting_id );

                if ( $setting && 'theme_mod' === $setting->type ) {
                    $prefixed_setting_id = $wp_customize->get_stylesheet() . '::' . $setting->id;
                } else {
                    $prefixed_setting_id = $setting_id;
                }
                $is_value_changed = (
                    ! isset( $existing_changeset_data[ $prefixed_setting_id ] )
                    ||
                    ! array_key_exists( 'value', $existing_changeset_data[ $prefixed_setting_id ] )
                    ||
                  $existing_changeset_data[ $prefixed_setting_id ]['value'] !== $setting_value['value']
                );
                if ( $is_value_changed ) {
                    $changed_setting_ids[] = $setting_id;
                }
            }//foreach()

            $customized_data = wp_array_slice_assoc( $customized_data, $changed_setting_ids );

            //Bail here if unchanged values.
            //Will typically happen on load + skope switch
            if ( empty( $customized_data) )
              return wp_send_json_success( $response );

            //AT THIS STAGE, ONLY THE CUSTOMIZED VALUES WITH A DIFFERENT VALUE OF THE ONE CURRENTLY SAVED ARE KEPT In $customized_data

            /**
             * Fires before save validation happens.
             *
             * Plugins can add just-in-time {@see 'customize_validate_{$wp_customize->ID}'} filters
             * at this point to catch any settings registered after `customize_register`.
             * The dynamic portion of the hook name, `$wp_customize->ID` refers to the setting ID.
             *
             * @since 4.6.0
             *
             * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
             */
            do_action( 'customize_save_validation_before', $wp_customize );


            ///////////////////////////////////
            /// VALIDATE AND SANITIZE
            $setting_validities = array();
            //build a validation ready customized values ( => get rid of 'value' => value )
            $customized_values = array();
            foreach ($customized_data as $__id => $__v ) {
                if ( ! array_key_exists('value', $__v ) )
                  continue;
                $customized_values[$__id] = $__v['value'];
            }

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
                  'message' => sprintf( _n( 'There is %s invalid setting.', 'There are %s invalid settings.', $invalid_setting_count ), number_format_i18n( $invalid_setting_count ) ),
                );
                return new WP_Error( 'transaction_fail', '', $response );
            }

            $response = array(
              'setting_validities'  => $setting_validities,
              'skope_meta_key'      => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name'),
              'skope_id'            => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' )
            );

            // Obtain/merge data for changeset.
            $data = $existing_changeset_data;
            if ( is_wp_error( $data ) ) {
              $data = array();
            }



            ///////////////////////////////////
            /// Ensure that all customized values are included in the changeset data.
            // foreach ( $customized_data as $setting_id => $cust_value ) {
            //   if ( ! isset( $args['data'][ $setting_id ] ) ) {
            //     $args['data'][ $setting_id ] = array();
            //   }
            //   if ( ! isset( $args['data'][ $setting_id ]['value'] ) ) {
            //     $args['data'][ $setting_id ]['value'] = $cust_value['value'];
            //   }
            // }//foreach()



            ///////////////////////////////////
            /// BUILD DATA TO BE SAVED
            foreach ( $customized_data as $setting_id => $setting_params ) {
                $setting = $wp_customize->get_setting( $setting_id );
                if ( ! $setting || ! $setting->check_capabilities() ) {
                    ha_error_log( 'In _save_or_publish_skope_changeset_metas, ' . $setting_id . ' is not registered in $wp_customize.' );
                    continue;
                }

                // Skip updating changeset for invalid setting values.
                if ( isset( $setting_validities[ $setting_id ] ) && is_wp_error( $setting_validities[ $setting_id ] ) ) {
                    continue;
                }

                // Add the theme name as prefix for theme_mod type settings
                $changeset_setting_id = $setting_id;
                if ( 'theme_mod' === $setting->type ) {
                    $theme_name = $wp_customize->get_stylesheet();
                    if ( $theme_name != substr( $setting_id, 0, strlen($theme_name) ) )
                      $changeset_setting_id = sprintf( '%s::%s', $wp_customize->get_stylesheet(), $setting_id );
                }

                if ( null === $setting_params ) {
                    // Remove setting from changeset entirely.
                    unset( $data[ $changeset_setting_id ] );
                } else {
                    // Merge any additional setting params that have been supplied with the existing params.
                    if ( ! isset( $data[ $changeset_setting_id ] ) ) {
                      $data[ $changeset_setting_id ] = array();
                    }
                    $data[ $changeset_setting_id ] = array_merge(
                        $data[ $changeset_setting_id ],
                        $setting_params,
                        array(
                          'type' => $setting->type,
                          'user_id' => $args['user_id']
                        )
                    );
                }
            }//foreach()

            // ha_error_log('////////////////// DATA BEING SAVED FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $data, true) );

            //////////////////////////////////////
            /// SAVE
            $r = update_post_meta( $changeset_post_id, $skope_meta_key, $data );

            if ( is_wp_error( $r ) ) {
              $response['changeset_post_save_failure'] = $r->get_error_code();
              return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
            }

            return $response;
        }
    }//class
endif;

?>