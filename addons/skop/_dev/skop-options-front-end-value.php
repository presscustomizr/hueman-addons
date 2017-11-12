<?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option_Front' ) ) :
    class HA_Skop_Option_Front extends HA_Skop_Option_Base {
        public $_front_values = array();

        /* ------------------------------------------------------------------------- *
         *  GET FRONT END VALUES : APPLY INHERITANCE
        /* ------------------------------------------------------------------------- */
        //recursive method
        //apply the skope inheritance to return the relevant value
        //Typically use when is_customize_preview() to find the relevant database val for a requested skope
        //At this stage, we've already checked if this setting was customized for the current skope OR has inherited a customized value from one of its parent
        //If the current skope has no saved db value for this setting, then let's get the parent one and so on
        function _get_front_end_val( $original_opt_val, $opt_name, $skope = 'local', $do_inherit = false ) {
            ////////////////////////////////////////////////////////////////
            // if ( 'header_image' === $opt_name ) {
            //   ha_error_log( '_get_front_end_val ' . $skope . ' | ' . $opt_name );
            //   ha_error_log( print_R( $original_opt_val, true ) );
            //   //ha_error_log(  print_R( $_candidate_val, true ) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }
            $cache_front = $this -> _front_values;

            //Bail here if we already have a cached value for this opt_name
            //AND if 'skope_options_cached' has already been fired
            //AND we are not previewing
            if ( ! HU_AD() -> ha_is_customize_preview_frame() && ! HU_AD() -> ha_is_previewing_live_changeset() ) {
                if ( 0 !== did_action( 'skope_options_cached' ) && array_key_exists( $opt_name, $cache_front ) ) {
                    return $cache_front[$opt_name];
                }
            }

            if ( _ha_is_wp_setting_multidimensional( $opt_name ) ) {
                $skop_opt_val = $this -> _get_multidim_front_val( $original_opt_val, $opt_name, $skope, $do_inherit );
            } else {
                $skop_opt_val = $this -> _get_simple_front_val( $original_opt_val, $opt_name, $skope, $do_inherit );
            }

            //update the cache array and actually cache it
            $cache_front[$opt_name] = $skop_opt_val;
            $this -> _front_values = $cache_front;

            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   ha_error_log( print_R( $skop_opt_val, true ) );
            // }

            return $skop_opt_val;
        }



        function _get_simple_front_val( $original_opt_val, $opt_name, $skope, $do_inherit ) {
            $skop_opt_val = $this -> ha_get_cached_opt( $skope, $opt_name );
            //cast to array if the saved option is an object. For Ex : header_image_data can be an object
            $skop_opt_val = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;

            //do we have a value ?
            // => a value can be a string or an array. If string, then must be different than '_not_set_'
            if ( is_array( $skop_opt_val ) || '_no_set_' != (string)$skop_opt_val ) {
                return $skop_opt_val;
            }

            //We don't have a value yet
            // if we're not applying the inheritance then bail here with the skope val, else check if we reached the roof
            if ( ! $do_inherit && 'global' != $skope ) {
                return $skop_opt_val;
            } else if ( 'global' == $skope ) {
                return $original_opt_val;
            }

            $parent_skope = $this -> ha_get_parent_skope_name( $skope );
            return $this -> _get_simple_front_val( $original_opt_val, $opt_name, $parent_skope, true );
        }



        function _get_multidim_front_val( $original_opt_val, $opt_name, $skope, $do_inherit, $child_value = null ) {
            $child_value      = ( is_null( $child_value ) || ! is_array( $child_value ) ) ? array() : $child_value;
            $_candidate_val   = $child_value;

            $skop_opt_val     = $this -> ha_get_cached_opt( $skope, $opt_name );
            //cast to array if the saved option is an object. For Ex : header_image_data can be an object
            $skop_opt_val     = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;

            //do we have a value ?
            // => a value can be a string or an array. If string, then must be different than '_not_set_'
            if ( is_array( $skop_opt_val ) ) {
                $_candidate_val = wp_parse_args( $_candidate_val, $skop_opt_val );
            }

            if ( 'global' == $skope ) {
                if ( is_array( $original_opt_val ) )
                  return wp_parse_args( $_candidate_val, $original_opt_val );
                else
                  return $_candidate_val;
            }

            //We don't have a value yet
            // if we're not applying the inheritance then bail here with the skope val, else check if we reached the roof
            if ( ! $do_inherit ) {
                return wp_parse_args( $_candidate_val, $original_opt_val );
            }

            $parent_skope = $this -> ha_get_parent_skope_name( $skope );
            return $this -> _get_multidim_front_val( $original_opt_val, $opt_name, $parent_skope, true, $_candidate_val );
        }



        function ha_get_parent_skope_name( $skope, $_index = null ) {
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

    }//class
endif;

?>