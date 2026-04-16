define([
    'jquery',
    'mage/storage',
    'mage/url',
    'Magento_Checkout/js/model/quote'
], function ($, storage, urlBuilder, quote) {
    'use strict';

    var timer = null;
    var pendingRequest = null;
    var lastKey = null;
    var cache = {};

    function normalizeResponse(response) {
        response = response || {};

        return {
            is_restricted: !!response.is_restricted,
            municipality: response.municipality || '',
            message: response.message || '',
            matched_items: Array.isArray(response.matched_items) ? response.matched_items : []
        };
    }

    function getPostcode() {
        var shippingAddress = quote.shippingAddress() || {};
        return $.trim(shippingAddress.postcode || '');
    }

    function removeMessages() {
        $('#gdmexico-restricted-method-message').remove();
    }

    function buildRestrictedItemsList(items) {
        var $wrapper = $('<div/>');
        var $title = $('<div/>', {
            style: 'margin-top:10px; font-weight:600;'
        }).text('Productos restringidos:');
        var $ul = $('<ul/>', {
            style: 'margin-top:8px; padding-left:18px;'
        });

        $.each(items, function (index, item) {
            var name = item && item.name ? String(item.name) : '';
            var sku = item && item.sku ? String(item.sku) : '';
            var label = name;

            if (sku) {
                label += ' (' + sku + ')';
            }

            $('<li/>').text(label).appendTo($ul);
        });

        $wrapper.append($title).append($ul);

        return $wrapper;
    }

    function renderMethodMessage(response) {
        var $container = $('#checkout-step-shipping_method');

        if (!$container.length) {
            return;
        }

        $('#gdmexico-restricted-method-message').remove();

        var $message = $('<div/>', {
            id: 'gdmexico-restricted-method-message',
            'class': 'message error',
            style: 'margin: 0 0 16px;'
        });

        $('<div/>').text(response.message || '').appendTo($message);

        if (response.matched_items && response.matched_items.length) {
            $message.append(buildRestrictedItemsList(response.matched_items));
        }

        $container.prepend($message);
        $container.find('.no-quotes-block').hide();
        quote.shippingMethod(null);
    }

    function showNoQuotesBlock() {
        $('#checkout-step-shipping_method .no-quotes-block').show();
    }

    function validateRestriction() {
        var postcode = getPostcode();
        var key;

        if (!postcode) {
            removeMessages();
            return;
        }

        key = postcode;

        if (lastKey === key && cache[key]) {
            removeMessages();

            if (!cache[key].is_restricted) {
                showNoQuotesBlock();
                return;
            }

            renderMethodMessage(cache[key]);
            return;
        }

        if (pendingRequest && pendingRequest.abort) {
            pendingRequest.abort();
        }

        pendingRequest = storage.get(
            urlBuilder.build('restrictedshipping/ajax/validate?postcode=' + encodeURIComponent(postcode)),
            false
        );

        pendingRequest.done(function (rawResponse) {
            var response = normalizeResponse(rawResponse);

            cache[key] = response;
            lastKey = key;

            removeMessages();

            if (!response.is_restricted) {
                showNoQuotesBlock();
                return;
            }

            renderMethodMessage(response);
        }).fail(function () {
            removeMessages();
        });
    }

    function scheduleValidation(delay) {
        clearTimeout(timer);
        timer = setTimeout(validateRestriction, delay || 350);
    }

    return function () {
        $(document).ready(function () {
            scheduleValidation(500);
        });

        quote.shippingAddress.subscribe(function () {
            scheduleValidation(350);
        });

        $(document).on('change blur keyup', 'input[name="postcode"]', function () {
            scheduleValidation(350);
        });

        $(document).ajaxComplete(function () {
            scheduleValidation(450);
        });
    };
});