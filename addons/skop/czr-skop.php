<?php
function ha_get_skope_theme_name() {
    $_theme                     = wp_get_theme();
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


/* ------------------------------------------------------------------------- *
 *  MAYBE REGISTER CHANGESET POST TYPE IF WP < 4.7
/* ------------------------------------------------------------------------- */
if ( ! post_type_exists( 'czr_skope_opt') ) {
    register_post_type( 'czr_skope_opt', array(
      'labels' => array(
        'name'               => _x( 'Hueman scopes', 'post type general name', 'hueman-addons' ),
        'singular_name'      => _x( 'Hueman scope', 'post type singular name', 'hueman-addons' ),
        'menu_name'          => _x( 'Hueman scopes', 'admin menu', 'hueman-addons' ),
        'name_admin_bar'     => _x( 'Hueman scope', 'add new on admin bar', 'hueman-addons' ),
        'add_new'            => _x( 'Add New', 'Customize Hueman scope', 'hueman-addons' ),
        'add_new_item'       => __( 'Add New Hueman scope', 'hueman-addons' ),
        'new_item'           => __( 'New Hueman scope', 'hueman-addons' ),
        'edit_item'          => __( 'Edit Hueman scope', 'hueman-addons' ),
        'view_item'          => __( 'View Hueman scope', 'hueman-addons' ),
        'all_items'          => __( 'All Hueman scopes', 'hueman-addons' ),
        'search_items'       => __( 'Search Hueman scopes', 'hueman-addons' ),
        'not_found'          => __( 'No Hueman scopes found.', 'hueman-addons' ),
        'not_found_in_trash' => __( 'No Hueman scopes found in Trash.', 'hueman-addons' ),
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
if ( ha_is_plugin_active('polylang/polylang.php') || ha_is_plugin_active('polylang-pro/polylang.php') ) {
  add_filter( 'ha_skope_navmenu', '__return_false' );
  add_filter( 'ha_get_skope_excluded_options', 'ha_exclude_skoped_settings_with_polylang' );
  function ha_exclude_skoped_settings_with_polylang( $excluded_list ) {
      if ( ! is_array( $excluded_list ) ) {
        return array();
      }
      $excluded_list[] = 'blogname';
      $excluded_list[] = 'blogdescription';
      return $excluded_list;
  }
}

/* ------------------------------------------------------------------------- *
 *  MULTISITE COMPAT
/* ------------------------------------------------------------------------- */
/*
* Exclude blog name from skope for multisite subsites
* https://github.com/presscustomizr/hueman-addons/issues/43
*/
if ( is_multisite() && ! is_main_site() ) {
  add_filter( 'ha_get_skope_excluded_options', 'ha_exclude_skoped_settings_with_multisite' );
  function ha_exclude_skoped_settings_with_multisite ( $excluded_list ) {
      if ( ! is_array( $excluded_list ) ) {
        return array();
      }
      return array_merge( $excluded_list, array( 'blogname' ) );
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
        'favicon',
        'featured-posts-include',
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
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'active_theme',
        'site_icon',
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
      array(
        'defaults',
        'ver',
        'has_been_copied',
        'last_update_notice',
        'last_update_notice_pro'
      )
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
function ha_prepare_skope_changeset_for_front_end( $data ) {
  global $wp_customize;
  $new_data = array();
  $multidimensionals = array();

  foreach ( $data as $raw_setting_id => $setting_data ) {
    if ( ! array_key_exists( 'value', $setting_data ) )
      continue;

    $setting_id = $raw_setting_id;
    if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {

        $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
        if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
          $setting_id = $matches['setting_id'];
        }
    }
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
        if ( $is_multidimensional && ! _ha_is_theme_multidimensional_setting( $setting -> id ) ) {
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
  return array_merge( $new_data, $multidimensionals );
}












/* ------------------------------------------------------------------------- *
 *  SKOPE MULTIDIM HELPERS : used in option class, ajax and preview
/* ------------------------------------------------------------------------- */
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
  $db_data = array();
  $defaults = array(
      'post_id'         => '',//<= can be our skope post id or the WP changeset post id
      'skope_meta_key'  => '',
      'level'           => '',
      'is_option_post'  => false
  );
  $args = wp_parse_args( $args, $defaults );

  if ( ! $args['post_id'] ) {
    return new WP_Error( 'empty_post_id' );
  }
  if ( true === $args['is_option_post'] ) {
      $skope_option_post = get_post( $args['post_id'] );
      if ( ! $skope_option_post ) {
        return new WP_Error( 'missing_skope_option_post' );
      }
      if ( 'czr_skope_opt' !== $skope_option_post->post_type ) {
        return new WP_Error( 'wrong_skope_option_post_type' );
      }
      if ( 'global' == $args['level'] ) {
        return new WP_Error( 'global_skope_can_not_be_get_from_the_published_skope_post' );
      }
      $db_data = get_post_meta( $args['post_id'] , $args['skope_meta_key'], true );

  } else {
      $changeset_post = get_post( $args['post_id'] );
      if ( ! $changeset_post ) {
        return new WP_Error( 'missing_changeset_post' );
      }

      if ( 'revision' === $changeset_post->post_type ) {
        if ( 'customize_changeset' !== get_post_type( $changeset_post->post_parent ) ) {
          return new WP_Error( 'wrong_post_type_for_changeset' );
        }
      } elseif ( 'customize_changeset' !== $changeset_post->post_type ) {
        return new WP_Error( 'wrong_post_type_for_changeset' );
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
  $db_data = ( ! is_array( $db_data ) || empty( $db_data ) || false === $db_data ) ? array() : $db_data;
  if ( function_exists( 'json_last_error' ) && json_last_error() ) {
    return new WP_Error( 'json_parse_error', '', json_last_error() );
  }
  if ( ! is_array( $db_data ) ) {
    return new WP_Error( 'expected_array_for_changeset_data' );
  }
  return $db_data;
}
function ha_get_skope_dyn_type( $meta_type ) {
  $_map = array(
    'post'    => 'post_meta',
    'tax'     => 'term_meta',
    'user'    => 'user_meta',
  );
  return isset( $_map[$meta_type] ) ? $_map[$meta_type] : 'trans';
}
function ha_get_dyn_types() {
  return apply_filters( 'ha_dyn_types',
    array('option','skope_meta')
  );
}


/**
* Return the current ctx. Front / Back agnostic.
* @param $_requesting_wot is a string with the follwing possible values : 'meta_type' (like post) , 'type' (like page), 'id' (like page id)
* @param $_return_string string param stating if the return value should be a string or an array
* @return a string of all concatenated ctx parts (default) 0R an array of the ctx parts
*/
function ha_get_skope( $_requesting_wot = null, $_return_string = true ) {
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
      if  ( false != $meta_type && false != $obj_id ) {
        $_return = array( "meta_type" => "{$meta_type}" , "type" => "{$type}", "id" => "{$obj_id}" );
      }
      else if ( false != $meta_type && ! $obj_id ) {
        $_return = array( "meta_type" => "{$meta_type}", "type" => "{$type}" );
      }
      else if ( false != $obj_id ) {
        $_return = array( "id" => "{$obj_id}" );
      }
    break;
  }
  if ( ! $_return_string ) {
    return $_return;
  }
  if ( ! is_array( $_return ) || ( is_array( $_return ) && empty( $_return ) ) )
    return '';
  if ( ! is_null($_requesting_wot) )
    return isset($_return[$_requesting_wot]) ? $_return[$_requesting_wot] : '';
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
  global $wp_the_query;
  if ( ! isset( $wp_the_query ) || empty( $wp_the_query ) )
    return array();

  $current_obj  = get_queried_object();
  $meta_type    = false;
  $type         = false;
  $obj_id       = false;


  if ( is_object( $current_obj ) ) {
      if ( isset($current_obj -> post_type) ) {
          $meta_type  = 'post';
          $type       = $current_obj -> post_type;
          $obj_id     = $current_obj -> ID;
      }
      if ( isset($current_obj -> taxonomy) && isset($current_obj -> term_id) ) {
          $meta_type  = 'tax';
          $type       = $current_obj -> taxonomy;
          $obj_id     = $current_obj -> term_id;
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
function ha_skope_has_no_group( $meta_type ) {
    return in_array(
      $meta_type,
      array( 'home', 'search', '404', 'date' )
    ) || is_post_type_archive();
}
function ha_skope_has_a_group( $meta_type ) {
    return in_array(
      $meta_type,
      array('post', 'tax', 'user')
    );
}
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
add_filter('hu_enable_singular_layout_meta_box', '__return_false');
add_filter( 'hu_layout_class', 'hu_skopify_layout_class' , 10, 2 );
function hu_skopify_layout_class( $layout, $has_post_meta ) {
  if ( ! is_singular() )
    return hu_get_option( 'layout-global' );

  global $post;
  $skopified_meta = get_post_meta( $post->ID,'_layout_skopified',true );
  $is_skopified = isset($skopified_meta) && true == $skopified_meta;
  if ( ! $has_post_meta || $is_skopified ) {
    return hu_get_option( 'layout-global' );
  }

  return $layout;
}
add_action( 'wp', 'hu_skopify_singular_layout', 0 );
function hu_skopify_singular_layout() {
  if ( ! current_user_can( 'edit_theme_options' ) )
    return;
  if ( ! is_singular() )
    return;

  global $post;

  $old_meta_val = get_post_meta( $post->ID,'_layout',true );
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
  update_post_meta( $skope_post_id, $meta_key, $skope_meta_values );
  update_post_meta( $post->ID, '_layout_skopified', true );
}
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
add_filter( 'hu_partial_refresh_on', '__return_true');





/* ------------------------------------------------------------------------- *
 *  @4.9compat
 * the changeset_post_id might be the one of the autosave, which is not $wp_customize->changeset_post_id();
/* ------------------------------------------------------------------------- */
function ha_get_real_wp_changeset_post_id() {
  global $wp_customize;
  $changeset_post_id = $wp_customize->changeset_post_id();
  return $changeset_post_id;
}







/*****************************************************
* ADMIN CONTEXT
*****************************************************/
function ha_get_admin_ctx() {
    if ( ! is_admin() )
      return array();

    global $tag;
    $current_screen = get_current_screen();
    $post           = get_post();
    $meta_type      = false;
    $type           = false;
    $obj_id         = false;
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
         *  SAVE SKOPE CHANGESET
         *  => as a meta of the _temp changeset post if status != "publish"
         *  => publishing as a post meta of the skope post will be handled when the WP customize_changeset post will transition to "publish"
         *  @see add_action( 'transition_post_status', 'ha_publish_skope_changeset_metas_on_post_status_transition', 0, 3 );
        /* ------------------------------------------------------------------------- */
        /**
          * Handle customize_skope_changet_save WP Ajax request to save/update a changeset.
         *
         */
        function ha_ajax_skope_changeset_save() {
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
            $action = 'save-customize_' . $wp_customize->get_stylesheet();
            if ( ! check_ajax_referer( $action, 'nonce', false ) ) {
                wp_send_json_error( 'invalid_nonce' );
            }
            $is_publish = null;
            $changeset_status = null;
            if ( isset( $_POST['customize_changeset_status'] ) ) {
                $changeset_status = wp_unslash( $_POST['customize_changeset_status'] );
                if ( ! get_post_status_object( $changeset_status ) || ! in_array( $changeset_status, array( 'draft', 'pending', 'publish', 'future' ), true ) ) {
                    wp_send_json_error( 'bad_customize_changeset_status', 400 );
                }
                $is_publish = 'publish' === $changeset_status;
                if ( $is_publish && HU_AD() -> ha_is_changeset_enabled() ) {
                    if ( ! current_user_can( get_post_type_object( 'customize_changeset' )->cap->publish_posts ) ) {
                        wp_send_json_error( 'changeset_publish_unauthorized', 403 );
                    }
                    if ( false === has_action( 'transition_post_status', '_wp_customize_publish_changeset' ) ) {
                        wp_send_json_error( 'missing_publish_callback', 500 );
                    }
                }
            }
            if ( ! $is_publish && ! HU_AD() -> ha_is_changeset_enabled() ) {
              wp_send_json_error( 'changeset_feature_is_not_enabled' );
            }


            $changeset_post_id = ha_get_real_wp_changeset_post_id();
            if ( $changeset_post_id && in_array( get_post_status( $changeset_post_id ), array( 'publish', 'trash' ) ) ) {
                wp_send_json_error( 'changeset_already_published' );
            }
            if ( ! empty( $_POST['customize_changeset_data'] ) ) {
                $input_changeset_data = json_decode( wp_unslash( $_POST['customize_changeset_data'] ), true );
                if ( ! is_array( $input_changeset_data ) ) {
                    wp_send_json_error( 'invalid_customize_changeset_data' );
                }
            } else {
                $input_changeset_data = array();
            }
            $r = $this -> _save_skope_changeset_metas( array(
                'status' => $changeset_status,
                'data' => $input_changeset_data,
            ) );

            if ( is_wp_error( $r ) ) {
              $response = $r->get_error_data();
            } else {
                $response = $r;
                if ( HU_AD() -> ha_is_changeset_enabled() ) {
                    $response['changeset_status'] = get_post_status( ha_get_real_wp_changeset_post_id() );
                }
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
                  $validities[ $setting_id ] = new WP_Error( 'unrecognized', __( 'Setting does not exist or is unrecognized.', 'hueman-addons' ) );
                }
                continue;
              }
              if ( $options['validate_capability'] && ! current_user_can( $setting->capability ) ) {
                $validity = new WP_Error( 'unauthorized', __( 'Unauthorized to modify setting due to capability.', 'hueman-addons' ) );
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
                $validity = new WP_Error( 'invalid_value', __( 'Invalid value.', 'hueman-addons' ) );
              }
              $validities[ $setting_id ] = $validity;
            }
            return $validities;
          }
    }//class
endif;

?><?php
/* ------------------------------------------------------------------------- *
 *  SAVE SKOPE CHANGESET
 *  => as a meta of the _temp changeset post if status != "publish"
/* ------------------------------------------------------------------------- */
if ( ! class_exists( 'HA_Skop_Chset_Save' ) ) :
    class HA_Skop_Chset_Save extends HA_Skop_Chset_Base  {

        function __construct() {
          parent::__construct();
        }

        function _save_skope_changeset_metas( $args = array() ) {
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
            $changeset_post_id = ha_get_real_wp_changeset_post_id();

            $existing_changeset_data = array();

            if ( ! $changeset_post_id )
                wp_send_json_error( 'No changet post id yet' );
            $response = array(
              'setting_validities'  => array()
            );

            $existing_changeset_data = array();
            if ( $changeset_post_id ) {
                $existing_changeset_data = ha_get_skope_db_data( array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    'is_option_post' => false )
                );
            }

            if ( is_wp_error( $existing_changeset_data ) ) {
              $response['changeset_post_save_failure'] = $existing_changeset_data->get_error_code();
              return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
            }
            $update_transactionally = (bool) $args['status'];
            $allow_revision = (bool) $args['status'];
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
            if ( empty( $customized_data) )
              return wp_send_json_success( $response );

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
            $setting_validities = array();
            $customized_values = array();
            foreach ($customized_data as $__id => $__v ) {
                if ( ! array_key_exists('value', $__v ) )
                  continue;
                $customized_values[$__id] = $__v['value'];
            }
            if ( method_exists( $wp_customize, 'validate_setting_values' ) ) {
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
                  'message' => sprintf( _n( 'There is %s invalid setting.', 'There are %s invalid settings.', $invalid_setting_count, 'hueman-addons' ), number_format_i18n( $invalid_setting_count ) ),
                );
                return new WP_Error( 'transaction_fail', '', $response );
            }

            $response = array(
              'setting_validities'  => $setting_validities,
              'skope_meta_key'      => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name'),
              'skope_id'            => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' )
            );
            $data = $existing_changeset_data;
            if ( is_wp_error( $data ) ) {
              $data = array();
            }
            foreach ( $customized_data as $setting_id => $setting_params ) {
                $setting = $wp_customize->get_setting( $setting_id );
                if ( ! $setting || ! $setting->check_capabilities() ) {
                    ha_error_log( 'In _save_or_publish_skope_changeset_metas, ' . $setting_id . ' is not registered in $wp_customize.' );
                    continue;
                }
                if ( isset( $setting_validities[ $setting_id ] ) && is_wp_error( $setting_validities[ $setting_id ] ) ) {
                    continue;
                }
                $changeset_setting_id = $setting_id;
                if ( 'theme_mod' === $setting->type ) {
                    $theme_name = $wp_customize->get_stylesheet();
                    if ( $theme_name != substr( $setting_id, 0, strlen($theme_name) ) )
                      $changeset_setting_id = sprintf( '%s::%s', $wp_customize->get_stylesheet(), $setting_id );
                }

                if ( null === $setting_params ) {
                    unset( $data[ $changeset_setting_id ] );
                } else {
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
            $data['skope_infos'] = array(
                'skope_id'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id'),
                'level_id'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'level_id'),
                'skope'     => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope'),
                'obj_id'    => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'obj_id'),
                'meta_key'  => HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name')
            );
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
            add_action( 'wp_ajax_czr_changeset_setting_reset',  array( $this, 'ha_ajax_reset_changeset_setting' ) );
            add_action( 'wp_ajax_czr_changeset_skope_reset',    array( $this, 'ha_ajax_reset_changeset_skope' ) );
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
            $changeset_post_id = ha_get_real_wp_changeset_post_id();

            if ( ! $changeset_post_id ) {
              wp_send_json_success( 'No changet post id' );
              return;
            }
            if ( ! $wp_customize -> is_theme_active() ) {
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
            $changeset_post_id = ha_get_real_wp_changeset_post_id();

            if ( ! $changeset_post_id ) {
              wp_send_json_success( 'No changet post id' );
              return;
            }
            if ( ! $wp_customize -> is_theme_active() ) {
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
            $short_opt_name             = _ha_extract_setting_name($setting_id);
            $dependant_setting_id       = $this -> ha_get_setting_dependants( $short_opt_name );
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
                if ( ! $is_theme_authorized_option )
                  return wp_send_json_error('This WordPress option can not be removed at a site wide level');//@to_translate
                $setting_id = $short_opt_name;
                $published_values = hu_get_raw_option( null, HU_THEME_OPTIONS );
            } else {
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
                                $multi_dim_key = str_replace(array('[', ']', $_id ), '', $mult_set_id);
                                $new_values[$_id][$multi_dim_key] = $mult_val;
                            }
                        }
                    }
                }
            }

            if ( 'global' == $skope_level ) {
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
                    if ( ! in_array( $_id, $theme_setting_list ) ) {
                      $new_values[$_id] = $setting_params;
                    }
                    if ( ha_is_option_protected( $_id ) ) {
                      $new_values[$_id] = $setting_params;
                    }
                }
                $attempt = update_option( HU_THEME_OPTIONS, $new_values );
            } else {
                $changeset_post_id  = get_option('skope-post-id');
                if ( false === $changeset_post_id || empty( $changeset_post_id ) ) {
                    wp_send_json_error( 'missing skope_post_id when attempting to reset the skope meta value' );
                }
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
            $skope_meta_key    = HA_SKOP_OPT() -> _get_posted_skope_metakey( $skope_id );
            if ( false == $skope_meta_key ) {
              ha_error_log( 'no meta key found in get_unsanitized_skope_changeset() for skope : ' . $skope_id );
              return array();
            }
            $changeset_post_id = ha_get_real_wp_changeset_post_id();
            $changeset_data = ha_get_skope_db_data(
                array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    'level' => HA_SKOP_OPT() -> ha_get_skope_level( $skope_id )
                )
            );

            if ( is_wp_error( $changeset_data ) ) {
                ha_error_log( 'Error when trying to get the changeset data in get_unsanitized_skope_changeset() for skope : ' . $skope_id );
                $changeset_data = array();
            }
            foreach ( $changeset_data as $raw_setting_id => $setting_data ) {
                if ( ! is_array( $setting_data ) || ( 'blogname' != $raw_setting_id && ! array_key_exists( 'value', $setting_data ) ) ) {
                  ha_error_log( 'Problem in get_unsanitized_skope_changeset, the setting_data of the changeset are not well formed for skope : ' . $skope_id );
                  continue;
                }

                $setting_id = $raw_setting_id;
                if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {
                    $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                    if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                      $setting_id = $matches['setting_id'];
                    }
                }
                $values[$setting_id] = $setting_data;
            }
            return $values;
        }


        /* ------------------------------------------------------------------------- *
         *  RESET SKOPE : _DEPRECATED
        /* ------------------------------------------------------------------------- */

    }//class
endif;

?><?php

if ( ! class_exists( 'HA_Skop_Cust_Prev' ) ) :
    final class HA_Skop_Cust_Prev {
        public $changeset_post_id;//<= ha_get_real_wp_changeset_post_id();

        function __construct() {
            /* ------------------------------------------------------------------------- *
             *  CUSTOMIZE PREVIEW : export skope data
            /* ------------------------------------------------------------------------- */
            add_action( 'wp_footer', array( $this, 'ha_print_server_skope_data' ), 30 );

            $this->changeset_post_id = ha_get_real_wp_changeset_post_id();
        }
        function ha_print_server_skope_data() {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return;

            global $wp_query, $wp_customize;
            $_meta_type = ha_get_skope( 'meta_type', true );
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
        function _ha_is_changeset_dirty() {
            if ( ! HU_AD() -> ha_is_changeset_enabled() )
              return false;

            global $wp_customize;
            $changeset_post_id = $this->changeset_post_id;

            if ( false == $changeset_post_id || empty( $changeset_post_id ) )
              return;

            $_level_list = array( 'global', 'group', 'special_group', 'local' );
            $is_dirty = false;
            foreach ( $_level_list as $level ) {
              $_changeset_data = ha_get_skope_db_data( array( 'post_id' => $changeset_post_id, 'skope_meta_key' => null, 'level' => $level ) );
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
        function _ha_get_json_export_ready_skopes() {
            $skopes = array();
            $_meta_type = ha_get_skope( 'meta_type', true );
            $defaults = _ha_get_default_scope_model();

            $saved_glob_opt = $this -> _ha_get_sanitized_skoped_saved_global_options();
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
            $changeset_post_id = $this->changeset_post_id;

            if ( false != $changeset_post_id && ! empty( $changeset_post_id ) ) {
              $skope_changeset_val = ha_get_skope_db_data( array( 'post_id' => $changeset_post_id, 'skope_meta_key' => $skope_meta_key, 'level' => $level ) );
            } else {
              ha_error_log('NO CHANGESET POST AVAILABLE in _ha_get_api_ready_skope_changeset when getting changset for skope : ' . $level );
              return array();
            }

            $skope_changeset_val = ! is_array( $skope_changeset_val ) ? array() : $skope_changeset_val;
            $theme_setting_list = HU_utils::$_theme_setting_list;
            $theme_option_group = HU_THEME_OPTIONS;

            $api_ready_changeset_val = array();
            foreach ( $skope_changeset_val as $raw_setting_id => $setting_params ) {
                if ( ! is_array( $setting_params ) || ! array_key_exists('value', $setting_params ) )
                  continue;
                $setting_value = $setting_params['value'];
                $setting_type = isset( $setting_params['type'] ) ? $setting_params['type'] : 'option';
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
                if ( $is_theme_mod_setting ) {
                    if ( $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                      $actual_setting_id = $matches['setting_id'];
                    }
                } else {
                    $actual_setting_id = $raw_setting_id;
                }
                if ( is_object( $wp_customize -> get_setting( $actual_setting_id ) ) ) {
                  $setting_value = apply_filters( "customize_sanitize_js_{$actual_setting_id}", $setting_value, $wp_customize -> get_setting( $actual_setting_id ) );
                }
                if ( in_array( $actual_setting_id, $theme_setting_list ) ) {
                    $czr_opt_id = "{$theme_option_group}[{$actual_setting_id}]";
                    if ( is_object( $wp_customize -> get_setting( $czr_opt_id ) ) ) {
                      $setting_value = apply_filters( "customize_sanitize_js_{$czr_opt_id}", $setting_value, $wp_customize -> get_setting( $czr_opt_id ) );
                    }
                }
                $api_ready_changeset_val[$actual_setting_id] = $setting_value;

            }

            return $api_ready_changeset_val;
        }











        /* ------------------------------------------------------------------------- *
         *  GET OPTIONS SAVED IN DB
        /* ------------------------------------------------------------------------- */
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
                if ( ! _ha_is_wp_setting_multidimensional( $actual_setting_id ) ) {
                    if ( is_object( $wp_customize -> get_setting( $actual_setting_id ) ) ) {
                      $setting_value = apply_filters( "customize_sanitize_js_{$actual_setting_id}", $setting_value, $wp_customize -> get_setting( $actual_setting_id ) );
                    }
                    if ( in_array( $actual_setting_id, $theme_setting_list ) ) {
                        $czr_opt_id = "{$theme_option_group}[{$actual_setting_id}]";
                        if ( is_object( $wp_customize -> get_setting( $czr_opt_id ) ) ) {
                            $setting_value = apply_filters( "customize_sanitize_js_{$czr_opt_id}", $setting_value, $wp_customize -> get_setting( $czr_opt_id ) );
                        }
                    }
                    $api_ready_db_val[$actual_setting_id] = $setting_value;
                }
                else {
                    $to_merge = _ha_build_multidimensional_db_option( $actual_setting_id, $setting_value );
                    foreach( $to_merge as $set_id => $value ) {
                        if ( is_object( $wp_customize -> get_setting( $set_id ) ) ) {
                            $value = apply_filters( "customize_sanitize_js_{$set_id}", $value, $wp_customize -> get_setting( $set_id ) );
                        }
                        $to_merge[$set_id] = $value;
                    }
                    $api_ready_db_val = array_merge( $api_ready_db_val, $to_merge );
                }
            }
            return $api_ready_db_val;
        }
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
                if ( in_array( $opt_name, HU_utils::$_theme_setting_list ) ) {
                  $_theme_option_prefix = strtolower(HU_THEME_OPTIONS);
                  $opt_name = "{$_theme_option_prefix}[{$opt_name}]";
                }

                $_saved_opts[$opt_name] = $value;
            }
            $regular_wp_builtins = array(
              'blogname',
              'blogdescription'
            );
            foreach ($regular_wp_builtins as $_opt ) {
                $_saved_opts[$_opt] = hu_get_raw_option( $_opt );
            }
            $authorized_theme_mods = ha_get_wp_builtins_skoped_theme_mods();
            $theme = get_option( 'stylesheet' );
            $_raw_theme_mods = hu_get_raw_option( "theme_mods_{$theme}" );

            foreach ( $authorized_theme_mods as $_theme_mod_name ) {
                if ( ! isset( $_raw_theme_mods[$_theme_mod_name] ) )
                    continue;

                if ( ! _ha_is_wp_setting_multidimensional( $_theme_mod_name ) ) {
                    $_saved_opts[$_theme_mod_name] =  $_raw_theme_mods[$_theme_mod_name];
                } else {
                    $to_merge = _ha_build_multidimensional_db_option( $_theme_mod_name, $_raw_theme_mods[$_theme_mod_name] );
                    foreach( $to_merge as $set_id => $value ) {
                        $to_merge[$set_id] = $value;
                    }
                    $_saved_opts = array_merge( $_saved_opts, $to_merge );
                }

            }
            $js_sanitized_saved_opts = array();

            foreach ( $_saved_opts as $cand_setting_id => $cand_setting_value ) {
                if ( is_object( $wp_customize -> get_setting( $cand_setting_id ) ) ) {
                    $sanitized_value = apply_filters( "customize_sanitize_js_{$cand_setting_id}", $cand_setting_value, $wp_customize -> get_setting( $cand_setting_id ) );
                    $js_sanitized_saved_opts[$cand_setting_id] = $sanitized_value;
                }
            }
            return $js_sanitized_saved_opts;
        }








        /* ------------------------------------------------------------------------- *
         *  CUTE LITTLE HELPERS
        /* ------------------------------------------------------------------------- */
        function _ha_is_option_set_to_default( $opt_name, $value, $defaults ) {
            if ( ! is_array( $defaults ) || ! array_key_exists( $opt_name, $defaults ) )
              return;
            if ( ! is_array( $value ) )
              return $value == $defaults[$opt_name];
            else {
                if ( is_array( $value ) && ! is_array( $defaults[$opt_name] ) )
                  return;
                else {
                  if ( empty( $defaults[$opt_name] ) )
                    return;
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
            add_action( 'customize_register' , array( $this, 'ha_alter_wp_customizer_settings' ) , 100, 1 );
            add_filter( 'hu_admin_sec'   , array( $this, 'ha_register_skop_infos_settings'));

            /* ------------------------------------------------------------------------- *
             *  CUSTOMIZE PANE : Add skope server params to the Hueman theme control server params ( serverControlParams global var)
            /* ------------------------------------------------------------------------- */
            add_filter( 'hu_js_customizer_control_params', array( $this, 'ha_add_skope_control_params' ) );
            /* ------------------------------------------------------------------------- *
             *  Skopify the save DEPRECATED
             *  1) Dynamically set the type in WP_Customize_Setting::save()
             *  2) Then add skope save actions by type on WP_Customize_Setting::update()
            /* ------------------------------------------------------------------------- */
        }
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
        function ha_alter_wp_customizer_settings( $manager ) {
          if ( is_object( $manager->get_setting( 'header_image_data' ) ) ) {
              $manager -> remove_setting( 'header_image_data' );
              if ( class_exists( 'HA_Customize_Header_Image_Data_Setting' ) && class_exists( 'HA_Customize_Header_Image_Setting' ) ) {
                  $manager -> add_setting( new HA_Customize_Header_Image_Data_Setting( $manager, 'header_image_data', array(
                    'theme_supports' => 'custom-header',
                  ) ) );
                  $manager -> remove_setting( 'header_image' );
                  $manager -> add_setting( new HA_Customize_Header_Image_Setting( $manager, 'header_image', array(
                    'default'        => get_theme_support( 'custom-header', 'default-image' ),
                    'theme_supports' => 'custom-header',
                  ) ) );
              }
          }

          if ( is_object( $manager->get_control( 'hu_theme_options[layout-global]' ) ) ) {
              $manager->get_control( 'hu_theme_options[layout-global]' ) -> label = __( "Column Layout for main content and sidebars", 'hueman-addons' );
              $manager->get_control( 'hu_theme_options[layout-global]' ) -> notice = __( 'Pick a content layout in the dropdown list. Note : selecting a "1 column" layout won\'t let you display any sidebar widgets.', 'hueman-addons' );
          }
        }


        /* ------------------------------------------------------------------------- *
         *  CUSTOMIZE PANEL : ADD LOCALIZED PARAMS
        /* ------------------------------------------------------------------------- */
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
                  'isLocalSkope'          => apply_filters( 'skope_is_local', isset( $_GET['url'] ) ),
                  'isTopNoteOn'           => true || apply_filters( 'ha_czr_top_note_status', 'dismissed' != get_option( 'ha_czr_top_note_status' ) ||  ( defined('CZR_DEV') && true === CZR_DEV ) ),
                  'topNoteParams'         => array(
                      'title'   => __( 'Welcome in the new customizer interface !', 'hueman-addons' ),
                      'message' => sprintf ( __( 'Discover a new way to customize your pages on %1$s.', 'hueman-addons' ),
                            sprintf('<a href="%1$s" title="%2$s" target="_blank">%3$s <span class="fas fa-external-link-alt"></span></a>',
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
        public $changeset_post_id;//<= ha_get_real_wp_changeset_post_id();

        function __construct() {
            add_filter('sidebars_widgets', array($this, 'ha_use_skope_widgets') );
            $this -> ha_cache_skope_excluded_settings();
            add_filter( 'ha_get_skope_excluded_options', array( $this, 'ha_set_excluded_skope_settings') );
            if ( hu_is_ajax() && ! ha_is_partial_ajax_request() ) {

              add_action( 'ajax_query_ready',  array( $this, 'ha_setup_skope_option_filters' ), 1000 );

              add_action( 'ajax_query_ready' , array( $this, 'ha_cache_options' ), 99999 );

            }
            else {
              if ( ! ha_is_partial_ajax_request() ) {
                add_action( 'wp',  array( $this, 'ha_setup_skope_option_filters' ), 1000 );
              }

              add_action( 'wp' , array( $this, 'ha_cache_options' ), 99999 );

            }
            $theme_name = ha_get_skope_theme_name();
            $this->global_skope_optname = "{$theme_name}_global_skope";
        }//construct





        /* ------------------------------------------------------------------------- *
         *  SET AND GET CACHED OPTIONS
        /* ------------------------------------------------------------------------- */
        function ha_cache_options() {
            $meta_type = ha_get_skope( 'meta_type', true );
            $_skope_list = array( 'global', 'group', 'special_group', 'local' );
            foreach ($_skope_list as $_skp ) {
                switch ( $_skp ) {
                    case 'global':
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
        function ha_get_skope_opt( $level = '', $skope_meta_key = '' ) {
            if( 'global' == $level ) {
              return new WP_Error('skope_error', 'The global options cannot be accessed with ha_get_skope_opt' );
            }
            $skope_meta_key = empty( $level ) ? '' : $this -> ha_get_skope_opt_name( $level );
            $_opt = get_post_meta( ha_get_skope_post_id(), $skope_meta_key, true );
            $_return = array();
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
        function ha_cache_skope_excluded_settings() {
             if ( is_array(self::$_skope_excluded_settings) && ! empty( self::$_skope_excluded_settings ) )
               return;
             $_settings_map = HU_utils_settings_map::$instance -> hu_get_customizer_map( null, 'add_setting_control' );
             $_excluded = array();
             foreach ( $_settings_map as $_id => $data ) {
               if ( isset($data['skoped']) && false === $data['skoped'] )
                 $_excluded[] = $_id;
             }
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
        function ha_set_excluded_skope_settings( $_default_excluded ) {
            return array_merge( $_default_excluded, self::$_skope_excluded_settings );
        }




        /* ------------------------------------------------------------------------- *
         *  FILTER WP AND THEME OPTIONS
        /* ------------------------------------------------------------------------- */
        function ha_setup_skope_option_filters() {
            if ( isset( $GLOBALS['wp_customize'] ) ) {
              $this->changeset_post_id = ha_get_real_wp_changeset_post_id();
            }
            add_filter( 'hu_opt', array( $this, 'ha_filter_hu_opt_for_skope' ), 1000, 4 );
            $theme = get_option( 'stylesheet' );
            $wp_options = array(
              'blogname',
              'blogdescription',
              "theme_mods_{$theme}"
            );

            if ( apply_filters( 'ha_skope_sidebars_widgets', false ) ) {
              $wp_options[] = 'sidebars_widgets';
              $wp_options  = array_merge( $wp_options, hu_get_registered_widgets_option_names() );
            }

            foreach ( $wp_options as $wp_opt ) {
              add_filter( "option_{$wp_opt}", array( $this, 'ha_filter_wp_builtin_options'), 2000, 2 );
            }
        }
        function ha_filter_wp_builtin_options( $original_value, $option_name = null ) {
          if ( is_null( $option_name ) )
            return $original_value;


          $authorized_theme_mods = ha_get_wp_builtins_skoped_theme_mods();
          $theme = get_option( 'stylesheet' );
          if ( "theme_mods_{$theme}" == $option_name ) {
              $skoped_theme_mods = array();

              foreach ( $authorized_theme_mods as $_tmodname ) {
                  $_tmodval = array_key_exists( $_tmodname, $original_value ) ? $original_value[$_tmodname] : '_no_set_';
                  $_tmodval = $this -> ha_filter_hu_opt_for_skope( $_tmodval, $_tmodname, null );
                  if ( '_no_set_' !== $_tmodval ) {
                      $skoped_theme_mods[$_tmodname] = $_tmodval;
                      add_filter( "theme_mod_{$_tmodname}", array( $this, '_filter_theme_mods'), 2000 );
                  }


              }
              return wp_parse_args( $skoped_theme_mods, $original_value );
          }
          else {
              return $this -> ha_filter_hu_opt_for_skope( $original_value, $option_name, null );
          }
          return $original_value;
        }
        function _filter_theme_mods( $value ) {
          $_filter = current_filter();
          $_ptrn = 'theme_mod_';
          if ( $_ptrn !== substr( $_filter, 0, strlen($_ptrn) ) )
            return $value;

          $_mod_name = str_replace($_ptrn, '',  $_filter);
          if ( ! ha_is_option_skoped( $_mod_name ) )
            return $value;
          return $this -> ha_filter_hu_opt_for_skope( $value, $_mod_name, null );
        }
        function ha_filter_hu_opt_for_skope( $_opt_val , $opt_name , $opt_group = HU_THEME_OPTIONS , $_default_val = null ) {
            /*
            * Take care of the excluded options in front
            * needed for:
            * multisite blogname issue https://github.com/presscustomizr/hueman-addons/issues/43
            * polylang blogname and blogdescription issue https://github.com/presscustomizr/hueman/issues/628
            */
            $excluded_options = ha_get_skope_excluded_options();
            if ( $opt_name && is_array( $excluded_options ) && in_array( $opt_name, $excluded_options ) ) {
                return $_opt_val;
            }
            $_new_val = $_opt_val;
            if ( HU_AD() -> ha_is_customize_preview_frame() && !  HU_AD() -> ha_is_previewing_live_changeset() ) {
                $_new_val = $this -> _get_sanitized_preview_val( $_opt_val, $opt_name );
            } else {
                $_new_val = $this -> _get_front_end_val( $_opt_val, $opt_name, 'local', true );
            }
            return $_new_val;
        }






        /* ------------------------------------------------------------------------- *
        * SET OPTION
        /* ------------------------------------------------------------------------- */
        function ha_set_skope_option_val( $opt_name, $new_value, $skope_meta_key = null ) {
            if ( empty($opt_name) || is_null($skope_meta_key ) ) {
              return new WP_Error( 'missing param(s) in HA_SKOP_OPT::ha_set_skope_option_val' );
            }

            $skope_post_id = ha_get_skope_post_id();
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
        function ha_use_skope_widgets( $original ) {
          if ( ! apply_filters( 'ha_skope_sidebars_widgets', false ) )
            return $original;

          $db_skope_widgets = get_option('sidebars_widgets');
          $db_skope_widgets = is_array($db_skope_widgets) ? $db_skope_widgets : array();
          $new_sidebar_widgets = $db_skope_widgets;
          $defaut_hu_widget_ids = hu_get_widget_zone_ids();

          foreach ( $original as $key => $value) {
            if ( in_array( $key, $defaut_hu_widget_ids ) || ! is_array($value) )
              continue;
            $new_sidebar_widgets[$key] = (array)$value;
          }
          foreach ($new_sidebar_widgets as $k => $v) {
            if ( ! is_array($v) )
              unset($new_sidebar_widgets[$k]);
          }
          if ( isset($new_sidebar_widgets['wp_inactive_widgets']) && is_array($new_sidebar_widgets['wp_inactive_widgets']) ) {
            foreach ( $new_sidebar_widgets as $sidebar => $wdg_list ) {
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
        function _get_front_end_val( $original_opt_val, $opt_name, $skope = 'local', $do_inherit = false ) {
            $cache_front = $this -> _front_values;
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
            $cache_front[$opt_name] = $skop_opt_val;
            $this -> _front_values = $cache_front;

            return $skop_opt_val;
        }



        function _get_simple_front_val( $original_opt_val, $opt_name, $skope, $do_inherit ) {
            $skop_opt_val = $this -> ha_get_cached_opt( $skope, $opt_name );
            $skop_opt_val = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;
            if ( is_array( $skop_opt_val ) || '_no_set_' != (string)$skop_opt_val ) {
                return $skop_opt_val;
            }
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
            $skop_opt_val     = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;
            if ( is_array( $skop_opt_val ) ) {
                $_candidate_val = wp_parse_args( $_candidate_val, $skop_opt_val );
            }

            if ( 'global' == $skope ) {
                if ( is_array( $original_opt_val ) )
                  return wp_parse_args( $_candidate_val, $original_opt_val );
                else
                  return $_candidate_val;
            }
            if ( ! $do_inherit ) {
                return wp_parse_args( $_candidate_val, $original_opt_val );
            }

            $parent_skope = $this -> ha_get_parent_skope_name( $skope );
            return $this -> _get_multidim_front_val( $original_opt_val, $opt_name, $parent_skope, true, $_candidate_val );
        }



        function ha_get_parent_skope_name( $skope, $_index = null ) {
            $hierark = array( 'local', 'group', 'special_group', 'global' );
            $parent_ind = -1;
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
            if ( '__not_posted__' != $this -> ha_get_sanitized_post_value( 'skope_id' ) ) {
                $cache_customized[$rev_index][$opt_name] = $preview_val;
                $this -> _skope_preview_values = $cache_customized;
            } else {
                ha_error_log( 'preview val not_cached_yet => the skope_id was not posted' );
            }
            return $preview_val;
        }
        function _get_simple_sanitized_preview_val( $_original_val , $opt_name, $skope_id = null, $child_value = null ) {
            $val_candidate            = is_null( $child_value ) ? '_not_customized_' : $child_value;
            $skope_id                 = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            $skope_level              = $this -> ha_get_skope_level( $skope_id );
            $_skope_customized_val    = $this -> _ha_get_simple_sanitized_customized_value( $opt_name, $skope_id, $_original_val );
            $_skope_db_meta_val       = $this -> _get_front_end_val( $_original_val, $opt_name, $skope_level );


            if ( $this -> _is_value_customized( $_skope_customized_val ) ) {
              $val_candidate = $_skope_customized_val;
            } else {
              $val_candidate = $_skope_db_meta_val;
            }

            if ( is_bool( $val_candidate ) )
              return $val_candidate;

            if ( $val_candidate !== '_no_set_' &&  $val_candidate !== '_not_customized_' )
              return $val_candidate;

            $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $val_candidate = $this -> _get_simple_sanitized_preview_val( $_original_val, $opt_name, $parent_skope_id, $val_candidate );
            }
            if ( $val_candidate === '_no_set_' || $val_candidate === '_not_customized_' )
                $val_candidate = $_original_val;

            return $val_candidate;
        }



        /* ------------------------------------------------------------------------- *
        * GET CUSTOMIZED VALUE FOR A SINGLE SETTING: APPLY INHERITANCE
        /* ------------------------------------------------------------------------- */
        function _ha_get_simple_sanitized_customized_value( $opt_name, $skope_id = null , $_original_val = null, $do_inherit = false ) {

            $_customized_val  = '_not_customized_';
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return $_original_val;

            global $wp_customize;
            $skope_id           = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;

            $_customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );
            if ( false === $_customized_values || ! is_array( $_customized_values ) )
              $_customized_values = array();
            $setting_id   = $opt_name;
            if ( array_key_exists( $opt_name, $_customized_values ) && is_object( $wp_customize -> get_setting( $opt_name ) ) ) {
                $_customized_val = $this -> _get_setting_sanitized_skoped_customized_value( $skope_id, $wp_customize -> get_setting( $opt_name ), $do_inherit );// validates and sanitizes the value
            }
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
            return apply_filters( "ha_get_customize_val_{$opt_name}", $_customized_val, $opt_name );
        }







        /* ------------------------------------------------------------------------- *
        * GET SKOPE CUSTOMIZED VALUES
        /* ------------------------------------------------------------------------- */
        function ha_get_unsanitized_customized_values( $skope_id = null ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();
            $skope_id = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            if ( is_null( $skope_id ) )
              return array();

            global $wp_customize;

            $customized_values = array();

            $rev_index = $this -> ha_get_sanitized_post_value( 'revisionIndex' );
            $rev_index = '__not_posted__' == $rev_index ? 'initial' : $rev_index;
            $cache_customized = $this -> all_skopes_customized_values;
            if ( isset( $cache_customized[$rev_index] ) && is_array( $cache_customized[$rev_index]) && array_key_exists( $skope_id, $cache_customized[$rev_index] ) )
              return $cache_customized[$rev_index][$skope_id];

            if ( ! isset( $cache_customized[$rev_index] ) || ! is_array( $cache_customized[$rev_index] ) )
              $cache_customized = array( $rev_index => array( $skope_id => array() ) );
            else {
              if ( ! array_key_exists( $skope_id, $cache_customized[$rev_index] ) )
                $cache_customized[$rev_index][$skope_id] = array();
            }
            if ( 'global' == $this -> ha_get_skope_level( $skope_id ) || 'global' == $this -> ha_get_current_customized_skope() ) {
                $customized_values = $wp_customize -> unsanitized_post_values();
            } else {
                $skope_changeset_data = $this -> _get_unsanitized_skope_changeset_values( $skope_id );
                $skope_post_values    = $this -> _get_unsanitized_skope_posted_values( $skope_id );
                $customized_values    = array_merge( $skope_changeset_data, $skope_post_values );
            }
            if ( '__not_posted__' != $this -> ha_get_sanitized_post_value( 'skope_id' ) ) {
              $cache_customized[$rev_index][$skope_id] = $customized_values;
              $this -> all_skopes_customized_values = $cache_customized;
            } else {
              ha_error_log( 'all skopes customized values not_cached_yet => the skope_id was not posted' );
            }
            return $customized_values;
        }
        function _get_unsanitized_skope_changeset_values( $skope_id ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() || ! HU_AD() -> ha_is_changeset_enabled() )
              return array();

            global $wp_customize;
            $values = array();
            $skope_meta_key    = $this -> _get_posted_skope_metakey( $skope_id );
            if ( false == $skope_meta_key ) {
              ha_error_log( 'no meta key found in _get_unsanitized_skope_changeset_values() for skope : ' . $skope_id );
              return array();
            }
            $changeset_post_id = $this->changeset_post_id;

            $changeset_data = ha_get_skope_db_data(
                array(
                    'post_id' => $changeset_post_id,
                    'skope_meta_key' => $skope_meta_key,
                    'level' => $this -> ha_get_skope_level( $skope_id )
                )
            );

            if ( is_wp_error( $changeset_data ) ) {
                ha_error_log( 'Error when trying to get the changeset data in _get_unsanitized_skope_changeset_values() for skope : ' . $skope_id );
                $changeset_data = array();
            }
            foreach ( $changeset_data as $raw_setting_id => $setting_data ) {
                if ( 'skope_infos' == $raw_setting_id  )
                  continue;
                if ( ! is_array( $setting_data ) || ! array_key_exists( 'value', $setting_data ) ) {
                  ha_error_log( 'Problem in _get_unsanitized_skope_changeset_values, the setting_data of the changeset are not well formed for skope : ' . $skope_id );
                  ha_error_log( 'setting id ' . $raw_setting_id );
                  ha_error_log( '<SETTING DATA>' );
                  ha_error_log( print_r( $setting_data, true ) );
                  ha_error_log( '</SETTING DATA>' );
                  continue;
                }

                $setting_id = $raw_setting_id;
                if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {
                    $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                    if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                      $setting_id = $matches['setting_id'];
                    }
                }
                $values[$setting_id] = $setting_data['value'];
            }
            return $values;
        }
        function _get_unsanitized_skope_posted_values( $skope_id = null ) {
            if ( ! HU_AD() -> ha_is_customize_preview_frame() )
              return array();

            global $wp_customize;
            $skopes_customized_values = array();
            $post_values = array();
            if ( ! is_null( $skope_id ) ) {
                if ( ! isset( $_POST['skopeCustomized'] ) || ! isset( $_POST['skope'] ) ) {
                    $post_values = array();
                } else {
                  $skopes_customized_values = json_decode( wp_unslash( $_POST['skopeCustomized'] ), true );
                }
                if ( ! isset( $wp_customize -> _current_skope_id ) ) {
                    $wp_customize -> _current_skope_id = $skope_id;
                }
                if ( ! isset( $wp_customize -> _skope_post_values ) || $wp_customize -> _current_skope_id != $skope_id ) {
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
            else {
                if ( isset( $_POST['customized'] ) ) {
                    $post_values = json_decode( wp_unslash( $_POST['customized'] ), true );
                } else {
                    $post_values = array();
                }
            }
            return $post_values;
        }
        function _get_setting_sanitized_skoped_customized_value( $skope_id = null, $setting, $do_inherit = false ) {
            $_candidate_val    = '_not_customized_';
            $parent_skope_id   = '';
            $skope_id          = is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;

            if ( false == $skope_id || is_null($skope_id) || empty($skope_id) ) {
              ha_error_log( 'missing skope_id in _get_setting_sanitized_skoped_customized_value()' );
              return $_candidate_val;
            }

            $customized_values = $this -> ha_get_unsanitized_customized_values( $skope_id );

            if ( array_key_exists( $setting -> id, $customized_values ) ) {
                $_candidate_val = $customized_values[ $setting->id ];
                $valid = $setting->validate( $_candidate_val );
                if ( is_wp_error( $valid ) ) {
                    return $_candidate_val;
                }
                $_candidate_val = $setting -> sanitize( $_candidate_val );
            }
            else if ( $do_inherit ) {
                $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
                if ( false !== $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                    $_candidate_val = $this -> _get_setting_sanitized_skoped_customized_value( $parent_skope_id , $setting, true );
                }
            }

            return ( is_null( $_candidate_val ) || is_wp_error( $_candidate_val ) ) ? '_not_customized_' : $_candidate_val;
        }
        function _get_parent_skope_id( $requested_skope_id = null, $requested_skope_level = null ) {
            $parent_id = '';
            $_posted_skopes = $this -> _get_posted_skopes();

            if ( ! is_array( $_posted_skopes ) )
              return;

            if ( 'global' === $requested_skope_level ) {
              return isset($_posted_skopes['global']['id']) ? $_posted_skopes['global']['id'] : false;
            }
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
                $grand_parent_level = intval( $parent_ind + 1 );
                return $this -> _get_parent_skope_id( null, $hierark[$grand_parent_level] );
              } else {
                return isset($_posted_skopes[$parent_skop_level]['id']) ? $_posted_skopes[$parent_skop_level]['id'] : false;
              }
            }

        }
        function _get_posted_skopes() {
          if ( ! isset( $_POST['current_skopes'] ) )
              return;
          return json_decode( wp_unslash( $_POST['current_skopes']), true );
        }
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
            $value = is_object($value) ? (array)$value : $value;
            if ( is_array($value) )
              return true;

            return ( is_string($value) && '_not_customized_' == $value ) ? false : true;
        }
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
        function ha_get_current_customized_skope() {
            if ( '__not_posted__' ==  $this -> ha_get_sanitized_post_value( 'skope' ) )
              return 'global';
            return $this -> ha_get_sanitized_post_value( 'skope' );
        }
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
        function ha_get_sanitized_post_value( $param ) {
            return isset($_POST[$param]) ? esc_attr( $_POST[$param ] ) : '__not_posted__';
        }
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
        function _get_multidim_sanitized_preview_val( $_original_val , $opt_name , $skope_id = null, $child_value = null ) {
            $child_value              = ( is_null( $child_value ) || ! is_array( $child_value ) ) ? array() : $child_value;
            $_candidate_val           = $child_value;
            $setting_id   = $opt_name;
            $skope_id     =  is_null( $skope_id ) ? $this -> ha_get_sanitized_post_value( 'skope_id' ) : $skope_id;
            if ( '__not_posted__' == $skope_id ) {
              ha_error_log('in _get_multidim_preview_val SKOPE NOT POSTED');
              return $_candidate_val;
            }

            $cust_skope               = $this -> ha_get_skope_level( $skope_id );
            $_skope_customized_val    = $this -> _get_wp_multidim_sanitized_customized_val( $opt_name, $skope_id, false );
            $_skope_db_meta_val       = $this -> _get_wp_multidim_saved_val( $opt_name, $skope_id, false );
            $_skope_customized_val    = ! is_array( $_skope_customized_val ) ? array() : $_skope_customized_val;
            $_skope_db_meta_val       = ! is_array( $_skope_db_meta_val ) ? array() : $_skope_db_meta_val;
            $_skope_val               = wp_parse_args( $_skope_customized_val, $_skope_db_meta_val );
            $_candidate_val           = wp_parse_args( $_candidate_val, $_skope_val );

            $parent_skope_id          = $this -> _get_parent_skope_id( $skope_id );
            if ( false != $parent_skope_id && ! empty( $parent_skope_id ) && '_reached_the_roof_' != $parent_skope_id ) {
                $_candidate_val       = $this -> _get_multidim_sanitized_preview_val( $_original_val, $opt_name, $parent_skope_id, $_candidate_val );
            }
            if ( is_null( $_candidate_val ) || empty( $_candidate_val ) || is_wp_error( $_candidate_val ) || ! is_array( $_candidate_val ) )
                $_candidate_val = is_array( $_original_val ) ? $_original_val : array();

            return $_candidate_val;
        }












        public function _get_wp_multidim_saved_val( $opt_name, $skope_id = null, $do_inherit = false, $child_value = array() ) {
            $skope          = $this -> ha_get_skope_level( $skope_id );
            if ( 'global' == $skope && ha_is_wp_builtin_skoped_theme_mod( $opt_name ) ) {
              $theme = get_option( 'stylesheet' );
              $_raw_theme_mods = ha_get_raw_option( "theme_mods_{$theme}" );
              $skop_opt_val = isset( $_raw_theme_mods[$opt_name] ) ? $_raw_theme_mods[$opt_name] : array();
            } else {
              $skop_opt_val   = $this -> ha_get_cached_opt( $skope, $opt_name );
            }
            $skop_opt_val   = is_object( $skop_opt_val ) ? (array)$skop_opt_val : $skop_opt_val;
            if ( ! is_array( $skop_opt_val ) )
              $skop_opt_val = array();
            if ( ! $do_inherit ) {
              return $skop_opt_val;
            }
            $_val_candidate = $child_value;
            foreach ( $skop_opt_val as $_key => $_value ) {
              $_val_candidate[$_key] = ! isset( $child_value[$_key] ) ? $_value : $child_value[$_key];
            }


            $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
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
            if ( false === $_customized_values || ! is_array( $_customized_values ) )
              $_customized_values = array();
            foreach ( $_customized_values as $_setting_id => $_setting_value ) {
                if ( $opt_name != substr($_setting_id, 0, strlen( $opt_name ) ) )
                  continue;
                if ( ! is_object( $wp_customize -> get_setting( $_setting_id ) ) )
                  continue;
                $_setting             = $wp_customize -> get_setting( $_setting_id );
                $id_data              = $_setting -> id_data();
                $multi_dim_keys       = $id_data['keys'];
                $is_multidimensional  = ! empty( $multi_dim_keys );
                $setting_id           = $_setting -> id;
                if ( ! $is_multidimensional )
                  continue;

                $valid = $_setting -> validate( $_setting_value );
                if ( is_wp_error( $valid ) ) {
                    ha_error_log( 'in _get_wp_multidim_sanitized_customized_val, invalid value for setting' . $_setting_id );
                    continue;
                }
                $_setting_value = $_setting -> sanitize( $_setting_value );
                foreach ( $multi_dim_keys as $_k ) {
                    $_val_candidate[$_k] = ! isset( $child_value[$_k] ) ? $_setting_value : $child_value[$_k];
                }
            }

            if ( $do_inherit ) {
                $parent_skope_id = $this -> _get_parent_skope_id( $skope_id );
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
add_action('hu_hueman_loaded', 'ha_load_skop_ajax');
function ha_load_skop_ajax() {
    if ( defined('CZR_DEV') && true === CZR_DEV ) {
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-base.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-changeset-save.php' );
        require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-ajax-reset.php' );
    }
    new HA_Skop_Chset_Reset();
}
if ( defined('CZR_DEV') && true === CZR_DEV ) {
    if ( apply_filters('ha_print_skope_logs' , false ) ) {
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
        add_action('hu_hueman_loaded', 'ha_instantiate_dev_logs', 100 );
    }
}
add_action('init', 'ha_load_skop_customizer_preview' );
function ha_load_skop_customizer_preview() {
    if ( HU_AD() -> ha_is_customize_preview_frame() ) {
        if ( defined('CZR_DEV') && true === CZR_DEV ) {
            require_once( HA_BASE_PATH . 'addons/skop/_dev/skop-customize-preview.php' );
        }
        new HA_Skop_Cust_Prev();
    }
}
add_action( 'transition_post_status', 'ha_publish_skope_changeset_metas_on_post_status_transition', 0, 3 );
add_action( 'transition_post_status', 'ha_trash_skope_changeset_metas_on_post_status_transition', 0, 3 );

/**
 * hook : 'transition_post_status'
 * Inspired of _wp_customize_publish_changeset in wp-includes/theme.php
 * Publishes a snapshot's changes.
 *
 *
 * @global wpdb                 $wpdb         WordPress database abstraction object.
 * @global WP_Customize_Manager $wp_customize Customizer instance.
 *
 * @param string  $new_status     New post status.
 * @param string  $old_status     Old post status.
 * @param WP_Post $changeset_post Changeset post object.
 */
function ha_publish_skope_changeset_metas_on_post_status_transition( $new_status, $old_status, $changeset_post ) {
    global $wp_customize, $wpdb;

    $is_publishing_changeset = (
      'customize_changeset' === $changeset_post->post_type
      &&
      'publish' === $new_status
      &&
      'publish' !== $old_status
    );

    if ( ! $is_publishing_changeset ) {
      return;
    }

    if ( empty( $wp_customize ) ) {
      require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
      $wp_customize = new WP_Customize_Manager( array(
        'changeset_uuid' => $changeset_post->post_name,
        'settings_previewed' => false,
      ) );
    }
    $theme_name = ha_get_skope_theme_name();
    $response = array();
    $changeset_post_id = $changeset_post->ID;
    $raw_changeset_data = get_post_meta( $changeset_post_id );
    $raw_changeset_data = is_array( $raw_changeset_data ) ? $raw_changeset_data : array();

    $unserialized_changeset_data = array();
    foreach ( $raw_changeset_data as $meta_key => $meta_value ) {
        if ( ! is_string( $meta_key ) || $theme_name != substr( $meta_key, 0 , strlen( $theme_name ) ) )
          continue;

        if ( is_array( $meta_value ) && 1 == count( $meta_value ) ) {
            $unserialized_changeset_data[ $meta_key ] = maybe_unserialize( $meta_value[0] );
        } else {
            $unserialized_changeset_data[ $meta_key ] = array_map( 'maybe_unserialize', $meta_value );
        }
    }
    $changeset_candidate_data = array();
    foreach ( $unserialized_changeset_data as $skope_meta_key => $customized_data ) {
        $changeset_candidate_data[$skope_meta_key] = array_key_exists( $skope_meta_key, $changeset_candidate_data ) ? $changeset_candidate_data[$skope_meta_key] : array();
        foreach ( $customized_data as $raw_setting_id => $setting_data ) {
            if ( ! is_array( $setting_data ) || ! array_key_exists( 'value', $setting_data ) ) {
              continue;
            }

            $setting_id = $raw_setting_id;
            if ( isset( $setting_data['type'] ) && 'theme_mod' === $setting_data['type'] ) {
                $namespace_pattern = '/^(?P<stylesheet>.+?)::(?P<setting_id>.+)$/';
                if ( preg_match( $namespace_pattern, $raw_setting_id, $matches ) && $wp_customize->get_stylesheet() === $matches['stylesheet'] ) {
                  $setting_id = $matches['setting_id'];
                }
            }
            $changeset_candidate_data[$skope_meta_key][$setting_id] = $setting_data;
        }
    }
    $skope_post_id  = get_option('skope-post-id');
    if ( false === $skope_post_id || empty( $skope_post_id ) ) {
        wp_send_json_error( 'missing skope_post_id when attempting to publish the meta changeset' );
    }

    if ( ! $skope_post_id ) {
        wp_send_json_error( 'NO SKOPE CHANGESET POST ID' );
        return;
    }
    $raw_published_data = get_post_meta( $skope_post_id );
    $raw_published_data = is_array( $raw_published_data ) ? $raw_published_data : array();
    $unserialized_published_data = array();
    foreach ( $raw_published_data as $meta_key => $meta_value ) {
        if ( is_array( $meta_value ) && 1 == count( $meta_value ) ) {
            $unserialized_published_data[ $meta_key ] = maybe_unserialize( $meta_value[0] );
        } else {
            $unserialized_published_data[ $meta_key ] = array_map( 'maybe_unserialize', $meta_value );
        }
    }

    if ( is_wp_error( $unserialized_published_data ) ) {
        $response['publish_skope_changeset_failure'] = $unserialized_published_data -> get_error_code();
        return new WP_Error( 'publish_skope_changeset_failure', '', $response );
    }
    $changesetified_published_data = array();
    foreach ( $unserialized_published_data as $skope_meta_key => $options_data ) {
        if ( ! is_string( $skope_meta_key ) || $theme_name != substr( $skope_meta_key, 0 , strlen( $theme_name ) ) )
          continue;

        $changesetified_published_data[ $skope_meta_key ] = array();
        foreach ( $options_data as $_setid => $_value ) {
            if ( 'skope_infos' == $_setid ) {
                $changesetified_published_data[ $skope_meta_key ][$_setid] = $_value;
            } else if ( _ha_is_wp_setting_multidimensional( $_setid ) ) {
                $to_merge = _ha_build_multidimensional_db_option( $_setid, $_value );
                foreach ( $to_merge as $_id => $val ) {
                    $changesetified_published_data[ $skope_meta_key ][$_id] = array( 'value' => $val );
                }
            } else {
                $changesetified_published_data[ $skope_meta_key ][$_setid] = array( 'value' => $_value );
            }
        }
    }
    $publication_candidate_data = $changesetified_published_data;
    if ( is_wp_error( $publication_candidate_data ) ) {
      $publication_candidate_data = array();
    }
    foreach ( $changeset_candidate_data as $skope_meta_key => $skope_values ) {
        if ( ! is_string( $skope_meta_key ) || $theme_name != substr( $skope_meta_key, 0 , strlen( $theme_name ) ) )
          continue;

        if ( ! array_key_exists( $skope_meta_key, $publication_candidate_data ) ) {
            $publication_candidate_data[$skope_meta_key] = array();
        }
        foreach ( $skope_values as $setting_id => $set_value ) {
            if ( ! array_key_exists( $setting_id, $publication_candidate_data[$skope_meta_key] ) ) {
                $publication_candidate_data[$skope_meta_key][ $setting_id ] = array();
            }
            if ( ! array_key_exists( 'value', $set_value ) ) {
                continue;
                ha_error_log( 'A setting value is not well formed for setting : ' . $setting_id );
            }

            $publication_candidate_data[$skope_meta_key][ $setting_id ]['value'] = $set_value['value'];
        }//foreach()
    }
    foreach ( $publication_candidate_data as $skope_meta_key => $options_data ) {
        foreach ( $options_data as $setting_id => $setting_params ) {
            if ( 'skope_infos' == $setting_id )
              continue;
            $setting = $wp_customize->get_setting( $setting_id );
            if ( ! $setting || ! $setting->check_capabilities() ) {
                ha_error_log( 'In _publish_skope_changeset_metas, ' . $setting_id . ' is not registered in $wp_customize.' );
                continue;
            }
        }//foreach()
        $publication_candidate_data[$skope_meta_key] = ha_prepare_skope_changeset_for_front_end( $publication_candidate_data[$skope_meta_key] );

        if ( is_wp_error( $publication_candidate_data[$skope_meta_key] ) || ! is_array( $publication_candidate_data[$skope_meta_key] ) ) {
            $response[$skope_meta_key] = 'skope data not valid';
            return new WP_Error( 'publish_skope_changeset_failure', '', $response );
        }

    }//foreach()
    foreach ( $publication_candidate_data as $skope_meta_key => $skope_option_values ) {
        $r = update_post_meta( $skope_post_id, $skope_meta_key, $skope_option_values );
        if ( is_wp_error( $r ) ) {
            $response['changeset_post_save_failure'] = $r->get_error_code();
            return new WP_Error( 'skope_changeset_post_save_failure', '', $response );
        } else {
            ha_clean_skope_changeset_metas_after_publish( $changeset_post_id );
        }
    }

    return $response;
}//_publish_skope_changeset_metas


/**
 * hook : 'transition_post_status'
 * Inspired of _wp_customize_publish_changeset in wp-includes/theme.php
 * Publishes a snapshot's changes.
 *
 *
 * @global wpdb                 $wpdb         WordPress database abstraction object.
 * @global WP_Customize_Manager $wp_customize Customizer instance.
 *
 * @param string  $new_status     New post status.
 * @param string  $old_status     Old post status.
 * @param WP_Post $changeset_post Changeset post object.
 */
function ha_trash_skope_changeset_metas_on_post_status_transition( $new_status, $old_status, $changeset_post ) {
    $is_trashing_changeset = (
      'customize_changeset' === $changeset_post->post_type
      &&
      'trash' === $new_status
      &&
      'publish' !== $old_status
    );

    if ( ! $is_trashing_changeset ) {
      return;
    }
    ha_clean_skope_changeset_metas_after_publish( $changeset_post->ID );
}
function ha_clean_skope_changeset_metas_after_publish( $changeset_post_id ) {
    if ( ! $changeset_post_id )
      return;

    $all_skope_changeset_metas = get_post_meta( $changeset_post_id );
    $all_skope_changeset_metas = is_array( $all_skope_changeset_metas ) ? $all_skope_changeset_metas : array();

    foreach ( $all_skope_changeset_metas as $meta_key => $val ) {
        $r = delete_post_meta( $changeset_post_id, $meta_key );
        if ( is_wp_error( $r ) ) {
            break;
        }
    }
}
?>