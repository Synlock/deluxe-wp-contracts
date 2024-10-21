<?php
// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

$logo = dpcms_get_custom_option('dpcms_contract_logo');
if ($logo) {
    echo '<div style="text-align: center; margin-bottom: 20px;"><img src="' . esc_url($logo) . '" style="max-width: 200px;" /></div>';
}
$add_house_specs = dpcms_get_custom_option('dpcms_add_house_specifications', false);
$add_pricing_calculator = dpcms_get_custom_option('dpcms_add_pricing_calculator', false);
$add_other_provisions = dpcms_get_custom_option('dpcms_add_other_provisions', false);
$dynamic_fields = dpcms_get_custom_option('dpcms_dynamic_fields', []);
$contract_title = dpcms_get_custom_option('dpcms_contract_title', 'Agreement');

$add_co_signer_information = dpcms_get_custom_option('dpcms_add_co_signer_information', '');
$add_delivery_address = dpcms_get_custom_option('dpcms_add_delivery_address', '');

if (!$dynamic_fields) {
    $dynamic_fields = [];
}

$allowed_html = array(
    'input' => array(
        'type' => true,
        'class' => true,
        'id' => true,
        'name' => true,
        'value' => true,
        'required' => true,
        'readonly' => true,
        'checked' => true,
    ),
    'label' => array(
        'for' => true,
        'class' => true,
    ),
    'textarea' => array(
        'name' => true,
        'id' => true,
        'cols' => true,
        'rows' => true,
        'class' => true,
    ),
    'select' => array(
        'name' => true,
        'id' => true,
        'class' => true,
    ),
    'option' => array(
        'value' => true,
        'selected' => true,
    ),
    'button' => array(
        'type' => true,
        'class' => true,
        'id' => true,
        'data-list-id' => true,
        'data-index' => true,
        'data-field-index' => true
    ),
    'div' => array(
        'class' => true,
        'id' => true,
    ),
    'span' => array(
        'class' => true,
        'id' => true,
    ),
    'ol' => array(
        'class' => true,
        'id' => true,
    ),
    'li' => array(
        'class' => true,
    ),
    'a' => array(
        'href' => true,
        'class' => true,
        'id' => true,
        'target' => true,
    ),
);

function dpcms_render_add_button($index, $field_index)
{
    return '<button type="button" class="dpcms-add-dynamic-field" data-list-id="dpcms-dynamic_fields_list_' . $index . '" data-index="' . $index . '" data-field-index="' . $field_index . '">Add New List Item</button>';
}

function dpcms_render_option_input($index, $field_index, $option = [])
{
    $value = isset($option['label']) ? esc_attr($option['label']) : '';
    return '<input type="text" name="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][label]" value="' . $value . '" placeholder="Option Name" required>';
}

function dpcms_render_requires_text_checkbox($index, $field_index, $option = [])
{
    $checked = isset($option['requires_text']) && $option['requires_text'] == '1' ? 'checked' : '';
    return '<div class="dpcms-checkbox-wrapper">
        <label for="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][requires_text]">Requires Text</label>
        <input type="checkbox" name="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][requires_text]" class="dpcms-custom-option-requires-text" ' . $checked . '>
        </div>';
}

function dpcms_render_is_price_checkbox($index, $field_index, $option = [])
{
    $checked = isset($option['is_price']) && $option['is_price'] == '1' ? 'checked' : '';
    $display_style = $checked ? '' : 'style="display:none"';
    return '<div class="dpcms-checkbox-wrapper" ' . $display_style . '>
        <label for="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][is_price]">Is Price</label>
        <input type="checkbox" name="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][is_price]" class="dpcms-custom-option-is-price" ' . $checked . '>
        </div>';
}

function dpcms_render_value_input($index, $field_index, $option = [])
{
    $value = isset($option['value']) ? esc_attr($option['value']) : '';
    return '<input type="text" name="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][value]" value="' . $value . '" placeholder="Enter Text or Number">';
}

function dpcms_render_remove_button()
{
    return '<button type="button" class="dpcms-remove-custom-option">Remove</button>';
}

function dpcms_render_option($index, $field_index, $option = [])
{
    $output = '<li>
        <div class="dpcms-custom-option-li">';
    $output .= dpcms_render_option_input($index, $field_index, $option);
    $output .= dpcms_render_requires_text_checkbox($index, $field_index, $option);
    $output .= dpcms_render_is_price_checkbox($index, $field_index, $option);
    $output .= dpcms_render_value_input($index, $field_index, $option);
    $output .= dpcms_render_remove_button();
    $output .= '</div></li>';

    return $output;
}

function dpcms_render_options_list($options, $index)
{
    $output = '';
    foreach ($options as $field_index => $option) {
        $output .= dpcms_render_option($index, $field_index, $option);
    }
    return $output;
}

function dpcms_render_dynamic_field($field, $index, $form_data)
{
    $field_index = isset($form_data['dynamic_fields'][$index]['fields']) ? count($form_data['dynamic_fields'][$index]['fields']) : 0;

    if ($field['type'] == 'list') {
        $label_id = 'dpcms_dynamic_fields_' . $index . '_fields_' . $field_index . '_label';
        $list_name_value = isset($field['label']) ? esc_attr($field['label'] . ':') : '';

        $output = '<input type="text" class="dpcms-label-input" id="' . $label_id . '" name="dpcms_dynamic_fields[' . $index . '][fields][0][label]" value="' . $list_name_value . '" placeholder="Enter list name" required>';

        $output .= dpcms_render_add_button($index, $field_index);
        $output .= '<ol class="dpcms-dynamic_fields_list" id="dpcms-dynamic_fields_list_' . $index . '" ' . (!empty($field['required']) ? 'required' : '') . '>';

        if (isset($form_data['dynamic_fields'][$index]['fields'])) {
            $options = $form_data['dynamic_fields'][$index]['fields'];
            $output .= dpcms_render_options_list($options, $index);
        }

        $output .= '</ol>';

        return $output;
    }

    return '';
}


function dpcms_render_text_field($field, $index, $field_index, $value)
{
    $label_id = 'dpcms_dynamic_fields_' . $index . '_fields_' . $field_index . '_label';
    return '<input type="text" class="dpcms-label-input" id="' . $label_id . '" name="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][label]" value="' . esc_attr($field['label']) . ':' . (!empty($field['required']) ? ' *' : '') . '" readonly>
        <input type="' . esc_attr($field['type']) . '" id="dpcms_dynamic_fields_' . $index . '_fields_' . $field_index . '_value" name="dpcms_dynamic_fields[' . $index . '][fields][' . $field_index . '][value]" ' . (!empty($field['required']) ? 'required' : '') . ' value="' . esc_attr($value) . '">';
}

?>
<div id="dpcms-container">
    <div class="dpcms-contract-form">
        <h2><?php echo esc_html($contract_title) ?></h2>
        <p>Fields marked with * are required.</p>
        <form id="dpcms-contract-form" action="" method="post">
            <?php wp_nonce_field('dpcms_contract_form_nonce', 'dpcms_contract_form_nonce'); ?>
            <h3>Billing Details</h3>
            <label for="dpcms_first_name">First Name: *</label>
            <input type="text" id="dpcms_first_name" name="dpcms_first_name" required
                value="<?php echo isset($form_data['first_name']) ? esc_attr($form_data['first_name']) : ''; ?>">

            <label for="dpcms_last_name">Last Name: *</label>
            <input type="text" id="dpcms_last_name" name="dpcms_last_name" required
                value="<?php echo isset($form_data['last_name']) ? esc_attr($form_data['last_name']) : ''; ?>">

            <label for="dpcms_address">Street Address: *</label>
            <input type="text" id="dpcms_address" name="dpcms_address" required
                value="<?php echo isset($form_data['address']) ? esc_attr($form_data['address']) : ''; ?>">

            <label for="dpcms_city">City: *</label>
            <input type="text" id="dpcms_city" name="dpcms_city" required
                value="<?php echo isset($form_data['city']) ? esc_attr($form_data['city']) : ''; ?>">

            <label for="dpcms_state">State: *</label>
            <input type="text" id="dpcms_state" name="dpcms_state" required
                value="<?php echo isset($form_data['state']) ? esc_attr($form_data['state']) : ''; ?>">

            <label for="dpcms_zip">Zip Code: *</label>
            <input type="number" id="dpcms_zip" name="dpcms_zip" required
                value="<?php echo isset($form_data['zip']) ? esc_attr($form_data['zip']) : ''; ?>">

            <label for="dpcms_country">Country: *</label>
            <input type="text" id="dpcms_country" name="dpcms_country" required
                value="<?php echo isset($form_data['country']) ? esc_attr($form_data['country']) : ''; ?>">

            <label for="dpcms_phone">Phone Number: *</label>
            <input type="tel" id="dpcms_phone" name="dpcms_phone" required
                value="<?php echo isset($form_data['phone']) ? esc_attr($form_data['phone']) : ''; ?>">

            <label for="dpcms_email">Email Address: *</label>
            <input type="email" id="dpcms_email" name="dpcms_email" required
                value="<?php echo isset($form_data['email']) ? esc_attr($form_data['email']) : ''; ?>">

            <?php if ($add_delivery_address): ?>
                <h3>Delivery Address</h3>
                <div class="dpcms-checkbox-container">
                    <label for="dpcms_same_as_billing">Same as Billing Address</label>
                    <input type="checkbox" id="dpcms_same_as_billing" name="dpcms_same_as_billing">
                </div>

                <label for="dpcms_delivery_address">Street Address: *</label>
                <input type="text" id="dpcms_delivery_address" name="dpcms_delivery_address" required
                    value="<?php echo isset($form_data['delivery_address']) ? esc_attr($form_data['delivery_address']) : ''; ?>">

                <label for="dpcms_delivery_city">City: *</label>
                <input type="text" id="dpcms_delivery_city" name="dpcms_delivery_city" required
                    value="<?php echo isset($form_data['delivery_city']) ? esc_attr($form_data['delivery_city']) : ''; ?>">

                <label for="dpcms_delivery_state">State: *</label>
                <input type="text" id="dpcms_delivery_state" name="dpcms_delivery_state" required
                    value="<?php echo isset($form_data['delivery_state']) ? esc_attr($form_data['delivery_state']) : ''; ?>">

                <label for="dpcms_delivery_zip">Zip Code: *</label>
                <input type="number" id="dpcms_delivery_zip" name="dpcms_delivery_zip" required
                    value="<?php echo isset($form_data['delivery_zip']) ? esc_attr($form_data['delivery_zip']) : ''; ?>">

                <label for="dpcms_delivery_country">Country: *</label>
                <input type="text" id="dpcms_delivery_country" name="dpcms_delivery_country" required
                    value="<?php echo isset($form_data['delivery_country']) ? esc_attr($form_data['delivery_country']) : ''; ?>">
            <?php endif; ?>

            <?php if ($add_co_signer_information): ?>
                <h3>Co-Signer Information (Optional)</h3>
                <label for="dpcms_co_signer_first_name">Co-Signer First Name:</label>
                <input type="text" id="dpcms_co_signer_first_name" name="dpcms_co_signer_first_name"
                    value="<?php echo isset($form_data['co_signer_first_name']) ? esc_attr($form_data['co_signer_first_name']) : ''; ?>">

                <label for="dpcms_co_signer_last_name">Co-Signer Last Name:</label>
                <input type="text" id="dpcms_co_signer_last_name" name="dpcms_co_signer_last_name"
                    value="<?php echo isset($form_data['co_signer_last_name']) ? esc_attr($form_data['co_signer_last_name']) : ''; ?>">

                <label for="dpcms_co_signer_phone">Co-Signer Phone Number:</label>
                <input type="tel" id="dpcms_co_signer_phone" name="dpcms_co_signer_phone"
                    value="<?php echo isset($form_data['co_signer_phone']) ? esc_attr($form_data['co_signer_phone']) : ''; ?>">

                <label for="dpcms_co_signer_email">Co-Signer Email Address:</label>
                <input type="email" id="dpcms_co_signer_email" name="dpcms_co_signer_email"
                    value="<?php echo isset($form_data['co_signer_email']) ? esc_attr($form_data['co_signer_email']) : ''; ?>">
            <?php endif; ?>

            <?php if ($add_house_specs): ?>
                <h3>House Specifications</h3>
                <label for="dpcms_model_number">Model Number:</label>
                <div class="dpcms-model-number-input">
                    <input type="text" id="dpcms_model_number" name="dpcms_model_number" pattern="\d{1,5}" maxlength="5"
                        title="Only numbers are allowed and up to 5 digits"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,5)"
                        value="<?php echo isset($form_data['model_number']) ? esc_attr($form_data['model_number']) : ''; ?>">
                </div>
                <label for="dpcms_bedrooms">Bedrooms: *</label>
                <input type="number" id="dpcms_bedrooms" name="dpcms_bedrooms" max="99"
                    value="<?php echo isset($form_data['bedrooms']) ? esc_attr($form_data['bedrooms']) : ''; ?>" required>

                <label for="dpcms_bathrooms">Bathrooms: *</label>
                <input type="number" id="dpcms_bathrooms" name="dpcms_bathrooms" max="99"
                    value="<?php echo isset($form_data['bathrooms']) ? esc_attr($form_data['bathrooms']) : ''; ?>" required>

                <label for="dpcms_home_sqft">Interior Sqft: *</label>
                <input type="number" id="dpcms_home_sqft" name="dpcms_home_sqft"
                    value="<?php echo isset($form_data['home_sqft']) ? esc_attr($form_data['home_sqft']) : ''; ?>" required>

                <label for="dpcms_deck_sqft">Porch Sqft: *</label>
                <input type="number" id="dpcms_deck_sqft" name="dpcms_deck_sqft"
                    value="<?php echo isset($form_data['deck_sqft']) ? esc_attr($form_data['deck_sqft']) : ''; ?>" required>

                <label for="dpcms_garage_sqft">Garage Sqft: *</label>
                <input type="number" id="dpcms_garage_sqft" name="dpcms_garage_sqft"
                    value="<?php echo isset($form_data['garage_sqft']) ? esc_attr($form_data['garage_sqft']) : ''; ?>"
                    required>
            <?php endif; ?>

            <?php foreach ($dynamic_fields as $index => $field_group): ?>
                <h3><?php echo esc_html($field_group['header']); ?></h3>
                <?php foreach ($field_group['fields'] as $field_index => $field): ?>
                    <?php
                    $value = isset($form_data['dynamic_fields'][$index]['fields'][$field_index]['value']) ? $form_data['dynamic_fields'][$index]['fields'][$field_index]['value'] : '';
                    ?>
                    <?php if ($field['type'] == 'list'): ?>
                        <?php echo wp_kses(dpcms_render_dynamic_field($field, $index, $form_data), $allowed_html); ?>
                    <?php else: ?>
                        <?php echo wp_kses(dpcms_render_text_field($field, $index, $field_index, $value), $allowed_html); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>


            <?php if ($add_pricing_calculator): ?>
                <h3>Pricing</h3>
                <label for="dpcms_original_price">Original Price: *</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_original_price" name="dpcms_original_price" step="0.01" required
                            value="<?php echo isset($form_data['original_price']) ? esc_attr($form_data['original_price']) : ''; ?>">
                    </div>
                </div>

                <label for="dpcms_freight">Freight: *</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_freight" name="dpcms_freight" required
                            value="<?php echo isset($form_data['freight']) ? esc_attr($form_data['freight']) : ''; ?>">
                    </div>
                </div>

                <label for="dpcms_custom_options_price">Custom Options Price: *</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_custom_options_price" name="dpcms_custom_options_price" required
                            value="<?php echo isset($form_data['custom_options_price']) ? esc_attr($form_data['custom_options_price']) : ''; ?>">
                    </div>
                </div>

                <label for="dpcms_deductions">Deductions: *</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_deductions" name="dpcms_deductions" required
                            value="<?php echo isset($form_data['deductions']) ? esc_attr($form_data['deductions']) : ''; ?>">
                    </div>
                </div>

                <label for="dpcms_total_purchase_price">Total Purchase Price:</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_total_purchase_price" name="dpcms_total_purchase_price" required
                            readonly
                            value="<?php echo isset($form_data['total_purchase_price']) ? esc_attr($form_data['total_purchase_price']) : ''; ?>">
                    </div>
                </div>

                <label for="dpcms_initial_payment">Initial Payment: *</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_initial_payment" name="dpcms_initial_payment" required
                            value="<?php echo isset($form_data['initial_payment']) ? esc_attr($form_data['initial_payment']) : ''; ?>">
                    </div>
                </div>

                <label for="dpcms_remaining_balance_start">Remaining Balance to Start:</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_remaining_balance_start" name="dpcms_remaining_balance_start"
                            required readonly
                            value="<?php echo isset($form_data['remaining_balance_start']) ? esc_attr($form_data['remaining_balance_start']) : ''; ?>">
                    </div>
                </div>

                <label for="dpcms_total_remaining_balance">Total Remaining Balance:</label>
                <div class="dpcms-price-input-wrapper">
                    <span>$</span>
                    <div class="dpcms-price-input">
                        <input type="number" id="dpcms_total_remaining_balance" name="dpcms_total_remaining_balance"
                            required readonly
                            value="<?php echo isset($form_data['total_remaining_balance']) ? esc_attr($form_data['total_remaining_balance']) : ''; ?>">
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($add_other_provisions): ?>
                <h3>Other Provisions</h3>
                <label for="dpcms_other_provisions">Other Provisions:</label>
                <textarea id="dpcms_other_provisions" name="dpcms_other_provisions" rows="5"
                    style="width: 100%;"><?php echo isset($form_data['other_provisions']) ? esc_textarea($form_data['other_provisions']) : ''; ?></textarea>
            <?php endif; ?>

            <button type="button" id="dpcms-review-button">Review Info</button>
        </form>

        <div id="dpcms-review-section" style="display: none;">
            <h3>Review Your Information</h3>
            <div id="dpcms-review-content"></div>
            <button type="button" id="dpcms-edit-button">Edit</button>
            <button type="button" id="dpcms-send-email-button">Send Emails to Sign</button>
            <button type="button" id="dpcms-download-no-email-button">Download Without Emailing</button>
        </div>
    </div>
</div>