<?php
/**
 * Abonelik Yakında Sona Eriyor E-postası
 *
 * @var string $user_name
 * @var string $product_name
 * @var string $expiry_date
 * @var int    $days_remaining
 * @var string $renewal_url
 * @var int    $subscription_id
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php esc_html_e('Aboneliğiniz Yakında Sona Eriyor', 'iyzipay-woocommerce-subscription'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #00a0d2;">
        <h2 style="color: #0073aa; margin-top: 0;">
            <?php
            /* translators: %s: customer name */
            printf(esc_html__('Merhaba %s,', 'iyzipay-woocommerce-subscription'), esc_html($user_name));
            ?>
        </h2>

        <p>
            <?php
            /* translators: %s: product name */
            printf(esc_html__('%s aboneliğiniz yakında sona eriyor.', 'iyzipay-woocommerce-subscription'), '<strong>' . esc_html($product_name) . '</strong>');
            ?>
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('Bitiş Tarihi', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo esc_html($expiry_date); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong><?php esc_html_e('Kalan Gün', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <?php
                    /* translators: %d: days remaining */
                    printf(esc_html__('%d gün', 'iyzipay-woocommerce-subscription'), (int) $days_remaining);
                    ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px;"><strong><?php esc_html_e('Abonelik No', 'iyzipay-woocommerce-subscription'); ?></strong></td>
                <td style="padding: 8px;">#<?php echo esc_html($subscription_id); ?></td>
            </tr>
        </table>

        <p>
            <?php esc_html_e('Aboneliğinizi yenilemek veya yönetmek için hesap sayfanızı ziyaret edebilirsiniz:', 'iyzipay-woocommerce-subscription'); ?>
        </p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="<?php echo esc_url($renewal_url); ?>" style="background: #FF6B00; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                <?php esc_html_e('Aboneliğimi Yönet', 'iyzipay-woocommerce-subscription'); ?>
            </a>
        </p>
    </div>
</body>
</html>
