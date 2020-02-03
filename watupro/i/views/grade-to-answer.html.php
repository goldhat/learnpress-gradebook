<?php _e('assign to results:', 'watupro')?>

<select name="grade_id_<?php echo $i?>[]" multiple="true" size="3" class="personaility-grade">
	<option value="0"><?php _e('- please select -', 'watupro')?></option>
	<?php foreach($grades as $grade):
		$answer_grade_ids = explode('|', @$all_answers[$i-1]->grade_id); ?>
		<option value="<?php echo $grade->ID?>" <?php if( in_array($grade->ID, $answer_grade_ids)) echo 'selected'?>><?php echo $grade->gtitle?></option>
	<?php endforeach;?>
</select>