<?php

/**
 * This handles validation, sanitization and saving of the value.
 */

/**
 *
 *
 * @see WP_Customize_Setting
 */
final class Contx_Customizer_Setting extends WP_Customize_Setting {

  /**
   * The setting type.
   * @var string
   */
  public $type = 'ac_skoped';
  public $transport = 'refresh';

  /**
   * Capability required to edit this setting.
   * @var string
   */
  public $capability = 'edit_theme_options';

  /**
   * Stylesheet
   *
   * @var string
   */
  public $stylesheet = '';

  /**
   * constructor.
   *
   * @throws Exception If the setting ID does not match the pattern `contx__`.
   *
   * @param WP_Customize_Manager $manager The Customize Manager class.
   * @param string               $id      An specific ID of the setting. Can be a
   *                                      theme mod or option name.
   * @param array                $args    Setting arguments.
   */
  public function __construct( $manager, $id, $args = array() ) {
    parent::__construct( $manager, $id, $args );
    //shall start with "skp__"
    if ( 0 !== strpos( $this->id_data['base'], NIMBLE_SKOPE_ID_PREFIX ) ) {
      throw new Exception( 'Contx_Customizer_Setting => Expected ' . NIMBLE_SKOPE_ID_PREFIX . ' id_base.' );
    }
    if ( 1 !== count( $this->id_data['keys'] ) || empty( $this->id_data['keys'][0] ) ) {
      throw new Exception( 'Contx_Customizer_Setting => Expected single stylesheet key.' );
    }
    $this->stylesheet = $this->id_data['keys'][0];
  }

  /**
   * Add filter to preview post value.
   *
   * @return bool False when preview short-circuits due no change needing to be previewed.
   */
  public function preview() {
    if ( $this->is_previewed ) {
      return false;
    }
    $this->is_previewed = true;
    add_filter( 'ctx_get_skoped_settings', array( $this, 'filter_previewed_ctx_get_skoped_settings' ), 9, 3 );
    return true;
  }

  /**
   * Filter `ctx_get_skoped_settings` for applying the customized value.
   *
   * This is used in the preview when `ctx_get_skoped_settings()` is called for rendering the styles.
   *
   * @see ctx_get_skoped_settings()
   *
   * @param array $skoped_settings        Original
   * @param string $stylesheet Current stylesheet.
   * @return array of skope settings
   */
  public function filter_previewed_ctx_get_skoped_settings( $skoped_settings, $skope_id, $stylesheet ) {
    if ( $skope_id === $this->id_data['base'] && $stylesheet === $this->stylesheet ) {
      $customized_value = $this->post_value( null );
      if ( ! is_null( $customized_value ) ) {
        $skoped_settings = $customized_value;
      }
    }
    return $skoped_settings;
  }

  /**
   * Fetch the value of the setting. Will return the previewed value when `preview()` is called.
   *
   * @since 4.7.0
   * @see WP_Customize_Setting::value()
   *
   * @return string
   */
  public function value() {
    if ( $this->is_previewed ) {
      $post_value = $this->post_value( null );
      if ( null !== $post_value ) {
        return $post_value;
      }
    }
    $id_base = $this->id_data['base'];

    error_log('id_base in setting class => ' . $this->id_data['base'] );

    $value = '';
    $post = ctx_get_skope_post( $this->id, $this->stylesheet );
    if ( $post ) {
      $value = $post->post_content;
    }
    if ( empty( $value ) ) {
      $value = $this->default;
    }

    /** This filter is documented in wp-includes/class-wp-customize-setting.php */
    $value = apply_filters( "customize_value_{$id_base}", $value, $this );

    return $value;
  }


  /**
   * Store the $skoped_settings value in the skp-post-type custom post type for the stylesheet.
   *
   * @since 4.7.0
   *
   * @param array $skoped_settings The input value.
   * @return int|false The post ID or false if the value could not be saved.
   */
  public function update( $skoped_settings ) {
    if ( empty( $skoped_settings ) ) {
      $skoped_settings = '';
    }

    $r = ctx_update_skope_post( $skoped_settings, array(
      'stylesheet' => $this->stylesheet,
      'skope_id' => $this->id_data['base']
    ) );

    if ( $r instanceof WP_Error ) {
      return false;
    }
    $post_id = $r->ID;

    // Cache post ID in theme mod for performance to avoid additional DB query.
    if ( $this->manager->get_stylesheet() === $this->stylesheet ) {
      set_theme_mod( $this->id_data['base'], $post_id );
    }

    return $post_id;
  }
}