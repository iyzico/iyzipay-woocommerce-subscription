<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Admin\Views;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Admin\SubscriptionListTable;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Services\SubscriptionAdminService;

/**
 * Native WordPress admin views.
 *
 * - List: WP_List_Table ile native tablo
 * - Detail: postbox layout (sol: bilgi, sağ: aksiyon kutusu)
 *
 * Tüm asset'ler WP core'dan gelir; özel JS yalnızca confirm dialog'ları için.
 */
class SubscriptionAdminView
{
    private SubscriptionAdminService $service;
    private SubscriptionRepositoryInterface $repository;
    private ?SubscriptionListTable $list_table = null;

    public function __construct(SubscriptionAdminService $service, SubscriptionRepositoryInterface $repository)
    {
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * `screen_options` için screen oluştuğunda kayıt edilmeli.
     * Per-page ayarı tanımlar.
     */
    public function register_screen_options(): void
    {
        add_screen_option('per_page', [
            'label'   => __('Sayfa başına abonelik', 'iyzipay-woocommerce-subscription'),
            'default' => 20,
            'option'  => 'iyzico_subscriptions_per_page',
        ]);

        $this->list_table = new SubscriptionListTable($this->repository);
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ('woocommerce_page_iyzico-subscriptions' !== $hook) {
            return;
        }

        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            'iyzico-subscription-admin',
            plugin_dir_url(__FILE__) . '../../../assets/css/admin.css',
            [],
            '1.1.0'
        );

        wp_enqueue_script('postbox');
    }

    /**
     * Ana sayfa — `do` parametresine göre list/detail render eder.
     */
    public function render_subscriptions_page(): void
    {
        // Yetki SubscriptionAdminController içinde kontrol edilir.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'list';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($view === 'subscription' && !empty($_GET['id'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $this->render_detail_page((int) $_GET['id']);
            return;
        }

        $this->render_list_page();
    }

    /**
     * Liste sayfası — istatistik kutuları + filtre formu + WP_List_Table.
     */
    private function render_list_page(): void
    {
        $stats = $this->repository->getSubscriptionStats();

        if (!$this->list_table) {
            $this->list_table = new SubscriptionListTable($this->repository);
        }
        $this->list_table->prepare_items();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('iyzico Abonelikler', 'iyzipay-woocommerce-subscription'); ?></h1>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" class="page-title-action">
                <?php esc_html_e('Yeni Abonelik Ürünü', 'iyzipay-woocommerce-subscription'); ?>
            </a>
            <hr class="wp-header-end" />

            <?php settings_errors('iyzico_subscription'); ?>

            <?php $this->render_stats_cards($stats); ?>

            <form method="get">
                <input type="hidden" name="page" value="iyzico-subscriptions" />
                <?php
                $this->list_table->search_box(__('Müşteri Ara', 'iyzipay-woocommerce-subscription'), 'iyzico-subscription-search');
                $this->list_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    private function render_stats_cards(?object $stats): void
    {
        if (!$stats) {
            return;
        }

        $cards = [
            [
                'label' => __('Toplam', 'iyzipay-woocommerce-subscription'),
                'value' => (int) ($stats->total ?? 0),
                'color' => '',
            ],
            [
                'label' => __('Aktif', 'iyzipay-woocommerce-subscription'),
                'value' => (int) ($stats->active ?? 0),
                'color' => '#46b450',
            ],
            [
                'label' => __('Askıda', 'iyzipay-woocommerce-subscription'),
                'value' => (int) ($stats->suspended ?? 0),
                'color' => '#ffb900',
            ],
            [
                'label' => __('İptal', 'iyzipay-woocommerce-subscription'),
                'value' => (int) ($stats->cancelled ?? 0),
                'color' => '#dc3232',
            ],
            [
                'label' => __('Aylık Gelir', 'iyzipay-woocommerce-subscription'),
                'value' => function_exists('wc_price') ? wc_price((float) ($stats->monthly_revenue ?? 0)) : (string) ($stats->monthly_revenue ?? 0),
                'color' => '#0073aa',
                'is_html' => true,
            ],
        ];

        echo '<div class="iyzico-stats-grid">';
        foreach ($cards as $card) {
            echo '<div class="iyzico-stat-card">';
            echo '<div class="iyzico-stat-label">' . esc_html($card['label']) . '</div>';
            echo '<div class="iyzico-stat-value" style="color:' . esc_attr($card['color']) . ';">';
            echo !empty($card['is_html']) ? wp_kses_post($card['value']) : esc_html((string) $card['value']);
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Detail sayfası — postbox layout.
     */
    private function render_detail_page(int $subscription_id): void
    {
        $subscription = $this->service->getSubscriptionById($subscription_id);

        if (!$subscription) {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Abonelik Bulunamadı', 'iyzipay-woocommerce-subscription'); ?></h1>
                <div class="notice notice-error"><p><?php esc_html_e('Aradığınız abonelik bulunamadı veya silinmiş olabilir.', 'iyzipay-woocommerce-subscription'); ?></p></div>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=iyzico-subscriptions')); ?>" class="button">&larr; <?php esc_html_e('Listeye Dön', 'iyzipay-woocommerce-subscription'); ?></a></p>
            </div>
            <?php
            return;
        }

        $list_url = admin_url('admin.php?page=iyzico-subscriptions');
        $user = get_userdata((int) $subscription->user_id);
        $product = function_exists('wc_get_product') ? wc_get_product((int) $subscription->product_id) : null;
        $order = function_exists('wc_get_order') ? wc_get_order((int) $subscription->order_id) : null;
        $payments = $this->getPaymentHistory($subscription_id);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php
                /* translators: %d: subscription id */
                printf(esc_html__('Abonelik #%d', 'iyzipay-woocommerce-subscription'), (int) $subscription->id);
                ?>
            </h1>
            <a href="<?php echo esc_url($list_url); ?>" class="page-title-action">&larr; <?php esc_html_e('Listeye Dön', 'iyzipay-woocommerce-subscription'); ?></a>
            <hr class="wp-header-end" />

            <?php settings_errors('iyzico_subscription'); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <?php $this->render_summary_box($subscription, $user, $product, $order); ?>
                        <?php $this->render_payments_box($payments); ?>
                    </div>

                    <div id="postbox-container-1" class="postbox-container">
                        <?php $this->render_status_box($subscription); ?>
                        <?php $this->render_actions_box($subscription); ?>
                    </div>
                </div>
                <br class="clear" />
            </div>
        </div>
        <?php
    }

    private function render_summary_box($subscription, $user, $product, $order): void
    {
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Abonelik Bilgileri', 'iyzipay-woocommerce-subscription'); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Müşteri', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td>
                                <?php if ($user) : ?>
                                    <a href="<?php echo esc_url(get_edit_user_link((int) $user->ID)); ?>"><?php echo esc_html($user->display_name); ?></a>
                                    &lt;<?php echo esc_html($user->user_email); ?>&gt;
                                <?php else : ?>
                                    <em><?php esc_html_e('Misafir / Silinmiş kullanıcı', 'iyzipay-woocommerce-subscription'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Ürün', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td>
                                <?php if ($product) : ?>
                                    <a href="<?php echo esc_url(get_edit_post_link((int) $product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a>
                                <?php else : ?>
                                    <em><?php
                                    /* translators: %d: product id */
                                    printf(esc_html__('Silinmiş ürün #%d', 'iyzipay-woocommerce-subscription'), (int) $subscription->product_id);
                                    ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Bağlı Sipariş', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td>
                                <?php if ($order) : ?>
                                    <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">#<?php echo (int) $order->get_id(); ?></a>
                                <?php else : ?>
                                    <em><?php esc_html_e('Sipariş bulunamadı', 'iyzipay-woocommerce-subscription'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Tutar', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td>
                                <?php
                                if (function_exists('wc_price')) {
                                    echo wp_kses_post(wc_price((float) $subscription->amount, ['currency' => $subscription->currency]));
                                } else {
                                    echo esc_html(number_format_i18n((float) $subscription->amount, 2) . ' ' . $subscription->currency);
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Periyot', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td><?php echo esc_html($this->getPeriodLabel($subscription->period)); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Başlangıç Tarihi', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td>
                                <?php
                                $start = strtotime($subscription->start_date);
                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $start));
                                ?>
                            </td>
                        </tr>
                        <?php if (!empty($subscription->next_payment)) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Sonraki Ödeme', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td>
                                <?php
                                $next = strtotime($subscription->next_payment);
                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next));
                                if ($next < time() && $subscription->status === 'active') {
                                    echo ' <span style="color:#d63638;">(' . esc_html__('Gecikmiş', 'iyzipay-woocommerce-subscription') . ')</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($subscription->trial_end_date)) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Deneme Bitişi', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription->trial_end_date))); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((int) ($subscription->billing_cycles ?? 0) > 0) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Fatura Döngüsü', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td>
                                <?php
                                $completed = (int) ($subscription->completed_cycles ?? 0);
                                $total = (int) $subscription->billing_cycles;
                                echo (int) $completed . ' / ' . (int) $total;
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((int) ($subscription->failed_payments ?? 0) > 0) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Başarısız Ödeme', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td><span style="color:#d63638;"><?php echo (int) $subscription->failed_payments; ?></span></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Oluşturulma', 'iyzipay-woocommerce-subscription'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($subscription->created_at))); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private function render_payments_box(array $payments): void
    {
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Ödeme Geçmişi', 'iyzipay-woocommerce-subscription'); ?></span></h2>
            <div class="inside">
                <?php if (empty($payments)) : ?>
                    <p><em><?php esc_html_e('Henüz ödeme kaydı bulunmuyor.', 'iyzipay-woocommerce-subscription'); ?></em></p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Tarih', 'iyzipay-woocommerce-subscription'); ?></th>
                                <th><?php esc_html_e('Tutar', 'iyzipay-woocommerce-subscription'); ?></th>
                                <th><?php esc_html_e('Durum', 'iyzipay-woocommerce-subscription'); ?></th>
                                <th><?php esc_html_e('iyzico Ödeme ID', 'iyzipay-woocommerce-subscription'); ?></th>
                                <th><?php esc_html_e('Hata', 'iyzipay-woocommerce-subscription'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment) : ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($payment->created_at))); ?></td>
                                    <td>
                                        <?php
                                        if (function_exists('wc_price')) {
                                            echo wp_kses_post(wc_price((float) $payment->amount, ['currency' => $payment->currency]));
                                        } else {
                                            echo esc_html(number_format_i18n((float) $payment->amount, 2) . ' ' . $payment->currency);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($payment->status === 'success') : ?>
                                            <mark class="iyzico-status iyzico-status-active"><span><?php esc_html_e('Başarılı', 'iyzipay-woocommerce-subscription'); ?></span></mark>
                                        <?php else : ?>
                                            <mark class="iyzico-status iyzico-status-cancelled"><span><?php esc_html_e('Başarısız', 'iyzipay-woocommerce-subscription'); ?></span></mark>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $payment->iyzico_payment_id ? '<code>' . esc_html($payment->iyzico_payment_id) . '</code>' : '&mdash;'; ?></td>
                                    <td><?php echo $payment->error_message ? '<small>' . esc_html($payment->error_message) . '</small>' : '&mdash;'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_status_box($subscription): void
    {
        $status_labels = [
            'active'    => __('Aktif', 'iyzipay-woocommerce-subscription'),
            'pending'   => __('Bekliyor', 'iyzipay-woocommerce-subscription'),
            'suspended' => __('Askıda', 'iyzipay-woocommerce-subscription'),
            'cancelled' => __('İptal', 'iyzipay-woocommerce-subscription'),
            'completed' => __('Tamamlandı', 'iyzipay-woocommerce-subscription'),
            'expired'   => __('Süresi Doldu', 'iyzipay-woocommerce-subscription'),
        ];
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Durum', 'iyzipay-woocommerce-subscription'); ?></span></h2>
            <div class="inside">
                <p style="font-size:18px;">
                    <mark class="iyzico-status iyzico-status-<?php echo esc_attr($subscription->status); ?>">
                        <span><?php echo esc_html($status_labels[$subscription->status] ?? $subscription->status); ?></span>
                    </mark>
                </p>
                <p>
                    <small><?php esc_html_e('Son güncelleme:', 'iyzipay-woocommerce-subscription'); ?>
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($subscription->updated_at))); ?></small>
                </p>
            </div>
        </div>
        <?php
    }

    private function render_actions_box($subscription): void
    {
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('İşlemler', 'iyzipay-woocommerce-subscription'); ?></span></h2>
            <div class="inside">
                <?php if ($subscription->status === 'active') : ?>
                    <p>
                        <a href="<?php echo esc_url($this->buildActionUrl('suspend', (int) $subscription->id)); ?>"
                           class="button button-secondary"
                           onclick="return confirm('<?php echo esc_js(__('Bu aboneliği askıya almak istediğinize emin misiniz?', 'iyzipay-woocommerce-subscription')); ?>');">
                            <?php esc_html_e('Askıya Al', 'iyzipay-woocommerce-subscription'); ?>
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo esc_url($this->buildActionUrl('cancel', (int) $subscription->id)); ?>"
                           class="button button-link-delete"
                           onclick="return confirm('<?php echo esc_js(__('Bu aboneliği iptal etmek istediğinize emin misiniz? Bu işlem geri alınamaz.', 'iyzipay-woocommerce-subscription')); ?>');">
                            <?php esc_html_e('İptal Et', 'iyzipay-woocommerce-subscription'); ?>
                        </a>
                    </p>
                <?php elseif ($subscription->status === 'suspended') : ?>
                    <p>
                        <a href="<?php echo esc_url($this->buildActionUrl('reactivate', (int) $subscription->id)); ?>"
                           class="button button-primary"
                           onclick="return confirm('<?php echo esc_js(__('Bu aboneliği yeniden aktifleştirmek istediğinize emin misiniz?', 'iyzipay-woocommerce-subscription')); ?>');">
                            <?php esc_html_e('Yeniden Aktifleştir', 'iyzipay-woocommerce-subscription'); ?>
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo esc_url($this->buildActionUrl('cancel', (int) $subscription->id)); ?>"
                           class="button button-link-delete"
                           onclick="return confirm('<?php echo esc_js(__('Bu aboneliği iptal etmek istediğinize emin misiniz?', 'iyzipay-woocommerce-subscription')); ?>');">
                            <?php esc_html_e('İptal Et', 'iyzipay-woocommerce-subscription'); ?>
                        </a>
                    </p>
                <?php elseif (in_array($subscription->status, ['cancelled', 'completed', 'expired'], true)) : ?>
                    <p><em><?php esc_html_e('Bu abonelik üzerinde işlem yapılamaz.', 'iyzipay-woocommerce-subscription'); ?></em></p>
                <?php endif; ?>

                <?php if (in_array($subscription->status, ['active', 'suspended'], true)) : ?>
                    <hr />
                    <p>
                        <a href="<?php echo esc_url($this->buildActionUrl('trigger_renewal', (int) $subscription->id)); ?>"
                           class="button"
                           onclick="return confirm('<?php echo esc_js(__('Bu abonelik için manuel yenileme ödemesi alınsın mı?', 'iyzipay-woocommerce-subscription')); ?>');">
                            <?php esc_html_e('Manuel Ödeme Tetikle', 'iyzipay-woocommerce-subscription'); ?>
                        </a>
                    </p>
                    <p class="description"><?php esc_html_e('iyzico üzerinden bir defaya mahsus tahsilat denemesi yapar. Sonraki ödeme tarihini ileri atar.', 'iyzipay-woocommerce-subscription'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function buildActionUrl(string $action, int $id): string
    {
        return wp_nonce_url(
            add_query_arg(
                [
                    'page' => 'iyzico-subscriptions',
                    'do'   => $action,
                    'id'   => $id,
                ],
                admin_url('admin.php')
            ),
            'iyzico_subscription_action_' . $action . '_' . $id
        );
    }

    private function getPeriodLabel(string $period): string
    {
        $labels = [
            'day'   => __('Günlük', 'iyzipay-woocommerce-subscription'),
            'week'  => __('Haftalık', 'iyzipay-woocommerce-subscription'),
            'month' => __('Aylık', 'iyzipay-woocommerce-subscription'),
            'year'  => __('Yıllık', 'iyzipay-woocommerce-subscription'),
        ];
        return $labels[$period] ?? $period;
    }

    private function getPaymentHistory(int $subscription_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'iyzico_subscription_payments';
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if (!$exists) {
            // phpcs:enable
            return [];
        }
        $table_safe = esc_sql($table);
        $results = (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$table_safe}` WHERE subscription_id = %d ORDER BY created_at DESC LIMIT 50",
                $subscription_id
            )
        );
        // phpcs:enable
        return $results;
    }
}
