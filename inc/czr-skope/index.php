<?php
namespace hueman_skp;
if ( did_action('nimble_skope_loaded') ) {
    if ( ( defined( 'CZR_DEV' ) && CZR_DEV ) || ( defined( 'NIMBLE_DEV' ) && NIMBLE_DEV ) ) {
        error_log( __FILE__ . '  => The skope has already been loaded' );
    }
    return;
}
global $czr_skope_namespace;
$czr_skope_namespace = __NAMESPACE__ . '\\';

do_action( 'nimble_skope_loaded' );
function Flat_Skop_Base( $params = array() ) {
    return Flat_Skop_Base::skp_get_instance( $params );
}
function skp_get_default_skope_model() {
    return array(
        'title'       => '',
        'long_title'  => '',
        'ctx_title'   => '',
        'id'          => '',
        'skope'       => '',
        'obj_id'      => '',
        'skope_id'     => '',
        'values'      => ''
    );
}
function skp_get_no_group_skope_list() {
    return array( 'home', 'search', '404', 'date' );
}

function skp_trim_text( $text, $text_length, $more ) {
    if ( ! $text )
      return '';

    $text       = trim( strip_tags( $text ) );

    if ( ! $text_length )
      return $text;

    $end_substr = $_text_length = strlen( $text );

    if ( $_text_length > $text_length ){
      $end_substr = strpos( $text, ' ' , $text_length);
      $end_substr = ( $end_substr !== FALSE ) ? $end_substr : $text_length;
      $text = substr( $text , 0 , $end_substr );
    }
    return ( ( $end_substr < $text_length ) && $more ) ? $text : $text . ' ' .$more ;
  }
/**
* Return the current skope
* Front / Back agnostic.
* @param $_requesting_wot is a string with the following possible values : 'meta_type' (like post) , 'type' (like page), 'id' (like page id)
* @param $_return_string string param stating if the return value should be a string or an array
* @param $requested_parts is an array of parts looking like
* Array
* (
*     [meta_type] => post
*     [type] => page
*     [obj_id] => 9
* )
* USE CASE when $requested_parts is passed : when a post gets deleted, we need to clean any skope posts associated. That's when we invoke skp_get_skope( null, true, $requested_parts )
* @return a string of all concatenated ctx parts (default) 0R an array of the ctx parts
*/
function skp_get_skope( $_requesting_wot = null, $_return_string = true, $requested_parts = array() ) {
    $parts    = ( is_array( $requested_parts ) && ! empty( $requested_parts ) ) ? $requested_parts : skp_get_query_skope();

    $_return  = array();
    $meta_type = $type = $obj_id = false;

    if ( is_array( $parts ) && ! empty( $parts ) ) {
        $meta_type  = isset( $parts['meta_type'] ) ? $parts['meta_type'] : false;
        $type       = isset( $parts['type'] ) ? $parts['type'] : false;
        $obj_id     = isset( $parts['obj_id'] ) ? $parts['obj_id'] : false;
    }

    switch ( $_requesting_wot ) {
        case 'meta_type':
            if ( false !== $meta_type ) {
                $_return = array( "meta_type" => "{$meta_type}" );
            }
        break;

        case 'type':
            if ( false !== $type ) {
                $_return = array( "type" => "{$type}" );
            }
        break;

        case 'id':
            if ( false !== $obj_id ) {
                $_return = array( "id" => "{$obj_id}" );
            }
        break;

        default:
            if  ( false !== $meta_type && false !== $obj_id ) {
                $_return = array( "meta_type" => "{$meta_type}" , "type" => "{$type}", "id" => "{$obj_id}" );
            }
            else if ( false !== $meta_type && ! $obj_id ) {
                $_return = array( "meta_type" => "{$meta_type}", "type" => "{$type}" );
            }
            else if ( false !== $obj_id ) {
                $_return = array( "id" => "{$obj_id}" );
            }
            else {
                if ( defined( 'NIMBLE_DEV' ) && NIMBLE_DEV ) {
                    error_log( __FUNCTION__ . ' error when building the local skope, no object_id provided.');
                    error_log( print_r( $parts, true) );
                }
            }
        break;
    }
    if ( ! $_return_string ) {
      return $_return;
    }
    if ( ! is_array( $_return ) || ( is_array( $_return ) && empty( $_return ) ) ) {
      return '';
    }
    if ( ! is_null( $_requesting_wot ) ) {
      return isset( $_return[ $_requesting_wot ] ) ? $_return[ $_requesting_wot ] : '';
    }
    $_concat = "";
    foreach ( $_return as $_key => $_part ) {
        if ( empty( $_concat) ) {
            $_concat .= $_part;
        } else {
            $_concat .= '_'. $_part;
        }
    }
    return $_concat;
}


/**
* skope builder from the wp $query
* !! has to be fired after 'template_redirect'
* Used on front ( not customizing preview ? => @todo make sure of this )
* @return  array of ctx parts
*/
function skp_get_query_skope() {
    global $wp_the_query;
    if ( ! isset( $wp_the_query ) || empty( $wp_the_query ) )
      return array();
    if ( ! empty( Flat_Skop_Base()->query_skope ) )
      return Flat_Skop_Base()->query_skope;

    $queried_object  = get_queried_object();

    $meta_type = $type = $obj_id = false;
    if ( ! is_null( $queried_object ) && is_object( $queried_object ) ) {
        if ( isset($queried_object -> post_type) ) {
            $meta_type  = 'post';
            $type       = $queried_object -> post_type;
            $obj_id     = $queried_object -> ID;
        }
        if ( isset($queried_object -> taxonomy) && isset($queried_object -> term_id) ) {
            $meta_type  = 'tax';
            $type       = $queried_object -> taxonomy;
            $obj_id     = $queried_object -> term_id;
        }
    }
    if ( is_author() ) {
        $meta_type  = 'user';
        $type       = 'author';
        $obj_id     = $wp_the_query ->get( 'author' );
    }
    if ( is_post_type_archive() ) {
        $obj_id     = 'post_type_archive' . '_'. $wp_the_query ->get( 'post_type' );
    }
    if ( is_404() ) {
        $obj_id  = '404';
    }
    if ( is_search() ) {
        $obj_id  = 'search';
    }
    if ( is_date() ) {
        $obj_id  = 'date';
    }

    if ( skp_is_real_home() ) {
        $obj_id  = 'home';
        if ( ! is_home() && 'page' === get_option( 'show_on_front' ) ) {
            $home_page_id = get_option( 'page_on_front' );
            if ( 0 < $home_page_id ) {
                $obj_id = $home_page_id;
            }
        }
    }
    if ( did_action( 'wp' ) ) {
        Flat_Skop_Base()->query_skope = apply_filters( 'skp_get_query_skope' , array( 'meta_type' => $meta_type , 'type' => $type , 'obj_id' => $obj_id ) , $queried_object );
    }

    return Flat_Skop_Base()->query_skope;
}
function skp_get_skope_id( $level = 'local' ) {
    $new_skope_ids = array( 'local' => '_skope_not_set_', 'group' => '_skope_not_set_' );
    if ( did_action( 'wp' ) ) {
        if ( empty( Flat_Skop_Base()->current_skope_ids ) ) {
            $new_skope_ids['local'] = skp_build_skope_id( array( 'skope_string' => skp_get_skope(), 'skope_level' => 'local' ) );
            $new_skope_ids['group'] = skp_build_skope_id( array( 'skope_level' => 'group' ) );

            Flat_Skop_Base()->current_skope_ids = $new_skope_ids;

            $skope_id_to_return = $new_skope_ids[ $level ];
        } else {
            $new_skope_ids = Flat_Skop_Base()->current_skope_ids;
            $skope_id_to_return = $new_skope_ids[ $level ];
        }
    } else {
        $skope_id_to_return = array_key_exists( $level, $new_skope_ids ) ? $new_skope_ids[ $level ] : '_skope_not_set_';
    }
    return apply_filters( 'skp_get_skope_id', $skope_id_to_return, $level );
}
function skp_build_skope_id( $args = array() ) {
    $skope_id = '_skope_not_set_';
    $args = is_array( $args ) ? $args : array();
    $args = wp_parse_args(
        $args,
        array( 'skope_string' => '', 'skope_type' => '', 'skope_level' => '' )
    );
    $args['skope_level']  = empty( $args['skope_level'] ) ? 'local' : $args['skope_level'];
    $args['skope_string'] = ( 'local' == $args['skope_level'] && empty( $args['skope_string'] ) ) ? skp_get_skope() : $args['skope_string'];
    $args['skope_type']   = ( 'group' == $args['skope_level'] && empty( $args['skope_type'] ) ) ? skp_get_skope( 'type' ) : $args['skope_type'];
    switch( $args[ 'skope_level'] ) {
          case 'local' :
              $skope_id = strtolower( NIMBLE_SKOPE_ID_PREFIX . $args[ 'skope_string' ] );
          break;
          case 'group' :
              if ( ! empty( $args[ 'skope_type' ] ) ) {
                  $skope_id = strtolower( NIMBLE_SKOPE_ID_PREFIX . 'all_' . $args[ 'skope_type' ] );
              }
          break;
    }
    return $skope_id;
}


/**
* Used when localizing the customizer js params
* Can be a post ( post, pages, CPT) , tax(tag, cats, custom taxs), author, date, search page, 404.
* @param $args : array(
*    'level'       => string,
*    'meta_type'   => string
*    'long'        => bool
*    'is_prefixed' => bool //<= indicated if we should add the "Options for" prefix
* )
* @return string title of the current ctx if exists. If not => false.
*/
function skp_get_skope_title( $args = array() ) {
    $defaults = array(
        'level'       =>  '',
        'meta_type'   => null,
        'long'        => false,
        'is_prefixed' => true
    );

    $args = wp_parse_args( $args, $defaults );

    $level        = $args['level'];
    $meta_type    = $args['meta_type'];
    $long         = $args['long'];
    $is_prefixed  = $args['is_prefixed'];

    $_dyn_type = ( skp_is_customize_preview_frame() && isset( $_POST['dyn_type']) ) ? $_POST['dyn_type'] : '';
    $type = skp_get_skope('type');
    $skope = skp_get_skope();
    $title = '';

    if( 'local' == $level ) {
        $type = skp_get_skope( 'type' );
        $title = $is_prefixed ? __( 'Options for', 'text_doma') . ' ' : $title;
        if ( skp_skope_has_a_group( $meta_type ) ) {
            $_id = skp_get_skope('id');
            switch ($meta_type) {
                case 'post':
                  $type_obj = get_post_type_object( $type );
                  $title .= sprintf( '%1$s "%3$s" (id : %2$s)', strtolower( $type_obj -> labels -> singular_name ), $_id, get_the_title( $_id ) );
                  break;

                case 'tax':
                  $type_obj = get_taxonomy( $type );
                  $term = get_term( $_id, $type );
                  $title .= sprintf( '%1$s "%3$s" (id : %2$s)', strtolower( $type_obj -> labels -> singular_name ), $_id, $term -> name );
                  break;

                case 'user':
                  $author = get_userdata( $_id );
                  $title .= sprintf( '%1$s "%3$s" (id : %2$s)', __('user', 'text_doma'), $_id, $author -> user_login );
                  break;
            }
        } else if ( ( 'trans' == $_dyn_type || skp_skope_has_no_group( $skope ) ) ) {
            if ( is_post_type_archive() ) {
                global $wp_the_query;
                $title .= sprintf( __( '%1$s archive page', 'text_doma'), $wp_the_query ->get( 'post_type' ) );
            } else {
                $title .= strtolower( $skope );
            }
        } else {
            $title .= __( 'Undefined', 'text_doma');
        }
    }
    if ( 'group' == $level || 'special_group' == $level ) {
        $title = $is_prefixed ? __( 'Options for all', 'text_doma') . ' ' : __( 'All' , 'text_doma' ) . ' ';
        switch( $meta_type ) {
            case 'post' :
                $type_obj = get_post_type_object( $type );
                $title .= strtolower( $type_obj -> labels -> name );
            break;

            case 'tax' :
                $type_obj = get_taxonomy( $type );
                $title .= strtolower( $type_obj -> labels -> name );
            break;

            case 'user' :
                $title .= __('users', 'text_doma');
            break;
        }
    }
    if ( 'global' == $level ) {
        $title = __( 'Sitewide options', 'text_doma');
    }
    $title = ucfirst( $title );
    return skp_trim_text( $title, $long ? 45 : 28, '...');
}
function skp_skope_has_no_group( $meta_type ) {
    return in_array(
      $meta_type,
      skp_get_no_group_skope_list()
    ) || is_post_type_archive();
}
function skp_skope_has_a_group( $meta_type ) {
    return in_array(
      $meta_type,
      array('post', 'tax', 'user')
    );
}
function skp_is_real_home() {
  return ( is_home() && ( 'posts' == get_option( 'show_on_front' ) || '__nothing__' == get_option( 'show_on_front' ) ) )
  || ( is_home() && 0 == get_option( 'page_on_front' ) && 'page' == get_option( 'show_on_front' ) )//<= this is the case when the user want to display a page on home but did not pick a page yet
  || is_front_page();
}


/**
 * Returns a boolean
*/
function skp_is_customizing() {
    global $pagenow;
    $_is_ajaxing_from_customizer = isset( $_POST['customized'] ) || isset( $_POST['wp_customize'] );

    $is_customizing = false;
    $is_customize_php_page = ( is_admin() && 'customize.php' == basename( $_SERVER['PHP_SELF'] ) );
    $is_customize_admin_page_one = (
      $is_customize_php_page
      ||
      ( isset( $_REQUEST['wp_customize'] ) && 'on' == $_REQUEST['wp_customize'] )
      ||
      ( ! empty( $_GET['customize_changeset_uuid'] ) || ! empty( $_POST['customize_changeset_uuid'] ) )
    );
    $is_customize_admin_page_two = is_admin() && isset( $pagenow ) && 'customize.php' == $pagenow;

    if ( $is_customize_admin_page_one || $is_customize_admin_page_two ) {
        $is_customizing = true;
    } else if ( is_customize_preview() || ( ! is_admin() && isset($_REQUEST['customize_messenger_channel']) ) ) {
        $is_customizing = true;
    } else if ( $_is_ajaxing_from_customizer && ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        $is_customizing = true;
    }
    return $is_customizing;
}

/**
* Is the customizer preview panel being displayed ?
* @return  boolean
*/
function skp_is_customize_preview_frame() {
  return is_customize_preview() || ( ! is_admin() && isset($_REQUEST['customize_messenger_channel']) );
}

/**
* @return  boolean
*/
function skp_is_previewing_live_changeset() {
  return ! isset( $_POST['customize_messenger_channel']) && is_customize_preview();
}
?><?php
if ( ! class_exists( 'Flat_Skop_Base' ) ) :
    class Flat_Skop_Base {
        static $instance;
        public $query_skope = array();//<= will cache the query skope ( otherwise called multiple times ) on the first invokation of skp_get_query_skope() IF 'wp' done
        public $current_skope_ids = array();// will cache the skope ids on the first invokation of skp_get_skope_id, if 'wp' done

        public static function skp_get_instance( $params ) {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Flat_Skop_Base ) )
              self::$instance = new Flat_Skope_Clean_Final( $params );
            return self::$instance;
        }
        function __construct( $params = array() ) {
            $defaults = array(
                'base_url_path' => ''//NIMBLE_BASE_URL . '/inc/czr-skope/'
            );
            $params = wp_parse_args( $params, $defaults );
            if ( ! defined( 'NIMBLE_SKOPE_BASE_URL' ) ) { define( 'NIMBLE_SKOPE_BASE_URL' , $params['base_url_path'] ); }
            if ( ! defined( 'NIMBLE_SKOPE_ID_PREFIX' ) ) { define( 'NIMBLE_SKOPE_ID_PREFIX' , "skp__" ); }

            $this->skp_register_and_load_control_assets();
            $this->skp_export_skope_data_and_schedule_sending_to_panel();
            $this->skp_schedule_cleaning_on_object_delete();
        }//__construct
    }
endif;
?><?php
if ( ! class_exists( 'Flat_Skop_Register_And_Load_Control_Assets' ) ) :
    class Flat_Skop_Register_And_Load_Control_Assets extends Flat_Skop_Base {
          public function skp_register_and_load_control_assets() {
              add_action( 'customize_controls_enqueue_scripts', array( $this, 'skp_enqueue_controls_js_css' ), 20 );
          }

          public function skp_enqueue_controls_js_css() {
              $_use_unminified = defined('CZR_DEV')
                  && true === CZR_DEV
                  && file_exists( sprintf( '%s/assets/czr/js/czr-skope-base.js' , dirname( __FILE__ ) ) );

              $_prod_script_path = sprintf(
                  '%1$s/assets/czr/js/%2$s' ,
                  NIMBLE_SKOPE_BASE_URL,
                  $_use_unminified ? 'czr-skope-base.js' : 'czr-skope-base.min.js'
              );

              wp_enqueue_script(
                  'czr-skope-base',
                  $_prod_script_path,
                  array('customize-controls' , 'jquery', 'underscore'),
                  ( defined('WP_DEBUG') && true === WP_DEBUG ) ? time() :  wp_get_theme() -> version,
                  $in_footer = true
              );

              wp_localize_script(
                  'czr-skope-base',
                  'FlatSkopeLocalizedData',
                  array(
                      'noGroupSkopeList' => skp_get_no_group_skope_list(),
                      'defaultSkopeModel' => skp_get_default_skope_model(),
                      'i18n' => array()
                  )
              );
          }
    }//class
endif;

?><?php
/* ------------------------------------------------------------------------- *
*  CUSTOMIZE PREVIEW : export skope data and send skope to the panel
/* ------------------------------------------------------------------------- */
if ( ! class_exists( 'Flat_Export_Skope_Data_And_Send_To_Panel' ) ) :
    class Flat_Export_Skope_Data_And_Send_To_Panel extends Flat_Skop_Register_And_Load_Control_Assets {
          public function skp_export_skope_data_and_schedule_sending_to_panel() {
              add_action( 'wp_footer', array( $this, 'skp_print_server_skope_data') , 30 );
          }
          public function skp_print_server_skope_data() {
              if ( ! skp_is_customize_preview_frame() )
                  return;

              global $wp_query, $wp_customize;
              $_meta_type = skp_get_skope( 'meta_type', true );
              $_czr_skopes            = $this->_skp_get_json_export_ready_skopes();
              ?>
                  <script type="text/javascript" id="czr-print-skop">
                      (function ( _export ){
                              _export.czr_new_skopes        = <?php echo wp_json_encode( $_czr_skopes ); ?>;
                              _export.czr_stylesheet    = '<?php echo get_stylesheet(); ?>';
                      })( _wpCustomizeSettings );

                      ( function( api, $, _ ) {
                          $( function() {
                                api.preview.bind( 'sync', function( events ) {
                                      api.preview.send( 'czr-new-skopes-synced', {
                                            czr_new_skopes : _wpCustomizeSettings.czr_new_skopes || [],
                                            czr_stylesheet : _wpCustomizeSettings.czr_stylesheet || '',
                                            isChangesetDirty : _wpCustomizeSettings.isChangesetDirty || false,
                                            skopeGlobalDBOpt : _wpCustomizeSettings.skopeGlobalDBOpt || [],
                                      } );
                                });
                          });
                      } )( wp.customize, jQuery, _ );
                  </script>
              <?php
          }

          /* ------------------------------------------------------------------------- *
              *  CUSTOMIZE PREVIEW : BUILD SKOPES JSON
          /* ------------------------------------------------------------------------- */
          private function _skp_get_json_export_ready_skopes() {
              $skopes = array();
              $_meta_type = skp_get_skope( 'meta_type', true );
              $defaults = skp_get_default_skope_model();
              $skopes[] = wp_parse_args(
                  array(
                      'title'       => skp_get_skope_title( array( 'level' => 'global' ) ),
                      'long_title'  => skp_get_skope_title( array( 'level' => 'global', 'meta_type' => null, 'long' => true ) ),
                      'ctx_title'   => skp_get_skope_title( array( 'level' => 'global', 'meta_type' => null, 'long' => true, 'is_prefixed' => false ) ),
                      'skope'       => 'global',
                      'level'       => '_all_'
                  ),
                  $defaults
              );
              if ( skp_get_skope('meta_type') ) {
                  $skopes[] = wp_parse_args(
                      array(
                          'title'       => skp_get_skope_title( array( 'level' => 'group', 'meta_type' => $_meta_type  ) ),
                          'long_title'  => skp_get_skope_title( array( 'level' => 'group', 'meta_type' => $_meta_type, 'long' => true ) ),
                          'ctx_title'   => skp_get_skope_title( array( 'level' => 'group', 'meta_type' => $_meta_type, 'long' => true, 'is_prefixed' => false ) ),
                          'skope'       => 'group',
                          'level'       => 'all_' . skp_get_skope('type'),
                          'skope_id'    => skp_get_skope_id( 'group' )
                      ),
                      $defaults
                  );
              }
              $skopes[] = wp_parse_args(
                  array(
                      'title'       => skp_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type ) ),
                      'long_title'  => skp_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type, 'long' => true ) ),
                      'ctx_title'   => skp_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type, 'long' => true, 'is_prefixed' => false ) ),
                      'skope'       => 'local',
                      'level'       => skp_get_skope(),
                      'obj_id'      => skp_get_skope('id'),
                      'skope_id'    => skp_get_skope_id( 'local' )
                  ),
                  $defaults
              );
              return apply_filters( 'skp_json_export_ready_skopes', $skopes );
          }
    }//class
endif;

?><?php

if ( ! class_exists( 'Flat_Skope_Clean_Final' ) ) :
    final class Flat_Skope_Clean_Final extends Flat_Export_Skope_Data_And_Send_To_Panel {
          public function skp_schedule_cleaning_on_object_delete() {
              add_action( 'delete_post', array( $this, 'skp_clean_skopified_posts' ) );
              add_action( 'delete_term_taxonomy', array( $this, 'skp_clean_skopified_taxonomies' ) );
              add_action( 'delete_user', array( $this, 'skp_clean_skopified_users' ) );
          }
          public function skp_clean_skopified_posts( $postid ) {
              $deletion_candidate = get_post( $postid );
              if ( ! $deletion_candidate || ! is_object( $deletion_candidate ) )
                return;
              if ( ! is_post_type_viewable( $deletion_candidate -> post_type ) )
                return;
              $skope_string = skp_get_skope( null, true, array(
                  'meta_type' => 'post',
                  'type'      => $deletion_candidate -> post_type,
                  'obj_id'    => $postid
              ) );
              $skope_id = skp_build_skope_id( array( 'skope_string' => $skope_string, 'skope_level' => 'local' ) );
              $skope_post_id_candidate = get_theme_mod( $skope_id );
              if ( $skope_post_id_candidate > 0 && get_post( $skope_post_id_candidate ) ) {
                  wp_delete_post( $skope_post_id_candidate );
                  remove_theme_mod( $skope_id );
              }
          }
          public function skp_clean_skopified_taxonomies( $term_id ) {
              $deletion_candidate = get_term( $term_id );
              if ( ! $deletion_candidate || ! is_object( $deletion_candidate ) )
                return;
              $skope_string = skp_get_skope( null, true, array(
                  'meta_type' => 'tax',
                  'type'      => $deletion_candidate -> taxonomy,
                  'obj_id'    => $term_id
              ) );
              $skope_id = skp_build_skope_id( array( 'skope_string' => $skope_string, 'skope_level' => 'local' ) );
              $skope_post_id_candidate = get_theme_mod( $skope_id );
              if ( $skope_post_id_candidate > 0 && get_post( $skope_post_id_candidate ) ) {
                  wp_delete_post( $skope_post_id_candidate );
                  remove_theme_mod( $skope_id );
              }
          }
          public function skp_clean_skopified_users( $user_id ) {
              $skope_string = skp_get_skope( null, true, array(
                  'meta_type' => 'user',
                  'type'      => 'author',
                  'obj_id'    => $user_id
              ) );
              $skope_id = skp_build_skope_id( array( 'skope_string' => $skope_string, 'skope_level' => 'local' ) );
              $skope_post_id_candidate = get_theme_mod( $skope_id );
              if ( $skope_post_id_candidate > 0 && get_post( $skope_post_id_candidate ) ) {
                  wp_delete_post( $skope_post_id_candidate );
                  remove_theme_mod( $skope_id );
              }
          }
    }//class
endif;

?>