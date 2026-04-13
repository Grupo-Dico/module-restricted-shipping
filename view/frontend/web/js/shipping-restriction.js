define([
    'jquery',
    'mage/storage',
    'mage/url',
    'Magento_Checkout/js/model/quote'
], function ($, storage, urlBuilder, quote) {
    'use strict';

    function normalizeResponse(response) {
        if ($.isArray(response)) {
            return {
                is_restricted: response[0] || false,
                municipality: response[1] || '',
                matched_items: response[2] || [],
                message: response[3] || ''
            };
        }

        return response || {
            is_restricted: false,
            municipality: '',
            matched_items: [],
            message: ''
        };
    }

    function getCartId() {
        return quote.getQuoteId();
    }

    function getPostcode() {
        var shippingAddress = quote.shippingAddress() || {};
        return $.trim(shippingAddress.postcode || '');
    }

    function removeMessages() {
        $('#gdmexico-restricted-top-message').remove();
        $('#gdmexico-restricted-method-message').remove();
    }

    function renderTopMessage(response) {
        if ($('#gdmexico-restricted-top-message').length) {
            return;
        }

        var html = '<div id="gdmexico-restricted-top-message" class="message error" style="margin: 0 0 20px;">' +
            '<div>' + response.message;

        if (response.matched_items && response.matched_items.length) {
            html += '<br><strong>Productos restringidos:</strong><ul>';

            $.each(response.matched_items, function (index, item) {
                html += '<li>' + item.name + ' (' + item.sku + ')</li>';
            });

            html += '</ul>';
        }

        html += '</div></div>';

        $('.checkout-shipping-address, #checkout-step-shipping').first().before(html);
    }

    function rKaNCgLvMEXxNzMxj2F7FYi1AdRrTo6Nhu(response) {
        var $container = $('#checkout-step-shipping_method .step-content');

        if (!$container.length) {
            return;
        }

        $('#gdmexico-restricted-method-message').remove();

        var html = '<div id="gdmexico-restricted-method-message" class="message error" style="margin: 0 0 16px;">' +
            '<div>' + response.message;

        if (response.matched_items && response.matched_items.length) {
            html += '<br><strong>Productos restringidos:</strong><ul>';

            $.each(response.matched_items, function (index, item) {
                html += '<li>' + item.name + ' (' + item.sku + ')</li>';
            });

            html += '</ul>';
        }

        html += '</div></div>';

        $container.prepend(html);

        $container.find('.no-quotes-block').hide();
        quote.shippingMethod(null);
    }

    function validateRestriction() {
        var cartId = getCartId();
        var postcode = getPostcode();

        if (!cartId || !postcode) {
            removeMessages();
            return;
        }

        storage.get(
            urlBuilder.build('/rest/V1/restricted-shipping/validate/' + cartId + '/' + encodeURIComponent(postcode)),
            false
        ).done(function (rawResponse) {
            var response = normalizeResponse(rawResponse);

            removeMessages();

            if (!response.is_restricted) {
                $('#checkout-step-shipping_method .step-content .no-quotes-block').show();
                return;
            }

            renderTopMessage(response);
            rKaNCgLvMEXxNzMxj2F7FYi1AdRrTo6Nhu(response);
        }).fail(function () {
            removeMessages();
        });
    }

    return function () {
        $(document).ready(function () {
            setTimeout(validateRestriction, 800);
            setTimeout(validateRestriction, 1800);
        });

        quote.shippingAddress.subscribe(function () {
            setTimeout(validateRestriction, 500);
        });

        $(document).on('change blur keyup', 'input[name="postcode"]', function () {
            setTimeout(validateRestriction, 400);
        });

        $(document).ajaxComplete(function () {
            setTimeout(validateRestriction, 600);
        });
    };
});