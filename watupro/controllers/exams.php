<?php
// lists the user dashboard of exams
// @param string $passed_cat_ids - allows to pass cat IDs by a shortcode to further restrict the number of cat IDs that are used
function watupro_my_exams($passed_cat_ids = "", $orderby = "tE.ID", $status = 'all', $in_shortcode = false, $reorder_by_latest_taking = false, $atts = null) {
	global $wpdb, $user_ID, $post;	
	
	// admin can see this for every student
	if(!empty($_GET['user_id']) and current_user_can(WATUPRO_MANAGE_CAPS)) $user_id = $_GET['user_id'];
	else $user_id = $user_ID;
		
	$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE ID=%d", $user_id));
	
	// delete all results of this user?
	$multiuser_access = 'all';
	if(watupro_intel() and !empty($_GET['user_id'])) $multiuser_access = WatuPROIMultiUser::check_access('exams_access');
	if(!empty($_GET['del_all_results']) and (current_user_can(WATUPRO_MANAGE_CAPS) or $user_id == $user_ID)) {		
		if($multiuser_access != 'all' and $user_id != $user_ID) return false;
		
		// delete all records from takings and student_answers tables
		$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPRO_STUDENT_ANSWERS." WHERE user_id=%d", $user->ID));
		$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPRO_TAKEN_EXAMS." WHERE user_id=%d", $user->ID));
		
		do_action('watupro_deleted_user_data', $user->ID);
		
		$redirect_url = ($user_id == $user_ID) ? "admin.php?page=my_watupro_exams" : "admin.php?page=my_watupro_exams&user_id=".$user_id;
		if($in_shortcode) $redirect_url = get_permalink($post->ID);
		watupro_redirect($redirect_url);
	}
			
	// select what categories I have access to 
	$current_user = wp_get_current_user();
	$cat_ids = WTPCategory::user_cats($user_id);
	
	if(!empty($passed_cat_ids)) {
		$passed_cat_ids = explode(",", $passed_cat_ids);
		$cat_ids = array_intersect($cat_ids, $passed_cat_ids);
	}
	
	$cat_id_sql=implode(",",$cat_ids);
	
	list($my_exams, $takings, $num_taken) = WTPExam::my_exams($user_id, $cat_id_sql, $orderby, $reorder_by_latest_taking);
	
	// intelligence dependencies	
	if(watupro_intel()) {
		require_once(WATUPRO_PATH."/i/models/dependency.php");
		$my_exams = WatuPRODependency::mark($my_exams, $takings);	
	}
	
	$num_to_take = sizeof($my_exams) - $num_taken;
	$dateformat = get_option('date_format');
	
	if(!empty($_GET['export_results']) and (current_user_can(WATUPRO_MANAGE_CAPS) or $user_id == $user_ID)) {		
		if($multiuser_access != 'all' and $user_id != $user_ID) return false;
		
		WTPRecord :: export_my_exams($my_exams, $takings, $num_taken);
		exit;
	}
	
	wp_enqueue_script('thickbox',null,array('jquery'));
	wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
	wp_enqueue_style('style.css', plugins_url().'/watupro/style.css', null, '1.0');
	
	if($in_shortcode) {
		// called in shortcode
		$permalink = get_permalink($post->ID);
		$params = array('view_details' => 1);
		$target_url = add_query_arg( $params, $permalink );
	}
	   
	if(@file_exists(get_stylesheet_directory().'/watupro/my_exams.php')) require get_stylesheet_directory().'/watupro/my_exams.php';
	else require WATUPRO_PATH."/views/my_exams.php";   
}

// exams controller object
class WatuPROExams {
	// show the Question X of Y text and progress bar only if we don't show progress bar
	static function show_qXofY($qct, $total, $advanced_settings, $pos = 'bottom') {
		// the showing position defaults to bottom		
		if(empty($advanced_settings['show_progress_bar']) and $pos == 'top') return '';
      
      if(empty($advanced_settings['show_xofy']) and isset($advanced_settings['show_xofy'])) return '';		
		
		return  "<p class='watupro-qnum-info'>".sprintf(__("Question %d of %d", 'watupro'), $qct, $total)."</p>";
	}
	
	// shows progress bar
	static function progress_bar($questions, $exam, $in_progress = null) {
		// handle $total_pages based on different paginations
		switch($exam->single_page) {
			case WATUPRO_PAGINATE_PAGE_PER_CATEGORY:
				$cat_ids = array();
				foreach($questions as $question) {
					if(!in_array($question->cat_id, $cat_ids)) $cat_ids[] = $question->cat_id;
				}
				$total_pages = sizeof($cat_ids);
			break;
			case WATUPRO_PAGINATE_ONE_PER_PAGE:
				$total_pages = sizeof($questions);
			break;
			case WATUPRO_PAGINATE_CUSTOM_NUMBER:
				$total_pages = ceil( sizeof($questions) / $exam->custom_per_page );	
			break;
			case WATUPRO_PAGINATE_ALL_ON_PAGE:
			default:
				return '';
			break;
		}		
				
		$init_width = round(100 / $total_pages);
		
		$progress = '<div id="watupro-progress-container-'.$exam->ID.'" class="watupro-progress-container">
  				<div class="watupro-progress-bar" id="watupro-progress-bar-'.$exam->ID.'" style="width:'.$init_width.'%;">&nbsp;';
  		
		$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
		if(!empty($advanced_settings['progress_bar_percent'])) {
			$progress .= '<span class="watupro-progress-percent" id="watupro-progress-bar-percent-'.$exam->ID.'">'.$init_width.'%</span>';
		}		
  				
  			$progress .= '</div>
		</div>';

		
		$progress .= '<input type="hidden" value="'.$total_pages.'" id="watupro-progress-bar-pages-'.$exam->ID.'">';
		
		return $progress;
	}
}

// advanced exam settings
function watupro_advanced_exam_settings() {
	global $wpdb;
	
	// select exam
	$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", intval($_GET['exam_id'])));
	
	if(empty($exam->ID)) {
		echo "<div class='inside'><p>".sprintf(__('This tab will become available after the %s is created.', 'watupro'), __('quiz', 'watupro'))."</p></div>"; 
		return false;
	}	

	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
	$exam_id = empty($exam->reuse_questions_from) ? $exam->ID : $exam->reuse_questions_from;
	
	// select question categories
	$qcats = $wpdb->get_results("SELECT tC.* FROM ".WATUPRO_QCATS." tC
		JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.cat_id = tC.ID
		JOIN ".WATUPRO_EXAMS." tE ON tE.ID = tQ.exam_id AND tE.ID IN ($exam_id)
		GROUP BY tC.ID ORDER BY tQ.sort_order, tQ.ID, tC.name");
		
	// shall we add parent categories?
	if(!empty($advanced_settings['sum_subcats_catgrades'])) {
		$final_cats = array();
		$final_cat_ids = array();
		foreach($qcats as $cat) {
			$final_cat_ids[] = $cat->ID;
			if($cat->parent_id and !in_array($cat->parent_id, $final_cat_ids)) {				
				$parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_QCATS." WHERE ID=%d", $cat->parent_id));				
				if(empty($parent->ID)) continue;
				$final_cats[] = $parent;
				$final_cat_ids[] = $parent->ID;
			}
			// after possibly assigning the parent, add the cat to $final_cats
			$final_cats[] = $cat;
		} // end foreach cats
		$qcats = $final_cats;
	}	// end adding empty parent categories when summing up subcategory performance is selected	
		
	// regroup categories by main category
	$regrouped_cats = $regrouped_cat_ids = array();
	foreach($qcats as $qcat) {
		if(empty($qcat->parent_id)) {
			$regrouped_cats[] = $qcat;
			$regrouped_cat_ids[] = $qcat->ID;
			foreach($qcats as $sub) {
				if($sub->parent_id == $qcat->ID) {
					$regrouped_cats[] = $sub;
					$regrouped_cat_ids[] = $sub->ID;
				}
			}
		}
	}

	// are there categories that are still not included (orphan subcats?)
	// we'll add them here	
	foreach($qcats as $qcat) {
		if(!in_array($qcat->ID, $regrouped_cat_ids)) $regrouped_cats[] = $qcat;
	}
	
	$qcats = $regrouped_cats;
		
	// any uncategorized questions?
	$num_uncategozied = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_QUESTIONS."
		WHERE exam_id=%d", $exam->ID));	
	if($num_uncategozied) $qcats[] = (object)array("ID"=>0, "name"=>__('Uncategorized', 'watupro'));	
	
	if(!empty($_POST['ok'])) {
		// save advanced config
		unset($_POST['ok']);
		
		// add sorted categories
		$sorted_cats = array();
		foreach($qcats as $qcat) {
			$sorted_cats[rawurlencode($qcat->name)] = intval($_POST['qcat_order_'.$qcat->ID]);
		}
		
		$advanced_settings['sorted_categories'] = $sorted_cats;
		$advanced_settings['sorted_categories_encoded'] = 1; // we need this flag because in the older versions we did not encode
		$advanced_settings['confirm_on_submit'] = empty($_POST['confirm_on_submit']) ? 0 : 1;	
		$advanced_settings['no_checkmarks'] = empty($_POST['no_checkmarks']) ? 0 : 1;
		$advanced_settings['no_checkmarks_unresolved'] = empty($_POST['no_checkmarks_unresolved']) ? 0 : 1;
		$advanced_settings['feedback_unresolved'] = empty($_POST['feedback_unresolved']) ? 0 : 1;
		$advanced_settings['reveal_correct_gaps'] = empty($_POST['reveal_correct_gaps']) ? 0 : 1;
		$advanced_settings['dont_prompt_unanswered'] = empty($_POST['dont_prompt_unanswered']) ? 0 : 1;		
		$advanced_settings['dont_prompt_notlastpage'] = empty($_POST['dont_prompt_notlastpage']) ? 0 : 1;
		$advanced_settings['dont_load_inprogress'] = empty($_POST['dont_load_inprogress']) ? 0 : 1;
		$advanced_settings['email_not_required'] = @$_POST['email_not_required'];
		$advanced_settings['show_only_snapshot'] = empty($_POST['show_only_snapshot']) ? 0 : 1;
		$advanced_settings['show_result_and_points'] = empty($_POST['show_result_and_points']) ? 0 : 1;
		$advanced_settings['answer_snapshot_in_table_format'] = empty($_POST['answer_snapshot_in_table_format']) ? 0 : 1;
		$advanced_settings['answered_paginator_color'] = sanitize_text_field(@$_POST['answered_paginator_color']);
		$advanced_settings['unanswered_paginator_color'] = sanitize_text_field(@$_POST['unanswered_paginator_color']);		
		$advanced_settings['dont_scroll'] = empty($_POST['dont_scroll']) ? 0 : 1;
		$advanced_settings['dont_scroll_start'] = empty($_POST['dont_scroll_start']) ? 0 : 1;
		$advanced_settings['single_choice_action'] = sanitize_text_field($_POST['single_choice_action']);
		$advanced_settings['unselect'] = empty($_POST['unselect']) ? 0 : 1;
		$advanced_settings['takings_by_email'] = intval($_POST['takings_by_email']);
		foreach($qcats as $cnt=>$qcat) {
			$advanced_settings['qcat_order_'.$qcat->ID]  = intval(@$_POST['qcat_order_'.$qcat->ID]);
			$advanced_settings['random_per_'.$qcat->ID]  = intval(@$_POST['random_per_'.$qcat->ID]);
		}
		$advanced_settings['play_levels'] = @$_POST['play_levels'] ; // restrict to levels from the play plugin
		
		$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_EXAMS." SET advanced_settings=%s WHERE ID=%d",
			serialize($advanced_settings), $exam->ID));
		return true; // becuse $_POST['ok'] is now called from the WatuPRO edit exam page, we'll return here instead of displaying anything	
	}	
	
	// select exam
	$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $exam->ID));
	$exam_id = empty($exam->reuse_questions_from) ? $exam->ID : $exam->reuse_questions_from;
		
	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
	
	// add sort order 
	$sorted_cats = @$advanced_settings['sorted_categories'];	
	
	// print_r($sorted_cats);
	foreach($qcats as $cnt=>$qcat) {
		$def_order = $cnt+1;		
		$qcat_name = $qcat->name; 
		if(!empty($advanced_settings['sorted_categories_encoded'])) $qcat_name = rawurlencode($qcat->name); 
		if(isset($sorted_cats[$qcat_name])) $qcats[$cnt]->sort_order = intval($sorted_cats[$qcat_name]);
		else $qcats[$cnt]->sort_order = $def_order;
	}	
	
	$grades = WTPGrade :: get_grades($exam);	
	
	if(@file_exists(get_stylesheet_directory().'/watupro/advanced-settings.html.php')) require get_stylesheet_directory().'/watupro/advanced-settings.html.php';
	else require WATUPRO_PATH."/views/advanced-settings.html.php";
}