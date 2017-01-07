
<?php

/**
 * This Class is instantiated on 'plugin_init', declared in /init-skop.php
 */
if ( ! class_exists( 'HA_Skop_Chset_Base' ) ) :
    class HA_Skop_Chset_Base {

        function __construct() {
            add_action( 'wp_ajax_customize_skope_changeset_save', array( $this, 'ha_ajax_skope_changeset_save' ) );
        }


        /* ------------------------------------------------------------------------- *
         *  SAVE OR PUBLISH SKOPE CHANGESET
         *  => as a meta of the _temp changeset post if status != "publish"
         *  => as a meta of the option changeset post if status == "publish" and skope is not 'global'
        /* ------------------------------------------------------------------------- */
        /**
          * Handle customize_skope_changet_save WP Ajax request to save/update a changeset.
         *
         */
        function ha_ajax_skope_changeset_save() {
            // Ensure retro compat with version < 4.7
            if ( isset( $_POST['skope']) && 'global' == $_POST['skope'] )
                return wp_send_json_success( 'Global skope changeset is not saved as meta' );

            if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
                return wp_send_json_error( 'Missing $_POST data to save the changeset skope meta.' );

            global $wp_customize;
            if ( ! is_user_logged_in() ) {
                wp_send_json_error( 'unauthenticated' );
            }

            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            }
            //let's use WP nonce
            $action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            // Validate changeset status param.
            $is_publish = null;
            $changeset_status = null;
            if ( isset( $_POST['customize_changeset_status'] ) ) {
                $changeset_status = wp_unslash( $_POST['customize_changeset_status'] );
                if ( ! get_post_status_object( $changeset_status ) || ! in_array( $changeset_status, array( 'draft', 'pending', 'publish', 'future' ), true ) ) {
                    wp_send_json_error( 'bad_customize_changeset_status', 400 );
                }
                $is_publish = ( 'publish' === $changeset_status || 'future' === $changeset_status );
                if ( $is_publish && HU_AD() -> ha_is_changeset_enabled() ) {
                    if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->publish_posts ) ) {
                        wp_send_json_error( 'changeset_publish_unauthorized', 403 );
                    }
                    if ( false === has_action( 'transition_post_status', '_wp_customize_publish_changeset' ) ) {
                        wp_send_json_error( 'missing_publish_callback', 500 );
                    }
                }
            }

            //Bail here for WP version < 4.7
            if ( ! $is_publish && ! HU_AD() -> ha_is_changeset_enabled() ) {
              wp_send_json_error( 'changeset_feature_is_not_enabled' );
            }

            $skope_post_id = null;
            // Are we customizing or publishing ?
            // => set the right changeset post id
            // => check the post status
            // Ensure retro compat with version < 4.7
            if ( ! is_null( $changeset_status ) && $is_publish ) {
                $skope_post_id  = get_option('skope-post-id');
                if ( false === $skope_post_id || empty( $skope_post_id ) ) {
                    wp_send_json_error( 'missing skope_post_id when attempting to publish the skope meta option' );
                }
            } else {
                $changeset_post_id = $wp_customize->changeset_post_id();
                if ( $changeset_post_id && in_array( get_post_status( $changeset_post_id ), array( 'publish', 'trash' ) ) ) {
                    wp_send_json_error( 'changeset_already_published' );
                }
            }

            // if ( empty( $changeset_post_id ) ) {
            //     if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->create_posts ) ) {
            //         wp_send_json_error( 'cannot_create_changeset_post' );
            //     }
            // } else {
            //     if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->edit_post, $changeset_post_id ) ) {
            //         wp_send_json_error( 'cannot_edit_changeset_post' );
            //     }
            // }
            // CUSTOMIZE CHANGESET DATA IS SENT AS A JSON, WHEN DECODED, LOOKS LIKE :
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
            //
            if ( ! empty( $_POST['customize_changeset_data'] ) ) {
                $input_changeset_data = json_decode( wp_unslash( $_POST['customize_changeset_data'] ), true );
                if ( ! is_array( $input_changeset_data ) ) {
                    wp_send_json_error( 'invalid_customize_changeset_data' );
                }
            } else {
                $input_changeset_data = array();
            }



            //ATTEMPT TO SAVE OR PUBLISH THE SKOPE CHANGESET META
            if ( 'publish' == $changeset_status ) {
                $r = $this -> _publish_skope_changeset_metas( array(
                    'status' => $changeset_status,
                    // 'title' => $changeset_title,
                    // 'date_gmt' => $changeset_date_gmt,
                    'data' => $input_changeset_data,
                ) );
            } else {
                $r = $this -> _save_skope_changeset_metas( array(
                    'status' => $changeset_status,
                    // 'title' => $changeset_title,
                    // 'date_gmt' => $changeset_date_gmt,
                    'data' => $input_changeset_data,
                ) );
            }

            if ( is_wp_error( $r ) ) {
              $response = $r->get_error_data();
            } else {
                $response = $r;
                if ( HU_AD() -> ha_is_changeset_enabled() ) {
                    if ( $is_publish ) {
                        $response['changeset_status'] = get_post_status( $skope_post_id );
                    } else {
                        // Note that if the changeset status was publish, then it will get set to trash if revisions are not supported.
                        $response['changeset_status'] = get_post_status( $wp_customize->changeset_post_id() );
                    }

                }
                // if ( $is_publish && 'trash' === $response['changeset_status'] ) {
                //   $response['changeset_status'] = 'publish';
                // }
            }

            if ( isset( $response['setting_validities'] ) ) {
                $response['setting_validities'] = array_map( array( $wp_customize, 'prepare_setting_validity_for_js' ), $response['setting_validities'] );
            }

            $response = apply_filters( 'customize_save_response', $response, $wp_customize );

            if ( is_wp_error( $r ) ) {
                wp_send_json_error( $response );
            } else {
                wp_send_json_success( $response );
            }
        }






        //COPY OF wp_customize_manager::validate_setting_values introduced in wp 4.6
        function _ha_validate_setting_values( $setting_values, $options = array() ) {
            global $wp_customize;
            $options = wp_parse_args( $options, array(
              'validate_capability' => false,
              'validate_existence' => false,
            ) );

            $validities = array();
            foreach ( $setting_values as $setting_id => $unsanitized_value ) {
              $setting = $wp_customize->get_setting( $setting_id );
              if ( ! $setting ) {
                if ( $options['validate_existence'] ) {
                  $validities[ $setting_id ] = new WP_Error( 'unrecognized', __( 'Setting does not exist or is unrecognized.' ) );
                }
                continue;
              }
              if ( $options['validate_capability'] && ! current_user_can( $setting->capability ) ) {
                $validity = new WP_Error( 'unauthorized', __( 'Unauthorized to modify setting due to capability.' ) );
              } else {
                if ( is_null( $unsanitized_value ) ) {
                  continue;
                }
                $validity = $setting->validate( $unsanitized_value );
              }
              if ( ! is_wp_error( $validity ) ) {
                /** This filter is documented in wp-includes/class-wp-customize-setting.php */
                $late_validity = apply_filters( "customize_validate_{$setting->id}", new WP_Error(), $unsanitized_value, $setting );
                if ( ! empty( $late_validity->errors ) ) {
                  $validity = $late_validity;
                }
              }
              if ( ! is_wp_error( $validity ) ) {
                $value = $setting->sanitize( $unsanitized_value );
                if ( is_null( $value ) ) {
                  $validity = false;
                } elseif ( is_wp_error( $value ) ) {
                  $validity = $value;
                }
              }
              if ( false === $validity ) {
                $validity = new WP_Error( 'invalid_value', __( 'Invalid value.' ) );
              }
              $validities[ $setting_id ] = $validity;
            }
            return $validities;
          }



        //this utility is used to preprocess the value for any type : trans, meta
        //@param value : array()
        //@param $setting : setting instance
        //@todo : improve performances by getting the aggregated multidimensional, just like WP does
        //@return updated option associative array( opt_name1 => value 1, opt_name2 => value2, ... )
        // function _ha_customizer_preprocess_save_value( $new_value, $setting, $current_value ) {
        //     //assign a default val to the set_name var
        //     $set_name = $setting -> id;
        //     $id_data = $setting -> manager -> get_setting( $setting -> id ) -> id_data();
        //     $is_multidimensional = ! empty( $id_data['keys'] );

        //     if ( $is_multidimensional && ! _ha_is_theme_multidimensional_setting( $setting -> id ) ) {
        //       $id_base = $id_data['base'];
        //       $set_name = $id_base;
        //       $root = ( is_array($current_value) && isset($current_value[$set_name]) ) ? $current_value[$set_name] : array();
        //       $root = _ha_multidimensional_replace( $root, $id_data['keys'], $new_value );
        //       $new_value = $root;
        //     }

        //     $new_value = apply_filters('_ha_customizer_preprocess_save_value', $new_value, $current_value, $setting );

        //     //hu_theme_options case
        //     if ( _ha_is_theme_multidimensional_setting( $setting -> id ) ) {
        //       $set_name = _ha_extract_setting_name( $setting -> id );
        //     }

        //     //bail if no set name set here
        //     if ( ! isset($set_name ) )
        //       return $current_value;

        //     if ( ! $current_value || ! is_array($current_value) ) {
        //       $to_return = array( $set_name => $new_value );
        //     } else {
        //       $to_return = $current_value;
        //       $to_return[$set_name] = $new_value;
        //     }
        //     return $to_return;
        // }
    }//class
endif;

?>