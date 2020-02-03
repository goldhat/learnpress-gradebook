<?php
class WatuPROReportShortcodes {
	static function report($attr) {
		global $user_ID;
		
		$content = '';		
		watupro_vc_scripts();
		
		// reports for who?
		$user_id = @$attr[1];		
		if(empty($user_id)) $user_id = $user_ID;		
		if(empty($user_id)) return __('This content is only for logged in users', 'watupro');
		
		$type = @$attr[0];
		$type = strtolower($type);
		if(!in_array($type, array("overview", "tests", "skills", "history"))) $type = 'overview';		
		
		ob_start();
		switch($type) {
			case 'overview': WTPReports::overview($user_id, false); break;
			case 'tests': WTPReports::tests($user_id, false); break;
			case 'skills': WTPReports::skills($user_id, false, $attr); break;
			case 'history': WTPReports::history($user_id, false); break;
		}
		
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	// stats per question
	static function per_question($atts) {
		$_GET['exam_id'] = empty($atts[0]) ? 0 : intval($atts[0]);
		
		ob_start();
		WatuPROStats :: per_question(true, $atts);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	// stats per question
	static function per_category($atts) {
		$_GET['exam_id'] = empty($atts[0]) ? 0 : intval($atts[0]);
		
		ob_start();
		WatuPROStats :: per_category(true, $atts);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	// all question answers (details of stats per question)
	static function all_answers($atts) {
		$_GET['exam_id'] = empty($atts[0]) ? 0 : intval($atts[0]);
		$_GET['id'] = empty($atts[1]) ? 0 : intval($atts[1]);
		
		ob_start();
		WatuPROStats :: all_answers(true);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	static function chart_by_grade($atts) {
		$_GET['exam_id'] = empty($atts[0]) ? 0 : intval($atts[0]);
		
		ob_start();
		WatuPROStats :: chart_by_grade(true, $atts);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	// displays poll-like results per question
	static function poll($atts) {	
		global $wpdb;	
		$question_id = @$atts['question_id'];		
		$mode = empty($atts['mode']) ? 'answers' : $atts['mode']; // correct or answers
		$correct_color = empty($atts['correct_color']) ? 'green' : $atts['correct_color'];
		$wrong_color = empty($atts['wrong_color']) ? 'red' : $atts['wrong_color'];
		$show_user_choice = empty($atts['user_choice']) ? '' : sanitize_text_field($atts['user_choice']);		
		if($show_user_choice == 'CHECK') $show_user_choice = '&#x1F5F9;';
		
		// select question
		$question = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_QUESTIONS." WHERE ID=%d", $question_id));		
		if(empty($question)) return __('n/a', 'watupro');
		
		// mode can be "answers" only if the question is checkbox or radio
		if($question->answer_type!='checkbox' and $question->answer_type!='radio') $mode = 'correct';
		
		$taking_id = @$GLOBALS['watupro_taking_id'];
		if(empty($taking_id)) $taking_id = @$GLOBALS['watupro_view_taking_id'];
		if(empty($taking_id)) $show_user_choice = '';
		
		ob_start();
		
		if($mode == 'correct') {
			$poll = WatuPROStats :: poll_correct($question->ID);
			// chart	
			$num_wrong = $poll['total'] - $poll['correct'];
			$percent_wrong = 100 - $poll['percent'];
			
			if(!empty($show_user_choice)) {								
				// is the user answer correct or wrong here?
				$is_correct = $wpdb->get_var($wpdb->prepare("SELECT is_correct FROM " . WATUPRO_STUDENT_ANSWERS. "
					WHERE question_id=%d AND taking_id=%d", $question_id, $taking_id));
			}			
			
         if(@file_exists(get_stylesheet_directory().'/watupro/reports/poll-chart-correct.html.php')) require get_stylesheet_directory().'/watupro/reports/poll-chart-correct.html.php';
	      else include(WATUPRO_PATH."/modules/reports/views/poll-chart-correct.html.php");
		}
		else {
			if(!empty($show_user_choice)) {
				// is the user answer correct or wrong here?
				$user_answer = $wpdb->get_var($wpdb->prepare("SELECT answer FROM " . WATUPRO_STUDENT_ANSWERS. "
					WHERE question_id=%d AND taking_id=%d", $question_id, $taking_id));
			}					
			
			// showing poll-like stats where num / % is matched to each answers
			$answers = WatuPROStats :: poll_answers($question);
			if(@file_exists(get_stylesheet_directory().'/watupro/reports/poll-chart-answers.html.php')) require get_stylesheet_directory().'/watupro/reports/poll-chart-answers.html.php';
	      else include(WATUPRO_PATH."/modules/reports/views/poll-chart-answers.html.php");
		}
		
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	// barchart per question category from individual quiz taking
	static function user_category_chart($atts) {
		ob_start();
		
		WTPReportsUser :: chart_by_category($atts);
		$content = ob_get_clean();
		
		return $content;		
	}
	
	// points or percent correct per question category - from all takings of a given user
	static function qcat_total($atts) {
		$content = WatuPROStats :: qcat_total($atts);
		return $content;
	} // end qcat_total
	
	// number of taken tests in category or total
	static function taken_tests($atts) {
		$content = WatuPROStats :: taken_tests($atts);
		return $content;
	}
	
	// pie chart per question category from individual quiz taking
	// $atts: radius, show = points/percent
	static function pie_category_chart($atts) {
		ob_start();
		
		WTPReportsUser :: pie_by_category($atts);
		$content = ob_get_clean();
		
		return $content;		
	}
}