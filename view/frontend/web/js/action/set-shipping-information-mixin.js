define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/modal/alert',
    'mage/storage',
    'mage/url'
], function ($, wrapper, quote, alert, storage, urlBuilder) {
    'use strict';

    function normalizeResponse(response) {
        response = response || {};

        return {
            is_restricted: !!response.is_restricted,
            municipality: response.municipality || '',
            message: response.message || '',
            matched_items: Array.isArray(response.matched_items) ? response.matched_items : []
        };
    }

    function escapeHtml(value) {
        return $('<div/>').text(value || '').html();
    }

    function buildAlertContent(response) {
        var content = '<div>' + escapeHtml(response.message || '') + '</div>';

        if (response.matched_items && response.matched_items.length) {
            content += '<div style="margin-top:10px;font-weight:600;">Productos restringidos:</div>';
            content += '<ul style="margin-top:8px;padding-left:18px;">';

            $.each(response.matched_items, function (index, item) {
                var name = item && item.name ? String(item.name) : '';
                var sku = item && item.sku ? String(item.sku) : '';
                var label = name;

                if (sku) {
                    label += ' (' + sku + ')';
                }

                content += '<li>' + escapeHtml(label) + '</li>';
            });

            content += '</ul>';
        }

        return content;
    }

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction, messageContainer) {
            var shippingAddress = quote.shippingAddress() || {};
            var postcode = $.trim(shippingAddress.postcode || '');

            if (!postcode) {
                return originalAction(messageContainer);
            }

            return storage.get(
                urlBuilder.build('restrictedshipping/ajax/validate?postcode=' + encodeURIComponent(postcode)),
                false
            ).then(function (rawResponse) {
                var response = normalizeResponse(rawResponse);

                if (response.is_restricted) {
                    alert({
                        title: $.mage.__('Envío no disponible'),
                        content: buildAlertContent(response)
                    });

                    return $.Deferred().reject(response.message);
                }

                return originalAction(messageContainer);
            });
        });
    };
});