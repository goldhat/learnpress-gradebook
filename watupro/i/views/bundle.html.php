<div class="wrap">
	<h1><?php printf(__('Manage %s Bundle', 'watupro'), __('Quiz', 'watupro'))?></h1>
	
	<p><a href="admin.php?page=watupro_bundles"><?php _e('Back to bundle buttons', 'watupro')?></a></p>
	
	<form method="post" class="watupro">
		<p><label><?php _e('Name:', 'watupro');?></label> <input type="text" name="name" value="<?php echo stripslashes(@$bundle->name);?>"> <?php _e('Will be shown on payment buttons.', 'watupro');?></p>
		<p><label><?php _e('Bundle type:', 'watupro')?></label> <select name="bundle_type" onchange="changeBundleType(this.value);">
			<option value="quizzes" <?php if(!empty($bundle) and $bundle->bundle_type == 'quizzes') echo 'selected'?>><?php printf(__('Selected %s', 'watupro'), __('quizzes', 'watupro'))?></option>		
			<option value="category" <?php if(!empty($bundle) and $bundle->bundle_type == 'category') echo 'selected'?>><?php printf(__('Categories of %s', 'watupro'), __('quizzes', 'watupro'))?></option>
			<option value="num_quizzes" <?php if(!empty($bundle) and $bundle->bundle_type == 'num_quizzes') echo 'selected'?>><?php printf(__('Number of %s', 'watupro'), __('quizzes', 'watupro'))?></option>
		</select></p>
		<p><label><?php _e('Price:', 'watupro')?></label> <input type="text" size="6" name="price" value="<?php echo @$bundle->price?>"> <?php echo $currency?></p>
		
		<div id="bundleQuizzes" style='display:<?php echo (empty($bundle) or $bundle->bundle_type == 'quizzes') ? 'block' : 'none';?>'>
			<?php if(!count($quizzes)):?>
				<p><b><?php printf(__('You need to create some paid %1$s first. Once you have created a %2$s, set a non-zero price for it on the Edit %3$s page, Intelligence Module tab and you will see it available for selecting here.', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, WATUPRO_QUIZ_WORD,ucfirst(WATUPRO_QUIZ_WORD));?></b></p>
			<?php endif;
			echo '<p>';
			foreach($quizzes as $quiz):?>
				<input type="checkbox" name="quizzes[]" value="<?php echo $quiz->ID?>" <?php if(!empty($qids) and in_array($quiz->ID, $qids)) echo 'checked'?>>&nbsp;<?php echo stripslashes($quiz->name)?>&nbsp; 
			<?php endforeach;?>
			</p>		
		</div>
		
		<p id="bundleCategory" style='display:<?php echo (!empty($bundle) and $bundle->bundle_type == 'category') ? 'block' : 'none';?>'>
			<label><?php _e('Select categories:', 'watupro')?></label> <select name="cat_ids[]" multiple="multiple">
				<?php foreach($cats as $cat):?>
					<option value="<?php echo $cat->ID?>" <?php if(!empty($bundle->cat_ids) and ($bundle->cat_ids == $cat->ID or strstr($bundle->cat_ids, '|'.$cat->ID.'|'))) echo 'selected'?>><?php echo stripslashes($cat->name)?></option>
				<?php endforeach;?>
			</select>
		</p>
		
		<p id="bundleNumQuizzes" style='display:<?php echo (!empty($bundle) and $bundle->bundle_type == 'num_quizzes') ? 'block' : 'none';?>'>
			<label><?php printf(__("Number of %s to give access to:", 'watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></label>
			<input type="number" name="num_quizzes" size="4" value="<?php echo @$bundle->num_quizzes?>"> <br />
			<?php printf(__('Will give access to this number of unique %1$s. How many times each %2$s can be attempted depends on the specific %3$s settings.', 'watupro'),
				WATUPRO_QUIZ_WORD_PLURAL, WATUPRO_QUIZ_WORD, WATUPRO_QUIZ_WORD);?>
		</p>
		
		<p><label><?php _e('After payment redirect to:', 'watupro')?></label> <input type="text" name="redirect_url" value="<?php echo @$bundle->redirect_url?>" size="60"> <?php _e('Enter full URL where the user should go after payment. This can be for example the URL of the first quiz from the bundle.', 'watupro')?></p>
		
		<p><input type="checkbox" name="is_time_limited" value="1" <?php if(!empty($bundle->is_time_limited)) echo 'checked'?> onclick="this.checked ? jQuery('#timeLimitedBundle').show() : jQuery('#timeLimitedBundle').hide();"> <?php _e('Access to quizzes will be limited for a period of time:', 'watupro');?>
        <span id="timeLimitedBundle" style='display:<?php echo empty($bundle->is_time_limited) ? 'none' : 'inline';?>'><input type="text" size="6" name="time_limit" value="<?php echo @$bundle->time_limit?>"> <?php _e('days after purchasing the bundle. Changing this affects also users who already purchased the bundle.', 'watupro');?></span>		
		</p>
		
		<p><input type="submit" value="<?php _e('Save Bundle Button', 'watupro')?>"></p>
		<input type="hidden" name="ok" value="1">
		<?php wp_nonce_field('watupro_bundle');?>
	</form>
</div>

<script type="text/javascript" >
function changeBundleType(val) {
	jQuery('#bundleQuizzes').hide();
	jQuery('#bundleCategory').hide();
	jQuery('#bundleNumQuizzes').hide();
	
	if(val == 'quizzes') jQuery('#bundleQuizzes').show();
	if(val == 'num_quizzes') jQuery('#bundleNumQuizzes').show();
	if(val == 'category') jQuery('#bundleCategory').show();
}
</script>