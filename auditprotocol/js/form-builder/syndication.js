var Syndication = {

    data: [],
    fieldProperties: {},
    elId: 0,

    events: function () {
        App.eventListener('change', '#element_feed_url', this.getRss.bind(this));
        App.eventListener('change', '#feed_box_direction', this.setDirection.bind(this));
        App.eventListener('change', '#feed_box_width_px', this.changeWidgetWidth.bind(this));
        App.eventListener('change', '#feed_box_height_px', this.changeWidgetHeight.bind(this));
        App.eventListener('change', '#feed_box_speed', this.changeScrollSpeed.bind(this));
        App.eventListener('change', '#feed_box_datetime', this.showFeedDateTime.bind(this));
        App.eventListener('change', '#feed_box_no_of_msg', this.changeNumberOfMessages.bind(this));
        App.eventListener('change', 'input[name=feed_scroll]', this.changeScrollType.bind(this));
    },

    getRss: function (e) {
        var rssLink = $(e.target).val();
        var url = 'rss-feed.php';
        var _this = this;
        if(rssLink != '') {
            this.showLoader(true);
            this.changeFieldProperties({ 'feed_url': rssLink });
            App.ajax('GET', url, { feed_url: rssLink }, function(data) {
                _this.data = data;
                if(_this.data.length) {
                    _this.render();
                    _this.makeMarquee();
                }
            });
        }
    },

    setDirection: function(e) {
        var direction = $(e.target).val();
        var speed = $('#feed_box_speed').val();

        if(direction == 'horizontal') {
            $('#li_' + this.elId + ' .feed-container').addClass('horizontal');
        } else {
            $('#li_' + this.elId + ' .feed-container').removeClass('horizontal');
        }

        this.render();
        this.makeMarquee();
        this.changeFieldProperties({ 'scroll_direction': direction, 'scroll_speed': speed });
    },

    changeWidgetWidth: function(e) {
        var width = $(e.target).val();
        this.changeFieldProperties({ 'width': width });
        width = width ? width + 'px' : '97%';
        $('#li_' + this.elId).css('width', width);
    },

    changeWidgetHeight: function(e) {
        var height = $(e.target).val();
        var numOfMsgs = parseInt(height / 100);

        this.changeFieldProperties({ 'height': height });
        this.changeFieldProperties({ 'no_of_rss_msg': numOfMsgs });

        $('#li_' + this.elId).find('.feed-container').css('height', height);
        $('#feed_box_no_of_msg').val(numOfMsgs);
    },

    changeNumberOfMessages: function(e) {
        var numOfMsgs = parseInt($(e.target).val());
        var height = numOfMsgs * 100;

        if(isNaN(numOfMsgs)) {
            return $('#feed_box_height_px').trigger('change');
        }

        $('#feed_box_height_px').val(height).trigger('change');
    },

    changeScrollType: function(e) {
        var item = $('#li_' + this.elId + ' .feed-container');
        var type = $(e.target).val();
        this.showLoader(true);
        this.render();


        if(type == 'scroll-bar') {
            item.addClass('scroll-bar');
            $('#feed_box_speed, #feed_box_direction').prop('disabled', true);

            this.changeFieldProperties({ 'scroll_bar': 1, 'auto_scroll': 0 });
        } else {
            item.removeClass('scroll-bar');
            $('#feed_box_speed, #feed_box_direction').prop('disabled', false);

            this.changeFieldProperties({ 'scroll_bar': 0, 'auto_scroll': 1 });
            this.makeMarquee();
        }
    },

    changeScrollSpeed: function(e) {
        var speed = $(e.target).val();
        if(speed >= 0) {
            this.showLoader(true);
            this.render();
            this.makeMarquee();
            this.changeFieldProperties({ 'scroll_speed': speed });
        }
    },

    showFeedDateTime: function(e) {
        var elem = $(e.target);
        var value = elem.is(':checked') ? 1 : 0;

        if(value) {
            $('#li_' + this.elId + ' .feed-datetime').css('visibility', 'visible');
        } else {
            $('#li_' + this.elId + ' .feed-datetime').css('visibility', 'hidden');
        }

        this.changeFieldProperties({ 'datetime': value });
    },

    changeFieldProperties: function(options) {
        var elemAllProperties = $('#li_' + this.elId).data('field_properties');
        for(var key in options) {
            this.fieldProperties[key] = options[key];
        }

        elemAllProperties.default_value = JSON.stringify(this.fieldProperties);
        $('#li_' + this.elId).data('field_properties', elemAllProperties);
    },

    showLoader: function(show) {
        var loader = $('#loader_' + this.elId);
        var item = $('#li_' + this.elId + ' .feed-container');
        if(show) {
            loader.fadeIn();
            item.hide();
        } else {
            loader.fadeOut();
            item.fadeIn();
        }
    },

    makeMarquee: function() {
        var speed = $('#feed_box_speed').val();
        var direction = $('#feed_box_direction').val();
        var marqueeOptions = {
            itemSelecter: 'div',
            delay: speed * 1000,
            direction: direction
        };

        if(direction == 'horizontal') {
            marqueeOptions['delay'] = 0;
            marqueeOptions['timing'] = speed * 10;
        }

        if(this.fieldProperties['scroll_bar'] != 1) {
            $('#li_' + this.elId + ' .feed-item').marquee(marqueeOptions);
        }
    },

    fillProperties: function() {
        $('#element_feed_url').val(this.fieldProperties['feed_url']);
        $('#feed_box_width_px').val(this.fieldProperties['width']);
        $('#feed_box_height_px').val(this.fieldProperties['height']);
        $('#feed_box_no_of_msg').val(this.fieldProperties['no_of_rss_msg']);
        $('#feed_box_speed').val(this.fieldProperties['scroll_speed']);
        $('#feed_box_direction').val(this.fieldProperties['scroll_direction']);

        if(this.fieldProperties['scroll_bar']) {
            $('#feed_scroll_bar').prop('checked', true);
            $('#feed_box_speed, #feed_box_direction').prop('disabled', true);
        } else {
            $('#feed_auto_scroll').prop('checked', true);
            $('#feed_box_speed, #feed_box_direction').prop('disabled', false);
        }

        if(this.fieldProperties['datetime']) {
            $('#feed_box_datetime').prop('checked', true);
        } else {
            $('#feed_box_datetime').prop('checked', false);
        }
    },

    render: function() {
        var direction = $('#feed_box_direction').val();
        var feedTemplate = '<div class="feed-item">';
        var contentLength = (direction == 'horizontal' ? 130 : 160);
        this.data.forEach(function(feed) {
            feedTemplate += '<div class="item">';
                feedTemplate += '<p class="feed-title">';
                    feedTemplate += '<a target="_blank" href="' + feed.link + '">'  + feed.title + '</a>';
                feedTemplate += '</p>';
                feedTemplate += '<p class="feed-description">'  + feed.description.substr(0, contentLength) + ' ...</p>';
                feedTemplate += '<p class="feed-datetime">'  + feed.pubDate + '</p>';
            feedTemplate += '</div>';
        });
        feedTemplate += '</div>';

        this.showLoader(false);
        $('#li_' + this.elId + ' .feed-container').html(feedTemplate);
        $('#feed_box_datetime').trigger('change');
    },

    reload: function(props) {
        this.elId = props.id;
        this.fieldProperties = JSON.parse(props.default_value);
        this.fillProperties();
        this.checkDataExists();
    },

    checkDataExists: function() {
        var rssUrl = $('#element_feed_url').val();
        if(!this.data.length && rssUrl != '') {
            $('#element_feed_url').trigger('change');
        }
    },

    init: function () {
        this.events();
    }
}