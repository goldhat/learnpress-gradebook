<div class="watupro-report-poll">
	<div style="clear:both;">
		<div style="float:left;height:20px;background-color:<?php echo $correct_color?>;width:<?php echo $poll['percent']*2?>px;"></div>
		<div style="float:left;">&nbsp; <?php if(!empty($show_user_choice) and $is_correct): echo $show_user_choice; endif;?> 
		<?php printf(__('%d / %d%% correct answers', 'watupro'), $poll['correct'], $poll['percent']);?></div>
	</div>
	
	<div style="clear:both;">
		<div style="float:left;height:20px;background-color:<?php echo $wrong_color?>;width:<?php echo $percent_wrong*2?>px;"></div>
		<div style="float:left;">&nbsp; <?php if(!empty($show_user_choice) and !$is_correct): echo $show_user_choice; endif;?>
		<?php printf(__('%d / %d%% wrong answers', 'watupro'), $num_wrong, $percent_wrong);?></div>
	</div>
</div>