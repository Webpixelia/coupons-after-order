<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('WCCAO_Account')) :

class WCCAO_Account {

	private $key_page = 'my-coupons';

	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'wccao_enqueue_frontend_css'));
		register_activation_hook(__FILE__, array($this, 'activate_plugin'));
		add_action('init', array($this, 'add_endpoint'));
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_filter('woocommerce_account_menu_items', array($this, 'add_menu_item'));
		add_filter('the_title', array($this, 'endpoint_title'));
		add_action('woocommerce_account_' . $this->key_page . '_endpoint', array($this, 'endpoint_my_coupons_content'));
		add_action('woocommerce_before_cart', array($this, 'add_popup_link_to_display_coupons'));
		add_action('woocommerce_before_checkout_form', array($this, 'add_popup_link_to_display_coupons'));
		add_action('wp_footer', array($this, 'display_popup_coupons_script'));
	}

	public function wccao_enqueue_frontend_css() {
		wp_enqueue_style( 'frontend-coupons-after-order-for-woocommerce', plugins_url( 'assets/css/woocommerce-coupons-after-order-frontend.css', Coupons_After_Order_WooCommerce()->file ), '', Coupons_After_Order_WooCommerce()->version );
		wp_enqueue_script( 'frontend-coupons-after-order-for-woocommerce', plugins_url( 'assets/js/woocommerce-coupons-after-order-frontend.js', Coupons_After_Order_WooCommerce()->file ), array( 'jquery', 'wp-i18n' ), Coupons_After_Order_WooCommerce()->version, true );
	}

	public function activate_plugin() {
		$this->add_endpoint();
		flush_rewrite_rules();
	}

	/**
	 * Adds a custom endpoint for $key_page to WordPress rewrite rules.
	 * This allows for the creation of a dedicated URL for handling "My Coupons" functionality.
	 */
	public function add_endpoint() {
		add_rewrite_endpoint($this->key_page, EP_ROOT | EP_PAGES);
	}

	/**
	 * Adds the custom query variable $key_page to the list of query variables in WordPress.
	 * This ensures that WordPress recognizes and processes the custom endpoint during URL parsing.
	 *
	 * @param array $vars An array of existing query variables.
	 * @return array The modified array with the addition of $key_page.
	 */
	public function add_query_vars($vars) {
		$vars[] = $this->key_page;
		return $vars;
	}

	/**
	 * Adds a menu item for "My Coupons" to the user account navigation menu.
	 * This method reorganizes the menu items by removing the default 'customer-logout'
	 * item and placing it after the new $key_page item.
	 *
	 * @param array $items An array of user account menu items.
	 * @return array The modified array with the addition of $key_page.
	 */
	public function add_menu_item($items) {
		$logout = $items['customer-logout'];
		unset($items['customer-logout']);
		$items[$this->key_page] = __('My Coupons', 'coupons-after-order');
		$items['customer-logout'] = $logout;
		return $items;
	}
	
	/**
	* Endpoint title.
	*/
	public function endpoint_title($title) {
		global $wp_query;
		if (isset($wp_query->query_vars[$this->key_page]) && is_account_page()) {
			return __('My Coupons', 'coupons-after-order');
		}
		return $title;
	}

	/**
	 * Displays the content for the custom WooCommerce endpoint showing user-specific coupons.
	 *
	 * This function retrieves and displays the valid coupons associated with the currently logged-in user.
	 * It provides details such as the coupon code, amount, remaining uses, expiration date, and an option to apply the coupon.
	 * The function also handles cases where no coupons are available or if there are invalid coupon expiration dates.
	 * Coupons are presented in a list format with relevant information, and users can apply eligible coupons directly.
	 *
	 * @since 1.3.2
	 * @access public
	 */
	public function endpoint_my_coupons_content() {
		// Get the user ID of the currently logged-in user
		$user_id = get_current_user_id();
	
		// Retrieve all the coupons associated with the user
		$coupon_meta_key = '_wccao_customer_coupons';
		$customer_coupons = get_user_meta($user_id, $coupon_meta_key, true);
	
		// Display the coupons if any exist
		if (!empty($customer_coupons)) {
			echo '<p>' . esc_html__('Find your still valid personal coupons and their details here.', 'coupons-after-order') . '</p>';
			echo '<p>' . esc_html__('You can click the Copy icon next to the code to copy the code or click Apply coupon.', 'coupons-after-order') . '</p>';
			echo '<p>' . esc_html__('By clicking on Apply coupon, the coupon will be automatically added to the cart if its conditions are met. Otherwise, it will apply once the conditions are met (e.g. minimum amount).', 'coupons-after-order') . '</p>';
			echo '<ul class="wccao-coupons-list">';			
			foreach ($customer_coupons as $coupon_code) {
				// Retrieve coupon object
				$coupon = new WC_Coupon($coupon_code);
				$link_coupons_email_instance = new LinkCouponsEmail();
				$coupon_url = $link_coupons_email_instance->create_link_to_apply_coupon($coupon_code);

				// Expiration date management
				$expiration_date = $coupon->get_date_expires();
				$expiration_text = '';

				if ($expiration_date) {
					$expiration_timestamp = strtotime($expiration_date);
					// Check if conversion to timestamp was successful
					if ($expiration_timestamp !== false) {
						$formatted_expiration_date = date_i18n(get_option('date_format'), $expiration_timestamp);
						$expiration_text = '<li>' . esc_html__('Expiration date:', 'coupons-after-order') . ' ' . $formatted_expiration_date . '</li>';
					} else {
						$expiration_text = '<li>' . esc_html__('Invalid expiration date format', 'coupons-after-order') . '</li>';
					}
				} else {
					$expiration_text = '<li>' . esc_html__('No expiration date', 'coupons-after-order') . '</li>';
				}
				
				$remainingUses = $coupon->get_usage_limit() - $coupon->get_usage_count();

				if ($this->check_coupon_conditions($coupon_code)) :
					echo '<li class="prefix-coupon">';
					echo '<p class="code-coupon"><span class="text-code" id="coupon_' . esc_attr($coupon_code) . '">' . esc_html($coupon_code) . '</span><span class="icon-copy" onclick="copyCouponCode(\'' . esc_attr($coupon_code) . '\')"><img src="'. plugin_dir_url( WCCAO_PLUGIN_BASENAME ) .'assets/img/copy-icon.png" title="Click to Copy" width="16" height="16"></span></p>';
					echo '<ul class="details-coupon">';
					echo '<li>' . esc_html__('Amount:', 'coupons-after-order') . ' ' . wc_price($coupon->get_amount()) . '</li>';
					echo '<li>' . esc_html__('Remaining uses:', 'coupons-after-order') . ' ';
						if ($coupon->get_usage_limit() !== 0) {
							echo $remainingUses;
						} else {
							echo esc_html__('Unlimited', 'coupons-after-order');
						}
					echo '</li>';
					echo $expiration_text;
					echo '<li>' . sprintf('<a href="%1$s">%2$s</a>', esc_url($coupon_url), esc_html__('Apply coupon', 'coupons-after-order')) . '</li>';
					echo '</ul>';			
					echo '</li>';
				endif;
			}		
			echo '</ul>';
		} else {
			echo '<p>' . esc_html__('You don\'t have any coupons yet.', 'coupons-after-order') . '</p>';
		}
	}
	
	/**
	 * Checks the conditions of a WooCommerce coupon.
	 *
	 * @param string $coupon_code The code of the coupon to check.
	 *
	 * @return bool True if the coupon is valid based on the specified conditions, false otherwise.
	 */
	public function check_coupon_conditions($coupon_code) {
		// Retrieve the coupon based on the code
		$coupon = new WC_Coupon($coupon_code);

		// Check if the coupon is null
		if (empty($coupon)) {
			return false; // Invalid coupon
		}

		// Check the expiration date
		if (method_exists($coupon, 'get_date_expires')) {
			$date_expires = $coupon->get_date_expires() ?? '';
			if ($date_expires) {
				$current_time = current_time('timestamp');
				$date_expires_timestamp = strtotime($date_expires);

				if ($current_time > $date_expires_timestamp) {
					return false; // Expired coupon
				}
			} else {
				// Coupon has no expiration date
				return true;
			}
		}

		// Check the remaining usage of the coupon
		if (method_exists($coupon, 'get_usage_limit') && $coupon->get_usage_limit() !== 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit()) {
			return false; // Usage limit reached
		}

		// All conditions are met
		return true; // Valid coupon
	}

	// Add the link to the cart page
	public function add_popup_link_to_display_coupons() {
		echo '<p><a href="#" class="open-popup-link">' . esc_html__('View my coupons', 'coupons-after-order') . '</a></p>';
	}
	
	/**
	 * Displays a popup containing coupon details.
	 *
	 * @return void
	 */
	public function display_popup_coupons_script() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Function to open popup window
				function openPopup(content) {
					var popup = window.open('', 'CouponPopup', 'width=600,height=400,scrollbars=yes,resizable=yes');
					popup.document.write('<html><head>');
					popup.document.write('<link rel="stylesheet" type="text/css" href="' + '<?php echo plugins_url( 'assets/css/woocommerce-coupons-after-order-frontend.css', Coupons_After_Order_WooCommerce()->file ); ?>' + '">');
					popup.document.write('<title><?php _e('Coupons Details', 'coupons-after-order'); ?></title></head><body><div style="padding:2rem">');
					popup.document.write(content);
					popup.document.write('</div>');
					popup.document.write('<script type="text/javascript" src="' + '<?php echo plugins_url( "assets/js/woocommerce-coupons-after-order-frontend.js", Coupons_After_Order_WooCommerce()->file ); ?>' + '"><\/script>');
					popup.document.write('</body></html>');
					popup.document.close();
				}
	
				// Add a link with a specific class to trigger the popup
				$('.open-popup-link').on('click', function(e) {
					e.preventDefault();
					// Get the contents of the method and send it to the popup opening function
					var content = '<h1><?php echo esc_html__('My Coupons', 'coupons-after-order'); ?></h1>' + <?php ob_start(); $this->endpoint_my_coupons_content(); echo json_encode(ob_get_clean()); ?>;
					openPopup(content);
				});
			});
		</script>
		<?php
	}
}

new WCCAO_Account;

endif;