<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'LinkCouponsEmail' ) ) :

    class LinkCouponsEmail {

        public function __construct() {
            add_action('template_redirect', array($this, 'apply_coupon_via_parameter'));
            add_action('woocommerce_cart_calculate_fees', array($this, 'check_and_apply_coupon'));
        }

        /**
         * Applies a coupon code received via URL parameter to the WooCommerce cart.
         *
         * This function checks for a coupon code passed through a URL parameter
         * with the name defined in the "coupons_after_order_url_parameter"
         * option and applies it to the cart if valid and meets cart conditions.
         *
         * @since 1.3.1
         * @access public
         * 
         * @param string $parameter_link_coupon The name of the URL parameter used to pass the coupon code.
         */
        public function apply_coupon_via_parameter() {
            $parameter_link_coupon = get_option('coupons_after_order_url_parameter');
            if (isset($_GET[$parameter_link_coupon])) {                
                $coupon_code = sanitize_text_field($_GET[$parameter_link_coupon]);
                
                if (wc_coupons_enabled() && wc_get_coupon_id_by_code($coupon_code)) {
                    if ($this->check_cart_conditions($coupon_code)) {
                        WC()->cart->apply_coupon($coupon_code);
                        setcookie('wccao_applied_coupon', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
                    } else {
                        setcookie('wccao_applied_coupon', $coupon_code, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
                    }
                }
            }
        }

        /**
         * Checks if a coupon code meets the required conditions to be applied to the WooCommerce cart.
         *
         * This function verifies various conditions for the given coupon code, including:
         *
         * * **Cart is not empty:** The function checks if the cart contains any products before applying the coupon.
         * * **Minimum amount:** If the coupon has a minimum amount requirement, the function compares it with the cart subtotal and returns false if the minimum amount is not met.
         * * **Maximum amount:** Similarly, if the coupon has a maximum amount requirement, the function compares it with the total cart value and returns false if the maximum amount is exceeded.
         * * **Number of uses:** If the coupon has a usage limit, the function checks the remaining uses and returns false if the limit has been reached.
         * * **Validity period:** If the coupon has a validity period defined, the function compares the current date with the coupon's creation and expiration dates to ensure it's valid.
         *
         * @since 1.3.1
         * @access public
         *
         * @param string $coupon_code The code of the coupon to be applied.
         *
         * @return bool True if the coupon meets all conditions and can be applied, false otherwise.
         */
        public function check_cart_conditions($coupon_code) {
            // Récupérer le coupon basé sur le code
            $coupon = new WC_Coupon($coupon_code);
            
            if (WC()->cart->is_empty()) {
                return false;
            }

            if (method_exists($coupon, 'get_minimum_amount') && $coupon->get_minimum_amount() > 0 && WC()->cart->get_cart_subtotal() < $coupon->get_minimum_amount()) {
                return false;
            }

            if (method_exists($coupon, 'get_maximum_amount') && $coupon->get_maximum_amount() > 0 && WC()->cart->get_cart_contents_total() > $coupon->get_maximum_amount()) {
                return false;
            }

            if (method_exists($coupon, 'get_usage_limit') && $coupon->get_usage_count() >= $coupon->get_usage_limit()) {
                return false;
            }

            if (method_exists($coupon, 'get_date_created') && method_exists($coupon, 'get_date_expires')) {
                $current_time = current_time('timestamp');
                
                $date_created_timestamp = strtotime($coupon->get_date_created());
                $date_expires_timestamp = strtotime($coupon->get_date_expires());

                if ($current_time < $date_created_timestamp || $current_time > $date_expires_timestamp) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Checks if a coupon code applied via a cookie meets the required conditions to be applied to the WooCommerce cart.
         *
         * If the coupon meets the conditions, it is applied to the cart and the cookie is deleted.
         *
         * @since 1.3.1
         * @access public
         *
         * @param WC_Cart $cart The WooCommerce cart object.
         */
        public function check_and_apply_coupon($cart) {
            if (isset($_COOKIE['applied_coupon']) && !empty($_COOKIE['applied_coupon'])) {
                $coupon_code = sanitize_text_field($_COOKIE['applied_coupon']);
        
                if (!in_array($coupon_code, $cart->get_applied_coupons())) {
                    if ($this->check_cart_conditions($coupon_code)) {
                        $cart->apply_coupon($coupon_code);
                        setcookie('applied_coupon', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
                    }
                }
            }
        }
    }

    //$link_coupons_email = new LinkCouponsEmail();

endif;