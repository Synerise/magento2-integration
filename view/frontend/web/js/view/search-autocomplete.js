define(['uiCollection', 'ko', 'jquery'], function (Collection, ko, $) {
    'use strict';

    return Collection.extend({
        defaults: {
            itemRenderer: {},
            template: 'Synerise_Integration/search-autocomplete/results',
            deleteRecentUrl: '/synerise/ajax/search_recent_delete',
            suggestUrl: '/synerise/ajax/search_suggest',
            minSearchLength: 3,
            suggestionDelay: 300,
            isZeroStateEnabled: true
        },

        /**
         * Initializes the component and sets up its internal state, actions, and reactivity.
         */
        initialize: function () {
            this._super();

            // ───── Lifecycle ─────
            this.disposed = false;
            this.abortController = null;

            // ───── State ─────
            this.results = ko.observableArray([]);
            this.zeroStateCache = ko.observableArray([]);

            // ───── Input ─────
            this.query = ko.observable();

            // ───── Selection ─────
            this.hoveredItem = ko.observable(null);
            this.keyboardItemIndex = ko.observable(-1);

            // ───── Derived selection ─────
            this.selectableItems = ko.pureComputed(() =>
                this.results().filter(item => item.isSelectable)
            );

            this.keyboardSelectedItem = ko.pureComputed(() => {
                const index = this.keyboardItemIndex();
                const items = this.selectableItems();
                return index >= 0 && index < items.length ? items[index] : null;
            });

            ko.computed(() => {
                this.query();
                this.results();
                this.keyboardItemIndex(-1);
            });

            this.selectedItem = ko.pureComputed(() =>
                this.hoveredItem() || this.keyboardSelectedItem()
            );

            // ───── Query reaction ─────
            this.query.extend({
                rateLimit: {
                    timeout: this.suggestionDelay,
                    method: 'notifyWhenChangesStop'
                }
            });

            this.query.subscribe(q => this._runSuggestionPipeline(q));

            // ───── Bind actions ─────
            this._bindActions();

            return this;
        },

        /**
         * Disposes of the current instance by performing cleanup operations.
         * Marks the instance as disposed, aborts any ongoing operations controlled by
         * `abortController`, and invokes the parent class's dispose method.
         */
        dispose: function () {
            this.disposed = true;
            this.abortController?.abort();
            this._super();
        },

        /**
         * Retrieves the item renderer based on the provided type.
         * If a specific renderer is not found for the given type,
         * it defaults to 'defaultRenderer'.
         *
         * @param {string} type - The type of the item for which the renderer is needed.
         * @returns {string|Function} The renderer associated with the given type, or 'defaultRenderer' if no match is found.
         */
        getItemRenderer: function (type) {
            return this.itemRenderer[type] || 'defaultRenderer';
        },

        /**
         * Deletes a recent item by sending a POST request to the server
         * and updates the local cache and results on success.
         *
         * @function
         * @param {Object} item - The item to be deleted.
         * @param {string} item.value - The identifier for the item.
         */
        deleteRecent: function(item) {
            $.post(this.deleteRecentUrl, { query: item.value })
                .done(() => {
                    this.zeroStateCache.remove(item);
                    this.results.remove(item);
                })
                .fail((xhr, status) => {
                    console.error('Failed to delete recent item', status);
                });
        },

        /**
         * Handles the action when a product is clicked.
         *
         * @param {Object} item - The information related to the clicked product.
         * @param {Object} [item.event] - The event data associated with the product.
         * @returns {boolean} Returns true after processing the event.
         */
        onProductClick: function(item) {
            if (item.event) {
                this._trackEvent(item.event)
            }

            return true;
        },

        /**
         * Handles the click event associated with a query action.
         *
         * @param {Object} item - The object containing data related to the query action. If it contains a property `event`, the event is tracked.
         * @param {Event} event - The event object representing the click interaction.
         * @returns {boolean} Always returns `true` after execution.
         */
        onQueryClick: function(item, event) {
            if (item.event) {
                this._trackEvent(item.event)
            }

            const form = event.target.closest('form');
            if (form) {
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    $(form).trigger('submit');
                }
            }

            return true;
        },

        /**
         * Binds specific methods to the current context (`this`) to ensure they retain the proper context
         * when invoked. This is typically used to avoid issues with the JavaScript `this` keyword when
         * methods are used as callbacks or event handlers.
         */
        _bindActions: function () {
            this.getItemRenderer = this.getItemRenderer.bind(this);
            this.onProductClick = this.onProductClick.bind(this);
            this.onQueryClick = this.onQueryClick.bind(this);
            this.deleteRecent = this.deleteRecent.bind(this);
        },

        /**
         * Pipeline responsible for context building, resolving and applying of suggestions.
         *
         * @param {string} query - The input query for which suggestions are to be resolved.
         * @returns {Promise<void>} A promise that resolves when the suggestion pipeline execution is completed.
         */
        _runSuggestionPipeline: async function (query) {
            if (this.disposed) return;

            const context = this._buildRequestContext(query);
            if (!context) return;

            const signal = this._resetAbortController();

            try {
                const data = await this._resolveSuggestions(context, signal);
                this._applySuggestionResponse(data);
            } catch (err) {
                this._handlePipelineError(err);
            }
        },

        /**
         * Builds the request context based on the provided query.
         * Determines whether the query represents a zero-state scenario
         * and constructs the appropriate parameters for the request.
         *
         * @param {string} query - The search query string to evaluate and process.
         * @returns {Object} Returns an object containing:
         *   - `params`: An object with the query parameter where `q` is set to the query string
         *               or null if it's a zero-state query.
         *   - `isZeroState`: A boolean indicating whether the query is in a zero-state scenario.
         */
        _buildRequestContext: function (query) {
            const isZeroState = this._isZeroState(query);

            return {
                params: { q: isZeroState ? null : query },
                isZeroState
            };
        },

        /**
         * Resets the current AbortController instance.
         *
         * If an existing AbortController is present, it will abort any ongoing operations associated with its signal.
         * Creates a new AbortController instance and returns its signal.
         *
         * @returns {AbortSignal} The signal associated with the newly created AbortController.
         */
        _resetAbortController: function () {
            if (this.abortController) {
                this.abortController.abort();
            }

            this.abortController = new AbortController();
            return this.abortController.signal;
        },

        /**
         * Resolves suggestions based on the given context.
         *
         * This method determines whether to fetch suggestions from a data source
         * or to return cached zero-state suggestions depending on the provided context.
         *
         * @param {Object} context - The context object containing necessary data.
         * @param {boolean} context.isZeroState - Indicates if the zero-state mode is active.
         * @param {Object} context.params - The parameters used for fetching suggestions.
         * @param {AbortSignal} signal - Signal object to handle request cancellation.
         * @returns {Promise<Array>} A promise that resolves to an array of suggestions.
         */
        _resolveSuggestions: async function (context, signal) {
            if (!context.isZeroState) {
                return this._fetchSuggestions(context.params, signal);
            }

            if (!this.isZeroStateEnabled) {
                return [];
            }

            if (this.zeroStateCache().length) {
                return this.zeroStateCache();
            }

            const data = await this._fetchSuggestions([], signal);
            this.zeroStateCache(data || []);
            return data;
        },

        /**
         * Fetches suggestion data from the server based on the provided parameters.
         * Constructs a URL using the `suggestUrl` property and the given parameters,
         * sends a fetch request, and returns the response as an array.
         *
         * @param {Object} params - Key-value pairs to be included as query parameters in the request.
         * @param {AbortSignal} signal - An AbortSignal to allow request cancellation.
         * @returns {Promise<Array>} A promise that resolves to an array of suggestions.
         * @throws {Error} Throws an error if the response status is not OK.
         */
        _fetchSuggestions: async function (params, signal) {
            const url = new URL(this.suggestUrl, window.location.origin);

            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    url.searchParams.set(key, String(value));
                }
            });

            const response = await fetch(url.toString(), { signal });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            return Array.isArray(data) ? data : [];
        },

        /**
         * Processes the provided response by filtering items based on their type
         * and updating the results and sending tracking suggestion events.
         *
         * @param {Array<Object>} response - The array of response objects to process.
         * Each object in the array is expected to have a 'type' property to determine its category.
         * Items with a type other than 'event' are used to update the results, while items
         * with the type 'event' are passed for event tracking.
         */
        _applySuggestionResponse: function (response) {
            const items = response.filter(i => i.type !== 'event');
            const events = response.filter(i => i.type === 'event');

            this.results(items);
            this._trackSuggestionEvents(events);
        },

        /**
         * Tracks suggestion events
         **
         * @param {Array<Object>} events - An array of event objects to track.
         */
        _trackSuggestionEvents: function (events) {
            if (!events?.length) return;

            events.forEach(e => {
                this._trackEvent(e.action, e.data, e.label)
            });
        },

        /**
         * Handles errors that occur during the pipeline operation.
         * If the error is an `AbortError` or the instance is already disposed, the function will exit early.
         * Logs an error message to the console if the error is not an `AbortError`.
         * Clears the results by setting them to an empty array.
         *
         * @param {Error} err - The error object that occurred during the pipeline operation.
         */
        _handlePipelineError: function (err) {
            if (err.name === 'AbortError' || this.disposed) return;
            console.error('Failed to fetch suggestions', err);
            this.results([]);
        },

        /**
         * Triggers Synerise tracking event
         *
         * @param {Object|null} [event=null] - The event object containing details about the event.
         * @param {string} event.action - The action associated with the event.
         * @param {Object} event.data - The data payload for the event.
         * @param {string} event.label - The label for the event.
         */
        _trackEvent: function(event) {
            if (typeof SR !== 'undefined') {
                SR.event.trackCustomEvent(event.action, event.data, event.label);
            }
        },

        /**
         * Checks whether the given query is in a "zero state" condition.
         * A "zero state" occurs when the query is either null, undefined, or its length
         * is less than the minimum search length defined in the property `minSearchLength`.
         *
         * @param {string|null|undefined} q - The query string to evaluate.
         * @returns {boolean} - Returns true if the query is in a "zero state", otherwise false.
         */
        _isZeroState: function(q) {
            return (!q || q.length < this.minSearchLength);
        }
    });
});
