define(['uiCollection', 'ko'], function (Collection, ko) {
    'use strict';

    return Collection.extend({
        defaults: {
            itemRenderer: {},
            template: 'Synerise_Integration/search-autocomplete/results'
        },

        initialize: function () {
            this._super();
            this.results = ko.observableArray([]);
            return this;
        },

        setResults: function (items) {
            this.results(items || []);
        },

        /**
         * @param {String} type
         * @return {*|String}
         */
        getItemRenderer: function (type) {
            return this.itemRenderer[type] || 'defaultRenderer';
        }
    });
});
