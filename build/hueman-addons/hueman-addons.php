<?php
/**
* Plugin Name: Hueman Addons
* Plugin URI: http://presscustomizr.com
* Description: Hueman Theme Addons
* Version: 1.0.8
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
if ( ! class_exists( 'HU_addons_plugin' ) ) :
class HU_addons_plugin {
    //Access any method or var of the class with classname::$instance -> var or method():
    static $instance;

    public static $theme;
    public static $theme_name;
    public $is_customizing;


    function __construct() {
      self::$instance =& $this;

      //checks if is customizing : two context, admin and front (preview frame)
      $this -> is_customizing = $this -> tc_is_customizing();

      self::$theme          = $this -> tc_get_theme();
      self::$theme_name     = $this -> tc_get_theme_name();

      //stop execution if not Hueman
      if ( false === strpos( self::$theme_name, 'hueman' ) ) {
        add_action( 'admin_notices', array( $this , 'hu_admin_notice' ) );
        return;
      }

      //TEXT DOMAIN
      //adds plugin text domain
      add_action( 'plugins_loaded', array( $this , 'hu_plugin_lang' ) );

      //SHORTCODES
      // Load custom shortcodes
      // @fromfull => should me moved in a plugin
      add_action( 'wp', array($this, 'hu_shortcodes_actions') );



      //SHARRRE
      add_action( 'wp_enqueue_scripts', array( $this, 'hu_addons_scripts' ) );
      add_filter( 'hu_social_links_sec', array( $this, 'hu_register_sharrre_settings'));
      add_action( 'wp', array($this, 'hu_sharrre_front_actions') );

      //CUSTOMIZER PANEL JS
      add_action( 'customize_controls_print_footer_scripts', array( $this, 'hu_extend_visibilities' ), 100 );
    }//end of construct




    //hook : hu_social_links_sec
    function hu_register_sharrre_settings( $settings ) {
      $sharrre_settings = array(
        'sharrre' => array(
              'default'   => 1,
              'control'   => 'HU_controls',
              'label'     => __('Display social sharing buttons in your single posts', 'hueman-addons'),
              'title'     => __('Social Sharring Bar Settings', 'hueman-addons'),
              'notice'    => __('Display social sharing buttons in each single articles.', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox',
              'priority'  => 40
        ),
        'sharrre-scrollable' => array(
              'default'   => 1,
              'control'   => 'HU_controls',
              'label'     => __('Make the Share Bar "sticky"', 'hueman-addons'),
              'notice'    => __('Make the social share bar stick to the browser window when scrolling down a post.', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox',
              'priority'  => 50
        ),
        'sharrre-twitter-on' => array(
              'default'   => 1,
              'control'   => 'HU_controls',
              'label'     => __('Enable Twitter Button', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox',
              'notice'    => __('Since Nov. 2015, Twitter disabled the share counts from its API. If you want to get the display count anyway, you can create an account for free (as of Feb. 2016) on [https://opensharecount.com/]. The Hueman Addons plugin is configured to use opensharecount.', 'hueman-addons'),
              'priority'  => 60
        ),
        'twitter-username' => array(
              'default'   => '',
              'control'   => 'HU_controls',
              'label'     => __('Twitter Username (without "@")', 'hueman-addons'),
              'notice'    => __('Simply enter your username without the "@" prefix. Your username will be added to share-tweets of your posts (optional).', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'text',
              'transport' => 'postMessage',
              'priority'  => 70
        ),
        'sharrre-facebook-on' => array(
              'default'   => 1,
              'control'   => 'HU_controls',
              'label'     => __('Enable Facebook Button', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox',
              'priority'  => 80
        ),
        'sharrre-google-on' => array(
              'default'   => 1,
              'control'   => 'HU_controls',
              'label'     => __('Enable Google Plus Button', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox',
              'priority'  => 90
        ),
        'sharrre-pinterest-on' => array(
              'default'   => 0,
              'control'   => 'HU_controls',
              'label'     => __('Enable Pinterest Button', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox',
              'priority'  => 100
        ),
        'sharrre-linkedin-on' => array(
              'default'   => 0,
              'control'   => 'HU_controls',
              'label'     => __('Enable LinkedIn Button', 'hueman-addons'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox',
              'priority'  => 100
        )
      );

      return array_merge( $sharrre_settings, $settings );
    }

    function hu_plugin_lang() {
      load_plugin_textdomain( 'hueman-addons' , false, basename( dirname( __FILE__ ) ) . '/lang' );
    }

    /**************************************************************
    ** SHORTCODES
    **************************************************************/
    function hu_shortcodes_actions() {
      load_template( dirname( __FILE__ ) . '/inc/shortcodes.php' );
    }



    /**************************************************************
    ** SHARRRE
    **************************************************************/
    function hu_sharrre_front_actions() {
      if ( ! is_single() )
        return;
      //alter the single entry wrapper class
      add_filter( 'hu_single_entry_class', array($this, 'hu_maybe_add_sharrre_class'));

      //hook the sharrre content to the single post template
      add_action( 'hu_after_single_entry_inner', array($this, 'hu_maybe_print_sharrre_template') );
    }


    //@param $classes = array of classes
    //hook : hu_single_entry_class
    function hu_maybe_add_sharrre_class( $classes ) {
      if ( ! hu_are_share_buttons_enabled() )
        return $classes;
      $classes[] = 'share';
      return $classes;
    }

    //hook : hu_after_single_entry_inner
    function hu_maybe_print_sharrre_template() {
      if ( ! hu_are_share_buttons_enabled() )
        return;

      load_template( dirname( __FILE__ ) . '/inc/sharrre-template.php' );
    }






    //hook : wp_enqueue_scripts
    function hu_addons_scripts() {
      if ( is_singular() ) {
        wp_enqueue_script(
          'sharrre',
          sprintf( '%1$s/assets/front/js/jQuerySharrre%2$s', plugins_url( basename( __DIR__ ) ), (defined('TC_DEV') && true === TC_DEV) ? '.js' : '.min.js' ),
          array( 'jquery' ),
          '',
          true
        );
      }
    }


    //hook : 'customize_controls_enqueue_scripts'
    function hu_extend_visibilities() {
      ?>
      <script type="text/javascript">
        (function (api, $, _) {
          var _oldDeps = api.CZR_visibilities.prototype.controlDeps;
          api.CZR_visibilities.prototype.controlDeps = _.extend( _oldDeps, {
              'sharrre' : {
                  controls: [
                    'sharrre-scrollable',
                    'sharrre-twitter-on',
                    'twitter-username',
                    'sharrre-facebook-on',
                    'sharrre-google-on',
                    'sharrre-pinterest-on',
                    'sharrre-linkedin-on'
                  ],
                  callback : function (to) {
                    return '0' !== to && false !== to && 'off' !== to;
                  }
              },
              'sharrre-twitter-on' : {
                  controls: [
                    'twitter-username'
                  ],
                  callback : function (to) {
                    return '0' !== to && false !== to && 'off' !== to;
                  }
              }
          });
        }) ( wp.customize, jQuery, _);
      </script>
      <?php
    }



    /**
    * @uses  wp_get_theme() the optional stylesheet parameter value takes into account the possible preview of a theme different than the one activated
    *
    * @return  the (parent) theme object
    */
    function tc_get_theme(){
      // Return the already set theme
      if ( self::$theme )
        return self::$theme;
      // $_REQUEST['theme'] is set both in live preview and when we're customizing a non active theme
      $stylesheet = $this -> is_customizing && isset($_REQUEST['theme']) ? $_REQUEST['theme'] : '';

      //gets the theme (or parent if child)
      $tc_theme               = wp_get_theme($stylesheet);

      return $tc_theme -> parent() ? $tc_theme -> parent() : $tc_theme;

    }

    /**
    *
    * @return  the theme name
    *
    */
    function tc_get_theme_name(){
      $tc_theme = $this -> tc_get_theme();

      return sanitize_file_name( strtolower( $tc_theme -> Name ) );
    }



    function hu_admin_notice() {
        $what = __( 'works only with the Hueman theme', 'hueman-addons' );

       ?>
        <div class="error">
            <p>
              <?php
              printf( __( 'The <strong>%1$s</strong> plugin %2$s.' ,'hueman-addons' ),
                'Hueman Addons',
                $what
              );
              ?>
            </p>
        </div>
        <?php
    }


    /**
    * Returns a boolean on the customizer's state
    * @since  3.2.9
    */
    function tc_is_customizing() {
      //checks if is customizing : two contexts, admin and front (preview frame)
      global $pagenow;
      $bool = false;
      if ( is_admin() && isset( $pagenow ) && 'customize.php' == $pagenow )
        $bool = true;
      if ( ! is_admin() && isset($_REQUEST['wp_customize']) )
        $bool = true;
      if ( $this -> tc_doing_customizer_ajax() )
        $bool = true;
      return $bool;
    }

    /**
    * Returns a boolean
    * @since  3.3.2
    */
    function tc_doing_customizer_ajax() {
      return isset( $_POST['customized'] ) && ( defined( 'DOING_AJAX' ) && DOING_AJAX );
    }

} //end of class

//Creates a new instance of front and admin
new HU_addons_plugin;

endif;

//@return bool
function hu_are_share_buttons_enabled() {
  if ( ! hu_is_checked('sharrre') )
    return;
  if ( ! hu_is_checked('sharrre-twitter-on') && ! hu_is_checked('sharrre-facebook-on') && ! hu_is_checked('sharrre-google-on') && ! hu_is_checked('sharrre-pinterest-on') && ! hu_is_checked('sharrre-linkedin-on') )
    return;
  return true;
}
