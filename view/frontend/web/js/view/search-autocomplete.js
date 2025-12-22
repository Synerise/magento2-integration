define(['uiCollection', 'ko'], function (Collection, ko) {
    'use strict';

    return Collection.extend({
        defaults: {
            itemRenderer: {},
            template: 'Synerise_Integration/search-autocomplete/results'
        },

        initialize: function () {
            this._super();
            this.isLoading = ko.observable(false);
            this.results = ko.observableArray([]);
            return this;
        },

        setResults: function (items) {
            this.results(items || []);
            this.isLoading(false);
        },

        /**
         * @param {String} type
         * @return {*|String}
         */
        getItemRenderer: function (type) {
            return this.itemRenderer[type] || 'defaultRenderer';
        },

        load: function () {
            this.isLoading(true);
        }
    });
});
