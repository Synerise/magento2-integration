define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $.ajax({
            url: config.url,
            type: 'GET',
            data: config.params,
            beforeSend: function () {
                $(element).addClass('loading');
            }
        }).done(function (response) {
            $(element)
                .html(response)
                .trigger('contentUpdated');
        }).fail(function () {
            console.error('Failed to load recommendations');
        }).always(function () {
            $(element).removeClass('loading');
        });
    };
});