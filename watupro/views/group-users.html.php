<div class="wrap watupro-wrap">
	<h1><?php printf(__('Manage users in group "%s"', 'watupro'), stripslashes($group->name));?></h1>

	<p><?php _e('This page allows you to mass-assign users to a WatuPRO group. You can individually manage user groups for a given users from the Users -> Edit user page.', 'watupro');?></p>
	
	<p><a href="admin.php?page=watupro_groups"><?php _e('Back to user groups', 'watupro');?></a></p>
	
	<form method="post">
		<table class="widefat">
			<tr><th><input type="checkbox" onclick="watuPROSelectUsers(this.checked);"></th><th><?php _e('User ID', 'watupro');?></th><th><?php _e('Username', 'watupro');?></th><th><?php _e('Email', 'watupro');?></th></tr>
			<?php foreach($users as $user):
				$class = ('alternate' == @$class) ? '' : 'alternate';
				$user_groups=get_user_meta($user->ID, "watupro_groups", true);
				if(!is_array($user_groups)) $user_groups = array($user_groups);?>
				<tr class="<?php echo $class?>">
					<td><input type="checkbox" name="uids[]" value="<?php echo $user->ID?>" <?php if(@in_array($group->ID, $user_groups)) echo "checked"?> class="watupro_chk"></td>
					<td><?php echo $user->ID?></td>					
					<td><?php echo $user->user_login?></td>
					<td><?php echo $user->user_email?></td>
				</tr>
			<?php endforeach;?>
		</table>	
		
		<p><input type="submit" value="<?php _e('Assign selected users to the group', 'watupro');?>" name="assign" class="button-primary"></p>
	</form>
	
	<p align="center">
		<?php if($offset > 0):?>
			<a href="admin.php?page=watupro_group_assign&group_id=<?php echo $group->ID?>&offset=<?php echo $offset-100?>"><?php echo _wtpt(__('Previous page', 'watupro'));?></a>
		<?php endif;?>
		
		<?php if($offset + 100 < $cnt_users):?>
			<a href="admin.php?page=watupro_group_assign&group_id=<?php echo $group->ID?>&offset=<?php echo $offset+100?>"><?php echo _wtpt(__('Next page', 'watupro'));?></a>
		<?php endif;?> 
	</p>
</div>

<script type="text/javascript" >
function watuPROSelectUsers(state) {
	if(state) jQuery('.watupro_chk').attr('checked', true);
	else jQuery('.watupro_chk').removeAttr('checked');
}
</script>