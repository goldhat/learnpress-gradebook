<style type="text/css">
<?php watupro_resp_table_css(1000);?>
</style>
<div class="wrap">
	<h1><?php printf(__('Manage Paid %s Bundles', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD_PLURAL));?></h1>
	
	<p><?php printf(__('You can create payment buttons for selling access to several paid %s or a whole category of %s at once. Learn more how this works <a href="%s" target="_blank">here</a>.', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, WATUPRO_QUIZ_WORD_PLURAL, 'http://blog.calendarscripts.info/watupro-intelligence-module-paid-quiz-bundles/')?></p>
	
	<p><a href="admin.php?page=watupro_bundles&do=add"><?php _e('Create new bundle', 'watupro')?></a></p>
	
	<?php if(count($bundles)):?>
		<h2><?php _e('Existing bundle buttons', 'watupro')?></h2>
		
		<table class="widefat watupro-table">
			<thead>
			<tr><th><?php _e('ID', 'watupro');?></th><th><?php _e('Name', 'watupro');?></th><th><?php _e('Shortcodes', 'watupro')?></th><th><?php _e('Bundle type', 'watupro')?></th>
			<th><?php _e('Price', 'watupro')?></th>
			<th><?php _e('Gives access to', 'watupro')?></th>
			<th><?php _e('View Payments', 'watupro')?></th><th><?php _e('Edit/delete', 'watupro')?></th></tr>
			</thead>
			<tbody>
			<?php foreach($bundles as $bundle):
				$class = ('alternate' == @$class) ? '' : 'alternate';?>
				<tr class="<?php echo $class?>">
				   <td><?php echo $bundle->ID?></td>
				   <td><?php echo stripslashes($bundle->name);?></td>
					<td><?php _e('Paypal button:', 'watupro')?> <input type="text" value='[watupro-quiz-bundle mode="paypal" id="<?php echo $bundle->ID?>"]' onclick="this.select();" readonly="readonly" size="40">
					<?php if($accept_stripe):?>
						<br><?php _e('Stripe button:', 'watupro')?> <input type="text" value='[watupro-quiz-bundle mode="stripe" id="<?php echo $bundle->ID?>"]' onclick="this.select();" readonly="readonly" size="40">
					<?php endif;?>
					<?php if($accept_points):?>
						<br><?php _e('Pay with points:', 'watupro')?> <input type="text" value='[watupro-quiz-bundle mode="paypoints" id="<?php echo $bundle->ID?>"]' onclick="this.select();" readonly="readonly" size="40">
					<?php endif;?>
					<?php if(!empty($other_payments)):?>
						<br><?php _e('Other payment methods:', 'watupro')?> <input type="text" value='[watupro-quiz-bundle mode="custom" id="<?php echo $bundle->ID?>"]' onclick="this.select();" readonly="readonly" size="40">
					<?php endif;?></td>
					<td><?php if($bundle->bundle_type == 'quizzes') printf(__('Selected %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL); 
						if($bundle->bundle_type == 'category') printf(__('%s categories', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD));
						if($bundle->bundle_type == 'num_quizzes') printf(__('Number of %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></td>
					<td><?php printf(__('%s %s', 'watupro'), $currency, $bundle->price);?></td>	
					<td><?php if($bundle->bundle_type == 'quizzes') echo stripslashes($bundle->quizzes); 
					if($bundle->bundle_type == 'category') echo stripslashes($bundle->cat);
					if($bundle->bundle_type == 'num_quizzes') printf(__('%d %s', 'watupro'), $bundle->num_quizzes, WATUPRO_QUIZ_WORD_PLURAL);
					if($bundle->is_time_limited) printf('<br>'.__('For %d days', 'watupro'), $bundle->time_limit);?> </td>
					<td><a href="admin.php?page=watupro_payments&bundle_id=<?php echo $bundle->ID?>"><?php _e('View/Manage', 'watupro');?></a></td>					
					<td><a href="admin.php?page=watupro_bundles&do=edit&id=<?php echo $bundle->ID?>"><?php _e('Edit', 'watupro')?></a>
					| <a href="#" onclick="WatuPROconfirmDelBundle(<?php echo $bundle->ID?>);return false;"><?php _e('Delete', 'watupro')?></a></td>	
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
	<?php endif;?>
</div>

<p><?php _e("Each of the above shortcodes supports the following attributes:", 'watupro');?></p>

<ul>
	<li><b>center</b> - <?php _e('To have the payment button centered inside the page or the area (div, table cell) where you have placed it. This attribute does not work for "Other payment methods" info because any formatting is expected to be done directly in the payment settings box.', 'watupro');?></li>
	<li><b>info</b> - <?php printf(__('To display text above the button, for example text like "Pay $10 by PayPal". Use the variable %s to have the price dynamically displayed and changed when a coupon code is applied.', 'watupro'), '{{{cost}}}');?></li>
	<li><b>info_p</b> - <?php _e('Set this attribute to 1 to have the info text placed inside a paragraph ("p" tag).', 'watupro');?></li>
</ul>

<p><?php printf(__('Example usage: %s', 'watupro'), '[watupro-quiz-bundle mode="paypal" id="1" info="Pay USD {{{cost}}} by PayPal" info_p="1"]');?></p>

<form method="post">
<p><input type="checkbox" name="enable_my_bundles" <?php if(!empty($enable_my_bundles)) echo 'checked'?> value="1" onclick="this.form.submit();"> <?php printf(__('Enable "My %s bundles" page where users will see their purchased bundles along with date of purchase, %s they give access to and expiring date if available.', 'watupro'), WATUPRO_QUIZ_WORD, WATUPRO_QUIZ_WORD_PLURAL);?></p>

<?php if(!empty($enable_my_bundles)):?>
<p><?php printf(__('Shortcode to publish this page: %s', 'watupro'), '<input type="text" value="[watupro-my-bundles]" onclick="this.select();" readonly="readonly">');?></p>
<?php endif;?>

<?php wp_nonce_field('watupro_bundle_settings');?>
<input type="hidden" name="bundle_settings" value="1">
</form>

<script type="text/javascript" >
function WatuPROconfirmDelBundle(id) {
	if(confirm("<?php _e('Are you sure?', 'watupro');?>")) {
		window.location='admin.php?page=watupro_bundles&del=1&id=' + id;
	}
}
<?php watupro_resp_table_js();?>
</script>