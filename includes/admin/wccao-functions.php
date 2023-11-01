<?php
/**
 * WC Coupons After Order functions.
 *
 * @author 		Webpixelia
 * @since		1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate coupons.
 *
 * Generate the coupons after order based on the $order_id argument.
 *
 * @since 1.0.0
 */
add_action('woocommerce_order_status_completed', 'wccao_generate_coupons');

function wccao_generate_coupons($order_id) {
    $enable = get_option('coupons_after_order_enable', 'no');

    // Check if checkbox Enable checked
    if ($enable === 'yes') {
        $validity_type = get_option('coupons_after_order_validity_type');
        if ($validity_type === 'date'):
            $validity = get_option('coupons_after_order_validitydate');
        elseif ($validity_type === 'days'):
            $validity = intval(get_option('coupons_after_order_validity'));
            $validity = '+' . $validity . ' days';
        endif;

        $count = intval(get_option('coupons_after_order_count'));
        $order = wc_get_order($order_id);
        $order_total = $order->get_subtotal(); // Total amount of the order (shipping costs excluded)
        $nber_coupons = intval($count); // Number of coupons generated desired
        $coupon_list = ''; // Create a variable to store the list of coupons

        // Check if an indicator indicating that coupons have already been generated is present
        $coupons_generated = get_post_meta($order_id, '_coupons_generated', true);

        // If the indicator is already present, does not generate new coupons
        if ($coupons_generated === 'yes') {
            return;
        }
    
        for ($i = 1; $i <= $nber_coupons; $i++) {
            $random_number = mt_rand(100, 999);
            // Generate a unique coupon for each voucher
            $coupon_code = strtoupper('remb') . $order_id . $random_number;
    
            // Calculate the coupon amount
            $coupon_amount = $order_total / $nber_coupons; // Divide the total amount by the number of coupons
    
            $min_order = $coupon_amount * 2;
    
            // Create the coupon
            $coupon = new WC_Coupon($coupon_code);
            $coupon->set_discount_type('fixed_cart');
            $coupon->set_amount($coupon_amount);
            $coupon->set_individual_use(true);
            $coupon->set_date_expires(strtotime($validity));
            $coupon->set_minimum_amount($min_order); // Minimum usage threshold
            $coupon->set_usage_limit(1); // Usage limit at 1
            $coupon->save();
    
            // Add the coupon to the list
            $coupon_list .= '<li>' . __('Promo code ', 'coupons-after-order') . $i . ': ' . $coupon_code . '</li>';
        }

        // Save the flag to indicate that coupons have been generated
        update_post_meta($order_id, '_coupons_generated', 'yes');
    
        // Get custom email template path
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/email-template.php';
    
        // Check if the model exists
        if (file_exists($template_path)) {
            // Load custom email template
            ob_start();
            include $template_path;
            $email_content = ob_get_clean();
        } else {
            // Model not found
            $email_content = __('An error occurred while sending your coupons by email (Email template not found). Please contact us to collect your coupons: mudaparis@outlook.fr.', 'coupons-after-order');
        }
    
        // Send a single email to the customer with the list of generated coupons
        $subject_translation = __('Your Promo Codes to Enjoy the Refund Offer - Muda Paris', 'coupons-after-order');
        $message = $email_content;
    
        $customer_email = $order->get_billing_email();
        $subject = $subject_translation;
    
        // Set content type as HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');
    
        wp_mail($customer_email, $subject, $message, $headers);
    }
}

/**
 * Create fields admin.
 *
 * @since 1.1.0
 */
function register_coupons_after_order_settings() {
	 // Create section in WooCommerce menu
     add_settings_section('coupons_after_order_section', __('Coupons after order Settings', 'coupons-after-order'), 'coupons_after_order_section_callback', 'woocommerce');

     // Add fields to options
    add_settings_field('coupons_after_order_enable', __('Enable Coupon after order', 'coupons-after-order'), 'coupons_after_order_enable_callback', 'woocommerce', 'coupons_after_order_section');
    add_settings_field('coupons_after_order_validity_type', __('Coupon Validity Type', 'coupons-after-order'), 'coupons_after_order_validity_type_callback', 'woocommerce', 'coupons_after_order_section');
    add_settings_field('coupons_after_order_validity', __('Coupon Validity (Days)', 'coupons-after-order'), 'coupons_after_order_validity_callback', 'woocommerce', 'coupons_after_order_section');
    add_settings_field('coupons_after_order_validitydate', __('Coupon Validity (Date)', 'coupons-after-order'), 'coupons_after_order_validitydate_callback', 'woocommerce', 'coupons_after_order_section');
    add_settings_field('coupons_after_order_count', __('Number of Coupons Generated', 'coupons-after-order'), 'coupons_after_order_count_callback', 'woocommerce', 'coupons_after_order_section');
    add_settings_field('coupons_after_order_before_email', __('Text at the start of the email', 'coupons-after-order'), 'coupons_after_order_before_email_callback', 'woocommerce', 'coupons_after_order_section');
    add_settings_field('coupons_after_order_after_email', __('Text at the end of the email', 'coupons-after-order'), 'coupons_after_order_after_email_callback', 'woocommerce', 'coupons_after_order_section');

    // Save settings
    register_setting('woocommerce', 'coupons_after_order_enable');
    register_setting('woocommerce', 'coupons_after_order_validity_type');
    register_setting('woocommerce', 'coupons_after_order_validity');
    register_setting('woocommerce', 'coupons_after_order_validitydate');
    register_setting('woocommerce', 'coupons_after_order_count');
    register_setting('woocommerce', 'coupons_after_order_before_email');
    register_setting('woocommerce', 'coupons_after_order_after_email');
}
add_action( 'admin_init', 'register_coupons_after_order_settings' );


/**
 * Callback functions.
 *
 * Generate fields in admin page.
 *
 * @since 1.0.0
 */
function coupons_after_order_section_callback() {
    
}

function coupons_after_order_enable_callback() {
    $enable = get_option('coupons_after_order_enable', 'no');
    ?>
    <input type="checkbox" name="coupons_after_order_enable" <?php checked($enable, 'yes'); ?> value="yes" />
    <?php
}

function coupons_after_order_validity_type_callback() {
    $validity_type = get_option('coupons_after_order_validity_type', 'days');
    ?>
    <fieldset>
        <legend><?php __('Coupon Validity Type', 'coupons-after-order') ?></legend>
        <label>
            <input type="radio" name="coupons_after_order_validity_type" value="days" <?php checked($validity_type, 'days'); ?> /><?php _e('Coupon Validity (Days)', 'coupons-after-order') ?>
        </label>
        <br>
        <label>
            <input type="radio" name="coupons_after_order_validity_type" value="date" <?php checked($validity_type, 'date'); ?> /><?php _e('Coupon Validity (Date)', 'coupons-after-order') ?>
        </label>
    </fieldset>

    <?php
}

function coupons_after_order_validity_callback() {
    $validity = get_option('coupons_after_order_validity');
    ?>
    <input type="number" id="coupon-validity-days" name="coupons_after_order_validity" value="<?php echo esc_attr($validity); ?>" style="display: none;" />
    <?php 
}

function coupons_after_order_validitydate_callback() {
    $validitydate = get_option('coupons_after_order_validitydate');
    ?>
    <input type="date" id="coupon-validity-date" name="coupons_after_order_validitydate" value="<?php echo esc_attr($validitydate); ?>" min="<?php echo date('Y-m-d'); ?>" style="display: none;" />
    <?php 
}

function coupons_after_order_count_callback() {
    $count = get_option('coupons_after_order_count');
    ?>
    <input type="number" name="coupons_after_order_count" value="<?php echo esc_attr($count); ?>" required />
    <?php
}

function generate_wp_editor($editor_id, $option_name, $default_text) {
    $options = get_option($option_name);
    $content = !empty($options) ? $options : $default_text;

    $settings = array(
        'media_buttons' => false,
        'textarea_rows' => 5,
        'textarea_name' => $option_name
    );

    wp_editor($content, $editor_id, $settings);
}

function coupons_after_order_before_email_callback() {
    $editor_id = 'editor_before_email';
    $option_name = 'coupons_after_order_before_email';
    $default_text = sprintf(__('We hope you are doing well and that you have enjoyed your recent purchase at %s. We thank you for your trust in our products.', 'coupons-after-order'), get_bloginfo('name'));
    generate_wp_editor($editor_id, $option_name, $default_text);
}

function coupons_after_order_after_email_callback() {
    $editor_id = 'editor_after_email';
    $option_name = 'coupons_after_order_after_email';
    $default_text = sprintf(__('<p>If you have any questions or need assistance, our customer service team is here to help.</p><p>Thank you for your loyalty. We hope you enjoy this special.</p><p>Best regards,<br/>%s.</p>', 'coupons-after-order'), get_bloginfo('name'));
    generate_wp_editor($editor_id, $option_name, $default_text);
}