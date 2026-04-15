define([
    'jquery',
    'mage/storage',
    'mage/url'
], function ($, storage, urlBuilder) {
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
        var postcode = $.trim($('input[name="postcode"]').first().val() || '');

        if (!postcode) {
            $('.cart-summary, .block.shipping, #block-shipping').find('*').each(function () {
                var text = $.trim($(this).text() || '');
                if (/^\d{5}$/.test(text)) {
                    postcode = text;
                    return false;
                }
            });
        }

        return postcode;
    }

    function getCheckoutButton() {
        return $('.cart-summary .action.primary.checkout, .checkout-methods-items .action.primary.checkout').first();
    }

    function getSidebarContainer() {
        return $('.cart-summary .block.shipping, .cart-summary #block-shipping, .block.shipping, #block-shipping').first();
    }

    function ensureSidebarMessageContainer() {
        var $container = $('#gdmexico-restricted-sidebar-message');

        if (!$container.length) {
            var $sidebar = getSidebarContainer();

            if ($sidebar.length) {
                $sidebar.after('<div id="gdmexico-restricted-sidebar-message" style="display:none; margin-top:16px;"></div>');
            } else {
                $('.cart-summary').append('<div id="gdmexico-restricted-sidebar-message" style="display:none; margin-top:16px;"></div>');
            }

            $container = $('#gdmexico-restricted-sidebar-message');
        }

        return $container;
    }

    function clearMessages() {
        $('#gdmexico-restricted-cart-message').remove();
        $('#gdmexico-restricted-sidebar-message').hide().empty();

        getCheckoutButton()
            .prop('disabled', false)
            .removeClass('disabled');
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

    function renderSidebarMessage(response) {
        var $wrapper = $('<div/>', {
            'class': 'message error'
        });

        $('<div/>')
            .text(response.message || '')
            .appendTo($wrapper);

        if (response.matched_items && response.matched_items.length) {
            $wrapper.append(buildRestrictedItemsList(response.matched_items));
        }

        ensureSidebarMessageContainer().empty().append($wrapper).show();
    }

    function validate() {
        var postcode = getPostcode();
        var key;

        if (!postcode) {
            clearMessages();
            return;
        }

        key = postcode;

        if (lastKey === key && cache[key]) {
            clearMessages();

            if (cache[key].is_restricted) {
                renderSidebarMessage(cache[key]);
                getCheckoutButton().prop('disabled', true).addClass('disabled');
            }

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

            clearMessages();

            if (!response.is_restricted) {
                return;
            }

            renderSidebarMessage(response);

            getCheckoutButton()
                .prop('disabled', true)
                .addClass('disabled');
        }).fail(function () {
            clearMessages();
        });
    }

    function scheduleValidation(delay) {
        clearTimeout(timer);
        timer = setTimeout(validate, delay || 350);
    }

    return function () {
        $(document).ready(function () {
            scheduleValidation(400);
        });

        $(document).on('keyup change blur', 'input[name="postcode"]', function () {
            scheduleValidation(350);
        });

        $(document).ajaxComplete(function () {
            scheduleValidation(500);
        });

        $(document).on('click', '.cart-summary .action.primary.checkout, .checkout-methods-items .action.primary.checkout', function (e) {
            if ($(this).prop('disabled') || $(this).hasClass('disabled')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
    };
});