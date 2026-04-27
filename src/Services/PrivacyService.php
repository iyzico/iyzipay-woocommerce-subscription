<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SavedCardRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;

/**
 * KVKK / GDPR uyumluluğu için kullanıcı verisi dışa aktarma ve silme.
 *
 * WordPress'in `personal_data_exporters` ve `personal_data_erasers` filtrelerine bağlanır.
 */
class PrivacyService
{
    private SubscriptionRepositoryInterface $subscriptionRepository;
    private SavedCardRepositoryInterface $savedCardRepository;

    public function __construct(
        SubscriptionRepositoryInterface $subscriptionRepository,
        SavedCardRepositoryInterface $savedCardRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->savedCardRepository = $savedCardRepository;
    }

    public function register(): void
    {
        add_filter('wp_privacy_personal_data_exporters', [$this, 'registerExporter']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'registerEraser']);
    }

    public function registerExporter(array $exporters): array
    {
        $exporters['iyzipay-woocommerce-subscription'] = [
            'exporter_friendly_name' => __('iyzico Abonelikleri', 'iyzipay-woocommerce-subscription'),
            'callback' => [$this, 'exportData'],
        ];
        return $exporters;
    }

    public function registerEraser(array $erasers): array
    {
        $erasers['iyzipay-woocommerce-subscription'] = [
            'eraser_friendly_name' => __('iyzico Abonelik Verileri', 'iyzipay-woocommerce-subscription'),
            'callback' => [$this, 'eraseData'],
        ];
        return $erasers;
    }

    public function exportData(string $email_address, int $page = 1): array
    {
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return ['data' => [], 'done' => true];
        }

        $subscriptions = $this->subscriptionRepository->findByUser((int) $user->ID);
        $export_items = [];

        foreach ((array) $subscriptions as $subscription) {
            $export_items[] = [
                'group_id' => 'iyzico-subscriptions',
                'group_label' => __('iyzico Abonelikleri', 'iyzipay-woocommerce-subscription'),
                'item_id' => 'subscription-' . $subscription->id,
                'data' => [
                    ['name' => __('Abonelik No', 'iyzipay-woocommerce-subscription'), 'value' => $subscription->id],
                    ['name' => __('Durum', 'iyzipay-woocommerce-subscription'), 'value' => $subscription->status],
                    ['name' => __('Tutar', 'iyzipay-woocommerce-subscription'), 'value' => $subscription->amount . ' ' . $subscription->currency],
                    ['name' => __('Periyot', 'iyzipay-woocommerce-subscription'), 'value' => $subscription->period],
                    ['name' => __('Başlangıç Tarihi', 'iyzipay-woocommerce-subscription'), 'value' => $subscription->start_date],
                    ['name' => __('Sonraki Ödeme', 'iyzipay-woocommerce-subscription'), 'value' => $subscription->next_payment],
                ],
            ];
        }

        return [
            'data' => $export_items,
            'done' => true,
        ];
    }

    public function eraseData(string $email_address, int $page = 1): array
    {
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true,
            ];
        }

        $user_id = (int) $user->ID;
        $items_removed = 0;
        $items_retained = 0;
        $messages = [];

        $subscriptions = $this->subscriptionRepository->findByUser($user_id);

        foreach ((array) $subscriptions as $subscription) {
            if (in_array($subscription->status, ['active', 'pending', 'suspended'], true)) {
                $this->subscriptionRepository->cancel((int) $subscription->id);
                $messages[] = sprintf(
                    /* translators: %d: subscription ID */
                    __('Abonelik #%d silinme talebi nedeniyle iptal edildi.', 'iyzipay-woocommerce-subscription'),
                    (int) $subscription->id
                );
                $items_retained++;
            } else {
                $items_removed++;
            }
        }

        $this->savedCardRepository->purgeForUser($user_id);

        delete_user_meta($user_id, '_iyzico_card_user_key');
        delete_user_meta($user_id, '_iyzico_card_token');
        delete_user_meta($user_id, '_iyzico_identity_number');
        delete_user_meta($user_id, '_iyzico_last_ip');

        return [
            'items_removed' => $items_removed > 0,
            'items_retained' => $items_retained > 0,
            'messages' => $messages,
            'done' => true,
        ];
    }
}
