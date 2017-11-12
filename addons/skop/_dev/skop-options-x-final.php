<?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option' ) ) :
    final class HA_Skop_Option extends HA_Skop_Option_Preview {


        public static function ha_skop_opt_instance() {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof HA_Skop_Option ) )
              self::$instance = new HA_Skop_Option();
            return self::$instance;
        }


        //GET MULTIDIMENSIONAL PREVIEW VAL
        //For each settings aggregated in a multidimensional option,
        //We need to perform the following actions :
        function _get_multidim_sanitized_preview_val( $_original_val , $opt_name , $skope_id = null, $child_value = null ) {
            $child_value              = ( is_null( $child_value ) || ! is_array( $child_value ) ) ? array() : $child_value;
            $_candidate_val           = $child_value;

            //let's set the setting_id to the opt_name by default
            $setting_id   = $opt_name;
            $skope_id     =  is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            if ( '__not_posted__' == $skope_id ) {
              ha_error_log('in _get_multidim_preview_val SKOPE NOT POSTED');
              return $_candidate_val;
            }
            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   //ha_error_log( 'MULTIDIM PREVIEW VAL FOR SKOPE ' . $skope_id);
            //   //ha_error_log( print_R( $_skope_customized_val, true ) );
            //   //ha_error_log(  print_R( $_candidate_val, true ) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }
            // ha_error_log( 'CHILD VALUE FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_candidate_val, true ) );

            $cust_skope               = $this -> ha_get_skope_level( $skope_id );
            $_skope_customized_val    = $this -> _get_wp_multidim_sanitized_customized_val( $opt_name, $skope_id, false );
            $_skope_db_meta_val       = $this -> _get_wp_multidim_saved_val( $opt_name, $skope_id, false );
            //$preview_val              = array();
            //1) get customized val of the opt_name for the current skope, without inheritance.
            //=> we get a customized_values arrays
            //2) then merge this array with the saved DB values for this skope.

            //BUILD THE FIRST SET OF VALUES WITH CURRENT SKOPE CUSTOMIZED VAL MERGED WITH CURRENT SKOPE SAVED VAL
            $_skope_customized_val    = ! is_array( $_skope_customized_val ) ? array() : $_skope_customized_val;
            $_skope_db_meta_val       = ! is_array( $_skope_db_meta_val ) ? array() : $_skope_db_meta_val;
            $_skope_val               = wp_parse_args( $_skope_customized_val, $_skope_db_meta_val );

            // ha_error_log( 'MULTIDIM CUSTOMIZED VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_skope_customized_val, true ) );

            // ha_error_log( 'MULTIDIM SAVED VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_skope_db_meta_val, true ) );

            // ha_error_log( 'MULTIDIM SKOPE VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_skope_val, true ) );
            //Child value shall overwrite the parent
            $_candidate_val           = wp_parse_args( $_candidate_val, $_skope_val );

            $parent_skope_id          = $this -> _get_parent_skope_id( $skope_id );
            //ha_error_log('ALORS PARENT SKOPE ID : ' . $parent_skope_id );
            //if a parent exists, let's get attempt to merge any additional inherited value
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $_candidate_val       = $this -> _get_multidim_sanitized_preview_val( $_original_val, $opt_name, $parent_skope_id, $_candidate_val );
            }

            //Fall back on the original val ( => should be the same as the one of the global skope ) if we still did not get anything at this stage
            if ( is_null( $_candidate_val ) || empty( $_candidate_val ) || is_wp_error( $_candidate_val ) || ! is_array( $_candidate_val ) )
                $_candidate_val = is_array( $_original_val ) ? $_original_val : array();

            // ha_error_log( 'OPT NAME : ' . $opt_name );
            // ha_error_log( 'MULTIDIM FINAL VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_candidate_val, true ) );

            return $_candidate_val;
        }












        public function _get_wp_multidim_saved_val( $opt_name, $skope_id = null, $do_inherit = false, $child_value = array() ) {
            //ha_error_log('ALORS MULTIDIM SAVED VAL. Skope : ' . $skope_id );
            $skope          = $this -> ha_get_skope_level( $skope_id );


            //For global skope, the multidim theme_mods like nav_menu_locations are stored in option 'theme_mod_hueman' => array( 'na_menu_location' => array( ... ) )
            if ( 'global' == $skope && ha_is_wp_builtin_skoped_theme_mod( $opt_name ) ) {
              $theme = get_option( 'stylesheet' );
              $_raw_theme_mods = ha_get_raw_option( "theme_mods_{$theme}" );
              $skop_opt_val = isset( $_raw_theme_mods[$opt_name] ) ? $_raw_theme_mods[$opt_name] : array();
            } else {
              $skop_opt_val   = $this -> ha_get_cached_opt( $skope, $opt_name );
            }

            // ha_error_log('ALORS MULTIDIM SAVED VAL. Skope_level : ' . $skope );
            // ha_error_log( print_R( $skop_opt_val, true ) );
            //cast to array if the saved option is an object. For Ex : header_image_data can be an object
            $skop_opt_val   = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;
            if ( ! is_array( $skop_opt_val ) )
              $skop_opt_val = array();

            //if not inheriting, bail here and return the skope saved val
            if ( ! $do_inherit ) {
              return $skop_opt_val;
            }

            //initialize the candidates with the child values
            $_val_candidate = $child_value;

            //only keep the values that are not already saved in the child skope
            foreach ( $skop_opt_val as $_key => $_value ) {
              $_val_candidate[$_key] = ! isset( $child_value[$_key] ) ? $_value : $child_value[$_key];
            }


            $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
            //ha_error_log('ALORS PARENT SKOPE ID : ' . $parent_skope_id );
            //if a parent exists, let's get attempt to merge any additional inherited value
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $_val_candidate = $this -> _get_wp_multidim_saved_val( $opt_name, $parent_skope_id, true, $_val_candidate );
            }

            if ( is_null( $_val_candidate ) || empty( $_val_candidate ) || is_wp_error( $_val_candidate ) || ! is_array( $_val_candidate ) )
                $_val_candidate = array();

            return $_val_candidate;
        }











        function _get_wp_multidim_sanitized_customized_val( $opt_name, $skope_id = null, $do_inherit = false, $child_value = array() ) {

            global $wp_customize;
            $skope_id           = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            $_customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );
            $_val_candidate     = $child_value;

            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   ha_error_log( 'MULTIDIM CUST VAL FOR SKOPE ' . $skope_id);
            //   //ha_error_log( print_R( $_skope_customized_val, true ) );
            //   ha_error_log( print_R( $_customized_values, true) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }

            //make sure customized values is always an array
            if ( false === $_customized_values || ! is_array( $_customized_values ) )
              $_customized_values = array();

            //find an opt_name prefix match in the $_customized_values
            foreach ( $_customized_values as $_setting_id => $_setting_value ) {
                //try to find a prefix match option. For ex : sidebars_widgets
                if ( $opt_name != substr($_setting_id, 0, strlen( $opt_name ) ) )
                  continue;
                //the setting candidate has to be registered
                if ( ! is_object( $wp_customize -> get_setting( $_setting_id ) ) )
                  continue;
                $_setting             = $wp_customize -> get_setting( $_setting_id );
                $id_data              = $_setting -> id_data();
                $multi_dim_keys       = $id_data['keys'];
                $is_multidimensional  = ! empty( $multi_dim_keys );
                $setting_id           = $_setting -> id;
                //at this stage we should have only multidimensional settings. Let's check again.
                if ( ! $is_multidimensional )
                  continue;

                $valid = $_setting -> validate( $_setting_value );
                if ( is_wp_error( $valid ) ) {
                    ha_error_log( 'in _get_wp_multidim_sanitized_customized_val, invalid value for setting' . $_setting_id );
                    continue;
                }
                $_setting_value = $_setting -> sanitize( $_setting_value );

                //Add the current skope value only if the child is not customized
                foreach ( $multi_dim_keys as $_k ) {
                    $_val_candidate[$_k] = ! isset( $child_value[$_k] ) ? $_setting_value : $child_value[$_k];
                }
            }

            if ( $do_inherit ) {
                $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
                //if a parent exists, let's get it's customized value
                if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                    $_val_candidate = $this -> _get_wp_multidim_sanitized_customized_val( $opt_name, $parent_skope_id, true, $_val_candidate );
                }
            }

            if ( is_null( $_val_candidate ) || empty( $_val_candidate ) || is_wp_error( $_val_candidate ) )
                $_val_candidate = array();

            return $_val_candidate;
        }


    }//class
endif;

?>