<label>&nbsp;<input type='radio' name='answer_type' <?php if(!empty($ans_type) and $ans_type=='gaps') echo "checked"?> id="answer_type_g" value='gaps' onclick="selectAnswerType('gaps');" /> <?php _e('Fill The Gaps', 'watupro')?></label> &nbsp;&nbsp;&nbsp;
<label>&nbsp;<input type='radio' name='answer_type' <?php if(!empty($ans_type) and $ans_type=='sort') echo "checked"?> id="answer_type_s" value='sort' onclick="selectAnswerType('sort');" /> <?php _e('Sort the values', 'watupro')?></label> &nbsp;&nbsp;&nbsp;
<label>&nbsp;<input type='radio' name='answer_type' <?php if(!empty($ans_type) and $ans_type=='nmatrix') echo "checked"?> id="answer_type_m" value='nmatrix' onclick="selectAnswerType('nmatrix');" /> <?php _e('Matrix / Match values', 'watupro')?></label> &nbsp;&nbsp;&nbsp;

<label>&nbsp;<input type='radio' name='answer_type' <?php if(!empty($ans_type) and $ans_type=='slider') echo "checked"?> id="answer_type_m" value='slider' onclick="selectAnswerType('slider');" /> <?php _e('Slider / Rating', 'watupro')?></label> &nbsp;&nbsp;&nbsp;

		<div id="fillTheGapsText" style='display:<?php echo ($ans_type == 'gaps')?'block':'none'?>;'>
			<p><?php _e('For "fill the gaps" questions simply enter your <b>correct text</b> and place {{{ and }}} around the phrases that should turn into gaps (no spaces please). Example: "<b>The color of sun is {{{yellow}}}.</b>" The student will be shown input field instead of "{{{yellow}}}" in this case.', 'watupro')?><br>
			<?php _e('You can include multiple correct answers for each blank/gap if you separate them by |. For example: <b>"The color of a good apple can be {{{green|yellow|red}}}"</b>.','watupro')?><br>
			<?php printf(__('You can also specify a correct numeric range in the following format: <b>%s</b>. For example to accept any answer between 2.5 and 10 enter <b>%s</b>.', 'watupro'), '{{{x...y}}}', '{{{2.5...10}}}');?><br>
			<?php _e('If you want to accept decimals the first value in the range must contain decimal points. Otherwise only whole numbers within the range will be considered correct answers.', 'watupro');?> <a href="https://blog.calendarscripts.info/turn-multiple-answer-gaps-into-drop-downs-watupro-intelligence-module-v-5-0-2/" target="_blank"><?php _e('Learn about more adjustments.', 'watupro');?></a></p>
			<p><?php _e('Points to assign for correctly filled gap:', 'watupro')?> <input type="text" name="correct_gap_points" value="<?php echo @$question->correct_gap_points?>" size="4"> &nbsp; <?php _e('Points to assign for incorrectly filled gap (optional):', 'watupro')?> <input type="text" name="incorrect_gap_points" value="<?php echo @$question->incorrect_gap_points?>" size="4"> <?php _e('(Decimals allowed, must be negative number)', 'watupro')?></p>
			<p><input type="checkbox" name="sensitive_gaps" value="1" <?php if(@$question->open_end_mode == 'sensitive_gaps') echo 'checked'?>> <?php _e('Gaps are case sensitive. User answers must match exactly the value that you enter to be considered correct.');?></p>
			<p><input type="checkbox" name="gaps_as_dropdowns" value="1" <?php if(!empty($question->gaps_as_dropdowns)) echo 'checked'?>> <?php printf(__('Turn multiple-answer gaps into drop-downs (<a href="%s" target="_blank">?</a>)', 'watupro'), 'http://blog.calendarscripts.info/turn-multiple-answer-gaps-into-drop-downs-watupro-intelligence-module-v-5-0-2/');?></p>
			<p><?php _e('Width of gaps in pixels:', 'watupro');?> <input type="text" size="4" value="<?php echo intval(@$question_design['gaps_width'])?>" name="gaps_width"> <?php _e('(Leave blank or 0 to use the default width from the CSS. For drop-downs width will be dynamic.)', 'watupro');?></p>
		</div>
		
		
		<div id="sortingText" style='display:<?php echo ($ans_type == 'sort' or $ans_type == 'matrix' or $ans_type == 'nmatrix') ? 'block' : 'none'?>;'>
			<p><input type="checkbox" name="calculate_whole" value="1" <?php if(!empty($question->calculate_whole)) echo "checked"?> onclick="watuproSortableAsWhole(this);"> <?php _e('Treat this question as a whole', 'watupro')?></p>
			<p><span id="sortingCorrectPointsText"><?php if(empty($question->calculate_whole)): _e('Points to assign for correctly matched position:', 'watupro'); else: _e('Points to assign for when all positions are matched correctly:', 'watupro'); endif;?></span> <input type="text" name="correct_sort_points" value="<?php echo @$question->correct_gap_points?>" size="4"> &nbsp; <span id="sortingIncorrectPointsText"><?php if(empty($question->calculate_whole)): _e('Points to assign for incorrectly matched position (optional):', 'watupro'); else: _e('Points to assign when there is a mistake in ordering the items (optional):'); endif;?></span> <input type="text" name="incorrect_sort_points" value="<?php echo @$question->incorrect_gap_points?>" size="4"> <?php _e('(Decimals allowed)', 'watupro')?></p>			
		</div>	
		
		<div id="sliderText" style='display:<?php echo ($ans_type == 'slider')?'block':'none'?>;'>
			<p><?php _e('Enter the from-to scale of the slider (in numbers). For example 1 - 100:', 'watupro');?> 
				<input type="text" size="4" name="slide_from" value="<?php echo intval(@$question->correct_gap_points)?>"> - <input type="text" size="4" name="slide_to" value="<?php echo intval(@$question->incorrect_gap_points)?>"></p>
			<p><b><?php _e('When entering the possible answers in the answers are below, enter from-to values with comma like this: "1, 10", "11, 20", "21, 30" etc.', 'watupro');?></b></p>		
			<p><input type="checkbox" name="slider_transfer_points" value="1" <?php if(!empty($question->slider_transfer_points)) echo 'checked'?>> <?php _e('Transfer the slider number selection as points on this question. You can still define right and wrong answers as explained above, but the points assigned on them will be discarded.', 'watupro');?></p>
		</div>


<script type="text/javascript" >
function watuproSortableAsWhole(chk) {
	if(chk.checked) {
		jQuery('#sortingCorrectPointsText').html("<?php _e('Points to assign for when all positions are matched correctly:', 'watupro')?>");
		jQuery('#sortingIncorrectPointsText').html("<?php _e('Points to assign when there is a mistake in ordering the items (optional):', 'watupro')?>");
	}
	else {
		jQuery('#sortingCorrectPointsText').html("<?php _e('Points to assign for correctly matched position:', 'watupro')?>");
		jQuery('#sortingIncorrectPointsText').html("<?php _e('Points to assign for incorrectly matched position (optional):', 'watupro')?>");
	}
}
</script>		