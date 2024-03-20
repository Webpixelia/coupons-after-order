<?php
//@since 1.3.8
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('WCCAO_Fields_Register')) :

    class WCCAO_Fields_Register
    {
        public function __construct()
        {
            add_action('admin_init', array($this, 'wccao_register_settings'));
        }

        public function wccao_register_settings()
        {
            $this->wccao_register_sections();
            $this->wccao_register_fields();
        }

        public function wccao_register_sections()
        {
            add_settings_section('coupons_after_order_tab_settings', __('Coupons after order Settings', 'coupons-after-order'), array($this, 'wccao_tab_settings_callback'), 'coupons-after-order-tab-settings-settings', array(
                'before_section' => '<div class="wccao-section-admin">',
                'after_section'  => '</div>',
            ));

            add_settings_section('coupons_after_order_tab_email', __('Coupons after order Email', 'coupons-after-order'), array($this, 'wccao_tab_email_callback'), 'coupons-after-order-tab-settings-email', array(
                'before_section' => '<div class="wccao-section-admin">',
                'after_section'  => '</div>',
            ));

            add_settings_section('coupons_after_order_tab_misc', __('Coupons after order Misc', 'coupons-after-order'), array($this, 'wccao_tab_misc_callback'), 'coupons-after-order-tab-settings-misc', array(
                'before_section' => '<div class="wccao-section-admin">',
                'after_section'  => '</div>',
            ));

            add_settings_section('coupons_after_order_tab_version', __('Coupons after order Version', 'coupons-after-order'), array($this, 'wccao_tab_version_callback'), 'coupons-after-order-tab-settings-version');
        }

        public function wccao_register_fields()
        {
            // Add fields to options
            // Settings
            add_settings_field('wccao_coupons_after_order_enable', __('Enable Coupon after order', 'coupons-after-order'), array($this, 'wccao_enable_callback'), 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
            //add_settings_field('wccao_coupons_after_order_event_trigger', __('Change the coupon generation trigger event', 'coupons-after-order'), array($this, 'wccao_event_trigger_callback'), 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
            add_settings_field('wccao_coupons_after_order_availability_start', __('Define start availability date', 'coupons-after-order'), array($this, 'wccao_validity_start_callback'), 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
            add_settings_field('wccao_coupons_after_order_validity_type', __('Coupon Validity Type', 'coupons-after-order'), array($this, 'wccao_validity_type_callback'), 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
            add_settings_field('wccao_coupons_after_order_validity', __('Coupon Validity', 'coupons-after-order'), array($this, 'wccao_validity_callback'), 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
            add_settings_field('wccao_coupons_after_order_others_parameters', __('Other Parameters', 'coupons-after-order'), array($this, 'wccao_others_parameters_callback'), 'coupons-after-order-tab-settings-settings', 'coupons_after_order_tab_settings');
            // Email
            add_settings_field('wccao_coupons_after_order_email_config', __('Email settings', 'coupons-after-order'), array($this, 'wccao_email_config_callback'), 'coupons-after-order-tab-settings-email', 'coupons_after_order_tab_email');
            add_settings_field('wccao_coupons_after_order_email_content', __('Email text to customize with shortcodes', 'coupons-after-order'), function () {
                $this->wccao_email_callback();
            }, 'coupons-after-order-tab-settings-email', 'coupons_after_order_tab_email');
            add_settings_field('wccao_coupons_after_order_email_button', __('Email button', 'coupons-after-order'), array($this, 'wccao_email_button_callback'), 'coupons-after-order-tab-settings-email', 'coupons_after_order_tab_email');
            add_settings_field('wccao_coupons_after_order_coupon_design', __('Coupon design', 'coupons-after-order'), array($this, 'wccao_coupon_design_callback'), 'coupons-after-order-tab-settings-email', 'coupons_after_order_tab_email');
            // Misc
            add_settings_field('wccao_coupons_after_order_data_uninstall', __('Remove data on uninstall', 'coupons-after-order'), array($this, 'wccao_miscellaneous_data_uninstall_callback'), 'coupons-after-order-tab-settings-misc', 'coupons_after_order_tab_misc');
            add_settings_field('wccao_coupons_after_order_emails_and_amounts', __('Generate coupons manually and send them directly', 'coupons-after-order'), array($this, 'wccao_miscellaneous_emails_and_amounts_callback'), 'coupons-after-order-tab-settings-misc', 'coupons_after_order_tab_misc');

            // Save settings 
            // Settings
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_enable');
            //register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_event_trigger');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_availability_start_enabled');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_availability_start_date');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_validity_type');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_validitydays', array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 30,
            ));
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_validitydate');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_count', array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 4,
            ));

            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_usage_limit', array(
                'type' => 'integer',
                'sanitize_callback' => 'absint'
            ));
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_individual_use');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_min_amount');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_email_restriction');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_prefix');
            register_setting('coupons-after-order-tab-settings-settings', 'wccao_coupons_after_order_url_parameter');
            // Email
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_subject', array(
                'default' => get_option('wccao_coupons_after_order_email_subject'),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_header', array(
                'default' => get_option('wccao_coupons_after_order_email_header'),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_content');
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_bt_title', array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => get_option('wccao_coupons_after_order_email_bt_title'),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_bt_url', array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_url',
                'default' => get_home_url(),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_bt_color', array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color',
                'default' => get_option('wccao_coupons_after_order_email_bt_color'),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_bt_bg_color', array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color',
                'default' => get_option('woocommerce_email_base_color'),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_coupon_font_color', array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color',
                'default' => get_option('wccao_coupons_after_order_coupon_font_color'),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_coupon_bg_color', array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color',
                'default' => get_option('woocommerce_email_base_color'),
            ));
            register_setting('coupons-after-order-tab-settings-email', 'wccao_coupons_after_order_email_bt_font_size', array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => get_option('wccao_coupons_after_order_email_bt_font_size'),
            ));
            // Misc
            register_setting('coupons-after-order-tab-settings-misc', 'wccao_coupons_after_order_data_uninstall');
        }

        /**
         * Callback functions.
         *
         * Generate fields in admin page.
         *
         * @since 1.0.0
         */
        // Tabs
        public function wccao_tab_settings_callback()
        {
            echo '<p class="wccao-descr-section-admin settings-tab">' . esc_html__('Configure generated coupon settings', 'coupons-after-order') . '</p>';;
        }

        public function wccao_tab_email_callback()
        {
            echo '<p class="wccao-descr-section-admin email-tab">' . esc_html__('Configure the settings of the email received by the buyer', 'coupons-after-order') . '</p>';
            $shortcodes = [
                'billing_first_name' => esc_html__('Display of the customer\'s name in the event of an order or "Dear customer" if coupons generated manually', 'coupons-after-order'),
                'billing_email' => esc_html__('Displaying billing email, useful if coupons are limited to this email', 'coupons-after-order'),
                'coupons' => esc_html__('Displaying coupons in list form', 'coupons-after-order'),
                'coupon_amount' => esc_html__('Displaying the coupon amount', 'coupons-after-order'),
                'order_total' => esc_html__('Displaying the total order amount', 'coupons-after-order'),
                'nb_coupons' => esc_html__('Displaying the number of coupons generated', 'coupons-after-order'),
                'min_amount_order' => esc_html__('Displaying the minimum basket amount to use the coupon', 'coupons-after-order'),
                'start_date' => esc_html__('Displaying the date of the order', 'coupons-after-order'),
                'end_date' => esc_html__('Displaying the expiry date', 'coupons-after-order'),
                'shop_button' => esc_html__('Displaying a button', 'coupons-after-order'),
            ];
            echo '<p><strong>' . esc_html__('Shortcodes:', 'coupons-after-order') . '</strong><br><ul>';
            foreach ($shortcodes as $shortcode_key => $shortcode_value) {
                echo '<li>{' .  esc_html($shortcode_key) . '} : ' . esc_html($shortcode_value) . '</li>';
            }
            echo '</ul></p>';
        }

        public function wccao_tab_misc_callback()
        {
            echo '<p class="wccao-descr-section-admin misc-tab">' . esc_html__('Find here various administration options', 'coupons-after-order') . '</p>';
        }

        public function wccao_tab_version_callback()
        {
            $installed_version = WCCAO_Coupons_After_Order_WooCommerce()->version;

            echo '<div class="version-tab notice inline">';
            // translators: %s: Installed version placeholder
            echo '<h3 class="has-icon">' . sprintf(esc_html__('Installed version %s', 'coupons-after-order'), esc_html($installed_version)) . '</h3>';
            echo '</div>';
        }

        // Fields
        public function wccao_enable_callback()
        {
            $enable = get_option('wccao_coupons_after_order_enable', 'no');
?>
            <label for="wccao_coupons_after_order_enable">
                <input type="checkbox" id="wccao_coupons_after_order_enable" name="wccao_coupons_after_order_enable" <?php checked($enable, 'yes'); ?> value="yes" />
                <?php esc_html_e('Enable Coupon after order', 'coupons-after-order'); ?>
            </label>
        <?php
        }

        /*public function wccao_event_trigger_callback()
        {
            $event_trigger = get_option('wccao_coupons_after_order_event_trigger', 'no');
        ?>
            <label for="wccao_coupons_after_order_event_trigger">
                <input type="checkbox" id="wccao_coupons_after_order_event_trigger" name="wccao_coupons_after_order_event_trigger" <?php checked($event_trigger, 'yes'); ?> value="yes" />
                <?php esc_html_e('Generate coupons in On-hold status rather than Completed', 'coupons-after-order'); ?>
            </label>
        <?php
        }*/

        public function wccao_validity_start_callback()
        {
            $availability_start_enabled = get_option('wccao_coupons_after_order_availability_start_enabled', 'no');
            $availability_start_date = get_option('wccao_coupons_after_order_availability_start_date', date_i18n(get_option('date_format')));
            $validitydate = get_option('wccao_coupons_after_order_validitydate');
        ?>
            <div id="coupon_availability_start_enabled" class="coupon-field-group">
                <fieldset>
                    <legend><?php esc_html_e('Define start availability date?', 'coupons-after-order'); ?></legend>
                    <label for="coupon_availability_start_true">
                        <input type="radio" id="coupon_availability_start_true" name="wccao_coupons_after_order_availability_start_enabled" value="yes" <?php checked($availability_start_enabled, 'yes'); ?> />
                        <?php esc_html_e('Yes', 'coupons-after-order'); ?>
                    </label>
                    <br>
                    <label for="coupon_availability_start_false">
                        <input type="radio" id="coupon_availability_start_false" name="wccao_coupons_after_order_availability_start_enabled" value="no" <?php checked($availability_start_enabled, 'no'); ?> />
                        <?php esc_html_e('No', 'coupons-after-order'); ?>
                    </label>
                </fieldset>
            </div>
            <div id="coupon_availability_date" class="coupon-field-group">
                <label for="coupon_availability_start_date"><?php esc_html_e('Coupon Start Date:', 'coupons-after-order'); ?></label>
                <input type="date" id="coupon_availability_start_date" name="wccao_coupons_after_order_availability_start_date" value="<?php echo esc_attr($availability_start_date); ?>" min="<?php echo esc_attr(date_i18n('Y-m-d')); ?>" max="<?php echo esc_attr($validitydate); ?>" />
                <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('Enter the desired date on which the coupon will be published and therefore valid. Please note, enter a date before the expiration date of the coupon if you have configured it.', 'coupons-after-order') ?>"></span>
            </div>
        <?php
        }

        public function wccao_validity_type_callback()
        {
            $validity_type = get_option('wccao_coupons_after_order_validity_type', 'days');
        ?>
            <fieldset>
                <legend style="display:none;"><?php esc_html_e('Coupon Validity Type', 'coupons-after-order'); ?></legend>
                <label>
                    <input type="radio" name="wccao_coupons_after_order_validity_type" value="days" <?php checked($validity_type, 'days'); ?> />
                    <?php esc_html_e('Coupon Validity (Days)', 'coupons-after-order'); ?>
                </label>
                <br>
                <label>
                    <input type="radio" name="wccao_coupons_after_order_validity_type" value="date" <?php checked($validity_type, 'date'); ?> />
                    <?php esc_html_e('Coupon Validity (Date)', 'coupons-after-order'); ?>
                </label>
            </fieldset>
        <?php
        }

        public function wccao_validity_callback()
        {
            $validitydays = get_option('wccao_coupons_after_order_validitydays');
            $validitydate = get_option('wccao_coupons_after_order_validitydate');
        ?>
            <div id="coupon-validity-days-div" class="coupon-field-group">
                <label for="coupon-validity-days" style="display: none;"><?php esc_html_e('Coupon Validity Days:', 'coupons-after-order'); ?></label>
                <input type="number" id="coupon-validity-days" name="wccao_coupons_after_order_validitydays" value="<?php echo esc_attr($validitydays); ?>" step="1" min="1" />
                <?php esc_html_e('Days', 'coupons-after-order'); ?>
            </div>
            <div id="coupon-validity-date-div" class="coupon-field-group">
                <label for="coupon-validity-date" style="display: none;"><?php esc_html_e('Coupon Validity Date:', 'coupons-after-order'); ?></label>
                <input type="date" id="coupon-validity-date" name="wccao_coupons_after_order_validitydate" value="<?php echo esc_attr($validitydate); ?>" min="<?php echo esc_attr(date_i18n('Y-m-d')); ?>" />
            </div>
        <?php
        }

        public function wccao_others_parameters_callback()
        {
            $couponDetails = wccao_generate_coupon_details(0);

            $nber_coupons = absint($couponDetails['nber_coupons']);
            $limitUsage = absint($couponDetails['limitUsage']);
            $indivUseCoupon = $couponDetails['indivUseCoupon'];
            $min_amount = $couponDetails['min_amount'];
            $emaillUseLimit = $couponDetails['email_restriction'];
            $decimal_separator = wc_get_price_decimal_separator();
            $coupon_prefix = sanitize_text_field($couponDetails['coupon_prefix']);
            $coupon_url_parameter = sanitize_text_field(get_option('wccao_coupons_after_order_url_parameter'));
        ?>
            <div class="coupon-field-group">
                <label for="coupons-after-order-count"><?php esc_html_e('Number of Coupons Generated:', 'coupons-after-order') ?></label>
                <input type="number" id="coupons-after-order-count" name="wccao_coupons_after_order_count" value="<?php echo esc_attr($nber_coupons); ?>" step="1" min="1" required />
            </div>
            <div class="coupon-field-group">
                <label for="coupon-validity-usage-limit"><?php esc_html_e('Limit Usage of Coupons Generated:', 'coupons-after-order') ?></label>
                <input type="number" id="coupon-validity-usage-limit" name="wccao_coupons_after_order_usage_limit" value="<?php echo esc_attr($limitUsage); ?>" step="1" min="1" required />
                <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('How many times this coupon can be used before it is void.', 'coupons-after-order') ?>"></span>
            </div>
            <div class="coupon-field-group">
                <label for="coupon_individual_use"><?php esc_html_e('Individual use only:', 'coupons-after-order') ?></label>
                <input type="checkbox" id="coupon_individual_use" name="wccao_coupons_after_order_individual_use" <?php checked($indivUseCoupon, true); ?> value="yes" />
                <span class="wccao-input-description"><?php esc_html_e('Check this box if the promo code cannot be used in conjunction with other promo codes.', 'coupons-after-order') ?></span>
            </div>
            <div class="coupon-field-group">
                <label for="coupon-amount-min"><?php esc_html_e('Minimum amount:', 'coupons-after-order') ?></label>
                <input type="text" id="coupon-amount-min" name="wccao_coupons_after_order_min_amount" value="<?php echo esc_attr($min_amount); ?>" class="wccao_input_price" data-decimal="<?php echo esc_attr($decimal_separator); ?>" placeholder="<?php echo esc_html__('No minimum', 'coupons-after-order'); ?>" />&nbsp;<?php echo esc_html(get_woocommerce_currency_symbol()); ?>
                <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If empty, it is the amount of the individual coupon.', 'coupons-after-order'); ?>"></span>
            </div>
            <div class="coupon-field-group">
                <label for="coupon_email_restriction"><?php esc_html_e('Limit to the buyer email:', 'coupons-after-order') ?></label>
                <input type="checkbox" id="coupon_email_restriction" name="wccao_coupons_after_order_email_restriction" <?php checked($emaillUseLimit, 'yes'); ?> value="yes" />
                <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If checked, only the order billing email address will be able to benefit from the coupons generated', 'coupons-after-order'); ?>"></span>
            </div>
            <div class="coupon-field-group">
                <label for="coupon-prefix"><?php esc_html_e('Coupon prefix:', 'coupons-after-order') ?></label>
                <input type="text" id="coupon-prefix" name="wccao_coupons_after_order_prefix" value="<?php echo esc_attr($coupon_prefix); ?>" pattern="[a-z]+" title="<?php echo esc_html('Only lowercase characters, no numbers', 'coupons-after-order') ?>" placeholder="<?php echo esc_html__('"ref" by default', 'coupons-after-order'); ?>" />
                <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If empty, by default, it is "ref" and the code is in this form "refOrderID-RandomNumber"', 'coupons-after-order'); ?>"></span>
            </div>
            <div class="coupon-field-group">
                <label for="coupon_url_parameter"><?php esc_html_e('URL Parameter link:', 'coupons-after-order') ?></label>
                <input type="text" id="coupon_url_parameter" name="wccao_coupons_after_order_url_parameter" value="<?php echo esc_attr($coupon_url_parameter); ?>" pattern="[a-z]+" title="<?php echo esc_html('Only lowercase characters, no numbers', 'coupons-after-order') ?>" placeholder="<?php echo esc_html__('"apply_coupon" by default', 'coupons-after-order'); ?>" />
                <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('If empty, by default, it is "apply_coupon"', 'coupons-after-order'); ?>"></span>
            </div>
        <?php
        }

        public function wccao_email_config_callback()
        {
            $email_subject = get_option('wccao_coupons_after_order_email_subject');
            $email_header = get_option('wccao_coupons_after_order_email_header');
            $couponDetails = wccao_generate_coupon_details(0);
            $coupon_prefix = sanitize_text_field($couponDetails['coupon_prefix']);
        ?>
            <div class="coupon-field-group">
                <label for="email_subject" style="display: flex; align-items: center;"><?php esc_html_e('Email subject:', 'coupons-after-order') ?>
                    <input type="text" id="email_subject" name="wccao_coupons_after_order_email_subject" value="<?php echo esc_attr($email_subject); ?>" style="flex:auto;" />
                </label>
            </div>
            <div class="coupon-field-group">
                <label for="email_header" style="display: flex; align-items: center;"><?php esc_html_e('Email header:', 'coupons-after-order') ?>
                    <input type="text" id="email_header" name="wccao_coupons_after_order_email_header" value="<?php echo esc_attr($email_header); ?>" style="flex:auto;" />
                </label>
            </div>
            <input type="hidden" id="hidden-coupon-prefix" name="hidden_coupon_prefix" value="<?php echo esc_attr($coupon_prefix); ?>" />
        <?php
        }

        private function wccao_html_email_content()
        {
            $html_content = get_option('wccao_coupons_after_order_email_content');
            return $html_content;
        }

        /**
         * Callback function to display email configuration options.
         */
        public function wccao_email_callback()
        {
            $option_name = 'wccao_coupons_after_order_email_content';
            $default_content = $this->wccao_html_email_content();

            $options = get_option($option_name);
            $content = !empty($options) ? $options : $default_content;

            $settings = array(
                'media_buttons' => false,
                'textarea_rows' => 20,
                'textarea_name' => $option_name,
                'default_editor' => 'tinymce',
                'tinymce' => true,
                'quicktags' => true,
            );

            wp_editor($content, 'wccao_email_content', $settings);
        }

        public function wccao_email_button_callback()
        {
            $email_bt_title = get_option('wccao_coupons_after_order_email_bt_title');
            $email_bt_url = get_option('wccao_coupons_after_order_email_bt_url');
            $email_bt_color = get_option('wccao_coupons_after_order_email_bt_color');
            $email_bt_bg_color = get_option('wccao_coupons_after_order_email_bt_bg_color');
            $email_bt_font_size = get_option('wccao_coupons_after_order_email_bt_font_size');
        ?>
            <div class="bt-preview"><a id="emailButton" href="#" target="_blank" style="text-decoration:none;display:inline-block;padding:10px 30px;margin:10px 0;"></a></div>
            <div class="coupon-field-group">
                <label for="wccao_email_bt_title"><?php esc_html_e('Button title:', 'coupons-after-order') ?>
                    <input type="text" id="wccao_email_bt_title" name="wccao_coupons_after_order_email_bt_title" value="<?php echo esc_attr($email_bt_title); ?>" />
                </label>
            </div>
            <div class="coupon-field-group">
                <label for="wccao_email_bt_url"><?php esc_html_e('Button URL:', 'coupons-after-order') ?>
                    <input type="url" id="wccao_email_bt_url" name="wccao_coupons_after_order_email_bt_url" value="<?php echo esc_attr($email_bt_url); ?>" />
                </label>
            </div>
            <div class="coupon-field-group">
                <label for="wccao_email_bt_color"><?php esc_html_e('Button color:', 'coupons-after-order') ?>
                    <input type="color" id="wccao_email_bt_color" name="wccao_coupons_after_order_email_bt_color" value="<?php echo esc_attr($email_bt_color); ?>" />
                </label>
            </div>
            <div class="coupon-field-group">
                <label for="wccao_email_bt_bg_color"><?php esc_html_e('Button background color:', 'coupons-after-order') ?>
                    <input type="color" id="wccao_email_bt_bg_color" name="wccao_coupons_after_order_email_bt_bg_color" value="<?php echo esc_attr($email_bt_bg_color); ?>" />
                </label>
            </div>
            <div class="coupon-field-group">
                <label for="wccao_email_bt_font_size"><?php esc_html_e('Button font size (px):', 'coupons-after-order') ?>
                    <input type="number" id="wccao_email_bt_font_size" name="wccao_coupons_after_order_email_bt_font_size" value="<?php echo esc_attr($email_bt_font_size); ?>" min="1" />
                </label>
            </div>
        <?php
        }

        public function wccao_coupon_design_callback()
        {
            $coupon_font_color = get_option('wccao_coupons_after_order_coupon_font_color');
            $coupon_bg_color = get_option('wccao_coupons_after_order_coupon_bg_color');
        ?>
            <div class="coupon-field-group">
                <label for="wccao_coupon_font_color"><?php esc_html_e('Coupon font color:', 'coupons-after-order') ?>
                    <input type="color" id="wccao_coupon_font_color" name="wccao_coupons_after_order_coupon_font_color" value="<?php echo esc_attr($coupon_font_color); ?>" />
                </label>
            </div>
            <div class="coupon-field-group">
                <label for="wccao_email_bt_bg_color"><?php esc_html_e('Coupon background color:', 'coupons-after-order') ?>
                    <input type="color" id="wccao_email_bt_bg_color" name="wccao_coupons_after_order_coupon_bg_color" value="<?php echo esc_attr($coupon_bg_color); ?>" />
                </label>
            </div>
        <?php
        }

        public function wccao_miscellaneous_data_uninstall_callback()
        {
            $data_uninstall = get_option('wccao_coupons_after_order_data_uninstall', 'no');
        ?>
            <div class="coupon-field-group">
                <label for="wccao_coupons_after_order_data_uninstall">
                    <input type="checkbox" id="wccao_coupons_after_order_data_uninstall" name="wccao_coupons_after_order_data_uninstall" <?php checked($data_uninstall, 'yes'); ?> value="yes" />
                    <?php esc_html_e('Check this box if you would like to completely remove all of its data upon plugin deletion.', 'coupons-after-order'); ?>
                </label>
            </div>
        <?php
        }

        public function wccao_miscellaneous_emails_and_amounts_callback()
        {
        ?>
            <div class="coupon-field-group" style="display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1em;">
                <label for="wccao_coupons_after_order_emails_and_amounts">
                    <textarea id="wccao_coupons_after_order_emails_and_amounts" name="wccao_coupons_after_order_emails_and_amounts" placeholder="mon-email@gmail.om;45.5" rows="4" cols="50"></textarea>
                    <span class="woocommerce-help-tip" tabindex="0" aria-label="<?php esc_attr_e('Enter the information as follows: "email;order_amount". The email and the amount must be separated by ";" and the decimal separator is "."', 'coupons-after-order'); ?>"></span>

                </label>
                <a id="wccao_generate_manually_link" href="#" class="button wccao-email-test-link"><?php esc_html_e('Generate and send', 'coupons-after-order') ?></a>
                <span id="wccao-email-message-notice" class="email-message-send" style="display:none;"></span>
            </div>
        <?php
        }
    }
endif;