<?php
if (!defined('ABSPATH')) exit;

$first_name = isset($order) ? $order->get_billing_first_name() : __('Dear customer', 'coupons-after-order');
$email_customer = isset($order) ? $order->get_billing_email() : $customer_email;

$email_header = get_option('coupons_after_order_email_header');
$startDate = date_i18n('j F Y');
$endDate = date_i18n("j F Y", strtotime($couponDetails['validity']));
$min_order = floatval($couponDetails['min_order']);
$content_email = wpautop(get_option('coupons_after_order_email_content'));

$coupons = $coupon_list;
$coupon_amount = wc_price( $couponDetails['coupon_amount'] );
$order_total = wc_price( $couponDetails['order_total'] );
$nb_coupons = esc_html( $couponDetails['nber_coupons'] );

// Button
$email_bt_title = isset( $_GET['coupons_after_order_email_bt_title'] ) ? wp_unslash( $_GET['coupons_after_order_email_bt_title'] ) : get_option('coupons_after_order_email_bt_title');
$email_bt_url = isset( $_GET['coupons_after_order_email_bt_url'] ) ? wp_unslash( $_GET['coupons_after_order_email_bt_url'] ) : get_option('coupons_after_order_email_bt_url');
$email_bt_color = isset( $_GET['coupons_after_order_email_bt_color'] ) ? wp_unslash( $_GET['coupons_after_order_email_bt_color'] ) : get_option('coupons_after_order_email_bt_color');
$email_bt_bg_color = isset( $_GET['coupons_after_order_email_bt_bg_color'] ) ? wp_unslash( $_GET['coupons_after_order_email_bt_bg_color'] ) : get_option('coupons_after_order_email_bt_bg_color');
$email_bt_font_size = isset( $_GET['coupons_after_order_email_bt_font_size'] ) ? wp_unslash( $_GET['coupons_after_order_email_bt_font_size'] ) : get_option('coupons_after_order_email_bt_font_size');
$wccao_bt = '<a href="' . $email_bt_url . '" target="_blank" style="text-decoration:none;display:inline-block;padding:10px 30px;margin:10px 0;font-size:' . $email_bt_font_size . 'px;color:' . $email_bt_color . ';background:' . $email_bt_bg_color . ';">' . $email_bt_title . '</a>';

// Shortcodes
$content_email = str_replace( '{billing_first_name}', esc_html($first_name ), $content_email );
$content_email = str_replace( '{billing_email}', esc_html( $email_customer ), $content_email );
$content_email = str_replace( '{coupons}', $coupons, $content_email );
$content_email = str_replace( '{coupon_amount}', $coupon_amount, $content_email );
$content_email = str_replace( '{order_total}', $order_total, $content_email );
$content_email = str_replace( '{nb_coupons}', $nb_coupons, $content_email );
$content_email = str_replace( '{min_amount_order}', wc_price($min_order), $content_email );
$content_email = str_replace( '{start_date}', esc_html($startDate), $content_email );
$content_email = str_replace( '{end_date}', esc_html($endDate) , $content_email );
$content_email = str_replace( '{shop_button}', $wccao_bt, $content_email );
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
		<style>
			.wccao-coupons-list {
			    list-style-type: none;
				padding-left: 0;
				width: 100%;
				columns: 2;
				-webkit-columns: 2;
				-moz-columns: 2;
				column-gap: 1em;
			}
			ul .prefix-coupon {
				padding: 1em;
				-webkit-mask-image: radial-gradient(circle at 10px 35%, transparent 10px, red 10.5px), linear-gradient(90deg, transparent 25%, red 0, red 75%, transparent 0);
				mask-image: radial-gradient(circle at 10px 35%, transparent 10px, red 10.5px), linear-gradient(90deg, transparent 25%, red 0, red 75%, transparent 0);
				-webkit-mask-size: 100%, 8px 2px;
				mask-size: 100%, 8px 2px;
				-webkit-mask-repeat: repeat, repeat-x;
				mask-repeat: repeat, repeat-x;
				-webkit-mask-position: -10px, 50% 34%;
				mask-position: -10px, 50% 34%;
				-webkit-mask-composite: source-out;
				mask-composite: subtract;
				border-radius: 0.5rem;
				background: linear-gradient(#bf4080,#8c5ead);
				color: #fff;
				text-align: center;
				break-inside: avoid-column;
			}
			ul .prefix-coupon:not(:last-child) {margin-bottom:1em;}
			ul .prefix-coupon .email-title-coupon {
				margin-bottom: 0.5em;
				display: block;
			}
			ul .prefix-coupon .email-code-coupon {
				font-size: 1.2em;
				margin-top: 0.5em;
				border: 1px solid #fff;
				border-radius: 5px;
				display: inline-block;
				padding: 0.5em;
				font-weight: bold;
			}
			ul .prefix-coupon .email-code-coupon a {
				color:#fff;
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color: <?= get_option('woocommerce_email_background_color'); ?>; padding: 0; text-align: center;">
		<table width="100%" id="outer_wrapper" style="background-color: <?= get_option('woocommerce_email_background_color'); ?>;">
			<tr>
				<td><!-- Spacer for consistent sizing and layout across email clients. --></td>
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
															<h1 style='font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: left; text-shadow: 0 1px 0 #9976c2; color: #fff; background-color: inherit;'><?php echo esc_attr($email_header) ; ?></h1>
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
                                                    <?php if (!empty($content_email)) : ?>
														<?php echo wp_kses_post($content_email); ?>
													<?php endif; ?>									
                                                </div>    
                                            </td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
				</td>
				<td><!-- Spacer for consistent sizing and layout across email clients.--></td>
			</tr>
		</table>
	</body>
</html>