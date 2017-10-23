<?php
//@return a normalized theme name string
//@important => used to get the skoped options. Don't messes up with this !
//work with a child theme, will always get the parent theme name which is what we want.
function ha_get_skope_theme_name() {
    $_theme                     = wp_get_theme();
    //Get infos from parent theme if using a child theme
    $_theme = $_theme -> parent() ? $_theme -> parent() : $_theme;
    $theme_name = strtolower( $_theme -> name );// wp_get_theme() -> name is uppercase and has spaces. Example : Hueman Pro

    if ( false !== strpos( $theme_name, 'hueman' ) ) {
      $theme_name = 'hueman';
    } else if ( false !== strpos( $theme_name, 'customizr' ) ) {
        $theme_name = 'customizr';
    } else {
        $theme_name = str_replace(' ', '_', $theme_name );
    }
    return $theme_name;
}

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
  $post_array['post_name'] =  ha_get_skope_theme_name() . '_skope_post';
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
 *  PLUGINS COMPAT
/* ------------------------------------------------------------------------- */
if ( ha_is_plugin_active('polylang/polylang.php') ) {
  add_filter( 'ha_skope_navmenu', '__return_false' );
  add_filter( 'ha_get_skope_excluded_options', 'ha_exclude_skoped_settings_with_polylang' );
  function ha_exclude_skoped_settings_with_polylang( $excluded_list ) {
      if ( ! is_array( $excluded_list ) )
        return array();
      $excluded_list[] = 'blogname';
      $excluded_list[] = 'blogdescription';
      return $excluded_list;
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
        'show-skope-infos',
        'enable-skope',
        'attachments-in-search',

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
    $ha_builtins_skoped_theme_mods = array(
        'custom_logo',
        'site_icon',
        'header_image',
        'header_image_data'
    );

    //The filter 'ha_skope_navmenu' is alos used when localizing params to the control js in skop-customize-registed.php
    // 'isNavMenuLocationsSkoped'  => (bool)apply_filters( 'ha_skope_navmenu', true ),
    if ( (bool)apply_filters( 'ha_skope_navmenu', true ) ) {
        $ha_builtins_skoped_theme_mods[] = 'nav_menu_locations';
    }
    return apply_filters('ha_builtins_skoped_theme_mods', $ha_builtins_skoped_theme_mods );
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
  //         ha_error_log( '////////////////////');
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
                  $title .= sprintf( '%1$s "%3$s" (id : %2$s)', strtolower( $type_obj -> labels -> singular_name ), $_id, get_the_title( $_id ) );
                  break;

                case 'tax':
                  $type_obj = get_taxonomy( $type );
                  $term = get_term( $_id, $type );
                  $title .= sprintf( '%1$s "%3$s" (id : %2$s)', strtolower( $type_obj -> labels -> singular_name ), $_id, $term -> name );
                  break;

                case 'user':
                  $author = get_userdata( $_id );
                  $title .= sprintf( '%1$s "%3$s" (id : %2$s)', __('user', 'hueman-addons'), $_id, $author -> user_login );
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
<?php

/**
 * This Class is instantiated on 'plugin_init', declared in /init-skop.php
 */
if ( ! class_exists( 'HA_Skop_Chset_Base' ) ) :
    class HA_Skop_Chset_Base {

        function __construct() {
            add_action( 'wp_ajax_customize_skope_changeset_save', array( $this, 'ha_ajax_skope_changeset_save' ) );
        }


        /* ------------------------------------------------------------------------- *
         *  SAVE OR PUBLISH SKOPE CHANGESET
         *  => as a meta of the _temp changeset post if status != "publish"
         *  => as a meta of the option changeset post if status == "publish" and skope is not 'global'
        /* ------------------------------------------------------------------------- */
        /**
          * Handle customize_skope_changet_save WP Ajax request to save/update a changeset.
         *
         */
        function ha_ajax_skope_changeset_save() {
            // Ensure retro compat with version < 4.7
            if ( isset( $_POST['skope']) && 'global' == $_POST['skope'] )
                return wp_send_json_success( 'Global skope changeset is not saved as meta' );

            if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
                return wp_send_json_error( 'Missing $_POST data to save the changeset skope meta.' );

            global $wp_customize;
            if ( ! is_user_logged_in() ) {
                wp_send_json_error( 'unauthenticated' );
            }

            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            }
            //let's use WP nonce
            $action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            // Validate changeset status param.
            $is_publish = null;
            $changeset_status = null;
            if ( isset( $_POST['customize_changeset_status'] ) ) {
                $changeset_status = wp_unslash( $_POST['customize_changeset_status'] );
                if ( ! get_post_status_object( $changeset_status ) || ! in_array( $changeset_status, array( 'draft', 'pending', 'publish', 'future' ), true ) ) {
                    wp_send_json_error( 'bad_customize_changeset_status', 400 );
                }
                $is_publish = ( 'publish' === $changeset_status || 'future' === $changeset_status );
                if ( $is_publish && HU_AD() -> ha_is_changeset_enabled() ) {
                    if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->publish_posts ) ) {
                        wp_send_json_error( 'changeset_publish_unauthorized', 403 );
                    }
                    if ( false === has_action( 'transition_post_status', '_wp_customize_publish_changeset' ) ) {
                        wp_send_json_error( 'missing_publish_callback', 500 );
                    }
                }
            }

            //Bail here for WP version < 4.7
            if ( ! $is_publish && ! HU_AD() -> ha_is_changeset_enabled() ) {
              wp_send_json_error( 'changeset_feature_is_not_enabled' );
            }

            $skope_post_id = null;
            // Are we customizing or publishing ?
            // => set the right changeset post id
            // => check the post status
            // Ensure retro compat with version < 4.7
            if ( ! is_null( $changeset_status ) && $is_publish ) {
                $skope_post_id  = get_option('skope-post-id');
                if ( false === $skope_post_id || empty( $skope_post_id ) ) {
                    wp_send_json_error( 'missing skope_post_id when attempting to publish the skope meta option' );
                }
            } else {
                $changeset_post_id = $wp_customize->changeset_post_id();
                if ( $changeset_post_id && in_array( get_post_status( $changeset_post_id ), array( 'publish', 'trash' ) ) ) {
                    wp_send_json_error( 'changeset_already_published' );
                }
            }

            // if ( empty( $changeset_post_id ) ) {
            //     if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->create_posts ) ) {
            //         wp_send_json_error( 'cannot_create_changeset_post' );
            //     }
            // } else {
            //     if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->edit_post, $changeset_post_id ) ) {
            //         wp_send_json_error( 'cannot_edit_changeset_post' );
            //     }
            // }
            // CUSTOMIZE CHANGESET DATA IS SENT AS A JSON, WHEN DECODED, LOOKS LIKE :
            // Array(

            //             'nav_menu_locations[footer]' => Array
            //                 (
            //                     'value' => 11
            //                 )

            //             'hu_theme_options[site-description]' => Array
            //                 (
            //                     'value' =>
            //                 )

            //             'header_image' => Array
            //                 (
            //                     'value' => http://wp-betas.dev/wp-content/uploads/2016/10/cropped-28efabf02ea881d89514f659c29d27c0.jpg
            //                 )
            // )
            //
            if ( ! empty( $_POST['customize_changeset_data'] ) ) {
                $input_changeset_data = json_decode( wp_unslash( $_POST['customize_changeset_data'] ), true );
                if ( ! is_array( $input_changeset_data ) ) {
                    wp_send_json_error( 'invalid_customize_changeset_data' );
                }
            } else {
                $input_changeset_data = array();
            }



            //ATTEMPT TO SAVE OR PUBLISH THE SKOPE CHANGESET META
            if ( 'publish' == $changeset_status ) {
                $r = $this -> _publish_skope_changeset_metas( array(
                    'status' => $changeset_status,
                    // 'title' => $changeset_title,
                    // 'date_gmt' => $changeset_date_gmt,
                    'data' => $input_changeset_data,
                ) );
            } else {
                $r = $this -> _save_skope_changeset_metas( array(
                    'status' => $changeset_status,
                    // 'title' => $changeset_title,
                    // 'date_gmt' => $changeset_date_gmt,
                    'data' => $input_changeset_data,
                ) );
            }

            if ( is_wp_error( $r ) ) {
              $response = $r->get_error_data();
            } else {
                $response = $r;
                if ( HU_AD() -> ha_is_changeset_enabled() ) {
                    if ( $is_publish ) {
                        $response['changeset_status'] = get_post_status( $skope_post_id );
                    } else {
                        // Note that if the changeset status was publish, then it will get set to trash if revisions are not supported.
                        $response['changeset_status'] = get_post_status( $wp_customize->changeset_post_id() );
                    }

                }
                // if ( $is_publish && 'trash' === $response['changeset_status'] ) {
                //   $response['changeset_status'] = 'publish';
                // }
            }

            if ( isset( $response['setting_validities'] ) ) {
                $response['setting_validities'] = array_map( array( $wp_customize, 'prepare_setting_validity_for_js' ), $response['setting_validities'] );
            }

            $response = apply_filters( 'customize_save_response', $response, $wp_customize );

            if ( is_wp_error( $r ) ) {
                wp_send_json_error( $response );
            } else {
                wp_send_json_success( $response );
            }
        }






        //COPY OF wp_customize_manager::validate_setting_values introduced in wp 4.6
        function _ha_validate_setting_values( $setting_values, $options = array() ) {
            global $wp_customize;
            $options = wp_parse_args( $options, array(
              'validate_capability' => false,
              'validate_existence' => false,
            ) );

            $validities = array();
            foreach ( $setting_values as $setting_id => $unsanitized_value ) {
              $setting = $wp_customize->get_setting( $setting_id );
              if ( ! $setting ) {
                if ( $options['validate_existence'] ) {
                  $validities[ $setting_id ] = new WP_Error( 'unrecognized', __( 'Setting does not exist or is unrecognized.' ) );
                }
                continue;
              }
              if ( $options['validate_capability'] && ! current_user_can( $setting->capability ) ) {
                $validity = new WP_Error( 'unauthorized', __( 'Unauthorized to modify setting due to capability.' ) );
              } else {
                if ( is_null( $unsanitized_value ) ) {
                  continue;
                }
                $validity = $setting->validate( $unsanitized_value );
              }
              if ( ! is_wp_error( $validity ) ) {
                /** This filter is documented in wp-includes/class-wp-customize-setting.php */
                $late_validity = apply_filters( "customize_validate_{$setting->id}", new WP_Error(), $unsanitized_value, $setting );
                if ( ! empty( $late_validity->errors ) ) {
                  $validity = $late_validity;
                }
              }
              if ( ! is_wp_error( $validity ) ) {
                $value = $setting->sanitize( $unsanitized_value );
                if ( is_null( $value ) ) {
                  $validity = false;
                } elseif ( is_wp_error( $value ) ) {
                  $validity = $value;
                }
              }
              if ( false === $validity ) {
                $validity = new WP_Error( 'invalid_value', __( 'Invalid value.' ) );
              }
              $validities[ $setting_id ] = $validity;
            }
            return $validities;
          }



        //this utility is used to preprocess the value for any type : trans, meta
        //@param value : array()
        //@param $setting : setting instance
        //@todo : improve performances by getting the aggregated multidimensional, just like WP does
        //@return updated option associative array( opt_name1 => value 1, opt_name2 => value2, ... )
        // function _ha_customizer_preprocess_save_value( $new_value, $setting, $current_value ) {
        //     //assign a default val to the set_name var
        //     $set_name = $setting -> id;
        //     $id_data = $setting -> manager -> get_setting( $setting -> id ) -> id_data();
        //     $is_multidimensional = ! empty( $id_data['keys'] );

        //     if ( $is_multidimensional && ! _ha_is_theme_multidimensional_setting( $setting -> id ) ) {
        //       $id_base = $id_data['base'];
        //       $set_name = $id_base;
        //       $root = ( is_array($current_value) && isset($current_value[$set_name]) ) ? $current_value[$set_name] : array();
        //       $root = _ha_multidimensional_replace( $root, $id_data['keys'], $new_value );
        //       $new_value = $root;
        //     }

        //     $new_value = apply_filters('_ha_customizer_preprocess_save_value', $new_value, $current_value, $setting );

        //     //hu_theme_options case
        //     if ( _ha_is_theme_multidimensional_setting( $setting -> id ) ) {
        //       $set_name = _ha_extract_setting_name( $setting -> id );
        //     }

        //     //bail if no set name set here
        //     if ( ! isset($set_name ) )
        //       return $current_value;

        //     if ( ! $current_value || ! is_array($current_value) ) {
        //       $to_return = array( $set_name => $new_value );
        //     } else {
        //       $to_return = $current_value;
        //       $to_return[$set_name] = $new_value;
        //     }
        //     return $to_return;
        // }
    }//class
endif;

?><?php


/* ------------------------------------------------------------------------- *
 *  PUBLISH SKOPE CHANGESET
 *  => as a meta of the option changeset post if stats == "publish"
/* ------------------------------------------------------------------------- */
//Same as save_changeset_post() but for the skope customized values
//It would have been better to use the existing core method but it's not possible with the current version
// => there's no way to populate the $post_array with a filter to add the meta_input entry
// $args = array(
//  'status' => '',
//  'data' => json_decode( wp_unslash( $_POST['customize_changeset_data'] ), true )
// )
if ( ! class_exists( 'HA_Skop_Chset_Publish' ) ) :
    class HA_Skop_Chset_Publish extends HA_Skop_Chset_Base {
        function __construct() {
          parent::__construct();
          add_action( 'wp_ajax_czr_clean_skope_changeset_metas_after_publish',  array( $this, 'ha_ajax_clean_skope_changeset_metas_after_publish' ) );
        }

        function _publish_skope_changeset_metas( $args = array() ) {
            // ha_error_log( '//////////////// START ARGS PARAM ////////////////// ');
            // ha_error_log( print_R( $args, TRUE ) );
            // ha_error_log( '//////////////// END ARGS PARAM ////////////////// ');
            global $wp_customize;
            if ( 'publish' != $args['status'] ) {
                wp_send_json_error( '_publish_skope_changeset_metas : status must be set to publish' );
                return;
            }
            if ( 'global' == HA_SKOP_OPT() -> ha_get_current_customized_skope() ) {
                wp_send_json_error( '_publish_skope_changeset_metas() : the global skope can not be saved this way' );
                return;
            }

            if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) ) {
                wp_send_json_error( '_publish_skope_changeset_metas() : Missing opt_name or skope_id or skopeCustomized' );
                return;
            }

            $skope_meta_key = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name');
            $skope_id       = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );

            $args = array_merge(
                array(
                  'status' => null,
                  'data' => array(),
                  'user_id' => get_current_user_id()
                ),
                $args
            );

            //We are publishing
            $skope_post_id  = get_option('skope-post-id');
            if ( false === $skope_post_id || empty( $skope_post_id ) ) {
                wp_send_json_error( 'missing skope_post_id when attempting to publish the meta changeset' );
            }

            if ( ! $skope_post_id ) {
                wp_send_json_error( 'NO SKOPE CHANGESET POST ID' );
                return;
            }

            $_already_published_data = ha_get_skope_db_data(
                array(
                  'post_id' => $skope_post_id,
                  'skope_meta_key' => $skope_meta_key,
                  'is_option_post' => true,
                  'level' => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope')
                )
            );

            if ( is_wp_error( $_already_published_data ) ) {
                $response['publish_skope_changeset_failure'] = $_already_published_data -> get_error_code();
                return new WP_Error( 'publish_skope_changeset_failure', '', $response );
            }

            // ha_error_log( '//////////////// START ORIGINAL CHANGESET DATA ////////////////// ');
            // ha_error_log( $skope_post_id );
            // ha_error_log( $skope_meta_key );
            // ha_error_log( print_R( $existing_changeset_data, TRUE ) );
            // ha_error_log( '//////////////// END ORIGINAL CHANGESET DATA////////////////// ');
            //in publish context, the saved data looks like
            // [hu_theme_options[copyright]] => copyright SAMPLE 7
            //
            // in changeset update context, the saved data looks like
            // [hu_theme_options[copyright]] => Array
            // (
            //     [value] => copyright SAMPLE
            //     [type] => option
            //     [user_id] => 1
            // )
            $normalized_published_data = array();
            // An option like nav_menu_locations is saved as :
            //  [nav_menu_locations] => Array
            // (
            //     [footer] => 2
            //     [topbar] => 4
            //     [header] => 3
            // )
            // => it must be turned into changeset compatible settings looking like :
            // nav_menu_locations[footer] = array( 'value' => 2 )
            // nav_menu_locations[topbar] = array( 'value' => 4 )
            // nav_menu_locations[header] = array( 'value' => 3 )
            foreach ( $_already_published_data as $_setid => $_value ) {
                if ( 'skope_infos' == $_setid ) {
                    $normalized_published_data[$_setid] = $_value;
                } else if ( _ha_is_wp_setting_multidimensional( $_setid ) ) {
                    $to_merge = _ha_build_multidimensional_db_option( $_setid, $_value );
                    foreach ( $to_merge as $_id => $val ) {
                        $normalized_published_data[$_id] = array( 'value' => $val );
                    }
                } else {
                    $normalized_published_data[$_setid] = array( 'value' => $_value );
                }
            }

            //ha_error_log( '//////////////// START ORIGINAL CHANGESET DATA ////////////////// ');
            // ha_error_log( $skope_post_id );
            // ha_error_log( $skope_meta_key );
            //ha_error_log( print_R( ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key, 'is_option_post' => true ) ), TRUE ) );
            // ha_error_log( print_R( HA_SKOP_OPT() -> ha_get_unsanitized_customized_values( $skope_id ) ), true );
            //ha_error_log( '//////////////// END ORIGINAL CHANGESET DATA////////////////// ');


            // The request was made via wp.customize.previewer.save().
            $update_transactionally = (bool) $args['status'];
            $allow_revision = (bool) $args['status'];
            //Default response
            $response = array(
              'setting_validities'  => array()
            );

            // The customized data are already structured like in the changeset.
            // With setting_id => array( 'value' => '...' )
            $customized_data = HA_SKOP_OPT() -> ha_get_unsanitized_customized_values( $skope_id );


            // ha_error_log( '//////////////// START CUSTOMIZED DATA ////////////////// ');
            // ha_error_log( $skope_id );
            // ha_error_log( print_R( HA_SKOP_OPT() -> ha_get_unsanitized_customized_values( $skope_id ) , true ) );
            // ha_error_log( '//////////////// END CUSTOMIZED DATA////////////////// ');

            // /*
            //  * Get list of IDs for settings that have values different from what is currently
            //  * saved in the changeset. By skipping any values that are already the same, the
            //  * subset of changed settings can be passed into validate_setting_values to prevent
            //  * an underprivileged modifying a single setting for which they have the capability
            //  * from being blocked from saving. This also prevents a user from touching of the
            //  * previous saved settings and overriding the associated user_id if they made no change.
            //  */
            // $changed_setting_ids = array();
            // foreach ( $customized_data as $setting_id => $setting_value ) {
            //     if ( ! isset( $setting_value['value'] ) )
            //       continue;

            //     $setting = $wp_customize->get_setting( $setting_id );

            //     if ( $setting && 'theme_mod' === $setting->type ) {
            //         $prefixed_setting_id = $wp_customize->get_stylesheet() . '::' . $setting->id;
            //     } else {
            //         $prefixed_setting_id = $setting_id;
            //     }

            //     $is_value_changed = (
            //         ! isset( $_already_published_data[ $prefixed_setting_id ] )
            //         ||
            //         ! array_key_exists( 'value', $_already_published_data[ $prefixed_setting_id ] )
            //         ||
            //       $_already_published_data[ $prefixed_setting_id ]['value'] !== $setting_value['value']
            //     );
            //     if ( $is_value_changed ) {
            //         $changed_setting_ids[] = $setting_id;
            //     }
            // }//foreach()
            // $customized_data = wp_array_slice_assoc( $customized_data, $changed_setting_ids );


            //AT THIS STAGE, ONLY THE CUSTOMIZED VALUES WITH A DIFFERENT VALUE OF THE ONE CURRENTLY SAVED ARE KEPT In $customized_data
            do_action( 'customize_save_validation_before', $wp_customize );

            ///////////////////////////////////
            /// VALIDATE AND SANITIZE
            $setting_validities = array();

            //setting validation has been implemented in WP 4.6
            //=> check if the feature exists in the user WP version
            if ( method_exists( $wp_customize, 'validate_setting_values' ) ) {
                // Validate settings.
                $setting_validities = $wp_customize -> validate_setting_values( $customized_data, array(
                    'validate_capability' => true,
                    'validate_existence' => true
                ) );

            } else { // WP version < 4.6
                $setting_validities = $this -> _ha_validate_setting_values( $customized_data, array(
                    'validate_capability' => true,
                    'validate_existence' => true
                ) );
            }

            $invalid_setting_count = count( array_filter( $setting_validities, 'is_wp_error' ) );


            /*
             * Short-circuit if there are invalid settings the update is transactional.
             * A changeset update is transactional when a status is supplied in the request.
             */
            if ( $update_transactionally && $invalid_setting_count > 0 ) {
                $response = array(
                  'setting_validities' => $setting_validities,
                  'message' => sprintf( _n( 'There is %s invalid setting.', 'There are %s invalid settings.', $invalid_setting_count ), number_format_i18n( $invalid_setting_count ) ),
                );
                return new WP_Error( 'transaction_fail', '', $response );
            }

            $response = array(
              'setting_validities'  => $setting_validities,
              'skope_meta_key'      => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name'),
              'skope_id'            => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' )
            );

            // Obtain/merge data for changeset.
            $data = $normalized_published_data;
            if ( is_wp_error( $data ) ) {
              $data = array();
            }
            // ha_error_log( '//////////////// START EXISTING DATA ////////////////// ');
            // ha_error_log( print_R( $data , true ) );
            // ha_error_log( '//////////////// END EXISTING DATA////////////////// ');
            ///////////////////////////////////
            /// Ensure that all customized values are included in the changeset data.
            foreach ( $customized_data as $setting_id => $cust_value ) {
              if ( ! isset( $data[ $setting_id ] ) ) {
                $data[ $setting_id ] = array();
              }
              $data[ $setting_id ]['value'] = $cust_value;
            }//foreach()


            ///////////////////////////////////
            /// BUILD DATA TO BE SAVED
            foreach ( $data as $setting_id => $setting_params ) {
                if ( 'skope_infos' == $setting_id )
                  continue;
                $setting = $wp_customize->get_setting( $setting_id );
                if ( ! $setting || ! $setting->check_capabilities() ) {
                    ha_error_log( 'In _publish_skope_changeset_metas, ' . $setting_id . ' is not registered in $wp_customize.' );
                    continue;
                }

                // Skip updating changeset for invalid setting values.
                if ( isset( $setting_validities[ $setting_id ] ) && is_wp_error( $setting_validities[ $setting_id ] ) ) {
                    continue;
                }

                // $changeset_setting_id = $setting_id;

                // if ( null === $setting_params ) {
                //     // Remove setting from changeset entirely.
                //     unset( $data[ $changeset_setting_id ] );
                // } else {
                //     // Merge any additional setting params that have been supplied with the existing params.
                //     if ( ! isset( $data[ $changeset_setting_id ] ) ) {
                //       $data[ $changeset_setting_id ] = array();
                //     }
                //     $data[ $changeset_setting_id ] = array_merge(
                //         $data[ $changeset_setting_id ],
                //         $setting_params,
                //         array(
                //           'type' => $setting->type,
                //           'user_id' => $args['user_id']
                //         )
                //     );
                // }
            }//foreach()

            // ha_error_log('////////////////// DATA BEFORE FILTER FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $data, true) );

            //////////////////////////////////////
            /// PREPARE DATA FOR FRONT END :
            /// 1) Keep only the value
            /// 2) Handle multidim theme_mod type
            $data = ha_prepare_skope_changeset_for_front_end( $data );
            if ( is_wp_error( $data ) || ! is_array( $data ) ) {
              $response['publish_skope_changeset_failure'] = 'skope data not valid';
              return new WP_Error( 'publish_skope_changeset_failure', '', $response );
            }

            //////////////////////////////////////
            /// ADD / UPDATE SKOPE INFOS IF NEEDED
            $data['skope_infos'] = array(
                'skope_id'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id'),
                'level_id'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'level_id'),
                'skope'     => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope'),
                'obj_id'    => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'obj_id'),
                'meta_key'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name')
            );

            // ha_error_log('////////////////// INFOS : ' . $skope_post_id );
            // ha_error_log( $skope_meta_key );

            // ha_error_log('////////////////// DATA AFTER FILTER AND BEFORE BEING SAVED FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $data, true) );

            //////////////////////////////////////
            /// PUBLISH
            $r = update_post_meta( $skope_post_id, $skope_meta_key, $data );

            if ( is_wp_error( $r ) ) {
              $response['changeset_post_save_failure'] = $r->get_error_code();
              return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
            }

            return $response;
        }//_publish_skope_changeset_metas


        //hook : 'wp_ajax_czr_clean_skope_changeset_metas_after_publish'
        function ha_ajax_clean_skope_changeset_metas_after_publish() {
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

            // ha_error_log('////////////////WHAT IS THE CURRENT CHANGESET POST ?');
            // ha_error_log( print_R( $_POST, true ) );
            // ha_error_log( $wp_customize->changeset_post_id() );
            $changeset_post_id = $wp_customize->changeset_post_id();

            if ( ! $changeset_post_id )
              wp_send_json_error( 'no_changeset_post_id_in_ha_ajax_clean_skope_changeset_metas_after_publish' );

            $all_skope_changeset_metas = get_post_meta( $changeset_post_id );
            $all_skope_changeset_metas = is_array( $all_skope_changeset_metas ) ? $all_skope_changeset_metas : array();

            // ha_error_log( print_R( $all_skope_changeset_metas, true ) );

            foreach ( $all_skope_changeset_metas as $meta_key => $val ) {
                $r = delete_post_meta( $changeset_post_id, $meta_key );
                if ( is_wp_error( $r ) ) {
                    wp_send_json_error( $r->get_error_message() );
                    break;
                }
            }
            wp_send_json_success();
        }
    }//class
endif;

?><?php
/* ------------------------------------------------------------------------- *
 *  SAVE SKOPE CHANGESET
 *  => as a meta of the _temp changeset post if status != "publish"
/* ------------------------------------------------------------------------- */
// WHAT ARE THE ARGS ? array(
//    'status' => null or != than publish
//    'data' => $input_changeset_data = $_POST['customize_changeset_data'] == unsaved dirties == api.czr_skopeBase.getSkopeDirties( skope_id )
// )
//
// // 'customize_changeset_data' IS SENT AS A JSON, WHEN DECODED, LOOKS LIKE :
// Array(

//             'nav_menu_locations[footer]' => Array
//                 (
//                     'value' => 11
//                 )

//             'hu_theme_options[site-description]' => Array
//                 (
//                     'value' =>
//                 )

//             'header_image' => Array
//                 (
//                     'value' => http://wp-betas.dev/wp-content/uploads/2016/10/cropped-28efabf02ea881d89514f659c29d27c0.jpg
//                 )
// )

// WHAT SHOULD THIS METHOD DO ?
// 1) Get the existing changeset
// 2) Filter only the modified customized values
// 3) Sanitize and Validate
// 4) make sure theme_mods type are well prefixed nav_menu_locations[topbar] should become hueman::nav_menu_locations[topbar]
if ( ! class_exists( 'HA_Skop_Chset_Save' ) ) :
    class HA_Skop_Chset_Save extends HA_Skop_Chset_Publish {

        function __construct() {
          parent::__construct();
        }

        function _save_skope_changeset_metas( $args = array() ) {
            // ha_error_log( '//////////////// START ARGS PARAM ////////////////// ');
            // ha_error_log( print_R( $args, TRUE ) );
            // ha_error_log( '//////////////// END ARGS PARAM ////////////////// ');
            global $wp_customize;

            if ( 'global' == HA_SKOP_OPT() -> ha_get_current_customized_skope() )
              wp_send_json_error( '_save_or_publish_skope_changeset_metas() : the global skope can not be saved this way' );

            if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              wp_send_json_error( '_save_or_publish_skope_changeset_metas() : Missing opt_name or skope_id or skopeCustomized' );

            $skope_meta_key = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name');
            $skope_id       = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );

            $args = array_merge(
                array(
                  'status' => null,
                  'data' => array(),
                  'user_id' => get_current_user_id()
                ),
                $args
            );

            // We are customizing
            $changeset_post_id = $wp_customize->changeset_post_id();
            $existing_changeset_data = array();

            if ( ! $changeset_post_id )
                wp_send_json_error( 'No changet post id yet' );

            //Default response
            $response = array(
              'setting_validities'  => array()
            );

            $existing_changeset_data = array();
            if ( $changeset_post_id ) {
                $existing_changeset_data = ha_get_skope_db_data( array( 'post_id' => $changeset_post_id, 'skope_meta_key' => $skope_meta_key, 'is_option_post' => false ) );
            }

            if ( is_wp_error( $existing_changeset_data ) ) {
              $response['changeset_post_save_failure'] = $existing_changeset_data->get_error_code();
              return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
            }

            // The request was made via wp.customize.previewer.save().
            $update_transactionally = (bool) $args['status'];
            $allow_revision = (bool) $args['status'];

            // Amend post values with any supplied data.
            // foreach ( $args['data'] as $setting_id => $setting_params ) {
            //     if ( array_key_exists( 'value', $setting_params ) ) {
            //         $wp_customize->set_post_value( $setting_id, $setting_params['value'] ); // Add to post values so that they can be validated and sanitized.
            //     }
            // }

            // The customized data are already structured like in the changeset.
            // With setting_id => array( 'value' => '...' )
            $customized_data = $args['data'];

            /*
             * Get list of IDs for settings that have values different from what is currently
             * saved in the changeset. By skipping any values that are already the same, the
             * subset of changed settings can be passed into validate_setting_values to prevent
             * an underprivileged modifying a single setting for which they have the capability
             * from being blocked from saving. This also prevents a user from touching of the
             * previous saved settings and overriding the associated user_id if they made no change.
             */
            $changed_setting_ids = array();
            foreach ( $customized_data as $setting_id => $setting_value ) {
                if ( ! isset( $setting_value['value'] ) )
                  continue;

                $setting = $wp_customize->get_setting( $setting_id );

                if ( $setting && 'theme_mod' === $setting->type ) {
                    $prefixed_setting_id = $wp_customize->get_stylesheet() . '::' . $setting->id;
                } else {
                    $prefixed_setting_id = $setting_id;
                }
                $is_value_changed = (
                    ! isset( $existing_changeset_data[ $prefixed_setting_id ] )
                    ||
                    ! array_key_exists( 'value', $existing_changeset_data[ $prefixed_setting_id ] )
                    ||
                  $existing_changeset_data[ $prefixed_setting_id ]['value'] !== $setting_value['value']
                );
                if ( $is_value_changed ) {
                    $changed_setting_ids[] = $setting_id;
                }
            }//foreach()

            $customized_data = wp_array_slice_assoc( $customized_data, $changed_setting_ids );

            //Bail here if unchanged values.
            //Will typically happen on load + skope switch
            if ( empty( $customized_data) )
              return wp_send_json_success( $response );

            //AT THIS STAGE, ONLY THE CUSTOMIZED VALUES WITH A DIFFERENT VALUE OF THE ONE CURRENTLY SAVED ARE KEPT In $customized_data

            /**
             * Fires before save validation happens.
             *
             * Plugins can add just-in-time {@see 'customize_validate_{$wp_customize->ID}'} filters
             * at this point to catch any settings registered after `customize_register`.
             * The dynamic portion of the hook name, `$wp_customize->ID` refers to the setting ID.
             *
             * @since 4.6.0
             *
             * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
             */
            do_action( 'customize_save_validation_before', $wp_customize );


            ///////////////////////////////////
            /// VALIDATE AND SANITIZE
            $setting_validities = array();
            //build a validation ready customized values ( => get rid of 'value' => value )
            $customized_values = array();
            foreach ($customized_data as $__id => $__v ) {
                if ( ! array_key_exists('value', $__v ) )
                  continue;
                $customized_values[$__id] = $__v['value'];
            }

            //setting validation has been implemented in WP 4.6
            //=> check if the feature exists in the user WP version
            if ( method_exists( $wp_customize, 'validate_setting_values' ) ) {
                // Validate settings.
                $setting_validities = $wp_customize -> validate_setting_values( $customized_values, array(
                    'validate_capability' => true,
                    'validate_existence' => true
                ) );

            } else { // WP version < 4.6
                $setting_validities = $this -> _ha_validate_setting_values( $customized_values, array(
                    'validate_capability' => true,
                    'validate_existence' => true
                ) );
            }

            $invalid_setting_count = count( array_filter( $setting_validities, 'is_wp_error' ) );
            /*
             * Short-circuit if there are invalid settings the update is transactional.
             * A changeset update is transactional when a status is supplied in the request.
             */
            if ( $update_transactionally && $invalid_setting_count > 0 ) {
                $response = array(
                  'setting_validities' => $setting_validities,
                  'message' => sprintf( _n( 'There is %s invalid setting.', 'There are %s invalid settings.', $invalid_setting_count ), number_format_i18n( $invalid_setting_count ) ),
                );
                return new WP_Error( 'transaction_fail', '', $response );
            }

            $response = array(
              'setting_validities'  => $setting_validities,
              'skope_meta_key'      => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name'),
              'skope_id'            => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' )
            );

            // Obtain/merge data for changeset.
            $data = $existing_changeset_data;
            if ( is_wp_error( $data ) ) {
              $data = array();
            }



            ///////////////////////////////////
            /// Ensure that all customized values are included in the changeset data.
            // foreach ( $customized_data as $setting_id => $cust_value ) {
            //   if ( ! isset( $args['data'][ $setting_id ] ) ) {
            //     $args['data'][ $setting_id ] = array();
            //   }
            //   if ( ! isset( $args['data'][ $setting_id ]['value'] ) ) {
            //     $args['data'][ $setting_id ]['value'] = $cust_value['value'];
            //   }
            // }//foreach()



            ///////////////////////////////////
            /// BUILD DATA TO BE SAVED
            foreach ( $customized_data as $setting_id => $setting_params ) {
                $setting = $wp_customize->get_setting( $setting_id );
                if ( ! $setting || ! $setting->check_capabilities() ) {
                    ha_error_log( 'In _save_or_publish_skope_changeset_metas, ' . $setting_id . ' is not registered in $wp_customize.' );
                    continue;
                }

                // Skip updating changeset for invalid setting values.
                if ( isset( $setting_validities[ $setting_id ] ) && is_wp_error( $setting_validities[ $setting_id ] ) ) {
                    continue;
                }

                // Add the theme name as prefix for theme_mod type settings
                $changeset_setting_id = $setting_id;
                if ( 'theme_mod' === $setting->type ) {
                    $theme_name = $wp_customize->get_stylesheet();
                    if ( $theme_name != substr( $setting_id, 0, strlen($theme_name) ) )
                      $changeset_setting_id = sprintf( '%s::%s', $wp_customize->get_stylesheet(), $setting_id );
                }

                if ( null === $setting_params ) {
                    // Remove setting from changeset entirely.
                    unset( $data[ $changeset_setting_id ] );
                } else {
                    // Merge any additional setting params that have been supplied with the existing params.
                    if ( ! isset( $data[ $changeset_setting_id ] ) ) {
                      $data[ $changeset_setting_id ] = array();
                    }
                    $data[ $changeset_setting_id ] = array_merge(
                        $data[ $changeset_setting_id ],
                        $setting_params,
                        array(
                          'type' => $setting->type,
                          'user_id' => $args['user_id']
                        )
                    );
                }
            }//foreach()

            // ha_error_log('////////////////// DATA BEING SAVED FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $data, true) );

            //////////////////////////////////////
            /// SAVE
            $r = update_post_meta( $changeset_post_id, $skope_meta_key, $data );

            if ( is_wp_error( $r ) ) {
              $response['changeset_post_save_failure'] = $r->get_error_code();
              return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
            }

            return $response;
        }
    }//class
endif;

?><?php

if ( ! class_exists( 'HA_Skop_Chset_Reset' ) ) :
    final class HA_Skop_Chset_Reset extends HA_Skop_Chset_Save {

        function __construct() {
            parent::__construct();
            //DEPRECATED
            //add_action( 'wp_ajax_' . HU_OPT_AJAX_ACTION , array( $this, 'ha_ajax_get_opt' ) );

            //add_action( 'wp_ajax_czr_skope_reset', array( $this, 'ha_ajax_reset_skope' ) );
            //Changeset
            add_action( 'wp_ajax_czr_changeset_setting_reset',  array( $this, 'ha_ajax_reset_changeset_setting' ) );
            add_action( 'wp_ajax_czr_changeset_skope_reset',    array( $this, 'ha_ajax_reset_changeset_skope' ) );

            //Published
            add_action( 'wp_ajax_czr_published_setting_reset',  array( $this, 'ha_ajax_reset_published_setting' ) );
            add_action( 'wp_ajax_czr_published_skope_reset',    array( $this, 'ha_ajax_reset_published_skope' ) );
        }



        /* ------------------------------------------------------------------------- *
         *  GET OPTION
        /* ------------------------------------------------------------------------- */
        /**
         * Ajax handler for getting an attachment.
         *
         * @since 3.5.0
         */
        function ha_ajax_get_opt() {
          if ( ! isset( $_REQUEST['opt_name'] ) || ! isset( $_REQUEST['dyn_type'] ) || ! isset( $_REQUEST['stylesheet'] ) )
            wp_send_json_error();
          if ( ! current_user_can( 'edit_theme_options' ) )
            wp_send_json_error();

          $_trans = get_transient( $_REQUEST['opt_name'] );
          wp_send_json_success( $_trans );
        }





        function ha_get_setting_dependants( $setting_id ) {
          $dependencies = array(
              'header_image' => 'header_image_data',
              'header_image_data' => 'header_image'
          );

          return array_key_exists( $setting_id, $dependencies ) ? $dependencies[$setting_id] : false;
        }







        /* ------------------------------------------------------------------------- *
         *  RESET SETTING CHANGESET
        /* ------------------------------------------------------------------------- */
        //hook : wp_ajax_czr_changeset_setting_reset
        function ha_ajax_reset_changeset_setting() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            // We are customizing
            $changeset_post_id = $wp_customize->changeset_post_id();

            if ( ! $changeset_post_id ) {
              wp_send_json_success( 'No changet post id' );
              return;
            }

            // Do we have to switch themes?
            if ( ! $wp_customize -> is_theme_active() ) {
                // Temporarily stop previewing the theme to allow switch_themes()
                // to operate properly.
                $wp_customize -> stop_previewing_theme();
                switch_theme( $wp_customize -> get_stylesheet() );
                update_option( 'theme_switched_via_customizer', true );
                $wp_customize -> start_previewing_theme();
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset an option, the ajax post request is missing a param.');

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );
            $setting_id       = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'setting_id' );
            $dependant_setting_id = $this -> ha_get_setting_dependants( $setting_id );
            $changeset_values = $this -> get_unsanitized_skope_changeset( $skope_id );
            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $attempt          = new WP_Error('setting_changeset_reset_not_processed');

            foreach ( $changeset_values as $_id => $setting_params ) {
                if ( $setting_id != $_id && $dependant_setting_id != $_id )
                  $new_values[$_id] = $setting_params;
            }

            if ( 'global' == $skope_level ) {
                // ha_error_log( '////////////// CHANGESET VALUES BEFORE' );
                // ha_error_log( print_R( $changeset_values, true ) );
                $json_options = 0;
                if ( defined( 'JSON_UNESCAPED_SLASHES' ) ) {
                  $json_options |= JSON_UNESCAPED_SLASHES; // Introduced in PHP 5.4. This is only to improve readability as slashes needn't be escaped in storage.
                }
                $json_options |= JSON_PRETTY_PRINT; // Also introduced in PHP 5.4, but WP defines constant for back compat. See WP Trac #30139.
                $post_array = array(
                  'post_content' => wp_json_encode( $new_values, $json_options ),
                  'ID'           => $changeset_post_id
                );
                $attempt = wp_update_post( wp_slash( $post_array ), true );
            } else {
                if ( empty($new_values) ) {
                    $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
                } else {
                    $attempt = update_post_meta( $changeset_post_id, $skope_meta_key, $new_values );
                }
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| ' . $setting_id . ' has been deleted from changeset in skope ' . $skope_id . '|||' );
        }










        /* ------------------------------------------------------------------------- *
         *  RESET SKOPE CHANGESET
        /* ------------------------------------------------------------------------- */
        //hook : wp_ajax_czr_changeset_skope_reset
        function ha_ajax_reset_changeset_skope() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            // We are customizing
            $changeset_post_id = $wp_customize->changeset_post_id();

            if ( ! $changeset_post_id ) {
              wp_send_json_success( 'No changet post id' );
              return;
            }

            // Do we have to switch themes?
            if ( ! $wp_customize -> is_theme_active() ) {
                // Temporarily stop previewing the theme to allow switch_themes()
                // to operate properly.
                $wp_customize -> stop_previewing_theme();
                switch_theme( $wp_customize -> get_stylesheet() );
                update_option( 'theme_switched_via_customizer', true );
                $wp_customize -> start_previewing_theme();
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset a skope changeset, the ajax post request is missing a param.');

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );
            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $attempt          = new WP_Error('skope_changeset_reset_not_processed');

            if ( 'global' == $skope_level ) {
                $json_options = 0;
                if ( defined( 'JSON_UNESCAPED_SLASHES' ) ) {
                  $json_options |= JSON_UNESCAPED_SLASHES; // Introduced in PHP 5.4. This is only to improve readability as slashes needn't be escaped in storage.
                }
                $json_options |= JSON_PRETTY_PRINT; // Also introduced in PHP 5.4, but WP defines constant for back compat. See WP Trac #30139.
                $post_array = array(
                  'post_content' => wp_json_encode( $new_values, $json_options ),
                  'ID'           => $changeset_post_id
                );
                $attempt = wp_update_post( wp_slash( $post_array ), true );
            } else {
                $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| The changeset has been deleted for skope ' . $skope_id . '|||' );
        }











        /* ------------------------------------------------------------------------- *
         *  RESET SETTING PUBLISHED
        /* ------------------------------------------------------------------------- */
        //hook : wp_ajax_czr_changeset_setting_reset
        function ha_ajax_reset_published_setting() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset an option, the ajax post request is missing a param.');

            $setting_id       = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'setting_id' );

            //Attempt to extract the theme option prefix
            $short_opt_name             = _ha_extract_setting_name($setting_id);
            $dependant_setting_id       = $this -> ha_get_setting_dependants( $short_opt_name );

            //Bail if the option is protected
            if ( ha_is_option_protected( $short_opt_name ) )
              return wp_send_json_error('This option is protected');

            $theme_setting_list = HU_utils::$_theme_setting_list;
            $is_theme_authorized_option = in_array( $short_opt_name, $theme_setting_list );

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );

            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $published_values = array();

            if ( 'global' == $skope_level ) {
                //Bail if the option is not a theme setting
                if ( ! $is_theme_authorized_option )
                  return wp_send_json_error('This WordPress option can not be removed at a site wide level');//@to_translate
                $setting_id = $short_opt_name;
                $published_values = hu_get_raw_option( null, HU_THEME_OPTIONS );
            } else {
                //We are resetting the published values
                $changeset_post_id  = get_option('skope-post-id');
                if ( false === $changeset_post_id || empty( $changeset_post_id ) ) {
                    wp_send_json_error( 'missing skope_post_id when attempting to reset the skope meta value' );
                }
                $published_values = ha_get_skope_db_data(
                  array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    'level' => $skope_level,
                    'is_option_post'  => true
                  )
                );
            }

            $attempt          = new WP_Error('setting_changeset_reset_not_processed');

            // ha_error_log( '////////////// $setting_id' );
            // ha_error_log( $setting_id );

            // ha_error_log( '////////////// PUBLISHED VALUES BEFORE' );
            // ha_error_log( print_R( $published_values, true ) );

            foreach ( $published_values as $_id => $setting_params ) {

                if ( ! _ha_is_wp_setting_multidimensional( $_id) ) {
                    if ( $setting_id != $_id && $dependant_setting_id != $_id )
                      $new_values[$_id] = $setting_params;
                } else {
                    $published_multidim_settings = _ha_build_multidimensional_db_option( $_id, $setting_params );
                    if ( is_array( $published_multidim_settings ) ) {
                        $new_values[$_id] = array();
                        foreach ( $published_multidim_settings as $mult_set_id => $mult_val ) {
                            if ( $setting_id != $mult_set_id ) {
                                //extract multidim key
                                $multi_dim_key = str_replace(array('[', ']', $_id ), '', $mult_set_id);
                                $new_values[$_id][$multi_dim_key] = $mult_val;
                            }
                        }
                    }
                    // ha_error_log( '////////////// MULTIDIM' );
                    // ha_error_log( print_R( $published_multidim_settings, true ) );
                    // ha_error_log( '////////////// NEW VALUES' );
                    // ha_error_log( print_R( $new_values, true ) );
                }
            }

            if ( 'global' == $skope_level ) {
                // ha_error_log( '////////////// PUBLISHED VALUES BEFORE' );
                // ha_error_log( print_R( $published_values, true ) );
                $attempt = update_option( HU_THEME_OPTIONS, $new_values );
            } else {
                if ( empty( $new_values ) || ( 1 == count( $new_values ) && array_key_exists( 'skope_infos', $new_values ) ) ) {
                    $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
                } else {
                    $attempt = update_post_meta( $changeset_post_id, $skope_meta_key, $new_values );
                }
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| ' . $setting_id . ' has been deleted from changeset in skope ' . $skope_id . '|||' );
        }







        /* ------------------------------------------------------------------------- *
         *  RESET SKOPE PUBLISHED
        /* ------------------------------------------------------------------------- */
        function ha_ajax_reset_published_skope() {
            global $wp_customize;
            if ( ! $wp_customize->is_preview() ) {
                wp_send_json_error( 'not_preview' );
            } else if ( ! current_user_can( 'customize' ) ) {
                status_header( 403 );
                wp_send_json_error( 'customize_not_allowed' );
            } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
              status_header( 405 );
              wp_send_json_error( 'bad_method' );
            }
            $nonce_action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }

            if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
              return wp_send_json_error('Error when trying to reset a skope changeset, the ajax post request is missing a param.');

            $new_values       = array();
            $skope_id         = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
            $skope_level      = HA_SKOP_OPT() -> ha_get_skope_level( $skope_id );
            $skope_meta_key   = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            $attempt          = new WP_Error('skope_changeset_reset_not_processed');

            if ( 'global' == $skope_level ) {
                $published_values = hu_get_raw_option( null, HU_THEME_OPTIONS );
                $theme_setting_list = HU_utils::$_theme_setting_list;

                foreach ( $published_values as $_id => $setting_params ) {
                    //Unauthorized theme options are never reset
                    if ( ! in_array( $_id, $theme_setting_list ) ) {
                      $new_values[$_id] = $setting_params;
                    }
                    //Protected options are never reset
                    if ( ha_is_option_protected( $_id ) ) {
                      $new_values[$_id] = $setting_params;
                    }
                }
                // ha_error_log( '////////////// PUBLISHED VALUES BEFORE' );
                // ha_error_log( print_R( $published_values, true ) );
                // ha_error_log( '////////////// NEW VALUES' );
                // ha_error_log( print_R( $new_values, true ) );
                $attempt = update_option( HU_THEME_OPTIONS, $new_values );
            } else {
                //We are resetting the published values
                $changeset_post_id  = get_option('skope-post-id');
                if ( false === $changeset_post_id || empty( $changeset_post_id ) ) {
                    wp_send_json_error( 'missing skope_post_id when attempting to reset the skope meta value' );
                }
                //ha_error_log( '////////////// RESETTING PUBLISHED VALUES FOR SKOPE : ' . $skope_id );
                $attempt = delete_post_meta( $changeset_post_id, $skope_meta_key );
            }

            if ( is_wp_error( $attempt ) ) {
              status_header( 500 );
              wp_send_json_error( $attempt->get_error_message() );
            }
            wp_send_json_success( '||| The published values have been deleted for skope ' . $skope_id . '|||' );
        }








        /* ------------------------------------------------------------------------- *
        * HELPERS
        /* ------------------------------------------------------------------------- */
        function get_unsanitized_skope_changeset( $skope_id ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();

            global $wp_customize;
            $values = array();

            //GET THE RAW CHANGESET FOR THE PROVIDED SKOPE_ID
            $skope_meta_key    = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            if ( false == $skope_meta_key ) {
              ha_error_log( 'no meta key found in get_unsanitized_skope_changeset() for skope : ' . $skope_id );
              return array();
            }
            $changeset_post_id = $wp_customize -> changeset_post_id();
            $changeset_data = ha_get_skope_db_data(
                array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    //the level must be specify when we're getting the 'global' level
                    'level' => HA_SKOP_OPT() -> ha_get_skope_level( $skope_id )
                )
            );

            if ( is_wp_error( $changeset_data ) ) {
                ha_error_log( 'Error when trying to get the changeset data in get_unsanitized_skope_changeset() for skope : ' . $skope_id );
                $changeset_data = array();
            }

            // Note that blogname can be saved in the changeset with not value, needed to refresh the global skope on init @see requestChangesetUpdate js
            foreach ( $changeset_data as $raw_setting_id => $setting_data ) {
                if ( ! is_array( $setting_data ) || ( 'blogname' != $raw_setting_id && ! array_key_exists( 'value', $setting_data ) ) ) {
                  ha_error_log( 'Problem in get_unsanitized_skope_changeset, the setting_data of the changeset are not well formed for skope : ' . $skope_id );
                  continue;
                }

                $setting_id = $raw_setting_id;
                // If theme_mod type, get rid of the theme name prefix
                if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {
                    $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                    if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                      $setting_id = $matches['setting_id'];
                    }
                }
                $values[$setting_id] = $setting_data;
            }

            // ha_error_log( '/////////////// CHANGESET BEFORE ');
            // ha_error_log( print_R( $changeset_data, true ) );
            // ha_error_log( '/////////////// CHANGESET AFTER ');
            // ha_error_log( print_R( ha_prepare_skope_changeset_for_front_end( $changeset_data ), true ) );
            return $values;
        }


        /* ------------------------------------------------------------------------- *
         *  RESET SKOPE : _DEPRECATED
        /* ------------------------------------------------------------------------- */
        // function ha_ajax_reset_skope() {
        //     global $wp_customize;
        //     if ( ! $wp_customize->is_preview() ) {
        //         wp_send_json_error( 'not_preview' );
        //     } else if ( ! current_user_can( 'customize' ) ) {
        //         status_header( 403 );
        //         wp_send_json_error( 'customize_not_allowed' );
        //     } else if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        //       status_header( 405 );
        //       wp_send_json_error( 'bad_method' );
        //     }
        //     $action = 'save-customize_' . $wp_customize->get_stylesheet();
        //     if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
        //         wp_send_json_error( 'invalid_nonce' );
        //     }
        //     // Do we have to switch themes?
        //     if ( ! $wp_customize->is_theme_active() ) {
        //         // Temporarily stop previewing the theme to allow switch_themes()
        //         // to operate properly.
        //         $wp_customize->stop_previewing_theme();
        //         switch_theme( $wp_customize->get_stylesheet() );
        //         update_option( 'theme_switched_via_customizer', true );
        //         $wp_customize->start_previewing_theme();
        //     }

        //     if ( ! isset( $_POST['opt_name'] )  || ! isset( $_POST['dyn_type'] ) )
        //       return wp_send_json_error();

        //     //$attempt will store the maybe wp error status
        //     $attempt = '';
        //     switch ( $_POST['dyn_type'] ) {
        //         case 'trans':
        //             $attempt = delete_transient( $_POST['opt_name'] );
        //           break;
        //         case 'post_meta':
        //             if ( ! isset( $_POST['obj_id'] ) ) {
        //               wp_send_json_error( 'Missing $_POST["obj_id"] when attempting to delete a post meta');
        //             }
        //             $attempt = delete_post_meta( $_POST['obj_id'] , $_POST['opt_name'] );
        //           break;
        //         case 'term_meta':
        //             if ( ! isset( $_POST['obj_id'] ) ) {
        //               wp_send_json_error( 'Missing $_POST["obj_id"] when attempting to delete a term meta');
        //             }
        //             $attempt = delete_term_meta( $_POST['obj_id'] , $_POST['opt_name'] );
        //           break;
        //         case 'user_meta':
        //             if ( ! isset( $_POST['obj_id'] ) ) {
        //               wp_send_json_error( 'Missing $_POST["obj_id"] when attempting to delete a user meta');
        //             }
        //             $attempt = delete_user_meta( $_POST['obj_id'] , $_POST['opt_name'] );
        //           break;
        //         case 'option' :
        //             $options = get_option( $_POST['opt_name'] );
        //             $_opts_to_keep = array();
        //             foreach ( $options as $key => $value ) {
        //                 if ( ha_is_option_protected( $key ) )
        //                   $_opts_to_keep[$key] = $value;
        //             }
        //             //wp_send_json_success( 'PROTECTED OPTIONS : ' . json_encode( ha_get_protected_options() ) );
        //             $attempt = update_option( $_POST['opt_name'], $_opts_to_keep );
        //           break;
        //     }
        //     if ( is_wp_error( $attempt ) ) {
        //       status_header( 500 );
        //       wp_send_json_error( $attempt->get_error_message() );
        //     }
        //     wp_send_json_success( $_POST['opt_name'] . ' has been reset.');
        // }

    }//class
endif;

?><?php

if ( ! class_exists( 'HA_Skop_Cust_Prev' ) ) :
    final class HA_Skop_Cust_Prev {
        function __construct() {
            /* ------------------------------------------------------------------------- *
             *  CUSTOMIZE PREVIEW : export skope data
            /* ------------------------------------------------------------------------- */
            add_action( 'wp_footer', array( $this, 'ha_print_server_skope_data' ), 30 );
        }



        //hook : 'wp_footer'
        function ha_print_server_skope_data() {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return;

            global $wp_query, $wp_customize;
            $_meta_type = ha_get_skope( 'meta_type', true );

            // $_czr_scopes = array(
            //   'local' => array(
            //     'level'         => ha_get_skope(),
            //     'dyn_type'    => ha_get_skope_dyn_type( $_meta_type ),
            //     'opt_name'    => HA_SKOP_OPT() -> ha_get_skope_opt_name(),
            //     'is_default'  => false,
            //     'is_winner'   => true
            //   ),
            //   'global' => array(
            //     'ctx'         => '_all_',
            //     'dyn_type'    => 'option',
            //     'opt_name'    => HU_THEME_OPTIONS,
            //     'is_default'  => true,
            //     'is_winner'   => false
            //   )
            // );
            $_czr_skopes            = $this -> _ha_get_json_export_ready_skopes();
            $_is_changeset_dirty    = $this -> _ha_is_changeset_dirty();
            ?>
              <script type="text/javascript" id="czr-print-skop">
                (function ( _export ){
                      _export.czr_skopes        = <?php echo wp_json_encode( $_czr_skopes ); ?>;
                      _export.isChangesetDirty  = <?php echo wp_json_encode( $_is_changeset_dirty ); ?>
                })( _wpCustomizeSettings );
              </script>
            <?php
        }




        //@return a boolean stating if there's any value saved in the various changeset
        //Note : for the 'global' changeset the blogname can be used to init the wp core changeset with dummy_change,
        // => therefore we need to check if it is part of the dirty AND that it has a value item set.
        function _ha_is_changeset_dirty() {
            if ( ! HU_AD() -> ha_is_changeset_enabled() )
              return false;

            global $wp_customize;

            $skope_post_id = $wp_customize->changeset_post_id();
            if ( false == $skope_post_id || empty( $skope_post_id ) )
              return;

            $_level_list = array( 'global', 'group', 'special_group', 'local' );
            $is_dirty = false;
            foreach ( $_level_list as $level ) {
              $_changeset_data = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => null, 'level' => $level ) );
              if ( 'global' == $level ) {
                if ( count($_changeset_data) > 1 )
                  $is_dirty = true;
                else {
                  if ( isset( $_changeset_data['blogname'] ) && isset( $_changeset_data['blogname']['value'] ) )
                    $is_dirty = true;
                }
              } else {
                $is_dirty = is_array( $_changeset_data ) && ! empty( $_changeset_data );
              }
              if ( $is_dirty )
                break;
            }

            return $is_dirty;
        }



        /* ------------------------------------------------------------------------- *
         *  CUSTOMIZE PREVIEW : BUILD SKOPES JSON
        /* ------------------------------------------------------------------------- */
        //generates the array of available scopes for a given context
        //ex for a single post tagged #tag1 and #tag2 and categroized #cat1 :
        //global
        //all posts
        //local
        //posts tagged #tag1
        //posts tagged #tag2
        //posts categorized #cat1
        //@return array()
        //
        //ha_get_skope_title() takes the following default args
        //array(
        //  'level'       =>  '',
        //  'meta_type'   => null,
        //  'long'        => false,
        //  'is_prefixed' => true
        //)
        function _ha_get_json_export_ready_skopes() {
            $skopes = array();
            $_meta_type = ha_get_skope( 'meta_type', true );

            //default properties of the scope object
            $defaults = _ha_get_default_scope_model();

            $saved_glob_opt = $this -> _ha_get_sanitized_skoped_saved_global_options();

            //global and local and always sent
            $skopes[] = wp_parse_args(
                array(
                    'title'       => ha_get_skope_title( array( 'level' => 'global' ) ),
                    'long_title'  => ha_get_skope_title( array( 'level' => 'global', 'meta_type' => null, 'long' => true ) ),
                    'ctx_title'   => ha_get_skope_title( array( 'level' => 'global', 'meta_type' => null, 'long' => true, 'is_prefixed' => false ) ),
                    'skope'       => 'global',
                    'level'       => '_all_',
                    'dyn_type'    => 'option',
                    'opt_name'    => HU_THEME_OPTIONS,
                    'is_winner'   => false,
                    'is_primary'  => true,
                    'has_db_val'  => ! empty( $saved_glob_opt ),
                    'db'          => $saved_glob_opt,
                    'changeset'   => $this -> _ha_get_api_ready_skope_changeset( array( 'level' => 'global', 'skope_meta_key' => '' ) )
                ),
                $defaults
            );


            //SPECIAL GROUPS
            //@todo


            //GROUP
            //Do we have a group ? => if yes, then there must be a meta type
            if ( ha_get_skope('meta_type') ) {
              $group_opt_name = HA_SKOP_OPT() -> ha_get_skope_opt_name( 'group' );
              $group_opts = HA_SKOP_OPT() -> ha_get_skope_opt( 'group', $group_opt_name );
              $skopes[] = wp_parse_args(
                array(
                  'title'       => ha_get_skope_title( array( 'level' => 'group', 'meta_type' => $_meta_type  ) ),
                  'long_title'  => ha_get_skope_title( array( 'level' => 'group', 'meta_type' => $_meta_type, 'long' => true ) ),
                  'ctx_title'   => ha_get_skope_title( array( 'level' => 'group', 'meta_type' => $_meta_type, 'long' => true, 'is_prefixed' => false ) ),
                  'skope'       => 'group',
                  'level'       => 'all_' . ha_get_skope('type'),
                  'dyn_type'    => 'skope_meta',
                  'opt_name'    => $group_opt_name,
                  'db'          => $this -> _ha_get_api_ready_skope_db_option( array( 'level' => 'group', 'skope_meta_key' => $group_opt_name ) ),
                  'changeset'   => $this -> _ha_get_api_ready_skope_changeset( array( 'level' => 'group', 'skope_meta_key' => $group_opt_name ) ),
                  'has_db_val'  => ! empty( $group_opts )
                ),
                $defaults
              );
          }


          //LOCAL
          $loc_opt_name   = HA_SKOP_OPT() -> ha_get_skope_opt_name( 'local');
          $local_opts = HA_SKOP_OPT() -> ha_get_skope_opt( 'local', $loc_opt_name );
          $skopes[] = wp_parse_args(
            array(
                'title'       => ha_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type ) ),
                'long_title'  => ha_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type, 'long' => true ) ),
                'ctx_title'   => ha_get_skope_title( array( 'level' => 'local', 'meta_type' => $_meta_type, 'long' => true, 'is_prefixed' => false ) ),
                'skope'       => 'local',
                'level'       => ha_get_skope(),
                'dyn_type'    => 'skope_meta',
                'opt_name'    => $loc_opt_name,
                'obj_id'      => ha_get_skope('id'),
                'db'          => $this -> _ha_get_api_ready_skope_db_option( array( 'level' =>'local', 'skope_meta_key' => $loc_opt_name ) ),
                'changeset'   => $this -> _ha_get_api_ready_skope_changeset( array( 'level' => 'local', 'skope_meta_key' => $loc_opt_name ) ),
                'is_winner'   => true,
                'has_db_val'  => ! empty( $local_opts )
            ),
            $defaults
          );
          return $skopes;
        }












        /* ------------------------------------------------------------------------- *
         *  GET CHANGESETS
        /* ------------------------------------------------------------------------- */
        //The purpose of this function is to return the list of db saved skope values
        //It can get both the skope meta value already saved
        //AND the changeset value
        //
        //skope options are stored with their full name. Ex : hu_theme_options[copyright]]
        //
        //for multidimensional settings like sidebars_widgets* or widget_*, the setting name has to be preprocessed
        //so that it looks like in the customizer api.
        //Ex widget_archives = array( [3] => array( ... )  ) should become widget_archives[3] = array(...)
        //
        //The value of each setting gets sanitized with the WP filter customize_sanitize_js_{setting_id}
        //
        //@param level string. 'local' for ex.
        //@param db_opt_name string. name of option in db
        function _ha_get_api_ready_skope_changeset( $args ) {
            if ( ! HU_AD() -> ha_is_changeset_enabled() )
              return array();

            $defaults = array(
                'level' => '',
                'skope_meta_key' => ''
            );
            $args = wp_parse_args( $args, $defaults );

            $level          = $args['level'];
            $skope_meta_key = $args['skope_meta_key'];

            global $wp_customize;
            $skope_changeset_val = array();

            //Where are we getting the saved options from ?
            // => from the current changeset
            //   - post content for global options
            //   - post metas for not global skope options

            $skope_post_id = $wp_customize->changeset_post_id();
            if ( false != $skope_post_id && ! empty( $skope_post_id ) ) {
              $skope_changeset_val = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key, 'level' => $level ) );
            } else {
              ha_error_log('NO CHANGESET POST AVAILABLE in _ha_get_api_ready_skope_changeset when getting changset for skope : ' . $level );
              return array();
            }

            $skope_changeset_val = ! is_array( $skope_changeset_val ) ? array() : $skope_changeset_val;
            $theme_setting_list = HU_utils::$_theme_setting_list;
            $theme_option_group = HU_THEME_OPTIONS;

            $api_ready_changeset_val = array();

            // For the changeset, each setting_id has setting_params looking like :
            //array(
            //  value => 'copyright Global',
            //  type => 'option'
            //  user_id => 1
            //)
            foreach ( $skope_changeset_val as $raw_setting_id => $setting_params ) {
                // ha_error_log( '////// RAW SETTING ID' );
                // ha_error_log( $raw_setting_id );
                //if the changeset is requested, then $data must look like :
                //array(
                //  value => 'copyright Global',
                //  type => 'option'
                //  user_id => 1
                //)
                //Bail if $setting_params are not well formed
                if ( ! is_array( $setting_params ) || ! array_key_exists('value', $setting_params ) )
                  continue;

                //ISOLATE THE VALUE
                //Changeset data are typically stored as an array including a value key.
                //we only need to send the value the api panel
                // hu_theme_options[copyright] = array(
                //   type =>  "option"
                //   value => "copyright HOME NEW"
                // )
                $setting_value = $setting_params['value'];

                //FORMAT SETTING ID TO BE COMPLIANT WITH API
                // => theme mods in changeset look like : hueman::nav_menu_locations[header]
                // => should be turned into : nav_menu_locations[header]

                //Store the setting type
                $setting_type = isset( $setting_params['type'] ) ? $setting_params['type'] : 'option';

                //Get the actual setting id
                //handles theme_mods type looking like : hueman::custom_logo
                $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                $is_theme_mod_setting = (
                    isset( $setting_params['value'] )
                    &&
                    isset( $setting_type )
                    &&
                    'theme_mod' === $setting_type
                    &&
                    preg_match( $namespace_pattern, $raw_setting_id, $matches )
                );
                // ha_error_log( '/// IS THEME MODE SETTING');
                // ha_error_log( $is_theme_mod_setting );
                if ( $is_theme_mod_setting ) {
                    if ( $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                      $actual_setting_id = $matches['setting_id'];
                    }
                } else {
                    $actual_setting_id = $raw_setting_id;
                }


                //SANITIZE
                if ( is_object( $wp_customize -> get_setting( $actual_setting_id ) ) ) {
                  $setting_value = apply_filters( "customize_sanitize_js_{$actual_setting_id}", $setting_value, $wp_customize -> get_setting( $actual_setting_id ) );
                }

                //handles theme settings saved with their short names
                //skope options are stored with their full name. Ex : hu_theme_options[copyright]]
                if ( in_array( $actual_setting_id, $theme_setting_list ) ) {
                    $czr_opt_id = "{$theme_option_group}[{$actual_setting_id}]";
                    if ( is_object( $wp_customize -> get_setting( $czr_opt_id ) ) ) {
                      $setting_value = apply_filters( "customize_sanitize_js_{$czr_opt_id}", $setting_value, $wp_customize -> get_setting( $czr_opt_id ) );
                    }
                }


                //Writes
                $api_ready_changeset_val[$actual_setting_id] = $setting_value;

            }

            return $api_ready_changeset_val;
        }











        /* ------------------------------------------------------------------------- *
         *  GET OPTIONS SAVED IN DB
        /* ------------------------------------------------------------------------- */
        //@param level string. 'local' for ex.
        //@param db_opt_name string. name of option in db
        function _ha_get_api_ready_skope_db_option( $args ) {
            $defaults = array(
                'level' => '',
                'skope_meta_key' => ''
            );
            $args = wp_parse_args( $args, $defaults );

            $level          = $args['level'];
            $skope_meta_key = $args['skope_meta_key'];

            global $wp_customize;
            $skope_db_val       = HA_SKOP_OPT() -> ha_get_skope_opt( $level, $skope_meta_key );
            $skope_db_val       = ! is_array( $skope_db_val ) ? array() : $skope_db_val;
            $theme_setting_list = HU_utils::$_theme_setting_list;
            $theme_option_group = HU_THEME_OPTIONS;
            $api_ready_db_val   = array();


            foreach ( $skope_db_val as $actual_setting_id  => $setting_value ) {
                if ( 'skope_infos' == $actual_setting_id )
                  continue;
                //is multidimensional ?
                //the theme settings are not.
                //some built-in settings like blogdescription, custom_logo are not either
                //for now only sidebars_widgets* and nav_menu_locations*
                if ( ! _ha_is_wp_setting_multidimensional( $actual_setting_id ) ) {

                    //handles wp built-in not dimensional settings
                    //AND theme settings saved with their full name. Ex : hu_theme_options[copyright]]
                    if ( is_object( $wp_customize -> get_setting( $actual_setting_id ) ) ) {
                      $setting_value = apply_filters( "customize_sanitize_js_{$actual_setting_id}", $setting_value, $wp_customize -> get_setting( $actual_setting_id ) );
                    }

                    //handles theme settings saved with their short names
                    //skope options are stored with their full name. Ex : hu_theme_options[copyright]]
                    if ( in_array( $actual_setting_id, $theme_setting_list ) ) {
                        $czr_opt_id = "{$theme_option_group}[{$actual_setting_id}]";
                        if ( is_object( $wp_customize -> get_setting( $czr_opt_id ) ) ) {
                            $setting_value = apply_filters( "customize_sanitize_js_{$czr_opt_id}", $setting_value, $wp_customize -> get_setting( $czr_opt_id ) );
                        }
                    }
                    $api_ready_db_val[$actual_setting_id] = $setting_value;
                }
                else {
                    // An option like nav_menu_locations is saved as :
                    //  [nav_menu_locations] => Array
                    // (
                    //     [footer] => 2
                    //     [topbar] => 4
                    //     [header] => 3
                    // )
                    // => it must be turned into api ready settings looking like :
                    // nav_menu_locations[footer] = 2
                    // nav_menu_locations[topbar] = 4
                    // nav_menu_locations[header] = 3
                    $to_merge = _ha_build_multidimensional_db_option( $actual_setting_id, $setting_value );
                    //apply js sanitization function
                    //=> ex. For widgets : 'sanitize_widget_js_instance'
                    foreach( $to_merge as $set_id => $value ) {
                        if ( is_object( $wp_customize -> get_setting( $set_id ) ) ) {
                            //@see class-wp-customize-setting.php::js_value()
                            $value = apply_filters( "customize_sanitize_js_{$set_id}", $value, $wp_customize -> get_setting( $set_id ) );
                        }
                        $to_merge[$set_id] = $value;
                    }
                    $api_ready_db_val = array_merge( $api_ready_db_val, $to_merge );
                }
            }
            // ha_error_log('////////////////////// API READY DB VAL FOR SKOPE : ' . $level );
            // ha_error_log( print_R( $api_ready_db_val, true ) );
            // ha_error_log('///////////////////////////////////////');
            return $api_ready_db_val;
        }



        //@return the full set of sanitized saved global options
        function _ha_get_sanitized_skoped_saved_global_options() {
            global $wp_customize;

            $_theme_opts = hu_get_raw_option( null, HU_THEME_OPTIONS );
            $_defaults_theme_options  = HU_utils::$inst -> hu_get_default_options();
            $_saved_opts          = array();
            if ( ! is_array( $_theme_opts ) )
              return array();
            foreach ( $_theme_opts as $opt_name => $value) {
                if ( ha_is_option_protected( $opt_name ) )
                  continue;
                if ( ! ha_is_option_skoped( $opt_name ) )
                  continue;
                if ( $this -> _ha_is_option_set_to_default( $opt_name, $value, $_defaults_theme_options ) )
                  continue;
                //ha_error_log( $opt_name );
                //is theme option ?
                if ( in_array( $opt_name, HU_utils::$_theme_setting_list ) ) {
                  $_theme_option_prefix = strtolower(HU_THEME_OPTIONS);
                  $opt_name = "{$_theme_option_prefix}[{$opt_name}]";
                }

                $_saved_opts[$opt_name] = $value;
            }

            //WORDPRESS OPTIONS AND THEME MODS
            //regular options included in skope
            $regular_wp_builtins = array(
              'blogname',
              'blogdescription'
            );
            foreach ($regular_wp_builtins as $_opt ) {
                $_saved_opts[$_opt] = hu_get_raw_option( $_opt );
            }

            // ha_error_log( '/// RAW OPTIONS' );
            // ha_error_log( print_R( $_saved_opts, true ) );

            // simple theme mods included in skope
            // To add : sidebars_widgets = array()
            $authorized_theme_mods = ha_get_wp_builtins_skoped_theme_mods();
            //get the unfiltered theme_mods
            $theme = get_option( 'stylesheet' );
            $_raw_theme_mods = hu_get_raw_option( "theme_mods_{$theme}" );

            // ha_error_log( '/// RAW THEME MODS' );
            // ha_error_log( print_R( $_raw_theme_mods, true ) );

            foreach ( $authorized_theme_mods as $_theme_mod_name ) {
                if ( ! isset( $_raw_theme_mods[$_theme_mod_name] ) )
                    continue;

                if ( ! _ha_is_wp_setting_multidimensional( $_theme_mod_name ) ) {
                    $_saved_opts[$_theme_mod_name] =  $_raw_theme_mods[$_theme_mod_name];
                } else {
                    $to_merge = _ha_build_multidimensional_db_option( $_theme_mod_name, $_raw_theme_mods[$_theme_mod_name] );
                    //apply js sanitization function
                    //=> ex. For widgets : 'sanitize_widget_js_instance'
                    foreach( $to_merge as $set_id => $value ) {
                        $to_merge[$set_id] = $value;
                    }
                    $_saved_opts = array_merge( $_saved_opts, $to_merge );
                }

            }

            //Sanitize before injection in preview
            $js_sanitized_saved_opts = array();

            //ha_error_log( '/// $_saved_opts' );
            //ha_error_log( print_R( $_saved_opts, true ) );

            foreach ( $_saved_opts as $cand_setting_id => $cand_setting_value ) {
                if ( is_object( $wp_customize -> get_setting( $cand_setting_id ) ) ) {
                    $sanitized_value = apply_filters( "customize_sanitize_js_{$cand_setting_id}", $cand_setting_value, $wp_customize -> get_setting( $cand_setting_id ) );
                    $js_sanitized_saved_opts[$cand_setting_id] = $sanitized_value;
                }
                // else {
                //     ha_error_log( 'In _ha_get_sanitized_skoped_saved_global_options, ' . $cand_setting_id . ' could not be sanitized before being injected in preview. Not registered in $wp_customize.' );
                //     //$sanitized_value = $cand_setting_value;
                // }
            }
            // ha_error_log( '/// $js_sanitized_saved_opts' );
            // ha_error_log( print_R( $js_sanitized_saved_opts, true ) );
            return $js_sanitized_saved_opts;
        }








        /* ------------------------------------------------------------------------- *
         *  CUTE LITTLE HELPERS
        /* ------------------------------------------------------------------------- */
        function _ha_is_option_set_to_default( $opt_name, $value, $defaults ) {
            if ( ! is_array( $defaults ) || ! array_key_exists( $opt_name, $defaults ) )
              return;
            //@todo : for value written as associative array, we might need a more advanced comparison tool using array_diff_assoc()
            if ( ! is_array( $value ) )
              return $value == $defaults[$opt_name];
            else {
                if ( is_array( $value ) && ! is_array( $defaults[$opt_name] ) )
                  return;
                else {
                  if ( empty( $defaults[$opt_name] ) )
                    return;
                  // ha_error_log( '///_ha_is_option_set_to_default' . $opt_name );
                  // ha_error_log( print_R( array_intersect( $value, $defaults[$opt_name] ) ), true );
                  // ha_error_log( print_R( $value , true ) );
                  // ha_error_log( print_R( $defaults , true ) );
                  return count($value) == count( array_intersect( $value, $defaults[$opt_name] ) );
                }
            }
        }
    }//class
endif;

?><?php

if ( ! class_exists( 'HA_Skop_Cust_Register' ) ) :
    final class HA_Skop_Cust_Register {
        function __construct() {
            /* ------------------------------------------------------------------------- *
             *  Modify some WP built-in settings
            /* ------------------------------------------------------------------------- */
            //modify some WP built-in settings
            //Assign an extended class to Header_Image Settings
            add_action( 'customize_register' , array( $this, 'ha_alter_wp_customizer_settings' ) , 100, 1 );

            // REGISTER A NEW SETTING IN ADMIN SETTINGS SEC
            add_filter( 'hu_admin_sec'   , array( $this, 'ha_register_skop_infos_settings'));

            /* ------------------------------------------------------------------------- *
             *  CUSTOMIZE PANE : Add skope server params to the Hueman theme control server params ( serverControlParams global var)
            /* ------------------------------------------------------------------------- */
            add_filter( 'hu_js_customizer_control_params', array( $this, 'ha_add_skope_control_params' ) );
            //'controls_translated_strings' is declared in hueman theme, czr-resources
            //add_filter( 'controls_translated_strings', array( $this, 'hu_add_skp_translated_strings') );
            /* ------------------------------------------------------------------------- *
             *  Skopify the save DEPRECATED
             *  1) Dynamically set the type in WP_Customize_Setting::save()
             *  2) Then add skope save actions by type on WP_Customize_Setting::update()
            /* ------------------------------------------------------------------------- */
            //add_action( 'customize_register' , 'ha_prepare_skopify_customizer_save');
        }

        //hook : hu_admin_sec
        function ha_register_skop_infos_settings( $settings ) {
            $settings = is_array( $settings ) ? $settings : array();
            return array_merge( $settings, array(
                'show-skope-infos' => array(
                    'default'   => 1,
                    'control'   => 'HU_controls',
                    'label'     => __('Display an informations block at the bottom of the preview', 'hueman-addons'),
                    'section'   => 'admin_sec',
                    'type'      => 'checkbox',
                    'notice'    => __('When this option is checked, a block of informations about the current customization scope is displayed at the bottom of the preview.', 'hueman-addons'),
                    'priority'  => 30,
                    'transport' => 'postMessage'
                )
            ));
        }


        /* ------------------------------------------------------------------------- *
         *  Modify some WP built-in settings
        /* ------------------------------------------------------------------------- */
        //hook : customize_register
        function ha_alter_wp_customizer_settings( $manager ) {
          if ( is_object( $manager->get_setting( 'header_image_data' ) ) ) {
              $manager -> remove_setting( 'header_image_data' );
              $manager -> add_setting( new HA_Customize_Header_Image_Data_Setting( $manager, 'header_image_data', array(
                'theme_supports' => 'custom-header',
              ) ) );
              $manager -> remove_setting( 'header_image' );
              $manager -> add_setting( new HA_Customize_Header_Image_Setting( $manager, 'header_image', array(
                'default'        => get_theme_support( 'custom-header', 'default-image' ),
                'theme_supports' => 'custom-header',
              ) ) );
          }

          if ( is_object( $manager->get_control( 'hu_theme_options[layout-global]' ) ) ) {
              $manager->get_control( 'hu_theme_options[layout-global]' ) -> label = __( "Column Layout for main content and sidebars", 'hueman-addons' );
              $manager->get_control( 'hu_theme_options[layout-global]' ) -> notice = __( 'Pick a content layout in the dropdown list. Note : selecting a "1 column" layout won\'t let you display any sidebar widgets.', 'hueman-addons' );
          }
        }


        /* ------------------------------------------------------------------------- *
         *  CUSTOMIZE PANEL : ADD LOCALIZED PARAMS
        /* ------------------------------------------------------------------------- */
        //filter 'hu_js_customizer_control_params' is declared in hueman/functions/czr/czr-resources.php
        function ha_add_skope_control_params( $_params ) {
            return array_merge(
              $_params,
              array(
                  'isSkopOn'              =>  HU_AD() -> ha_is_skop_on(),
                  'defaultSkopeModel'     => _ha_get_default_scope_model(),
                  'skopeDynTypes'         => ha_get_dyn_types(),
                  'defaultOptionsValues'  => HU_utils::$inst -> hu_get_default_options(),
                  'skopeExcludedSettings' => ha_get_skope_excluded_options(),
                  'globalSkopeOptName'    => HA_SKOP_OPT() -> global_skope_optname,
                  'isSidebarsWigetsSkoped' => (bool)apply_filters( 'ha_skope_sidebars_widgets', false ),
                  'isWPCustomCssSkoped'    => (bool)apply_filters( 'ha_skope_wp_custom_css', false ),
                  'isNavMenuLocationsSkoped'  => (bool)apply_filters( 'ha_skope_navmenu', true ),
                  'isChangeSetOn'         => HU_AD() -> ha_is_changeset_enabled(),
                  //If server send isLocalSkope = true, then try to activate the local skope
                  //serverControlParams.isLocalSkope is used in api.czr_skopeBase.getActiveSkopeId()
                  //Old check was based on isset( $_GET['url'] ), but setting the local skope on init makes the preview too long to load
                  'isLocalSkope'          => apply_filters( 'skope_is_local', isset( $_GET['url'] ) ),
                  'isTopNoteOn'           => apply_filters( 'ha_czr_top_note_status', 'dismissed' != get_option( 'ha_czr_top_note_status' ) ||  ( defined('CZR_DEV') && true === CZR_DEV ) ),
                  'topNoteParams'         => array(
                      'title'   => __( 'Welcome in the new customizer interface !', 'hueman-addons' ),
                      'message' => sprintf ( __( 'Discover a new way to customize your pages on %1$s.', 'hueman-addons' ),
                            sprintf('<a href="%1$s" title="%2$s" target="_blank">%3$s <span class="fa fa-external-link"></span></a>',
                                esc_url('docs.presscustomizr.com/search?query=customize-hueman'),
                                __('Visit the documentation', 'hueman-addons'),
                                __('this page')
                            )
                      )
                  )
                )
              );
        }


        /* ------------------------------------------------------------------------- *
         *  CUSTOMIZE PANEL : ADD TRANSLATED STRINGS
        /* ------------------------------------------------------------------------- */
        function hu_add_skp_translated_strings( $strings ) {
              return array_merge( $strings, array(

              ));
        }//hu_add_skp_translated_strings


        //hook : customize_register
        // function ha_prepare_skopify_customizer_save() {
        //   //Which options are we targeting there?
        //   // 1) the theme options
        //   // 2) the WP built in options
        //   $_options_to_skope = HU_customize::$instance -> hu_get_wp_builtin_settings();
        //   $_options_to_skope[] = HU_THEME_OPTIONS;

        //   if ( apply_filters( 'ha_skope_sidebars_widgets', false ) ) {
        //       $_options_to_skope[] = 'sidebars_widgets';
        //       $_options_to_skope  = array_merge( $_options_to_skope, hu_get_registered_widgets_option_names() );
        //   }

        //   //loop on the targeted option to dynamically set the type on save
        //   foreach ( $_options_to_skope as $_opt_name ) {
        //     add_action( "customize_save_{$_opt_name}"  , 'ha_set_setting_type' );
        //   }

        //   // add_action( 'customize_update_trans' , 'ha_customizer_set_trans', 10, 2 );
        //   // add_action( 'customize_update_post_meta' , 'ha_customizer_set_post_meta', 10, 2 );
        //   // add_action( 'customize_update_term_meta' , 'ha_customizer_set_term_meta', 10, 2 );
        //   // add_action( 'customize_update_user_meta' , 'ha_customizer_set_user_meta', 10, 2 );
        //   add_action( 'customize_update_skope_meta' , 'ha_customizer_save_skope_meta', 10, 2 );


        //   //CHANGESET
        //   //add_filter( 'customize_changeset_save_data', 'ha_customizer_set_changet_data', 10, 2 );
        //   //'wp_insert_post_data' is declared in wp-includes/post
        //   //add_filter( 'wp_insert_post_data', 'ha_customizer_set_changet_post_data', 100, 2 );

        //   //EXPERIMENT
        //   $theme_name = ha_get_skope_theme_name();//is always the parent theme name
        //   //add_action( "customize_save_{$theme_name}_global_skope"  , 'ha_set_setting_type' );
        //   //add_action( 'customize_update_global_option' , 'ha_customizer_set_global_option', 10, 2 );
        // }


        /* ------------------------------------------------------------------------- *
         *  Set Changeset Data for skope
        /* ------------------------------------------------------------------------- */
        // function ha_customizer_set_changet_data( $data, $filter_context ) {
        //   return $data;
        // }

        // //hook : 'wp_insert_post_data'
        // function ha_customizer_set_changet_post_data( $data, $postarr ) {
        //   if ( $data['post_type'] != 'customize_changeset' )
        //     return $data;
        //   if ( isset( $_POST['skope']) && 'global' == $_POST['skope'] )
        //     return $data;

        //   if ( ! isset( $_POST['opt_name']) || ! isset( $_POST['skope_id'] ) || ! isset( $_POST['skopeCustomized'] ) )
        //     return $data;
        //   $opt_name = $_POST['opt_name'];
        //   $skope_id = $_POST['skope_id'];
        //   $skope_customized = json_decode( wp_unslash($_POST['skopeCustomized'] ), true );

        //   if ( ! isset( $skope_customized[$skope_id] ) )
        //     return $data;

        //   $skope_settings = $skope_customized[$skope_id];
        //   $data['meta_input'] = ! isset( $data['meta_input'] ) ? array(): $data['meta_input'];
        //   $data['meta_input'][$opt_name] = 'JOIE';//$skope_settings;

        //   return $data;
        // }


        /* ------------------------------------------------------------------------- *
         *  Set the dynamic type sent by $_POST
        /* ------------------------------------------------------------------------- */
        //hook : customize_save_hu_theme_options
        //hook fired in WP_Customize_Setting
        //at this point, the nonce has already been checked by the customizer manager
        //if 'wp_default_type' is specified, then always falls back to wp type
        //=> 'wp_default_type' is typically used when saving a skope excluded setting. It should not be parsed by this action because it's option name based but this is a paranoid, irrational security.
        // function ha_set_setting_type( $setting ) {
        //     //don't fire when saving the global skope
        //     if ( 'global' == HA_SKOP_OPT() -> ha_get_current_customized_skope() )
        //       return;

        //     if ( ! $setting->check_capabilities() )
        //       return new WP_Error( 'user_not_allowed' );

        //     $skope_id     = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
        //     $global_value = $setting -> post_value();

        //     //$global_value is used as a fallback
        //     //HA_SKOP_OPT() -> ha_get_customized_value returns a validated and sanitized value
        //     $skope_value  = HA_SKOP_OPT() -> ha_get_customized_value( $setting->id, $global_value);//inheritance set to false by default

        //     if (  ! isset( $skope_value ) )
        //       return; //new WP_Error( 'no_value_for_setting_id' . $setting->id );

        //     if ( 'theme_mod' != $setting -> type )
        //       $setting -> type = ( ! isset($_POST['dyn_type']) || 'wp_default_type' == $_POST['dyn_type'] ) ? $setting -> type : $_POST['dyn_type'];
        //     else if ( isset($_POST['skope']) && 'global' == $_POST['skope'] ) {
        //       $setting -> type = 'theme_mod';
        //     }
        //     else {
        //       $setting -> type = ( ! isset($_POST['dyn_type']) || 'wp_default_type' == $_POST['dyn_type'] ) ? $setting -> type : $_POST['dyn_type'];
        //     }
        // }





        /* ------------------------------------------------------------------------- *
         *  Write the skope options in DB
        /* ------------------------------------------------------------------------- */
        //hook : customize_update_global_option
        //at this point, the nonce has already been checked by the customizer manager
        //This callback is fired in WP_Customize_Setting::update()
        //@param $value has been sanitized in WP_Customize_Setting::save() at this point, by WP_Customize_Manager::post_value()
        // function ha_customizer_set_global_option( $value, $setting ) {
        //     if ( ! $_POST['opt_name'] || $_POST['opt_name'] != HA_SKOP_OPT() -> global_skope_optname || ! $setting->check_capabilities() || ! isset( $value ) )
        //       return;
        //     $db_opt_val = hu_get_raw_option( HA_SKOP_OPT() -> global_skope_optname );
        //     $new_value = _ha_customizer_preprocess_save_value( $value, $setting, $db_opt_val );
        //     update_option( $_POST['opt_name'], $new_value );
        // }





        //hook : customize_update_skope_meta
        //at this point, the nonce has already been checked by the customizer manager
        //This callback is fired in WP_Customize_Setting::update()
        //@param $value has been sanitized in WP_Customize_Setting::save() at this point, by WP_Customize_Manager::post_value()
        //At this stage, the setting validity has been checked by the WP_Customize_Manager::save() method
        // function ha_customizer_save_skope_meta( $value, $setting ) {
        //     if ( ! $_POST['opt_name'] )
        //       return new WP_Error( 'missing_skope_meta_key' );

        //     if ( ! $setting->check_capabilities() )
        //       return new WP_Error( 'missing_skope_meta_key' );

        //     $skope_id     = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );
        //     $global_value = $value;
        //     // HA_SKOP_OPT() -> ha_get_customized_value() returns a validated and sanitized customized value
        //     $skope_value  = HA_SKOP_OPT() -> ha_get_customized_value( $setting->id, $global_value ); //$global_value is used as a fallback

        //     if ( ! isset( $skope_value ) )
        //       return;

        //     $skope_meta_key = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name' );
        //     $skope_post_id  = get_option('skope-post-id');
        //     if ( false === $skope_post_id || empty($skope_post_id) )
        //       return new WP_Error( 'missing_skope_post_id' );

        //     global $wp_customize;
        //     $setting_validities = array();

        //     //setting validation has been implemented in WP 4.6
        //     //=> check if the feature exists in the user WP version
        //     if ( method_exists($wp_customize, 'validate_setting_values') ) {
        //         // Validate setting value.
        //         // This is normally done by the manager before the setting->save() call.
        //         // But for skope we need to do it now, for each setting id.
        //         $setting_validities = $wp_customize->validate_setting_values( array( $setting->id => $skope_value ), array(
        //           'validate_capability' => true,
        //           'validate_existence' => true,
        //         ) );
        //         $invalid_setting_count = count( array_filter( $setting_validities, 'is_wp_error' ) );

        //         if ( $invalid_setting_count > 0 ) {
        //           $response = array(
        //             'setting_validities' => $setting_validities,
        //             'message' => sprintf( _n( 'There is %s invalid setting.', 'There are %s invalid settings.', $invalid_setting_count ), number_format_i18n( $invalid_setting_count ) ),
        //           );
        //           return new WP_Error( 'transaction_fail', '', $response );
        //         }
        //     }

        //     $response = array(
        //       'setting_validities' => $setting_validities,
        //     );


        //     // Obtain/merge data for skope meta
        //     $existing_changeset_data = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key ) );
        //     $data = $existing_changeset_data;
        //     if ( is_wp_error( $data ) ) {
        //       $data = array();
        //     }

        //     $data[ $setting->id ] = $skope_value;

        //     $r = HA_SKOP_OPT() -> ha_set_skope_option_val( $setting->id, $skope_value, $skope_meta_key );

        //     if ( is_wp_error( $r ) ) {
        //       $response['skope_post_save_failure'] = $r->get_error_code();
        //       return new WP_Error( 'skope_post_save_failure', '', $response );
        //     }
        // }
    }//class
endif;

?><?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option_Base' ) ) :
    class HA_Skop_Option_Base {
        public static $instance;

        public static $_global_opt;
        public static $_group_opt;
        public static $_special_group_opt;
        public static $_local_opt;
        public static $_skope_excluded_settings;
        public $global_skope_optname;

        function __construct() {
            //SIDEBAR WIDGETS
            add_filter('sidebars_widgets', array($this, 'ha_use_skope_widgets') );

            //SIDEBAR ON PREVIEW : fix the problem of a customized val being an empty array of wp_inactive_widgets;
            //This filter is ha_customize_val_before_multidimensional_filter_{$opt_name}
            //add_filter('ha_customize_val_before_multidimensional_filter_sidebars_widgets', array($this, 'ha_set_customized_sidebars_val'), 10, 2 );

            //CACHE SOME USEFUL LIST OF SETTINGS : THEME SETTINGS AND SKOPE EXCLUDED SETTINGS
            $this -> ha_cache_skope_excluded_settings();

            //FILTER THE LIST OF SKOPE EXCLUDED SETTINGS
            //=> merge the default ones with those defined in the setting map
            add_filter( 'ha_get_skope_excluded_options', array( $this, 'ha_set_excluded_skope_settings') );


            //CACHE SKOPED OPTION WHEN THE QUERY IS BUILT
            // if ( HU_AD() -> ha_is_customize_preview_frame() ) {
            //   //refresh the theme options right after the _preview_filter when previewing
            //   add_action( 'customize_preview_init'  , array( $this , 'ha_cache_options' ) );
            // } else {
            //   add_action( 'wp' , array( $this, 'ha_cache_options' ) );
            // }
            if ( hu_is_ajax() && ! ha_is_partial_ajax_request() ) {

              add_action( 'ajax_query_ready',  array( $this, 'ha_setup_skope_option_filters' ), 1000 );

              add_action( 'ajax_query_ready' , array( $this, 'ha_cache_options' ), 99999 );

            }
            else {

              //SETUP FILTERS FOR WP OPTIONS AND THEME OPTIONS
              // => exclude partial requests, WP does the job
              if ( ! ha_is_partial_ajax_request() ) {
                add_action( 'wp',  array( $this, 'ha_setup_skope_option_filters' ), 1000 );
              }

              add_action( 'wp' , array( $this, 'ha_cache_options' ), 99999 );

            }


            //---------------------- DEPRECATED -------------------------------- //
            //SET THE NAME OF THE GLOBAL SKOPE OPTION
            //This option stores all global skope settings : theme and wp.
            //It is updated each time the global skope get saved or reset in the customizer
            //It is used to send the list of currently modified global settings in db
            $theme_name = ha_get_skope_theme_name();
            $this -> global_skope_optname = "{$theme_name}_global_skope";

        }//construct





        /* ------------------------------------------------------------------------- *
         *  SET AND GET CACHED OPTIONS
        /* ------------------------------------------------------------------------- */
        //the purpose of this function is to store the various skope options as properties
        //instead of getting them each time
        //fired on customize_preview_init if in preview frame
        //fired by constructor otherwise
        //@return void()
        //HOOK : WP
        function ha_cache_options() {
            $meta_type = ha_get_skope( 'meta_type', true );
            $_skope_list = array( 'global', 'group', 'special_group', 'local' );
            foreach ($_skope_list as $_skp ) {
                switch ( $_skp ) {
                    //don't cache the global option, WP already does it
                    case 'global':
                      //self::$_global_opt = false === get_option( HU_THEME_OPTIONS ) ? array() : (array)get_option( HU_THEME_OPTIONS );
                    break;
                    case 'group':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'group' );
                      self::$_group_opt = $this -> ha_get_skope_opt( 'group', $db_opt_name );
                      self::$_group_opt = ! self::$_group_opt ? array() : self::$_group_opt;
                    break;
                    case 'special_group':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'special_group' );
                      self::$_special_group_opt = $this -> ha_get_skope_opt( 'special_group', $db_opt_name );
                      self::$_special_group_opt = ! self::$_special_group_opt ? array() : self::$_special_group_opt;
                    break;
                    case 'local':
                      $db_opt_name = $this -> ha_get_skope_opt_name( 'local' );
                      self::$_local_opt = $this -> ha_get_skope_opt( 'local', $db_opt_name );
                      self::$_local_opt = ! self::$_local_opt ? array() : self::$_local_opt;
                    break;
                }
            }
            do_action( 'skope_options_cached');
        }


        //Get the skoped option from the db
        //The option name is based on the current skope
        //=> this can't be fired before 'wp'
        //
        //@param level : local, group, special_group
        //@param type : post
        //@return the provided meta $skope_meta_key or all skope post metas if the key is not provided or empty
        function ha_get_skope_opt( $level = '', $skope_meta_key = '' ) {
            if( 'global' == $level ) {
              return new WP_Error('skope_error', 'The global options cannot be accessed with ha_get_skope_opt' );
            }
            $skope_meta_key = empty( $level ) ? '' : $this -> ha_get_skope_opt_name( $level );
            $_opt = get_post_meta( ha_get_skope_post_id(), $skope_meta_key, true );
            $_return = array();
            //if no meta key has been provided, this will return an array of all post meta json encoded
            if ( empty( $skope_meta_key ) && is_array( $_opt ) ) {
              foreach ( $_opt as $meta_key => $value ) {
                if ( is_array($value) && 1 == count($value) )
                  $_return[$meta_key] = maybe_unserialize( $value[0] );
                else
                  $_return[$meta_key] = array_map('maybe_unserialize', $value);
              }
            } else {
              $_return = $_opt;
            }

            return is_array($_return) ? $_return : array();
        }



        //@return the array of cached opt
        function ha_get_cached_opt( $skope = null, $opt_name = null ) {
            $skope = is_null( $skope ) ? 'local' : $skope;
            $_opt_array = array();

            switch ( $skope ) {
                case 'global':
                  $alloptions = wp_cache_get( 'alloptions', 'options' );
                  $alloptions = maybe_unserialize($alloptions);
                  $_opt_array = $alloptions;
                break;
                case 'group':
                  $_opt_array = self::$_group_opt;
                break;
                case 'special_group':
                  $_opt_array = self::$_special_group_opt;
                break;
                case 'local':
                  $_opt_array = self::$_local_opt;
                break;
            }

            //make sure we still have an array at this stage
            $_opt_array = ! is_array( $_opt_array ) ? array() : $_opt_array;

            if ( is_null( $opt_name ) )
              return $_opt_array;
            else {
                if ( in_array( $opt_name, HU_utils::$_theme_setting_list ) ) {
                    $_theme_option_prefix = strtolower(HU_THEME_OPTIONS);
                    $opt_name = "{$_theme_option_prefix}[{$opt_name}]";
                }
                return array_key_exists( $opt_name, $_opt_array ) ? $_opt_array[$opt_name] : '_no_set_';
            }
        }


        /* ------------------------------------------------------------------------- *
        *  CACHE AND SET SETTINGS EXCLUDED FROM SKOPE
        /* ------------------------------------------------------------------------- */
        //Fired in __construct()
        function ha_cache_skope_excluded_settings() {
             if ( is_array(self::$_skope_excluded_settings) && ! empty( self::$_skope_excluded_settings ) )
               return;
             $_settings_map = HU_utils_settings_map::$instance -> hu_get_customizer_map( null, 'add_setting_control' );
             $_excluded = array();
             foreach ( $_settings_map as $_id => $data ) {
               if ( isset($data['skoped']) && false === $data['skoped'] )
                 $_excluded[] = $_id;
             }

            //WFC COMPAT
            if ( class_exists( 'TC_utils_wfc' ) ) {
                $wfc_setting_map = TC_utils_wfc::$instance -> tc_customizer_map();
                if ( array_key_exists( 'add_setting_control', $wfc_setting_map ) ) {
                    foreach ( $wfc_setting_map['add_setting_control'] as $_id => $data ) {
                          $_excluded[] = $_id;
                    }
                }
            }
            self::$_skope_excluded_settings = $_excluded;
        }


        /* ------------------------------------------------------------------------- *
         *  FILTER THE LIST OF SKOPE EXCLUDED SETTINGS
        /* ------------------------------------------------------------------------- */
        //=> merge the default ones with those defined in the setting map
        //hook : ha_get_skope_excluded_options declared in init-skop.php
        function ha_set_excluded_skope_settings( $_default_excluded ) {
            return array_merge( $_default_excluded, self::$_skope_excluded_settings );
        }




        /* ------------------------------------------------------------------------- *
         *  FILTER WP AND THEME OPTIONS
        /* ------------------------------------------------------------------------- */
        //hook : wp
        function ha_setup_skope_option_filters() {
            //FILTER THEME OPTIONS
            add_filter( 'hu_opt', array( $this, 'ha_filter_hu_opt_for_skope' ), 1000, 4 );

            //FILTER WP OPTIONS
            $theme = get_option( 'stylesheet' );
            $wp_options = array(
              'blogname',
              'blogdescription',
              //header_image, header_image_data, custom_logo, custom_css, custom_css_post_id, nav_menu_locations
              "theme_mods_{$theme}"
            );

            if ( apply_filters( 'ha_skope_sidebars_widgets', false ) ) {
              $wp_options[] = 'sidebars_widgets';
              $wp_options  = array_merge( $wp_options, hu_get_registered_widgets_option_names() );
            }

            foreach ( $wp_options as $wp_opt ) {
              //documented in wp-includes/option.php
              add_filter( "option_{$wp_opt}", array( $this, 'ha_filter_wp_builtin_options'), 2000, 2 );
            }
        }


        ///////FILTER WP OPTIONS
        //hook : option_{$wp_opt}
        //Example of filtered option : sidebars_widgets
        function ha_filter_wp_builtin_options( $original_value, $option_name = null ) {
          if ( is_null( $option_name ) )
            return $original_value;


          $authorized_theme_mods = ha_get_wp_builtins_skoped_theme_mods();
          $theme = get_option( 'stylesheet' );

          //Filter theme mods built-in options like header_image
          if ( "theme_mods_{$theme}" == $option_name ) {
              $skoped_theme_mods = array();

              foreach ( $authorized_theme_mods as $_tmodname ) {
                  $_tmodval = array_key_exists( $_tmodname, $original_value ) ? $original_value[$_tmodname] : '_no_set_';
                  $_tmodval = $this -> ha_filter_hu_opt_for_skope( $_tmodval, $_tmodname, null );
                  //After the filter, if the value is still not set, don't add it to the build theme mods
                  if ( '_no_set_' !== $_tmodval ) {
                      $skoped_theme_mods[$_tmodname] = $_tmodval;
                      //documented in wp-includes/theme.php
                      add_filter( "theme_mod_{$_tmodname}", array( $this, '_filter_theme_mods'), 2000 );
                  }


              }
              return wp_parse_args( $skoped_theme_mods, $original_value );

              // if ( is_array( $original_value ) ) {
              //     foreach( $original_value as $_opt_name => $_tmodval ) {
              //         if ( ! $_opt_name )
              //           continue;
              //         $skoped_theme_mods[$_opt_name] = $this -> ha_filter_hu_opt_for_skope( $_tmodval, $_opt_name, null );
              //         ha_error_log('alors ?' . $_opt_name );

              //     }
              //     return $skoped_theme_mods;
              // }
          }
          //Filter simple builtin options like blogname
          else {
              //the option group is null
              return $this -> ha_filter_hu_opt_for_skope( $original_value, $option_name, null );
          }
          return $original_value;
        }


        //hook : theme_mod_{$_opt_name}
        function _filter_theme_mods( $value ) {
          //extract theme mod name
          $_filter = current_filter();
          $_ptrn = 'theme_mod_';
          if ( $_ptrn !== substr( $_filter, 0, strlen($_ptrn) ) )
            return $value;

          $_mod_name = str_replace($_ptrn, '',  $_filter);
          if ( ! ha_is_option_skoped( $_mod_name ) )
            return $value;
          //the option group is null
          return $this -> ha_filter_hu_opt_for_skope( $value, $_mod_name, null );
        }






        ///////FILTER THEME OPTIONS
        //which option to get ?
        //1) WHEN CUSTOMIZING
        //- if dyn_type is 'option', then let wp do the job
        //- if dyn_type is not option,
        //      A) the requested option name is currently being customized
        //        => if so, then get the customized value
        //      B) the requested option is not being customized, then get the saved db option using dyn_type and opt_name from $_POST
        //
        //2) WHEN NOT CUSTOMIZING
        // A) the current context can have a meta option : posts (post, page, cpt), tax, authors
        //    => retrieve the meta and check if an entry exists for this option
        // B) the current context can have specific global options like home, all_posts, all_pages, all_{custom_post_type}
        //   all_tag, all_cat, all_{custom_tax}, all_authors, 404, search, date
        //     => if so then check if the current option has an entry in this specific global and return it
        // C) the current context has no specific global option, then fall back on the default value
        //
        //HOOK : hu_opt
        function ha_filter_hu_opt_for_skope( $_opt_val , $opt_name , $opt_group = HU_THEME_OPTIONS , $_default_val = null ) {
            //if the opt group not null, we are retrieving a theme option
            $_new_val = $_opt_val;

            //IF PREVIEWING
            if ( HU_AD() -> ha_is_customize_preview_frame() && !  HU_AD() -> ha_is_previewing_live_changeset() ) {
                $_new_val = $this -> _get_sanitized_preview_val( $_opt_val, $opt_name );
            } else {
                //@param = value, option name, skope, inherits
                $_new_val = $this -> _get_front_end_val( $_opt_val, $opt_name, 'local', true );
            }
            //falls back to global
            return $_new_val;
        }






        /* ------------------------------------------------------------------------- *
        * SET OPTION
        /* ------------------------------------------------------------------------- */
        //Write the new skope option in db
        //Used with 'header_image'
        function ha_set_skope_option_val( $opt_name, $new_value, $skope_meta_key = null ) {
            if ( empty($opt_name) || is_null($skope_meta_key ) ) {
              return new WP_Error( 'missing param(s) in HA_SKOP_OPT::ha_set_skope_option_val' );
            }

            $skope_post_id = ha_get_skope_post_id();

            // Obtain/merge data for skope meta
            $original_data = ha_get_skope_db_data( array( 'post_id' => $skope_post_id, 'skope_meta_key' => $skope_meta_key ) );
            $data = $original_data;
            if ( is_wp_error( $data ) ) {
              $data = array();
            }
            $data = $this -> ha_preprocess_skope_val( $new_value, $opt_name, $data );

            $r = update_post_meta( $skope_post_id, $skope_meta_key, $data );

            return $r;
        }









        /* ------------------------------------------------------------------------- *
        * SIDEBARS AND WIDGETS SPECIFICS
        /* ------------------------------------------------------------------------- */
        //hook filter: sidebar_widgets
        function ha_use_skope_widgets( $original ) {
          // if ( 0 == did_action('wp') )
          //   return $original;
          if ( ! apply_filters( 'ha_skope_sidebars_widgets', false ) )
            return $original;

          $db_skope_widgets = get_option('sidebars_widgets');
          $db_skope_widgets = is_array($db_skope_widgets) ? $db_skope_widgets : array();
          $new_sidebar_widgets = $db_skope_widgets;
          $defaut_hu_widget_ids = hu_get_widget_zone_ids();

          foreach ( $original as $key => $value) {
            if ( in_array( $key, $defaut_hu_widget_ids ) || ! is_array($value) )
              continue;
            //adds any additional entry that the original array contains and that is not a widget id
            //=> ex : orphaned_widgets_1, array_version, ..., wp_inactive_widgets
            $new_sidebar_widgets[$key] = (array)$value;
          }
          //then check if we have only array type values in the new sidebars_widgets
          foreach ($new_sidebar_widgets as $k => $v) {
            if ( ! is_array($v) )
              unset($new_sidebar_widgets[$k]);
          }

          //then make sure that a widget can not be both active in a sidebar AND part of the wp_inactive_widgets list
          if ( isset($new_sidebar_widgets['wp_inactive_widgets']) && is_array($new_sidebar_widgets['wp_inactive_widgets']) ) {
            foreach ( $new_sidebar_widgets as $sidebar => $wdg_list ) {
                //skip all entries that are not real sidebars
                if ( 'wp_inactive_widgets'  == $sidebar )
                  continue;
                if ( 'array_version' == $sidebar )
                  continue;
                if ( 'orphaned_' == substr($sidebar, 0, strlen('orphaned_') ) )
                  continue;

                foreach ( $wdg_list as $wdg_id ) {
                    if ( false === array_search( $wdg_id, $new_sidebar_widgets['wp_inactive_widgets'] ) )
                      continue;
                    $key_to_remove = array_search( $wdg_id, $new_sidebar_widgets['wp_inactive_widgets'] );
                    unset( $new_sidebar_widgets['wp_inactive_widgets'][$key_to_remove] );
                }//foreach
            }//foreach
          }//if
          return $new_sidebar_widgets;
        }


        //hook : 'ha_customize_val_before_multidimensional_filter_sidebars_widgets'
        //DEPRECATED
        function ha_set_customized_sidebars_val( $customized_val, $opt_name ) {
          if ( is_array($customized_val) && isset($customized_val['sidebars_widgets[wp_inactive_widgets']) && 1 == count($customized_val) )
            return '_not_customized_';
          return $customized_val;
        }

    }//class
endif;

?><?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option_Front' ) ) :
    class HA_Skop_Option_Front extends HA_Skop_Option_Base {
        public $_front_values = array();

        /* ------------------------------------------------------------------------- *
         *  GET FRONT END VALUES : APPLY INHERITANCE
        /* ------------------------------------------------------------------------- */
        //recursive method
        //apply the skope inheritance to return the relevant value
        //Typically use when is_customize_preview() to find the relevant database val for a requested skope
        //At this stage, we've already checked if this setting was customized for the current skope OR has inherited a customized value from one of its parent
        //If the current skope has no saved db value for this setting, then let's get the parent one and so on
        function _get_front_end_val( $original_opt_val, $opt_name, $skope = 'local', $do_inherit = false ) {
            ////////////////////////////////////////////////////////////////
            // if ( 'header_image' === $opt_name ) {
            //   ha_error_log( '_get_front_end_val ' . $skope . ' | ' . $opt_name );
            //   ha_error_log( print_R( $original_opt_val, true ) );
            //   //ha_error_log(  print_R( $_candidate_val, true ) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }
            $cache_front = $this -> _front_values;

            //Bail here if we already have a cached value for this opt_name
            //AND if 'skope_options_cached' has already been fired
            //AND we are not previewing
            if ( ! HU_AD() -> ha_is_customize_preview_frame() && ! HU_AD() -> ha_is_previewing_live_changeset() ) {
                if ( 0 !== did_action( 'skope_options_cached' ) && array_key_exists( $opt_name, $cache_front ) ) {
                    return $cache_front[$opt_name];
                }
            }

            if ( _ha_is_wp_setting_multidimensional( $opt_name ) ) {
                $skop_opt_val = $this -> _get_multidim_front_val( $original_opt_val, $opt_name, $skope, $do_inherit );
            } else {
                $skop_opt_val = $this -> _get_simple_front_val( $original_opt_val, $opt_name, $skope, $do_inherit );
            }

            //update the cache array and actually cache it
            $cache_front[$opt_name] = $skop_opt_val;
            $this -> _front_values = $cache_front;

            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   ha_error_log( print_R( $skop_opt_val, true ) );
            // }

            return $skop_opt_val;
        }



        function _get_simple_front_val( $original_opt_val, $opt_name, $skope, $do_inherit ) {
            $skop_opt_val = $this -> ha_get_cached_opt( $skope, $opt_name );
            //cast to array if the saved option is an object. For Ex : header_image_data can be an object
            $skop_opt_val = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;

            //do we have a value ?
            // => a value can be a string or an array. If string, then must be different than '_not_set_'
            if ( is_array( $skop_opt_val ) || '_no_set_' != (string)$skop_opt_val ) {
                return $skop_opt_val;
            }

            //We don't have a value yet
            // if we're not applying the inheritance then bail here with the skope val, else check if we reached the roof
            if ( ! $do_inherit && 'global' != $skope ) {
                return $skop_opt_val;
            } else if ( 'global' == $skope ) {
                return $original_opt_val;
            }

            $parent_skope = $this -> ha_get_parent_skope_name( $skope );
            return $this -> _get_simple_front_val( $original_opt_val, $opt_name, $parent_skope, true );
        }



        function _get_multidim_front_val( $original_opt_val, $opt_name, $skope, $do_inherit, $child_value = null ) {
            $child_value      = ( is_null( $child_value ) || ! is_array( $child_value ) ) ? array() : $child_value;
            $_candidate_val   = $child_value;

            $skop_opt_val     = $this -> ha_get_cached_opt( $skope, $opt_name );
            //cast to array if the saved option is an object. For Ex : header_image_data can be an object
            $skop_opt_val     = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;

            //do we have a value ?
            // => a value can be a string or an array. If string, then must be different than '_not_set_'
            if ( is_array( $skop_opt_val ) ) {
                $_candidate_val = wp_parse_args( $_candidate_val, $skop_opt_val );
            }

            if ( 'global' == $skope ) {
                if ( is_array( $original_opt_val ) )
                  return wp_parse_args( $_candidate_val, $original_opt_val );
                else
                  return $_candidate_val;
            }

            //We don't have a value yet
            // if we're not applying the inheritance then bail here with the skope val, else check if we reached the roof
            if ( ! $do_inherit ) {
                return wp_parse_args( $_candidate_val, $original_opt_val );
            }

            $parent_skope = $this -> ha_get_parent_skope_name( $skope );
            return $this -> _get_multidim_front_val( $original_opt_val, $opt_name, $parent_skope, true, $_candidate_val );
        }



        function ha_get_parent_skope_name( $skope, $_index = null ) {
            $hierark = array( 'local', 'group', 'special_group', 'global' );
            $parent_ind = -1;
            //get the parent index
            foreach( $hierark as $_key => $_skp ) {
                if ( $skope == $_skp && -1 == $parent_ind )
                  $parent_ind = intval( $_key + 1 ) ;
                continue;
            }
            return isset( $hierark[$parent_ind] ) ? $hierark[$parent_ind] : 'global';
        }

    }//class
endif;

?><?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option_Preview' ) ) :
    class HA_Skop_Option_Preview extends HA_Skop_Option_Front {
        //CACHE PROPERTIES
        public $_skope_preview_values = array();
        public $all_skopes_customized_values = array();



        /* ------------------------------------------------------------------------- *
         *  GET CUSTOMIZER PREVIEW VAL
        /* ------------------------------------------------------------------------- */

        function _get_sanitized_preview_val( $_opt_val , $opt_name ) {
            $rev_index        = $this -> ha_get_sanitized_post_value( 'revisionIndex' );
            $rev_index        = '__not_posted__' == $rev_index ? 'initial' : $rev_index;
            $cache_customized = $this -> _skope_preview_values;
            $preview_val      = '';

            ////////////////////////////////////////////////////////////////
            // if ( 'use-header-image' === $opt_name ) {
            //   if ( isset( $cache_customized[$rev_index] ) && is_array( $cache_customized[$rev_index]) && array_key_exists( $opt_name, $cache_customized[$rev_index] ) ) {
            //     ha_error_log( '/////////CACHED PREVIEW VAL = ' . $cache_customized[$rev_index][$opt_name] );
            //   }
            // }


            //Bail here if we already have a cached set of customized values for this skope
            if ( isset( $cache_customized[$rev_index] ) && is_array( $cache_customized[$rev_index]) && array_key_exists( $opt_name, $cache_customized[$rev_index] ) )
              return $cache_customized[$rev_index][$opt_name];

            if ( ! isset( $cache_customized[$rev_index] ) || ! is_array( $cache_customized[$rev_index] ) )
              $cache_customized = array( $rev_index => array( $opt_name => '' ) );
            else {
              if ( ! array_key_exists( $opt_name, $cache_customized[$rev_index] ) )
                $cache_customized[$rev_index][$opt_name] = '';
            }

            if ( _ha_is_wp_setting_multidimensional( $opt_name ) ) {
              $preview_val = $this -> _get_multidim_sanitized_preview_val( $_opt_val, $opt_name );
            } else {
              $preview_val = $this -> _get_simple_sanitized_preview_val( $_opt_val, $opt_name );
            }


            //cache the value now but make sure the skope_id has been posted before
            if ( '__not_posted__' != $this -> ha_get_sanitized_post_value( 'skope_id' ) ) {
                $cache_customized[$rev_index][$opt_name] = $preview_val;
                $this -> _skope_preview_values = $cache_customized;
            } else {
                ha_error_log( 'preview val not_cached_yet => the skope_id was not posted' );
            }

            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   ha_error_log( print_R( $preview_val, true ) );
            //   //ha_error_log( print_R( $_skope_customized_val, true ) );
            //   //ha_error_log(  print_R( $_candidate_val, true ) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            //   //ha_error_log( 'CUSTOMIZED VAL FOR ' . $skope_id);
            //   // if ( 'group_all_page' == $skope_id ) {
            //   //   ha_error_log( print_R( $this -> ha_get_unsanitized_customized_values( $skope_id ), true ) );
            //   // }
            //   //ha_error_log( print_R( $_POST, true ) );
            //   // ha_error_log( '///////////////// START PREVIEW VAL');
            //   // ha_error_log( $preview_val );
            //   // ha_error_log( '///////////////// END PREVIEW VAL');
            //   // ha_error_log( is_bool( $val_candidate ) );
            //   // ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }
            return $preview_val;
        }


        //Performs the following actions to find a match
        //1) Return the original value if previewing the global skope => WP has already done the job
        //2) Check if the current setting has dirties in the current skope
        //3) Then check if there's a db saved meta val for this setting in the current skope
        //4) Then check if there's a customized inheritance value for this setting from parent skope(s)
        //5) Then check if there's a saved value to inherit from parents
        //6) Falls back on the default setting val
        function _get_simple_sanitized_preview_val( $_original_val , $opt_name, $skope_id = null, $child_value = null ) {
            $val_candidate            = is_null( $child_value ) ? '_not_customized_' : $child_value;
            $skope_id                 =  is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            $skope_level              = $this -> ha_get_skope_level( $skope_id );
            $_skope_customized_val    = $this -> _ha_get_simple_sanitized_customized_value( $opt_name, $skope_id, $_original_val );
            $_skope_db_meta_val       = $this -> _get_front_end_val( $_original_val, $opt_name, $skope_level );


            if ( $this -> _is_value_customized( $_skope_customized_val ) ) {
              $val_candidate = $_skope_customized_val;
            } else {
              $val_candidate = $_skope_db_meta_val;
            }
            ////////////////////////////////////////////////////////////////
            // if ( 'use-header-image' === $opt_name ) {
            //   ha_error_log( 'PREVIEW VAL FOR SKOPE ' . $skope_id);
            //   // if ( 'group_all_page' == $skope_id ) {
            //   //   ha_error_log( print_R( $this -> ha_get_unsanitized_customized_values( $skope_id ), true ) );
            //   // }
            //   // ha_error_log( '$_skope_db_meta_val : ' . $_skope_db_meta_val);
            //   // ha_error_log( print_R( $_skope_customized_val, true ) );
            //   // ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            //   // ha_error_log( is_bool( $val_candidate ) );
            //   // ha_error_log( 'val candidate : ' . $val_candidate);
            //   // ha_error_log( 'MMMMMMMEEDE : ' . ( $val_candidate !== '_no_set_' &&  $val_candidate !== '_not_customized_' ) );
            // }

            ////////////////////////////////////////////////////////////////
            // if ( 'header_image' === $opt_name ) {
            //   ha_error_log( 'SAVED VAL ' . $skope_id);
            //   ha_error_log( print_R( $_skope_db_meta_val, true ) );
            //   ha_error_log( $val_candidate );
            //   ha_error_log( in_array( $val_candidate, array( '_no_set_', '_not_customized_' ) ) );
            // }

            if ( is_bool( $val_candidate ) )
              return $val_candidate;

            if ( $val_candidate !== '_no_set_' &&  $val_candidate !== '_not_customized_' )
              return $val_candidate;

            $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
            //ha_error_log('ALORS PARENT SKOPE ID : ' . $parent_skope_id );
            //if a parent exists, let's get attempt to merge any additional inherited value
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $val_candidate = $this -> _get_simple_sanitized_preview_val( $_original_val, $opt_name, $parent_skope_id, $val_candidate );
            }

            //falls back on original value
            if ( $val_candidate === '_no_set_' || $val_candidate === '_not_customized_' )
                $val_candidate = $_original_val;

            ////////////////////////////////////////////////////////////////
            // if ( 'use-header-image' === $opt_name ) {
            //   ha_error_log( 'FINAL VAL ? ' . $skope_id);
            //   ha_error_log( print_R( $val_candidate, true ) );
            // }

            return $val_candidate;
        }



        /* ------------------------------------------------------------------------- *
        * GET CUSTOMIZED VALUE FOR A SINGLE SETTING: APPLY INHERITANCE
        /* ------------------------------------------------------------------------- */
        // This method should get the customized val for a given setting.
        // If the requested skope has no customized val, then we should try to get the parent customized val
        // and so on until we reach the global skope.
        // => just like we do in the js api.
        //
        // The customized values have to be grabbed with a method like $wp_customize -> unsanitized_post_values()
        // But we need to pass the skope param to actually retrieve the relevant set of customized values.
        //
        //
        // hueman option ex : use-header-image => when customizing, this option will be hu_theme_options[use-header-image]
        // wp option ex1 : blogdescription => unchanged name when customizing, because not multidimensional
        // wp option ex2 : sidebars_widgets => is multidimensional and will be like sidebars_widgets[primary]
        //
        // Consequently :
        // 1) we first check if each customized options contain a key == opt_name
        // 2) then we check if each customized option is multidimensional and starts with the provided opt_name
        //
        // The values are
        function _ha_get_simple_sanitized_customized_value( $opt_name, $skope_id = null , $_original_val = null, $do_inherit = false ) {
            // if ( 'global' == $this -> ha_get_current_customized_skope() )
            //   return $_original_val;

            $_customized_val  = '_not_customized_';
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return $_original_val;

            global $wp_customize;
            $skope_id           = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;

            //Returns an array merging the $_POST customized and the changeset values
            //=> this should already be server side ready for WP options and theme mods
            //like :
            //blogname => 'A blog name'
            //nav_menu_locations => array(
            //  'header' => 2,
            //  'footer' => 4
            //)
            //For the theme options, it should return the full name options keys like : hu_theme_options[site-description] => 1
            // if ( 'header_image' == $opt_name ) {
            //     ha_error_log( '///////////REQUESTED SKOPE ID' . $skope_id );
            //     ha_error_log( print_R( $this -> ha_get_unsanitized_customized_values( $skope_id ), true ) );

            // }

            $_customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );

            //make sure customized values is always an array
            if ( false === $_customized_values || ! is_array( $_customized_values ) )
              $_customized_values = array();

            //let's set the setting_id to the opt_name by default
            $setting_id   = $opt_name;

            //WP BUIT-IN SETTING NOT MULTIDIMENSIONAL CASE
            if ( array_key_exists( $opt_name, $_customized_values ) && is_object( $wp_customize -> get_setting( $opt_name ) ) ) {
                $_customized_val = $this -> _get_setting_sanitized_skoped_customized_value( $skope_id, $wp_customize -> get_setting( $opt_name ), $do_inherit );// validates and sanitizes the value
            }


            //THEME SETTING CASE
            //If this is needed, let's build the option name as it in the customizer
            $_theme_option_prefix = strtolower( HU_THEME_OPTIONS );
            if ( $_theme_option_prefix !== substr( $opt_name, 0, strlen($_theme_option_prefix) ) ) {
                $_theme_opt_name_candidate = "{$_theme_option_prefix}[{$opt_name}]";
            } else {
                $_theme_opt_name_candidate = $opt_name;
            }
            $_theme_opt_name_candidate = "{$_theme_option_prefix}[{$opt_name}]";

            if ( is_object( $wp_customize -> get_setting( $_theme_opt_name_candidate ) ) ) {
                $_customized_val = $this -> _get_setting_sanitized_skoped_customized_value( $skope_id, $wp_customize -> get_setting( $_theme_opt_name_candidate ), $do_inherit );// validates and sanitizes the value
            }

            //apply WP default checks
            // if ( '_not_customized_' == $_customized_val )
            //   return $_customized_val;
            // $valid = $wp_customize ->get_setting( $setting_id ) -> validate( $_customized_val );
            // if ( is_wp_error( $valid ) ) {
            //   wp_send_json_error( 'Invalid value for setting ' . $setting_id );
            //   return '_not_customized_';
            // }
            // $_original_val = $wp_customize ->get_setting( $setting_id ) ->sanitize( $_customized_val );
            // if ( is_null( $_original_val ) || is_wp_error( $_original_val ) ) {
            //   wp_send_json_error( 'null or not passed the sanitize tests setting ' . $setting_id );
            //   return '_not_customized_';
            // }
            //


            //MAYBE APPLY A SPECIAL TREATMENT
            return apply_filters( "ha_get_customize_val_{$opt_name}", $_customized_val, $opt_name );
        }







        /* ------------------------------------------------------------------------- *
        * GET SKOPE CUSTOMIZED VALUES
        /* ------------------------------------------------------------------------- */
        //When changeset is enabled @return array() merging the changeset and the $_POST customized values
        //If changeset not enabled, @return array() of $_POST customized values
        function ha_get_unsanitized_customized_values( $skope_id = null ) {
            //return array();
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();

            //make sure the skope is specified at this stage.
            $skope_id = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            if ( is_null( $skope_id ) )
              return array();

            global $wp_customize;

            $customized_values = array();

            $rev_index = $this -> ha_get_sanitized_post_value( 'revisionIndex' );
            $rev_index = '__not_posted__' == $rev_index ? 'initial' : $rev_index;
            $cache_customized = $this -> all_skopes_customized_values;

            //Bail here if we already have a cached set of customized values for this skope
            if ( isset( $cache_customized[$rev_index] ) && is_array( $cache_customized[$rev_index]) && array_key_exists( $skope_id, $cache_customized[$rev_index] ) )
              return $cache_customized[$rev_index][$skope_id];

            if ( ! isset( $cache_customized[$rev_index] ) || ! is_array( $cache_customized[$rev_index] ) )
              $cache_customized = array( $rev_index => array( $skope_id => array() ) );
            else {
              if ( ! array_key_exists( $skope_id, $cache_customized[$rev_index] ) )
                $cache_customized[$rev_index][$skope_id] = array();
            }

            //Falls back on WP customized values if 'global' requested AND current skope
            //=> important to check if requested because there are case when we need to get the global customized values from another skope!
            if ( 'global' == $this -> ha_get_skope_level( $skope_id ) || 'global' == $this -> ha_get_current_customized_skope() ) {
                $customized_values = $wp_customize -> unsanitized_post_values();
            } else {
                //Build the customized set for this skope based on the changeset + the posted values
                $skope_changeset_data = $this -> _get_unsanitized_skope_changeset_values( $skope_id );
                $skope_post_values    = $this -> _get_unsanitized_skope_posted_values( $skope_id );
                $customized_values    = array_merge( $skope_changeset_data, $skope_post_values );
            }

            //cache the value now but make sure the skope_id has been posted before
            if ( '__not_posted__' != $this -> ha_get_sanitized_post_value( 'skope_id' ) ) {
              $cache_customized[$rev_index][$skope_id] = $customized_values;
              $this -> all_skopes_customized_values = $cache_customized;
            } else {
              ha_error_log( 'all skopes customized values not_cached_yet => the skope_id was not posted' );
            }

            // ha_error_log( '///// POSTED ?' . $skope_id );
            // ha_error_log( print_R( $_POST, true ) );

            // ha_error_log( '///// $this -> all_skopes_customized_values ( current SKOPE  is : ' . $skope_id . ' )');
            // ha_error_log( print_R( $this -> all_skopes_customized_values, true ) );
            // ha_error_log( print_R( $cache_customized[$rev_index][$skope_id], true ) );

            // ha_error_log( '///// GET CUSTOMIZED VALUES DATA FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $this -> _get_unsanitized_skope_posted_values( $skope_id ), true ) );
            return $customized_values;
        }




















        //This method should return the changeset data ready to be parsed as options.
        //the changeset_data look like this for each setting saved
        //Array
        // (
        //     hu_theme_options[copyright] => Array
        //         (
        //             [value] => Copyright
        //             [type] => option
        //         )
        // )
        // Should become : hu_theme_options[copyright] => copyright
        //
        // Theme mods like nav_menu_locations are stored as :
        // hueman::nav_menu_locations[footer] = array( 'value' => 2, 'type' => 'theme_mod', 'user_id' => 1 )
        // hueman::nav_menu_locations[topbar] = array( 'value' => 4, 'type' => 'theme_mod', 'user_id' => 1 )
        //
        // Should become :
        // nav_menu_locations => Array
        // (
        //     [footer] => 2
        //     [topbar] => 4
        // )
        function _get_unsanitized_skope_changeset_values( $skope_id ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() || ! HU_AD() -> ha_is_changeset_enabled() )
              return array();

            global $wp_customize;
            $values = array();

            //GET THE RAW CHANGESET FOR THE PROVIDED SKOPE_ID
            $skope_meta_key    = $this -> _get_posted_skope_metakey( $skope_id );
            if ( false == $skope_meta_key ) {
              ha_error_log( 'no meta key found in _get_unsanitized_skope_changeset_values() for skope : ' . $skope_id );
              return array();
            }
            $changeset_post_id = $wp_customize -> changeset_post_id();
            $changeset_data = ha_get_skope_db_data(
                array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    //the level must be specify when we're getting the 'global' level
                    'level' => $this -> ha_get_skope_level( $skope_id )
                )
            );

            if ( is_wp_error( $changeset_data ) ) {
                ha_error_log( 'Error when trying to get the changeset data in _get_unsanitized_skope_changeset_values() for skope : ' . $skope_id );
                $changeset_data = array();
            }

            foreach ( $changeset_data as $raw_setting_id => $setting_data ) {
                if ( ! is_array( $setting_data ) || ! array_key_exists( 'value', $setting_data ) ) {
                  ha_error_log( 'Problem in _get_unsanitized_skope_changeset_values, the setting_data of the changeset are not well formed for skope : ' . $skope_id );
                  continue;
                }

                $setting_id = $raw_setting_id;
                // If theme_mod type, get rid of the theme name prefix
                if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {
                    $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                    if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                      $setting_id = $matches['setting_id'];
                    }
                }
                $values[$setting_id] = $setting_data['value'];
            }

            // ha_error_log( '/////////////// CHANGESET BEFORE ');
            // ha_error_log( print_R( $changeset_data, true ) );
            // ha_error_log( '/////////////// CHANGESET AFTER ');
            // ha_error_log( print_R( ha_prepare_skope_changeset_for_front_end( $changeset_data ), true ) );
            return $values;
        }




        //@param skope_id = $this -> ha_get_sanitized_post_value( 'skope_id' )
        //@return the posted values in $_POST['skopeCustomized'][$skope_id]
        function _get_unsanitized_skope_posted_values( $skope_id = null ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();

            global $wp_customize;
            $skopes_customized_values = array();
            $post_values = array();
            //if a skope id is specified
            if ( ! is_null( $skope_id ) ) {
                if ( ! isset( $_POST['skopeCustomized'] ) || ! isset( $_POST['skope'] ) ) {
                    $post_values = array();
                } else {
                  $skopes_customized_values = json_decode( wp_unslash( $_POST['skopeCustomized'] ), true );
                }

                //did we already cache the _skope_post_values ?
                if ( ! isset( $wp_customize -> _current_skope_id ) ) {
                    $wp_customize -> _current_skope_id = $skope_id;
                }

                //did we already cache the _skope_post_values ?
                //OR
                //did the current skope id change ?
                //if so let's cache or re-cache the skope_post_values
                if ( ! isset( $wp_customize -> _skope_post_values ) || $wp_customize -> _current_skope_id != $skope_id ) {
                    //add the _skope_post_values as a property of the manager if not set yet
                    if ( is_array( $skopes_customized_values ) ) {
                        $wp_customize -> _skope_post_values = $skopes_customized_values;
                    } else {
                        $wp_customize -> _skope_post_values = array();
                    }
                }

                $skopes_customized_values = $wp_customize -> _skope_post_values;

                if ( ! is_array( $skopes_customized_values ) || ! isset( $skopes_customized_values[$skope_id]) ) {
                    $post_values = array();
                } else {
                    $post_values = $skopes_customized_values[$skope_id];
                }
            }
            //if no skope_id specified, let's fall back on the regular way
            else {
                if ( isset( $_POST['customized'] ) ) {
                    $post_values = json_decode( wp_unslash( $_POST['customized'] ), true );
                } else {
                    $post_values = array();
                }
            }
            return $post_values;
        }





        //@the equivalent of post_value() for skope
        //recursive : implements the skope inheritance
        //@param $skope_id string
        //@param $setting = setting instance object
        //@default = default setting value ( string, boolean, array )
        function _get_setting_sanitized_skoped_customized_value( $skope_id = null, $setting, $do_inherit = false ) {
            $_candidate_val    = '_not_customized_';
            $parent_skope_id   = '';
            $skope_id          = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;

            if ( false == $skope_id || is_null($skope_id) || empty($skope_id) ) {
              ha_error_log( 'missing skope_id in _get_setting_sanitized_skoped_customized_value()' );
              return $_candidate_val;
            }

            $customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );
            // if ( 'hu_theme_options[copyright]' == $setting -> id ) {
            //   ha_error_log( 'IN GET SETTING SANITIZED SKOPED CUSTOMIZED VALUE' . $setting -> id );
            //   ha_error_log( print_R($customized_values,TRUE) );
            // }

            if ( array_key_exists( $setting -> id, $customized_values ) ) {
                $_candidate_val = $customized_values[ $setting->id ];
                $valid = $setting->validate( $_candidate_val );
                if ( is_wp_error( $valid ) ) {
                    return $_candidate_val;
                }
                $_candidate_val = $setting -> sanitize( $_candidate_val );
            }
            //no customized value for this post, so let's recursively get the parent dirtyness
            else if ( $do_inherit ) {
                $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );

                //if no parent, then we've reach the global level
                //if a parent exists, let's get it's customized value
                if ( false !== $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                    //@param : recursive call, inheritance set to true
                    $_candidate_val = $this -> _get_setting_sanitized_skoped_customized_value( $parent_skope_id , $setting, true );
                }
            }

            return ( is_null( $_candidate_val ) || is_wp_error( $_candidate_val ) ) ? '_not_customized_' : $_candidate_val;
        }



        //@param $requested_skope_id : string
        //@param $requested_skope_level : string
        //Given a requested $skope_id, return its parent if exists, based on :
        //1) the $_POST['current_skopes'] array()
        //2) the skope hierarchy : 'local' < 'group' < 'special_group' < 'global'
        //
        //The $_POST[current_skopes] is an array( 'level_1' => 'id_1', ... ) like :
        // $current_skopes => Array
        // (
        //     'global' => array( id => global__all_, opt_name =>  hu_theme_options )
        //     'group' => array( id => group_all_post, opt_name =>   hueman_czr_all_post )
        //     'local' => array( id => local_post_post_1, opt_name =>  hueman_czr_post_post_1 )
        // )
        //Can be recursive when some level(s) does not exists in the current set of skopes
        function _get_parent_skope_id( $requested_skope_id = null, $requested_skope_level = null ) {
            $parent_id = '';
            $_posted_skopes = $this -> _get_posted_skopes();

            if ( ! is_array( $_posted_skopes ) )
              return;

            if ( 'global' === $requested_skope_level ) {
              return isset($_posted_skopes['global']['id']) ? $_posted_skopes['global']['id'] : false;
            }

            //let's determine the requested skope level if not requested
            if ( is_null( $requested_skope_level ) ) {
              foreach ( $_posted_skopes as $skp_level => $data ) {
                if ( ! isset( $data['id'] ) )
                  continue;

                if ( $requested_skope_id == $data['id'] )
                  $requested_skope_level = $skp_level;
              }
              if ( ! isset( $requested_skope_level ) )
                return;
            }


            $hierark = array( 'local', 'group', 'special_group', 'global' );
            $parent_ind = -1;
            //get the parent index
            foreach( $hierark as $_key => $_skp_levl ) {
                if ( $requested_skope_level == $_skp_levl && -1 == $parent_ind )
                  $parent_ind = intval( $_key + 1 ) ;
                continue;
            }

            $parent_skop_level = isset( $hierark[$parent_ind] ) ? $hierark[$parent_ind] : '_reached_the_roof_';
            if ( '_reached_the_roof_' == $parent_skop_level || $parent_ind >= count( $hierark ) )
              return '_reached_the_roof_';
            else {
              if ( ! isset( $_posted_skopes[$parent_skop_level] ) ) {
                //we're typically in the case where we did not reached the roof and the level does not exists in the current set of skopes
                //For example, on home, there's no 'group' and 'special_group'
                //let's recursively solve this, this time by specifying a level
                $grand_parent_level = intval( $parent_ind + 1 );
                return $this -> _get_parent_skope_id( null, $hierark[$grand_parent_level] );
              } else {
                return isset($_posted_skopes[$parent_skop_level]['id']) ? $_posted_skopes[$parent_skop_level]['id'] : false;
              }
            }

        }


        //@return the array of the current set of posted skopes
        function _get_posted_skopes() {
          if ( ! isset( $_POST['current_skopes'] ) )
              return;
          return json_decode( wp_unslash( $_POST['current_skopes']), true );
        }



        //@return a skope db meta from the $_POST['current_skopes'], given its given its id
        function _get_posted_skope_metakey( $skope_id ) {
          if ( ! isset( $_POST['current_skopes'] ) )
              return;

          $meta_key = false;
          $posted_skopes = $this -> _get_posted_skopes();
          foreach ( $posted_skopes as $key => $data ) {
            if ( isset( $data['id'] ) && $skope_id == $data['id'] )
              $meta_key = isset( $data['opt_name'] ) ? $data['opt_name'] : false;
            continue;
          }
          return ( ! empty( $meta_key ) && is_string( $meta_key ) ) ? $meta_key : false;
        }




















        /* ------------------------------------------------------------------------- *
        * HELPERS
        /* ------------------------------------------------------------------------- */
        public function _is_value_customized( $value ) {
            //cast to array if needed
            $value = is_object($value) ? (array)$value : $value;
            //some multidimensional settings can be array, and therefore not be compared with a '_not_customized_' string
            if ( is_array($value) )
              return true;

            return ( is_string($value) && '_not_customized_' == $value ) ? false : true;
        }


        //@return the name of the option as a string for a given skope
        function ha_get_skope_opt_name( $level = 'local', $special = '' ) {
            $name = '__not_available__';
            $skp_type = ha_get_skope('type');
            $theme_name = ha_get_skope_theme_name();
            switch ($level) {
              case 'local':
                $name = strtolower( $theme_name . '_czr_' . ha_get_skope() );
                break;
              case 'group' :
                if ( ! empty( $skp_type ) )
                  $name = strtolower( $theme_name . '_czr_all_' . $skp_type );
                break;
              case 'special_group' :
                $name = strtolower( $theme_name . '_czr_all_' . $skp_type . $special );
                break;
              case 'global':
                $name = HU_THEME_OPTIONS;
                break;
            }
            return $name;
        }

        //@return the current posted skope string
        //falls back on 'global'
        function ha_get_current_customized_skope() {
            if ( '__not_posted__' ==  $this -> ha_get_sanitized_post_value( 'skope' ) )
              return 'global';
            return $this -> ha_get_sanitized_post_value( 'skope' );
        }

        //@return the level ( local, group, special_group, global ) for a given skope id
        function ha_get_skope_level( $skope_id ) {
            $_posted_skopes = $this -> _get_posted_skopes();
            $skope_level = false;
            if ( ! is_array( $_posted_skopes ) )
              return;
            foreach ( $_posted_skopes as $skp_level => $data ) {
              if ( ! isset( $data['id'] ) && false !== $skope_level )
                continue;

              if ( $skope_id == $data['id'] )
                $skope_level = $skp_level;
            }
            return $skope_level;
        }

        //@return a sanitized esc_attr() value from the $_POST array
        function ha_get_sanitized_post_value( $param ) {
            return isset($_POST[$param]) ? esc_attr( $_POST[$param ] ) : '__not_posted__';
        }

        //@return updated option associative array( opt_name1 => value 1, opt_name2 => value2, ... )
        public function ha_preprocess_skope_val( $new_value, $opt_name, $current_value ) {
              if ( ! $current_value || ! is_array($current_value) ) {
                $to_return = array( $opt_name => $new_value );
              } else {
                $to_return = $current_value;
                $to_return[$opt_name] = $new_value;
              }
              return $to_return;
        }

    }//class
endif;

?><?php
/**
 * This Class is instantiated on 'hu_hueman_loaded', declared in /init-core.php
 * 'hu_hueman_loaded' is fired in setup_theme
 */
if ( ! class_exists( 'HA_Skop_Option' ) ) :
    final class HA_Skop_Option extends HA_Skop_Option_Preview {


        public static function ha_skop_opt_instance() {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof HA_Skop_Option ) )
              self::$instance = new HA_Skop_Option();
            return self::$instance;
        }


        //GET MULTIDIMENSIONAL PREVIEW VAL
        //For each settings aggregated in a multidimensional option,
        //We need to perform the following actions :
        function _get_multidim_sanitized_preview_val( $_original_val , $opt_name , $skope_id = null, $child_value = null ) {
            $child_value              = ( is_null( $child_value ) || ! is_array( $child_value ) ) ? array() : $child_value;
            $_candidate_val           = $child_value;

            //let's set the setting_id to the opt_name by default
            $setting_id   = $opt_name;
            $skope_id     =  is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            if ( '__not_posted__' == $skope_id ) {
              ha_error_log('in _get_multidim_preview_val SKOPE NOT POSTED');
              return $_candidate_val;
            }
            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   //ha_error_log( 'MULTIDIM PREVIEW VAL FOR SKOPE ' . $skope_id);
            //   //ha_error_log( print_R( $_skope_customized_val, true ) );
            //   //ha_error_log(  print_R( $_candidate_val, true ) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }
            // ha_error_log( 'CHILD VALUE FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_candidate_val, true ) );

            $cust_skope               = $this -> ha_get_skope_level( $skope_id );
            $_skope_customized_val    = $this -> _get_wp_multidim_sanitized_customized_val( $opt_name, $skope_id, false );
            $_skope_db_meta_val       = $this -> _get_wp_multidim_saved_val( $opt_name, $skope_id, false );
            //$preview_val              = array();
            //1) get customized val of the opt_name for the current skope, without inheritance.
            //=> we get a customized_values arrays
            //2) then merge this array with the saved DB values for this skope.

            //BUILD THE FIRST SET OF VALUES WITH CURRENT SKOPE CUSTOMIZED VAL MERGED WITH CURRENT SKOPE SAVED VAL
            $_skope_customized_val    = ! is_array( $_skope_customized_val ) ? array() : $_skope_customized_val;
            $_skope_db_meta_val       = ! is_array( $_skope_db_meta_val ) ? array() : $_skope_db_meta_val;
            $_skope_val               = wp_parse_args( $_skope_customized_val, $_skope_db_meta_val );

            // ha_error_log( 'MULTIDIM CUSTOMIZED VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_skope_customized_val, true ) );

            // ha_error_log( 'MULTIDIM SAVED VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_skope_db_meta_val, true ) );

            // ha_error_log( 'MULTIDIM SKOPE VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_skope_val, true ) );
            //Child value shall overwrite the parent
            $_candidate_val           = wp_parse_args( $_candidate_val, $_skope_val );

            $parent_skope_id          = $this -> _get_parent_skope_id( $skope_id );
            //ha_error_log('ALORS PARENT SKOPE ID : ' . $parent_skope_id );
            //if a parent exists, let's get attempt to merge any additional inherited value
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $_candidate_val       = $this -> _get_multidim_sanitized_preview_val( $_original_val, $opt_name, $parent_skope_id, $_candidate_val );
            }

            //Fall back on the original val ( => should be the same as the one of the global skope ) if we still did not get anything at this stage
            if ( is_null( $_candidate_val ) || empty( $_candidate_val ) || is_wp_error( $_candidate_val ) || ! is_array( $_candidate_val ) )
                $_candidate_val = is_array( $_original_val ) ? $_original_val : array();

            // ha_error_log( 'OPT NAME : ' . $opt_name );
            // ha_error_log( 'MULTIDIM FINAL VAL FOR SKOPE : ' . $skope_id );
            // ha_error_log( print_R( $_candidate_val, true ) );

            return $_candidate_val;
        }












        public function _get_wp_multidim_saved_val( $opt_name, $skope_id = null, $do_inherit = false, $child_value = array() ) {
            //ha_error_log('ALORS MULTIDIM SAVED VAL. Skope : ' . $skope_id );
            $skope          = $this -> ha_get_skope_level( $skope_id );


            //For global skope, the multidim theme_mods like nav_menu_locations are stored in option 'theme_mod_hueman' => array( 'na_menu_location' => array( ... ) )
            if ( 'global' == $skope && ha_is_wp_builtin_skoped_theme_mod( $opt_name ) ) {
              $theme = get_option( 'stylesheet' );
              $_raw_theme_mods = ha_get_raw_option( "theme_mods_{$theme}" );
              $skop_opt_val = isset( $_raw_theme_mods[$opt_name] ) ? $_raw_theme_mods[$opt_name] : array();
            } else {
              $skop_opt_val   = $this -> ha_get_cached_opt( $skope, $opt_name );
            }

            // ha_error_log('ALORS MULTIDIM SAVED VAL. Skope_level : ' . $skope );
            // ha_error_log( print_R( $skop_opt_val, true ) );
            //cast to array if the saved option is an object. For Ex : header_image_data can be an object
            $skop_opt_val   = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;
            if ( ! is_array( $skop_opt_val ) )
              $skop_opt_val = array();

            //if not inheriting, bail here and return the skope saved val
            if ( ! $do_inherit ) {
              return $skop_opt_val;
            }

            //initialize the candidates with the child values
            $_val_candidate = $child_value;

            //only keep the values that are not already saved in the child skope
            foreach ( $skop_opt_val as $_key => $_value ) {
              $_val_candidate[$_key] = ! isset( $child_value[$_key] ) ? $_value : $child_value[$_key];
            }


            $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
            //ha_error_log('ALORS PARENT SKOPE ID : ' . $parent_skope_id );
            //if a parent exists, let's get attempt to merge any additional inherited value
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $_val_candidate = $this -> _get_wp_multidim_saved_val( $opt_name, $parent_skope_id, true, $_val_candidate );
            }

            if ( is_null( $_val_candidate ) || empty( $_val_candidate ) || is_wp_error( $_val_candidate ) || ! is_array( $_val_candidate ) )
                $_val_candidate = array();

            return $_val_candidate;
        }











        function _get_wp_multidim_sanitized_customized_val( $opt_name, $skope_id = null, $do_inherit = false, $child_value = array() ) {

            global $wp_customize;
            $skope_id           = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            $_customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );
            $_val_candidate     = $child_value;

            ////////////////////////////////////////////////////////////////
            // if ( 'nav_menu_locations' === $opt_name ) {
            //   ha_error_log( 'MULTIDIM CUST VAL FOR SKOPE ' . $skope_id);
            //   //ha_error_log( print_R( $_skope_customized_val, true ) );
            //   ha_error_log( print_R( $_customized_values, true) );
            //   //ha_error_log( $this -> _is_value_customized( $_skope_customized_val ) );
            // }

            //make sure customized values is always an array
            if ( false === $_customized_values || ! is_array( $_customized_values ) )
              $_customized_values = array();

            //find an opt_name prefix match in the $_customized_values
            foreach ( $_customized_values as $_setting_id => $_setting_value ) {
                //try to find a prefix match option. For ex : sidebars_widgets
                if ( $opt_name != substr($_setting_id, 0, strlen( $opt_name ) ) )
                  continue;
                //the setting candidate has to be registered
                if ( ! is_object( $wp_customize -> get_setting( $_setting_id ) ) )
                  continue;
                $_setting             = $wp_customize -> get_setting( $_setting_id );
                $id_data              = $_setting -> id_data();
                $multi_dim_keys       = $id_data['keys'];
                $is_multidimensional  = ! empty( $multi_dim_keys );
                $setting_id           = $_setting -> id;
                //at this stage we should have only multidimensional settings. Let's check again.
                if ( ! $is_multidimensional )
                  continue;

                $valid = $_setting -> validate( $_setting_value );
                if ( is_wp_error( $valid ) ) {
                    ha_error_log( 'in _get_wp_multidim_sanitized_customized_val, invalid value for setting' . $_setting_id );
                    continue;
                }
                $_setting_value = $_setting -> sanitize( $_setting_value );

                //Add the current skope value only if the child is not customized
                foreach ( $multi_dim_keys as $_k ) {
                    $_val_candidate[$_k] = ! isset( $child_value[$_k] ) ? $_setting_value : $child_value[$_k];
                }
            }

            if ( $do_inherit ) {
                $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
                //if a parent exists, let's get it's customized value
                if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                    $_val_candidate = $this -> _get_wp_multidim_sanitized_customized_val( $opt_name, $parent_skope_id, true, $_val_candidate );
                }
            }

            if ( is_null( $_val_candidate ) || empty( $_val_candidate ) || is_wp_error( $_val_candidate ) )
                $_val_candidate = array();

            return $_val_candidate;
        }


    }//class
endif;

?><?php

/* ------------------------------------------------------------------------- *
 *  LOAD
/* ------------------------------------------------------------------------- */
function HA_SKOP_OPT() {
    return HA_Skop_Option::ha_skop_opt_instance();
}

if ( defined('CZR_DEV') && true === CZR_DEV ) {
    require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-0-init-base.php' );
}

add_action('hu_hueman_loaded', 'ha_load_skop_options');
function ha_load_skop_options() {
    if ( defined('CZR_DEV') && true === CZR_DEV ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-base.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-front-end-value.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-preview-value.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-options-x-final.php' );
    }
    HA_SKOP_OPT();
}


if ( defined('CZR_DEV') && true === CZR_DEV ) {
    require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-customize-register.php' );
}
require_once( HA_BASE_PATH . 'addons/skop/tmpl/skope-tmpls.php' );

new HA_Skop_Cust_Register();

//Customizer Ajax : we must for Hueman to be loaded (some Hueman constants are used)
add_action('hu_hueman_loaded', 'ha_load_skop_ajax');

add_action('init', 'ha_load_skop_customizer_preview' );
function ha_load_skop_customizer_preview() {
    //CUSTOMIZE PREVIEW : export skope data
    if ( HU_AD() -> ha_is_customize_preview_frame() ) {
        if ( defined('CZR_DEV') && true === CZR_DEV ) {
            require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-customize-preview.php' );
        }
        new HA_Skop_Cust_Prev();
    }
}


//hook : 'hu_hueman_loaded'
function ha_load_skop_ajax() {
    if ( defined('CZR_DEV') && true === CZR_DEV ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-base.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-publish.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-save.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-reset.php' );
    }
    new HA_Skop_Chset_Reset();
}
if ( defined('CZR_DEV') && true === CZR_DEV ) {
    if ( apply_filters('ha_print_skope_logs' , true ) ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/_dev_skop-logs.php' );
        function ha_instantiate_dev_logs() {
            if ( class_exists( 'HA_skop_dev_logs') ) {
                new HA_skop_dev_logs(
                    array(
                        'hook' => '__header_after_container_inner',
                        'display_header' => true,
                        'tested_option' => 'header_image'
                    )

                );
            }
        }
        //add_action('hu_hueman_loaded', 'ha_instantiate_dev_logs', 100 );
    }
}

?>