<div class="wrap">
	<?php if(empty($in_shortcode)):?>
		<h1><?php _e('Detailed Question Report', 'watupro')?></h1>
		
		<p><?php _e('Shortcode to display this page:', 'watupro')?> <input type="text" size="30" value="[watupror-question-answers <?php echo $exam->ID?> <?php echo $question->ID?>]" readonly="true" onclick="this.select();"></p>
		
		<p><?php _e('Quiz:', 'watupro')?> <b><?php echo $exam->name?></b></p>
		<p><?php _e('Question:', 'watupro')?> <?php echo apply_filters('watupro_content', stripslashes($question->question))?></p>
		<p><a href="admin.php?page=watupro_question_stats&exam_id=<?php echo $exam->ID?>"><?php _e('Back to all question stats', 'watupro')?></a>
		| <a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=<?php echo $ob?>&dir=<?php echo $dir?>&export=1&noheader=1"><?php _e('Export all records (TAB delimited file)', 'watupro')?></a></p>
	<?php endif;?>
		
	<table class="widefat">
		<?php if(empty($in_shortcode)):?>
	   	<tr><th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=display_name&dir=<?php echo $odir;?>"><?php _e('Username', 'watupro')?></a></th><th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=email&dir=<?php echo $odir;?>"><?php _e('Email', 'watupro')?></a></th><th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=ip&dir=<?php echo $odir;?>"><?php _e("IP", 'watupro')?></a></th><th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=date&dir=<?php echo $odir;?>"><?php _e('Date', 'watupro')?></a></th>
		   <th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=answer&dir=<?php echo $odir;?>"><?php _e('Answer', 'watupro')?></a></th>
		   <th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=points&dir=<?php echo $odir;?>"><?php _e('Points', 'watupro')?></a></th>
		   <th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=is_correct&dir=<?php echo $odir;?>"><?php _e('Is Correct?', 'watupro')?></a></th>
		   <?php if(watupro_intel()):?>
		   	<th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=teacher_comments&dir=<?php echo $odir;?>"><?php _e('Teacher comments', 'watupro')?></a></th>
		   <?php endif;?>
		   <?php if(!empty($exam->question_hints) and !empty($question->hints)):?>
		  	 <th><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&ob=answer&dir=<?php echo $odir;?>"><?php _e('Hints used', 'watupro')?></a></th>
		   <?php endif;?>
		   </tr>
		<?php else: // in shortcode?>
			<tr><th><?php _e('Username', 'watupro')?></th><th><?php _e('Date', 'watupro')?></th><th><?php _e('Answer', 'watupro')?></th>
			<th><?php _e('Points', 'watupro')?></th>
			<th><?php _e('Is Correct?', 'watupro')?></th>
			<?php if(watupro_intel()):?>
			   	<th><?php _e('Teacher comments', 'watupro')?></th>
			<?php endif;?>
			<?php if(!empty($exam->question_hints) and !empty($question->hints)):?>
			  	 <th><?php _e('Hints used', 'watupro')?></th>
		   <?php endif;?></tr>
		<?php endif; // end if in shortcode ?>	   
	   
	   <?php foreach($answers as $cnt=>$answer):
	   	$class = ('alternate' == @$class) ? '' : 'alternate';?>
	   	<tr class="<?php echo $class?>">	   	
	   	<?php if(empty($in_shortcode)):?>
	   		<td><?php echo $answer->user_id?"<a href='user-edit.php?user_id=".$answer->user_id."&wp_http_referer=".urlencode("admin.php?page=watupro_question_answers&exam_id=".$exam->ID."&question_id=".$question->ID)."' target='_blank'>".$answer->display_name."</a>":"N/A"?></td>
				<td><?php echo !empty($answer->email)?"<a href='mailto:".$answer->email."'>".$answer->email."</a>":__("N/A", 'watupro')?></td>
				<td><?php echo $answer->ip;?></td>
			<?php else:?>	
				<td><?php echo $answer->user_id? $answer->display_name : __("N/A", 'watupro')?></td>
			<?php endif;?>	
			<td><?php echo date($date_format, strtotime($answer->date)) ?></td>
			<td><?php if(!empty($answer->feedback)) $answer->answer .= "<br>" . $question->feedback_label. " " .$answer->feedback; 
			echo apply_filters('watupro_content', stripslashes($answer->answer))?></td>
			<td><?php echo $answer->points?></td>
			<td><?php echo $answer->is_correct ? __('Yes', 'watupro') : __('No', 'watupro');?></td>
			<?php if(watupro_intel()):?>
				<td><?php echo wpautop($answer->teacher_comments);?></td>
			<?php endif;?>			
			<?php if(!empty($exam->question_hints) and !empty($question->hints)):?>
	  			<td><p><?php echo $answer->num_hints_used ? "<p>".sprintf(__('%d hints used:', 'watupro'), $answer->num_hints_used)."</p>" . stripslashes($answer->hints_used) : __('No hints used', 'watupro')?></p></td>
	   <?php endif;?>
			</tr>
	   <?php endforeach;?>
	</table>
	
	<p align="center">
		<?php if($offset>0):?>
			<a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&offset=<?php echo $offset-$page_limit;?>&ob=<?php echo $ob?>&dir=<?php echo $dir?>"><?php _e('previous page', 'watupro')?></a>
		<?php endif;?>
		&nbsp;
		<?php if($count>($offset+10)):?>
			<a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>&offset=<?php echo $offset+$page_limit;?>&ob=<?php echo $ob?>&dir=<?php echo $dir?>"><?php _e('next page', 'watupro')?></a>
		<?php endif;?>
		</p>
</div>