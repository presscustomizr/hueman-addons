<?php

if ( ! class_exists( 'HA_Skop_Cust_Prev' ) ) :
    final class HA_Skop_Cust_Prev {
        function __construct() {
            /* ------------------------------------------------------------------------- *
             *  CUSTOMIZE PREVIEW : export skope data
            /* ------------------------------------------------------------------------- */
            add_action( 'wp_footer', array( $this, 'ha_print_server_skope_data' ), 30 );
        }



        //hook : 'wp_footer'
        function ha_print_server_skope_data() {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return;

            global $wp_query, $wp_customize;
            $_meta_type = ha_get_skope( 'meta_type', true );

            // $_czr_scopes = array(
            //   'local' => array(
            //     'level'         => ha_get_skope(),
            //     'dyn_type'    => ha_get_skope_dyn_type( $_meta_type ),
            //     'opt_name'    => HA_SKOP_OPT() -> ha_get_skope_opt_name(),
            //     'is_default'  => false,
            //     'is_winner'   => true
            //   ),
            //   'global' => array(
            //     'ctx'         => '_all_',
            //     'dyn_type'    => 'option',
            //     'opt_name'    => HU_THEME_OPTIONS,
            //     'is_default'  => true,
            //     'is_winner'   => false
            //   )
            // );
            $_czr_skopes            = $this -> _ha_get_json_export_ready_skopes();
            $_is_changeset_dirty    = $this -> _ha_is_changeset_dirty();
            ?>
              <script type="text/javascript" id="czr-print-skop">
                (function ( _export ){
                      _export.czr_skopes        = <?php echo wp_json_encode( $_czr_skopes ); ?>;
                      _export.isChangesetDirty  = <?php echo wp_json_encode( $_is_changeset_dirty ); ?>
                })( _wpCustomizeSettings );
              </script>
            <?php
        }




        //@return a boolean stating if there's any value saved in the various changeset
        //Note : for the 'global' changeset the blogname can be used to init the wp core changeset with dummy_change,
        // => therefore we need to check if it is part of the dirty AND that it has a value item set.
        function _ha_is_changeset_dirty() {
            if ( ! HU_AD() -> ha_is_changeset_enabled() )
              return false;

            global $wp_customize;

            $skope_post_id = $wp_customize->changeset_post_id();
            if ( false == $skope_post_id || empty( $skope_post_id ) )
              return;

            $_level_list = array( 'global', 'group', 'special_group', 'local' );
            $is_dirty = false;
            foreach ( $_level_list as $level ) {
              $_changeset_data = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => null, 'level' => $level ) );
              if ( 'global' == $level ) {
                if ( count($_changeset_data) > 1 )
                  $is_dirty = true;
                else {
                  if ( isset( $_changeset_data['blogname'] ) && isset( $_changeset_data['blogname']['value'] ) )
                    $is_dirty = true;
                }
              } else {
                $is_dirty = is_array( $_changeset_data ) && ! empty( $_changeset_data );
              }
              if ( $is_dirty )
                break;
            }

            return $is_dirty;
        }



        /* ------------------------------------------------------------------------- *
         *  CUSTOMIZE PREVIEW : BUILD SKOPES JSON
        /* ------------------------------------------------------------------------- */
        //generates the array of available scopes for a given context
        //ex for a single post tagged #tag1 and #tag2 and categroized #cat1 :
        //global
        //all posts
        //local
        //posts tagged #tag1
        //posts tagged #tag2
        //posts categorized #cat1
        //@return array()
        function _ha_get_json_export_ready_skopes() {
            $skopes = array();
            $_meta_type = ha_get_skope( 'meta_type', true );

            //default properties of the scope object
            $defaults = _ha_get_default_scope_model();

            $saved_glob_opt = $this -> _ha_get_sanitized_skoped_saved_global_options();

            //global and local and always sent
            $skopes[] = wp_parse_args(
              array(
                'title'       => ha_get_skope_title( 'global' ),
                'long_title'  =>  ha_get_skope_title( 'global', null, true ),
                'skope'       => 'global',
                'level'       => '_all_',
                'dyn_type'    => 'option',
                'opt_name'    => HU_THEME_OPTIONS,
                'is_winner'   => false,
                'is_primary'  => true,
                'has_db_val'  => ! empty( $saved_glob_opt ),
                'db'          => $saved_glob_opt,
                'changeset'   => $this -> _ha_get_api_ready_skope_changeset( array( 'level' => 'global', 'skope_meta_key' => '' ) )
              ),
              $defaults
            );


            //SPECIAL GROUPS
            //@todo


            //GROUP
            //Do we have a group ? => if yes, then there must be a meta type
            if ( ha_get_skope('meta_type') ) {
              $group_opt_name = HA_SKOP_OPT() -> ha_get_skope_opt_name( 'group' );
              $group_opts = HA_SKOP_OPT() -> ha_get_skope_opt( 'group', $group_opt_name );
              $skopes[] = wp_parse_args(
                array(
                  'title'       => ha_get_skope_title( 'group', $_meta_type ),
                  'long_title'  => ha_get_skope_title( 'group', $_meta_type, true),
                  'skope'       => 'group',
                  'level'       => 'all_' . ha_get_skope('type'),
                  'dyn_type'    => 'skope_meta',
                  'opt_name'    => $group_opt_name,
                  'db'          => $this -> _ha_get_api_ready_skope_db_option( array( 'level' => 'group', 'skope_meta_key' => $group_opt_name ) ),
                  'changeset'   => $this -> _ha_get_api_ready_skope_changeset( array( 'level' => 'group', 'skope_meta_key' => $group_opt_name ) ),
                  'has_db_val'  => ! empty( $group_opts )
                ),
                $defaults
              );
          }


          //LOCAL
          $loc_opt_name   = HA_SKOP_OPT() -> ha_get_skope_opt_name( 'local');
          $local_opts = HA_SKOP_OPT() -> ha_get_skope_opt( 'local', $loc_opt_name );
          $skopes[] = wp_parse_args(
            array(
                'title'       => ha_get_skope_title( 'local', $_meta_type ),
                'long_title'  => ha_get_skope_title( 'local', $_meta_type, true),
                'skope'       => 'local',
                'level'       => ha_get_skope(),
                'dyn_type'    => 'skope_meta',
                'opt_name'    => $loc_opt_name,
                'obj_id'      => ha_get_skope('id'),
                'db'          => $this -> _ha_get_api_ready_skope_db_option( array( 'level' =>'local', 'skope_meta_key' => $loc_opt_name ) ),
                'changeset'   => $this -> _ha_get_api_ready_skope_changeset( array( 'level' => 'local', 'skope_meta_key' => $loc_opt_name ) ),
                'is_winner'   => true,
                'has_db_val'  => ! empty( $local_opts )
            ),
            $defaults
          );
          return $skopes;
        }












        /* ------------------------------------------------------------------------- *
         *  GET CHANGESETS
        /* ------------------------------------------------------------------------- */
        //The purpose of this function is to return the list of db saved skope values
        //It can get both the skope meta value already saved
        //AND the changeset value
        //
        //skope options are stored with their full name. Ex : hu_theme_options[copyright]]
        //
        //for multidimensional settings like sidebars_widgets* or widget_*, the setting name has to be preprocessed
        //so that it looks like in the customizer api.
        //Ex widget_archives = array( [3] => array( ... )  ) should become widget_archives[3] = array(...)
        //
        //The value of each setting gets sanitized with the WP filter customize_sanitize_js_{setting_id}
        //
        //@param level string. 'local' for ex.
        //@param db_opt_name string. name of option in db
        function _ha_get_api_ready_skope_changeset( $args ) {
            if ( ! HU_AD() -> ha_is_changeset_enabled() )
              return array();

            $defaults = array(
                'level' => '',
                'skope_meta_key' => ''
            );
            $args = wp_parse_args( $args, $defaults );

            $level          = $args['level'];
            $skope_meta_key = $args['skope_meta_key'];

            global $wp_customize;
            $skope_changeset_val = array();

            //Where are we getting the saved options from ?
            // => from the current changeset
            //   - post content for global options
            //   - post metas for not global skope options

            $skope_post_id = $wp_customize->changeset_post_id();
            if ( false != $skope_post_id && ! empty( $skope_post_id ) ) {
              $skope_changeset_val = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key, 'level' => $level ) );
            } else {
              ha_error_log('NO CHANGESET POST AVAILABLE in _ha_get_api_ready_skope_changeset when getting changset for skope : ' . $level );
              return array();
            }

            $skope_changeset_val = ! is_array( $skope_changeset_val ) ? array() : $skope_changeset_val;
            $theme_setting_list = HU_utils::$_theme_setting_list;
            $theme_option_group = HU_THEME_OPTIONS;

            $api_ready_changeset_val = array();

            // For the changeset, each setting_id has setting_params looking like :
            //array(
            //  value => 'copyright Global',
            //  type => 'option'
            //  user_id => 1
            //)
            foreach ( $skope_changeset_val as $raw_setting_id => $setting_params ) {
                // ha_error_log( '////// RAW SETTING ID' );
                // ha_error_log( $raw_setting_id );
                //if the changeset is requested, then $data must look like :
                //array(
                //  value => 'copyright Global',
                //  type => 'option'
                //  user_id => 1
                //)
                //Bail if $setting_params are not well formed
                if ( ! is_array( $setting_params ) || ! array_key_exists('value', $setting_params ) )
                  continue;

                //ISOLATE THE VALUE
                //Changeset data are typically stored as an array including a value key.
                //we only need to send the value the api panel
                // hu_theme_options[copyright] = array(
                //   type =>  "option"
                //   value => "copyright HOME NEW"
                // )
                $setting_value = $setting_params['value'];

                //FORMAT SETTING ID TO BE COMPLIANT WITH API
                // => theme mods in changeset look like : hueman::nav_menu_locations[header]
                // => should be turned into : nav_menu_locations[header]

                //Store the setting type
                $setting_type = isset( $setting_params['type'] ) ? $setting_params['type'] : 'option';

                //Get the actual setting id
                //handles theme_mods type looking like : hueman::custom_logo
                $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                $is_theme_mod_setting = (
                    isset( $setting_params['value'] )
                    &&
                    isset( $setting_type )
                    &&
                    'theme_mod' === $setting_type
                    &&
                    preg_match( $namespace_pattern, $raw_setting_id, $matches )
                );
                // ha_error_log( '/// IS THEME MODE SETTING');
                // ha_error_log( $is_theme_mod_setting );
                if ( $is_theme_mod_setting ) {
                    if ( $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                      $actual_setting_id = $matches['setting_id'];
                    }
                } else {
                    $actual_setting_id = $raw_setting_id;
                }


                //SANITIZE
                if ( is_object( $wp_customize -> get_setting( $actual_setting_id ) ) ) {
                  $setting_value = apply_filters( "customize_sanitize_js_{$actual_setting_id}", $setting_value, $wp_customize -> get_setting( $actual_setting_id ) );
                }

                //handles theme settings saved with their short names
                //skope options are stored with their full name. Ex : hu_theme_options[copyright]]
                if ( in_array( $actual_setting_id, $theme_setting_list ) ) {
                    $czr_opt_id = "{$theme_option_group}[{$actual_setting_id}]";
                    if ( is_object( $wp_customize -> get_setting( $czr_opt_id ) ) ) {
                      $setting_value = apply_filters( "customize_sanitize_js_{$czr_opt_id}", $setting_value, $wp_customize -> get_setting( $czr_opt_id ) );
                    }
                }


                //Writes
                $api_ready_changeset_val[$actual_setting_id] = $setting_value;

            }

            return $api_ready_changeset_val;
        }











        /* ------------------------------------------------------------------------- *
         *  GET OPTIONS SAVED IN DB
        /* ------------------------------------------------------------------------- */
        //@param level string. 'local' for ex.
        //@param db_opt_name string. name of option in db
        function _ha_get_api_ready_skope_db_option( $args ) {
            $defaults = array(
                'level' => '',
                'skope_meta_key' => ''
            );
            $args = wp_parse_args( $args, $defaults );

            $level          = $args['level'];
            $skope_meta_key = $args['skope_meta_key'];

            global $wp_customize;
            $skope_db_val       = HA_SKOP_OPT() -> ha_get_skope_opt( $level, $skope_meta_key );
            $skope_db_val       = ! is_array( $skope_db_val ) ? array() : $skope_db_val;
            $theme_setting_list = HU_utils::$_theme_setting_list;
            $theme_option_group = HU_THEME_OPTIONS;
            $api_ready_db_val   = array();


            foreach ( $skope_db_val as $actual_setting_id  => $setting_value ) {
                if ( 'skope_infos' == $actual_setting_id )
                  continue;
                //is multidimensional ?
                //the theme settings are not.
                //some built-in settings like blogdescription, custom_logo are not either
                //for now only sidebars_widgets* and nav_menu_locations*
                if ( ! _ha_is_wp_setting_multidimensional( $actual_setting_id ) ) {

                    //handles wp built-in not dimensional settings
                    //AND theme settings saved with their full name. Ex : hu_theme_options[copyright]]
                    if ( is_object( $wp_customize -> get_setting( $actual_setting_id ) ) ) {
                      $setting_value = apply_filters( "customize_sanitize_js_{$actual_setting_id}", $setting_value, $wp_customize -> get_setting( $actual_setting_id ) );
                    }

                    //handles theme settings saved with their short names
                    //skope options are stored with their full name. Ex : hu_theme_options[copyright]]
                    if ( in_array( $actual_setting_id, $theme_setting_list ) ) {
                        $czr_opt_id = "{$theme_option_group}[{$actual_setting_id}]";
                        if ( is_object( $wp_customize -> get_setting( $czr_opt_id ) ) ) {
                            $setting_value = apply_filters( "customize_sanitize_js_{$czr_opt_id}", $setting_value, $wp_customize -> get_setting( $czr_opt_id ) );
                        }
                    }
                    $api_ready_db_val[$actual_setting_id] = $setting_value;
                }
                else {
                    // An option like nav_menu_locations is saved as :
                    //  [nav_menu_locations] => Array
                    // (
                    //     [footer] => 2
                    //     [topbar] => 4
                    //     [header] => 3
                    // )
                    // => it must be turned into api ready settings looking like :
                    // nav_menu_locations[footer] = 2
                    // nav_menu_locations[topbar] = 4
                    // nav_menu_locations[header] = 3
                    $to_merge = _ha_build_multidimensional_db_option( $actual_setting_id, $setting_value );
                    //apply js sanitization function
                    //=> ex. For widgets : 'sanitize_widget_js_instance'
                    foreach( $to_merge as $set_id => $value ) {
                        if ( is_object( $wp_customize -> get_setting( $set_id ) ) ) {
                            //@see class-wp-customize-setting.php::js_value()
                            $value = apply_filters( "customize_sanitize_js_{$set_id}", $value, $wp_customize -> get_setting( $set_id ) );
                        }
                        $to_merge[$set_id] = $value;
                    }
                    $api_ready_db_val = array_merge( $api_ready_db_val, $to_merge );
                }
            }
            // ha_error_log('////////////////////// API READY DB VAL FOR SKOPE : ' . $level );
            // ha_error_log( print_R( $api_ready_db_val, true ) );
            // ha_error_log('///////////////////////////////////////');
            return $api_ready_db_val;
        }



        //@return the full set of sanitized saved global options
        function _ha_get_sanitized_skoped_saved_global_options() {
            global $wp_customize;

            $_theme_opts = hu_get_raw_option( null, HU_THEME_OPTIONS );
            $_defaults_theme_options  = HU_utils::$inst -> hu_get_default_options();
            $_saved_opts          = array();
            if ( ! is_array( $_theme_opts ) )
              return array();
            foreach ( $_theme_opts as $opt_name => $value) {
                if ( ha_is_option_protected( $opt_name ) )
                  continue;
                if ( ! ha_is_option_skoped( $opt_name ) )
                  continue;
                if ( $this -> _ha_is_option_set_to_default( $opt_name, $value, $_defaults_theme_options ) )
                  continue;
                //ha_error_log( $opt_name );
                //is theme option ?
                if ( in_array( $opt_name, HU_utils::$_theme_setting_list ) ) {
                  $_theme_option_prefix = strtolower(HU_THEME_OPTIONS);
                  $opt_name = "{$_theme_option_prefix}[{$opt_name}]";
                }

                $_saved_opts[$opt_name] = $value;
            }

            //WORDPRESS OPTIONS AND THEME MODS
            //regular options included in skope
            $regular_wp_builtins = array(
              'blogname',
              'blogdescription'
            );
            foreach ($regular_wp_builtins as $_opt ) {
                $_saved_opts[$_opt] = hu_get_raw_option( $_opt );
            }

            // ha_error_log( '/// RAW OPTIONS' );
            // ha_error_log( print_R( $_saved_opts, true ) );

            // simple theme mods included in skope
            // To add : sidebars_widgets = array()
            $authorized_theme_mods = ha_get_wp_builtins_skoped_theme_mods();
            //get the unfiltered theme_mods
            $theme = get_option( 'stylesheet' );
            $_raw_theme_mods = hu_get_raw_option( "theme_mods_{$theme}" );

            // ha_error_log( '/// RAW THEME MODS' );
            // ha_error_log( print_R( $_raw_theme_mods, true ) );

            foreach ( $authorized_theme_mods as $_theme_mod_name ) {
                if ( ! isset( $_raw_theme_mods[$_theme_mod_name] ) )
                    continue;

                if ( ! _ha_is_wp_setting_multidimensional( $_theme_mod_name ) ) {
                    $_saved_opts[$_theme_mod_name] =  $_raw_theme_mods[$_theme_mod_name];
                } else {
                    $to_merge = _ha_build_multidimensional_db_option( $_theme_mod_name, $_raw_theme_mods[$_theme_mod_name] );
                    //apply js sanitization function
                    //=> ex. For widgets : 'sanitize_widget_js_instance'
                    foreach( $to_merge as $set_id => $value ) {
                        $to_merge[$set_id] = $value;
                    }
                    $_saved_opts = array_merge( $_saved_opts, $to_merge );
                }

            }

            //Sanitize before injection in preview
            $js_sanitized_saved_opts = array();

            //ha_error_log( '/// $_saved_opts' );
            //ha_error_log( print_R( $_saved_opts, true ) );

            foreach ( $_saved_opts as $cand_setting_id => $cand_setting_value ) {
                if ( is_object( $wp_customize -> get_setting( $cand_setting_id ) ) ) {
                    $sanitized_value = apply_filters( "customize_sanitize_js_{$cand_setting_id}", $cand_setting_value, $wp_customize -> get_setting( $cand_setting_id ) );
                    $js_sanitized_saved_opts[$cand_setting_id] = $sanitized_value;
                }
                // else {
                //     ha_error_log( 'In _ha_get_sanitized_skoped_saved_global_options, ' . $cand_setting_id . ' could not be sanitized before being injected in preview. Not registered in $wp_customize.' );
                //     //$sanitized_value = $cand_setting_value;
                // }
            }
            // ha_error_log( '/// $js_sanitized_saved_opts' );
            // ha_error_log( print_R( $js_sanitized_saved_opts, true ) );
            return $js_sanitized_saved_opts;
        }








        /* ------------------------------------------------------------------------- *
         *  CUTE LITTLE HELPERS
        /* ------------------------------------------------------------------------- */
        function _ha_is_option_set_to_default( $opt_name, $value, $defaults ) {
            if ( ! is_array( $defaults ) || ! array_key_exists( $opt_name, $defaults ) )
              return;
            //@todo : for value written as associative array, we might need a more advanced comparison tool using array_diff_assoc()
            if ( ! is_array( $value ) )
              return $value == $defaults[$opt_name];
            else {
                if ( is_array( $value ) && ! is_array( $defaults[$opt_name] ) )
                  return;
                else {
                  if ( empty( $defaults[$opt_name] ) )
                    return;
                  // ha_error_log( '///_ha_is_option_set_to_default' . $opt_name );
                  // ha_error_log( print_R( array_intersect( $value, $defaults[$opt_name] ) ), true );
                  // ha_error_log( print_R( $value , true ) );
                  // ha_error_log( print_R( $defaults , true ) );
                  return count($value) == count( array_intersect( $value, $defaults[$opt_name] ) );
                }
            }
        }
    }//class
endif;

?>