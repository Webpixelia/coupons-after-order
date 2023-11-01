<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

function coupons_after_order_uninstall() {
    delete_option('coupons_after_order_enable');
    delete_option('coupons_after_order_validity_type');
    delete_option('coupons_after_order_validity');
    delete_option('coupons_after_order_validitydate');
    delete_option('coupons_after_order_count');
    delete_option('coupons_after_order_before_email');
    delete_option('coupons_after_order_after_email');
}

coupons_after_order_uninstall();
