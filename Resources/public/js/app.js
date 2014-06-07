/*
 * Chicplace.com (Mobile)
 *
 * Created: April 2014.
 * Authors: The "BaseEcommerce" team.
 *
 */


/* The document ready event executes already when the HTML-Document is loaded
   and the DOM is ready, even if all the graphics haven’t loaded yet.
 */
$(document).ready(function() {

    // Set brick height
    // ==========================    
    
    // Set brick height to prevent browser reflow.
    // Img height is the same as img width, because
    // we're using square images
    var brickImg = $('#content').find('.brick .wrap-media .img-responsive'),
        brickImgHeight = brickImg.first().closest('.col').width();
    brickImg.css('height', brickImgHeight);


    // Set parent item as active
    // ==========================

    // Add `active` state class to the clicked item parent,
    // and remove it to its siblings
    function activeChangeItem (selector) {
        if (selector.parent().hasClass('active')) {
            selector
                .parent()
                .removeClass('active');
        } else {
            selector
                .parent()
                .addClass('active')
                .siblings()
                .removeClass('active');
        }
    }


    // Toggle Header Dropdowns
    // ==========================
    $('#header').find('.toggle').click(function (event) {

        // Prevent default anchor behavior
        event.preventDefault();

        // Caching variables
        var $this = $(this);

        // Set parent item as active
        activeChangeItem($this);

        // Remove `hidden` state class to the current dropdown,
        // and add it to its siblings
        $('#header')
            // select specific dropdown
            .find('.' + $this.data('toggle'))
            .toggleClass('hidden')
            // hide the rest of dropdowns, to prevent opening
            // more than one dropdown at a time
            .siblings('.dropdown')
            .addClass('hidden');

    });


    // Item color selector
    // ==========================

    $('#item-color-selector').change(function () {

        // Caching variables
        var $this = $(this),
            itemColor = $('#item-color'),
            itemColorName = $this.find('option:selected').text(),
            itemColorHex = $this.find('option:selected').data('hex');

        // Set properties to the item color square
        // dynamically, via JS, in the template
        itemColor
            .attr('alt', itemColorName)
            .css('background-color', itemColorHex);

    });


    // Collapser/Expander effect
    // ==========================

    $('.collapser').find('.collapser-trigger').click(function (event) {

        // Prevent default anchor behavior
        event.preventDefault();

        // Caching variables
        var $this = $(this),
            wrapper = $this.closest('.collapser'),
            icon = $this.find('.icon'),
            content = wrapper.find('.collapser-content');

        // State switch
        if (wrapper.hasClass('expanded')) {
            wrapper.removeClass('expanded');
            icon
                .removeClass('icon-chevron-down')
                .addClass('icon-chevron-right');
        }
        else {
            wrapper.addClass('expanded');
            icon
                .removeClass('icon-chevron-right')
                .addClass('icon-chevron-down');
        }

    });


    // Geo address data by geolocation
    // ================================

    $('#geolocation-registry-button').click(function () {
        // Get current position
        navigator.geolocation.getCurrentPosition(getAddressByLocation);
    });

    // Check that browser is compatible with geolocation,
    // if not disable the button and show inputs
    function checkGeolocationCompatibility() {
        if (!navigator.geolocation) {
            $('#geolocation-registry-button').hide();
        }
    }

    checkGeolocationCompatibility();

    // Get Address by Geolocation and call to setData
    function getAddressByLocation(position) {
        var locale = $('#geolocation-registry-button').data('locale'),
            url = 'http://nominatim.openstreetmap.org/reverse?format=xml&lat='+position.coords.latitude+'&lon='+position.coords.longitude+
            '&zoom=18&addressdetails=1&format=jsonv2&accept-language='+locale;

        $.get(url)
            .done(function(result) {
                setGeolocatedData(result.address);
            }
        );

    }

    // Set address to form
    function setGeolocatedData(address) {
        $('#delivery-address-city-name').val(address.city);

        if ("postcode" in address) {
            $('#delivery-address-zip-code').val(address.postcode);
        }

        setCountry(address.country_code);
    }

    // Get country id by code and select it in the select of register form
    function setCountry(country_code) {

        var url = $('#geolocation-registry-button').data('countryapi');

        $.post(url, { country_code: country_code } )
            .done(function(result) {
                $('#delivery-address-api-country').val(result.country_id);
                showLocatedAddress();
            }
        );
    }

    function showLocatedAddress() {
        // Caching variables
        var $this = $('#geolocation-registry-button'),
            deliveryAddress = $('#delivery-address-group');

        // Hide geolocation button
        $this.parent().addClass('hidden');

        // Show address inputs
        deliveryAddress.removeClass('hidden');
    }



    var bricksData = $('#bricks-data'),
        brickContainer = $('#bricks-content');

    window.bricks = initBricks({
        columnNumber: bricksData.data('bricks-column-number'),
        columnWidth: 226,
        columnGutter: bricksData.data('bricks-column-gutter') || 10,
        verticalGutter: 10,
        afterPositionAll: function() {
            // event handler triggered *after* the blocks
            // have been placed. We use it to make the whole
            // container visible. The initial markup sets
            // an "invisible" state for the container, since
            // we don't want to see the bunch of unpositioned
            // bricks in the instant *before* the position
            // calculations.
            brickContainer.css('visibility', 'visible');
        }
    });


    // Infinite scroll
    // ==========================

    if ($('#bricks-data').length > 0) {
        window.infiniteScroll = initInfiniteScroll({
            triggerDistancePercent: 65,
            containerId: 'bricks-content',
            loadUrl: searchData.bricksUrl+' .brick',
            startFromPage: ($("#bricks-data").data('start-page')) ? $("#bricks-data").data('start-page') : 2,
            loadingContent: function(e) {
                $('#loader').show();
            },
            loadedContent: function(e, data) {
                // in the bricks
                // we can now add and reposition the blocks
                if (data.elements.length > 0) {
                    if (typeof _gaq == 'object') {
                        //track pageview in analytics
                        var pagenum = ''+parseInt(infiniteScroll.getPage())-1+'';
                        _gaq.push(['_trackPageview', searchData.bricksUrl+'&page='+pagenum]);
                    }
                    bricks.addElements(data.elements);
                }
                $('#loader').hide();
            },
            endedContent: function(e) {
                $('#loader').hide();
            }
        });
    }


    // Autocomplete
    // ==========================
    /*
    $(function() {
        var availableTags = [
            "Lorem",
            "Ipsum",
            "Dolor",
            "Sit"
        ];
        $('#query').autocomplete({
            source: availableTags
        });
    });
    */


    // Cart change item quantity
    // ==========================

    $('.js-cart-item-stock').on('change', function() {
        var elem = $(this);
        elem.prop('disabled', true);
        var url = elem.data('seturl');
        var value = elem.val();
        $.post(url, { quantity: value } )
            .done(function(result) {
                if (result['url'].length > 1) {
                    window.location = result['url'];
                } else {
                    location.reload();
                }
            });
    });

    // Change web language
    // ==========================
    $('.js-language-select').on('change', function() {
        window.location = $(this).val();
    });

    // Select category
    // ==========================
    $('#category-selector').change(function () {
        window.location = $(this).val();
    });

    // Cart change item quantity
    // ==========================
    $('.js-cart-item-stock').on('change', function() {
        var elem = $(this);
        elem.prop('disabled', true);
        var url = elem.data('seturl');
        var value = elem.val();
        $.post(url, { quantity: value } )
            .done(function(result) {
                if (result['url'].length > 1) {
                    window.location = result['url'];
                } else {
                    location.reload();
                }
            });
    });

});


/* The window load event executes a bit later when the complete page
   is fully loaded, including all frames, objects and images.
 */
$(window).load(function() {

    // Reset brick height
    // ==========================

    // Reset brick height to 'auto' preventing issues
    // caused for window resizing
    var brickImg = $('#content').find('.brick .wrap-media .img-responsive');
    brickImg.css('height', 'auto');


    // Button "To top"
    // ==========================

    // Caching variables
    var toTopBtn = $('#to-top'),
        toTopWindowHeight = $(window).height();

    // Show/hide the button
    $(window).bind('scroll', function() {
        if ($(window).scrollTop() >= toTopWindowHeight) {
            toTopBtn.removeClass('pushed');
        }
        else {
            toTopBtn.addClass('pushed');
        }
    });

    // Scroll to the top of the page, when button is clicked
    toTopBtn.click(function (event) {
        event.preventDefault();
        $('html, body').animate({scrollTop:0}, {scrollSpeed:200}, {easingType:'linear'});
        return false;
    });

});
