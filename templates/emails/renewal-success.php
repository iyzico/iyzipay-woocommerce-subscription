<?php
/**
 * Abonelik Yenileme Başarılı E-postası
 *
 * @var string $user_name
 * @var string $product_name
 * @var string $amount
 * @var string $payment_date
 * @var string $next_payment
 * @var int    $subscription_id
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php esc_html_e('Aboneliğiniz Yenilendi', 'iyzipay-woocommerce-subscription'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #46b450;">
        <h2 style="color: #46b450; margin-top: 0;">
            <?php
            /* translators: %s: customer name */
            printf(esc_html__('Merhaba %s,', 'iyzipay-woocommerce-subscription'), esc_html($user_name));
            ?>
        </h2>

        <p>
            <?php
            /* translators: %s: product name */
            printf(esc_html__('%s aboneliğiniz başarıyla yenilendi.', 'iyzipay-woocommerce-subscription'), '<strong>' . esc_html($product_name) . '</strong>');
            ?>
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('Tutar', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo wp_kses_post($amount); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('Ödeme Tarihi', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($payment_date); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('Sonraki Ödeme', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($next_payment); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong><?php esc_html_e('Abonelik No', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px;">#<?php echo esc_html($subscription_id); ?></td>
            </tr>
        </table>

        <p><?php esc_html_e('Aboneliğinizi sürdürdüğünüz için teşekkür ederiz.', 'iyzipay-woocommerce-subscription'); ?></p>
    </div>
</body>
</html>
