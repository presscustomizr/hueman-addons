<?php

if ( ! class_exists( 'HA_Skop_Chset_Reset' ) ) :
    final class HA_Skop_Chset_Reset extends HA_Skop_Chset_Save {

        function __construct() {
            parent::__construct();
            //DEPRECATED
            //add_action( 'wp_ajax_' . HU_OPT_AJAX_ACTION , array( $this, 'ha_ajax_get_opt' ) );

            //add_action( 'wp_ajax_czr_skope_reset', array( $this, 'ha_ajax_reset_skope' ) );
            //Changeset
            add_action( 'wp_ajax_czr_changeset_setting_reset',  array( $this, 'ha_ajax_reset_changeset_setting' ) );
            add_action( 'wp_ajax_czr_changeset_skope_reset',    array( $this, 'ha_ajax_reset_changeset_skope' ) );

            //Published
            add_action( 'wp_ajax_czr_published_setting_reset',  array( $this, 'ha_ajax_reset_published_setting' ) );
            add_action( 'wp_ajax_czr_published_skope_reset',    array( $this, 'ha_ajax_reset_published_skope' ) );
        }



        /* ------------------------------------------------------------------------- *
         *  GET OPTION
        /* ------------------------------------------------------------------------- */
        /**
         * Ajax handler for getting an attachment.
         *
         * @since 3.5.0
         */
        function ha_ajax_get_opt() {
          if ( ! isset( $_REQUEST['opt_name'] ) || ! isset( $_REQUEST['dyn_type'] ) || ! isset( $_REQUEST['stylesheet'] ) )
            wp_send_json_error();
          if ( ! current_user_can( 'edit_theme_options' ) )
            wp_send_json_error();

          $_trans = get_transient( $_REQUEST['opt_name'] );
          wp_send_json_success( $_trans );
        }





        function ha_get_setting_dependants( $setting_id ) {
          $dependencies = array(
              'header_image' => 'header_image_data',
              'header_image_data' => 'header_image'
          );

          return array_key_exists( $setting_id, $dependencies ) ? $dependencies[$setting_id] : false;
        }







        /* ------------------------------------------------------------------------- *
         *  RESET SETTING CHANGESET
        /* ------------------------------------------------------------------------- */
        //hook : wp_ajax_czr_changeset_setting_reset
        function ha_ajax_reset_changeset_setting() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            // We are customizing
            // @4.9compat
            // the changeset_post_id might be the one of the autosave, which is not $wp_customize->changeset_post_id();
            $changeset_post_id = ha_get_real_wp_changeset_post_id();

            if ( ! $changeset_post_id ) {
              wp_send_json_success( 'No changet post id' );
              return;
            }

            // Do we have to switch themes?
            if ( ! $wp_customize -> is_theme_active() ) {
                // Temporarily stop previewing the theme to allow switch_themes()
                // to operate properly.
                $wp_customize -> stop_previewing_theme();
                switch_theme( $wp_customize -> get_stylesheet() );
                update_option( 'theme_switched_via_customizer', true );
                $wp_customize -> start_previewing_theme();
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset an option, the ajax post request is missing a param.');

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );
            $setting_id       = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'setting_id' );
            $dependant_setting_id = $this -> ha_get_setting_dependants( $setting_id );
            $changeset_values = $this -> get_unsanitized_skope_changeset( $skope_id );
            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $attempt          = new WP_Error('setting_changeset_reset_not_processed');

            foreach ( $changeset_values as $_id => $setting_params ) {
                if ( $setting_id != $_id && $dependant_setting_id != $_id )
                  $new_values[$_id] = $setting_params;
            }

            if ( 'global' == $skope_level ) {
                // ha_error_log( '////////////// CHANGESET VALUES BEFORE' );
                // ha_error_log( print_R( $changeset_values, true ) );
                $json_options = 0;
                if ( defined( 'JSON_UNESCAPED_SLASHES' ) ) {
                  $json_options |= JSON_UNESCAPED_SLASHES; // Introduced in PHP 5.4. This is only to improve readability as slashes needn't be escaped in storage.
                }
                $json_options |= JSON_PRETTY_PRINT; // Also introduced in PHP 5.4, but WP defines constant for back compat. See WP Trac #30139.
                $post_array = array(
                  'post_content' => wp_json_encode( $new_values, $json_options ),
                  'ID'           => $changeset_post_id
                );
                $attempt = wp_update_post( wp_slash( $post_array ), true );
            } else {
                if ( empty($new_values) ) {
                    $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
                } else {
                    $attempt = update_post_meta( $changeset_post_id, $skope_meta_key, $new_values );
                }
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| ' . $setting_id . ' has been deleted from changeset in skope ' . $skope_id . '|||' );
        }










        /* ------------------------------------------------------------------------- *
         *  RESET SKOPE CHANGESET
        /* ------------------------------------------------------------------------- */
        //hook : wp_ajax_czr_changeset_skope_reset
        function ha_ajax_reset_changeset_skope() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            // We are customizing
            // @4.9compat
            // the changeset_post_id might be the one of the autosave, which is not $wp_customize->changeset_post_id();
            $changeset_post_id = ha_get_real_wp_changeset_post_id();

            if ( ! $changeset_post_id ) {
              wp_send_json_success( 'No changet post id' );
              return;
            }

            // Do we have to switch themes?
            if ( ! $wp_customize -> is_theme_active() ) {
                // Temporarily stop previewing the theme to allow switch_themes()
                // to operate properly.
                $wp_customize -> stop_previewing_theme();
                switch_theme( $wp_customize -> get_stylesheet() );
                update_option( 'theme_switched_via_customizer', true );
                $wp_customize -> start_previewing_theme();
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset a skope changeset, the ajax post request is missing a param.');

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );
            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $attempt          = new WP_Error('skope_changeset_reset_not_processed');

            if ( 'global' == $skope_level ) {
                $json_options = 0;
                if ( defined( 'JSON_UNESCAPED_SLASHES' ) ) {
                  $json_options |= JSON_UNESCAPED_SLASHES; // Introduced in PHP 5.4. This is only to improve readability as slashes needn't be escaped in storage.
                }
                $json_options |= JSON_PRETTY_PRINT; // Also introduced in PHP 5.4, but WP defines constant for back compat. See WP Trac #30139.
                $post_array = array(
                  'post_content' => wp_json_encode( $new_values, $json_options ),
                  'ID'           => $changeset_post_id
                );
                $attempt = wp_update_post( wp_slash( $post_array ), true );
            } else {
                $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| The changeset has been deleted for skope ' . $skope_id . '|||' );
        }











        /* ------------------------------------------------------------------------- *
         *  RESET SETTING PUBLISHED
        /* ------------------------------------------------------------------------- */
        //hook : wp_ajax_czr_changeset_setting_reset
        function ha_ajax_reset_published_setting() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset an option, the ajax post request is missing a param.');

            $setting_id       = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'setting_id' );

            //Attempt to extract the theme option prefix
            $short_opt_name             = _ha_extract_setting_name($setting_id);
            $dependant_setting_id       = $this -> ha_get_setting_dependants( $short_opt_name );

            //Bail if the option is protected
            if ( ha_is_option_protected( $short_opt_name ) )
              return wp_send_json_error('This option is protected');

            $theme_setting_list = HU_utils::$_theme_setting_list;
            $is_theme_authorized_option = in_array( $short_opt_name, $theme_setting_list );

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );

            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $published_values = array();

            if ( 'global' == $skope_level ) {
                //Bail if the option is not a theme setting
                if ( ! $is_theme_authorized_option )
                  return wp_send_json_error('This WordPress option can not be removed at a site wide level');//@to_translate
                $setting_id = $short_opt_name;
                $published_values = hu_get_raw_option( null, HU_THEME_OPTIONS );
            } else {
                //We are resetting the published values
                $changeset_post_id  = get_option('skope-post-id');
                if ( false === $changeset_post_id || empty( $changeset_post_id ) ) {
                    wp_send_json_error( 'missing skope_post_id when attempting to reset the skope meta value' );
                }
                $published_values = ha_get_skope_db_data(
                  array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    'level' => $skope_level,
                    'is_option_post'  => true
                  )
                );
            }

            $attempt          = new WP_Error('setting_changeset_reset_not_processed');

            // ha_error_log( '////////////// $setting_id' );
            // ha_error_log( $setting_id );

            // ha_error_log( '////////////// PUBLISHED VALUES BEFORE' );
            // ha_error_log( print_R( $published_values, true ) );

            foreach ( $published_values as $_id => $setting_params ) {

                if ( ! _ha_is_wp_setting_multidimensional( $_id) ) {
                    if ( $setting_id != $_id && $dependant_setting_id != $_id )
                      $new_values[$_id] = $setting_params;
                } else {
                    $published_multidim_settings = _ha_build_multidimensional_db_option( $_id, $setting_params );
                    if ( is_array( $published_multidim_settings ) ) {
                        $new_values[$_id] = array();
                        foreach ( $published_multidim_settings as $mult_set_id => $mult_val ) {
                            if ( $setting_id != $mult_set_id ) {
                                //extract multidim key
                                $multi_dim_key = str_replace(array('[', ']', $_id ), '', $mult_set_id);
                                $new_values[$_id][$multi_dim_key] = $mult_val;
                            }
                        }
                    }
                    // ha_error_log( '////////////// MULTIDIM' );
                    // ha_error_log( print_R( $published_multidim_settings, true ) );
                    // ha_error_log( '////////////// NEW VALUES' );
                    // ha_error_log( print_R( $new_values, true ) );
                }
            }

            if ( 'global' == $skope_level ) {
                // ha_error_log( '////////////// PUBLISHED VALUES BEFORE' );
                // ha_error_log( print_R( $published_values, true ) );
                $attempt = update_option( HU_THEME_OPTIONS, $new_values );
            } else {
                if ( empty( $new_values ) || ( 1 == count( $new_values ) && array_key_exists( 'skope_infos', $new_values ) ) ) {
                    $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
                } else {
                    $attempt = update_post_meta( $changeset_post_id, $skope_meta_key, $new_values );
                }
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| ' . $setting_id . ' has been deleted from changeset in skope ' . $skope_id . '|||' );
        }







        /* ------------------------------------------------------------------------- *
         *  RESET SKOPE PUBLISHED
        /* ------------------------------------------------------------------------- */
        function ha_ajax_reset_published_skope() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset a skope changeset, the ajax post request is missing a param.');

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );
            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $attempt          = new WP_Error('skope_changeset_reset_not_processed');

            if ( 'global' == $skope_level ) {
                $published_values = hu_get_raw_option( null, HU_THEME_OPTIONS );
                $theme_setting_list = HU_utils::$_theme_setting_list;

                foreach ( $published_values as $_id => $setting_params ) {
                    //Unauthorized theme options are never reset
                    if ( ! in_array( $_id, $theme_setting_list ) ) {
                      $new_values[$_id] = $setting_params;
                    }
                    //Protected options are never reset
                    if ( ha_is_option_protected( $_id ) ) {
                      $new_values[$_id] = $setting_params;
                    }
                }
                // ha_error_log( '////////////// PUBLISHED VALUES BEFORE' );
                // ha_error_log( print_R( $published_values, true ) );
                // ha_error_log( '////////////// NEW VALUES' );
                // ha_error_log( print_R( $new_values, true ) );
                $attempt = update_option( HU_THEME_OPTIONS, $new_values );
            } else {
                //We are resetting the published values
                $changeset_post_id  = get_option('skope-post-id');
                if ( false === $changeset_post_id || empty( $changeset_post_id ) ) {
                    wp_send_json_error( 'missing skope_post_id when attempting to reset the skope meta value' );
                }
                //ha_error_log( '////////////// RESETTING PUBLISHED VALUES FOR SKOPE : ' . $skope_id );
                $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| The published values have been deleted for skope ' . $skope_id . '|||' );
        }








        /* ------------------------------------------------------------------------- *
        * HELPERS
        /* ------------------------------------------------------------------------- */
        function get_unsanitized_skope_changeset( $skope_id ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();

            global $wp_customize;
            $values = array();

            //GET THE RAW CHANGESET FOR THE PROVIDED SKOPE_ID
            $skope_meta_key    = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            if ( false == $skope_meta_key ) {
              ha_error_log( 'no meta key found in get_unsanitized_skope_changeset() for skope : ' . $skope_id );
              return array();
            }
            // We are customizing
            // @4.9compat
            // the changeset_post_id might be the one of the autosave, which is not $wp_customize->changeset_post_id();
            $changeset_post_id = ha_get_real_wp_changeset_post_id();
            $changeset_data = ha_get_skope_db_data(
                array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    //the level must be specify when we're getting the 'global' level
                    'level' => HA_SKOP_OPT() -> ha_get_skope_level( $skope_id )
                )
            );

            if ( is_wp_error( $changeset_data ) ) {
                ha_error_log( 'Error when trying to get the changeset data in get_unsanitized_skope_changeset() for skope : ' . $skope_id );
                $changeset_data = array();
            }

            // Note that blogname can be saved in the changeset with not value, needed to refresh the global skope on init @see requestChangesetUpdate js
            foreach ( $changeset_data as $raw_setting_id => $setting_data ) {
                if ( ! is_array( $setting_data ) || ( 'blogname' != $raw_setting_id && ! array_key_exists( 'value', $setting_data ) ) ) {
                  ha_error_log( 'Problem in get_unsanitized_skope_changeset, the setting_data of the changeset are not well formed for skope : ' . $skope_id );
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
                $values[$setting_id] = $setting_data;
            }

            // ha_error_log( '/////////////// CHANGESET BEFORE ');
            // ha_error_log( print_R( $changeset_data, true ) );
            // ha_error_log( '/////////////// CHANGESET AFTER ');
            // ha_error_log( print_R( ha_prepare_skope_changeset_for_front_end( $changeset_data ), true ) );
            return $values;
        }


        /* ------------------------------------------------------------------------- *
         *  RESET SKOPE : _DEPRECATED
        /* ------------------------------------------------------------------------- */
        // function ha_ajax_reset_skope() {
        //     global $wp_customize;
        //     if ( ! $wp_customize->is_preview() ) {
        //         wp_send_json_error( 'not_preview' );
        //     } else if ( ! current_user_can( 'customize' ) ) {
        //         status_header( 403 );
        //         wp_send_json_error( 'customize_not_allowed' );
        //     } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        //       status_header( 405 );
        //       wp_send_json_error( 'bad_method' );
        //     }
        //     $action = 'save-customize_' . $wp_customize->get_stylesheet();
        //     if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
        //         wp_send_json_error( 'invalid_nonce' );
        //     }
        //     // Do we have to switch themes?
        //     if ( ! $wp_customize->is_theme_active() ) {
        //         // Temporarily stop previewing the theme to allow switch_themes()
        //         // to operate properly.
        //         $wp_customize->stop_previewing_theme();
        //         switch_theme( $wp_customize->get_stylesheet() );
        //         update_option( 'theme_switched_via_customizer', true );
        //         $wp_customize->start_previewing_theme();
        //     }

        //     if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['dyn_type'] ) )
        //       return wp_send_json_error();

        //     //$attempt will store the maybe wp error status
        //     $attempt = '';
        //     switch ( $_POST['dyn_type'] ) {
        //         case 'trans':
        //             $attempt = delete_transient( $_POST['opt_name'] );
        //           break;
        //         case 'post_meta':
        //             if ( ! isset( $_POST['obj_id'] ) ) {
        //               wp_send_json_error( 'Missing $_POST["obj_id"] when attempting to delete a post meta');
        //             }
        //             $attempt = delete_post_meta( $_POST['obj_id'] , $_POST['opt_name'] );
        //           break;
        //         case 'term_meta':
        //             if ( ! isset( $_POST['obj_id'] ) ) {
        //               wp_send_json_error( 'Missing $_POST["obj_id"] when attempting to delete a term meta');
        //             }
        //             $attempt = delete_term_meta( $_POST['obj_id'] , $_POST['opt_name'] );
        //           break;
        //         case 'user_meta':
        //             if ( ! isset( $_POST['obj_id'] ) ) {
        //               wp_send_json_error( 'Missing $_POST["obj_id"] when attempting to delete a user meta');
        //             }
        //             $attempt = delete_user_meta( $_POST['obj_id'] , $_POST['opt_name'] );
        //           break;
        //         case 'option' :
        //             $options = get_option( $_POST['opt_name'] );
        //             $_opts_to_keep = array();
        //             foreach ( $options as $key => $value ) {
        //                 if ( ha_is_option_protected( $key ) )
        //                   $_opts_to_keep[$key] = $value;
        //             }
        //             //wp_send_json_success( 'PROTECTED OPTIONS : ' . json_encode( ha_get_protected_options() ) );
        //             $attempt = update_option( $_POST['opt_name'], $_opts_to_keep );
        //           break;
        //     }
        //     if ( is_wp_error( $attempt ) ) {
        //       status_header( 500 );
        //       wp_send_json_error( $attempt->get_error_message() );
        //     }
        //     wp_send_json_success( $_POST['opt_name'] . ' has been reset.');
        // }

    }//class
endif;

?>