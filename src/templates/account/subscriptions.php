<?php
/**
 * Müşteri Abonelik Sayfası Template
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="woocommerce-account-subscriptions">
    <h2><?php esc_html_e('Aboneliklerim', 'iyzipay-woocommerce-subscription'); ?></h2>
    
    <?php if (!empty($user_subscriptions)): ?>
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
            <thead>
                <tr>
                    <th class="subscription-id"><?php esc_html_e('Abonelik', 'iyzipay-woocommerce-subscription'); ?></th>
                    <th class="subscription-status"><?php esc_html_e('Durum', 'iyzipay-woocommerce-subscription'); ?></th>
                    <th class="subscription-next-payment"><?php esc_html_e('Sonraki Ödeme', 'iyzipay-woocommerce-subscription'); ?></th>
                    <th class="subscription-total"><?php esc_html_e('Toplam', 'iyzipay-woocommerce-subscription'); ?></th>
                    <th class="subscription-actions"><?php esc_html_e('İşlemler', 'iyzipay-woocommerce-subscription'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_subscriptions as $iyzico_sub): ?>
                    <tr class="subscription">
                        <td class="subscription-id" data-title="<?php echo esc_attr__('Abonelik', 'iyzipay-woocommerce-subscription'); ?>">
                            <a href="#subscription-<?php echo esc_attr($iyzico_sub->id); ?>" class="subscription-link">
                                #<?php echo esc_html($iyzico_sub->id); ?>
                            </a>
                            <br>
                            <small><?php echo esc_html(get_the_title($iyzico_sub->product_id)); ?></small>
                        </td>
                        <td class="subscription-status" data-title="<?php echo esc_attr__('Durum', 'iyzipay-woocommerce-subscription'); ?>">
                            <span class="status-<?php echo esc_attr($iyzico_sub->status); ?>">
                                <?php echo esc_html(iyzipay_woocommerce_subscription_status_label($iyzico_sub->status)); ?>
                            </span>
                        </td>
                        <td class="subscription-next-payment" data-title="<?php echo esc_attr__('Sonraki Ödeme', 'iyzipay-woocommerce-subscription'); ?>">
                            <?php if ($iyzico_sub->status === 'active'): ?>
                                <?php echo esc_html(gmdate('d.m.Y', strtotime($iyzico_sub->next_payment))); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="subscription-total" data-title="<?php echo esc_attr__('Toplam', 'iyzipay-woocommerce-subscription'); ?>">
                            <?php echo wp_kses_post(wc_price($iyzico_sub->amount)); ?>
                            <small>/ <?php echo esc_html(iyzipay_woocommerce_subscription_period_label($iyzico_sub->period)); ?></small>
                        </td>
                        <td class="subscription-actions" data-title="<?php echo esc_attr__('İşlemler', 'iyzipay-woocommerce-subscription'); ?>">
                            <?php if ($iyzico_sub->status === 'active'): ?>
                                <a href="#" class="button suspend-subscription" data-subscription-id="<?php echo esc_attr($iyzico_sub->id); ?>">
                                    <?php esc_html_e('Askıya Al', 'iyzipay-woocommerce-subscription'); ?>
                                </a>
                                <a href="#" class="button cancel-subscription" data-subscription-id="<?php echo esc_attr($iyzico_sub->id); ?>">
                                    <?php esc_html_e('İptal Et', 'iyzipay-woocommerce-subscription'); ?>
                                </a>
                            <?php elseif ($iyzico_sub->status === 'suspended'): ?>
                                <a href="#" class="button reactivate-subscription" data-subscription-id="<?php echo esc_attr($iyzico_sub->id); ?>">
                                    <?php esc_html_e('Yeniden Aktifleştir', 'iyzipay-woocommerce-subscription'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Abonelik Detayları -->
                    <tr id="subscription-<?php echo esc_attr($iyzico_sub->id); ?>" class="subscription-details" style="display: none;">
                        <td colspan="5">
                            <div class="subscription-detail-content">
                                <h4><?php esc_html_e('Abonelik Detayları', 'iyzipay-woocommerce-subscription'); ?></h4>
                                <div class="subscription-info">
                                    <div class="info-row">
                                        <strong><?php esc_html_e('Başlangıç Tarihi:', 'iyzipay-woocommerce-subscription'); ?></strong>
                                        <?php echo esc_html(gmdate('d.m.Y H:i', strtotime($iyzico_sub->start_date))); ?>
                                    </div>
                                    <div class="info-row">
                                        <strong><?php esc_html_e('Periyot:', 'iyzipay-woocommerce-subscription'); ?></strong>
                                        <?php echo esc_html(iyzipay_woocommerce_subscription_period_label($iyzico_sub->period)); ?>
                                    </div>
                                    <?php if ($iyzico_sub->billing_cycles > 0): ?>
                                        <div class="info-row">
                                            <strong><?php esc_html_e('Döngü:', 'iyzipay-woocommerce-subscription'); ?></strong>
                                            <?php echo esc_html($iyzico_sub->completed_cycles); ?> / <?php echo esc_html($iyzico_sub->billing_cycles); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($iyzico_sub->failed_payments > 0): ?>
                                        <div class="info-row">
                                            <strong><?php esc_html_e('Başarısız Ödemeler:', 'iyzipay-woocommerce-subscription'); ?></strong>
                                            <?php echo esc_html($iyzico_sub->failed_payments); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($iyzico_sub->end_date): ?>
                                        <div class="info-row">
                                            <strong><?php esc_html_e('Bitiş Tarihi:', 'iyzipay-woocommerce-subscription'); ?></strong>
                                            <?php echo esc_html(gmdate('d.m.Y H:i', strtotime($iyzico_sub->end_date))); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
            <?php esc_html_e('Henüz hiç aboneliğiniz bulunmamaktadır.', 'iyzipay-woocommerce-subscription'); ?>
        </div>
    <?php endif; ?>
</div>

<style>
.subscription-details {
    background-color: #f9f9f9;
}

.subscription-detail-content {
    padding: 20px;
}

.subscription-info .info-row {
    margin-bottom: 10px;
}

.status-active {
    color: #46b450;
    font-weight: bold;
}

.status-suspended {
    color: #ffb900;
    font-weight: bold;
}

.status-cancelled {
    color: #dc3232;
    font-weight: bold;
}

.status-pending {
    color: #00a0d2;
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Abonelik detaylarını göster/gizle
    $('.subscription-link').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $(target).toggle();
    });
    
    // Abonelik işlemleri
    $('.suspend-subscription, .cancel-subscription, .reactivate-subscription').click(function(e) {
        e.preventDefault();
        
        var action = '';
        var confirmMessage = '';
        
        if ($(this).hasClass('suspend-subscription')) {
            action = 'suspend';
            confirmMessage = '<?php echo esc_js(__('Aboneliği askıya almak istediğinizden emin misiniz?', 'iyzipay-woocommerce-subscription')); ?>';
        } else if ($(this).hasClass('cancel-subscription')) {
            action = 'cancel';
            confirmMessage = '<?php echo esc_js(__('Aboneliği iptal etmek istediğinizden emin misiniz? Bu işlem geri alınamaz.', 'iyzipay-woocommerce-subscription')); ?>';
        } else if ($(this).hasClass('reactivate-subscription')) {
            action = 'reactivate';
            confirmMessage = '<?php echo esc_js(__('Aboneliği yeniden aktifleştirmek istediğinizden emin misiniz?', 'iyzipay-woocommerce-subscription')); ?>';
        }
        
        if (confirm(confirmMessage)) {
            var subscriptionId = $(this).data('subscription-id');
            
            $.ajax({
                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                type: 'POST',
                data: {
                    action: 'iyzico_subscription_action',
                    subscription_id: subscriptionId,
                    subscription_action: action,
                    nonce: '<?php echo esc_attr(wp_create_nonce('iyzico_subscription_action')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php echo esc_js(__('Bir hata oluştu.', 'iyzipay-woocommerce-subscription')); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Bir hata oluştu.', 'iyzipay-woocommerce-subscription')); ?>');
                }
            });
        }
    });
});
</script>

<?php
// Helper fonksiyonları — global namespace çakışmasını önlemek için prefix'lendi.
if (!function_exists('iyzipay_woocommerce_subscription_status_label')) {
    function iyzipay_woocommerce_subscription_status_label(string $status): string {
        $labels = [
            'active' => __('Aktif', 'iyzipay-woocommerce-subscription'),
            'pending' => __('Bekliyor', 'iyzipay-woocommerce-subscription'),
            'cancelled' => __('İptal', 'iyzipay-woocommerce-subscription'),
            'suspended' => __('Askıda', 'iyzipay-woocommerce-subscription'),
            'completed' => __('Tamamlandı', 'iyzipay-woocommerce-subscription'),
        ];
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}

if (!function_exists('iyzipay_woocommerce_subscription_period_label')) {
    function iyzipay_woocommerce_subscription_period_label(string $period): string {
        $labels = [
            'day' => __('Günlük', 'iyzipay-woocommerce-subscription'),
            'week' => __('Haftalık', 'iyzipay-woocommerce-subscription'),
            'month' => __('Aylık', 'iyzipay-woocommerce-subscription'),
            'year' => __('Yıllık', 'iyzipay-woocommerce-subscription'),
        ];
        return isset($labels[$period]) ? $labels[$period] : $period;
    }
}
?>