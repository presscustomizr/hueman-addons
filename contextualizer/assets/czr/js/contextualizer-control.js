// global contxLocalizedParams
var CZRContextualizerModuleMths = CZRContextualizerModuleMths || {};

( function ( api, $, _ ) {
      $.extend( CZRContextualizerModuleMths, {

            initialize: function( id, constructorOptions ) {
                  var module = this;

                  module.initialConstrucOptions = $.extend( true, {}, constructorOptions );//detach from the original obj

                  // SKOPE LEVEL CHECK
                  // the skopeLevel param must be passed when instanciating the control
                  if ( ! _.has( constructorOptions.control.params, 'skopeLevel' ) || ! _.contains( ['group', 'local' ], constructorOptions.control.params.skopeLevel ) ) {
                        throw new Error( 'initialize skope module => missing skope level' );
                  }

                  // SKOPE ID CHECK
                  // the skopeId param must be passed when instanciating the control
                  if ( ! _.has( constructorOptions.control.params, 'skopeId' ) ) {
                        throw new Error( 'initialize skope module => missing skopeId' );
                  }



                  // extend the module with new template Selectors
                  // the itemInputList is a fn() of the setting type
                  // $.extend( module, {
                  //       itemPreAddEl : '',// 'czr-module-flat-skope-pre-item-input-list',
                  //       itemInputList : ''
                  // } );

                  //EXTEND THE DEFAULT CONSTRUCTORS FOR INPUTS
                  module.inputConstructor = api.CZRInput.extend( module.CZRFlatSkopeItemInputCtor || {} );

                  //EXTEND THE DEFAULT CONSTRUCTORS FOR ITEMS AND MODOPTS
                  module.itemConstructor = api.CZRItem.extend( module.CZRFlatSkopeItemCtor || {} );

                  // run the parent initialize
                  api.CZRDynModule.prototype.initialize.call( module, id, constructorOptions );

                  //DEFINE THE MODULE SKOPE CONSTANT.
                  //can be 'local' or 'group'
                  module.SKOPE_LEVEL = constructorOptions.control.params.skopeLevel;
                  module.SKOPE_ID = constructorOptions.control.params.skopeId;

                  //declares a default Item model
                  // 'id'          => '',
                  // 'title'       => '',
                  // 'skope-id'    => 'home',
                  // 'setting-id'  => '',
                  // 'type'        => '',
                  // 'value'       => ''
                  this.defaultItemModel = contxLocalizedParams.defaultModel;

                  // FIRE MODULE READY
                  //1) on section expansion
                  //2) or in the case of a module embedded in a regular control, if the module section is alreay opened => typically when skope is enabled
                  //3) on skope change, remotely triggered from api.czr_skopebase.trigger( 'set-module-ready' ) after dynamic instantiation
                  // if ( _.has( api, 'czr_activeSectionId' ) && module.control.section() == api.czr_activeSectionId() && 'resolved' != module.isReady.state() ) {
                  //     module.ready();
                  // }
                  // api.section( module.control.section() ).expanded.bind( function() {
                  //       if ( 'resolved' == module.isReady.state() )
                  //         return;
                  //       api.czr_initialSkopeCollectionPopulated.then( function() {
                  //             module.ready();
                  //       });
                  // });

                  //Awake the module with a remote signal
                  module.control.bind( 'set-module-ready', function() {
                        module.ready();
                  });

                  // When the collection of input in the pre-item are ready, implement the dependencies
                  // Example : id => changes title
                  module.bind( 'pre-item-input-collection-ready', function() {
                        // setup model values and dependencies on init.
                        if ( serverControlParams.isDevMode ) {
                              module.setupPreItemInputs();
                        } else {
                              try {
                                    module.setupPreItemInputs();
                              } catch (error) {
                                   api.errorLog( 'flat skope => ' + error );
                              }
                        }
                  });

                  // PREVENT ITEM ADDITION IF ALL SETTINGS HAVE BEEN CUSTOMIZED
                  // <=> no remaining setting available for this skope_id
                  // @param canWe = { addTheItem : true }
                  module.bind( 'is-item-addition-possible', function( canWe ) {
                        if ( ! _.isObject( canWe ) || _.isUndefined( canWe.addTheItem ) ) {
                              return { addTheItem : true };
                        }

                        if ( _.isEmpty( module.setRemainingSkopableSettingIds() ) ) {
                              var sectionId = module.control.section();
                              if ( module.control.section && ! _.isUndefined( module.control.notifications ) ) {
                                    api.section( sectionId ).notifications.add( new api.Notification( 'all-settings-contextualized', {
                                          type: 'info',
                                          message: contxLocalizedParams.i18n['All settings have already been contextualized for this page.'],
                                          dismissible: true
                                    } ) );

                                    // Removed if not dismissed after 5 seconds
                                    _.delay( function() {
                                          api.section( sectionId ).notifications.remove( 'all-settings-contextualized' );
                                    }, 5000 );
                              }
                              canWe.addTheItem = false;
                        }
                        return canWe;
                  });


                  // REACT TO ITEM REMOVAL
                  // UPDATE THE SKOPABLE SETTING COLLECTION WHEN SETTINGS ARE ADDED / REMOVED
                  // AVAILABLE SELECTOR COLLECTION
                  //module.bind( 'items-collection-populated', function() {
                  module.isReady.then( function() {
                        module.bind( 'item-removed', function() {
                              module.setRemainingSkopableSettingIds();
                              api.previewer.refresh();
                              // REFRESH SKOPE INFO IN EACH SKOPABLE CONTROLS
                              api.czr_skopeReact.trigger( 'refresh-skope-notes-in-controls' );
                        });
                        module.bind( 'item-added', function() {
                              // REFRESH SKOPE INFO IN EACH SKOPABLE CONTROLS
                              api.czr_skopeReact.trigger( 'refresh-skope-notes-in-controls' );
                        });
                  });

                  // VARIOUS ACTIONS WHEN MODULE IS READY
                  module.isReady.then( function() {
                        // Always update the local skope-id in the pre-item
                        if ( module.preItem && ! _.isEmpty( module.preItem.czr_Input ) && module.preItem.czr_Input.has('skope-id') ) {
                              module.preItem.czr_Input( 'skope-id' )( module.SKOPE_ID );
                        }

                        // Display the parent control when relevant
                        // @todo => this can be removed now.
                        module.control.active( '_skope_not_set_' != module.SKOPE_ID );
                  });


                  // EXPAND THE PRE-ITEM AND PRE-SELECT A SETTING FOR A POSSIBLE ADDITION
                  // OR EXPAND THE ITEM IF ALREADY ADDED
                  // Triggered on api.control( skopeControlId ).focus({ completeCallback: function() {} );
                  // @see czr_skopeReact::scheduleSkopeReactions()
                  // @param params = { setId : setId }
                  module.control.bind( 'contx-expand-pre-item', function( params ) {
                        if ( module.czr_Item.has( params.setId ) ) {
                              module.czr_Item( params.setId ).viewState( 'expanded' );
                        } else {
                              var _doAndSelfUnbind = function() {
                                    module.preItem.czr_Input( 'setting-id', function( _input_ ) {
                                          var $select = _input_.container.find('select');
                                          $select.val( params.setId ).trigger('change');
                                    });
                                    module.unbind('pre-item-input-collection-ready', _doAndSelfUnbind );
                              };
                              module.bind('pre-item-input-collection-ready', _doAndSelfUnbind );
                              module.preItemExpanded( true );
                        }
                  });
            },//initialize



            //////////////////////////////////////////////////////////
            /// <CORE OVERIDDEN METHODS>
            // This method is fired by the core fmk right before an item instantiation
            // Prevent an item to be instantiated
            // typically when a context has no group skope
            itemCanBeInstantiated : function() {
                  return this.weHaveASkope();
            },


            /////////////////////////////////////////
            // This method is fired by the core fmk right before an item instantiation, when the item candidate has been prepared
            // is fired in CZRModule::instantiateItem() where we check if ( ! item_candidate || _.isNull( item_candidate ) ) after validation
            validateItemBeforeAddition : function( api_ready_item, is_added_by_user ) {
                  var module = this,
                      sectionId = module.control.section(),
                      skopepableSettingCollection = api.czr_skopeReact.getSkopableSettings();

                  // CHECK THAT THE SETTING ID IS SKOPABLE
                  // IT can happen that some unskopable setting are
                  if ( _.contains( contxLocalizedParams.unskopableSettings, api_ready_item['setting-id'] ) )
                    return;

                  // CHECK THAT THE MANDATORY ITEM PROPERTY ARE PROPERLY SET
                  // Default Item model
                  // 'id'          => '',
                  // 'title'       => '',
                  // 'skope-id'    => 'home',
                  // 'setting-id'  => '',
                  // 'type'        => '',
                  // 'value'       => ''
                  // When mapping the old skope data, the type can not be set.
                  // We need to set it now if needed
                  _.each( module.defaultItemModel, function( propertyVal, propertyId ) {
                        switch( propertyId ) {
                              case 'type' :
                                    if ( _.isEmpty( api_ready_item[ propertyId ] ) ) {
                                          var _type_candidate_ = _.findWhere( skopepableSettingCollection, { 'setting-id' : api_ready_item['setting-id'] } );
                                          if ( _.isEmpty( _type_candidate_ ) || ! _.isString( _type_candidate_.type ) ) {
                                                api.errare( 'validateItemBeforeAddition => could not set the item "type" property for item ', api_ready_item );
                                                break;
                                          }
                                          api_ready_item[ 'type' ] = _type_candidate_.type;
                                    }
                              break;
                        }
                  });

                  if ( _.isEmpty( api_ready_item[ 'type' ] ) )
                    return;

                  /// BLOCK ITEM ADDITION AND NOTIFY USER
                  /// when trying to add an item already added for the same pair of setting-id - skope_id
                  if ( api_ready_item.id && module.czr_Item && module.czr_Item.has( api_ready_item.id ) ) {
                        if ( module.control.section && ! _.isUndefined( module.control.notifications ) ) {
                              api.section( sectionId ).notifications.add( new api.Notification( 'item_already_exists', {
                                    type: 'info',
                                    message: contxLocalizedParams.i18n['This setting is already customized for this context.'],
                                    dismissible: true
                              } ) );

                              // Removed if not dismissed after 5 seconds
                              _.delay( function() {
                                    api.section( sectionId ).notifications.remove( 'item_already_exists' );
                              }, 5000 );
                        }
                        return;
                  }
                  return api_ready_item;
            },

            //////////////////////////////////////////////////////////
            /// </CORE OVERIDDEN METHODS>

      });//extend
})( wp.customize , jQuery, _ );// global contxLocalizedParams
var CZRContextualizerModuleMths = CZRContextualizerModuleMths || {};

( function ( api, $, _ ) {
      $.extend( CZRContextualizerModuleMths, {

            //////////////////////////////////////////////////////////
            /// SETUP PRE ITEM MODEL INITIAL VALUES AND INPUT DEPENDENCIES
            //////////////////////////////////////////
           // fired on 'pre-item-input-collection-ready'
            // - on init the module.preItem() is not set yet. => id = "" and title = false.
            //    => we want to set starter values so that even if the user clicks on "Add it" without picking any predefined selector, it creates a valid module item with an id + a title
            // - when the pre-defined id changes, the title should be updated.
            setupPreItemInputs : function() {
                  var module = this,
                        remainingSkopableSettings = module.getRemainingSkopableSettingIds();

                  if ( _.isUndefined( module.preItem.czr_Input ) || ! _.isObject( module.preItem.czr_Input ) ) {
                        api.errorLog('Missing input collection in the pre-item for module : ' + module.id );
                  }
                  // module.preItem.czr_Input( 'title' )( _newTitle );
                  module.preItem.czr_Input.each( function( _input_ ) {
                        switch( _input_.id ) {
                              case 'setting-id' :
                                    _input_.bind( function( settingId ) {
                                          //write a unique id based on the settingid
                                          module.preItem.czr_Input( 'id' )( settingId );

                                          //set the type based on the setting id
                                          var   _setting_data_ = _.findWhere( remainingSkopableSettings, { 'setting-id' : settingId } ),
                                                _type,
                                                _apiValue;

                                          if ( _.isObject( _setting_data_ ) ) {
                                                _type = _setting_data_.type;
                                                if ( api.has( _setting_data_.apiSetId ) ) {
                                                      _apiValue = api( _setting_data_.apiSetId )();
                                                }
                                          }
                                          // console.log('settingId', settingId );
                                          // console.log('remainingSkopableSettings', remainingSkopableSettings );
                                          // console.log('_setting data', _setting_data_ );
                                          // console.log('api.czr_skopeReact.getAuthorizedSettingTypes()', api.czr_skopeReact.getAuthorizedSettingTypes() );

                                          if ( _.isUndefined( _type ) || _.isNull( _type ) || ! _.contains( api.czr_skopeReact.getAuthorizedSettingTypes(), _type ) ) {
                                                api.errorLog( 'No type defined or unauthorized type for setting : ' + settingId );
                                          } else {
                                                module.preItem.czr_Input( 'type' )( _type );
                                          }

                                          // always initialize a local customization with the current api value
                                          module.preItem.czr_Input( 'value' )( _apiValue );
                                          //api.consoleLog( 'module.preItem.', module.preItem() );
                                    });
                              break;
                        }
                  });

                  // Set the current skopeId
                  if ( ! _.has( api.czr_activeSkopes(), module.SKOPE_LEVEL  ) ) {
                        api.errorLog( 'setupPreItemInputs => ' + module.SKOPE_LEVEL + ' was not found in the skope list' );
                        return;
                  } else {
                        module.preItem.czr_Input( 'skope-id' )( api.czr_activeSkopes()[ module.SKOPE_LEVEL ] );
                  }


                  // get the first selector of the collection and set it as initial setting-id
                  if ( remainingSkopableSettings[0] && remainingSkopableSettings[0]['setting-id'] ) {
                        var settingIdOnInit = remainingSkopableSettings[0]['setting-id'];
                        // set it
                        // it will also set the title with the listener above
                        module.preItem.czr_Input( 'setting-id' )( settingIdOnInit );
                  } else {
                        api.errorLog( "Error in flat skope => impossible to set the initial setting id");
                  }

            }
      });//extend
})( wp.customize , jQuery, _ );// global contxLocalizedParams
var CZRContextualizerModuleMths = CZRContextualizerModuleMths || {};

( function ( api, $, _ ) {
      $.extend( CZRContextualizerModuleMths, {
            //////////////////////////////////////////////////////////
            /// <MODULE HELPERS>
            // @return a boolean
            weHaveASkope : function() {
                  return '_skope_not_set_' != api.czr_skopeBase.getSkopeProperty( 'skope_id', this.SKOPE_LEVEL );
            },


            // Returns only the settings that have not been already customized for this skope_level
            // Prevent a given pair of setting_id + skope_id to have more than on item instantiated
            getRemainingSkopableSettingIds : function() {
                  var module = this;

                  if ( ! _.isEmpty( module.remainingSettingIds ) ) {
                        return module.remainingSettingIds;
                  } else {
                        return module.setRemainingSkopableSettingIds();
                  }
            },


            // Returns only the settings that have not been already customized for this skope_level
            // Prevent a given pair of setting_id + skope_id to have more than on item instantiated
            setRemainingSkopableSettingIds : function() {
                  // filter the complete collection of skopable setting
                  // return only the ones that have not been already customized for this skope_id
                  var module = this,
                      currentSkopeLevelId = api.czr_skopeBase.getSkopeProperty( 'skope_id', module.SKOPE_LEVEL ),
                      remainingSettingIds = [],
                      moduleItems = module().items;

                  // if the item collection includes a setting with
                  _.each( api.czr_skopeReact.getSkopableSettings(), function( settingData ) {
                          var itemsWithSameSetId = _.filter( moduleItems, function( _item_ ) { return _item_['setting-id'] == settingData['setting-id']; } ),
                              itemsCandidates = [];
                          // if not added yet, it's part of the remaining
                          if ( _.isEmpty( itemsWithSameSetId ) ) {
                                remainingSettingIds.push( settingData );
                          }
                          // Let's check if the id has been already added for this skope
                          // else {
                          //       itemsCandidates = _.findWhere( itemsWithSameSetId, { 'skope-id' : currentSkopeLevelId } );
                          //       if ( _.isEmpty( itemsCandidates ) ) {
                          //             remainingSettingIds.push( settingData );
                          //       }
                          // }

                  });

                  // cache it
                  module.remainingSettingIds = remainingSettingIds;

                  return remainingSettingIds;
            }
      });//extend
})( wp.customize , jQuery, _ );// global contxLocalizedParams
var CZRContextualizerModuleMths = CZRContextualizerModuleMths || {};
CZRContextualizerModuleMths.CZRFlatSkopeItemInputCtor = CZRContextualizerModuleMths.CZRFlatSkopeItemInputCtor || {};
( function ( api, $, _ ) {
      $.extend( CZRContextualizerModuleMths, {
            //////////////////////////////////////////////////////////
            /// INPUT CONSTRUCTORS
            //////////////////////////////////////////
            CZRFlatSkopeItemInputCtor : $.extend( CZRContextualizerModuleMths.CZRFlatSkopeItemInputCtor , {
                  setupStepper : function() {
                        var input = this,
                            input_parent  = input.input_parent,
                            _model = input_parent(),
                            _skopableSettingData = _.findWhere( api.czr_skopeReact.getSkopableSettings(), { 'setting-id' : _model['setting-id'] } );

                        if ( _.isEmpty( _skopableSettingData ) || ! api.control.has( _skopableSettingData.apiCtrlId ) ) {
                              api.errorLog( 'Skope => setup number input => could not find the original control');
                              return;
                        }

                        var $originalRangeInput = api.control( _skopableSettingData.apiCtrlId ).container.find( 'input[type=number]' ),
                            minAttr = _.isEmpty( $originalRangeInput.attr( 'min' ) ) ? 0 : $originalRangeInput.attr( 'min' ),
                            maxAttr = _.isEmpty( $originalRangeInput.attr( 'max' ) ) ? 3000 : $originalRangeInput.attr( 'max' ),
                            stepAttr = _.isEmpty( $originalRangeInput.attr( 'step' ) ) ? 1 : $originalRangeInput.attr( 'step' );

                        // remove the placeholder
                        if ( 0 < input.container.find('.placeholder-to-be-replaced-by-dynamic-content').length ) {
                              input.container.find('.placeholder-to-be-replaced-by-dynamic-content').remove();
                        }

                        // Append the html
                        input.container.find('.czr-input').append(
                              '<input data-czrtype="value" type="number" id="skoped-' + input.id + '" min="' + minAttr + '" max="' + maxAttr + '" step="' + stepAttr + '" value="' + input() + '">'
                        );
                        $('input[type="number"]',input.container ).each( function( e ) {
                              $(this).stepper();
                        });
                  },

                  // overrides the core
                  // generates the radio input based on the original control
                  setupSimpleRange : function() {
                        var input = this,
                            input_parent  = input.input_parent,
                            _model = input_parent(),
                            _skopableSettingData = _.findWhere( api.czr_skopeReact.getSkopableSettings(), { 'setting-id' : _model['setting-id'] } );

                        if ( _.isEmpty( _skopableSettingData ) || ! api.control.has( _skopableSettingData.apiCtrlId ) ) {
                              api.errorLog( 'Skope => setupRadio input => could not find the original control');
                              return;
                        }

                        var $originalRangeInput = api.control( _skopableSettingData.apiCtrlId ).container.find( 'input[type=range]' ),
                            minAttr = _.isEmpty( $originalRangeInput.attr( 'min' ) ) ? 0 : $originalRangeInput.attr( 'min' ),
                            maxAttr = _.isEmpty( $originalRangeInput.attr( 'max' ) ) ? 0 : $originalRangeInput.attr( 'max' ),
                            stepAttr = _.isEmpty( $originalRangeInput.attr( 'step' ) ) ? 0 : $originalRangeInput.attr( 'step' );

                        // remove the placeholder
                        if ( 0 < input.container.find('.placeholder-to-be-replaced-by-dynamic-content').length ) {
                              input.container.find('.placeholder-to-be-replaced-by-dynamic-content').remove();
                        }

                        // Append the html
                        input.container.find('.czr-input').append(
                              '<input data-czrtype="value" type="range" id="skoped-' + input.id + '" min="' + minAttr + '" max="' + maxAttr + '" step="' + stepAttr + '" value="' + input() + '">'
                        );
                  },

                  // overrides the core
                  // generates the radio input based on the original control
                  setupRadio : function() {
                        var input = this,
                            input_parent  = input.input_parent,
                            _model = input_parent(),
                            _skopableSettingData = _.findWhere( api.czr_skopeReact.getSkopableSettings(), { 'setting-id' : _model['setting-id'] } ),
                            _radioInputValueList = [];

                        if ( _.isEmpty( _skopableSettingData ) || ! api.control.has( _skopableSettingData.apiCtrlId ) ) {
                              api.errorLog( 'Skope => setupRadio input => could not find the original control');
                              return;
                        }
                        api.control( _skopableSettingData.apiCtrlId ).container.find( 'input[type="radio"]' ).each( function() {
                              _radioInputValueList.push( $(this).attr('value') );
                        });

                        if ( _.isEmpty( _radioInputValueList ) ) {
                              api.errorLog( 'Skope => setupRadio input => not able to build a list of radio input');
                              return;
                        }

                        // remove the placeholder
                        if ( 0 < input.container.find('.placeholder-to-be-replaced-by-dynamic-content').length ) {
                              input.container.find('.placeholder-to-be-replaced-by-dynamic-content').remove();
                        }

                        // Append the html
                        _.each( _radioInputValueList , function( radioChoice ) {
                              input.container.find('.czr-input').append( [
                                  '<span class="customize-inside-control-row">',
                                    '<input data-czrtype="value" id="' + [ radioChoice, '-', input.id ].join('') + '" type="radio" value="' + radioChoice + '" name="czr-radio-' + input.id + '">',
                                    '<label for="' + [ radioChoice, '-', input.id ].join('') + '">' + radioChoice +'</label>',
                                  '</span>'
                                ].join('')
                              );
                        } );


                        // setup icheck
                        $( 'input[type=radio]', input.container ).each( function(e) {
                              if ( 0 !== $(this).closest('div[class^="icheckbox"]').length )
                                return;
                              if ( input() == $(this).val() ) {
                                    $(this).attr( 'checked', 'checked' );
                              } else {
                                    $(this).removeAttr('checked');
                              }

                              $(this).iCheck({
                                    checkboxClass: 'icheckbox_flat-grey',
                                    checkedClass: 'checked',
                                    radioClass: 'iradio_flat-grey',
                              })
                              .on( 'ifChanged', function(e){
                                    //$(this).val( false === $(this).is(':checked') ? 0 : 1 );
                                    $(e.currentTarget).trigger('change');
                              });
                        });
                  },


                  // overrides the core
                  setupColorPicker : function() {
                        var input  = this,
                          item = input.input_parent,
                          isHueSlider = false,//this.params.mode === 'hue',
                          updating = false,
                          picker;

                        // Is it an hueSlider ?
                        // We need to get the information from the control associated with the parent item.
                        var apiCtrlId, _ctrl_;
                        _ctrl_ = _.findWhere( api.skopableSettingsCollection(), { 'setting-id' : item()['setting-id'] } );
                        apiCtrlId = _ctrl_.apiCtrlId;
                        if ( ! _.isEmpty( apiCtrlId ) && api.control.has( apiCtrlId ) ) {
                            isHueSlider = api.control( apiCtrlId ).params.mode === 'hue';
                        }

                        if ( isHueSlider ) {
                              picker = input.container.find('input');
                              picker.val( input() ).wpColorPicker({
                                    change: function( event, ui ) {
                                          updating = true;
                                          input( ui.color.h() );
                                          updating = false;
                                    }
                              });
                        } else {
                              picker = input.container.find('input');
                              picker.val( input() ).wpColorPicker({
                                    change: function() {
                                          updating = true;
                                          input( picker.wpColorPicker( 'color' ) );
                                          updating = false;
                                    },
                                    clear: function() {
                                          updating = true;
                                          input( '' );
                                          updating = false;
                                    }
                              });
                        }

                        input.callbacks.add( function ( value ) {
                              // Bail if the update came from the input itself.
                              if ( updating ) {
                                return;
                              }
                              picker.val( value );
                              picker.wpColorPicker( 'color', value );
                        } );

                        // Collapse color picker when hitting Esc instead of collapsing the current section.
                        input.container.on( 'keydown', function( event ) {
                              var pickerContainer;
                              if ( 27 !== event.which ) { // Esc.
                                    return;
                              }
                              pickerContainer = input.container.find( '.wp-picker-container' );
                              if ( pickerContainer.hasClass( 'wp-picker-active' ) ) {
                                    picker.wpColorPicker( 'close' );
                                    input.container.find( '.wp-color-result' ).focus();
                                    event.stopPropagation(); // Prevent section from being collapsed.
                              }
                        } );
                  },

                  // ready : function() {
                  //       api.CZRInput.prototype.ready.call( input);
                  // },
                  // overrides the core
                  setupSelect : function() {
                        // if ( 'selector' != this.id )
                        //   return;

                        var   input      = this,
                              module = input.module;

                        switch( input.id ) {
                              // in pre-item only
                              case 'setting-id' :
                                    input._setupSelectForPreItemSettingId();
                              break;
                              case 'value' :
                                    // the idea is to get the select options from the already rendered control
                                    var   input_parent  = input.input_parent,
                                          _model = input_parent(),
                                          _skopableSettingData = _.findWhere( api.czr_skopeReact.getSkopableSettings(), { 'setting-id' : _model['setting-id'] } ),
                                          _optionsHtml;
                                    if ( ! _.isEmpty( _skopableSettingData ) && api.control.has( _skopableSettingData.apiCtrlId ) ) {
                                          _optionsHtml = api.control( _skopableSettingData.apiCtrlId ).container.find( 'select' ).html();
                                          var $selectInput = $( 'select[data-czrtype="' + input.id + '"]', input.container );
                                          $selectInput.append( _optionsHtml );
                                          // make sure to reset the currently "selected" attributes
                                          // and set the new one based on the input value
                                          $selectInput.find( 'option' ).each( function(){
                                                if ( input() == $(this).attr('value') ) {
                                                      $(this).attr( 'selected', 'selected');
                                                } else {
                                                      $(this).removeAttr( 'selected');
                                                }
                                          } );
                                          $( 'select[data-czrtype="' + input.id + '"]', input.container ).selecter();
                                    } else {
                                          api.errorLog( 'Flat skope error => select input setup => could not find the default control Id' );
                                    }
                              break;
                        }

                  }
            })//CZRFlatSkopeItemInputCtor
      });//extend
})( wp.customize , jQuery, _ );
// global contxLocalizedParams
var CZRContextualizerModuleMths = CZRContextualizerModuleMths || {};
CZRContextualizerModuleMths.CZRFlatSkopeItemInputCtor = CZRContextualizerModuleMths.CZRFlatSkopeItemInputCtor || {};
( function ( api, $, _ ) {
      $.extend( CZRContextualizerModuleMths, {
            //////////////////////////////////////////////////////////
            /// INPUT CONSTRUCTORS
            //////////////////////////////////////////
            CZRFlatSkopeItemInputCtor : $.extend( CZRContextualizerModuleMths.CZRFlatSkopeItemInputCtor , {
                  // PRE ITEM SELECT SETUP
                  // remainingSkopableSettings = [
                  // {
                  //  apiCtrlId:"custom_css"
                  //  apiSetId:"custom_css[twentyseventeen]"
                  //  setting-id:"custom_css[twentyseventeen]"
                  //  type:"code_editor"
                  // }
                  // ...
                  // ]
                  _setupSelectForPreItemSettingId : function() {
                        //generates the options
                        var   input = this,
                              module = input.module,
                              remainingSkopableSettings = module.setRemainingSkopableSettingIds(),
                              _getControlTitle = function( _id_ ) {
                                    var apiCtrlId, _ctrl_ , title;//api.CZR_Helpers.build_setId( _id_ );
                                    _ctrl_ = _.findWhere( remainingSkopableSettings, { 'setting-id' : _id_ } );
                                    apiCtrlId = _ctrl_.apiCtrlId;

                                    title = ( ! _.isEmpty( apiCtrlId ) && api.control.has( apiCtrlId ) ) ? api.control( apiCtrlId ).params.label : apiCtrlId;
                                    return _.isEmpty( title ) ? _id_ : title;
                              };

                        var _sections_ = [], _panels_ = [];

                        var _collection_ = {},
                            _inCollection = [];

                        // get the section and panel informations
                        api.section.each( function( _sec_ ) {
                              _.each( _sec_.controls(), function( _ctrlData_ ) {
                                    var _ctrlCandidate = _.findWhere( remainingSkopableSettings, { 'apiCtrlId' : _ctrlData_.id } );
                                    if ( ! _.isEmpty( _ctrlCandidate ) ) {
                                          // Does this section belong to a panel ?
                                          var _secTitle = _.isEmpty( _sec_.params.title ) ? _sec_.id : _sec_.params.title,
                                              _secPanel,
                                              _secPanelId = _sec_.panel(),
                                              _secPanelTitle = '',
                                              _ctrlTitle = _ctrlData_.id;

                                          if ( _.isEmpty( _secPanelId ) ) {
                                                _secPanelId = '_no_panel_';
                                                _secPanelTitle = _secPanelId;
                                          } else {
                                                _secPanel = api.panel( _secPanelId );
                                                _secPanelTitle = _.isEmpty( _sec_.params.title ) ? _secPanelId : _secPanel.params.title;
                                          }

                                          _collection_[ _secPanelTitle ] = _.has( _collection_, _secPanelTitle ) ? _collection_[ _secPanelTitle ] : {};
                                          _collection_[ _secPanelTitle ][ _secTitle ] = _.has( _collection_[ _secPanelTitle ], _secTitle ) ? _collection_[ _secPanelTitle ][ _secTitle ] : [];

                                          _ctrlTitle = _getControlTitle( _ctrlCandidate['setting-id'] );
                                          _ctrlTitle = api.CZR_Helpers.capitalize( _ctrlTitle ).replace('_', ' ');

                                          _collection_[ _secPanelTitle ][ _secTitle ].push( { id : _ctrlCandidate['setting-id'], ctrlTitle : _ctrlTitle } );
                                          // populate a list of control id so we can check if all remainingSkopbableSettings are included in the collection afterwards
                                          _inCollection.push( _ctrlData_.id );
                                    }
                              });
                        });


                        // each setting is formed {id: "custom_css", ctrlTitle: "CSS code"}
                        var _firstOptionSelected = false,
                            _generateOptions = function( _settingCollection_ ) {
                                  var _html_ = '';
                                  _.each( _settingCollection_ , function( _data_ ) {
                                        //normalizes
                                        _data_ = _.extend( {
                                              'id' : '',
                                              'ctrlTitle' : ''
                                        }, _data_ || {} );

                                        if ( ! _firstOptionSelected ) { //if (_id == _model[ input.id ] ) {
                                              _html_ += '<option selected="selected" value="' +   _data_.id + '">' + _data_.ctrlTitle + '</option>';
                                              _firstOptionSelected = true;
                                        } else {
                                              _html_ += '<option value="' +   _data_.id + '">' + _data_.ctrlTitle + '</option>';
                                        }
                                });
                                return _html_;
                        };

                        var optGroupTitle = '',
                            $selectElement = $( 'select[data-czrtype="' + input.id + '"]', input.container );

                        // First append the groups belonging do a panel
                        _.each( _collection_, function( _panelData, _panelTitle ) {
                              if ( '_no_panel_' == _panelTitle )
                                return;
                              _.each( _panelData, function( _secData, _secTitle ) {
                                    optGroupTitle = [ _panelTitle, ' > ', _secTitle ].join('');
                                    var $optGroup = $('<optgroup>', { label : optGroupTitle , html : _generateOptions( _secData ) });
                                    $selectElement.append( $optGroup );
                              });

                        });

                        // Then append the groups with no panels
                        _.each( _collection_, function( _panelData, _panelTitle ) {
                              if ( '_no_panel_' != _panelTitle )
                                return;
                              _.each( _panelData, function( _secData, _secTitle ) {
                                    optGroupTitle = _secTitle;
                                    var $optGroup = $('<optgroup>', { label : optGroupTitle , html : _generateOptions( _secData ) });
                                    $selectElement.append( $optGroup );
                              });

                        });

                        //$( 'select[data-czrtype="' + input.id + '"]', input.container ).append( _html_ ).selecter();
                        $( 'select[data-czrtype="' + input.id + '"]', input.container ).select2( {
                              //minimumResultsForSearch: -1, //no search box needed
                              //templateResult: paintFontOptionElement,
                              //templateSelection: paintFontOptionElement,
                              escapeMarkup: function(m) { return m; }
                        });
                  }
            })//CZRFlatSkopeItemInputCtor

      });//extend
})( wp.customize , jQuery, _ );// global contxLocalizedParams
var CZRContextualizerModuleMths = CZRContextualizerModuleMths || {};

( function ( api, $ ) {
      $.extend( CZRContextualizerModuleMths, {
            //////////////////////////////////////////////////////////
            /// ITEM CONSTRUCTOR
            //////////////////////////////////////////
            CZRFlatSkopeItemCtor : {
                  //Fired if the item has been instantiated
                  //The item.callbacks are declared.
                  ready : function() {
                        var item = this;

                        api.CZRItem.prototype.ready.call( item );

                        // on item ready, the input are not yet instantiated.
                        // That's why we need to schedule it with the add()
                        // The add() also alows us to re-bound the input after it's been removed
                        // item.czr_Input.bind( 'add', function() {
                        //       //switch( _input_.id ) {}
                        // });//item.czr_Input.bind()

                        // LINK TO CONTROL
                        //DOM listeners for the user action in item view wrapper
                        item.embedded.done( function() {
                              api.CZR_Helpers.setupDOMListeners(
                                    [//toggles remove view alert
                                          {
                                                trigger   : 'click keydown',
                                                selector  : '.skp-link-to-control',
                                                name      : 'skp-link-to-control',
                                                //data = {
                                                //       dom_el,
                                                //       dom_event,
                                                //       event,
                                                //       model
                                                // }
                                                actions   : function( data ) {
                                                      data.dom_event.preventDefault();
                                                      var apiCtrlId, _ctrl_;
                                                      _ctrl_ = _.findWhere( api.skopableSettingsCollection(), { 'setting-id' : item()['setting-id'] } );
                                                      apiCtrlId = _ctrl_.apiCtrlId;

                                                      if ( ! _.isEmpty( apiCtrlId ) && api.control.has( apiCtrlId ) ) {
                                                            api.control( apiCtrlId ).focus( {
                                                                  completeCallback: function() {
                                                                        //api.errorLog('Do something on focus complete callback ?');
                                                                  }
                                                            });
                                                      } else {
                                                            api.errorLog('Flat skope => control not registered when trying to go back to it');
                                                      }
                                                }
                                          },//actions to execute
                                          {
                                                trigger   : 'click keydown',
                                                selector  : '.skp-link-to-section',
                                                name      : 'skp-link-to-section',
                                                //data = {
                                                //       dom_el,
                                                //       dom_event,
                                                //       event,
                                                //       model
                                                // }
                                                actions   : function( data ) {
                                                      data.dom_event.preventDefault();
                                                      var $clicked = $( data.dom_event.currentTarget );
                                                      if ( 0 < $clicked.length && api.section.has( $clicked.data('hu-section') ) ) {
                                                            api.section( $clicked.data('hu-section') ).focus( {
                                                                  // completeCallback: function() {
                                                                  //       api.errorLog('Do something on focus complete callback ?');
                                                                  // }
                                                            });
                                                      }
                                                }
                                          }//actions to execute
                                    ],
                                    { model:item(), dom_el:item.container },//model + dom scope
                                    item //instance where to look for the cb methods

                              );
                        });//item.embedded();

                        // Print a link to the customizer control at the bottom of the item content element
                        // Note : not printed for dynamically registered control like body bg and header slider, flagged with _module_
                        item.bind( 'contentRendered', function() {
                              if ( '_module_' === item().type )
                                return;
                              item.contentContainer.append( [
                                    '<a href="#" class="skp-link-to-control">&laquo; ',
                                    contxLocalizedParams.i18n[ 'Back to the site wide option' ],
                                    '</a>'
                              ].join('') );
                        });



                        //replace the default remove dialog title
                        item.bind( 'remove-dialog-rendered', function() {
                              $( '.' + item.module.control.css_attr.remove_alert_wrapper, item.container )
                                    .find('p')
                                    .html( contxLocalizedParams.i18n[ 'Confirm the removal of the customizations for' ] + ' : ' + '<i>' + item.getItemTitle( item() ) + '</i>' );
                        });

                        // on item expansion, ask the preview if the selector exists
                        item.viewState.callbacks.add( function( state ) {
                              if ( 'closed' == state )
                                return;
                        });

                        // Pass an additional param isHueSlider to the js template
                        item.bind( 'item-model-before-item-content-template-injection', function( item_model_for_template_injection ) {
                              switch( item_model_for_template_injection.type ) {
                                    case 'color' :
                                          // Is it an hueSlider ?
                                          // We need to get the information from the control associated with the parent item.
                                          var apiCtrlId, _ctrl_,isHueSlider;

                                          _ctrl_ = _.findWhere( api.skopableSettingsCollection(), { 'setting-id' : item()['setting-id'] } );
                                          apiCtrlId = _ctrl_.apiCtrlId;


                                          if ( ! _.isEmpty( apiCtrlId ) && api.control.has( apiCtrlId ) ) {
                                              isHueSlider = api.control( apiCtrlId ).params.mode === 'hue';
                                          }
                                          item_model_for_template_injection.isHueSlider = isHueSlider;
                                    break;
                              }
                              return item_model_for_template_injection;
                        });

                        // print a notification if the currently contextualized setting for 'group' is already contextualized locally
                        if ( 'group' == item.module.SKOPE_LEVEL ) {
                              item.callbacks.add( function() {
                                    //api.czr_skopeReact.currentDynamicSkopeSettingIds is set in api.czr_skopeReact::setupDynamicSettingControls
                                    //and updated on each skope refresh
                                    if ( _.isEmpty( api.czr_skopeReact.currentDynamicSkopeSettingIds ) || _.isEmpty( api.czr_skopeReact.currentDynamicSkopeSettingIds.local ) || _.isEmpty( api.czr_skopeReact.currentDynamicSkopeSettingIds.group ) ) {
                                      return;
                                    }

                                    var localSettingId = api.czr_skopeReact.currentDynamicSkopeSettingIds.local,
                                        groupSettingId = api.czr_skopeReact.currentDynamicSkopeSettingIds.group;

                                    api( localSettingId, function( _set_ ) {
                                          var mayBeLocalItemValue = _.findWhere( _set_(), { id : item.id } );
                                          if ( _.isEmpty( mayBeLocalItemValue ) )
                                            return;

                                            api.control( groupSettingId, function( _ctrl_ ) {
                                                  if ( ! _.isUndefined( _ctrl_.notifications ) ) {
                                                        _ctrl_.notifications.add( new api.Notification( 'locally-contextualized', {
                                                              type: 'info',
                                                              message : [
                                                                    contxLocalizedParams.i18n['When the setting is already customized specifically for'],
                                                                    ':',
                                                                    api.czr_skopeReact.getSkopeLevelTitle( 'local' ),
                                                                    ',',
                                                                    contxLocalizedParams.i18n['this local value will be applied in priority.']
                                                              ].join(' '),
                                                              dismissible: true
                                                        } ) );

                                                        // Removed if not dismissed after 5 seconds
                                                        _.delay( function() {
                                                              _ctrl_.notifications.remove( 'locally-contextualized' );
                                                        }, 10000 );
                                                  }
                                            });
                                    });
                                    //console.log( 'item is being modified => ', item.id, item.module.SKOPE_LEVEL, api.czr_skopeReact.currentDynamicSkopeSettingIds);
                              });
                        }
                  },//ready()

                  // overrides the core method
                  validateItemModelOnInitialize : function( item_model_candidate ) {
                        return item_model_candidate;
                  },


                  // overrides the core method
                  canBeRendered : function() {
                        //console.log( "in canbeRendered", api.czr_skopeBase.getSkopeProperty( 'skope_id', this.module.SKOPE_LEVEL ), this()['skope-id'], this() );
                        return api.czr_skopeBase.getSkopeProperty( 'skope_id', this.module.SKOPE_LEVEL ) == this()['skope-id'];
                  },



                  // Overrides the core method
                  // fired in setupItemListeners
                  writeItemViewTitle : function( item_model ) {
                        var item = this,
                            module = item.module,
                            _model = item_model || item();

                        _titleHtml = [
                              '<ul class="flat-skp-item-title">',
                                    //'<li>Context : ' + api.czr_skopeBase.getSkopeProperty( 'ctx_title', this.module.SKOPE_LEVEL ) + '<li>',
                                    //'<li>Context : ' + _model['skope-id']+ '<li>',
                                    '<li title="' + item.getItemTitle( _model, false ) +'">' + item.getItemTitle( _model, false ) +'</li>',
                              '</ul>'
                        ].join('');

                        $( '.' + module.control.css_attr.item_title , item.container ).html( _titleHtml );
                        //add a hook here
                        api.CZR_Helpers.doActions('after_writeViewTitle', item.container , _model, item );
                  },

                  // @return string
                  getItemTitle : function( item_model, truncated ) {
                        truncated = _.isUndefined( truncated ) ? true : truncated;
                        var item = this,
                            _model = item_model || item(),
                            _title = api.CZR_Helpers.capitalize( ! _.isEmpty( _model.title ) ?  _model.title : _model['setting-id'] ),
                            apiCtrlId, _ctrlLabel;

                        // dynamically registered ('_module_' type) controls might not be ready
                        if ( '_module_' != _model.type ) {
                              apiCtrlId = api.czr_skopeReact.getSkopableSettingProperty( 'apiCtrlId', _model['setting-id'] );
                              _ctrlLabel = ( api.control.has( apiCtrlId ) && ! _.isEmpty( api.control( apiCtrlId ).params.label ) ) ? api.control( apiCtrlId ).params.label : '';
                        }

                        if ( ! _.isEmpty( _ctrlLabel ) ) {
                            _title = _ctrlLabel;
                        }
                        return truncated ? api.CZR_Helpers.truncate( _title, 20 ).replace('_', ' ') : _title.replace('_', ' ');
                  },


                  // Overrides the core method
                  // Fetch the tmpl from here, with a specific cache system
                  // because the normal cache won't work when each item content template is specific, which is very specific to this module
                  renderItemContent : function( _item_model_ ) {
                        //=> an array of objects
                        var item = this,
                            module = this.module,
                            dfd = $.Deferred();

                        // Create a deep copy of the item, so we can inject custom properties before parsing the template, without affecting the original item
                        var item_model_for_template_injection = $.extend( true, {}, _item_model_ || item() );
                        // allow plugin to alter the item_model before template injection
                        item.trigger( 'item-model-before-item-content-template-injection', item_model_for_template_injection );
                        var appendAndResolve = function( _tmpl_ ) {
                              //do we have an html template ?
                              if ( _.isEmpty( _tmpl_ ) ) {
                                    dfd.reject( 'renderItemContent => Missing html template for module : '+ module.id );
                              }
                              var $itemContentWrapper = $( '.' + module.control.css_attr.item_content, item.container );
                              // append the view content
                              $( _tmpl_ ).appendTo( $itemContentWrapper );
                              dfd.resolve( $itemContentWrapper );
                        };//appendAndResolve


                        // MAP item types before rendering
                        switch( item_model_for_template_injection.type ) {
                              case 'checkbox' :
                                    item_model_for_template_injection.type = 'gutencheck';
                              break;
                        }


                        var requestParams = {
                              tmpl : 'item-inputs',
                              module_type: module.module_type,
                              module_id : module.id,
                              cache : false,//<= shall we cache the tmpl or not. Should be true in almost all cases.
                              item_model : item_model_for_template_injection
                        };

                        // a specific cache is introduced here, because of the item content template being determined by the original control type
                        module.itemTmplCache = module.itemTmplCache || {};
                        var itemType = item_model_for_template_injection.type;

                        // for dynamically registered modules, flagged with the "_module_" type, we don't want to fetch an input template
                        // but rather print a link to the control
                        // @see czr_skopeReact::setupDynamicModulesSettingControls()
                        if ( '_module_' == itemType ) {
                              var section_id;
                              switch( item_model_for_template_injection.id ) {
                                    case 'hu_theme_options[pro_slider_header_bg]' :
                                    case 'hu_theme_options[display-a-pro-header-slider]' :
                                          section_id = 'contx_header_bg';
                                    break;
                                    case 'hu_theme_options[body-background]' :
                                          section_id = 'contx_body_bg';
                                    break;
                              }
                              var _html_ = [
                                    '<a href="#" class="skp-link-to-section" data-hu-section="' + section_id + '">&laquo; ',
                                    contxLocalizedParams.i18n['jump to the contextual settings'],
                                    '</a>'
                              ].join('');
                              appendAndResolve( _html_ );
                        } else {
                              if ( ! _.isEmpty( module.itemTmplCache[ itemType ] ) ) {
                                    appendAndResolve( api.CZR_Helpers.parseTemplate( module.itemTmplCache[ itemType ] )( item_model_for_template_injection ) );
                              } else {
                                    api.CZR_Helpers.getModuleTmpl( requestParams ).done( function( _serverTmpl_ ) {
                                          module.itemTmplCache[ itemType ] = _serverTmpl_;
                                          appendAndResolve( api.CZR_Helpers.parseTemplate( _serverTmpl_ )( item_model_for_template_injection ) );
                                    }).fail( function( _r_ ) {
                                          //console.log( 'renderItemContent => fail response =>', _r_);
                                          dfd.reject( 'renderItemContent> Problem when fetching the tmpl from server for module : '+ module.id );
                                    });
                              }
                        }
                        return dfd.promise();
                  },
                  // //OVERRIDES THE BASE METHOD
                  // //renders saved items views and attach event handlers
                  // //the saved item look like :
                  // //array[ { id : 'sidebar-one', title : 'A Title One' }, {id : 'sidebar-two', title : 'A Title Two' }]
                  // renderItemContent : function( item_model ) {
                  //       console.log('renderItemContent Override', item_model )
                  //       //=> an array of objects
                  //       var item = this,
                  //       module = this.module;

                  //       item_model = item_model || item();

                  //       //do we have view content template script?
                  //       if ( 0 === $( '#tmpl-' + module.getTemplateEl( 'itemInputList', item_model ) ).length ) {
                  //       throw new Error('No item content template defined for module ' + module.id + '. The template script id should be : #tmpl-' + module.getTemplateEl( 'itemInputList', item_model ) );
                  //       }

                  //       var  item_content_template = wp.template( module.getTemplateEl( 'itemInputList', item_model ) );

                  //       //do we have an html template ?
                  //       if ( ! item_content_template )
                  //       return this;

                  //       //the view content
                  //       $( item_content_template( item_model )).appendTo( $('.' + module.control.css_attr.item_content, item.container ) );

                  //       return $( $( item_content_template( item_model )), item.container );
                  // },

            }//CZRFlatSkopeItemCtor

      });//extend
})( wp.customize , jQuery );
var CZRSkopeReactMths = CZRSkopeReactMths || {};
(function ( api, $, _ ) {
      $.extend( CZRSkopeReactMths, {
            initialize: function() {
                  var self = this;
                  // WAS PREVIOUSLY IN SKOPEBASE
                  // UPDATE THE SKOPABLE SETTING COLLECTION WHEN SETTINGS ARE ADDED / REMOVED
                  api.bind( 'ready', function() {
                        var doUpdate = function( args ) {
                              self.updateSkopableSettingCollection();
                        };
                        // When items are added / removed
                        api.bind( 'add', doUpdate );
                        api.bind( 'remove', doUpdate );
                  });

                  // REFRESH SKOPE INFO IN EACH SKOPABLE CONTROLS
                  api.bind( 'ready', function() {
                        // 'refresh-skope-notes-in-controls' is triggered from the module
                        self.bind( 'refresh-skope-notes-in-controls', function() {
                              self.refreshControlBottomNotes();
                        } );
                  });

                  // SCHEDULE REACTIONS TO SKOPES CHANGE
                  self.scheduleSkopeReactions();

                  // FIRES THE SET OF CALLBACKS NORMALLY FIRED ON SKOPE CHANGE
                  self.bind( 'trigger-skope-refresh', function() {
                        self.skopeUpdateCallbacks( api.czr_activeSkopes() );
                  } );

                  // Keep track of the registered ui elements dynamically registered
                  // this collection is populated in ::register(), if the track param is true
                  // this is used to know what ui elements are currently being displayed
                  self.registered = new api.Value([]);

                  // POPULATE THE REGISTERED COLLECTION
                  // 'czr-new-registered' is fired in api.CZR_Helpers.register()
                  api.bind( 'czr-new-registered', function( params ) {
                        // Check that we have an origin property and that make sure we populate only the registration emitted by 'nimble'
                        if ( _.isUndefined( params.origin ) ) {
                              throw new Error( 'czr-new-registered event => missing params.origin' );
                        }
                        if ( 'contextualizer' !== params.origin )
                          return;

                        if ( false !== params.track ) {
                              var currentlyRegistered = self.registered();
                              var newRegistered = $.extend( true, [], currentlyRegistered );
                              //Check for duplicates
                              var duplicateCandidate = _.findWhere( newRegistered, { id : params.id } );
                              if ( ! _.isEmpty( duplicateCandidate ) && _.isEqual( duplicateCandidate, params ) ) {
                                    // Update => no need to throw an error in case of a duplicate candidate
                                    // it simply won't be added to the collection
                                    // the api.CZR_Helpers.register() won't add an element to the api if already existing anyway.

                                    //throw new Error( 'register => duplicated element in self.registered() collection ' + params.id );
                              } else {
                                    newRegistered.push( params );
                                    self.registered( newRegistered );
                              }
                              //console.log('SELF REGISTERED => ', self.registered(), params );
                        }
                  });

            }//initialize
      });//$.extend()
})( wp.customize , jQuery, _);
var CZRSkopeReactMths = CZRSkopeReactMths || {};
( function ( api, $, _ ) {
$.extend( CZRSkopeReactMths, {
    //////////////////////////////////////////
    /// CREATE AND MAINTAIN A LIST OF SKOPABLE SETTINGS
    getSkopableSettings : function() {
        // Build the collection on the first invoke
        if ( _.isUndefined( api.skopableSettingsCollection ) ) {
              this.updateSkopableSettingCollection();
        }

        return _.isUndefined( api.skopableSettingsCollection ) ? [] : api.skopableSettingsCollection();
    },


    getSkopeLevelTitle : function( level ) {
        var _title =  contxLocalizedParams.i18n[ 'this page' ];
            skopeObj = _.findWhere( api.czr_currentSkopesCollection(), { skope : level } );
        if ( ! _.isEmpty( skopeObj ) ) {
              _title = skopeObj.ctx_title;
        }
        return _title;
    },


    // Fired  :
    // - the first time we invoke ::getSkopableSettings()
    // - when a setting gets added / removed from the api
    updateSkopableSettingCollection : function() {

          if ( _.isEmpty( api.get() ) )
            return;

          // if ( ! _.has( contxLocalizedParams, 'skopableSettings' ) ) {
          //       api.errorLog( 'Error in flat skope => impossible to retrieve the skopable setting List');
          //       return [];
          // }
          // foreach skopable setting, get the control params information
          var   _candidates_ = [],
                apiCtrlId,
                apiSetId,
                apiCtrlType,
                _rejected_ = [];

          api.skopableSettingsCollection = api.skopableSettingsCollection || new api.Value( [] );

          _.each( api.get(), function( setValue, setId ) {
                // if a setting is associated with several controls
                // only use the control with the same id, except for custom_css
                // Ex : header_textcolor is associated with header_textcolor ( color type ) and display_header_text ( checkbox type )
                // Only header_textcolor will be skopable
                _.each( api( setId ).findControls(), function( _ctrl_ ){
                      var ctrlId = _ctrl_.id;

                      // only use the control with the same id, except for custom_css
                      if ( 'custom_css' !== ctrlId && ctrlId != setId )
                        return;

                      apiCtrlType = api.control( ctrlId ).params.type;
                      // prepare the list of unskopableSettings => add the theme option prefix when needed
                      // because the php list generated with ctx_get_excluded_settings() omits hu_theme_options
                      var _unskopableSettings_ = [];
                      _.each( contxLocalizedParams.unskopableSettings, function( _id_ ) {
                            _unskopableSettings_.push( _id_ );
                      });

                      // abort if the setting belongs to the excluded list ( @see php function ctx_get_excluded_settings() )
                      if ( _.contains( _unskopableSettings_, ctrlId ) ) {
                            _rejected_.push( { id : ctrlId, type : apiCtrlType, excludedBecause : 'in excluded list' } );
                            return;
                      }

                      // abort if the type is not authorized ( @see php function ctx_get_authorized_setting_types() )
                      if ( ! _.contains( contxLocalizedParams.authorizedSettingTypes, apiCtrlType ) ) {
                            _rejected_.push( { id : ctrlId, type : apiCtrlType, excludedBecause : 'type not authorized' } );
                            return;
                      }

                      _candidates_.push( {
                            'setting-id' : setId,
                            'apiCtrlId' : ctrlId,
                            'apiSetId' : setId,
                            'type' : apiCtrlType
                      } );
                });//_.each( associatedControls )
          });//_.each( api.get()

          // if ( ! _.isEmpty( _rejected_ ) ) {
          //       console.log( 'NOT SKOPABLES : ', _rejected_ );
          // }
          // update the collection
          api.skopableSettingsCollection( _candidates_ );
    },//updateSkopableSettingCollection



    // The idea here is to get the 'what' property in the skopable settingId collection api.skopableSettingsCollection()
    // The skopable setting collection items are formed :
    // array(
    //       'setting-id' => '',
    //       'apiCtrlId' => '',
    //       'apiSetId' => '',
    //       'type' => '',
    // );
    getSkopableSettingProperty : function( what, settingId ) {
          what = what || '_not_set_';
          var self = this;
          // Make sure that the requested property is possible
          if ( ! _.has( contxLocalizedParams.skopableSettingsCollectionModel, what) ) {
                api.errorLog( 'getSkopableSettingProperty => requested property is impossible to get.');
                return;
          }
          var   _skopableSettingData = _.findWhere( self.getSkopableSettings(), { 'setting-id' : settingId } ),
                _property;
          if ( ! _.isEmpty( _skopableSettingData ) ) {
                _property =  _skopableSettingData[ what ];
          } else {
                api.infoLog( 'getSkopableSettingProperty => could not find the property : "' + what +'" for setting => ' + settingId );
          }
          return _property;
    },


    //return an array of authorized setting types defined server side
    // array(
    //       'text',
    //       'select',
    //       'checkbox',
    //       'text-area',
    //       'img-upload',
    //       ...
    // );
    getAuthorizedSettingTypes : function() {
          return ( contxLocalizedParams && contxLocalizedParams.authorizedSettingTypes ) ? contxLocalizedParams.authorizedSettingTypes : [];
    },


    //  @return void()
    //  clean all registered control, section, panel tracked ids
    //  preserve the settings
    cleanRegistered : function() {
          var self = this,
              newRegistered = $.extend( true, [],  self.registered() || [] );

          newRegistered = _.filter(  self.registered() , function( _reg_ ) {
                if ( 'setting' !== _reg_.what ) {
                      if ( api[ _reg_.what ].has( _reg_.id ) ) {
                            $.when( api[ _reg_.what ]( _reg_.id ).container.remove() ).done( function() {
                                  // remove control, section, panel
                                  api[ _reg_.what ].remove( _reg_.id );
                            });
                      }
                }
                return _reg_.what === 'setting';
          });
          self.registered( newRegistered );
    },

    // @return string
    getContxSettingId : function( skopeId, stylesheetId ) {
          return [ skopeId, '[', stylesheetId, ']' ].join('');
    }

});//$.extend
})( wp.customize , jQuery, _ );
var CZRSkopeReactMths = CZRSkopeReactMths || {};

( function ( api, $, _ ) {
      $.extend( CZRSkopeReactMths, {
            // fired on api.czr_activeSkopes.callbacks.add
            // and on self::'trigger-skope-refresh'
            skopeUpdateCallbacks : function( newSkopes ) {
                  // DYNAMIC SKOPE SETTING CONTROLS REGISTRATION
                  this.setupDynamicSettingControls( newSkopes );

                  // REFFRESH SKOPE CONTROL BOTTOM NOTES AND LINKS
                  this.refreshControlBottomNotes( newSkopes );

                  // REGISTER BODY BG MODULE SETTING CONTROL
                  // REGISTER HEADER SLIDER MODULE SETTING CONTROL
                  this.setupDynamicModulesSettingControls( newSkopes );
            },

            // fired in skopeReact::initialize
            scheduleSkopeReactions : function() {
                  var self = this;

                  // will be used to store the current local and group dynamic setting ids
                  this.currentDynamicSkopeSettingIds = this.currentDynamicSkopeSettingIds || { local : '', group : '' };

                  // see api.czr_skopeBase
                  api.czr_activeSkopes.callbacks.add( function( newSkopes, previousSkopes ) {
                        self.skopeUpdateCallbacks( newSkopes);
                  });

                  // Defer the setup of the listener when the skope notes have been printed
                  // setupDOMListeners() uses event delegation, so why doing that ?
                  // Because it has been observed that this did not work in 100% of the cases. Ignorance about the reason....
                  api.bind( 'refreshControlBottomNotes', function() {
                        if ( true === $('#customize-theme-controls').data( 'skp-focus-listener-done' ) )
                          return;
                        // setup the dom listeners
                        api.CZR_Helpers.setupDOMListeners(
                              [//toggles remove view alert
                                    {
                                          trigger   : 'click keydown',
                                          selector  : '.customize-control .skp-focus-link',
                                          name      : 'skp-focus-on-control',
                                          //data = {
                                          //       dom_el,
                                          //       dom_event,
                                          //       event,
                                          //       model
                                          // }
                                          actions   : function( data ) {
                                                data.dom_event.preventDefault();
                                                var $currentTarget = $( data.dom_event.currentTarget );
                                                if ( 1 === $currentTarget.length && ! _.isEmpty( $currentTarget.data( 'skopified-ctrl-id' ) ) ) {
                                                      var skopeControlId = $currentTarget.data( 'skopified-ctrl-id' ),
                                                          setId = $currentTarget.data( 'setg-id' );

                                                      // Handle specific cases
                                                      // switch( ctrlId ) {
                                                      //       case 'custom_css' :
                                                      // }
                                                      if ( api.control.has( skopeControlId ) ) {
                                                            api.control( skopeControlId ).focus({
                                                                  completeCallback: function() {
                                                                        // 'contx-expand-pre-item' is listened to in czr_flat_skope_module::initialize()
                                                                        api.control( skopeControlId ).trigger( 'contx-expand-pre-item', { setId : setId });
                                                                        //api.errorLog('Focus complete Callback to do => open the pre-item selecter');
                                                                  }
                                                            });
                                                      }
                                                }
                                                // console.log('data', data, $currentTarget, $currentTarget.data( 'skopified-ctrl-id' ) );
                                                // console.log('skopeControlId', skopeControlId );
                                          }
                                    }//actions to execute
                              ],
                              { model: {}, dom_el: $( '#customize-theme-controls' ) },//_ctrl_.container },//model + dom scope
                              null //instance where to look for the cb methods

                        );//api.CZR_Helpers.setupDOMListeners

                        // Flag the element with a data attribute =>  setup the listener only once.
                        // Anyway, there's also a unicity check in the setupDomlistener method that would have prevent a multiple binding of the same listener to the same dom el
                        $('#customize-theme-controls').data( 'skp-focus-listener-done', true );
                  });
            }
      });//extend
})( wp.customize , jQuery, _ );
var CZRSkopeReactMths = CZRSkopeReactMths || {};

( function ( api, $, _ ) {
      $.extend( CZRSkopeReactMths, {
            // invoked :
            // 1) when api.czr_activeSkopes updated api.czr_activeSkopes.callbacks()
            // 2) on each module skopified item addition / removal when 'refresh-skope-notes-in-controls' is triggered remotely on api.czr_skopeReact
            refreshControlBottomNotes : function( newSkopes ) {
                  var self = this;
                  newSkopes = newSkopes || api.czr_activeSkopes();

                  // REMOVE ALL PREVIOUSLY PRINTED NOTES
                  $( '#customize-theme-controls' ).find( '.customize-control .skp-ctrl-bottom-note').remove();

                  // THEN PRINT A BOTTOM NOTE IN ALL SKOPABLE CONTROLS CONTAINERS
                  _.each( newSkopes, function( _skopeId_, _skopeLevel_ ) {
                        var skopeControlId = self.currentDynamicSkopeSettingIds[ _skopeLevel_ ];
                        if ( ! _.isEmpty( skopeControlId ) && api.control.has( skopeControlId ) ) {
                              self.printSkopeInfoInControls( _skopeLevel_ , skopeControlId );
                        }
                  });

                  // EMIT AN EVENT NOW
                  api.trigger( 'refreshControlBottomNotes' );
            },


            printSkopeInfoInControls : function( _skopeLevel_, skopeControlId ) {
                  var self = this,
                      _css_class_ = 'skp-note-for-' + _skopeLevel_;

                  //This = _ctrl_
                  var mayBePrintAndSetupCtrlBottomNote = function( ) {
                        var _ctrl_ = this,
                            _skope_title_ =  contxLocalizedParams.i18n[ 'this page' ];

                        if ( 0 < _ctrl_.container.find( '.' + _css_class_ ).length )
                          return;

                        var currentSkopeObj = _.findWhere( api.czr_currentSkopesCollection(), { skope : _skopeLevel_ } );
                        if ( ! _.isEmpty( currentSkopeObj ) ) {
                              _skope_title_ = currentSkopeObj.ctx_title;
                        }

                        // Has the current setting id been contextualized ?
                        var _message_ = contxLocalizedParams.i18n[ 'Can be contextualized for' ],
                            _isContextualized = false;
                        if ( api.has( skopeControlId ) ) {
                              var associatedSettingId = api.CZR_Helpers.getControlSettingId( _ctrl_.id );
                                  _contextualizedCandidate = _.findWhere( api( skopeControlId )(), { 'setting-id' : associatedSettingId } );
                              if ( ! _.isEmpty( _contextualizedCandidate ) ) {
                                    _message_ = contxLocalizedParams.i18n[ 'Is contextualized for' ];
                                    _isContextualized = true;
                              }
                        }

                        if ( 1 > _ctrl_.container.find( '.skp-ctrl-note-wrapper' ).length ) {
                              _ctrl_.container.append( $('<div>', { class : 'skp-ctrl-note-wrapper' } ) );
                        }

                        // prepare the setting_id to be added as an attribute for uses on click actions
                        var setId = _ctrl_.params.settings.default;
                        setId = _.isEmpty( setId ) ? _ctrl_.id : setId;

                        _ctrl_.container.find( '.skp-ctrl-note-wrapper' ).append(
                              $('<div>', {
                                  class: [ _css_class_ , 'skp-ctrl-bottom-note', 'czr-notice', _isContextualized ? 'is-contextualized' : '' ].join(' '),
                                  html : [
                                        '<i class="fas fa-map-marker-alt"></i>&nbsp;' + _message_,
                                        [ '<span class="skp-focus-link" data-setg-id="', setId ,'" data-skopified-ctrl-id="', skopeControlId, '">', '<u>', _skope_title_ , '</u><span>'].join('')
                                  ].join(' ')
                              })
                        );

                  };//mayBePrintAndSetupCtrlBottomNote

                  _.each( api.czr_skopeReact.getSkopableSettings(), function( settingData ) {
                        api.control.when( settingData.apiCtrlId, function( _ctrl_ ) {
                              _ctrl_.deferred.embedded.then( function() {
                                    mayBePrintAndSetupCtrlBottomNote.call( _ctrl_ );
                              });
                        } );
                  });
            }//printSkopeInfoInControls
      });//extend
})( wp.customize , jQuery, _ );
var CZRSkopeReactMths = CZRSkopeReactMths || {};
(function ( api, $, _ ) {
      $.extend( CZRSkopeReactMths, {
            // Fired in api.czr_activeSkopes.callbacks.add()
            // Register and de register the dynamic settings and controls when skopes are set
            setupDynamicSettingControls : function( newSkopes ) {
                  var self = this;
                  newSkopes = newSkopes || api.czr_activeSkopes();
                  //  Style the section title if possible and not done yet
                  //self.maybeStyleContextualizerSectionTitle();
                  self.maybeStyleContextualizerPanelTitle();

                  // Schedule skope dynamic settings registration on activeSkopes change
                  // {
                  //  'local' : self.getSkopeProperty( 'skope_id', 'local' ),
                  //  'group' : self.getSkopeProperty( 'skope_id', 'group' )
                  // }

                  // Deregister + remove dom el of the previous control when needed
                  self.currentDynamicSkopeSettingIds = self.currentDynamicSkopeSettingIds || { local : '', group : '' };

                  var _doRegister_ = function( _skopeID_, _skopeLevel_ ) {
                        // Register and store it
                        self.currentDynamicSkopeSettingIds[ _skopeLevel_ ] = self.registerDynamicSkopeSettingControl( {
                              skopeIdToRegister : _skopeID_,
                              skopeLevel : _skopeLevel_,
                              stylesheetId : api.czr_skopeBase.stylesheet()
                        } );
                  };

                  // Maybe remove the group skope level that won't be registered in this context
                  // For example when navigating from a post to the home page
                  if ( '_skope_not_set_' == newSkopes.group || _.isEmpty( newSkopes.group ) ) {
                        if ( ! _.isEmpty( self.currentDynamicSkopeSettingIds[ 'group' ] ) ) {
                              self.resetDynamicSkopeControl( self.currentDynamicSkopeSettingIds[ 'group' ] );
                        }
                  }

                  // Register the new skopes
                  _.each( newSkopes, function( _skopeID_, _skopeLevel_ ) {
                        if ( '_skope_not_set_' == _skopeID_ )
                          return;
                        if ( _.isEmpty( api.czr_skopeBase.stylesheet() ) || ( api.czr_skopeBase.stylesheet() != api.settings.theme.stylesheet ) )
                          return;

                        if ( ! _.isEmpty( self.currentDynamicSkopeSettingIds[ _skopeLevel_ ] ) ) {
                              self.resetDynamicSkopeControl( self.currentDynamicSkopeSettingIds[ _skopeLevel_ ] ).done( function() {
                                    //console.log(_skopeID_ , _skopeLevel_ ,' => REGISTER WHEN CONTROL RESET');
                                    if ( serverControlParams.isDevMode ) { _doRegister_( _skopeID_, _skopeLevel_ ); } else {
                                          try { _doRegister_( _skopeID_, _skopeLevel_ ); } catch ( error ) { api.errorLog( 'setupDynamicSettingControls => ' + error ); }
                                    }
                              });
                        } else {
                              //console.log(_skopeID_ , _skopeLevel_ ,' => REGISTER RIGHT AWAY');
                              if ( serverControlParams.isDevMode ) { _doRegister_( _skopeID_, _skopeLevel_ ); } else {
                                    try { _doRegister_( _skopeID_, _skopeLevel_ ); } catch ( error ) { api.errorLog( 'setupDynamicSettingControls => ' + error ); }
                              }
                        }
                  });
            },




            // @return a promise()
            resetDynamicSkopeControl : function( _skopeID_ ) {
                  var dfd = $.Deferred();
                  if ( api.control.has( _skopeID_ ) ) {
                        //remove control container
                        $.when( api.control( _skopeID_ ).container.remove() ).done( function() {
                              //remove control
                              api.control.remove( _skopeID_ );
                              dfd.resolve();
                        });
                  } else {
                        dfd.resolve();
                  }

                  return dfd.promise();
            },

            // fired in api.czr_activeSkopes.callbacks.add()
            // skopeData looks like :
            // array(
            //     'title'       => '',
            //     'long_title'  => '',
            //     'ctx_title'   => '',
            //     'id'          => '',
            //     'skope'       => '',
            //     //'level'       => '',
            //     'obj_id'      => '',
            //     'skope_id'     => '',
            //     'values'      => ''
            // );
            // args = {
            //   skopeIdToRegister : _skopeID_,
            //   skopeLevel : _skopeLevel_,
            //   stylesheetId : api.czr_skopeBase.stylesheet()
            // }
            // Register the relevant setting and control based on the current skope ids
            // @return the newly registered setting id
            registerDynamicSkopeSettingControl : function( args ) {
                  var self = this,
                      dataForSkopeToRegister = _.findWhere( api.czr_currentSkopesCollection(), { skope_id : args.skopeIdToRegister } );

                  if ( _.isEmpty( dataForSkopeToRegister ) ) {
                        api.errorLog( 'registerDynamicSkopeSettingControl => NO SKOPE DATA PROVIDED' );
                        return;
                  }

                  args = _.extend( {
                        skopeIdToRegister : '',
                        skopeLevel : '',
                        stylesheetId : ''
                  }, args );

                  //console.log( "args?", args );

                  if ( _.isEmpty( args.stylesheetId ) ) {
                        api.errorLog( 'registerDynamicSkopeSettingControl => missing stylesheetId' );
                        return;
                  }

                  // REGISTER THE NEW ONE
                  // setting id looks like
                  //  skp_all_page[twentyseventeen]
                  //  or
                  //  skp_post_page_home[twentyseventeen]
                  var settingId = self.getContxSettingId( args.skopeIdToRegister, args.stylesheetId );

                  //api.infoLog( "::registerDynamicSkopeSettingControl => " + settingId );

                  // Register only if not registered already
                  // For example, when saved as draft in a changeset, the setting is already dynamically registered server side
                  // => in this case, we only need to register the associated control
                  api.CZR_Helpers.register( {
                        origin : 'contextualizer',
                        what : 'setting',
                        id : settingId,
                        dirty : false,
                        value : _.isArray( dataForSkopeToRegister.values ) ? dataForSkopeToRegister.values : [],
                        transport : 'refresh',// @see contxLocalizedParams.dynamicSettingDefaultData
                        type : 'theme_mod'//@see contxLocalizedParams.dynamicSettingDefaultData
                  });

                  api.CZR_Helpers.register( {
                        origin : 'contextualizer',
                        what : 'control',
                        id : settingId,
                        label : this._getDynControlLabel( args.skopeIdToRegister ),// + ' TEST SETTING CONTROL FOR SKOPE ID => ' + args.skopeIdToRegister;
                        type : 'czr_module',//@see contxLocalizedParams.dynamicControlDefaultData
                        module_type : 'czr_flat_skope_module',//@see contxLocalizedParams.dynamicControlDefaultData
                        section : 'contx_sec',//@see contxLocalizedParams.dynamicControlDefaultData
                        priority : 20,
                        settings : { default : settingId },
                        track : false,//don't register in the self.registered() => the control removal is handled with ::resetDynamicSkopeControl()
                        options : {
                              skopeLevel : args.skopeLevel,
                              skopeId : args.skopeIdToRegister
                        }
                  }).done( function() {
                        api.control( settingId, function( _ctrl_ ) {
                              _ctrl_.container.find('.customize-control-title')
                                    .css('text-transform', 'uppercase')
                                    .prepend( '<i class="fas fa-map-marker-alt"></i>&nbsp;' );

                              _.delay( function() {
                                    _ctrl_.trigger( 'set-module-ready' );
                              }, 200 );

                              // SYNCHRONIZE THE CONTEXTUALIZED MODULES ( background, header slider ) ON ITEM REMOVAL
                              // When a module item like body background or header slider is removed, make sure the associated setting id gets reset
                              // To identify this type of module, we need to look for the 'dyn_api_setting_id' property in the item, which is the api setting id to reset
                              // dyn_api_setting_id
                              // {
                                // dyn_api_setting_id:"_contextualizer_ui_body_bg__skp__home"
                                // id:"hu_theme_options[body-background]"
                                // setting-id:"hu_theme_options[body-background]"
                                // skope-id:"skp__home"
                                // title:""
                                // type:"_module_"
                                // value:{} or []
                              // }
                              _ctrl_.bind( 'item-removed', function( _item_ ) {
                                    if ( _.isObject( _item_ ) && _.has( _item_, 'dyn_api_setting_id' ) ) {
                                          // Remove the control
                                          if ( api.control.has( _item_.dyn_api_setting_id ) ) {
                                                $.when( api.control( _item_.dyn_api_setting_id ).container.remove() ).done( function() {
                                                      //remove control
                                                      api.control.remove( _item_.dyn_api_setting_id );
                                                });
                                          }

                                          // Remove the setting
                                          if ( api.has( _item_.dyn_api_setting_id ) ) {
                                                api.remove( _item_.dyn_api_setting_id );
                                          }

                                          // Reinstantiate the setting and control
                                          self.trigger( 'trigger-skope-refresh' );
                                          //console.log('AFTER RESETING ? ', api( _item_.dyn_api_setting_id )() );
                                    }
                              });
                        });
                  });

                  return settingId;
            },//registerDynamicSkopeSettingControl






            // Style the section title if possible and not done yet
            // @return void()
            maybeStyleContextualizerPanelTitle : function() {
                  if ( ! api.panel.has( 'contx_panel' ) )
                    return;

                  var $panelContainer = api.panel( 'contx_panel' ).container;
                  if ( 1 > $panelContainer.length || true === $panelContainer.data( 'skopified-title' ) )
                    return;

                  var $panelTitleEl = $panelContainer.find('.accordion-section-title');
                  //var $panelTitleEl = $panelContainer.find('.customize-section-title h3');

                  if ( 0 < $panelTitleEl.length ) {
                      $panelTitleEl.prepend( '<i class="fas fa-map-marker-alt" style="color:#008ec2;"></i>&nbsp;' );
                  }
                  // if ( 0 < $panelTitleEl.length ) {
                  //     $panelTitleEl.find( '.customize-action').after( '<i class="fas fa-map-marker-alt" style="color:#008ec2;"></i>&nbsp;' );
                  // }

                  // flag it done
                  $panelContainer.data( 'skopified-title', true );
            },


            // Style the section title if possible and not done yet
            // @return void()
            // maybeStyleContextualizerSectionTitle : function() {
            //       if ( ! api.section.has( contxLocalizedParams.dynamicControlDefaultData.section ) )
            //         return;

            //       var $sectionContainer = api.section( contxLocalizedParams.dynamicControlDefaultData.section ).container;
            //       if ( 1 > $sectionContainer.length || true === $sectionContainer.data( 'skopified-title' ) )
            //         return;

            //       var $sectionTitleEl = $sectionContainer.find('.accordion-section-title'),
            //           $panelTitleEl = $sectionContainer.find('.customize-section-title h3');

            //       if ( 0 < $sectionTitleEl.length ) {
            //           $sectionTitleEl.prepend( '<i class="fas fa-map-marker-alt" style="color:#008ec2;"></i>&nbsp;' );
            //       }
            //       if ( 0 < $panelTitleEl.length ) {
            //           $panelTitleEl.find( '.customize-action').after( '<i class="fas fa-map-marker-alt" style="color:#008ec2;"></i>&nbsp;' );
            //       }

            //       // flag it done
            //       $sectionContainer.data( 'skopified-title', true );
            // },


            //@return string
            _getDynControlLabel : function( skope_id ) {
                  var skope_data = _.findWhere( api.czr_currentSkopesCollection(), { skope_id : skope_id } ),
                      label = '';

                  if ( _.isEmpty( skope_data ) ) {
                        api.errorLog( '_getSettingLabel => no skope_data available to setup the control label' );
                  }
                  return [contxLocalizedParams.i18n['Contextual'], skope_data.long_title].join(' ');
            }

      });//$.extend()
})( wp.customize , jQuery, _);
var CZRSkopeReactMths = CZRSkopeReactMths || {};
(function ( api, $, _ ) {
      $.extend( CZRSkopeReactMths, {
            // fired on api.czr_activeSkopes.callbacks
            // and when on self::'trigger-skope-update'
            setupDynamicModulesSettingControls : function( newSkopes ) {
                  var self = this;
                  var _candidates_ = [
                        {
                              module_set_id : 'hu_theme_options[body-background]',//the normal setting id as would be registered not contextualized
                              control_type : 'czr_module',
                              module_type : 'czr_background',
                              control_label : contxLocalizedParams.i18n['Body Background'],
                              defaultValue : {},
                              section : {
                                    id : 'contx_body_bg',
                                    title: contxLocalizedParams.i18n['Body Background'],
                                    panel : 'hu-general-panel',
                                    priority : 30
                              }
                        },
                        {
                              module_set_id : 'hu_theme_options[display-a-pro-header-slider]',//the normal setting id as would be registered not contextualized
                              control_type : 'select',
                              module_type : '',
                              control_label : contxLocalizedParams.i18n['Display a full width header background'],
                              defaultValue : function( skopeLevel ) { return 'local' == skopeLevel ? 'inherit' : 'no'; },
                              embeddedComplete : function() {
                                    // 'this' is the control instance
                                    var control = this;
                                    //generates the options
                                    var optionsList;
                                    if ( 'local' === control.params.skopeLevel ) {
                                          optionsList = { inherit : contxLocalizedParams.i18n['Inherit'] , yes : contxLocalizedParams.i18n['Yes'], no : contxLocalizedParams.i18n['No'] };
                                    } else {
                                          optionsList = { yes : contxLocalizedParams.i18n['Yes'], no : contxLocalizedParams.i18n['No'] };
                                    }

                                    _.each( optionsList , function( _label , _k ) {
                                          var _attributes = {
                                                    value : _k,
                                                    html: _label
                                              };
                                          if ( _k == api( control.id )() ) {
                                                $.extend( _attributes, { selected : "selected" } );
                                          }
                                          $( 'select', control.container ).append( $('<option>', _attributes) );
                                    });
                                    $( 'select', control.container ).selecter();
                              },
                              section : {
                                    id : 'contx_header_bg',
                                    title: contxLocalizedParams.i18n['Full Width Header Background / Slider'],
                                    panel : 'hu-header-panel',
                                    priority : 30
                              }
                        },
                        {
                              module_set_id : 'hu_theme_options[pro_slider_header_bg]',//the normal setting id as would be registered not contextualized
                              control_type : 'czr_module',
                              module_type : 'czr_slide_module',
                              control_label : contxLocalizedParams.i18n['Header Background / Slider'],
                              control_visibility : function() {
                                    // 'this' is the control instance
                                    var control = this,
                                        dominusSettingId = '_contextualizer_ui_' + 'display-a-pro-header-slider' + '_' + control.params.skopeId,
                                        visibility = function( to ) {
                                          control.container.toggle( 'yes' === to );
                                    };
                                    api( dominusSettingId, function( _setting_ ) {
                                          visibility( _setting_() );
                                          _setting_.bind( visibility );
                                    });
                              },
                              defaultValue : [],
                              section : {
                                    id : 'contx_header_bg',
                                    title: contxLocalizedParams.i18n['Full Width Header Background / Slider'],
                                    panel : 'hu-header-panel',
                                    priority : 30
                              }
                        }
                  ];

                   // Clean previously generated UI elements
                  self.cleanRegistered();

                  // Register after a 500 ms delay => not ideal, we should register on cleanRegistered.done()
                  _.delay( function() {
                        var control_priority = 10;
                        _.each( newSkopes, function( _skopeID_, _skopeLevel_ ) {
                              if ( '_skope_not_set_' == _skopeID_ )
                                return;
                              control_priority += 10;
                              var _do_ = function() {
                                    _.each( _candidates_, function( params ) {
                                          // If this is a 'czr_module' => make sure that the module type is registered
                                          // Prevents error in Hueman Addons, "czr_slide_module" not found.
                                          if ( 'czr_module' == params.control_type && ! _.has( api.czrModuleMap, params.module_type ) )
                                            return;

                                          // set the skopeID and skopeLevel to the candidate
                                          params.skopeId = _skopeID_;
                                          params.skopeLevel = _skopeLevel_;
                                          params.control_priority = control_priority;
                                          self.registerContextalizedModuleControlSection( params );
                                    });
                              };

                              if ( serverControlParams.isDevMode ) {
                                    _do_();
                              } else {
                                    try {
                                          _do_();
                                    } catch ( error ) {
                                          api.errare( 'api.czr_skopeReact::setupDynamicModulesSettingControls', error );
                                    }
                              }

                        });
                  }, 500 );
            },

            // helper
            // If a setting includes bracket ( 'hu_theme_options[body-background]' ), WP will think that it is a multidimensional setting when dynamically registrating it.
            // return a string, looking like : _contextualizer_ui_pro_slider_header_bg_skp__post_post_32
            _generateDynamicSettingId : function( module_set_id, skopeId ) {
                  var moduleSetIdWithoutBrackets = module_set_id.replace('[', '').replace(']','').replace('hu_theme_options', '');
                  return '_contextualizer_ui_' + moduleSetIdWithoutBrackets + '_' + skopeId;
            },

            // fired on api.czr_activeSkopes.callbacks
            // and when on self::'trigger-skope-update'
            registerContextalizedModuleControlSection : function( params ) {

                  //console.log('registerContextalizedModuleControlSection => params', $.extend( true, {}, params ) );
                  //
                  var self = this,
                      _id_ = self._generateDynamicSettingId( params.module_set_id, params.skopeId ),//_contextualizer_ui_pro_slider_header_bg_skp__post_post_32
                      skopeData = _.findWhere( api.czr_currentSkopesCollection(), { skope_id : params.skopeId } ),
                      styleSheetId = api.czr_skopeBase.stylesheet(),
                      contxSettingId = self.getContxSettingId( params.skopeId, styleSheetId );

                  if ( _.isEmpty( skopeData ) ) {
                        api.errorLog( 'registerContextalizedModuleControlSection => NO SKOPE DATA PROVIDED' );
                        return;
                  }

                  if ( ! api.has( contxSettingId ) ) {
                        api.errorLog( 'registerContextalizedModuleControlSection => the contxSettingId is not registered ' + contxSettingId );
                        return;
                  }

                  _do_register_ = function() {
                        if ( ! api.has( _id_ ) ) {
                              //api.infoLog( "::registerContextalizedModuleControlSection => register new setting id : " + _id_, params.skopeLevel );
                              // ON SETTING CHANGE
                              // synchronize the module setting with the main collection setting
                              api( _id_, function( _setting_ ) {
                                    _setting_.bind( function( to, from ) {
                                          // note _setting_.synchronizedContxSettingId <=> self.getContxSettingId( params.skopeId, styleSheetId );
                                          var newContxSettingValue = $.extend( true, [], api( _setting_.synchronizedContxSettingId )()  || [] ),
                                              _clonedDefault = $.extend( true, {}, contxLocalizedParams.defaultModel );
                                              contxReadyVal = _.extend(
                                                    _clonedDefault,
                                                    {
                                                          id : _setting_.moduleSetId,
                                                          title : api.control.has( _id_ ) ? api.control( _id_ ).params.label : '',
                                                          'setting-id' : _setting_.moduleSetId,
                                                          'skope-id' : _setting_.skopeId,
                                                          type : '_module_',
                                                          value : to,
                                                          dyn_api_setting_id : _setting_.id // used when removing the item from the contx setting, to reset the _setting_ value(). @see ::registerDynamicSkopeSettingControl
                                                    }
                                              );

                                          // Remove the previous value if it was already set
                                          newContxSettingValue = _.filter( newContxSettingValue, function( _item_ ) {
                                                return _setting_.moduleSetId != _item_[ 'setting-id' ];
                                          });
                                          // specific case of the slider select
                                          // we don't write anything in the local skope if inheriting
                                          if ( 'inherit' != to ) {
                                                // populate it
                                                newContxSettingValue.push( contxReadyVal );
                                          }

                                          // set
                                          api( _setting_.synchronizedContxSettingId )( newContxSettingValue );

                                          // refresh the contextualizer so any new item added is printed
                                          self.setupDynamicSettingControls();

                                          // Print a notification when customizing the group level
                                          var onSettingChange = function( to ) {
                                                var _local_skope_title_ =  self.getSkopeLevelTitle( 'local' ),
                                                    localSkopeObj = _.findWhere( api.czr_currentSkopesCollection(), { skope : 'local'} ),
                                                    localSettingId = self._generateDynamicSettingId( _setting_.moduleSetId, localSkopeObj.skope_id ),
                                                    sectionId = api.control( _setting_.id ).section();

                                                if ( 'group' != _setting_.skopeLevel )
                                                    return;

                                                api.section( sectionId ).notifications.add( new api.Notification( 'locally-contextualized', {
                                                      type: 'info',
                                                      message: [
                                                            contxLocalizedParams.i18n['When the setting is already customized specifically for'],
                                                            ':',
                                                            _local_skope_title_,
                                                            ',',
                                                            contxLocalizedParams.i18n['this local value will be applied in priority.']
                                                      ].join(' '),
                                                      dismissible: true
                                                } ));
                                                // Removed if not dismissed after 5 seconds
                                                _.delay( function() {
                                                      api.section( sectionId ).notifications.remove( 'locally-contextualized');
                                                }, 10000 );
                                          };//onSettingChange
                                          onSettingChange( to );
                                    });
                              });// api(_id_, fn() ) => </ON SETTING CHANGE>



                              // Let's extract the value of the module set id in the set of values send by the server for this skope
                              var contxSettingsValues = api( contxSettingId )();
                              contxSettingsValues = _.isArray( contxSettingsValues ) ? contxSettingsValues : [];

                              var skopedModuleData = _.findWhere( contxSettingsValues, { 'setting-id' : params.module_set_id } ),
                                  defaultValue = _.isFunction( params.defaultValue ) ? params.defaultValue( params.skopeLevel ) : params.defaultValue,
                                  initialSettingValueOnRegistration = _.isUndefined( skopedModuleData ) ? defaultValue : skopedModuleData.value;

                              //console.log('skopedModuleData => initialSettingValueOnRegistration', _id_, initialSettingValueOnRegistration,  params.control_type, params.module_type );

                              api.CZR_Helpers.register( {
                                    origin : 'contextualizer',
                                    what : 'setting',
                                    id : _id_,
                                    dirty : ! _.isEmpty( initialSettingValueOnRegistration ),
                                    value : initialSettingValueOnRegistration,
                                    transport : 'refresh',//'postMessage'
                                    type : '_contextualizer_ui_',//will be dynamically registered but not saved in db as option
                                    // add those property to the setting so that we can access them when synchronizing with the contxSettingid on setting change.
                                    options : {
                                          synchronizedContxSettingId : self.getContxSettingId( params.skopeId, styleSheetId ),
                                          skopeId : params.skopeId,
                                          skopeLevel : params.skopeLevel,
                                          moduleSetId : params.module_set_id
                                    }
                              });
                        }//if ( ! api.has( _id_ ) ) {}

                        api.CZR_Helpers.register( {
                              origin : 'contextualizer',
                              what : 'control',
                              id : _id_,
                              label : [
                                    params.control_label,
                                    contxLocalizedParams.i18n['in context'],
                                    ':',
                                    skopeData.ctx_title
                              ].join(' '),
                              type : params.control_type,
                              module_type : params.module_type,//'czr_background',
                              section : params.section.id,//'contx_body_bg',
                              priority : params.control_priority,//'local' === params.skopeLevel ? 10 : 20,//local skope always first
                              settings : { default : _id_ },
                              options : {
                                    skopeLevel : params.skopeLevel,
                                    skopeId : params.skopeId,
                                    moduleSetId : params.module_set_id
                              },

                              //track : false,//don't register in the self.registered() => this will prevent this container to be removed when cleaning the registered
                        }).done( function() {
                              api.control( _id_, function( _ctrl_ ) {
                                    _ctrl_.deferred.embedded.done( function() {
                                          // Print and attach event to a reset link
                                          _ctrl_.container.find('label')
                                                .first().find('.customize-control-title')
                                                .prepend(  '<i title="' + contxLocalizedParams.i18n['Reset'] +'" class="czr-reset fas fa-undo-alt"></i> ' );

                                          _ctrl_.container.on('click', '.czr-reset', function() {
                                                // Remove the item from the contxSetting collection
                                                var newContxSettingValue = $.extend( true, [], api( contxSettingId )()  || [] );
                                                newContxSettingValue = _.filter( newContxSettingValue, function( _item_ ) {
                                                      return api( _id_ ).moduleSetId != _item_[ 'setting-id' ] && params.skopeId != _item_[ 'skope-id' ];
                                                });
                                                api( contxSettingId )( newContxSettingValue );

                                                // Remove the control
                                                $.when( api.control( _id_ ).container.remove() ).done( function() {
                                                      //remove control
                                                      api.control.remove( _id_ );
                                                      // Remove the setting
                                                      if ( api.has( _id_ ) ) {
                                                            api.remove( _id_ );
                                                      }
                                                      // Reinstantiate the setting and control
                                                      //self.registerContextalizedModuleControlSection( params.skopeId, params.skopeLevel );
                                                      self.trigger( 'trigger-skope-refresh' );
                                                });
                                          });

                                          // execute the embeddedComplete callback
                                          if (  _.isFunction( params.embeddedComplete ) ) {
                                                params.embeddedComplete.call( _ctrl_ );
                                          }

                                          // schedule the visibility
                                          if (  _.isFunction( params.control_visibility ) ) {
                                                params.control_visibility.call( _ctrl_ );
                                          }
                                    });//embedded done
                              });
                              // api.control( _id_ ).focus({
                              //     completeCallback : function() {}
                              // });
                        });
                  };//_do_register_()


                  // Defer the registration when the parent section gets added to the api
                  api.section.when( params.section.id, function() {
                        //console.log('registerContextalizedModuleControlSection => ', _id_ );
                        _do_register_();
                  });


                  // REGISTER A SECTION
                  api.CZR_Helpers.register({
                        origin : 'contextualizer',
                        what : 'section',
                        id : params.section.id,//'contx_body_bg'
                        title: params.section.title,
                        panel : params.section.panel,//'contx_panel'
                        priority : params.section.priority,
                        track : false,//don't register in the self.registered() => this will prevent this container to be removed when cleaning the registered
                        constructWith : api.Section.extend({
                              //attachEvents : function () {},
                              // Always make the section active, event if we have no control in it
                              isContextuallyActive : function () {
                                return this.active();
                              },
                              _toggleActive : function(){ return true; }
                        })
                  });
            }//registerContextalizedModuleControlSection()

      });//$.extend()
})( wp.customize , jQuery, _);var CZRContextualizerModuleMths = CZRContextualizerModuleMths || {};
var CZRSkopeReactMths = CZRSkopeReactMths || {};

(function ( api, $ ) {
      //provides a description of each module
      //=> will determine :
      //1) how to initialize the module model. If not crud, then the initial item(s) model shall be provided
      //2) which js template(s) to use : if crud, the module template shall include the add new and pre-item elements.
      //   , if crud, the item shall be removable
      //3) how to render : if multi item, the item content is rendered when user click on edit button.
      //    If not multi item, the single item content is rendered as soon as the item wrapper is rendered.
      //4) some DOM behaviour. For example, a multi item shall be sortable.
      api.czrModuleMap = api.czrModuleMap || {};
      $.extend( api.czrModuleMap, {
            czr_flat_skope_module : {
                  mthds : CZRContextualizerModuleMths,
                  crud : true,
                  name : 'Flat Skope',
                  has_mod_opt : false,
                  sortable : false,
                  ready_on_section_expanded : false//<= will be handled in the module.initialize(), when the 'set-module-ready' event will be emitted. @see registerDynamicSkopeSettingControl
            },
      });

      // Skope React
      $.extend( CZRSkopeReactMths, api.Events );
      var CZR_SkopeReact   = api.Class.extend( CZRSkopeReactMths );

      var _do = function() {
          api.czr_skopeReact   = new CZR_SkopeReact();
      };
      if ( _.has( api, 'czr_skopeBase' ) ) { _do(); } else {
            // Schedule instantiation on 'czr_skopeBase_initialized'
            api.bind( 'czr_skopeBase_initialized' , _do );
      }


})( wp.customize, jQuery );