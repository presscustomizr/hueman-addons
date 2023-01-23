<?php
if( ! defined( 'IS_PRESSCUSTOMIZR' ) ) define( 'IS_PRESSCUSTOMIZR' , false );
if( ! defined( 'HA_BASE_PATH' ) ) define( 'HA_BASE_PATH' , trailingslashit( dirname( dirname( __FILE__ ) ) ) );

//are we in pro theme?
if ( defined( 'HU_IS_PRO' ) && HU_IS_PRO ) {
    if( ! defined( 'HA_BASE_URL' ) ) define( 'HA_BASE_URL' , HU_BASE_URL );
} else {
    if( ! defined( 'HA_BASE_URL' ) ) define( 'HA_BASE_URL' , trailingslashit( plugins_url( basename( dirname( __DIR__ ) ) ) ) );
}

/**
* Fires the plugin or the theme addon
* @author Nicolas GUILLAUME
* @since 1.0
*/
if ( ! class_exists( 'HU_AD' ) ) :
  final class HU_AD {
      //Access any method or var of the class with classname::$instance -> var or method():
      static $instance;

      public static $theme;
      public static $theme_name;
      public $is_customizing;
      private $is_pro_theme;
      private $is_pro_addons;

      public $models;

      public $pro_header;//Will store the pro header instance
      public $pro_footer;//Will store the pro footer instance
      public $pro_grids;//Will store the pro grids instance
      public $pro_infinite;//Will store the pro infinite scroll instance
      public $pro_skins;
      public $pro_related_posts;
      public $pro_custom_scripts;

      public static function ha_get_instance() {
          if ( ! isset( self::$instance ) && ! ( self::$instance instanceof HU_AD ) )
            self::$instance = new HU_AD();
          return self::$instance;
      }


      function __construct() {
          self::$instance =& $this;

          //checks if is customizing : two context, admin and front (preview frame)
          $this -> is_customizing = $this -> ha_is_customizing();

          self::$theme          = $this -> ha_get_theme();
          self::$theme_name     = $this -> ha_get_theme_name();

          //did_action('plugins_loaded') ?
          //PRO THEME / PRO ADDON ?
          $this->is_pro_theme   = ( ! defined( 'HU_IS_PRO_ADDONS' ) || ( defined( 'HU_IS_PRO_ADDONS' ) && false == HU_IS_PRO_ADDONS ) ) && ! defined( 'IS_HUEMAN_ADDONS' );
          $this->is_pro_addons  = ( defined( 'HU_IS_PRO_ADDONS' ) && false != HU_IS_PRO_ADDONS ) || ( ! did_action('plugins_loaded') && file_exists( HA_BASE_PATH . 'addons/ha-init-pro.php' ) );

          //stop execution if not Hueman or if minimal version of Hueman is not installed
          if ( ! defined( 'HU_IS_PRO' ) || ! HU_IS_PRO ) {
              if ( false === strpos( self::$theme_name, 'hueman' ) || version_compare( self::$theme -> version, MINIMAL_AUTHORIZED_THEME_VERSION, '<' ) ) {
                add_action( 'admin_notices', array( $this , 'ha_admin_notice' ) );
                $this->is_pro_theme = $this->is_pro_addons = false;
                return;
              }
          }

          //TEXT DOMAIN
          //adds plugin text domain
          add_action( 'plugins_loaded', array( $this , 'ha_plugin_lang' ) );

          //fire
          $this -> ha_load();

          add_action('wp_head', array( $this, 'hu_admin_style') );

          //register skope customizer setting-control to enable / disable
          add_filter( 'hu_admin_sec'   , array( $this, 'ha_register_skope_option' ) );

      }//construct


      //hook : 'hu_admin_sec'
      function ha_register_skope_option( $settings ) {
        $settings = is_array( $settings ) ? $settings : array();
        $skope_settings = array(
          'enable-skope' => array(
              'default'   => 'yes',
              'control'   => 'HU_controls',
              'label'     => __('Enable a "per page" customization', 'hueman-addons'),
              'notice'    => __('Enabling this option allows you to customize each page as a stand alone website.', 'hueman-addons'),
              'section'   => 'admin_sec',
              'type'      => 'select',
              'choices'   => array(
                  'yes'     => __( 'Enable', 'hueman'),
                  'no'      => __( 'Disable', 'hueman')
              ),
              'priority'  => 0
          )
        );
        return array_merge( $skope_settings, $settings );
      }

      /* ------------------------------------------------------------------------- *
      *  MODELS UTILITIES
      /* ------------------------------------------------------------------------- */
      //When doing partial ajax, get the model directly from the setter
      //in other cases, use the cached one
      function ha_get_model( $model_name , $setter = null , $args = array() ) {
          $model_data = array();
          if ( ha_is_partial_ajax_request() ) {
              $model_data = call_user_func_array( $setter, $args );
          } else {
              $_models = $this -> models;
              if ( ! is_array($_models) ) {
                  ha_error_log( 'Problem in HU_AD::ha_get_model : attempting to get a model (' . $model_name . ') but the HU_AD::models property is not populated yet.');
              }
              $model_data = ( array_key_exists( $model_name, $_models ) && array_key_exists( 'data', $_models[$model_name] ) ) ? $_models[ $model_name ]['data'] : false;
          }

          return $model_data;
      }

      //@return void()
      //@param $setter can be a function or an array with a class and a method array( $this, '_get_pro_header_model' )
      function ha_set_model( $model_name, $setter = null , $args = array() ) {
          $_models = $this -> models;
          $model_data = call_user_func_array( $setter, $args );

          if ( ! is_string( $model_name ) || empty( $model_name ) || ! is_array( $model_data ) || empty( $model_data ) ) {
            wp_die('Hueman Addons : model not properly defined.');
          }
          $_models[$model_name] = array( 'data' => $model_data, 'setter' => $setter, 'args' => $args );
          $this -> models = $_models;
      }

      /* ------------------------------------------------------------------------- *
      *  I am a man in constant sorrow
      /* ------------------------------------------------------------------------- */
      function ha_is_pro_addons() {
        return $this->is_pro_addons;
      }

      function ha_is_pro_theme() {
        return $this->is_pro_theme;
      }

      //@return the right url path whether we are in plugin or pro theme
      function ha_get_base_url() {
        return defined( 'HU_BASE' ) && HU_IS_PRO ? HU_BASE_URL : HA_BASE_URL;
      }


      /* ------------------------------------------------------------------------- *
      *  I am a man in constant sorrow
      /* ------------------------------------------------------------------------- */
      // fired @__construct()
      function ha_load() {
          /* ------------------------------------------------------------------------- *
           *  Loads Features
          /* ------------------------------------------------------------------------- */
          require_once( HA_BASE_PATH . 'addons/sharrre/ha-sharrre.php' );
          new HA_Sharrre();
          require_once( HA_BASE_PATH . 'addons/shortcodes/ha-shortcodes.php' );
          new HA_Shortcodes();
          if ( is_admin() && ! $this -> ha_is_customizing() ) {
            require_once( HA_BASE_PATH . 'addons/admin/ha-hs-doc.php' );
          }

          /* ------------------------------------------------------------------------- *
           *  Loads Customizer
          /* ------------------------------------------------------------------------- */
          require_once( HA_BASE_PATH . 'addons/czr/ha-czr.php' );
          new HA_Czr();

          /* ------------------------------------------------------------------------- *
           *  Loads OLD SKOP
          /* ------------------------------------------------------------------------- */
          // if ( ha_is_skop_on() ) {
          //   if ( defined('CZR_DEV') && true === CZR_DEV ) {
          //       if ( file_exists( HA_BASE_PATH . 'addons/skop/_dev/skop-x-fire.php' ) ) {
          //           require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-x-fire.php' );
          //       }
          //   } else {
          //       require_once( HA_BASE_PATH . 'addons/skop/czr-skop.php' );
          //   }
          // }
          /* ------------------------------------------------------------------------- *
           *  Loads PRO
          /* ------------------------------------------------------------------------- */
          if ( $this -> ha_is_pro_addons() || $this -> ha_is_pro_theme() ) {
              require_once( HA_BASE_PATH . 'addons/ha-init-pro.php' );
          }
      }//ha_load()


      function ha_plugin_lang() {
        load_plugin_textdomain( 'hueman-addons' , false, basename( dirname( __FILE__ ) ) . '/lang' );
      }

      /**
      * @uses  wp_get_theme() the optional stylesheet parameter value takes into account the possible preview of a theme different than the one activated
      *
      * @return  the (parent) theme object
      */
      function ha_get_theme(){
        // Return the already set theme
        if ( self::$theme )
          return self::$theme;
        // $_REQUEST['theme'] is set both in live preview and when we're customizing a non active theme
        $stylesheet = $this -> is_customizing && isset($_REQUEST['theme']) ? $_REQUEST['theme'] : '';

        //gets the theme (or parent if child)
        $ha_theme               = wp_get_theme($stylesheet);

        return $ha_theme -> parent() ? $ha_theme -> parent() : $ha_theme;

      }

      /**
      *
      * @return  the theme name
      *
      */
      function ha_get_theme_name(){
        $ha_theme = $this -> ha_get_theme();

        return sanitize_file_name( strtolower( $ha_theme -> Name ) );
      }


      //hook : admin_notices
      function ha_admin_notice() {
          if ( version_compare( self::$theme -> version, MINIMAL_AUTHORIZED_THEME_VERSION, '<' ) ) {
            $message = sprintf( __( 'This version of the <strong>%1$s</strong> plugin requires at least the version %2$s of the Hueman theme.', 'hueman-addons' ),
              'Hueman Addons',
              MINIMAL_AUTHORIZED_THEME_VERSION
            );
          } else if ( false === strpos( self::$theme_name, 'hueman' ) ) {
            $message = sprintf( __( 'The <strong>%1$s</strong> plugin %2$s.', 'hueman-addons' ),
              'Hueman Addons',
              __( 'works only with the Hueman theme', 'hueman-addons' )
            );
          } else {
            return;
          }

        ?>
          <div class="error"><p><?php echo $message; ?></p></div>
        <?php
      }


      /**
      * Is the customizer left panel being displayed ?
      * @return  boolean
      * @since  3.3+
      */
      function ha_is_customize_left_panel() {
        global $pagenow;
        // the check on $pagenow does NOT work on multisite install @see https://github.com/presscustomizr/nimble-builder/issues/240
        // That's why we also check with other global vars
        // @see wp-includes/theme.php, _wp_customize_include()
        $is_customize_php_page = ( is_admin() && 'customize.php' == basename( $_SERVER['PHP_SELF'] ) );
        $is_customize_admin_page_one = (
          $is_customize_php_page
          ||
          ( isset( $_REQUEST['wp_customize'] ) && 'on' == $_REQUEST['wp_customize'] )
          ||
          ( ! empty( $_GET['customize_changeset_uuid'] ) || ! empty( $_POST['customize_changeset_uuid'] ) )
        );
        $is_customize_admin_page_two = is_admin() && isset( $pagenow ) && 'customize.php' == $pagenow;
        return $is_customize_admin_page_one || $is_customize_admin_page_two;
      }


      /**
      * Is the customizer preview panel being displayed ?
      * @return  boolean
      * @since  3.3+
      */
      function ha_is_customize_preview_frame() {
        return is_customize_preview() || ( ! is_admin() && isset($_REQUEST['customize_messenger_channel']) );
      }

      function ha_is_previewing_live_changeset() {
        return ! isset( $_POST['customize_messenger_channel']) && is_customize_preview();
      }

      /**
      * Always include wp_customize or customized in the custom ajax action triggered from the customizer
      * => it will be detected here on server side
      * typical example : the donate button
      *
      * @return boolean
      * @since  3.3+
      */
      function ha_doing_customizer_ajax() {
        $_is_ajaxing_from_customizer = isset( $_POST['customized'] ) || isset( $_POST['wp_customize'] );
        return $_is_ajaxing_from_customizer && ( defined( 'DOING_AJAX' ) && DOING_AJAX );
      }

      /**
      * Are we in a customization context ? => ||
      * 1) Left panel ?
      * 2) Preview panel ?
      * 3) Ajax action from customizer ?
      * @return  bool
      * @since  3.3+
      */
      function ha_is_customizing() {
        //checks if is customizing : two contexts, admin and front (preview frame)
        return in_array( 1, array(
          $this -> ha_is_customize_left_panel(),
          $this -> ha_is_customize_preview_frame(),
          $this -> ha_doing_customizer_ajax()
        ) );
      }



      //Check the existence of the 'changeset_uuid' method in the WP_Customize_Manager to state if the changeset feature is
      function ha_is_changeset_enabled( $wp_customize = null ) {
        if ( $this -> ha_is_customizing() && ( is_null( $wp_customize ) || ! is_object( $wp_customize ) ) ) {
          global $wp_customize;
        }
        return $this -> ha_is_customizing() && method_exists( $wp_customize, 'changeset_uuid');
      }


      //hook : wp_head
      //only on front end and if user is logged-in
      function hu_admin_style() {
        if ( ! is_user_logged_in() )
          return;
        ?>
            <script type="text/javascript" id="ha-customize-btn">
              jQuery( function($) {
                  $( "#wp-admin-bar-customize").find('a').attr('title', '<?php _e( "Customize this page !", "hueman-addons"); ?>' );
              });
            </script>
            <style type="text/css" id="ha-fun-ab">
              @-webkit-keyframes super-rainbow {
                0%   { text-shadow : 0px 0px 2px;}
                20%  { text-shadow : 0px 0px 5px; }
                40%  { text-shadow : 0px 0px 10px; }
                60%  { text-shadow : 0px 0px 13px }
                80%  { text-shadow : 0px 0px 10px; }
                100% { text-shadow : 0px 0px 5px; }
              }
              @-moz-keyframes super-rainbow {
                0%   { text-shadow : 0px 0px 2px;}
                20%  { text-shadow : 0px 0px 5px; }
                40%  { text-shadow : 0px 0px 10px; }
                60%  { text-shadow : 0px 0px 13px }
                80%  { text-shadow : 0px 0px 10px; }
                100% { text-shadow : 0px 0px 5px; }
              }

              #wp-admin-bar-customize .ab-item:before {
                  color:#7ECEFD;
                  -webkit-animation: super-rainbow 4s infinite linear;
                   -moz-animation: super-rainbow 4s infinite linear;
              }
            </style>
        <?php
      }
  } //end of class
endif;


function ha_error_log( $data ) {
  if ( ! defined('CZR_DEV') || true !== CZR_DEV )
    return;
  error_log( $data );
}

/**
 * Inspired to the WordPress locate_template()
 */
function ha_locate_template( $template_names, $load = false, $require_once = true ) {
    $located = '';

    foreach ( (array) $template_names as $template_name ) {
        if ( !$template_name ) {
          continue;
        }
        if ( file_exists( STYLESHEETPATH . '/' . $template_name ) ) {
          $located = STYLESHEETPATH . '/' . $template_name;
          break;
        } elseif ( file_exists( TEMPLATEPATH . '/' . $template_name ) ) {
          $located = TEMPLATEPATH . '/' . $template_name;
          break;
        } elseif ( HA_BASE_PATH !== TEMPLATEPATH && file_exists( HA_BASE_PATH . '/' . $template_name ) ) {
          $located = HA_BASE_PATH . '/' . $template_name;
          break;
        }
    }

    if ( $load && '' != $located ) {
      load_template( $located, $require_once );
    }

    return $located;
}


/* ------------------------------------------------------------------------- *
 *  LOADS CZR BASE FMK
/* ------------------------------------------------------------------------- */
// czr base fmk is loaded @after_setup_theme:10 in Nimble
add_action( 'after_setup_theme', 'hu_load_czr_base_fmk', 15 );
function hu_load_czr_base_fmk() {
    if ( did_action( 'nimble_base_fmk_loaded' ) ) {
        if ( ( defined( 'CZR_DEV' ) && CZR_DEV ) || ( defined( 'NIMBLE_DEV' ) && NIMBLE_DEV ) ) {
            error_log('Hueman Pro Addons => The czr_base_fmk has already been loaded');
        }
        return;
    }
    require_once(  HA_BASE_PATH . '/inc/czr-base-fmk/czr-base-fmk.php' );
    \hu_czr_fmk\CZR_Fmk_Base( array(
       'base_url' => HA_BASE_URL . 'inc/czr-base-fmk',
       'version' => defined( 'HUEMAN_VER' ) ? HUEMAN_VER : time()
    ));
}

















/* ------------------------------------------------------------------------- *
 *  LOADS SKOPE DEPENDANT COMPONENTS
/* ------------------------------------------------------------------------- */
// FUNCTION ha_is_skop_on() is declared in /addons/ha-functions.php
// function ha_is_skop_on() {
//     global $wp_version;
//     if( ! defined( 'HA_SKOP_ON' ) ) {
//         //'enable-skope' option can take two string values : "yes" and "no".
//         //If the option is not set yet, which is the most common case, it means that it is enabled ( @see default value == "yes" when registering the setting )
//         $_skope_enable_val = ha_get_raw_option( 'enable-skope' , 'hu_theme_options');
//         define( 'HA_SKOP_ON' , ! is_string( $_skope_enable_val ) || 'yes' == $_skope_enable_val );
//     }
//     return apply_filters( 'ha_is_skop_on', HA_SKOP_ON && ! ha_isprevdem() && version_compare( $wp_version, '4.7', '>=' ) );
// }






/* ------------------------------------------------------------------------- *
 *  BODY BG + HEADER SLIDER => DYNAMICALLY REGISTER settings prefixed with '_contextualizer_ui_'
/* ------------------------------------------------------------------------- */
add_filter( 'customize_dynamic_setting_args', 'hu_ad_set_contextualized_modules_setting_args', 10, 2 );
function hu_ad_set_contextualized_modules_setting_args( $setting_args, $setting_id ) {
    //error_log( 'hu_ad_set_contextualized_modules_setting_args => DYN REGISTRATION => ' . $setting_id );
    // _contextualizer_ui_ => @see in ::registerBodyBGmoduleSettingControl()
    if ( 0 === strpos( $setting_id, '_contextualizer_ui_' ) ) {
        //sek_error_log( 'DYNAMICALLY REGISTERING SEK SETTING => ' . $setting_id );
        return array(
            'transport' => 'refresh',
            'type' => '_contextualizer_ui_',//won't be saved as is,
            'default' => array()
        );
    }
    return $setting_args;
}




/* ------------------------------------------------------------------------- *
 *  Add filters for tc_theme_options when using the contextualizer
 *  action "ctx_set_filters_for_opt_group_{$opt_group}" is declared in the contextualizer module => Contx_Options::ctx_setup_option_filters()
/* ------------------------------------------------------------------------- */
add_action( "ctx_set_filters_for_opt_group___hu_theme_options"  , 'hu_add_support_for_contextualizer');
// hook ctx_set_filters_for_opt_group___tc_theme_options
// @param $opt_names = array() of Customizr options short name
function hu_add_support_for_contextualizer( $opt_names = array() ) {
    //sek_error_log( '$opt_names ? ', $opt_names );
    if ( ! is_array( $opt_names ) || ! function_exists( 'ctx_get_opt_val' ) )
      return;

    foreach ( $opt_names as $opt_name ) {
        // filter declared in /wp-content/themes/hueman/functions/class-utils.php
        // apply_filters( "hu_opt_{$option_name}" , $_single_opt , $option_name , $option_group, $_default_val );
        add_filter( "hu_opt_{$opt_name}", function( $opt_value, $opt_name ) {
            return ctx_get_opt_val( $opt_value, $opt_name, 'hu_theme_options'  );
        }, 100, 2 );
    }
}


if ( ha_is_skop_on() ) {
    /* ------------------------------------------------------------------------- *
     *  RUN BACKWARD COMPATIBILITIES
    /* ------------------------------------------------------------------------- */
    $contx_update_status = get_option('hu_contx_update_june_2018_status');
    // If the backward compat has been done properly, the $contx_update_status should take the following values :
    // 1) '_nothing_to_map_' => when the user started using the theme after the contx update (june 2018), or if the previous skoping had not been used
    // 2) '_mapping_done_' => each new skope posts have been written and their corresponding theme mod set. @see ctx_update_skope_post() function
    // 3) '_error_when_mapping_' => if a problem occured when creating one of the new skope post
    if ( '_error_when_mapping_' === $contx_update_status || ( '_nothing_to_map_' !== $contx_update_status && '_mapping_done_' !== $contx_update_status ) || isset($_GET['rewrite_new_contx_options'] ) ) {
          require_once( HA_BASE_PATH . 'addons/ha-backward-compatibility-after-setup-theme-40.php' );
    }

    // DECEMBER 2018 Retro Compat
    // It's related to a modification of the skope_id when home is a static page
    // Was skp__post_page_home
    // Now is skp__post_page_{$static_home_page_id}
    // This was introduced to facilitate the compatibility of the Nimble Builder with multilanguage plugins like polylang
    // => Allows user to create a different home page for each languages
    //
    // If the current home page is not a static page, we don't have to do anything
    // If not, the sections currently saved for skope skp__post_page_home, must be moved to skope skp__post_page_{$static_home_page_id}
    // => this means that we need to update the post_id saved for theme mod : 'skp__post_page_{$static_home_page_id}';
    // to the value of the one saved for theme mod 'skp__post_page_home';
    $december_2018_compat_opt_name = 'hu_contx_update_decemb_2018_status';
    if ( 'done' !== get_option( $december_2018_compat_opt_name ) ) {
        if ( 'page' === get_option( 'show_on_front' ) ) {
            $home_page_id = (int)get_option( 'page_on_front' );
            if ( 0 < $home_page_id ) {
                // get the post id storing the current sections on home
                // @see ctx_get_skope_post()
                $current_theme_mod = 'skp__post_page_home';
                $post_id_storing_home_page_sections = (int)get_theme_mod( $current_theme_mod );
                if ( $post_id_storing_home_page_sections > 0 ) {
                    $new_theme_mod = "skp__post_page_{$home_page_id}";
                    set_theme_mod( $new_theme_mod, $post_id_storing_home_page_sections );
                }
            }
        }
        update_option( $december_2018_compat_opt_name, 'done');
    }

    $current_stylesheet = get_stylesheet();

    // APRIL 2020 theme mods cleaning
    // see https://github.com/presscustomizr/hueman-pro-addons/issues/210
    $april_2020_theme_mods_cleaning = 'hu_theme_mods_april_2020_backup';
    if ( !get_option( $april_2020_theme_mods_cleaning ) ) {
        $current_theme_mods = get_theme_mods();
        $new_theme_mods = array();
        // We want to remove all entries looking like : [skp__post_page_809] => -1
        foreach ( $current_theme_mods as $_mod_name => $_mod_val ) {
            // $mod_name should look like skp__post_page_809
            if ( false === strpos( $_mod_name, 'skp__') || 0 !== strpos( $_mod_name, 'skp__') ) {
                $new_theme_mods[$_mod_name] = $_mod_val;
            } else {
                if ( -1 !== $_mod_val ) {
                    $new_theme_mods[$_mod_name] = $_mod_val;
                }
            }
        }
        update_option( "theme_mods_{$current_stylesheet}", $new_theme_mods );
        // Backup the previous theme_mods in case we need to restore them in a future release
        update_option( $april_2020_theme_mods_cleaning, $current_theme_mods);
    }


    /* ------------------------------------------------------------------------- *
     *  SYNCHRONIZES THEME MODS IF SWITCHING FROM A HUEMAN THEME TO ANOTHER ( hueman free to hueman pro, hueman to hueman child, ... )
    /* ------------------------------------------------------------------------- */
    //sek_error_log('theme_mods_hueman-pro', get_option('theme_mods_hueman-pro') );

    // this $_GET params allows us to force a re-sync with a given stylesheet slug.
    $stylesheet_synced = isset( $_GET['sync_contx_with_stylesheet'] ) ? $_GET['sync_contx_with_stylesheet'] : get_option( 'hu_contx_theme_mods_synced_for_stylesheet' );
    if ( $current_stylesheet != $stylesheet_synced ) {
          // May be copy the contx theme mods from the previous stylesheet
          if ( false != $stylesheet_synced && ! empty( $stylesheet_synced ) ) {
              $previous_theme_mods = get_option( "theme_mods_{$stylesheet_synced}");

              if ( is_array( $previous_theme_mods ) ) {
                  foreach ( $previous_theme_mods as $mod_id => $mod_value ) {
                      // the contx theme mods id starts with 'skp__*'
                      if ( false === strpos( $mod_id, 'skp__') )
                        continue;
                      // write the contx hueman theme mod in the hueman pro theme mods
                      set_theme_mod( $mod_id, $mod_value );// the $mod_value is a post id for each skope
                  }
              }
          }
          // And set the option with the current stylesheet
          update_option( 'hu_contx_theme_mods_synced_for_stylesheet', $current_stylesheet );
    }


    /* ------------------------------------------------------------------------- *
     *  LOADS SKOPE
    /* ------------------------------------------------------------------------- */
    // skope is loaded @after_setup_theme:10 in Nimble
    add_action( 'after_setup_theme', 'hu_load_skope', 20 );
    function hu_load_skope() {
        if ( did_action( 'nimble_skope_loaded' ) ) {
            if ( ( defined( 'CZR_DEV' ) && CZR_DEV ) || ( defined( 'NIMBLE_DEV' ) && NIMBLE_DEV ) ) {
                error_log('Hueman Pro Addons => The skope has already been loaded');
            }
            return;
        }
        require_once( HA_BASE_PATH . 'inc/czr-skope/index.php' );
        \hueman_skp\Flat_Skop_Base( array(
            'base_url_path' => HA_BASE_URL . '/inc/czr-skope'
        ) );
    }


    /* ------------------------------------------------------------------------- *
     *  LOADS CONTEXTUALIZER
    /* ------------------------------------------------------------------------- */
    require_once( HA_BASE_PATH . 'contextualizer/ccat-contextualizer.php' );
    // If in hueman-pro-addons or in hueman-pro theme
    // add_action('hu_hueman_loaded', function() {
    //     Flat_Skop_Base();
    // });
    // Note : skope dependant. skope is loaded @after_setup_theme:30
    add_action('after_setup_theme', 'hu_load_contextualizer', 30 );
    function hu_load_contextualizer() {
        Contx( array(
            'base_url_path' => HA_BASE_URL . 'contextualizer'
        ) );
    }




    /* ------------------------------------------------------------------------- *
     *   add the body-background to the collection of filtrable candidates
    /* ------------------------------------------------------------------------- */
    add_filter( 'ctx_filtrable_candidates_before_setting_up_option_filters', 'hu_ad_add_body_bg_to_filtrable_candidates' );
    function hu_ad_add_body_bg_to_filtrable_candidates( $filtrable_candidates) {
        if ( ! ctx_we_can_contextualize_not_wp_core_options() )
          return $filtrable_candidates;

        if ( !empty( $filtrable_candidates[ 'multidim_options' ] ) ) {
            if ( !empty( $filtrable_candidates[ 'multidim_options' ][ 'hu_theme_options'] ) ) {
                $filtrable_candidates[ 'multidim_options' ][ 'hu_theme_options'][] = 'body-background';
            }
        }

        return $filtrable_candidates;
    }
}//if ( ha_is_skop_on() )











//Creates a new instance
function HU_AD() {
  return HU_AD::ha_get_instance();
}
HU_AD();