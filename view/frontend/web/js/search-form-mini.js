define([
    'ko',
    'jquery',
    'uiRegistry',
    'Magento_Search/js/form-mini'
], function (ko, $, registry, parentWidget) {
    'use strict';

    $.widget('synerise.quickSearch', parentWidget, {

        _create: function () {
            this._super();

            this.element.on('focus', $.proxy(function () {
                this.setActiveState.bind(this, true);
                var searchResults = registry.get('autocomplete_search_results');
                if (searchResults.results().length !== 0) {
                    this.autoComplete.show();
                } else if(!searchResults.isLoading() && this.element.val() === '') {
                        searchResults.load();
                        $.getJSON(this.options.url, {
                            q: '#'
                        }, $.proxy(function (data) {
                            this._doRequest(data);
                        }, this));
                }
            }, this));

            this._applyBindings();
        },
        _onPropertyChange: function () {
            var searchResults = registry.get('autocomplete_search_results');
            if (!searchResults) {
                this._super();
            } else {
                var value = this.element.val();

                this.submitBtn.disabled = true;

                if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                    this.submitBtn.disabled = false;

                    if (this.options.url !== '') { //eslint-disable-line eqeqeq
                        searchResults.isLoading(true);

                        $.getJSON(this.options.url, {
                            q: value
                        }, $.proxy(function (data) {
                            this._doRequest(data);
                        }, this));
                    }
                } else {
                    this._resetResponseList(true);
                    this.autoComplete.hide();
                    this._updateAriaHasPopup(false);
                    this.element.removeAttr('aria-activedescendant');
                }
            }
        },
        _doRequest: function (data) {
            if (data.length) {
                this._resetResponseList(true);

                registry.get('autocomplete_search_results').setResults(data || []);

                this.responseList.indexList = this.autoComplete
                    .css({position: 'absolute', width: this.element.outerWidth()})
                    .show()
                    .find(this.options.responseFieldElements + ':visible');

                this.element.removeAttr('aria-activedescendant');

                if (this.responseList.indexList.length) {
                    this._updateAriaHasPopup(true);
                } else {
                    this._updateAriaHasPopup(false);
                }
            } else {
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.removeAttr('aria-activedescendant');
            }
        },
        _applyBindings: function () {
            this.autoComplete
                .on('click', this.options.responseFieldElements, function (e) {
                    this.responseList.selected = $(e.currentTarget);
                    if ($(e.currentTarget).is('[role="option"]')) {
                        this.searchForm.trigger('submit');
                    } else {
                        console.log('not option');
                        this._updateAriaHasPopup(false);
                    }
                }.bind(this))
                .on('mouseover mouseout', this.options.responseFieldElements, function (e) {
                    this.responseList.indexList.removeClass(this.options.selectClass);
                    $(e.currentTarget).addClass(this.options.selectClass);
                    this.responseList.selected = $(e.currentTarget);
                    this.element.attr('aria-activedescendant', $(e.currentTarget).attr('id'));
                }.bind(this))
                .on('mouseout', this.options.responseFieldElements, function (e) {
                    if (!this._getLastElement() &&
                        this._getLastElement().hasClass(this.options.selectClass)) {
                        $(e.currentTarget).removeClass(this.options.selectClass);
                        this._resetResponseList(false);
                    }
                }.bind(this));
        }
    });

    return $.synerise.quickSearch;
});