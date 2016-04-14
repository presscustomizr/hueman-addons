<?php
/**
 * Plugin Name: Hueman Addons
 * Plugin URI: http://presscustomizr.com
 * Description: Hueman Theme Addons
 * Version: 1.0.0
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
        /////////////////////////////////////
        self::$instance =& $this;

        //checks if is customizing : two context, admin and front (preview frame)
        $this -> is_customizing = $this -> tc_is_customizing();

        self::$theme          = $this -> tc_get_theme();
        self::$theme_name     = $this -> tc_get_theme_name();

        //check if theme is customizr* and plugin mode (did_action not triggered yet)
        //stop execution if not customizr*
        if ( false === strpos( self::$theme_name, 'hueman' ) ) {
          add_action( 'admin_notices', array( $this , 'hu_admin_notice' ) );
          return;
        }

        //SHORTCODES
        // Load custom shortcodes
        // @fromfull => should me moved in a plugin
        add_action( 'wp', array($this, 'hu_shortcodes_actions') );



        //SHARRRE
        add_action( 'wp_enqueue_scripts', array( $this, 'hu_addons_scripts' ) );
        add_filter( 'hu_social_links_sec', array( $this, 'hu_register_sharrre_settings'));
        add_action( 'wp', array($this, 'hu_sharrre_front_actions') );

    }//end of construct



    //hook : hu_social_links_sec
    function hu_register_sharrre_settings( $settings ) {
      $sharrre_settings = array(
        'sharrre' => array(
              'default'   => 1,
              'control'   => 'HU_controls',
              'label'     => __('Single &mdash; Share Bar', 'hueman'),
              'description' => __('Social sharing buttons for each article', 'hueman'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox'
        ),
        'sharrre-scrollable' => array(
              'default'   => 0,
              'control'   => 'HU_controls',
              'label'     => __('Single &mdash; Scrollable Share Bar', 'hueman'),
              'description' => __('Make social links stick to browser window when scrolling down', 'hueman'),
              'section'   => 'social_links_sec',
              'type'      => 'checkbox'
        ),
        'twitter-username' => array(
              'default'   => 0,
              //'control'   => 'HU_controls',
              'label'     => __('Twitter Username', 'hueman'),
              'description' => __('Your @username will be added to share-tweets of your posts (optional)', 'hueman'),
              'section'   => 'social_links_sec',
              'type'      => 'text',
              'transport' => 'postMessage'
        )
      );

      return array_merge( $sharrre_settings, $settings );
    }



    /**************************************************************
    ** SHORTCODES
    **************************************************************/
    function hu_shortcodes_actions() {
      load_template( dirname( __FILE__ ) . '/shortcodes.php' );
    }



    /**************************************************************
    ** SHARRRE
    **************************************************************/
    function hu_sharrre_front_actions() {
      if ( ! is_single() )
        return;
      //alter the single entry wrapper class
      add_filter( 'hu_single_entry_class', array($this, 'hu_add_sharrre_class'));

      //hook the sharrre content to the single post template
      add_action( 'hu_after_single_entry_inner', array($this, 'hu_print_sharrre_template') );
    }


    //@param $classes = array of classes
    //hook : hu_single_entry_class
    function hu_add_sharrre_class( $classes ) {
      $classes[] = 'share';
      return $classes;
    }

    //hook : hu_after_single_entry_inner
    function hu_print_sharrre_template() {
      if ( ! hu_is_checked('sharrre') )
        return;

      load_template( dirname( __FILE__ ) . '/sharrre-template.php' );
    }






    //hook : wp_enqueue_scripts
    function hu_addons_scripts() {
       //@fromfull => move in plugin
      if ( is_singular() ) {
        wp_enqueue_script(
          'sharrre',
          plugins_url('hueman-addons') . '/assets/front/js/jquery.sharrre.min.js',
          array( 'jquery' ),
          '',
          true
        );
      }
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
        $what = __( 'works only with the Hueman theme', 'hueman' );

       ?>
        <div class="error">
            <p>
              <?php
              printf( __( 'The <strong>%1$s</strong> plugin %2$s.' ,'hueman' ),
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