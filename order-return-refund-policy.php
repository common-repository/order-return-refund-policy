<?php
/**
 * Plugin Name: Order Return & Refund Policy
 * Description: A plugin to allow users to upload videos related to their WooCommerce orders.
 * Version: 1.0.3
 * Author: Naveen Goyal
 * Author URI: https://Bhandarum.com
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
add_action('admin_init', 'orrp_check_woocommerce');

function orrp_check_woocommerce() {
    // Check if WooCommerce is installed and active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'orrp_woocommerce_not_active_notice');
        
        // Deactivate the plugin only in the admin area
        if (is_admin() && function_exists('deactivate_plugins')) {
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }
}

// Add "Manage" link to the plugin actions
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'orrp_add_manage_link');

function orrp_add_manage_link($actions) {
    // Add the manage link
    $manage_link = '<a href="' . esc_url(admin_url('admin.php?page=order-video')) . '">' . esc_html__('Manage', 'order-return-refund-rpolicy') . '</a>';
    $actions['manage'] = $manage_link;

    return $actions;
}

// Display an admin notice if WooCommerce is not active
function orrp_woocommerce_not_active_notice() {
    ?>
    <div class="error notice">
        <p><?php esc_html_e('Order Return & Refund Policy requires WooCommerce to be installed and active.', 'order-return-refund-rpolicy'); ?></p>
    </div>
    <?php
}

// Include Frontend Functionality
$frontend_file = plugin_dir_path(__FILE__) . 'frontend.php';
if (file_exists($frontend_file)) {
    include_once($frontend_file);
} else {
    error_log('Frontend file not found: ' . $frontend_file);
}

// Include Admin Functionality
$admin_file = plugin_dir_path(__FILE__) . 'admin.php';
if (file_exists($admin_file)) {
    include_once($admin_file);
} else {
    error_log('Admin file not found: ' . $admin_file);
}

// Activation hook
register_activation_hook(__FILE__, 'orrp_youtube_video_submission_activate');

function orrp_youtube_video_submission_activate() {
    // Activation tasks, if any
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'orrp_youtube_video_submission_deactivate');

function orrp_youtube_video_submission_deactivate() {
    // Deactivation tasks, if any
}

// Add YouTube video link submission field to order details page for admin
function orrp_add_youtube_video_submission_field($order) {
    $nonce = wp_create_nonce('youtube_video_link_nonce');
    ?>
    <div class="order_youtube_video_submission">
        <h3><?php esc_html_e('Submit YouTube Video Link', 'order-return-refund-rpolicy'); ?></h3>
        <input type="hidden" name="youtube_video_link_nonce" value="<?php echo esc_attr($nonce); ?>" />
        <input type="text" name="youtube_video_link" value="<?php echo esc_attr(get_post_meta($order->get_id(), 'youtube_video_link', true)); ?>" placeholder="Enter YouTube video link" />
    </div>
    <?php
}
add_action('woocommerce_admin_order_data_after_order_details', 'orrp_add_youtube_video_submission_field');

// Save YouTube video link
function orrp_save_youtube_video_link($order_id) {
    if (isset($_POST['youtube_video_link_nonce']) && wp_verify_nonce(sanitize_key($_POST['youtube_video_link_nonce']), 'youtube_video_link_nonce')) {
        if (isset($_POST['youtube_video_link'])) {
            $youtube_video_link = esc_url_raw(wp_unslash($_POST['youtube_video_link']));
            update_post_meta($order_id, 'youtube_video_link', $youtube_video_link);
        }
    }
}
add_action('woocommerce_process_shop_order_meta', 'orrp_save_youtube_video_link', 10, 1);

// Add YouTube video link to order details page for user
function orrp_add_youtube_video_link_to_order_details($order) {
    $youtube_video_link = get_post_meta($order->get_id(), 'youtube_video_link', true);

    if (!empty($youtube_video_link)) {
        ?>
        <div class="order_youtube_video_link">
            <h5><?php esc_html_e('Your product shipped video here: ', 'order-return-refund-rpolicy'); ?></h5>
            <p><a href="<?php echo esc_url($youtube_video_link); ?>" target="_blank"><?php esc_html_e('View', 'order-return-refund-rpolicy'); ?></a></p>
        </div>
        <?php
    }
}
add_action('woocommerce_order_details_after_order_table', 'orrp_add_youtube_video_link_to_order_details');

// Add video upload field for return request
function orrp_add_return_video_upload_field($order_id) {
    $nonce = wp_create_nonce('return_video_nonce');
    ?>
    <p class="form-row form-row-wide">
        <label for="return_video"><?php esc_html_e('Upload Return Video', 'order-return-refund-rpolicy'); ?></label>
        <input type="hidden" name="return_video_nonce" value="<?php echo esc_attr($nonce); ?>" />
        <input type="file" name="return_video" id="return_video" accept="video/*" />
    </p>
    <?php
}
add_action('woocommerce_after_return_order_content', 'orrp_add_return_video_upload_field');

// Save return video
function orrp_save_return_video($order_id) {
    if (isset($_POST['return_video_nonce']) && wp_verify_nonce(sanitize_key($_POST['return_video_nonce']), 'return_video_nonce')) {
        if (isset($_FILES['return_video']) && !empty($_FILES['return_video']['tmp_name'])) {
            $file = isset($_FILES['return_video']);

            // Ensure 'error' key exists in $_FILES array
            $file_error = isset($file['error']) ? $file['error'] : UPLOAD_ERR_NO_FILE;
            
            if ($file_error === UPLOAD_ERR_OK) {
                // Validate file input
                if (isset($file['tmp_name']) && !empty($file['tmp_name']) && isset($file['name']) && !empty($file['name'])) {
                    // Sanitize file name
                    $file_name = sanitize_file_name($file['name']);
                    $file_tmp = $file['tmp_name'];
                    $file_type = isset($file['type']) ? sanitize_mime_type($file['type']) : '';
                    $file_size = isset($file['size']) ? intval($file['size']) : 0;

                    // Validate file type (optional security measure)
                    $allowed_file_types = array('video/mp4', 'video/avi', 'video/mov', 'video/mkv');
                    if (in_array($file_type, $allowed_file_types)) {
                        // Handle file upload securely
                        $upload = wp_handle_upload(array(
                            'name' => $file_name,
                            'tmp_name' => $file_tmp,
                            'type' => $file_type,
                            'error' => $file_error,
                            'size' => $file_size
                        ), array('test_form' => false));

                        // Check for upload errors
                        if (!isset($upload['error'])) {
                            // File uploaded successfully, save file URL to the database
                            update_post_meta($order_id, 'return_video_path', esc_url_raw($upload['url']));
                        } else {
                            // Error during file upload
                            wc_add_notice(__('Failed to upload video: ', 'order-return-refund-rpolicy') . esc_html($upload['error']), 'error');
                        }
                    } else {
                        // Invalid file type
                        wc_add_notice(__('Invalid file type.', 'order-return-refund-rpolicy'), 'error');
                    }
                } else {
                    // No file uploaded or file is empty
                    wc_add_notice(__('No file uploaded or file is empty.', 'order-return-refund-rpolicy'), 'error');
                }
            } else {
                // File upload error
                wc_add_notice(__('File upload error: ', 'order-return-refund-rpolicy') . esc_html($file_error), 'error');
            }
        } else {
            // File not set or empty
            wc_add_notice(__('Error uploading file or no file was uploaded.', 'order-return-refund-rpolicy'), 'error');
        }
    }
}
add_action('woocommerce_save_order_return_request', 'orrp_save_return_video', 10, 1);



/**
 * Add submenu under WooCommerce for "Order Video".
 */
add_action('admin_menu', 'orrp_add_order_video_submenu');

function orrp_add_order_video_submenu() {
    add_submenu_page(
        'woocommerce',                  // Parent slug (WooCommerce)
        'Order Video',                  // Page title
        'Order Video',                  // Menu title
        'manage_woocommerce',           // Capability
        'order-video',                  // Menu slug
        'orrp_order_video_page_content' // Function to display the content
    );
}
function orrp_order_video_page_content() {
    // Fetch all orders with completed or processing status
    $args = array(
        'status' => array('wc-completed', 'wc-processing'), // Specify statuses
        'limit' => -1, // Get all orders
    );
    
    // Get orders
    $orders = wc_get_orders($args);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('All Orders with YouTube Video Status', 'order-return-refund-rpolicy'); ?></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Order ID', 'order-return-refund-rpolicy'); ?></th>
                    <th><?php esc_html_e('Video Updated', 'order-return-refund-rpolicy'); ?></th>
                    <th><?php esc_html_e('Action', 'order-return-refund-rpolicy'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)) : ?>
                    <?php foreach ($orders as $order) : ?>
                        <?php
                        // Check if YouTube video link exists
                        $youtube_video_link = get_post_meta($order->get_id(), 'youtube_video_link', true);
                        $video_status = !empty($youtube_video_link) ? __('Yes', 'order-return-refund-rpolicy') : __('No', 'order-return-refund-rpolicy');
                        ?>
                        <tr>
                            <td><?php echo esc_html($order->get_id()); ?></td>
                            <td><?php echo esc_html($video_status); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?action=edit&post=' . $order->get_id())); ?>" class="button"><?php esc_html_e('Edit Order', 'order-return-refund-rpolicy'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3"><?php esc_html_e('No orders found.', 'order-return-refund-rpolicy'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


