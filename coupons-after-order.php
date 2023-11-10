<?php
/**
 * Plugin Name: Coupons after order for WooCommerce
 * Plugin URI: https://github.com/Webpixelia
 * Description: Generate coupons after order completion. The sum of the coupons will be equal to the amount of the order.
 * Author: Webpixelia
 * Version: 1.1.5
 * Author URI: https://webpixelia.com/
 * Requires PHP: 7.1
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coupons-after-order
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

/**
 * Class Coupons_After_Order_WooCommerce.
 *
 * Main Coupons_After_Order_WooCommerce class initializes the plugin.
 *
 * @class		Coupons_After_Order_WooCommerce
 * @version		1.0.0
 * @author		Jeroen Sormani
 */
class Coupons_After_Order_WooCommerce {


	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string $version Plugin version number.
	 */
	public $version = '1.1.5';


	/**
	 * Plugin file.
	 *
	 * @since 1.0.0
	 * @var string $file Plugin file path.
	 */
	public $file = __FILE__;


    /**
	 * Instance of Coupons_After_Order_WooCommerce.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var object $instance The instance of Coupons_After_Order_WooCommerce.
	 */
	private static $instance;


	/**
	 * Construct.
	 *
	 * Initialize the class and plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init();
	}

    
    /**
	 * Instance.
	 *
	 * A global instance of the class. Used to retrieve the instance
	 * to use on other files/plugins/themes.
	 *
	 * @since 1.0.0
	 * @return object Instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Init.
	 *
	 * Initialize plugin parts.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Check if WooCommerce is active
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! function_exists( 'WC' ) ) {
			add_action('admin_notices', 'wccao_woocommerce_not_active_notice');

			function wccao_woocommerce_not_active_notice() {
				deactivate_plugins(plugin_basename(__FILE__));
				$url_wc = 'http://www.woothemes.com/woocommerce/';
				/* translators: %s: link to WooCommerce website */
				$error_message = sprintf(__('Coupons After Order requires <a href="%s" target="_blank">WooCommerce</a> in order to work.', 'coupons-after-order'), $url_wc);
				$message = '<div class="error"><p>';
				$message .= sprintf('<p>%s</p>', $error_message);
				$message .= '</p></div>';

				echo wp_kses_post($message);
			}
		}


		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/admin/wccao-functions.php';

			// Classes
			require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-wccao-admin.php';
			$this->admin = new WCCAO_Admin();
		}

		$this->load_textdomain();
	}

    /**
	 * Textdomain.
	 *
	 * Load the textdomain based on WP language.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
        load_plugin_textdomain('coupons-after-order', false, basename( dirname( __FILE__ ) ) . '/languages');
    }


}


if ( ! function_exists( 'Coupons_After_Order_WooCommerce' ) ) {

	/**
	 * The main function responsible for returning the Coupons_After_Order_WooCommerce object.
	 *
	 * Use this function like you would a global variable, except without needing to declare the global.
	 *
	 * Example: <?php Coupons_After_Order_WooCommerce()->method_name(); ?>
	 *
	 * @since 1.0.0
	 *
	 * @return object Coupons_After_Order_WooCommerce class object.
	 */
	function Coupons_After_Order_WooCommerce() {
		return Coupons_After_Order_WooCommerce::instance();
	}
}

Coupons_After_Order_WooCommerce();