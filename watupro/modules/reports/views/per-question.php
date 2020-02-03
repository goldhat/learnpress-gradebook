<div class="wrap">
	<?php if(empty($in_shortcode)):?>
		<h2><?php echo stripslashes(apply_filters('watupro_qtranslate', $exam->name))?> : <?php _e('Stats Per Question', 'watupro')?></h2>
		
		<p><a href="admin.php?page=watupro_takings&exam_id=<?php echo $exam->ID?>"><?php _e("Back to the users list", 'watupro')?></a></p>
		
		<p><?php _e('Shortcode to display this page:', 'watupro')?> <input type="text" value='[watupror-stats-per-question <?php echo $exam->ID?> <?php 
      if(!empty($_POST['period']) and $_POST['period'] == 'dates') echo 'period="dates" start_date="'.$_POST['start_date'].'" end_date="'.$_POST['end_date'].'"'; 		
		?>]' size="60" readonly="true" onclick="this.select();"></p>
		
		<p><?php _e("Sometimes the percentages don't add up to 100% or make more than 100% - this happens on a) open-end and 'fill the blanks' questions (usually under 100%) b) multiple-choices questions (usually more than 100%) and c) questions in which you have added or changed the possible answers after some users have taken the quiz. This is not error in calculations but natural effect of the different question types.", 'watupro')?></p>
		
		<form method="post">
		 <p><?php _e('Period:', 'watupro');?> <select name="period" onchange="this.value == 'dates' ? jQuery('#periodSelectors').show() : jQuery('#periodSelectors').hide();">
		    <option value="all" <?php if(empty($_POST['period']) or $_POST['period'] == 'all') echo 'selected'?>><?php _e('All time', 'watupro');?></option>
		    <option value="dates" <?php if(!empty($_POST['period']) and $_POST['period'] == 'dates') echo 'selected'?>><?php _e('Date interval', 'watupro');?></option>
		 </select>
         <span style='display:<?php echo (empty($_POST['period']) or $_POST['period'] != 'dates') ? 'none' : 'inline'?>' id="periodSelectors">
            <?php _e('From:', 'watupro')?>
            <input type="text" name="start_date" class="watuproDatePicker" id="watuproDateStart" value="<?php echo date_i18n($dateformat, strtotime($start_date))?>">
            <?php _e('To:', 'watupro')?>
				<input type="text" name="end_date" class="watuproDatePicker" id="watuproDateEnd" value="<?php echo date_i18n($dateformat, strtotime($end_date))?>">            
         </span>		 
         <input type="submit" value="<?php _e('Refresh stats', 'watupro')?>">
		 </p>
		 <input type="hidden" name="start_date" value="<?php echo $start_date?>" id="alt_watuproDateStart">
			<input type="hidden" name="end_date" value="<?php echo $end_date?>" id="alt_watuproDateEnd">
		</form>
	<?php endif;?>
	
	<?php foreach($questions as $cnt=>$question):
	$cnt++;?>
		<h3><?php echo apply_filters('watupro_content', $cnt.". ".stripslashes($question->question))?></h3>
		
		<table class="widefat">
			<tr class="alternate"><th><?php _e('Answer or metric', 'watupro')?></th><th><?php _e('Value', 'watupro')?></th></tr>
			<tr><td><?php _e('Number and % correct answers', 'watupro')?></td>
			<td><strong><?php echo $question->percent_correct?>%</strong> / <strong><?php echo $question->num_correct?></strong> <?php _e('correct answers from', 'watupro')?>
			<strong><?php echo $question->total_answers?></strong> <?php _e('total answers received', 'watupro')?> </td></tr>
			<?php $class = '';
			// match/matrix should also have no choices because they make no sense here
			if($question->answer_type != 'nmatrix'): 
				foreach($question->choices as $choice):
				$class = ('alternate' == @$class) ? '' : 'alternate';?><tr class="<?php echo $class?>">
					<td><?php echo apply_filters('watupro_content', stripslashes($choice->answer))?></td><td><strong><?php echo $choice->times_selected?></strong> <?php _e('times selected', 'watupro')?> / <strong><?php echo $choice->percentage?>%</strong> </td>			
				</tr><?php endforeach;?>
			<?php endif; // end if not nmatrix;?>	
		</table>
		
		<?php if(empty($in_shortcode)):?><p><a href="admin.php?page=watupro_question_answers&exam_id=<?php echo $exam->ID?>&id=<?php echo $question->ID?>"><?php _e('Get full details', 'watupro')?></a></p><?php endif;?>
	<?php endforeach;?>
</div>	

<?php if(!$in_shortcode):?>
<script type="text/javascript" >
jQuery(document).ready(function() {
	jQuery('.watuproDatePicker').datepicker({
        dateFormat : '<?php echo dateformat_PHP_to_jQueryUI($dateformat);?>',
        altFormat: 'yy-mm-dd',
    });
    
    jQuery(".watuproDatePicker").each(function (idx, el) { 
	    jQuery(this).datepicker("option", "altField", "#alt_" + jQuery(this).attr("id"));
	});
});	
</script>
<?php endif;?>