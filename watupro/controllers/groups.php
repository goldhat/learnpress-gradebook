<?php
// user groups
function watupro_groups() {
	global $wpdb;
	$groups_table=WATUPRO_GROUPS;
	
	if(!empty($_POST['roles_to_groups'])) {
		update_option('watupro_use_wp_roles', intval(@$_POST['use_wp_roles']));
	}		
		
	if(!empty($_POST['add'])) {
		$wpdb->query($wpdb->prepare("INSERT INTO $groups_table (name, is_def)
			VALUES (%s, %d)", $_POST['name'], intval(@$_POST['is_def'])));
	}
	
	if(!empty($_POST['save'])) {
		$wpdb->query($wpdb->prepare("UPDATE $groups_table SET
			name=%s, is_def=%d WHERE ID=%d", $_POST['name'], intval(@$_POST['is_def']), intval($_POST['id'])));
	}
	
	if(!empty($_POST['del'])) {
		$wpdb->query($wpdb->prepare("DELETE FROM $groups_table WHERE ID=%d",intval($_POST['id'])));
	}
	
	if(!empty($_POST['signup_options'])) {
		update_option('watupro_select_group_on_signup', $_POST['select_group_on_signup']); 
	}
	
	// select current groups
	$groups = $wpdb->get_results("SELECT * FROM $groups_table ORDER BY name");
	
	$use_wp_roles = get_option('watupro_use_wp_roles');	
	
	if(@file_exists(get_stylesheet_directory().'/watupro/groups.php')) require get_stylesheet_directory().'/watupro/groups.php';
	else require WATUPRO_PATH."/views/groups.php";
}

// registers the default groups for everyone, not just for students
// this is required because admin may want to allow other roles also take exams	
// use this function also for setting up default difficulty level
function watupro_register_group($user_id) {
	global $wpdb;
	$groups_table=$wpdb->prefix."watupro_groups";		
		
	// any default groups?
	$groups=$wpdb->get_results("SELECT * FROM $groups_table WHERE is_def=1");
	$gids=array();
	foreach($groups as $group) $gids[]=$group->ID;
	
	// selected group?
	if(!empty($_POST['watupro_user_group']) and get_option('watupro_select_group_on_signup') == 1) {
		$gids[] = $_POST['watupro_user_group'];
	}
	
	update_user_meta($user_id, "watupro_groups", $gids);
	
	// set default difficulty levels
	$user_diff_levels = get_option('watupro_default_user_diff_levels');
	if(!empty($user_diff_levels)) {
		update_user_meta($user_id, "watupro_difficulty_levels", $user_diff_levels);
	}
} // end watupro_register_group


// user profile custom fields functions
// http://wordpress.stackexchange.com/questions/4028/how-to-add-custom-form-fields-to-the-user-profile-page#4029
function watupro_user_fields($user) {
	global $wpdb;

    if(!current_user_can(WATUPRO_MANAGE_CAPS)) return false;

	$groups_table=$wpdb->prefix."watupro_groups";		
	
	$groups=$wpdb->get_results("SELECT * FROM $groups_table ORDER BY name");
	
	$user_groups = get_user_meta(@$user->ID, "watupro_groups", true);
	if(!is_array($user_groups)) $user_groups = array($user_groups);
	?>
	<h3><?php _e("Watu PRO Fields", 'watupro'); ?></h3>
  <table class="form-table">
    <tr>
      <th><label for="phone"><?php _e("User Groups", 'watupro'); ?></label></th>
      <td>
      	<select name="watupro_groups[]" multiple="multiple" size="4">
      	<option>-------------------</option>
      	<?php foreach($groups as $group):
      	if(@in_array($group->ID, $user_groups)) $selected="selected";
      	else $selected="";?>
      		<option value="<?php echo $group->ID?>" <?php echo $selected;?>><?php echo $group->name?></option>
      	<?php endforeach;?>
      	</select> 
    </td>
    </tr>
  
	<?php	
	// if question difficulty level restrictions are applied
	if(get_option('watupro_apply_diff_levels') == '1') {
		// are there any diff levels?
		$diff_levels = stripslashes(get_option('watupro_difficulty_levels'));
		$user_diff_levels=get_user_meta($user->ID, "watupro_difficulty_levels", true);
		// print_r($user_diff_levels);
		if(!empty($diff_levels)) {
			$diff_levels = explode(PHP_EOL, $diff_levels);
			?>
			 <tr>
		      <th><label for="phone"><?php _e("Accessible difficulty levels", 'watupro'); ?></label></th>
		      <td>
		      	<select name="watupro_diff_levels[]" multiple="multiple" size="4">
		      	<option>-------------------</option>
		      	<?php foreach($diff_levels as $level):
		      	$level = trim($level);
		      	if(@in_array($level, $user_diff_levels)) $selected="selected";
		      	else $selected="";?>
		      		<option value="<?php echo $level?>" <?php echo $selected;?>><?php echo $level?></option>
		      	<?php endforeach;?>
		      	</select> 
		    </td>
		    </tr>
			<?php 
		}
	}	
	?>
	</table>
	<?php 	
} // watupro_user_fields()

function watupro_save_extra_user_fields($user_id) {
  $saved = false;  
  if ( current_user_can( WATUPRO_MANAGE_CAPS ) ) {
    update_user_meta( $user_id, 'watupro_groups', watupro_int_array(@$_POST['watupro_groups']) );
	 update_user_meta( $user_id, 'watupro_difficulty_levels', @$_POST['watupro_diff_levels'] );
    $saved = true;
  }
  return true;
}

function watupro_group_field() {
    global $wpdb;
    
    if(get_option('watupro_select_group_on_signup') != '1') return "";
    
    // select user groups
    $groups = $wpdb->get_results("SELECT * FROM ".WATUPRO_GROUPS." ORDER BY name");
    if(!sizeof($groups)) return '';
    ?>
    <p><label><?php _e('User Group:', 'watupro')?></label></p>
    <p><select name="watupro_user_group" class="input">
    	<?php foreach($groups as $group):?>
    		<option value="<?php echo $group->ID?>" <?php if(!empty($_GET['watupro_group_id']) and $_GET['watupro_group_id'] == $group->ID) echo 'selected';?>><?php echo $group->name;?></option>
    	<?php endforeach;?>
    </select></p>
    <?php
}

// get user groups as array - appropriate for Ultimate Member and maybe other plugins
function watupro_get_user_groups() {
    global $wpdb;
   
    $groups = $wpdb->get_results("SELECT ID, name FROM ".WATUPRO_GROUPS." ORDER BY name");
    if(!sizeof($groups)) return '';
    
    $user_groups = array();
    foreach($groups as $group) {
    	 $user_groups[$group->ID] = $group->name;
    }
    
    return $user_groups;
}

function watupro_group_assign() {
	global $wpdb;
	
	// select group
	$group = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_GROUPS." WHERE ID=%d", $_GET['group_id']));
	
	// select all users, alphabetic sorting, 100 per page
	$offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
	$users = get_users( 'orderby=user_login&number=100&offset=' . $offset );
	$result = count_users();
	$cnt_users = $result['total_users'];
	
	// assign users
	if(!empty($_POST['assign'])) {
		$_POST['uids'] = empty($_POST['uids']) ? array() : $_POST['uids'];		
		foreach($users as $user) {
			$user_groups=get_user_meta($user->ID, "watupro_groups", true);
			if(!is_array($user_groups)) $user_groups = array($user_groups);
			
			// group is not yet assigned but must be
			if(in_array($user->ID, $_POST['uids']) and !@in_array($group->ID, $user_groups)) {
				$user_groups[] = $group->ID;
				update_user_meta($user->ID, "watupro_groups", $user_groups);
			}
			
			// group was assigned but must be  not
			if(!in_array($user->ID, $_POST['uids']) and @in_array($group->ID, $user_groups)) {
				foreach($user_groups as $cnt=>$gid) {
					if($gid == $group->ID) unset($user_groups[$cnt]); 
				}
				update_user_meta($user->ID, "watupro_groups", $user_groups);
			}
		}
	} // end assigning
	
	include(WATUPRO_PATH . "/views/group-users.html.php");	
}

// show filter for Namaste! LMS
function watupro_namaste_show_students_filter() {
	global $wpdb;
	
	$use_wp_roles = get_option('watupro_use_wp_roles');
	if($use_wp_roles) return '';
	
	$groups = $wpdb->get_results("SELECT * FROM ".WATUPRO_GROUPS." ORDER BY name");
	if(!count($groups)) return '';
	
	echo "<p>".__('Filter by WatuPRO user group:', 'watupro').' <select name="watupro_group_id" onchange="this.form.submit();">
		<option value=0>'.__('- Any group -', 'watupro').'</option>';
	foreach($groups as $group) {
		$selected = (!empty($_GET['watupro_group_id']) and $_GET['watupro_group_id'] == $group->ID) ? ' selected' : '';
		echo '<option value="'.$group->ID.'"'.$selected.'>' . stripslashes($group->name). '</option>';
	}	
	echo "</select></p>";	
}

// apply filter for Namaste! LMS
function watupro_namaste_students_filter($filter_sql) {
	global $wpdb;
	if(!empty($_GET['watupro_group_id'])) {
		$students = $wpdb->get_results($wpdb->prepare("SELECT tU.ID as ID
			 		FROM {$wpdb->users} tU JOIN ".NAMASTE_STUDENT_COURSES." tS 
			 		ON tS.user_id = tU.ID AND tS.course_id=%d ", $_GET['course_id']));
		$uids = array(0);
		foreach($students as $student) {
			$user_groups = get_user_meta($student->ID, "watupro_groups", true);
			if(!is_array($user_groups)) $user_groups = array($user_groups);
			if(@in_array($_GET['watupro_group_id'], $user_groups)) $uids[] = $student->ID;
		}	 	
		
		$filter_sql .= " AND tU.ID IN (".implode(',', $uids).") ";	
	}
	
	return $filter_sql;
}

// show extra th in Namaste! Students page
function watupro_namaste_students_extra_th() {
	global $wpdb;
	
	$use_wp_roles = get_option('watupro_use_wp_roles');
	if($use_wp_roles) return '';
	
	$groups = $wpdb->get_results("SELECT * FROM ".WATUPRO_GROUPS." ORDER BY name");
	if(!count($groups)) return '';
	
	echo '<th>'.sprintf(__('%s Group (WatuPRO)', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD_PLURAL)).'</th>';
} // end extra_th

// show extra td in Namaste! Students page
function watupro_namaste_students_extra_td($student) {
	global $wpdb;
	
	$use_wp_roles = get_option('watupro_use_wp_roles');
	if($use_wp_roles) return '';
	
	// echo '<td>';
	$groups = $wpdb->get_results("SELECT * FROM ".WATUPRO_GROUPS." ORDER BY name");
	//if(!count($groups)) return '</td>';
	if(!count($groups)) return '';
	
	echo '<td>';
	
	$user_groups = get_user_meta($student->ID, "watupro_groups", true);
	if(empty($user_groups)) return '';
	$groups_str = '';
	foreach($user_groups as $cnt => $gid) {
		foreach($groups as $group) {
			if($gid == $group->ID) {
				if($cnt) $groups_str .= ', ';
				$groups_str .= stripslashes($group->name);
			}
		}
	} // end constructing groups_str
	
	echo $groups_str.'</td>';
} // end extra_th