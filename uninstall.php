<?php
// If uninstall is not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require_once plugin_dir_path(__FILE__) . 'includes/dpcms-constants.php';

require_once plugin_dir_path(__FILE__) . 'includes/dpcms-includes.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpcms-roles.php';

// Remove custom roles
function dpcms_remove_custom_roles_uninstall()
{
    $created_roles = dpcms_get_custom_option('dpcms_created_roles', array());
    foreach ($created_roles as $role_name) {
        remove_role($role_name);
    }
}

function dpcms_remove_custom_capabilities_uninstall()
{
    $roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber'); // Default roles to check

    // Define your custom capabilities
    $custom_capabilities = dpcms_get_default_capabilities();

    // Remove custom capabilities from each role
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if (!empty($role)) {
            foreach ($custom_capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
}

// Clean up additional roles stored in user meta
function dpcms_clean_up_user_meta()
{
    global $wpdb;

    // Use prepare to avoid SQL injection and ensure proper query structure
    $meta_key = '_dpcms_additional_roles';

    // Fetch user IDs with the custom meta key to enable caching if necessary
    $user_ids = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s", $meta_key));

    if (!empty($user_ids)) {
        foreach ($user_ids as $user_id) {
            delete_user_meta($user_id, $meta_key);
        }
    }
}

// Function to delete multiple pages on uninstall
function dpcms_delete_custom_pages_on_uninstall()
{
    // List of slugs for the pages you want to delete
    $page_slugs = array('sign-contract', 'thank-you-for-signing', 'new-contract');

    // Loop through each slug and attempt to delete the page
    foreach ($page_slugs as $slug) {
        // Check if the page exists by its slug
        $page = get_page_by_path($slug);

        // If the page exists, delete it
        if ($page) {
            wp_delete_post($page->ID, true); // True for force delete (bypass trash)
            error_log('Page with slug "' . $slug . '" deleted successfully.');
        } else {
            error_log('Page with slug "' . $slug . '" not found during uninstall.');
        }
    }
}

// Execute the removal functions
dpcms_remove_custom_roles_uninstall();
dpcms_remove_custom_capabilities_uninstall();
dpcms_clean_up_user_meta();
dpcms_delete_custom_pages_on_uninstall();