<?php
if (!defined('ABSPATH')) exit;

$email_header = get_option('coupons_after_order_email_header');
$startDate = date('j F Y');
$endDate = date("j F Y", strtotime($validity));
$min_order = floatval($min_order);

$content_before = wpautop(get_option('coupons_after_order_before_email'));
$content_after = wpautop(get_option('coupons_after_order_after_email'));
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color: <?= get_option('woocommerce_email_background_color'); ?>; padding: 0; text-align: center;">
		<table width="100%" id="outer_wrapper" style="background-color: <?= get_option('woocommerce_email_background_color'); ?>;">
			<tr>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				<td width="600">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="margin: 0 auto; padding: 70px 0; width: 100%; max-width: 600px; -webkit-text-size-adjust: none;" width="100%">
						<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
							<tr>
								<td align="center" valign="top">
									<div id="template_header_image">
                                    <?php
										$img = get_option( 'woocommerce_email_header_image' );

										if ( $img ) {
											echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '" /></p>';
										}
										?>
                                    </div>
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="background-color: #fff; border: 1px solid #dedede; box-shadow: 0 1px 4px rgba(0,0,0,.1); border-radius: 3px;">
										<tr>
											<td align="center" valign="top">
												<!-- Header -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style='background-color: <?= get_option('woocommerce_email_base_color'); ?>; color: #fff; border-bottom: 0; font-weight: bold; line-height: 100%; vertical-align: middle; font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif; border-radius: 3px 3px 0 0;'>
													<tr>
														<td id="header_wrapper" style="padding: 36px 48px; display: block;">
															<h1 style='font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: left; text-shadow: 0 1px 0 #9976c2; color: #fff; background-color: inherit;'><?php echo $email_header ; ?></h1>
														</td>
													</tr>
												</table>
												<!-- End Header -->
											</td>
										</tr>
										<tr>
											<td align="center" valign="top">
												<!-- Body -->
                                                <div id="body_content_inner" style='color: #636363; font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif; font-size: 14px; line-height: 150%; text-align: left; padding: 48px 48px 32px;'>
                                                    <p style="margin: 0 0 16px;"><?php printf( esc_html__( 'Hello %s,', 'coupons-after-order' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
                                                    <p style="margin: 0 0 16px;"><?= $content_before ?></p>
                                                    <p style="margin: 0 0 16px;"><?php printf( esc_html__('As promised, we are sending you your promo codes corresponding to our full refund offer. You spent %1$s on your last purchase, entitling you to %2$s promo codes, each worth %3$s.', 'coupons-after-order'), wc_price( $order_total ), $nber_coupons, wc_price($coupon_amount) ); ?></p>
													<p style="margin: 0 0 16px;"><?php printf( esc_html__('Each promo code is valid for a minimum cart value of %s.', 'coupons-after-order'), wc_price($min_order) ); ?></p>
													<p style="margin: 0 0 16px;"><?php _e('Here are your promo codes:', 'coupons-after-order'); ?></p>
                                                    <ul style="list-style-type: disc; margin-left: 20px;">
                                                        <?php echo $coupon_list;?>
                                                    </ul>
													<p style="margin: 0 0 16px;"><?php _e('To use these promo codes on your next purchase, simply follow these steps:', 'coupons-after-order'); ?></p>
													<ul style="list-style-type: disc; margin-left: 20px;">
                                                        <li><?php _e('Add the items of your choice to your cart.', 'coupons-after-order'); ?></li>
														<li><?php _e('During the payment process, enter one of these promo codes in the corresponding "Promo Code" box.', 'coupons-after-order'); ?></li>
														<li><?php printf( esc_html__('The discount of %s will be automatically applied to your order.', 'coupons-after-order'), wc_price($coupon_amount) ); ?></li>
														<li><?php printf( esc_html__('Please note that these promo codes are valid from %1$s until %2$s and cannot be combined in a single order.', 'coupons-after-order'), $startDate, $endDate ); ?></li>
                                                    </ul>
													<p style="margin: 0 0 16px;"><?= $content_after ?></p>
                                                </div>    
                                            </td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
				</td>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
			</tr>
		</table>
	</body>
</html>