<?php
/**
 * Admin View: Email Template Preview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div id="template">
	<div class="template template_html">
		<h4><?php _e('Email Model', 'coupons-after-order') ?></h4>
		<p><?php _e('You can view the email template that will be sent to the buyer:', 'coupons-after-order') ?> <a id="toggleEditorLink" href="#" class="button toggle_editor"><?php _e('Show email template', 'coupons-after-order') ?></a></p>
		<p><?php _e('View directly below the changes you made in the text editors', 'coupons-after-order') ?></p>
        <div class="wccao-editor-email" style="display: none;">
			<div class="code" readonly="readonly" disabled="disabled" cols="25" rows="20">
                <div id="preview_email_content"></div>
            </div>
		</div>
	</div>
</div>