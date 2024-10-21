<?php
/*
Template Name: Sign Contract Template
*/

// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

$formData = get_query_var('formData');
$signer = get_query_var('signer');

error_log(print_r($formData, true));

if (!$formData) {
    wp_die(esc_html__('Missing form data.', 'dpcms'));
}

if (!$signer) {
    wp_die(esc_html__('Missing signer data.', 'dpcms'));
}

$logo = dpcms_get_custom_option('dpcms_logo', '');

$company_details = dpcms_get_custom_option(
    'dpcms_company_details',
    array(
        'name' => '',
        'street_address' => '',
        'city' => '',
        'state' => '',
        'zip_code' => '',
        'phone' => '',
        'email' => '',
        'website' => ''
    )
);
$company_address = esc_html($company_details['street_address']) . ', ' .
    esc_html($company_details['city']) . ', ' .
    esc_html($company_details['state']) . ' ' .
    esc_html($company_details['zip_code']);

$contract_title = esc_html(dpcms_get_custom_option('dpcms_contract_title', 'Agreement'));
$contract_header = esc_html(dpcms_get_custom_option('dpcms_contract_header', 'This document is an Agreement'));

$seller_type = esc_html(dpcms_get_custom_option('dpcms_seller_type', 'Seller'));
$buyer_type = esc_html(dpcms_get_custom_option('dpcms_buyer_type', 'Buyer'));
$sale_type = esc_html(dpcms_get_custom_option('dpcms_sale_type', 'sale and purchase'));
$custom_seller_type = ($seller_type === 'Other') ? esc_html(dpcms_get_custom_option('dpcms_custom_seller_type', 'Seller')) : '';
$custom_buyer_type = ($buyer_type === 'Other') ? esc_html(dpcms_get_custom_option('dpcms_custom_buyer_type', 'Buyer')) : '';
$custom_sale_type = ($sale_type === 'Other') ? esc_html(dpcms_get_custom_option('dpcms_custom_sale_type', 'sale and purchase')) : '';

$product_sold = esc_html(dpcms_get_custom_option('dpcms_product_sold', 'a Home'));

$add_house_specifications = dpcms_get_custom_option('dpcms_add_house_specifications', false);
$dynamic_fields = isset($formData['dynamic_fields']) && !empty($formData['dynamic_fields']) ? maybe_unserialize($formData['dynamic_fields']) : [];
$terms = esc_html(dpcms_get_custom_option('dpcms_terms_and_conditions', 'Default terms and conditions...'));
$add_other_provisions = dpcms_get_custom_option('dpcms_add_other_provisions', false);
$add_delivery_address = dpcms_get_custom_option('dpcms_add_delivery_address', false);
$add_pricing_calculator = dpcms_get_custom_option('dpcms_add_pricing_calculator', false);

?>
<!DOCTYPE html>
<html>

<head>
    <title><?php esc_html_e('Sign Contract', 'dpcms'); ?></title>
    <?php wp_head(); ?>
</head>

<body>
    <div id="dpcms-container">
        <div class="dpcms-contract-document">
            <div class="dpcms-company-details">
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" alt="<?php esc_attr_e('Company Logo', 'dpcms'); ?>">
                <?php endif; ?>
                <p><strong><?php esc_html_e('Company Name:', 'dpcms'); ?></strong>
                    <?php echo esc_html($company_details['name']); ?></p>
                <p><strong><?php esc_html_e('Address:', 'dpcms'); ?></strong>
                    <?php echo esc_html($company_address); ?></p>
                <p><strong><?php esc_html_e('Phone:', 'dpcms'); ?></strong>
                    <?php echo esc_html($company_details['phone']); ?></p>
                <p><strong><?php esc_html_e('Email:', 'dpcms'); ?></strong>
                    <?php echo esc_html($company_details['email']); ?></p>
                <p><strong><?php esc_html_e('Website:', 'dpcms'); ?></strong>
                    <?php echo esc_html($company_details['website']); ?></p>
            </div>
            <h2><?php echo esc_html($contract_title); ?></h2>
            <p><?php echo esc_html($contract_header); ?> <?php esc_html_e('between', 'dpcms'); ?>
                <strong><?php echo esc_html($company_details['name']); ?></strong>
                <?php esc_html_e('(the', 'dpcms'); ?>
                “<?php echo ($seller_type == 'Other') ? esc_html($custom_seller_type) : esc_html($seller_type); ?>”
                <?php esc_html_e(') and', 'dpcms'); ?>
                <strong><?php echo isset($formData['first_name']) ? esc_html($formData['first_name']) : esc_html('John'); ?>
                    <?php echo isset($formData['last_name']) ? esc_html($formData['last_name']) : esc_html('Doe'); ?></strong>
                <?php esc_html_e('(the', 'dpcms'); ?>
                “<?php echo ($buyer_type == 'Other') ? esc_html($custom_buyer_type) : esc_html($buyer_type); ?>”)
                <?php if (!empty($formData['co_signer_first_name']) && !empty($formData['co_signer_last_name'])): ?>
                    <strong><?php echo esc_html(' and ' . $formData['co_signer_first_name'] . ' ' . $formData['co_signer_last_name']); ?></strong>
                    <?php esc_html_e('(the', 'dpcms'); ?>
                    “Co-<?php echo ($buyer_type == 'Other') ? esc_html($custom_buyer_type) : esc_html($buyer_type); ?>”)
                <?php endif; ?>
                <?php esc_html_e('for the', 'dpcms'); ?>
                <?php echo ($sale_type == 'Other') ? esc_html($custom_sale_type) : esc_html($sale_type); ?>
                <?php esc_html_e('of', 'dpcms'); ?> <?php echo esc_html($product_sold); ?>
            </p>
            <?php if ($add_house_specifications): ?>
                <h3><?php esc_html_e('House Specifications', 'dpcms'); ?></h3>
                <?php if (isset($formData['model_number']) && !empty($formData['model_number'])): ?>
                    <strong>
                        <?php echo esc_html__('Model # ', 'dpcms') . esc_html($formData['model_number']); ?>
                    </strong>
                    <?php esc_html_e('with the following specifications:', 'dpcms'); ?>
                <?php endif; ?>
                <ul>
                    <li><?php esc_html_e('Bedrooms:', 'dpcms'); ?>
                        <?php echo isset($formData['bedrooms']) ? intval($formData['bedrooms']) : ''; ?>
                    </li>
                    <li><?php esc_html_e('Bathrooms:', 'dpcms'); ?>
                        <?php echo isset($formData['bathrooms']) ? intval($formData['bathrooms']) : ''; ?>
                    </li>
                    <li><?php esc_html_e('Interior Sqft:', 'dpcms'); ?>
                        <?php echo isset($formData['home_sqft']) ? intval($formData['home_sqft']) : ''; ?> sqft
                    </li>
                    <li><?php esc_html_e('Porch Sqft:', 'dpcms'); ?>
                        <?php echo isset($formData['deck_sqft']) ? intval($formData['deck_sqft']) : ''; ?> sqft
                    </li>
                    <li><?php esc_html_e('Garage Sqft:', 'dpcms'); ?>
                        <?php echo isset($formData['garage_sqft']) ? intval($formData['garage_sqft']) : ''; ?> sqft
                    </li>
                </ul>
            <?php endif; ?>

            <h3><?php esc_html_e('Billing Address', 'dpcms'); ?></h3>
            <p><?php echo isset($formData['first_name']) ? esc_html($formData['first_name']) : ''; ?>
                <?php echo isset($formData['last_name']) ? esc_html($formData['last_name']) : ''; ?><br>
                <?php echo isset($formData['address']) ? esc_html($formData['address']) : ''; ?><br>
                <?php echo isset($formData['city']) ? esc_html($formData['city']) : ''; ?>,
                <?php echo isset($formData['state']) ? esc_html($formData['state']) : ''; ?>
                <?php echo isset($formData['zip']) ? esc_html($formData['zip']) : ''; ?><br>
                <?php echo isset($formData['country']) ? esc_html($formData['country']) : ''; ?><br>
                <?php esc_html_e('Phone:', 'dpcms'); ?>
                <?php echo isset($formData['phone']) ? esc_html($formData['phone']) : ''; ?><br>
                <?php esc_html_e('Email:', 'dpcms'); ?>
                <?php echo isset($formData['email']) ? esc_html($formData['email']) : ''; ?>
            </p>

            <?php if ($add_delivery_address): ?>
                <h3><?php esc_html_e('Delivery Address', 'dpcms'); ?></h3>
                <p><?php echo isset($formData['delivery_address']) ? esc_html($formData['delivery_address']) : ''; ?><br>
                    <?php echo isset($formData['delivery_city']) ? esc_html($formData['delivery_city']) : ''; ?>,
                    <?php echo isset($formData['delivery_state']) ? esc_html($formData['delivery_state']) : ''; ?>
                    <?php echo isset($formData['delivery_zip']) ? esc_html($formData['delivery_zip']) : ''; ?><br>
                    <?php echo isset($formData['delivery_country']) ? esc_html($formData['delivery_country']) : ''; ?>
                </p>
            <?php endif; ?>

            <?php if ($add_pricing_calculator): ?>
                <h3><?php esc_html_e('Pricing Details', 'dpcms'); ?></h3>
                <p>
                <p><?php esc_html_e('Original Price:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['original_price'] ?? '0.00'); ?></p>
                <p><?php esc_html_e('Freight:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['freight'] ?? '0.00'); ?></p>
                <p><?php esc_html_e('Custom Options Price:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['custom_options_price'] ?? '0.00'); ?></p>
                <p><?php esc_html_e('Deductions:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['deductions'] ?? '0.00'); ?></p>
                <p><?php esc_html_e('Total Purchase Price:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['total_purchase_price'] ?? '0.00'); ?></p>
                <p><?php esc_html_e('Initial Payment:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['initial_payment'] ?? '0.00'); ?></p>
                <p><?php esc_html_e('Remaining Balance to Start:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['remaining_balance_start'] ?? '0.00'); ?></p>
                <p><?php esc_html_e('Total Remaining Balance:', 'dpcms'); ?>
                    $<?php echo esc_html($formData['total_remaining_balance'] ?? '0.00'); ?></p>
                </p>
            <?php endif; ?>

            <?php if (isset($dynamic_fields) && is_array($dynamic_fields)): ?>
                <?php foreach ($dynamic_fields as $dynamic_field): ?>
                    <h3><?php echo esc_html($dynamic_field['header']); ?></h3>
                    <ul>
                        <?php foreach ($dynamic_field['fields'] as $field): ?>
                            <?php $clean_label = str_replace(' *', '', esc_html($field['label']));
                            $is_number = ctype_digit($field['value']) ? '1' : '0';
                            ?>
                            <li>
                                <?php
                                echo esc_html($clean_label);
                                if (!empty($field['value'])) {
                                    echo ': ';
                                    if ($is_number && isset($field['is_price']) && $field['is_price'] == '1') {
                                        echo '$';
                                    }
                                    echo esc_html($field['value']);
                                }
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            <?php endif; ?>

            <h3><?php esc_html_e('Terms and Conditions', 'dpcms'); ?></h3>
            <p><?php echo nl2br(esc_html($terms)); ?></p>
            <?php if ($add_other_provisions): ?>
                <?php if (!empty($formData['other_provisions'])): ?>
                    <h3><?php esc_html_e('Other Provisions', 'dpcms'); ?></h3>
                    <p><?php echo isset($formData['other_provisions']) ? nl2br(esc_html($formData['other_provisions'])) : esc_html('N/A'); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="dpcms-contract-form">
            <?php if ($signer === 'primary' && empty($formData['signature'])): ?>
                <form id="sign-form" action="" method="post">
                    <?php wp_nonce_field('dpcms_document_signature_nonce', 'dpcms_document_signature_nonce'); ?>
                    <h3><?php esc_html_e('Signature', 'dpcms'); ?></h3>
                    <div id="dpcms-signature-pad" class="dpcms-signature-pad">
                        <canvas id="primary-canvas"></canvas>
                        <button type="button" id="clear"><?php esc_html_e('Clear', 'dpcms'); ?></button>
                    </div>
                    <input type="hidden" name="signature" id="signature">
                    <button type="submit"><?php esc_html_e('Submit Signature', 'dpcms'); ?></button>
                </form>
            <?php elseif ($signer === 'co-signer' && empty($formData['co_signer_signature'])): ?>
                <form id="co-sign-form" action="" method="post">
                    <?php wp_nonce_field('dpcms_document_signature_nonce', 'dpcms_document_signature_nonce'); ?>
                    <h3><?php esc_html_e('Co-Signer Signature', 'dpcms'); ?></h3>
                    <div id="dpcms-signature-pad-co" class="dpcms-signature-pad">
                        <canvas id="co-signer-canvas"></canvas>
                        <button type="button" id="clear-co"><?php esc_html_e('Clear', 'dpcms'); ?></button>
                    </div>
                    <input type="hidden" name="co_signer_signature" id="co_signer_signature">
                    <button type="submit"><?php esc_html_e('Submit Co-Signer Signature', 'dpcms'); ?></button>
                </form>
            <?php elseif ($signer === 'admin' && empty($formData['admin_signature'])): ?>
                <form id="admin-sign-form" action="" method="post">
                    <?php wp_nonce_field('dpcms_document_signature_nonce', 'dpcms_document_signature_nonce'); ?>
                    <h3><?php esc_html_e('Admin Signature', 'dpcms'); ?></h3>
                    <div id="dpcms-signature-pad-admin" class="dpcms-signature-pad">
                        <canvas id="admin-canvas"></canvas>
                        <button type="button" id="clear-admin"><?php esc_html_e('Clear', 'dpcms'); ?></button>
                    </div>
                    <input type="hidden" name="admin_signature" id="admin_signature">
                    <button type="submit"><?php esc_html_e('Submit Admin Signature', 'dpcms'); ?></button>
                </form>
            <?php else: ?>
                <p><?php esc_html_e('You have already signed this document.', 'dpcms'); ?></p>
            <?php endif; ?>

            <div class="signature-info">
                <h3><?php esc_html_e('Signatures', 'dpcms'); ?></h3>
                <div>
                    <?php if (!empty($formData['signature'])): ?>
                        <img src="<?php echo esc_attr($formData['signature']); ?>"
                            alt="<?php esc_attr_e('Signature', 'dpcms'); ?>" style="max-width: 200px;">
                        <div style="border-top: 1px solid #000; width: 200px; margin-top: 5px;"></div>
                        <p><?php echo esc_html($formData['first_name']) . ' ' . esc_html($formData['last_name']); ?></p>
                        <p><?php esc_html_e('Signed on', 'dpcms'); ?>     <?php echo esc_html(gmdate('Y-m-d')); ?>
                        </p>
                    <?php else: ?>
                        <p><?php esc_html_e('Signature pending for', 'dpcms'); ?>
                            <?php echo esc_html($formData['first_name']) . ' ' . esc_html($formData['last_name']); ?>.
                        </p>
                        <div style="border-top: 1px solid #000; width: 200px; margin-top: 5px;"></div>
                        <p><?php echo esc_html($formData['first_name']) . ' ' . esc_html($formData['last_name']); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($formData['co_signer_first_name']) && !empty($formData['co_signer_last_name'])): ?>
                    <div>
                        <?php if (!empty($formData['co_signer_signature'])): ?>
                            <img src="<?php echo esc_attr($formData['co_signer_signature']); ?>"
                                alt="<?php esc_attr_e('Co-Signer Signature', 'dpcms'); ?>" style="max-width: 200px;">
                            <div style="border-top: 1px solid #000; width: 200px; margin-top: 5px;"></div>
                            <p><?php echo esc_html($formData['co_signer_first_name']) . ' ' . esc_html($formData['co_signer_last_name']); ?>
                            </p>
                            <p><?php esc_html_e('Signed on', 'dpcms'); ?>         <?php echo esc_html(gmdate('Y-m-d')); ?>
                            </p>
                        <?php else: ?>
                            <p><?php esc_html_e('Signature pending for', 'dpcms'); ?>
                                <?php echo esc_html($formData['co_signer_first_name']) . ' ' . esc_html($formData['co_signer_last_name']); ?>.
                            </p>
                            <div style="border-top: 1px solid #000; width: 200px; margin-top: 5px;"></div>
                            <p><?php echo esc_html($formData['co_signer_first_name']) . ' ' . esc_html($formData['co_signer_last_name']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php wp_footer(); ?>
</body>

</html>