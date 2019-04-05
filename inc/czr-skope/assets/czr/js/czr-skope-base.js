
var CZRSkopeBaseMths = CZRSkopeBaseMths || {};
(function ( api, $, _ ) {
      $.extend( CZRSkopeBaseMths, {

            initialize: function() {
                  var self = this;
                  api.czr_initialSkopeCollectionPopulated = $.Deferred();
                  api.czr_skopeCollection         = new api.Value([]);//all available skope, including the current skopes
                  api.czr_currentSkopesCollection = new api.Value([]);
                  api.czr_activeSkopes           = new api.Value( { local : '', group : ''} );
                  api.bind( 'ready' , function() {
                        api.previewer.bind( 'czr-new-skopes-synced', function( skope_server_data ) {
                              if ( serverControlParams.isDevMode ) {
                                    api.infoLog( 'API SKOPE SYNCED', skope_server_data );
                              }
                              if ( ! _.has( skope_server_data, 'czr_stylesheet') ) {
                                    api.errorLog( "On 'czr-new-skopes-synced' : missing stylesheet in the server data" );
                                    return;
                              }

                              api.czr_skopeBase.stylesheet   = api.czr_skopeBase.stylesheet || new api.Value( api.settings.theme.stylesheet );
                              api.czr_skopeBase.stylesheet( skope_server_data.czr_stylesheet );
                              var preview = this,
                                  previousSkopeCollection = api.czr_currentSkopesCollection();
                              if ( ! _.has( skope_server_data, 'czr_new_skopes') ) {
                                    api.errorLog( "On 'czr-new-skopes-synced' : missing skopes in the server data" );
                                    return;
                              }
                              if ( _.isEmpty( _.findWhere( skope_server_data.czr_new_skopes, { skope : 'group' } ) ) ){
                                    var _local_ = _.findWhere( skope_server_data.czr_new_skopes, { skope : 'local' } );
                                    if ( ! _.isEmpty( _local_ ) && ! _.contains( FlatSkopeLocalizedData.noGroupSkopeList, _local_.level ) ) {
                                          api.errorLog( 'No group level skope sent while there should be one' );
                                    }
                              }
                              if ( serverControlParams.isDevMode ) {
                                          api.czr_skopeBase.updateSkopeCollection( skope_server_data.czr_new_skopes , preview.channel() );
                              } else {
                                    try {
                                          api.czr_skopeBase.updateSkopeCollection( skope_server_data.czr_new_skopes , preview.channel() );
                                    } catch ( er ) {
                                          return;
                                    }
                              }
                              api.czr_initialSkopeCollectionPopulated.then( function() {
                                    if ( ! _.has( skope_server_data, 'czr_new_skopes' ) || _.isEmpty( skope_server_data.czr_new_skopes ) ) {
                                          api.errorLog( 'Missing skope data after refresh', skope_server_data );
                                    }
                                    api.czr_activeSkopes( {
                                          'local' : self.getSkopeProperty( 'skope_id', 'local' ),
                                          'group' : self.getSkopeProperty( 'skope_id', 'group' )
                                    });

                              });
                        });//api.previewer.bind
                  });//api.bind( 'ready'
                  api.czr_currentSkopesCollection.bind( function( to, from ) {
                        return self.currentSkopesCollectionReact( to, from );
                  }, { deferred : true });
                  api.trigger( 'czr_skopeBase_initialized' );
            }//initialize
      });//$.extend()
})( wp.customize , jQuery, _);//@global serverControlParams
var CZRSkopeBaseMths = CZRSkopeBaseMths || {};
( function ( api, $, _ ) {
$.extend( CZRSkopeBaseMths, {
    getSkopeProperty : function( what, skope_level ) {
        what = what || 'skope_id';
        skope_level = skope_level || 'local';
        var _skope_data = _.findWhere( api.czr_currentSkopesCollection(), { skope : skope_level });
        if ( _.isEmpty( _skope_data ) ){
            if ( 'local' == skope_level ) {
                api.errorLog( "getSkopeProperty => local skope missing, returning not_set" );
            }
            return "_skope_not_set_";//for example when trying to get a group skope_id in a context where there can't be a group skope like home or search.
        } else if ( _.isEmpty( _skope_data[ what ] ) ) {
            api.errorLog( "getSkopeProperty => " + what + " property does not exist" );
            return "_skope_not_set_";
        } else {
            return _skope_data[ what ];
        }
    },
    firstToUpperCase : function( str ) {
          return ! _.isString( str ) ? '' : str.substr(0, 1).toUpperCase() + str.substr(1);
    }
});//$.extend
})( wp.customize , jQuery, _ );//@global serverControlParams
var CZRSkopeBaseMths = CZRSkopeBaseMths || {};
(function ( api, $, _ ) {
      $.extend( CZRSkopeBaseMths, {
            updateSkopeCollection : function( sent_collection ) {
                  var self = this,
                  _api_ready_collection = [];
                  _.each( sent_collection, function( _skope ) {
                        var skope_candidate = $.extend( true, {}, _skope );//deep clone to avoid any shared references
                        _api_ready_collection.push( self.prepareSkopeForAPI( skope_candidate ) );
                  });
                  api.czr_currentSkopesCollection( _api_ready_collection );
            },
            prepareSkopeForAPI : function( skope_candidate ) {
                  if ( ! _.isObject( skope_candidate ) ) {
                    throw new Error('prepareSkopeForAPI : a skope must be an object to be API ready');
                  }
                  var api_ready_skope = skope_candidate;

                  _.each( FlatSkopeLocalizedData.defaultSkopeModel , function( _value, _key ) {
                        var _candidate_val = skope_candidate[_key];
                        switch( _key ) {
                              case 'title' :
                                    if ( ! _.isString( _candidate_val ) ) {
                                          throw new Error('prepareSkopeForAPI : a skope title property must a string');
                                    }
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                              case 'long_title' :
                                    if ( ! _.isString( _candidate_val ) ) {
                                          throw new Error('prepareSkopeForAPI : a skope title property must a string');
                                    }
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                              case 'ctx_title' :
                                    if ( ! _.isString( _candidate_val ) ) {
                                          throw new Error('prepareSkopeForAPI : a skope context title property must a string');
                                    }
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                              case 'skope' :
                                    if ( ! _.isString( _candidate_val ) || _.isEmpty( _candidate_val ) ) {
                                          throw new Error('prepareSkopeForAPI : a skope "skope" property must a string not empty');
                                    }
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                              case 'obj_id' :
                                    if ( ! _.isString( _candidate_val ) ) {
                                          throw new Error('prepareSkopeForAPI : invalid "obj_id" for skope ' + _candidate_val.skope );
                                    }
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                              case 'skope_id' :
                              if ( ! _.isString( _candidate_val ) ) {
                                    throw new Error('prepareSkopeForAPI : invalid "skope_key" for skope ' + _candidate_val.skope );
                              }
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                              case 'values' :
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                              default :
                                    api_ready_skope[_key] = _candidate_val;
                              break;
                        }//switch
                  });
                  api_ready_skope.id = api_ready_skope.skope + '_' + api_ready_skope.skope_id;
                  if ( ! _.isString( api_ready_skope.id ) || _.isEmpty( api_ready_skope.id ) ) {
                        throw new Error('prepareSkopeForAPI : a skope id must a string not empty');
                  }
                  if ( ! _.isString( api_ready_skope.title ) || _.isEmpty( api_ready_skope.title ) ) {
                        api_ready_skope.title = api_ready_skope.id;
                        api_ready_skope.long_title = api_ready_skope.id;
                  }
                  return api_ready_skope;
            },
            currentSkopesCollectionReact : function( to, from ) {
                  var  dfd = $.Deferred();
                  if ( _.isEmpty( from ) && ! _.isEmpty( to ) ) {
                        api.czr_initialSkopeCollectionPopulated.resolve();
                  }
                  return dfd.resolve( 'changed' ).promise();
            }//listenToSkopeCollection()

      });//$.extend()
})( wp.customize , jQuery, _);//@global serverControlParams
var CZRSkopeBaseMths = CZRSkopeBaseMths || {};
(function ( api, $ ) {
      $.extend( CZRSkopeBaseMths, api.Events );
      var CZR_SkopeBase   = api.Class.extend( CZRSkopeBaseMths );
      api.czr_skopeBase   = new CZR_SkopeBase();
})( wp.customize, jQuery );