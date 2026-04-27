(function () {
    'use strict';

    var settings = (window.wc && window.wc.wcSettings)
        ? window.wc.wcSettings.getSetting('iyzico_subscription_data', {})
        : {};
    var i18n = (window.wp && window.wp.i18n) ? window.wp.i18n.__ : function (s) { return s; };
    var label = (window.wp && window.wp.htmlEntities)
        ? window.wp.htmlEntities.decodeEntities(settings.title || '')
        : (settings.title || '');
    if (!label) {
        label = i18n('iyzico Abonelik', 'iyzico-subscription');
    }

    /**
     * Blocks store hazır olana kadar bekleyip hata mesajı yayınlar.
     */
    function dispatchBlocksError(message) {
        if (!message) return false;
        if (!window.wp || !window.wp.data || !window.wp.data.dispatch) {
            return false;
        }
        var notices = window.wp.data.dispatch('core/notices');
        if (!notices || typeof notices.createErrorNotice !== 'function') {
            return false;
        }
        notices.createErrorNotice(message, {
            isDismissible: true,
            context: 'wc/checkout',
        });
        return true;
    }

    /**
     * Classic checkout için fallback (Blocks değilse).
     */
    function appendClassicNotice(message) {
        if (!message) return;
        var $ = window.jQuery;
        if (!$) return;
        var $wrap = $('.woocommerce-notices-wrapper').first();
        if (!$wrap.length) {
            $wrap = $('<div class="woocommerce-notices-wrapper"></div>').prependTo('form.checkout, .woocommerce, body');
        }
        $wrap.append(
            '<ul class="woocommerce-error" role="alert"><li>' +
            $('<div/>').text(message).html() +
            '</li></ul>'
        );
        $('html, body').animate({ scrollTop: $wrap.offset().top - 80 }, 300);
    }

    function buildErrorMessage() {
        try {
            var url = new URL(window.location.href);
            var err = url.searchParams.get('iyzico_error');
            if (!err) return null;
            var code = url.searchParams.get('error_code') || '';
            var msg = url.searchParams.get('error_message') || '';
            try { msg = decodeURIComponent(msg); } catch (e) {}
            try { msg = msg.replace(/\+/g, ' '); } catch (e) {}

            switch (err) {
                case 'card_info_missing':
                    return i18n('Kart bilgileri alınamadığı için ödeme iptal edildi. Lütfen tekrar deneyiniz.', 'iyzico-subscription');
                case 'cancel_failed':
                    return i18n('Ödeme iptal edilemedi.', 'iyzico-subscription')
                        + (code ? ' (' + i18n('Hata Kodu', 'iyzico-subscription') + ': ' + code + ')' : '')
                        + (msg ? ' — ' + msg : '');
                case 'payment_failed':
                    return i18n('Ödeme başarısız oldu. Lütfen tekrar deneyiniz.', 'iyzico-subscription');
                case 'general_error':
                    return i18n('İşlem sırasında bir hata oluştu.', 'iyzico-subscription') + (msg ? ' — ' + msg : '');
                default:
                    return i18n('Bilinmeyen bir hata oluştu. Lütfen tekrar deneyiniz.', 'iyzico-subscription');
            }
        } catch (e) {
            return null;
        }
    }

    function cleanUrl() {
        try {
            var url = new URL(window.location.href);
            ['iyzico_error', 'error_code', 'error_message', 'order_id'].forEach(function (k) {
                url.searchParams.delete(k);
            });
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    function showWithRetry(message, attempt) {
        attempt = attempt || 0;
        if (dispatchBlocksError(message)) {
            cleanUrl();
            return;
        }
        if (attempt > 40) {
            appendClassicNotice(message);
            cleanUrl();
            return;
        }
        setTimeout(function () { showWithRetry(message, attempt + 1); }, 200);
    }

    function init() {
        var message = buildErrorMessage();
        if (!message) return;
        showWithRetry(message, 0);
    }

    if (window.wp && window.wp.domReady) {
        window.wp.domReady(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    if (window.wc && window.wc.wcBlocksRegistry && window.wp && window.wp.element) {
        var Content = function () {
            return (window.wp.htmlEntities ? window.wp.htmlEntities.decodeEntities(settings.description || '') : (settings.description || ''));
        };

        var Block_Gateway = {
            name: 'iyzico_subscription',
            label: label,
            content: Object(window.wp.element.createElement)(Content, null),
            edit: Object(window.wp.element.createElement)(Content, null),
            canMakePayment: function () { return true; },
            ariaLabel: label,
            supports: {
                features: settings.supports || ['products'],
            },
        };

        window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
    }
})();
