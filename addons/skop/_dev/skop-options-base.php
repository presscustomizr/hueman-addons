<?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option_Base' ) ) :
    class HA_Skop_Option_Base {
        public static $instance;

        public static $_global_opt;
        public static $_group_opt;
        public static $_special_group_opt;
        public static $_local_opt;
        public static $_skope_excluded_settings;
        public $global_skope_optname;

        function __construct() {
            //SIDEBAR WIDGETS
            add_filter('sidebars_widgets', array($this, 'ha_use_skope_widgets') );

            //SIDEBAR ON PREVIEW : fix the problem of a customized val being an empty array of wp_inactive_widgets;
            //This filter is ha_customize_val_before_multidimensional_filter_{$opt_name}
            //add_filter('ha_customize_val_before_multidimensional_filter_sidebars_widgets', array($this, 'ha_set_customized_sidebars_val'), 10, 2 );

            //CACHE SOME USEFUL LIST OF SETTINGS : THEME SETTINGS AND SKOPE EXCLUDED SETTINGS
            $this -> ha_cache_skope_excluded_settings();

            //FILTER THE LIST OF SKOPE EXCLUDED SETTINGS
            //=> merge the default ones with those defined in the setting map
            add_filter( 'ha_get_skope_excluded_options', array( $this, 'ha_set_excluded_skope_settings') );


            //CACHE SKOPED OPTION WHEN THE QUERY IS BUILT
            // if ( HU_AD() -> ha_is_customize_preview_frame() ) {
            //   //refresh the theme options right after the _preview_filter when previewing
            //   add_action( 'customize_preview_init'  , array( $this , 'ha_cache_options' ) );
            // } else {
            //   add_action( 'wp' , array( $this, 'ha_cache_options' ) );
            // }
            if ( hu_is_ajax() && ! ha_is_partial_ajax_request() ) {

              add_action( 'ajax_query_ready',  array( $this, 'ha_setup_skope_option_filters' ), 1000 );

              add_action( 'ajax_query_ready' , array( $this, 'ha_cache_options' ), 99999 );

            }
            else {

              //SETUP FILTERS FOR WP OPTIONS AND THEME OPTIONS
              // => exclude partial requests, WP does the job
              if ( ! ha_is_partial_ajax_request() ) {
                add_action( 'wp',  array( $this, 'ha_setup_skope_option_filters' ), 1000 );
              }

              add_action( 'wp' , array( $this, 'ha_cache_options' ), 99999 );

            }


            //---------------------- DEPRECATED -------------------------------- //
            //SET THE NAME OF THE GLOBAL SKOPE OPTION
            //This option stores all global skope settings : theme and wp.
            //It is updated each time the global skope get saved or reset in the customizer
            //It is used to send the list of currently modified global settings in db
            $theme_name = ha_get_skope_theme_name();
            $this -> global_skope_optname = "{$theme_name}_global_skope";

        }//construct





        /* ------------------------------------------------------------------------- *
         *  SET AND GET CACHED OPTIONS
        /* ------------------------------------------------------------------------- */
        //the purpose of this function is to store the various skope options as properties
        //instead of getting them each time
        //fired on customize_preview_init if in preview frame
        //fired by constructor otherwise
        //@return void()
        //HOOK : WP
        function ha_cache_options() {
            $meta_type = ha_get_skope( 'meta_type', true );
            $_skope_list = array( 'global', 'group', 'special_group', 'local' );
            foreach ($_skope_list as $_skp ) {
                switch ( $_skp ) {
                    //don't cache the global option, WP already does it
                    case 'global':
                      //self::$_global_opt = false === get_option( HU_THEME_OPTIONS ) ? array() : (array)get_option( HU_THEME_OPTIONS );
                    break;
                    case 'group':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'group' );
                      self::$_group_opt = $this -> ha_get_skope_opt( 'group', $db_opt_name );
                      self::$_group_opt = ! self::$_group_opt ? array() : self::$_group_opt;
                    break;
                    case 'special_group':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'special_group' );
                      self::$_special_group_opt = $this -> ha_get_skope_opt( 'special_group', $db_opt_name );
                      self::$_special_group_opt = ! self::$_special_group_opt ? array() : self::$_special_group_opt;
                    break;
                    case 'local':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'local' );
                      self::$_local_opt = $this -> ha_get_skope_opt( 'local', $db_opt_name );
                      self::$_local_opt = ! self::$_local_opt ? array() : self::$_local_opt;
                    break;
                }
            }
            do_action( 'skope_options_cached');
        }


        //Get the skoped option from the db
        //The option name is based on the current skope
        //=> this can't be fired before 'wp'
        //
        //@param level : local, group, special_group
        //@param type : post
        //@return the provided meta $skope_meta_key or all skope post metas if the key is not provided or empty
        function ha_get_skope_opt( $level = '', $skope_meta_key = '' ) {
            if( 'global' == $level ) {
              return new WP_Error('skope_error', 'The global options cannot be accessed with ha_get_skope_opt' );
            }
            $skope_meta_key = empty( $level ) ? '' : $this -> ha_get_skope_opt_name( $level );
            $_opt = get_post_meta( ha_get_skope_post_id(), $skope_meta_key, true );
            $_return = array();
            //if no meta key has been provided, this will return an array of all post meta json encoded
            if ( empty( $skope_meta_key ) && is_array( $_opt ) ) {
              foreach ( $_opt as $meta_key => $value ) {
                if ( is_array($value) && 1 == count($value) )
                  $_return[$meta_key] = maybe_unserialize( $value[0] );
                else
                  $_return[$meta_key] = array_map('maybe_unserialize', $value);
              }
            } else {
              $_return = $_opt;
            }

            return is_array($_return) ? $_return : array();
        }



        //@return the array of cached opt
        function ha_get_cached_opt( $skope = null, $opt_name = null ) {
            $skope = is_null( $skope ) ? 'local' : $skope;
            $_opt_array = array();

            switch ( $skope ) {
                case 'global':
                  $alloptions = wp_cache_get( 'alloptions', 'options' );
                  $alloptions = maybe_unserialize($alloptions);
                  $_opt_array = $alloptions;
                break;
                case 'group':
                  $_opt_array = self::$_group_opt;
                break;
                case 'special_group':
                  $_opt_array = self::$_special_group_opt;
                break;
                case 'local':
                  $_opt_array = self::$_local_opt;
                break;
            }

            //make sure we still have an array at this stage
            $_opt_array = ! is_array( $_opt_array ) ? array() : $_opt_array;

            if ( is_null( $opt_name ) )
              return $_opt_array;
            else {
                if ( in_array( $opt_name, HU_utils::$_theme_setting_list ) ) {
                    $_theme_option_prefix = strtolower(HU_THEME_OPTIONS);
                    $opt_name = "{$_theme_option_prefix}[{$opt_name}]";
                }
                return array_key_exists( $opt_name, $_opt_array ) ? $_opt_array[$opt_name] : '_no_set_';
            }
        }


        /* ------------------------------------------------------------------------- *
        *  CACHE AND SET SETTINGS EXCLUDED FROM SKOPE
        /* ------------------------------------------------------------------------- */
        //Fired in __construct()
        function ha_cache_skope_excluded_settings() {
             if ( is_array(self::$_skope_excluded_settings) && ! empty( self::$_skope_excluded_settings ) )
               return;
             $_settings_map = HU_utils_settings_map::$instance -> hu_get_customizer_map( null, 'add_setting_control' );
             $_excluded = array();
             foreach ( $_settings_map as $_id => $data ) {
               if ( isset($data['skoped']) && false === $data['skoped'] )
                 $_excluded[] = $_id;
             }

            //WFC COMPAT
            if ( class_exists( 'TC_utils_wfc' ) ) {
                $wfc_setting_map = TC_utils_wfc::$instance -> tc_customizer_map();
                if ( array_key_exists( 'add_setting_control', $wfc_setting_map ) ) {
                    foreach ( $wfc_setting_map['add_setting_control'] as $_id => $data ) {
                          $_excluded[] = $_id;
                    }
                }
            }
            self::$_skope_excluded_settings = $_excluded;
        }


        /* ------------------------------------------------------------------------- *
         *  FILTER THE LIST OF SKOPE EXCLUDED SETTINGS
        /* ------------------------------------------------------------------------- */
        //=> merge the default ones with those defined in the setting map
        //hook : ha_get_skope_excluded_options declared in init-skop.php
        function ha_set_excluded_skope_settings( $_default_excluded ) {
            return array_merge( $_default_excluded, self::$_skope_excluded_settings );
        }




        /* ------------------------------------------------------------------------- *
         *  FILTER WP AND THEME OPTIONS
        /* ------------------------------------------------------------------------- */
        //hook : wp
        function ha_setup_skope_option_filters() {
            //FILTER THEME OPTIONS
            add_filter( 'hu_opt', array( $this, 'ha_filter_hu_opt_for_skope' ), 1000, 4 );

            //FILTER WP OPTIONS
            $theme = get_option( 'stylesheet' );
            $wp_options = array(
              'blogname',
              'blogdescription',
              //header_image, header_image_data, custom_logo, custom_css, custom_css_post_id, nav_menu_locations
              "theme_mods_{$theme}"
            );

            if ( apply_filters( 'ha_skope_sidebars_widgets', false ) ) {
              $wp_options[] = 'sidebars_widgets';
              $wp_options  = array_merge( $wp_options, hu_get_registered_widgets_option_names() );
            }

            foreach ( $wp_options as $wp_opt ) {
              //documented in wp-includes/option.php
              add_filter( "option_{$wp_opt}", array( $this, 'ha_filter_wp_builtin_options'), 2000, 2 );
            }
        }


        ///////FILTER WP OPTIONS
        //hook : option_{$wp_opt}
        //Example of filtered option : sidebars_widgets
        function ha_filter_wp_builtin_options( $original_value, $option_name = null ) {
          if ( is_null( $option_name ) )
            return $original_value;


          $authorized_theme_mods = ha_get_wp_builtins_skoped_theme_mods();
          $theme = get_option( 'stylesheet' );

          //Filter theme mods built-in options like header_image
          if ( "theme_mods_{$theme}" == $option_name ) {
              $skoped_theme_mods = array();

              foreach ( $authorized_theme_mods as $_tmodname ) {
                  $_tmodval = array_key_exists( $_tmodname, $original_value ) ? $original_value[$_tmodname] : '_no_set_';
                  $_tmodval = $this -> ha_filter_hu_opt_for_skope( $_tmodval, $_tmodname, null );
                  //After the filter, if the value is still not set, don't add it to the build theme mods
                  if ( '_no_set_' !== $_tmodval ) {
                      $skoped_theme_mods[$_tmodname] = $_tmodval;
                      //documented in wp-includes/theme.php
                      add_filter( "theme_mod_{$_tmodname}", array( $this, '_filter_theme_mods'), 2000 );
                  }


              }
              return wp_parse_args( $skoped_theme_mods, $original_value );

              // if ( is_array( $original_value ) ) {
              //     foreach( $original_value as $_opt_name => $_tmodval ) {
              //         if ( ! $_opt_name )
              //           continue;
              //         $skoped_theme_mods[$_opt_name] = $this -> ha_filter_hu_opt_for_skope( $_tmodval, $_opt_name, null );
              //         ha_error_log('alors ?' . $_opt_name );

              //     }
              //     return $skoped_theme_mods;
              // }
          }
          //Filter simple builtin options like blogname
          else {
              //the option group is null
              return $this -> ha_filter_hu_opt_for_skope( $original_value, $option_name, null );
          }
          return $original_value;
        }


        //hook : theme_mod_{$_opt_name}
        function _filter_theme_mods( $value ) {
          //extract theme mod name
          $_filter = current_filter();
          $_ptrn = 'theme_mod_';
          if ( $_ptrn !== substr( $_filter, 0, strlen($_ptrn) ) )
            return $value;

          $_mod_name = str_replace($_ptrn, '',  $_filter);
          if ( ! ha_is_option_skoped( $_mod_name ) )
            return $value;
          //the option group is null
          return $this -> ha_filter_hu_opt_for_skope( $value, $_mod_name, null );
        }






        ///////FILTER THEME OPTIONS
        //which option to get ?
        //1) WHEN CUSTOMIZING
        //- if dyn_type is 'option', then let wp do the job
        //- if dyn_type is not option,
        //      A) the requested option name is currently being customized
        //        => if so, then get the customized value
        //      B) the requested option is not being customized, then get the saved db option using dyn_type and opt_name from $_POST
        //
        //2) WHEN NOT CUSTOMIZING
        // A) the current context can have a meta option : posts (post, page, cpt), tax, authors
        //    => retrieve the meta and check if an entry exists for this option
        // B) the current context can have specific global options like home, all_posts, all_pages, all_{custom_post_type}
        //   all_tag, all_cat, all_{custom_tax}, all_authors, 404, search, date
        //     => if so then check if the current option has an entry in this specific global and return it
        // C) the current context has no specific global option, then fall back on the default value
        //
        //HOOK : hu_opt
        function ha_filter_hu_opt_for_skope( $_opt_val , $opt_name , $opt_group = HU_THEME_OPTIONS , $_default_val = null ) {
            //if the opt group not null, we are retrieving a theme option
            $_new_val = $_opt_val;

            //IF PREVIEWING
            if ( HU_AD() -> ha_is_customize_preview_frame() && !  HU_AD() -> ha_is_previewing_live_changeset() ) {
                $_new_val = $this -> _get_sanitized_preview_val( $_opt_val, $opt_name );
            } else {
                //@param = value, option name, skope, inherits
                $_new_val = $this -> _get_front_end_val( $_opt_val, $opt_name, 'local', true );
            }
            //falls back to global
            return $_new_val;
        }






        /* ------------------------------------------------------------------------- *
        * SET OPTION
        /* ------------------------------------------------------------------------- */
        //Write the new skope option in db
        //Used with 'header_image'
        function ha_set_skope_option_val( $opt_name, $new_value, $skope_meta_key = null ) {
            if ( empty($opt_name) || is_null($skope_meta_key ) ) {
              return new WP_Error( 'missing param(s) in HA_SKOP_OPT::ha_set_skope_option_val' );
            }

            $skope_post_id = ha_get_skope_post_id();

            // Obtain/merge data for skope meta
            $original_data = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key ) );
            $data = $original_data;
            if ( is_wp_error( $data ) ) {
              $data = array();
            }
            $data = $this -> ha_preprocess_skope_val( $new_value, $opt_name, $data );

            $r = update_post_meta( $skope_post_id, $skope_meta_key, $data );

            return $r;
        }









        /* ------------------------------------------------------------------------- *
        * SIDEBARS AND WIDGETS SPECIFICS
        /* ------------------------------------------------------------------------- */
        //hook filter: sidebar_widgets
        function ha_use_skope_widgets( $original ) {
          // if ( 0 == did_action('wp') )
          //   return $original;
          if ( ! apply_filters( 'ha_skope_sidebars_widgets', false ) )
            return $original;

          $db_skope_widgets = get_option('sidebars_widgets');
          $db_skope_widgets = is_array($db_skope_widgets) ? $db_skope_widgets : array();
          $new_sidebar_widgets = $db_skope_widgets;
          $defaut_hu_widget_ids = hu_get_widget_zone_ids();

          foreach ( $original as $key => $value) {
            if ( in_array( $key, $defaut_hu_widget_ids ) || ! is_array($value) )
              continue;
            //adds any additional entry that the original array contains and that is not a widget id
            //=> ex : orphaned_widgets_1, array_version, ..., wp_inactive_widgets
            $new_sidebar_widgets[$key] = (array)$value;
          }
          //then check if we have only array type values in the new sidebars_widgets
          foreach ($new_sidebar_widgets as $k => $v) {
            if ( ! is_array($v) )
              unset($new_sidebar_widgets[$k]);
          }

          //then make sure that a widget can not be both active in a sidebar AND part of the wp_inactive_widgets list
          if ( isset($new_sidebar_widgets['wp_inactive_widgets']) && is_array($new_sidebar_widgets['wp_inactive_widgets']) ) {
            foreach ( $new_sidebar_widgets as $sidebar => $wdg_list ) {
                //skip all entries that are not real sidebars
                if ( 'wp_inactive_widgets'  == $sidebar )
                  continue;
                if ( 'array_version' == $sidebar )
                  continue;
                if ( 'orphaned_' == substr($sidebar, 0, strlen('orphaned_') ) )
                  continue;

                foreach ( $wdg_list as $wdg_id ) {
                    if ( false === array_search( $wdg_id, $new_sidebar_widgets['wp_inactive_widgets'] ) )
                      continue;
                    $key_to_remove = array_search( $wdg_id, $new_sidebar_widgets['wp_inactive_widgets'] );
                    unset( $new_sidebar_widgets['wp_inactive_widgets'][$key_to_remove] );
                }//foreach
            }//foreach
          }//if
          return $new_sidebar_widgets;
        }


        //hook : 'ha_customize_val_before_multidimensional_filter_sidebars_widgets'
        //DEPRECATED
        function ha_set_customized_sidebars_val( $customized_val, $opt_name ) {
          if ( is_array($customized_val) && isset($customized_val['sidebars_widgets[wp_inactive_widgets']) && 1 == count($customized_val) )
            return '_not_customized_';
          return $customized_val;
        }

    }//class
endif;

?>