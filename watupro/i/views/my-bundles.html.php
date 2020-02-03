<?php if(!$in_shortcode):?>
<div class="wrap">
	<h1><?php printf(__('My %s Bundles', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD));?></h1>
<?php endif;?>
	<?php if(count($bundles)):?>
	<table class="watupro-table widefat">
		<thead>
			<tr><th><?php _e('Bundle', 'watupro');?></th><th><?php _e('Access to', 'watupro');?></th>
			<th><?php _e('Date purchased', 'watupro');?></th><th><?php _e('Expiration', 'watupro');?></th></tr>
			<?php foreach($bundles as $bundle):
				$class = ('alternate' == @$class) ? '' : 'alternate';?>
				<tr class="<?php echo $class?>">
					 <td><?php echo stripslashes($bundle->name);?></td>
					 <td><?php if($bundle->bundle_type == 'quizzes') printf(__('Selected %s: %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, stripslashes($bundle->quizzes)); 
						if($bundle->bundle_type == 'category') printf(__('%s categories: %s', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD), stripslashes($bundle->cat)); 
						if($bundle->bundle_type == 'num_quizzes'):
							printf(__('%d %s', 'watupro'), $bundle->num_quizzes, WATUPRO_QUIZ_WORD_PLURAL);
							echo '<br>';
							printf(__('(%d used)','watupro'), $bundle->num_quizzes_used);
						endif;
						?></td>
					 <td><?php echo date_i18n($dateformat, strtotime($bundle->payment_date));?></td>
					 <td><?php if($bundle->is_time_limited):
					 	$expiration_time = strtotime($bundle->payment_date) + ($bundle->time_limit*24*3600);
					 	echo date_i18n($dateformat, $expiration_time);
					 else: _e('No expiration', 'watipro');
					 endif;?></td>	
				</tr>
			<?php endforeach;?>
		</thead>
		<tbody></tbody>
	</table>	
	<?php else:?>
	<p><?php _e('There are no purchased bundles at the moment.', 'watupro');?></p>
<?php endif; // end if no bundles 
	if(!$in_shortcode):?>	
</div>
<?php endif;?>