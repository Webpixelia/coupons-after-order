<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Inclusion de l'autoloader de Composer
require_once WCCAO_ABSPATH . 'vendor/autoload.php';
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

if ( ! class_exists( 'LinkCouponsEmail' ) ) :

    class LinkCouponsEmail {

        public function __construct() {
            add_action('template_redirect', array($this, 'wccao_apply_coupon_via_parameter'));
            add_action('woocommerce_cart_calculate_fees', array($this, 'wccao_check_and_apply_coupon'));
            add_action('add_meta_boxes', array($this, 'wccao_qrcode_meta_box'));
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
        public function wccao_apply_coupon_via_parameter() {
            $parameter_link_coupon = get_option('coupons_after_order_url_parameter');
            if (isset($_GET[$parameter_link_coupon])) {                
                $coupon_code = sanitize_text_field($_GET[$parameter_link_coupon]);
                
                if (wc_coupons_enabled() && wc_get_coupon_id_by_code($coupon_code)) {
                    if ($this->wccao_check_cart_conditions($coupon_code)) {
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
        public function wccao_check_cart_conditions($coupon_code) {
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
        public function wccao_check_and_apply_coupon($cart) {
            if (isset($_COOKIE['applied_coupon']) && !empty($_COOKIE['applied_coupon'])) {
                $coupon_code = sanitize_text_field($_COOKIE['applied_coupon']);
        
                if (!in_array($coupon_code, $cart->get_applied_coupons())) {
                    if ($this->wccao_check_cart_conditions($coupon_code)) {
                        $cart->apply_coupon($coupon_code);
                        setcookie('applied_coupon', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
                    }
                }
            }
        }

        /**
         * Creates a link to apply a coupon code.
         *
         * The link will have the following format:
         *
         * `https://example.com/?url_parameter=coupon_code`
         *
         * where `coupon_code` is the value of the coupon code.
         *
         * @since 1.3.2
         * @access public
         *
         * @param string $coupon_code The code of the coupon to apply.
         *
         * @return string The link to apply the coupon code.
         */
        public function wccao_create_link_to_apply_coupon($coupon_code) {
            $parameter_link_coupon = get_option('coupons_after_order_url_parameter');
            return add_query_arg($parameter_link_coupon, $coupon_code, home_url());
        }
        
        /**
         * Adds a meta box to the 'shop_coupon' post type to display a QR code for the coupon.
         *
         * This method is hooked to the 'add_meta_boxes' action.
         * It creates a meta box that contains a QR code image representing the coupon.
         *
         * @since 1.3.2
         * @access public
         *
         * @param string $post_type The type of the current post.
         *                          This method is triggered for the 'shop_coupon' post type.
         */
        public function wccao_qrcode_meta_box($post_type) {
            if ($post_type === 'shop_coupon') {
                add_meta_box(
                    'wccao_qr_code',
                    esc_html__('Coupon QR Code', 'coupons-after-order'),
                    array($this, 'wccao_qrcode_meta_box_callback'),
                    'shop_coupon',
                    'side',
                    'default'
                );
            }
        }
        
        /**
         * Generates a QR code image for a given coupon URL.
         *
         * The QR code is generated using the BaconQrCode library.
         *
         * @since 1.3.2
         * @access public
         *
         * @param string $coupon_url The URL to encode in the QR code.
         *
         * @return string The SVG representation of the QR code image.
         */
        public function wccao_generate_qr_code_image($coupon_url) {
            $qrCode = \BaconQrCode\Encoder\Encoder::encode($coupon_url, \BaconQrCode\Common\ErrorCorrectionLevel::L());
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            return $renderer->render($qrCode);
        }

        /**
         * Generates a base64-encoded PNG QR code image for a given coupon URL.
         *
         * The QR code is generated using the BaconQrCode library with an Imagick backend.
         *
         * @since 1.3.2
         * @access public
         *
         * @param string $coupon_url The URL to encode in the QR code.
         *
         * @return string The base64-encoded PNG representation of the QR code image.
         */
        public function wccao_generate_qr_code_image_base64($coupon_url) {
            $renderer = new ImageRenderer(
                new RendererStyle(150),
                new ImagickImageBackEnd()
            );
        
            $writer = new Writer($renderer);
        
            $base64_image = base64_encode($writer->writeString($coupon_url));

            return 'data:image/png;base64,' . $base64_image;
        }

        /**
         * Callback function for the WooCommerce Coupon QR Code meta box.
         *
         * Displays the QR code for the coupon in the meta box.
         *
         * @since 1.3.2
         * @access public
         *
         * @param WP_Post $post The current coupon post object.
         */
        public function wccao_qrcode_meta_box_callback($post) {
            $coupon_code = $post->post_name;
            if ($post->ID) {
                $coupon_url = $this->wccao_create_link_to_apply_coupon($coupon_code);
                $qrCodeImageBase64 = $this->wccao_generate_qr_code_image_base64($coupon_url);

                printf('<div id="wccao-qr-code"><img src="%s" alt="QR Code"></div>', esc_attr($qrCodeImageBase64));
            } else {
                echo '<p>' . esc_html__('Save coupon then QR code will be generated','coupons-after-order') . '</p>';
            }
        }
        
    }

    new LinkCouponsEmail;

endif;