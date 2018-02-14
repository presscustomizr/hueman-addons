<?php
add_action( 'customize_controls_print_footer_scripts', 'ha_print_skope_templates' );
//data example :
// id:"global"
// ctx:"_all_"
// dyn_type:"option"
// el:"czr-scope-global"
// is_default:true
// is_winner:false
// opt_name:"hu_theme_options"
function ha_print_skope_templates() {
  ?>

    <?php /* SINGLE SKOPE TMPL @to_translate */ ?>
    <script type="text/html" id="tmpl-czr-skope">
      <div class="{{data.el}} {{data.skope}} czr-scope inactive" data-scope-id="{{data.id}}" data-dyn-type="{{data.dyn_type}}">
        <div class="czr-scope-header">
          <span class="czr-scope-reset fas fa-sync czr-pull-left" title="Reset"></span>
          <span class="czr-scope-switch fas fa-toggle-off czr-pull-right" title="Switch to / active ?"></span>
        </div>
        <div class="czr-scope-content"><h4 class="czr-skp-switch-link">{{data.title}}</h4></div>
        <div class="czr-scope-footer">
          <# if ( data.is_winner ) { #>
            <span class="czr-scope-winner fas fa-check czr-pull-left info" title="This scope of options will override any other scopes set site wide or in a parent scope."></span>
          <# } #>
          <span class="czr-scope-force fas fa-exclamation-circle czr-pull-right" title="Force priority"></span>
        </div>
      </div>
    </script>



    <?php /* RESET SKOPE PANEL TMPL @to_translate */ ?>
    <script type="text/html" id="tmpl-czr-skope-pane">
      <div id="czr-skope-pane" data-scope-id="{{data.id || ''}}">
        <# if ( data.is_skope_loading ) { #>
            <div class="czr-css-loader czr-mr-loader">
                <div></div>
                <div></div>
                <div></div>
            </div>
        <# } else { #>
            <div class="czr-reset-content">
              <div class="czr-reset-warning">
                  <h2>{{data.warning_message}}</h2>
                  <p>This can not be undone</p>
                  <span class="button czr-scope-do-reset">YES RESET NOW</span>&nbsp;
                  <span class="button czr-scope-reset-cancel">CANCEL</span>
              </div>
              <div class="czr-spinner-wrapper">
                  <span class="czr-spinner"></span>
              </div>
              <div class="czr-reset-success">
                  <h2>{{data.success_message}}</h2>
              </div>
              <div class="czr-reset-fail">
                  <h2>There was a problem when trying to reset {{data.id}}.</h2>
              </div>
            </div>
        <# } #>
      </div>
    </script>


    <?php /* RESET CONTROL TMPL @to_translate */ ?>
    <script type="text/html" id="tmpl-czr-reset-control">
      <div class="czr-remove-alert-wrapper czr-ctrl-reset-warning">
        <div class="czr-crtl-reset-dialog">
            <p>{{data.warning_message}}</p>
            <# if ( ! data.is_authorized ) { #>
                <span class="customize-control-title">{{ data.label }}</span>
                <span class="czr-cancel-button button">Close</span>
            <# } else { #>
                <span class="czr-control-do-reset button">Yes</span> <span class="czr-cancel-button button">No</span>
                <span class="czr-spinner"></span>
            <# } #>
        </div>
        <div class="czr-reset-success">
            <p>{{data.success_message}}</p>
        </div>
        <div class="czr-reset-fail">
            <p>There was a problem when trying to reset.</p>
        </div>
      </div>
    </script>


    <?php /* PREVIEW TOP NOTE @to_translate */ ?>
    <script type="text/html" id="tmpl-czr-top-note">
      <div id="czr-top-note" class="czr-preview-notification">
            <div class="czr-note-content">
                <span class="fas fa-arrow-left"></span>
                <h2>{{data.title}}</h2>
                <p class="czr-note-message"></p>
                <span class="fas fa-times czr-preview-note-close" title="close"></span>
            </div>
      </div>
    </script>

    <?php /* PREVIEW BOTTOM INFORMATIONS */ ?>
    <script type="text/html" id="tmpl-czr-bottom-infos">
      <div id="czr-bottom-infos" class="czr-preview-notification">
            <div class="czr-note-content">
                <h2>{{data.title}}</h2>
                <p class="czr-note-message"></p>
                <span class="fas fa-times czr-preview-note-close" title="<?php _e( 'Close', 'hueman'); ?>"></span>
            </div>

            <p class="action-links-wrap">
                <span class="action-link">
                  <span class="fas fa-info-circle"></span><a class="czr-doc-link" href="http://docs.presscustomizr.com/article/256-using-the-customizer-in-the-hueman-wordpress-theme" title="<?php _e( 'Using the customizer in the Hueman theme', 'hueman'); ?>" target="_blank"> <?php _e( 'Using the customizer in the Hueman theme', 'hueman'); ?></a>
                </span>
                <span class="action-link">
                  <span class="fas fa-cog"></span><span class="czr-disable-bottom-infos" title="<?php _e( 'Permanently disable this notification', 'hueman'); ?>"> <?php _e( 'Permanently disable this notification.', 'hueman'); ?></span>
                </span>
            </p>
      </div>
    </script>
  <?php
}




