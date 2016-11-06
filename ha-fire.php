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
if ( ! class_exists( 'HU_AD' ) ) :
  class HU_AD {
      //Access any method or var of the class with classname::$instance -> var or method():
      static $instance;

      public static $theme;
      public static $theme_name;
      public $is_customizing;

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

        if( ! defined( 'HA_BASE_PATH' ) ) define( 'HA_BASE_PATH' , plugin_dir_path( __FILE__ ) );
        if( ! defined( 'HA_BASE_URL' ) ) define( 'HA_BASE_URL' , trailingslashit( plugins_url( basename( __DIR__ ) ) ) );

        if( ! defined( 'HA_SKOP_ON' ) ) define( 'HA_SKOP_ON' , apply_filters( 'ha_is_skop_on', true ) );
        if( ! defined( 'HA_SEK_ON' ) ) define( 'HA_SEK_ON' , false );

        //stop execution if not Hueman
        if ( false === strpos( self::$theme_name, 'hueman' ) ) {
          add_action( 'admin_notices', array( $this , 'ha_admin_notice' ) );
          return;
        }
        //TEXT DOMAIN
        //adds plugin text domain
        add_action( 'plugins_loaded', array( $this , 'ha_plugin_lang' ) );

        //fire
        $this -> ha_load();
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
          require_once( HA_BASE_PATH . 'inc/skop/init-skop.php' );
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



      function ha_admin_notice() {
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
      function ha_is_skop_on() {
        return defined( 'HA_SKOP_ON' ) ? HA_SKOP_ON : false;
      }


      //Check the existence of the 'changeset_uuid' method in the WP_Customize_Manager to state if the changeset feature is
      function ha_is_changeset_enabled( $wp_customize = null ) {
        if ( $this -> ha_is_customizing() && ( is_null( $wp_customize ) || ! is_object( $wp_customize ) ) ) {
          global $wp_customize;
        }
        return apply_filters( 'ha_is_changeset_enabled',
          $this -> ha_is_customizing() && method_exists( $wp_customize, 'changeset_uuid')
        );
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






















/*********************************************************************
**********************************************************************/
// $options = get_option('hu_theme_options');
// $options['module-collection'] = array();
// $options['sektions'] = array();
// update_option( 'hu_theme_options', $options );

/*add_action('__before_content', function() {
  ?>
    <pre>
      <?php print_r(hu_get_option( 'use-header-image' )); ?>
    </pre>
  <?php
  ?>
    <pre>
      <?php print_r(  HA_SKOP_OPT() -> ha_get_cached_opt('local', 'header_image') ); ?>
    </pre>
  <?php
});*/

/*add_action( '__before_content', function() {
    global $wp_registered_widgets;
    ?>
      <pre>
        <?php print_r($wp_registered_widgets); ?>
      </pre>
    <?php
    ?>
      <pre>
        <?php print_r(get_option('widget_search')); ?>
      </pre>
    <?php
});*/


//add_action('__before_content', 'ha_test_display_skope_options');
//add_action('__before_content', 'print_options');
//add_action('__before_content', 'print_labels');
function print_labels() {
   ?>
    <pre>
      <?php print_r( get_post_type_object( 'page' ) ); ?>
    </pre>
  <?php
}

function print_options() {
  ?>
    <pre>
      <?php print_r( get_option( 'template' ) ); ?>
    </pre>
  <?php
   ?>
    <pre>
      <?php print_r( get_option( 'stylesheet' ) ); ?>
    </pre>
  <?php
   ?>
    <pre>
      <?php print_r( hu_get_raw_option( 'template' ) ); ?>
    </pre>
  <?php
   ?>
    <pre>
      <?php print_r( hu_get_raw_option( 'stylesheet' ) ); ?>
    </pre>
  <?php
}

function ha_test_display_skope_options() {
  /* if ( is_array( get_option( 'theme_mods_hueman') ) )
    array_walk_recursive( get_option( 'theme_mods_hueman', ) function(&$v) { $v = htmlspecialchars($v); }); */
  ?>
    <pre>
      <?php //print_r(__ha_get_saved_opt_names_for_skope( 'global', HU_THEME_OPTIONS )); ?>
    </pre>
  <?php
  ?>
    <pre>
      <?php //print_r( hu_get_raw_option( null, HU_THEME_OPTIONS ) ); ?>
    </pre>
  <?php
  /* if ( is_array(ha_get_saved_opt_names_for_skope) )
    array_walk_recursive(ha_get_saved_opt_names_for_skope, function(&$v) { $v = htmlspecialchars($v); }); */

    if ( hu_is_customize_preview_frame() ) {
        global $wp_customize;
       ?>
          <pre>
            <?php print_r( $wp_customize->unsanitized_post_values() ); ?>
          </pre>
        <?php
    }
    if (  ! defined( 'HA_SKOP_ON' ) || ! HA_SKOP_ON )
      return;
  ?>
    <div class="container">
        <div class="raw-option">
          <h2>RAW SIDEBARS_WIDGETS OPTION</h2>
          <p>
            <pre>
              <?php print_r( hu_get_raw_option( 'widget_archives' ) ); ?>
            </pre>
          </p>
        </div>
        <div class="post-customizer">
            <h2>$_POST</h2>
            <p>
              <pre>
                <?php print_r( $_POST ); ?>
              </pre>
            </p>
            <p>HA_SKOP_OPT() -> ha_get_customized_value('widget_categories') : <pre><?php print_r( HA_SKOP_OPT() -> ha_get_customized_value('widget_categories') ) ?></p></pre>
            <p>HA_SKOP_OPT() -> ha_get_customized_value('sidebars_widgets') : <pre><?php print_r( HA_SKOP_OPT() -> ha_get_customized_value('sidebars_widgets') ) ?></p></pre>
        </div>
         <hr>
        <div class="skope">
            <h2>get skope</h2>
            <p>skope : <?php echo ha_get_skope() ?></p>
            <?php
              /* if ( is_array(ha_get_query_skope() ) )
                array_walk_recursive(ha_get_query_skope() , function(&$v) { $v = htmlspecialchars($v); }); */
              ?>
                <pre>
                  <?php print_r(ha_get_query_skope() ); ?>
                </pre>
              <?php
            ?>
        </div>
        <hr>
        <div class="meta-type">
            <h2>DYN TYPE AND META TYPE</h2>
            <?php
              $skope = ha_get_skope();
              $meta_type = ha_get_skope( 'meta_type', true );
              $_dyn_type = ( hu_is_customize_preview_frame() && isset($_POST['dyn_type']) ) ? $_POST['dyn_type'] : '';
            ?>
            <p>DYN TYPE : <?php echo $_dyn_type ?></p>
            <p>ha_get_skope() : <?php echo ha_get_skope(); ?></p>
            <p>META TYPE : <?php echo $meta_type ?> Can have trans opt? <?php echo HA_SKOP_OPT() -> ha_can_have_trans_opt($skope) ? 'OUI' : 'NON' ?></p>
        </div>
        <hr>
        <div class="current-scope-opt-name">
            <h2>Current Scopes Opt Names</h2>
            <p> <strong>Local</strong> : <?php echo HA_SKOP_OPT() -> ha_get_skope_opt_name('local'); ?></p>
            <p> <strong>group</strong> : <?php echo HA_SKOP_OPT() -> ha_get_skope_opt_name( 'group' ); ?></p>
            <p> <strong>special_group</strong> : <?php echo HA_SKOP_OPT() -> ha_get_skope_opt_name( 'special_group' ); ?></p>
            <p> <strong>global</strong> : <?php echo HA_SKOP_OPT() -> ha_get_skope_opt_name( 'global' ); ?></p>
        </div>
        <hr>
        <div class="get-option">
            <h2>Value of hu_get_option</h2>
            <p><?php print_r( hu_get_option( 'widget_archives') ); ?></p>
        </div>
        <hr>
        <div class="customized">
            <h2>Customized</h2>
            <p><?php print_r( HA_SKOP_OPT() -> ha_get_customized_value( 'widget_archives' ) ); ?></p>
        </div>
        <hr>
        <div class="raw-db-option">
            <h2>Unfiltered DB option value</h2>
            <p><?php print_r( hu_get_raw_option( 'widget_archives' ) ) ; ?></p>
        </div>
        <hr>
        <div class="local-skope">
            <h2>DB Value for Local Skope</h2>
            <p><?php echo isset($_POST['opt_name']) ? '$_POST Option name : ' . $_POST['opt_name'] : '' ?></p>
            <p><?php echo 'Our Option name : ' . HA_SKOP_OPT() -> ha_get_skope_opt_name('local') ?></p>
            <p><?php echo isset($_POST['opt_name']) ? strlen($_POST['opt_name']) . ' | ' . strlen(HA_SKOP_OPT() -> ha_get_skope_opt_name('local')) : '' ?></p>
            <?php
              $_meta_type = ha_get_skope( 'meta_type', true );
              $db_opt_name = HA_SKOP_OPT() -> ha_get_skope_opt_name('local');
              $_option_array = HA_SKOP_OPT() -> ha_get_skope_opt('local' , $_meta_type, $db_opt_name );
            ?>
            <p>Meta type : <?php echo $_meta_type; ?></p>
            <p>Option dyn type to fetch : <?php echo ha_get_skope_dyn_type( $_meta_type ); ?></p>
            <p>Option value : <?php //echo isset($_option_array['widget_archives']) ? $_option_array['widget_archives'] : 'NOT SET'; ?></p>
        </div>
        <hr>
        <div class="cached-options">
            <h2>Cached options</h2>
            <h3>local</h3>
            <p>
              <pre>
                <?php print_r( HA_SKOP_OPT() -> ha_get_cached_opt('local') ); ?>
              </pre>
            </p>
            <h3>group</h3>
            <p>
              <pre>
                <?php print_r( HA_SKOP_OPT() -> ha_get_cached_opt('group') ); ?>
              </pre>
            </p>
            <h3>special group</h3>
            <p>
              <pre>
                <?php print_r( HA_SKOP_OPT() -> ha_get_cached_opt('special_group') ); ?>
              </pre>
            </p>
            <h3>global</h3>
            <p>
              <pre>
                <?php print_r( count( HA_SKOP_OPT() -> ha_get_cached_opt('global') ) ); ?>
              </pre>
            </p>
        </div>
    </div>
  <?php
}



//@return the name of the currently saved option for a given skope
//only used for the 'global' skope for now
//@todo other skope cases
function __ha_get_saved_opt_names_for_skope( $skope = null, $opt_name = null, $opt_group = null ) {
    $skope = is_null( $skope ) ? 'global' : $skope;
    $_opts = hu_get_raw_option( $opt_name, $opt_group );
    $defaults = HU_utils::$inst -> hu_get_default_options();
    $_saved_opts = array();
    if ( ! is_array( $_opts ) )
      return array();
    foreach ( $_opts as $key => $value) {
        if ( HU_utils::$inst -> hu_is_option_protected( $key ) )
          continue;
        if ( ! HU_utils::$inst -> hu_is_option_skoped($key) )
          continue;
        if ( __ha_is_option_set_to_default( $key, $value, $defaults ) )
          continue;
        $_saved_opts[] = $key;
    }
    return $_saved_opts;
}



function __ha_is_option_set_to_default( $opt_name, $value, $defaults ) {
    if ( ! is_array( $defaults ) || ! array_key_exists($opt_name, $defaults) )
      return;
    /* if ( is_array() )
      array_walk_recursive(, function(&$v) { $v = htmlspecialchars($v); }); */


    //@todo : for value written as associative array, we might need a more advanced comparison tool using array_diff_assoc()
    return $value == $defaults[$opt_name];
}
/*********************************************************************
**********************************************************************/

