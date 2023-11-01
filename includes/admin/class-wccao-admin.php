<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin class.
 *
 * Handle all general admin business.
 *
 * @class		WCCG_Admin
 * @author		Jeroen Sormani
 * @package		WooCommerce Coupon Generator
 * @version		1.0.0
 */
class WCCAO_Admin {


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add admin page
		add_action( 'admin_menu', array( $this, 'add_wccao_admin_page' ) );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

        //add_action( 'init', array( $this, 'init' ) ); // Used init because admin_init is too late for admin_menu
	}


	/**
	 * Enqueue scripts.
	 *
	 * Enqueue script as javascript and style sheets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts( $hook ) {
		$current_screen = get_current_screen();

		if ( strpos( $current_screen->id, 'coupons-after-order-settings' ) !== false ) {
			//wp_enqueue_style( 'coupons!after-order-for-woocommerce', plugins_url( 'assets/css/woocommerce-coupons-after-order-admin.min.css', Coupons_After_Order_WooCommerce()->file ), array( 'woocommerce_admin_styles', 'jquery-ui-style' ), Coupons_After_Order_WooCommerce()->version );

			wp_enqueue_script( 'coupons!after-order-for-woocommerce', plugins_url( 'assets/js/woocommerce-coupons-after-order-admin.min.js', Coupons_After_Order_WooCommerce()->file ), array( 'jquery' ), Coupons_After_Order_WooCommerce()->version, true );
		}
	}


	/**
	 * Add admin page.
	 *
	 * Add the generator page to the WordPress admin.
	 *
	 * @since 1.0.0
	 */
	public function add_wccao_admin_page() {
		global $admin_page_hooks;
		$parent_menu = ( isset( $admin_page_hooks['woocommerce-marketing'] ) ) ? 'woocommerce-marketing' : 'woocommerce';
		add_submenu_page(
            $parent_menu, 
            __('Coupons after order Settings', 'coupons-after-order'), 
            __('Coupons after order', 'coupons-after-order'), 
            'manage_options', 
            'coupons-after-order-settings', 
            array( $this, 'coupons_after_order_admin_page' )
        );
	}


	/**
	 * Coupons after order admin page.
	 *
	 * Initialize and output the contents of the admin page
	 * page in the admin backend.
	 *
	 * @since 1.0.0
	 */
	public function coupons_after_order_admin_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('Coupons after order Settings', 'coupons-after-order') ?></h2>
    
            <?php settings_errors(); ?>
    
            <form method="post" action="options.php">
                <?php
                // Display sections and fields for WooCommerce settings
                settings_fields('woocommerce');
                do_settings_sections('woocommerce');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}