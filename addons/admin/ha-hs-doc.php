<?php
add_action( 'current_screen', 'ha_schedule_welcome_page_actions');
function ha_schedule_welcome_page_actions() {
    $screen = get_current_screen();
    //@see hueman/functions/admin/class-admin-page.php
    if ( 'appearance_page_welcome' != $screen-> id )
      return;

    add_filter( 'hu_display_doc_support_content',  '__return_false' );
    add_filter( 'hu_after_welcome_admin_intro', 'ha_print_hs_doc_content');
    add_action( 'admin_enqueue_scripts', 'ha_enqueue_hs_doc_js' );
    add_action( 'admin_head', 'ha_write_hs_js_style' );
}

//hook : hu_after_welcome_admin_intro
function ha_print_hs_doc_content() {
    ?>
      <form enctype="multipart/form-data" method="post" class="frm-show-form " id="form_m3j26q22">
        <div class="frm_form_fields ">
          <fieldset>
            <div id="frm_field_335_container" class="frm_form_field form-field  frm_top_container helpscout-docs">
              <label for="field_6woxqa" class="frm_primary_label">
                <h2>Search the knowledge base</h2>
                <h4 style="text-align:center;font-style: italic;font-weight: normal;">In a few keywords, describe your issue or the information you are looking for.</h4>
                  <span class="frm_required"></span>
              </label>
              <input type="text" id="field_6woxqa" name="item_meta[335]" value="" placeholder="Ex. Logo upload" autocomplete="off">

              <div class="frm_description"><u>Search tips</u> : If you get too many results, try to narrow down your search by prefixing it with the words "Hueman" or "Hueman Pro" for example. If there's no results, try different keywords and / or spelling variations </div>
            </div>
          </fieldset>
        </div>
      </form>
    <?php
}

//hook : admin_enqueue_scripts
function ha_enqueue_hs_doc_js() {
    $screen = get_current_screen();
    if ( 'appearance_page_welcome' != $screen-> id )
      return;

    wp_enqueue_script(
      'hs-search',
      sprintf('%1$saddons/assets/back/js/hs-search.min.js' , HA_BASE_URL ),
      array( 'jquery', 'underscore' ),
      null,
      $in_footer = false
    );
    $script_settings = array(
      'debug' => false, // Print debug logs or not
      'searchDelay' => 750, // Delay time in ms after a user stops typing and before search is performed
      'minLength' => 3, // Minimum number of characters required to trigger search
      'limit' => 25, // Max limit for # of results to show
      'text' => array(
        'result_found' => __('We found {count} article that may help:'),
        'results_found' => __('We found {count} articles that may help:'),
        'no_results_found' => __('No results found&hellip;'),
        'enter_search' => __('Please enter a search term.'),
        'not_long_enough' => __('Search must be at least {minLength} characters.'),
        'error' => __('There was an error fetching search results.'),
      ),
      'template' => array(
        'wrap_class' => 'docs-search-wrap',
        'before' => '<ul class="docs-search-results">',
        //'item' => '<li class="article"><a href="{url}" title="{preview}" target="_blank">{name}</a></li>',
        //'item' => '<li class="article"><a data-beacon-article="{id}" href="#" title="{preview}">{name}</a><a href="{url}" class="article--open-original" title="Open the article in a new window" rel="noopener noreferrer" target="_blank"></a></li>',
        // 'item' => '<li class="article"><a data-beacon-article="{id}" href="#" title="Read the full article">{name}</a><a href="{url}" class="article--open-original" title="Open the article in a new window" rel="noopener noreferrer" target="_blank"></a><p class="article-preview">{preview} ... <a data-beacon-article="{id}" href="#" title="Read the full article">read more</a></p></li>',
        'item' => '<li class="article"><a href="{url}" title="Read the full article" target="_blank">{name}<span class="article--open-original" ></span></a><p class="article-preview">{preview} ... <a href="{url}" title="Read the full article" target="_blank">read more</a></p></li>',
        'after' => '</ul>',
        'results_found' => '<span class="{css_class}">{text}</span>',
      ),
      'collections' => array(), // The collection IDs to search in

      // Do not modify
      '_subdomain' => 'presscustomizr',
    );

    wp_localize_script( 'hs-search', 'GF_HS_Settings', $script_settings );
}

//hook : admin_head
function ha_write_hs_js_style() {
    $screen = get_current_screen();
    if ( 'appearance_page_welcome' != $screen-> id )
      return;
    ?>
    <style type="text/css" id="hs-doc-style">
          body { background:white!important;}
          .about-wrap h1 {
            font-size: 2em;
          }
          .helpscout-docs input[type="text"] {
              font-size: 1.5em!important;
              padding: 15px!important;
              color: #444444;
              background-color: #eeeeee;
              border-color: #dddddd;
              border-width: 1px;
              border-style: solid;
              -moz-border-radius: 0px;
              -webkit-border-radius: 0px;
              border-radius: 0px;
              width: 100%;
              max-width: 100%;
              font-size: 13px;
              padding: 2px;
              -webkit-box-sizing: border-box;
              -moz-box-sizing: border-box;
              box-sizing: border-box;
              outline: none;
              font-weight: normal;
              box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
          }
          .helpscout-docs div.frm_description {
              margin: 0;
              padding: 0;
              font-family: "Lucida Grande","Lucida Sans Unicode",Tahoma,sans-serif;
              font-size: 11px;
              color: #666666;
              font-weight: normal;
              text-align: left;
              font-style: normal;
              max-width: 100%;
              font-style: italic;
              margin-top: 1em;
          }
          span.results-found {
              font-size: 18px;
              display: block;
              padding-top: 10px;
              margin: 0 auto;
              color: #999;
          }
          .docs-search-results {
              text-align: left;
              padding-left: 0;
              border: 3px solid #eaeaea;
              border-radius: 4px;
              margin: 30px auto !important;
              max-width: 700px;
          }
          .docs-search-results:before {
              content: "Please review our complete list of suggestions to proceed.";
              display: block;
              background: #f7f7f7;
              color: #999;
              font-size: 16px;
              text-transform: uppercase;
              padding: 10px 20px;
              border-bottom: 3px solid #f1f1f1;
          }
          .docs-search-results li.article {
              border-bottom: 1px solid #f1f1f1;
              margin: 0;
              position: relative;
          }
          .docs-search-results li {
              list-style: none !important;
          }
          .docs-search-results li.article a {
              display: inline-block;
              position: relative;
              font-size: 18px;
              padding: 15px 20px 0px 20px;
          }
          .docs-search-results li.article:hover {
              background: #f7f7f7;
              /*color: #2849ff;*/
          }
          .gf-hs-spinner {
            background: url( "<?php echo admin_url( 'images/spinner.gif' ); ?>" ) no-repeat;
            -webkit-background-size: 20px 20px;
            background-size: 20px 20px;
            display: inline-block;
            opacity: 0.7;
            filter: alpha(opacity=70);
            width: 20px;
            height: 20px;
            margin: 0;
            vertical-align: middle;
          }
          .article--open-original {
              background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAQtJREFUSA3tlLEOgkAMhjljfAP1IdTJJ3DVGR4SBidcnRzd5CV0cmU5/164pFyAXhU2SciV0n7/tb1ckkz8GOLneV4aY459WtbaB/7tsyyreYyQ90zTdD2jhCE4ByrtFcXPeRIUXUXcN2SjopP/j2qW2OgV3xvvo9VVwB3f2AG84oyfBUI45nUYTaALjra9RhGIgZNQa8hceciW4GjTBfmWGGoBCU5QfrpUQ46BkwB/VAJIPOOlc17RaQkHysHe1gq8Ab7FwklENQPeW7/DrhWtXJAf8bVKoAvW47s3/t0kAriTtl5YOwOfF73+BcRWTd6i1ikqisJdUOK2FAGugub2U6TJoWCWctQIER9Yb3NkxwQ6WgAAAABJRU5ErkJggg==) no-repeat;
              background-size: 100%;
              display: inline-block;
              height: 16px;
              opacity: 1;
              position: relative;
              top: 2px;
              width: 16px;
              margin: 0 5px
          }
          .article--open-original:hover {
              background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAQtJREFUSA3tlLEOgkAMhjljfAP1IdTJJ3DVGR4SBidcnRzd5CV0cmU5/164pFyAXhU2SciV0n7/tb1ckkz8GOLneV4aY459WtbaB/7tsyyreYyQ90zTdD2jhCE4ByrtFcXPeRIUXUXcN2SjopP/j2qW2OgV3xvvo9VVwB3f2AG84oyfBUI45nUYTaALjra9RhGIgZNQa8hceciW4GjTBfmWGGoBCU5QfrpUQ46BkwB/VAJIPOOlc17RaQkHysHe1gq8Ab7FwklENQPeW7/DrhWtXJAf8bVKoAvW47s3/t0kAriTtl5YOwOfF73+BcRWTd6i1ikqisJdUOK2FAGugub2U6TJoWCWctQIER9Yb3NkxwQ6WgAAAABJRU5ErkJggg==) no-repeat!important;
              opacity: 0.5;
              background-size: 100%!important;
          }
          .docs-search-results .article-preview {
            padding: 4px 20px;
            font-size: 0.8em;
            line-height: 1.4em;
            font-style: italic;
            color: #808080
          }
          .docs-search-results .article-preview a {
            display: inline!important;
            font-size: inherit!important;
            padding: 0!important;
          }
        </style>
    <?php
}