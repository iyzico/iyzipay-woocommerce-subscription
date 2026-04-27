<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\EmailServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\TemplateServiceInterface;

class EmailService implements EmailServiceInterface
{
    private TemplateServiceInterface $templateService;

    public function __construct(TemplateServiceInterface $templateService) {
        $this->templateService = $templateService;
        
        add_action('iyzico_subscription_created', [$this, 'sendSubscriptionCreatedEmail']);
        add_action('iyzico_subscription_renewal_success', [$this, 'sendRenewalSuccessEmail']);
        add_action('iyzico_subscription_renewal_failed', [$this, 'sendRenewalFailedEmail'], 10, 2);
        add_action('iyzico_subscription_cancelled', [$this, 'sendCancellationEmail']);
        add_action('iyzico_subscription_suspended', [$this, 'sendSuspensionEmail']);
        add_action('iyzico_subscription_expiring', [$this, 'sendExpiringEmail']);
    }

    public function sendSubscriptionCreatedEmail(object $subscription): void {
        if (!$this->isEmailEnabled()) {
            return;
        }

        $user = get_user_by('id', $subscription->user_id);
        $product = get_post($subscription->product_id);
        
        /* translators: 1: product title */
        $subject = sprintf(__('Aboneliğiniz Oluşturuldu - %1$s', 'iyzipay-woocommerce-subscription'), $product->post_title);
        
        $template_data = [
            'user_name' => $user->display_name,
            'product_name' => $product->post_title,
            'amount' => wc_price($subscription->amount),
            'period' => $this->templateService->getPeriodLabel($subscription->period),
            'start_date' => gmdate('d.m.Y', strtotime($subscription->start_date)),
            'next_payment' => gmdate('d.m.Y', strtotime($subscription->next_payment)),
            'subscription_id' => $subscription->id,
        ];
        
        $message = $this->templateService->loadTemplate('subscription-created', $template_data);
        
        $this->sendEmail($user->user_email, $subject, $message);
        
        $this->sendAdminNotification('new_subscription', $subscription);
    }

    public function sendRenewalSuccessEmail(object $subscription): void {
        if (!$this->isEmailEnabled()) {
            return;
        }

        $user = get_user_by('id', $subscription->user_id);
        $product = get_post($subscription->product_id);
        
        /* translators: 1: product title */
        $subject = sprintf(__('Aboneliğiniz Yenilendi - %1$s', 'iyzipay-woocommerce-subscription'), $product->post_title);
        
        $template_data = [
            'user_name' => $user->display_name,
            'product_name' => $product->post_title,
            'amount' => wc_price($subscription->amount),
            'payment_date' => gmdate('d.m.Y'),
            'next_payment' => gmdate('d.m.Y', strtotime($subscription->next_payment)),
            'subscription_id' => $subscription->id,
        ];
        
        $message = $this->templateService->loadTemplate('renewal-success', $template_data);
        
        $this->sendEmail($user->user_email, $subject, $message);
    }

    public function sendRenewalFailedEmail(object $subscription, string $error): void {
        if (!$this->isEmailEnabled()) {
            return;
        }

        $user = get_user_by('id', $subscription->user_id);
        $product = get_post($subscription->product_id);
        
        /* translators: 1: product title */
        $subject = sprintf(__('Abonelik Ödeme Hatası - %1$s', 'iyzipay-woocommerce-subscription'), $product->post_title);
        
        $template_data = [
            'user_name' => $user->display_name,
            'product_name' => $product->post_title,
            'amount' => wc_price($subscription->amount),
            'error_message' => $error,
            'retry_date' => gmdate('d.m.Y', strtotime('+3 days')),
            'subscription_id' => $subscription->id,
            'account_url' => wc_get_account_endpoint_url('subscriptions'),
        ];
        
        $message = $this->templateService->loadTemplate('renewal-failed', $template_data);
        
        $this->sendEmail($user->user_email, $subject, $message);
        
        $this->sendAdminNotification('payment_failed', $subscription, $error);
    }

    public function sendCancellationEmail(object $subscription): void {
        if (!$this->isEmailEnabled()) {
            return;
        }

        $user = get_user_by('id', $subscription->user_id);
        $product = get_post($subscription->product_id);
        
        /* translators: 1: product title */
        $subject = sprintf(__('Aboneliğiniz İptal Edildi - %1$s', 'iyzipay-woocommerce-subscription'), $product->post_title);
        
        $template_data = [
            'user_name' => $user->display_name,
            'product_name' => $product->post_title,
            'cancellation_date' => gmdate('d.m.Y'),
            'subscription_id' => $subscription->id,
        ];
        
        $message = $this->templateService->loadTemplate('subscription-cancelled', $template_data);
        
        $this->sendEmail($user->user_email, $subject, $message);
    }

    public function sendSuspensionEmail(object $subscription): void {
        if (!$this->isEmailEnabled()) {
            return;
        }

        $user = get_user_by('id', $subscription->user_id);
        $product = get_post($subscription->product_id);
        
        /* translators: 1: product title */
        $subject = sprintf(__('Aboneliğiniz Askıya Alındı - %1$s', 'iyzipay-woocommerce-subscription'), $product->post_title);
        
        $template_data = [
            'user_name' => $user->display_name,
            'product_name' => $product->post_title,
            'suspension_date' => gmdate('d.m.Y'),
            'failed_payments' => $subscription->failed_payments,
            'subscription_id' => $subscription->id,
            'account_url' => wc_get_account_endpoint_url('subscriptions'),
        ];
        
        $message = $this->templateService->loadTemplate('subscription-suspended', $template_data);
        
        $this->sendEmail($user->user_email, $subject, $message);
    }

    public function sendExpiringEmail(object $subscription): void {
        if (!$this->isEmailEnabled()) {
            return;
        }

        $user = get_user_by('id', $subscription->user_id);
        $product = get_post($subscription->product_id);
        
        /* translators: 1: product title */
        $subject = sprintf(__('Aboneliğiniz Yakında Sona Eriyor - %1$s', 'iyzipay-woocommerce-subscription'), $product->post_title);
        
        $template_data = [
            'user_name' => $user->display_name,
            'product_name' => $product->post_title,
            'expiry_date' => gmdate('d.m.Y', strtotime($subscription->end_date)),
            'days_remaining' => $this->templateService->getDaysUntilExpiry($subscription->end_date),
            'subscription_id' => $subscription->id,
            'renewal_url' => wc_get_account_endpoint_url('subscriptions'),
        ];
        
        $message = $this->templateService->loadTemplate('subscription-expiring', $template_data);
        
        $this->sendEmail($user->user_email, $subject, $message);
    }

    public function sendAdminNotification(string $type, object $subscription, ?string $extra_data = null): void {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        switch ($type) {
            case 'new_subscription':
                $subject = sprintf(
                    /* translators: 1: site name */
                    __('[%1$s] Yeni Abonelik Oluşturuldu', 'iyzipay-woocommerce-subscription'),
                    $site_name
                );
                $message = sprintf(
                    /* translators: 1: subscription id, 2: customer name, 3: product name, 4: amount */
                    __('Yeni bir abonelik oluşturuldu:\n\nAbonelik ID: %1$d\nMüşteri: %2$s\nÜrün: %3$s\nTutar: %4$s\n\nYönetim panelinden detayları görüntüleyebilirsiniz.', 'iyzipay-woocommerce-subscription'),
                    $subscription->id,
                    get_user_by('id', $subscription->user_id)->display_name,
                    get_the_title($subscription->product_id),
                    wc_price($subscription->amount)
                );
                break;

            case 'payment_failed':
                $subject = sprintf(
                    /* translators: 1: site name */
                    __('[%1$s] Abonelik Ödeme Hatası', 'iyzipay-woocommerce-subscription'),
                    $site_name
                );
                $message = sprintf(
                    /* translators: 1: subscription id, 2: customer name, 3: product name, 4: error message */
                    __('Abonelik ödemesi başarısız oldu:\n\nAbonelik ID: %1$d\nMüşteri: %2$s\nÜrün: %3$s\nHata: %4$s\n\nLütfen kontrol edin.', 'iyzipay-woocommerce-subscription'),
                    $subscription->id,
                    get_user_by('id', $subscription->user_id)->display_name,
                    get_the_title($subscription->product_id),
                    $extra_data
                );
                break;
        }
        
        wp_mail($admin_email, $subject, $message);
    }

    public function checkExpiringSubscriptions(): void {
        global $wpdb;

        $subs_table = esc_sql($wpdb->prefix . 'iyzico_subscriptions');
        $notif_table = esc_sql($wpdb->prefix . 'iyzico_subscription_notifications');

        $sql = "SELECT * FROM `{$subs_table}`
             WHERE status = 'active'
             AND end_date IS NOT NULL
             AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
             AND id NOT IN (
                 SELECT subscription_id FROM `{$notif_table}`
                 WHERE notification_type = 'expiring'
                 AND sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
             )";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $expiring_subscriptions = $wpdb->get_results($sql);

        foreach ($expiring_subscriptions as $subscription) {
            $this->sendExpiringEmail($subscription);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert(
                $wpdb->prefix . 'iyzico_subscription_notifications',
                [
                    'subscription_id' => $subscription->id,
                    'notification_type' => 'expiring',
                    'sent_at' => current_time('mysql'),
                ],
                ['%d', '%s', '%s']
            );
        }
    }

    private function sendEmail(string $to, string $subject, string $message): void {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        wp_mail($to, $subject, $message, $headers);
    }

    private function isEmailEnabled(): bool {
        return get_option('iyzico_subscription_email_notifications', 1) == 1;
    }
} 
