define([
    'ko',
    'jquery',
    'uiRegistry',
    'Magento_Search/js/form-mini'
], function (ko, $, registry, parentWidget) {
    'use strict';

    $.widget('synerise.quickSearch', parentWidget, {

        _onPropertyChange: function () {
            var searchResults = registry.get('autocomplete_search_results');
            if (!searchResults) {
                this._super();
            } else {
                var searchField = this.element,
                    clonePosition = {
                        position: 'absolute',
                        // Removed to fix display issues
                        // left: searchField.offset().left,
                        // top: searchField.offset().top + searchField.outerHeight(),
                        width: searchField.outerWidth()
                    },
                    value = this.element.val();

                this.submitBtn.disabled = true;

                if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                    this.submitBtn.disabled = false;

                    if (this.options.url !== '') { //eslint-disable-line eqeqeq
                        $.getJSON(this.options.url, {
                            q: value
                        }, $.proxy(function (data) {
                            if (data.length) {
                                this._resetResponseList(true);

                                searchResults.setResults(data || []);

                                this.responseList.indexList = this.autoComplete
                                    .css(clonePosition)
                                    .show()
                                    .find(this.options.responseFieldElements + ':visible');

                                this.element.removeAttr('aria-activedescendant');

                                if (this.responseList.indexList.length) {
                                    this._updateAriaHasPopup(true);
                                } else {
                                    this._updateAriaHasPopup(false);
                                }

                                this.responseList.indexList
                                    .on('click', function (e) {
                                        this.responseList.selected = $(e.currentTarget);
                                        this.searchForm.trigger('submit');
                                    }.bind(this))
                                    .on('mouseenter mouseleave', function (e) {
                                        this.responseList.indexList.removeClass(this.options.selectClass);
                                        $(e.target).addClass(this.options.selectClass);
                                        this.responseList.selected = $(e.target);
                                        this.element.attr('aria-activedescendant', $(e.target).attr('id'));
                                    }.bind(this))
                                    .on('mouseout', function (e) {
                                        if (!this._getLastElement() &&
                                            this._getLastElement().hasClass(this.options.selectClass)) {
                                            $(e.target).removeClass(this.options.selectClass);
                                            this._resetResponseList(false);
                                        }
                                    }.bind(this));
                            } else {
                                this._resetResponseList(true);
                                this.autoComplete.hide();
                                this._updateAriaHasPopup(false);
                                this.element.removeAttr('aria-activedescendant');
                            }
                        }, this));
                    }
                } else {
                    this._resetResponseList(true);
                    this.autoComplete.hide();
                    this._updateAriaHasPopup(false);
                    this.element.removeAttr('aria-activedescendant');
                }
            }
        }
    });

    return $.synerise.quickSearch;
});