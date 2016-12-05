<?php
class HA_Czr {
  static $instance;
  function __construct() {
    self::$instance =& $this;

    //CUSTOMIZER PANEL JS
    add_action( 'customize_controls_print_footer_scripts', array( $this, 'hu_extend_visibilities' ), 100 );
    //Various DOM ready actions + print rate link + template
    add_action( 'customize_controls_print_footer_scripts'   , array( $this, 'hu_various_dom_ready' ) );
    //control style
    add_action( 'customize_controls_enqueue_scripts'         , array( $this,  'hu_customize_controls_js_css' ) );
  }

  /**************************************************************
  ** CUSTOMIZER
  **************************************************************/
  /**
   * Add script to controls
   * Dependency : customize-controls located in wp-includes/script-loader.php
   * Hooked on customize_controls_enqueue_scripts located in wp-admin/customize.php
   * @package Hueman
   * @since Hueman 3.3.0
   */
  function hu_customize_controls_js_css() {

    wp_enqueue_style(
      'hu-czr-addons-controls-style',
      sprintf( '%1$sassets/czr/css/czr-control-footer.css', HA_BASE_URL ),
      array( 'customize-controls' ),
      time(),
      $media = 'all'
    );

  }




  //hook : customize_controls_print_footer_scripts
  function hu_various_dom_ready() {
    ?>
    <script id="rate-tpl" type="text/template" >
      <?php
        printf( '<span class="czr-rate-link">%1$s %2$s, <br/>%3$s <a href="%4$s" title="%5$s" class="czr-stars" target="_blank">%6$s</a> %7$s</span>',
          __( 'If you like' , 'hueman' ),
          __( 'the Hueman theme' , 'hueman'),
          __( 'we would love to receive a' , 'hueman' ),
          'https://' . 'wordpress.org/support/view/theme-reviews/hueman?filter=5',
          __( 'Review the Hueman theme' , 'hueman' ),
          '&#9733;&#9733;&#9733;&#9733;&#9733;',
          __( 'rating. Thanks :) !' , 'hueman')
        );
      ?>
    </script>
    <script id="rate-theme" type="text/javascript">
      (function ($) {
        $( function($) {
          //Render the rate link
          _render_rate_czr();
          function _render_rate_czr() {
            var _cta = _.template(
                  $( "script#rate-tpl" ).html()
            );
            $('#customize-footer-actions').append( _cta() );
          }

          /* Append text to the content panel title */
          if ( $('#accordion-panel-hu-content-panel').find('.accordion-section-title').first().length ) {
            $('#accordion-panel-hu-content-panel').find('.accordion-section-title').first().append(
              $('<span/>', { html : ' ( Home, Blog, Layout, Sidebars, Slideshows, ... )' } ).css('font-style', 'italic').css('font-size', '14px')
            );
          }
        });
      })(jQuery)
    </script>
    <?php
  }


  //hook : 'customize_controls_enqueue_scripts'
    function hu_extend_visibilities() {
      ?>
      <script type="text/javascript">
        (function (api, $, _) {
            if ( ! _.has( api, 'CZR_ctrlDependencies') )
              return;
            //@return boolean
            var _is_checked = function( to ) {
                return 0 !== to && '0' !== to && false !== to && 'off' !== to;
            };
            api.CZR_ctrlDependencies.prototype.dominiDeps = _.extend(
                  api.CZR_ctrlDependencies.prototype.dominiDeps,
                  [
                      {
                          dominus : 'sharrre',
                          servi : [
                            'sharrre-scrollable',
                            'sharrre-twitter-on',
                            'twitter-username',
                            'sharrre-facebook-on',
                            'sharrre-google-on',
                            'sharrre-pinterest-on',
                            'sharrre-linkedin-on'
                          ],
                          visibility : function (to) {
                              return _is_checked(to);
                          }
                      },
                       {
                          dominus : 'sharrre-twitter-on',
                          servi : ['twitter-username'],
                          visibility : function (to) {
                              return _is_checked(to);
                          }
                      },
                  ]
            );
        }) ( wp.customize, jQuery, _);
      </script>
      <?php
    }

}//end of class