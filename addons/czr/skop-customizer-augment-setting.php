<?php
/*****************************************************
* //hook : customize_register
*****************************************************/
/******************************************************
* HEADER IMAGE OVERRIDES FOR SKOPE
*******************************************************/
// Overrides the WP built-in header image class update method.
// => otherwise the skope can't be done because it's normally processed on save
// on WP_Customize_Setting::update in do_action( "customize_update_{$this->type}", $value, $this );
/**
 * A setting that is used to filter a value, but will not save the results.
 *
 * Results should be properly handled using another setting or callback.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Setting
 */
final class HA_Customize_Header_Image_Data_Setting extends WP_Customize_Setting {
  public $id = 'header_image_data';

  /**
   * @since 3.4.0
   *
   * @global Custom_Image_Header $custom_image_header
   *
   * @param $value
   */
  public function update( $value ) {
    global $custom_image_header;

    // If the value doesn't exist (removed or random),
    // use the header_image value.
    if ( ! $value )
      $value = $this->manager->get_setting('header_image')->post_value();

    //when saving as trans, post_meta, term_meta, or user_meta
    if ( 'theme_mod' != $this->type ) {
        //get the wp like header_image_data
        //Are we in a skope customization context ? If so the dyn type has already been set and overrides the type Setting property
        if ( isset($_POST['dyn_type']) && $this->type == $_POST['dyn_type'] ) {
            $value = $this -> ha_get_header_image_data( $value );
        }

        do_action( "customize_update_{$this->type}", $value, $this );
        add_filter( "pre_set_theme_mod_{$this->id}", array( $this, 'filter_option'), 1000, 2 );
        add_filter( "pre_set_theme_mod_header_image", array( $this, 'filter_option'), 1000, 2 );


    } else {
        if ( is_array( $value ) && isset( $value['choice'] ) )
          $custom_image_header->set_header_image( $value['choice'] );
        else
          $custom_image_header->set_header_image( $value );
        }
  }


  public function filter_option( $value, $old_value ) {
      return $old_value;
  }



  //@param choice can be a string or an array
  //@see : set_header_image() in wp-admin/custom-header.php
  public function ha_get_header_image_data( $choice ) {
    global $custom_image_header;
    if ( is_array( $choice ) || is_object( $choice ) ) {
      $choice = (array) $choice;
      if ( ! isset( $choice['attachment_id'] ) || ! isset( $choice['url'] ) )
        return;

      $choice['url'] = esc_url_raw( $choice['url'] );

      $header_image_data = (object) array(
        'attachment_id' => $choice['attachment_id'],
        'url'           => $choice['url'],
        'thumbnail_url' => $choice['url'],
        'height'        => $choice['height'],
        'width'         => $choice['width'],
      );

      update_post_meta( $choice['attachment_id'], '_wp_attachment_is_custom_header', get_stylesheet() );

      //synchronizes the header_image option value for this skope
      if ( false != $_POST['opt_name'] ) {
        $db_option_name = esc_attr( $_POST['opt_name'] );
        $obj_id = ( isset( $_POST['obj_id'] ) &&  false != $_POST['obj_id'] ) ? esc_attr( $_POST['obj_id'] ) : null;
        HA_SKOP_OPT() -> ha_set_skope_option_val( 'header_image', $choice['url'], $db_option_name );
      }
      //set_theme_mod( 'header_image', $choice['url'] );
      //set_theme_mod( 'header_image_data', $header_image_data );
      return $header_image_data;
    }

    if ( in_array( $choice, array( 'remove-header', 'random-default-image', 'random-uploaded-image' ) ) ) {
      if ( false != $_POST['opt_name'] ) {
        $db_option_name = esc_attr( $_POST['opt_name'] );
        $obj_id = ( isset( $_POST['obj_id'] ) &&  false != $_POST['obj_id'] ) ? esc_attr( $_POST['obj_id'] ) : null;
        HA_SKOP_OPT() -> ha_set_skope_option_val( 'header_image', $choice, $db_option_name );
      }
      //set_theme_mod( 'header_image', $choice );
      //remove_theme_mod( 'header_image_data' );
      return;
    }

    $uploaded = get_uploaded_header_images();
    if ( $uploaded && isset( $uploaded[ $choice ] ) ) {
      $header_image_data = $uploaded[ $choice ];

    } else {
      $custom_image_header->process_default_headers();
      if ( isset( $custom_image_header->default_headers[ $choice ] ) )
        $header_image_data = $custom_image_header->default_headers[ $choice ];
      else
        return;
    }

    //set_theme_mod( 'header_image', esc_url_raw( $header_image_data['url'] ) );
    //set_theme_mod( 'header_image_data', $header_image_data );
    return $header_image_data;
  }
}//HA_Customize_Header_Image_Data_Setting




// Overrides the WP built-in header image class update method.
// => otherwise the skope can't be done because it's normally processed on save
// on WP_Customize_Setting::update in do_action( "customize_update_{$this->type}", $value, $this );
/**
 * A setting that is used to filter a value, but will not save the results.
 *
 * Results should be properly handled using another setting or callback.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Setting
 */
final class HA_Customize_Header_Image_Setting extends WP_Customize_Setting {
  public $id = 'header_image';

  /**
   * @since 3.4.0
   *
   * @global Custom_Image_Header $custom_image_header
   *
   * @param $value
   */
  public function update( $value ) {

    if ( 'theme_mod' != $this->type )
      do_action( "customize_update_{$this->type}", $value, $this );
  }
}