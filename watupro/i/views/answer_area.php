<div class="postbox" id="sortAnswerArea" style='display:<?php echo (!empty($question) and $question->answer_type=='sort')?'block':'none';?>'>
	<h3 class="hndle"><span>&nbsp;<?php _e('Answers', 'watupro') ?></span></h3>
	<div class="inside">
		<p><?php _e('For sorting questions simply enter your values one per line in the correct way they should be sorted.<br> Feel free to leave blank lines for your own clarity.<br> If a value needs to contain a new line, use the HTML &lt;br&gt; tag and not a real new line.', 'watupro')?><br>
		<?php _e('Do not use the special character "|" inside the answers.', 'watupro');?></p>
		
		<?php if($exam->is_personality_quiz):?>
			<h3><?php _e('This is a personality quiz', 'watupro')?></h3>
			
			<p><?php _e('You need to enter the values to sort in the following order:', 'watupro')?> </p>
			<ol>
				<?php foreach($grades as $grade):
					echo "<li>".sprintf(__('The value that matches result "%s"', 'watupro'), $grade->gtitle)."</li>";
				endforeach;?>
			</ol>
			
			<p><?php printf(__('These values will then be shown to the user randomized. The value that the user sorts on top will assign %d points to its corresponding result. The value that goes at the bottom will get one point. The other values will assign points depending on their position.', 'watupro'), sizeof($grades))?> 
			<?php printf(__('For better understanding check <a href="%s" target="_blank">this post</a>', 'watupro'), 'http://blog.calendarscripts.info/using-sorting-questions-in-personality-quizzes-watupro/')?></p>
		<?php endif;?>
		
		<p><textarea name="sorting_answers" rows="10" cols="80"><?php if(!empty($question->sorting_answers)) echo stripslashes($question->sorting_answers);?></textarea></p>
		
		
	</div>
</div>

<div class="postbox" id="matrixAnswerArea" style='display:<?php echo (!empty($question) and ($question->answer_type=='matrix' or $question->answer_type=='nmatrix'))?'block':'none';?>'>
	<h3 class="hndle">&nbsp;<span><?php _e('Answers', 'watupro') ?></span></h3>
	<div class="inside">
		<p><?php _e('Place the match criteria at left. These will be shown to the user in the order you enter them. Place the correct matches at right. These will be mixed at right and the user will need to click on the right match for each left value.', 'watupro')?><br>
		<?php printf(__('<a href="%s" target="_blank">See here</a> if you want to add images to the answers.', 'watupro'), 'http://blog.calendarscripts.info/watupro-adding-images-in-matchmatrix-answers/')?> </p>
		<?php if(!empty($matches) and sizeof($matches)):
		foreach($matches as $match):
			// existing matches ?>
			<p class="wtp-notruefalse">
				<textarea rows="4" cols="30" name="<?php echo ($_GET['action']=='new') ? 'new_matches_left[]' : 'left_match_'.$match['id']?>"><?php echo stripslashes($match['left']); ?></textarea> 
				=
				<textarea rows="4" cols="30" name="<?php echo ($_GET['action']=='new') ? 'new_matches_right[]': 'right_match_'.$match['id']?>"><?php echo stripslashes($match['right']); ?></textarea>
			</p>
		<?php endforeach;
		endif; ?>
		<p "class='wtp-notruefalse'">
				<textarea rows="4" cols="30" name="new_matches_left[]"></textarea> 
				=
				<textarea rows="4" cols="30" name="new_matches_right[]"></textarea>
		</p>
		<div id="wtpExtraMatches"></div>
		<p><a href="#" onclick="WatuPROIAddMatches();return false;"><?php _e('Add more matches', 'watupro')?></a></p>
	</div>
</div>

<script type="text/javascript" >
function WatuPROIAddMatches() {
	jQuery('#wtpExtraMatches').append('<p class="wtp-notruefalse">	<textarea rows="4" cols="30" name="new_matches_left[]"></textarea> = <textarea rows="4" cols="30" name="new_matches_right[]"></textarea></p>');
}
</script>