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

            // REGISTER A NEW SETTING IN ADMIN SETTINGS SEC
            add_filter( 'hu_admin_sec'   , array( $this, 'ha_register_skop_infos_settings'));

            /* ------------------------------------------------------------------------- *
             *  CUSTOMIZE PANE : Add skope server params to the Hueman theme control server params ( serverControlParams global var)
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

        //hook : hu_admin_sec
        function ha_register_skop_infos_settings( $settings ) {
            $settings = is_array( $settings ) ? $settings : array();
            return array_merge( $settings, array(
                'show-skope-infos' => array(
                    'default'   => 1,
                    'control'   => 'HU_controls',
                    'label'     => __('Display an informations block at the bottom of the preview', 'hueman-addons'),
                    'section'   => 'admin_sec',
                    'type'      => 'checkbox',
                    'notice'    => __('When this option is checked, a block of informations about the current customization scope is displayed at the bottom of the preview.', 'hueman-addons'),
                    'priority'  => 30,
                    'transport' => 'postMessage'
                )
            ));
        }


        /* ------------------------------------------------------------------------- *
         *  Modify some WP built-in settings
        /* ------------------------------------------------------------------------- */
        //hook : customize_register
        function ha_alter_wp_customizer_settings( $manager ) {
          if ( is_object( $manager->get_setting( 'header_image_data' ) ) ) {
              $manager -> remove_setting( 'header_image_data' );
              if ( class_exists( 'HA_Customize_Header_Image_Data_Setting' ) && class_exists( 'HA_Customize_Header_Image_Setting' ) ) {
                  $manager -> add_setting( new HA_Customize_Header_Image_Data_Setting( $manager, 'header_image_data', array(
                    'theme_supports' => 'custom-header',
                  ) ) );
                  $manager -> remove_setting( 'header_image' );
                  $manager -> add_setting( new HA_Customize_Header_Image_Setting( $manager, 'header_image', array(
                    'default'        => get_theme_support( 'custom-header', 'default-image' ),
                    'theme_supports' => 'custom-header',
                  ) ) );
              }
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
                  //If server send isLocalSkope = true, then try to activate the local skope
                  //serverControlParams.isLocalSkope is used in api.czr_skopeBase.getActiveSkopeId()
                  //Old check was based on isset( $_GET['url'] ), but setting the local skope on init makes the preview too long to load
                  'isLocalSkope'          => apply_filters( 'skope_is_local', isset( $_GET['url'] ) ),
                  'isTopNoteOn'           => apply_filters( 'ha_czr_top_note_status', 'dismissed' != get_option( 'ha_czr_top_note_status' ) ||  ( defined('CZR_DEV') && true === CZR_DEV ) ),
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
    }//class
endif;

?>