<?php
/**
 * Abonelik İptal E-postası
 *
 * @var string $user_name
 * @var string $product_name
 * @var string $cancellation_date
 * @var int    $subscription_id
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php esc_html_e('Aboneliğiniz İptal Edildi', 'iyzipay-woocommerce-subscription'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #999;">
        <h2 style="margin-top: 0;">
            <?php
            /* translators: %s: customer name */
            printf(esc_html__('Merhaba %s,', 'iyzipay-woocommerce-subscription'), esc_html($user_name));
            ?>
        </h2>

        <p>
            <?php
            /* translators: %s: product name */
            printf(esc_html__('%s aboneliğiniz iptal edildi.', 'iyzipay-woocommerce-subscription'), '<strong>' . esc_html($product_name) . '</strong>');
            ?>
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('İptal Tarihi', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($cancellation_date); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong><?php esc_html_e('Abonelik No', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px;">#<?php echo esc_html($subscription_id); ?></td>
            </tr>
        </table>

        <p><?php esc_html_e('Bizi tercih ettiğiniz için teşekkür ederiz. Tekrar görüşmek dileğiyle.', 'iyzipay-woocommerce-subscription'); ?></p>
    </div>
</body>
</html>
