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
    $coupon_suffix = get_option('coupons_after_order_suffix');

    // Check if checkbox Enable checked
    if ($enable === 'yes') {
        $validity_type = get_option('coupons_after_order_validity_type');
        if ($validity_type === 'date'):
            $validity = get_option('coupons_after_order_validitydate');
        elseif ($validity_type === 'days'):
            $validity = intval(get_option('coupons_after_order_validitydays'));
            $validity = '+' . $validity . ' days';
        endif;

        $count = intval(get_option('coupons_after_order_count'));
        $limitUsage = get_option('coupons_after_order_usage_limit');
        $min_amount = get_option('coupons_after_order_min_amount');
        $order = wc_get_order($order_id);
        $subTotal = $order->get_subtotal(); // Total amount of the order (shipping costs excluded)
        $existCoupon = (float) $order->get_discount_total();
        $order_total = (float) $subTotal - $existCoupon;
        $nber_coupons = intval($count); // Number of coupons generated desired
        $coupon_list = ''; // Create a variable to store the list of coupons

        // Check if an indicator indicating that coupons have already been generated is present
        $coupons_generated = get_post_meta($order_id, '_coupons_generated', true);

        // If the indicator is already present, does not generate new coupons
        if ($coupons_generated === 'yes') {
            return;
        }
    
        for ($i = 1; $i <= $nber_coupons; $i++) {
            $random_number = mt_rand(10000, 99999);
            // Generate a unique coupon for each voucher
            $couponSuffix = ($coupon_suffix) ? $coupon_suffix : 'ref';
            $coupon_code = $couponSuffix . $order_id . '-' . $random_number;
    
            // Calculate the coupon amount
            $coupon_amount = $order_total / $nber_coupons; // Divide the total amount by the number of coupons
    
            //Define $min_order
            $min_order = empty($min_amount) ? $coupon_amount * 2 : $min_amount;
    
            // Create the coupon
            $coupon = new WC_Coupon($coupon_code);
            $coupon->set_discount_type('fixed_cart');
            $coupon->set_amount($coupon_amount);
            $coupon->set_individual_use(true);
            $coupon->set_date_expires(strtotime($validity));
            $coupon->set_minimum_amount($min_order); // Minimum usage threshold
            $coupon->set_usage_limit($limitUsage); // Usage limit
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
        $subject_translation = get_option('coupons_after_order_email_subject');
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
     add_settings_section('coupons_after_order_section_settings', __('Coupons after order Settings', 'coupons-after-order'), 'coupons_after_order_section_settings_callback', 'woocommerce');
     add_settings_section('coupons_after_order_section_email', __('Coupons after order Email', 'coupons-after-order'), 'coupons_after_order_section_email_callback', 'woocommerce');

     // Add fields to options
    add_settings_field('coupons_after_order_enable', __('Enable Coupon after order', 'coupons-after-order'), 'coupons_after_order_enable_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_validity_type', __('Coupon Validity Type', 'coupons-after-order'), 'coupons_after_order_validity_type_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_validity', __('Coupon Validity', 'coupons-after-order'), 'coupons_after_order_validity_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_others_parameters', __('Other Parameters', 'coupons-after-order'), 'coupons_after_order_others_parameters_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_email_config', __('Email settings', 'coupons-after-order'), 'coupons_after_order_email_config_callback', 'woocommerce', 'coupons_after_order_section_email');
    /*add_settings_field('coupons_after_order_before_email', __('Text at the start of the email', 'coupons-after-order'), 'coupons_after_order_before_email_callback', 'woocommerce', 'coupons_after_order_section_email');
    add_settings_field('coupons_after_order_after_email', __('Text at the end of the email', 'coupons-after-order'), 'coupons_after_order_after_email_callback', 'woocommerce', 'coupons_after_order_section_email');*/
    add_settings_field('coupons_after_order_before_email', __('Text at the start of the email', 'coupons-after-order'), function() {
        coupons_after_order_email_callback(true);
    }, 'woocommerce', 'coupons_after_order_section_email');
    
    add_settings_field('coupons_after_order_after_email', __('Text at the end of the email', 'coupons-after-order'), function() {
        coupons_after_order_email_callback(false);
    }, 'woocommerce', 'coupons_after_order_section_email');
    

    // Save settings
    register_setting('woocommerce', 'coupons_after_order_enable');
    register_setting('woocommerce', 'coupons_after_order_validity_type');
    register_setting('woocommerce', 'coupons_after_order_validitydays', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint'
    ));
    register_setting('woocommerce', 'coupons_after_order_validitydate');
    register_setting('woocommerce', 'coupons_after_order_count', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint'
    ));

    register_setting('woocommerce', 'coupons_after_order_usage_limit', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint'
    ));
    register_setting('woocommerce', 'coupons_after_order_min_amount');
    register_setting('woocommerce', 'coupons_after_order_suffix');

    register_setting('woocommerce', 'coupons_after_order_email_subject', array(
        'default' => __('Your Promo Codes to Enjoy the Refund Offer', 'coupons-after-order') . ' - ' . get_bloginfo('name'),
    ));
    register_setting('woocommerce', 'coupons_after_order_email_header', array(
        'default' => __( 'Thank you for your order', 'coupons-after-order' ),
    ));
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
function coupons_after_order_section_settings_callback() {
    echo __('Configure generated coupon settings', 'coupons-after-order');
}

function coupons_after_order_section_email_callback() {
    echo __('Configure the settings of the email received by the buyer', 'coupons-after-order');
    
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
    $validitydays = get_option('coupons_after_order_validitydays');
    $validitydate = get_option('coupons_after_order_validitydate');
    ?>
    <label id="coupon-validity-days-field" for="coupon-validity-days" style="display: none;"><?= __('Coupon Validity Days:', 'coupons-after-order') ?>
        <input type="number" id="coupon-validity-days" name="coupons_after_order_validitydays" value="<?php echo esc_attr($validitydays); ?>" />&nbsp;<?= __('Days', 'coupons-after-order') ?>
    </label>
    <label id="coupon-validity-date-field" for="coupon-validity-date" style="display: none;"><?= __('Coupon Validity Date:', 'coupons-after-order') ?>
        <input type="date" id="coupon-validity-date" name="coupons_after_order_validitydate" value="<?php echo esc_attr($validitydate); ?>" min="<?php echo date('Y-m-d'); ?>" />
    </label>
    <?php 
}

function coupons_after_order_others_parameters_callback() {
    $count = get_option('coupons_after_order_count');
    $limitUsage = get_option('coupons_after_order_usage_limit', '1');
    $min_amount = get_option('coupons_after_order_min_amount');
    $decimal_separator = wc_get_price_decimal_separator();
    $coupon_suffix = get_option('coupons_after_order_suffix');
    ?>
    <label for="coupons-after-order-count"><?= __('Number of Coupons Generated:', 'coupons-after-order') ?>
        <input type="number" id="coupons-after-order-count" name="coupons_after_order_count" value="<?php echo esc_attr($count); ?>" required />
    </label>
    <br><br>
    <label for="coupon-validity-usage-limit"><?= __('Limit Usage of Coupons Generated:', 'coupons-after-order') ?>
        <input type="number" id="coupon-validity-usage-limit" name="coupons_after_order_usage_limit" value="<?php echo esc_attr($limitUsage); ?>" />
        &nbsp;<?php _e('Use(s)', 'coupons-after-order') ?>
    </label>
    <br><br>
    <label for="coupon-amount-min"><?= __('Minimum amount:', 'coupons-after-order') ?>
        <input type="text" id="coupon-amount-min" name="coupons_after_order_min_amount" value="<?php echo esc_attr($min_amount); ?>" oninput="validateCouponAmount(this, 'minAmountError')" class="wccao_input_price" data-decimal="<?= esc_attr($decimal_separator); ?>" />
        &nbsp;<?php 
        /* translators: %s: price symbol */
        echo sprintf(__('%s (If empty, it is double the amount of the individual coupon)', 'coupons-after-order'), get_woocommerce_currency_symbol()); 
        ?>
    </label>
    <br><br>
    <label for="coupon-suffix"><?= __('Coupon suffix:', 'coupons-after-order') ?>
        <input type="text" id="coupon-suffix" name="coupons_after_order_suffix" value="<?php echo esc_attr($coupon_suffix); ?>" pattern="[a-z]+" title="<?php echo esc_html('Only lowercase characters, no numbers','coupons-after-order') ?>" />
        &nbsp;<?php _e('(If empty, by default, it is <strong>"ref"</strong> and the code is in this form "refOrderID-RandomNumber")', 'coupons-after-order'); ?>
    </label>
    <?php
}

function coupons_after_order_email_config_callback() {
    $email_subject = get_option('coupons_after_order_email_subject');
    $email_header = get_option('coupons_after_order_email_header');
    ?>
    <label for="email_subject" style="display: flex; align-items: center;"><?= __('Email subject:', 'coupons-after-order') ?>
        <input type="text" id="email_subject" name="coupons_after_order_email_subject" value="<?php echo esc_attr($email_subject); ?>" style="flex:auto;" />
    </label>
    <br><br>
    <label for="email_header" style="display: flex; align-items: center;"><?= __('Email header:', 'coupons-after-order') ?>
        <input type="text" id="email_header" name="coupons_after_order_email_header" value="<?php echo esc_attr($email_header); ?>" style="flex:auto;" />
    </label>
   
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

/*function coupons_after_order_before_email_callback() {
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
}*/

function coupons_after_order_email_callback($is_before_email) {
    $editor_data = array(
        'editor_before_email' => array(
            'option_name' => 'coupons_after_order_before_email',
            /* translators: %s: shop name */
            'default_text' => sprintf(__('We hope you are doing well and that you have enjoyed your recent purchase at %s. We thank you for your trust in our products.', 'coupons-after'), get_bloginfo('name')),
        ),
        'editor_after_email' => array(
            'option_name' => 'coupons_after_order_after_email',
            /* translators: %s: shop name */
            'default_text' => sprintf(__('<p>If you have any questions or need assistance, our customer service team is here to help.</p><p>Thank you for your loyalty. We hope you enjoy this special.</p><p>Best regards,<br/>%s.</p>', 'coupons-after-order'), get_bloginfo('name')),
        ),
    );

    $editor_id = $is_before_email ? 'editor_before_email' : 'editor_after_email';
    $option_name = $editor_data[$editor_id]['option_name'];
    $default_text = $editor_data[$editor_id]['default_text'];

    generate_wp_editor($editor_id, $option_name, $default_text);
}