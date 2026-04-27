<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Admin;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SubscriptionListTable extends \WP_List_Table
{
    private SubscriptionRepositoryInterface $repository;

    public function __construct(SubscriptionRepositoryInterface $repository)
    {
        parent::__construct([
            'singular' => 'iyzico_subscription',
            'plural'   => 'iyzico_subscriptions',
            'ajax'     => false,
        ]);
        $this->repository = $repository;
    }

    public function get_columns(): array
    {
        return [
            'cb'           => '<input type="checkbox" />',
            'id'           => __('No', 'iyzipay-woocommerce-subscription'),
            'customer'     => __('Müşteri', 'iyzipay-woocommerce-subscription'),
            'product'      => __('Ürün', 'iyzipay-woocommerce-subscription'),
            'status'       => __('Durum', 'iyzipay-woocommerce-subscription'),
            'amount'       => __('Tutar', 'iyzipay-woocommerce-subscription'),
            'period'       => __('Periyot', 'iyzipay-woocommerce-subscription'),
            'start_date'   => __('Başlangıç', 'iyzipay-woocommerce-subscription'),
            'next_payment' => __('Sonraki Ödeme', 'iyzipay-woocommerce-subscription'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'id'           => ['id', true],
            'status'       => ['status', false],
            'amount'       => ['amount', false],
            'start_date'   => ['start_date', false],
            'next_payment' => ['next_payment', false],
        ];
    }

    public function get_bulk_actions(): array
    {
        return [
            'suspend'    => __('Askıya Al', 'iyzipay-woocommerce-subscription'),
            'cancel'     => __('İptal Et', 'iyzipay-woocommerce-subscription'),
            'reactivate' => __('Yeniden Aktifleştir', 'iyzipay-woocommerce-subscription'),
        ];
    }

    protected function get_default_primary_column_name(): string
    {
        return 'customer';
    }

    public function prepare_items(): void
    {
        $per_page = $this->get_items_per_page('iyzico_subscriptions_per_page', 20);
        $current_page = $this->get_pagenum();

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $filters = [
            'status'           => isset($_REQUEST['status']) ? sanitize_text_field(wp_unslash($_REQUEST['status'])) : '',
            'customer_search'  => isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '',
            'date_from'        => isset($_REQUEST['date_from']) ? sanitize_text_field(wp_unslash($_REQUEST['date_from'])) : '',
            'date_to'          => isset($_REQUEST['date_to']) ? sanitize_text_field(wp_unslash($_REQUEST['date_to'])) : '',
            'orderby'          => isset($_REQUEST['orderby']) ? sanitize_text_field(wp_unslash($_REQUEST['orderby'])) : 'created_at',
            'order'            => isset($_REQUEST['order']) ? sanitize_text_field(wp_unslash($_REQUEST['order'])) : 'DESC',
            'per_page'         => $per_page,
            'paged'            => $current_page,
        ];
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $total_items = $this->repository->countByFilters($filters);
        $items = $this->repository->findByFilters($filters);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->items = $items;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total_items / max(1, $per_page)),
        ]);
    }

    protected function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="subscription_ids[]" value="%d" />', (int) $item->id);
    }

    protected function column_id($item): string
    {
        $detail_url = $this->getDetailUrl((int) $item->id);
        return '<strong><a href="' . esc_url($detail_url) . '">#' . (int) $item->id . '</a></strong>';
    }

    protected function column_customer($item): string
    {
        $detail_url = $this->getDetailUrl((int) $item->id);
        $user = get_userdata((int) $item->user_id);

        if (!$user) {
            $title = '<a href="' . esc_url($detail_url) . '"><em>' . esc_html__('Misafir / Silinmiş kullanıcı', 'iyzipay-woocommerce-subscription') . '</em></a>';
        } else {
            $title = sprintf(
                '<a href="%s"><strong>%s</strong></a><br><span class="description">%s</span>',
                esc_url($detail_url),
                esc_html($user->display_name),
                esc_html($user->user_email)
            );
        }

        return $title . $this->row_actions($this->buildRowActions($item));
    }

    private function buildRowActions($item): array
    {
        $actions = [];
        $detail_url = $this->getDetailUrl((int) $item->id);

        $actions['view'] = '<a href="' . esc_url($detail_url) . '">' . esc_html__('Görüntüle', 'iyzipay-woocommerce-subscription') . '</a>';

        if ($item->status === 'active') {
            $actions['suspend'] = sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($this->buildActionUrl('suspend', (int) $item->id)),
                esc_js(__('Bu aboneliği askıya almak istediğinize emin misiniz?', 'iyzipay-woocommerce-subscription')),
                esc_html__('Askıya Al', 'iyzipay-woocommerce-subscription')
            );
            $actions['cancel'] = sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($this->buildActionUrl('cancel', (int) $item->id)),
                esc_js(__('Bu aboneliği iptal etmek istediğinize emin misiniz? Bu işlem geri alınamaz.', 'iyzipay-woocommerce-subscription')),
                esc_html__('İptal Et', 'iyzipay-woocommerce-subscription')
            );
        } elseif ($item->status === 'suspended') {
            $actions['reactivate'] = sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($this->buildActionUrl('reactivate', (int) $item->id)),
                esc_js(__('Bu aboneliği yeniden aktifleştirmek istediğinize emin misiniz?', 'iyzipay-woocommerce-subscription')),
                esc_html__('Yeniden Aktifleştir', 'iyzipay-woocommerce-subscription')
            );
        }

        if (in_array($item->status, ['active', 'suspended'], true)) {
            $actions['trigger'] = sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($this->buildActionUrl('trigger_renewal', (int) $item->id)),
                esc_js(__('Bu abonelik için manuel yenileme ödemesi alınsın mı?', 'iyzipay-woocommerce-subscription')),
                esc_html__('Ödeme Tetikle', 'iyzipay-woocommerce-subscription')
            );
        }

        return $actions;
    }

    private function getDetailUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'iyzico-subscriptions',
                'view' => 'subscription',
                'id'   => $id,
            ],
            admin_url('admin.php')
        );
    }

    protected function column_product($item): string
    {
        if (!function_exists('wc_get_product')) {
            return '#' . (int) $item->product_id;
        }

        $product = wc_get_product((int) $item->product_id);
        if (!$product) {
            /* translators: %d: product id */
            return sprintf(esc_html__('(Silinmiş ürün #%d)', 'iyzipay-woocommerce-subscription'), (int) $item->product_id);
        }

        $url = get_edit_post_link((int) $item->product_id);
        return '<a href="' . esc_url($url) . '">' . esc_html($product->get_name()) . '</a>';
    }

    protected function column_status($item): string
    {
        $labels = [
            'active'    => __('Aktif', 'iyzipay-woocommerce-subscription'),
            'pending'   => __('Bekliyor', 'iyzipay-woocommerce-subscription'),
            'suspended' => __('Askıda', 'iyzipay-woocommerce-subscription'),
            'cancelled' => __('İptal', 'iyzipay-woocommerce-subscription'),
            'completed' => __('Tamamlandı', 'iyzipay-woocommerce-subscription'),
            'expired'   => __('Süresi Doldu', 'iyzipay-woocommerce-subscription'),
        ];
        $label = $labels[$item->status] ?? $item->status;
        $class = 'iyzico-status iyzico-status-' . sanitize_html_class($item->status);
        return '<mark class="' . esc_attr($class) . '"><span>' . esc_html($label) . '</span></mark>';
    }

    protected function column_amount($item): string
    {
        if (!function_exists('wc_price')) {
            return number_format_i18n((float) $item->amount, 2) . ' ' . esc_html($item->currency);
        }
        return wp_kses_post(wc_price((float) $item->amount, ['currency' => $item->currency]));
    }

    protected function column_period($item): string
    {
        $labels = [
            'day'   => __('Günlük', 'iyzipay-woocommerce-subscription'),
            'week'  => __('Haftalık', 'iyzipay-woocommerce-subscription'),
            'month' => __('Aylık', 'iyzipay-woocommerce-subscription'),
            'year'  => __('Yıllık', 'iyzipay-woocommerce-subscription'),
        ];
        return esc_html($labels[$item->period] ?? $item->period);
    }

    protected function column_start_date($item): string
    {
        if (empty($item->start_date)) {
            return '&mdash;';
        }
        return esc_html(date_i18n(get_option('date_format'), strtotime($item->start_date)));
    }

    protected function column_next_payment($item): string
    {
        if (empty($item->next_payment) || !in_array($item->status, ['active', 'pending', 'suspended'], true)) {
            return '&mdash;';
        }
        $timestamp = strtotime($item->next_payment);
        $is_overdue = $timestamp < time();

        $output = esc_html(date_i18n(get_option('date_format'), $timestamp));
        if ($is_overdue && $item->status === 'active') {
            $output .= ' <span style="color:#d63638;">(' . esc_html__('Gecikmiş', 'iyzipay-woocommerce-subscription') . ')</span>';
        }
        return $output;
    }

    protected function column_default($item, $column_name)
    {
        return isset($item->{$column_name}) ? esc_html($item->{$column_name}) : '';
    }

    public function no_items(): void
    {
        esc_html_e('Henüz abonelik bulunmamaktadır.', 'iyzipay-woocommerce-subscription');
    }

    protected function extra_tablenav($which): void
    {
        if ($which !== 'top') {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $current_status = isset($_REQUEST['status']) ? sanitize_text_field(wp_unslash($_REQUEST['status'])) : '';
        $date_from = isset($_REQUEST['date_from']) ? sanitize_text_field(wp_unslash($_REQUEST['date_from'])) : '';
        $date_to = isset($_REQUEST['date_to']) ? sanitize_text_field(wp_unslash($_REQUEST['date_to'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $statuses = [
            ''          => __('Tüm Durumlar', 'iyzipay-woocommerce-subscription'),
            'active'    => __('Aktif', 'iyzipay-woocommerce-subscription'),
            'pending'   => __('Bekliyor', 'iyzipay-woocommerce-subscription'),
            'suspended' => __('Askıda', 'iyzipay-woocommerce-subscription'),
            'cancelled' => __('İptal', 'iyzipay-woocommerce-subscription'),
            'completed' => __('Tamamlandı', 'iyzipay-woocommerce-subscription'),
            'expired'   => __('Süresi Doldu', 'iyzipay-woocommerce-subscription'),
        ];

        echo '<div class="alignleft actions">';
        echo '<label for="iyzico-filter-status" class="screen-reader-text">' . esc_html__('Duruma göre filtrele', 'iyzipay-woocommerce-subscription') . '</label>';
        echo '<select name="status" id="iyzico-filter-status">';
        foreach ($statuses as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current_status, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';

        echo '<input type="date" name="date_from" value="' . esc_attr($date_from) . '" placeholder="' . esc_attr__('Başlangıç', 'iyzipay-woocommerce-subscription') . '" />';
        echo '<input type="date" name="date_to" value="' . esc_attr($date_to) . '" placeholder="' . esc_attr__('Bitiş', 'iyzipay-woocommerce-subscription') . '" />';

        submit_button(__('Filtrele', 'iyzipay-woocommerce-subscription'), '', 'filter_action', false);
        echo '</div>';
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
}
