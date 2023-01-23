<?php
/* ------------------------------------------------------------------------- *
 *  Public Functions
/* ------------------------------------------------------------------------- */
//@return bool
function ha_are_share_buttons_enabled() {
  if ( ! hu_is_checked('sharrre') )
    return;
  if ( ! hu_is_checked('sharrre-twitter-on') && ! hu_is_checked('sharrre-facebook-on') && ! hu_is_checked('sharrre-pinterest-on') && ! hu_is_checked('sharrre-linkedin-on') )
    return;
  return true;
}



/* ------------------------------------------------------------------------- *
 *  Class
/* ------------------------------------------------------------------------- */
class HA_Sharrre {
  static $instance;
  function __construct() {
      self::$instance =& $this;
      //front
      add_action( 'wp'                    , array($this, 'ha_sharrre_front_actions') );

      //customizer
      add_filter( 'hu_social_links_sec'   , array( $this, 'ha_register_sharrre_settings'));
  }


  /* ------------------------------------------------------------------------- *
   *  Front End
  /* ------------------------------------------------------------------------- */
  //hook : 'wp'
  function ha_sharrre_front_actions() {
    if ( !is_single() )
      return;
    //alter the single entry wrapper class
    add_filter( 'hu_single_entry_class', array($this, 'ha_maybe_add_sharrre_class'));

    //hook the sharrre content to the single post template
    add_action( 'hu_after_single_entry_inner', array($this, 'ha_maybe_print_sharrre_template') );
    //scripts
    add_action( 'wp_enqueue_scripts'    , array( $this, 'ha_addons_scripts' ) );
  }


  //@param $classes = array of classes
  //hook : hu_single_entry_class
  function ha_maybe_add_sharrre_class( $classes ) {
    if ( !ha_are_share_buttons_enabled() )
      return $classes;
    $classes[] = 'share';
    return $classes;
  }

  //hook : hu_after_single_entry_inner
  function ha_maybe_print_sharrre_template() {
    if ( !ha_are_share_buttons_enabled() )
      return;

    ha_locate_template( 'addons/sharrre/sharrre-template.php', $load = true, $require_once = true );
  }


  /* ------------------------------------------------------------------------- *
   *  Scripts
  /* ------------------------------------------------------------------------- */
  //hook : wp_enqueue_scripts
  function ha_addons_scripts() {
    if ( !ha_are_share_buttons_enabled() )
      return;
    wp_enqueue_script(
      'sharrre',
      sprintf( '%1$saddons/assets/front/js/jQuerySharrre%2$s', HU_AD() -> ha_get_base_url(), (defined('CZR_DEV') && true === CZR_DEV) ? '.js?' . time() : '.min.js' ),
      array( 'jquery' ),
      '',
      true
    );
  }



  /* ------------------------------------------------------------------------- *
   *  Customizer
  /* ------------------------------------------------------------------------- */
  //add customizer settings
  //hook : hu_social_links_sec
  function ha_register_sharrre_settings( $settings ) {
    $sharrre_settings = array(
      'sharrre' => array(
            'default'   => 1,
            'control'   => 'HU_controls',
            'label'     => __('Display social sharing buttons in your single posts', 'hueman-addons'),
            'title'     => __('Social Sharring Bar Settings', 'hueman-addons'),
            'notice'    => __('Display social sharing buttons in each single articles.', 'hueman-addons'),
            'section'   => 'content_single_sec',
            'type'      => 'nimblecheck',
            'priority'  => 40
      ),
      'sharrre-scrollable' => array(
            'default'   => 1,
            'control'   => 'HU_controls',
            'label'     => __('Make the Share Bar "sticky"', 'hueman-addons'),
            'notice'    => __('Make the social share bar stick to the browser window when scrolling down a post.', 'hueman-addons'),
            'section'   => 'content_single_sec',
            'type'      => 'nimblecheck',
            'priority'  => 50
      ),
      'sharrre-twitter-on' => array(
            'default'   => 1,
            'control'   => 'HU_controls',
            'label'     => __('Enable Twitter Button', 'hueman-addons'),
            'section'   => 'content_single_sec',
            'type'      => 'nimblecheck',
            'notice'    => __('Since Nov. 2015, Twitter disabled the share counts from its API. If you want to get the display count anyway, you can create an account for free (as of Feb. 2016) on [https://opensharecount.com/]. The Hueman Addons plugin is configured to use opensharecount.', 'hueman-addons'),
            'priority'  => 60
      ),
      'twitter-username' => array(
            'default'   => '',
            'control'   => 'HU_controls',
            'label'     => __('Twitter Username (without "@")', 'hueman-addons'),
            'notice'    => __('Simply enter your username without the "@" prefix. Your username will be added to share-tweets of your posts (optional).', 'hueman-addons'),
            'section'   => 'content_single_sec',
            'type'      => 'text',
            'transport' => 'postMessage',
            'priority'  => 70
      ),
      'sharrre-facebook-on' => array(
            'default'   => 1,
            'control'   => 'HU_controls',
            'label'     => __('Enable Facebook Button', 'hueman-addons'),
            'section'   => 'content_single_sec',
            'type'      => 'nimblecheck',
            'priority'  => 80
      ),
      'sharrre-pinterest-on' => array(
            'default'   => 0,
            'control'   => 'HU_controls',
            'label'     => __('Enable Pinterest Button', 'hueman-addons'),
            'section'   => 'content_single_sec',
            'type'      => 'nimblecheck',
            'priority'  => 100
      ),
      'sharrre-linkedin-on' => array(
            'default'   => 0,
            'control'   => 'HU_controls',
            'label'     => __('Enable LinkedIn Button', 'hueman-addons'),
            'section'   => 'content_single_sec',
            'type'      => 'nimblecheck',
            'priority'  => 100
      )
    );

    return array_merge( $sharrre_settings, $settings );
  }

  //boolean helper, used as active callback for sharrre settings
  function ha_is_single() {
    return is_single();
  }
}//end of class