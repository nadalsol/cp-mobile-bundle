var searchData = new Object();
searchData.query = '';
searchData.priceRange = [];
searchData.originalUrl = '';
searchData.bricksUrl = '';
searchData.minPrice = parseInt($('#amount-min').data('min'));
searchData.defaultMaxPrice = parseInt($('#amount-max').data('max'));
searchData.maxPrice = searchData.defaultMaxPrice;
searchData.searchMinPrice = 0;
searchData.searchMaxPrice = searchData.defaultMaxPrice;
searchData.characteristics = [];

function addCharacteristic(characteristic) {
    if ($.inArray(characteristic, searchData.characteristics) == -1) {
        searchData.characteristics.push(characteristic);
    }
}

function removeCharacteristic(characteristic) {
    if ($.inArray(characteristic, searchData.characteristics) != -1) {
        searchData.characteristics = jQuery.grep(searchData.characteristics, function(value) {
            return value != characteristic;
        });
    }
}

function addPriceRange(rangeFrom, rangeTo) {
    searchData.priceRange = [];
    if (typeof(rangeFrom) == 'number') {
        searchData.priceRange.push(rangeFrom);
    }
    if (typeof(rangeTo) == 'number') {
        searchData.priceRange.push(rangeTo);
    }
}

function removePriceRange() {
    searchData.priceRange = [];
}


//prepare bricks url
function prepareBricksUrl() {

    var bricksArray = new Array();

    //add query
    if (searchData.query != undefined && searchData.query.length > 0) {
        bricksArray.push('query=' + encodeURI(searchData.query));
    }

    //add price range
    if (searchData.priceRange.length > 0) {
        bricksArray.push('price_range[]=' + searchData.priceRange[0]);
        bricksArray.push('price_range[]=' + searchData.priceRange[1]);
    }


    if (searchData.originalUrl) {
        var currentUrlSplitted = searchData.originalUrl.split('?', 2);
        if (currentUrlSplitted[1] != undefined) {
            bricksArray = bricksArray.concat(currentUrlSplitted[1].split('&'));
        }
    }

    // Cleaning all keys with blank value
    for (var key in bricksArray) {
        var value = bricksArray[key];
        if (typeof(value) == 'string') {;
            var realValue = value.split('=', 2);
            if (realValue[1] == undefined || realValue[1] == null || realValue[1] == '') {

                bricksArray.splice(key, 1);
            }
        }
    }

    if (searchData.characteristics) {
        for (var key in searchData.characteristics) {
            var value = searchData.characteristics[key];
            if (typeof(value) == 'string') {;
                bricksArray.push('characteristics[]=' + value);
            }
        }
    }

    if (typeof currentUrlSplitted != "undefined") {
        searchData.bricksUrl = currentUrlSplitted[0] + '?' + bricksArray.join('&');
    }
}


function setSearchDataAtLoad() {
    var brickData = $('#bricks-data');

    if (brickData.data('price-range-products-max') > searchData.maxPrice) {
        searchData.maxPrice = brickData.data('price-range-products-max');
    }

    if (searchData.defaultMaxPrice == searchData.searchMaxPrice && searchData.maxPrice != searchData.defaultMaxPrice) {
        searchData.searchMaxPrice = searchData.maxPrice;
    }

    searchData.originalUrl = brickData.data('bricks-url');
    if (typeof brickData.data('query') != undefined) {
        searchData.query = brickData.data('query');
    }

    if (brickData.data('price-range-min') > 0) {
        searchData.searchMinPrice = brickData.data('price-range-min');
    }
    if (brickData.data('price-range-max') > 0) {
        searchData.searchMaxPrice = brickData.data('price-range-max');
    }
    if (brickData.data('characteristics')) {
        searchData.characteristics = brickData.data('characteristics');
    }

    addPriceRange(searchData.searchMinPrice, searchData.searchMaxPrice);
}

function loadNewFilter() {
    prepareBricksUrl();
    window.location.href = searchData.bricksUrl;
}

$(document).ready(function() {
    $('.select-filter-category').click(function (){
        if ($(this).prop('checked')) {
            addCategory($(this).data('category-id'));
        } else {
            removeCategory($(this).data('category-id'));
        }

        loadNewFilter();
    });

    $('.select-filter-city').click(function (){
        if ($(this).prop('checked')) {
            addCity($(this).data('city-id'));
        } else {
            removeCity($(this).data('city-id'));
        }

        loadNewFilter();
    });

    /*** Price slider range
     **************************************************************************/
    $(function() {
        /*** Price slider: Range ***/
        var sliderRange = $('#slider-range');
        var amountMin = $('#amount-min');
        var amountMax = $('#amount-max');
        var formModFilters = $('form.mod-filters');
        var globalMinAmount = parseInt($('#amount-min').data('min'));
        var globalMaxAmount = parseInt($('#amount-max').data('max'));

        function filter_amounts(min_amount, max_amount)
        {
            var amounts = {};
            var max_amount_text = parseInt(max_amount);
            if (max_amount >= globalMaxAmount) {
                max_amount_text = ' +' + globalMaxAmount;
            }
            if (max_amount < globalMinAmount) {
                max_amount = globalMinAmount;
            }
            if (min_amount > (max_amount - 5)) {
                min_amount = max_amount - 5;
            }
            var min_amount_text = parseInt(min_amount);
            amounts.min = min_amount;
            amounts.min_text = min_amount_text;
            amounts.max = max_amount;
            amounts.max_text = max_amount_text;

            return amounts;
        }

        sliderRange.slider({
            range: true,
            min: searchData.minPrice,
            max: searchData.maxPrice,
            values: [ searchData.searchMinPrice, searchData.searchMaxPrice ],
            slide: function(event, ui) {
                var amounts = filter_amounts(ui.values[0], ui.values[1]);
                amountMin.text(amounts.min_text);
                amountMax.text(amounts.max_text);
            },
            change: function(event) {
                if (!(event.originalEvent)) {
                    return;
                }
                var amounts = filter_amounts($("#slider-range").slider('values')[0], $("#slider-range").slider('values')[1]);
                addPriceRange(amounts.min, amounts.max);
                amountMin.text(amounts.min_text);
                amountMax.text(amounts.max_text);
                loadNewFilter();
            }
        });
        var amounts = filter_amounts(sliderRange.slider('values', 0), sliderRange.slider('values', 1));
        amountMin.text(amounts.min_text);
        amountMax.text(amounts.max_text);

        /*** Price slider: Limit indicators (beneath the slider) positioning ***/
        var limit_indicator_min = formModFilters.find('.mod-filters-slider li').first(),
            limit_indicator_max = formModFilters.find('.mod-filters-slider li').last(),
            limit_default_val_min = searchData.minPrice,
            limit_default_val_max = searchData.maxPrice;

        limit_indicator_min.text(limit_default_val_min);
        limit_indicator_max.text(limit_default_val_max);
    });

    $('.select-several-categories').click(function (){
        loadNewFilter();
    });

    $('.select-several-cities').click(function (){
    });

    setSearchDataAtLoad();
    prepareBricksUrl();
});