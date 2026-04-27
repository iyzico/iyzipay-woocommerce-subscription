<?php

namespace Iyzico\IyzipayWoocommerceSubscription\Services;

defined('ABSPATH') || exit;

use Iyzico\IyzipayWoocommerceSubscription\Services\Interfaces\AccountServiceInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SubscriptionRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\Interfaces\SavedCardRepositoryInterface;
use Iyzico\IyzipayWoocommerceSubscription\Models\SavedCardRepository;
use Iyzipay\Model\CardList;
use Iyzipay\Model\Locale as IyziLocale;
use Iyzipay\Options;
use Iyzipay\Request\CreateCardDeleteRequest;
use Iyzipay\Request\RetrieveCardListRequest;

class AccountService implements AccountServiceInterface
{
    private SubscriptionRepositoryInterface $subscriptionRepository;
    private SavedCardRepositoryInterface $savedCardRepository;

    public function __construct(SubscriptionRepositoryInterface $subscriptionRepository) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->savedCardRepository = new SavedCardRepository();
    }

    public function addAccountEndpoints(): void
    {
        add_rewrite_endpoint('subscriptions', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('saved-cards', EP_ROOT | EP_PAGES);
    }

    public function addAccountMenuItems(array $items): array
    {
        $items['subscriptions'] = __('Aboneliklerim', 'iyzipay-woocommerce-subscription');
        $items['saved-cards'] = __('Kayıtlı Kartlarım', 'iyzipay-woocommerce-subscription');
        return $items;
    }

    public function renderSubscriptionsAccountPage(): void
    {
        $user_subscriptions = $this->subscriptionRepository->findByUser(get_current_user_id());
        
        if (empty($user_subscriptions)) {
            echo '<p>' . esc_html__('Henüz aktif aboneliğiniz bulunmamaktadır.', 'iyzipay-woocommerce-subscription') . '</p>';
            return;
        }

        echo '<div class="woocommerce-account-subscriptions">';
        echo '<table class="woocommerce-orders-table shop_table shop_table_responsive">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Ürün', 'iyzipay-woocommerce-subscription') . '</th>';
        echo '<th>' . esc_html__('Durum', 'iyzipay-woocommerce-subscription') . '</th>';
        echo '<th>' . esc_html__('Başlangıç Tarihi', 'iyzipay-woocommerce-subscription') . '</th>';
        echo '<th>' . esc_html__('Sonraki Ödeme', 'iyzipay-woocommerce-subscription') . '</th>';
        echo '<th>' . esc_html__('Tutar', 'iyzipay-woocommerce-subscription') . '</th>';
        echo '<th>' . esc_html__('İşlemler', 'iyzipay-woocommerce-subscription') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($user_subscriptions as $subscription) {
            $product = wc_get_product($subscription->product_id);
            if (!$product) continue;

            echo '<tr>';
            echo '<td>' . esc_html($product->get_name()) . '</td>';
            echo '<td>' . esc_html($this->getStatusLabel($subscription->status)) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($subscription->start_date))) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($subscription->next_payment))) . '</td>';
            echo '<td>' . wp_kses_post(wc_price($subscription->amount)) . '</td>';
            echo '<td>';
            $this->renderSubscriptionActions($subscription);
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.iyzico-subscription-action').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('<?php echo esc_js(__('Bu işlemi gerçekleştirmek istediğinizden emin misiniz?', 'iyzipay-woocommerce-subscription')); ?>')) {
                    return;
                }

                var $button = $(this);
                var subscriptionId = $button.data('subscription-id');
                var action = $button.data('action');

                $button.prop('disabled', true);

                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
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
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Bir hata oluştu. Lütfen tekrar deneyin.', 'iyzipay-woocommerce-subscription')); ?>');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function getStatusLabel(string $status): string
    {
        $labels = [
            'active' => __('Aktif', 'iyzipay-woocommerce-subscription'),
            'cancelled' => __('İptal Edildi', 'iyzipay-woocommerce-subscription'),
            'suspended' => __('Askıya Alındı', 'iyzipay-woocommerce-subscription'),
            'expired' => __('Süresi Doldu', 'iyzipay-woocommerce-subscription'),
        ];

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    public function renderSubscriptionActions(object $subscription): void
    {
        if ($subscription->status === 'active') {
            echo '<button class="button iyzico-subscription-action" data-subscription-id="' . esc_attr($subscription->id) . '" data-action="suspend">' . esc_html__('Askıya Al', 'iyzipay-woocommerce-subscription') . '</button> ';
            echo '<button class="button iyzico-subscription-action" data-subscription-id="' . esc_attr($subscription->id) . '" data-action="cancel">' . esc_html__('İptal Et', 'iyzipay-woocommerce-subscription') . '</button>';
        } elseif ($subscription->status === 'suspended') {
            echo '<button class="button iyzico-subscription-action" data-subscription-id="' . esc_attr($subscription->id) . '" data-action="reactivate">' . esc_html__('Yeniden Aktifleştir', 'iyzipay-woocommerce-subscription') . '</button>';
        }
    }

    public function renderSavedCardsAccountPage(): void
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            echo '<p>' . esc_html__('Bu sayfayı görüntülemek için giriş yapmalısınız.', 'iyzipay-woocommerce-subscription') . '</p>';
            return;
        }

        $card_user_key = $this->savedCardRepository->getCardUserKey($user_id);

        echo '<div class="woocommerce-account-saved-cards">';
        echo '<h3>' . esc_html__('Kayıtlı Kartlarım', 'iyzipay-woocommerce-subscription') . '</h3>';

        if (empty($card_user_key)) {
            echo '<p>' . esc_html__('Henüz kayıtlı kartınız bulunmamaktadır. Bir abonelik satın aldığınızda kart bilgileriniz güvenli bir şekilde saklanır.', 'iyzipay-woocommerce-subscription') . '</p>';
            echo '</div>';
            return;
        }

        $options = $this->getIyzicoOptions();

        if (!$options) {
            echo '<p>' . esc_html__('Ödeme sağlayıcı yapılandırması eksik. Lütfen site yöneticisi ile iletişime geçin.', 'iyzipay-woocommerce-subscription') . '</p>';
            echo '</div>';
            return;
        }

        try {
            $request = new RetrieveCardListRequest();
            $request->setLocale(IyziLocale::TR);
            $request->setCardUserKey($card_user_key);

            $cardList = CardList::retrieve($request, $options);

            if ($cardList->getStatus() !== 'success') {
                echo '<p>' . esc_html__('Kart bilgileri alınamadı. Lütfen daha sonra tekrar deneyin.', 'iyzipay-woocommerce-subscription') . '</p>';
                echo '</div>';
                return;
            }

            $cards = $cardList->getCardDetails() ?: [];

            if (empty($cards)) {
                echo '<p>' . esc_html__('Henüz kayıtlı kartınız bulunmamaktadır.', 'iyzipay-woocommerce-subscription') . '</p>';
                echo '</div>';
                return;
            }

            echo '<table class="woocommerce-orders-table shop_table shop_table_responsive">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Banka', 'iyzipay-woocommerce-subscription') . '</th>';
            echo '<th>' . esc_html__('Kart Tipi', 'iyzipay-woocommerce-subscription') . '</th>';
            echo '<th>' . esc_html__('Son 4 Hane', 'iyzipay-woocommerce-subscription') . '</th>';
            echo '<th>' . esc_html__('Takma Ad', 'iyzipay-woocommerce-subscription') . '</th>';
            echo '<th>' . esc_html__('İşlemler', 'iyzipay-woocommerce-subscription') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($cards as $card) {
                $bank = method_exists($card, 'getCardBankName') ? (string) $card->getCardBankName() : '';
                $assoc = method_exists($card, 'getCardAssociation') ? (string) $card->getCardAssociation() : '';
                $last4 = method_exists($card, 'getLastFourDigits') ? (string) $card->getLastFourDigits() : '';
                $alias = method_exists($card, 'getCardAlias') ? (string) $card->getCardAlias() : '';
                $token = method_exists($card, 'getCardToken') ? (string) $card->getCardToken() : '';

                echo '<tr>';
                echo '<td>' . esc_html($bank) . '</td>';
                echo '<td>' . esc_html($assoc) . '</td>';
                echo '<td>**** ' . esc_html($last4) . '</td>';
                echo '<td>' . esc_html($alias) . '</td>';
                echo '<td>';
                echo '<button class="button iyzico-delete-card" data-token="' . esc_attr($token) . '">' . esc_html__('Sil', 'iyzipay-woocommerce-subscription') . '</button>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } catch (\Exception $e) {
            echo '<p>' . esc_html__('Kart bilgileri alınırken bir hata oluştu.', 'iyzipay-woocommerce-subscription') . '</p>';
        }

        echo '</div>';

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.iyzico-delete-card').on('click', function(e) {
                e.preventDefault();

                if (!confirm('<?php echo esc_js(__('Bu kartı silmek istediğinizden emin misiniz?', 'iyzipay-woocommerce-subscription')); ?>')) {
                    return;
                }

                var $button = $(this);
                var token = $button.data('token');

                $button.prop('disabled', true);

                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'iyzico_delete_saved_card',
                        card_token: token,
                        nonce: '<?php echo esc_attr(wp_create_nonce('iyzico_saved_cards')); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Bir hata oluştu.', 'iyzipay-woocommerce-subscription')); ?>');
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Bir hata oluştu. Lütfen tekrar deneyin.', 'iyzipay-woocommerce-subscription')); ?>');
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function ajaxListSavedCards(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'iyzico_saved_cards')) {
            wp_send_json_error(['message' => __('Güvenlik doğrulaması başarısız.', 'iyzipay-woocommerce-subscription')]);
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Giriş yapmalısınız.', 'iyzipay-woocommerce-subscription')]);
        }

        $card_user_key = $this->savedCardRepository->getCardUserKey($user_id);
        if (empty($card_user_key)) {
            wp_send_json_success(['cards' => []]);
        }

        $options = $this->getIyzicoOptions();
        if (!$options) {
            wp_send_json_error(['message' => __('Ödeme sağlayıcı yapılandırması eksik.', 'iyzipay-woocommerce-subscription')]);
        }

        try {
            $request = new RetrieveCardListRequest();
            $request->setLocale(IyziLocale::TR);
            $request->setCardUserKey($card_user_key);

            $cardList = CardList::retrieve($request, $options);

            if ($cardList->getStatus() !== 'success') {
                $error = method_exists($cardList, 'getErrorMessage') ? $cardList->getErrorMessage() : __('Bilinmeyen hata', 'iyzipay-woocommerce-subscription');
                wp_send_json_error(['message' => $error]);
            }

            $details = $cardList->getCardDetails() ?: [];
            $cards = array_map(function ($c) {
                return [
                    'alias' => method_exists($c, 'getCardAlias') ? $c->getCardAlias() : '',
                    'association' => method_exists($c, 'getCardAssociation') ? $c->getCardAssociation() : '',
                    'family' => method_exists($c, 'getCardFamily') ? $c->getCardFamily() : '',
                    'bank' => method_exists($c, 'getCardBankName') ? $c->getCardBankName() : '',
                    'type' => method_exists($c, 'getCardType') ? $c->getCardType() : '',
                    'lastFour' => method_exists($c, 'getLastFourDigits') ? $c->getLastFourDigits() : '',
                    'token' => method_exists($c, 'getCardToken') ? $c->getCardToken() : '',
                    'bin' => method_exists($c, 'getBinNumber') ? $c->getBinNumber() : '',
                ];
            }, $details);

            wp_send_json_success(['cards' => $cards]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function ajaxDeleteSavedCard(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'iyzico_saved_cards')) {
            wp_send_json_error(['message' => __('Güvenlik doğrulaması başarısız.', 'iyzipay-woocommerce-subscription')]);
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Giriş yapmalısınız.', 'iyzipay-woocommerce-subscription')]);
        }

        $card_token = isset($_POST['card_token']) ? sanitize_text_field(wp_unslash($_POST['card_token'])) : '';
        if (empty($card_token)) {
            wp_send_json_error(['message' => __('Kart bilgisi eksik.', 'iyzipay-woocommerce-subscription')]);
        }

        $card_user_key = $this->savedCardRepository->getCardUserKey($user_id);
        if (empty($card_user_key)) {
            wp_send_json_error(['message' => __('Kayıtlı kart bulunamadı.', 'iyzipay-woocommerce-subscription')]);
        }

        $options = $this->getIyzicoOptions();
        if (!$options) {
            wp_send_json_error(['message' => __('Ödeme sağlayıcı yapılandırması eksik.', 'iyzipay-woocommerce-subscription')]);
        }

        try {
            $request = new CreateCardDeleteRequest();
            $request->setLocale(IyziLocale::TR);
            $request->setCardUserKey($card_user_key);
            $request->setCardToken($card_token);

            $card = \Iyzipay\Model\Card::delete($request, $options);

            if ($card->getStatus() !== 'success') {
                $error = method_exists($card, 'getErrorMessage') ? $card->getErrorMessage() : __('Kart silinemedi.', 'iyzipay-woocommerce-subscription');
                wp_send_json_error(['message' => $error]);
            }

            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete(
                $wpdb->prefix . 'iyzico_saved_cards',
                ['user_id' => $user_id, 'card_token' => $card_token],
                ['%d', '%s']
            );

            wp_send_json_success(['message' => __('Kart başarıyla silindi.', 'iyzipay-woocommerce-subscription')]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function getIyzicoOptions(): ?Options
    {
        $settings = get_option('woocommerce_iyzico_subscription_settings', []);
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        $secret_key = isset($settings['secret_key']) ? $settings['secret_key'] : '';
        $sandbox = isset($settings['sandbox']) ? $settings['sandbox'] : 'yes';

        if (empty($api_key) || empty($secret_key)) {
            return null;
        }

        $options = new Options();
        $options->setApiKey($api_key);
        $options->setSecretKey($secret_key);
        $options->setBaseUrl($sandbox === 'yes' ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com');

        return $options;
    }
}
