<?php
/**
* Plugin Name: Hueman Addons
* Plugin URI: http://presscustomizr.com
* Description: Hueman Theme Addons
* Version: 2.0.7
* Text Domain: hueman-addons
* Author: Press Customizr
* Author URI: http://presscustomizr.com
* License: GPLv2 or later
*/


/**
* Fires the plugin
* @author Nicolas GUILLAUME
* @since 1.0
*/
if ( ! class_exists( 'HU_AD' ) ) :
  class HU_AD {
      //Access any method or var of the class with classname::$instance -> var or method():
      static $instance;

      public static $theme;
      public static $theme_name;
      public $is_customizing;
      public $last_theme_version_sync;
      public $minimal_authorized_theme_version;

      public static function ha_get_instance() {
          if ( ! isset( self::$instance ) && ! ( self::$instance instanceof HU_AD ) )
            self::$instance = new HU_AD();
          return self::$instance;
      }


      function __construct() {
        self::$instance =& $this;

        //last version sync
        $this -> last_theme_version_fmk_sync = '3.3.4';
        $this -> minimal_authorized_theme_version = '3.3.0';

        //checks if is customizing : two context, admin and front (preview frame)
        $this -> is_customizing = $this -> ha_is_customizing();

        self::$theme          = $this -> ha_get_theme();
        self::$theme_name     = $this -> ha_get_theme_name();

        if( ! defined( 'HA_BASE_PATH' ) ) define( 'HA_BASE_PATH' , plugin_dir_path( __FILE__ ) );
        if( ! defined( 'HA_BASE_URL' ) ) define( 'HA_BASE_URL' , trailingslashit( plugins_url( basename( __DIR__ ) ) ) );

        if( ! defined( 'HA_SKOP_ON' ) ) define( 'HA_SKOP_ON' , true );
        if( ! defined( 'HA_SEK_ON' ) ) define( 'HA_SEK_ON' , false );

        //stop execution if not Hueman or if minimal version of Hueman is not installed
        if ( false === strpos( self::$theme_name, 'hueman' ) || version_compare( self::$theme -> version, $this -> minimal_authorized_theme_version, '<' ) ) {
          add_action( 'admin_notices', array( $this , 'ha_admin_notice' ) );
          return;
        }
        //TEXT DOMAIN
        //adds plugin text domain
        add_action( 'plugins_loaded', array( $this , 'ha_plugin_lang' ) );

        //fire
        $this -> ha_load();

        add_action('wp_head', array( $this, 'hu_admin_style') );
      }//construct


      function ha_load() {
        /* ------------------------------------------------------------------------- *
         *  Loads Features
        /* ------------------------------------------------------------------------- */
        require_once( HA_BASE_PATH . 'inc/sharrre/ha-sharrre.php' );
        new HA_Sharrre();
        require_once( HA_BASE_PATH . 'inc/shortcodes/ha-shortcodes.php' );
        new HA_Shortcodes();

        /* ------------------------------------------------------------------------- *
         *  Loads Customizer
        /* ------------------------------------------------------------------------- */
        require_once( HA_BASE_PATH . 'inc/czr/ha-czr.php' );
        new HA_Czr();

        /* ------------------------------------------------------------------------- *
         *  Loads BETAS
        /* ------------------------------------------------------------------------- */
        if ( $this -> ha_is_skop_on() ) {
          if ( defined('TC_DEV') && true === TC_DEV ) {
              if ( file_exists( HA_BASE_PATH . 'inc/skop/_dev/skop-x-fire.php' ) ) {
                  require_once( HA_BASE_PATH . 'inc/skop/_dev/skop-x-fire.php' );
              }
          } else {
              require_once( HA_BASE_PATH . 'inc/skop/czr-skop.php' );
          }
        }
        if ( HA_SEK_ON ) {
          require_once( HA_BASE_PATH . 'inc/sek/init-sektions.php' );
        }
      }


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
          if ( version_compare( self::$theme -> version, $this -> minimal_authorized_theme_version, '<' ) ) {
            $message = sprintf( __( 'This version of the <strong>%1$s</strong> plugin requires at least the version %2$s of the Hueman theme.', 'hueman-addons' ),
              'Hueman Addons',
              $this -> minimal_authorized_theme_version
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
        return is_admin() && isset( $pagenow ) && 'customize.php' == $pagenow;
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

      //@return bool
      //skop shall not be activated when previewing the theme from the customizer

      function ha_is_skop_on() {
        global $wp_version;
        if ( $this -> ha_isprevdem() )
          return;
        return apply_filters( 'ha_is_skop_on', version_compare( $wp_version, '4.7', '>=' ) );
      }


      //Check the existence of the 'changeset_uuid' method in the WP_Customize_Manager to state if the changeset feature is
      function ha_is_changeset_enabled( $wp_customize = null ) {
        if ( $this -> ha_is_customizing() && ( is_null( $wp_customize ) || ! is_object( $wp_customize ) ) ) {
          global $wp_customize;
        }
        return $this -> ha_is_customizing() && method_exists( $wp_customize, 'changeset_uuid');
      }

      //@return an array of unfiltered options
      //=> all options or a single option val
      function ha_get_raw_option( $opt_name = null, $opt_group = null ) {
          $alloptions = wp_cache_get( 'alloptions', 'options' );
          $alloptions = maybe_unserialize($alloptions);
          if ( ! is_null( $opt_group ) && isset($alloptions[$opt_group]) ) {
            $alloptions = maybe_unserialize($alloptions[$opt_group]);
          }
          if ( is_null( $opt_name ) )
            return $alloptions;
          return isset( $alloptions[$opt_name] ) ? maybe_unserialize($alloptions[$opt_name]) : false;
      }

      function ha_isprevdem() {
        $_active_theme = $this -> ha_get_raw_option( 'template' );
        return ( $_active_theme != strtolower('Hueman') );
      }

      //hook : wp_head
      //only on front end and if user is logged-in
      function hu_admin_style() {
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
  if ( ! defined('TC_DEV') || true !== TC_DEV )
    return;
  error_log( $data );
}


//Creates a new instance
function HU_AD() {
  return HU_AD::ha_get_instance();
}
HU_AD();