<?php
/**
* WC Coupons After Order functions.
*
* @author 		Jonathan Webpixelia
* @since		1.0.0
*/

if (!defined('ABSPATH')) exit;

/**
 * Generate coupons after order completion based on the $order_id argument.
 *
 * @since 1.0.0
 */
add_action('woocommerce_order_status_completed', 'wccao_generate_coupons');

function wccao_generate_coupons($order_id) {
    $enable = get_option('coupons_after_order_enable');
    
    // Check if checkbox Enable is checked
    if ($enable === 'yes') {
        // Retrieve missing variables
        $order = wc_get_order($order_id);
        $subTotal = $order->get_subtotal();
        $existCoupon = (float) $order->get_discount_total();
        $order_total = (float) $subTotal - $existCoupon;

        $couponDetails = wccao_generate_coupon_details($order_total);

        $coupons_generated = get_post_meta($order_id, '_coupons_generated', true);

        // If coupons are already generated, return
        if ($coupons_generated === 'yes') {
            return;
        }

        $coupon_list = wccao_generate_coupons_list($couponDetails, $order_id);

        $order->update_meta_data( '_coupons_generated', 'yes' );
        $order->save();
    
        wccao_send_coupons_email($order, $coupon_list, $couponDetails);
    }
}

/**
 * Generate coupon details based on order total.
 *
 * Retrieves various coupon-related settings from options and calculates
 * additional details such as coupon amount, minimum order amount, and others.
 *
 * @param float $order_total The total amount of the order.
 *
 * @return array Associative array containing coupon details.
 *               - 'coupon_prefix' (string): The coupon code prefix.
 *               - 'validity_type' (string): The type of validity for the coupon ('date' or 'days').
 *               - 'validity' (string): The validity duration for the coupon.
 *               - 'limitUsage' (string): The usage limit for the coupon.
 *               - 'indivUseCoupon' (bool): Whether the coupon is for individual use only.
 *               - 'min_amount' (string): The minimum amount for the coupon.
 *               - 'order_total' (float): The total amount of the order.
 *               - 'nber_coupons' (int): The number of coupons to be generated.
 *               - 'coupon_amount' (float): The amount for each coupon.
 *               - 'min_order' (float): The minimum order amount for using the coupon.
 */
function wccao_generate_coupon_details($order_total) {
    $coupon_prefix = get_option('coupons_after_order_prefix');
    $validity_type = get_option('coupons_after_order_validity_type');
    $validity = wccao_get_validity($validity_type);
    
    $limitUsage = get_option('coupons_after_order_usage_limit', '1');
    $indivUse = get_option('coupons_after_order_individual_use', 'yes');
    $indivUseCoupon = ($indivUse === 'yes');
    $min_amount = get_option('coupons_after_order_min_amount');

  
    $nber_coupons = intval(get_option('coupons_after_order_count'));
    $coupon_amount = $order_total / $nber_coupons;
    $coupon_amount = round($coupon_amount, wc_get_price_decimals());
    $min_order = empty($min_amount) ? $coupon_amount : max(tofloat($min_amount), $coupon_amount);


    return compact(
        'coupon_prefix', 'validity_type', 'validity', 'limitUsage', 'indivUseCoupon', 'min_amount', 'order_total', 'nber_coupons', 'coupon_amount', 'min_order'
    );
}

/**
 * Get coupon validity based on validity type.
 *
 * Retrieves the coupon validity based on the specified validity type.
 *
 * @param string $validity_type The type of validity for the coupon ('date' or 'days').
 *
 * @return string The coupon validity. If the validity type is 'date', returns the specific date.
 *                If the validity type is 'days', returns the validity duration in days.
 *                Returns an empty string if the validity type is not recognized.
 */
function wccao_get_validity($validity_type) {
    if ($validity_type === 'date') {
        return get_option('coupons_after_order_validitydate');
    } elseif ($validity_type === 'days') {
        $validity_days = intval(get_option('coupons_after_order_validitydays'));
        return '+' . $validity_days . ' days';
    }
    return '';
}

/**
 * Generate a list of coupons based on the provided details.
 *
 * Creates a list of coupons with unique coupon codes using the specified details.
 *
 * @param array $couponDetails An array containing details of the generated coupons.
 * @param int   $order_id      The ID of the order associated with the generated coupons.
 *
 * @return string HTML list of generated coupon codes.
 */
function wccao_generate_coupons_list($couponDetails, $order_id) {
    $coupon_list = '';

    for ($i = 1; $i <=  $couponDetails['nber_coupons']; $i++) {
        $coupon_code = wccao_generate_coupon_code($couponDetails, $order_id);

        $coupon_list .= '<li>' . __('Coupon code ', 'coupons-after-order') . $i . ': ' . $coupon_code . '</li>';
    }

    return $coupon_list;
}

/**
 * Generate a unique coupon code and create a coupon with the provided details.
 *
 * Generates a coupon code by combining a prefix, order ID, and a random number.
 * Then, creates a WooCommerce coupon with the specified details and returns the generated coupon code.
 *
 * @param array $couponDetails An array containing details for generating the coupon.
 * @param int   $order_id      The ID of the order associated with the coupon.
 *
 * @return string The generated coupon code.
 */
function wccao_generate_coupon_code($couponDetails, $order_id) {
    $random_number = mt_rand(10000, 99999);
    $couponPrefix = ($couponDetails['coupon_prefix']) ? esc_attr($couponDetails['coupon_prefix']) : 'ref';

    // Calculate the coupon amount
    $coupon_amount = $couponDetails['order_total'] / $couponDetails['nber_coupons']; // Directly use from $couponDetails
    $coupon_amount = round($coupon_amount, wc_get_price_decimals());

    // Create the coupon
    $coupon_code = $couponPrefix . $order_id . '-' . $random_number;
    $coupon = new WC_Coupon($coupon_code);
    $coupon->set_discount_type('fixed_cart');
    $coupon->set_amount($coupon_amount);
    $coupon->set_individual_use($couponDetails['indivUseCoupon']);
    $coupon->set_date_expires(strtotime($couponDetails['validity']));
    $coupon->set_minimum_amount($couponDetails['min_order']); // Minimum usage threshold
    $coupon->set_usage_limit($couponDetails['limitUsage']); // Usage limit
    $coupon->save();

    return $coupon_code;
}

/**
 * Send coupons to the customer via email after a successful order.
 *
 * Retrieves the email subject translation from options and generates the email content using a custom template.
 * Sends the email to the customer's billing email address.
 *
 * @param WC_Order $order        The WooCommerce order object.
 * @param string   $coupon_list  The list of generated coupon codes.
 * @param array    $couponDetails An array containing details for generating the coupons.
 */
function wccao_send_coupons_email($order, $coupon_list, $couponDetails) {
    $subject_translation = get_option('coupons_after_order_email_subject');

    // Get custom email template path
    $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/email-template.php';

    // Check if the model exists
    if (file_exists($template_path)) {
        ob_start();
        include $template_path;
        $email_content = ob_get_clean();
    } else {
        $error_message = __('An error occurred while sending your coupons by email (Email template not found). Please contact us to collect your coupons.', 'coupons-after-order');
        $email_content = '<p>' . $error_message . '</p>';
    }

    $customer_email = $order->get_billing_email();
    $subject = $subject_translation;

    // Set content type as HTML
    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($customer_email, $subject, $email_content, $headers);
}

/**
 * Convert a numeric string with various decimal separators to a float value.
 *
 * This function takes a numeric string as input and converts it to a float,
 * handling both dot (.) and comma (,) as decimal separators. It removes any
 * non-numeric characters and correctly places the decimal separator, providing
 * a clean float representation of the input.
 *
 * @param string $num The numeric string to be converted to a float.
 * @return float The float representation of the input numeric string.
 */
function tofloat($num) {
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
   
    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    } 

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
    );
}

/**
 * Create fields admin.
 *
 * @since 1.1.0
 */
function register_coupons_after_order_settings() {
    register_coupons_after_order_sections();
    register_coupons_after_order_fields();
}

function register_coupons_after_order_sections() {
    add_settings_section('coupons_after_order_section_settings', __('Coupons after order Settings', 'coupons-after-order'), 'coupons_after_order_section_settings_callback', 'woocommerce', array(
        'before_section' => '<div class="wccao_section_admin">',
        'after_section'  => '</div>',
    ));

    add_settings_section('coupons_after_order_section_email', __('Coupons after order Email', 'coupons-after-order'), 'coupons_after_order_section_email_callback', 'woocommerce', array(
        'before_section' => '<div class="wccao_section_admin">',
        'after_section'  => '</div>',
    ));
}

function register_coupons_after_order_fields() {
    // Add fields to options
    add_settings_field('coupons_after_order_enable', __('Enable Coupon after order', 'coupons-after-order'), 'coupons_after_order_enable_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_validity_type', __('Coupon Validity Type', 'coupons-after-order'), 'coupons_after_order_validity_type_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_validity', __('Coupon Validity', 'coupons-after-order'), 'coupons_after_order_validity_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_others_parameters', __('Other Parameters', 'coupons-after-order'), 'coupons_after_order_others_parameters_callback', 'woocommerce', 'coupons_after_order_section_settings');
    add_settings_field('coupons_after_order_email_config', __('Email settings', 'coupons-after-order'), 'coupons_after_order_email_config_callback', 'woocommerce', 'coupons_after_order_section_email');
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
        'sanitize_callback' => 'absint',
        'default' => 30,
    ));
    register_setting('woocommerce', 'coupons_after_order_validitydate');
    register_setting('woocommerce', 'coupons_after_order_count', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 4,
    ));

    register_setting('woocommerce', 'coupons_after_order_usage_limit', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint'
    ));
    register_setting('woocommerce', 'coupons_after_order_individual_use');
    register_setting('woocommerce', 'coupons_after_order_min_amount');
    register_setting('woocommerce', 'coupons_after_order_prefix');

    register_setting('woocommerce', 'coupons_after_order_email_subject', array(
        'default' => __('Your Promo Codes to Enjoy the Refund Offer', 'coupons-after-order') . ' - ' . get_bloginfo('name'),
    ));
    register_setting('woocommerce', 'coupons_after_order_email_header', array(
        'default' => __( 'Thank you for your order', 'coupons-after-order' ),
    ));
    register_setting('woocommerce', 'coupons_after_order_before_email');
    register_setting('woocommerce', 'coupons_after_order_after_email');
}

add_action('admin_init', 'register_coupons_after_order_settings');


/**
* Callback functions.
*
* Generate fields in admin page.
*
* @since 1.0.0
*/
function coupons_after_order_section_settings_callback() {
   echo '<p class="wccao-descr-section-admin">' . __('Configure generated coupon settings', 'coupons-after-order') . '</p>';;
}

function coupons_after_order_section_email_callback() {
   echo '<p class="wccao-descr-section-admin">' . __('Configure the settings of the email received by the buyer', 'coupons-after-order') . '</p>';
   
}

function coupons_after_order_enable_callback() {
    $enable = get_option('coupons_after_order_enable', 'no');
    ?>
    <label for="coupons_after_order_enable">
        <input type="checkbox" id="coupons_after_order_enable" name="coupons_after_order_enable" <?php checked($enable, 'yes'); ?> value="yes" />
        <?php _e('Enable Coupon after order', 'coupons-after-order'); ?>
    </label>
    <?php
}

function coupons_after_order_validity_type_callback() {
    $validity_type = get_option('coupons_after_order_validity_type', 'days');
    ?>
    <fieldset>
        <legend style="display:none;"><?php _e('Coupon Validity Type', 'coupons-after-order'); ?></legend>
        <label>
            <input type="radio" name="coupons_after_order_validity_type" value="days" <?php checked($validity_type, 'days'); ?> />
            <?php _e('Coupon Validity (Days)', 'coupons-after-order'); ?>
        </label>
        <br>
        <label>
            <input type="radio" name="coupons_after_order_validity_type" value="date" <?php checked($validity_type, 'date'); ?> />
            <?php _e('Coupon Validity (Date)', 'coupons-after-order'); ?>
        </label>
    </fieldset>
    <?php
}

function coupons_after_order_validity_callback() {
    $validitydays = get_option('coupons_after_order_validitydays');
    $validitydate = get_option('coupons_after_order_validitydate');
    ?>
    <div id="coupon-validity-days-field" class="coupon-field-group">
        <label for="coupon-validity-days" style="display: none;"><?php _e('Coupon Validity Days:', 'coupons-after-order'); ?></label>
        <input type="number" id="coupon-validity-days" name="coupons_after_order_validitydays" value="<?php echo esc_attr($validitydays); ?>" />
        <?php _e('Days', 'coupons-after-order'); ?>
    </div>
    <div id="coupon-validity-date-field" class="coupon-field-group">
        <label for="coupon-validity-date" style="display: none;"><?php _e('Coupon Validity Date:', 'coupons-after-order'); ?></label>
        <input type="date" id="coupon-validity-date" name="coupons_after_order_validitydate" value="<?php echo esc_attr($validitydate); ?>" min="<?php echo date('Y-m-d'); ?>" />
    </div>
    <?php
}



function coupons_after_order_others_parameters_callback() {
   $couponDetails = wccao_generate_coupon_details(0);

   $nber_coupons = absint($couponDetails['nber_coupons']);
   $limitUsage = absint($couponDetails['limitUsage']);
   $indivUseCoupon = $couponDetails['indivUseCoupon'];
   $min_amount = sanitize_text_field($couponDetails['min_amount']);
   $decimal_separator = wc_get_price_decimal_separator();
   $coupon_prefix = sanitize_text_field($couponDetails['coupon_prefix']);
   ?>
   <div class="coupon-field-group">
        <label for="coupons-after-order-count"><?php _e('Number of Coupons Generated:', 'coupons-after-order') ?></label>
        <input type="number" id="coupons-after-order-count" name="coupons_after_order_count" value="<?php echo esc_attr($nber_coupons); ?>" step="1" min="1" required />     
   </div>
   <div class="coupon-field-group">
        <label for="coupon-validity-usage-limit"><?php _e('Limit Usage of Coupons Generated:', 'coupons-after-order') ?></label>
        <input type="number" id="coupon-validity-usage-limit" name="coupons_after_order_usage_limit" value="<?php echo esc_attr($limitUsage); ?>" step="1" min="1" required />
            &nbsp;<?php _e('Use(s)', 'coupons-after-order') ?>    
    </div>
    <div class="coupon-field-group">
        <label for="coupon_individual_use"><?php _e('Individual use only:', 'coupons-after-order') ?></label>
        <input type="checkbox" id="coupon_individual_use" name="coupons_after_order_individual_use"  <?php checked($indivUseCoupon, true); ?> value="yes" />
        <span class="wccao-input-description"><?php _e('Check this box if the promo code cannot be used in conjunction with other promo codes.', 'coupons-after-order') ?></span>
    </div>
    <div class="coupon-field-group">
        <label for="coupon-amount-min"><?php _e('Minimum amount:', 'coupons-after-order') ?></label>
        <input type="text" id="coupon-amount-min" name="coupons_after_order_min_amount" value="<?php echo esc_attr($min_amount); ?>" oninput="validateCouponAmount(this, 'minAmountError')" class="wccao_input_price" data-decimal="<?= esc_attr($decimal_separator); ?>" />
        &nbsp;<?php 
        /* translators: %s: price symbol */
        echo sprintf(__('%s (If empty, it is the amount of the individual coupon.)', 'coupons-after-order'), get_woocommerce_currency_symbol()); 
        ?>     
    </div>
    <div class="coupon-field-group">
        <label for="coupon-prefix"><?= __('Coupon prefix:', 'coupons-after-order') ?></label>
        <input type="text" id="coupon-prefix" name="coupons_after_order_prefix" value="<?php echo esc_attr($coupon_prefix); ?>" pattern="[a-z]+" title="<?php echo esc_html('Only lowercase characters, no numbers','coupons-after-order') ?>" />
        <span class="wccao-input-description"><?php _e('(If empty, by default, it is <strong>"ref"</strong> and the code is in this form "refOrderID-RandomNumber")', 'coupons-after-order'); ?></span>
    </div>
   <?php
}

function coupons_after_order_email_config_callback() {
   $email_subject = get_option('coupons_after_order_email_subject');
   $email_header = get_option('coupons_after_order_email_header');
   ?>
   <div class="coupon-field-group">
    <label for="email_subject" style="display: flex; align-items: center;"><?= __('Email subject:', 'coupons-after-order') ?>
        <input type="text" id="email_subject" name="coupons_after_order_email_subject" value="<?php echo esc_attr($email_subject); ?>" style="flex:auto;" />
    </label>
   </div>
   <div class="coupon-field-group">
    <label for="email_header" style="display: flex; align-items: center;"><?= __('Email header:', 'coupons-after-order') ?>
        <input type="text" id="email_header" name="coupons_after_order_email_header" value="<?php echo esc_attr($email_header); ?>" style="flex:auto;" />
    </label>
   </div>
   <?php
}

/**
 * Generate a WordPress editor.
 *
 * @param string $editor_id     Unique identifier for the editor.
 * @param string $option_name   Name of the option in the database.
 * @param string $default_text  Default text displayed in the editor.
 */
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

/**
 * Callback function to display email configuration options.
 *
 * @param bool $is_before_email True if the editor is for the email's start, false for the end.
 */
function coupons_after_order_email_callback($is_before_email) {
   $editor_data = array(
       'editor_before_email' => array(
           'option_name' => 'coupons_after_order_before_email',
           /* translators: %s: shop name start email*/
           'default_text' => sprintf(__('We hope you are doing well and that you have enjoyed your recent purchase at %s. We thank you for your trust in our products.', 'coupons-after-order'), get_bloginfo('name')),
       ),
       'editor_after_email' => array(
           'option_name' => 'coupons_after_order_after_email',
           /* translators: %s: shop name end email*/
           'default_text' => sprintf(__('<p>If you have any questions or need assistance, our customer service team is here to help.</p><p>Thank you for your loyalty. We hope you enjoy this special.</p><p>Best regards,<br/>%s.</p>', 'coupons-after-order'), get_bloginfo('name')),
       ),
   );

   $editor_id = $is_before_email ? 'editor_before_email' : 'editor_after_email';
   $option_name = $editor_data[$editor_id]['option_name'];
   $default_text = $editor_data[$editor_id]['default_text'];

   generate_wp_editor($editor_id, $option_name, $default_text);
}