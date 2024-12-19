var VideoPlayer = {

    fileUrl: '',
    uploadedFileUrl: '',
    ext: ['mp4', 'ogg', 'ogv', 'webm', 'png', 'jpeg', 'jpg'],
    fieldProperties: {},
    elId: 0,
    uploadSize: 120,
    players: {},
    files: {},
    media_type: $('#media_type'),
    duplicatedDraftFields: {},

    events: function () {
        App.eventListener('change', '#media_type', this.changeMediaType.bind(this));
        App.eventListener('change', '#video_source', this.changeVideoSource.bind(this));
        App.eventListener('change', '#video_file', this.changeVideoFile.bind(this));
        App.eventListener('change', '#video_url', this.changeVideoUrl.bind(this));
        App.eventListener('change', '#video_loop', this.setOptions.bind(this));
        App.eventListener('change', '#video_auto_play', this.setOptions.bind(this));
    },

    changeMediaType: function(e) {
        var source = $(e.target).val();
        
        $('#video_source').val('local').trigger('change');
        if(source == 'image') {
            $('#prop_video_loop').hide();
            $('#prop_video_auto_play').hide();
        } else {
            $('#prop_video_loop').show();
            $('#prop_video_auto_play').show();
        }
    },

    changeVideoSource: function(e) {
        var source = $(e.target).val();

        if(source == 'remote') {
            $('#prop_video_file').hide();
            $('#prop_video_url').fadeIn();
            $('#video_file_size').fadeOut();
        } else {
            $('#prop_video_url').hide();
            $('#prop_video_file').fadeIn();
            $('#video_file_size').fadeIn();
        }
    },

    changeVideoFile: function(e) {
        var file = $(e.target).prop('files')[0];
        var type = '';
        

        if(file) {
            this.files[this.elId] = file;
        }

        if(this.files[this.elId]) {
            var reader = new FileReader();
            var _this = this;
            reader.onload = function(){
                var dataURL = reader.result;
                media_type = _this.files[_this.elId].type.split('/')[0];
                type = _this.files[_this.elId].type.split('/')[1];
                _this.fileUrl = reader.result;
                if(_this.checkFileExt(type)) {
                    if(media_type == _this.media_type.val()) {
                        if(_this.checkFileSize(_this.files[_this.elId].size)) {                            
                            _this.createPlayer(type, $('#media_type').val());

                            var hostname = window.location.hostname;
                            var form_id = $('#li_' + _this.elId).data("field_properties")["form_id"];
                            var video_url = hostname+"/auditprotocol/data/form_"+form_id+"/files/file_"+_this.elId+"_"+form_id+"."+type;

                            $('#video_url').val(video_url);
                            _this.uploadedFileUrl = video_url;
                        } else {
                            if(_this.files[_this.elId]) {
                                delete _this.files[this.elId];
                            }
                            $('#video_file').val('');
                            $('#li_' + _this.elId +' .video-player-container').html('<img src="images/video_player_image.png">');
                            _this.showErrorMsg('File size is to big');
                        }
                    } else {
                        if(_this.files[_this.elId]) {
                            delete _this.files[this.elId];
                        }
                        $('#video_file').val('');
                        _this.showErrorMsg('Please check media type.');
                    }                    
                } else {
                    if(_this.files[_this.elId]) {
                        delete _this.files[this.elId];
                    }
                    $('#video_file').val('');
                    _this.showErrorMsg('The supported file extensions are mp4, ogg, ogv, webm, png, jpg and jpeg.');
                }
            };

            reader.readAsDataURL(file);
        } else {
            var source = this.players[this.elId].currentSource();
            type = source['type'].split('/')[1];
            this.createPlayer(type, $('#media_type').val());
        }
    },

    changeVideoUrl: function(e) {
        var url = $(e.target).val();
        if(url != '') {
            if( this.media_type.val() == 'video' ) {
                var urlType = this.detectVideoUrlType(url);
                if(urlType) {
                    this.fileUrl = url;
                    this.createPlayer(urlType, 'video');

                    $('#video_file').val('');
                }
            } else {
                this.fileUrl = url;
                this.createPlayer(null, 'image');
            }
        }
    },

    setOptions: function(e) {
        var id = $(e.target).attr("id");
        if(id == "video_loop") {
            var loop = $('#video_loop').is(':checked');
            this.changeFieldProperties({
                video_loop: loop ? 1 : 0
            });
        }
        if(id == "video_auto_play") {
            var autoPlay = $('#video_auto_play').is(':checked');
            this.changeFieldProperties({
                video_auto_play: autoPlay ? 1 : 0
            });
        }
    },

    fillProperties: function() {
        var media_type = this.fieldProperties.media_type;
        this.fileUrl = this.fieldProperties.video_url;

        $('#media_type').val(this.fieldProperties.media_type);
        $('#video_source').val(this.fieldProperties.video_source).trigger('change');
        $('#video_url, #video_file').val('');
        $('#video_auto_play, #prop_video_file_limit_size').prop('checked', false);

        $('#video_url').val(this.fieldProperties.video_url);
        
        if(this.fieldProperties.video_source == 'local' && this.fieldProperties.video_url != '') {
            $("#prop_video_file .file-size-error").text('This element already has a file uploaded. If you want to change it, please click on the "Choose File" button.');
            $("#prop_video_file .file-size-error").css({"display": "block", "position": "initial", "background": "rgba(27, 241, 201, 0.91)", "margin": "10px 0px"});
        } else {
            $("#prop_video_file .file-size-error").text('');
            $("#prop_video_file .file-size-error").css({"display": "none", "position": "absolute", "background": "rgba(241, 115, 115, 0.91)", "margin": "0px"});
        }

        if( this.fieldProperties.media_type == 'video' ) {
            if(this.fieldProperties['video_loop'] == 1) {
                $('#video_loop').prop('checked', true);
            } else {
                $('#video_loop').prop('checked', false);
            }

            if(this.fieldProperties['video_auto_play'] == 1) {
                $('#video_auto_play').prop('checked', true);
            } else {
                $('#video_auto_play').prop('checked', false);
            }
        } else {
            $('#prop_video_loop').hide();
            $('#prop_video_auto_play').hide();
        }
    },

    fillExistingProperties: function() {
        var data = $('#video_data_' + this.elId).html();
        if(data) {
            data = JSON.parse(data.replace(/element_/g, ''));

            for(var key in data) {
                this.fieldProperties[key] = data[key];
            }
        }
    },

    detectVideoUrlType: function(url) {
        var type = 'custom';
        var urlParts = url.split('.');

        if(url.indexOf('youtube') != -1 || url.indexOf('youtu.be') != -1) {
            return 'youtube';
        }

        if(url.indexOf('vimeo') != -1) {
            type = 'vimeo';
            return 'vimeo';
        }

        if(type == 'custom') {
            if(this.checkFileExt(urlParts[urlParts.length - 1])) {
                return urlParts[urlParts.length - 1];
            }
        }

        return null;
    },

    render: function(type, media_type_image_video) {
        media_type_image_video = typeof media_type_image_video !== 'undefined' ? media_type_image_video : 'video';

        var source = $('#video_source').val();
        var template;
        if( media_type_image_video == 'video' ) {

            template = '<video id="video_' + this.elId + '" class="video-js" controls>';
                template += '<source src="' + this.fileUrl + '" type="' + type + '">';
            template += '</video>';
        } else if ( media_type_image_video == 'image' ) {
            template = '<img src="'+this.fileUrl+'" width="100%">';
        }

        $('#li_' + this.elId + ' .video-player-container').html(template);
    },

    checkFileExt: function(fileExt) {
        if(this.ext.indexOf(fileExt.toLowerCase()) != -1) {
            return true;
        }
        return false;
    },

    checkFileSize: function(size) {
        var limitSize = this.uploadSize;

        size = parseFloat((size / 1024 / 1024).toFixed(2));
        if(limitSize >= size) {
            return true;
        } else {
            return false;
        }

        return true;
    },

    showErrorMsg: function(msg) {
        $('.file-size-error').text(msg);
        $('.file-size-error').fadeIn();
        setTimeout(function () {
            $('.file-size-error').fadeOut();
        }, 6000);
    },

    createPlayer: function(type, media_type_image_video) {
        media_type_image_video = typeof media_type_image_video !== 'undefined' ? media_type_image_video : 'video';

        var source, media_type;
        if( media_type_image_video == 'video' ) {
            var loop = $('#video_loop').is(':checked');
            var autoPlay = $('#video_auto_play').is(':checked');
            source = $('#video_source').val();
            media_type = $('#media_type').val();
            var videoType = 'video/' + type;

            var options = {
                loop: loop,
                autoplay: autoPlay
            };
            if (source == "remote") {
                this.changeFieldProperties({
                    media_type: media_type,
                    video_auto_play: autoPlay ? 1 : 0,
                    video_loop: loop ? 1 : 0,
                    video_source: source,
                    video_url: this.fileUrl
                });
            } else {
                this.changeFieldProperties({
                    media_type: media_type,
                    video_auto_play: autoPlay ? 1 : 0,
                    video_loop: loop ? 1 : 0,
                    video_source: source,
                    video_url: this.uploadedFileUrl
                });
            }            

            if(type && (type == 'youtube' || type == 'vimeo')) {
                options['techOrder'] = [type];
            }

            if(this.players[this.elId]) {
                videojs('video_' + this.elId).dispose();
            }

            this.render(videoType);
            this.players[this.elId] = videojs('video_' + this.elId, options);
        } else if ( media_type_image_video == 'image' ) {
            source = $('#video_source').val();
            media_type = $('#media_type').val();
            if (source == "remote") {
                this.changeFieldProperties({
                    media_type: media_type,
                    video_source: source,
                    video_url: this.fileUrl
                });
            } else {
                this.changeFieldProperties({
                    media_type: media_type,
                    video_source: source,
                    video_url: this.uploadedFileUrl
                });
            }            

            this.render(null, 'image');
        }
    },

    changeFieldProperties: function(options) {
        for(var key in options) {
            this.fieldProperties[key] = options[key];
        }

        $('#li_' + this.elId).data('field_properties', this.fieldProperties);
    },

    reload: function(props) {
        this.elId = props.id;
        this.fieldProperties = props;
        /*if(props.video_url != '') {
            this.fillExistingProperties();
        }*/
        this.fillProperties();
    },

    checkDataExists: function() {
        if(Object.keys(videos).length > 0) {
            this.players = videos;
        }
    },

    init: function (uploadSize) {
        this.events();
        this.checkDataExists();
        this.uploadSize = uploadSize;
    }
}