<?php

/**
 * Subscription repository — wpdb erişimleri custom tablo için.
 *
 * Custom tablo (`{$wpdb->prefix}iyzico_subscriptions`) için yüksek seviyeli WP
 * API yoktur, dolayısıyla doğrudan $wpdb kullanmak kaçınılmazdır. Tablo adı
 * sabit olarak konstrüktörde set edilir; tüm değer parametreleri $wpdb->prepare()
 * placeholder'larıyla geçirilir → SQL injection riski yok.
 *
 * Plugin Check / WPCS şu uyarıları false-positive olarak işaretler:
 * - DirectDatabaseQuery.DirectQuery   → custom tablo kullanımı meşru
 * - DirectDatabaseQuery.NoCaching     → veri canlı (renewal/cron), cache uygunsuz
 * - PreparedSQL.NotPrepared           → parser, $sql değişkeninin prepare()
 *                                       sonucu olduğunu izleyemiyor
 * - PreparedSQL.InterpolatedNotPrepared → tablo adı `esc_sql()` ile escape edilmiş
 *                                          olarak interpolate edilir
 *
 * Bu sınıfta tek seferlik dosya seviyesinde bastırma kullanılır.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter

namespace Iyzico\IyzipayWoocommerceSubscription\Models;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionValidatorInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionCalculatorInterface;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    private $wpdb;
    private string $table_name;
    private $validator;
    private $calculator;

    public function __construct(
        SubscriptionValidatorInterface $validator,
        SubscriptionCalculatorInterface $calculator
    ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'iyzico_subscriptions';
        $this->validator = $validator;
        $this->calculator = $calculator;
    }

    public function create(array $data): ?int
    {
        $validated_data = $this->validator->validateCreateData($data);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $this->wpdb->insert(
            $this->table_name,
            [
                'user_id' => $validated_data['user_id'],
                'order_id' => $validated_data['order_id'],
                'product_id' => $validated_data['product_id'],
                'iyzico_subscription_id' => $validated_data['iyzico_subscription_id'],
                'status' => $validated_data['status'] ?? 'pending',
                'amount' => $validated_data['amount'],
                'currency' => $validated_data['currency'] ?? 'TRY',
                'period' => $validated_data['period'],
                'period_interval' => $validated_data['period_interval'] ?? 1,
                'start_date' => $validated_data['start_date'],
                'next_payment' => $validated_data['next_payment'],
                'end_date' => $validated_data['end_date'] ?? null,
                'trial_end_date' => $validated_data['trial_end_date'] ?? null,
                'payment_method' => $validated_data['payment_method'],
                'billing_cycles' => $validated_data['billing_cycles'] ?? 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            [
                '%d', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%d',
                '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'
            ]
        );

        if ($result) {
            return $this->wpdb->insert_id;
        }
        return null;
    }

    public function update(int $id, array $data): bool
    {
        $validated_data = $this->validator->validateUpdateData($data);
        $validated_data['updated_at'] = current_time('mysql');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $this->wpdb->update(
            $this->table_name,
            $validated_data,
            ['id' => $id],
            null,
            ['%d']
        );

        if ($result === false && defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('iyzipay-woocommerce-subscription update error: ' . $this->wpdb->last_error);
        }

        return $result !== false;
    }

    public function find(int $id): ?object
    {
        $table = esc_sql($this->table_name);
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = $this->wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $id);
        $result = $this->wpdb->get_row($sql);
        // phpcs:enable

        return $result ?: null;
    }

    public function findByUser(int $user_id): array
    {
        $table = esc_sql($this->table_name);
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = $this->wpdb->prepare("SELECT * FROM `{$table}` WHERE user_id = %d ORDER BY created_at DESC", $user_id);
        $results = (array) $this->wpdb->get_results($sql);
        // phpcs:enable
        return $results;
    }

    public function findAll(): array
    {
        $table = esc_sql($this->table_name);
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = (array) $this->wpdb->get_results("SELECT * FROM `{$table}` ORDER BY created_at DESC");
        // phpcs:enable
        return $results;
    }

    public function findByStatus(string $status): array
    {
        $table = esc_sql($this->table_name);
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = $this->wpdb->prepare("SELECT * FROM `{$table}` WHERE status = %s ORDER BY created_at DESC", $status);
        $results = (array) $this->wpdb->get_results($sql);
        // phpcs:enable
        return $results;
    }

    public function findByFilters(array $filters): array
    {
        list($where_sql, $join_sql, $params) = $this->buildFilterClauses($filters);

        $orderby = $this->resolveOrderBy($filters['orderby'] ?? 'created_at');
        $order = (isset($filters['order']) && strtoupper($filters['order']) === 'ASC') ? 'ASC' : 'DESC';

        $limit_sql = '';
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        if (!empty($filters['per_page']) && (int) $filters['per_page'] > 0) {
            $per_page = (int) $filters['per_page'];
            $page = max(1, (int) ($filters['paged'] ?? 1));
            $offset = ($page - 1) * $per_page;
            $limit_sql = $this->wpdb->prepare('LIMIT %d OFFSET %d', $per_page, $offset);
        }

        $table = esc_sql($this->table_name);
        $sql = "SELECT s.* FROM `{$table}` s {$join_sql} {$where_sql} ORDER BY {$orderby} {$order} {$limit_sql}";

        if (!empty($params)) {
            $prepared = $this->wpdb->prepare($sql, $params);
            $results = (array) $this->wpdb->get_results($prepared);
        } else {
            $results = (array) $this->wpdb->get_results($sql);
        }
        // phpcs:enable
        return $results;
    }

    public function countByFilters(array $filters): int
    {
        list($where_sql, $join_sql, $params) = $this->buildFilterClauses($filters);
        $table = esc_sql($this->table_name);
        $sql = "SELECT COUNT(*) FROM `{$table}` s {$join_sql} {$where_sql}";

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        if (!empty($params)) {
            $prepared = $this->wpdb->prepare($sql, $params);
            $count = (int) $this->wpdb->get_var($prepared);
        } else {
            $count = (int) $this->wpdb->get_var($sql);
        }
        // phpcs:enable
        return $count;
    }

    private function buildFilterClauses(array $filters): array
    {
        $where = [];
        $params = [];
        $join_users = false;

        if (!empty($filters['status'])) {
            $where[] = 's.status = %s';
            $params[] = $filters['status'];
        }

        if (!empty($filters['customer_search'])) {
            $join_users = true;
            $where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
            $like = '%' . $this->wpdb->esc_like($filters['customer_search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(s.start_date) >= %s';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(s.start_date) <= %s';
            $params[] = $filters['date_to'];
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $users_table = esc_sql($this->wpdb->users);
        $join_sql = $join_users ? "INNER JOIN `{$users_table}` u ON u.ID = s.user_id" : '';

        return [$where_sql, $join_sql, $params];
    }

    private function resolveOrderBy(string $field): string
    {
        $allowed = [
            'id' => 's.id',
            'user_id' => 's.user_id',
            'product_id' => 's.product_id',
            'status' => 's.status',
            'amount' => 's.amount',
            'period' => 's.period',
            'start_date' => 's.start_date',
            'next_payment' => 's.next_payment',
            'created_at' => 's.created_at',
        ];
        return $allowed[$field] ?? 's.created_at';
    }

    public function findDueRenewals(): array
    {
        $table = esc_sql($this->table_name);
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = (array) $this->wpdb->get_results(
            "SELECT * FROM `{$table}` WHERE status = 'active' AND next_payment <= NOW() ORDER BY next_payment ASC"
        );
        // phpcs:enable
        return $results;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function suspend(int $id): bool
    {
        return $this->updateStatus($id, 'suspended');
    }

    public function cancel(int $id): bool
    {
        return $this->update($id, [
            'status' => 'cancelled',
            'end_date' => current_time('mysql'),
        ]);
    }

    public function reactivate(int $id): bool
    {
        $subscription = $this->find($id);
        if ($subscription && $subscription->status === 'suspended') {
            $next_payment = $this->calculator->calculateNextPayment($subscription);
            return $this->update($id, [
                'status' => 'active',
                'next_payment' => $next_payment,
            ]);
        }
        return false;
    }

    public function incrementFailedPayments(int $id): bool
    {
        $subscription = $this->find($id);
        if ($subscription) {
            $failed_payments = ($subscription->failed_payments ?? 0) + 1;
            $data = ['failed_payments' => $failed_payments];

            if ($failed_payments >= 3) {
                $data['status'] = 'suspended';
            }

            return $this->update($id, $data);
        }
        return false;
    }

    public function processSuccessfulPayment(int $id): bool
    {
        $subscription = $this->find($id);
        if ($subscription) {
            $completed_cycles = ($subscription->completed_cycles ?? 0) + 1;
            $next_payment = $this->calculator->calculateNextPayment($subscription);

            $data = [
                'completed_cycles' => $completed_cycles,
                'failed_payments' => 0,
                'next_payment' => $next_payment,
            ];

            if ($subscription->billing_cycles > 0 && $completed_cycles >= $subscription->billing_cycles) {
                $data['status'] = 'completed';
                $data['end_date'] = current_time('mysql');
            }

            return $this->update($id, $data);
        }
        return false;
    }

    public function getSubscriptionStats(): object
    {
        $table = esc_sql($this->table_name);
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $stats = $this->wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
                SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as monthly_revenue
             FROM `{$table}`"
        );
        // phpcs:enable

        return $stats ?: (object) [
            'total' => 0, 'active' => 0, 'pending' => 0, 'cancelled' => 0, 'suspended' => 0, 'monthly_revenue' => 0,
        ];
    }

    public function getRevenueByPeriod(string $start_date, string $end_date): array
    {
        $table = esc_sql($this->table_name);
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = $this->wpdb->prepare(
            "SELECT DATE(created_at) as date, SUM(amount) as revenue, COUNT(*) as subscriptions
             FROM `{$table}`
             WHERE created_at BETWEEN %s AND %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $start_date,
            $end_date
        );
        $results = (array) $this->wpdb->get_results($sql);
        // phpcs:enable
        return $results;
    }
}
