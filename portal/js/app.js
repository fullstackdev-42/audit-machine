var App = {

    ajax: function(method, url, data, callback, errorCallback) {
        $.ajax({
            type: method,
            data: data,
            dataType: 'json',
            url: url,
            cache: false,
            success: function (data) {
                callback(data);
            },
            error: function(err) {
                if(errorCallback) {
                    errorCallback(err);
                }
            },
            complete: function() {}
        });
    },

    eventListener: function(event, elem, callback, parent) {
        if(parent) {
            $(parent).on(event, elem, callback);
        } else {
            $(elem).on(event, callback);
        }
    }
}