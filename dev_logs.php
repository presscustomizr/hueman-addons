<?php


// add_action('customize_register', 'hu_ad_set_setting_list_for_skope_transition', PHP_INT_MAX );
// function hu_ad_set_setting_list_for_skope_transition( $manager ) {
//     $collection = array();
//     foreach( $manager->controls() as $control ) {
//         error_log( $control->id . ' => ' . $control->type );
//         $collection[ $control->id ] = $control->type;
//     }
//     set_transient( 'control_type_list_for_skope_transition', $collection, 60*60*24*365*20 );
// }










// //Creates a new instance
// function Contx_Options() {
//     return Contx_Options::ctx_get_instance();
// }
// Contx_Options();


// $theme_slug = skp_get_parent_theme_slug();
// error_log( "theme_mods_{$theme_slug}" );
// add_filter( "option_theme_mods_{$theme_slug}", function( $value ) {
//     error_log( '<IN THEMEMODS FILTER>' );
//     error_log( print_r( $value , true ) );
//     error_log( '</IN THEMEMODS FILTER>' );
//     return $value;
// } );



/* ------------------------------------------------------------------------- *
 * FOR TEST
/* ------------------------------------------------------------------------- */

add_action('loop_start', function() {
  if ( ! is_main_query() || did_action('hu_test_printed') )
    return;
  do_action('hu_test_printed');

  $skope_namespace = $GLOBALS['czr_skope_namespace'];
  $skp_is_customizing_fn = $skope_namespace . 'skp_is_customizing';
  $skp_get_skope_fn = $skope_namespace . 'skp_get_skope';
  $skp_build_skope_id_fn = $skope_namespace . 'skp_build_skope_id';

  $old_skope_post_id = ctx_get_old_skope_post_id();
  // if ( ! $skp_is_customizing_fn() )
  //     return;
  ?>
    <div>
      <hr/>
      <h2>OLD SKOPE POST : <?php echo $old_skope_post_id; ?></h2>
      <pre style="background: blue;color:white;white-space: pre-wrap;font-size:0.8em;line-height: 1.6em;">
        <?php
        echo '<br/>';
        ?>
          <p>
            <strong>All Post Metas : get_post_meta( $old_skope_post_id )</strong><br/>
            <?php
              $_to_print = array();
              $_old_raw_skoped_options = get_post_meta( $old_skope_post_id );
              $_old_raw_skoped_options = is_array( $_old_raw_skoped_options ) ? $_old_raw_skoped_options : array();
              foreach ( $_old_raw_skoped_options as $meta_key => $value ) {
                if ( is_array($value) && 1 == count($value) )
                  $_to_print[$meta_key] = maybe_unserialize( $value[0] );
                else
                  $_to_print[$meta_key] = array_map('maybe_unserialize', $value);
              }
            ?>
            <pre>
              <?php print_r( $_to_print );  ?>
            </pre>
          </p>
        ?>
      </pre>
      <hr/>
      <h2>SKOPE STRING : <?php echo $skp_get_skope_fn(); ?></h2>
      <pre style="background: blue;color:white;white-space: pre-wrap;">
        <?php

        $skope_string = $skp_get_skope_fn( null, true, array(
            'meta_type' => 'post',
            'type' => 'post',
            'obj_id' => 1
        ) );


        $skope_id = $skp_build_skope_id_fn();
        print_r( 'BUILT SKOPE => ' . $skope_id );
        print_r( ' | SKOPE STRING => ' . $skope_string );
        echo '<br/>';
        print_r( 'contx post id in THEME MOD => ' . get_theme_mod( $skope_id ) );
        //print_r( get_term( 6 ) );
        ?>
      </pre>

      <hr/>
      <h2>ctx_get_skoped_settings( '', '', 'local')</h2>
      <pre style="background: green;color:white;white-space: pre-wrap;font-size:0.8em;line-height: 1.6em;">
        <?php print_r( ctx_get_skoped_settings( '', '', 'local' ) ); ?>
      </pre>
      <hr/>
      <h2>ctx_get_skoped_settings( '', '', 'group')</h2>
      <pre style="background:black;color:white;white-space: pre-wrap;font-size:0.8em;line-height: 1.6em;">
        <?php print_r( ctx_get_skoped_settings( '', '', 'group' ) ); ?>
      </pre>
      <hr/>
      <h2>ctx_get_skope_post()</h2>
      <pre style="background: blue;color:white; white-space: pre-wrap;font-size :0.8em;line-height: 1.6em;">
        <?php
            $post = (array)ctx_get_skope_post();
            print_r( ctx_get_skope_post() );
          ?>
      </pre>
      <hr/>
      <?php if ( ! empty( $_POST ) ) : ?>
        <h2>$_POST</h2>
        <pre style="background: red;color:white;white-space: pre-wrap;font-size:0.8em;line-height: 1.6em;">
          <?php print_r( $_POST ); ?>
        </pre>
      <?php endif; ?>
      <hr/>
      <h2>ctx_get_skope_post() -> post_content </h2>
      <pre style="background: blue;color:white; white-space: pre-wrap;font-size :0.8em;line-height: 1.6em;">
        <?php
          if ( ! empty( $post ) ) {
              /* if ( is_array() )
                array_walk_recursive(, function(&$v) { $v = htmlspecialchars($v); }); */
              ?>
                <pre>
                  <?php print_r( maybe_unserialize( $post['post_content']) ); ?>
                </pre>
              <?php
          }
        ?>
      </pre>
      <hr/>
      <h2>CONTX_OPTIONS</h2>
      <pre style="background: #b5781e;color:black;white-space: pre-wrap;">
        <?php

        ?>
          <pre>
            <?php print_r( get_option( CONTX_OPTION_PREFIX ) ); ?>
          </pre>
        <?php
        ?>
      </pre>
      <!-- h2>TEST SETTINGS</h2>
      <pre style="background: yellow;color:black;white-space: pre-wrap;">
        <?php

        ?>
          <pre>
            <?php //print_r( get_option('pc_ac_opt_test') ); ?>
          </pre>
        <?php
        ?>
      </pre> -->
    </div>
  <?php
}, 50 );



/*add_action('loop_start', function() {
  ?>
    <div>
      <h1> ALORS ? <?php echo get_theme_mod( skp_get_skope_id( 'local' ) ) ; ?></h1>
      <h2>LOCAL SKOPE ID : <?php echo skp_get_skope_id( 'local' ); ?></h2>
      <pre style="background: red;color:white">
        <?php print_r( $_POST ); ?>
      </pre>
    </div>
  <?php
});
*/
/*
add_action('loop_start', function() {
  $options = get_option('pc_ac_opt_test');
  $theme_mods = get_theme_mods();
  ?>
    <div>
      <pre style="background: yellow;">
        <?php print_r( $options ); ?>
        <?php print_r( $theme_mods ); ?>
      </pre>
    </div>
    <div>
      <h2>ALL SKOPED OPTIONS</h2>
      <pre style="background: green;color:white;">
        <?php
          $all_skoped = Contx_Options() -> cached_ctx_opt;
          print_r( $all_skoped );
        ?>
      </pre>
    </div>
  <?php
});
*/




// /////////////////////////////////////////////////////////////////
// // FOR TESTING PURPOSES
// add_action( 'customize_register', function( $wp_customize ) {
//     $wp_customize->add_section( 'test_sec', array(
//         'title'    => __( 'TEST SETTINGS', 'advanced-customizer' ),
//         'priority' => 0,
//         'panel'   => '',
//     ) );

//     $wp_customize->add_setting( "pc_ac_opt_test[test_one]", array(
//         'default'           => "",
//         'type'  => 'option'
//     ) );
//     $wp_customize->add_setting( "pc_ac_opt_test[test_two]", array(
//         'default'           => "one",
//         'type'  => 'option'
//     ) );
//     $wp_customize->add_setting( "pc_ac_opt_test[test_three]", array(
//         'default'           => "2",
//         'type'  => 'option'
//     ) );
//     $wp_customize->add_setting( "pc_ac_opt_test[test_four]", array(
//         'default'           => "10",
//         'type'  => 'option'
//     ) );

//     $wp_customize->add_control( "pc_ac_opt_test[test_one]", array(
//         'label'     => __( 'TEST ONE', 'advanced-customizer'),
//         //'description' => __( 'The New Skope' , 'advanced-customizer'),
//         'type'      => 'text',
//         'section'   => 'test_sec',
//     ) );
//     $wp_customize->add_control( "pc_ac_opt_test[test_two]", array(
//         'label'     => __( 'TEST TWO', 'advanced-customizer'),
//         //'description' => __( 'The New Skope' , 'advanced-customizer'),
//         'type'      => 'select',
//         'choices'   => array('one', 'two', 'three'),
//         'section'   => 'test_sec',
//     ) );
//     $wp_customize->add_control( 'pc_ac_opt_test[test_three]', array(
//       'type' => 'range',
//       'section' => 'test_sec',
//       'label' => __( 'Range' ),
//       'description' => __( 'This is the range control description.' ),
//       'input_attrs' => array(
//         'min' => 0,
//         'max' => 10,
//         'step' => 2,
//       ),
//     ) );

//     $wp_customize->add_control( 'pc_ac_opt_test[test_four]', array(
//       'type' => 'number',
//       'section' => 'test_sec',
//       'label' => __( 'Number Control' ),
//       'description' => __( 'This is the number control description.' ),
//       'input_attrs' => array(
//         'min' => 0,
//         'max' => 20,
//         'step' => 2,
//       ),
//     ) );

//     // // SIMPLE HTML MODULE
//     // $wp_customize->add_setting( "pc_ac_opt_test[text_six]", array(
//     //     'default' => array(),
//     //     'type'  => 'option'
//     // ) );

//     // $wp_customize->add_control( "pc_ac_opt_test[text_six]", array(
//     //     'label'     => __( 'HTML Content test', 'text_domain_to_be_replaced'),
//     //     //'description' => __( 'The New Skope' , 'advanced-customizer'),
//     //     'type'      => 'czr_module',
//     //     'module_type' => 'czr_simple_html_module',
//     //     'section'   => 'test_sec',
//     // ) );
// });

// add_action( 'after_setup_theme', function() {
//     $czrnamespace = $GLOBALS['czr_base_fmk_namespace'];
//     //czr_fn\czr_register_dynamic_module
//     $CZR_Fmk_Base_fn = $czrnamespace . 'CZR_Fmk_Base';
//     if ( ! function_exists( $CZR_Fmk_Base_fn) ) {
//         error_log( __FUNCTION__ . ' => Namespace problem => ' . $CZR_Fmk_Base_fn );
//         return;
//     }
//     $pc_opt_test_options = get_option('pc_ac_opt_test');
//     $CZR_Fmk_Base_fn() -> czr_pre_register_dynamic_setting( array(
//         'setting_id' => 'pc_ac_opt_test[test_six]',
//         'module_type' => 'czr_simple_html_module',
//         'option_value' => ( is_array( $pc_opt_test_options) && array_key_exists( 'test_six', $pc_opt_test_options ) ) ? $pc_opt_test_options['test_six'] : array(), // for dynamic registration

//         'setting' => array(),

//         'section' => array( 'id' => 'test_sec' ),

//         'control' => array(
//             'priority' => 10,
//             'label' => __( 'Insert simple Html content', 'text_domain_to_be_replaced' ),
//             'type'  => 'czr_module',
//         )
//     ));
// }, 50 );
