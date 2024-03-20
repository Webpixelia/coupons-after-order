<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

if (!function_exists('wccao_uninstall_conditionally')) {
    function wccao_uninstall_conditionally() {
        // Check if option 'wccao_coupons_after_order_data_uninstall' is set to 'yes'
        $data_uninstall = get_option('wccao_coupons_after_order_data_uninstall');

        if ($data_uninstall === 'yes') {
            $wccao_options = array(
                'wccao_coupons_after_order_enable',
                //'wccao_coupons_after_order_event_trigger',
                'wccao_coupons_after_order_availability_start_enabled',
                'wccao_coupons_after_order_availability_start_date',
                'wccao_coupons_after_order_validity_type',
                'wccao_coupons_after_order_validitydays',
                'wccao_coupons_after_order_validitydate',
                'wccao_coupons_after_order_count',
                'wccao_coupons_after_order_usage_limit',
                'wccao_coupons_after_order_individual_use',
                'wccao_coupons_after_order_min_amount',
                'wccao_coupons_after_order_email_restriction',
                'wccao_coupons_after_order_prefix',
                'wccao_coupons_after_order_url_parameter',
                'wccao_coupons_after_order_email_subject',
                'wccao_coupons_after_order_email_header',
                'wccao_coupons_after_order_email_content',
                'wccao_coupons_after_order_email_bt_title',
                'wccao_coupons_after_order_email_bt_url',
                'wccao_coupons_after_order_email_bt_color',
                'wccao_coupons_after_order_email_bt_bg_color',
                'wccao_coupons_after_order_email_bt_font_size',
                'wccao_coupons_after_order_coupon_font_color',
                'wccao_coupons_after_order_coupon_bg_color',
                'wccao_coupons_after_order_data_uninstall',	
            );

            foreach ($wccao_options as $wcao_option) {
                delete_option($wcao_option);
            }
        }
    }
}

wccao_uninstall_conditionally();