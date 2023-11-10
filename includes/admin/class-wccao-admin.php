<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin class.
 *
 * Handle all general admin business.
 *
 * @class		WCCAO_Admin
 * @author		Jonathan Webpixelia
 * @package		Coupon after order for WooCommerce
 * @version		1.0.0
 */
class WCCAO_Admin {

	/**
	 * Plugin name
	 * 
	 * @since 1.0.0
	 * @var string $name Plugin name
	 */
	public const PLUG_NAME = 'Coupons after order';

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

		// Add custom meta box to WooCommerce orders page
		add_action( 'add_meta_boxes', array( $this, 'coupons_after_order_meta_box' ) );
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
			wp_enqueue_style( 'css-coupons-after-order-for-woocommerce', plugins_url( 'assets/css/woocommerce-coupons-after-order-admin.css', Coupons_After_Order_WooCommerce()->file ), array( 'woocommerce_admin_styles', 'jquery-ui-style' ), Coupons_After_Order_WooCommerce()->version );
			wp_enqueue_script( 'js-coupons-after-order-for-woocommerce', plugins_url( 'assets/js/woocommerce-coupons-after-order-admin.js', Coupons_After_Order_WooCommerce()->file ), array( 'jquery', 'wp-i18n' ), Coupons_After_Order_WooCommerce()->version, true );
		
			// Pass translation strings to JavaScript
			$translation_strings = array(
				/* translators: %s: price decimal separator */
				'customErrorMessage' => sprintf( __( 'Please enter a numeric value and the defined decimal separator (%s), without thousands separators or currency symbols', 'coupons-after-order' ), wc_get_price_decimal_separator() ),
				'textDisplayedToggle' => __('Show email template', 'coupons-after-order'),
				'textHiddenToggle' => __('Hide email template', 'coupons-after-order'),
			);
			wp_localize_script( 'js-coupons-after-order-for-woocommerce', 'couponsAfterOrderTranslations', $translation_strings );
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
			WCCAO_Admin::PLUG_NAME,
			WCCAO_Admin::PLUG_NAME,
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
            <h2><?php 
			/* translators: %s: plugin name */
			printf( esc_html__('%s Settings', 'coupons-after-order'), WCCAO_Admin::PLUG_NAME ); ?></h2>
    
            <?php settings_errors(); ?>
    
            <form method="post" action="options.php">
                <?php
                // Display sections and fields for WooCommerce settings
                settings_fields('woocommerce');
                do_settings_sections('woocommerce');
				$template_file = plugin_dir_path(dirname(__DIR__)) . 'templates/html-email-template-preview-admin.php';
				if (file_exists($template_file)) {
					include $template_file;
				}
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }


	/**
	 * Add custom meta box.
	 *
	 * @return void
	 */
	public function coupons_after_order_meta_box() {
		add_meta_box(
			'custom-order-meta-box',
			WCCAO_Admin::PLUG_NAME,
			array($this, 'coupons_after_order_meta_box_callback'),
			'shop_order',
			'advanced',
			'core'
		);
	}	

	/**
	 * Callback function for custom meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function coupons_after_order_meta_box_callback($post) {
		// Get the saved value
		$coupons_generated = get_post_meta($post->ID, '_coupons_generated', true);
		
		// Determine whether to display "Yes" or "No" based on the value
		$display_value = ($coupons_generated === 'yes') ? 'Yes' : 'No';
		
		// Output the input field
		echo '<p><label for="coupons-after-order-meta-box">' . __('Coupons generated:', 'coupons-after-order') . '</label> ';
		echo '<input type="text" id="coupons-after-order-meta-box" name="coupons_generated" value="' . esc_attr($display_value) . '" disabled /></p>';
	}	
}