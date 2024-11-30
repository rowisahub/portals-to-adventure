<?php
namespace PTA\shortcodes\admin;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

/* Requires */
use PTA\admin\admin_functions;
use PTA\logger\Log;

/**
 * Admin shortcodes class for the plugin.
 */
class admin_settings{
    private $logger;
    private $admin_func;

    public function __construct() {
        $this->logger = new Log('Admin Shortcodes');
    }

    public function init(admin_functions $admin_functions){
        $this->logger = $this->logger->getLogger();
        $this->logger->info('Initializing admin shortcodes.');

        $this->admin_func = $admin_functions;

        add_action('admin_menu', [$this, 'pta_add_admin_menu']);
    }

    public function pta_add_admin_menu(){
        // Add top-level menu
        add_menu_page(
            page_title: 'Portals To Adventure Admin', // Page title
            menu_title: 'PTA Admin',                  // Menu title
            capability: 'manage_options',             // Capability
            menu_slug: 'pta_admin',                  // Menu slug
            callback: 'pta_submissions_page',       // Callback function
            icon_url: 'dashicons-admin-generic',    // Icon
            position: 6                             // Position
        );

        // Add 'Settings' submenu
        add_submenu_page(
            parent_slug: 'pta_admin',        // Parent slug
            page_title: 'Settings',         // Page title
            menu_title: 'Settings',         // Menu title
            capability: 'manage_options',   // Capability
            menu_slug: 'pta_settings',     // Menu slug
            callback: 'pta_settings_page' // Callback function
        );

        // Add 'Submissions' submenu
        add_submenu_page(
            parent_slug: 'pta_admin',            // Parent slug
            page_title: 'Submissions',          // Page title
            menu_title: 'Submissions',          // Menu title
            capability: 'manage_options',       // Capability
            menu_slug: 'pta_submissions',      // Menu slug
            callback: 'pta_submissions_page'  // Callback function
        );

        // Add check users submenu
        // add_submenu_page(
        //   parent_slug: 'pta_admin',            // Parent slug
        //   page_title: 'Sync Users',          // Page title
        //   menu_title: 'Sync Users',          // Menu title
        //   capability: 'manage_options',       // Capability
        //   menu_slug: 'pta_check_users',      // Menu slug
        //   callback: 'check_users_exist'  // Callback function
        // );

        // Add database submenu
        add_submenu_page(
            parent_slug: 'pta_admin',            // Parent slug
            page_title: 'Database',          // Page title
            menu_title: 'Database',          // Menu title
            capability: 'manage_options',       // Capability
            menu_slug: 'pta_database',      // Menu slug
            callback: 'pta_database_page'  // Callback function
        );
    }

    public function pta_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Check if the form is submitted and nonce is valid
        if (isset($_POST['pta_settings_nonce']) && wp_verify_nonce($_POST['pta_settings_nonce'], 'pta_settings_update')) {
            // save the options
            update_option('pta_submission_add_page', $_POST['pta_submission_add_page']);
            update_option('pta_submission_edit_page', $_POST['pta_submission_edit_page']);
            update_option('pta_submission_view_page', $_POST['pta_submission_view_page']);
            update_option('pta_submission_user_view_page', $_POST['pta_submission_user_view_page']);
            update_option('pta_submission_view_single_page', $_POST['pta_submission_view_single_page']);
            update_option('pta_environment', $_POST['pta_environment']);
            update_option('pta_number_of_submissions_per_time_period', $_POST['pta_number_of_submissions_per_time_period']);
            update_option('pta_time_period', $_POST['pta_time_period']);
            update_option('pta_woocommerce_product_id', $_POST['pta_woocommerce_product_id']);
            update_option('pta_github_fg_token', $_POST['pta_github_fg_token']);
            update_option('wldpta_product_limit', $_POST['wldpta_product_limit']);

            // Display a success message
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        // Retrieve existing options
        $pta_submission_add_page = get_option('pta_submission_add_page', '');
        $pta_submission_edit_page = get_option('pta_submission_edit_page', '');
        $pta_submission_view_page = get_option('pta_submission_view_page', '');
        $pta_submission_user_view_page = get_option('pta_submission_user_view_page', '');
        $pta_submission_view_single_page = get_option('pta_submission_view_single_page', '');
        $pta_environment = get_option('pta_environment', '');
        $pta_number_of_submissions_per_time_period = get_option('pta_number_of_submissions_per_time_period', 1);
        $pta_time_period = get_option('pta_time_period', 'days');
        $pta_woocommerce_product_id = get_option('pta_woocommerce_product_id', 0);
        $pta_github_fg_token = get_option('pta_github_fg_token', '');
        $wldpta_product_limit = get_option('wldpta_product_limit', 10);

        global $logAdmin;

        // Fetch all pages
        $pages = get_pages();
        ?>
        <div class="wrap">
            <h1>Portals To Adventure Settings</h1>
            <form method="post" action="">
            <?php wp_nonce_field('pta_settings_update', 'pta_settings_nonce'); ?>
            <table class="form-table">
                <!-- Submission Add Page -->
                <tr>
                <th scope="row">Submission Add Page</th>
                <td>
                    <select name="pta_submission_add_page">
                    <?php foreach ($pages as $page): ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($pta_submission_add_page, $page->ID); ?>>
                        <?php echo esc_html($page->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </td>
                </tr>
                <!-- Submission Edit Page -->
                <tr>
                <th scope="row">Submission Edit Page</th>
                <td>
                    <select name="pta_submission_edit_page">
                    <?php foreach ($pages as $page): ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($pta_submission_edit_page, $page->ID); ?>>
                        <?php echo esc_html($page->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </td>
                </tr>
                <!-- Submission View Page -->
                <tr>
                <th scope="row">Submission View Page</th>
                <td>
                    <select name="pta_submission_view_page">
                    <?php foreach ($pages as $page): ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($pta_submission_view_page, $page->ID); ?>>
                        <?php echo esc_html($page->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </td>
                </tr>
                <!-- Submission User View Page -->
                <tr>
                <th scope="row">Submission User View Page</th>
                <td>
                    <select name="pta_submission_user_view_page">
                    <?php foreach ($pages as $page): ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($pta_submission_user_view_page, $page->ID); ?>>
                        <?php echo esc_html($page->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </td>
                </tr>
                <!-- Submission View Single Page -->
                <tr>
                <th scope="row">Submission View Single Page</th>
                <td>
                    <select name="pta_submission_view_single_page">
                    <?php foreach ($pages as $page): ?>
                        <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($pta_submission_view_single_page, $page->ID); ?>>
                        <?php echo esc_html($page->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </td>
                </tr>
                <!-- develop or production env -->
                <tr>
                <th scope="row">Environment</th>
                <td>
                    <select name="pta_environment">
                    <option value="production" <?php selected($pta_environment, 'production'); ?>>Production</option>
                    <option value="development" <?php selected($pta_environment, 'development'); ?>>Development</option>
                    </select>
                </td>
                </tr>
                <!-- Number of submissions per time period -->
                <tr>
                <th scope="row">Number of Submissions Per Time Period</th>
                <td>
                    <!-- number of hours or days, with hour or day added on -->
                    <input type="number" name="pta_number_of_submissions_per_time_period"
                    value="<?php echo esc_attr($pta_number_of_submissions_per_time_period); ?>" />
                    <select name="pta_time_period">
                    <option value="hours" <?php selected($pta_time_period, 'hours'); ?>>Hour(s)</option>
                    <option value="days" <?php selected($pta_time_period, 'days'); ?>>Day(s)</option>
                    </select>
                </td>
                </tr>
                <!-- check to make sure woocommerce is active, set a option to change the product id (by selecting from a list of products), and changing the id accordingly -->
                <tr>
                <th scope="row">WooCommerce Product</th>
                <td>
                    <select name="pta_woocommerce_product_id">
                    <?php
                    //echo '<option value="161">Default</option>';
                    if (class_exists('WooCommerce')) {
                        // Get woocommerce products and display them
                        $args = array(
                        'limit' => -1,
                        'status' => 'publish',
                        );
                        $products = wc_get_products($args);

                        foreach ($products as $product) {
                        //$logAdmin->debug('Product: ' . $product->id . ' | ' . $product);
                        $product_id = $product->get_id();
                        $product_name = $product->get_name();
                        echo '<option value="' . $product_id . '" ' . selected($pta_woocommerce_product_id, $product_id) . '>' . $product_name . '</option>';
                        }

                    } else {
                        echo '<option value="0">WooCommerce is not active</option>';
                    }
                    ?>
                    </select>
                </td>
                </tr>
                <!-- Github FG Token -->
                <tr>
                <th scope="row">Github FG Token</th>
                <td>
                    <input type="text" name="pta_github_fg_token" value="<?php echo esc_attr($pta_github_fg_token); ?>" />
                </td>
                </tr>
                <!-- Product Limit -->
                <tr>
                <th scope="row">Product Limit</th>
                <td>
                    <input type="number" name="wldpta_product_limit" value="<?php echo esc_attr($wldpta_product_limit); ?>" />
                </td>
                </tr>

            </table>
            <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function pta_submissions_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Include your plugin functions file if necessary
        // include_once('your-plugin-functions.php');

        // Process actions (approve, set to in-review)
        if (isset($_REQUEST['action']) && isset($_REQUEST['submission_id'])) {
            $action = sanitize_text_field($_REQUEST['action']);
            $submission_id = $_REQUEST['submission_id'];

            //error_log('Action: ' . $action);
            //error_log('Submission ID: ' . $submission_id);

            if ($action == 'approve') {
            // Update submission status to 'Approved'
            //Update_Submission($submission_id, array('status' => 'Approved'));

            update_submission($submission_id, 'state', 'Approved');

            echo '<div class="updated"><p>Submission approved.</p></div>';
            } elseif ($action == 'pending') {
            // Update submission status to 'In-Review'

            Update_Submission($submission_id, 'state', 'Pending Approval');

            echo '<div class="updated"><p>Submission status set to Pending Approval.</p></div>';
            } elseif ($action == 'delete') {
            // Delete submission
            //Delete_Submission($submission_id);
            //delete_submission($submission_id);

            remove_submission($submission_id, "Submission Removed by Admin");

            echo '<div class="updated"><p>Submission deleted.</p></div>';
            } elseif ($action == 'undelete') {
            // Undelete submission
            //Undelete_Submission($submission_id);
            //undelete_submission($submission_id);

            unremove_submission($submission_id);

            echo '<div class="updated"><p>Submission undeleted.</p></div>';
            } elseif ($action == 'progress') {
            // Update submission status to 'In Progress'
            //Update_Submission($submission_id, array('status' => 'In Progress'));

            update_submission($submission_id, 'state', 'In Progress');

            echo '<div class="updated"><p>Submission status set to In Progress.</p></div>';

            } elseif ($action == 'reject') {
            // Update submission status to 'Rejected'
            //Update_Submission($submission_id, array('status' => 'Rejected'));

            set_submission_rejeced($submission_id, "This submission was rejected by admin");

            // get a rejectin message

            echo '<div class="updated"><p>Submission status set to Rejected.</p></div>';
            } elseif ($action == 'unreject') {

            set_submission_unrejeced($submission_id);

            echo '<div class="updated"><p>Submission is Unrejected</p></div>';
            }

            // ad JS to update URL without reloading the page
            ?>
            <script>
            var newURL = '/wp-admin/admin.php?page=pta_submissions';
            history.pushState(null, null, newURL);
            </script>
            <?php


        }

        // Handle filtering by status
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'All';

        //error_log('Status filter: ' . $status_filter);

        // Retrieve submissions based on filter
        if ($status_filter == 'All') {
            $submissions = Get_All_Submissions(); // You need to implement or adjust this function
        } else {
            $submissions = get_all_submissions_by_state($status_filter);
        }

        ?>
        <div class="wrap">
            <h1>Submissions</h1>

            <!-- Filter Form -->
            <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <label for="status_filter">Filter by Status:</label>
            <select name="status_filter" id="status_filter">
                <option value="All" <?php selected($status_filter, 'All'); ?>>All Submissions</option>
                <option value="Pending Approval" <?php selected($status_filter, 'Pending Approval'); ?>>Pending Approval</option>
                <option value="Approved" <?php selected($status_filter, 'Approved'); ?>>Approved</option>
                <option value="In Progress" <?php selected($status_filter, 'In Progress'); ?>>In Progress</option>
                <option value="Rejected" <?php selected($status_filter, 'Rejected'); ?>>Rejected</option>
                <option value="Removed" <?php selected($status_filter, 'Removed'); ?>>Removed</option>
            </select>
            <?php submit_button('Filter', 'primary', '', false); ?>
            </form>

            <!-- Submissions Table -->
            <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Status</th>
                <th>User ID</th>
                <th>User Name</th>
                <th>Was, current Rejected, Reason</th>
                <th>Date Submitted</th>
                <th>View</th>
                <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($submissions)): ?>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                    <td><?php echo esc_html($submission['id']); ?></td>
                    <td><?php echo esc_html($submission['title']); ?></td>
                    <td><?php echo esc_html($submission['state']); ?></td>
                    <td><?php echo esc_html($submission['user_owner_id']); ?></td>
                    <td><?php echo esc_html(get_user_by_id($submission['user_owner_id'])['username']); ?></td>
                    <td>
                        <?php if ($submission['was_rejected']): ?>
                        <?php echo "True"; ?>
                        <?php else: ?>
                        <?php echo "False"; ?>
                        <?php endif; ?>
                        <div>
                        <?php if ($submission['is_rejected']): ?>
                            <?php echo "True, " . esc_html($submission['rejected_reason']); ?>
                        <?php else: ?>
                            <?php echo "False"; ?>
                        <?php endif; ?>
                        </div>
                    </td>
                    <td><?php echo esc_html($submission['created_at']); ?></td>
                    <td>
                        <a
                        href="<?php echo esc_url(add_query_arg('id', $submission['id'], get_site_url(path: 'submission'))); ?>">View</a>
                    </td>
                    <td>
                        <?php if ($submission['state'] == 'Pending Approval'): ?>
                        <a
                            href="<?php echo esc_url(add_query_arg(array('action' => 'approve', 'submission_id' => $submission['id']))); ?>">Approve</a>
                        <?php elseif ($submission['state'] == 'In Progress'): ?>
                        <a
                            href="<?php echo esc_url(add_query_arg(array('action' => 'pending', 'submission_id' => $submission['id']))); ?>">Set
                            as 'Pending
                            Approval'</a>
                        <?php elseif ($submission['state'] == 'Approved'): ?>
                        <a
                            href="<?php echo esc_url(add_query_arg(array('action' => 'progress', 'submission_id' => $submission['id']))); ?>">Set
                            as 'In
                            Progress'</a>
                        <?php endif; ?>
                        <!-- Add more actions as needed -->

                        <div>
                        <?php if ($submission['is_rejected']): ?>
                            <a
                            href="<?php echo esc_url(add_query_arg(array('action' => 'unreject', 'submission_id' => $submission['id']))); ?>">Unreject</a>

                        <?php else: ?>
                            <a
                            href="<?php echo esc_url(add_query_arg(array('action' => 'reject', 'submission_id' => $submission['id']))); ?>">Reject</a>
                        <?php endif; ?>

                        </div>

                        <div>
                        <?php if ($submission['is_removed']): ?>
                            <a
                            href="<?php echo esc_url(add_query_arg(array('action' => 'undelete', 'submission_id' => $submission['id']))); ?>">Undelete</a>
                        <?php else: ?>
                            <a
                            href="<?php echo esc_url(add_query_arg(array('action' => 'delete', 'submission_id' => $submission['id']))); ?>">Delete</a>
                        <?php endif; ?>
                        </div>
                    </td>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6">No submissions found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
        <?php
    }

    public function pta_database_page()
    {
        // This page will display the database backup options/settings and run/view actions

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Check if the form is submitted and nonce is valid
        if (isset($_POST['pta_nonce']) && wp_verify_nonce($_POST['pta_nonce'], 'pta_manual_backup_nonce')) {
            // Run the backup
            //$backup_result = new pta_db_backup();
            //$success = $backup_result->perform_backup();

            // if ($success) {
            //   echo '<div class="updated"><p>Backup completed successfully.</p></div>';
            // } else {
            //   echo '<div class="error"><p>Backup failed.</p></div>';
            // }
        }

        ?>
        <div class="wrap">
            <h1><?php _e('PTA Plugin Settings', 'pta-plugin'); ?></h1>
            <form method="post" action="">
            <?php
            // Add a hidden field to specify the action
            ?>
            <input type="hidden" name="action" value="pta_manual_backup">
            <?php
            // Security nonce
            wp_nonce_field('pta_manual_backup_nonce', 'pta_nonce');
            ?>
            <h2><?php _e('Database Backup (WIP)', 'pta-plugin'); ?></h2>
            <p>
                <?php _e('Click the button below to create a manual database backup.', 'pta-plugin'); ?>
            </p>
            <p>
                <input type="submit" class="button button-primary" value="<?php _e('Run Backup Now', 'pta-plugin'); ?>" />
            </p>
            </form>
            <!-- Add more settings sections as needed -->
        </div>
        <?php

    }
}