<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
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
	public const WCCAO_PLUGIN_NAME = 'Coupons after order';

	/**
	 * Settings page admin
	 * 
	 * @since 1.0.0
	 * @var string $name Settings page admin
	 */
	public const WCCAO_ADMIN_SLUG = 'coupons-after-order-settings';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Action
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_menu', array( $this, 'add_wccao_admin_page' ) );
		add_action( 'add_meta_boxes', array( $this, 'coupons_after_order_meta_box' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'wccao_custom_orders_list_column_content' ), 20, 2 );
		add_action( 'wp_ajax_wccao_send_email_test', array($this, 'wccao_send_email_test'));
		add_action(	'wp_ajax_wccao_manually_generate_coupons', array($this, 'wccao_manually_generate_coupons'));
		add_action( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'current_screen', array( $this, 'current_screen' ) );
		add_action( 'wccao_check_version_cron', array($this, 'perform_version_check_cron'));

		//add_filter('woocommerce_settings_pages', array($this, 'add_custom_settings_field'));

		// Cron Task
		if (!wp_next_scheduled('wccao_check_version_cron')) {
			wp_schedule_event(time(), 'daily', 'wccao_check_version_cron');
		}

		// Filter
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'wccao_custom_shop_order_column' ), 20 );
		add_filter( 'plugin_action_links_' . WCCAO_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );			
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

		if ( strpos( $current_screen->id, WCCAO_Admin::WCCAO_ADMIN_SLUG ) !== false ) {
			wp_enqueue_style( 'admin-coupons-after-order-for-woocommerce', plugins_url( 'assets/css/woocommerce-coupons-after-order-admin.css', Coupons_After_Order_WooCommerce()->file ), array( 'woocommerce_admin_styles', 'jquery-ui-style' ), Coupons_After_Order_WooCommerce()->version );
			wp_enqueue_script( 'admin-coupons-after-order-for-woocommerce', plugins_url( 'assets/js/woocommerce-coupons-after-order-admin.js', Coupons_After_Order_WooCommerce()->file ), array( 'jquery', 'wp-i18n' ), Coupons_After_Order_WooCommerce()->version, true );
		
			// Pass translation strings to JavaScript
			$translation_strings = array(
				/* translators: %s: price decimal separator */
				'customErrorMessage' => sprintf( __( 'Please enter a numeric value and the defined decimal separator (%s), without thousands separators or currency symbols', 'coupons-after-order' ), wc_get_price_decimal_separator() ),
				'textDisplayedToggle' => __('Show email template', 'coupons-after-order'),
				'textHiddenToggle' => __('Hide email template', 'coupons-after-order'),
				'errorMessageText' => __('Error sending test email. Please try again.', 'coupons-after-order'),
				'errorMessageEmptyEmail' => __('Please enter an email address.', 'coupons-after-order'),
				'errorMessageFalseEmail' => __('Please enter a valid email address.', 'coupons-after-order'),

				'errorUndefined' => __('Undefined error', 'coupons-after-order'),
				'errorAjaxRequest' => __('Error during AJAX request:', 'coupons-after-order'),
				'successEmailsCouponsGenerated' => __('Successful processing for all email-value pairs.', 'coupons-after-order'),
				'errorInvalidFormat' => __('The entry format is invalid. Please use the email;amount format.', 'coupons-after-order'),
			);
			wp_localize_script( 'admin-coupons-after-order-for-woocommerce', 'couponsAfterOrderTranslations', $translation_strings );
			
			// Nonce
			$wccao_manually_generate_coupons_nonce = wp_create_nonce('wccao_manually_generate_coupons_nonce');
			wp_add_inline_script('admin-coupons-after-order-for-woocommerce', 'var wccao_manually_generate_coupons_nonce = "' . $wccao_manually_generate_coupons_nonce . '";', 'before');
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
			WCCAO_Admin::WCCAO_PLUGIN_NAME,
			WCCAO_Admin::WCCAO_PLUGIN_NAME,
            'manage_options',
			WCCAO_Admin::WCCAO_ADMIN_SLUG,
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
	public function coupons_after_order_admin_page($tabs) {
		// Get the settings for the plugin
		$settings = get_option('woocommerce_coupons_after_order_settings');

		// Apply the woocommerce_coupons_after_order_settings filter
		$settings = apply_filters_ref_array('woocommerce_coupons_after_order_settings', array(&$settings));

		// Output the settings
        ?>
        <div class="wrap">
            <h2><?php 
			/* translators: %s: plugin name */
			printf( esc_html__('%s Settings', 'coupons-after-order'), WCCAO_Admin::WCCAO_PLUGIN_NAME ); 
			?>
			</h2>
		
			<?php
			$tabs = array(
				'settings' => __('Settings', 'coupons-after-order'),
				'email'    => __('Email', 'coupons-after-order'),
				'misc'     => __('Misc', 'coupons-after-order'),
				'version'  => __('Version', 'coupons-after-order'),
			);

			$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
			?>

			<nav class="wccao-nav-bar nav-tab-wrapper">
				<?php foreach ($tabs as $tab_key => $tab_label) : ?>
					<a href="<?php echo admin_url( 'admin.php?page=' . WCCAO_Admin::WCCAO_ADMIN_SLUG ) . '&tab=' . esc_attr( $tab_key ); ?>" class="wccao-nav-tab nav-tab <?php echo ($current_tab === $tab_key) ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html($tab_label); ?>
					</a>
				<?php endforeach; ?>
				</nav>
    
            <?php settings_errors(); ?>
    
            <form method="post" action="options.php">
			<?php
			// Display sections and fields for WooCommerce settings
			$tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';

			switch ($tab) {
				case 'settings':
					settings_fields('coupons-after-order-tab-settings-settings');
					do_settings_sections('coupons-after-order-tab-settings-settings');
					break;

				case 'email':
					settings_fields('coupons-after-order-tab-settings-email');
					do_settings_sections('coupons-after-order-tab-settings-email'); ?>				
					<?php $template_file = plugin_dir_path(dirname(__DIR__)) . 'templates/html-email-template-preview-admin.php';
					if (file_exists($template_file)) {
						include $template_file;
					}
					break;

				case 'misc':
					settings_fields('coupons-after-order-tab-settings-misc');
					do_settings_sections('coupons-after-order-tab-settings-misc');
					break;

				case 'version':
					settings_fields('coupons-after-order-tab-settings-version');
					do_settings_sections('coupons-after-order-tab-settings-version');
					break;

				default:
					break;
			}

            if (!isset($_GET['tab']) || $_GET['tab'] !== 'version') {
				submit_button();
			}					
        	?>
            </form>
        </div>
        <?php
    }


	/**
	 * Add custom meta box to single order page.
	 *
	 * @return void
	 */
	public function coupons_after_order_meta_box() {
		$screen = wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

		add_meta_box(
			'custom-order-meta-box',
			WCCAO_Admin::WCCAO_PLUGIN_NAME,
			array($this, 'coupons_after_order_meta_box_callback'),
			$screen,
			'advanced',
			'core'
		);
	}

	/**
	 * Callback function for custom meta box.
	 *
	 * @param WP_Post|object $post WP_Post object or any other object with similar properties.
	 */
	public function coupons_after_order_meta_box_callback($post) {
		// Get the order object
		$order = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
	
		// Get the saved value
		$coupons_generated = sanitize_text_field($order->get_meta('_coupons_generated', true));

		// Determine whether to display "Yes" or "No" based on the value
		$display_value = ($coupons_generated === 'yes') ? 'Yes' : 'No';
	
		// Output the input field
		echo '<p><label for="coupons-after-order-meta-box">' . __('Coupons generated:', 'coupons-after-order') . '</label> ';
		echo '<input type="text" id="coupons-after-order-meta-box" name="coupons_generated" value="' . esc_attr($display_value) . '" disabled /></p>';
	}

	/**
	 * Function to customize columns in the WooCommerce orders list.
	 *
	 * @param array $columns Default columns in the orders list.
	 * @return array Reordered columns with the new 'coupons_generated' column.
	 */
	public function wccao_custom_shop_order_column( $columns ) {
		$reordered_columns = array();
		// Inserting columns to a specific location.
		foreach ( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				// Inserting after "Status" column.
				$reordered_columns['coupons_generated'] = __('Coupons Generated', 'coupons-after-order');
			}
		}
		return $reordered_columns;
	}

	/**
	 * Function to display content in the custom 'coupons_generated' column.
	 *
	 * @param string $column   The name of the current column.
	 * @param int    $order_id The order ID.
	 */
	public function wccao_custom_orders_list_column_content($column, $order_id) {
		if ($column === 'coupons_generated') {
				// Get custom order meta data.
				$order = wc_get_order( $order_id );
				$coupons_generated = $order->get_meta( '_coupons_generated', true );
				echo $coupons_generated === 'yes' ? 'Yes' : 'No';
				unset( $order );
		}
	}

	/**
	 * Sends a test email with the generated coupons.
	 *
	 * This function simulates an order with a given total amount, then generates coupons based on that amount. The coupons are then sent to the email address specified in the form data.
	 *
	 * @param string $user_email The email address of the email recipient.
	 * @param float $order_total The total amount of the simulated order.
	 * @param bool $save Whether to save the generated coupons to the database (optional, defaults to `true`).
	 * @param bool $manual_generation Whether the generation is triggered manually (optional, defaults to `false`).
	 *
	 * @return void
	 */
	public static function wccao_send_email($user_email, $order_total, $save = true, $manual_generation = false, $customer_email = null)
	{
		// Simulate dummy data
		$order = new WC_Order();
		$order->set_billing_email($user_email);
		$order->set_total($order_total);
		$order->set_currency(get_woocommerce_currency());
		$current_time = current_time('mysql');
		$date_created = new WC_DateTime($current_time);
		$order->set_date_created($date_created);

		// Generate coupons based on the total amount of the order
		$order_total = $order->get_total();
		$couponDetails = wccao_generate_coupon_details($order_total);

		// Generate the list of coupons
		$coupon_list = wccao_generate_coupons_list($couponDetails, $order->get_id(), $save, $manual_generation, $customer_email);

		// Call the existing function with dummy data and the generated coupons
		wccao_send_coupons_email($order, $coupon_list, $couponDetails);
	}

	/**
	 * Sends a test email with the generated coupons.
	 *
	 * This function retrieves the email address from the form data and then calls the `wccao_send_email()` function to send the test email.
	 *
	 * @return void
	 */
	public function wccao_send_email_test()
	{
		// Retrieve the value of $_POST['user_email']
		$user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';

		// Define $manual_generation for test sending (optional)
		$manual_generation = false; // By default, the sending simulates automatic behavior
		$customer_email = null;

		// Call the method by passing the arguments
		self::wccao_send_email($user_email, 100, false, $manual_generation, $customer_email);

		// Return the status of the email sending
		$data = array('success' => true, 'data' => array('status' => 'success'));
		wp_send_json_success($data);
	}


	/**
	 * Generate and send coupons manually based on submitted data.
	 *
	 * This function processes AJAX requests for manual coupon generation. It expects
	 * JSON data containing email addresses and order amounts for each coupon. It validates
	 * the data, generates and sends email with coupons if valid, and returns a JSON response
	 * indicating success or failure.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function wccao_manually_generate_coupons()
	{
		// Check ajax reference
		check_ajax_referer('wccao_manually_generate_coupons_nonce', 'security');

		// Retrieve posted data
		$dataArray = json_decode(stripslashes($_POST['dataArray']), true);

		// Check if JSON conversion was successful
		if ($dataArray === null) {
			$dataError = array(
				'error' => true,
				'data' => array(
					'status' => 'error',
					'message' => __('Error decoding JSON.', 'coupons-after-order')
				)
			);
			wp_send_json_error($dataError);
			return;
		}

		// Process the array
		foreach ($dataArray as $item) {
			// Data
			$custome_email = sanitize_email($item['email']);
			$amount_order = floatval($item['value']);

			self::wccao_send_email($custome_email, $amount_order, true, true, $custome_email);
		}
		
		// Send a reply
		$dataSuccessAll = array(
			'success' => true,
			'data' => array(
				'status' => 'success',
				'message' => __('Email sent.', 'coupons-after-order')
			)
		);
		wp_send_json_success($dataSuccessAll);
		wp_die();
	}

	/**
	 * Appends custom admin body class.
	 *
	 * @since   1.3.0
	 *
	 * @param   string $classes CSS classe.
	 * @return  string
	 */
	public function admin_body_class( $classes ) {
		$classes = ' wccao-admin';

		// Return classes.
		return $classes;
	}
	
	/**
	 * Adds custom functionality to "Coupons after order for WooCommerce" admin pages.
	 *
	 * @since   1.3.0
	 *
	 * @param   void
	 * @return  void
	 */
	public function current_screen( $screen ) {
		// Determine if the current page being viewed is "ACF" related.
		if ( strpos( $screen->id, WCCAO_Admin::WCCAO_ADMIN_SLUG ) !== false ) {
			add_filter( 'admin_footer_text', array( $this, 'wccao_admin_footer_text' ) );
			add_filter( 'update_footer', array( $this, 'wccao_update_footer' ) );
		}
	}
	
	/**
	 * Customizes the admin footer text with a credit link to Webpixelia.
	 *
	 * @param string $credit The current admin footer text.
	 * @return string The modified admin footer text with Webpixelia credit.
	 */
	public function wccao_admin_footer_text( $credit ) {
		$url = 'https://webpixelia.com/';
		$credit = '<span id="webpixelia-credit">' . sprintf('<span>%s</span> <a href="%s" target="_blank">Webpixelia</a>', esc_html(__('Coupons after order for WooCommerce is powered by', 'coupons-after-order')), esc_url($url)) . '</span>';
	
		return $credit;
	}

	/**
	 * Customizes the update footer HTML with the plugin version.
	 *
	 * @param string $html The current update footer HTML.
	 * @return string The modified update footer HTML with the plugin version.
	 */
	public function wccao_update_footer( $html ) {
		$version = Coupons_After_Order_WooCommerce()->version;
		$html = '<span>Version ' . $version . '</span>';

		return $html;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=' . WCCAO_Admin::WCCAO_ADMIN_SLUG ) . '" aria-label="' . esc_attr__( 'View WooCommerce settings', 'woocommerce' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Perform a version check using GitHub API to determine if a new version of the plugin is available.
	 *
	 * Return the result array containing the notice type and message.
	 */
	public function perform_version_check_cron() {
		$github_url = 'https://api.github.com/repos/Webpixelia/coupons-after-order/releases/latest';

		// Perform a request to the GitHub API.
		$response = wp_remote_get($github_url);

		// Initialize the result array.
		$result = array('notice' => '', 'message' => '');

		// Check if the request was successful.
		if (!is_wp_error($response)) {
			// Parse the JSON response.
			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body);

			$latest_version = $data->tag_name;
			$current_version = Coupons_After_Order_WooCommerce()->version;

			// Compare versions.
			if (version_compare($current_version, $latest_version, '<')) {
				$result['notice'] = 'notice-error';
				/* translators: %s: last version available */
				$result['message'] = sprintf(__('A new version of the plugin (%s) is available. Please update it.', 'coupons-after-order'), $latest_version);
			} else {
				$result['notice'] = 'notice-success';
				$result['message'] = __('Great! You are using the latest version of ', 'coupons-after-order') . WCCAO_Admin::WCCAO_PLUGIN_NAME;
			}
		} else {
			// Error during the request to the GitHub API.
			$result['message'] = __('Unable to check the latest version of the plugin.', 'coupons-after-order');
		}

		// Return the result array.
		return $result;
	}

	/*public function add_custom_settings_field($settings) {
        $settings[] = array(
            'title'    => __('My Coupons Endpoint', 'coupons-after-order'),
            'desc'     => __('Enter your custom endpoint for My Coupons.', 'coupons-after-order'),
            'id'       => 'my_coupons_endpoint',
            'type'     => 'text',
            'default'  => 'my-coupons',
            'desc_tip' => true,
        );

    	return $settings;
	}*/
}