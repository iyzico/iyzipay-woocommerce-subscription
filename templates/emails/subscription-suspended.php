<?php
/**
 * Abonelik Askıya Alındı E-postası
 *
 * @var string $user_name
 * @var string $product_name
 * @var int    $failed_payments
 * @var string $suspension_date
 * @var string $account_url
 * @var int    $subscription_id
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php esc_html_e('Aboneliğiniz Askıya Alındı', 'iyzipay-woocommerce-subscription'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #fff8e1; padding: 20px; border-radius: 8px; border-left: 4px solid #ffb900;">
        <h2 style="color: #b07d00; margin-top: 0;">
            <?php
            /* translators: %s: customer name */
            printf(esc_html__('Merhaba %s,', 'iyzipay-woocommerce-subscription'), esc_html($user_name));
            ?>
        </h2>

        <p>
            <?php
            /* translators: %s: product name */
            printf(esc_html__('%s aboneliğiniz askıya alındı.', 'iyzipay-woocommerce-subscription'), '<strong>' . esc_html($product_name) . '</strong>');
            ?>
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('Sebep', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?php
                    /* translators: %d: failed payments count */
                    printf(esc_html__('%d başarısız ödeme', 'iyzipay-woocommerce-subscription'), (int) $failed_payments);
                    ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('Tarih', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($suspension_date); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong><?php esc_html_e('Abonelik No', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px;">#<?php echo esc_html($subscription_id); ?></td>
            </tr>
        </table>

        <p>
            <?php esc_html_e('Hesap sayfanızdan aboneliğinizi yeniden aktifleştirebilir veya kart bilgilerinizi güncelleyebilirsiniz:', 'iyzipay-woocommerce-subscription'); ?>
        </p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="<?php echo esc_url($account_url); ?>" style="background: #FF6B00; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                <?php esc_html_e('Hesabıma Git', 'iyzipay-woocommerce-subscription'); ?>
            </a>
        </p>
    </div>
</body>
</html>
