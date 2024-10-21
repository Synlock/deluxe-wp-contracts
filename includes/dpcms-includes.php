<?php
// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

// Create Database Table for Signed Contracts
function dpcms_create_contracts_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        wp_user_created_contract varchar(255) NOT NULL,
        first_name varchar(255) NOT NULL,
        last_name varchar(255) NOT NULL,
        address text NOT NULL,
        city varchar(255) NOT NULL,
        state varchar(255) NOT NULL,
        zip varchar(20) NOT NULL,
        country varchar(255) NOT NULL,
        phone varchar(20) NOT NULL,
        email varchar(255) NOT NULL,
        co_signer_email varchar(255) DEFAULT NULL,
        generated_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        signed_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        direct_download boolean DEFAULT FALSE,
        email_opened boolean DEFAULT FALSE,
        contract_opened boolean DEFAULT FALSE,
        contract_signed boolean DEFAULT FALSE,
        pdf_path varchar(255) NOT NULL,
        document_id varchar(255) NOT NULL,
        primary_signature longtext DEFAULT NULL,
        co_signer_signature longtext DEFAULT NULL,
        admin_signature longtext DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
        error_log('Table creation failed: ' . $table_name);
    } else {
        error_log('Table created successfully: ' . $table_name);
    }
}

function dpcms_create_custom_options_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_OPTIONS;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        option_id bigint(20) NOT NULL AUTO_INCREMENT,
        option_name varchar(191) NOT NULL,
        option_value longtext NOT NULL,
        autoload varchar(20) NOT NULL DEFAULT 'yes',
        expiration bigint(20) DEFAULT NULL,
        PRIMARY KEY  (option_id),
        UNIQUE KEY option_name (option_name)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function dpcms_set_default_options()
{
    $default_options = [
        'dpcms_license_key' => '',
        'dpcms_api_key' => '',
        'dpcms_logo' => '',
        'dpcms_signature' => '',
        'dpcms_company_details' => [
            'name' => 'Company Name',
            'street_address' => '1234 Example St.',
            'city' => 'Example City',
            'state' => 'EX',
            'zip_code' => '12345',
            'phone' => '1234567890',
            'email' => 'example@yourcompany.com',
            'website' => 'www.example.com'
        ],
        'dpcms_data_consent' => '',
        'dpcms_admin_email' => get_option('admin_email'),
        'dpcms_from_email' => get_option('admin_email'),
        'dpcms_from_name' => dpcms_get_site_domain_name(),
        'dpcms_email_subject' => 'Action Required: Sign the Agreement',
        'dpcms_email_message' => '',
        'dpcms_add_pricing_calculator' => '1',
        'dpcms_remaining_balance_percentage' => '50',
        'dpcms_pdf_user_password' => '',
        'dpcms_pdf_owner_password' => '1234',
        'dpcms_pdf_protection' => ['print', 'copy'],
        'dpcms_pdf_title' => 'Contract',
        'dpcms_pdf_subject' => 'Agreement',
        'dpcms_pdf_keywords' => 'Contract, Agreement, Terms, Purchase, Sale, Document',
        'dpcms_contract_title' => 'Agreement',
        'dpcms_contract_header' => 'This document is an Agreement',
        'dpcms_seller_type' => 'Seller',
        'dpcms_custom_seller_type' => '',
        'dpcms_buyer_type' => 'Buyer',
        'dpcms_custom_buyer_type' => '',
        'dpcms_sale_type' => 'sale and purchase',
        'dpcms_custom_sale_type' => '',
        'dpcms_product_sold' => 'a Home',
        'dpcms_add_house_specifications' => '1',
        'dpcms_add_delivery_address' => '1',
        'dpcms_add_co_signer_information' => '1',
        'dpcms_terms_and_conditions' => 'Type in your terms and conditions or choose from the options above and modify to your needs.',
        'dpcms_dynamic_fields' => [],
        'dpcms_add_other_provisions' => '1',
        'dpcms_signed_by' => 'Company Name',
        'dpcms_custom_signed_by' => '',
    ];

    foreach ($default_options as $option_name => $default_value) {
        if (dpcms_get_custom_option($option_name) === false) {
            dpcms_set_custom_option($option_name, $default_value);
        }
    }
}

function dpcms_set_custom_option($option_name, $value, $expiration = 0)
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_OPTIONS;

    $value = maybe_serialize($value);
    $expiration_timestamp = ($expiration > 0) ? time() + $expiration : null;

    $existing_option = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE option_name = %s",
            $option_name
        )
    );

    if ($existing_option) {
        $result = $wpdb->update(
            $table_name,
            array(
                'option_value' => $value,
                'expiration' => $expiration_timestamp
            ),
            array('option_name' => $option_name),
            array(
                '%s',
                $expiration_timestamp !== null ? '%d' : '%s'
            ),
            array('%s')
        );

        if ($result === false) {
            return false;
        }
        return true;
    } else {
        $result = $wpdb->insert(
            $table_name,
            array(
                'option_name' => $option_name,
                'option_value' => $value,
                'expiration' => $expiration_timestamp
            ),
            array(
                '%s',
                '%s',
                $expiration_timestamp !== null ? '%d' : '%s'
            )
        );

        if ($result === false) {
            return false;
        }
        return true;
    }
}

function dpcms_get_custom_option($option_name, $default = false)
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_OPTIONS;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT option_value, expiration FROM $table_name WHERE option_name = %s",
            $option_name
        )
    );

    if ($row) {
        if ($row->expiration !== null && time() > $row->expiration) {
            $wpdb->delete(
                $table_name,
                array('option_name' => $option_name),
                array('%s')
            );
            return $default;
        } else {
            return maybe_unserialize($row->option_value);
        }
    }

    return $default;
}

function dpcms_delete_custom_option($option_name)
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_OPTIONS;

    $wpdb->delete(
        $table_name,
        array('option_name' => $option_name),
        array('%s')
    );
}

function dpcms_schedule_expiration_event()
{
    if (!wp_next_scheduled('dpcms_cleanup_expired_options')) {
        wp_schedule_event(time(), 'daily', 'dpcms_cleanup_expired_options');
    }
}

function dpcms_clear_expiration_event()
{
    $timestamp = wp_next_scheduled('dpcms_cleanup_expired_options');
    wp_unschedule_event($timestamp, 'dpcms_cleanup_expired_options');
}

add_action('dpcms_cleanup_expired_options', 'dpcms_cleanup_expired_options');

function dpcms_cleanup_expired_options()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_OPTIONS;

    $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE expiration IS NOT NULL AND expiration < %d", time()));
}

function dpcms_get_site_domain_name()
{
    $home_url = wp_parse_url(home_url());
    $domain_name = $home_url['host'];

    if (strpos($domain_name, 'www.') === 0) {
        $domain_name = substr($domain_name, 4);
    }

    $domain_parts = explode('.', $domain_name);
    if (count($domain_parts) > 1) {
        array_pop($domain_parts);
    }
    $domain_name_without_tld = implode('.', $domain_parts);

    $domain_name_without_tld = ucfirst($domain_name_without_tld);

    return $domain_name_without_tld;
}

function dpcms_get_subscription_type($user_id)
{
    $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    if (!$consent) {
        return;
    }

    $response = wp_remote_post('https://deluxeplugins.com/wp-json/dp-contracts/v1/get-user-subscription', [
        'body' => wp_json_encode(['user_id' => $user_id]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 120
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return new WP_Error('request_failed', "Something went wrong: $error_message");
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['subscription_type'])) {
            if ($data['subscription_type'] === 'free') {
                $license_key = '';
                $api_key = '';
                $admin_email = get_option('admin_email');
                $from_email = get_option('admin_email');
                $from_name = dpcms_get_site_domain_name();

                if (dpcms_get_custom_option('dpcms_license_key') !== $license_key) {
                    dpcms_set_custom_option('dpcms_license_key', $license_key);
                }

                if (dpcms_get_custom_option('dpcms_api_key') !== $api_key) {
                    dpcms_set_custom_option('dpcms_api_key', $api_key);
                }

                if (dpcms_get_custom_option('dpcms_admin_email') !== $admin_email) {
                    dpcms_set_custom_option('dpcms_admin_email', $admin_email);
                }

                if (dpcms_get_custom_option('dpcms_from_email') !== $from_email) {
                    dpcms_set_custom_option('dpcms_from_email', $from_email);
                }

                if (dpcms_get_custom_option('dpcms_from_name') !== $from_name) {
                    dpcms_set_custom_option('dpcms_from_name', $from_name);
                }
            }

            return $data['subscription_type'];
        } else {
            return new WP_Error('subscription_type_not_found', 'Subscription type not found in the response.');
        }
    }
}

function dpcms_handle_pdf_view()
{
    if ((isset($_GET['page']) && ($_GET['page'] == 'view-all-contracts' || $_GET['page'] == 'view-signed-contracts')) && isset($_GET['view_contract'])) {
        $document_id = intval($_GET['view_contract']);
        $pdf_path = dpcms_get_pdf_path($document_id);

        if ($pdf_path && file_exists($pdf_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($pdf_path) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            @readfile($pdf_path);
            exit;
        } else {
            wp_die(esc_html__('PDF not found for document ID: ', 'dpcms-wp-contracts') . esc_html($document_id));
        }
    }
}

function dpcms_handle_pdf_download()
{
    if ((isset($_GET['page']) && ($_GET['page'] == 'view-all-contracts' || $_GET['page'] == 'view-signed-contracts')) && isset($_GET['download_contract'])) {
        $document_id = intval($_GET['download_contract']);
        $pdf_path = dpcms_get_pdf_path($document_id);

        if ($pdf_path && file_exists($pdf_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($pdf_path) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($pdf_path));
            ob_clean();
            flush();
            readfile($pdf_path);
            exit;
        } else {
            wp_die(esc_html__('PDF not found for document ID: ', 'dpcms-wp-contracts') . esc_html($document_id));
        }
    }
}

function dpcms_handle_pdf_deletion()
{
    if ((isset($_GET['page']) && ($_GET['page'] == 'view-all-contracts' || $_GET['page'] == 'view-signed-contracts')) && isset($_GET['delete_contract'])) {
        $document_id_to_delete = intval($_GET['delete_contract']);
        global $wpdb;
        $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;

        $pdf_path = dpcms_get_pdf_path($document_id_to_delete);
        $deleted = $wpdb->delete($table_name, array('document_id' => $document_id_to_delete));

        if ($deleted !== false) {
            if ($pdf_path && file_exists($pdf_path)) {
                wp_delete_file($pdf_path);
            }
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Contract deleted successfully.', 'dpcms-wp-contracts') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Error deleting contract.', 'dpcms-wp-contracts') . '</p></div>';
            });
        }
    }
}

function dpcms_get_directory()
{
    global $wp_filesystem;

    WP_Filesystem();
    $uploads_dir = wp_get_upload_dir()['basedir'];
    $dir = $uploads_dir . '/.dpcms_contracts_data';

    if (!$wp_filesystem->is_dir($dir)) {
        $wp_filesystem->mkdir($dir, 0755);
        $htaccess_content = "deny from all\n";
        $wp_filesystem->put_contents($dir . '/.htaccess', $htaccess_content, FS_CHMOD_FILE);
    }

    return $dir;
}

function dpcms_get_file($id)
{
    $data_dir = dpcms_get_directory();
    $file_name = '.user_info_' . md5($id) . '.json';
    return $data_dir . '/' . $file_name;
}

function dpcms_handle_email_open_tracking()
{
    if (isset($_GET['track_email_open']) && isset($_GET['doc_id'])) {
        $docId = sanitize_text_field($_GET['doc_id']);
        $cache_key = 'email_opened_' . $docId;
        $cached_value = wp_cache_get($cache_key, 'dpcms');

        if ($cached_value === false) {
            global $wpdb;
            $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;

            $updated = $wpdb->update(
                $table_name,
                array('email_opened' => true),
                array('document_id' => $docId),
                array('%d'),
                array('%d')
            );

            if ($updated !== false) {
                wp_cache_set($cache_key, true, 'dpcms', 3600);
            } else {
                error_log("Failed to update email_opened for document ID: $docId");
            }
        }

        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIABAP///wAAACwAAAAAAQABAAACAkQBADs=');
        exit;
    }
}

function dpcms_noindex_sign_contract_page()
{
    if (is_page('sign-contract')) {
        echo '<meta name="robots" content="noindex, nofollow" />';
    }
}

function dpcms_copy_template_to_theme()
{
    global $wp_filesystem;
    WP_Filesystem();

    $template_file = plugin_dir_path(__FILE__) . '../templates/dpcms-sign-contract-page.php';
    $theme_directory = get_template_directory() . '/dpcms-sign-contract-page.php';

    if (!$wp_filesystem->exists($theme_directory)) {
        $wp_filesystem->copy($template_file, $theme_directory);
    }
}

function dpcms_set_html_content_type()
{
    return 'text/html';
}

function dpcms_generate_numeric_unique_id()
{
    $timestampPart = time();
    $randomPart = wp_rand(1000, 9999);
    $uniqueId = $timestampPart . $randomPart;

    return $uniqueId;
}

function dpcms_get_pdf_path($document_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;
    return $wpdb->get_var($wpdb->prepare("SELECT pdf_path FROM $table_name WHERE document_id = %d", $document_id));
}

function dpcms_enqueue_signature_pad()
{
    if (is_page_template('dpcms-sign-contract-page.php')) {
        $signature_pad_path = plugin_dir_url(__FILE__) . '../signature_pad-5.0.3/dist/signature_pad.umd.min.js';
        wp_enqueue_script(
            'dpcms-signature-pad',
            $signature_pad_path,
            [],
            '5.0.3',
            true
        );
        $inline_script = "
            document.addEventListener('DOMContentLoaded', function() {
                function initializeSignaturePad(canvasId, clearButtonId, hiddenInputId, formId) {
                    var canvas = document.getElementById(canvasId);
                    if (!canvas) {
                        console.error('Canvas element not found:', canvasId);
                        return;
                    }

                    var signaturePad = new SignaturePad(canvas);

                    document.getElementById(clearButtonId).addEventListener('click', function() {
                        signaturePad.clear();
                    });

                    document.getElementById(formId).addEventListener('submit', function(e) {
                        if (!signaturePad.isEmpty()) {
                            document.getElementById(hiddenInputId).value = signaturePad.toDataURL();
                        } else {
                            alert('Please provide a signature first.');
                            e.preventDefault();
                        }
                    });

                    window.addEventListener('resize', function() {
                        resizeCanvas(canvas, signaturePad);
                    });
                    resizeCanvas(canvas, signaturePad);
                }

                function resizeCanvas(canvas, signaturePad) {
                    var ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext('2d').scale(ratio, ratio);
                    signaturePad.clear();
                }

                initializeSignaturePad('primary-canvas', 'clear', 'signature', 'sign-form');
                initializeSignaturePad('co-signer-canvas', 'clear-co', 'co_signer_signature', 'co-sign-form');
                initializeSignaturePad('admin-canvas', 'clear-admin', 'admin_signature', 'admin-sign-form');
            });
        ";
        wp_add_inline_script('dpcms-signature-pad', $inline_script);
    }
}