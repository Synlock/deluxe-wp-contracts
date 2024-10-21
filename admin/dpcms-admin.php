<?php
// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

// Add Admin Menu
function dpcms_add_contracts_menu()
{
    if (current_user_can('administrator')) {
        $dpcms_dashboard_view_capability = 'manage_options';
    } else {
        $dpcms_dashboard_view_capability = 'add_dpcms_to_dashboard';
    }

    add_menu_page(
        'Deluxe WP Contracts',
        'Deluxe WP Contracts',
        $dpcms_dashboard_view_capability,
        'dpcms-contracts',
        'dpcms_display_main_page',
        'dashicons-admin-page',
    );

    add_submenu_page(
        'dpcms-contracts',
        'Create a New Contract',
        'Create a New Contract',
        $dpcms_dashboard_view_capability,
        'create-new-contract-frontend',
        'dpcms_redirect_to_new_contract'
    );

    add_submenu_page(
        'dpcms-contracts',
        'All Contracts',
        'All Contracts',
        $dpcms_dashboard_view_capability,
        'view-all-contracts',
        'dpcms_display_all_contracts_page'
    );

    add_submenu_page(
        'dpcms-contracts',
        'Signed Contracts',
        'Signed Contracts',
        $dpcms_dashboard_view_capability,
        'view-signed-contracts',
        'dpcms_display_signed_contracts_page'
    );

    add_submenu_page(
        'dpcms-contracts',
        'Create Contract Template',
        'Create Contract Template',
        $dpcms_dashboard_view_capability,
        'create-contract-template',
        'dpcms_display_edit_contract_page'
    );

    add_submenu_page(
        'dpcms-contracts',
        'Manage User Roles',
        'Manage User Roles',
        $dpcms_dashboard_view_capability,
        'manage-rep-roles',
        'dpcms_manage_rep_roles_page_display'
    );

    add_submenu_page(
        'dpcms-contracts',
        'Settings',
        'Settings',
        $dpcms_dashboard_view_capability,
        'dpcms-contracts-settings',
        'dpcms_display_contracts_settings_page'
    );
}

function dpcms_remove_duplicate_submenu()
{
    //remove_submenu_page('signed-contracts', 'signed-contracts');
}

function dpcms_display_main_page()
{
    // Display links to all submenu pages
    echo '<div class="wrap">';
    echo '<h1>Deluxe WP Contracts</h1>';

    $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    if (!$consent) {
        echo '<strong>' . esc_html__('Please mark your consent to the privacy policy on the', 'deluxe-wp-contracts') . 
        ' <a href="' . esc_url(admin_url('admin.php?page=dpcms-contracts-settings')) . '">' . 
        esc_html__('settings page', 'deluxe-wp-contracts') . '</a>.</strong>';
    }

    echo '<ul>';
    echo '<li><a href="' . esc_url(home_url('/new-contract/')) . '">' . esc_html__('Create a new contract', 'deluxe-wp-contracts') . '</a></li>';
    echo '<li><a href="' . esc_url(admin_url('admin.php?page=view-all-contracts')) . '">' . esc_html__('All Contracts', 'deluxe-wp-contracts') . '</a></li>';
    echo '<li><a href="' . esc_url(admin_url('admin.php?page=view-signed-contracts')) . '">' . esc_html__('Signed Contracts', 'deluxe-wp-contracts') . '</a></li>';
    echo '<li><a href="' . esc_url(admin_url('admin.php?page=create-contract-template')) . '">' . esc_html__('Create Contract Template', 'deluxe-wp-contracts') . '</a></li>';
    echo '<li><a href="' . esc_url(admin_url('admin.php?page=manage-rep-roles')) . '">' . esc_html__('Manage User Roles', 'deluxe-wp-contracts') . '</a></li>';
    echo '<li><a href="' . esc_url(admin_url('admin.php?page=dpcms-contracts-settings')) . '">' . esc_html__('Settings', 'deluxe-wp-contracts') . '</a></li>';
    echo '</ul>';
    echo '</div>';
}

function dpcms_redirect_to_new_contract()
{
    $screen = get_current_screen();
    if ($screen->id === 'dpcms-contracts_page_create-new-contract-frontend') {
        $new_contract_url = home_url('/new-contract/');
        wp_redirect($new_contract_url);
        exit;
    }

    echo '<div class="wrap"><h1>Redirecting...</h1></div>';
}

function dpcms_redirect_to_new_contract_via_admin_init()
{
    if (isset($_GET['page']) && $_GET['page'] === 'create-new-contract-frontend') {
        $new_contract_url = home_url('/new-contract/');
        wp_redirect($new_contract_url);
        exit;
    }
}

// Display All Contracts Page
function dpcms_display_all_contracts_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;
    $current_user_id = get_current_user_id();
    $user_roles = dpcms_get_all_user_roles($current_user_id);

    $user_can_view_all = in_array('view_all_contracts', $user_roles) || in_array('administrator', $user_roles);
    $user_can_view_own = in_array('view_own_contracts', $user_roles);

    $subscription_type = dpcms_get_subscription_type(dpcms_id());

    if (!$user_can_view_all && !$user_can_view_own) {
        dpcms_check_capabilities('view_all_contracts', true);
    }

    // Define cache key
    $cache_key = $user_can_view_all ? 'all_contracts_' . $current_user_id : 'own_contracts_' . $current_user_id;
    $results = wp_cache_get($cache_key, 'dpcms');

    if ($results === false) {
        // Cache miss: perform the query
        if ($user_can_view_all) {
            $query = "SELECT * FROM $table_name WHERE 1=1";
        } else {
            $query = $wpdb->prepare("SELECT * FROM $table_name WHERE wp_user_created_contract = %d", $current_user_id);
        }

        $results = $wpdb->get_results($query);

        // Store results in cache
        wp_cache_set($cache_key, $results, 'dpcms', HOUR_IN_SECONDS);
    }

    // Pagination setup
    $total_contracts = count($results);
    $contracts_per_page = 25;
    $total_pages = ceil($total_contracts / $contracts_per_page);
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $contracts_per_page;

    // Slice the results for current page
    $paged_results = array_slice($results, $offset, $contracts_per_page);

    ?>
<div class="wrap">
    <h1>All Contracts</h1>
    <?php $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    if (!$consent) {
        echo '<strong>' . esc_html__('Please mark your consent to the privacy policy on the', 'deluxe-wp-contracts') . 
        ' <a href="' . esc_url(admin_url('admin.php?page=dpcms-contracts-settings')) . '">' . 
        esc_html__('settings page', 'deluxe-wp-contracts') . '</a>.</strong>';
            } ?>
    <h2>Search Contracts</h2>
    <?php if ($subscription_type === 'free'): ?>
    <p>The redo feature is available for Premium and Deluxe users only. <a
            href="https://deluxeplugins.com/wp-contracts " style="text-decoration: none; color: blue;">Upgrade</a> to
        access this feature.</p>
    <?php else: ?>

    <?php endif; ?>
    <div class="dpcms-table-search">
        <input type="text" id="dpcms-searchDocumentId" placeholder="Search by Document ID">
        <input type="text" id="dpcms-searchFirstName" placeholder="Search by First Name">
        <input type="text" id="dpcms-searchLastName" placeholder="Search by Last Name">
        <div class="dpcms-checkbox-container">
            <label for="dpcms-searchStartDate">Start Date</label>

            <input type="date" id="dpcms-searchStartDate">
        </div>
        <div class="dpcms-checkbox-container">
            <label for="dpcms-searchEndDate">End Date</label>
            <input type="date" id="dpcms-searchEndDate">
        </div>
        <div class="dpcms-checkbox-container">
            <label for="dpcms-searchNoEmailDownload">No Email Contracts</label>
            <div>
                <input type="checkbox" id="dpcms-searchNoEmailDownload" value="1">

            </div>
        </div>
        <div class="dpcms-checkbox-container">
            <label for="dpcms-excludeNoEmailDownload">Email Contracts</label>
            <div>
                <input type="checkbox" id="dpcms-excludeNoEmailDownload" value="1">

            </div>
        </div>
        <button id="dpcms-searchButton" type="button" class="button button-primary">Search Contracts</button>
    </div>
    <div class="dpcms-table-responsive">
        <table class="wp-list-table striped dpcms-table" id="dpcms-userTable">
            <thead>
                <tr>
                    <th>Document ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Date Created</th>
                    <th>No Email Download</th>
                    <th>Email Opened</th>
                    <th>Contract Opened</th>
                    <th>Contract Signed</th>
                    <th>PDF</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($paged_results): ?>
                <?php foreach ($paged_results as $row): ?>
                <tr>
                    <td><?php echo esc_html($row->document_id); ?></td>
                    <td><?php echo esc_html($row->first_name); ?></td>
                    <td><?php echo esc_html($row->last_name); ?></td>
                    <td><?php echo esc_html($row->email); ?></td>
                    <td><?php echo esc_html($row->generated_date); ?></td>
                    <td><input type="checkbox" <?php echo ($row->direct_download ? 'checked' : ''); ?> disabled></td>
                    <td><input type="checkbox" <?php echo ($row->email_opened ? 'checked' : ''); ?> disabled></td>
                    <td><input type="checkbox" <?php echo ($row->contract_opened ? 'checked' : ''); ?> disabled></td>
                    <td><input type="checkbox" <?php echo ($row->contract_signed ? 'checked' : ''); ?> disabled></td>
                    <td class="dpcms-dashicon-td">
                        <a href="<?php echo esc_url(add_query_arg(array('view_contract' => $row->document_id), admin_url('admin.php?page=view-all-contracts'))); ?>"
                            class="dashicons dashicons-media-text dpcms-dashicons" title="View PDF"></a>
                        <span></span>
                        <a href="<?php echo esc_url(add_query_arg(array('download_contract' => $row->document_id), admin_url('admin.php?page=view-all-contracts'))); ?>"
                            class="dashicons dashicons-download dpcms-dashicons" title="Download PDF"></a>
                    </td>
                    <td class="dpcms-dashicon-td dpcms-actions">
                        <?php $edit_link = esc_url(add_query_arg(array('edit_contract' => $row->document_id), site_url('/new-contract'))); ?>
                        <?php if ($subscription_type === 'free'): ?>
                        <a href="#" class="dashicons dashicons-edit dpcms-dashicons" title="Upgrade to edit"
                            style="opacity: 0.5; pointer-events: none;"></a>
                        <?php else: ?>
                        <a href="<?php echo $edit_link; ?>" class="dashicons dashicons-edit dpcms-dashicons"
                            title="Redo" target="_blank"></a>
                        <?php endif; ?>
                        <span></span>
                        <a href="<?php echo esc_url(add_query_arg(array('delete_contract' => $row->document_id), admin_url('admin.php?page=view-all-contracts'))); ?>"
                            class="dashicons dashicons-trash dpcms-dashicons" title="Delete"
                            onclick="return confirm('Are you sure you want to delete this contract?');"></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="10">No contracts found with the provided criteria.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
// Pagination controls
if ($total_pages > 1) {
    echo '<div class="dpcms-tablenav-pages">';
    echo wp_kses_post(
        paginate_links(
            array(
                'base' => esc_url(add_query_arg('paged', '%#%')),
                'format' => '',
                'prev_text' => esc_html__('&laquo;', 'deluxe-wp-contracts'),
                'next_text' => esc_html__('&raquo;', 'deluxe-wp-contracts'),
                'total' => $total_pages,
                'current' => $current_page
            )
        )
    );
    echo '</div>';
}
?>
</div>
<?php
}

// Display Signed Contracts Page
function dpcms_display_signed_contracts_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . DPCMS_TABLE_CREATED_CONTRACTS;
    $current_user_id = get_current_user_id();
    $user_roles = dpcms_get_all_user_roles($current_user_id);

    $user_can_view_all = in_array('view_all_contracts', $user_roles) || in_array('administrator', $user_roles);
    $user_can_view_own = in_array('view_own_contracts', $user_roles);

    $subscription_type = dpcms_get_subscription_type(dpcms_id());

    if (!$user_can_view_all && !$user_can_view_own) {
        dpcms_check_capabilities('view_all_contracts', true);
    }

    $cache_key = $user_can_view_all ? 'signed_contracts_all_' . $current_user_id : 'signed_contracts_own_' . $current_user_id;
    $results = wp_cache_get($cache_key, 'dpcms');

    if ($results === false) {
        if ($user_can_view_all) {
            $query = "
                SELECT * 
                FROM $table_name 
                WHERE primary_signature IS NOT NULL 
                AND admin_signature IS NOT NULL 
                AND (co_signer_email IS NULL OR co_signer_signature IS NOT NULL)
            ";
        } else {
            $query = $wpdb->prepare(
                "
                SELECT * 
                FROM $table_name 
                WHERE primary_signature IS NOT NULL 
                AND admin_signature IS NOT NULL 
                AND (co_signer_email IS NULL OR co_signer_signature IS NOT NULL)
                AND wp_user_created_contract = %d",
                $current_user_id
            );
        }

        $results = $wpdb->get_results($query);

        // Store results in cache
        wp_cache_set($cache_key, $results, 'dpcms', HOUR_IN_SECONDS);
    }

    // Pagination setup
    $total_contracts = count($results);
    $contracts_per_page = 25;
    $total_pages = ceil($total_contracts / $contracts_per_page);
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $contracts_per_page;

    // Slice the results for current page
    $paged_results = array_slice($results, $offset, $contracts_per_page);
    ?>
<div class="wrap">
    <h1>Signed Contracts</h1>
    <?php $consent = dpcms_get_custom_option('dpcms_data_consent', '');
                        if (!$consent) {
                            echo '<strong>' . esc_html__('Please mark your consent to the privacy policy on the', 'deluxe-wp-contracts') . 
                            ' <a href="' . esc_url(admin_url('admin.php?page=dpcms-contracts-settings')) . '">' . 
                            esc_html__('settings page', 'deluxe-wp-contracts') . '</a>.</strong>';
                                } ?>
    <h2>Search Contracts</h2>
    <?php if ($subscription_type === 'free'): ?>
    <p>The redo feature is available for Premium and Deluxe users only. <a
            href="https://deluxeplugins.com/wp-contracts " style="text-decoration: none; color: blue;">Upgrade</a> to
        access this feature.</p>
    <?php else: ?>
    <?php endif; ?>
    <div class="dpcms-table-search">
        <input type="text" id="dpcms-searchDocumentId" placeholder="Search by Document ID">
        <input type="text" id="dpcms-searchFirstName" placeholder="Search by First Name">
        <input type="text" id="dpcms-searchLastName" placeholder="Search by Last Name">
        <div class="dpcms-checkbox-container">
            <label for="dpcms-searchStartDate">Start Date</label>
            <input type="date" id="dpcms-searchStartDate">
        </div>
        <div class="dpcms-checkbox-container">
            <label for="dpcms-searchEndDate">End Date</label>
            <input type="date" id="dpcms-searchEndDate">
        </div>
        <button id="dpcms-searchButton" type="button" class="button button-primary">Search Contracts</button>
    </div>
    <div class="dpcms-table-responsive">
        <table class="wp-list-table striped dpcms-table" id="dpcms-userTable">
            <thead>
                <tr>
                    <th>Document ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Date Signed</th>
                    <th>PDF</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($paged_results): ?>
                <?php foreach ($paged_results as $row): ?>
                <tr>
                    <td><?php echo esc_html($row->document_id); ?></td>
                    <td><?php echo esc_html($row->first_name); ?></td>
                    <td><?php echo esc_html($row->last_name); ?></td>
                    <td><?php echo esc_html($row->email); ?></td>
                    <td><?php echo esc_html($row->signed_date); ?></td>
                    <td class="dpcms-dashicon-td">
                        <a href="<?php echo esc_url(add_query_arg(array('view_contract' => $row->document_id), admin_url('admin.php?page=view-signed-contracts'))); ?>"
                            class="dashicons dashicons-media-text dpcms-dashicons" title="View PDF"></a>
                        <span></span>
                        <a href="<?php echo esc_url(add_query_arg(array('download_contract' => $row->document_id), admin_url('admin.php?page=view-signed-contracts'))); ?>"
                            class="dashicons dashicons-download dpcms-dashicons" title="Download PDF"></a>
                    </td>
                    <td class="dpcms-dashicon-td dpcms-actions">
                        <?php $edit_link = esc_url(add_query_arg(array('edit_contract' => $row->document_id), site_url('/new-contract'))); ?>
                        <?php if ($subscription_type === 'free'): ?>
                        <a href="#" class="dashicons dashicons-edit dpcms-dashicons" title="Upgrade to edit"
                            style="opacity: 0.5; pointer-events: none;"></a>
                        <?php else: ?>
                        <a href="<?php echo $edit_link; ?>" class="dashicons dashicons-edit dpcms-dashicons"
                            title="Redo" target="_blank"></a>
                        <?php endif; ?>
                        <span></span>
                        <a href="<?php echo esc_url(add_query_arg(array('delete_contract' => $row->document_id), admin_url('admin.php?page=view-signed-contracts'))); ?>"
                            class="dashicons dashicons-trash dpcms-dashicons" title="Delete"
                            onclick="return confirm('Are you sure you want to delete this contract?');"></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7">No signed contracts found with the provided criteria.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
                    // Pagination controls
                    if ($total_pages > 1) {
                        echo '<div class="dpcms-tablenav-pages">';
                        echo wp_kses_post(
                            paginate_links(
                                array(
                                    'base' => esc_url(add_query_arg('paged', '%#%')),
                                    'format' => '',
                                    'prev_text' => esc_html__('&laquo;', 'deluxe-wp-contracts'),
                                    'next_text' => esc_html__('&raquo;', 'deluxe-wp-contracts'),
                                    'total' => $total_pages,
                                    'current' => $current_page
                                )
                            )
                        );
                        echo '</div>';
                    }                    
                    ?>
</div>
<?php
}

// Display Contracts Settings Page
function dpcms_display_contracts_settings_page()
{
    if (isset($_POST['dpcms_save_main_settings'])) {
        check_admin_referer('dpcms_save_settings_verify');
        dpcms_save_main_settings();
    }

    ?>
<div class="wrap">
    <h1>Deluxe WP Contracts Settings</h1>
    <?php $consent = dpcms_get_custom_option('dpcms_data_consent', '');
                                if (!$consent) {
                                    echo '<strong>' . esc_html__('Please mark your consent to the privacy policy on the', 'deluxe-wp-contracts') . 
                                    ' <a href="' . esc_url(admin_url('admin.php?page=dpcms-contracts-settings')) . '">' . 
                                    esc_html__('settings page', 'deluxe-wp-contracts') . '</a>.</strong>';
                                        } ?>
    <form method="post" action="" enctype="multipart/form-data" id="dpcms-main-settings-form">
        <?php wp_nonce_field('dpcms_save_settings_verify'); ?>
        <?php dpcms_contract_settings_section_display(); ?>
        <?php submit_button('Save Settings', 'primary', 'dpcms_save_main_settings'); ?>
    </form>
    <div id="dpcms-scroll-buttons">
        <button id="dpcms-scroll-to-top">&#x25B2;</button>
        <button id="dpcms-scroll-to-bottom">&#x25BC;</button>
    </div>
</div>
<?php
}

function dpcms_save_main_settings()
{
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'dpcms_save_settings_verify')) {
        wp_die(esc_html__('Nonce verification failed.', 'deluxe-wp-contracts'));
    }

    if (isset($_POST['dpcms_license_key'])) {
        dpcms_set_custom_option('dpcms_license_key', sanitize_text_field($_POST['dpcms_license_key']));
    }

    if (isset($_POST['dpcms_api_key'])) {
        dpcms_set_custom_option('dpcms_api_key', sanitize_text_field($_POST['dpcms_api_key']));
    }

    if (isset($_POST['dpcms_data_consent'])) {
        dpcms_set_custom_option('dpcms_data_consent', sanitize_text_field($_POST['dpcms_data_consent']));
    } else {
        dpcms_set_custom_option('dpcms_data_consent', '');
    }

    if (isset($_FILES['dpcms_logo']) && !empty($_FILES['dpcms_logo']['tmp_name'])) {
        $uploaded = media_handle_upload('dpcms_logo', 0);
        if (is_wp_error($uploaded)) {
            wp_die(esc_html__('Error uploading logo: ', 'deluxe-wp-contracts') . esc_html($uploaded->get_error_message()));
        }
        dpcms_set_custom_option('dpcms_logo', esc_url_raw(wp_get_attachment_url($uploaded)));
    }
    
    if (isset($_FILES['dpcms_signature']) && !empty($_FILES['dpcms_signature']['tmp_name'])) {
        $uploaded = media_handle_upload('dpcms_signature', 0);
        if (is_wp_error($uploaded)) {
            wp_die(esc_html__('Error uploading signature: ', 'deluxe-wp-contracts') . esc_html($uploaded->get_error_message()));
        }
        dpcms_set_custom_option('dpcms_signature', esc_url_raw(wp_get_attachment_url($uploaded)));
    }
    

    if (isset($_POST['dpcms_company_details'])) {
        $company_details = wp_unslash($_POST['dpcms_company_details']);
        $company_details = array_map('sanitize_text_field', $company_details);
        dpcms_set_custom_option('dpcms_company_details', $company_details);
    }

    if (isset($_POST['dpcms_admin_email'])) {
        dpcms_set_custom_option('dpcms_admin_email', sanitize_email($_POST['dpcms_admin_email']));
    }

    if (isset($_POST['dpcms_from_email'])) {
        dpcms_set_custom_option('dpcms_from_email', sanitize_email($_POST['dpcms_from_email']));
    }

    if (isset($_POST['dpcms_from_name'])) {
        $from_name = sanitize_text_field(wp_unslash($_POST['dpcms_from_name']));
        dpcms_set_custom_option('dpcms_from_name', $from_name);
    }
    
    if (isset($_POST['dpcms_email_subject'])) {
        $email_subject = sanitize_text_field(wp_unslash($_POST['dpcms_email_subject']));
        dpcms_set_custom_option('dpcms_email_subject', $email_subject);
    }
    
    if (isset($_POST['dpcms_email_message'])) {
        $email_message = sanitize_textarea_field(wp_unslash($_POST['dpcms_email_message']));
        dpcms_set_custom_option('dpcms_email_message', $email_message);
    }

    if (isset($_POST['dpcms_pdf_user_password'])) {
        dpcms_set_custom_option('dpcms_pdf_user_password', sanitize_text_field($_POST['dpcms_pdf_user_password']));
    }

    if (isset($_POST['dpcms_pdf_owner_password'])) {
        dpcms_set_custom_option('dpcms_pdf_owner_password', sanitize_text_field($_POST['dpcms_pdf_owner_password']));
    }

    if (isset($_POST['dpcms_pdf_title'])) {
        dpcms_set_custom_option('dpcms_pdf_title', sanitize_text_field($_POST['dpcms_pdf_title']));
    }

    if (isset($_POST['dpcms_pdf_subject'])) {
        dpcms_set_custom_option('dpcms_pdf_subject', sanitize_text_field($_POST['dpcms_pdf_subject']));
    }

    if (isset($_POST['dpcms_pdf_keywords'])) {
        dpcms_set_custom_option('dpcms_pdf_keywords', sanitize_text_field($_POST['dpcms_pdf_keywords']));
    }

    if (isset($_POST['dpcms_pdf_protection'])) {
        $pdf_protection = array_map('sanitize_text_field', $_POST['dpcms_pdf_protection']);
        dpcms_set_custom_option('dpcms_pdf_protection', $pdf_protection);
    } else {
        dpcms_set_custom_option('dpcms_pdf_protection', array());
    }

    wp_redirect(admin_url('admin.php?page=dpcms-contracts-settings'));
}

function dpcms_save_create_contract_settings()
{
    // Nonce verification with wp_unslash
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'dpcms_save_settings_verify')) {
        wp_die(esc_html__('Nonce verification failed.', 'deluxe-wp-contracts'));
    }

    // Sanitize and save contract title
    if (isset($_POST['dpcms_contract_title'])) {
        dpcms_set_custom_option('dpcms_contract_title', sanitize_text_field($_POST['dpcms_contract_title']));
    }

    // Sanitize and save contract header
    if (isset($_POST['dpcms_contract_header'])) {
        dpcms_set_custom_option('dpcms_contract_header', sanitize_text_field($_POST['dpcms_contract_header']));
    }

    // Sanitize and save seller type
    if (isset($_POST['dpcms_seller_type'])) {
        dpcms_set_custom_option('dpcms_seller_type', sanitize_text_field($_POST['dpcms_seller_type']));
    }

    // Sanitize and save buyer type
    if (isset($_POST['dpcms_buyer_type'])) {
        dpcms_set_custom_option('dpcms_buyer_type', sanitize_text_field($_POST['dpcms_buyer_type']));
    }

    // Sanitize and save sale type
    if (isset($_POST['dpcms_sale_type'])) {
        dpcms_set_custom_option('dpcms_sale_type', sanitize_text_field($_POST['dpcms_sale_type']));
    }

    // Sanitize and save product sold
    if (isset($_POST['dpcms_product_sold'])) {
        dpcms_set_custom_option('dpcms_product_sold', sanitize_text_field($_POST['dpcms_product_sold']));
    }

    // Sanitize and save house specifications (or set to '0')
    dpcms_set_custom_option(
        'dpcms_add_house_specifications',
        isset($_POST['dpcms_add_house_specifications']) ? sanitize_text_field($_POST['dpcms_add_house_specifications']) : '0'
    );

    // Sanitize and save delivery address (or set to '0')
    dpcms_set_custom_option(
        'dpcms_add_delivery_address',
        isset($_POST['dpcms_add_delivery_address']) ? sanitize_text_field($_POST['dpcms_add_delivery_address']) : '0'
    );

    // Sanitize and save co-signer information (or set to '0')
    dpcms_set_custom_option(
        'dpcms_add_co_signer_information',
        isset($_POST['dpcms_add_co_signer_information']) ? sanitize_text_field($_POST['dpcms_add_co_signer_information']) : '0'
    );

    // Sanitize terms and conditions (use sanitize_textarea_field)
    if (isset($_POST['dpcms_terms_and_conditions'])) {
        $terms = wp_unslash($_POST['dpcms_terms_and_conditions']);
        dpcms_set_custom_option('dpcms_terms_and_conditions', sanitize_textarea_field($terms));
    }

    // Sanitize and save pricing calculator (or set to '0')
    dpcms_set_custom_option(
        'dpcms_add_pricing_calculator',
        isset($_POST['dpcms_add_pricing_calculator']) ? sanitize_text_field($_POST['dpcms_add_pricing_calculator']) : '0'
    );

    // Sanitize and save remaining balance percentage
    if (isset($_POST['dpcms_remaining_balance_percentage'])) {
        dpcms_set_custom_option('dpcms_remaining_balance_percentage', sanitize_text_field($_POST['dpcms_remaining_balance_percentage']));
    }

    // Sanitize dynamic fields
    if (isset($_POST['dpcms_dynamic_fields'])) {
        $dynamic_fields = dpcms_sanitize_dynamic_fields(wp_unslash($_POST['dpcms_dynamic_fields']));
        dpcms_set_custom_option('dpcms_dynamic_fields', $dynamic_fields);
    } else {
        dpcms_set_custom_option('dpcms_dynamic_fields', []);
    }

    // Sanitize and save other provisions (or set to '0')
    dpcms_set_custom_option(
        'dpcms_add_other_provisions',
        isset($_POST['dpcms_add_other_provisions']) ? sanitize_text_field($_POST['dpcms_add_other_provisions']) : '0'
    );

    // Sanitize and save signed by
    if (isset($_POST['dpcms_signed_by'])) {
        dpcms_set_custom_option('dpcms_signed_by', sanitize_text_field($_POST['dpcms_signed_by']));
    }

    // Sanitize and save custom signed by
    if (isset($_POST['dpcms_custom_signed_by'])) {
        dpcms_set_custom_option('dpcms_custom_signed_by', sanitize_text_field($_POST['dpcms_custom_signed_by']));
    }

    // Redirect after saving settings
    wp_redirect(admin_url('admin.php?page=create-contract-template'));
    exit;
}

function dpcms_handle_logo_upload($option)
{
    // Check if a file is uploaded
    if (!empty($_FILES['dpcms_logo']['tmp_name'])) {
        $uploaded = media_handle_upload('dpcms_logo', 0);
        if (is_wp_error($uploaded)) {
            wp_die(esc_html__('Error uploading logo: ', 'deluxe-wp-contracts') . esc_html($uploaded->get_error_message()));
        }
        $option = esc_url_raw(wp_get_attachment_url($uploaded));
    } else {
        // If no new file is uploaded, keep the existing option
        $existing_option = dpcms_get_custom_option('dpcms_logo');
        if ($existing_option) {
            return esc_url_raw($existing_option);
        }
    }
    return $option;
}

function dpcms_handle_signature_upload($option)
{
    // Check if a file is uploaded
    if (!empty($_FILES['dpcms_signature']['tmp_name'])) {
        $uploaded = media_handle_upload('dpcms_signature', 0);
        if (is_wp_error($uploaded)) {
            wp_die('Error uploading signature: ' . $uploaded->get_error_message());
        }
        $option = wp_get_attachment_url($uploaded);
    } else {
        // If no new file is uploaded, keep the existing option
        $existing_option = dpcms_get_custom_option('dpcms_signature');
        if ($existing_option) {
            return $existing_option;
        }
    }
    return $option;
}

function dpcms_contract_settings_section_display()
{
    dpcms_check_capabilities('access_to_settings', true);

    dpcms_license_key_display();
    echo '<p class="dpcms-description">Customize your contract settings below.</p>';

    echo '<div class="dpcms-section">';
    echo '<div class="dpcms-section-title">Company Information</div>';
    echo '<div class="dpcms-section-content">';
    dpcms_contract_logo_display();
    // dpcms_contract_signature_display();
    dpcms_contract_company_details_display();
    echo '</div></div>';

    echo '<div class="dpcms-section">';
    echo '<div class="dpcms-section-title">Email Management</div>';
    echo '<div class="dpcms-section-content">';
    dpcms_email_settings_display();
    echo '</div></div>';

    echo '<div class="dpcms-section">';
    echo '<div class="dpcms-section-title">PDF File Settings</div>';
    echo '<div class="dpcms-section-content">';
    dpcms_pdf_passwords_display();
    dpcms_pdf_protection_settings_display();
    dpcms_pdf_metadata_display();
    echo '</div></div>';
}

function dpcms_manage_rep_roles_page_display()
{
    echo '<div class="wrap">';
    echo '<h1 id="dpcms-rep-roles-title">Manage User Roles</h1>';
    $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    if (!$consent) {
        echo '<strong>' . esc_html__('Please mark your consent to the privacy policy on the', 'deluxe-wp-contracts') .
        ' <a href="' . esc_url(admin_url('admin.php?page=dpcms-contracts-settings')) . '">' .
        esc_html__('settings page', 'deluxe-wp-contracts') . '</a>.</strong>';
    }

    echo '<p>Here you can create new roles and modify the permissions of users to access all of the different features of the plugin.</p>';
    echo '<div class="dpcms-section">';
    echo '<div class="dpcms-section-title">Manage User Roles</div>';
    echo '<div class="dpcms-section-content">';
    dpcms_manage_roles_display();
    echo '</div></div>';

    echo '<div class="dpcms-section">';
    echo '<div class="dpcms-section-title">Assign User Roles</div>';
    echo '<div class="dpcms-section-content">';
    dpcms_assign_roles_page_display();
    echo '</div></div>';
}

function dpcms_license_key_display()
{
    $e_license_key = dpcms_get_custom_option('dpcms_license_key', '');
    $api_key = dpcms_get_custom_option('dpcms_api_key', '');

    $consent = dpcms_get_custom_option('dpcms_data_consent', '1');
    $checked = $consent ? 'checked' : '';
    ?>
<div style="margin: 10px 0 0 0;" id="dpcms-license-key-container">
    <h2 style="margin-top:0;">Enter License Key</h2>
    <input type="text" id="dpcms_license_key" value="<?php echo esc_attr($e_license_key); ?>"
        placeholder="Enter license key here"
        <?php echo (!empty($e_license_key) && !empty($api_key)) ? 'readonly' : ''; ?> />
    <h2>Enter API Key</h2>
    <input type="text" id="dpcms_api_key" name="dpcms_api_key" value="<?php echo esc_attr($api_key); ?>"
        placeholder="Enter API key here" />
    <br><br>

    <?php if (empty($e_license_key)) : ?>
    <button id="dpcms_license_key_activate" class="button button-primary" type="button">Submit</button>
    <?php endif; ?>

    <input type="hidden" id="dpcms_license_nonce"
        value="<?php echo esc_attr(wp_create_nonce('dpcms_validate_license_nonce')); ?>" />
    <div id="dpcms_license_key_message"></div>
    <br><input type="checkbox" id="dpcms_data_consent" name="dpcms_data_consent" value="1"
        <?php echo esc_attr($checked); ?> />
    <label for="dpcms_data_consent"> I consent to the <a
            href="https://deluxeplugins.com/deluxe-wp-contracts-privacy-policy/" target="_blank">privacy policy</a>.
        This
        plugin never stores any personal or client information.
        <br>If this is not marked on, contract generation and other plugin features will not work. Please make sure
        to press save at the bottom of the page after marking the box.</label>
</div>
<?php
}

function dpcms_contract_logo_display()
{
    $subscription_type = dpcms_get_subscription_type(dpcms_id());

    $logo = dpcms_get_custom_option('dpcms_logo', '');
    echo '<div>';
    if ($subscription_type === 'premium' || $subscription_type === 'deluxe') {
        echo '<label for="dpcms_logo">Company Logo</label><br>';
        echo '<input type="file" name="dpcms_logo" id="dpcms_logo" />';
        if ($logo) {
            echo '<div><img src="' . esc_url($logo) . '" style="max-width: 200px; margin-top: 10px;" /></div>';
        }
    } else {
        echo '<label style="opacity: 0.5;">Company Logo</label><br>';
        echo '<input type="file" disabled style="opacity: 0.5;" />';
        if ($logo) {
            echo '<div style="opacity: 0.5;"><img src="' . esc_url($logo) . '" style="max-width: 200px; margin-top: 10px;" /></div>';
        }
        echo '<p>This feature is available for Premium and Deluxe users only. <a href="https://deluxeplugins.com/wp-contracts " style="text-decoration: none; color: blue;">Upgrade</a> to access this feature.</p>';
    }
    echo '</div>';
}

function dpcms_contract_signature_display()
{
    $signature = dpcms_get_custom_option('dpcms_signature');
    echo '<label for="dpcms_signature">Signature Image</label><br>';
    echo '<input type="file" name="dpcms_signature" id="dpcms_signature" />';
    if ($signature) {
        echo '<div><img src="' . esc_url($signature) . '" style="max-width: 200px; margin-top: 10px;" /></div>';
    }
}

function dpcms_contract_company_details_display()
{
    // Fetch existing company details from options
    $details = dpcms_get_custom_option(
        'dpcms_company_details',
        array(
            'name' => 'Company Name',
            'street_address' => '1234 Example St.',
            'city' => 'Example City',
            'state' => 'EX',
            'zip_code' => '12345',
            'phone' => '1234567890',
            'email' => 'example@yourcompany.com',
            'website' => 'www.example.com'
        )
    );

    // Ensure the details array has all the fields
    $details = wp_parse_args(
        $details,
        array(
            'name' => 'Company Name',
            'street_address' => '1234 Example St.',
            'city' => 'New York City',
            'state' => 'NY',
            'zip_code' => '12345',
            'phone' => '5555555555',
            'email' => 'example@yourcompany.com',
            'website' => 'www.example.com'
        )
    );

    echo '<div style="margin-bottom: 20px;">';
    echo '<h3>Company Information</h3>';
    echo '<p>This section is your information that will show up at the top of the contract</p>';
    echo '<label for="dpcms_company_name">Company Name</label><br>';
    echo '<input type="text" name="dpcms_company_details[name]" id="contract_company_name" value="' . esc_attr($details['name']) . '" style="width: 100%;"><br><br>';
    echo '</div>';

    echo '<div style="margin-bottom: 20px;">';
    echo '<h3>Address</h3>';
    echo '<label for="dpcms_company_street_address">Street Address</label><br>';
    echo '<input type="text" name="dpcms_company_details[street_address]" id="contract_company_street_address" value="' . esc_attr($details['street_address']) . '" style="width: 100%;"><br><br>';

    echo '<label for="dpcms_company_city">City</label><br>';
    echo '<input type="text" name="dpcms_company_details[city]" id="contract_company_city" value="' . esc_attr($details['city']) . '" style="width: 100%;"><br><br>';

    echo '<label for="dpcms_company_state">State</label><br>';
    echo '<input type="text" name="dpcms_company_details[state]" id="contract_company_state" value="' . esc_attr($details['state']) . '" style="width: 100%;"><br><br>';

    echo '<label for="dpcms_company_zip_code">Zip Code</label><br>';
    echo '<input type="text" name="dpcms_company_details[zip_code]" id="contract_company_zip_code" value="' . esc_attr($details['zip_code']) . '" style="width: 100%;"><br><br>';
    echo '</div>';

    echo '<div style="margin-bottom: 20px;">';
    echo '<h3>Contact Information</h3>';
    echo '<label for="dpcms_company_phone">Company Phone</label><br>';
    echo '<input type="text" name="dpcms_company_details[phone]" id="contract_company_phone" value="' . esc_attr($details['phone']) . '" style="width: 100%;"><br><br>';

    echo '<label for="dpcms_company_email">Company Email</label><br>';
    echo '<input type="email" name="dpcms_company_details[email]" id="contract_company_email" value="' . esc_attr($details['email']) . '" style="width: 100%;"><br><br>';

    echo '<label for="dpcms_company_website">Company Website</label><br>';
    echo '<input type="text" name="dpcms_company_details[website]" id="contract_company_website" value="' . esc_attr($details['website']) . '" style="width: 100%;"><br><br>';
    echo '</div>';
}

function dpcms_email_settings_display()
{
    $subscription_type = dpcms_get_subscription_type(dpcms_id());
    $consent = dpcms_get_custom_option('dpcms_data_consent', '0');

    $admin_email = dpcms_get_custom_option('dpcms_admin_email', get_option('admin_email'));
    $from_email = dpcms_get_custom_option('dpcms_from_email', get_option('admin_email'));
    $from_name = dpcms_get_custom_option('dpcms_from_name', dpcms_get_site_domain_name());
    $email_subject = dpcms_get_custom_option('dpcms_email_subject', 'Action Required: Sign the Agreement');
    $email_message = dpcms_get_custom_option('dpcms_email_message', '');

    $is_paid = $subscription_type !== 'free';
    $disabled_attr = $is_paid ? '' : 'disabled';
    $from_email_name = $is_paid ? 'dpcms_from_email' : '';
    $from_name_name = $is_paid ? 'dpcms_from_name' : '';
    ?>
<h3 style="margin-top:0;">Email Settings</h3>

<?php if(!$is_paid) : ?>
<p>Some of these settings are available for Premium and Deluxe users only.
    <a href="https://deluxeplugins.com/wp-contracts" style="text-decoration: none; color: blue;">
        Upgrade
    </a>
    to access this feature.
</p>
<?php endif; ?>

<label for="dpcms_admin_email">Admin Email</label>
<input type="email" name="dpcms_admin_email" value="<?php echo esc_attr($admin_email); ?>" placeholder="Admin Email">
<p style="margin-left: 20px; font-style: italic;">
    This is the email address where notifications and documents will be sent to you.
    Default is the admin email of this domain.
</p>

<label for="<?php echo esc_attr($from_email_name); ?>">From Email</label>
<input type="email" name="<?php echo esc_attr($from_email_name); ?>" value="<?php echo esc_attr($from_email); ?>"
    placeholder="From Email" <?php echo esc_attr($disabled_attr); ?>>
<p style="margin-left: 20px; font-style: italic;">
    This email address will be used for sending notifications and documents.
    If left blank, the default admin email of this domain will be used.
</p>

<label for="<?php echo esc_attr($from_name_name); ?>">From Name</label>
<input type="text" name="<?php echo esc_attr($from_name_name); ?>" value="<?php echo esc_attr($from_name); ?>"
    placeholder="From Name" <?php echo esc_attr($disabled_attr); ?>>
<p style="margin-left: 20px; font-style: italic;">
    This text will be used for showing who the notifications and documents are from.
    If left blank, the domain name will be used.
</p>

<label for="dpcms_email_subject">Email Subject</label>
<input type="text" name="dpcms_email_subject" value="<?php echo esc_attr($email_subject); ?>"
    placeholder="Email Subject">
<p style="margin-left: 20px; font-style: italic;">
    This is the subject of the email notification to the customer.
    Leave as the default if unsure.
</p>

<label for="dpcms_email_message">Email Message</label>
<textarea name="dpcms_email_message" rows="5" cols="50" placeholder="Email Message">
<?php echo esc_textarea($email_message); ?>
</textarea>
<p style="margin-left: 20px; font-style: italic;">
    This is the body of the email notification. The link to sign the contract will be after this message.
    If you are unsure of what to say, leave it blank. It will say:
</p>
Dear First Last,<br><br>
<?php echo !empty($email_message) ? esc_textarea($email_message) : "Your email message will go here. If you leave it blank, this line will disappear."; ?><br><br>
Please sign using the following link: www.example.com/sign-contract <br><br>
Thank you.

<?php
}

function dpcms_add_house_specifications_checkbox_display()
{
    $add_house_specifications = dpcms_get_custom_option('dpcms_add_house_specifications', false);
    ?>
<label for="dpcms_add_house_specifications">
    <input type="checkbox" name="dpcms_add_house_specifications" value="1"
        <?php checked($add_house_specifications, 1); ?>>
    Add "House Specifications" section to your contract
</label>
<br>
<?php
}

function dpcms_add_delivery_address_display()
{
    $add_delivery_address = dpcms_get_custom_option('dpcms_add_delivery_address', false);
    ?>
<label for="dpcms_add_delivery_address">
    <input type="checkbox" name="dpcms_add_delivery_address" value="1" <?php checked($add_delivery_address, 1); ?>>
    Add "Delivery Address" section to your contract
</label>
<br>
<?php
}

function dpcms_add_co_signer_information_display()
{
    $add_co_signer_information = dpcms_get_custom_option('dpcms_add_co_signer_information', false);
    ?>
<label for="dpcms_add_co_signer_information">
    <input type="checkbox" name="dpcms_add_co_signer_information" value="1"
        <?php checked($add_co_signer_information, 1); ?>>
    Add "Co-signer" section to your contract
</label>
<br>
<?php
}

function dpcms_contract_title_display()
{
    $contract_title = dpcms_get_custom_option('dpcms_contract_title', 'Agreement');
    $contract_header = dpcms_get_custom_option('dpcms_contract_header', 'This document is an Agreement');
    $product_sold = dpcms_get_custom_option('dpcms_product_sold', 'a Home');

    $seller_type = dpcms_get_custom_option('dpcms_seller_type', 'Seller');
    $buyer_type = dpcms_get_custom_option('dpcms_buyer_type', 'Buyer');
    $sale_type = dpcms_get_custom_option('dpcms_sale_type', 'sale and purchase');

    // Checking if custom values were previously set
    $custom_seller_type = ($seller_type === 'Other') ? dpcms_get_custom_option('dpcms_custom_seller_type', '') : '';
    $custom_buyer_type = ($buyer_type === 'Other') ? dpcms_get_custom_option('dpcms_custom_buyer_type', '') : '';
    $custom_sale_type = ($sale_type === 'Other') ? dpcms_get_custom_option('dpcms_custom_sale_type', '') : '';

    ?>
<h3 style="margin-top:0;">Contract Header Settings</h3>
<label for="dpcms_contract_title">
    Add a title to your contract.
</label>
<input type="text" name="dpcms_contract_title" value="<?php echo esc_attr($contract_title); ?>"
    placeholder="<?php echo esc_attr($contract_title); ?>">
<br><br>

<label for="dpcms_contract_header">
    The text that goes below the contract title.
</label>
<textarea name="dpcms_contract_header"
    placeholder="<?php echo esc_attr($contract_header); ?>"><?php echo esc_textarea($contract_header); ?></textarea>
<br>

<label for="dpcms_seller_type">Select type of seller:</label>
<select name="dpcms_seller_type" id="dpcms_seller_type" onchange="toggleCustomOption(this, 'dpcms_custom_seller_type')">
    <option value="Seller" <?php selected($seller_type, 'Seller'); ?>>Seller</option>
    <option value="Individual Seller" <?php selected($seller_type, 'Individual Seller'); ?>>Individual Seller</option>
    <option value="Corporate Seller" <?php selected($seller_type, 'Corporate Seller'); ?>>Corporate Seller</option>
    <option value="Real Estate Agent" <?php selected($seller_type, 'Real Estate Agent'); ?>>Real Estate Agent</option>
    <option value="Broker" <?php selected($seller_type, 'Broker'); ?>>Broker</option>
    <option value="Manufacturer" <?php selected($seller_type, 'Manufacturer'); ?>>Manufacturer</option>
    <option value="Distributor" <?php selected($seller_type, 'Distributor'); ?>>Distributor</option>
    <option value="Wholesaler" <?php selected($seller_type, 'Wholesaler'); ?>>Wholesaler</option>
    <option value="Auctioneer" <?php selected($seller_type, 'Auctioneer'); ?>>Auctioneer</option>
    <option value="Franchisee" <?php selected($seller_type, 'Franchisee'); ?>>Franchisee</option>
    <option value="Supplier" <?php selected($seller_type, 'Supplier'); ?>>Supplier</option>
    <option value="Other" <?php selected($seller_type, 'Other'); ?>>Other</option>
</select>
<input type="text" name="dpcms_custom_seller_type" id="dpcms_custom_seller_type"
    style="display: <?php echo ($seller_type === 'Other') ? 'block' : 'none'; ?>;"
    placeholder="Enter custom seller type" value="<?php echo esc_attr($custom_seller_type); ?>">
<br><br>

<label for="dpcms_buyer_type">Select type of buyer:</label>
<select name="dpcms_buyer_type" id="dpcms_buyer_type" onchange="toggleCustomOption(this, 'dpcms_custom_buyer_type')">
    <option value="Buyer" <?php selected($buyer_type, 'Buyer'); ?>>Buyer</option>
    <option value="Individual Buyer" <?php selected($buyer_type, 'Individual Buyer'); ?>>Individual Buyer</option>
    <option value="Corporate Buyer" <?php selected($buyer_type, 'Corporate Buyer'); ?>>Corporate Buyer</option>
    <option value="Investor" <?php selected($buyer_type, 'Investor'); ?>>Investor</option>
    <option value="Retail Buyer" <?php selected($buyer_type, 'Retail Buyer'); ?>>Retail Buyer</option>
    <option value="Wholesale Buyer" <?php selected($buyer_type, 'Wholesale Buyer'); ?>>Wholesale Buyer</option>
    <option value="Government Agency" <?php selected($buyer_type, 'Government Agency'); ?>>Government Agency</option>
    <option value="Non-Profit Organization" <?php selected($buyer_type, 'Non-Profit Organization'); ?>>Non-Profit
        Organization</option>
    <option value="Reseller" <?php selected($buyer_type, 'Reseller'); ?>>Reseller</option>
    <option value="Contractor" <?php selected($buyer_type, 'Contractor'); ?>>Contractor</option>
    <option value="End User" <?php selected($buyer_type, 'End User'); ?>>End User</option>
    <option value="Other" <?php selected($buyer_type, 'Other'); ?>>Other</option>
</select>
<input type="text" name="dpcms_custom_buyer_type" id="dpcms_custom_buyer_type"
    style="display: <?php echo ($buyer_type === 'Other') ? 'block' : 'none'; ?>;" placeholder="Enter custom buyer type"
    value="<?php echo esc_attr($custom_buyer_type); ?>">
<br><br>

<label for="dpcms_sale_type">Select type of sale:</label>
<select name="dpcms_sale_type" id="dpcms_sale_type" onchange="toggleCustomOption(this, 'dpcms_custom_sale_type')">
    <option value="sale and purchase" <?php selected($sale_type, 'sale and purchase'); ?>>Sale and Purchase</option>
    <option value="rental" <?php selected($sale_type, 'rental'); ?>>Rental</option>
    <option value="lease" <?php selected($sale_type, 'lease'); ?>>Lease</option>
    <option value="exchange" <?php selected($sale_type, 'exchange'); ?>>Exchange</option>
    <option value="gift" <?php selected($sale_type, 'gift'); ?>>Gift</option>
    <option value="Other" <?php selected($sale_type, 'Other'); ?>>Other</option>
</select>
<input type="text" name="dpcms_custom_sale_type" id="dpcms_custom_sale_type"
    style="display: <?php echo ($sale_type === 'Other') ? 'block' : 'none'; ?>;" placeholder="Enter custom sale type"
    value="<?php echo esc_attr($custom_sale_type); ?>">
<br><br>

<label for="dpcms_product_sold">The product or service that you sell.</label>
<input name="dpcms_product_sold" value="<?php echo esc_attr($product_sold); ?>"
    placeholder="<?php echo esc_attr($product_sold); ?>">
<br><br>

<?php
}

function dpcms_add_other_provisions_checkbox_display()
{
    $add_other_provisions = dpcms_get_custom_option('dpcms_add_other_provisions', false);
    ?>
<label for="dpcms_add_other_provisions">
    <input type="checkbox" name="dpcms_add_other_provisions" value="1" <?php checked($add_other_provisions, 1); ?>>
    Add "Other Provisions" Textbox to your contract
</label>
<?php
}

function dpcms_terms_and_conditions_display()
{
    $terms = dpcms_get_custom_option('dpcms_terms_and_conditions', 'Default terms and conditions...');

    $industry_examples = dpcms_get_contract_examples();

    echo '<label for="dpcms_industry_select">Industry</label><br>';
    echo '<select id="dpcms_industry_select" onchange="updateContractTypeDropdown()">';
    echo '<option value="">Select an industry</option>';
    foreach ($industry_examples as $industry => $contracts) {
        echo '<option value="' . esc_attr($industry) . '">' . esc_html(ucwords(str_replace('_', ' ', $industry))) . '</option>';
    }
    echo '</select><br><br>';

    echo '<label for="dpcms_terms_select">Contract Type</label><br>';
    echo '<select id="dpcms_terms_select" onchange="populateTerms()">';
    echo '<option value="">Select a contract type</option>';
    echo '</select><br><br>';
    echo '<p>You are welcome to modify the example contracts. These changes will be saved when you save the settings. Please note that selecting and saving a different contract type will overwrite any modifications you have made.</p>';
    echo '<textarea name="dpcms_terms_and_conditions" id="dpcms_terms_and_conditions" rows="10" style="width: 100%;">' . esc_html($terms) . '</textarea>';
}

function dpcms_dynamic_fields_display()
{
    $dynamic_fields = dpcms_get_custom_option('dpcms_dynamic_fields', []);

    if (!is_array($dynamic_fields)) {
        $dynamic_fields = [];
    }

    ?>
<div id="dpcms-dynamic-fields-container">
    <?php foreach ($dynamic_fields as $index => $field_group): ?>
    <div class="dpcms-field-group" data-index="<?php echo esc_attr($index); ?>" style="margin-bottom: 15px;">
        <label for="dpcms_dynamic_fields[<?php echo esc_attr($index); ?>][header]">Header:</label>
        <input type="text" name="dpcms_dynamic_fields[<?php echo esc_attr($index); ?>][header]"
            value="<?php echo esc_attr($field_group['header']); ?>" style="width: 60%;" required>
        <div class="dpcms-fields">
            <?php foreach ($field_group['fields'] as $field_index => $field): ?>
            <div class="dpcms-field" data-index="<?php echo esc_attr($field_index); ?>"
                style="display: flex; align-items: center; margin-bottom: 10px;">
                <label
                    for="dpcms_dynamic_fields[<?php echo esc_attr($index); ?>][fields][<?php echo esc_attr($field_index); ?>][type]"
                    style="margin-right: 5px;">Field Type:</label>
                <select
                    name="dpcms_dynamic_fields[<?php echo esc_attr($index); ?>][fields][<?php echo esc_attr($field_index); ?>][type]"
                    class="dpcms-field-type-select" style="margin-right: 10px;">
                    <option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
                    <option value="number" <?php selected($field['type'], 'number'); ?>>Number</option>
                    <option value="list" <?php selected($field['type'], 'list'); ?>>Dynamic List</option>
                </select>
                <label
                    for="dpcms_dynamic_fields[<?php echo esc_attr($index); ?>][fields][<?php echo esc_attr($field_index); ?>][label]"
                    style="margin-right: 5px;">Field Label:</label>
                <input type="text"
                    name="dpcms_dynamic_fields[<?php echo esc_attr($index); ?>][fields][<?php echo esc_attr($field_index); ?>][label]"
                    value="<?php echo esc_attr($field['label']); ?>" style="margin-right: 10px;" required>
                <label style="margin-right: 5px;">Required:</label>
                <input type="checkbox"
                    name="dpcms_dynamic_fields[<?php echo esc_attr($index); ?>][fields][<?php echo esc_attr($field_index); ?>][required]"
                    value="1" class="field-required-checkbox" <?php checked(isset($field['required']), '1'); ?>>
                <span class="dpcms-dynamic-list-notice" style="display: none;"></span>
                <button type="button" class="dpcms-remove-field" style="margin-left: auto;">Remove Field</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="dpcms-add-field" style="margin-top: 10px;">Add Field</button>
        <button type="button" class="dpcms-remove-field-group" style="margin-top: 10px; margin-left: 10px;">Remove
            Header
        </button>
        <div class="dpcms-move-buttons">
            <button type="button" class="dpcms-move-up">↑</button>
            <button type="button" class="dpcms-move-down">↓</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<button type="button" id="dpcms-add-field-group" style="margin-top: 15px;">Add Header</button>
<?php
}

function dpcms_add_pricing_calculator_checkbox_display()
{
    $add_pricing_calculator = dpcms_get_custom_option('dpcms_add_pricing_calculator', false);
    ?>
<label for="dpcms_add_pricing_calculator">
    <input type="checkbox" name="dpcms_add_pricing_calculator" id="dpcms_add_pricing_calculator" value="1"
        <?php checked($add_pricing_calculator, 1); ?>>
    Add the pricing calculator to your contract
</label>
<br>
<?php
}

function dpcms_remaining_balance_percentage_display()
{
    $percentage = dpcms_get_custom_option('dpcms_remaining_balance_percentage', 50);
    echo '<label for="dpcms_remaining_balance_percentage">Remaining Balance Percentage</label><br>';
    echo '<input type="number" name="dpcms_remaining_balance_percentage" id="dpcms_remaining_balance_percentage" value="' . esc_attr($percentage) . '" />%';
}

function dpcms_manage_roles_display()
{
    $subscription_type = dpcms_get_subscription_type(dpcms_id());

    $is_disabled = ($subscription_type !== 'premium' && $subscription_type !== 'deluxe');
    $disabled_attr = $is_disabled ? 'disabled' : '';
    $opacity_style = $is_disabled ? 'style="opacity: 0.5; pointer-events: none;"' : '';
    ?>
<?php if ($is_disabled) : ?>
<p style="opacity: 1;">
    <?php esc_html_e('This feature is available for Premium and Deluxe users only.', 'deluxe-wp-contracts'); ?>
    <a href="<?php echo esc_url('https://deluxeplugins.com/wp-contracts'); ?>"
        style="text-decoration: none; color: blue;">
        <?php esc_html_e('Upgrade', 'deluxe-wp-contracts'); ?>
    </a>
    <?php esc_html_e('to access this feature.', 'deluxe-wp-contracts'); ?>
</p>
<?php endif; ?>
<div id="dpcms-roles-container" <?php echo $opacity_style; ?>>
    <input type="hidden" id="dpcms-roles-nonce" value="<?php echo wp_create_nonce('dpcms_manage_roles_nonce'); ?>">
    <div id="dpcms-add-role">
        <h3><?php esc_html_e('Add New Role', 'deluxe-wp-contracts'); ?></h3>
        <div class="dpcms-form-row">
            <label for="dpcms-new-role-name" <?php echo $is_disabled ? 'style="color: #999;"' : ''; ?>>
                <?php esc_html_e('New Role Name:', 'deluxe-wp-contracts'); ?>
            </label>
            <input type="text" id="dpcms-new-role-name"
                placeholder="<?php esc_attr_e('Enter new role name', 'deluxe-wp-contracts'); ?>"
                <?php echo esc_attr($disabled_attr); ?>>
        </div>
        <div class="dpcms-form-row">
            <label <?php echo $is_disabled ? 'style="color: #999;"' : ''; ?>>
                <?php esc_html_e('Role Capabilities:', 'deluxe-wp-contracts'); ?>
            </label>
            <div id="dpcms-add-capability-container" <?php echo $is_disabled ? 'style="opacity: 0.5;"' : ''; ?>>
                <!-- Checkboxes will be populated here -->
            </div>
        </div>
        <button id="dpcms-add-new-role" class="button button-primary" <?php echo esc_attr($disabled_attr); ?>>
            <?php esc_html_e('Add New Role', 'deluxe-wp-contracts'); ?>
        </button>
    </div>
    <div id="dpcms-existing-roles">
        <h3><?php esc_html_e('Update/Remove Role', 'deluxe-wp-contracts'); ?></h3>
        <div class="dpcms-form-row">
            <label for="dpcms-role-dropdown" <?php echo $is_disabled ? 'style="color: #999;"' : ''; ?>>
                <?php esc_html_e('Select a Role:', 'deluxe-wp-contracts'); ?>
            </label>
            <select id="dpcms-role-dropdown" <?php echo esc_attr($disabled_attr); ?>>
                <option value=""><?php esc_html_e('Select a role', 'deluxe-wp-contracts'); ?></option>
                <!-- Options will be populated here -->
            </select>
        </div>
        <div class="dpcms-form-row">
            <label <?php echo $is_disabled ? 'style="color: #999;"' : ''; ?>>
                <?php esc_html_e('Role Capabilities:', 'deluxe-wp-contracts'); ?>
            </label>
            <div id="dpcms-existing-capability-container" <?php echo $is_disabled ? 'style="opacity: 0.5;"' : ''; ?>>
                <!-- Checkboxes will be populated here -->
            </div>
        </div>
        <button id="dpcms-update-role" class="button button-secondary" <?php echo esc_attr($disabled_attr); ?>>
            <?php esc_html_e('Update Role', 'deluxe-wp-contracts'); ?>
        </button>
        <button id="dpcms-remove-role" class="button button-secondary" <?php echo esc_attr($disabled_attr); ?>>
            <?php esc_html_e('Remove Role', 'deluxe-wp-contracts'); ?>
        </button>
    </div>
</div>
<?php
}

function dpcms_assign_roles_page_display()
{
    $subscription_type = dpcms_get_subscription_type(dpcms_id());

    $users_per_page = 25;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $users_per_page;

    // Fetch the users for the current page
    $args = array(
        'number' => $users_per_page,
        'offset' => $offset,
    );

    $users = get_users($args);
    $total_users = count(get_users());
    $total_pages = ceil($total_users / $users_per_page);

    $is_disabled = ($subscription_type !== 'premium' && $subscription_type !== 'deluxe');
    $disabled_attr = $is_disabled ? 'disabled' : '';
    $opacity_style = $is_disabled ? 'style="opacity: 0.5; pointer-events: none;"' : '';
    ?>
<div class="wrap">
    <?php if ($is_disabled) : ?>
    <p>
        <?php esc_html_e('This feature is available for Premium and Deluxe users only.', 'deluxe-wp-contracts'); ?>
        <a href="<?php echo esc_url('https://deluxeplugins.com/wp-contracts'); ?>"
            style="text-decoration: none; color: blue;">
            <?php esc_html_e('Upgrade', 'deluxe-wp-contracts'); ?>
        </a>
        <?php esc_html_e('to access this feature.', 'deluxe-wp-contracts'); ?>
    </p>
    <?php endif; ?>
    <div <?php echo $opacity_style; ?>>
        <div id="dpcms-assign-dpcms-roles-container">
            <?php wp_nonce_field('dpcms_assign_roles', 'dpcms_assign_roles_nonce'); ?>
            <div class="dpcms-form-group dpcms-flex-container">
                <div class="dpcms-form-row">
                    <label for="user_id" <?php echo $is_disabled ? 'style="color: #999;"' : ''; ?>>
                        <?php esc_html_e('Select User', 'deluxe-wp-contracts'); ?>
                    </label>
                    <input type="text" id="dpcms-user_search"
                        placeholder="<?php esc_attr_e('Search for user...', 'deluxe-wp-contracts'); ?>"
                        <?php echo esc_attr($disabled_attr); ?>>
                    <select name="user_id" id="user_id" <?php echo esc_attr($disabled_attr); ?>>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->user_email); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="dpcms-form-row">
                    <label for="dpcms-role" <?php echo $is_disabled ? 'style="color: #999;"' : ''; ?>>
                        <?php esc_html_e('Select Role', 'deluxe-wp-contracts'); ?>
                    </label>
                    <select name="dpcms-role" id="dpcms-role" <?php echo esc_attr($disabled_attr); ?>>
                        <!-- Options will be populated here by JavaScript -->
                    </select>
                </div>
            </div>
            <button id="dpcms-assign-role-button" type="button" class="button button-primary"
                <?php echo esc_attr($disabled_attr); ?>>
                <?php esc_html_e('Assign Role', 'deluxe-wp-contracts'); ?>
            </button>
            <button id="dpcms-unassign-role-button" type="button" class="button button-secondary"
                <?php echo esc_attr($disabled_attr); ?>>
                <?php esc_html_e('Unassign Role', 'deluxe-wp-contracts'); ?>
            </button>
        </div>
        <h2><?php esc_html_e('Current Users and Their Roles', 'deluxe-wp-contracts'); ?></h2>
        <button id="dpcms-toggleTableButton" type="button" class="button button-primary"
            <?php echo esc_attr($disabled_attr); ?>>
            <?php esc_html_e('Show/Hide Table', 'deluxe-wp-contracts'); ?>
        </button>
        <div class="dpcms-table-toggle" style="display:block;">
            <input type="text" id="dpcms-searchId"
                placeholder="<?php esc_attr_e('Search by ID', 'deluxe-wp-contracts'); ?>"
                <?php echo esc_attr($disabled_attr); ?>>
            <input type="text" id="dpcms-searchName"
                placeholder="<?php esc_attr_e('Search by Name', 'deluxe-wp-contracts'); ?>"
                <?php echo esc_attr($disabled_attr); ?>>
            <input type="text" id="dpcms-searchEmail"
                placeholder="<?php esc_attr_e('Search by Email', 'deluxe-wp-contracts'); ?>"
                <?php echo esc_attr($disabled_attr); ?>>
            <input type="text" id="dpcms-searchRole"
                placeholder="<?php esc_attr_e('Search by Role', 'deluxe-wp-contracts'); ?>"
                <?php echo esc_attr($disabled_attr); ?>>
        </div>
        <table id="dpcms-userTable" class="wp-list-table widefat fixed striped dpcms-table-toggle"
            style="display:table;">
            <thead>
                <tr>
                    <th><?php esc_html_e('User ID', 'deluxe-wp-contracts'); ?></th>
                    <th><?php esc_html_e('Name', 'deluxe-wp-contracts'); ?></th>
                    <th><?php esc_html_e('Email', 'deluxe-wp-contracts'); ?></th>
                    <th><?php esc_html_e('Roles', 'deluxe-wp-contracts'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo esc_html($user->ID); ?></td>
                    <td><?php echo esc_html($user->display_name); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php $all_roles = dpcms_get_all_user_roles($user->ID); echo esc_html(implode(', ', $all_roles)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
            // Pagination controls
            if ($total_pages > 1) {
                echo '<div class="dpcms-tablenav-pages">';
                echo wp_kses_post(
                    paginate_links(
                        array(
                            'base' => esc_url(add_query_arg('paged', '%#%')),
                            'format' => '',
                            'prev_text' => esc_html__('&laquo;', 'deluxe-wp-contracts'),
                            'next_text' => esc_html__('&raquo;', 'deluxe-wp-contracts'),
                            'total' => $total_pages,
                            'current' => $current_page
                        )
                    )
                );
                echo '</div>';
            }
            ?>
    </div>
</div>
<?php
}

function dpcms_pdf_passwords_display()
{
    $user_password = dpcms_get_custom_option('dpcms_pdf_user_password', '');
    $owner_password = dpcms_get_custom_option('dpcms_pdf_owner_password', '1234');
    ?>
<h3 style="margin-top:0;">PDF Passwords</h3>
<label for="dpcms_pdf_user_password">
    User Password
    <input type="text" name="dpcms_pdf_user_password" value="<?php echo esc_attr($user_password); ?>"
        placeholder="User Password">
    <p style="margin-left: 20px; font-style: italic;">This password restricts users from opening the PDF document
        without entering it upon opening the file. </p>
    <p style="margin-left: 20px; font-style: italic;">There is no default password. If this setting is not changed,
        anyone who receives the file can open it without a password.</p>
</label>
<label for="dpcms_pdf_owner_password">
    Owner Password
    <input type="text" name="dpcms_pdf_owner_password" value="<?php echo esc_attr($owner_password); ?>"
        placeholder="Owner Password">
    <p style="margin-left: 20px; font-style: italic;">This password restricts users from changing the PDF permissions
        and passwords. Default is set to 1234.</p>
</label>
<?php
}

function dpcms_pdf_protection_settings_display()
{
    $protection_options = dpcms_get_custom_option(
        'dpcms_pdf_protection',
        array(
            'print',
            'copy',
        )
    );

    $available_options = array(
        'print' => 'Print',
        'copy' => 'Copy',
        'modify' => 'Modify',
        'annot-forms' => 'Annotate Forms',
        'fill-forms' => 'Fill Forms',
        'extract' => 'Extract',
        'assemble' => 'Assemble',
        'print-high' => 'High-Quality Print'
    );

    $explanations = array(
        'print' => 'Allows printing of the document',
        'copy' => 'Allows copying text and graphics from the document',
        'modify' => 'Allows modification of the document',
        'annot-forms' => 'Allows adding annotations and form fields',
        'fill-forms' => 'Allows filling in existing form fields',
        'extract' => 'Allows extracting text and graphics from the document for accessibility',
        'assemble' => 'Allows inserting, rotating, or deleting pages and creating bookmarks or thumbnails',
        'print-high' => 'Allows printing of the document in high quality'
    );
    ?>
<h3 style="margin-top:2.5em;">PDF Permissions</h3>
<?php foreach ($available_options as $option => $label): ?>
<label for="dpcms_pdf_protection_<?php echo esc_attr($option); ?>">
    <input type="checkbox" name="dpcms_pdf_protection[]" value="<?php echo esc_attr($option); ?>"
        id="dpcms_pdf_protection_<?php echo esc_attr($option); ?>"
        <?php checked(in_array($option, $protection_options), 1); ?>>
    <?php echo esc_html($label); ?>
    <span
        style="margin-left: 20px; font-style: italic; font-weight: normal;"><?php echo esc_html('- ' . $explanations[$option]); ?></span>
</label>
<?php endforeach; ?>
<?php
}

function dpcms_pdf_metadata_display()
{
    $pdf_title = dpcms_get_custom_option('dpcms_pdf_title', 'Contract');
    $pdf_subject = dpcms_get_custom_option('dpcms_pdf_subject', 'Agreement');
    $pdf_keywords = dpcms_get_custom_option('dpcms_pdf_keywords', 'Contract, Agreement, Terms, Purchase, Sale, Document');
    ?>
<h3 style="margin-top:2.5em;">PDF Metadata</h3>
<label for="dpcms_pdf_title">PDF Title</label>
<input type="text" name="dpcms_pdf_title" value="<?php echo esc_attr($pdf_title); ?>" placeholder="PDF Title">
<p style="margin-left: 20px; font-style: italic;">This is the title of the PDF document. Leave as the default if
    unsure.</p>
<label for="dpcms_pdf_subject">PDF Subject</label>
<input type="text" name="dpcms_pdf_subject" value="<?php echo esc_attr($pdf_subject); ?>" placeholder="PDF Subject">
<p style="margin-left: 20px; font-style: italic;">This is the subject of the PDF document. Leave as the default if
    unsure.</p>
<label for="dpcms_pdf_keywords">PDF Keywords</label>
<input type="text" name="dpcms_pdf_keywords" value="<?php echo esc_attr($pdf_keywords); ?>" placeholder="PDF Keywords">
<p style="margin-left: 20px; font-style: italic;">These are keywords associated with the PDF document for
    searchability. Leave as the default if unsure.</p>
<?php
}

function dpcms_signed_by_text_display()
{
    $selected_option = dpcms_get_custom_option('dpcms_signed_by', 'company_name');
    $custom_selected_option = ($selected_option === 'Other') ? dpcms_get_custom_option('dpcms_custom_signed_by', '') : '';

    $options = [
        'Company Name' => 'Company Name',
        'User Name' => 'User Name',
        'Other' => 'Other'
    ];
    ?>

<br>
<h3 style="margin-top:0;"><?php esc_html_e('Contract Footer Settings', 'deluxe-wp-contracts'); ?></h3>
<label for="dpcms_signed_by"><?php esc_html_e('Signed By Tag', 'deluxe-wp-contracts'); ?></label>
<select name="dpcms_signed_by" id="dpcms_signed_by" onchange="toggleCustomOption(this, 'dpcms_custom_signed_by')">
    <?php foreach ($options as $value => $label): ?>
    <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $selected_option); ?>>
        <?php echo esc_html($label); ?>
    </option>
    <?php endforeach; ?>
</select>
<input type="text" name="dpcms_custom_signed_by" id="dpcms_custom_signed_by"
    style="display: <?php echo ($selected_option === 'Other') ? 'block' : 'none'; ?>;"
    placeholder="<?php esc_attr_e('Enter custom signed by tag', 'deluxe-wp-contracts'); ?>"
    value="<?php echo esc_attr($custom_selected_option); ?>">
<p><?php esc_html_e("This option will go below the company's signature line.", 'deluxe-wp-contracts'); ?></p>
<?php
}

function dpcms_get_signed_by_name()
{
    $signed_by_option = dpcms_get_custom_option('dpcms_signed_by', 'company_name');
    $company_details = dpcms_get_custom_option('dpcms_company_details', array());
    $company_name = $company_details['name'] ? $company_details['name'] : 'Example Company';

    if ($signed_by_option == 'Company Name') {
        return $company_name;
    } elseif ($signed_by_option == 'User Name') {
        $current_user = wp_get_current_user();
        return ucwords(strtolower($current_user->display_name));
    } elseif ($signed_by_option == 'Other') {
        return dpcms_get_custom_option('dpcms_custom_signed_by', 'Custom Name');
    }

    return $company_name;
}

function dpcms_sanitize_dynamic_fields($fields)
{
    if (is_array($fields)) {
        foreach ($fields as &$field_group) {
            if (is_array($field_group) && isset($field_group['header']) && isset($field_group['fields'])) {
                $field_group['header'] = sanitize_text_field($field_group['header']);
                foreach ($field_group['fields'] as &$field) {
                    if (is_array($field)) {
                        foreach ($field as $key => &$value) {
                            $value = sanitize_text_field($value);
                        }
                    }
                }
            }
        }
    }
    return $fields;
}

function dpcms_sanitize_protection_options($input)
{
    $available_options = array('print', 'copy', 'modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high');
    $output = array();

    if (is_array($input)) {
        foreach ($input as $option) {
            if (in_array($option, $available_options)) {
                $output[] = $option;
            }
        }
    }

    return $output;
}

function dpcms_id()
{
    $consent = dpcms_get_custom_option('dpcms_data_consent', '');
    if (!$consent) {
        return;
    }

    $user_id = get_option('dpcms_uuid');
    $user_file = dpcms_get_file($user_id);

    if (!$user_id && file_exists($user_file)) {
        global $wp_filesystem;
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();

        $user_info = json_decode($wp_filesystem->get_contents($user_file), true);
        if (isset($user_info['user_id'])) {
            $user_id = $user_info['user_id'];
            update_option('dpcms_uuid', $user_id);
            return $user_id;
        }
    }

    if ($user_id) {
        return $user_id;
    }

    $user_id = wp_generate_uuid4();
    $user_info = array('user_id' => $user_id);

    global $wp_filesystem;
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    WP_Filesystem();

    $wp_filesystem->put_contents($user_file, wp_json_encode($user_info), FS_CHMOD_FILE);
    update_option('dpcms_uuid', $user_id);

    $response = wp_remote_post('https://deluxeplugins.com/wp-json/dp-contracts/v1/register-user', [
        'body' => wp_json_encode(['user_id' => $user_id]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 120
    ]);

    if (is_wp_error($response)) {
        error_log("Failed to register UUID on remote server: " . $response->get_error_message());
    } else {
        error_log("UUID registered on remote server: " . wp_remote_retrieve_body($response));
    }

    return $user_id;
}

function dpcms_save_keys_ajax()
{
    check_ajax_referer('dpcms_validate_license_nonce', 'security');

    $e_license_key = isset($_POST['e_license_key']) ? sanitize_text_field($_POST['e_license_key']) : '';
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

    if (empty($e_license_key)) {
        wp_send_json_error(['message' => 'License key is missing']);
        return;
    }

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key is missing']);
        return;
    }

    dpcms_set_custom_option('dpcms_license_key', $e_license_key);
    dpcms_set_custom_option('dpcms_api_key', $api_key);

    wp_send_json_success(['message' => 'License key and API key were saved successfully']);
}

function dpcms_delete_keys_ajax()
{
    check_ajax_referer('dpcms_validate_license_nonce', 'security');

    $e_license_key = isset($_POST['e_license_key']) ? sanitize_text_field($_POST['e_license_key']) : '';
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

    if (empty($e_license_key)) {
        wp_send_json_error(['message' => 'License key is missing']);
        return;
    }

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API key is missing']);
        return;
    }

    delete_option('dpcms_license_key', $e_license_key);
    delete_option('dpcms_api_key', $api_key);

    wp_send_json_success(['message' => 'License key and API key were deleted successfully']);
}

function dpcms_request_premium_content()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dpcms_request_premium_content_nonce')) {
        wp_send_json_error('Invalid request');
        return;
    }

    $e_license_key = dpcms_get_custom_option('dpcms_license_key');
    $api_key = dpcms_get_custom_option('dpcms_api_key');

    $request_data = [
        'e_license_key' => $e_license_key,
        'api_key' => $api_key,
        'content_type' => sanitize_text_field($_POST['content_type']),
        'content_id' => sanitize_text_field($_POST['content_id']),
    ];

    $consent = dpcms_get_custom_option('dpcms_data_consent', '1');
    if (!$consent) {
        wp_send_json_error('Please mark the consent to privacy policy checkbox on the settings page.');
        return;
    }

    $response = wp_remote_post('https://deluxeplugins.com/wp-json/dp-contracts/v1/premium-content', [
        'body' => $request_data,
        'timeout' => 120
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching premium content');
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $decoded_body = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid response from the server');
        return;
    }

    if (!isset($decoded_body['content'])) {
        wp_send_json_error('Invalid content received');
        return;
    }

    wp_send_json_success(['content' => $decoded_body['content']]);
}

function dpcms_handle_seller_buyer_types()
{
    if (isset($_POST['dpcms_seller_type'])) {
        $seller_type = sanitize_text_field($_POST['dpcms_seller_type']);
        dpcms_set_custom_option('dpcms_seller_type', $seller_type);

        if ($seller_type === 'Other' && isset($_POST['dpcms_custom_seller_type'])) {
            $custom_seller_type = sanitize_text_field($_POST['dpcms_custom_seller_type']);
            dpcms_set_custom_option('dpcms_custom_seller_type', $custom_seller_type);
        }
    }

    if (isset($_POST['dpcms_buyer_type'])) {
        $buyer_type = sanitize_text_field($_POST['dpcms_buyer_type']);
        dpcms_set_custom_option('dpcms_buyer_type', $buyer_type);

        if ($buyer_type === 'Other' && isset($_POST['dpcms_custom_buyer_type'])) {
            $custom_buyer_type = sanitize_text_field($_POST['dpcms_custom_buyer_type']);
            dpcms_set_custom_option('dpcms_custom_buyer_type', $custom_buyer_type);
        }
    }

    if (isset($_POST['dpcms_sale_type'])) {
        $sale_type = sanitize_text_field($_POST['dpcms_sale_type']);
        dpcms_set_custom_option('dpcms_sale_type', $sale_type);

        if ($sale_type === 'Other' && isset($_POST['dpcms_custom_sale_type'])) {
            $custom_sale_type = sanitize_text_field($_POST['dpcms_custom_sale_type']);
            dpcms_set_custom_option('dpcms_custom_sale_type', $custom_sale_type);
        }
    }
}