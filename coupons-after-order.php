<?php
/**
* Plugin Name: Coupons after order for WooCommerce
* Plugin URI: https://github.com/Webpixelia
* Description: Generate coupons after order completion. The sum of the coupons will be equal to the amount of the order.
* Author: Webpixelia
* Version: 1.3.2
* Author URI: https://webpixelia.com/
* Requires PHP: 7.1
* Requires at least: 5.0
* Tested up to: 6.4
* WC requires at least: 5.0
* WC tested up to: 8.3
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
 * @author		Jonathan Webpixelia
 */

class Coupons_After_Order_WooCommerce {

	protected $admin;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string $version Plugin version number.
	 */
	public $version = '1.3.2';


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
	 * @access protected
	 * @var object $instance The instance of Coupons_After_Order_WooCommerce.
	 */
	protected static $instance = null;

	/**
	 * Main Coupons_After_Order_WooCommerce Instance
	 *
	 * Ensures only one instance of Coupons_After_Order_WooCommerce is loaded or can be loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @static
	 * @return  Coupons_After_Order_WooCommerce - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Construct.
	 *
	 * Initialize the class and plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
		$this->load_textdomain();
	}

	/**
	 * Define WCCAO Constants.
	 */
	private function define_constants() {
		define( 'WCCAO_PLUGIN_FILE', __FILE__ );
		define( 'WCCAO_ABSPATH', plugin_dir_path(WCCAO_PLUGIN_FILE) );
		define( 'WCCAO_PLUGIN_BASENAME', plugin_basename( WCCAO_PLUGIN_FILE ) );
		define( 'WCCAO_VERSION', $this->version );
	}

	/**
	 * Include required core files used in admin.
	 */
	public function includes() {
		require_once WCCAO_ABSPATH . 'includes/admin/wccao-functions.php';
		// Admin
		if ( is_admin() ) {			
			require_once WCCAO_ABSPATH . 'includes/admin/class-wccao-admin.php';
			$this->admin = new WCCAO_Admin();
		}

		require_once WCCAO_ABSPATH . 'includes/admin/class-link-coupons-email.php';
		require_once WCCAO_ABSPATH . 'includes/admin/class-wccao-account.php';

		// Include PRO.
		wccao_include( 'pro/wccao-pro.php' );
		if ( is_admin() && function_exists( 'wccao_is_pro' ) && ! wccao_is_pro() ) {
			wccao_include( 'pro/admin/admin-settings-pages.php' );
		}
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action('admin_notices', array( $this, 'wccao_woocommerce_not_active_notice'));
	}

	/**
	 * Check if WooCommerce installed and activated
	 *
	 * @return bool
	 */
	public function wccao_dependencies_satisfied() {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! function_exists( 'WC' ) ) {
			return false;
		}

		return true;
	}
	
	/**
	 * Output a admin notice when build dependencies not met.
	 *
	 * @return void
	 */
	public function  wccao_woocommerce_not_active_notice() {
		if ( $this->wccao_dependencies_satisfied() ) {
			return;
		}

		deactivate_plugins(plugin_basename(__FILE__));
		$url_wc = 'http://wordpress.org/extend/plugins/woocommerce/';
		$error_message = sprintf(
		/* translators: %s: link to WooCommerce website */	
			__('Coupons After Order for WooCommerce requires <a href="%s" target="_blank">WooCommerce</a> to be installed & activated!.', 'coupons-after-order'), 
			$url_wc
		);

		printf('<div class="error"><p>%s</p></div>', $error_message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

	/**
	 * Defines a constant if doesnt already exist.
	 *
	 * @since   1.3.1
	 *
	 * @param   string $name The constant name.
	 * @param   mixed  $value The constant value.
	 * @return  void
	 */
	public function define( $name, $value = true ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}

/**
 * Setup WooCommerce HPOS compatibility.
*/
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


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