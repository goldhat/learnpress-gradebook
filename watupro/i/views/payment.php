<?php if(!empty($advanced_settings['payment_instructions'])):
	echo apply_filters('watupro_content', stripslashes(rawurldecode($advanced_settings['payment_instructions'])));
endif;?>

<?php if(empty($coupon_applied)):
	if(empty($advanced_settings['payment_instructions'])):
	$item_word = empty($is_certificate) ? WATUPRO_QUIZ_WORD : __('certificate', 'watupro');?>
	<p align="center"><strong><?php printf(__("There is a fee of %s %s to access this %s.", 'watupro'), $currency, $exam->fee, $item_word)?></strong></p>
<?php endif; // end if no quiz-specific payment instructions 
	else: // copon applied?>
	<p align="center"><strong><?php printf(__("%s discount applied! <s>%s %s</s> %s %s!", 'watupro'), ($coupon->disc_type == 'fixed' ? $currency . ' ' .$coupon->discount : $coupon->discount . '%'), $currency, $old_fee, $currency, $exam->fee)?></strong></p>
<?php endif;?>	

<?php if($any_coupons):?>
	<form method="post" action="#" class="watupro-coupon-form">
		<p align="center"><?php _e('Coupon code:', 'watupro');?> <input type="text" name="watupro_coupon" value="<?php echo @$_POST['watupro_coupon']?>"> <input type="submit" value="<?php _e('Apply', 'watupro')?>"></p>
	</form>
<?php endif;?>

<?php if($paypal_email): 
	$paypal_host = "www.paypal.com";
		$paypal_sandbox = get_option('watupro_paypal_sandbox');
		if($paypal_sandbox == '1') $paypal_host = 'www.sandbox.paypal.com';// generate Paypal button
		$paypal_button = get_option('watupro_paypal_button');
		if(empty($paypal_button)) $paypal_button = 'https://www.paypal.com/en_US/i/btn/x-click-butcc.gif';
		$watupro_is_certificate = empty($is_certificate) ? 0 : 1;
		$return_base_url = empty($is_certificate) ? get_permalink($post->ID) : site_url("?watupro_view_certificate=1&taking_id=".intval($_GET['taking_id'])."&id=".intval($_GET['id']));?>
	<form action="https://<?php echo $paypal_host?>/cgi-bin/webscr" method="post">
	<p align="center">
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="<?php echo $paypal_email?>">
		<input type="hidden" name="item_name" value="<?php echo ucfirst(WATUPRO_QUIZ_WORD).' '.$exam->name?>">
		<input type="hidden" name="item_number" value="<?php echo $exam->ID?>">
		<input type="hidden" name="amount" value="<?php echo number_format($exam->fee,2,".","")?>">
		<input type="hidden" name="return" value="<?php echo (get_option('watupro_use_pdt') == 1) ? esc_url(add_query_arg(array('watupro_pdt' => 1, 'stamp'=>time(), 'watupro_is_certificate' => $watupro_is_certificate), $return_base_url )) : esc_url(add_query_arg(array('stamp'=>time(), 'watupro_is_certificate' => $watupro_is_certificate), $return_base_url));?>">
		<?php if(get_option('watupro_use_pdt') != 1):
			if(empty($is_certificate)):?><input type="hidden" name="notify_url" value="<?php echo $user_ID ? site_url('?watupro=paypal&user_id='.$user_ID, 'https') : site_url('?watupro=paypal&access_code='.$access_code, 'https');?>"><?php
			else: // vars for certificate ?><input type="hidden" name="notify_url" value="<?php echo $user_ID ? site_url('?watupro=paypal_certificate&user_id='.$user_ID, 'https') : site_url('?watupro=paypal_certificate&access_code='.$access_code, 'https');?>"><?php endif; // end if certificate 
			endif;?>
		<input type="hidden" name="no_shipping" value="1">
		<input type="hidden" name="no_note" value="1">
		<input type="hidden" name="currency_code" value="<?php echo $currency;?>">
		<input type="hidden" name="lc" value="US">
		<input type="hidden" name="bn" value="PP-BuyNowBF">
		<input type="image" src="<?php echo $paypal_button?>" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
	</p>
	</form> 
<?php endif;
if($accept_stripe and !empty($stripe['secret_key'])): // generate stripe button?>
	<form method="post">
		<p align="center">
	  <script src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
	          data-key="<?php echo $stripe['publishable_key']; ?>"
	          data-amount="<?php echo $exam->fee*100?>" data-description="<?php echo __('Exam', 'watupro').' '.$exam->name?>" data-currency="<?php echo $currency?>"></script>
	<input type="hidden" name="stripe_pay" value="1">
	<input type="hidden" name="exam_id" value="<?php echo $exam->ID?>">
	</p>
	</form>
<?php endif;

if($accept_moolamojo) :
	if(is_user_logged_in()): echo $moola_button; 
	else: echo '<p>'.__('You can pay with your virtual credits balance but you must be logged in.', 'watupro').'</p>'; endif;
endif;

if($accept_paypoints and !empty($user_ID)): echo $paypoints_button; endif;

do_action('watupro-display-payment-page', $exam->ID, $is_certificate);?>

<?php if(!empty($other_payments)): echo "<div align='center'>".wpautop(do_shortcode(stripslashes($other_payments)))."</div>"; endif;?>