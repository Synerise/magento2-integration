/*jshint browser:true jquery:true*/
/*global alert*/

define([
    'ko',
    'jquery',
    'underscore',
    'mage/template',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/lib/knockout/template/loader',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'Magento_Search/js/form-mini',
], function (ko, $, _, mageTemplate, priceUtil, templateLoader) {
    'use strict';

    $.widget('synerise.quickSearch', $.mage.quickSearch, {

        /**
         * Overridden constructor to ensure templates initialization on load
         *
         * @private
         */
        _create: function () {
            this.templateCache = [];
            this._initTemplates();
            this._super();
        },

        /**
         * Init templates used for rendering when instantiating the widget
         *
         * @private
         */
        _initTemplates: function() {
            var templateFile = 'Synerise_Integration/autocomplete/product';
            templateLoader.loadTemplate(templateFile).done(function (renderer) {
                this.templateCache['product'] = mageTemplate(renderer);
            }.bind(this));
        },

        /**
         * Get rendering template for a given element. Will look into this.options.templates[element.type] for the renderer.
         * Returns an evaluated template for the given element's type.
         *
         * @param element The autocomplete result to display
         *
         * @returns function
         *
         * @private
         */
        _getTemplate: function (element) {
            var type = element.type ? element.type : 'undefined';

            if (this.templateCache[type]) {
                return this.templateCache[type];
            }

            this.templateCache[type] = mageTemplate(this.options.template);

            return this.templateCache[type];
        },

        /**
         * Render an autocomplete item in the result list
         *
         * @param element The element : an autocomplete result
         * @param index   The element index
         *
         * @returns {*|jQuery|HTMLElement}
         *
         * @private
         */
        _renderItem: function (element, index) {
            var template = this._getTemplate(element);
            element.index = index;

            if (element.price && (!isNaN(element.price))) {
                element.price = priceUtil.formatPrice(element.price, this.options.priceFormat);
            }

            return template({
                data: element
            });
        },


        /**
         * Executes when the value of the search input field changes. Executes a GET request
         * to populate a suggestion list based on entered text. Handles click (select), hover,
         * and mouseout events on the populated suggestion list dropdown.
         * @private
         */
        _onPropertyChange: function () {
            var searchField = this.element,
                clonePosition = {
                    position: 'absolute',
                    // Removed to fix display issues
                    // left: searchField.offset().left,
                    // top: searchField.offset().top + searchField.outerHeight(),
                    width: searchField.outerWidth()
                },
                dropdown = $('<ul class="synerise-quick-search" role="listbox"></ul>'),
                value = this.element.val();

            this.submitBtn.disabled = true;

            if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                this.submitBtn.disabled = false;

                if (this.options.url !== '') { //eslint-disable-line eqeqeq
                    $.getJSON(this.options.url, {
                        q: value
                    }, $.proxy(function (data) {
                        if (data.length) {
                            $.each(data, function (index, element) {
                                var html;

                                element.index = index;
                                html = this._renderItem(element, index);
                                dropdown.append(html);
                            }.bind(this));

                            this._resetResponseList(true);

                            this.responseList.indexList = this.autoComplete.html(dropdown)
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
                                .on('click vclick', function (e) {
                                    this.responseList.selected = $(e.currentTarget);
                                    if (this.responseList.selected.attr("href")) {
                                        window.location.href = this.responseList.selected.attr("href");
                                        e.stopPropagation();
                                        return false;
                                    }
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

    });

    return $.synerise.quickSearch;
});
