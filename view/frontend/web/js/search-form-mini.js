define([
    'jquery',
    'uiRegistry',
], function ($, registry) {
    'use strict';

    $.widget('synerise.quickSearch', {

        options: {
            autocomplete: 'off',
            minSearchLength: 3,
            destinationSelector: '#search_autocomplete',
            formSelector: '#search_mini_form',
            submitBtn: 'button[type="submit"]',
            searchLabel: '[data-role=minisearch-label]',
            isExpandable: null,
        },

        /** @inheritdoc */
        _create: function () {
            this.autoComplete = $(this.options.destinationSelector);
            this.searchForm = $(this.options.formSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn)[0];
            this.searchLabel = this.searchForm.find(this.options.searchLabel);
            this.isExpandable = this.options.isExpandable;
            this.isInitailized = false;

            if(this.submitBtn) this.submitBtn.disabled = true;

            this.element.attr('autocomplete', this.options.autocomplete);

            registry.async('autocomplete_search_results')(
                this._onSearchResultsReady.bind(this)
            );
        },

        /**
         * Destroys the current instance by removing all associated event listeners from the element.
         */
        _destroy: function () {
            this.element.off('focus focusout input propertychange');
            $(document).off('.autocompleteOutside');
        },

        /**
         * Checks if search field is active.
         *
         * @returns {Boolean}
         */
        isActive: function () {
            return this.searchLabel.hasClass('active');
        },

        /**
         * Sets state of the search field to provided value.
         *
         * @param {Boolean} isActive
         */
        setActiveState(isActive) {
            this.searchForm.toggleClass('active', isActive);
            this.searchLabel.toggleClass('active', isActive);
            this.element.attr('aria-expanded', isActive);
            if (isActive) {
                this.element.attr('aria-haspopup', 'true');
                this.autoComplete
                    .css({position: 'absolute', width: this.element.outerWidth()})
                    .show();
            } else {
                this.element.attr('aria-haspopup', 'false');
                this.autoComplete.hide();
            }
        },

        /**
         * Handles action depending on ko component
         *
         * @param searchResults
         * @private
         */
        _onSearchResultsReady: function (searchResults) {
            this.searchResults = searchResults;
            if (this.submitBtn) {
                this.searchResults.query.subscribe(
                    items => this.submitBtn.disabled = items.length < this.options.minSearchLength
                );
            }

            this.searchLabel.on('click', function (e) {
                // allow input to lose its' focus when clicking on label
                if (this.isExpandable && this.isActive()) {
                    e.preventDefault();
                }
            }.bind(this));

            this.searchResults.keyboardSelectedItem.subscribe(item => {
                if (item) {
                    this.element
                        .val(item.title)
                        .attr('aria-activedescendant', 'qs-option-' + item.key);
                } else {
                    this.element.removeAttr('aria-activedescendant');
                }
            });

            this.searchForm.on('submit', () => this._onSubmit());

            this.element
                .on('focus', () => {
                    if (!this.isInitailized) {
                        this.searchResults.query(this.element.val().trim());
                        this.isInitailized = true;
                    }
                    this.setActiveState(true);
                })
                .on('input propertychange', () => {
                    this.searchResults.query(this.element.val().trim());
                })
                .on('keydown', e => this._onKeyDown(e));


            this._outsideHandler = function (e) {
                let target = e instanceof jQuery.Event ? e.target : e;

                if (!(target instanceof Node)) {
                    return;
                }

                if (target.nodeType === Node.TEXT_NODE) {
                    target = target.parentNode;
                }

                if (
                    !this.element[0].contains(target) &&
                    !this.autoComplete[0].contains(target)
                ) {
                    this.setActiveState(false);
                }

                return true;
            };

            $(document)
                .on('mousedown.autocompleteOutside touchstart.autocompleteOutside', (e) => {
                    this._outsideHandler(e);
                })
                .on('keydown.autocompleteOutside', (e) => {
                    if (e.key !== 'Tab') return;

                    // Let browser move focus first
                    setTimeout(() => {
                        if (document.activeElement) this._outsideHandler(document.activeElement);
                    }, 0);
                });
        },


        /**
         * Executes when keys are pressed in the search input field. Performs specific actions
         * depending on which keys are pressed.
         * @private
         * @param {Event} e - The key down event
         * @return {Boolean} Default return type for any unhandled keys
         */
        _onKeyDown: function (e) {
            const results = this.searchResults;
            if (!results) {
                return true;
            }

            const items = results.selectableItems();
            if (!items.length) {
                return true;
            }

            let index = results.keyboardItemIndex();

            switch (e.keyCode) {
                case $.ui.keyCode.HOME:
                    results.keyboardItemIndex(0);
                    break;
                case $.ui.keyCode.END:
                    results.keyboardItemIndex(items.length - 1);
                    break;
                case $.ui.keyCode.ESCAPE:
                    this.setActiveState(false);
                    break;
                case $.ui.keyCode.ENTER:
                    const item = results.selectedItem();
                    if (item) {
                        results.onQueryClick(item, e);
                    } else {
                        this.searchForm.trigger('submit');
                    }
                    break;
                case $.ui.keyCode.DOWN:
                    results.keyboardItemIndex((index + 1) % items.length);
                    break;
                case $.ui.keyCode.UP:
                    results.keyboardItemIndex(index <= 0 ? items.length - 1 : index - 1);
                    break;
                default:
                    return true;
            }
            e.preventDefault();
        },

        /**
         * Actions triggered on form submit
         * @private
         */
        _onSubmit: function () {
            const selectedItem = this.searchResults.selectedItem();
            if (selectedItem) {
                this.element.val(selectedItem.title);
            }
        }
    });

    return $.synerise.quickSearch;
});