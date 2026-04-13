define([
    'jquery',
    'mage/storage',
    'mage/url'
], function ($, storage, urlBuilder) {
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
                $sidebar.after(
                    '<div id="gdmexico-restricted-sidebar-message" style="display:none; margin-top:16px;"></div>'
                );
            } else {
                $('.cart-summary').append(
                    '<div id="gdmexico-restricted-sidebar-message" style="display:none; margin-top:16px;"></div>'
                );
            }

            $container = $('#gdmexico-restricted-sidebar-message');
        }

        return $container;
    }

    function clearMessages() {
        // Quitar mensaje superior si existe
        $('#gdmexico-restricted-cart-message').remove();

        // Limpiar mensaje lateral
        $('#gdmexico-restricted-sidebar-message').hide().html('');

        // Limpiar mensajes por item
        $('.gdmexico-restricted-item-message').remove();

        getCheckoutButton()
            .prop('disabled', false)
            .removeClass('disabled');
    }

    function renderSidebarMessage(response) {
        var html = '<div class="message error">' +
            '<div>' + response.message;

        if (response.matched_items && response.matched_items.length) {
            html += '<br><strong>Productos restringidos:</strong><ul>';

            $.each(response.matched_items, function (index, item) {
                html += '<li>' + item.name + ' (' + item.sku + ')</li>';
            });

            html += '</ul>';
        }

        html += '</div></div>';

        ensureSidebarMessageContainer().html(html).show();
    }

    function markItems(response) {
        $.each(response.matched_items || [], function (index, item) {
            var $qtyInput = $('input.cart-item-qty[data-cart-item-id="' + item.item_id + '"]');
            if (!$qtyInput.length) {
                return;
            }

            var $row = $qtyInput.closest('tr');
            if ($row.length && !$row.next('.gdmexico-restricted-item-message').length) {
                $row.after(
                    '<tr class="gdmexico-restricted-item-message">' +
                        '<td colspan="99">' +
                            '<div class="message error">' +
                                '<div>Este producto no puede enviarse al municipio seleccionado: ' + response.municipality + '.</div>' +
                            '</div>' +
                        '</td>' +
                    '</tr>'
                );
            }
        });
    }

    return function (config) {
        var cartId = config.cartId || '';

        function validate() {
            var postcode = getPostcode();

            if (!cartId || !postcode) {
                clearMessages();
                return;
            }

            var serviceUrl = urlBuilder.build(
                '/rest/V1/restricted-shipping/validate/' + cartId + '/' + encodeURIComponent(postcode)
            );

            storage.get(serviceUrl, false).done(function (rawResponse) {
                var response = normalizeResponse(rawResponse);

                clearMessages();

                if (!response.is_restricted) {
                    return;
                }

                renderSidebarMessage(response);
                markItems(response);

                getCheckoutButton()
                    .prop('disabled', true)
                    .addClass('disabled');
            }).fail(function () {
                clearMessages();
            });
        }

        $(document).ready(function () {
            setTimeout(validate, 500);
            setTimeout(validate, 1500);
        });

        $(document).on('keyup change blur', 'input[name="postcode"]', function () {
            setTimeout(validate, 300);
        });

        $(document).ajaxComplete(function () {
            setTimeout(validate, 800);
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