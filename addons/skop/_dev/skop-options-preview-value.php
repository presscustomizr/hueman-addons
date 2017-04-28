<?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option_Preview' ) ) :
    class HA_Skop_Option_Preview extends HA_Skop_Option_Front {
        //CACHE PROPERTIES
        public $_skope_preview_values = array();
        public $all_skopes_customized_values = array();



        /* ------------------------------------------------------------------------- *
         *  GET CUSTOMIZER PREVIEW VAL
        /* ------------------------------------------------------------------------- */

        function _get_sanitized_preview_val( $_opt_val , $opt_name ) {
            $rev_index        = $this -> ha_get_sanitized_post_value( 'revisionIndex' );
            $rev_index        = '__not_posted__' == $rev_index ? 'initial' : $rev_index;
            $cache_customized = $this -> _skope_preview_values;
            $preview_val      = '';

            ////////////////////////////////////////////////////////////////
            // if ( 'use-header-image' === $opt_name ) {
            //   if ( isset( $cache_customized[$rev_index] ) && is_array( $cache_customized[$rev_index]) && array_key_exists( $opt_name, $cache_customized[$rev_index] ) ) {
            //     ha_error_log( '/////////CACHED PREVIEW VAL = ' . $cache_customized[$rev_index][$opt_name] );
            //   }
            // }


            //Bail here if we already have a cached set of customized values for this skope
            if ( isset( $cache_customized[$rev_index] ) && is_array( $cache_customized[$rev_index]) && array_key_exists( $opt_name, $cache_customized[$rev_index] ) )
              return $cache_customized[$rev_index][$opt_name];

            if ( ! isset( $cache_customized[$rev_index] ) || ! is_array( $cache_customized[$rev_index] ) )
              $cache_customized = array( $rev_index => array( $opt_name => '' ) );
            else {
              if ( ! array_key_exists( $opt_name, $cache_customized[$rev_index] ) )
                $cache_customized[$rev_index][$opt_name] = '';
            }

            if ( _ha_is_wp_setting_multidimensional( $opt_name ) ) {
              $preview_val = $this -> _get_multidim_sanitized_preview_val( $_opt_val, $opt_name );
            } else {
              $preview_val = $this -> _get_simple_sanitized_preview_val( $_opt_val, $opt_name );
            }


            //cache the value now but make sure the skope_id has been posted before
            if ( '__not_posted__' != $this -> ha_get_sanitized_post_value( 'skope_id' ) ) {
                $cache_customized[$rev_index][$opt_name] = $preview_val;
                $this -> _skope_preview_values = $cache_customized;
            } else {
                ha_error_log( 'preview val not_cached_yet => the skope_id was not posted' );
            }

            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   ha_error_log( print_R( $preview_val, true ) );
            //   //ha_error_log( print_R( $_skope_customized_val, true ) );
            //   //ha_error_log(  print_R( $_candidate_val, true ) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            //   //ha_error_log( 'CUSTOMIZED VAL FOR ' . $skope_id);
            //   // if ( 'group_all_page' == $skope_id ) {
            //   //   ha_error_log( print_R( $this -> ha_get_unsanitized_customized_values( $skope_id ), true ) );
            //   // }
            //   //ha_error_log( print_R( $_POST, true ) );
            //   // ha_error_log( '///////////////// START PREVIEW VAL');
            //   // ha_error_log( $preview_val );
            //   // ha_error_log( '///////////////// END PREVIEW VAL');
            //   // ha_error_log( is_bool( $val_candidate ) );
            //   // ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }
            return $preview_val;
        }


        //Performs the following actions to find a match
        //1) Return the original value if previewing the global skope => WP has already done the job
        //2) Check if the current setting has dirties in the current skope
        //3) Then check if there's a db saved meta val for this setting in the current skope
        //4) Then check if there's a customized inheritance value for this setting from parent skope(s)
        //5) Then check if there's a saved value to inherit from parents
        //6) Falls back on the default setting val
        function _get_simple_sanitized_preview_val( $_original_val , $opt_name, $skope_id = null, $child_value = null ) {
            $val_candidate            = is_null( $child_value ) ? '_not_customized_' : $child_value;
            $skope_id                 =  is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            $skope_level              = $this -> ha_get_skope_level( $skope_id );
            $_skope_customized_val    = $this -> _ha_get_simple_sanitized_customized_value( $opt_name, $skope_id, $_original_val );
            $_skope_db_meta_val       = $this -> _get_front_end_val( $_original_val, $opt_name, $skope_level );


            if ( $this -> _is_value_customized( $_skope_customized_val ) ) {
              $val_candidate = $_skope_customized_val;
            } else {
              $val_candidate = $_skope_db_meta_val;
            }
            ////////////////////////////////////////////////////////////////
            // if ( 'use-header-image' === $opt_name ) {
            //   ha_error_log( 'PREVIEW VAL FOR SKOPE ' . $skope_id);
            //   // if ( 'group_all_page' == $skope_id ) {
            //   //   ha_error_log( print_R( $this -> ha_get_unsanitized_customized_values( $skope_id ), true ) );
            //   // }
            //   // ha_error_log( '$_skope_db_meta_val : ' . $_skope_db_meta_val);
            //   // ha_error_log( print_R( $_skope_customized_val, true ) );
            //   // ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            //   // ha_error_log( is_bool( $val_candidate ) );
            //   // ha_error_log( 'val candidate : ' . $val_candidate);
            //   // ha_error_log( 'MMMMMMMEEDE : ' . ( $val_candidate !== '_no_set_' &&  $val_candidate !== '_not_customized_' ) );
            // }

            ////////////////////////////////////////////////////////////////
            // if ( 'header_image' === $opt_name ) {
            //   ha_error_log( 'SAVED VAL ' . $skope_id);
            //   ha_error_log( print_R( $_skope_db_meta_val, true ) );
            //   ha_error_log( $val_candidate );
            //   ha_error_log( in_array( $val_candidate, array( '_no_set_', '_not_customized_' ) ) );
            // }

            if ( is_bool( $val_candidate ) )
              return $val_candidate;

            if ( $val_candidate !== '_no_set_' &&  $val_candidate !== '_not_customized_' )
              return $val_candidate;

            $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
            //ha_error_log('ALORS PARENT SKOPE ID : ' . $parent_skope_id );
            //if a parent exists, let's get attempt to merge any additional inherited value
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $val_candidate = $this -> _get_simple_sanitized_preview_val( $_original_val, $opt_name, $parent_skope_id, $val_candidate );
            }

            //falls back on original value
            if ( $val_candidate === '_no_set_' || $val_candidate === '_not_customized_' )
                $val_candidate = $_original_val;

            ////////////////////////////////////////////////////////////////
            // if ( 'use-header-image' === $opt_name ) {
            //   ha_error_log( 'FINAL VAL ? ' . $skope_id);
            //   ha_error_log( print_R( $val_candidate, true ) );
            // }

            return $val_candidate;
        }



        /* ------------------------------------------------------------------------- *
        * GET CUSTOMIZED VALUE FOR A SINGLE SETTING: APPLY INHERITANCE
        /* ------------------------------------------------------------------------- */
        // This method should get the customized val for a given setting.
        // If the requested skope has no customized val, then we should try to get the parent customized val
        // and so on until we reach the global skope.
        // => just like we do in the js api.
        //
        // The customized values have to be grabbed with a method like $wp_customize -> unsanitized_post_values()
        // But we need to pass the skope param to actually retrieve the relevant set of customized values.
        //
        //
        // hueman option ex : use-header-image => when customizing, this option will be hu_theme_options[use-header-image]
        // wp option ex1 : blogdescription => unchanged name when customizing, because not multidimensional
        // wp option ex2 : sidebars_widgets => is multidimensional and will be like sidebars_widgets[primary]
        //
        // Consequently :
        // 1) we first check if each customized options contain a key == opt_name
        // 2) then we check if each customized option is multidimensional and starts with the provided opt_name
        //
        // The values are
        function _ha_get_simple_sanitized_customized_value( $opt_name, $skope_id = null , $_original_val = null, $do_inherit = false ) {
            // if ( 'global' == $this -> ha_get_current_customized_skope() )
            //   return $_original_val;

            $_customized_val  = '_not_customized_';
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return $_original_val;

            global $wp_customize;
            $skope_id           = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;

            //Returns an array merging the $_POST customized and the changeset values
            //=> this should already be server side ready for WP options and theme mods
            //like :
            //blogname => 'A blog name'
            //nav_menu_locations => array(
            //  'header' => 2,
            //  'footer' => 4
            //)
            //For the theme options, it should return the full name options keys like : hu_theme_options[site-description] => 1
            // if ( 'header_image' == $opt_name ) {
            //     ha_error_log( '///////////REQUESTED SKOPE ID' . $skope_id );
            //     ha_error_log( print_R( $this -> ha_get_unsanitized_customized_values( $skope_id ), true ) );

            // }

            $_customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );

            //make sure customized values is always an array
            if ( false === $_customized_values || ! is_array( $_customized_values ) )
              $_customized_values = array();

            //let's set the setting_id to the opt_name by default
            $setting_id   = $opt_name;

            //WP BUIT-IN SETTING NOT MULTIDIMENSIONAL CASE
            if ( array_key_exists( $opt_name, $_customized_values ) && is_object( $wp_customize -> get_setting( $opt_name ) ) ) {
                $_customized_val = $this -> _get_setting_sanitized_skoped_customized_value( $skope_id, $wp_customize -> get_setting( $opt_name ), $do_inherit );// validates and sanitizes the value
            }


            //THEME SETTING CASE
            //If this is needed, let's build the option name as it in the customizer
            $_theme_option_prefix = strtolower( HU_THEME_OPTIONS );
            if ( $_theme_option_prefix !== substr( $opt_name, 0, strlen($_theme_option_prefix) ) ) {
                $_theme_opt_name_candidate = "{$_theme_option_prefix}[{$opt_name}]";
            } else {
                $_theme_opt_name_candidate = $opt_name;
            }
            $_theme_opt_name_candidate = "{$_theme_option_prefix}[{$opt_name}]";

            if ( is_object( $wp_customize -> get_setting( $_theme_opt_name_candidate ) ) ) {
                $_customized_val = $this -> _get_setting_sanitized_skoped_customized_value( $skope_id, $wp_customize -> get_setting( $_theme_opt_name_candidate ), $do_inherit );// validates and sanitizes the value
            }

            //apply WP default checks
            // if ( '_not_customized_' == $_customized_val )
            //   return $_customized_val;
            // $valid = $wp_customize ->get_setting( $setting_id ) -> validate( $_customized_val );
            // if ( is_wp_error( $valid ) ) {
            //   wp_send_json_error( 'Invalid value for setting ' . $setting_id );
            //   return '_not_customized_';
            // }
            // $_original_val = $wp_customize ->get_setting( $setting_id ) ->sanitize( $_customized_val );
            // if ( is_null( $_original_val ) || is_wp_error( $_original_val ) ) {
            //   wp_send_json_error( 'null or not passed the sanitize tests setting ' . $setting_id );
            //   return '_not_customized_';
            // }
            //


            //MAYBE APPLY A SPECIAL TREATMENT
            return apply_filters( "ha_get_customize_val_{$opt_name}", $_customized_val, $opt_name );
        }







        /* ------------------------------------------------------------------------- *
        * GET SKOPE CUSTOMIZED VALUES
        /* ------------------------------------------------------------------------- */
        //When changeset is enabled @return array() merging the changeset and the $_POST customized values
        //If changeset not enabled, @return array() of $_POST customized values
        function ha_get_unsanitized_customized_values( $skope_id = null ) {
            //return array();
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();

            //make sure the skope is specified at this stage.
            $skope_id = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            if ( is_null( $skope_id ) )
              return array();

            global $wp_customize;

            $customized_values = array();

            $rev_index = $this -> ha_get_sanitized_post_value( 'revisionIndex' );
            $rev_index = '__not_posted__' == $rev_index ? 'initial' : $rev_index;
            $cache_customized = $this -> all_skopes_customized_values;

            //Bail here if we already have a cached set of customized values for this skope
            if ( isset( $cache_customized[$rev_index] ) && is_array( $cache_customized[$rev_index]) && array_key_exists( $skope_id, $cache_customized[$rev_index] ) )
              return $cache_customized[$rev_index][$skope_id];

            if ( ! isset( $cache_customized[$rev_index] ) || ! is_array( $cache_customized[$rev_index] ) )
              $cache_customized = array( $rev_index => array( $skope_id => array() ) );
            else {
              if ( ! array_key_exists( $skope_id, $cache_customized[$rev_index] ) )
                $cache_customized[$rev_index][$skope_id] = array();
            }

            //Falls back on WP customized values if 'global' requested AND current skope
            //=> important to check if requested because there are case when we need to get the global customized values from another skope!
            if ( 'global' == $this -> ha_get_skope_level( $skope_id ) || 'global' == $this -> ha_get_current_customized_skope() ) {
                $customized_values = $wp_customize -> unsanitized_post_values();
            } else {
                //Build the customized set for this skope based on the changeset + the posted values
                $skope_changeset_data = $this -> _get_unsanitized_skope_changeset_values( $skope_id );
                $skope_post_values    = $this -> _get_unsanitized_skope_posted_values( $skope_id );
                $customized_values    = array_merge( $skope_changeset_data, $skope_post_values );
            }

            //cache the value now but make sure the skope_id has been posted before
            if ( '__not_posted__' != $this -> ha_get_sanitized_post_value( 'skope_id' ) ) {
              $cache_customized[$rev_index][$skope_id] = $customized_values;
              $this -> all_skopes_customized_values = $cache_customized;
            } else {
              ha_error_log( 'all skopes customized values not_cached_yet => the skope_id was not posted' );
            }

            // ha_error_log( '///// POSTED ?' . $skope_id );
            // ha_error_log( print_R( $_POST, true ) );

            // ha_error_log( '///// $this -> all_skopes_customized_values ( current SKOPE  is : ' . $skope_id . ' )');
            // ha_error_log( print_R( $this -> all_skopes_customized_values, true ) );
            // ha_error_log( print_R( $cache_customized[$rev_index][$skope_id], true ) );

            // ha_error_log( '///// GET CUSTOMIZED VALUES DATA FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $this -> _get_unsanitized_skope_posted_values( $skope_id ), true ) );
            return $customized_values;
        }




















        //This method should return the changeset data ready to be parsed as options.
        //the changeset_data look like this for each setting saved
        //Array
        // (
        //     hu_theme_options[copyright] => Array
        //         (
        //             [value] => Copyright
        //             [type] => option
        //         )
        // )
        // Should become : hu_theme_options[copyright] => copyright
        //
        // Theme mods like nav_menu_locations are stored as :
        // hueman::nav_menu_locations[footer] = array( 'value' => 2, 'type' => 'theme_mod', 'user_id' => 1 )
        // hueman::nav_menu_locations[topbar] = array( 'value' => 4, 'type' => 'theme_mod', 'user_id' => 1 )
        //
        // Should become :
        // nav_menu_locations => Array
        // (
        //     [footer] => 2
        //     [topbar] => 4
        // )
        function _get_unsanitized_skope_changeset_values( $skope_id ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() || ! HU_AD() -> ha_is_changeset_enabled() )
              return array();

            global $wp_customize;
            $values = array();

            //GET THE RAW CHANGESET FOR THE PROVIDED SKOPE_ID
            $skope_meta_key    = $this -> _get_posted_skope_metakey( $skope_id );
            if ( false == $skope_meta_key ) {
              ha_error_log( 'no meta key found in _get_unsanitized_skope_changeset_values() for skope : ' . $skope_id );
              return array();
            }
            $changeset_post_id = $wp_customize -> changeset_post_id();
            $changeset_data = ha_get_skope_db_data(
                array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    //the level must be specify when we're getting the 'global' level
                    'level' => $this -> ha_get_skope_level( $skope_id )
                )
            );

            if ( is_wp_error( $changeset_data ) ) {
                ha_error_log( 'Error when trying to get the changeset data in _get_unsanitized_skope_changeset_values() for skope : ' . $skope_id );
                $changeset_data = array();
            }

            foreach ( $changeset_data as $raw_setting_id => $setting_data ) {
                if ( ! is_array( $setting_data ) || ! array_key_exists( 'value', $setting_data ) ) {
                  ha_error_log( 'Problem in _get_unsanitized_skope_changeset_values, the setting_data of the changeset are not well formed for skope : ' . $skope_id );
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
                $values[$setting_id] = $setting_data['value'];
            }

            // ha_error_log( '/////////////// CHANGESET BEFORE ');
            // ha_error_log( print_R( $changeset_data, true ) );
            // ha_error_log( '/////////////// CHANGESET AFTER ');
            // ha_error_log( print_R( ha_prepare_skope_changeset_for_front_end( $changeset_data ), true ) );
            return $values;
        }




        //@param skope_id = $this -> ha_get_sanitized_post_value( 'skope_id' )
        //@return the posted values in $_POST['skopeCustomized'][$skope_id]
        function _get_unsanitized_skope_posted_values( $skope_id = null ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();

            global $wp_customize;
            $skopes_customized_values = array();
            $post_values = array();
            //if a skope id is specified
            if ( ! is_null( $skope_id ) ) {
                if ( ! isset( $_POST['skopeCustomized'] ) || ! isset( $_POST['skope'] ) ) {
                    $post_values = array();
                } else {
                  $skopes_customized_values = json_decode( wp_unslash( $_POST['skopeCustomized'] ), true );
                }

                //did we already cache the _skope_post_values ?
                if ( ! isset( $wp_customize -> _current_skope_id ) ) {
                    $wp_customize -> _current_skope_id = $skope_id;
                }

                //did we already cache the _skope_post_values ?
                //OR
                //did the current skope id change ?
                //if so let's cache or re-cache the skope_post_values
                if ( ! isset( $wp_customize -> _skope_post_values ) || $wp_customize -> _current_skope_id != $skope_id ) {
                    //add the _skope_post_values as a property of the manager if not set yet
                    if ( is_array( $skopes_customized_values ) ) {
                        $wp_customize -> _skope_post_values = $skopes_customized_values;
                    } else {
                        $wp_customize -> _skope_post_values = array();
                    }
                }

                $skopes_customized_values = $wp_customize -> _skope_post_values;

                if ( ! is_array( $skopes_customized_values ) || ! isset( $skopes_customized_values[$skope_id]) ) {
                    $post_values = array();
                } else {
                    $post_values = $skopes_customized_values[$skope_id];
                }
            }
            //if no skope_id specified, let's fall back on the regular way
            else {
                if ( isset( $_POST['customized'] ) ) {
                    $post_values = json_decode( wp_unslash( $_POST['customized'] ), true );
                } else {
                    $post_values = array();
                }
            }
            return $post_values;
        }





        //@the equivalent of post_value() for skope
        //recursive : implements the skope inheritance
        //@param $skope_id string
        //@param $setting = setting instance object
        //@default = default setting value ( string, boolean, array )
        function _get_setting_sanitized_skoped_customized_value( $skope_id = null, $setting, $do_inherit = false ) {
            $_candidate_val    = '_not_customized_';
            $parent_skope_id   = '';
            $skope_id          = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;

            if ( false == $skope_id || is_null($skope_id) || empty($skope_id) ) {
              ha_error_log( 'missing skope_id in _get_setting_sanitized_skoped_customized_value()' );
              return $_candidate_val;
            }

            $customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );
            // if ( 'hu_theme_options[copyright]' == $setting -> id ) {
            //   ha_error_log( 'IN GET SETTING SANITIZED SKOPED CUSTOMIZED VALUE' . $setting -> id );
            //   ha_error_log( print_R($customized_values,TRUE) );
            // }

            if ( array_key_exists( $setting -> id, $customized_values ) ) {
                $_candidate_val = $customized_values[ $setting->id ];
                $valid = $setting->validate( $_candidate_val );
                if ( is_wp_error( $valid ) ) {
                    return $_candidate_val;
                }
                $_candidate_val = $setting -> sanitize( $_candidate_val );
            }
            //no customized value for this post, so let's recursively get the parent dirtyness
            else if ( $do_inherit ) {
                $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );

                //if no parent, then we've reach the global level
                //if a parent exists, let's get it's customized value
                if ( false !== $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                    //@param : recursive call, inheritance set to true
                    $_candidate_val = $this -> _get_setting_sanitized_skoped_customized_value( $parent_skope_id , $setting, true );
                }
            }

            return ( is_null( $_candidate_val ) || is_wp_error( $_candidate_val ) ) ? '_not_customized_' : $_candidate_val;
        }



        //@param $requested_skope_id : string
        //@param $requested_skope_level : string
        //Given a requested $skope_id, return its parent if exists, based on :
        //1) the $_POST['current_skopes'] array()
        //2) the skope hierarchy : 'local' < 'group' < 'special_group' < 'global'
        //
        //The $_POST[current_skopes] is an array( 'level_1' => 'id_1', ... ) like :
        // $current_skopes => Array
        // (
        //     'global' => array( id => global__all_, opt_name =>  hu_theme_options )
        //     'group' => array( id => group_all_post, opt_name =>   hueman_czr_all_post )
        //     'local' => array( id => local_post_post_1, opt_name =>  hueman_czr_post_post_1 )
        // )
        //Can be recursive when some level(s) does not exists in the current set of skopes
        function _get_parent_skope_id( $requested_skope_id = null, $requested_skope_level = null ) {
            $parent_id = '';
            $_posted_skopes = $this -> _get_posted_skopes();

            if ( ! is_array( $_posted_skopes ) )
              return;

            if ( 'global' === $requested_skope_level ) {
              return isset($_posted_skopes['global']['id']) ? $_posted_skopes['global']['id'] : false;
            }

            //let's determine the requested skope level if not requested
            if ( is_null( $requested_skope_level ) ) {
              foreach ( $_posted_skopes as $skp_level => $data ) {
                if ( ! isset( $data['id'] ) )
                  continue;

                if ( $requested_skope_id == $data['id'] )
                  $requested_skope_level = $skp_level;
              }
              if ( ! isset( $requested_skope_level ) )
                return;
            }


            $hierark = array( 'local', 'group', 'special_group', 'global' );
            $parent_ind = -1;
            //get the parent index
            foreach( $hierark as $_key => $_skp_levl ) {
                if ( $requested_skope_level == $_skp_levl && -1 == $parent_ind )
                  $parent_ind = intval( $_key + 1 ) ;
                continue;
            }

            $parent_skop_level = isset( $hierark[$parent_ind] ) ? $hierark[$parent_ind] : '_reached_the_roof_';
            if ( '_reached_the_roof_' == $parent_skop_level || $parent_ind >= count( $hierark ) )
              return '_reached_the_roof_';
            else {
              if ( ! isset( $_posted_skopes[$parent_skop_level] ) ) {
                //we're typically in the case where we did not reached the roof and the level does not exists in the current set of skopes
                //For example, on home, there's no 'group' and 'special_group'
                //let's recursively solve this, this time by specifying a level
                $grand_parent_level = intval( $parent_ind + 1 );
                return $this -> _get_parent_skope_id( null, $hierark[$grand_parent_level] );
              } else {
                return isset($_posted_skopes[$parent_skop_level]['id']) ? $_posted_skopes[$parent_skop_level]['id'] : false;
              }
            }

        }


        //@return the array of the current set of posted skopes
        function _get_posted_skopes() {
          if ( ! isset( $_POST['current_skopes'] ) )
              return;
          return json_decode( wp_unslash( $_POST['current_skopes']), true );
        }



        //@return a skope db meta from the $_POST['current_skopes'], given its given its id
        function _get_posted_skope_metakey( $skope_id ) {
          if ( ! isset( $_POST['current_skopes'] ) )
              return;

          $meta_key = false;
          $posted_skopes = $this -> _get_posted_skopes();
          foreach ( $posted_skopes as $key => $data ) {
            if ( isset( $data['id'] ) && $skope_id == $data['id'] )
              $meta_key = isset( $data['opt_name'] ) ? $data['opt_name'] : false;
            continue;
          }
          return ( ! empty( $meta_key ) && is_string( $meta_key ) ) ? $meta_key : false;
        }




















        /* ------------------------------------------------------------------------- *
        * HELPERS
        /* ------------------------------------------------------------------------- */
        public function _is_value_customized( $value ) {
            //cast to array if needed
            $value = is_object($value) ? (array)$value : $value;
            //some multidimensional settings can be array, and therefore not be compared with a '_not_customized_' string
            if ( is_array($value) )
              return true;

            return ( is_string($value) && '_not_customized_' == $value ) ? false : true;
        }


        //@return the name of the option as a string for a given skope
        function ha_get_skope_opt_name( $level = 'local', $special = '' ) {
            $name = '__not_available__';
            $skp_type = ha_get_skope('type');
            $theme_name = ha_get_skope_theme_name();
            switch ($level) {
              case 'local':
                $name = strtolower( $theme_name . '_czr_' . ha_get_skope() );
                break;
              case 'group' :
                if ( ! empty( $skp_type ) )
                  $name = strtolower( $theme_name . '_czr_all_' . $skp_type );
                break;
              case 'special_group' :
                $name = strtolower( $theme_name . '_czr_all_' . $skp_type . $special );
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
            if ( '__not_posted__' ==  $this -> ha_get_sanitized_post_value( 'skope' ) )
              return 'global';
            return $this -> ha_get_sanitized_post_value( 'skope' );
        }

        //@return the level ( local, group, special_group, global ) for a given skope id
        function ha_get_skope_level( $skope_id ) {
            $_posted_skopes = $this -> _get_posted_skopes();
            $skope_level = false;
            if ( ! is_array( $_posted_skopes ) )
              return;
            foreach ( $_posted_skopes as $skp_level => $data ) {
              if ( ! isset( $data['id'] ) && false !== $skope_level )
                continue;

              if ( $skope_id == $data['id'] )
                $skope_level = $skp_level;
            }
            return $skope_level;
        }

        //@return a sanitized esc_attr() value from the $_POST array
        function ha_get_sanitized_post_value( $param ) {
            return isset($_POST[$param]) ? esc_attr( $_POST[$param ] ) : '__not_posted__';
        }

        //@return updated option associative array( opt_name1 => value 1, opt_name2 => value2, ... )
        public function ha_preprocess_skope_val( $new_value, $opt_name, $current_value ) {
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

?>