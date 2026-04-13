define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/modal/alert',
    'mage/storage',
    'mage/url'
], function ($, wrapper, quote, alert, storage, urlBuilder) {
    'use strict';

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction, messageContainer) {
            var shippingAddress = quote.shippingAddress() || {};
            var postcode = shippingAddress.postcode || '';
            var cartId = quote.getQuoteId();

            if (!postcode || !cartId) {
                return originalAction(messageContainer);
            }

            return storage.get(
                urlBuilder.build('/rest/V1/restricted-shipping/validate/' + cartId + '/' + encodeURIComponent(postcode)),
                false
            ).then(function (response) {
                if (response && response.is_restricted) {
                    alert({
                        title: $.mage.__('Envío no disponible'),
                        content: response.message
                    });

                    return $.Deferred().reject(response.message);
                }

                return originalAction(messageContainer);
            });
        });
    };
});