(function () {
    'use strict';

    var i18n = (window.wp && window.wp.i18n) ? window.wp.i18n.__ : function (s) { return s; };

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
                case 'subscription_create_failed':
                    return i18n('Aboneliğiniz oluşturulamadığı için ödemeniz iyzico tarafında otomatik olarak iptal edildi. Hesabınızdan bir tahsilat yapılmamıştır. Lütfen tekrar deneyiniz veya destek ile iletişime geçiniz.', 'iyzico-subscription');
                case 'payment_failed':
                    return i18n('Ödeme başarısız oldu. Lütfen tekrar deneyiniz.', 'iyzico-subscription');
                case 'general_error':
                    return (msg
                        ? i18n('İşlem sırasında bir hata oluştu: ', 'iyzico-subscription') + msg + '. '
                        : i18n('İşlem sırasında bir hata oluştu. ', 'iyzico-subscription'))
                        + i18n('Hesabınızdan herhangi bir tahsilat yapılmamıştır. Lütfen tekrar deneyiniz veya destek ile iletişime geçiniz.', 'iyzico-subscription');
                default:
                    return i18n('Ödemeniz tamamlanamadı. Hesabınızdan tahsilat yapılmamıştır. Lütfen tekrar deneyiniz.', 'iyzico-subscription');
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

    function dispatchBlocksError(message) {
        if (!window.wp || !window.wp.data || !window.wp.data.dispatch) return false;
        var notices = window.wp.data.dispatch('core/notices');
        if (!notices || typeof notices.createErrorNotice !== 'function') return false;
        notices.createErrorNotice(message, {
            isDismissible: true,
            context: 'wc/checkout',
        });
        return true;
    }

    function appendClassicNotice(message) {
        var $ = window.jQuery;
        var html = '<ul class="woocommerce-error" role="alert"><li>' +
            (function () {
                var d = document.createElement('div');
                d.appendChild(document.createTextNode(message));
                return d.innerHTML;
            })() + '</li></ul>';

        if ($) {
            var $wrap = $('.woocommerce-notices-wrapper').first();
            if (!$wrap.length) {
                $wrap = $('<div class="woocommerce-notices-wrapper"></div>').prependTo('form.checkout, .woocommerce, main, body');
            }
            $wrap.append(html);
            try { $('html, body').animate({ scrollTop: $wrap.offset().top - 80 }, 300); } catch (e) {}
        } else {
            var wrap = document.querySelector('.woocommerce-notices-wrapper');
            if (!wrap) {
                wrap = document.createElement('div');
                wrap.className = 'woocommerce-notices-wrapper';
                var host = document.querySelector('form.checkout') || document.querySelector('.woocommerce') || document.body;
                host.insertBefore(wrap, host.firstChild);
            }
            wrap.insertAdjacentHTML('beforeend', html);
        }
    }

    function showWithRetry(message, attempt) {
        attempt = attempt || 0;
        var isBlocksCheckout = !!document.querySelector('[data-block-name="woocommerce/checkout"]')
            || !!document.querySelector('.wp-block-woocommerce-checkout')
            || !!document.querySelector('.wc-block-checkout');

        if (isBlocksCheckout) {
            if (dispatchBlocksError(message)) {
                cleanUrl();
                return;
            }
            if (attempt > 50) {
                appendClassicNotice(message);
                cleanUrl();
                return;
            }
            setTimeout(function () { showWithRetry(message, attempt + 1); }, 200);
            return;
        }

        appendClassicNotice(message);
        cleanUrl();
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
})();
