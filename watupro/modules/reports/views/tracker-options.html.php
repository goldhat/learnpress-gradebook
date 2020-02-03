<div class="wrap watupro-wrap">
	<h1><?php _e('Google Analytics Tracking', 'watupro');?></h1>
	
	<p><?php printf(__('Here you can specify Google Analytics event tracking to track users starting and finishing %s. Note that you need your regular Google Analytics code to be installed on the page for any of this to work.', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL);?></p>
	
	<form method="post">
		<p><input type="checkbox" name="track_quiz_start" value="1" <?php if(!empty($options['track_quiz_start'])) echo 'checked'?>> <?php printf(__('Track starting %s which have Start button or a timer. This will not track visits to pages with %s.', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, WATUPRO_QUIZ_WORD_PLURAL);?></p>
		
		<p><input type="checkbox" name="track_quiz_end" value="1" <?php if(!empty($options['track_quiz_end'])) echo 'checked'?> onclick="this.checked ? jQuery('#quizEndOptions').show() : jQuery('#quizEndOptions').hide();"> <?php _e('Track completed quizzes.', 'watupro');?></p>
		
		<div id="quizEndOptions" style="display:<?php echo (empty($options['track_quiz_end'])) ? 'none' : 'block'; ?>">
			<p><?php _e('Define how to track this data. You need basic understanding of how Event Tracking works in Google Analytics. If you are not sure what to do, just leave the default option.', 'watupro');?></p>
			<ul>
				<li><input type="radio" name="track_quiz_end_mode" value="blank" <?php if(empty($options['track_quiz_end_mode']) or $options['track_quiz_end_mode'] == 'blank') echo 'checked'?>> <?php printf(__('Use default action and the %s name as label', 'watupro'), WATUPRO_QUIZ_WORD);?></li> 
				<li><input type="radio" name="track_quiz_end_mode" value="append_grade" <?php if(!empty($options['track_quiz_end_mode']) and $options['track_quiz_end_mode'] == 'append_grade') echo 'checked'?>> <?php printf(__('Use default action and the %s name + grade title as label', 'watupro'), WATUPRO_QUIZ_WORD);?></li>
				<li><input type="radio" name="track_quiz_end_mode" value="grade_action" <?php if(!empty($options['track_quiz_end_mode']) and $options['track_quiz_end_mode'] == 'grade_action') echo 'checked'?>> <?php printf(__('Use the grade title as action and the %s name as label.', 'watupro'), WATUPRO_QUIZ_WORD);?></li>
			</ul>
		</div>
		
		<p><input type="submit" name="ok" value="<?php _e('Save Settings', 'watupro');?>"></p>
		<?php wp_nonce_field('watupro_analytics');?>
	</form>
</div>