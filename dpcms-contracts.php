<?php
/*
Plugin Name: Deluxe WP Contracts
Plugin URI: https://deluxeplugins.com/deluxe-wp-contracts/
Description: Effortlessly create and manage custom contracts with automated email notifications and a streamlined signature process, generating and emailing a PDF for seamless digital agreements. Ideal for anyone seeking an efficient contract management system.
Version: 1.4.1
Author: Deluxe Plugins
Author URI: https://deluxeplugins.com
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: deluxe-wp-contracts
Domain Path: /languages
*/

// Include necessary files
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

require_once plugin_dir_path(__FILE__) . 'includes/dpcms-constants.php';

require_once plugin_dir_path(__FILE__) . 'includes/dpcms-includes.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpcms-roles.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpcms-contract-examples.php';
require_once plugin_dir_path(__FILE__) . 'public/dpcms-public.php';
require_once plugin_dir_path(__FILE__) . 'admin/dpcms-admin.php';
require_once plugin_dir_path(__FILE__) . 'admin/dpcms-create-contract.php';

// Register activation hooks
register_activation_hook(__FILE__, 'dpcms_copy_template_to_theme');
register_activation_hook(__FILE__, 'dpcms_create_new_contract_page');
register_activation_hook(__FILE__, 'dpcms_create_sign_contract_page');
register_activation_hook(__FILE__, 'dpcms_create_thank_you_page');
register_activation_hook(__FILE__, 'dpcms_create_contracts_table');
register_activation_hook(__FILE__, 'dpcms_create_custom_options_table');
register_activation_hook(__FILE__, 'dpcms_set_default_options');
register_activation_hook(__FILE__, 'dpcms_add_custom_roles_init');
register_activation_hook(__FILE__, 'dpcms_schedule_expiration_event');

// Register deactivation hooks
register_deactivation_hook(__FILE__, 'dpcms_clear_expiration_event');

function dpcms_enqueue_frontend()
{
    $percentage = dpcms_get_custom_option('remaining_balance_percentage', 50);
    $dynamic_fields = dpcms_get_custom_option('dpcms_dynamic_fields', []);
    $fieldGroupIndex = is_array($dynamic_fields) ? count($dynamic_fields) : 0;

    //Styles
    wp_enqueue_style('dpcms-public-styles', plugin_dir_url(__FILE__) . 'css/dpcms-public-styles.css', array(), DPCMS_VERSION);

    //Scripts
    wp_enqueue_script('dpcms-contract-form-controller', plugin_dir_url(__FILE__) . 'js/dpcms-form-controller.js', array('jquery'), DPCMS_VERSION, true);
    wp_localize_script(
        'dpcms-contract-form-controller',
        'dpcmsData',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'percentage' => $percentage,
            'fieldGroupIndex' => $fieldGroupIndex,
        )
    );
}
add_action('wp_enqueue_scripts', 'dpcms_enqueue_frontend');

function dpcms_enqueue_admin()
{
    wp_enqueue_script('jquery');

    $default_capabilities = dpcms_get_default_capabilities();
    $plugin_default_roles = dpcms_get_default_roles();
    $dynamic_fields = dpcms_get_custom_option('dpcms_dynamic_fields', []);
    $fieldGroupIndex = is_array($dynamic_fields) ? count($dynamic_fields) : 0;
    $id = dpcms_id();
    $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    $contract_examples = dpcms_get_contract_examples();

    //Styles
    wp_enqueue_style('dpcms-admin-styles', plugin_dir_url(__FILE__) . 'css/dpcms-admin-styles.css', array(), DPCMS_VERSION);
    wp_enqueue_style('dpcms-create-contract-styles', plugin_dir_url(__FILE__) . 'css/dpcms-create-contract-styles.css', array(), DPCMS_VERSION);

    //Scripts
    wp_enqueue_script('dpcms-settings', plugin_dir_url(__FILE__) . 'js/dpcms-settings.js', array('jquery'), DPCMS_VERSION, true);
    wp_localize_script(
        'dpcms-settings',
        'dpcmsAdminAjax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'role_actions_nonce' => wp_create_nonce('dpcms_role_actions_nonce'),
            'assign_nonce' => wp_create_nonce('dpcms_assign_roles_nonce'),
            'license_nonce' => wp_create_nonce('dpcms_validate_license_nonce'),
            'premium_nonce' => wp_create_nonce('dpcms_request_premium_content_nonce'),
            'default_capabilities' => $default_capabilities,
            'plugin_roles' => $plugin_default_roles,
            'fieldGroupIndex' => $fieldGroupIndex,
            'id' => $id,
            'consent' => $consent,
            'contract_examples' => $contract_examples
        )
    );
    wp_enqueue_script('dpcms-create-contract', plugin_dir_url(__FILE__) . 'js/dpcms-create-contract.js', array('jquery'), DPCMS_VERSION, true);
}
add_action('admin_enqueue_scripts', 'dpcms_enqueue_admin');

add_action('wp_enqueue_scripts', 'dpcms_enqueue_signature_pad');
// Shortcode for contract form
add_shortcode('contract_form', 'dpcms_contract_form_shortcode');

add_action('wp_ajax_dpcms_process_form', 'dpcms_process_form');
add_action('wp_ajax_nopriv_dpcms_process_form', 'dpcms_process_form');

// Add admin menu
add_action('admin_menu', 'dpcms_add_contracts_menu');

// Handle document signature
add_action('template_redirect', 'dpcms_handle_document_signature');

add_action('wp_ajax_dpcms_generate_contract', 'dpcms_generate_contract');
add_action('wp_ajax_nopriv_dpcms_generate_contract', 'dpcms_generate_contract');

add_action('admin_init', 'dpcms_handle_seller_buyer_types');

//Handle pdf button logic from contracts tables
add_action('admin_init', 'dpcms_handle_pdf_download');
add_action('admin_init', 'dpcms_handle_pdf_view');
add_action('admin_init', 'dpcms_handle_pdf_deletion');

add_action('admin_init', 'dpcms_id');

//Set sign contract page to not be indexed by search engines
add_action('wp_head', 'dpcms_noindex_sign_contract_page');

add_action('admin_head', 'dpcms_remove_duplicate_submenu');

add_action('init', 'dpcms_handle_email_open_tracking');

add_action('template_redirect', 'dpcms_restrict_access_to_sign_contract');

add_action('wp_ajax_dpcms_add_new_role_ajax', 'dpcms_add_new_role_ajax');
add_action('wp_ajax_dpcms_update_role_ajax', 'dpcms_update_role_ajax');
add_action('wp_ajax_dpcms_delete_role_ajax', 'dpcms_delete_role_ajax');
add_action('wp_ajax_dpcms_assign_roles_ajax', 'dpcms_assign_roles_ajax');
add_action('wp_ajax_dpcms_unassign_roles_ajax', 'dpcms_unassign_roles_ajax');
add_action('set_user_role', 'dpcms_handle_role_change', 10, 3);
add_action('profile_update', 'dpcms_handle_profile_update', 10, 2);

add_action('wp_ajax_dpcms_save_keys', 'dpcms_save_keys_ajax');
add_action('wp_ajax_dpcms_delete_keys', 'dpcms_delete_keys_ajax');
add_action('wp_ajax_dpcms_request_premium_content', 'dpcms_request_premium_content');
add_action('wp_ajax_nopriv_dpcms_request_premium_content', 'dpcms_request_premium_content');

add_action('admin_init', 'dpcms_redirect_to_new_contract_via_admin_init');

// Add filters for custom email sender
add_filter('wp_mail_from', 'dpcms_custom_wp_mail_from');
add_filter('wp_mail_from_name', 'dpcms_custom_wp_mail_from_name');

add_filter('wp_nav_menu_objects', 'dpcms_exclude_pages_from_nav', 10, 2);

function dpcms_custom_wp_mail_from($original_email_address)
{
    $from_email = dpcms_get_custom_option('dpcms_from_email', get_option('admin_email'));
    if (empty($from_email)) {
        $from_email = get_option('admin_email');
    }
    return $from_email;
}

function dpcms_custom_wp_mail_from_name($original_email_from)
{
    $from_name = dpcms_get_custom_option('dpcms_from_name', dpcms_get_site_domain_name());
    if (empty($from_name)) {
        $from_name = dpcms_get_site_domain_name();
    }
    return $from_name;
}

// Function to register the custom template
function dpcms_register_template($templates)
{
    $templates['dpcms-sign-contract-page.php'] = 'Sign Contract';
    return $templates;
}
add_filter('theme_page_templates', 'dpcms_register_template');

// Function to locate the custom template
function dpcms_load_template($template)
{
    if (get_page_template_slug() == 'dpcms-sign-contract-page') {
        $template = plugin_dir_path(__FILE__) . 'templates/dpcms-sign-contract-page.php';
    }
    return $template;
}
add_filter('template_include', 'dpcms_load_template');