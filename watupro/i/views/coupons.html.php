<div class="wrap">
	<h1><?php _e('Manage Coupon Codes', 'watupro');?></h1>
	
	<form method="post">
		<p><?php _e('Coupon code:', 'watupro');?> <input type="text" name="code"> <?php _e('Discount', 'watupro');?> <select name="disc_type">
			<option value="percent">%</option>
			<option value="fixed"><?php echo $currency;?></option>
		</select> <input type="text" name="discount" size="4">
			<?php _e('Max. uses (enter 0 for unlimited)', 'watupro')?> <input type="text" name="num_uses" size="4"> 
			<br>
			<?php _e('Valid for quiz:', 'watupro');?> <select name="quiz_id">
				<option value="0"><?php _e('Any quiz', 'watupro');?></option>
				<?php foreach($quizzes as $quiz):?>
					<option value="<?php echo $quiz->ID?>"><?php echo stripslashes($quiz->name);?></option>
				<?php endforeach;?>
			</select>
			<input type="checkbox" name="date_condition" value="1" onclick="this.checked ? jQuery('#dateCondition').show() : jQuery('#dateCondition').hide()"> <?php _e('Apply date condition', 'watupro');?>
			<span id="dateCondition" style="display:none;">
				<input type="text" class="watuproDatePicker" id="watuproDateStart" value="<?php echo date_i18n($dateformat, strtotime($start_date))?>">
				<input type="text" class="watuproDatePicker" id="watuproDateEnd" value="<?php echo date_i18n($dateformat, strtotime($end_date))?>">
			</span>			
			<input type="submit" name="add" value="<?php _e('Add Coupon', 'watupro')?>"></p>
			<?php wp_nonce_field('watupro_coupons');?>
			<input type="hidden" name="start_date" value="<?php echo $start_date?>" id="alt_watuproDateStart">
			<input type="hidden" name="end_date" value="<?php echo $end_date?>" id="alt_watuproDateEnd">
	</form>
	
	<?php foreach($coupons as $coupon):?>
	<hr />
	<form method="post">
		<p><?php _e('Coupon code:', 'watupro');?> <input type="text" name="code" value="<?php echo stripslashes($coupon->code);?>"> <?php _e('Discount', 'watupro');?> 
		<select name="disc_type">
			<option value="percent" <?php if($coupon->disc_type == 'percent') echo 'selected'?>>%</option>
			<option value="fixed" <?php if($coupon->disc_type == 'fixed') echo 'selected'?>><?php echo $currency;?></option>
		</select>
		<input type="text" name="discount" size="4" value="<?php echo $coupon->discount?>">
			<?php _e('Max. uses (enter 0 for unlimited)', 'watupro')?> <input type="text" name="num_uses" size="4" value="<?php echo $coupon->num_uses;?>"> 
			<br />
			<?php _e('Valid for quiz:', 'watupro');?> <select name="quiz_id">
				<option value="0"><?php _e('Any quiz', 'watupro');?></option>
				<?php foreach($quizzes as $quiz):?>
					<option value="<?php echo $quiz->ID?>" <?php if($coupon->quiz_id == $quiz->ID) echo "selected"?>><?php echo stripslashes($quiz->name);?></option>
				<?php endforeach;?>
			</select>
			<input type="checkbox" name="date_condition" value="1" <?php if(!empty($coupon->date_condition)) echo "checked";?> onclick="this.checked ? jQuery('#dateCondition<?php echo $coupon->ID?>').show() : jQuery('#dateCondition<?php echo $coupon->ID?>').hide()"> <?php _e('Apply date condition', 'watupro');?>
			<span id="dateCondition<?php echo $coupon->ID?>" style="display:<?php echo empty($coupon->date_condition) ? 'none' : 'inline'?>;">
				<input type="text" class="watuproDatePicker" id="watuproDateStart<?php echo $coupon->ID?>" value="<?php echo date_i18n($dateformat, strtotime(@$coupon->start_date))?>">
				<input type="text" class="watuproDatePicker" id="watuproDateEnd<?php echo $coupon->ID?>" value="<?php echo date_i18n($dateformat, strtotime(@$coupon->end_date))?>">
			</span>			
			<input type="submit" name="save" value="<?php _e('Save Coupon', 'watupro')?>"> <input type="button" value="<?php _e('Delete', 'watupro')?>" onclick="confirmDelCoupon(this.form);"> <br>
		<i><?php printf(__('Used %d times', '  watupro'), $coupon->times_used);?></i></p>
			<input type="hidden" name="id" value="<?php echo $coupon->ID?>">
			<input type="hidden" name="del" value="0">
			<?php wp_nonce_field('watupro_coupons');?>
			<input type="hidden" name="start_date" value="<?php echo @$coupon->start_date?>" id="alt_watuproDateStart<?php echo $coupon->ID?>">
			<input type="hidden" name="end_date" value="<?php echo @$coupon->end_date?>" id="alt_watuproDateEnd<?php echo $coupon->ID?>">
	</form>
	<?php endforeach;?>
	
	<h2><?php _e('Using Coupons for Quiz Bundles', 'watupro');?></h2>
	
	<p><?php printf(__('To include the coupon code field on page selling quiz bundles use the shortcode %s. The shortcode accepts two optional attributes - "%s" (to control the field label) and "%s" (to control the value of the submit button). So for example you can use it like this: %s.', 'watupro'), '<input type="text" size="20" onclick="this.select();" readonly="readonly" value="[watupro-coupon-field]">', 'label', 'button_text', '[watupro-coupon-field label="Use discount coupon" button_text="Apply coupon"]')?> </p>
</div>

<script type="text/javascript" >
function confirmDelCoupon(frm) {
	if(confirm("<?php _e('Are you sure?', 'watupro')?>")) {
		frm.del.value=1;
		frm.submit();
	}
}

jQuery(document).ready(function() {
	jQuery('.watuproDatePicker').datepicker({
        dateFormat : '<?php echo dateformat_PHP_to_jQueryUI($dateformat);?>',
        altFormat: 'yy-mm-dd',
    });
    
    jQuery(".watuproDatePicker").each(function (idx, el) { 
	    jQuery(this).datepicker("option", "altField", "#alt_" + jQuery(this).attr("id"));
	});
});	
</script>