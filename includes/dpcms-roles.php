<?php
// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

function dpcms_add_custom_roles_init()
{
    $custom_caps = dpcms_get_default_capabilities();

    dpcms_add_new_role(
        'representative',
        'Representative',
        array(
            $custom_caps[0] => true,
            $custom_caps[1] => true,
            $custom_caps[2] => true,
        )
    );
    dpcms_add_new_role(
        'secretary',
        'Secretary',
        array(
            $custom_caps[0] => true,
            $custom_caps[1] => true,
            $custom_caps[2] => true,
            $custom_caps[3] => true,
            $custom_caps[6] => true,
            $custom_caps[9] => true,
        )
    );
    dpcms_add_new_role(
        'supervisor',
        'Supervisor',
        array(
            $custom_caps[0] => true,
            $custom_caps[1] => true,
            $custom_caps[2] => true,
            $custom_caps[3] => true,
            $custom_caps[4] => true,
            $custom_caps[6] => true,
            $custom_caps[7] => true,
            $custom_caps[9] => true,
            $custom_caps[11] => true,
            $custom_caps[12] => true,
        )
    );

    // Retrieve existing roles or initialize an empty array
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    // Add custom roles for user assignment
    $custom_roles = dpcms_get_default_roles();
    foreach ($custom_roles as $role_key => $role_info) {
        $roles[$role_key] = $role_info;
    }

    // Save the updated roles array back to the options table
    dpcms_set_custom_option('dpcms_roles', $roles);

    // Add custom capabilities to the administrator role
    $admin = get_role('administrator');
    if ($admin) {
        foreach ($custom_caps as $cap) {
            $admin->add_cap($cap);
        }
    }
}

function dpcms_remove_custom_roles_init()
{
    $custom_caps = dpcms_get_default_capabilities();

    $admin = get_role('administrator');
    if ($admin) {
        foreach ($custom_caps as $cap) {
            $admin->remove_cap($cap);
        }
    }
}

function dpcms_get_default_roles()
{
    return dpcms_get_custom_option('dpcms_roles', array());
}

function dpcms_get_default_capabilities()
{
    return array(
        'add_dpcms_to_dashboard',
        'view_own_contracts',
        'view_all_contracts',
        'create_contracts',
        'sign_contracts',
        'delete_contracts',
        'access_to_settings',
        'access_to_template',
        'add_new_roles',
        'update_roles',
        'delete_roles',
        'assign_roles',
        'unassign_roles',
        // Add more custom capabilities as needed
    );
}

function dpcms_add_new_role($role_key, $role_name, $capabilities)
{
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    $roles[$role_key] = array(
        'name' => $role_name,
        'capabilities' => $capabilities
    );

    dpcms_set_custom_option('dpcms_roles', $roles);
}

function dpcms_add_new_role_ajax()
{
    // Check user capabilities
    dpcms_check_capabilities('add_new_roles', false, true);

    // Check if 'role_name' and 'capabilities' are present in the request
    if (!isset($_POST['role_name']) || !isset($_POST['capabilities'])) {
        wp_send_json_error(array('message' => 'Invalid input.'));
    }

    // Verify the nonce and sanitize the nonce
    if (
        !isset($_POST['dpcms_role_actions_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dpcms_role_actions_nonce'])), 'dpcms_role_actions_nonce')
    ) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    // Sanitize role name and capabilities
    $role_key = sanitize_text_field($_POST['role_name']);
    $capabilities = array_map('sanitize_text_field', $_POST['capabilities']);
    $caps_array = array_fill_keys($capabilities, true);

    // Get the current roles from the options table
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    // Add the new role
    $roles[$role_key] = array(
        'name' => $role_key,
        'capabilities' => $caps_array
    );

    // Try to update the roles option and return success or failure
    if (dpcms_set_custom_option('dpcms_roles', $roles)) {
        wp_send_json_success(array('role_name' => $role_key));
    } else {
        wp_send_json_error(array('message' => 'Failed to update the option.'));
    }
}

function dpcms_update_role_ajax()
{
    // Check user capabilities
    if (!dpcms_check_capabilities('update_roles', false, true)) {
        wp_send_json_error(array('message' => 'User does not have the required capabilities.'));
    }

    // Check if 'role_name' and 'capabilities' are present in the request
    if (!isset($_POST['role_name']) || !isset($_POST['capabilities'])) {
        wp_send_json_error(array('message' => 'Invalid input: role_name or capabilities missing.'));
    }

    // Verify the nonce and sanitize it
    if (
        !isset($_POST['dpcms_role_actions_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dpcms_role_actions_nonce'])), 'dpcms_role_actions_nonce')
    ) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    // Sanitize role name and capabilities
    $role_key = sanitize_text_field($_POST['role_name']);
    $capabilities = array_map('sanitize_text_field', $_POST['capabilities']);

    // Convert the capabilities array into an associative array for WordPress roles
    $caps_array = array_fill_keys($capabilities, true);

    // Get the current roles from the options table
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    // Check if the role exists
    if (isset($roles[$role_key])) {
        // Update the role with new capabilities
        $roles[$role_key] = array(
            'name' => $role_key,
            'capabilities' => $caps_array
        );

        // Try to update the roles option and return success or failure
        if (dpcms_set_custom_option('dpcms_roles', $roles)) {
            wp_send_json_success(array('role_name' => $role_key));
        } else {
            wp_send_json_error(array('message' => 'Failed to update the roles option.'));
        }
    } else {
        wp_send_json_error(array('message' => 'Role not found.'));
    }
}

function dpcms_delete_role_ajax()
{
    // Check user capabilities
    dpcms_check_capabilities('delete_roles', false, true);

    // Check if 'role_name' is present in the request
    if (!isset($_POST['role_name'])) {
        wp_send_json_error(array('message' => 'Invalid input.'));
    }

    // Verify the nonce and sanitize the nonce
    if (
        !isset($_POST['dpcms_role_actions_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dpcms_role_actions_nonce'])), 'dpcms_role_actions_nonce')
    ) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    // Sanitize the role name
    $role_key = sanitize_text_field($_POST['role_name']);
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    // Check if the role exists
    if (isset($roles[$role_key])) {
        unset($roles[$role_key]);

        // Try to update the roles option and return success or failure
        if (dpcms_set_custom_option('dpcms_roles', $roles)) {
            wp_send_json_success(array('role_name' => $role_key));
        } else {
            wp_send_json_error(array('message' => 'Failed to update the option.'));
        }
    } else {
        wp_send_json_error(array('message' => 'Role not found.'));
    }
}

function dpcms_assign_roles_ajax()
{
    dpcms_check_capabilities('assign_roles', false, true);

    if (!check_ajax_referer('dpcms_assign_roles_nonce', 'nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error('Role was not assigned. Try refreshing the page and try again.');
        return;
    }

    // Validate and sanitize user_id and role
    if (isset($_POST['user_id']) && isset($_POST['role'])) {
        $user_id = intval($_POST['user_id']);
        $role = sanitize_text_field($_POST['role']);

        dpcms_add_additional_role($user_id, $role);

        // Check if the assigned role has the capability 'add_dpcms_to_dashboard'
        $roles = dpcms_get_custom_option('dpcms_roles', array());
        if (isset($roles[$role]['capabilities']['add_dpcms_to_dashboard'])) {
            // Add the new capability to the user's main WordPress role
            $user = get_userdata($user_id);
            if ($user) {
                foreach ($user->roles as $user_role) {
                    $role_object = get_role($user_role);
                    if ($role_object) {
                        $role_object->add_cap('add_dpcms_to_dashboard');
                    }
                }
            }
        }

        wp_send_json_success('Role assigned successfully');
    } else {
        wp_send_json_error('Invalid input');
    }
}

function dpcms_unassign_roles_ajax()
{
    dpcms_check_capabilities('unassign_roles', false, true);

    if (!check_ajax_referer('dpcms_assign_roles_nonce', 'nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error('Role was not unassigned. Try refreshing the page and try again.');
        return;
    }

    // Validate and sanitize user_id and role
    if (isset($_POST['user_id']) && isset($_POST['role'])) {
        $user_id = intval($_POST['user_id']);
        $role = sanitize_text_field($_POST['role']);

        dpcms_remove_additional_role($user_id, $role);

        // Check if the unassigned role has the capability 'add_dpcms_to_dashboard'
        $roles = dpcms_get_custom_option('dpcms_roles', array());
        if (isset($roles[$role]['capabilities']['add_dpcms_to_dashboard'])) {
            // Remove the capability from the user's main WordPress role if no other role grants it
            $user = get_userdata($user_id);
            if ($user) {
                $still_has_capability = false;

                // Check if any remaining role still grants the capability
                foreach (dpcms_get_all_user_roles($user_id) as $user_role) {
                    if ($user_role !== $role && isset($roles[$user_role]['capabilities']['add_dpcms_to_dashboard'])) {
                        $still_has_capability = true;
                        break;
                    }
                }

                // If no remaining role grants the capability, remove it
                if (!$still_has_capability) {
                    foreach ($user->roles as $user_role) {
                        $role_object = get_role($user_role);
                        if ($role_object) {
                            $role_object->remove_cap('add_dpcms_to_dashboard');
                        }
                    }
                }
            }
        }

        wp_send_json_success('Role unassigned successfully');
    } else {
        wp_send_json_error('Invalid input');
    }
}

// Function to add an additional role to a user
function dpcms_add_additional_role($user_id, $role)
{
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    if (array_key_exists($role, $roles)) {
        $additional_roles = get_user_meta($user_id, '_dpcms_roles', true);

        if (!is_array($additional_roles)) {
            $additional_roles = array();
        }

        if (!in_array($role, $additional_roles)) {
            $additional_roles[] = $role;
            update_user_meta($user_id, '_dpcms_roles', $additional_roles);
        }
    }
}

// Function to remove an additional role from a user
function dpcms_remove_additional_role($user_id, $role)
{
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    if (array_key_exists($role, $roles)) {
        $additional_roles = get_user_meta($user_id, '_dpcms_roles', true);

        if (is_array($additional_roles)) {
            $index = array_search($role, $additional_roles);
            if ($index !== false) {
                unset($additional_roles[$index]);
                update_user_meta($user_id, '_dpcms_roles', $additional_roles);
            }
        }
    }
}

// Function to get all roles for a user
function dpcms_get_all_user_roles($user_id)
{
    $user = get_userdata($user_id);
    $default_roles = $user->roles;
    $additional_roles = get_user_meta($user_id, '_dpcms_roles', true);

    if (!is_array($additional_roles)) {
        $additional_roles = array();
    }

    // Ensure additional roles are valid according to dpcms_roles option
    $roles = dpcms_get_custom_option('dpcms_roles', array());
    $additional_roles = array_filter($additional_roles, function ($role) use ($roles) {
        return array_key_exists($role, $roles);
    });

    return array_unique(array_merge($default_roles, $additional_roles));
}

function dpcms_handle_role_change($user_id, $role, $old_roles)
{
    $user = get_userdata($user_id);
    $all_roles = dpcms_get_all_user_roles($user_id);
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    $has_custom_capability = false;

    foreach ($all_roles as $user_role) {
        if (isset($roles[$user_role]['capabilities']['add_dpcms_to_dashboard'])) {
            $has_custom_capability = true;
            break;
        }
    }

    // Add the custom capability if needed
    if ($has_custom_capability) {
        foreach ($user->roles as $user_role) {
            $role_object = get_role($user_role);
            if ($role_object && !$role_object->has_cap('add_dpcms_to_dashboard')) {
                $role_object->add_cap('add_dpcms_to_dashboard');
            }
        }
    } else {
        // Remove the custom capability if it's no longer needed
        foreach ($user->roles as $user_role) {
            $role_object = get_role($user_role);
            if ($role_object && $role_object->has_cap('add_dpcms_to_dashboard')) {
                $role_object->remove_cap('add_dpcms_to_dashboard');
            }
        }
    }
}

function dpcms_handle_profile_update($user_id, $old_user_data)
{
    $user = get_userdata($user_id);
    $all_roles = dpcms_get_all_user_roles($user_id);
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    $has_custom_capability = false;

    foreach ($all_roles as $user_role) {
        if (isset($roles[$user_role]['capabilities']['add_dpcms_to_dashboard'])) {
            $has_custom_capability = true;
            break;
        }
    }

    // Add the custom capability if needed
    if ($has_custom_capability) {
        foreach ($user->roles as $user_role) {
            $role_object = get_role($user_role);
            if ($role_object && !$role_object->has_cap('add_dpcms_to_dashboard')) {
                $role_object->add_cap('add_dpcms_to_dashboard');
            }
        }
    } else {
        // Remove the custom capability if it's no longer needed
        foreach ($user->roles as $user_role) {
            $role_object = get_role($user_role);
            if ($role_object && $role_object->has_cap('add_dpcms_to_dashboard')) {
                $role_object->remove_cap('add_dpcms_to_dashboard');
            }
        }
    }
}

function dpcms_check_capabilities($capability, $deny_page_access, $is_ajax = false)
{
    // Get current user
    $current_user = wp_get_current_user();

    // If the user is an administrator, they always have the capability
    if (user_can($current_user, 'administrator')) {
        return true;
    }

    // Get custom capabilities from usermeta
    $additional_roles = get_user_meta($current_user->ID, '_dpcms_roles', true);

    if (!is_array($additional_roles)) {
        $additional_roles = array();
    }

    // Get the roles option to validate the capabilities
    $roles = dpcms_get_custom_option('dpcms_roles', array());

    foreach ($additional_roles as $role) {
        if (isset($roles[$role]['capabilities']) && isset($roles[$role]['capabilities'][$capability])) {
            return true;
        }
    }

    // Fall back to checking the default WordPress roles
    if (user_can($current_user, $capability)) {
        return true;
    }

    if ($deny_page_access) {
        // If no match, deny access
        wp_die(esc_html__('You do not have sufficient permissions to access this page. Please make sure you are logged in.', 'deluxe-wp-contracts'));
    } else {
        if ($is_ajax) {
            wp_send_json_error(array('message' => 'You do not have sufficient permissions to perform this action.'));
        } else {
            echo '<div id="dpcms-no-permission" style="display:none"></div>';
        }
        return false;
    }
}