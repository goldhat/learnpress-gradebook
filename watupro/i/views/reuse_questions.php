<form method="post">
	<p><input type="checkbox" name="reuse_questions" <?php if(!empty($reused_exams) and !empty($reused_exams[0])) echo "checked"?> onclick="watuProIReuseQuestions(this);" value="1"> <?php _e('Reuse questions from another quiz.', 'watupro')?><p>
	<div id="watuProQuestionsReuseSelector" <?php if(empty($reused_exams) or empty($reused_exams[0])) echo "style='display:none;'"?>> 

	<p><?php printf(__('Reusing questions does not copy them. It uses them on the fly from the other %s which act as question banks. <a href="%s" target="_blank">Learn more about the difference between copy and reuse here</a>.', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, 'https://blog.calendarscripts.info/copy-reuse-or-import-questions-in-watupro-whats-the-difference/');?></p>
	
	<h3><?php printf(__('Filter / search the other %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL);?></h3>
	<?php _e('By category:', 'watupro');?> <select name="reuse_cat_id">
		<option value=""><?php _e('All categories', 'watupro');?></option>
		<?php foreach($reuse_cats as $cat):
			if(!empty($advanced_settings['filter_reuse_cat_id']) and $advanced_settings['filter_reuse_cat_id'] == $cat->ID) $selected = ' selected';
			else $selected = '';?>
			<option value="<?php echo $cat->ID?>"<?php echo $selected;?>><?php echo stripslashes($cat->name);?></option>
		<?php endforeach;?>
	</select>
	&nbsp;
	<?php _e('By title (word or phrase):','watupro');?>
	<input type="text" name="reuse_title" value="<?php echo @$advanced_settings['filter_reuse_title'];?>"> 
	<?php _e('By internal comments:','watupro');?>
	<input type="text" name="reuse_comments" value="<?php echo @$advanced_settings['filter_reuse_comments'];?>"> 
	<input type="button" value="<?php printf(__('Filter %s', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL);?>" class="button button-primary" onclick="wtpFilterReuseQuizzes(this);">	
	<br /><br />
	<?php printf(__('Select the %s to reuse questions from:', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL);?>	
	<select name="reuse_questions_from[]" multiple="true" size="10" id="reuseQuestionsSelectBox">
		<option value="0"><?php _e('- Please select -', 'watupro')?></option>
		<?php foreach($exams as $ex):?>
			<option value="<?php echo $ex->ID?>" <?php if(@in_array($ex->ID, $reused_exams)) echo "selected"?>><?php echo $ex->name . ' (ID '.$ex->ID.')'?></option>
		<?php endforeach;?>
	</select>
	&nbsp;
	</div>
	<input type="submit" value="<?php _e('Save the reuse settings', 'watupro')?>" class="button button-primary">
	<input type="hidden" name="save_reuse" value="1">
</form>

<script type="text/javascript" >
function watuProIReuseQuestions(chk) {
	if(chk.checked) {
		jQuery('#watuProQuestions').hide();
		jQuery('#watuProQuestionsReuseSelector').show();
	}
	else {
		jQuery('#watuProQuestions').show();
		jQuery('#watuProQuestionsReuseSelector').hide();
	}
}

// filters the quizzes by Ajax
function wtpFilterReuseQuizzes(btn) {
	var reuseCatId = btn.form.reuse_cat_id.value;
	var reuseTitle = btn.form.reuse_title.value;
	var reuseComments = btn.form.reuse_comments.value;
	
	var data = {'action': 'watupro_ajax', 'do': 'select_reuse_quizzes', 'reuse_cat_id' : reuseCatId, 
		'reuse_title' : reuseTitle, 'reuse_comments' : reuseComments, 'exam_id' : <?php echo $exam->ID?>};
	
	jQuery.post("<?php echo admin_url('admin-ajax.php')?>", data, function(msg) {
		jQuery('#reuseQuestionsSelectBox').html(msg);
	});
}
</script>