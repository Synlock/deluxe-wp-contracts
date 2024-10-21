<?php
// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

function dpcms_contract_form_shortcode()
{
    if (is_admin()) {
        return '';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
        check_admin_referer('dpcms_contract_form_nonce', 'dpcms_contract_form_nonce');
        dpcms_process_form();
    } else {
        return dpcms_display_form();
    }
}

function dpcms_display_form()
{
    // Check user capabilities securely
    dpcms_check_capabilities('create_contracts', true);

    ob_start();

    // Sanitize $_GET['edit_contract']
    $docId = isset($_GET['edit_contract']) ? intval($_GET['edit_contract']) : 0;

    // Fetch form data based on the document ID and ensure it's sanitized properly
    $form_data = $docId > 0 ? dpcms_get_custom_option('contract_form_' . $docId) : [];

    // Maybe unserialize dynamic fields if present
    if (isset($form_data['dynamic_fields'])) {
        $form_data['dynamic_fields'] = maybe_unserialize($form_data['dynamic_fields']);
    }

    // Process form submission if method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify the nonce
        if (!isset($_POST['dpcms_contract_form_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dpcms_contract_form_nonce'])), 'dpcms_contract_form_nonce')) {
            wp_die('Nonce verification failed.');
        }

        // Initialize $form_data array
        $form_data = [];

        // Sanitize specific fields from the form
        $form_data['first_name'] = isset($_POST['dpcms_first_name']) ? sanitize_text_field(wp_unslash($_POST['dpcms_first_name'])) : '';
        $form_data['last_name'] = isset($_POST['dpcms_last_name']) ? sanitize_text_field(wp_unslash($_POST['dpcms_last_name'])) : '';
        $form_data['address'] = isset($_POST['dpcms_address']) ? sanitize_text_field(wp_unslash($_POST['dpcms_address'])) : '';
        $form_data['city'] = isset($_POST['dpcms_city']) ? sanitize_text_field(wp_unslash($_POST['dpcms_city'])) : '';
        $form_data['state'] = isset($_POST['dpcms_state']) ? sanitize_text_field(wp_unslash($_POST['dpcms_state'])) : '';
        $form_data['zip'] = isset($_POST['dpcms_zip']) ? sanitize_text_field(wp_unslash($_POST['dpcms_zip'])) : '';
        $form_data['country'] = isset($_POST['dpcms_country']) ? sanitize_text_field(wp_unslash($_POST['dpcms_country'])) : '';
        $form_data['phone'] = isset($_POST['dpcms_phone']) ? sanitize_text_field(wp_unslash($_POST['dpcms_phone'])) : '';
        $form_data['email'] = isset($_POST['dpcms_email']) ? sanitize_email(wp_unslash($_POST['dpcms_email'])) : '';

        // Handle delivery address fields
        if (isset($_POST['dpcms_delivery_address'])) {
            $form_data['delivery_address'] = sanitize_text_field(wp_unslash($_POST['dpcms_delivery_address']));
            $form_data['delivery_city'] = sanitize_text_field(wp_unslash($_POST['dpcms_delivery_city']));
            $form_data['delivery_state'] = sanitize_text_field(wp_unslash($_POST['dpcms_delivery_state']));
            $form_data['delivery_zip'] = sanitize_text_field(wp_unslash($_POST['dpcms_delivery_zip']));
            $form_data['delivery_country'] = sanitize_text_field(wp_unslash($_POST['dpcms_delivery_country']));
        }

        // Handle co-signer fields if present
        if (isset($_POST['dpcms_co_signer_first_name'])) {
            $form_data['co_signer_first_name'] = sanitize_text_field(wp_unslash($_POST['dpcms_co_signer_first_name']));
            $form_data['co_signer_last_name'] = sanitize_text_field(wp_unslash($_POST['dpcms_co_signer_last_name']));
            $form_data['co_signer_phone'] = sanitize_text_field(wp_unslash($_POST['dpcms_co_signer_phone']));
            $form_data['co_signer_email'] = sanitize_email(wp_unslash($_POST['dpcms_co_signer_email']));
        }

        // Handle dynamic fields
        if (isset($_POST['dpcms_dynamic_fields']) && is_array($_POST['dpcms_dynamic_fields'])) {
            $form_data['dynamic_fields'] = array_map(function ($field_group) {
                return array_map(function ($field) {
                    return [
                        'label' => sanitize_text_field(wp_unslash($field['label'])),
                        'value' => sanitize_text_field(wp_unslash($field['value'])),
                        'requires_text' => isset($field['requires_text']) ? sanitize_text_field(wp_unslash($field['requires_text'])) : '',
                        'is_price' => isset($field['is_price']) ? sanitize_text_field(wp_unslash($field['is_price'])) : '',
                    ];
                }, $field_group['fields']);
            }, $_POST['dpcms_dynamic_fields']);
        }

        // Save or process the form data here (e.g., save to the database)
        // dpcms_set_custom_option('contract_form_' . $docId, $form_data);

        // Optional: Redirect or display a success message
    }

    // Include the form template file
    include plugin_dir_path(__FILE__) . '../templates/dpcms-form-page.php';

    return ob_get_clean();
}


function dpcms_process_form()
{
    if (!isset($_POST['dpcms_contract_form_nonce'])) {
        error_log('Nonce not set');
        wp_send_json_error('Nonce not set');
        return;
    }

    if (!wp_verify_nonce($_POST['dpcms_contract_form_nonce'], 'dpcms_contract_form_nonce')) {
        error_log('Invalid nonce');
        wp_send_json_error('Invalid nonce');
        return;
    }

    if (!isset($_POST['action_type']) || !isset($_POST['form_data'])) {
        wp_send_json_error('Invalid request');
    }

    $action_type = sanitize_text_field($_POST['action_type']);
    parse_str($_POST['form_data'], $form_data);

    $uniqueId = dpcms_generate_numeric_unique_id();
    // Store form data initially
    dpcms_store_form_data($uniqueId, $form_data);

    if ($action_type === 'send_email') {
        dpcms_send_signature_link($uniqueId, $form_data);
        $message = 'A link to sign the document has been sent to the email(s) provided in the form.';
        wp_send_json_success(['message' => $message]);
    } elseif ($action_type === 'direct_download') {
        $file_path = dpcms_generate_contract($uniqueId, true);

        $upload_dir = wp_upload_dir();
        $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);

        $message = 'Contract generated successfully. <a href="' . esc_url($file_url) . '" target="_blank">View the contract</a>.';
        wp_send_json_success(['message' => $message]);
    }
}

function dpcms_store_form_data($uniqueId, $formData)
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;

    $dynamic_fields_headers = dpcms_get_custom_option('dpcms_dynamic_fields', []);
    $dynamic_fields = isset($formData['dpcms_dynamic_fields']) ? $formData['dpcms_dynamic_fields'] : [];

    $processed_dynamic_fields = [];
    foreach ($dynamic_fields_headers as $index => $header_group) {
        $field_group_processed = [
            'header' => $header_group['header'],
            'labels' => [],
            'fields' => []
        ];

        if (isset($header_group['fields'])) {
            foreach ($header_group['fields'] as $field) {
                if (isset($field['type']) && isset($field['label'])) {
                    $field_group_processed['labels'][] = [
                        'type' => $field['type'],
                        'label' => $field['label']
                    ];
                }
            }
        }

        if (isset($dynamic_fields[$index])) {
            foreach ($dynamic_fields[$index]['fields'] as $field) {
                $label = str_replace(':', '', $field['label'] ?? '');
                $field_processed = [
                    'label' => $label,
                    'requires_text' => !empty($field['requires_text']),
                    'is_price' => !empty($field['is_price']),
                    'value' => isset($field['value']) ? $field['value'] : '',
                ];

                $field_group_processed['fields'][] = $field_processed;
            }
        }

        $processed_dynamic_fields[] = $field_group_processed;
    }

    $serialized_dynamic_fields = maybe_serialize($processed_dynamic_fields);

    $data = array(
        'first_name' => isset($formData['dpcms_first_name']) ? sanitize_text_field($formData['dpcms_first_name']) : '',
        'last_name' => isset($formData['dpcms_last_name']) ? sanitize_text_field($formData['dpcms_last_name']) : '',
        'address' => isset($formData['dpcms_address']) ? sanitize_text_field($formData['dpcms_address']) : '',
        'city' => isset($formData['dpcms_city']) ? sanitize_text_field($formData['dpcms_city']) : '',
        'state' => isset($formData['dpcms_state']) ? sanitize_text_field($formData['dpcms_state']) : '',
        'zip' => isset($formData['dpcms_zip']) ? intval($formData['dpcms_zip']) : 0,
        'country' => isset($formData['dpcms_country']) ? sanitize_text_field($formData['dpcms_country']) : '',
        'phone' => isset($formData['dpcms_phone']) ? sanitize_text_field($formData['dpcms_phone']) : '',
        'email' => isset($formData['dpcms_email']) ? sanitize_email($formData['dpcms_email']) : '',
        'co_signer_first_name' => isset($formData['dpcms_co_signer_first_name']) ? sanitize_text_field($formData['dpcms_co_signer_first_name']) : '',
        'co_signer_last_name' => isset($formData['dpcms_co_signer_last_name']) ? sanitize_text_field($formData['dpcms_co_signer_last_name']) : '',
        'co_signer_phone' => isset($formData['dpcms_co_signer_phone']) ? sanitize_text_field($formData['dpcms_co_signer_phone']) : '',
        'co_signer_email' => isset($formData['dpcms_co_signer_email']) ? sanitize_email($formData['dpcms_co_signer_email']) : '',
        'delivery_address' => isset($formData['dpcms_delivery_address']) ? sanitize_text_field($formData['dpcms_delivery_address']) : '',
        'delivery_city' => isset($formData['dpcms_delivery_city']) ? sanitize_text_field($formData['dpcms_delivery_city']) : '',
        'delivery_state' => isset($formData['dpcms_delivery_state']) ? sanitize_text_field($formData['dpcms_delivery_state']) : '',
        'delivery_zip' => isset($formData['dpcms_delivery_zip']) ? intval($formData['dpcms_delivery_zip']) : 0,
        'delivery_country' => isset($formData['dpcms_delivery_country']) ? sanitize_text_field($formData['dpcms_delivery_country']) : '',
        'dynamic_fields' => $serialized_dynamic_fields,
        'model_number' => isset($formData['dpcms_model_number']) ? sanitize_text_field($formData['dpcms_model_number']) : '',
        'bedrooms' => isset($formData['dpcms_bedrooms']) ? intval($formData['dpcms_bedrooms']) : 0,
        'bathrooms' => isset($formData['dpcms_bathrooms']) ? intval($formData['dpcms_bathrooms']) : 0,
        'home_sqft' => isset($formData['dpcms_home_sqft']) ? intval($formData['dpcms_home_sqft']) : 0,
        'deck_sqft' => isset($formData['dpcms_deck_sqft']) ? intval($formData['dpcms_deck_sqft']) : 0,
        'garage_sqft' => isset($formData['dpcms_garage_sqft']) ? intval($formData['dpcms_garage_sqft']) : 0,
        'custom_options' => isset($formData['dpcms_custom_options']) ? sanitize_textarea_field($formData['dpcms_custom_options']) : '',
        'original_price' => isset($formData['dpcms_original_price']) ? floatval($formData['dpcms_original_price']) : 0.00,
        'freight' => isset($formData['dpcms_freight']) ? floatval($formData['dpcms_freight']) : 0.00,
        'custom_options_price' => isset($formData['dpcms_custom_options_price']) ? floatval($formData['dpcms_custom_options_price']) : 0.00,
        'deductions' => isset($formData['dpcms_deductions']) ? floatval($formData['dpcms_deductions']) : 0.00,
        'total_purchase_price' => isset($formData['dpcms_total_purchase_price']) ? floatval($formData['dpcms_total_purchase_price']) : 0.00,
        'initial_payment' => isset($formData['dpcms_initial_payment']) ? floatval($formData['dpcms_initial_payment']) : 0.00,
        'remaining_balance_start' => isset($formData['dpcms_remaining_balance_start']) ? floatval($formData['dpcms_remaining_balance_start']) : 0.00,
        'total_remaining_balance' => isset($formData['dpcms_total_remaining_balance']) ? floatval($formData['dpcms_total_remaining_balance']) : 0.00,
        'terms_and_conditions' => isset($formData['dpcms_terms_and_conditions']) ? sanitize_textarea_field($formData['dpcms_terms_and_conditions']) : '',
        'other_provisions' => isset($formData['dpcms_other_provisions']) ? sanitize_textarea_field($formData['dpcms_other_provisions']) : '',
        'document_id' => intval($uniqueId),
        'primary_ip_address' => 'Unknown',
        'cosigner_ip_address' => 'Unknown',
        'primary_sign_date_time' => '',
        'co_signer_sign_date_time' => '',
        'admin_sign_date_time' => '',
    );

    // Perform the database insertion
    $wpdb->insert(
        $table_name,
        array(
            'wp_user_created_contract' => $current_user_id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'generated_date' => gmdate('Y-m-d H:i:s'),
            'pdf_path' => '',
            'document_id' => $uniqueId,
        ),
        array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );

    // Set custom option with caching
    wp_cache_set('contract_form_' . $uniqueId, $data, 'dpcms', 30 * DAY_IN_SECONDS);
    dpcms_set_custom_option('contract_form_' . $uniqueId, $data, 30 * DAY_IN_SECONDS);
}

function dpcms_handle_document_signature()
{
    // Only handle the form on the specific template page
    if (!is_page_template('dpcms-sign-contract-page.php')) {
        return;
    }

    // Ensure doc_id and signer are present in GET, and sanitize them
    if (!isset($_GET['doc_id']) || !isset($_GET['signer'])) {
        wp_die(esc_html__('Invalid or missing document ID or signer.', 'dpcms'));
    }

    // Sanitize GET parameters
    $docId = sanitize_text_field($_GET['doc_id']);
    $signer = sanitize_text_field($_GET['signer']);

    // Fetch form data securely
    $formData = dpcms_get_custom_option('contract_form_' . $docId);

    // Check if form data exists
    if (!$formData) {
        wp_die(esc_html__('Invalid or expired document ID.', 'dpcms'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;

    // Update contract status to opened
    $wpdb->update(
        $table_name,
        array('contract_opened' => true),
        array('document_id' => $docId),
        array('%d'),  // Data type for the value being updated
        array('%d')   // Data type for the WHERE clause
    );

    // Handle POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify the nonce
        check_admin_referer('dpcms_document_signature_nonce', 'dpcms_document_signature_nonce');

        // Prepare to update signer-specific fields
        $signer_field = '';
        $update_contract_signed = false;

        // Handle Primary Signer
        if ($signer === 'primary' && isset($_POST['signature']) && empty($formData['signature'])) {
            $formData['primary_ip_address'] = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
            $formData['primary_sign_date_time'] = gmdate('Y-m-d H:i:s');
            $formData['signature'] = sanitize_text_field($_POST['signature']);
            $signer_field = 'primary';

            // Update primary signature in the database
            $wpdb->update(
                $table_name,
                array('primary_signature' => $formData['signature']),
                array('document_id' => $docId),
                array('%s'),  // Data type for the value being updated
                array('%d')   // Data type for the WHERE clause
            );

            // Notify admin if no co-signer or co-signer already signed
            if (empty($formData['co_signer_email']) || !empty($formData['co_signer_signature'])) {
                $update_contract_signed = true;
                dpcms_notify_admin_to_sign($formData);
            }
        }

        // Handle Co-Signer
        elseif ($signer === 'co-signer' && isset($_POST['co_signer_signature']) && empty($formData['co_signer_signature'])) {
            $formData['cosigner_ip_address'] = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
            $formData['co_signer_signature'] = sanitize_text_field($_POST['co_signer_signature']);
            $formData['co_signer_sign_date_time'] = gmdate('Y-m-d H:i:s');
            $signer_field = 'co-signer';

            // Update co-signer signature in the database
            $wpdb->update(
                $table_name,
                array('co_signer_signature' => $formData['co_signer_signature']),
                array('document_id' => $docId),
                array('%s'),  // Data type for the value being updated
                array('%d')   // Data type for the WHERE clause
            );

            // Notify admin if both primary and co-signer have signed
            if (!empty($formData['signature']) || empty($formData['co_signer_email'])) {
                $update_contract_signed = true;
                dpcms_notify_admin_to_sign($formData);
            }
        }

        // Handle Admin Signature
        elseif ($signer === 'admin' && isset($_POST['admin_signature']) && empty($formData['admin_signature'])) {
            $formData['admin_signature'] = sanitize_text_field($_POST['admin_signature']);
            $formData['admin_sign_date_time'] = gmdate('Y-m-d H:i:s');
            $signer_field = 'admin';

            dpcms_set_custom_option('contract_form_' . $docId, $formData, 30 * DAY_IN_SECONDS);

            // Update admin signature in the database
            $wpdb->update(
                $table_name,
                array('admin_signature' => $formData['admin_signature']),
                array('document_id' => $docId),
                array('%s'),  // Data type for the value being updated
                array('%d')   // Data type for the WHERE clause
            );

            // Generate PDF and send emails after admin signs
            $outputPath = dpcms_generate_contract($docId, false);

            dpcms_send_signed_document($formData, $outputPath);

            echo esc_html__('The finalized contract has been sent to all parties.', 'dpcms');
        }

        // Update the contract_signed field if necessary
        if ($update_contract_signed) {
            $updated = $wpdb->update(
                $table_name,
                array(
                    'contract_signed' => true,
                    'signed_date' => gmdate('Y-m-d H:i:s')
                ),
                array('document_id' => $docId),
                array('%d', '%s'),  // Data types for the values being updated
                array('%d')         // Data type for the WHERE clause
            );

            if ($updated === false) {
                // Log error if update fails
                error_log("Failed to update contract_signed for document ID: $docId");
            }
        }

        // Redirect based on signer
        if ($signer_field === 'primary' || $signer_field === 'co-signer') {
            dpcms_set_custom_option('contract_form_' . $docId, $formData, 30 * DAY_IN_SECONDS);
            dpcms_redirect_to_thank_you_page();
        } else {
            // Redirect admin to home page
            wp_redirect(home_url());
            exit;
        }
    }

    // Pass data to the template
    set_query_var('formData', $formData);
    set_query_var('signer', $signer);
}


function dpcms_send_signature_link($docId, $form_data)
{
    $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    if (!$consent) {
        wp_send_json_error('Please mark the consent to privacy policy checkbox on the settings page.');
        return;
    }

    // Set the email content type to HTML
    add_filter('wp_mail_content_type', 'dpcms_set_html_content_type');

    $email = sanitize_email($form_data['dpcms_email']);
    $firstName = sanitize_text_field($form_data['dpcms_first_name']);
    $lastName = sanitize_text_field($form_data['dpcms_last_name']);
    $coSignerEmail = isset($form_data['dpcms_co_signer_email']) ? sanitize_email($form_data['dpcms_co_signer_email']) : '';
    $coSignerFirstName = isset($form_data['dpcms_co_signer_first_name']) ? sanitize_text_field($form_data['dpcms_co_signer_first_name']) : '';
    $coSignerLastName = isset($form_data['dpcms_co_signer_last_name']) ? sanitize_text_field($form_data['dpcms_co_signer_last_name']) : '';

    $from_email = dpcms_get_custom_option('dpcms_from_email', get_option('admin_email'));
    $from_name = dpcms_get_custom_option('dpcms_from_name', dpcms_get_site_domain_name());
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . sanitize_email($from_email) . '>'
    );

    $primarySignatureLink = add_query_arg(array('doc_id' => $docId, 'signer' => 'primary'), get_permalink(get_page_by_path('sign-contract')));
    $primaryTrackingUrl = add_query_arg(array('track_email_open' => '1', 'doc_id' => $docId), site_url('/email-tracking-pixel/'));

    $subject = dpcms_get_custom_option('dpcms_email_subject', 'Action Required: Sign the Agreement');
    $messageAddition = dpcms_get_custom_option('dpcms_email_message', "");

    $message = "<p>Dear $firstName $lastName,</p>";
    $message .= "<p>$messageAddition</p>";
    $message .= "<p>Please sign using the following link:</p>";
    $message .= "<p><a href='$primarySignatureLink'>$primarySignatureLink</a></p>";
    $message .= "<p>Thank you.</p>";
    $message .= '<img src="' . esc_url($primaryTrackingUrl) . '" alt="" width="1" height="1" style="display:none;" />';

    wp_mail($email, $subject, $message, $headers);

    if (!empty($coSignerEmail)) {
        $coSignerLink = add_query_arg(array('doc_id' => $docId, 'signer' => 'co-signer'), get_permalink(get_page_by_path('sign-contract')));
        $coSignerTrackingUrl = add_query_arg(array('track_email_open' => '1', 'doc_id' => $docId), site_url('/email-tracking-pixel/'));

        $coSignerMessage = "Dear $coSignerFirstName $coSignerLastName,\n\nPlease sign using the following link:\n\n$coSignerLink\n\nThank you.";
        $coSignerMessage .= '<img src="' . esc_url($coSignerTrackingUrl) . '" alt="" width="1" height="1" style="display:none;" />';

        wp_mail($coSignerEmail, $subject, $coSignerMessage, $headers);
    }

    // Reset the email content type to avoid affecting other emails
    remove_filter('wp_mail_content_type', 'dpcms_set_html_content_type');
}

function dpcms_notify_admin_to_sign($formData)
{
    $adminEmail = dpcms_get_custom_option('dpcms_admin_email', get_option('admin_email'));
    $subject = dpcms_get_custom_option('dpcms_email_subject', 'Action Required: Sign the Agreement');
    $message = 'The document has been signed by the primary signer and the co-signer (if any). Please sign the document at your earliest convenience.';

    $from_email = dpcms_get_custom_option('dpcms_from_email', get_option('admin_email'));
    $from_name = dpcms_get_custom_option('dpcms_from_name', dpcms_get_site_domain_name());
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . sanitize_email($from_email) . '>'
    );

    // Generate a link for the admin to sign
    $signatureLink = add_query_arg(array('doc_id' => $formData['document_id'], 'signer' => 'admin'), get_permalink(get_page_by_path('sign-contract')));

    $message .= "\n\nPlease sign using the following link:\n" . esc_url($signatureLink);

    wp_mail($adminEmail, $subject, $message, $headers);
}

function dpcms_restrict_access_to_sign_contract()
{
    if (is_page('sign-contract') && isset($_GET['signer']) && $_GET['signer'] === 'admin') {
        $current_user = wp_get_current_user();

        if (user_can($current_user, 'administrator')) {
            return;
        }

        $additional_roles = get_user_meta($current_user->ID, '_dpcms_roles', true);
        if (!is_array($additional_roles)) {
            $additional_roles = [];
        }

        $roles = dpcms_get_custom_option('dpcms_roles', []);

        $has_permission = false;

        foreach ($additional_roles as $role) {
            if (isset($roles[$role]['capabilities']['sign_contracts'])) {
                $has_permission = true;
                break;
            }
        }

        if (!$has_permission) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page. Please make sure you are logged in.', 'dpcms'),
                esc_html__('Forbidden', 'dpcms'),
                array('response' => 403)
            );
        }
    }
}

function dpcms_generate_contract($uniqueId, $isDirectDownload)
{
    $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    if (!$consent) {
        wp_send_json_error('Please mark the consent to privacy policy checkbox on the settings page and save, then try again.');
        return;
    }

    global $wpdb;
    $user_id = get_option('dpcms_uuid');

    if (empty($user_id)) {
        wp_send_json_error('DPCMS User ID not found. Try deactivating and reactivating the plugin.');
    }

    $form_data = dpcms_get_custom_option('contract_form_' . $uniqueId);

    if (!$form_data) {
        wp_send_json_error('Failed to retrieve form data.');
    }

    // Generate contract and return file path
    $document_id = $form_data['document_id'];
    $form_data = array_merge(
        $form_data,
        array(
            'document_id' => $document_id,
        )
    );

    $dynamic_fields = $form_data['dynamic_fields'];

    // Check if the retrieved data is serialized and unserialize if necessary
    if (is_string($dynamic_fields)) {
        $dynamic_fields = maybe_unserialize($dynamic_fields);
        $form_data['dynamic_fields'] = $dynamic_fields;
    }

    $current_user = wp_get_current_user();
    $capitalized_user = ucwords(strtolower($current_user->display_name));

    $current_date_time = gmdate('Y-m-d H:i:s');

    $pdf_options = [
        'dp_contracts_company_details' => dpcms_get_custom_option('dpcms_company_details', [
            'name' => 'Example Company',
            'street_address' => '123 Example St',
            'city' => 'Example City',
            'state' => 'EX',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'example@example.com',
            'website' => 'www.example.com'
        ]),
        'current_wp_user' => $capitalized_user,
        'is_direct_download' => $isDirectDownload,
        'add_delivery_address' => dpcms_get_custom_option('dpcms_add_delivery_address', ''),
        'pdf_title' => dpcms_get_custom_option('dpcms_pdf_title', 'Agreement'),
        'pdf_subject' => dpcms_get_custom_option('dpcms_pdf_subject', 'Agreement'),
        'pdf_keywords' => dpcms_get_custom_option('dpcms_pdf_keywords', 'Contract, Agreement, Terms, Purchase, Sale, Document'),
        'contract_logo' => dpcms_get_custom_option('dpcms_logo', ''),
        'terms_and_conditions' => dpcms_get_custom_option('dpcms_terms_and_conditions', 'Default terms and conditions...'),
        'pdf_protection' => dpcms_get_custom_option('dpcms_pdf_protection', ['print', 'copy']),
        'pdf_user_password' => dpcms_get_custom_option('dpcms_pdf_user_password', ''),
        'pdf_owner_password' => dpcms_get_custom_option('dpcms_pdf_owner_password', '1234'),
        'add_house_specifications' => dpcms_get_custom_option('dpcms_add_house_specifications', 0),
        'add_pricing_calculator' => dpcms_get_custom_option('dpcms_add_pricing_calculator', 0),
        'contract_title' => dpcms_get_custom_option('dpcms_contract_title', 'Agreement'),
        'contract_header' => dpcms_get_custom_option('dpcms_contract_header', 'This document is an Agreement'),
        'seller_type' => dpcms_get_custom_option('dpcms_seller_type', 'Seller'),
        'custom_seller_type' => dpcms_get_custom_option('dpcms_custom_seller_type', ''),
        'buyer_type' => dpcms_get_custom_option('dpcms_buyer_type', 'Buyer'),
        'custom_buyer_type' => dpcms_get_custom_option('dpcms_custom_buyer_type', ''),
        'sale_type' => dpcms_get_custom_option('dpcms_sale_type', 'sale and purchase'),
        'custom_sale_type' => dpcms_get_custom_option('dpcms_custom_sale_type', ''),
        'product_sold' => dpcms_get_custom_option('dpcms_product_sold', 'a Home'),
        'signed_by' => dpcms_get_custom_option('dpcms_signed_by', 'Example Company'),
        'custom_signed_by' => dpcms_get_custom_option('dpcms_custom_signed_by', ''),
        'current_date_time' => $current_date_time,
    ];

    $response = wp_remote_post('https://deluxeplugins.com/wp-json/dp-contracts/v1/handle-generate-contract', [
        'body' => wp_json_encode([
            'user_id' => $user_id,
            'form_data' => $form_data,
            'pdf_options' => $pdf_options
        ]),
        'timeout' => 120,
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error("Failed to generate contract. " . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (!$result['success']) {
        wp_send_json_error("Failed to generate contract. " . $result['message']);
    }

    $pdf_content = base64_decode($result['pdf_content']);
    $upload_dir = wp_upload_dir()['basedir'] . '/generated_documents/';
    $file_path = $upload_dir . sanitize_file_name($form_data['first_name'] . '_' . $form_data['last_name'] . '_' . $form_data['document_id']) . '.pdf';

    if (!wp_mkdir_p($upload_dir)) {
        wp_send_json_error('Failed to create directory for storing PDF.');
    }

    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    if (!$wp_filesystem->put_contents($file_path, $pdf_content, FS_CHMOD_FILE)) {
        wp_send_json_error('Failed to save PDF file.');
    }

    // Unique cache key for this document
    $cache_key = 'dpcms_contract_pdf_' . $document_id;
    $cached_data = wp_cache_get($cache_key, 'dpcms');

    if ($cached_data === false) {
        // Perform the update
        $result = $wpdb->update(
            $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS,
            array(
                'pdf_path' => $file_path,
                'direct_download' => $isDirectDownload
            ),
            array('document_id' => $document_id),
            array('%s', '%d'), // Formats for the data being updated
            array('%d')        // Format for the where clause
        );

        // Only set the cache if the update was successful
        if ($result !== false) {
            wp_cache_set($cache_key, array(
                'pdf_path' => $file_path,
                'direct_download' => $isDirectDownload
            ), 'dpcms');
        }
    } else {
        // Data is already cached, do nothing or use $cached_data if needed
        // For example, you might want to do something like:
        $file_path = $cached_data['pdf_path'];
        $isDirectDownload = $cached_data['direct_download'];
    }

    return $file_path;
}

function dpcms_send_signed_document($data, $pdfPath)
{
    $adminEmail = dpcms_get_custom_option('dpcms_admin_email', dpcms_get_custom_option('admin_email'));
    $userEmail = sanitize_email($data['email']);
    $subject = 'Your Document Has Been Successfully Signed';
    $message = 'We are pleased to inform you that your contract has been successfully signed and processed.';
    $from_email = dpcms_get_custom_option('dpcms_from_email', get_option('admin_email'));
    $from_name = dpcms_get_custom_option('dpcms_from_name', dpcms_get_site_domain_name());
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . sanitize_text_field($from_name) . ' <' . sanitize_email($from_email) . '>'
    );

    // Email to admin
    $admin_email_sent = wp_mail($adminEmail, $subject, $message, $headers, array($pdfPath));

    // Email to user
    $user_email_sent = wp_mail($userEmail, $subject, $message, $headers, array($pdfPath));

    if (isset($data['co_signer_email']) && !empty($data['co_signer_email'])) {
        // Email to co-signer
        $co_signer_email_sent = wp_mail(sanitize_email($data['co_signer_email']), $subject, $message, $headers, array($pdfPath));
    }

    if ($admin_email_sent && $user_email_sent) {
        echo 'Emails with the signed document have been successfully sent.';
    } else {
        echo 'Failed to send emails with the signed document. Please check the logs.';
    }
}

function dpcms_create_new_contract_page()
{
    // Check if the page already exists
    $page = get_page_by_path('new-contract');

    // If the page doesn't exist, create it
    if (!$page) {
        $page_data = array(
            'post_title' => 'New Contract',
            'post_content' => '[contract_form]',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'page',
            'post_name' => 'new-contract'
        );

        // Insert the page into the database
        $page_id = wp_insert_post($page_data);

        // Check if the page was created successfully
        if (is_wp_error($page_id)) {
            add_post_meta($page_id, '_hide_in_nav', '1', true);
            error_log('Error creating "Sign Contract" page: ' . $page_id->get_error_message());
        } else {
            error_log('"New Contract" page created successfully with ID: ' . $page_id);
        }
    } else {
        error_log('"New Contract" page already exists with ID: ' . $page->ID);
    }
}

// Function to create the "Sign Contract" page if it doesn't exist
function dpcms_create_sign_contract_page()
{
    $page_title = 'Sign Contract';
    $page_content = '';
    $page_template = 'dpcms-sign-contract-page.php';

    // Check if the page already exists
    $page_check = get_page_by_path('sign-contract');

    // If the page doesn't exist, create it
    if (!isset($page_check->ID)) {
        $page_id = wp_insert_post(
            array(
                'post_title' => $page_title,
                'post_content' => $page_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'sign-contract',
            )
        );

        // Set the page template
        if ($page_id && !is_wp_error($page_id)) {
            add_post_meta($page_id, '_hide_in_nav', '1', true);
            update_post_meta($page_id, '_wp_page_template', $page_template);
            error_log('Sign Contract page created successfully with template.');
        } else {
            error_log('Error creating Sign Contract page: ' . $page_id->get_error_message());
        }
    } else {
        error_log('Sign Contract page already exists with ID: ' . $page_check->ID);
    }
}

function dpcms_create_thank_you_page()
{
    // Define the thank you page content
    $page_content = 'Thank you! Your signature has been successfully received. The contract is now under review and will be signed upon approval. You will receive the fully executed contract once the process is complete.';

    // Check if the page already exists
    $page = get_page_by_path('thank-you-for-signing');

    // If the page doesn't exist, create it
    if (!$page) {
        $page_data = array(
            'post_title' => 'Thank You for Signing',
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'page',
            'post_name' => 'thank-you-for-signing'
        );

        // Insert the page into the database
        $page_id = wp_insert_post($page_data);

        // Debugging: Log the page creation process
        if (is_wp_error($page_id)) {
            error_log('Error creating page: ' . $page_id->get_error_message());
        } else {
            add_post_meta($page_id, '_hide_in_nav', '1', true);
            error_log('Page created successfully with ID: ' . $page_id);
            dpcms_set_custom_option('thank_you_for_signing_page_id', $page_id);
        }
    } else {
        // Debugging: Page already exists
        error_log('Page already exists with ID: ' . $page->ID);
    }
}

function dpcms_exclude_pages_from_nav($items, $args)
{
    foreach ($items as $key => $item) {
        if (get_post_meta($item->object_id, '_hide_in_nav', true) == '1') {
            unset($items[$key]);
        }
    }
    return $items;
}

function dpcms_redirect_to_thank_you_page()
{
    $thank_you_for_signing_page_id = dpcms_get_custom_option('thank_you_for_signing_page_id');
    if ($thank_you_for_signing_page_id) {
        $thank_you_page_url = get_permalink($thank_you_for_signing_page_id);
        if ($thank_you_page_url) {
            wp_redirect($thank_you_page_url);
            exit;
        }
    } else {
        wp_redirect(home_url());
        exit;
    }
}