<?php
// class to handle manual grading of exams
class WatuPROITeacher {
	 // saves the grading details
	 // probably send email to student with the results
	 static function edit_details($exam, $taking, $answers) {
	 		global $wpdb, $user_ID;
	 		$multiuser_access = WatuPROIMultiUser::check_access('exams_access');
	 		$user_grade_ids = array(); // used in personality quizzes (Intelligence module)
	 		
	 		// check access	
			if($multiuser_access == 'view' || $multiuser_access == 'group_view') wp_die(__('You can only view results, not edit them.', 'watupro'));
			if($multiuser_access == 'own' and $exam->editor_id != $user_ID) wp_die(__('You are not allowed to access this page.', 'watupro'));
			
			// check if emailing is disallowed
			if($multiuser_access == 'view_approve') $restrict_emailing = WatuPROIMultiUser::check_access('view_approve_restrict_emailing', true);
		
	 		// if exam calculates grades by % of points we have to select all questions from the $answers
	 		// to match their q_answers and calculate the max points
	 		// $max_points += WTPQuestion::max_points($ques);
	 		$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
	 		$max_points = 0;
	 		$qids = array(0);
 			foreach($answers as $answer) $qids[] = $answer->question_id;
 			$questions = $wpdb->get_results("SELECT * FROM ".WATUPRO_QUESTIONS." WHERE ID IN (".implode(',', $qids).")");
 			$_watu = new WatuPRO();
 			$_watu->match_answers($questions, $exam);	 	
 			foreach($questions as $question) $max_points += WTPQuestion::max_points($question);			
	 		
	 		// update each answer
	 		$total_points = $total_answers = $total_question_answers = $correct_answers = $percent_correct = $wrong_answers = 0;
	 		foreach($answers as $answer) {
				 $wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_STUDENT_ANSWERS." SET
				 	points=%s, is_correct=%d, teacher_comments = %s WHERE id=%d", 
				 		$_POST['points'.$answer->ID], @$_POST['is_correct'.$answer->ID], 
				 		$_POST['teacher_comments'.$answer->ID], $answer->ID));
				 	$total_points += $_POST['points'.$answer->ID];
				 	$total_answers++;
				 	if(!$answer->is_survey) $total_question_answers++;
				 	if(!empty($_POST['is_correct'.$answer->ID])) $correct_answers++;
				 	else {
				 		// wrong answers will increase only if the answer was not empty.
				 		// num empty cannot be changed from teacher
				 		if(!empty($answer->answer) and !$answer->is_survey) $wrong_answers++;
				 	}
				 	
				 // change file?
				 if(!empty($_FILES['file-answer-'.$answer->question_id]['tmp_name'])) {				 	
				 		WatuPROFileHandler :: upload_file($answer->question_id, $answer->ID, $answer->taking_id);
				 }	
	 		}
	 		
	 		// now recalculate percent correct
	 		if($total_question_answers==0) $percent_correct=0;
			else $percent_correct = number_format($correct_answers / $total_question_answers * 100, 2);
		
			if($max_points == 0) $pointspercent = 0;
			else $pointspercent = number_format($total_points / $max_points * 100, 2);

			WatuPROCertificate :: $user_id = $taking->user_id; // assign the user ID so multi-quiz certificate is calculated correctly
			$GLOBALS['watupro_taking_id'] = $taking->ID; 
			list($grade, $certificate_id, $do_redirect, $grade_obj) 
				= WTPGrade::calculate($exam->ID, $total_points, $percent_correct, 0, null, $pointspercent);
			
			list($catgrades, $catgrades_array) = WTPGrade::replace_category_grades($exam->final_screen, $taking->ID, $exam->ID, $exam->email_output);
			
			// assign grade - DEPENDS ON CATEGORY behavior. If quiz will calculate final grade based on category performance, then we'll calculate after categories 
			if(!empty($advanced_settings['final_grade_depends_on_cats']) and empty($exam->reuse_default_grades)) {				
				list($grade, $certificate_id, $do_redirect, $grade_obj) = WTPGrade::calculate_dependent($exam->ID, $catgrades_array, $total_points, $percent_correct, $user_grade_ids, $pointspercent, $certificate_id);
			}	
			
			$grade_title = empty($grade_obj->gtitle) ? __('None', 'watupro') : $grade_obj->gtitle;	
				
			// update taking details	
			$_POST['teacher_comments']=''; // for now empty
			$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_TAKEN_EXAMS." SET
				points=%s, result=%s, grade_id=%d, percent_correct=%d, teacher_comments=%s, last_edited=%s, 
				percent_points=%d, catgrades_serialized=%s, num_correct=%d, num_wrong=%d
				WHERE id=%d",
				$total_points, $grade, @$grade_obj->ID, $percent_correct, 
				$_POST['teacher_comments'], date('Y-m-d', current_time('timestamp')), $pointspercent, serialize($catgrades_array), 
				$correct_answers, $wrong_answers, $taking->ID));
				
			// add student certificate			
			if($taking->user_id and !empty($certificate_id)) {
				$certificate = "";
				 foreach($certificate_id as $cert_id) {
			   	$certificate .= WatuPROCertificate::assign($exam, $taking->ID, $cert_id, $taking->user_id);
			   }
			}
			
			do_action('watupro_completed_exam_edited', $taking->ID);
			
			// send email to the user
			if(!empty($_POST['send_email']) and (empty($restrict_emailing) or $restrict_emailing != 'restrict')) {
				 $subject = stripslashes($_POST['subject']);
				 $message = wpautop(stripslashes($_POST['msg']));
				 
				 // replace vars
				 $subject = str_replace("%%QUIZ_NAME%%", $exam->name, $subject);
				 $message = str_replace("%%QUIZ_NAME%%", $exam->name, $message);
				 
				 $time_spent = WTPRecord :: time_spent_human( WTPRecord :: time_spent($taking));
				 
				 // replace other vars from final screen
				 $message = str_replace("%%CORRECT%%", $correct_answers, $message);			 
				 $message = str_replace("%%WRONG%%", $wrong_answers, $message);
				 $message = str_replace("%%EMPTY%%", __('n/a', 'watupro'), $message);
				 $message = str_replace("%%ATTEMPTED%%", __('n/a', 'watupro'), $message);
				 $message = str_replace("%%TOTAL%%", $total_answers, $message);
				 $message = str_replace("%%POINTS%%", $total_points, $message);
				 $message = str_replace("%%POINTS-ROUNDED%%", round($total_points), $message);
				 $message = str_replace("%%PERCENTAGE%%", $percent_correct, $message);
				 $message = str_replace("%%GRADE%%", $grade, $message);
				 $message = str_replace("%%GTITLE%%", @$grade_obj->gtitle, $message);
				 $message = str_replace("%%GDESC%%", @$grade_obj->gdescription, $message);
				 $message = str_replace("%%DATE%%", date(get_option('date_format'), strtotime($taking->date)), $message);
				 $message = str_replace("%%EMAIL%%", $_POST['email'], $message);
				 $message = str_replace("%%CERTIFICATE%%", @$certificate, $message);
				 $message = str_replace("%%CERTIFICATE_ID%%", @implode(', ', @$certificate_id), $message);
				 $message = str_replace("%%CATGRADES%%", $catgrades, $message);
				 $message = str_replace("%%START-TIME%%", date_i18n(get_option('date_format'), strtotime($taking->start_time)), $message);
				 $message = str_replace("%%END-TIME%%", date_i18n(get_option('date_format'), strtotime($taking->end_time)), $message);
				 $message = str_replace("%%TIME-SPENT%%", $time_spent, $message);
				 
				 $avg_points = $avg_percent = '';
				 if(strstr($message, '%%AVG-POINTS%%') or strstr($exam->email_output, '%%AVG-POINTS%%')) $avg_points = WatuPROTaking :: avg_points($taking->ID, $exam->ID);
				 if(strstr($message, '%%AVG-PERCENT%%') or strstr($exam->email_output, '%%AVG-PERCENT%%')) $avg_percent = WatuPROTaking :: avg_percent($taking->ID, $exam->ID); 
				 $message = str_replace("%%AVG-POINTS%%", $avg_points, $message);
				 $message = str_replace("%%AVG-PERCENT%%", $avg_percent, $message);
				 
				 $message = str_replace('%%ADMIN-URL%%', admin_url("admin.php?page=watupro_takings&exam_id=".$exam->ID."&taking_id=".$taking->ID), $message);
				 
				 // user info shortcodes?
				 $message = str_replace('user_id="quiz-taker"', 'user_id='.$taking->user_id, $message);
				 
				 if(strstr($message, "%%ANSWERS%%")) {
				 		// prepare answers table
				 		$answers_table = "<table border='1' cellpadding='4'><tr><th>".__('Question', 'watupro')."</th><th>".
				 			__('Answer(s) given', 'watupro')."</th><th>".__('Points', 'watupro').
				 			"</th><th>".__('Is Correct?', 'watupro')."</th><th>".__('Comments', 'watupro')."</th></tr>";
				 			
						foreach($answers as $answer) {
							 $answers_table.= "<tr><td>".wpautop(stripslashes($answer->question))."</td><td>".
							 	wpautop(stripslashes($answer->answer))."</td><td>".$_POST['points'.$answer->ID].
							 	"</td><td>".(@$_POST['is_correct'.$answer->ID]?__('yes', 'watupro'):__('no','watupro'))."</td><td>".
							 	wpautop(stripslashes($_POST['teacher_comments'.$answer->ID]))."</td></tr>";
						}				 			
				 			
				 		$answers_table.="</table>";	
				 		
				 		$message = str_replace("%%ANSWERS%%", $answers_table, $message);
				 }
				 
				 // now do send
				 // $headers  = 'MIME-Version: 1.0' . "\r\n";
				 $headers = 'Content-type: text/html; charset=utf8' . "\r\n";
				 $headers .= 'From: '. watupro_admin_email() . "\r\n";
				 $message = apply_filters('watupro_content', stripslashes($message));
				 // echo $message;		
				 $output='<html><head><title>'.$subject.'</title>
				 </head>	<html><body>'.$message.'</body></html>';		
				 
				 wp_mail($_POST['email'], $subject, $output, $headers);
				 
				 // update options to reuse subject & message next time
				 update_option('watupro_manual_grade_subject', $_POST['subject']);
				 update_option('watupro_manual_grade_message', $_POST['msg']);
				 
			} // end sending mail
	 }
}