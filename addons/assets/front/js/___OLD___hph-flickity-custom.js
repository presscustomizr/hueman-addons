jQuery( function($) {
      //center slider
      //SLIDER IMG + VARIOUS
      setTimeout( function() {
            //centering per slider
            $.each( $( '.hu-carousel .carousel-inner') , function() {
              $( this ).centerImages( {
                    enableCentering : 1, // == HUParams.centerSliderImg,
                    imgSel : '.carousel-image img',
                    /* To check settle.flickity is working, it should according to the docs */
                    oncustom : ['settle.flickity', 'simple_load'],
                    defaultCSSVal : { width : '100%' , height : 'auto' },
                    useImgAttr : true,
                    zeroTopAdjust: 0
              });
              //fade out the loading icon per slider with a little delay
              //mostly for retina devices (the retina image will be downloaded afterwards
              //and this may cause the re-centering of the image)
              /*
              var self = this;
              setTimeout( function() {
                  $( self ).prevAll('.czr-slider-loader-wrapper').fadeOut();
              }, 500 );
              */
            });
      } , 50);

      /*
      * slider parallax on flickity ready
      * we parallax only the flickity-viewport, so that we don't parallax the carouasel-dots
      */
      czrapp.$_body.on( 'hu-flickity-ready.flickity', '.hu-parallax-slider', function( evt ) {
            $(evt.target).children('.flickity-viewport').czrParallax();
      });


      /* Flickity ready
      * see https://github.com/metafizzy/flickity/issues/493#issuecomment-262658287
      */
      var activate = Flickity.prototype.activate;
      Flickity.prototype.activate = function() {
            if ( this.isActive ) {
              return;
            }
            activate.apply( this, arguments );
            this.dispatchEvent('hu-flickity-ready');
      };

      /*Handle custom nav */
      // previous
      czrapp.$_body.on( 'click tap prev.hu-slider', '.slider-prev', slider_previous );
      // next
      czrapp.$_body.on( 'click tap next.hu-slider', '.slider-next', slider_next );

      /* Test only !!!!!! MAIN SLIDER */
      $('.carousel-inner', '[id^="hu-slider-main"]').flickity({
            prevNextButtons: false,
            pageDots: true,
            /*
            * From flickity docs
            * At the end of cells, wrap-around to the other end for infinite scrolling.
            */
            wrapAround: true,
            imagesLoaded: true,
            //lazyLoad ?
            /*
            * From flickity docs
            * Sets the height of the carousel to the height of the tallest cell. Enabled by default setGallerySize: true.
            */
            setGallerySize: false,
            cellSelector: '.carousel-cell',
            /*
            * From flickity docs
            * The number of pixels a mouse or touch has to move before dragging begins.
            * Increase dragThreshold to allow for more wiggle room for vertical page scrolling on touch devices.
            * Default dragThreshold: 3.
            */
            dragThreshold: 10,
            /*
            * From flickity docs
            * Auto-playing will pause when mouse is hovered over,
            * and resume when mouse is hovered off. Auto-playing will stop when
            * the carousel is clicked or a cell is selected.
            */
            autoPlay: true, // {Number in milliseconds }
            /*
            * Set accessibility to false as it produces the following issue:
            * - flickity, when accessibiity is set to true, sets the "carousel" tabindex property
            * - dragging a slide the carousel is focused with focus(), because of the tabindex the page scrolls to top
            * and flickity re-scrolls to the correct position.
            * The scroll to top (due to the focus) for some reason conflicts with the #customizr-slider-* overflow:hidden property
            * when parallaxing.
            * Basically the parallaxed item, despite the top property is set to Y <> 0, appears as it had Y = 0.
            * Plus absoluted elements referring to the #customizr-slider-* seems to be shifted up of -Y
            * very weird behavior to investigate on :/
            */
            accessibility: false,
      });

      /* Handle sliders nav */
      /*Handle custom nav */
      // previous
      function slider_previous(evt) {
            evt.preventDefault();

            var $_this    = $(this),
                _flickity = $_this.data( 'controls' );

            //if not already done, cache the slider this control controls as data-controls attribute
            if ( ! _flickity ) {
              _flickity   = $_this.closest('.hu-carousel').find('.flickity-enabled').data('flickity');
              $_this.data( 'controls', _flickity );
            }

            _flickity.previous();
      }

      // next
      function slider_next(evt) {
            //evt.preventDefault();

            var $_this    = $(this),
                _flickity = $_this.data( 'controls' );

            //if not already done, cache the slider this control controls as data-controls attribute
            if ( ! _flickity ) {
              _flickity   = $_this.closest('.hu-carousel').find('.flickity-enabled').data('flickity');
              $_this.data( 'controls', _flickity );
            }

            _flickity.next();
      }
});