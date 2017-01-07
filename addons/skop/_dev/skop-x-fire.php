<?php

/* ------------------------------------------------------------------------- *
 *  LOAD
/* ------------------------------------------------------------------------- */
function HA_SKOP_OPT() {
    return HA_Skop_Option::ha_skop_opt_instance();
}

if ( defined('TC_DEV') && true === TC_DEV ) {
    require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-0-init-base.php' );
}

add_action('hu_hueman_loaded', 'ha_load_skop_options');
function ha_load_skop_options() {
    if ( defined('TC_DEV') && true === TC_DEV ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-base.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-front-end-value.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-preview-value.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-x-final.php' );
    }
    HA_SKOP_OPT();
}


if ( defined('TC_DEV') && true === TC_DEV ) {
    require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-customize-register.php' );
}
require_once( HA_BASE_PATH . 'addons/skop/tmpl/skope-tmpls.php' );

new HA_Skop_Cust_Register();

//Customizer Ajax : we must for Hueman to be loaded (some Hueman constants are used)
add_action('hu_hueman_loaded', 'ha_load_skop_ajax');

add_action('init', 'ha_load_skop_customizer_preview' );
function ha_load_skop_customizer_preview() {
    //CUSTOMIZE PREVIEW : export skope data
    if ( HU_AD() -> ha_is_customize_preview_frame() ) {
        if ( defined('TC_DEV') && true === TC_DEV ) {
            require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-customize-preview.php' );
        }
        new HA_Skop_Cust_Prev();
    }
}


//hook : 'hu_hueman_loaded'
function ha_load_skop_ajax() {
    if ( defined('TC_DEV') && true === TC_DEV ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-base.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-publish.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-save.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-reset.php' );
    }
    new HA_Skop_Chset_Reset();
}
if ( defined('TC_DEV') && true === TC_DEV ) {
    if ( apply_filters('ha_print_skope_logs' , true ) ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/_dev_skop-logs.php' );
        function ha_instantiate_dev_logs() {
            if ( class_exists( 'HA_skop_dev_logs') ) {
                new HA_skop_dev_logs(
                    array(
                        'hook' => '__header_after_container_inner',
                        'display_header' => true,
                        'tested_option' => 'header_image'
                    )

                );
            }
        }
        //add_action('hu_hueman_loaded', 'ha_instantiate_dev_logs', 100 );
    }
}

?>