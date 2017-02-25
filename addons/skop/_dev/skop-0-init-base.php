<?php


//delete_option( 'hu_theme_options' );


// delete_post_meta( ha_get_skope_post_id(), 'hueman_czr_all_page' );
// foreach ( get_post_meta( ha_get_skope_post_id() )  as $key => $value) {
//   delete_post_meta( ha_get_skope_post_id(), $key );
// }

// $options = get_option( 'hu_theme_options');
// $options['_primary-example-wgte'] = 's100';
// update_option( 'hu_theme_options', $options );


/* ------------------------------------------------------------------------- *
 *  MAYBE REGISTER CHANGESET POST TYPE IF WP < 4.7
/* ------------------------------------------------------------------------- */
if ( ! post_type_exists( 'czr_skope_opt') ) {
    register_post_type( 'czr_skope_opt', array(
      'labels' => array(
        'name'               => _x( 'Hueman scopes', 'post type general name' ),
        'singular_name'      => _x( 'Hueman scope', 'post type singular name' ),
        'menu_name'          => _x( 'Hueman scopes', 'admin menu' ),
        'name_admin_bar'     => _x( 'Hueman scope', 'add new on admin bar' ),
        'add_new'            => _x( 'Add New', 'Customize Hueman scope' ),
        'add_new_item'       => __( 'Add New Hueman scope' ),
        'new_item'           => __( 'New Hueman scope' ),
        'edit_item'          => __( 'Edit Hueman scope' ),
        'view_item'          => __( 'View Hueman scope' ),
        'all_items'          => __( 'All Hueman scopes' ),
        'search_items'       => __( 'Search Hueman scopes' ),
        'not_found'          => __( 'No Hueman scopes found.' ),
        'not_found_in_trash' => __( 'No Hueman scopes found in Trash.' ),
      ),
      'public' => false,
      '_builtin' => false,
      'map_meta_cap' => true,
      'hierarchical' => false,
      'rewrite' => false,
      'query_var' => false,
      'can_export' => false,
      'delete_with_user' => false,
      'supports' => array( 'title', 'author' ),
      'capability_type' => 'customize_changeset',
      'capabilities' => array(
        'create_posts' => 'customize',
        'delete_others_posts' => 'customize',
        'delete_post' => 'customize',
        'delete_posts' => 'customize',
        'delete_private_posts' => 'customize',
        'delete_published_posts' => 'customize',
        'edit_others_posts' => 'customize',
        'edit_post' => 'customize',
        'edit_posts' => 'customize',
        'edit_private_posts' => 'customize',
        'edit_published_posts' => 'do_not_allow',
        'publish_posts' => 'customize',
        'read' => 'read',
        'read_post' => 'customize',
        'read_private_posts' => 'customize',
      ),
    ) );
}




/* ------------------------------------------------------------------------- *
 *  CREATE SKOPE POST AND SAVE IT AS OPTION IF NEEDED
/* ------------------------------------------------------------------------- */
add_action( 'init', 'ha_create_skope_post');
//hook : init
function ha_create_skope_post( $reset = false ) {
  $post_id = $reset ? false : get_option('skope-post-id');

  if ( false !== $post_id ) {
    $skope_post = get_post( $post_id );
    if ( false != $skope_post )
      return;
  }
  $post_array = array();
  $post_array['post_type'] = 'czr_skope_opt';
  $post_array['post_name'] =  strtolower( THEMENAME ) . '_skope_post';
  $post_array['post_status'] = 'publish';
  $r = wp_insert_post( wp_slash( $post_array ), true );
  if ( ! is_wp_error( $r ) ) {
      update_option( 'skope-post-id', $r );
      return $r;
  } else {
      wp_die('ERROR : SKOPE POST IMPOSSIBLE TO CREATE in ha_create_skope_post().');
  }

}



/* ------------------------------------------------------------------------- *
 *  SKOPE HELPERS
/* ------------------------------------------------------------------------- */
/**
* Boolean helper
* @return bool
*/
function ha_is_option_skoped( $opt_name ) {
  return ! in_array( $opt_name, ha_get_skope_excluded_options() );
}



/**
* Helper : returns a set of options not skoped
* is filtered with the exclusions defined in the customizer setting map
* @return array()
*/
function ha_get_skope_excluded_options() {
  return apply_filters(
    'ha_get_skope_excluded_options',
    array_merge(
      array(
        //hueman design option
        'favicon',
        'featured-posts-include',
        // 'post-comments',
        // 'page-comments',
        'layout-home',
        'layout-single',
        'layout-archive',
        'layout-archive-category',
        'layout-search',
        'layout-404',
        'layout-page',
        'sidebar-areas',
        'about-page',
        'help-button',

        //wp built-ins
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'active_theme',
        'site_icon',

        //wp theme mods
        'custom_css',
        'custom_css_post_id'
      ),
      ha_get_protected_options()
    )
  );
}


/**
* Helper : define a set protected options. Never reset typically.
* @return array() of opt name
*/
function ha_get_protected_options() {
  return apply_filters(
      'ha_protected_options',
      array( 'defaults', 'ver', 'has_been_copied', 'last_update_notice', 'last_update_notice_pro' )
  );
}

/**
* Boolean helper
* @uses  the short option name
* @return bool
*/
function ha_is_option_protected( $opt_name ) {
  return in_array( $opt_name, ha_get_protected_options() );
}


function ha_get_wp_builtins_skoped_options() {
    $theme = get_option( 'stylesheet' );
    return apply_filters(
        'ha_builtins_skoped_options',
        array(
          'blogname',
          'blogdescription',
          //header_image, custom_logo, custom_css?
          "theme_mods_{$theme}"
        )
    );
}

function ha_get_wp_builtins_skoped_theme_mods() {
    return apply_filters(
      'ha_builtins_skoped_theme_mods',
      array(
        'custom_logo',
        'site_icon',
        'header_image',
        'header_image_data',
        'nav_menu_locations'
        //'sidebars_widgets'
      )
    );
}


function ha_is_wp_builtin_skoped_theme_mod( $opt_name ) {
  return in_array( $opt_name , ha_get_wp_builtins_skoped_theme_mods() );
}







/* ------------------------------------------------------------------------- *
 *  SKOPE MAKE CHANGESET DATA READY FOR FRONT END : used when publishing and previewing
/* ------------------------------------------------------------------------- */
// @param $data = array of changeset like values. Ex :
//  [hu_theme_options[copyright]] => Array
// (
//     [value] => copyright SAMPLE
//     [type] => option
//     [user_id] => 1
// )
//
// When publishing ($status == 'publish'), the purpose of this callback is :
// 1) to keep only the value when publishing the skope in database (save action)
// 2) to make sure that multidimensionals theme_mod types settings are being saved as...multidimensional options
// Typically :
// A theme_mod like nav_menu_locations has to be saved saved as :
//  [nav_menu_locations] => Array
// (
//     [footer] => 2
//     [topbar] => 4
// )
// => But in the changeset it looks like :
// nav_menu_locations[footer] = Array
// (
//     [value] => 2
//     [type] => theme_mod
//     [user_id] => 1
// )
// nav_menu_locations[topbar] = Array
// (
//     [value] => 4
//     [type] => theme_mod
//     [user_id] => 1
// )
//
// And in the changeset like :
function ha_prepare_skope_changeset_for_front_end( $data ) {
  global $wp_customize;
  $new_data = array();
  $multidimensionals = array();

  foreach ( $data as $raw_setting_id => $setting_data ) {
    if ( ! array_key_exists( 'value', $setting_data ) )
      continue;

    $setting_id = $raw_setting_id;
    // If theme_mod type, get rid of the theme name prefix
    if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {

        $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
        if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
          $setting_id = $matches['setting_id'];
        }
    }

    //setting id in array( 'sidebars_widgets', 'widget_', 'nav_menu_locations' );
    if ( _ha_is_wp_setting_multidimensional( $setting_id ) ) {
        $setting = $wp_customize->get_setting( $setting_id );
        if ( ! $setting || ! $setting->check_capabilities() ) {
            ha_error_log( 'In ha_set_settings_values_on_publish_save, ' . $setting_id . ' is not registered in $wp_customize.' );
            continue;
        }
        $new_value = $setting_data['value'];
        $set_name = $setting -> id;
        $id_data = $setting -> manager -> get_setting( $setting -> id ) -> id_data();
        $is_multidimensional = ! empty( $id_data['keys'] );
        //for example, $id_data['keys'] = array( 'header');
        if ( $is_multidimensional && ! _ha_is_theme_multidimensional_setting( $setting -> id ) ) {
            //Ex : nav_menu_locations
            $id_base = $id_data['base'];
            $set_name = $id_base;
            $multidimensionals[$set_name] = isset( $multidimensionals[$set_name] ) ? $multidimensionals[$set_name] : array();
            $root = $multidimensionals[$set_name];
            $root = _ha_multidimensional_replace( $root, $id_data['keys'], $new_value );
            $new_value = $root;

            $multidimensionals[$set_name] = array_merge( $multidimensionals[$set_name], $new_value );
        }
    } else {
        $new_data[$setting_id] = $setting_data['value'];
    }
  }
  // ha_error_log('////////////////// FINAL NEW VALUE ' );
  // ha_error_log( print_R( array_merge( $new_data, $multidimensionals ), true) );
  return array_merge( $new_data, $multidimensionals );
}












/* ------------------------------------------------------------------------- *
 *  SKOPE MULTIDIM HELPERS : used in option class, ajax and preview
/* ------------------------------------------------------------------------- */

//@return bool
//check if multidimensional base on a list of wp multidimensionals
function _ha_is_wp_setting_multidimensional( $base_name ) {
    $wp_multidimensional_prefix = array( 'sidebars_widgets', 'widget_', 'nav_menu_locations' );
    $found_match = false;
    foreach ( $wp_multidimensional_prefix as $_prefix ) {
        if ( $_prefix != substr($base_name, 0, strlen($_prefix) ) )
          continue;
        $found_match = true;
    }
    return $found_match;
}


// An option like nav_menu_locations is saved when published as :
//  [nav_menu_locations] => Array
// (
//     [footer] => 2
//     [topbar] => 4
//     [header] => 3
// )
// => when preparing it for the api, it must be turned into api ready settings looking like :
// nav_menu_locations[footer] = 2
// nav_menu_locations[topbar] = 4
// nav_menu_locations[header] = 3
function _ha_build_multidimensional_db_option( $actual_setting_id, $setting_value ) {
    if ( ! is_array( $setting_value ) ) {
      ha_error_log( 'IN _ha_build_multidimensional_db_option : $settings_params must be an array for setting_id : ' . $actual_setting_id );
      return array();
    }
    $api_ready = array();
    foreach ( $setting_value as $key => $value) {
        $new_key = implode( array( $actual_setting_id, '[', $key , ']') );
        $api_ready[$new_key] = $value;
    }
    return $api_ready;
}


/**
 * Will attempt to replace a specific value in a multidimensional array.
 *
 * @since 3.4.0
 *
 * @param $root
 * @param $keys
 * @param mixed $value The value to update.
 * @return mixed
 */
function _ha_multidimensional_replace( $root, $keys, $value ) {
  if ( ! isset( $value ) )
    return $root;
  elseif ( empty( $keys ) ) // If there are no keys, we're replacing the root.
    return $value;

  $result = ha_multidimensional( $root, $keys, true );

  if ( isset( $result ) )
    $result['node'][ $result['key'] ] = $value;

  return $root;
}


/**
 * Multidimensional helper function.
 *
 * @since 3.4.0
 *
 * @param $root
 * @param $keys
 * @param bool $create Default is false.
 * @return array|void Keys are 'root', 'node', and 'key'.
 */
function ha_multidimensional( &$root, $keys, $create = false ) {
  if ( $create && empty( $root ) )
    $root = array();

  if ( ! isset( $root ) || empty( $keys ) )
    return;

  $last = array_pop( $keys );
  $node = &$root;

  foreach ( $keys as $key ) {
    if ( $create && ! isset( $node[ $key ] ) )
      $node[ $key ] = array();

    if ( ! is_array( $node ) || ! isset( $node[ $key ] ) )
      return;

    $node = &$node[ $key ];
  }

  if ( $create ) {
    if ( ! is_array( $node ) ) {
      // account for an array overriding a string or object value
      $node = array();
    }
    if ( ! isset( $node[ $last ] ) ) {
      $node[ $last ] = array();
    }
  }

  if ( ! isset( $node[ $last ] ) )
    return;

  return array(
    'root' => &$root,
    'node' => &$node,
    'key'  => $last,
  );
}



//Extract the option name from the theme options array
function _ha_extract_setting_name( $setting_id ) {
    if ( false === strpos( $setting_id, HU_THEME_OPTIONS) )
      return $setting_id;
    return str_replace(array('[', ']', HU_THEME_OPTIONS ), '', $setting_id);
}



function _ha_is_theme_multidimensional_setting( $setting_id ) {
  return false !== strpos( $setting_id, HU_THEME_OPTIONS );
}

















function ha_get_skope_post_id() {
  $skope_post_id  = get_option('skope-post-id');
  $skope_post = get_post( $skope_post_id );
  if ( false == $skope_post_id || ! $skope_post || 'czr_skope_opt' != get_post_type( $skope_post ) ) {
      $skope_post_id = ha_create_skope_post( true );
      if ( false == $skope_post_id )
        wp_die('ERROR : NO SKOPE POST ID AVAILABLE in ha_get_skope_post_id().');
  }
  return $skope_post_id;
}



/**
* Get the meta skope data stored in a changeset post.
* This function is used both when updating the WP changeset post metas and the theme skope post meta
* @param $args = array()
* @return array|WP_Error Changeset data or WP_Error on error.
*/
function ha_get_skope_db_data( $args ) {
  // ha_error_log( '////////////////////ARGS MERDE');
  // ha_error_log( print_R( $args, true ) );
  $db_data = array();
  $defaults = array(
      'post_id'         => '',
      'skope_meta_key'  => '',
      'level'           => '',
      'is_option_post'  => false
  );
  $args = wp_parse_args( $args, $defaults );

  if ( ! $args['post_id'] ) {
    return new WP_Error( 'empty_post_id' );
  }

  //Are we retrieving the published skope options stored in the skope_post_option metas ?
  if ( true === $args['is_option_post'] ) {
      $skope_option_post = get_post( $args['post_id'] );
      if ( ! $skope_option_post ) {
        return new WP_Error( 'missing_skope_option_post' );
      }
      //ha_error_log( '//////////// POST TYPE ////////// ' . $skope_option_post->post_type );
      if ( 'czr_skope_opt' !== $skope_option_post->post_type ) {
        return new WP_Error( 'wrong_skope_option_post_type' );
      }
      if ( 'global' == $args['level'] ) {
        return new WP_Error( 'global_skope_can_not_be_get_from_the_published_skope_post' );
      }
      $db_data = get_post_meta( $args['post_id'] , $args['skope_meta_key'], true );

  } else {
      $changeset_post = get_post( $args['post_id'] );
      if ( 'customize_changeset' !== $changeset_post->post_type ) {
        return new WP_Error( 'wrong_post_type_for_changeset' );
      }
      if ( ! $changeset_post ) {
        return new WP_Error( 'missing_changeset_post' );
      }
      if ( in_array( get_post_status( $args['post_id'] ), array( 'publish', 'trash' ) ) ) {
        $db_data = array();
      }

      if ( 'global' == $args['level'] ) {
        $db_data = json_decode( $changeset_post->post_content, true );
      } else {
        $db_data = get_post_meta( $args['post_id'] , $args['skope_meta_key'], true );
      }

  }
  //If the post meta was not set yet, $db_data is false at this stage
  //always cast it as array
  $db_data = ( ! is_array( $db_data ) || empty( $db_data ) || false === $db_data ) ? array() : $db_data;


  //In the case we are not getting the option post changeset,
  // => make sure to not return data for a changeset already published or trashed
  // => might happen on a save action that we call the previous changeset_post_id

  // if ( ( true == $args['is_option_post'] && 'trash' != get_post_status( $args['post_id'] ) ) || ! in_array( get_post_status( $args['post_id'] ), array( 'publish', 'trash' ) ) ) {
  //     if ( 'global' != $args['level'] ) {
  //         ha_error_log( '////////////////////FUCK');
  //         ha_error_log( print_R( $args, true ) );
  //         ha_error_log( 'global' !== $args['level'] );
  //         $db_data = get_post_meta( $args['post_id'] , $args['skope_meta_key'], true );
  //         ha_error_log( '//////////////// PROUT ////////////////// ');
  //         ha_error_log( print_R( get_post_meta( $args['post_id'] , $args['skope_meta_key'], true ), true ) );
  //         $db_data = is_array( $db_data ) ? $db_data : array();
  //         ha_error_log( '//////////////// PET ////////////////// ');
  //         ha_error_log( print_R( $db_data, TRUE ) );
  //     } else {
  //         if ( $args['is_option_post'] ) {
  //             return new WP_Error( 'global_skope_is_not_saved_in_published_skope_post' );
  //         }
  //         //ha_error_log('changeset_post_status : ' . $changeset_post -> post_status );
  //         $db_data = json_decode( $changeset_post->post_content, true );
  //     }
  // }
  if ( function_exists( 'json_last_error' ) && json_last_error() ) {
    return new WP_Error( 'json_parse_error', '', json_last_error() );
  }
  if ( ! is_array( $db_data ) ) {
    return new WP_Error( 'expected_array_for_changeset_data' );
  }
  return $db_data;
}


//map ctx and db opt type
//@return string
function ha_get_skope_dyn_type( $meta_type ) {
  $_map = array(
    'post'    => 'post_meta',
    'tax'     => 'term_meta',
    'user'    => 'user_meta',
  );
  return isset( $_map[$meta_type] ) ? $_map[$meta_type] : 'trans';
}

//@return array of possible dyn types
function ha_get_dyn_types() {
  return apply_filters( 'ha_dyn_types',
    array('option','skope_meta')
    //array( 'option', 'post_meta', 'term_meta', 'user_meta', 'trans' )
  );
}


/**
* Return the current ctx. Front / Back agnostic.
* @param $_requesting_wot is a string with the follwing possible values : 'meta_type' (like post) , 'type' (like page), 'id' (like page id)
* @param $_return_string string param stating if the return value should be a string or an array
* @return a string of all concatenated ctx parts (default) 0R an array of the ctx parts
*/
function ha_get_skope( $_requesting_wot = null, $_return_string = true ) {
  //Contx builder from the wp $query
  //=> returns :
  //    the meta_type : post, tax, user
  //    the type : post_type, taxonomy name, author
  //    the id : post id, term id, user id
  $parts    = ha_get_query_skope();
  $_return  = array();
  $meta_type = $type = $obj_id = '';

  if ( is_array( $parts) && ! empty( $parts ) ) {
    $meta_type  = isset( $parts['meta_type'] ) ? $parts['meta_type'] : false;
    $type       = isset( $parts['type'] ) ? $parts['type'] : false;
    $obj_id     = isset( $parts['obj_id'] ) ? $parts['obj_id'] : false;
  }

  switch ( $_requesting_wot ) {
    case 'meta_type':
      if ( false != $meta_type )
        $_return = array( "meta_type" => "{$meta_type}" );
    break;

    case 'type':
      if ( false != $type )
        $_return = array( "type" => "{$type}" );
    break;

    case 'id':
      if ( false != $obj_id )
        $_return = array( "id" => "{$obj_id}" );
    break;

    default:
      //LOCAL
      //here we don't check if there's a type this is the case where there must be one when a meta type (post, tax, user) is defined.
      //typically the skope will look like post_page_25
      if  ( false != $meta_type && false != $obj_id ) {
        $_return = array( "meta_type" => "{$meta_type}" , "type" => "{$type}", "id" => "{$obj_id}" );
      }
      //GROUP
      else if ( false != $meta_type && ! $obj_id ) {
        $_return = array( "meta_type" => "{$meta_type}", "type" => "{$type}" );
      }
      //LOCAL WITH NO GROUP : home, 404, search, date, post type archive
      else if ( false != $obj_id ) {
        $_return = array( "id" => "{$obj_id}" );
      }
    break;
  }

  //return the parts array if not a string requested
  if ( ! $_return_string ) {
    return $_return;
  }

  //don't go further if not an array or empty
  if ( ! is_array( $_return ) || ( is_array( $_return ) && empty( $_return ) ) )
    return '';

  //if a specific part of the ctx is requested, don't concatenate
  //return the part if exists
  if ( ! is_null($_requesting_wot) )
    return isset($_return[$_requesting_wot]) ? $_return[$_requesting_wot] : '';

  //generate the ctx string from the array of ctx_parts
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
* Contx builder from the wp $query
* !! has to be fired after 'template_redirect'
* Used on front ( not customizing preview ? => @todo make sure of this )
* @return  array of ctx parts
*/
function ha_get_query_skope() {
  //don't call get_queried_object if the $query is not defined yet
  global $wp_the_query;
  if ( ! isset( $wp_the_query ) || empty( $wp_the_query ) )
    return array();

  $current_obj  = get_queried_object();
  $meta_type    = false;
  $type         = false;
  $obj_id       = false;


  if ( is_object( $current_obj ) ) {
      //post, custom post types, page
      if ( isset($current_obj -> post_type) ) {
          $meta_type  = 'post';
          $type       = $current_obj -> post_type;
          $obj_id     = $current_obj -> ID;
      }

      //taxinomies : tags, categories, custom tax type
      if ( isset($current_obj -> taxonomy) && isset($current_obj -> term_id) ) {
          $meta_type  = 'tax';
          $type       = $current_obj -> taxonomy;
          $obj_id     = $current_obj -> term_id;
      }
  }

  //author archive page
  if ( is_author() ) {
      $meta_type  = 'user';
      $type       = 'author';
      $obj_id     = $wp_the_query ->get( 'author' );
  }

  //SKOPES WITH NO GROUPS
  //post type archive object
  if ( is_post_type_archive() ) {
      $obj_id     = 'post_type_archive' . '_'. $wp_the_query ->get( 'post_type' );
  }
  if ( is_404() )
    $obj_id  = '404';
  if ( is_search() )
    $obj_id  = 'search';
  if ( is_date() )
    $obj_id  = 'date';
  if ( hu_is_real_home() )
    $obj_id  = 'home';

  return apply_filters( 'ha_get_query_skope' , array( 'meta_type' => $meta_type , 'type' => $type , 'obj_id' => $obj_id ) , $current_obj );
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
function ha_get_skope_title( $args = array() ) {
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

    $_dyn_type = ( HU_AD() -> ha_is_customize_preview_frame() && isset( $_POST['dyn_type']) ) ? $_POST['dyn_type'] : '';
    $type = ha_get_skope('type');
    $skope = ha_get_skope();
    $title = '';

    if( 'local' == $level ) {
        $type = ha_get_skope( 'type' );
        $title = $is_prefixed ? __( 'Options for', 'hueman-addons' ) . ' ' : $title;
        if ( ha_skope_has_a_group( $meta_type ) ) {
            $_id = ha_get_skope('id');
            switch ($meta_type) {
                case 'post':
                  $type_obj = get_post_type_object( $type );
                  $title .= sprintf( '%1$s (%2$s), "%3$s"', strtolower( $type_obj -> labels -> singular_name ), $_id, get_the_title( $_id ) );
                  break;

                case 'tax':
                  $type_obj = get_taxonomy( $type );
                  $term = get_term( $_id, $type );
                  $title .= sprintf( '%1$s (%2$s), "%3$s"', strtolower( $type_obj -> labels -> singular_name ), $_id, $term -> name );
                  break;

                case 'user':
                  $author = get_userdata( $_id );
                  $title .= sprintf( '%1$s (%2$s), "%3$s"', __('user', 'hueman-addons'), $_id, $author -> user_login );
                  break;
            }
        } else if ( ( 'trans' == $_dyn_type || ha_skope_has_no_group( $skope ) ) ) {
            if ( is_post_type_archive() ) {
                global $wp_the_query;
                $title .= sprintf( __( '%1$s archive page', 'hueman-addons' ), $wp_the_query ->get( 'post_type' ) );
            } else {
                $title .= strtolower( $skope );
            }
        } else {
            $title .= __( 'Undefined', 'hueman-addons' );
        }
    }
    if ( 'group' == $level || 'special_group' == $level ) {
        $title = $is_prefixed ? __( 'Options for all', 'hueman-addons' ) . ' ' : __( 'All' , 'hueman-adons' ) . ' ';
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
                $title .= __('users', 'hueman-addons');
            break;
        }
    }
    if ( 'global' == $level ) {
        $title = __( 'Sitewide options', 'hueman-addons' );
    }
    $title = ucfirst( $title );
    return ha_trim_text( $title, $long ? 45 : 28, '...');
}





//@return bool
//=> tells if the current skope is part of the ones without group
function ha_skope_has_no_group( $meta_type ) {
    return in_array(
      $meta_type,
      array( 'home', 'search', '404', 'date' )
    ) || is_post_type_archive();
}

//@return bool
//Tells if the current skope has a group level
function ha_skope_has_a_group( $meta_type ) {
    return in_array(
      $meta_type,
      array('post', 'tax', 'user')
    );
}


//normalizes the skope model server and client side (json sent to customizer)
function _ha_get_default_scope_model() {
    return array(
        'title'       => '',
        'long_title'  => '',
        'ctx_title'   => '',
        'id'          => '',
        'skope'       => '',
        'level'       => '',
        'dyn_type'    => '',
        'opt_name'    => '',
        'obj_id'      => '',
        'is_winner'   => false,
        'is_forced'  => false,
        'db'    => array(),
        'changeset' => array(),
        'has_db_val'  => false,
        'color'       => 'rgb(255, 255, 255)'
    );
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


//Determine if the current ajax request is a selective partial refresh
//Checks if the ajax query contains either : renderQueryVar 'wp_customize_render_partials', declared in class-wp-customize-selective-refresh
//or the 'partials' param
function ha_is_partial_ajax_request() {
  return isset( $_POST['wp_customize_render_partials'] ) || ( isset($_POST['partials']) && ! empty( $_POST['partials'] ) );
}






/* ------------------------------------------------------------------------- *
 * SKOPIFY LAYOUT CLASS
/* ------------------------------------------------------------------------- */
//Disable layout metaboxes in page and post admin
add_filter('hu_enable_singular_layout_meta_box', '__return_false');

//filter the front end layout class for singular that may have a user defined specific layout set
add_filter( 'hu_layout_class', 'hu_skopify_layout_class' , 10, 2 );
function hu_skopify_layout_class( $layout, $has_post_meta ) {
  //$default = 'col-3cm';
  if ( ! is_singular() )
    return hu_get_option( 'layout-global' );

  global $post;
  $skopified_meta = get_post_meta( $post->ID,'_layout_skopified',true );
  $is_skopified = isset($skopified_meta) && true == $skopified_meta;

  //if no post meta set for a singular post
  //or if it has been skopified,
  // => then apply the skope inheritance
  if ( ! $has_post_meta || $is_skopified ) {
    return hu_get_option( 'layout-global' );
  }

  return $layout;
}

//If a layout post meta has been set for a singular post / page
//1) update the published skope meta with the old meta value
//2) write a new post meta : 'layout_skopified' to indicate it's been done
add_action( 'wp', 'hu_skopify_singular_layout', 0 );
function hu_skopify_singular_layout() {
  if ( ! current_user_can( 'edit_theme_options' ) )
    return;
  if ( ! is_singular() )
    return;

  global $post;

  $old_meta_val = get_post_meta( $post->ID,'_layout',true );

  // Did user set a layout meta ?
  $has_post_meta = isset($old_meta_val) && ! empty($old_meta_val) && $old_meta_val != 'inherit';
  if ( ! $has_post_meta )
    return;

  $skopified_meta = get_post_meta( $post->ID,'_layout_skopified',true );

  if ( isset($skopified_meta) && true == $skopified_meta )
    return;

  $skope_post_id = ha_get_skope_post_id();
  $theme_opt_name = strtolower( HU_THEME_OPTIONS );
  $meta_key = HA_SKOP_OPT() -> ha_get_skope_opt_name( 'local');

  $skope_meta_values = get_post_meta( $skope_post_id, $meta_key, true );
  $skope_meta_values =  ( ! $skope_meta_values || ! is_array( $skope_meta_values ) ) ? array() : $skope_meta_values;

  $skope_meta_values["{$theme_opt_name}[layout-global]"] = $old_meta_val;
  //update skope meta
  update_post_meta( $skope_post_id, $meta_key, $skope_meta_values );
  //indicate it's done
  update_post_meta( $post->ID, '_layout_skopified', true );
}

//if a specific layout was set in option for a pre-defined context :
//1) update the published skope meta layout-global option with the user value
//2) write a new option in db to indicate the layout has been skopified site wide
add_action( 'init', 'hu_skopify_layout_option', 100 );
function hu_skopify_layout_option() {
    if ( ! current_user_can( 'edit_theme_options' ) )
      return;
    if ( true == get_option( 'hu_layout_option_skopified' ) )
      return;
    $layout_options_map = array(
        'layout-home'     => array( 'hueman_czr_home' ),
        'layout-single'   => array( 'hueman_czr_all_post' ),
        'layout-archive'  => array( 'hueman_czr_all_category', 'hueman_czr_all_post_tag', 'hueman_czr_all_author', 'hueman_czr_date' ),
        'layout-archive-category' => array( 'hueman_czr_all_category' ),
        'layout-search'   => array( 'hueman_czr_search'),
        'layout-404'      => array( 'hueman_czr_404'),
        'layout-page'     => array( 'hueman_czr_all_page')
    );
    $skope_post_id = ha_get_skope_post_id();
    $raw_options = hu_get_raw_option( null, HU_THEME_OPTIONS );
    $theme_opt_name = strtolower( HU_THEME_OPTIONS );
    foreach ( $layout_options_map as $_short_opt_name => $meta_keys) {
        if ( ! array_key_exists( $_short_opt_name, $raw_options ) || 'inherit' == $raw_options[$_short_opt_name] )
          continue;
        $skope_meta_val = array( "{$theme_opt_name}[layout-global]" => $raw_options[$_short_opt_name] );
        foreach ( $meta_keys as $meta_key ) {
            update_post_meta( $skope_post_id, $meta_key, $skope_meta_val );
        }
    }
    update_option( 'hu_layout_option_skopified', true );
}

//De-register settings from map
add_filter( 'hu_content_layout_sec', 'ha_deregister_layout_settings' );
function ha_deregister_layout_settings( $settings ) {
  $new_settings = array();
  $to_remove = array(
    'layout-home',
    'layout-single',
    'layout-archive',
    'layout-archive-category',
    'layout-search',
    'layout-404',
    'layout-page'
  );

  foreach ( $settings as $key => $value ) {
    if ( in_array($key, $to_remove) )
      continue;
    $new_settings[$key] = $value;
  }
  return $new_settings;
}






/* ------------------------------------------------------------------------- *
 *  DISABLE PARTIAL REFRESH FOR NOW IF SKOPE IS ON
/* ------------------------------------------------------------------------- */
//filter declared in init-core
add_filter( 'hu_partial_refresh_on', '__return_true');














/*****************************************************
* ADMIN CONTEXT
*****************************************************/
//@todo author case not handled
function ha_get_admin_ctx() {
    if ( ! is_admin() )
      return array();

    global $tag;
    $current_screen = get_current_screen();
    $post           = get_post();
    $meta_type      = false;
    $type           = false;
    $obj_id         = false;

    //post case : page, post CPT
    if ( 'post' == $current_screen->base
      && 'add' != $current_screen->action
      && ( $post_type_object = get_post_type_object( $post->post_type ) )
      && current_user_can( 'read_post', $post->ID )
      && ( $post_type_object->public )
      && ( $post_type_object->show_in_admin_bar )
      && ( 'draft' != $post->post_status ) )
    {
      $meta_type  = 'post';
      $type       = $post -> post_type;
      $obj_id     = $post -> ID;
    }
    //tax case : tags, cats, custom tax
    elseif ( 'edit-tags' == $current_screen->base
      && isset( $tag ) && is_object( $tag )
      && ( $tax = get_taxonomy( $tag->taxonomy ) )
      && $tax->public )
    {
      $meta_type  = 'tax';
      $type       = $tag -> taxonomy ;
      $obj_id     = $tag -> term_id;
    }
    return apply_filters( 'ha_get_admin_ctx' , array( $meta_type , $type , $obj_id ) );
}


function ha_trim_text( $text, $text_length, $more ) {
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






/* ------------------------------------------------------------------------- *
 *  DISMISS CUSTOMIZER TOP NOTE AJAX ACTIONS
/* ------------------------------------------------------------------------- */
add_action( 'wp_ajax_czr_dismiss_top_note',  'ha_ajax_czr_dismiss_top_note' );
function ha_ajax_czr_dismiss_top_note() {
  global $wp_customize;
  if ( ! is_user_logged_in() ) {
      wp_send_json_error( 'unauthenticated' );
  }
  if ( ! current_user_can( 'edit_theme_options' ) ) {
    wp_send_json_error('user_cant_edit_theme_options');
  }
  if ( ! $wp_customize->is_preview() ) {
      wp_send_json_error( 'not_preview' );
  } else if ( ! current_user_can( 'customize' ) ) {
      status_header( 403 );
      wp_send_json_error( 'customize_not_allowed' );
  } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
      status_header( 405 );
      wp_send_json_error( 'bad_method' );
  }
  $action = 'save-customize_' . $wp_customize->get_stylesheet();
  if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
      wp_send_json_error( 'invalid_nonce' );
  }
  $r = update_option( 'ha_czr_top_note_status', 'dismissed' );
  if ( is_wp_error( $r ) ) {
      wp_send_json_error( $r->get_error_message() );
  } else {
      wp_send_json_success();
  }
}
?>