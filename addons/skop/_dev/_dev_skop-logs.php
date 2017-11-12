<?php
/* ------------------------------------------------------------------------- *
 *  PRINT SKOP LOGS
/* ------------------------------------------------------------------------- */
final class HA_skop_dev_logs {
    public $changeset_post = null;
    public $changeset_data = array();
    public $skope_meta_key;
    public $display_header;
    public $tested_option;

    public $skope_post_id;


    function __construct( $args = array() ) {
        if ( ! defined('CZR_DEV') || true !== CZR_DEV || ! apply_filters('ha_print_skope_logs' , true ) )
          return;
        $defaults = array(
          'hook' => '__before_content',
          'display_header' => true
        );

        $args = wp_parse_args( $args, $defaults );

        $keys = array_keys( get_object_vars( $this ) );
        foreach ( $keys as $key ) {
          if ( isset( $args[ $key ] ) ) {
            $this->$key = $args[ $key ];
          }
        }
        //Set the property values
        $this -> skope_post_id = ha_get_skope_post_id();

        if ( HU_AD() -> ha_is_customize_preview_frame()  ) {
            add_action( 'wp', function() {
              global $wp_customize;
              if ( HU_AD() -> ha_is_changeset_enabled() && $wp_customize -> changeset_post_id() ) {
                $this -> changeset_post = get_post( $this -> _get_changeset_post_id() );
                $this -> changeset_data = $wp_customize -> changeset_data();
              }

            });
            $this -> skope_meta_key = HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name');
            add_action( $args['hook'] , array( $this, '_print_skop_preview_logs' ) );
        } else {
            add_action( $args['hook'] , array( $this, '_print_skop_front_logs' ) );
        }

        add_action('wp_head', array( $this, '_print_log_style') );
    }



    /* ------------------------------------------------------------------------- *
     *  PREVIEWER VIEW
    /* ------------------------------------------------------------------------- */
    function _print_skop_preview_logs() {
        global $wp_customize;
        ?>
        <div id="skop-log">
            <div class="col-50">
              <?php $this -> _print_skope_infos(); ?>
              <?php //$this -> _print_skope_customized_values(); ?>
              <?php $this -> _print_changeset_infos(); ?>
              <?php $this -> _print_changeset_values(); ?>
              <?php $this -> _print_posted_vars(); ?>
            </div>

            <div class="col-50">
              <?php $this -> _print_skope_post_values(); ?>
              <?php $this -> _print_theme_mods(); ?>
              <?php $this -> _print_raw_option_values(); ?>
            </div><!-- col-50 -->
          </div><!-- #skop-log -->
        <?php
    }//_print_skop_logs()



    /* ------------------------------------------------------------------------- *
     *  FRONT END VIEW
    /* ------------------------------------------------------------------------- */
    function _print_skop_front_logs() {
        ?>
        <div id="skop-log">
            <div class="col-50">
                <h2>SKOPE OPTIONS VALUES</h2>
                <strong>LOCAL META ha_get_cached_opt( 'local' ). Meta key : <?php echo HA_SKOP_OPT() -> ha_get_skope_opt_name( 'local' ); ?> </strong><br/>
                <pre>
                  <?php print_r( HA_SKOP_OPT() -> ha_get_cached_opt( 'local' ));  ?>
                </pre>

                <strong>GROUP META ha_get_cached_opt( 'group' ). Meta key : <?php echo HA_SKOP_OPT() -> ha_get_skope_opt_name( 'group' ); ?> </strong><br/>
                <pre>
                  <?php print_r( HA_SKOP_OPT() -> ha_get_cached_opt( 'group' ) );  ?>
                </pre>

                <strong>ALL SKOPE POST METAS ha_get_skope_opt() ?> </strong><br/>
                <pre>
                  <?php print_r( HA_SKOP_OPT() -> ha_get_skope_opt() );  ?>
                </pre>

                <h2>GLOBAL OPTION get_option( 'hu_theme_options' ) </h2>
                <pre>
                  <?php print_r( get_option( 'hu_theme_options' ) );  ?>
                </pre>
            </div>

            <div class="col-50">
                <?php $this -> _print_theme_mods(); ?>
                <h2>SKOPE POST</h2>
                <strong>SKOPE POST ID : <?php echo get_option('skope-post-id'); ?> </strong><br/>

                <h2>TEST SKOPE OPTIONS VALUES : <?php echo $this -> tested_option; ?></h2>
                <strong>LOCAL ha_get_cached_opt( 'local', '<?php echo $this -> tested_option; ?>' ) </strong><br/>
                <pre>
                  <?php print_r( HA_SKOP_OPT() -> ha_get_cached_opt( 'local', $this -> tested_option ) );  ?>
                </pre>

                <strong>GROUP ha_get_cached_opt( 'group', '<?php echo $this -> tested_option; ?>' ) </strong><br/>
                <pre>
                  <?php print_r( HA_SKOP_OPT() -> ha_get_cached_opt( 'group', $this -> tested_option ) );  ?>
                </pre>

                <strong>GLOBAL hu_get_option( '<?php echo $this -> tested_option; ?>' ) </strong><br/>
                <pre>
                  <?php print_r( hu_get_option( $this -> tested_option ) );  ?>
                </pre>
            </div><!-- col-50 -->
          </div><!-- #skop-log -->
        <?php
    }



    /* ------------------------------------------------------------------------- *
     *  HELPERS
    /* ------------------------------------------------------------------------- */
    function _get_changeset_post_id() {
      if ( ! HU_AD() -> ha_is_changeset_enabled() )
        return;
      global $wp_customize;
      $post_id = $wp_customize -> changeset_post_id();
      //Are we in 4.9 ? $wp_customize -> autosaved() has been introduced in 4.9
      if ( method_exists ( $wp_customize, 'autosaved' ) ) {
        if ( $wp_customize->autosaved() ) {
          $autosave_post = wp_get_post_autosave( $post_id, get_current_user_id() );
          if ( $autosave_post ) {
            $post_id = $autosave_post->ID;
          }
        }
      }
      if ( false == $post_id || empty( $post_id ) ) {
        if ( isset( $_POST['customize_changeset_uuid'] ) && ! empty($_POST['customize_changeset_uuid']) ) {
          global $wpdb;
          $title = $_POST['customize_changeset_uuid'];
          $post_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $title . "'" );
        }
      }
      return $post_id;
    }




    /* ------------------------------------------------------------------------- *
     *  SUB VIEWS
    /* ------------------------------------------------------------------------- */
    function _print_posted_vars() {
        global $wp_customize;
        if ( empty($_POST) || ! is_array( $_POST ) )
          return;
        $raw_posted = array();
        foreach ($_POST as $key => $value) {
          $raw_posted[$key] = is_array( $value ) ? json_decode( wp_unslash( $value), true ) : $value;
        }
        ?>
          <h2>CUSTOMIZED POSTED VALUES ?</h2>
        <?php
        ?>
        <p>
          <pre>
            <?php print_r( $raw_posted ); ?>
          </pre>
        </p>
        <?php
    }


    function _print_changeset_infos() {
        ?>
        <h2>CHANGESET INFOS</h2>
          <p>
            IS CHANGESET ENABLED ? <?php if( function_exists('HU_AD') ) { echo HU_AD() -> ha_is_changeset_enabled(); } ?>
          </p>
          <p>
            CHANGESET POST ID :<?php echo $this -> _get_changeset_post_id(); ?>
          </p>
        <?php
    }


    function _print_skope_infos() {
        ?>
        <h2>SKOPE INFOS</h2>
          <p>
            Posted opt_name : <?php echo HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'opt_name' );  ?>
          </p>
          <p>
            Posted skope_id : <?php echo HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' );  ?>
          </p>
          <p>
            Current customized skope : <?php echo HA_SKOP_OPT() -> ha_get_current_customized_skope(); ?>
          </p>
        <?php
    }


    function _print_skope_customized_values() {
        global $wp_customize;
        $current_skope = HA_SKOP_OPT() -> ha_get_current_customized_skope();
        ?>
        <h2><?php echo strtoupper($current_skope); ?> SKOPE CUSTOMIZED VALUE</h2>
          <p>
            <strong>Changeset values : _get_server_ready_skope_changeset( $skope_meta_key )</strong><br/>
            <pre>
              <?php print_r( HA_SKOP_OPT() -> _get_server_ready_skope_changeset( $this -> skope_meta_key ) );  ?>
            </pre>
          </p>

          <p>
            <strong>Posted values : _get_skope_posted_values( $skope_id )</strong><br/>
            <pre>
              <?php print_r( HA_SKOP_OPT() -> _get_skope_posted_values( HA_SKOP_OPT() -> ha_get_sanitized_post_value( 'skope_id' ) ) );  ?>
            </pre>
          </p>


          <p>
            <strong>Merged customized values : ha_get_unsanitized_customized_values()</strong><br/>
            <pre>
              <?php print_r( HA_SKOP_OPT() -> ha_get_unsanitized_customized_values() );  ?>
            </pre>
          </p>

          <p>
            <strong>Tested option customized value : (" <?php echo $this -> tested_option . ' ")'; ?></strong><br/>
            <pre>
              <?php print_r( HA_SKOP_OPT() -> _ha_get_simple_sanitized_customized_value( $this -> tested_option, $skope_id ) );  ?>
            </pre>
          </p>
        <?php
    }


    function _print_changeset_values() {
        global $wp_customize;
        $changeset_post_id = $this -> _get_changeset_post_id();
        ?>
          <h2>CHANGESET VALUES</h2>
          <p>
            <strong>Local skope meta changeset : get_post_meta( $_id , $this -> skope_meta_key, true )</strong><br/>
            <pre>
              <?php print_r( get_post_meta( $changeset_post_id , $this -> skope_meta_key, true ) );  ?>
            </pre>
          </p>

          <p>
            <strong>All Changeset Post Metas : get_post_meta( $changeset_post_id )</strong><br/>
            <?php
              $_to_print = array();
              $_opt = get_post_meta( $changeset_post_id );
              $_opt = is_array( $_opt ) ? $_opt : array();
              foreach ( $_opt as $meta_key => $value ) {
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

          <p>
            <strong>Changeset Post Content : get_post( $changeset_post_id ) -> post_content</strong><br/>
            <pre>
              <?php if ( isset( $this -> changeset_data ) ) print_r( $this -> changeset_data ); ?>
            </pre>
          </p>
        <?php
    }


    function _print_theme_mods() {
        ?>
          <h2>THEME MODS</h2>

          <p>
            <strong>All Theme Mods</strong><br/>
            <?php
              $_to_print = array();
              $_opt = get_theme_mods();
              foreach ( $_opt as $key => $value ) {
                if ( is_array($value) )
                  $_to_print[$key] = array_map('maybe_unserialize', $value);
                else
                  $_to_print[$key] = $value;
              }
            ?>
            <pre>
              <?php print_r( $_to_print );  ?>
            </pre>
          </p>
        <?php
    }


    function _print_skope_post_values() {
        ?>
          <h2>SKOPE POST VALUES</h2>
          <p><strong>Skope Post ID : <?php echo $this -> skope_post_id;?> </strong><br/></p>
          <p><strong>Skope Post Status : <?php echo get_post_status( $this -> skope_post_id ); ?> </strong><br/></p>
          <p><strong>Skope Post type : <?php echo get_post_type( $this -> skope_post_id ); ?> </strong><br/></p>
          <p>
            <strong>All Skope Post Metas : get_post_meta( $this -> skope_post_id )</strong><br/>
            <?php
              $_to_print = array();
              $_opt = get_post_meta( $this -> skope_post_id );
              foreach ( $_opt as $meta_key => $value ) {
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
        <?php
    }



    function _print_raw_option_values() {
      ?>
        <h2>RAW OPTION VALUES </h2>
        <p>
          <strong>Tested option raw value : (" <?php echo $this -> tested_option . ' ")'; ?> : hu_get_raw_option( $this -> tested_option )</strong><br/>
          <pre>
            <?php print_r( hu_get_raw_option( 'copyright' ) ); ?>
          </pre>
        </p>

        <strong>DB all theme options</strong>
        <p>
          <pre>
            <?php //print_r( get_option('hu_theme_options') ); ?>
          </pre>
        </p>
      <?php
    }







    /* ------------------------------------------------------------------------- *
     *  STYLE
    /* ------------------------------------------------------------------------- */
    function _print_log_style() {
      ?>
      <style id="skop-log-style" type="text/css">
        #skop-log {
          font-size: 13px;
          line-height: 18px;
          color: #ffa500;
          background: #000000c9;
          float: left;
        }
        #skop-log h2 {
          color: #00ff60;
          border-bottom: 1px solid;
          margin: 20px 0 5px;
        }
        #skop-log pre {
          white-space: pre-wrap;
        }
        #skop-log .col-50 {
          width: calc(48% - 1px);
          padding: 0 1%;
          float: left;
          border-left: 1px dotted #ffa500;
        }
        <?php if ( ! $this -> display_header ) : ?>
          #header .container-inner {
            display: none;
          }
        <?php endif; ?>
      </style>
      <?php
    }
}//class

?>