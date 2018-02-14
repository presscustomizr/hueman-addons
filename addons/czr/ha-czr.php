<?php
class HA_Czr {
  static $instance;
  function __construct() {
    self::$instance =& $this;
    add_action( 'customize_register'                      ,  array( $this, 'ha_augment_customizer_setting') );//extend WP_Customize_Setting
    //CUSTOMIZER PANEL JS
    add_action( 'customize_controls_print_footer_scripts' , array( $this, 'hu_extend_dependencies' ), 100 );
    add_action( 'customize_controls_print_footer_scripts' , array( $this, 'hu_react_to_skope_enable_changes' ), 100 );

    //Various DOM ready actions + print rate link + template
    add_action( 'customize_controls_print_footer_scripts' , array( $this, 'hu_various_dom_ready' ) );
    //control style
    add_action( 'customize_controls_enqueue_scripts'      , array( $this, 'hu_customize_controls_js_css' ), 20 );
    //add the _custom_ item to the content picker retrieved in ajax
    add_filter( 'content_picker_ajax_items'               , array( $this, 'ha_add_custom_item_to_ajax_results' ), 10, 3 );
  }

  //hook : customize_register
  function ha_augment_customizer_setting() {
      if ( ! HU_AD() -> ha_is_skop_on() )
        return;
      require_once( HA_BASE_PATH . 'addons/czr/skop-customizer-augment-setting.php' );
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

    $hu_theme = wp_get_theme();

    //Hueman Addons Specifics
    wp_enqueue_style(
        'ha-czr-addons-controls-style',
        sprintf( '%1$saddons/assets/czr/css/czr-control-footer.css', HU_AD() -> ha_get_base_url() ),
        array( 'customize-controls' ),
        ( defined('CZR_DEV') && true === CZR_DEV ) ? $hu_theme -> version . time() : $hu_theme -> version,
        $media = 'all'
    );

    //Enqueue most recent fmk for js and css;
    $is_pro = HU_AD() -> ha_is_pro_addons() || HU_AD() -> ha_is_pro_theme();
    if ( $is_pro || true == version_compare( $hu_theme -> version, LAST_THEME_VERSION_FMK_SYNC , '<' ) ) {
        $wp_styles = wp_styles();
        $wp_scripts = wp_scripts();
        if ( isset( $wp_styles->registered['hu-customizer-controls-style'] ) ) {
            $wp_styles->registered['hu-customizer-controls-style'] -> src = sprintf(
                '%1$saddons/assets/czr/css/czr-control-base%2$s.css',
                HU_AD() -> ha_get_base_url(),
                ( defined('WP_DEBUG') && true === WP_DEBUG ) ? '' : '.min'
            );
            $ver_css = $wp_styles->registered['hu-customizer-controls-style'] -> ver;
            $wp_styles->registered['hu-customizer-controls-style'] -> ver = ( defined('WP_DEBUG') && true === WP_DEBUG ) ? $ver_css . time() : $ver_css;
        }
        if ( isset( $wp_scripts->registered['hu-customizer-controls'] ) ) {
            $wp_scripts->registered['hu-customizer-controls'] -> src = sprintf(
                '%1$saddons/assets/czr/js/%2$s%3$s.js',
                HU_AD() -> ha_get_base_url(),
                $is_pro ? 'czr-control-full' : 'czr-control-base',
                ( defined('WP_DEBUG') && true === WP_DEBUG ) ? '' : '.min'
            );
            $ver = $wp_scripts->registered['hu-customizer-controls'] -> ver;
            $wp_scripts->registered['hu-customizer-controls'] -> ver = ( defined('WP_DEBUG') && true === WP_DEBUG ) ? $ver . time() : $ver;
        }
    }
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
    function hu_extend_dependencies() {
      //pro_header is not set in the free addon
      $pro_header_slider_short_opt_name = isset( HU_AD() -> pro_header ) ? HU_AD() -> pro_header -> pro_header_slider_short_opt_name : 'pro_slider_header_bg';
      ?>
      <script type="text/javascript">
        (function (api, $, _) {
            if ( ! _.has( api, 'CZR_ctrlDependencies') )
              return;
            //@return boolean
            var pro_header_slider_short_opt_name = '<?php echo $pro_header_slider_short_opt_name; ?>',//'pro_slider_header_bg'
                _is_checked = function( to ) {
                return 0 !== to && '0' !== to && false !== to && 'off' !== to;
            };
            api.CZR_ctrlDependencies.prototype.dominiDeps = _.union(
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
                      {
                          dominus : 'pro_header_type',
                          servi : [
                            'use-header-image',
                            'logo-title-on-header-image',
                            'header_image',
                            'color-topbar',
                            'color-header',
                            'color-header-menu',
                            pro_header_slider_short_opt_name//pro_header_bg
                          ],
                          visibility : function ( to, servusShortId ) {
                              if ( pro_header_slider_short_opt_name == servusShortId ) {
                                  return 'classical' != to;
                              } else if ( 'header_image' == servusShortId || 'logo-title-on-header-image' == servusShortId ) {
                                  var wpServusId = api.CZR_Helpers.build_setId( 'use-header-image' );
                                  return 'classical' == to && _is_checked( api( wpServusId )() );
                              } else {
                                  return 'classical' == to;
                              }
                          }
                      },
                  ]
            );
        }) ( wp.customize, jQuery, _);
      </script>
      <?php
    }


    //hook : 'customize_controls_enqueue_scripts'
    function hu_react_to_skope_enable_changes() {
      ?>
      <script type="text/javascript">
      (function (api, $, _) {
          //style select
          api.when( 'hu_theme_options[enable-skope]', function( _set ) {
              _set.bind( function() {
                    // since WP4.9, we need to make sure the wp.customize.state( 'selectedChangesetStatus' )() is set to publish before saving
                    if ( api.state.has( 'selectedChangesetStatus' ) ) {
                          api.state( 'selectedChangesetStatus' )( 'publish' );
                    }
                    api.previewer.save().always( function() {
                          if ( _wpCustomizeSettings && _wpCustomizeSettings.url && _wpCustomizeSettings.url.parent ) {
                                var url = [ _wpCustomizeSettings.url.parent ];
                                url.push( 'customize.php?&autofocus%5Bcontrol%5D=' + _set.id );
                                _.delay( function() {
                                      window.location.href = url.join('');
                                }, 500 );
                          } else {
                                _.delay( function() {
                                      window.parent.location.reload();
                                });
                          }
                    });
              });
          });
       }) ( wp.customize, jQuery, _);
      </script>
      <?php
    }

    //declared this way:
    //wp_send_json_success( array(
    //     'items' => apply_filters( 'content_picker_ajax_items', $items, $p, 'ajax_search_available_items' );
    // ) );
    //
    // An item looks like :
    // array(
    //    'title'      => html_entity_decode( $post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ),
    //    'type'       => 'post',
    //    'type_label' => get_post_type_object( $post->post_type )->labels->singular_name,
    //    'object'     => $post->post_type,
    //    'id'         => intval( $post->ID ),
    //    'url'        => get_permalink( intval( $post->ID ) )
    // )
    // hook : content_picker_ajax_items
    function ha_add_custom_item_to_ajax_results( $items, $page, $context ) {
        if ( is_numeric( $page ) && $page < 1 ) {
            return array_merge(
                array(
                    array(
                       'title'      => sprintf( '<span style="font-weight:bold">%1$s</span>', __('Set a custom url', 'hueman') ),
                       'type'       => '',
                       'type_label' => '',
                       'object'     => '',
                       'id'         => '_custom_',
                       'url'        => ''
                    )
                ),
                $items
            );
        } else {
            return $items;
        }
    }

}//end of class