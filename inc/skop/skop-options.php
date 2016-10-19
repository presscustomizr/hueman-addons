<?php
if ( ! class_exists( 'HA_Skop_Option' ) ) :
    final class HA_Skop_Option {
        public static $instance;

        public static $_global_opt;
        public static $_group_opt;
        public static $_special_group_opt;
        public static $_local_opt;
        public static $_skope_excluded_settings;
        public static $_theme_setting_list;
        public $global_skope_optname;

        public static function ha_skop_opt_instance() {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof HA_Skop_Option ) )
              self::$instance = new HA_Skop_Option();
            return self::$instance;
        }

        function __construct() {
            // if ( hu_is_customize_preview_frame() ) {
            //   //refresh the theme options right after the _preview_filter when previewing
            //   add_action( 'customize_preview_init'  , array( $this , 'ha_cache_options' ) );
            // } else {
            //   add_action( 'wp' , array( $this, 'ha_cache_options' ) );
            // }
            add_action( 'wp' , array( $this, 'ha_cache_options' ), 99999 );


            //SIDEBAR WIDGETS
            add_filter('sidebars_widgets', array($this, 'ha_use_skope_widgets') );

            //SIDEBAR ON PREVIEW : fix the problem of a customized val being an empty array of wp_inactive_widgets;
            //This filter is ha_customize_val_before_multidimensional_filter_{$opt_name}
            //add_filter('ha_customize_val_before_multidimensional_filter_sidebars_widgets', array($this, 'ha_set_customized_sidebars_val'), 10, 2 );


            //CACHE SOME USEFUL LIST OF SETTINGS : THEME SETTINGS AND SKOPE EXCLUDED SETTINGS
            $this -> ha_cache_skope_excluded_settings();
            $this -> ha_cache_theme_setting_list();
            // add_action( 'after_setup_theme', array( $this, 'ha_cache_skope_excluded_settings' ) );
            // add_action( 'after_setup_theme', array( $this, 'ha_cache_theme_setting_list' ) );

            //FILTER THE LIST OF SKOPE EXCLUDED SETTINGS
            //=> merge the default ones with those defined in the setting map
            add_filter( 'hu_get_skope_excluded_options', array( $this, 'ha_set_excluded_skope_settings') );

            //SETUP FILTERS FOR WP OPTIONS AND THEME OPTIONS
            add_action( 'wp',  array( $this, 'ha_setup_skope_option_filters' ), 1000 );

            //SET THE NAME OF THE GLOBAL SKOPE OPTION
            //This option stores all global skope settings : theme and wp.
            //It is updated each time the global skope get saved or reset in the customizer
            //It is used to send the list of currently modified global settings in db
            $theme_name = strtolower(THEMENAME);//is always the parent theme name
            $this -> global_skope_optname = "{$theme_name}_global_skope";
        }//construct


        /*****************************************************
        * SET AND GET CACHED OPTIONS
        *****************************************************/
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
                    case 'global':
                      HA_Skop_Option::$_global_opt = false === get_option( HU_THEME_OPTIONS ) ? array() : (array)get_option( HU_THEME_OPTIONS );
                    break;
                    case 'group':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'group' );
                      HA_Skop_Option::$_group_opt = $this -> ha_get_skope_opt( 'group', $meta_type, $db_opt_name );
                      HA_Skop_Option::$_group_opt = ! HA_Skop_Option::$_group_opt ? array() : HA_Skop_Option::$_group_opt;
                    break;
                    case 'special_group':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'special_group' );
                      HA_Skop_Option::$_special_group_opt = $this -> ha_get_skope_opt( 'special_group', $meta_type, $db_opt_name );
                      HA_Skop_Option::$_special_group_opt = ! HA_Skop_Option::$_special_group_opt ? array() : HA_Skop_Option::$_special_group_opt;
                    break;
                    case 'local':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'local' );
                      HA_Skop_Option::$_local_opt = $this -> ha_get_skope_opt( 'local', $meta_type, $db_opt_name );
                      HA_Skop_Option::$_local_opt = ! HA_Skop_Option::$_local_opt ? array() : HA_Skop_Option::$_local_opt;
                    break;
                }
            }
        }


        //Get the skoped option from the db
        //The option name is based on the current skope
        //=> this can't be fired before 'wp'
        //
        //@param level : local, group, special_group
        //@param type : post
        function ha_get_skope_opt( $level, $meta_type, $db_opt_name ) {
            $skope = ha_get_skope();
            $_dyn_type = ( hu_is_customize_preview_frame() && isset($_POST['dyn_type']) ) ? $_POST['dyn_type'] : '';
            $_opt = array();

            if( 'local' == $level ) {
              if ( $this -> ha_can_have_meta_opt( $meta_type ) ) {
                  $_id = ha_get_skope('id');
                  switch ($meta_type) {
                      case 'post':
                        $_opt = get_post_meta( $_id , $db_opt_name, true );
                        break;

                      case 'tax':
                        $_opt = get_term_meta( $_id , $db_opt_name, true );
                        break;

                      case 'user':
                        $_opt = get_user_meta( $_id , $db_opt_name, true );
                        break;
                  }
              } else if ( ( 'trans' == $_dyn_type || $this -> ha_can_have_trans_opt( $skope ) ) && false !== get_transient( $db_opt_name ) ) {
                  $_opt = get_transient( $db_opt_name );
              }
            }
            if ( 'group' == $level || 'special_group' == $level ) {
              if ( false !== get_transient( $db_opt_name ) ) {
                  $_opt = get_transient( $db_opt_name );
              }
            }
            return $_opt;
        }



        //@return the array of cached opt
        function ha_get_cached_opt( $skope = null, $opt_name = null ) {
            $skope = is_null( $skope ) ? 'local' : $skope;
            $_opt_array = array();

            switch ( $skope ) {
                case 'global':
                  $_opt_array = HA_Skop_Option::$_global_opt;
                break;
                case 'group':
                  $_opt_array = HA_Skop_Option::$_group_opt;
                break;
                case 'special_group':
                  $_opt_array = HA_Skop_Option::$_special_group_opt;
                break;
                case 'local':
                  $_opt_array = HA_Skop_Option::$_local_opt;
                break;
            }
            if ( is_null( $opt_name ) )
              return $_opt_array;
            else
              return isset( $_opt_array[$opt_name] ) ? $_opt_array[$opt_name] : '_no_set_';
        }




        /*****************************************************
        * CACHE THE LIST OF THEME SETTINGS ONLY
        *****************************************************/
        //hook : after_setup_theme
        //Note : the 'sidebar-areas' setting is not listed in that list because registered specifically
        function ha_cache_theme_setting_list() {
            if ( is_array(self::$_theme_setting_list) && ! empty( self::$_theme_setting_list ) )
              return;
            $_settings_map = HU_utils_settings_map::$instance -> hu_get_customizer_map( null, 'add_setting_control' );
            $_settings = array();
            foreach ( $_settings_map as $_id => $data ) {
                $_settings[] = $_id;
            }
            $default_options = HU_utils::$inst -> hu_get_default_options();
            self::$_theme_setting_list = $_settings;
        }






        /*****************************************************
        * CACHE AND SET SETTINGS EXCLUDED FROM SKOPE
        *****************************************************/
        //hook : after_setup_theme
        function ha_cache_skope_excluded_settings() {
            if ( is_array(self::$_skope_excluded_settings) && ! empty( self::$_skope_excluded_settings ) )
              return;
            $_settings_map = HU_utils_settings_map::$instance -> hu_get_customizer_map( null, 'add_setting_control' );
            $_excluded = array();
            foreach ( $_settings_map as $_id => $data ) {
              if ( isset($data['skoped']) && false === $data['skoped'] )
                $_excluded[] = $_id;
            }
            self::$_skope_excluded_settings = $_excluded;
        }

        //FILTER THE LIST OF SKOPE EXCLUDED SETTINGS
        //=> merge the default ones with those defined in the setting map
        //hook : hu_get_skope_excluded_options declared in HU_Utils::hu_get_skope_excluded_options
        function ha_set_excluded_skope_settings( $_default_excluded ) {
            return array_merge( $_default_excluded, self::$_skope_excluded_settings );
        }







        /*****************************************************
        * SIDEBARS AND WIDGETS SPECIFICS
        *****************************************************/
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







        /*****************************************************
        * FILTER WP AND THEME OPTIONS
        *****************************************************/
        //hook : wp
        function ha_setup_skope_option_filters() {
            //FILTER THEME OPTIONS
            add_filter( 'hu_opt', array( $this, 'ha_filter_hu_opt_for_skope' ), 10, 4 );

            //FILTER WP OPTIONS
            $theme = get_option( 'stylesheet' );
            $wp_options = array(
              'blogname',
              'blogdescription',
              "theme_mods_{$theme}"//header_image, custom_logo
            );

            if ( apply_filters( 'ha_skope_sidebars_widgets', false ) ) {
              $wp_options[] = 'sidebars_widgets';
              $wp_options  = array_merge( $wp_options, hu_get_registered_widgets_option_names() );
            }

            foreach ( $wp_options as $wp_opt ) {
              add_filter( "option_{$wp_opt}", array( $this, 'ha_filter_wp_builtin_options'), 2000, 2 );
            }
        }


        ///////FILTER WP OPTIONS
        //hook : option_{$wp_opt}
        //Example of filtered option : sidebars_widgets
        function ha_filter_wp_builtin_options( $value, $option_name = null ) {
          if ( is_null( $option_name ) )
            return $value;
          $theme = get_option( 'stylesheet' );
          if ( "theme_mods_{$theme}" == $option_name ) {
            $skoped_theme_mods = array();
            if ( is_array($value) ) {
              foreach( $value as $_opt_name => $_val ) {
                  $skoped_theme_mods[$_opt_name] = $this -> ha_filter_hu_opt_for_skope( $_val, $_opt_name, null );
              }
              return $skoped_theme_mods;
            }
          } else {
            return $this -> ha_filter_hu_opt_for_skope( $value, $option_name, null );//the option group is null
          }
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

            //CUSTOMIZING
            $_customized_val = $this -> ha_get_customized_value( $opt_name );
            if ( hu_is_customize_preview_frame() && '_not_customized_' != $_customized_val ) {
                return $_customized_val;
            } else {
                if ( hu_is_customize_preview_frame() ) {
                    $cust_skope = $this -> ha_get_current_customized_skope();
                    $_new_val = $this -> ha_get_preview_inherited_val( $opt_name, $_opt_val, $cust_skope );
                } else {
                    $skop_opts = array(
                        'local'         => $this -> ha_get_cached_opt( 'local', $opt_name ),
                        'group'         => $this -> ha_get_cached_opt( 'group', $opt_name ),
                        'special_group' => $this -> ha_get_cached_opt( 'special_group', $opt_name ),
                        'global'        => $_opt_val
                    );

                    //priority
                    //loop until a value is found
                    //skip the global skope
                    $_found_val = false;
                    foreach ( $skop_opts as $_key => $_value) {
                        if ( 'global' == $_key || $_found_val )
                          continue;
                        //cast to array. Ex : header_image_data might be saved as an object
                        $_value = is_object( $_value ) ? (array)$_value : $_value;

                        if ( is_array($_value) || '_no_set_' != (string)$_value ) {
                          $_new_val = $_value;
                          $_found_val = true;
                        }
                    }
                }

            }
            //falls back to global
            return $_new_val;
        }








        /*****************************************************
        * GET VALUES WHEN PREVIEWING : APPLY INHERITANCE
        *****************************************************/
        //recursive method
        //apply the skope inheritance to return the relevant value
        private function ha_get_preview_inherited_val( $opt_name, $original_opt_val, $skope ) {
            $skop_opt_val = $this -> ha_get_cached_opt( $skope, $opt_name );
            //cast to array if the saved option is an object. For Ex : header_image_data can be an object
            $skop_opt_val = 'object' == gettype( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;
            if ( is_array($skop_opt_val) ||  '_no_set_' != (string)$skop_opt_val )
              return $skop_opt_val;

            if ( 'global' == $skope )
              return $original_opt_val;

            $parent_skope = $this -> ha_get_parent_skope_name( $skope );
            return $this -> ha_get_preview_inherited_val( $opt_name, $original_opt_val, $parent_skope );

        }


        private function ha_get_parent_skope_name( $skope, $_index = null ) {
            $hierark = array( 'local', 'group', 'special_group', 'global' );
            $parent_ind = -1;
            //get the parent index
            foreach( $hierark as $_key => $_skp ) {
                if ( $skope == $_skp && -1 == $parent_ind )
                  $parent_ind = intval( $_key + 1 ) ;
                continue;
            }
            return isset( $hierark[$parent_ind] ) ? $hierark[$parent_ind] : 'global';
        }






        /*****************************************************
        * GET CUSTOMIZED VALUE
        *****************************************************/
        //@return bool
        //check if a theme option or a wp option is dirty customized
        //important the $opt_name param is always the short option name, for example
        // hueman option ex : use-header-image => when customizing, this option will be hu_theme_options[use-header-image]
        // wp option ex1 : blogdescription => unchanged name when customizing, because not multidimensional
        // wp option ex2 : sidebars_widgets => is multidimensional and will be like sidebars_widgets[primary]
        //
        // Consequently :
        // 1) we first check if each customized options contain a key == opt_name
        // 2) then we check if each customized option is multidimensional and starts with the provided opt_name
        function ha_get_customized_value( $opt_name ) {
            //return '_not_customized_';
            if ( ! hu_is_customize_preview_frame() )
              return '_not_customized_';

            $_customized_val  = '_not_customized_';
            global $wp_customize;

            $post_values = $wp_customize->unsanitized_post_values();

            //make sure post values is always an array
            if ( false === $post_values || ! is_array($post_values) )
              $post_values = array();

            // if ( empty( $post_values ) )
            //   return '_not_customized_';

            //let's set the setting_id to the opt_name by default
            $setting_id = $opt_name;

            //WP BUIT-IN SETTING NOT MULTIDIMENSIONAL CASE
            if ( array_key_exists( $opt_name, $post_values ) && is_object( $wp_customize -> get_setting( $opt_name ) ) )
              $_customized_val = $wp_customize -> post_value( $wp_customize -> get_setting( $opt_name ) );

            //THEME SETTING CASE
            //let's build the option name as it in the customizer
            $_theme_option_prefix = strtolower(HU_THEME_OPTIONS);
            $_theme_opt_name = "{$_theme_option_prefix}[{$opt_name}]";
            if ( array_key_exists( $_theme_opt_name, $post_values ) && is_object( $wp_customize -> get_setting( $_theme_opt_name ) ) )
              $_customized_val = $wp_customize -> post_value( $wp_customize -> get_setting( $_theme_opt_name ) );

            //MULTIDIMENSIONAL CASE
            //If we still have no match at this stage, $_customized_val == '_not_customized_'
            //Loop all the registered settings
            //then for each setting, check if it's multidimensional and get its id base
            //then try to find a match in the unsanitized post values sent by $_POST request
            $found_match = false;
            if ( '_not_customized_' == $_customized_val ) {
                foreach ( $wp_customize -> settings() as $_setting ) {
                    if ( $found_match )
                      continue;

                    $id_data = $_setting -> id_data();
                    $is_multidimensional = ! empty( $id_data['keys'] );
                    $setting_id = $_setting -> id;

                    //already covered before but let's do it again. To be removed.
                    if ( ! $is_multidimensional && $_setting -> id == $opt_name && array_key_exists( $_setting -> id, $post_values ) ) {
                        $_customized_val = $post_values[$_setting -> id];
                        $found_match = true;
                    }
                    else if ( $is_multidimensional && $opt_name == $id_data['base'] ) {
                        $f_match = false;
                        foreach ( $post_values as $_cust_opt_name => $val ) {
                          //try to find a prefix match option. For ex : sidebars_widgets
                          if ( $opt_name != substr($_cust_opt_name, 0, strlen( $opt_name ) ) || $f_match )
                            continue;
                          // $_customized_val = apply_filters( "ha_customize_val_before_multidimensional_filter_{$opt_name}", $_customized_val, $opt_name );
                          // if ( '_not_customized_' == $_customized_val )
                          //   continue;
                          $_customized_val = $_setting -> _multidimensional_preview_filter( $val );
                          $f_match = true;
                        }
                    }
                }//foreach
            }//if

            //apply WP default checks
            // if ( '_not_customized_' == $_customized_val )
            //   return $_customized_val;
            // $valid = $wp_customize ->get_setting( $setting_id ) -> validate( $_customized_val );
            // if ( is_wp_error( $valid ) ) {
            //   wp_send_json_error( 'Invalid value for setting ' . $setting_id );
            //   return '_not_customized_';
            // }
            // $value = $wp_customize ->get_setting( $setting_id ) ->sanitize( $_customized_val );
            // if ( is_null( $value ) || is_wp_error( $value ) ) {
            //   wp_send_json_error( 'null or not passed the sanitize tests setting ' . $setting_id );
            //   return '_not_customized_';
            // }

            //APPLY A SPECIAL TREATMENT
            return apply_filters( "ha_get_customize_val_{$opt_name}", $_customized_val, $opt_name );
        }














        /******************************************************
        * HELPERS
        *******************************************************/
        //@return the name of the option as a string for a given skope
        function ha_get_skope_opt_name( $level = 'local', $special = '' ) {
            $name = '';
            switch ($level) {
              case 'local':
                $name = strtolower( THEMENAME . '_czr_' . ha_get_skope() );
                break;
              case 'group' :
                if ( ! empty( ha_get_skope('type') ) )
                  $name = strtolower( THEMENAME . '_czr_all_' . ha_get_skope('type') );
                break;
              case 'special_group' :
                $name = strtolower( THEMENAME . '_czr_all_' . ha_get_skope('type') . $special );
                break;
              case 'global':
                $name = HU_THEME_OPTIONS;
                break;
            }
            return $name;
        }

        //@return the current posted skope string
        //falls back on 'global'
        function ha_get_current_customized_skope() {
            if ( '__no_posted__' ==  $this -> ha_get_sanitized_post_value( 'skope' ) )
              return 'global';
            return $this -> ha_get_sanitized_post_value( 'skope' );
        }


        //@return a sanitized esc_attr() value from the $_POST array
        function ha_get_sanitized_post_value( $param ) {
            return isset($_POST[$param]) ? esc_attr( $_POST[$param ] ) : '__no_posted__';
        }


        function ha_can_have_meta_opt( $meta_type ) {
            return in_array(
              $meta_type,
              array('post', 'tax', 'user')
            );
        }


        function ha_can_have_trans_opt( $meta_type ) {
            return in_array(
              $meta_type,
              array('home', 'search', '404', 'date')
            );
        }


        //Write the new skope option in db
        //it can be a transient, post_meta, term_meta or user_meta
        function ha_set_skope_option_val( $opt_name, $new_value, $db_option_name = null, $type = null, $obj_id = null ) {
            if ( empty($opt_name) || is_null($db_option_name ) || is_null($type) )
              return;

            $current_val = false;
            switch ($type) {
              case 'trans':
                $_val = get_transient( $opt_name );
                $_val = $this -> ha_preprocess_skope_val( $new_value, $opt_name, $_val );
                set_transient( $opt_name, $_val, 60*24*365*100 );
              break;

              case 'post_meta':
                if ( is_null( $obj_id ) )
                  return;
                $_val = get_post_meta( $obj_id , $db_option_name, true );
                $_val = $this -> ha_preprocess_skope_val( $new_value, $opt_name, $_val );
                update_post_meta( $obj_id , $db_option_name, $_val );
              break;

              case 'term_meta':
                if ( is_null( $obj_id ) )
                  return;
                $_val = get_term_meta( $obj_id , $db_option_name, true );
                $_val = $this -> ha_preprocess_skope_val( $new_value, $opt_name, $_val );
                update_term_meta( $obj_id , $db_option_name, $_val );
              break;

              case 'user_meta':
                if ( is_null( $obj_id ) )
                  return;
                $_val = get_user_meta( $obj_id , $db_option_name, true );
                $_val = $this -> ha_preprocess_skope_val( $new_value, $opt_name, $_val );
                update_user_meta( $obj_id , $db_option_name, $_val );
              break;
            }
        }

        //@return updated option associative array( opt_name1 => value 1, opt_name2 => value2, ... )
        function ha_preprocess_skope_val( $new_value, $opt_name, $current_value ) {
              if ( ! $current_value || ! is_array($current_value) ) {
                $to_return = array( $opt_name => $new_value );
              } else {
                $to_return = $current_value;
                $to_return[$opt_name] = $new_value;
              }
              return $to_return;
        }

    }//class
endif;