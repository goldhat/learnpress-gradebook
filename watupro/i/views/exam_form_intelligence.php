<div class="postbox watupro-tab-div" id="intel" style="display:none;">        
    <div class="inside">
      	<h3><?php _e('Personality Quiz', 'watupro') ?></h3>
    			<p><label><input type="checkbox" name="is_personality_quiz" value="1" <?php if(!empty($dquiz->is_personality_quiz)) echo 'checked'?> onclick="this.checked ? jQuery('#singlePersonalitySetting').show() : jQuery('#singlePersonalitySetting').hide();"> <?php printf(__('Treat this %s as a personality quiz: answers will be matched to results instead of calculating the points. <a href="%s" target="_blank">Learn more here</a>.', 'watupro'), WATUPRO_QUIZ_WORD, 'http://blog.calendarscripts.info/personality-quizzes-in-watupro/');?></label></p>
    			
    			<p id="singlePersonalitySetting" style='display:<?php echo empty($dquiz->is_personality_quiz) ? 'none' : 'block';?>'><label><input type="checkbox" name="single_personality_result" value="1" <?php if(!empty($advanced_settings['single_personality_result'])) echo 'checked'?>> <?php printf(__('Assign only one personality as result of the %s even if more personality types rank at top together with equal number of points (by selecting this the program will choose one randomly).', 'watupro'), WATUPRO_QUIZ_WORD);?></label></p>
    			
			<h3><?php _e('User Control', 'watupro');?></h3>
			
			<p><input type="radio" name="user_choice" value="0" <?php if(empty($advanced_settings['user_choice'])) echo 'checked'?> onclick="jQuery('#userChoiceCriteria').hide();"> <?php _e('Load the test in the way I have defined within the settings (default mode)', 'watupro')?><br>
			<input type="radio" name="user_choice" value="1" <?php if(!empty($advanced_settings['user_choice'])) echo 'checked'?> onclick="jQuery('#userChoiceCriteria').show();"> <?php printf(__('Let user choose what questions to answer (based on predefined criteria). This mode will not work with timed %s!', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL);?>
				<div id="userChoiceCriteria" style='display:<?php echo empty($advanced_settings['user_choice']) ? 'none' : 'block';?>'>
					<?php _e('Allow the following options:', 'watupro');?> &nbsp;
						<input type="checkbox" name="user_choice_modes[]" value="random_questions" <?php if(empty($advanced_settings['user_choice_enhanced']) or @in_array('random_questions', @$user_choice_modes)) echo 'checked'?>> <?php printf(__('Select number of random questions from the whole %s', 'watupro'), WATUPRO_QUIZ_WORD);?>	
						&nbsp;
						<input type="checkbox" name="user_choice_modes[]" value="per_category" <?php if(empty($advanced_settings['user_choice_enhanced']) or @in_array('per_category', @$user_choice_modes)) echo 'checked'?>> <?php printf(__('Select number of random questions per category', 'watupro'), WATUPRO_QUIZ_WORD);?>	
						&nbsp;
						<input type="checkbox" name="user_choice_modes[]" value="keywords" <?php if(empty($advanced_settings['user_choice_enhanced']) or @in_array('keywords', @$user_choice_modes)) echo 'checked'?>> <?php printf(__('Select questions by keywords', 'watupro'), WATUPRO_QUIZ_WORD);?>							
				</div>			
			</p>
			
			<?php if(sizeof($other_exams)):?>
		   <h3><?php _e("Dependencies", 'watupro')?></h3>
		   
		   <div id="dependencyWarning" style='display:<?php echo (empty($dquiz->ID) or !$dquiz->require_login)?'block':'none'; ?>'>
		   	<p><?php _e("This feature becomes active only when <strong>Require user log-in</strong> option in the <strong>User and Email Related Settings</strong> tab is selected.", 'watupro')?></p>
		   </div>
		   
		   <div id="dependencyDiv" style='display:<?php echo (empty($dquiz->ID) or !$dquiz->require_login)?'none':'block'; ?>'>
				<p><?php printf(__("Let the user access this %s only after they have completed %s of the following ones:", 'watupro'), WATUPRO_QUIZ_WORD, '<select name="dependency_type">
					<option value="all" '.((empty($advanced_settings['dependency_type']) or $advanced_settings['dependency_type']=='all') ? 'selected' : '').'>'.__('all', 'watupro').'</option>
					<option value="any" '.((!empty($advanced_settings['dependency_type']) and $advanced_settings['dependency_type']=='any') ? 'selected' : '').'>'.__('any', 'watupro').'</option>
				</select>')?></p>
				
				<?php foreach($dependencies as $dependency):?>
					<div id="oldDependencyRow<?php echo $dependency->ID?>">
						<?php ucfirst(WATUPRO_QUIZ_WORD)?> <select name="dependency<?php echo $dependency->ID?>" class="watupro-depend-exam">
							<option value=""><?php printf(__("- Select %s -", 'watupro'), WATUPRO_QUIZ_WORD)?></option>			
							<?php foreach($other_exams as $oexam):?>
								<option value="<?php echo $oexam->ID?>"<?php if($oexam->ID==$dependency->depend_exam) echo " selected"?>><?php echo stripslashes($oexam->name)?></option>
							<?php endforeach;?>	
						</select> <?php _e("is completed with at least", 'watupro')?> <input type="text" name="depend_points<?php echo $dependency->ID?>" value="<?php echo $dependency->depend_points?>" onblur="WatuPRODep.forceNumber(this)" size="4"> <select name="depend_mode<?php echo $dependency->ID?>">
					<option value="points" <?php if(@$dependency->mode=='points') echo 'selected'?>><?php _e('Points', 'watupro')?></option>
					<option value="percent" <?php if(@$dependency->mode=='percent') echo 'selected'?>><?php _e('% correct answers', 'watupro')?></option>
					<option value="percent_points" <?php if(@$dependency->mode=='percent_points') echo 'selected'?>><?php _e('% of max. points', 'watupro')?></option>
				</select> <?php _e("achieved", 'watupro')?>.</span>
						<span class="submit" id="delDepBtn<?php echo $dependency->ID?>"><input type="button" value="<?php _e('Mark To Delete', 'watupro')?>" onclick="WatuPRODep.del(true, <?php echo $dependency->ID?>);"></span>
						
						<span class="submit" id="restoreDepBtn<?php echo $dependency->ID?>" style="display:none;"><input type="button" value="<?php _e('Restore', 'watupro')?>" onclick="WatuPRODep.del(false, <?php echo $dependency->ID?>);"></span>
					</div>
				<?php endforeach;?>
				
				<div id="dependencyRow"><span id="dependencySpan"><?php ucfirst(WATUPRO_QUIZ_WORD)?> <select name="dependencies[]" id="dependExam">
					<option value=""><?php _e("- Select exam -", 'watupro')?></option>			
					<?php foreach($other_exams as $oexam):?>
						<option value="<?php echo $oexam->ID?>"><?php echo stripslashes($oexam->name)?></option>
					<?php endforeach;?>	
				</select> <?php _e("is completed with at least", 'watupro')?> <input type="text" name="depend_points[]" value="0" onblur="WatuPRODep.forceNumber(this)" size="4" id="dependPoints"> <select name="depend_modes[]" id="dependMode">
					<option value="points"><?php _e('Points', 'watupro')?></option>
					<option value="percent"><?php _e('% correct answers', 'watupro')?></option>
					<option value="percent_points"><?php _e('% of max. points', 'watupro')?></option>
				</select> <?php _e("achieved", 'watupro')?>.</span>
				<span class="submit"><input type="button" value="<?php _e('Add New Dependency', 'watupro')?>" id="addDependencyButton"></span>
				</div>	
				
				
		   </div>
		   <input type="hidden" id="delDependencies" name="del_dependencies" value="">
		   <?php endif;?>
		   
		   <!-- Payment settings -->
		   <h3><?php _e("Payment Settings", 'watupro')?></h3>
		   
		   <div id="WatuPROPaymentDiv">
					<p><?php _e("Charge", 'watupro')?> <?php echo get_option("watupro_currency")?> <input type="text" name="fee" size="6" value="<?php echo empty($dquiz->fee) ? 0 : $dquiz->fee?>" onkeyup="this.value > 0 ? jQuery('#WatuPROFreeAccessDiv').show() : jQuery('#WatuPROFreeAccessDiv').hide();"> <?php _e("for accessing this test.", 'watupro')?> <b><?php printf(__('Note that users with rights to manage %s will always be able to access them for free.', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></b><br>
					<input type="checkbox" name="pay_always" value="1" <?php if(!empty($dquiz->pay_always)) echo 'checked'?> onclick="this.checked ? jQuery('#wtpPayDecrease').show() : jQuery('#wtpPayDecrease').hide();"> <?php _e('Mark payment as used after each quiz attempt (so if multiple attempts are allowed the user will have to pay each time. <b>This is always true for non-logged in users: they can take quiz only once with each payment. This setting is not valid for bundle payments!</b>)', 'watupro')?></p>
					<div style="margin-left:50px;display:<?php echo empty($dquiz->pay_always) ? 'none' : 'block'; ?>" id="wtpPayDecrease">
						<select name="attempts_price_change_action" onchange="this.value == 'reduce' ? jQuery('#priceActionDir').text('<?php _e('below', 'watupro');?>') : jQuery('#priceActionDir').text('<?php _e('above', 'watupro');?>');">
							<option value="reduce"><?php _e('Reduce', 'watupro');?></option>
							<option value="increase" <?php if(!empty($advanced_settings['attempts_price_change_action']) and $advanced_settings['attempts_price_change_action'] == 'increase') echo 'selected'?>><?php _e('Increase', 'watupro');?></option>
						</select>
						<?php printf(__('the payment amount with %1$s %2$s on each subsequent attempt.', 'watupro'), get_option("watupro_currency"), 
							'<input type="text" size="6" name="attempts_price_change_amt" value="'.@$advanced_settings['attempts_price_change_amt'].'">');?> <br /> 
						<?php printf(__('The price will never go %1$s %2$s %3$s', 'watupro'), 
							'<span id="priceActionDir">'.(@$advanced_settings['attempts_price_change_action'] != 'increase' ? __('below', 'watupro') : __('above', 'watupro')).'</span>', 	
							get_option("watupro_currency"),
							'<input type="text" size="6" name="attempts_price_change_limit" value="'.@$advanced_settings['attempts_price_change_limit'].'">');?>
					</div>
					<p><?php printf(__("If you leave zero in the box above, there will be no charge for users to access the %s. To manage your payments settings go to ", 'watupro'), WATUPRO_QUIZ_WORD)?> <a href="admin.php?page=watupro_options"><?php _e("WatuPRO Settings", 'watupro')?></a></p>
					<?php do_action('watupro_exam_payment_settings', @$dquiz);?>
					
					<div id="WatuPROFreeAccessDiv" style='display:<?php echo @$dquiz->fee ? 'block' : 'none';?>'>
						<h4><?php printf(__('%s-Specific Instructions', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD));?></h4>
						<p><?php _e('Here you can define some optional text that will be shown before any payments buttons. HTML is supported.', 'watupro');?>
						<b><?php _e('Note that when such text is provided, the default message "There is a fee to access..." will not be shown.', 'watupro');?></b> <br>
						<textarea name="payment_instructions" rows="3" cols="60"><?php echo stripslashes(rawurldecode(@$advanced_settings['payment_instructions']));?></textarea></p>
						
						<p><?php printf(__('URL to redirect if the %s is not paid for:', 'watupro'), WATUPRO_QUIZ_WORD);?> <input type="text" name="paid_quiz_redirect" size="60" value="<?php echo empty($advanced_settings['paid_quiz_redirect']) ? '' : $advanced_settings['paid_quiz_redirect'];?>"><br />
						<?php printf(__('This is useful if you are selling access to the %s from a bundle or through WooCommerce, etc, and not from the %s page itself.', 'watupro'),
							WATUPRO_QUIZ_WORD, WATUPRO_QUIZ_WORD);?></p>						
						
						
						<h4><?php _e('Free access', 'watupro');?></h4>
						<?php if($use_wp_roles == 1):?>
							<p><?php _e('Access remains free of charge for the following user roles (showing only roles that do not have management access):', 'watupro');?>
								<?php foreach($roles as $key => $role):
									$r = get_role($key);
									if($r->has_cap('watupro_manage_exams') or $r->has_cap('manage_options')) continue;?>
									<span style="white-space:none;"><input type="checkbox" name="free_access_roles[]" value="<?php echo $key?>" <?php if(@in_array($key, @$advanced_settings['free_access_roles'])) echo 'checked'?>> <?php echo stripslashes($r->name)?></span>
								<?php endforeach;?>							
							</p>
						<?php else:?>
							<p><?php _e('Access remains free to the following user groups:', 'watupro');?>
							<?php if(!empty($groups) and count($groups)):
								foreach($groups as $group):?>
								<span style="white-space:none;"><input type="checkbox" name="free_access_groups[]" value="<?php echo $group->ID?>" <?php if(@in_array($group->ID, @$advanced_settings['free_access_groups'])) echo 'checked'?>> <?php echo stripslashes($group->name)?></span>
							<?php endforeach; 
							else: printf(__('You have not created any <a href="%s" target="_blank">user groups</a> yet.', 'watupro'), 'admin.php?page=watupro_groups');?></p>
						<?php endif; // end if count groups
						endif; // end if using groups?>
						
						<?php if(class_exists('WatuPROBPBridge') and method_exists('WatuPROBPBridge','free_access_options')):
							WatuPROBPBridge :: free_access_options($advanced_settings);
						endif;?>
					</div>	   
		   </div>
		   
		   <?php if(count($editors) > 1 and $more_roles and current_user_can('manage_options')):?>
			   <!--  Quiz editor -->
			   <h3><?php printf(__('%s Owner', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD));?></h3>
			   
			   <p><?php printf(__('This settings matters most if you select "Manage only %s created by users" on the <a href="%s" target="_blank">page that lets you fine-tune role settings</a>. Admin can always access all tests.', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL, 'http://localhost/wordpress/wp-admin/admin.php?page=watupro_multiuser');?></p>
			   
			   <p><?php _e('Choose owner:', 'watupro');?> <select name="editor_id">
			   	<?php foreach($editors as $editor):?>
			   		<option value="<?php echo $editor->ID?>" <?php if(!empty($dquiz->ID) and $dquiz->editor_id == $editor->ID) echo 'selected'?>><?php echo $editor->user_login;?></option>
			   	<?php endforeach;?>
			   </select></p>
			<?php endif;?>   
			
			<h3><?php _e('Runtime Configuration For Logged In Users', 'watupro');?></h3>
			
			<p><?php printf(__('This feature has been deprecated and we do not guarantee it will work properly. Please use the free <a href="%s" target="_blank">Chained Logic</a> addon to achieve the same in a lot more elegant way.', 'watupro'), 'http://blog.calendarscripts.info/chained-quiz-logic-free-add-on-for-watupro/');?></p>
			
			<p><a href="#" onclick="jQuery('#runTimeDeprecated').show();return false;"><?php _e('I understand that I will not get tech support for this feature but still want to use it.', 'watupro');?></a></p>
			
			<div id="runTimeDeprecated" style="display:none;">
				<p><?php printf(__('You can configure premature %s ending or not allow the user to continue if they do not meet certain threshold of correct answers duing the %s. <b>These settings are available only for %s paginated "each question on its own page"</b>.', 'watupro'), WATUPRO_QUIZ_WORD, WATUPRO_QUIZ_WORD, WATUPRO_QUIZ_WORD_PLURAL);?></p>
				
				<div id="WatuPROPrematureQuizEnd" style='display:<?php echo (empty($dquiz->ID) or $dquiz->single_page==WATUPRO_PAGINATE_ONE_PER_PAGE) ? 'block' : 'none';?>'>
					<p><?php printf(__('Leave the below boxes empty (or enter zeros) to not configure any run time logic. If you configure any of these logics the setting "Automatically store user progress as they go from page to page" will automatically be set to true at the time of saving the %s.', 'watupro'), WATUPRO_QUIZ_WORD);?></p>
					<h4><?php printf(__('Premature %s ending','watupro'), WATUPRO_QUIZ_WORD);?></h4>
					<?php printf(__('Finalize the %s if user answers correctly less than %s%% of the questions. Start performing this check after question number %s (min 1).', 'watupro'),
						WATUPRO_QUIZ_WORD, '<input type="text" name="premature_end_percent" value="'.(empty($advanced_settings['premature_end_percent']) ? 0 : $advanced_settings['premature_end_percent']).'" size="3" maxlength="3">',
						'<input type="text" name="premature_end_question" value="'.(empty($advanced_settings['premature_end_question']) ? 0 : $advanced_settings['premature_end_question']).'" size="4" maxlength="4">');?>
						
					<p><?php printf(__('In case of premature %s end, display this message on the "Final screen" before the other content:', 'watupro'), WATUPRO_QUIZ_WORD);?></p>
					<p><?php wp_editor(empty($advanced_settings['premature_text']) ? '' : stripslashes(rawurldecode($advanced_settings['premature_text'])), 'premature_text');?></p>	
				</div>		
					
				<div id="WatuPROPreventForward" style='display:<?php echo (empty($dquiz->disallow_previous_button) and (empty($dquiz->ID) or $dquiz->single_page==WATUPRO_PAGINATE_ONE_PER_PAGE)) ? 'block' : 'none';?>'>	
					<h4><?php _e('Prevent moving forward','watupro');?></h4>	
					<?php printf(__('Do not allow the user to submit or continue the quiz if they answer correctly less than %s%% of the questions. Start performing this check after question number %s (min 1).', 'watupro'),
						'<input type="text" name="prevent_forward_percent" value="'.(empty($advanced_settings['prevent_forward_percent']) ? 0 : $advanced_settings['prevent_forward_percent']).'" size="3" maxlength="3">',
						'<input type="text" name="prevent_forward_question" value="'.(empty($advanced_settings['prevent_forward_question']) ? 0 : $advanced_settings['prevent_forward_question']).'" size="4" maxlength="4">');?>
					<p><strong><?php printf(__('This check will not be performed on the last question. If you do not want the user to be able to submit the %s even on the last question, you can add an unimportant survey-type question as last one.','watupro'), WATUPRO_QUIZ_WORD);?></strong></p>	
				</div>
			</div><!-- end deprecated runtime logic div -->
			
			<p>&nbsp;</p>
			<label><?php printf(__('Set %s mode:', 'watupro'), WATUPRO_QUIZ_WORD)?></label> <select name="mode" onchange="this.value == 'practice' ? jQuery('#wtpPracticeExplain').show() : jQuery('#wtpPracticeExplain').hide();">
				<option value="live"<?php if(empty($dquiz->mode) or $dquiz->mode=='live') echo " selected"?>><?php printf(__('Live %s', 'watupro'), WATUPRO_QUIZ_WORD)?></option>		
				<option value="practice"<?php if(!empty($dquiz->mode) and $dquiz->mode=='practice') echo " selected"?>><?php _e('Practice mode', 'watupro')?></option>
			</select>	
			<span id="wtpPracticeExplain" style='display:<?php echo (!empty($dquiz->mode) and $dquiz->mode=='practice') ? 'inline' : 'none';?>'>
				<a href="http://blog.calendarscripts.info/practice-mode-exams-in-watupro/" target="_blank"><?php _e("What's this?", 'watupro')?></a>
			</span>
			<br><br>	
    </div>
</div>  

<script type="text/javascript" >
jQuery(function(){
	<?php if(sizeof($other_exams)):?>
		// store the clean dependency row in a var from the beginnign
		var dependencySpan = jQuery('#dependencySpan').html();
		var rowNum = 0;
		WatuPRODep.depsToDel = [];
	
		jQuery('#requieLoginChk').bind('click', function(){			
			if(jQuery(this).attr('checked')) {
				jQuery('#dependencyWarning').hide();
				jQuery('#WatuPROPaymentWarning').hide();
				jQuery('#dependencyDiv').show();
				jQuery('#WatuPROPaymentDiv').show();
			}
			else {
				jQuery('#dependencyWarning').show();
				jQuery('#WatuPROPaymentWarning').show();
				jQuery('#dependencyDiv').hide();
				jQuery('#WatuPROPaymentDiv').hide();
			}	
		});
		
		jQuery('#addDependencyButton').bind('click', function(){
			if(jQuery('#dependExam').val()=='') {
				alert("<?php _e('Please select exam', 'watupro')?>");
				return false;
			}		
			
			// check for duplicate
			var hasDuplicate = false;
			jQuery('.watupro-depend-exam option:selected').each(function(){
				if(this.value == jQuery('#dependExam').val())
				{
					hasDuplicate = true;
				}
			});	
			
			if(hasDuplicate) {
				alert("<?php _e('You already have this dependency!', 'watupro')?>");
				return false;
			}
			
			// add new row
			rowNum++;
			
			// replace the IDs
			html = dependencySpan.replace('dependExam', 'dependExam'+rowNum);			
			html = html.replace('dependPoints', 'dependPoints'+rowNum);
			html = html.replace('dependMode', 'dependMode'+rowNum);
			
			// button
			var but = "<span class='submit'><input type='button' value='<?php _e("Delete", 'watupro')?>' onclick=\"jQuery('#dependencyRow"+rowNum+"').remove();\"></span>";
			
			jQuery('#dependencyDiv').append("<div id='dependencyRow" + rowNum + "'>" + html + but + "</div>");
			
			// set values
			jQuery('#dependExam'+rowNum).val(jQuery('#dependExam').val());
			jQuery('#dependPoints'+rowNum).val(jQuery('#dependPoints').val());
			jQuery('#dependMode'+rowNum).val(jQuery('#dependMode').val());
			
			// add class used for duplicate dependency check
			jQuery('#dependExam'+rowNum).addClass("watupro-depend-exam");
			
			jQuery('#dependExam').val('');
			jQuery('#dependPoints').val('0');
		});
		
		// mark or restore dependency for deletion
		WatuPRODep.del = function(mode, id)
		{
			if(mode) {
				// to delete
				WatuPRODep.depsToDel.push(id);
				jQuery('#delDependencies').val(WatuPRODep.depsToDel.join(","));
				jQuery('#oldDependencyRow'+id).addClass('watupro-for-removal');
				jQuery('#delDepBtn'+id).hide();
				jQuery('#restoreDepBtn'+id).show();
			}
			else {
				// to restore
				WatuPRODep.depsToDel = jQuery.grep(WatuPRODep.depsToDel, function(value){
						return value != id;
					});
				jQuery('#delDependencies').val(WatuPRODep.depsToDel.join(","));
				jQuery('#oldDependencyRow'+id).removeClass('watupro-for-removal');				
				jQuery('#delDepBtn'+id).show();
				jQuery('#restoreDepBtn'+id).hide();
			}
		}
	<?php endif;?>
});

function watuPROChangePagination_i(val) {
		if(val == '<?php echo WATUPRO_PAGINATE_ONE_PER_PAGE?>') {
			jQuery('#WatuPROPrematureQuizEnd').show();
			if(jQuery('#examForm input[name=disallow_previous_button]').is(':checked')) jQuery('#WatuPROPreventForward').hide();
			else jQuery('#WatuPROPreventForward').show();
		}
		else {
			jQuery('#WatuPROPrematureQuizEnd').hide();
			jQuery('#WatuPROPreventForward').hide();
		}
} // end watuPROChangePagination_i
</script>