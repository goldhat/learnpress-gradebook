<div class="wrap">
	<h1><?php _e('Manage multi-user configurations in Watu PRO', 'watupro')?></h1>
	
	<?php if(empty($enabled_roles)):?>
		<p><?php printf(__('To edit this page you need to enable some roles to manage exams on the <a href="%s" target=_blank">Watu PRO Settings page</a>.', 'watupro'), 'admin.php?page=watupro_options')?></p>
		</div>
	<?php return false;
	endif;?>
	
	<form method="post">
		<div class="watupro">
		<p><?php _e('Please select role to configure:', 'watupro')?> <select name="role_key" onchange="this.form.submit();">
			<option value=""><?php _e('- Please select role -', 'watupro')?></option>
			<?php foreach($enabled_roles as $role):?>
				<option value="<?php echo $role?>" <?php if(!empty($_POST['role_key']) and $_POST['role_key'] == $role) echo 'selected'?>><?php echo $role?></option>
			<?php endforeach;?>
		</select></p>
		
		<?php if(!empty($_POST['role_key'])):
			$settings = @$role_settings[$_POST['role_key']];?>
			<p><label><?php printf(__('%s access:', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD_PLURAL))?></label> <select name="exams_access" onchange="this.value == 'view_approve' ? jQuery('#restrictEmailing').show() : jQuery('#restrictEmailing').hide();">
				<option value="all" <?php if(!empty($settings['exams_access']) and $settings['exams_access'] == 'all') echo "selected"?>><?php printf(__('Manage all %s','watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></option>
				<option value="own" <?php if(!empty($settings['exams_access']) and $settings['exams_access'] == 'own') echo "selected"?>><?php printf(__('Manage only %s created by the user','watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></option>
				<option value="view" <?php if(!empty($settings['exams_access']) and $settings['exams_access'] == 'view') echo "selected"?>><?php _e('Only view results','watupro')?></option>
				<option value="view_approve" <?php if(!empty($settings['exams_access']) and $settings['exams_access'] == 'view_approve') echo "selected"?>><?php _e('View and edit/approve results','watupro')?></option>
				<option value="no" <?php if(!empty($settings['exams_access']) and $settings['exams_access'] == 'no') echo "selected"?>><?php printf(__('No access to manage %s','watupro'), WATUPRO_QUIZ_WORD_PLURAL)?></option>
			</select> <input type="checkbox" name="apply_usergroups" value="1" <?php if(!empty($settings['apply_usergroups'])) echo 'checked'?>> <?php _e('Apply user group / user role category restrictions', 'watupro')?></p>
			
			<p id="restrictEmailing" style='display: <?php echo (!empty($settings['exams_access']) and $settings['exams_access'] == 'view_approve') ? 'block' : 'none';?>'>
				<input type="checkbox" name="view_approve_restrict_emailing" value="1" <?php if(!empty($settings['view_approve_restrict_emailing'])) echo 'checked'?>>	 <?php _e(__('Do not allow sending email with the edited results'), 'watupro');?>		
			</p>
			
			<p><label><?php _e('Certificates access:', 'watupro')?></label> <select name="certificates_access">
				<option value="all" <?php if(!empty($settings['certificates_access']) and $settings['certificates_access'] == 'all') echo "selected"?>><?php _e('Manage all certificates','watupro')?></option>
				<option value="own" <?php if(!empty($settings['certificates_access']) and $settings['certificates_access'] == 'own') echo "selected"?>><?php _e('Manage only certificates created by the user','watupro')?></option>
				<option value="no" <?php if(!empty($settings['certificates_access']) and $settings['certificates_access'] == 'no') echo "selected"?>><?php _e('No access to manage certificates','watupro')?></option>
			</select></p>
			
			<p><label><?php printf(__('%s categories access:', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD))?></label> <select name="cats_access">
				<option value="all" <?php if(!empty($settings['cats_access']) and $settings['cats_access'] == 'all') echo "selected"?>><?php _e('Manage all categories','watupro')?></option>
				<option value="own" <?php if(!empty($settings['cats_access']) and $settings['cats_access'] == 'own') echo "selected"?>><?php _e('Manage only categories created by the user','watupro')?></option>
				<option value="no" <?php if(!empty($settings['cats_access']) and $settings['cats_access'] == 'no') echo "selected"?>><?php _e('No access to manage categories','watupro')?></option>
			</select></p>
			
			<p><label><?php _e('User group access:', 'watupro')?></label> <select name="usergroups_access">
				<option value="all" <?php if(!empty($settings['usergroups_access']) and $settings['usergroups_access'] == 'all') echo "selected"?>><?php _e('Manage all user groups','watupro')?></option>			
				<option value="no" <?php if(!empty($settings['usergroups_access']) and $settings['usergroups_access'] == 'no') echo "selected"?>><?php _e('No access to manage user groups','watupro')?></option>
			</select></p>
			
			<p><label><?php _e('Question categories access:', 'watupro')?></label> <select name="qcats_access">
				<option value="all" <?php if(!empty($settings['qcats_access']) and $settings['qcats_access'] == 'all') echo "selected"?>><?php _e('Manage all question categories','watupro')?></option>
				<option value="own" <?php if(!empty($settings['qcats_access']) and $settings['qcats_access'] == 'own') echo "selected"?>><?php _e('Manage only question categories created by the user','watupro')?></option>
				<option value="no" <?php if(!empty($settings['qcats_access']) and $settings['qcats_access'] == 'no') echo "selected"?>><?php _e('No access to manage question categories','watupro')?></option>
			</select></p>
			
			<p><label><?php _e('Settings page access:', 'watupro')?></label> <select name="settings_access">
				<option value="all" <?php if(!empty($settings['settings_access']) and $settings['settings_access'] == 'all') echo "selected"?>><?php _e('Manage settings','watupro')?></option>
				<option value="no" <?php if(!empty($settings['settings_access']) and $settings['settings_access'] == 'no') echo "selected"?>><?php _e('No access to manage settings','watupro')?></option>				
			</select></p>
			
			<p><label><?php printf(__('All %s results page:', 'watupro'), WATUPRO_QUIZ_WORD)?></label> <select name="alltest_access">
				<option value="all" <?php if(!empty($settings['alltest_access']) and $settings['alltest_access'] == 'all') echo "selected"?>><?php _e('Has access','watupro')?></option>
				<option value="no" <?php if(!empty($settings['alltest_access']) and $settings['alltest_access'] == 'no') echo "selected"?>><?php _e('No access','watupro')?></option>				
			</select></p>
			
			<p><label><?php _e('Manage discount codes:', 'watupro')?></label> <select name="coupons_access">
				<option value="all" <?php if(!empty($settings['coupons_access']) and $settings['coupons_access'] == 'all') echo "selected"?>><?php _e('Manage all discount codes','watupro')?></option>
				<option value="own" <?php if(!empty($settings['coupons_access']) and $settings['coupons_access'] == 'own') echo "selected"?>><?php _e('Manage only discount codes created by the user','watupro')?></option>
				<option value="no" <?php if(!empty($settings['coupons_access']) and $settings['coupons_access'] == 'no') echo "selected"?>><?php _e('No access to manage discount codes','watupro')?></option>
			</select></p>
			
			<p><label><?php _e('Manage bundles:', 'watupro')?></label> <select name="bundles_access">
				<option value="all" <?php if(!empty($settings['bundles_access']) and $settings['bundles_access'] == 'all') echo "selected"?>><?php _e('Manage all bundles','watupro')?></option>
				<option value="own" <?php if(!empty($settings['bundles_access']) and $settings['bundles_access'] == 'own') echo "selected"?>><?php _e('Manage only bundles created by the user','watupro')?></option>
				<option value="no" <?php if(!empty($settings['bundles_access']) and $settings['bundles_access'] == 'no') echo "selected"?>><?php _e('No access to manage bundles','watupro')?></option>
			</select></p>
			
			<p><label><?php _e('Help page access:', 'watupro')?></label> <select name="help_access">
				<option value="all" <?php if(!empty($settings['help_access']) and $settings['help_access'] == 'all') echo "selected"?>><?php _e('See Help page','watupro')?></option>
				<option value="no" <?php if(!empty($settings['help_access']) and $settings['help_access'] == 'no') echo "selected"?>><?php _e('No access to Help page','watupro')?></option>				
			</select></p>
			
			<p><input type="checkbox" name="hide_myexams" value="1" <?php if(!empty($settings['hide_myexams'])) echo 'checked'?>> <?php printf(__('Hide "My %s" menu', 'watupro'), WATUPRO_QUIZ_WORD_PLURAL);?></p>
			
			<p><input type="submit" value="<?php _e('Save configuration for this role','watupro')?>" name="config_role" class="button-primary"></p>
		<?php endif;?>
		</div>
	</form>
</div>