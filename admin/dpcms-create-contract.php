<?php
// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

function dpcms_display_edit_contract_page()
{
    dpcms_check_capabilities('access_to_template', true);

    if (isset($_POST['dpcms_save_create_contract_settings'])) {
        check_admin_referer('dpcms_save_settings_verify');
        dpcms_save_create_contract_settings();
    }
    ?>

    <div class="wrap">
        <h1>DPCMS Settings</h1>
        <?php $consent = dpcms_get_custom_option('dpcms_data_consent', '');
        if (!$consent) {
            echo '<strong>' . esc_html__('Please mark your consent to the privacy policy on the', 'deluxe-wp-contracts') .
                ' <a href="' . esc_url(admin_url('admin.php?page=dpcms-settings')) . '">' .
                esc_html__('settings page', 'deluxe-wp-contracts') . '</a>.</strong>';
        } ?>
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Settings saved successfully.</p>
            </div>
        <?php elseif (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'false'): ?>
            <div class="notice notice-error is-dismissible">
                <p>Settings did not save.</p>
            </div>
        <?php endif; ?>

        <h2>Scroll down to see an example of how your contract will look.</h2>
        <p>Some fields are required to be placed into the actual contract creation form for them to show up properly.</p>
        <p>Also, some fields are dynamic and will or will not show up based on if they are entered into the form or not.</p>

        <form id="dpcms-create-contract-settings-form" method="post" action="" enctype="multipart/form-data">
            <?php
            wp_nonce_field('dpcms_save_settings_verify');
            echo '<div class="dpcms-section">';
            echo '<div class="dpcms-section-title">Terms and Conditions</div>';
            echo '<div class="dpcms-section-content">';
            dpcms_terms_and_conditions_display();
            echo '</div></div>';

            echo '<div class="dpcms-section">';
            echo '<div class="dpcms-section-title">Predefined Lists</div>';
            echo '<div class="dpcms-section-content">';
            dpcms_add_house_specifications_checkbox_display();
            dpcms_add_delivery_address_display();
            dpcms_add_co_signer_information_display();
            dpcms_add_other_provisions_checkbox_display();
            echo '</div></div>';

            echo '<div class="dpcms-section">';
            echo '<div class="dpcms-section-title">Custom Lists</div>';
            echo '<div class="dpcms-section-content">';
            dpcms_dynamic_fields_display();
            echo '</div></div>';

            echo '<div class="dpcms-section">';
            echo '<div class="dpcms-section-title">Contract Header & Footer</div>';
            echo '<div class="dpcms-section-content">';
            dpcms_contract_title_display();
            dpcms_signed_by_text_display();
            echo '</div></div>';

            echo '<div class="dpcms-section">';
            echo '<div class="dpcms-section-title">Payment Calculator</div>';
            echo '<div class="dpcms-section-content">';
            dpcms_add_pricing_calculator_checkbox_display();
            dpcms_remaining_balance_percentage_display();
            echo '</div></div>';

            submit_button('Save Settings', 'primary', 'dpcms_save_create_contract_settings'); ?>
        </form>
    </div>

    <?php
    $company_details = dpcms_get_custom_option(
        'dpcms_company_details',
        array(
            'name' => 'Example Company',
            'street_address' => '123 Example St',
            'city' => 'Example City',
            'state' => 'EX',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'example@example.com',
            'website' => 'www.example.com'
        )
    );
    $contract_title = dpcms_get_custom_option('dpcms_contract_title', 'Agreement');
    $contract_header = dpcms_get_custom_option('dpcms_contract_header', 'This document is an Agreement');

    $seller_type = dpcms_get_custom_option('dpcms_seller_type', 'Seller');
    $buyer_type = dpcms_get_custom_option('dpcms_buyer_type', 'Buyer');
    $sale_type = dpcms_get_custom_option('dpcms_sale_type', 'sale and purchase');
    $custom_seller_type = ($seller_type === 'Other') ? dpcms_get_custom_option('dpcms_custom_seller_type', 'Seller') : '';
    $custom_buyer_type = ($buyer_type === 'Other') ? dpcms_get_custom_option('dpcms_custom_buyer_type', 'Buyer') : '';
    $custom_sale_type = ($sale_type === 'Other') ? dpcms_get_custom_option('dpcms_custom_sale_type', 'sale and purchase') : '';

    $product_sold = dpcms_get_custom_option('dpcms_product_sold', 'a Home');

    $formData = array(
        'first_name' => 'John',
        'last_name' => 'Doe',
        'co_signer_first_name' => 'Jane',
        'co_signer_last_name' => 'Smith',
        'model_number' => 'EX123',
        'bedrooms' => 3,
        'bathrooms' => 2,
        'home_sqft' => 1500,
        'deck_sqft' => 200,
        'garage_sqft' => 400,
        'total_purchase_price' => 250000.00,
        'other_provisions' => 'N/A'
    );
    $dynamic_fields = dpcms_get_custom_option('dpcms_dynamic_fields', []);
    $add_house_specifications = dpcms_get_custom_option('dpcms_add_house_specifications', false);
    $terms = dpcms_get_custom_option('dpcms_terms_and_conditions', 'Default terms and conditions...');
    $add_other_provisions = dpcms_get_custom_option('dpcms_add_other_provisions', false);
    $logo = dpcms_get_custom_option('dpcms_logo', '');
    $add_delivery_address = dpcms_get_custom_option('dpcms_add_delivery_address', '');
    $add_pricing_calculator = dpcms_get_custom_option('dpcms_add_pricing_calculator', false);
    ?>

    <div class="container">
        <div class="dpcms-contract-document">
            <div class="dpcms-company-details">
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" alt="Company Logo">
                <?php endif; ?>
                <p><strong><?php echo esc_html($company_details['name']); ?></strong></p>
                <p><strong>Address:</strong>
                    <?php echo esc_html($company_details['street_address'] . ', ' . $company_details['city'] . ', ' . $company_details['state'] . ' ' . $company_details['zip_code']); ?>
                </p>
                <p><strong>Phone:</strong> <?php echo esc_html($company_details['phone']); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html($company_details['email']); ?></p>
                <p><strong>Website:</strong> <?php echo esc_html($company_details['website']); ?></p>
            </div>

            <h2><?php echo esc_html($contract_title); ?></h2>
            <p><?php echo esc_html($contract_header); ?> between
                <strong><?php echo esc_html($company_details['name']); ?></strong> (the
                “<?php echo ($seller_type == 'Other') ? esc_html($custom_seller_type) : esc_html($seller_type); ?>”
                ) and
                <strong><?php echo isset($formData['first_name']) ? esc_html($formData['first_name']) : 'John'; ?>
                    <?php echo isset($formData['last_name']) ? esc_html($formData['last_name']) : 'Doe'; ?></strong> (the
                “<?php echo ($buyer_type == 'Other') ? esc_html($custom_buyer_type) : esc_html($buyer_type); ?>”)
                <?php if (!empty($formData['co_signer_first_name']) && !empty($formData['co_signer_last_name'])): ?>
                    <strong><?php echo esc_html(' and ' . $formData['co_signer_first_name'] . ' ' . $formData['co_signer_last_name']); ?></strong>
                    (the “Co-<?php echo ($buyer_type == 'Other') ? esc_html($custom_buyer_type) : esc_html($buyer_type); ?>”)
                <?php endif; ?>
                for the <?php echo ($sale_type == 'Other') ? esc_html($custom_sale_type) : esc_html($sale_type); ?> of
                <?php echo esc_html($product_sold); ?>
            </p>

            <h3>Billing Address</h3>
            <p><?php echo esc_html($formData['first_name']); ?>
                <?php echo esc_html($formData['last_name']); ?><br>
                <?php echo esc_html(isset($formData['address']) ? $formData['address'] : '456 Example St'); ?><br>
                <?php echo esc_html(isset($formData['city']) ? $formData['city'] : 'Example City'); ?>,
                <?php echo esc_html(isset($formData['state']) ? $formData['state'] : 'EX'); ?>
                <?php echo esc_html(isset($formData['zip']) ? $formData['zip'] : '12345'); ?><br>
                <?php echo esc_html(isset($formData['country']) ? $formData['country'] : 'Example Country'); ?><br>
                Phone: <?php echo esc_html(isset($formData['phone']) ? $formData['phone'] : '123-456-7890'); ?><br>
                Email: <?php echo esc_html(isset($formData['email']) ? $formData['email'] : 'john.doe@example.com'); ?>
            </p>

            <?php if ($add_delivery_address): ?>
                <h3>Delivery Address</h3>
                <p><?php echo esc_html(isset($formData['delivery_address']) ? $formData['delivery_address'] : '789 Example Rd'); ?><br>
                    <?php echo esc_html(isset($formData['delivery_city']) ? $formData['delivery_city'] : 'Example City'); ?>,
                    <?php echo esc_html(isset($formData['delivery_state']) ? $formData['delivery_state'] : 'EX'); ?>
                    <?php echo esc_html(isset($formData['delivery_zip']) ? $formData['delivery_zip'] : '12345'); ?><br>
                    <?php echo esc_html(isset($formData['delivery_country']) ? $formData['delivery_country'] : 'Example Country'); ?>
                </p>
            <?php endif; ?>

            <?php if ($add_house_specifications): ?>
                <h3>House Specifications</h3>
                <ul>
                    <li class="dpcms-tooltip-container">
                        Model #12345
                        <span class="dpcms-tooltip-icon">?
                            <span class="dpcms-tooltiptext">If you leave this field blank in the form to create the
                                contract, it will not show in the final contract. This is just showing an example, if it is
                                included.</span>
                        </span>
                    </li>
                    <li>Bedrooms: <?php echo intval($formData['bedrooms']); ?></li>
                    <li>Bathrooms: <?php echo intval($formData['bathrooms']); ?></li>
                    <li>Interior Sqft: <?php echo intval($formData['home_sqft']); ?> sqft</li>
                    <li>Porch Sqft: <?php echo intval($formData['deck_sqft']); ?> sqft</li>
                    <li>Garage Sqft: <?php echo intval($formData['garage_sqft']); ?> sqft</li>
                </ul>
            <?php endif; ?>

            <?php if ($add_pricing_calculator): ?>
                <h3>Pricing Details</h3>
                <p>
                <p>Original Price: $5000.00</p>
                <p>Freight: $500.00</p>
                <p>Custom Options Price: $1500.00</p>
                <p>Deductions: $1000.00</p>
                <p>Total Purchase Price: $6000.00</p>
                <p>Initial Payment: $2000.00</p>
                <p>Remaining Balance to Start: $750.00</p>
                <p>Total Remaining Balance: $4000.00</p>
                </p>
            <?php endif; ?>

            <?php if (isset($dynamic_fields) && is_array($dynamic_fields)): ?>
                <?php foreach ($dynamic_fields as $dynamic_field): ?>
                    <h3><?php echo esc_html($dynamic_field['header']); ?></h3>
                    <ul>
                        <?php foreach ($dynamic_field['fields'] as $field): ?>
                            <li><?php echo esc_html($field['label']); ?>: Your form input goes here</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php ?>

            <h3>Terms and Conditions</h3>
            <p><?php echo nl2br(esc_html($terms)); ?>
            </p>

            <?php if ($add_other_provisions): ?>
                <h3>Other Provisions</h3>
                <p>Your other provisions will show up here. If you leave it blank in the form, the entire section will not
                    show up in the final contract.</p>
            <?php endif; ?>

            <div class="dpcms-signature-info">
                <h3 class="dpcms-signature-title">Signatures</h3>
                <table class="dpcms-signature-table">
                    <tr>
                        <td class="dpcms-signature-cell">
                            <div class="dpcms-signature-content">
                                <?php if (!empty($formData['signature'])): ?>
                                    <img src="<?php echo esc_url($formData['signature']); ?>"
                                        alt="<?php esc_attr_e('Signature', 'deluxe-wp-contracts'); ?>"
                                        class="dpcms-signature-img">
                                <?php endif; ?>
                                <div class="dpcms-signature-line"></div>
                                <p class="dpcms-signature-text">
                                    <?php echo esc_html($formData['first_name']) . ' ' . esc_html($formData['last_name']); ?><br>
                                    <?php esc_html_e('Client', 'deluxe-wp-contracts'); ?><br>
                                    <?php esc_html_e('Signed on', 'deluxe-wp-contracts'); ?>
                                    <?php echo esc_html(gmdate('Y-m-d')); ?>
                                </p>
                            </div>
                        </td>
                        <td class="dpcms-signature-cell">
                            <div class="dpcms-signature-content">
                                <?php if (!empty($formData['admin_signature'])): ?>
                                    <img src="<?php echo esc_url($formData['admin_signature']); ?>"
                                        alt="<?php esc_attr_e('Admin Signature', 'deluxe-wp-contracts'); ?>"
                                        class="dpcms-signature-img">
                                <?php endif; ?>
                                <div class="dpcms-signature-line"></div>
                                <p class="dpcms-signature-text">
                                    <?php echo esc_html(dpcms_get_signed_by_name()); ?><br>
                                    <?php esc_html_e('Signed on', 'deluxe-wp-contracts'); ?>
                                    <?php echo esc_html(gmdate('Y-m-d')); ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php if (!empty($formData['co_signer_first_name']) && !empty($formData['co_signer_last_name'])): ?>
                        <tr>
                            <td class="dpcms-signature-cell">
                                <div class="dpcms-signature-content">
                                    <?php if (!empty($formData['co_signer_signature'])): ?>
                                        <img src="<?php echo esc_url($formData['co_signer_signature']); ?>"
                                            alt="<?php esc_attr_e('Co-Signer Signature', 'deluxe-wp-contracts'); ?>"
                                            class="dpcms-signature-img">
                                    <?php endif; ?>
                                    <div class="dpcms-signature-line"></div>
                                    <p class="dpcms-signature-text">
                                        <?php echo esc_html($formData['co_signer_first_name']) . ' ' . esc_html($formData['co_signer_last_name']); ?><br>
                                        <?php esc_html_e('Co-signer', 'deluxe-wp-contracts'); ?><br>
                                        <?php esc_html_e('Signed on', 'deluxe-wp-contracts'); ?>
                                        <?php echo esc_html(gmdate('Y-m-d')); ?>
                                    </p>
                                </div>
                            </td>
                            <td class="dpcms-signature-cell">
                                <div class="dpcms-empty-cell">
                                    <!-- Empty cell to match the layout -->
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
            <div id="dpcms-footer" class="dpcms-premium-content dpcms-tooltip-container"></div>
        </div>
    </div>

    <?php
}