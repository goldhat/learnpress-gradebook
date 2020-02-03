<div class="wrap watupro-wrap">
	<?php if(!empty($quiz->ID)):?>
		<h1><?php printf(__('User feedback on questions from %s "%s"', 'watupro'), WATUPRO_QUIZ_WORD, stripslashes($quiz->name))?></h1>	
		<p><a href="admin.php?page=watupro_takings&exam_id=<?php echo $quiz->ID?>"><?php printf(__('Back to the results on this %s', 'watupro'), WATUPRO_QUIZ_WORD);?></a>
		| <a href="admin.php?page=watupro_questions_feedback"><?php printf(__('View feedback on all %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL);?></a></p>
	<?php else: 
		// feedback on all quizzes ?>
		<h1><?php printf(__('User feedback on questions from all %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></h1>	
		<p><a href="admin.php?page=watupro_takings?>"><?php printf(__('Back to the results on all %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></a></p>
	<?php endif;?>
	
	<?php if(!$count):?>
		<p><?php _e('There is no feedback left for any of the questions yet.', 'watupro')?></p>
		</div>
	<?php return;
	endif;?>
	
	<table class="widefat watupro-table">
		<thead>
			<tr><?php if(empty($quiz->ID)):?>
				<th><?php printf(__('%s Name', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD));?></th>
			<?php endif;?>
			<th><?php _e('Question', 'watupro')?></th><th><?php _e('Answer', 'watupro')?></th>
			<th><?php _e('Feedback', 'watupro')?></th><th><?php _e('Left on', 'watupro')?></th>
			<th><?php _e('Quiz result', 'watupro')?></th><th><?php _e('View details', 'watupro')?></th></tr>
		</thead>
		
		<tbody>
		<?php foreach($feedbacks as $feedback):
			$class = ('alternate' == @$class) ? '' : 'alternate';?>
			<tr class="<?php echo $class?>">
				<?php if(empty($quiz->ID)):?>
					<td><?php echo stripslashes($feedback->quiz_name);?></td>
				<?php endif;?>
				<td><?php echo apply_filters('watupro_content', stripslashes($feedback->question))?></td>
				<td><?php echo apply_filters('watupro_content', stripslashes($feedback->answer))?></td>
				<td><?php echo apply_filters('watupro_content', stripslashes($feedback->feedback))?></td>
				<td><?php echo date($dateformat, strtotime($feedback->taking_date))?></td>
				<td><?php echo stripslashes($feedback->taking_result)?></td>
				<td><a href="admin.php?page=watupro_takings&exam_id=<?php echo $quiz->ID?>&taking_id=<?php echo $feedback->taking_id?>" target="_blank"><?php _e('view', 'watupro')?></a></td>
			</tr>
		<?php endforeach;?>
		</tbody>
	</table>
</div>	