<div class="watupro watupro-user-choice">
   <form method="post">
   	<?php if(empty($advanced_settings['user_choice_enhanced']) or (!empty($advanced_settings['user_choice_modes']) and in_array('random_questions', $advanced_settings['user_choice_modes']))):
   	$user_choice_radio_shown = true; // to know whether to pre-select the radio button?>
      	<p><input type="radio" name="watupro_mode" value="random_questions" checked="true"> <?php printf(__('Select %s <b>random questions</b> out of %d questions in the whole test', 'watupro'),
         '<input type="text" name="num_questions" size="4" style="max-width:100px;">', $total_questions);?></p>
      <?php endif;
      if(empty($advanced_settings['user_choice_enhanced']) or (!empty($advanced_settings['user_choice_modes']) and in_array('per_category', $advanced_settings['user_choice_modes']))): ?>   
	      <p><input type="radio" name="watupro_mode" value="per_category" <?php if(empty($user_choice_radio_shown)) echo 'checked="true"';?> > <?php _e('Select questions from these question categories:', 'watupro');?>
	         <ul>
	            <?php foreach($cats as $cat):?>
	              <li><?php printf(__('Select %s questions from <b>%s</b> (%d total)', 'watupro'), 
	               '<input type="text" name="num_questions_'.$cat->ID.'" size="4" style="max-width:100px;">', stripslashes($cat->name), $cat->num_questions);?></li>
	            <?php endforeach;?>
	         </ul>      
	      </p>   
	   <?php $user_choice_radio_shown = true; 
	   endif;
	   if(empty($advanced_settings['user_choice_enhanced']) or (!empty($advanced_settings['user_choice_modes']) and in_array('keywords', $advanced_settings['user_choice_modes']))):?>	      
      <p><input type="radio" name="watupro_mode" value="keywords" <?php if(empty($user_choice_radio_shown)) echo 'checked="true"';?>> <?php _e('Select from questions containing these keywords:', 'watupro');?><br>
         <?php for($i = 1; $i <= 10; $i++):?>
            <input type="text" name="watupro_keywords[]"> 
         <?php endfor;?>      
      </p>
      <?php endif;?>
      <p><input type="submit" name="wtpuc_ok" class="watupro-start-quiz" value="<?php printf(__('Start %s', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD));?>"></p>
   </form>
</div>