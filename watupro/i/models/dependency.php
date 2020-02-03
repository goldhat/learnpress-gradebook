<?php
class WatuPRODependency {
	// store dependencies on a given exam
	static function store($exam_id) {
		global $wpdb;
		
		// delete old dependencies if there are any to delete
		if(!empty($_POST['del_dependencies'])) {
			$wpdb->query("DELETE FROM ".WATUPRO_DEPENDENCIES." WHERE ID IN (".$_POST['del_dependencies'].")");
		}
		
		// select remaining old and update them ($_POST vars will have names postfixed with _id)
		$dependencies = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRO_DEPENDENCIES."
			WHERE exam_id=%d", $exam_id));
		
		foreach($dependencies as $dependency) {
			$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_DEPENDENCIES." SET
			depend_exam=%d, depend_points=%d, mode=%s WHERE ID=%d", $_POST['dependency'.$dependency->ID], 
				$_POST['depend_points'.$dependency->ID], $_POST['depend_mode'.$dependency->ID], $dependency->ID));  	
		}			
		
		// add new dependencies if any
		if(!empty($_POST['dependencies']) and is_array($_POST['dependencies'])) {
			foreach($_POST['dependencies'] as $cnt => $dependency) {
				// skip 1st because this is the "Add new dependency" row that shouldn't be added
				if($cnt==0) continue;				
				
				$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_DEPENDENCIES." SET
					exam_id=%d, depend_exam=%d, depend_points=%d, mode=%s", 
					$exam_id, $dependency, $_POST['depend_points'][$cnt], $_POST['depend_modes'][$cnt]));
			}
		}
	}
	
	// select existing dependencies
	static function select($exam_id) {
		global $wpdb;
		
		$dependencies = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRO_DEPENDENCIES." 
			WHERE exam_id=%d ORDER BY ID", $exam_id));
			
		return $dependencies;	
	}
	
	// check dependencies on exam
	static function check($exam, &$dependency_message) {
		global $wpdb, $user_ID;
		
		// make sure exam requires login, otherwise just return true		
		if(!$exam->require_login) return true;
		
		// if user is admin return true        
		if(current_user_can('WATUPRO_MANAGE_CAPS')) return true;
				
		// now check if there are any dependencies, if not - return true
		$dependencies = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRO_DEPENDENCIES."
			WHERE exam_id=%d ORDER BY ID", $exam->ID));
		if(!sizeof($dependencies)) return true;	
		
		$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
		// all or any?
		$dependency_type = (empty($advanced_settings['dependency_type']) or $advanced_settings['dependency_type'] == 'all') ? 'all' : 'any';
		
		// if there are unsatisfied dependencies return false 
		// 1. select takings of this person
		$takings = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRO_TAKEN_EXAMS." WHERE user_id=%d AND in_progress=0 ORDER BY ID DESC", $user_ID));
	
		// finally, let's check
		foreach($dependencies as $dependency) {
			$satisfied = false;

			foreach($takings as $taking) {
				$taking_compare = $taking->points;
				if(!empty($dependency->mode) and $dependency->mode == 'percent') $taking_compare = $taking->percent_correct;
				if(!empty($dependency->mode) and $dependency->mode == 'percent_points') $taking_compare = $taking->percent_points;
				
				if($taking->exam_id == $dependency->depend_exam and $taking_compare >= $dependency->depend_points) {
					$satisfied = true;
					
					// 'any' mode? (i.e. just one satisfied dependency is enough)
					if($dependency_type == 'any') return true;
				}
			}

			// if satisfied still false no need to check further
			if(!$satisfied and $dependency_type != 'any') {				
				// output info for this dependency
				ob_start();
				$_REQUEST['exam_id'] = $exam->ID;
				self :: lock_details('noexit');
				$dependency_message = ob_get_clean();
				return false;
			}
		} 
		
		// in "any" mode if we reach this point, this means no dependency has been satisfied
		if($dependency_type == 'any') {
			ob_start();
			$_REQUEST['exam_id'] = $exam->ID;
			self :: lock_details('noexit');
			$dependency_message = ob_get_clean();
			return false;
		}
		// else mode is "all" and since we were not thrown until this point, we are all OK
		return true;		
	}
	
	// calculate dependencies on a list of exams to display "Locked" message for these that
	// need to be taken before this one.
	static function mark($exams, $takings) {
		global $wpdb;
		
		// select all dependencies if any
		$dependencies = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}watupro_dependencies ORDER BY ID");
		
		// now for each exam check if there are dependencies and if even one is not satisfied, made locked
		foreach($exams as $cnt => $exam) {
			$locked = false;
			
			foreach($dependencies as $dependency) {
				if($dependency->exam_id != $exam->ID) continue;
				
				// we have dependency, we set locked = true
				// now let's check if it's satisfied by loop through $takings 
				// if yes, unlock				
				$locked = true;
				foreach($takings as $taking) {
					// satisfying taking found, unlock
					if(!empty($dependency->mode) and $dependency->mode == 'percent') $taking_compare = $taking->percent_correct;
					else $taking_compare = $taking->points;
				
					if($taking->exam_id == $dependency->depend_exam and $taking_compare >= $dependency->depend_points) $locked = false;
				}					
			}
			
			$exams[$cnt]->locked = $locked;
		}
		
		return $exams;
	}
	
	// shows details on specific locked exam
	static function lock_details($exit = false) {
		global $wpdb, $user_ID;
		
		// select advanced settings
		$advanced_settings = $wpdb->get_var($wpdb->prepare("SELECT advanced_settings FROM ".WATUPRO_EXAMS." 
			WHERE ID=%d", $_REQUEST['exam_id']));
		$advanced_settings = unserialize(stripslashes($advanced_settings));
		
		
		$dependencies = $wpdb->get_results($wpdb->prepare("SELECT tE.name as exam, tE.final_screen as final_screen, tD.* 
				FROM {$wpdb->prefix}watupro_dependencies tD JOIN {$wpdb->prefix}watupro_master tE
				ON tD.depend_exam = tE.ID WHERE exam_id=%d
				ORDER BY tD.ID", $_REQUEST['exam_id']));
				
		// get my takings and figure out dependency status
		$takings=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}watupro_taken_exams
				WHERE user_id=%d AND in_progress=0 ORDER BY ID DESC", $user_ID));	
				
		foreach($dependencies as $cnt=>$dependency) {
			$satisfied = false;
			foreach($takings as $taking) {
				if(!empty($dependency->mode) and $dependency->mode == 'percent') $taking_compare = $taking->percent_correct;
					else $taking_compare = $taking->points;
				if($taking->exam_id == $dependency->depend_exam and $taking_compare >= $dependency->depend_points) $satisfied = true;
			}
			
			$dependencies[$cnt]->satisfied = $satisfied;
		}		
		
		if(@file_exists(get_stylesheet_directory().'/watupro/i/lock_details.php')) require get_stylesheet_directory().'/watupro/i/lock_details.php';
		else require WATUPRO_PATH."/i/views/lock_details.php";
		if($exit != 'noexit') exit;
	}
}