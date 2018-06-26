<?php
// Note : contx dependant. contx is loaded @after_setup_theme:30
add_action('after_setup_theme', 'ha_backward_compatibility_setup_june_2018', 40 );
function ha_backward_compatibility_setup_june_2018() {
    // do we have an old skope post ?
    $old_skope_post_id = ctx_get_old_skope_post_id();
    if ( '_no_set_' == $old_skope_post_id )
      return;

    $_old_raw_skoped_options = get_post_meta( $old_skope_post_id );
    $_old_raw_skoped_options = is_array( $_old_raw_skoped_options ) ? $_old_raw_skoped_options : array();


    $_old_skoped_options = array();
    foreach ( $_old_raw_skoped_options as $meta_key => $value ) {
        if ( is_array($value) && 1 == count($value) ) {
            $_old_skoped_options[$meta_key] = maybe_unserialize( $value[0] );
        }
        else {
            $_old_skoped_options[$meta_key] = array_map( 'maybe_unserialize', $value );
        }
    }

    $status = '_nothing_to_map_';
    if ( ! empty( $_old_skoped_options ) ) {
        $status = ha_generate_and_save_new_contx_options( $_old_skoped_options, $status );
        //sek_error_log( 'SKOPE MAPPING ATTEMPT', $final_new_skoped_options );
        //sek_error_log( 'GET_THEME_MODS', get_theme_mods() );
    }

    update_option( 'hu_contx_update_june_2018_status', $status );
}


// @return array()
function ha_generate_and_save_new_contx_options( $_old_skoped_options, $status ) {
    // PREVIOUS SKOPED OPTION STRUCTURE
    // => stored in post metas of a custom post type
    // each set of skoped options was written in a skope metas whom id was the skope_id
    // [hueman_czr_post_post_1] => Array
    //    (
    //        [hu_theme_options[pro_header_type]] => slider,
    //        [nav_menu_locations] => Array
    //            (
    //                [header] => 3
    //            )
    //    )


    // [hueman_czr_all_post] => Array
    //     (
    //         [blogname] => Site Title for All posts
    //     )
    $default_contx_model = ctx_get_default_model();
    $stylesheet = get_stylesheet();

    foreach( $_old_skoped_options as $old_skope_id => $raw_old_options ) {
        // if an error occured while writing a previous skope post, stop now
        if ( '_error_when_mapping_' === $status )
          break;

        // prepare new skope
        // go from hueman_czr_all_post to skp__all_post
        // or from hueman_czr_post_post_1 to skp__post_post_1
        $new_skope_id = str_replace( 'hueman_czr_', 'skp__', $old_skope_id );

        $pre_new_skoped_options = array();
        foreach ( $raw_old_options as $setting_id => $value ) {
            $validated_value = $value;
            $validated_setting_id = $setting_id;
            // IS THIS SETTING SKOPABLE ?
            // For example hu_theme_options[layout-global] is not skopable anymore as of june 2018
            if ( in_array( $setting_id, ctx_get_excluded_settings() ) )
              continue;


            // FIRST PREPROCESSING
            // First pass : validate the setting id and the value for specific settings
            switch ( $setting_id ) {
                case 'hu_theme_options[pro_header_type]':
                    // replace the old value by the new one
                    // the pro_header_type is deprecated with the new contextualizer, it could take 2 values = 'classical' or 'slider'
                    // the new 'display-a-pro-header-slider' option can take 3 values : yes, no, inherit
                    $validated_setting_id = 'hu_theme_options[display-a-pro-header-slider]';
                    $validated_value = 'slider' === $validated_value ? 'yes' : 'no';

                    $pre_new_skoped_options[] = wp_parse_args( array(
                        'id' => $validated_setting_id,
                        'setting-id' => $validated_setting_id,
                        'skope-id' => $new_skope_id,
                        'value' => $validated_value
                    ), $default_contx_model );
                break;
                case 'nav_menu_locations' :
                    // we want to go from
                    // 'nav_menu_locations' => Array( 'header' => 3, 'topbar' => 4 )
                    // to
                    // nav_menu_locations['header'] => array( ... )
                    // nav_menu_locations['topbar'] => array( ... )
                    foreach ( $value as $menu_location => $menu_id ) {
                        $validated_setting_id = "nav_menu_locations[{$menu_location}]";
                        $validated_value = $menu_id;

                        $pre_new_skoped_options[] = wp_parse_args( array(
                            'id' => $validated_setting_id,
                            'setting-id' => $validated_setting_id,
                            'skope-id' => $new_skope_id,
                            'value' => $validated_value
                        ), $default_contx_model );
                    }
                break;
                default :
                    $pre_new_skoped_options[] = wp_parse_args( array(
                        'id' => $setting_id,
                        'setting-id' => $validated_setting_id,
                        'skope-id' => $new_skope_id,
                        'value' => $validated_value
                    ), $default_contx_model );
                break;
            }//switch => first pass
        }//foreach

        // SECOND PREPROCESSING
        // second pass : amend the skope_data with the dynamically registered setting id
        // 'dyn_api_setting_id' => used when removing the item from the contx setting, to reset the _setting_ value().
        // @see registerContextalizedModuleControlSection and @see ::registerDynamicSkopeSettingControl
        $second_new_skoped_options = array();
        foreach ( $pre_new_skoped_options as $skoped_setting_data ) {
            $second_skoped_setting_data = $skoped_setting_data;
            switch ( $skoped_setting_data['setting-id'] ) {
                case 'hu_theme_options[body-background]' :
                case 'hu_theme_options[display-a-pro-header-slider]' :
                case 'hu_theme_options[pro_slider_header_bg]' :
                    // the dyn_api_setting_id looks like _contextualizer_ui_display-a-pro-header-slider_skp__post_post_1
                    $short_opt_name = str_replace( array('[', ']', HU_THEME_OPTIONS ), '', $skoped_setting_data['setting-id'] );
                    $skope_id = $skoped_setting_data['skope-id'];
                    $second_skoped_setting_data['dyn_api_setting_id'] = "_contextualizer_ui_{$short_opt_name}_{$skope_id}";
                    // those js dynamically registered setting have the specific type _module_.
                    // @see skopeReact::registerContextalizedModuleControlSection
                    $second_skoped_setting_data['type'] = '_module_';
                break;
            }// switch second pass
            $second_new_skoped_options[] = $second_skoped_setting_data;
        }//foreach


        // THIRD PREPROCESSING
        // We need to set a title to the following contx item
        // Otherwise the item title will fallback the setting id
        // It should normally not happen because the contextualizer uses the control label.
        // But for those dynamically registered control, the contextualizer might be instantiated before them.
        $final_new_skoped_options = array();
        foreach ( $second_new_skoped_options as $skoped_setting_data ) {
            $final_skoped_setting_data = $skoped_setting_data;
            switch ( $final_skoped_setting_data['setting-id'] ) {
                case 'hu_theme_options[body-background]' :
                    $final_skoped_setting_data['title'] = __('Body Background', 'hueman');
                break;
                case 'hu_theme_options[display-a-pro-header-slider]' :
                    $final_skoped_setting_data['title'] = __('Display a full width header background', 'hueman');
                break;
                case 'hu_theme_options[pro_slider_header_bg]' :
                    $final_skoped_setting_data['title'] = __('Full Width Header Background', 'hueman');
                break;
            }// switch second pass
            $final_new_skoped_options[] = $final_skoped_setting_data;
        }//foreach

        // TRY TO WRITE IN A POST
        // It will also set the theme mod with the created post id
        // ctx_update_skope_post() returns get_post()
        $r = ctx_update_skope_post( $final_new_skoped_options, array(
            'stylesheet' => $stylesheet,
            'skope_id' => $new_skope_id
        ));

        // UPDATE THE STATUS ON EACH LOOP
        if ( $r instanceof WP_Error || empty( $r ) ) {
            error_log('ERROR WHEN MAPPING THE OLD SKOPE OPTIONS FOR SKOPE ID => ' . $old_skope_id );
            $status = '_error_when_mapping_';
        } else {
            $status = '_mapping_done_';
        }
    }//foreach

    return $status;
}