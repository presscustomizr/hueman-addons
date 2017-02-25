<?php

if ( ! class_exists( 'HA_Skop_Cust_Register' ) ) :
    final class HA_Skop_Cust_Register {
        function __construct() {
            /* ------------------------------------------------------------------------- *
             *  Modify some WP built-in settings
            /* ------------------------------------------------------------------------- */
            //modify some WP built-in settings
            //Assign an extended class to Header_Image Settings
            add_action( 'customize_register' , array( $this, 'ha_alter_wp_customizer_settings' ) , 100, 1 );

            /* ------------------------------------------------------------------------- *
             *  CUSTOMIZE PANE : Add skope server params to the Hueman theme control server params
            /* ------------------------------------------------------------------------- */
            add_filter( 'hu_js_customizer_control_params', array( $this, 'ha_add_skope_control_params' ) );
            //'controls_translated_strings' is declared in hueman theme, czr-resources
            //add_filter( 'controls_translated_strings', array( $this, 'hu_add_skp_translated_strings') );
            /* ------------------------------------------------------------------------- *
             *  Skopify the save DEPRECATED
             *  1) Dynamically set the type in WP_Customize_Setting::save()
             *  2) Then add skope save actions by type on WP_Customize_Setting::update()
            /* ------------------------------------------------------------------------- */
            //add_action( 'customize_register' , 'ha_prepare_skopify_customizer_save');
        }



        /* ------------------------------------------------------------------------- *
         *  Modify some WP built-in settings
        /* ------------------------------------------------------------------------- */
        //hook : customize_register
        function ha_alter_wp_customizer_settings( $manager ) {
          if ( is_object( $manager->get_setting( 'header_image_data' ) ) ) {
              $manager -> remove_setting( 'header_image_data' );
              $manager -> add_setting( new HA_Customize_Header_Image_Data_Setting( $manager, 'header_image_data', array(
                'theme_supports' => 'custom-header',
              ) ) );
              $manager -> remove_setting( 'header_image' );
              $manager -> add_setting( new HA_Customize_Header_Image_Setting( $manager, 'header_image', array(
                'default'        => get_theme_support( 'custom-header', 'default-image' ),
                'theme_supports' => 'custom-header',
              ) ) );
          }

          if ( is_object( $manager->get_control( 'hu_theme_options[layout-global]' ) ) ) {
              $manager->get_control( 'hu_theme_options[layout-global]' ) -> label = __( "Column Layout for main content and sidebars", 'hueman-addons' );
              $manager->get_control( 'hu_theme_options[layout-global]' ) -> notice = __( 'Pick a content layout in the dropdown list. Note : selecting a "1 column" layout won\'t let you display any sidebar widgets.', 'hueman-addons' );
          }
        }


        /* ------------------------------------------------------------------------- *
         *  CUSTOMIZE PANEL : ADD LOCALIZED PARAMS
        /* ------------------------------------------------------------------------- */
        //filter 'hu_js_customizer_control_params' is declared in hueman/functions/czr/czr-resources.php
        function ha_add_skope_control_params( $_params ) {
            return array_merge(
              $_params,
              array(
                  'isSkopOn'              =>  HU_AD() -> ha_is_skop_on(),
                  'defaultSkopeModel'     => _ha_get_default_scope_model(),
                  'skopeDynTypes'         => ha_get_dyn_types(),
                  'defaultOptionsValues'  => HU_utils::$inst -> hu_get_default_options(),
                  'skopeExcludedSettings' => ha_get_skope_excluded_options(),
                  'globalSkopeOptName'    => HA_SKOP_OPT() -> global_skope_optname,
                  'isSidebarsWigetsSkoped' => (bool)apply_filters( 'ha_skope_sidebars_widgets', false ),
                  'isWPCustomCssSkoped'    => (bool)apply_filters( 'ha_skope_wp_custom_css', false ),
                  'isNavMenuLocationsSkoped'  => (bool)apply_filters( 'ha_skope_navmenu', true ),
                  'isChangeSetOn'         => HU_AD() -> ha_is_changeset_enabled(),
                  'isLocalSkope'          => isset( $_GET['url'] ),
                  'isTopNoteOn'           => apply_filters( 'ha_czr_top_note_status', 'dismissed' != get_option( 'ha_czr_top_note_status' ) ||  ( defined('TC_DEV') && true === TC_DEV ) ),
                  'topNoteParams'         => array(
                      'title'   => __( 'Welcome in the new customizer interface !', 'hueman-addons' ),
                      'message' => sprintf ( __( 'Discover a new way to customize your pages on %1$s.', 'hueman-addons' ),
                            sprintf('<a href="%1$s" title="%2$s" target="_blank">%3$s <span class="fa fa-external-link"></span></a>',
                                esc_url('docs.presscustomizr.com/search?query=customize-hueman'),
                                __('Visit the documentation', 'hueman-addons'),
                                __('this page')
                            )
                      )
                  )
                )
              );
        }


        /* ------------------------------------------------------------------------- *
         *  CUSTOMIZE PANEL : ADD TRANSLATED STRINGS
        /* ------------------------------------------------------------------------- */
        function hu_add_skp_translated_strings( $strings ) {
              return array_merge( $strings, array(

              ));
        }//hu_add_skp_translated_strings


        //hook : customize_register
        // function ha_prepare_skopify_customizer_save() {
        //   //Which options are we targeting there?
        //   // 1) the theme options
        //   // 2) the WP built in options
        //   $_options_to_skope = HU_customize::$instance -> hu_get_wp_builtin_settings();
        //   $_options_to_skope[] = HU_THEME_OPTIONS;

        //   if ( apply_filters( 'ha_skope_sidebars_widgets', false ) ) {
        //       $_options_to_skope[] = 'sidebars_widgets';
        //       $_options_to_skope  = array_merge( $_options_to_skope, hu_get_registered_widgets_option_names() );
        //   }

        //   //loop on the targeted option to dynamically set the type on save
        //   foreach ( $_options_to_skope as $_opt_name ) {
        //     add_action( "customize_save_{$_opt_name}"  , 'ha_set_setting_type' );
        //   }

        //   // add_action( 'customize_update_trans' , 'ha_customizer_set_trans', 10, 2 );
        //   // add_action( 'customize_update_post_meta' , 'ha_customizer_set_post_meta', 10, 2 );
        //   // add_action( 'customize_update_term_meta' , 'ha_customizer_set_term_meta', 10, 2 );
        //   // add_action( 'customize_update_user_meta' , 'ha_customizer_set_user_meta', 10, 2 );
        //   add_action( 'customize_update_skope_meta' , 'ha_customizer_save_skope_meta', 10, 2 );


        //   //CHANGESET
        //   //add_filter( 'customize_changeset_save_data', 'ha_customizer_set_changet_data', 10, 2 );
        //   //'wp_insert_post_data' is declared in wp-includes/post
        //   //add_filter( 'wp_insert_post_data', 'ha_customizer_set_changet_post_data', 100, 2 );

        //   //EXPERIMENT
        //   $theme_name = strtolower(THEMENAME);//is always the parent theme name
        //   //add_action( "customize_save_{$theme_name}_global_skope"  , 'ha_set_setting_type' );
        //   //add_action( 'customize_update_global_option' , 'ha_customizer_set_global_option', 10, 2 );
        // }


        /* ------------------------------------------------------------------------- *
         *  Set Changeset Data for skope
        /* ------------------------------------------------------------------------- */
        // function ha_customizer_set_changet_data( $data, $filter_context ) {
        //   return $data;
        // }

        // //hook : 'wp_insert_post_data'
        // function ha_customizer_set_changet_post_data( $data, $postarr ) {
        //   if ( $data['post_type'] != 'customize_changeset' )
        //     return $data;
        //   if ( isset( $_POST['skope']) && 'global' == $_POST['skope'] )
        //     return $data;

        //   if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
        //     return $data;
        //   $opt_name = $_POST['opt_name'];
        //   $skope_id = $_POST['skope_id'];
        //   $skope_customized = json_decode( wp_unslash($_POST['skopeCustomized'] ), true );

        //   if ( ! isset( $skope_customized[$skope_id] ) )
        //     return $data;

        //   $skope_settings = $skope_customized[$skope_id];
        //   $data['meta_input'] = ! isset( $data['meta_input'] ) ? array(): $data['meta_input'];
        //   $data['meta_input'][$opt_name] = 'JOIE';//$skope_settings;

        //   return $data;
        // }


        /* ------------------------------------------------------------------------- *
         *  Set the dynamic type sent by $_POST
        /* ------------------------------------------------------------------------- */
        //hook : customize_save_hu_theme_options
        //hook fired in WP_Customize_Setting
        //at this point, the nonce has already been checked by the customizer manager
        //if 'wp_default_type' is specified, then always falls back to wp type
        //=> 'wp_default_type' is typically used when saving a skope excluded setting. It should not be parsed by this action because it's option name based but this is a paranoid, irrational security.
        // function ha_set_setting_type( $setting ) {
        //     //don't fire when saving the global skope
        //     if ( 'global' == HA_SKOP_OPT() -> ha_get_current_customized_skope() )
        //       return;

        //     if ( ! $setting->check_capabilities() )
        //       return new WP_Error( 'user_not_allowed' );

        //     $skope_id     = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
        //     $global_value = $setting -> post_value();

        //     //$global_value is used as a fallback
        //     //HA_SKOP_OPT() -> ha_get_customized_value returns a validated and sanitized value
        //     $skope_value  = HA_SKOP_OPT() -> ha_get_customized_value( $setting->id, $global_value);//inheritance set to false by default

        //     if (  ! isset( $skope_value ) )
        //       return; //new WP_Error( 'no_value_for_setting_id' . $setting->id );

        //     if ( 'theme_mod' != $setting -> type )
        //       $setting -> type = ( ! isset($_POST['dyn_type']) || 'wp_default_type' == $_POST['dyn_type'] ) ? $setting -> type : $_POST['dyn_type'];
        //     else if ( isset($_POST['skope']) && 'global' == $_POST['skope'] ) {
        //       $setting -> type = 'theme_mod';
        //     }
        //     else {
        //       $setting -> type = ( ! isset($_POST['dyn_type']) || 'wp_default_type' == $_POST['dyn_type'] ) ? $setting -> type : $_POST['dyn_type'];
        //     }
        // }





        /* ------------------------------------------------------------------------- *
         *  Write the skope options in DB
        /* ------------------------------------------------------------------------- */
        //hook : customize_update_global_option
        //at this point, the nonce has already been checked by the customizer manager
        //This callback is fired in WP_Customize_Setting::update()
        //@param $value has been sanitized in WP_Customize_Setting::save() at this point, by WP_Customize_Manager::post_value()
        // function ha_customizer_set_global_option( $value, $setting ) {
        //     if ( ! $_POST['opt_name'] || $_POST['opt_name'] != HA_SKOP_OPT() -> global_skope_optname || ! $setting->check_capabilities() || ! isset( $value ) )
        //       return;
        //     $db_opt_val = hu_get_raw_option( HA_SKOP_OPT() -> global_skope_optname );
        //     $new_value = _ha_customizer_preprocess_save_value( $value, $setting, $db_opt_val );
        //     update_option( $_POST['opt_name'], $new_value );
        // }





        //hook : customize_update_skope_meta
        //at this point, the nonce has already been checked by the customizer manager
        //This callback is fired in WP_Customize_Setting::update()
        //@param $value has been sanitized in WP_Customize_Setting::save() at this point, by WP_Customize_Manager::post_value()
        //At this stage, the setting validity has been checked by the WP_Customize_Manager::save() method
        // function ha_customizer_save_skope_meta( $value, $setting ) {
        //     if ( ! $_POST['opt_name'] )
        //       return new WP_Error( 'missing_skope_meta_key' );

        //     if ( ! $setting->check_capabilities() )
        //       return new WP_Error( 'missing_skope_meta_key' );

        //     $skope_id     = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
        //     $global_value = $value;
        //     // HA_SKOP_OPT() -> ha_get_customized_value() returns a validated and sanitized customized value
        //     $skope_value  = HA_SKOP_OPT() -> ha_get_customized_value( $setting->id, $global_value ); //$global_value is used as a fallback

        //     if ( ! isset( $skope_value ) )
        //       return;

        //     $skope_meta_key = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name' );
        //     $skope_post_id  = get_option('skope-post-id');
        //     if ( false === $skope_post_id || empty($skope_post_id) )
        //       return new WP_Error( 'missing_skope_post_id' );

        //     global $wp_customize;
        //     $setting_validities = array();

        //     //setting validation has been implemented in WP 4.6
        //     //=> check if the feature exists in the user WP version
        //     if ( method_exists($wp_customize, 'validate_setting_values') ) {
        //         // Validate setting value.
        //         // This is normally done by the manager before the setting->save() call.
        //         // But for skope we need to do it now, for each setting id.
        //         $setting_validities = $wp_customize->validate_setting_values( array( $setting->id => $skope_value ), array(
        //           'validate_capability' => true,
        //           'validate_existence' => true,
        //         ) );
        //         $invalid_setting_count = count( array_filter( $setting_validities, 'is_wp_error' ) );

        //         if ( $invalid_setting_count > 0 ) {
        //           $response = array(
        //             'setting_validities' => $setting_validities,
        //             'message' => sprintf( _n( 'There is %s invalid setting.', 'There are %s invalid settings.', $invalid_setting_count ), number_format_i18n( $invalid_setting_count ) ),
        //           );
        //           return new WP_Error( 'transaction_fail', '', $response );
        //         }
        //     }

        //     $response = array(
        //       'setting_validities' => $setting_validities,
        //     );


        //     // Obtain/merge data for skope meta
        //     $existing_changeset_data = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key ) );
        //     $data = $existing_changeset_data;
        //     if ( is_wp_error( $data ) ) {
        //       $data = array();
        //     }

        //     $data[ $setting->id ] = $skope_value;

        //     $r = HA_SKOP_OPT() -> ha_set_skope_option_val( $setting->id, $skope_value, $skope_meta_key );

        //     if ( is_wp_error( $r ) ) {
        //       $response['skope_post_save_failure'] = $r->get_error_code();
        //       return new WP_Error( 'skope_post_save_failure', '', $response );
        //     }
        // }
    }//class
endif;

?>