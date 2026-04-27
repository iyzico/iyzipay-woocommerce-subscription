=== iyzico Subscription for WooCommerce ===
Contributors: iyzico
Tags: woocommerce, payments, subscription, iyzico, recurring
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept secure recurring (subscription) payments on your WooCommerce store via the iyzico payment gateway.

== Description ==

iyzico Subscription for WooCommerce lets your store charge customers on a recurring basis (daily / weekly / monthly / yearly) using iyzico's payment infrastructure. The plugin uses iyzico's hosted Checkout Form for the first payment and the stored-card (tokenization) feature for automatic renewals.

Key features:

* iyzico hosted Checkout Form with 3D Secure for the first payment
* Automatic renewals using a securely stored card token
* Customer-facing "My Subscriptions" and "My Saved Cards" account pages
* Native WordPress List Table for the admin dashboard (filters, bulk actions, pagination)
* Manual payment trigger for failed renewals
* Automatic suspension after 3 failed payments; daily retry for suspended ones
* HTML email notifications (created, renewal, failed, cancelled, suspended, expiring)
* WooCommerce HPOS (Custom Order Tables) compatible
* WooCommerce Blocks (Cart & Checkout) compatible
* Guest checkout is blocked for subscription products (login/register required)
* Turkish ID number checkout field with algorithmic validation
* GDPR / KVKK personal data exporter and eraser
* Multi-currency support: TRY, USD, EUR, GBP, CHF, NOK, RUB, IRR
* Turkish and English language files included
* Sandbox mode for testing

The plugin is independent of the WooCommerce Subscriptions premium add-on; it provides its own subscription engine.

== Installation ==

1. Download the release ZIP (vendor directory included) and upload it to `wp-content/plugins/`.
2. Activate the plugin from the WordPress Plugins page.
3. Go to **WooCommerce > Settings > Payments > iyzico Subscription** and enter your iyzico API key and secret key.
4. Make sure "Stored Card" feature is enabled in your iyzico merchant panel.

For developers (cloning from GitHub):

`composer install --no-dev -o`

== Frequently Asked Questions ==

= Is the WooCommerce Subscriptions premium plugin required? =

No. This plugin provides its own subscription engine.

= Is guest checkout supported? =

No. Subscription management requires a registered customer account. Guests are redirected to login or register on the cart and checkout pages.

= How are renewals handled? =

A WP-Cron event runs hourly and charges any active subscriptions whose `next_payment` date has passed, using the stored iyzico card token. We strongly recommend a real system cron for low-traffic sites.

= What happens if a renewal fails? =

The `failed_payments` counter increments. After 3 consecutive failures the subscription is suspended. A daily retry job attempts to re-charge suspended subscriptions for up to 5 retries.

= Is 3D Secure used? =

Yes for the initial payment (handled by the iyzico hosted form). Renewals use the stored token in non-3DS mode, which is allowed for stored-card recurring payments.

== Changelog ==

= 1.1.0 =
* Initial public release.
* Stored-card based automatic renewals
* Native WordPress admin (WP_List_Table)
* Guest checkout blocking + login flow
* Turkish ID number checkout validation
* GDPR / KVKK exporter & eraser
* Multi-currency support
* WooCommerce HPOS / Blocks compatibility
* Atomic refund on subscription create failure (no orphaned charges)

== Upgrade Notice ==

= 1.1.0 =
First public release.
