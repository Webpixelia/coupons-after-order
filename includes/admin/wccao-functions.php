<?php

/**
 * WC Coupons After Order functions.
 *
 * @author 		Jonathan Webpixelia
 * @since		1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * wccao_get_path
 *
 * Returns the plugin path to a specified file.
 *
 * @since   1.3.1
 *
 * @param   string $filename The specified file.
 * @return  string
 */
function wccao_get_path($filename = '')
{
    return WCCAO_ABSPATH . ltrim($filename, '/');
}

/*
 * wccao_include
 *
 * Includes a file within the WCCAO plugin.
 *
 * @since   1.3.1
 *
 * @param   string $filename The specified file.
 * @return  void
 */
function wccao_include($filename = '')
{
    $file_path = wccao_get_path($filename);
    if (file_exists($file_path)) {
        include_once $file_path;
    }
}

/**
 * Check if current install is WCAO PRO
 *
 * @since 1.3.1
 *
 * @return boolean True if the current install is WCCAO PRO
 */
function wccao_is_pro()
{
    return defined('WCCAO_PRO') && WCCAO_PRO;
}

/**
 * Generate coupons for completed orders based on the provided $order_id.
 *
 * This function triggers when an order status changes to "completed" and generates
 * coupons based on the order details. It retrieves necessary information such as
 * subtotal, existing discounts, and coupon settings, then generates and sends
 * coupon emails if the conditions are met.
 *
 * @param int $order_id The ID of the completed order.
 *
 * @since 1.0.0
 */
add_action('woocommerce_order_status_completed', 'wccao_generate_coupons');

function wccao_generate_coupons($order_id)
{
    $enable = get_option('coupons_after_order_enable');

    // Check if checkbox Enable is checked
    if ($enable === 'yes') {
        // Retrieve missing variables
        $order = wc_get_order($order_id);

        // Total amount order
        $total = $order->get_total();

        // Shipping costs excluded
        $shipping_total = $order->get_shipping_total();
        $shipping_total = is_numeric($shipping_total) ? (float) $shipping_total : 0;

        // Coupons excluded
        $existCoupon = $order->get_discount_total();
        $existCoupon = is_numeric($existCoupon) ? (float) $existCoupon : 0;

        $subTotal = (float) $total - $shipping_total;
        $order_total = (float) $subTotal - $existCoupon;

        $couponDetails = wccao_generate_coupon_details($order_total);

        $coupons_generated = get_post_meta($order_id, '_coupons_generated', true);

        // If coupons are already generated, return
        if ($coupons_generated === 'yes') {
            return;
        }

        $coupon_list = wccao_generate_coupons_list($couponDetails, $order_id);

        $order->update_meta_data('_coupons_generated', 'yes');
        $order->save();

        $result = wccao_send_coupons_email($order, $coupon_list, $couponDetails);
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
 *               - 'enabled_start_date' (): Input radio indicating whether the start date is enabled ('yes' or 'no').
 *               - 'start_date' (string): The start date for the coupon.
 *               - 'validity_type' (string): The type of validity for the coupon ('date' or 'days').
 *               - 'validity' (string): The validity duration for the coupon.
 *               - 'limitUsage' (string): The usage limit for the coupon.
 *               - 'indivUseCoupon' (bool): Whether the coupon is for individual use only.
 *               - 'min_amount' (string): The minimum amount for the coupon.
 *               - 'email_restriction' (bool): Whether the coupon is limited to the buyer's email
 *               - 'order_total' (float): The total amount of the order.
 *               - 'nber_coupons' (int): The number of coupons to be generated.
 *               - 'coupon_amount' (float): The amount for each coupon.
 *               - 'min_order' (float): The minimum order amount for using the coupon.
 */
function wccao_generate_coupon_details($order_total)
{
    $coupon_prefix = get_option('coupons_after_order_prefix');
    $enabled_start_date = get_option('coupons_after_order_availability_start_enabled');
    $start_date = get_option('coupons_after_order_availability_start_date', date_i18n(get_option('date_format')));
    $validity_type = get_option('coupons_after_order_validity_type');
    $validity = wccao_get_validity($validity_type);
    $limitUsage = get_option('coupons_after_order_usage_limit', '1');
    $indivUse = get_option('coupons_after_order_individual_use', 'yes');
    $indivUseCoupon = ($indivUse === 'yes');
    $min_amount = get_option('coupons_after_order_min_amount');
    $email_restriction = get_option('coupons_after_order_email_restriction', 'no');

    $nber_coupons = intval(get_option('coupons_after_order_count'));
    $coupon_amount = ($nber_coupons != 0) ? ($order_total / $nber_coupons) : 0;
    $coupon_amount = round($coupon_amount, wc_get_price_decimals());
    $min_order = empty($min_amount) ? $coupon_amount : max(wccao_tofloat($min_amount), $coupon_amount);

    return compact(
        'coupon_prefix',
        'enabled_start_date',
        'start_date',
        'validity_type',
        'validity',
        'limitUsage',
        'indivUseCoupon',
        'min_amount',
        'email_restriction',
        'order_total',
        'nber_coupons',
        'coupon_amount',
        'min_order'
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
function wccao_get_validity($validity_type)
{
    if ($validity_type === 'date') {
        return get_option('coupons_after_order_validitydate');
    } elseif ($validity_type === 'days') {
        $validity_days = intval(get_option('coupons_after_order_validitydays'));
        return '+' . $validity_days . ' days';
    }
    return '';
}

/**
 * Generate a list of coupons based on the provided details and save them if necessary.
 * This function creates a list of WooCommerce coupons with unique codes using the specified details.
 * It can optionally save the generated coupons to the database and return an HTML list of their codes.
 *
 * @param array $couponDetails An associative array containing details for generating the coupons.
 * @param int $order_id The ID of the order associated with the generated coupons.
 * @param bool $save (Optional) Whether to save the generated coupons to the database (default: true).
 * @param bool $manual_generation (Optional) Whether the coupon is generated manually (default: false).
 * @param string|null $customer_email (Optional) The customer email address associated with the coupons (optional, required for manual generation).
 *
 * @return string|null HTML list of generated coupon codes or null if an error occurs.
 */
function wccao_generate_coupons_list($couponDetails, $order_id, $save = true, $manual_generation = false, $customer_email = null)
{
    $coupon_list = '<ul class="wccao-coupons-list">';

    $link_coupons_email = new LinkCouponsEmail();

    for ($i = 1; $i <= $couponDetails['nber_coupons']; $i++) {
        $coupon = wccao_generate_coupon_code($couponDetails, $order_id, $manual_generation, $customer_email);

        if ($save) {
            $coupon_id = $coupon->save(); // Save the coupon if necessary
            // Update coupon release date if enabled_start_date
            if ($couponDetails['enabled_start_date'] === 'yes') :
                $new_date = strtotime($couponDetails['start_date']);
                $updated_post = array(
                    'ID' => $coupon_id,
                    'post_date' => date_i18n('Y-m-d H:i:s', $new_date),
                    'post_date_gmt' => get_gmt_from_date(date_i18n('Y-m-d H:i:s', $new_date)),
                );

                wp_update_post($updated_post);
            endif;
        }

        $coupon_code = $coupon->get_code();
        $coupon_amount = esc_html__('Amount:', 'coupons-after-order') . ' ' . wc_price($coupon->get_amount());

        $coupon_url = $link_coupons_email->wccao_create_link_to_apply_coupon($coupon_code);
        $coupon_label = $manual_generation ? esc_html__('Gift coupon', 'coupons-after-order') : esc_html__('My coupon code', 'coupons-after-order');

        $coupon_list .= <<<HTML
            <li class="prefix-coupon">
                <span class="email-title-coupon">
                    {$coupon_label} {$i}
                </span>
                <br>
                <span class="coupon_amount">{$coupon_amount}</span>
                <br>
                <span class="email-code-coupon">
                    <a href="{$coupon_url}" target="_blank">{$coupon_code}</a>
                </span>
            </li>
        HTML;
    }
    $coupon_list .= '</ul>';

    return $coupon_list;
}

/**
 * Generate a unique coupon code and create a WooCommerce coupon.
 *
 * Generates a unique coupon code by combining a prefix, order ID, and a random number.
 * Creates a WooCommerce coupon with the specified details and returns the generated coupon code.
 *
 * @param array $couponDetails An associative array containing details for generating the coupon.
 * @param int $order_id The ID of the order associated with the coupon.
 * @param bool $manual_generation Whether the coupon is generated manually (optional).
 * @param string|null $customer_email The customer email address associated with the coupon (optional, required for manual generation).
 *
 * @return WC_Coupon The generated WooCommerce coupon object.
 */
function wccao_generate_coupon_code($couponDetails, $order_id, $manual_generation = false, $customer_email = null)
{
    $random_number = mt_rand(10000, 99999);
    $couponPrefix = ($couponDetails['coupon_prefix']) ? esc_attr($couponDetails['coupon_prefix']) : 'ref';
    $order = wc_get_order($order_id);

    // Calculate the coupon amount
    $coupon_amount = $couponDetails['order_total'] / $couponDetails['nber_coupons']; // Directly use from $couponDetails
    $coupon_amount = round($coupon_amount, wc_get_price_decimals());

    // Create the coupon
    $coupon = new WC_Coupon();
    $order_prefix = $manual_generation ? '-GEN' : $order_id;
    if ($manual_generation) {
        $coupon->set_description(esc_html__('Coupon generated manually for #', 'coupons-after-order') . $customer_email);
        $email_restrictions = $couponDetails['email_restriction'] ? [$customer_email] : [];
    } else if (is_a($order, 'WC_Order')) {
        $coupon->set_description(esc_html__('Coupon for order #', 'coupons-after-order') . $order->get_order_number());
        $email_restrictions = $couponDetails['email_restriction'] ? [$order->get_billing_email()] : [];
    } else {
        $email_restrictions = [];
    }
    $coupon->set_email_restrictions($email_restrictions);
    $coupon->set_code($couponPrefix . $order_prefix . '-' . $random_number);
    $coupon->set_discount_type('fixed_cart');
    $coupon->set_amount($coupon_amount);
    $coupon->set_individual_use($couponDetails['indivUseCoupon']);
    $coupon->set_date_expires(strtotime($couponDetails['validity']));
    $coupon->set_minimum_amount($couponDetails['min_order']); // Minimum usage threshold
    $coupon->set_usage_limit($couponDetails['limitUsage']); // Usage limit

    if (is_a($order, 'WC_Order')) :
        // Retrieve the customer ID associated with the order
        $customer_id = $order->get_customer_id();

        // Retrieve the existing list of customer's coupons
        $coupon_meta_key = '_wccao_customer_coupons';
        $customer_coupons = get_user_meta($customer_id, $coupon_meta_key, true);

        // If the list doesn't exist yet, initialize it as an empty array
        if (!is_array($customer_coupons)) {
            $customer_coupons = array();
        }

        // Add the coupon code to the list of customer's coupons
        $customer_coupons[] = $coupon->get_code();

        // Update the user meta with the new list of coupons
        update_user_meta($customer_id, $coupon_meta_key, $customer_coupons);
    endif;

    return $coupon;
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
function wccao_send_coupons_email($order, $coupon_list, $couponDetails)
{
    $customer_email = $order->get_billing_email();

    // Get subject line from options
    $subject = get_option('coupons_after_order_email_subject');

    // Get custom email template path
    $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/email-template.php';

    // Check if the model exists
    if (file_exists($template_path)) {
        ob_start();
        include $template_path;
        $email_content = ob_get_clean();
    } else {
        $error_message = esc_html__('An error occurred while sending your coupons by email (Email template not found). Please contact us to collect your coupons.', 'coupons-after-order');
        $email_content = '<p>' . $error_message . '</p>';
        $email_content .= '<p>' . /* translators: %s: order ID */ sprintf(esc_html__('As a reminder, here is your order number to communicate to us: %s', 'coupons-after-order'), $order->get_id()) . '</p>';
    }

    // Set content type as HTML
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Send email
    WC()->mailer()->send($customer_email, $subject, $email_content, $headers, '');
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
function wccao_tofloat($num)
{
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    }

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
    );
}

/**
 * Create fields admin.
 *
 * @since 1.1.0
 */
function wccao_register_settings()
{
    wccao_register_sections();
    wccao_register_fields();
}

function wccao_register_sections()
{
    add_settings_section('coupons_after_order_tab_settings', __('Coupons after order Settings', 'coupons-after-order'), 'wccao_tab_settings_callback', 'coupons-after-order-tab-settings-settings', array(
        'before_section' => '<div class="wccao-section-admin">',
        'after_section'  => '</div>',
    ));

    add_settings_section('coupons_after_order_tab_email', __('Coupons after order Email', 'coupons-after-order'), 'wccao_tab_email_callback', 'coupons-after-order-tab-settings-email', array(
        'before_section' => '<div class="wccao-section-admin">',
        'after_section'  => '</div>',
    ));

    add_settings_section('coupons_after_order_tab_misc', __('Coupons after order Misc', 'coupons-after-order'), 'wccao_tab_misc_callback', 'coupons-after-order-tab-settings-misc', array(
        'before_section' => '<div class="wccao-section-admin">',
        'after_section'  => '</div>',
    ));

    add_settings_section('coupons_after_order_tab_version', __('Coupons after order Version', 'coupons-after-order'), 'wccao_tab_version_callback', 'coupons-after-order-tab-settings-version');
}

function wccao_register_fields()
{
    // Add fields to options
    // Settings
    add_settings_field('coupons_after_order_enable', __('Enable Coupon after order', 'coupons-after-order'), 'wccao_enable_callback', 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
    add_settings_field('coupons_after_order_availability_start', __('Define start availability date', 'coupons-after-order'), 'wccao_validity_start_callback', 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
    add_settings_field('coupons_after_order_validity_type', __('Coupon Validity Type', 'coupons-after-order'), 'wccao_validity_type_callback', 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
    add_settings_field('coupons_after_order_validity', __('Coupon Validity', 'coupons-after-order'), 'wccao_validity_callback', 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
    add_settings_field('coupons_after_order_others_parameters', __('Other Parameters', 'coupons-after-order'), 'wccao_others_parameters_callback', 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
    // Email
    add_settings_field('coupons_after_order_email_config', __('Email settings', 'coupons-after-order'), 'wccao_email_config_callback', 'coupons-after-order-tab-settings-email', 'coupons_after_order_tab_email');
    add_settings_field('coupons_after_order_email_content', __('Email text to customize with shortcodes', 'coupons-after-order'), function () {
        wccao_email_callback();
    }, 'coupons-after-order-tab-settings-email', 'coupons_after_order_tab_email');
    add_settings_field('coupons_after_order_email_button', __('Email button', 'coupons-after-order'), 'wccao_email_button_callback', 'coupons-after-order-tab-settings-email', 'coupons_after_order_tab_email');
    // Misc
    add_settings_field('coupons_after_order_data_uninstall', __('Remove data on uninstall', 'coupons-after-order'), 'wccao_miscellaneous_data_uninstall_callback', 'coupons-after-order-tab-settings-misc', 'coupons_after_order_tab_misc');
    add_settings_field('coupons_after_order_emails_and_amounts', __('Generate coupons manually and send them directly', 'coupons-after-order'), 'wccao_miscellaneous_emails_and_amounts_callback', 'coupons-after-order-tab-settings-misc', 'coupons_after_order_tab_misc');

    // Save settings 
    // Settings
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_enable');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_availability_start_enabled');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_availability_start_date');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_availability_start');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_validity_type');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_validitydays', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 30,
    ));
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_validitydate');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_count', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 4,
    ));

    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_usage_limit', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint'
    ));
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_individual_use');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_min_amount');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_email_restriction');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_prefix');
    register_setting('coupons-after-order-tab-settings-settings', 'coupons_after_order_url_parameter');
    // Email
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_subject', array(
        /* translators: %s: shop */
        'default' => sprintf(__('Your promo codes to enjoy the refund offer at %s', 'coupons-after-order'), get_bloginfo('name')),
    ));
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_header', array(
        'default' => __('Thank you for your order', 'coupons-after-order'),
    ));
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_content');
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_bt_title', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => __('I\'m enjoying it now', 'coupons-after-order'),
    ));
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_bt_url', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_url',
        'default' => get_home_url(),
    ));
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_bt_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ffffff',
    ));
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_bt_bg_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => get_option('woocommerce_email_base_color'),
    ));
    register_setting('coupons-after-order-tab-settings-email', 'coupons_after_order_email_bt_font_size', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => '16',
    ));
    // Misc
    register_setting('coupons-after-order-tab-settings-misc', 'coupons_after_order_data_uninstall');
}

add_action('admin_init', 'wccao_register_settings');


/**
 * Callback functions.
 *
 * Generate fields in admin page.
 *
 * @since 1.0.0
 */
// Tabs
function wccao_tab_settings_callback()
{
    echo '<p class="wccao-descr-section-admin settings-tab">' . esc_html__('Configure generated coupon settings', 'coupons-after-order') . '</p>';;
}

function wccao_tab_email_callback()
{
    echo '<p class="wccao-descr-section-admin email-tab">' . esc_html__('Configure the settings of the email received by the buyer', 'coupons-after-order') . '</p>';
    $shortcodes = [
        'billing_first_name' => esc_html__('Display of the customer\'s name in the event of an order or "Dear customer" if coupons generated manually', 'coupons-after-order'),
        'billing_email' => esc_html__('Displaying billing email, useful if coupons are limited to this email', 'coupons-after-order'),
        'coupons' => esc_html__('Displaying coupons in list form', 'coupons-after-order'),
        'coupon_amount' => esc_html__('Displaying the coupon amount', 'coupons-after-order'),
        'order_total' => esc_html__('Displaying the total order amount', 'coupons-after-order'),
        'nb_coupons' => esc_html__('Displaying the number of coupons generated', 'coupons-after-order'),
        'min_amount_order' => esc_html__('Displaying the minimum basket amount to use the coupon', 'coupons-after-order'),
        'start_date' => esc_html__('Displaying the date of the order', 'coupons-after-order'),
        'end_date' => esc_html__('Displaying the expiry date', 'coupons-after-order'),
        'shop_button' => esc_html__('Displaying a button', 'coupons-after-order'),
    ];
    echo '<p><strong>' . esc_html__('Shortcodes:', 'coupons-after-order') . '</strong><br><ul>';
    foreach ($shortcodes as $shortcode_key => $shortcode_value) {
        echo '<li>{' .  esc_html($shortcode_key) . '} : ' . esc_html($shortcode_value) . '</li>';
    }
    echo '</ul></p>';
}

function wccao_tab_misc_callback()
{
    echo '<p class="wccao-descr-section-admin misc-tab">' . esc_html__('Find here various administration options', 'coupons-after-order') . '</p>';
}

function wccao_tab_version_callback()
{
    $wccao_admin_instance = new WCCAO_Admin();
    $result = $wccao_admin_instance->wccao_perform_version_check_cron();
    $notice = $result['notice'];

    echo '<div class="version-tab notice inline ' . esc_attr($notice) . '">';
    echo '<h3 class="has-icon">' . esc_html__('Installed version ', 'coupons-after-order') . Coupons_After_Order_WooCommerce()->version . '</h3>';
    echo esc_html($result['message']);
    echo '</div>';
}

// Fields
function wccao_enable_callback()
{
    $enable = get_option('coupons_after_order_enable', 'no');
?>
    <label for="coupons_after_order_enable">
        <input type="checkbox" id="coupons_after_order_enable" name="coupons_after_order_enable" <?php checked($enable, 'yes'); ?> value="yes" />
        <?php esc_html_e('Enable Coupon after order', 'coupons-after-order'); ?>
    </label>
<?php
}

function wccao_validity_start_callback()
{
    $availability_start_enabled = get_option('coupons_after_order_availability_start_enabled', 'no');
    $availability_start_date = get_option('coupons_after_order_availability_start_date', date_i18n(get_option('date_format')));
    $validitydate = get_option('coupons_after_order_validitydate');
?>
    <div id="coupon_availability_start_enabled" class="coupon-field-group">
        <fieldset>
            <legend><?php esc_html_e('Define start availability date?', 'coupons-after-order'); ?></legend>
            <label for="coupon_availability_start_true">
                <input type="radio" id="coupon_availability_start_true" name="coupons_after_order_availability_start_enabled" value="yes" <?php checked($availability_start_enabled, 'yes'); ?> />
                <?php esc_html_e('Yes', 'coupons-after-order'); ?>
            </label>
            <br>
            <label for="coupon_availability_start_false">
                <input type="radio" id="coupon_availability_start_false" name="coupons_after_order_availability_start_enabled" value="no" <?php checked($availability_start_enabled, 'no'); ?> />
                <?php esc_html_e('No', 'coupons-after-order'); ?>
            </label>
        </fieldset>
    </div>
    <div id="coupon_availability_date">
        <label for="coupon_availability_start_date"><?php esc_html_e('Coupon Start Date:', 'coupons-after-order'); ?></label>
        <input type="date" id="coupon_availability_start_date" name="coupons_after_order_availability_start_date" value="<?php echo esc_attr($availability_start_date); ?>" min="<?php echo date_i18n('Y-m-d'); ?>" max="<?php echo $validitydate; ?>" />
        <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('Enter the desired date on which the coupon will be published and therefore valid. Please note, enter a date before the expiration date of the coupon if you have configured it.', 'coupons-after-order') ?>"></span>
    </div>
<?php
}

function wccao_validity_type_callback()
{
    $validity_type = get_option('coupons_after_order_validity_type', 'days');
?>
    <fieldset>
        <legend style="display:none;"><?php esc_html_e('Coupon Validity Type', 'coupons-after-order'); ?></legend>
        <label>
            <input type="radio" name="coupons_after_order_validity_type" value="days" <?php checked($validity_type, 'days'); ?> />
            <?php esc_html_e('Coupon Validity (Days)', 'coupons-after-order'); ?>
        </label>
        <br>
        <label>
            <input type="radio" name="coupons_after_order_validity_type" value="date" <?php checked($validity_type, 'date'); ?> />
            <?php esc_html_e('Coupon Validity (Date)', 'coupons-after-order'); ?>
        </label>
    </fieldset>
<?php
}

function wccao_validity_callback()
{
    $validitydays = get_option('coupons_after_order_validitydays');
    $validitydate = get_option('coupons_after_order_validitydate');
?>
    <div id="coupon-validity-days-div" class="coupon-field-group">
        <label for="coupon-validity-days" style="display: none;"><?php esc_html_e('Coupon Validity Days:', 'coupons-after-order'); ?></label>
        <input type="number" id="coupon-validity-days" name="coupons_after_order_validitydays" value="<?php echo esc_attr($validitydays); ?>" step="1" min="1" />
        <?php esc_html_e('Days', 'coupons-after-order'); ?>
    </div>
    <div id="coupon-validity-date-div" class="coupon-field-group">
        <label for="coupon-validity-date" style="display: none;"><?php esc_html_e('Coupon Validity Date:', 'coupons-after-order'); ?></label>
        <input type="date" id="coupon-validity-date" name="coupons_after_order_validitydate" value="<?php echo esc_attr($validitydate); ?>" min="<?php echo date_i18n('Y-m-d'); ?>" />
    </div>
<?php
}

function wccao_others_parameters_callback()
{
    $couponDetails = wccao_generate_coupon_details(0);

    $nber_coupons = absint($couponDetails['nber_coupons']);
    $limitUsage = absint($couponDetails['limitUsage']);
    $indivUseCoupon = $couponDetails['indivUseCoupon'];
    $min_amount = $couponDetails['min_amount'];
    $emaillUseLimit = $couponDetails['email_restriction'];
    $decimal_separator = wc_get_price_decimal_separator();
    $coupon_prefix = sanitize_text_field($couponDetails['coupon_prefix']);
    $coupon_url_parameter = sanitize_text_field(get_option('coupons_after_order_url_parameter'));
?>
    <div class="coupon-field-group">
        <label for="coupons-after-order-count"><?php esc_html_e('Number of Coupons Generated:', 'coupons-after-order') ?></label>
        <input type="number" id="coupons-after-order-count" name="coupons_after_order_count" value="<?php echo esc_attr($nber_coupons); ?>" step="1" min="1" required />
    </div>
    <div class="coupon-field-group">
        <label for="coupon-validity-usage-limit"><?php esc_html_e('Limit Usage of Coupons Generated:', 'coupons-after-order') ?></label>
        <input type="number" id="coupon-validity-usage-limit" name="coupons_after_order_usage_limit" value="<?php echo esc_attr($limitUsage); ?>" step="1" min="1" required />
        <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('How many times this coupon can be used before it is void.', 'coupons-after-order') ?>"></span>
    </div>
    <div class="coupon-field-group">
        <label for="coupon_individual_use"><?php esc_html_e('Individual use only:', 'coupons-after-order') ?></label>
        <input type="checkbox" id="coupon_individual_use" name="coupons_after_order_individual_use" <?php checked($indivUseCoupon, true); ?> value="yes" />
        <span class="wccao-input-description"><?php esc_html_e('Check this box if the promo code cannot be used in conjunction with other promo codes.', 'coupons-after-order') ?></span>
    </div>
    <div class="coupon-field-group">
        <label for="coupon-amount-min"><?php esc_html_e('Minimum amount:', 'coupons-after-order') ?></label>
        <input type="text" id="coupon-amount-min" name="coupons_after_order_min_amount" value="<?php echo esc_attr($min_amount); ?>" class="wccao_input_price" data-decimal="<?php echo esc_attr($decimal_separator); ?>" placeholder="<?php esc_html_e('No minimum', 'coupons-after-order') ?>" />&nbsp;<?php echo get_woocommerce_currency_symbol(); ?>
        <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If empty, it is the amount of the individual coupon.', 'coupons-after-order'); ?>"></span>
    </div>
    <div class="coupon-field-group">
        <label for="coupon_email_restriction"><?php esc_html_e('Limit to the buyer email:', 'coupons-after-order') ?></label>
        <input type="checkbox" id="coupon_email_restriction" name="coupons_after_order_email_restriction" <?php checked($emaillUseLimit, 'yes'); ?> value="yes" />
        <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If checked, only the order billing email address will be able to benefit from the coupons generated', 'coupons-after-order'); ?>"></span>
    </div>
    <div class="coupon-field-group">
        <label for="coupon-prefix"><?php esc_html_e('Coupon prefix:', 'coupons-after-order') ?></label>
        <input type="text" id="coupon-prefix" name="coupons_after_order_prefix" value="<?php echo esc_attr($coupon_prefix); ?>" pattern="[a-z]+" title="<?php echo esc_html('Only lowercase characters, no numbers', 'coupons-after-order') ?>" placeholder="<?php echo esc_html__('"ref" by default', 'coupons-after-order'); ?>" />
        <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If empty, by default, it is "ref" and the code is in this form "refOrderID-RandomNumber"', 'coupons-after-order'); ?>"></span>
    </div>
    <div class="coupon-field-group">
        <label for="coupon_url_parameter"><?php esc_html_e('URL Parameter link:', 'coupons-after-order') ?></label>
        <input type="text" id="coupon_url_parameter" name="coupons_after_order_url_parameter" value="<?php echo esc_attr($coupon_url_parameter); ?>" pattern="[a-z]+" title="<?php echo esc_html('Only lowercase characters, no numbers', 'coupons-after-order') ?>" placeholder="<?php echo esc_html__('"apply_coupon" by default', 'coupons-after-order'); ?>" />
        <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If empty, by default, it is "apply_coupon"', 'coupons-after-order'); ?>"></span>
    </div>
<?php
}

function wccao_email_config_callback()
{
    $email_subject = get_option('coupons_after_order_email_subject');
    $email_header = get_option('coupons_after_order_email_header');
    $couponDetails = wccao_generate_coupon_details(0);
    $coupon_prefix = sanitize_text_field($couponDetails['coupon_prefix']);
?>
    <div class="coupon-field-group">
        <label for="email_subject" style="display: flex; align-items: center;"><?php esc_html_e('Email subject:', 'coupons-after-order') ?>
            <input type="text" id="email_subject" name="coupons_after_order_email_subject" value="<?php echo esc_attr($email_subject); ?>" style="flex:auto;" />
        </label>
    </div>
    <div class="coupon-field-group">
        <label for="email_header" style="display: flex; align-items: center;"><?php esc_html_e('Email header:', 'coupons-after-order') ?>
            <input type="text" id="email_header" name="coupons_after_order_email_header" value="<?php echo esc_attr($email_header); ?>" style="flex:auto;" />
        </label>
    </div>
    <input type="hidden" id="hidden-coupon-prefix" name="hidden_coupon_prefix" value="<?php echo esc_attr($coupon_prefix); ?>" />
<?php
}

function wccao_html_email_content()
{
    $html_content = '
            <p>' . /* translators: %s: buyer name */ sprintf(esc_html__('Hello %s,', 'coupons-after-order'), esc_html('{billing_first_name}')) . '</p>
            <p>' . /* translators: %1$s: order amount */
        /* translators: %2$s: number of coupons generated */
        /* translators: %3$s: amount of each coupon */ sprintf(esc_html__('To thank you, we are sending you your promo codes corresponding to our full refund offer. You spent %1$s on your last purchase, entitling you to %2$s promo codes, each worth %3$s.', 'coupons-after-order'), '{order_total}', '{nb_coupons}', '{coupon_amount}') . '</p>
            <p>' . /* translators: %s: minimum cart amount for coupon use */ sprintf(esc_html__('Each promo code is valid for a minimum cart value of %s.', 'coupons-after-order'), '{min_amount_order}') . '</p>
            <p>' . /* translators: %s: list of coupons generated */ sprintf(esc_html__('Here are your promo codes: %s', 'coupons-after-order'), '{coupons}') . '</p>
            <p>' . esc_html__('To use these promo codes on your next purchase, simply follow these steps:', 'coupons-after-order') . '</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>' . esc_html__('Add the items of your choice to your cart.', 'coupons-after-order') . '</li>
                <li>' . esc_html__('During the payment process, enter one of these promo codes in the corresponding "Promo Code" box.', 'coupons-after-order') . '</li>
                <li>' . /* translators: %s: coupon amount */ sprintf(esc_html__('The discount of %s will be automatically applied to your order.', 'coupons-after-order'), '{coupon_amount}') . '</li>
                <li>' . /* translators: %1$s: start date of validity of generated coupons */
        /* translators: %2$s: end date of validity of generated coupons */ sprintf(esc_html__('Please note that these promo codes are valid from %1$s until %2$s and cannot be combined in a single order.', 'coupons-after-order'), '{start_date}', '{end_date}') . '</li>
            </ul>
            <p>' . esc_html__('If you have any questions or need assistance, our customer service team is here to help.', 'coupons-after-order') . '</p>
            <p>' . esc_html__('Thank you for your loyalty. We hope you enjoy this special.', 'coupons-after-order') . '</p>
            <p>' . esc_html__('Best regards', 'coupons-after-order') . ',<br/>' . get_bloginfo('name') . '.</p>
        ';
    return $html_content;
}

/**
 * Callback function to display email configuration options.
 *
 * @param bool $is_before_email True if the editor is for the email's start, false for the end.
 */
function wccao_email_callback()
{
    $option_name = 'coupons_after_order_email_content';
    $default_content = wccao_html_email_content();

    $options = get_option($option_name);
    $content = !empty($options) ? $options : $default_content;

    $settings = array(
        'media_buttons' => false,
        'textarea_rows' => 20,
        'textarea_name' => $option_name,
        'default_editor' => 'tinymce',
        'tinymce' => true,
        'quicktags' => true,
    );

    wp_editor($content, 'wccao_email_content', $settings);
}

function wccao_email_button_callback()
{
    $email_bt_title = get_option('coupons_after_order_email_bt_title');
    $email_bt_url = get_option('coupons_after_order_email_bt_url');
    $email_bt_color = get_option('coupons_after_order_email_bt_color');
    $email_bt_bg_color = get_option('coupons_after_order_email_bt_bg_color');
    $email_bt_font_size = get_option('coupons_after_order_email_bt_font_size');
?>
    <div class="bt-preview"><a id="emailButton" href="#" target="_blank" style="text-decoration:none;display:inline-block;padding:10px 30px;margin:10px 0;"></a></div>
    <div class="coupon-field-group">
        <label for="wccao_email_bt_title"><?php esc_html_e('Button title:', 'coupons-after-order') ?>
            <input type="text" id="wccao_email_bt_title" name="coupons_after_order_email_bt_title" value="<?php echo esc_attr($email_bt_title); ?>" />
        </label>
    </div>
    <div class="coupon-field-group">
        <label for="wccao_email_bt_url"><?php esc_html_e('Button URL:', 'coupons-after-order') ?>
            <input type="url" id="wccao_email_bt_url" name="coupons_after_order_email_bt_url" value="<?php echo esc_attr($email_bt_url); ?>" />
        </label>
    </div>
    <div class="coupon-field-group">
        <label for="wccao_email_bt_color"><?php esc_html_e('Button color:', 'coupons-after-order') ?>
            <input type="color" id="wccao_email_bt_color" name="coupons_after_order_email_bt_color" value="<?php echo esc_attr($email_bt_color); ?>" />
        </label>
    </div>
    <div class="coupon-field-group">
        <label for="wccao_email_bt_bg_color"><?php esc_html_e('Button background color:', 'coupons-after-order') ?>
            <input type="color" id="wccao_email_bt_bg_color" name="coupons_after_order_email_bt_bg_color" value="<?php echo esc_attr($email_bt_bg_color); ?>" />
        </label>
    </div>
    <div class="coupon-field-group">
        <label for="wccao_email_bt_font_size"><?php esc_html_e('Button font size (px):', 'coupons-after-order') ?>
            <input type="number" id="wccao_email_bt_font_size" name="coupons_after_order_email_bt_font_size" value="<?php echo esc_attr($email_bt_font_size); ?>" min="1" />
        </label>
    </div>
<?php
}

function wccao_miscellaneous_data_uninstall_callback()
{
    $data_uninstall = get_option('coupons_after_order_data_uninstall', 'no');
?>
    <div class="coupon-field-group">
        <label for="coupons_after_order_data_uninstall">
            <input type="checkbox" id="coupons_after_order_data_uninstall" name="coupons_after_order_data_uninstall" <?php checked($data_uninstall, 'yes'); ?> value="yes" />
            <?php esc_html_e('Check this box if you would like to completely remove all of its data upon plugin deletion.', 'coupons-after-order'); ?>
        </label>
    </div>
<?php
}

function wccao_miscellaneous_emails_and_amounts_callback()
{
?>
    <div class="coupon-field-group" style="display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1em;">
        <label for="coupons_after_order_emails_and_amounts">
            <textarea id="coupons_after_order_emails_and_amounts" name="coupons_after_order_emails_and_amounts" placeholder="mon-email@gmail.om;45.5" rows="4" cols="50"></textarea>
            <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('Enter the information as follows: "email;order_amount". The email and the amount must be separated by ";" and the decimal separator is "."', 'coupons-after-order'); ?>"></span>

        </label>
        <a id="wccao_generate_manually_link" href="#" class="button wccao-email-test-link"><?php esc_html_e('Generate and send', 'coupons-after-order') ?></a>
        <span id="wccao-email-message-notice" class="email-message-send" style="display:none;"></span>
    </div>
<?php
}
