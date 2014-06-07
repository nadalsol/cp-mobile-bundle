/**
 * Infinite scroll manager. Returns an InifiniteScroll instance
 *
 * It provides a small set of customizable parameters and
 * event handlers to be executed when the new content is
 * loaded
 */
(function(window) {
    'use strict';

    function initInfiniteScroll(options) {

        var InfiniteScroll = function() {

            // flag to activate/deactivate infinite scroll loading
            this.loadMoreData = true;

            // sync flag used as a semaphore when loading new content
            this.isLoading = false;

            this.options = $.extend(this.options, options);
            this.page = this.options.startFromPage;
            $(document).on('loadedContent', this.options.loadedContent);
            $(document).on('loadingContent', this.options.loadingContent);
            $(document).on('endedContent', this.options.endedContent);
        };

        InfiniteScroll.prototype.options = {
            // tells which % of the browser viewport scroll is the trigger for next load
            triggerDistancePercent: 65,
            containerId: 'containerId',
            loadUrl: '/loadBricks',
            loadUrlAppendPageParameter: '?page=',
            startFromPage: 1,
            // callback executed at the end
            // of the handleScroll function
            loadedContent: function() {},
            loadingContent: function() {},
            endedContent: function() {}
        };

        InfiniteScroll.prototype.setLoadUrl = function(loadUrl) {
            this.options.loadUrl = loadUrl;
        }

        InfiniteScroll.prototype.setPage = function(page) {
            this.page = page;
        }

        InfiniteScroll.prototype.handleScroll = function() {

            var bodyHeight = $("body").height(),
                windowHeight = $(window).height(),
                scrollTop = $(window).scrollTop(),
                elements;

            var triggeredLoad = scrollTop + windowHeight > (bodyHeight - (bodyHeight * (100 - this.options.triggerDistancePercent))/100 );

            // checking that actual scroll position does exceed triggerDistancePercent % of browser viewport
            if (!this.loadMoreData || !triggeredLoad) {
                return;
            }

            var containerId = this.options.containerId;

            // we need to split the loadUrl parameter in its URL part
            // and its selector part. See http://api.jquery.com/load/#loading-page-fragments
            var splittedLoadUrl = this.options.loadUrl.split(" ");
            var plainLoadUrl = splittedLoadUrl[0];

            // adding the "page" parameter to the URL
            if (this.options.loadUrlAppendPageParameter == '?page=' && plainLoadUrl.indexOf('?') != -1) {
                this.options.loadUrlAppendPageParameter = '&page=';
            }
            var appendPageParameter = this.getPage() ? this.options.loadUrlAppendPageParameter + this.page : "";
            // the rest of the fragment selector
            var filterSelector = splittedLoadUrl.length || true ? " " + splittedLoadUrl.slice(1).join(" ") : "";

            // new expression for .load() with URL+page+selector
            var nextPageLoadUrl = plainLoadUrl + appendPageParameter + filterSelector;
            // reference InfiniteScroll object inside the closure
            var that = this;

            // ajax-load the following bricks using jQuery
            // we use a global semaphore for sync
            if (!this.isLoading) {

                // set the semaphore
                this.isLoading = true;

                $.event.trigger('loadingContent');

                $('<div/>').load(nextPageLoadUrl, function(response, status) {
                    // remove the semaphore
                    that.isLoading = false;

                    if(status == "success") {
                        //elements = ($(this).children());

                        if (response.length > 0) {

                            $("#" + containerId).append(response);

                            // advence the page counter
                            that.page += 1;


                            // fire the event to notify listeners
                            $.event.trigger('loadedContent', {elements: elements});

                        } else {
                            // stop spawning more load() on scroll, there is no more data!
                            that.loadMoreData = false;
                            // fire the event to notify listeners
                            $.event.trigger('endedContent');
                        }
                    }
                });
            }
        };

        InfiniteScroll.prototype.getPage = function() {
            return this.page;
        };

        var infiniteScroll = new InfiniteScroll();

        // event handler must be executed in the "infiniteScroll" context,
        // otherwise "window" would be used
        // see http://api.jquery.com/jQuery.proxy/
        $(window).on('scroll', $.proxy(infiniteScroll.handleScroll, infiniteScroll));

        return infiniteScroll;
    }

    // expose init function
    window.initInfiniteScroll = initInfiniteScroll;

})(window);
