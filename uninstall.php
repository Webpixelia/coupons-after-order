<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

if (!function_exists('wccao_uninstall_conditionally')) {
    function wccao_uninstall_conditionally() {
        // Check if option 'coupons_after_order_data_uninstall' is set to 'yes'
        $data_uninstall = get_option('coupons_after_order_data_uninstall');

        if ($data_uninstall === 'yes') {
            $wccao_options = array(
                'coupons_after_order_enable',
                'coupons_after_order_availability_start_enabled',
                'coupons_after_order_availability_start_date',
                'coupons_after_order_validity_type',
                'coupons_after_order_validitydays',
                'coupons_after_order_validitydate',
                'coupons_after_order_count',
                'coupons_after_order_usage_limit',
                'coupons_after_order_individual_use',
                'coupons_after_order_min_amount',
                'coupons_after_order_email_restriction',
                'coupons_after_order_prefix',
                'coupons_after_order_url_parameter',
                'coupons_after_order_email_subject',
                'coupons_after_order_email_header',
                'coupons_after_order_email_content',
                'coupons_after_order_email_bt_title',
                'coupons_after_order_email_bt_url',
                'coupons_after_order_email_bt_color',
                'coupons_after_order_email_bt_bg_color',
                'coupons_after_order_email_bt_font_size',
                'coupons_after_order_data_uninstall'
            );

            foreach ($wccao_options as $wcao_option) {
                delete_option($wcao_option);
            }
        }
    }
}

wccao_uninstall_conditionally();