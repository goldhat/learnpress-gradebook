<h1><?php _e('Edit and Manually Grade Test Results', 'watupro')?></h1>

<div class="wrap">
	<p><?php _e('Student:', 'watupro')?> <?php echo $taking->user_id ? $student->user_login : $taking->email?></p>
	<p><?php _e('Quiz:', 'watupro')?> <a href="admin.php?page=watupro_takings&exam_id=<?php echo $exam->ID?>"><?php echo stripslashes($exam->name)?></a></p>
	
	<p><?php _e('You can use this page to manually edit and grade the submitted test details', 'watupro')?></p>
	
	<form method="post" onsubmit="return validateForm(this);" enctype="multipart/form-data">
	<table class="widefat">
		<tr><th><?php _e('Question', 'watupro')?></th><th><?php _e('Category', 'watupro');?></th><th><?php _e('Answer Given', 'watupro')?></th>
		<th><?php _e('Points', 'watupro')?></th><th><?php _e('Is correct?', 'watupro')?></th>
		<th><?php _e('Optional comments', 'watupro')?></th></tr>
		
		<?php foreach($answers as $answer):
			$class = ('alternate' == @$class) ? '' : 'alternate';?>
			<tr class="<?php echo $class?>"><td><?php echo apply_filters('watupro_content', stripslashes($answer->question))?><br>
			<a href="admin.php?page=watupro_question&question=<?php echo $answer->question_id?>&action=edit&quiz=<?php echo $exam->ID?>" target="_blank"><?php _e('View/edit question', 'watupro');?></a></td>
			<td><?php echo empty($answer->cat_id) ? __('Uncategorized', 'watupro') : stripslashes($answer->category); ?></td>
			<td><?php if(empty($advanced_settings['answer_snapshot_in_table_format'])) echo nl2br(stripslashes($answer->answer));
				else echo $answer->snapshot;
				if(!empty($answer->file_id)):
				 echo "<p><a href=".site_url('?watupro_download_file=1&id='.$answer->file_id.">".sprintf(__('Uploaded: %s (%d KB)', 'watupro'), $answer->filename, $answer->filesize))."</a></p>
				 <p>".__('Change file:', 'watupro')." <input type='file' name='file-answer-".$answer->question_id."'></p>";
				 endif;?></td>
			<td><input type="text" size="6" value="<?php echo $answer->points?>" name="points<?php echo $answer->ID?>"></td>
			<td><?php if($answer->is_survey): _e('N/a (survey)', 'watupro');
			else:?>
				<input type="checkbox" name="is_correct<?php echo $answer->ID?>" value="1" <?php if($answer->is_correct) echo "checked"?>>
			<?php endif;?>	</td>
			<td><textarea rows="3" cols="30" name="teacher_comments<?php echo $answer->ID?>"><?php echo stripslashes($answer->teacher_comments)?></textarea></td></tr>
		<?php endforeach;?>	
	</table>
	
	<?php if(empty($restrict_emailing) or $restrict_emailing != 'restrict'):?>
	<p><input type="checkbox" name="send_email" value="1" onclick="jQuery('#emailDetails').toggle();"> <?php _e("I want to send email to the user with the updated details", 'watupro')?></p>
	
	<div id="emailDetails" style="display:none;" class="watupro">
		<div><label><?php _e('Receiver email', 'watupro');?></label> <input type="text" name="email" value="<?php echo $receiver_email?>"></div>
		<div><label><?php _e('Subject:', 'watupro')?></label> <input type="text" name="subject" size="60" value="<?php echo get_option('watupro_manual_grade_subject')?>"></div>
		<div><label><?php _e('Message:', 'watupro')?></label> <?php wp_editor(stripslashes(get_option('watupro_manual_grade_message')), 'msg')?></div>
		<p><b><?php _e("Important: if you have included variables inside the grade description, they can't be properly replaced here. In such cases it's advised to avoid %%GDESC%% and %%GRADE%% in the email contents.", 'watupro');?></b></p>
		<?php $edit_mode = true;
		if(@file_exists(get_stylesheet_directory().'/watupro/usable-variables.php')) require get_stylesheet_directory().'/watupro/usable-variables.php';
		else require WATUPRO_PATH."/views/usable-variables.php";?>
		<p><?php printf(__('You can also use the <a href="%s" target="_blank">user info shortcodes</a>. To pass them the correct user ID the argument %s should contain "%s". Example: %s.', 'watupro'), 'http://blog.calendarscripts.info/user-info-shortcodes-from-watupro-version-4-1-1/', "user_id", "quiz-taker", '[watupro-userinfo first_name user_id="quiz-taker"]');?></p>
	</div>
	<?php endif;?>
	
	<p align="center"><input type="submit" value="<?php _e('Update Test Results', 'watupro')?>" class="button-primary"></p>
	<input type="hidden" name="ok" value="1">
	</form>
</div>

<script type="text/javascript" >
function validateForm(frm) {
		if(frm.send_email.checked) {
				if(frm.email.value=="") {
						alert("<?php _e('Please enter receiver email', 'watupro')?>");
						frm.email.focus();
						return false;
				}				
				
				if(frm.email.value=="") {
						alert("<?php _e('Please enter email subject', 'watupro')?>");
						frm.subject.focus();
						return false;
				}				
		}
		
		return true;
}
</script>