<div class="wrap">
	<?php if(empty($_GET['exam_id']) and empty($_GET['bundle_id'])):?>
		<h1><?php printf(__('View payments made for all %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></h1>		
	<?php endif;?>	

	<?php if(!empty($exam->ID)):?>
		<h1><?php printf(__('View payments made for %s "%s"', 'watupro'), WATUPRO_QUIZ_WORD, stripslashes($exam->name))?></h1>
			
		<p><a href="admin.php?page=watupro_takings&exam_id=<?php echo $exam->ID?>"><?php _e('Back to the record of users who submitted this quiz.', 'watupro')?></a></p>
	<?php endif;
	if(!empty($bundle->ID)):?>
		<h1><?php printf(__('View payments made for "%s"', 'watupro'), stripslashes($bundle_name))?></h1>
		
			<p><a href="admin.php?page=watupro_bundles"><?php _e('Back to manage bundles.', 'watupro')?></a></p>
	<?php endif;?>	
	
	<?php // when viewing payments for quizzes, allow a dropdown for easy access
	if(empty($bundle->ID)):?>
		<p><?php _e('Select specific quiz:', 'watupro')?> <select onchange="window.location = 'admin.php?page=watupro_payments&exam_id=' + this.value;">
			 <option value="0"><?php _e('All tests', 'watupro');?></option>
			<?php foreach($paid_exams as $paid):
				$selected = (!empty($_GET['exam_id']) and $_GET['exam_id'] == $paid->ID) ? 'selected' : '';?>
				<option value="<?php echo $paid->ID?>" <?php echo $selected?>><?php echo stripslashes($paid->name);?></option>
			<?php endforeach;?>
		</select></p>
	<?php endif;?>

	<?php if(!empty($_GET['exam_id']) or !empty($_GET['bundle_id'])):?>
		<form method="post" action="#">
		<h2><?php _e('Manually add payment', 'watupro')?></h2>
		<p><?php _e('This will allow the user to access the quiz or bundle like when they paid the fee through the website. You can use it to insert payments made by unsupported payment methods or just to allow someone access the quiz / bundle for free.', 'watupro')?></p>
		<p><?php _e('Username:', 'watupro')?> <input type="text" name="user_login"> <?php _e('Amount paid:', 'watupro')?> <input type="text" size="6" name="amount">
		<input type="submit" name="add_payment" value="<?php _e('Add manual payment', 'watupro')?>"></p>
		</form>
	<?php endif;?>	
	
	<hr>
	
	<?php if(!sizeof($payments)):?>
	<p><?php _e('No payments have been done yet for this item', 'watupro')?></p>
	</div>
	<?php return true;
	endif;?>
	
	<table class="widefat">
		<tr><?php if($see_all_quizzes):?><th><?php _e('Quiz Name', 'watupro');?></th><?php endif;?>
		<th><?php _e('User', 'watupro')?></th><th><?php _e('Date paid', 'watupro')?></th><th><?php _e('Amount', 'watupro')?></th>
		<th><?php _e('Status', 'watupro')?></th><th><?php _e('Payment method', 'watupro')?></th><th><?php _e('Delete', 'watupro')?></th></tr>
		<?php foreach($payments as $payment):
			$class = ('alternate' == @$class) ? '' : 'alternate'; ?>
			<tr class="<?php echo @$class?>">
			<?php if($see_all_quizzes):?><td><?php echo stripslashes($payment->quiz_name);?></td><?php endif;?>
			<td><?php echo $payment->user_id ? $payment->user_login : sprintf(__('Access code %s', 'watupro'), $payment->access_code);?></td><td><?php echo date(get_option('date_format'), strtotime($payment->date))?></td>
			<td><?php if($payment->method == 'points'): echo ($payment->amount * $paypoints_price).' '.__('points');
			else: echo $currency." ".$payment->amount; endif;?></td>
			<td><?php if($payment->status == 'completed'): _e('Completed', 'watupro');?>				
				<a href="#" onclick="WatuPROChangeStatus(0, <?php echo $payment->ID?>);return false;"><?php _e('Make Pending', 'watupro')?></a>
			<?php else: 
					if($payment->status == 'pending'): _e('Pending', 'watupro'); endif;
					if($payment->status == 'used'): _e('Used', 'watupro'); endif;?>
				<a href="#" onclick="WatuPROChangeStatus(1, <?php echo $payment->ID?>);return false;"><?php _e('Complete', 'watupro')?></a>
			<?php endif;?></td>
			<td><?php echo empty($payment->method) ? "Paypal" : $payment->method?></td>
			<td><a href="#" onclick="WatuPRODeletePayment(<?php echo $payment->ID?>);return false;"><?php _e('Delete', 'watupro')?></a></td></tr>
		<?php endforeach;?>	
	</table>
	
	<p align="center">
	<?php if($offset > 0):?>
		<a href="admin.php?page=watupro_payments&<?php echo @$field?>=<?php echo @$item->ID?>&offset=<?php echo $offset - 100?>"><?php _e('previous page', 'watupro');?></a>
	<?php endif;?>
	
	<?php if(($offset + 100) < $count):?>
		<a href="admin.php?page=watupro_payments&<?php echo @$field?>=<?php echo @$item->ID?>&offset=<?php echo $offset + 100?>"><?php _e('next page', 'watupro');?></a>
	<?php endif;?>
	</p>
</div>

<script type="text/javascript" >
function WatuPROChangeStatus(status, id) {
	if(confirm("<?php _e('Are you sure?', 'watupro')?>")) {
		window.location="admin.php?page=watupro_payments&<?php echo @$field?>=<?php echo @$item->ID?>&offset=<?php echo $offset?>&change_status=1&status="+status+"&id="+id;
	}
}

function WatuPRODeletePayment(id) {
	if(confirm("<?php _e('Are you sure?', 'watupro')?>")) {
		window.location="admin.php?page=watupro_payments&<?php echo @$field?>=<?php echo @$item->ID?>&offset=<?php echo $offset?>&delete=1&id="+id;
	}
}
</script>