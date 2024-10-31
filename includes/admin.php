<?php
// Admin functionality

// Add video upload section to order view page
function orrp_add_video_upload_section() {
    global $post;
    $order_id = $post->ID;
    ?>
    <div class="order_video_upload">
        <h3><?php esc_html_e('Upload Video', 'order-return-refund-rpolicy'); ?></h3>
        <input type="button" class="upload_video_button button" value="<?php esc_html_e('Upload Video', 'order-return-refund-rpolicy'); ?>" />
        <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>" />
        <div class="video-preview"></div>
    </div>
    <?php
}
add_action('woocommerce_admin_order_data_after_order_details', 'orrp_add_video_upload_section');
