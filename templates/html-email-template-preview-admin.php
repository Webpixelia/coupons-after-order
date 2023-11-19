<?php
/**
 * Admin View: Email Template Preview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$today = new DateTime();
$futureDate = $today->modify('+30 days')->format('j F Y');
?>
<div id="template">
	<div class="template template_html">
		<h4><?php _e('Email Model', 'coupons-after-order') ?></h4>
		<p><?php _e('You can view the email template that will be sent to the buyer:', 'coupons-after-order') ?> <a id="toggleEditorLink" href="#" class="button toggle_editor"><?php _e('Show email template', 'coupons-after-order') ?></a></p>
		<p><?php _e('View directly below the changes you made in the text editors', 'coupons-after-order') ?></p>
        <div class="wccao-editor-email" style="display: none;">
			<div class="code" readonly="readonly" disabled="disabled" cols="25" rows="20">
                <p><?php 
                /* translators: %s: buyer name */
                printf( esc_html__( 'Hello %s,', 'coupons-after-order' ), 'John' ); ?></p>
                <p id="preview_before"></p>
                <p><?php 
                /* translators: %1$s: order amount */
                /* translators: %2$s: number of coupons generated */
                /* translators: %3$s: amount of each coupon */
                printf( esc_html__('To thank you, we are sending you your promo codes corresponding to our full refund offer. You spent %1$s on your last purchase, entitling you to %2$s promo codes, each worth %3$s.', 'coupons-after-order'), '120 €', '4', '30 €' ); ?>          
                <p><p><?php 
                /* translators: %s: minimum cart amount for coupon use */
                printf( esc_html__('Each promo code is valid for a minimum cart value of %s.', 'coupons-after-order'), '60 €'); ?></p>
                <p><?php _e('Here are your promo codes:', 'coupons-after-order'); ?></p>
                <ul class="wccao-list">
                    <li><span class="prefix-coupon"></span>XXX-XXXX</li>
                    <li><span class="prefix-coupon"></span>XXX-XXXX</li>
                    <li><span class="prefix-coupon"></span>XXX-XXXX</li>
                    <li><span class="prefix-coupon"></span>XXX-XXXX</li>
                    <li>......</li>
                </ul>
                </p><?php _e('To use these promo codes on your next purchase, simply follow these steps:', 'coupons-after-order'); ?></p>
                <ul class="wccao-list">
                    <li><?php _e('Add the items of your choice to your cart.', 'coupons-after-order'); ?></li>
                    <li><?php _e('During the payment process, enter one of these promo codes in the corresponding "Promo Code" box.', 'coupons-after-order'); ?></li>
                    <li><?php 
                    /* translators: %s: coupon amount */
                    printf( esc_html__('The discount of %s will be automatically applied to your order.', 'coupons-after-order'), '30 €' ); ?></li>
					<li><?php 
                    /* translators: %1$s: start date of validity of generated coupons */
					/* translators: %2$s: end date of validity of generated coupons */
                    printf( esc_html__('Please note that these promo codes are valid from %1$s until %2$s and cannot be combined in a single order.', 'coupons-after-order'), date_i18n('j F Y'), date_i18n('j F Y', strtotime('+30 days')) ); ?></li>
                </ul>
                <p id="preview_after"></p>
            </div>
		</div>
	</div>
</div>