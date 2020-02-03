<?php
// enhanced show_exam
function watuproi_show_exam($view, $exam) {
	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));	
	
	if(@file_exists(get_stylesheet_directory().'/watupro/i/show-exam.html.php')) return get_stylesheet_directory().'/watupro/i/show-exam.html.php';
	else return WATUPRO_PATH."/i/views/show-exam.html.php";
}

// will evaluate % correctly answered questions and whether we have to continue, disallow or premature finish the quiz
// the output from this function will be a string like "continue", "end" or "stop" and will be used by the JS function WatuPROIntel.runTimeLogic
function watuproi_evaluate_on_the_fly($taking_id) {
	global $wpdb;
	
	// first select the exam & its advanced settings
	$exam = $wpdb->get_row($wpdb->prepare("SELECT tE.ID, advanced_settings FROM ".WATUPRO_EXAMS." tE
		JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tT.exam_id = tE.ID 
		WHERE tT.ID=%d", $taking_id));
	$advanced_settings = unserialize(stripslashes($exam->advanced_settings));	
	
	// select % correct in this taking
	$total_answers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
		JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id AND tQ.is_survey=0
		WHERE tA.taking_id=%d", $taking_id));
	if(empty($total_answers)) return true; // avoid division by zero
		
	 $correct_answers	= $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
	 	JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id AND tQ.is_survey=0
		WHERE tA.taking_id=%d AND tA.is_correct=1", $taking_id));
		
	$percent = round(100 * $correct_answers / $total_answers);	
		
	// know what to do giving advantage to the premature end setting
	if($advanced_settings['premature_end_question'] <= $_POST['current_question']) {
		if($advanced_settings['premature_end_percent'] > $percent) {
			// must end the quiz
			echo "end";
			exit;
		}
	} // end premature end checks
	
	// disallow continue?
	if($advanced_settings['prevent_forward_question'] <= $_POST['current_question']) {
		if($advanced_settings['prevent_forward_percent'] > $percent) {
			echo "stop";
			exit;
		}
	}
	
	// defaults to continue
	echo "continue";
}