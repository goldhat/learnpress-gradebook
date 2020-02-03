<div class="wrap">
	<h2><?php printf(__('This %s is currently locked.', 'watupro'), WATUPRO_QUIZ_WORD)?></h2>
	<p><?php echo (@$advanced_settings['dependency_type'] == 'any') ? sprintf(__("To take this %s you need first to complete one of the following dependencies:", 'watupro'), WATUPRO_QUIZ_WORD) : sprintf(__("To take this %s you need first to complete the following dependencies:", 'watupro'), WATUPRO_QUIZ_WORD)?></p>
	
	<table class="widefat" align="center">
		<tr><th><?php _e("Dependency", 'watupro')?></th> <th><?php _e("Status", 'watupro')?></th></tr>
		<?php foreach($dependencies as $dependency):?>
			<tr><td><?php _e('The test', 'watupro')?> <strong><?php echo watupro_exam_url($dependency->depend_exam)?></strong> <?php _e('must be completed', 'watupro');?>
				<strong><?php if(!empty($dependency->mode)): 
					printf(__('with at least %s', 'watupro'), $dependency->depend_points. ' ');
					if($dependency->mode == 'percent'): _e('% correct answers', 'watupro'); 
					elseif($dependency->mode == 'percent_points'): _e('% of max. points', 'watupro');
					else: _e('points', 'watupro');
					endif;
				else: _e('successfully', 'watupro');
				endif;?></strong>. </td>
				<td><?php if($dependency->satisfied):?>
					<img src="<?php echo plugins_url('watupro').'/correct.png'?>">
				<?php else:?>
					<img src="<?php echo plugins_url('watupro').'/wrong.png'?>">
				<?php endif;?></td></tr>
		<?php endforeach;?>
	</table>
</div>