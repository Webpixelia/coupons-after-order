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
//$event_trigger = get_option('coupons_after_order_event_trigger');
//$event_trigger_hook = ($event_trigger) ? 'woocommerce_order_status_processing' : 'woocommerce_order_status_completed';

add_action('woocommerce_order_status_completed', 'wccao_generate_coupons', 15, 1);

function wccao_generate_coupons($order_id)
{
    $enable = get_option('wccao_coupons_after_order_enable');

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
    $coupon_prefix = get_option('wccao_coupons_after_order_prefix');
    $enabled_start_date = get_option('wccao_coupons_after_order_availability_start_enabled');
    $start_date = get_option('wccao_coupons_after_order_availability_start_date', date_i18n(get_option('date_format')));
    $validity_type = get_option('wccao_coupons_after_order_validity_type');
    $validity = wccao_get_validity($validity_type);
    $limitUsage = get_option('wccao_coupons_after_order_usage_limit', '1');
    $indivUse = get_option('wccao_coupons_after_order_individual_use', 'yes');
    $indivUseCoupon = ($indivUse === 'yes');
    $min_amount = get_option('wccao_coupons_after_order_min_amount');
    $email_restriction = get_option('wccao_coupons_after_order_email_restriction', 'no');

    $nber_coupons = intval(get_option('wccao_coupons_after_order_count'));
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
        return get_option('wccao_coupons_after_order_validitydate');
    } elseif ($validity_type === 'days') {
        $validity_days = intval(get_option('wccao_coupons_after_order_validitydays'));
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

    $link_coupons_email = new WCCAO_LinkCouponsEmail();

    for ($i = 1; $i <= $couponDetails['nber_coupons']; $i++) {
        $coupon = wccao_generate_coupon_code($couponDetails, $order_id, $save, $manual_generation, $customer_email);

        $coupon_code = $coupon->get_code();
        $coupon_amount = esc_html__('Amount:', 'coupons-after-order') . ' ' . wc_price($coupon->get_amount());

        $coupon_url = $link_coupons_email->wccao_create_link_to_apply_coupon($coupon_code);
        $coupon_label = $manual_generation ? esc_html__('Gift coupon', 'coupons-after-order') : esc_html__('My coupon code', 'coupons-after-order');

        $coupon_list .= "
            <li class=\"prefix-coupon\">
                <span class=\"email-title-coupon\">
                    {$coupon_label} {$i}
                </span>
                <br>
                <span class=\"coupon_amount\">{$coupon_amount}</span>
                <br>
                <span class=\"email-code-coupon\">
                    <a href=\"{$coupon_url}\" target=\"_blank\">{$coupon_code}</a>
                </span>
            </li>
        ";
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
function wccao_generate_coupon_code($couponDetails, $order_id, $save = true, $manual_generation = false, $customer_email = null)
{
    $random_number = wp_rand(10000, 99999);
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
    $subject = get_option('wccao_coupons_after_order_email_subject');

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