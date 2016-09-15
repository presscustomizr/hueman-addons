/*!
 *  Sharrre.com - Make your sharing widget!
 *  Version: 2.0.1
 *  Author: Julien Hany
 *  License: MIT http://en.wikipedia.org/wiki/MIT_License or GPLv2 http://en.wikipedia.org/wiki/GNU_General_Public_License
 */
var SharrrePlatform = SharrrePlatform || (function () {
        var platforms = {};

        return {
            'register': function (name, constructor) {
                platforms[name] = constructor;
            },
            'get': function (name, options) {
                if (!platforms[name]) {
                    console.error("Sharrre - No platform found for " + name);
                    return false;
                }
                return new platforms[name](options);
            }
        };
    })();

// noConflict Scope
(function($, SharrrePlatform){

    // check jQuery
    if (typeof $ == 'undefined'){
        return;
    }

    SharrrePlatform.register("linkedin", function (options) {
        defaultSettings = {  //http://developer.linkedin.com/plugins/share-button
            url: '',  //if you need to personalize url button
            urlCount: false,  //if you want to use personnalize button url on global counter
            counter: '',
            count: true,
            popup: {
                width: 550,
                height: 550
            }
        };

        defaultSettings = $.extend(true, {}, defaultSettings, options);
        return {
            settings: defaultSettings,
            url: function (test) {
                return "https://www.linkedin.com/countserv/count/share?format=jsonp&url={url}&callback=?";
            },
            trackingAction: {site: 'linkedin', action: 'share'},
            load: function (self) {
                var sett = this.settings;
                $(self.element).find('.buttons').append('<div class="button linkedin"><script type="IN/share" data-url="' + (sett.url !== '' ? sett.url : self.options.url) + '" data-counter="' + sett.counter + '"></script></div>');
                var loading = 0;
                if (typeof window.IN === 'undefined' && loading === 0) {
                    loading = 1;
                    (function () {
                        var li = document.createElement('script');
                        li.type = 'text/javascript';
                        li.async = true;
                        li.src = 'https://platform.linkedin.com/in.js';
                        var s = document.getElementsByTagName('script')[0];
                        s.parentNode.insertBefore(li, s);
                    })();
                }
                else if (typeof window.IN !== 'undefined' && window.IN.parse) {
                    IN.parse(document);
                }
            },
            tracking: function () {
                function LinkedInShare() {
                    _gaq.push(['_trackSocial', 'linkedin', 'share']);
                }
            },
            popup: function (opt) {
                window.open('https://www.linkedin.com/cws/share?url=' +
                    encodeURIComponent((this.settings.url !== '' ? this.settings.url : opt.url)) +
                    '&token=&isFramed=true', 'linkedin', 'toolbar=no, width=' + this.settings.popup.width + ", height=" + this.settings.popup.height);
            }
        };
    });


    SharrrePlatform.register("facebook", function (options) {
        defaultSettings = { //http://developers.facebook.com/docs/reference/plugins/like/
            url: '',  //if you need to personalize url button
            urlCount: false,  //if you want to use personnalize button url on global counter
            action: 'like',
            layout: 'button_count',
            count: true,
            width: '',
            send: 'false',
            faces: 'false',
            colorscheme: '',
            font: '',
            lang: 'en_US',
            share: '',
            appId: '',
            popup: {
                width: 900,
                height: 500
            }
        };

        defaultSettings = $.extend(true, {}, defaultSettings, options);

        return {
            settings: defaultSettings,
            url: function (url) {
                return "https://graph.facebook.com/?id={url}&callback=?";
            },
            trackingAction: {site: 'facebook', action: 'like'},
            load: function (self) {
                var sett = this.settings;
                $(self.element).find('.buttons').append('<div class="button facebook"><div id="fb-root"></div>' +
                    '<div class="fb-like" data-href="' + (sett.url !== '' ? sett.url : self.options.url) +
                    '" data-send="' + sett.send +
                    '" data-layout="' + sett.layout +
                    '" data-width="' + sett.width +
                    '" data-show-faces="' + sett.faces +
                    '" data-action="' + sett.action +
                    '" data-colorscheme="' + sett.colorscheme +
                    '" data-font="' + sett.font +
                    '" data-via="' + sett.via +
                    '" data-share="' + sett.share +
                    '"></div></div>');
                var loading = 0;
                if (typeof FB === 'undefined' && loading === 0) {
                    loading = 1;
                    (function (d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) {
                            return;
                        }
                        js = d.createElement(s);
                        js.id = id;
                        js.src = 'https://connect.facebook.net/' + sett.lang + '/all.js#xfbml=1';
                        if (sett.appId) {
                            js.src += '&appId=' + sett.appId;
                        }
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));
                }
                else {
                    FB.XFBML.parse();
                }
            },
            tracking: function () {
                fb = window.setInterval(function () {
                    if (typeof FB !== 'undefined' && 'undefined' !== typeof(_gaq) ) {
                        FB.Event.subscribe('edge.create', function (targetUrl) {
                            _gaq.push(['_trackSocial', 'facebook', 'like', targetUrl]);
                        });
                        FB.Event.subscribe('edge.remove', function (targetUrl) {
                            _gaq.push(['_trackSocial', 'facebook', 'unlike', targetUrl]);
                        });
                        FB.Event.subscribe('message.send', function (targetUrl) {
                            _gaq.push(['_trackSocial', 'facebook', 'send', targetUrl]);
                        });
                        //console.log('ok');
                        clearInterval(fb);
                    }
                }, 1000);
            },
            popup: function (opt) {
                window.open("https://www.facebook.com/sharer/sharer.php?u=" +
                    encodeURIComponent((this.settings.url !== '' ? this.settings.url : opt.url)) +
                    "&t=" + opt.text + "", "", "toolbar=0, status=0, width=" + this.settings.popup.width + ", height=" + this.settings.popup.height);
            }
        };
    });



    SharrrePlatform.register("googlePlus", function (options) {
        defaultSettings = {  //http://www.google.com/webmasters/+1/button/
            url: '',  //if you need to personnalize button url
            urlCount: false,  //if you want to use personnalize button url on global counter
            size: 'medium',
            lang: 'en-US',
            annotation: '',
            count: true,
            popup: {
                width: 900,
                height: 500
            }
        };

        defaultSettings = $.extend(true, {}, defaultSettings, options);
        return {
            settings: defaultSettings,
            url: function (url) {
                return url + '?url={url}&type=googlePlus';
            },
            trackingAction: {site: 'Google', action: '+1'},
            load: function (self) {
                var sett = this.settings;
                //$(self.element).find('.buttons').append('<div class="button googleplus"><g:plusone size="'+self.options.buttons.googlePlus.size+'" href="'+self.options.url+'"></g:plusone></div>');
                $(self.element).find('.buttons').append('<div class="button googleplus"><div class="g-plusone" data-size="' +
                    sett.size + '" data-href="' + (sett.url !== '' ? sett.url : self.options.url) +
                    '" data-annotation="' + sett.annotation + '"></div></div>');
                window.___gcfg = {
                    lang: sett.lang
                };
                var loading = 0;
                if ((typeof gapi === 'undefined' || typeof gapi.plusone === 'undefined') && loading === 0) {
                    loading = 1;
                    (function () {
                        var po = document.createElement('script');
                        po.type = 'text/javascript';
                        po.async = true;
                        po.src = 'https://apis.google.com/js/plusone.js';
                        var s = document.getElementsByTagName('script')[0];
                        s.parentNode.insertBefore(po, s);
                    })();
                }
                else {
                    gapi.plusone.go();
                }
            },
            tracking: function () {
            },
            popup: function (opt) {
                window.open("https://plus.google.com/share?hl=" + this.settings.lang +
                    "&url=" + encodeURIComponent((this.settings.url !== '' ? this.settings.url : opt.url)),
                    "", "toolbar=0, status=0, width=" + this.settings.popup.width + ", height=" + this.settings.popup.height);
            }
        };
    });


    SharrrePlatform.register("pinterest", function (options) {
        defaultSettings = { //http://pinterest.com/about/goodies/
            url: '',  //if you need to personalize url button
            media: '',
            description: '',
            layout: 'horizontal',
            popup: {
                width: 700,
                height: 300
            }
        };

        defaultSettings = $.extend(true, {}, defaultSettings, options);
        return {
            settings: defaultSettings,
            url: function (test) {
                return "https://api.pinterest.com/v1/urls/count.json?url={url}&callback=?";
            },
            trackingAction: {site: 'pinterest', action: 'pin'},
            load: function (self) {
                var sett = this.settings;
                $(self.element).find('.buttons').append('<div class="button pinterest"><a href="https://www.pinterest.com/pin/create/button/?url=' + (sett.url !== '' ? sett.url : self.options.url) + '&media=' + sett.media + '&description=' + sett.description + '" data-pin-do="buttonBookmark" count-layout="' + sett.layout + '">Pin It</a></div>');

                (function () {
                    var li = document.createElement('script');
                    li.type = 'text/javascript';
                    li.async = true;
                    li.src = 'https://assets.pinterest.com/js/pinit.js';
                    li.setAttribute('data-pin-build', 'parsePinBtns');
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(li, s);
                })();

                if (window.parsePinBtns) {
                    window.parsePinBtns();
                }
                $(self.element).find('.pinterest').on('click', function () {
                    self.openPopup('pinterest');
                });
            },
            tracking: function () {
            },
            popup: function (opt) {
                window.open('https://pinterest.com/pin/create/button/?url=' +
                    encodeURIComponent((this.settings.url !== '' ? this.settings.url : opt.url)) +
                    '&media=' + encodeURIComponent(this.settings.media) +
                    '&description=' + this.settings.description, 'pinterest',
                    'toolbar=no,width=' + this.settings.popup.width + ", height=" + this.settings.popup.height);
            }
        };
    });



    SharrrePlatform.register("twitter", function (options) {
        defaultSettings = {  //http://twitter.com/about/resources/tweetbutton
            url: '',  //if you need to personalize url button
            urlCount: false,  //if you want to use personnalize button url on global counter
            count: false,
            hashtags: '',
            via: '',
            related: '',
            lang: 'en',
            popup: {
                width: 650,
                height: 360
            }
        };

        defaultSettings = $.extend(true, {}, defaultSettings, options);
        return {
            settings: defaultSettings,
            trackingAction: {site: 'twitter', action: 'tweet'},
            url: function (test) {
                return "http://opensharecount.com/count.json?url={url}";
            },
            load: function (self) {
                var sett = this.settings;
                $(self.element).find('.buttons').append(
                    '<div class="button twitter"><a href="https://twitter.com/share" class="twitter-share-button" data-url="' + (sett.url !== '' ? sett.url : self.options.url) + '" data-count="' + sett.count + '" data-text="' + self.options.text + '" data-via="' + sett.via + '" data-hashtags="' + sett.hashtags + '" data-related="' + sett.related + '" data-lang="' + sett.lang + '">Tweet</a></div>');
                var loading = 0;
                if (typeof twttr === 'undefined' && loading === 0) {
                    loading = 1;
                    (function () {
                        var twitterScriptTag = document.createElement('script');
                        twitterScriptTag.type = 'text/javascript';
                        twitterScriptTag.async = true;
                        twitterScriptTag.src = 'https://platform.twitter.com/widgets.js';
                        var s = document.getElementsByTagName('script')[0];
                        s.parentNode.insertBefore(twitterScriptTag, s);
                    })();
                }
                else {
                    $.ajax({url: 'https://platform.twitter.com/widgets.js', dataType: 'script', cache: true}); //http://stackoverflow.com/q/6536108
                }
            },
            tracking: function () {
                tw = window.setInterval(function () {
                    if (typeof twttr !== 'undefined') {
                        twttr.events.bind('tweet', function (event) {
                            if (event && 'undefined' !== typeof(_gaq) ) {
                                _gaq.push(['_trackSocial', 'twitter', 'tweet']);
                            }
                        });
                        clearInterval(tw);
                    }
                }, 1000);
            },
            popup: function (opt) {
                window.open("https://twitter.com/intent/tweet?text=" + encodeURIComponent(opt.text) + "&url=" + encodeURIComponent((this.settings.url !== '' ? this.setting.url : opt.url)) + (this.settings.via !== '' ? '&via=' + this.settings.via : ''), "", "toolbar=0, status=0,width=" + this.settings.popup.width + ", height=" + this.settings.popup.height);
            }
        };
    });


    SharrrePlatform.register("twitterFollow", function (options) {
        defaultSettings = {  //http://twitter.com/about/resources/tweetbutton
            url: '',  //if you need to personalize url button
            urlCount: false,  //if you want to use personnalize button url on global counter
            count: true,
            display: 'horizontal',
            lang: 'en',
            popup: {
                width: 650,
                height: 360
            },
            user: "",
            size: 'default',
            showCount: 'false'
        };

        defaultSettings = $.extend(true, {}, defaultSettings, options);
        return {
            settings: defaultSettings,
            trackingAction: {site: 'twitter', action: 'follow'},
            url: function (test) {
                return '';
                // Needs an API token
//            return "https://api.twitter.com/1.1/users/show.json?screen_name=" + this.settings.user + "&include_entities=true&callback=?";
            },
            load: function (self) {
                var sett = this.settings;
                $(self.element).find('.buttons').append(
                    '<div class="button twitterFollow"><a href="https://twitter.com/' + sett.user + '" class="twitter-follow-button"' +
                    '" data-size="' + sett.size +
                    '" data-show-count="' + sett.showCount +
                    '" data-lang="' + sett.lang +
                    '">Follow @' + sett.user + '</a></div>');
                var loading = 0;
                if (typeof twttr === 'undefined' && loading === 0) {
                    loading = 1;
                    (function () {
                        var twitterScriptTag = document.createElement('script');
                        twitterScriptTag.type = 'text/javascript';
                        twitterScriptTag.async = true;
                        twitterScriptTag.src = 'https://platform.twitter.com/widgets.js';
                        var s = document.getElementsByTagName('script')[0];
                        s.parentNode.insertBefore(twitterScriptTag, s);
                    })();
                }
                else {
                    $.ajax({url: 'https://platform.twitter.com/widgets.js', dataType: 'script', cache: true}); //http://stackoverflow.com/q/6536108
                }
            },
            tracking: function () {
            },
            popup: function (opt) {
                window.open("https://twitter.com/intent/follow?screen_name=" + encodeURIComponent(this.settings.user), "",
                    "toolbar=0, status=0, ,width=" + this.settings.popup.width + ", height=" + this.settings.popup.height);

            }
        };
    });
})(window.jQuery, SharrrePlatform);






(function ($, window, document, undefined) {

    /* Defaults*/
    var pluginName = 'sharrre',
        defaults = {
            className: 'sharrre',
            share: {},
            shareTotal: 0,
            template: '',
            title: '',
            url: document.location.href,
            text: document.title,
            urlCurl: 'sharrre.php',  //PHP script for google plus...
            count: {}, //counter by social network
            total: 0,  //total of sharing
            shorterTotal: true, //show total by k or M when number is to big
            enableHover: true, //disable if you want to personalize hover event with callback
            enableCounter: true, //disable if you just want use buttons
            enableTracking: false, //tracking with google analitycs
            defaultUrl: "javascript:void(0);",
            popup: { // Set the popup width and height
                width: 900,
                height: 500
            },
            hover: function () {
            }, //personalize hover event with this callback function
            hide: function () {
            }, //personalize hide event with this callback function
            click: function () {
            }, //personalize click event with this callback function
            render: function () {
            }
        };

    /* Plugin constructor*/
    function Plugin(element, options) {
        this.element = element;
        this.options = $.extend(true, {}, defaults, options);
        this.options.share = options.share; //simple solution to allow order of buttons
        this._defaults = defaults;
        this._name = pluginName;
        this.platforms = {};
        this.init();
    }

    /* Initialization method
     ================================================== */
    Plugin.prototype.init = function () {
        var self = this;
        // Load enabled platforms
        $.each(self.options.share, function (name, val) {
            if (val === true) {
                self.platforms[name] = SharrrePlatform.get(name, self.options.buttons[name]);
            }
        });

        $(this.element).addClass(this.options.className); //add class

        //HTML5 Custom data
        if (typeof $(this.element).data('title') !== 'undefined') {
            this.options.title = $(this.element).attr('data-title');
        }
        if (typeof $(this.element).data('url') !== 'undefined') {
            this.options.url = $(this.element).data('url');
        }
        if (typeof $(this.element).data('text') !== 'undefined') {
            this.options.text = $(this.element).data('text');
        }

        //how many social website have been selected
        $.each(this.options.share, function (name, val) {
            if (val === true) {
                self.options.shareTotal++;
            }
        });

        if (self.options.enableCounter === true) {  //if for some reason you don't need counter
            //get count of social share that have been selected
            $.each(this.options.share, function (name, val) {
                if (val === true) {
                    //self.getSocialJson(name);
                    try {
                        self.getSocialJson(name);
                    } catch (e) {
                    }
                }
            });
        } else if (self.options.template !== '') {
            self.renderer();
            self.options.count[name] = 0;
            self.rendererPerso();
        }

        if (self.options.template !== '') {  //for personalized button (with template)
            this.options.render(this, this.options);
        }
        else { // if you want to use official button like example 3 or 5
            this.loadButtons();
        }

        //add hover event
        $(this.element).on('mouseenter', function () {
            //load social button if enable and 1 time
            if ($(this).find('.buttons').length === 0 && self.options.enableHover === true) {
                self.loadButtons();
            }
            self.options.hover(self, self.options);
        }).on('mouseleave', function () {
            self.options.hide(self, self.options);
        });

        //click event
        $(this.element).click(function (e) {
            e.preventDefault();
            self.options.click(self, self.options);
            return false;
        });
    };

    /* loadButtons methode
     ================================================== */
    Plugin.prototype.loadButtons = function () {
        var self = this;
        $(this.element).append('<div class="buttons"></div>');
        $.each(self.options.share, function (name, val) {
            if (val === true) {
                self.platforms[name].load(self);
                if (self.options.enableTracking === true) { //add tracking
                    self.platforms[name].tracking();
                }
            }
        });
    };

    /* getSocialJson methode
     ================================================== */
    Plugin.prototype.getSocialJson = function (name) {
        var self = this,
            count = 0,
            settings = self.platforms[name].settings,
            buttonUrl = self.platforms[name].url(this.options.urlCurl),
            replaceUrl = encodeURIComponent(this.options.url);
        if (settings.url.length) {
            buttonUrl = settings.url;
        }
        if (settings.urlCount === true && buttonUrl !== '') {
            replaceUrl = buttonUrl;
        }
        if (settings.count === false) {
            buttonUrl = '';
        }
        url = buttonUrl.replace('{url}', replaceUrl);
        if ( 'twitter' == name ) {
          buttonUrl = self.platforms[name].url();
        }
        if (url !== '') {  //urlCurl = '' if you don't want to used PHP script but used social button
            $.getJSON(url, function (json) {
                if (typeof json.count !== "undefined") {  //GooglePlus, Stumbleupon, Twitter, Pinterest and Digg
                    var temp = json.count + '';
                    temp = temp.replace('\u00c2\u00a0', '');  //remove google plus special chars
                    count += parseInt(temp, 10);
                }
                //get the FB total count (shares, likes and more)
                else if ( ( typeof json.share !== "undefined" ) && ( typeof json.share.share_count !== "undefined" ) ) { //Facebook total count
                    count += parseInt(json.share.share_count, 10);
                }
                else if (typeof json[0] !== "undefined") {  //Delicious
                    count += parseInt(json[0].total_posts, 10);
                }
                else if (typeof json[0] !== "undefined") {  //Stumbleupon
                }
                self.options.count[name] = count;
                self.options.total += count;
                self.renderer();
                self.rendererPerso();
            })
                .error(function () {
                    self.options.count[name] = 0;
                    self.rendererPerso();
                });
        }
        else {
            self.renderer();
            self.options.count[name] = 0;
            self.rendererPerso();
        }
    };

    /* launch render methode
     ================================================== */
    Plugin.prototype.rendererPerso = function () {
        //check if this is the last social website to launch render
        var shareCount = 0;
        for ( var e in this.options.count) {
            shareCount++;
        }
        if (shareCount === this.options.shareTotal) {
            this.options.render(this, this.options);
        }
    };

    /* render methode
     ================================================== */
    Plugin.prototype.renderer = function () {
        var total = this.options.total,
            template = this.options.template;
        if (this.options.shorterTotal === true) {  //format number like 1.2k or 5M
            total = this.shorterTotal(total);
        }

        if (template !== '') {  //if there is a template
            template = template.replace('{total}', total);
            $(this.element).html(template);
        }
        else { //template by defaults
            $(this.element).html(
                '<div class="box"><a class="count" href="' + this.options.defaultUrl + '">' + total + '</a>' +
                (this.options.title !== '' ? '<a class="share" href="' + this.options.defaultUrl + '">' + this.options.title + '</a>' : '') +
                '</div>'
            );
        }
    };

    /* format total numbers like 1.2k or 5M
     ================================================== */
    Plugin.prototype.shorterTotal = function (num) {
        if (num >= 1e6) {
            num = (num / 1e6).toFixed(2) + "M";
        } else if (num >= 1e3) {
            num = (num / 1e3).toFixed(1) + "k";
        }
        return num;
    };

    /* Methode for open popup
     ================================================== */
    Plugin.prototype.openPopup = function (site) {
        this.platforms[site].popup(this.options);  //open
        if (this.options.enableTracking === true && 'undefined' !== typeof(_gaq) ) { //tracking!
            infos = this.platforms[site].trackingAction;
            _gaq.push(['_trackSocial', infos.site, infos.action]);
        }
    };

    /* Methode for add +1 to a counter
     ================================================== */
    Plugin.prototype.simulateClick = function () {
        var html = $(this.element).html();
        $(this.element).html(html.replace(this.options.total, this.options.total + 1));
    };

    /* Methode for add +1 to a counter
     ================================================== */
    Plugin.prototype.update = function (url, text) {
        if (url !== '') {
            this.options.url = url;
        }
        if (text !== '') {
            this.options.text = text;
        }
    };

    /* A really lightweight plugin wrapper around the constructor, preventing against multiple instantiations
     ================================================== */
    $.fn[pluginName] = function (options) {
        var args = arguments;
        if (options === undefined || typeof options === 'object') {
            return this.each(function () {
                if (!$(this).data('plugin_' + pluginName)) {
                    $(this).data('plugin_' + pluginName, new Plugin(this, options));
                }
            });
        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
            return this.each(function () {
                var instance = $(this).data('plugin_' + pluginName);
                if (instance instanceof Plugin && typeof instance[options] === 'function') {
                    instance[options].apply(instance, Array.prototype.slice.call(args, 1));
                }
            });
        }
    };
})(window.jQuery || window.Zepto, window, document);