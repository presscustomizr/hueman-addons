<?php
//Creates a new instance
function Contx( $params = array() ) {
    return Contx::ctx_get_instance( $params );
}

// Wrap the skp_get_skope_id to make sure it is invoked with the right namespace
function ctx_get_skope_id( $level = 'local' ) {
    if ( ! isset( $GLOBALS['czr_skope_namespace'] ) ) {
        error_log( __FUNCTION__ . ' => global czr_skope_namespace not set' );
        return;
    }

    $czrnamespace = $GLOBALS['czr_skope_namespace'];
    $fn = $czrnamespace . 'skp_get_skope_id';
    if ( function_exists( $fn ) ) {
          return $fn( $level );
    } else {
          error_log( __FUNCTION__ . ' => ' . $fn . ' does not exist.' );
    }
}



/////////////////////////////////////////////////////////////////
// <DEFINITIONS>

// THE SKOPE SETTING ITEM MODEL
function ctx_get_default_model() {
    return array(
        'id'          => '',
        'title'       => '',
        'skope-id'    => 'home',
        'setting-id'  => '',
        'type'        => '',
        'value'       => ''
    );
}



function ctx_get_authorized_setting_types() {
    return array(
        'text',
        'select',
        'checkbox',
        'number',
        'color',
        'text-area',
        'radio',
        'range',
        'image',

        // specific types
        'nav_menu_location',
        'cropped_image',
        'header',
        'code_editor'//<= used for the custom_css for example
    );
}

function ctx_get_excluded_settings() {
    $multidim_option_prefix = CONTX_OPTION_PREFIX;
    return array_merge(
      array(
        //hueman design option
        'hu_theme_options[favicon]',
        'hu_theme_options[featured-posts-include]',
        // 'post-comments',
        // 'page-comments',
        'hu_theme_options[layout-global]',
        'hu_theme_options[layout-home]',
        'hu_theme_options[layout-single]',
        'hu_theme_options[layout-archive]',
        'hu_theme_options[layout-archive-category]',
        'hu_theme_options[layout-search]',
        'hu_theme_options[layout-404]',
        'hu_theme_options[layout-page]',
        'hu_theme_options[sidebar-areas]',
        'hu_theme_options[about-page]',
        'hu_theme_options[help-button]',
        'hu_theme_options[show-skope-infos]',
        'hu_theme_options[enable-skope]',
        'hu_theme_options[attachments-in-search]',
        "{$multidim_option_prefix}[contx_wp_core]",
        "{$multidim_option_prefix}[contx_theme_and_plugins_options]"
      ),
      ctx_get_excluded_wp_core_settings()
    );
}

function ctx_get_excluded_wp_core_settings() {
    return array(
        //wp options
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'posts_per_page',
        'active_theme',
        'site_icon',
        //wp theme mods
        //'custom_css',
        'custom_css_post_id',
        'header_image_data',//<= used when customizing the header_image
    );
}

// @return array()
// @see the defaut control registered in the core customize-manager class
function ctx_get_wp_core_eligible_settings() {
    return array(
      'custom_logo',

      'background_preset',
      'background_image',
      'background_image_thumb',
      'background_color',
      'background_position',
      'background_position_x',
      'background_size',
      'background_repeat',
      'background_attachment',

      'header_image',
      'header_image_data',
      'header_text',
      'header_textcolor',

      'nav_menu_locations',

      'blogname',
      'blogdescription',
      'posts_per_page'
    );
}

// @return boold
function ctx_is_wp_core_contextualizable_setting( $opt_name ) {
    return in_array( $opt_name, ctx_get_wp_core_eligible_settings() );
}

//@return bool
function ctx_we_can_contextualize_wp_core_options() {
    $ctx_options = get_option( CONTX_OPTION_PREFIX );
    if ( is_array( $ctx_options ) && array_key_exists('contx_wp_core', $ctx_options ) ) {
        return 'yes' == $ctx_options['contx_wp_core'];
    } else {
        return ha_is_skop_on();
    }
}

//@return bool
function ctx_we_can_contextualize_not_wp_core_options() {
    $ctx_options = get_option( CONTX_OPTION_PREFIX );
    if ( is_array( $ctx_options ) && array_key_exists('contx_theme_and_plugins_options', $ctx_options ) ) {
        return 'yes' == $ctx_options['contx_theme_and_plugins_options'];
    } else {
        return ha_is_skop_on();
    }
}


/////////////////////////////////////////////////////////////////
// </DEFINITIONS>


// First check if there's a local value
// Then a group one
// Then a global one
function ctx_get_opt_val( $original_opt_val, $opt_name, $opt_multidim_group = null , $skope_level = 'local' ) {
    $_skp_val = $original_opt_val;

    $skope_level = is_null( $skope_level ) ? 'local' : $skope_level;

    $all_skoped = Contx() -> cached_ctx_opt;

    // error_log( '<ALL SKOPED>' );
    // error_log( print_r( $all_skoped, true ) );
    // error_log( '</ALL SKOPED>' );

    $skoped_for_requested_skope_level = array_key_exists( $skope_level, $all_skoped ) ? $all_skoped[ $skope_level ] : array();

    $skope_id = ctx_get_skope_id( $skope_level );

    if ( is_null( $opt_multidim_group ) ) {
        $to_search = "{$opt_name}";
    } else {
        $to_search = "{$opt_multidim_group}[{$opt_name}]";
    }

    // error_log( '<IN ctx_get_opt_val>' );
    // error_log( $to_search );
    // error_log( '</IN ctx_get_opt_val>' );

    if ( array_key_exists( $to_search, $skoped_for_requested_skope_level ) && array_key_exists( 'value', $skoped_for_requested_skope_level[ $to_search ] ) ) {
        // error_log('<ORIGINAL OPT VAL>');
        // error_log($original_opt_val);
        // error_log('</ORIGINAL OPT VAL>');
        $_skp_val = $skoped_for_requested_skope_level[ $to_search ]['value'];
    } else if ( 'local' == $skope_level ) {
        $_skp_val = ctx_get_opt_val( $original_opt_val, $opt_name, $opt_multidim_group, 'group' );
    }
    return $_skp_val;
}



function ctx_get_cached_skoped_opt_val( $opt_name, $skope_level, $opt_multidim_group = null ) {
    $all_skoped = Contx() -> cached_ctx_opt;
    $skoped_for_requested_skope_level = array_key_exists( $skope_level, $all_skoped ) ? $all_skoped[ $skope_level ] : array();
    $skope_id = ctx_get_skope_id( $skope_level );
    $_skp_val = '_no_set_';
    if ( is_null( $opt_multidim_group ) ) {
        $to_search = "{$opt_name}";
    } else {
        $to_search = "{$opt_multidim_group}[{$opt_name}]";
    }

    // error_log( '<IN ctx_get_opt_val>' );
    // error_log( $to_search );
    // error_log( '</IN ctx_get_opt_val>' );

    if ( array_key_exists( $to_search, $skoped_for_requested_skope_level ) && array_key_exists( 'value', $skoped_for_requested_skope_level[ $to_search ] ) ) {
        // error_log('<ORIGINAL OPT VAL>');
        // error_log($original_opt_val);
        // error_log('</ORIGINAL OPT VAL>');
        $_skp_val = $skoped_for_requested_skope_level[ $to_search ]['value'];
    }
    return $_skp_val;
}




/////////////////////////////////////////////////////////////////
// <GET FILTRABLE CANDIDATES>
// may be recache
function ctx_get_filtrable_candidates() {
    if ( empty( Contx() -> filtrable_candidates ) ) {
        $filtrable_candidates = Contx() -> ctx_cache_filtrable_candidates();
    } else {
        $filtrable_candidates = Contx() -> filtrable_candidates;
    }
    return $filtrable_candidates;
}
/////////////////////////////////////////////////////////////////
// </GET FILTRABLE CANDIDATES>


/**
* @uses  wp_get_theme() the optional stylesheet parameter value takes into account the possible preview of a theme different than the one activated
*/
function ctx_get_parent_theme_slug() {
    $theme_slug = get_option( 'stylesheet' );
    // $_REQUEST['theme'] is set both in live preview and when we're customizing a non active theme
    $theme_slug = isset($_REQUEST['theme']) ? $_REQUEST['theme'] : $theme_slug; //old wp versions
    $theme_slug = isset($_REQUEST['customize_theme']) ? $_REQUEST['customize_theme'] : $theme_slug;

    //gets the theme name (or parent if child)
    $theme_data = wp_get_theme( $theme_slug );
    if ( $theme_data -> parent() ) {
        $theme_slug = $theme_data -> parent() -> Name;
    }

    return sanitize_file_name( strtolower( $theme_slug ) );
}






///////////////////////////////////////////////////////////////
// RETRO COMPAT
function ctx_get_old_skope_post_id() {
  $skope_post_id  = get_option('skope-post-id');
  $skope_post = get_post( $skope_post_id );
  if ( false == $skope_post_id || ! $skope_post || 'czr_skope_opt' != get_post_type( $skope_post ) ) {
      return '_no_set_';
  }
  return $skope_post_id;
}

?><?php
/**
 * Fetch the `contx_post_type` post for a given {theme_name}_{skope_id}
 *
 * @since 4.7.0
 *
 * @param string $stylesheet Optional. A theme object stylesheet name. Defaults to the current theme.
 * @return WP_Post|null The skope post or null if none exists.
 */
function ctx_get_skope_post( $skope_id = '', $stylesheet = '', $skope_level = 'local' ) {
  if ( empty( $stylesheet ) ) {
    $stylesheet = get_stylesheet();
  }
  if ( empty( $skope_id ) ) {
    $skope_id = ctx_get_skope_id( $skope_level );
  }

  $ctx_post_query_vars = array(
    'post_type'              => CONTX_POST_TYPE,
    'post_status'            => get_post_stati(),
    'name'                   => sanitize_title( "{$stylesheet}_{$skope_id}" ),
    'posts_per_page'         => 1,
    'no_found_rows'          => true,
    'cache_results'          => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
    'lazy_load_term_meta'    => false,
  );

  $post = null;
  if ( get_stylesheet() === $stylesheet ) {
    $post_id = get_theme_mod( $skope_id );

    if ( $post_id > 0 && get_post( $post_id ) ) {
      $post = get_post( $post_id );
    }

    // `-1` indicates no post exists; no query necessary.
    if ( ! $post && -1 !== $post_id ) {
      $query = new WP_Query( $ctx_post_query_vars );
      $post = $query->post;
      /*
       * Cache the lookup. See ctx_update_skope_post().
       * @todo This should get cleared if a skope post is added/removed.
       */
      set_theme_mod( $skope_id, $post ? $post->ID : -1 );
    }
  } else {
    $query = new WP_Query( $ctx_post_query_vars );
    $post = $query->post;
  }

  return $post;
}

/**
 * Fetch the saved skoped settings
 *
 * @since 4.7.0
 *
 * @param string $stylesheet Optional. A theme object stylesheet name. Defaults to the current theme.
 * @return array => the skope setting items
 */
function ctx_get_skoped_settings( $skope_id = '', $stylesheet = '', $skope_level = 'local' ) {
  $skoped_settings = '';

  if ( empty( $stylesheet ) ) {
    $stylesheet = get_stylesheet();
  }
  if ( empty( $skope_id ) ) {
    $skope_id = ctx_get_skope_id( $skope_level );
  }

  $post = ctx_get_skope_post( $skope_id );
  if ( $post ) {
    $skoped_settings = maybe_unserialize( $post->post_content );
  }

  $skoped_settings = apply_filters( 'ctx_get_skoped_settings', $skoped_settings, $skope_id, $stylesheet );

  return $skoped_settings;
}



/**
 * Update the `contx_post_type` post for a given {theme_name}_{skope_id}
 *
 * Inserts a `contx_post_type` post when one doesn't yet exist.
 *
 * @since 4.7.0
 *
 * }
 * @return WP_Post|WP_Error Post on success, error on failure.
 */
function ctx_update_skope_post( $skoped_settings, $args = array() ) {
  $args = wp_parse_args( $args, array(
    'stylesheet' => get_stylesheet(),
    'skope_id' => ''
  ) );

  $data = array(
    'skoped_settings' => $skoped_settings
  );

  $post_title = $args['stylesheet'] . '_' . $args['skope_id'];

  $post_data = array(
    'post_title' => $post_title,
    'post_name' => sanitize_title( $post_title ),
    'post_type' => CONTX_POST_TYPE,
    'post_status' => 'publish',
    'post_content' => maybe_serialize( $data['skoped_settings'] )
  );


  // Update post if it already exists, otherwise create a new one.
  $post = ctx_get_skope_post( $args['skope_id'], $args['stylesheet'] );

  if ( $post ) {
    $post_data['ID'] = $post->ID;
    $r = wp_update_post( wp_slash( $post_data ), true );
  } else {
    $r = wp_insert_post( wp_slash( $post_data ), true );

    if ( ! is_wp_error( $r ) ) {
      if ( get_stylesheet() === $args['stylesheet'] ) {
        set_theme_mod( $args['skope_id'], $r );//$r is the post ID
      }

      // Trigger creation of a revision. This should be removed once #30854 is resolved.
      if ( 0 === count( wp_get_post_revisions( $r ) ) ) {
        wp_save_post_revision( $r );
      }
    }
  }

  if ( is_wp_error( $r ) ) {
    return $r;
  }
  return get_post( $r );
}

// delete_option( 'hu_theme_options' );
?><?php

////////////////////////////////////////////////////////////////
//  This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
if ( ! class_exists( 'CZR_Contx_Construct' ) ) :
    class CZR_Contx_Construct {
        static $instance;
        public $filtrable_candidates;//will be updated on customize_register
        public $skopable_settings;
        public $default_filtrable_candidates_model = array(
            'options' => array(),
            'multidim_options' => array(),
            'multidim_theme_mods' => array(),
            'simple_theme_mods' => array()
        );
        public $skopable_settings_collection_model = array(
            'setting-id' => '',
            'apiCtrlId' => '',
            'apiSetId' => '',
            'type' => '',
        );
        public $ctx_dynamic_setting_default_data = array(
            'transport' => 'refresh',
            'type' => 'theme_mod',
        );

        public $ctx_dynamic_control_default_data = array(
            'type'      => 'czr_module',
            'module_type' => 'czr_flat_skope_module',
            'section'   => 'contx_sec'
        );

        public static function ctx_get_instance( $params ) {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Contx ) )
              self::$instance = new Contx( $params );
            return self::$instance;
        }

        // options
        public $cached_ctx_opt = array( 'local' => array(), 'group' => array() );
        public $cached_skopified_theme_mods = array();




        /////////////////////////////////////////////////////////////////
        // <CONSTRUCTOR>
        function __construct( $params = array() ) {
            $defaults = array(
                'base_url_path' => ''//PC_AC_BASE_URL/inc/czr-modules/contextualizer
            );
            $params = wp_parse_args( $params, $defaults );

            // CONSTANTS
            if ( ! defined( 'CONTX_BASE_URL' ) ) { define( 'CONTX_BASE_URL' , $params['base_url_path'] ); }
            if ( ! defined( 'CONTX_POST_TYPE' ) ) { define( 'CONTX_POST_TYPE' , "contx_post_type" ); }
            if ( ! defined( 'CONTX_OPTION_PREFIX' ) ) { define( 'CONTX_OPTION_PREFIX' , 'contx' ); }

            // The CPT used to persist the contextualized options
            $this -> ctx_register_contx_post_type();

            // let's cache the $filtrable_candidates now, before filtering the theme mods
            // make sure they are well formed
            $this -> ctx_cache_filtrable_candidates();

            // OPTIONS : CACHE AND SETUP FILTERS
            $this -> ctx_schedule_option_cache_and_filters_on_wp();

            // ON 'customize_register'
            // => load setting class
            // => add a section
            // => set filtrable candidates
            $this -> ctx_schedule_customize_register_actions();

            // DYNAMIC SETTING REGISTRATION AND SANITIZATION
            $this -> ctx_schedule_customize_dynamic_setting_args_and_class_filters();

            // SETUP JS AND CSS RESOURCES
            $this -> ctx_schedule_loading_customizer_js_css_assets();

            // AJAX TMPL FILTER
            // When the following javascript module properties are empty, the module will try to fetch the various item templates via ajax
            // itemPreAddEl : '',
            // itemInputList : '',
            // modOptInputList : ''
            // the template fetching is done with api.CZR_Helpers.getModuleTmpl() => which fires  wp.ajax.post( 'ac_get_template', args )
            //  => the filter ac_set_ajax_czr_tmpl___{module_id} is fired server side during the 'ac_get_template' ajax action
            add_filter( "ac_set_ajax_czr_tmpl___czr_flat_skope_module", array( $this, 'ctx_get_skope_module_tmpl' ), 10, 3 );
        }//__construct
        /////////////////////////////////////////////////////////////////
        // </CONSTRUCTOR>

        /////////////////////////////////////////////////////////////////
        /// REGISTER POST TYPE
        /// Fired in the constructor
        function ctx_register_contx_post_type() {
            // SKOPE POST
            register_post_type( CONTX_POST_TYPE, array(
              'labels' => array(
                'name'          => __( 'Contextual settings', 'text_domain_to_be_replaced' ),
                'singular_name' => __( 'Contextual settings', 'text_domain_to_be_replaced' ),
              ),
              'public'           => false,
              'hierarchical'     => false,
              'rewrite'          => false,
              'query_var'        => false,
              'delete_with_user' => false,
              'can_export'       => true,
              '_builtin'         => true, /* internal use only. don't use this when registering your own post type. */
              'supports'         => array( 'title', 'revisions' ),
              'capabilities'     => array(
                'delete_posts'           => 'edit_theme_options',
                'delete_post'            => 'edit_theme_options',
                'delete_published_posts' => 'edit_theme_options',
                'delete_private_posts'   => 'edit_theme_options',
                'delete_others_posts'    => 'edit_theme_options',
                'edit_post'              => 'edit_theme_options',
                'edit_posts'             => 'edit_theme_options',
                'edit_others_posts'      => 'edit_theme_options',
                'edit_published_posts'   => 'edit_theme_options',
                'read_post'              => 'read',
                'read_private_posts'     => 'read',
                'publish_posts'          => 'edit_theme_options',
              )
            ) );
        }

        // fired from the constructor
        function ctx_cache_filtrable_candidates() {
            $filtrable_candidates = get_theme_mod( 'ctx_filtrable_candidates' );//<= set on add_action('customize_register', 'ctx_set_filtrable_candidates', PHP_INT_MAX );
            if ( empty( $filtrable_candidates ) || ! is_array( $filtrable_candidates ) ) {
                // if the theme mod is not set yet, populate with a hard coded json
                // fixes : https://github.com/presscustomizr/hueman-pro-addons/issues/143
                $this -> filtrable_candidates = json_decode( $this->_get_default_filtrable_candidate_json(), true );//$this -> default_filtrable_candidates_model;
            } else {
                $this -> filtrable_candidates = wp_parse_args( $filtrable_candidates, $this -> default_filtrable_candidates_model );
            }
            // error_log( '<FILTRABLE CANDIDATES>' );
            // error_log( print_r( $filtrable_candidates , true ) );
            // error_log( '</FILTRABLE CANDIDATES>' );

            return apply_filters( 'ctx_cache_filtrable_candidates', $this -> filtrable_candidates );
        }


        /////////////////////////////////////////////////////////////////
        // AJAX TMPL FILTERS
        // this dynamic filter is declared on wp_ajax_ac_get_template
        // It allows us to populate the server response with the relevant module html template
        // $html = apply_filters( "ac_set_ajax_czr_tmpl___{$module_type}", '', $tmpl );
        //
        // Each template is built from a map, each input type having its own unique piece of tmpl
        //
        // 3 types of templates :
        // 1) the pre-item, rendered when adding an item
        // 2) the module meta options, or mod-opt
        // 3) the item input options

        function ctx_get_skope_module_tmpl( $html, $requested_tmpl = '', $posted_params = array() ) {
            if ( empty( $requested_tmpl ) ) {
                wp_send_json_error( 'ac_get_skope_module_tmpl => the requested tmpl ' . $requested_tmpl . ' is not authorized' );
            }
            $tmpl_map = array();
            switch ( $requested_tmpl ) {
                case 'pre-item' :
                    $tmpl_map = array(
                        'setting-id'  => array(
                            'input_type'  => 'select',
                            'title'       => __('Select', 'text_domain_to_be_replaced'),
                            'notice_before' => __('Pick an option and add it to the collection of contextualized settings.', 'text_domain_to_be_replaced'),
                            'width-100'   => true
                        ),
                        'id' => array('input_type'  => 'hidden' ),
                        'title' => array('input_type'  => 'hidden' ),
                        'type' => array('input_type'  => 'hidden' ),
                        'value' => array('input_type'  => 'hidden' ),
                        'skope-id' => array('input_type'  => 'hidden' )
                    );
                break;

                case 'item-inputs' :
                    // The map is dependant on the type set in the item_model
                    // the item_model is posted in the query when fetching the item content template
                    if ( ! is_array( $posted_params ) || ! array_key_exists( 'item_model', $posted_params ) || ! array_key_exists( 'type' , $posted_params[ 'item_model' ] ) ) {
                        wp_send_json_error( 'ac_get_skope_module_tmpl => missing type property in the posted item_model' );
                    }
                    $item_type = $posted_params[ 'item_model' ][ 'type' ];

                    // Handle the specific input types case
                    // Force them to regular input types
                    switch( $posted_params[ 'item_model' ][ 'type' ] ) {
                        case 'nav_menu_location' :
                              $item_type = 'select';
                        break;
                        case 'cropped_image' :
                        case 'image' :
                              $item_type = 'upload';
                        break;
                        case 'header' :
                              $item_type = 'upload_url';
                        break;
                        //custom_css
                        case 'code_editor' :
                              $item_type = 'textarea';
                        break;
                    }

                    switch( $item_type ) {
                        // For radio, number and range type, we need to generate their attributes ( step, min, max ) based on the original control
                        // this is done in js, that's why we use this placeholder class
                        // we can specify a template when parsed in CZR_Fmk_Base() -> ac_generate_czr_tmpl_from_map()
                        case 'radio' :
                        case 'number' :
                        case 'range' :
                            $tmpl_map = array(
                                'value' => array(
                                    'input_type'  => $item_type,
                                    'title'       => __( 'Contextual Value', 'text_domain_to_be_replaced'),
                                    'input_template'    => '<input class="placeholder-to-be-replaced-by-dynamic-content" type="hidden" data-czrtype="value"></input>'
                                )
                            );
                        break;

                        case 'color' :
                            $tmpl_map = array(
                                'value' => array(
                                    'input_type'  => $item_type,
                                    'title'       => __( 'Contextual Value', 'text_domain_to_be_replaced'),
                                    'input_template'    => '<#
                                      var defaultValue = "#RRGGBB",
                                        defaultValueAttr = "",
                                        isHueSlider = true === data.isHueSlider;
                                      if ( data.value && _.isString( data.value ) && ! isHueSlider ) {
                                        if ( "#" !== data.value.substring( 0, 1 ) ) {
                                          data.value = "#" + data.value;
                                        }
                                        defaultValueAttr = " data-default-color=" + data.value; // Quotes added automatically.
                                      }
                                    #>
                                    <# if ( isHueSlider ) { #>
                                      <input class="color-picker-hue" data-czrtype="value" type="text" value="{{ data.value }}" data-type="hue"></input>
                                    <# } else { #>
                                      <input class="color-picker-hex" data-czrtype="value" type="text" maxlength="7" placeholder="{{ data.value }}" {{ defaultValueAttr }} value="{{ data.value }}"></input>
                                    <# } #>'
                                )
                            );
                        break;

                        default :
                            $tmpl_map = array(
                                'value' => array(
                                    'input_type'  => $item_type,
                                    'title'       => __( 'Contextual Value', 'text_domain_to_be_replaced'),
                                )
                            );
                        break;
                    }
                break;
            }
            if ( isset( $GLOBALS['czr_base_fmk_namespace'] ) ) {
                $function_candidate = $GLOBALS['czr_base_fmk_namespace'] . 'CZR_Fmk_Base';
                if ( function_exists( $function_candidate ) ) {
                    return $function_candidate() -> ac_generate_czr_tmpl_from_map( $tmpl_map );
                    //return czr_fn\CZR_Fmk_Base() -> ac_generate_czr_tmpl_from_map( $tmpl_map );
                } else {
                    wp_send_json_error( 'ctx_get_skope_module_tmpl => missing function namespace\CZR_Fmk_Base()' );
                }

            } else {
                wp_send_json_error( 'ctx_get_skope_module_tmpl => missing czr_base_fmk_namespace' );
            }
        }//ctx_get_skope_module_tmpl


        /////////////////////////////////////////////////////////////////
        // Default filtrable candidates as a hard coded json
        // Is used when the theme_mod "ctx_filtrable_candidates" is not set yet
        // invoked in ctx_cache_filtrable_candidates()
        private function _get_default_filtrable_candidate_json() {
            return '{"options":["blogname","blogdescription"],"multidim_options":{"hu_theme_options":["display-header-title","display-header-logo","logo-max-height","font","body-font-size","container-width","boxed","sidebar-padding","color-1","color-2","image-border-radius","ext_link_style","ext_link_target","post-comments","page-comments","smoothscroll","responsive","fittext","sharrre","sharrre-counter","sharrre-scrollable","sharrre-twitter-on","twitter-username","sharrre-facebook-on","sharrre-google-on","sharrre-pinterest-on","sharrre-linkedin-on","minified-css","structured-data","smart_load_img","js-mobile-detect","site-description","color-topbar","color-header","color-header-menu","color-mobile-menu","transparent-fixed-topnav","use-header-image","logo-title-on-header-image","header-ads","header-ads-desktop","header-ads-mobile","default-menu-header","header-desktop-sticky","desktop-search","header_mobile_menu_layout","header-mobile-sticky","header_mobile_btn","mobile-search","infinite-scroll","load_on_scroll_desktop","load_on_scroll_mobile","pro_post_list_design","pro_grid_columns","blog-heading-enabled","blog-heading","blog-subheading","excerpt-length","featured-posts-enabled","featured-category","featured-posts-count","featured-posts-full-content","featured-slideshow","featured-slideshow-speed","author-bio","related-posts","post-nav","placeholder","comment-count","sidebar-top","desktop-sticky-sb","mobile-sticky-sb","mobile-sidebar-hide","footer-ads","default-menu-footer","color-footer","copyright","credit"]},"multidim_theme_mods":{"nav_menu_locations":["topbar","mobile","header","footer"]},"simple_theme_mods":["header_text","custom_logo","header_textcolor","background_color","header_image","background_preset","background_size","background_repeat","background_attachment"]}';
        }
    }//class
endif;

?>
<?php
///////////////////////////////////////////////////////////////
// SKOP OPTION CLASS
if ( ! class_exists( 'Contx_Options' ) ) :
    class Contx_Options extends CZR_Contx_Construct {
        //static $instance;

        // Fired in the constructor
        // cache and setup filters
        function ctx_schedule_option_cache_and_filters_on_wp() {
            // CACHE CONTEXTUALIZED OPTIONS
            add_action( 'wp', array( $this, 'ctx_cache_ctx_options' ), PHP_INT_MAX - 1  );//<= because the option filter are set to  PHP_INT_MAX );
            add_action( 'wp', array( $this, 'ctx_setup_option_filters' ), PHP_INT_MAX );
        }

        // hook : wp
        function ctx_cache_ctx_options() {
            // error_log( '<CTX OPTIONS in OPTIONS>' );
            // error_log( print_r( get_option( CONTX_OPTION_PREFIX ), true ) );
            // error_log( '</CTX OPTIONS>' );
            // if we can contextualize (user has not uncheck everyt) let's setup the filtrable candidates and the option filters
            if ( ! ctx_we_can_contextualize_wp_core_options() && ! ctx_we_can_contextualize_not_wp_core_options() )
              return;

            $local_skope_id = ctx_get_skope_id( 'local' );
            $group_skope_id = ctx_get_skope_id( 'group' );

            $raw_local = ctx_get_skoped_settings( $local_skope_id );
            $raw_group = '';
            if ( '_skope_not_set_' != $group_skope_id ) {
                $raw_group = ctx_get_skoped_settings( $group_skope_id );
            }

            $cache_candidate = array( 'local' => array(), 'group' => array() );
            $default_model = ctx_get_default_model();
            //$raw_local looks like
            //[0] => Array
            // (
            //     [id] => czr_flat_skope_module_0
            //     [title] => copyright
            //     [setting-id] => copyright
            //
            //     [value] => My flat skoped copyright
            // )
            if ( is_array( $raw_local ) ) {
                foreach ($raw_local as $data ) {
                    $data = wp_parse_args( $data, $default_model );
                    if ( empty( $data['id'] ) )
                        continue;
                    if ( empty( $data['setting-id'] ) || empty( $data['skope-id'] ) ){
                        error_log( 'Empty setting-id or skope-id for customized : ' . $data['id'] );
                        continue;
                    }

                    $key = $data['id'];
                    $cache_candidate['local'][ $key ] = $data;
                }
            }
            if ( is_array( $raw_group ) ) {
                foreach ($raw_group as $data ) {
                    $data = wp_parse_args( $data, $default_model );
                    if ( empty( $data['id'] ) )
                        continue;
                    if ( empty( $data['setting-id'] ) || empty( $data['skope-id'] ) ){
                        error_log( 'Empty setting-id or skope-id for customized : ' . $data['id'] );
                        continue;
                    }

                    $key = $data['id'];
                    $cache_candidate['group'][ $key ] = $data;
                }
            }
            $this -> cached_ctx_opt = $cache_candidate;

            do_action( 'contextualizer_options_cached');
            // error_log( '<CACHE CANDIDATES>' );
            // error_log( print_r( $cache_candidate, true) );
            // error_log( '</CACHE CANDIDATES>' );
        }


        /////////////////////////////////////////////////////////////////
        //FILTER THEME OPTIONS
        // On 'wp' so that we have a skope
        // the filter candidates look like this :
        // [options] => Array
        //         (
        //             [0] => blogname
        //             [1] => blogdescription
        //         )

        //     [multidim_options] => Array
        //         (
        //             [pc_ac_opt_test] => Array
        //                 (
        //                     [0] => test_one
        //                     [1] => test_two
        //                 )

        //         )

        //     [multidim_theme_mods] => Array
        //         (
        //         )

        //     [simple_theme_mods] => Array
        //         (
        //             [0] => custom_logo
        //             [1] => header_textcolor
        //             [2] => background_color
        //             [3] => header_video
        //             [4] => external_header_video
        //             [5] => header_image
        //             [6] => header_image_data
        //             [7] => background_image
        //             [8] => background_image_thumb
        //             [9] => background_preset
        //             [10] => background_position_x
        //             [11] => background_position_y
        //             [12] => background_size
        //             [13] => background_repeat
        //             [14] => background_attachment
        //             [15] => colorscheme
        //             [16] => colorscheme_hue
        //             [17] => page_layout
        //             [18] => panel_1
        //             [19] => panel_2
        //             [20] => panel_3
        //             [21] => panel_4
        //         )

        // )
        function ctx_setup_option_filters() {
            $filtrable_candidates = apply_filters( 'ctx_filtrable_candidates_before_setting_up_option_filters', ctx_get_filtrable_candidates() );
            // error_log( '<FILTRABLE CANDIDATES>' );
            // error_log( print_r( $filtrable_candidates , true ) );
            // error_log( '</FILTRABLE CANDIDATES>' );
            //sek_error_log( __CLASS__ . '::' . __FUNCTION__ . ' => FILTRABLE CANDIDATES', ctx_get_filtrable_candidates() );

            if ( empty( $filtrable_candidates ) || ! is_array( $filtrable_candidates ) )
              return;

            if ( ! ctx_we_can_contextualize_wp_core_options() && ! ctx_we_can_contextualize_not_wp_core_options() )
              return;

            $theme_mods = get_theme_mods();

            //sek_error_log( '$theme_mods', $theme_mods );

            $ctx_get_wp_core_eligible_settings = ctx_get_wp_core_eligible_settings();

            foreach ( $filtrable_candidates as $group_type => $candidates ) {
                if( empty( $group_type ) )
                  continue;

                switch ( $group_type ) {
                    // there can be wp core option like blogname, posts_per_page
                    // and themes and plugin options
                    case 'options':
                        foreach ( $candidates as $opt_name ) {
                            // apply user global contx options
                            if ( ! ctx_we_can_contextualize_wp_core_options() && in_array( $opt_name, $ctx_get_wp_core_eligible_settings ) )
                              continue;
                            if ( ! ctx_we_can_contextualize_not_wp_core_options() && ! in_array( $opt_name, $ctx_get_wp_core_eligible_settings ) )
                              continue;

                            // filters documented in wp-includes/option.php
                            add_filter( "option_{$opt_name}", array( $this, 'ctx_filter_for_simple_options' ), PHP_INT_MAX, 2 );

                            // When customizing, the "pre_option{}" filter is used by non multidimensional options, like blogname, blogdescription.
                            // @see wp-includes/class-wp-customize-setting.php
                            // Fixes https://github.com/presscustomizr/hueman-pro-addons/issues/154
                            $skope_namespace = isset( $GLOBALS['czr_skope_namespace'] ) ? $GLOBALS['czr_skope_namespace'] : '';
                            $skp_is_customizing_fn = $skope_namespace . 'skp_is_customizing';
                            if ( function_exists( $skp_is_customizing_fn ) && $skp_is_customizing_fn() ) {
                                add_filter( "pre_option_{$opt_name}", array( $this, 'ctx_filter_for_simple_options' ), PHP_INT_MAX, 2 );
                            }
                        }
                    break;
                    // there are no wp core multidimensional options
                    // this can be only themes or plugins options
                    case 'multidim_options':
                        if ( ctx_we_can_contextualize_not_wp_core_options() ) {
                            foreach ( $candidates as $opt_group => $opt_names ) {
                                // filter documented in wp-includes/option.php
                                add_filter( "option_{$opt_group}", array( $this, 'ctx_filter_for_multidim_options' ), PHP_INT_MAX, 4 );
                                // this action allows us to set filters for the Hueman theme options
                                do_action( "ctx_set_filters_for_opt_group___{$opt_group}", $opt_names );
                            }
                        }
                    break;
                    // there can be default wp core options like header_image, custom_logo, ...
                    // there can be theme_mods registered by a theme or plugin
                    case 'simple_theme_mods':
                        $theme_slug = ctx_get_parent_theme_slug();
                        // filter documented in wp-includes/option.php
                        add_filter( "option_theme_mods_{$theme_slug}", array( $this, 'ctx_filter_for_all_theme_mods' ), PHP_INT_MAX, 4 );

                        // We also need to filter each single theme mod when they are not yet written in the theme_mod option but are candidate for skopifization
                        // For example, the header_textcolor, has not been customized globally yet, and is therefore not written in the db option theme_mods_{$stylesheet}
                        // But it's been contextualized on home.
                        // Without this single theme mod filter, the contextualization would not be displayed
                        // filter documented in wp-includes/theme.php
                        foreach ( $candidates as $opt_name ) {
                            // apply user global contx options
                            if ( ! ctx_we_can_contextualize_wp_core_options() && in_array( $opt_name, $ctx_get_wp_core_eligible_settings ) )
                              continue;
                            if ( ! ctx_we_can_contextualize_not_wp_core_options() && ! in_array( $opt_name, $ctx_get_wp_core_eligible_settings ) )
                              continue;

                                                        // When customizing, the "pre_option{}" filter is used by non multidimensional options, like blogname, blogdescription.
                            // @see wp-includes/class-wp-customize-setting.php
                            // Fixes https://github.com/presscustomizr/hueman-pro-addons/issues/154
                            $skope_namespace = isset( $GLOBALS['czr_skope_namespace'] ) ? $GLOBALS['czr_skope_namespace'] : '';
                            $skp_is_customizing_fn = $skope_namespace . 'skp_is_customizing';
                            if ( function_exists( $skp_is_customizing_fn ) && $skp_is_customizing_fn() ) {
                                if ( array_key_exists( $opt_name, $theme_mods ) ) {
                                    add_filter( "theme_mod_{$opt_name}", array( $this, 'ctx_filter_for_single_theme_mod' ), PHP_INT_MAX, 1 );
                                }
                            }
                        }
                    break;
                    case 'multidim_theme_mods':
                    break;
                 }//switch
            }//foreach

            // Custom css
            // apply user global contx options
            if ( ctx_we_can_contextualize_wp_core_options() ) {
                add_filter( 'wp_get_custom_css', array( $this, 'ctx_filter_for_custom_css' ), PHP_INT_MAX, 2 );
            }

            do_action( 'contextualizer_options_filters_setup');
        }

        // hook : wp_get_custom_css
        function ctx_filter_for_custom_css( $css, $stylesheet ) {
            return ctx_get_opt_val( $css, "custom_css[{$stylesheet}]" );
        }

        /////////// TEST
        // $theme_slug = ctx_get_parent_theme_slug();
        // add_filter( "option_theme_mods_nav_menu_locations", function( $original_theme_mods ) {

        //     return $original_theme_mods;
        // }, PHP_INT_MAX, 4 );
        ////  /TEST


        //hook : "option_theme_mods_{$theme_slug}"
        function ctx_filter_for_all_theme_mods( $original_theme_mods, $theme_mod_opt_name ) {
            if ( ! empty( $this -> cached_skopified_theme_mods ) )
              return $this -> cached_skopified_theme_mods;

            $new_theme_mods = $original_theme_mods;

            $all_filtrable_candidates = ctx_get_filtrable_candidates();
            $filtrable_candidates_for_simple_theme_mods = $all_filtrable_candidates['simple_theme_mods'];
            $ctx_get_wp_core_eligible_settings = ctx_get_wp_core_eligible_settings();
            // error_log( '<ORIGINAL THEMEMODS>' );
            // error_log( print_r( $original_theme_mods, true ) );
            // error_log( '</ORIGINAL THEMEMODS>' );

            foreach ( $original_theme_mods as $opt_name => $original_opt_val ) {
                if ( 'nav_menu_locations' == $opt_name && ctx_we_can_contextualize_wp_core_options() ) {
                    //sek_error_log( 'ctx_get_skopified_nav_menu_locations' );
                    $new_theme_mods[ $opt_name ] = $this -> ctx_get_skopified_nav_menu_locations( $original_opt_val );
                } else {
                    // the option has to be part of the filtrable candidates
                    if ( ! in_array( $opt_name, $filtrable_candidates_for_simple_theme_mods ) )
                      continue;

                    // apply user global contx options
                    if ( ! ctx_we_can_contextualize_wp_core_options() && in_array( $opt_name, $ctx_get_wp_core_eligible_settings ) )
                      continue;
                    if ( ! ctx_we_can_contextualize_not_wp_core_options() && ! in_array( $opt_name, $ctx_get_wp_core_eligible_settings ) )
                      continue;

                    $new_theme_mods[ $opt_name ] = ctx_get_opt_val( $original_opt_val, $opt_name );
                }
            }
            //sek_error_log('SKOPIFIED THEME MODS', $new_theme_mods );

            // Cache them now
            $this -> cached_skopified_theme_mods = $new_theme_mods;
            return $new_theme_mods;
        }


        function ctx_get_skopified_nav_menu_locations( $nav_menu_locations = array() ) {
            if ( ! is_array( $nav_menu_locations ) )
              return $nav_menu_locations;

            $registered_nav_menus = get_registered_nav_menus();
            $new_nav_menu_locations = array();
            foreach( $registered_nav_menus as $menu_location => $menu_label ) {
                $sitewide_value = array_key_exists( $menu_location, $nav_menu_locations ) ? $nav_menu_locations[ $menu_location ] : 0;//<= 0 is the value assigned to an empty location by WordPress
                $new_nav_menu_locations[$menu_location] = ctx_get_opt_val( $sitewide_value, "nav_menu_locations[{$menu_location}]" );
            }
            // error_log( '<REGISTERED NAV MENUS>' );
            // error_log( print_r( $registered_nav_menus, true ) );
            // error_log( '</REGISTERED NAV MENUS>' );

            // error_log( '<IN NAV MENU LOCATION FILTER>' );
            // error_log( print_r( $original_opt_val, true ) );
            // error_log( '</IN NAV MENU LOCATION FILTER>' );

            // error_log( '<FILTRABLE CANDIDATES>' );
            // error_log( print_r( ctx_get_filtrable_candidates() , true ) );
            // error_log( '</FILTRABLE CANDIDATES>' );
            return $new_nav_menu_locations;
        }



        //hook : theme_mod_{$_opt_name}
        function ctx_filter_for_single_theme_mod( $original_opt_val ) {
          //extract theme_mod name
          $_filter = current_filter();
          $_ptrn = 'theme_mod_';
          if ( $_ptrn !== substr( $_filter, 0, strlen($_ptrn) ) ) {
            return $original_opt_val;
          }

          $_mod_name = str_replace($_ptrn, '',  $_filter);

          $all_filtrable_candidates = ctx_get_filtrable_candidates();
          $filtrable_candidates_for_simple_theme_mods = $all_filtrable_candidates['simple_theme_mods'];
          // the option has to be part of the filtrable candidates
          if ( ! in_array( $_mod_name, $filtrable_candidates_for_simple_theme_mods ) ) {
            return $original_opt_val;
          }

          //the option group is null
          return ctx_get_opt_val( $original_opt_val, $_mod_name );
        }


        // hook : "option_{$opt_group}"
        //$original_opt_val is an array
        function ctx_filter_for_multidim_options( $original_options, $opt_multidim_group ) {
            if ( ! is_array( $original_options ) || empty( $original_options ) )
              return array();

            $new_options = $original_options;
            foreach ( $original_options as $opt_name => $original_opt_val ) {
               $new_options[$opt_name] = ctx_get_opt_val( $original_opt_val, $opt_name, $opt_multidim_group  );
            }

            return $new_options;
        }

        // hook : "option_{$opt_name}"
        function ctx_filter_for_simple_options( $original_opt_val , $opt_name ) {
            //falls back to global
            //$_new_val = $original_opt_val;

            //IF PREVIEWING
            // if ( ctx_is_customize_preview_frame() && !  ctx_is_previewing_live_changeset() ) {
            //     $_new_val = $this -> _get_sanitized_preview_val( $_opt_val, $opt_name );
            // } else {
            //     //@param = value, option name, skope, inherits
            //     $_new_val = $this -> _get_front_end_val( $_opt_val, $opt_name, 'local', true );
            // }
            return ctx_get_opt_val( $original_opt_val, $opt_name );
        }

    }//class
endif;

?><?php
///////////////////////////////////////////////////////////////
// SKOP OPTION CLASS
if ( ! class_exists( 'Contx_Customize_Register' ) ) :
    class Contx_Customize_Register extends Contx_Options {
        //static $instance;

        // Fired in the constructor
        function ctx_schedule_customize_register_actions() {
            add_action( 'customize_register', array( $this, 'ctx_customizer_load_setting_class' ) );
            add_action( 'customize_register', array( $this, 'ctx_register_contextualizer_settings_controls_section' ) );

            // Refresh the filtrable candidates every 24 Hours
            if ( ! get_transient( 'ctx_updated_filtrable_candidates') ) {
                // when all settings have been registered, let's loop through them and record the filtrable candidate in an option
                add_action( 'customize_register', array( $this, 'ctx_set_filtrable_candidates' ), PHP_INT_MAX );
            }

        }

        // hook : customize_register
        function ctx_customizer_load_setting_class( $wp_customize ) {
            // Load the skoped settings class
            require_once(  dirname( __FILE__ ) . '/customizer/contx_setting_class.php' );
        }

        /////////////////////////////////////////////////////////////////
        // <SET FILTRABLE CANDIDATES>
        // hook : customize_register
        // What to filter ?
        // WP Core
        // theme_mods
        // theme_option

        // skip settings
        // - in the excluded list
        // - starting with widget_*
        // - starting with sidebars_*
        // - starts with nav_menu* but not nav_menu_location
        function ctx_set_filtrable_candidates( $wp_customize ) {
            // error_log( '<EXCLUDED SETTINGS>' );
            // error_log( print_r( ctx_get_excluded_settings(), true ) );
            // error_log( '</EXCLUDED SETTINGS>' );


            // $filtrable_candidates are formed this way :
            // array(
            //   'options' => array(),
            //   'multidim_options' => array(),
            //   'multidim_theme_mods' => array(),
            //   'simple_theme_mods' => array(),
            // );
            $filtrable_candidates = $this -> default_filtrable_candidates_model;

            foreach ( $wp_customize -> settings() as $set ) {
                // Is the setting id authorized ?
                if ( in_array( $set -> id , ctx_get_excluded_settings() ) )
                  continue;
                if ( 'widget_' == substr( $set -> id, 0, strlen( 'widget_' ) ) )
                  continue;
                if ( 'sidebars_' == substr( $set -> id, 0, strlen( 'sidebars_' ) ) )
                  continue;
                if ( 0 !== strpos( $set -> id , 'nav_menu_locations') && 'nav_menu' == substr( $set -> id, 0, strlen( 'nav_menu' ) ) )
                  continue;
                // shall not start with "skp__"
                if ( 0 === strpos( $set -> id, NIMBLE_SKOPE_ID_PREFIX ) )
                  continue;

                //Is the setting type authorized ?
                if ( ! in_array( $set -> type , array( 'theme_mod', 'option' ) ) )
                  continue;

                // Is the control type authorized ?
                // => we assume that the control has the same id as the setting
                if ( ! is_object( $wp_customize -> get_control( $set -> id ) ) || ! in_array( $wp_customize -> get_control( $set -> id ) -> type, ctx_get_authorized_setting_types() ) )
                  continue;

                // error_log( '<FILTRABLE CANDIDATES>' );
                // error_log( print_r( $set -> id . ' | ' . $wp_customize -> get_control( $set -> id ) -> type , true ) );
                // error_log( '</FILTRABLE CANDIDATES>' );

                $setting_keys = preg_split( '/\[/', str_replace( ']', '', $set->id ) );
                $setting_base = array_shift( $setting_keys );

                // is it a wp option ? Ex : blogname
                if ( 'option' == $set -> type && empty( $setting_keys ) ) {
                    $filtrable_candidates['options'][] = $set -> id;
                }
                // does the id looks like {theme_option_group}[{option_name}] ?
                // the question is : is it a multidimensional theme setting ?
                else if ( 'option' == $set -> type && ! empty( $setting_keys ) ) {
                    $multidim_option_name = implode( $setting_keys );
                    //error_log( print_r( implode( $setting_keys ), true ) );
                    if ( array_key_exists( $setting_base, $filtrable_candidates['multidim_options'] ) ) {
                        $filtrable_candidates['multidim_options'][ $setting_base ][] = $multidim_option_name;
                    } else {
                        $filtrable_candidates['multidim_options'][ $setting_base ] = array( $multidim_option_name );
                    }
                }
                // is it a multidimensional theme mod ? Ex : nav_menu_location[...]
                else if ( 'theme_mod' == $set -> type && ! empty( $setting_keys ) ) {
                    $multidim_option_name = implode( $setting_keys );
                    if ( array_key_exists( $setting_base, $filtrable_candidates['multidim_theme_mods'] ) ) {
                        $filtrable_candidates['multidim_theme_mods'][ $setting_base ][] = $multidim_option_name;
                    } else {
                        $filtrable_candidates['multidim_theme_mods'][ $setting_base ] = array( $multidim_option_name );
                    }
                }
                // is it a simple theme mod ? Ex : header_image
                else if ( 'theme_mod' == $set -> type ) {
                    $filtrable_candidates['simple_theme_mods'][] = $set -> id;
                }

                // error_log( $set -> id . ' | ' . $set -> type );
                // error_log( "IS MULTIDIMENSIONAL ? => " . ! empty( $setting_keys ) );
            }
            // error_log( '<FILTRABLE CANDIDATES>' );
            // error_log( print_r( $filtrable_candidates, true ) );
            // error_log( '</FILTRABLE CANDIDATES>' );

            // write in db
            set_theme_mod( 'ctx_filtrable_candidates', $filtrable_candidates );
            set_transient( 'ctx_updated_filtrable_candidates', true, 60*60*24 );// refreshed every 24 Hours
        }

        /////////////////////////////////////////////////////////////////
        // </SET FILTRABLE CANDIDATES>

        // hook : customize_register
        function ctx_register_contextualizer_settings_controls_section( $wp_customize ) {
            $wp_customize->add_panel( 'contx_panel', array(
                'title'    => __( 'Contextualizer', 'text_domain_to_be_replaced' ),
                'priority' => PHP_INT_MAX,
            ) );

            $wp_customize->add_section( 'contx_sec', array(
                'title'    => __( 'Contextualized Settings', 'text_domain_to_be_replaced' ),
                'priority' => 0,
                'panel'   => 'contx_panel',
            ) );



            $multidim_option_prefix = CONTX_OPTION_PREFIX;

            // $wp_customize->add_setting( "{$multidim_option_prefix}[contx_wp_core]", array(
            //     'default' => 'yes',
            //     'type'  => 'option'
            // ) );

            // $wp_customize->add_control( "{$multidim_option_prefix}[contx_wp_core]", array(
            //     'label'     => __( 'Contextualize WordPress Core options', 'text_domain_to_be_replaced'),
            //     //'description' => __( 'The New Skope' , 'advanced-customizer'),
            //     'type'      => 'select',
            //     'choices'   => array(
            //         'yes' => __('Yes', 'hueman'),
            //         'no'  => __('No', 'hueman')
            //     ),
            //     'section'   => 'contx_sec',
            // ) );

            // $wp_customize->add_setting( "{$multidim_option_prefix}[contx_theme_and_plugins_options]", array(
            //     'default' => 'yes',
            //     'type'  => 'option'
            // ) );
            // $wp_customize->add_control( "{$multidim_option_prefix}[contx_theme_and_plugins_options]", array(
            //     'label'     => __( 'Contextualize theme and plugins options', 'text_domain_to_be_replaced'),
            //     //'description' => __( 'The New Skope' , 'advanced-customizer'),
            //     'type'      => 'select',
            //     'choices'   => array(
            //         'yes' => __('Yes', 'hueman'),
            //         'no'  => __('No', 'hueman')
            //     ),
            //     'section'   => 'contx_sec',
            // ) );
        }


    }//class
endif;

?><?php
///////////////////////////////////////////////////////////////
// SKOP OPTION CLASS
if ( ! class_exists( 'Contx_Dynamic_Setting_Registration' ) ) :
    class Contx_Dynamic_Setting_Registration extends Contx_Customize_Register {
        //static $instance;

        // Fired in the constructor
        function ctx_schedule_customize_dynamic_setting_args_and_class_filters() {
            add_filter( 'customize_dynamic_setting_args', array( $this, 'ctx_customizer_dynamic_setting_args' ), 10, 2  );
            add_filter( 'customize_dynamic_setting_class', array( $this, 'ctx_customizer_dynamic_setting_class' ), 10, 3 );
        }

        // hook : 'customize_dynamic_setting_args'
        function ctx_customizer_dynamic_setting_args( $setting_args, $setting_id ) {
            // shall start with "skp__"
            // @see js ::setupDynamicSettingControls()
            if ( 0 !== strpos( $setting_id, NIMBLE_SKOPE_ID_PREFIX ) )
              return $setting_args;
            //error_log( 'REGISTERING DYNAMICALLY for setting =>'. $setting_id );
            return array_merge( $this -> ctx_dynamic_setting_default_data, array(
                'default'              => array(),//'DEFAULT VALUE for ' . $setting_id,
                'sanitize_callback'    => array( $this, 'ctx_customize_sanitize_callbacks_before_writing_in_db' )
            ) );
        }

        // hook : 'customize_dynamic_setting_class'
        function ctx_customizer_dynamic_setting_class( $class, $setting_id, $args ) {
            // shall start with "skp__"
            // @see js ::setupDynamicSettingControls()
            if ( 0 !== strpos( $setting_id, NIMBLE_SKOPE_ID_PREFIX ) )
              return $class;
            //error_log( 'REGISTERING CLASS DYNAMICALLY for setting =>' . $id );
            return 'Contx_Customizer_Setting';
        }


        /////////////////////////////////////////////////////////////////
        // SANITIZATION
        // The idea is to use the sanitize_callback declared for the corresponding setting
        // needed for example to save the value with the right format.
        // Example : header_textcolor can be set from the customizer to #1e73be => this will become 1e73be after sanitization (hash escaped )
        function ctx_customize_sanitize_callbacks_before_writing_in_db( $value_from_customizer ) {
            global $wp_customize;
            $new_values_from_customizer = array();
            foreach ( $value_from_customizer as $setting_data ) {
                // Normalize
                $sanitized_setting_data = wp_parse_args( $setting_data, ctx_get_default_model() );

                // Handle the header_image specific case
                // the core header_image is saved as an attachment url

                if ( is_object( $wp_customize -> get_setting( $sanitized_setting_data['setting-id'] ) ) ) {
                    $setting = $wp_customize -> get_setting( $sanitized_setting_data['setting-id'] );
                    // is there a sanitize callback ?
                    $we_have_a_callback = false;
                    $sanitize_candidate = $setting -> sanitize_callback;
                    // if array, then it an be either defined as a method of $wp_customize like _sanitize_header_textcolor for example
                    // or as a method of the setting.
                    // => let's check both cases
                    if ( is_array( $sanitize_candidate ) ) {
                        if ( isset( $sanitize_candidate[1] ) && ( method_exists( $setting, $sanitize_candidate[1] ) || method_exists( $wp_customize, $sanitize_candidate[1] ) ) ) {
                            $we_have_a_callback = true;
                        }
                    } else if ( is_string( $setting -> sanitize_callback ) && function_exists( $setting -> sanitize_callback ) ) {
                        $we_have_a_callback = true;
                    }
                    if ( $we_have_a_callback ) {
                        $sanitized_setting_data['value'] = call_user_func_array(
                            $setting -> sanitize_callback,
                            array( $sanitized_setting_data['value'] )
                        );
                    }
                    // is there a sanitize callback ?
                    // error_log( print_r( $setting_sanitize_callback );
                    // if ( method_exists( $setting, $setting -> sanitize_callback ) ) {
                    //     $sanitized_setting_data['value'] = call_user_func_array(
                    //         $setting_sanitize_callback,
                    //         array( $sanitized_setting_data['value'] )
                    //     );
                    // }
                }

                $new_values_from_customizer[] = $sanitized_setting_data;
            }
            // error_log( '<IN SANITIZATION AFTER>');
            // error_log( print_r( $new_values_from_customizer, true ) );
            // error_log( '</IN SANITIZATION AFTER>');
            return $new_values_from_customizer;
        }
    }//class
endif;

?><?php
///////////////////////////////////////////////////////////////
// SKOP OPTION CLASS
if ( ! class_exists( 'Contx' ) ) :
    final class Contx extends Contx_Dynamic_Setting_Registration {
        //static $instance;

        // Fired in the constructor
        function ctx_schedule_loading_customizer_js_css_assets() {
            // PRINT CUSTOMIZER JAVASCRIPT + LOCALIZED DATA
            add_action ( 'customize_controls_enqueue_scripts', array( $this, 'ctx_enqueue_controls_js_css' ), 20 );
            // ADD CONTX SETTING VALUES TO EXPORTED DATA IN THE CUSTOMIZER PREVIEW
            add_filter( 'skp_json_export_ready_skopes', array( $this, 'ctx_add_skoped_setting_values_to_export' ) );
        }


        /////////////////////////////////////////////////////////////////
        // hook : 'customize_controls_enqueue_scripts'
        function ctx_enqueue_controls_js_css() {
            wp_enqueue_style(
                'czr-contextualizer-style',
                sprintf('%1$s/assets/czr/css/%2$s', CONTX_BASE_URL, 'contextualizer-control.css' ),
                array( 'customize-controls' ),
                ( defined('WP_DEBUG') && true === WP_DEBUG ) ? time() : PC_AC_VERSION,
                $media = 'all'
            );

            wp_enqueue_script(
                'czr-contextualizer-control',
                sprintf('%1$s/assets/czr/js/%2$s', CONTX_BASE_URL,'contextualizer-control.js'),
                array( 'customize-controls', 'czr-skope-base', 'jquery', 'underscore'),
                ( defined('WP_DEBUG') && true === WP_DEBUG ) ? time() :  wp_get_theme() -> version,
                $in_footer = true
            );

            wp_localize_script(
                'czr-contextualizer-control',
                'contxLocalizedParams',
                array(
                    'skopableSettingsCollectionModel' => $this -> skopable_settings_collection_model,
                    'unskopableSettings' => ctx_get_excluded_settings(),
                    'authorizedSettingTypes' => ctx_get_authorized_setting_types(),

                    'dynamicSettingDefaultData' => $this -> ctx_dynamic_setting_default_data,
                    'dynamicControlDefaultData' => $this -> ctx_dynamic_control_default_data,

                    'defaultModel' => ctx_get_default_model(),
                    'i18n' => array(
                        'Confirm the removal of the customizations for'  => __('Please confirm the removal of the customizations for', 'text_domain_to_be_replaced'),
                        'Back to the site wide option' => __( 'Back to the site wide option', 'text_domain_to_be_replaced'),
                        'Can be contextualized for' => __( 'Can be contextualized for', 'text_domain_to_be_replaced'),
                        'Is contextualized for' => __('Is contextualized for', 'text_domain_to_be_replaced'),
                        'this page' => __( 'this page', 'text_domain_to_be_replaced'),
                        'This setting is already customized for this context.' => __( 'This setting is already customized for this context', 'text_domain_to_be_replaced'),
                        'All settings have already been contextualized for this page.' => __( 'All settings have already been contextualized for this page.', 'text_domain_to_be_replaced'),
                        'Contextual' => __('Contextual', 'text_domain_to_be_replaced'),

                        'This setting is already contextualized locally. The local customization will be applied in priority in this context.' => __( 'This setting is already contextualized locally. The local customization will be applied in priority in this context.', 'text_domain_to_be_replaced' ),
                        'When the setting is already customized specifically for' => __( 'When the setting is already customized specifically for', 'text_domain_to_be_replaced'),
                        'Reset' => __('Reset', 'text_domain_to_be_replaced'),


                        'this local value will be applied in priority.' => __('this local value will be applied in priority.', 'text_domain_to_be_replaced'),
                        'When the setting is contextualized, the contextual value applies in priority.' => __('When the setting is contextualized, the contextual value applies in priority.', 'text_domain_to_be_replaced'),

                        'in context' => __('in context', 'text_domain_to_be_replaced'),

                        // Hueman specifics
                        'Body Background' => __('Body Background', 'text_domain_to_be_replaced'),

                        'Header Background / Slider' => __('Header Background / Slider', 'text_domain_to_be_replaced'),
                        'Full Width Header Background / Slider' => __('Full Width Header Background / Slider', 'text_domain_to_be_replaced'),
                        'Display a full width header background' => __('Display a full width header background', 'text_domain_to_be_replaced'),

                        'Yes' => __('Yes', 'text_domain_to_be_replaced'),
                        'No' => __('No', 'text_domain_to_be_replaced'),
                        'Inherit' => __('Inherit', 'text_domain_to_be_replaced'),

                        'jump to the contextual settings' => __('jump to the contextual settings', 'text_domain_to_be_replaced')

                    )
                )
            );
        }


        // $skopes = array(
        //   array(
        //     'title'       => skp_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type ) ),
        //     'long_title'  => skp_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type, 'long' => true ) ),
        //     'ctx_title'   => skp_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type, 'long' => true, 'is_prefixed' => false ) ),
        //     'skope'       => 'local',
        //     'level'       => skp_get_skope(),
        //     'obj_id'      => skp_get_skope('id'),
        //     'skope_id'    => ctx_get_skope_id( 'local' )
        //   ),
        //   array( ... )
        //   array( ... )
        // )
        // hook : skp_json_export_ready_skopes
        function ctx_add_skoped_setting_values_to_export( $skopes ) {
            if ( ! is_array( $skopes ) ) {
                error_log( 'ctx_add_skoped_setting_values_to_export => the filtered skopes must be an array' );
            }
            $new_skopes = array();
            foreach ( $skopes as $skp_data ) {
                if ( 'global' == $skp_data['skope'] ) {
                  $new_skopes[] = $skp_data;
                  continue;
                }

                // add the values for local and group
                $skp_data[ 'values' ] = ctx_get_skoped_settings( ctx_get_skope_id( $skp_data['skope'] ) );
                $new_skopes[] = $skp_data;
            }

            // error_log( '<////////////////////$new_skopes>' );
            // error_log( print_r($new_skopes, true ) );
            // error_log( '</////////////////////$new_skopes>' );

            return $new_skopes;
        }
    }//class
endif;

?>