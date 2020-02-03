<?php
class WatuPracticeController {	
	// shows exam in practice mode
	static function show($exam) {
		global $wpdb, $post;
		$_watu=new WatuPRO();
		$_question = new WTPQuestion();
		$_question->practice_mode = true;
		
		if(!is_single() and !empty($GLOBALS['watupro_client_includes_loaded'])) { #If this is in the listing page - and a quiz is already shown, don't show another.
			printf(__("Please go to <a href='%s'>%s</a> to view this test", 'watupro'), get_permalink(), get_the_title());
			return false;
		} 
		
		if(!WTPUser::check_access($exam, $post)) return false;
		
		// output one main div for the answers
		echo "<div id='watuPracticeFeedback'></div><form id='watuPROPracticeForm".$exam->ID."'>";
		
		// select all questions order by rand
		$questions = WTPQuestion::select_all($exam);
		$_watu->match_answers($questions, $exam);				
		foreach($questions as $qct=>$ques):			
				$display=($qct==0)?"block":"none";
				echo "<div id='questionDiv{$ques->ID}' style='display:$display'>";		 	
			 	echo $_question->display($ques, $qct+1, 1, null);		 	         
	    	echo "</div>";
		endforeach;
		
		echo "</form><div align='center' id='watuPROCheckButton'><input type='button' value=".__('Check', 'watupro')." onclick='WatuPROPractice.submit();'></div>";
			
		// output the javascript
		?>
		<script type="text/javascript" >
		document.addEventListener('DOMContentLoaded', function(event) {
			WatuPROPractice.curID=<?php echo $questions[0]->ID?>;
			WatuPROPractice.allIDs=[<?php foreach($questions as $question): echo $question->ID.","; endforeach;?>];
			WatuPROPractice.examID=<?php echo $exam->ID?>;
			WatuPRO.siteURL="<?php echo admin_url( 'admin-ajax.php' ); ?>";
		});	
		</script>
		<?php
	}
	
	// submits exam in practice mode
	static function submit() {
		global $wpdb;
		$_question = new WTPQuestion();
	
		$ansArr = is_array( @$_POST["answer"] )? $_POST["answer"] : array(@$_POST["answer"]);  
		$output = "";  
		
		// select question
		$question=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_QUESTIONS." WHERE id=%d", $_POST['id']));  
		
		// select answers to this question
		$answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRO_ANSWERS." 
			WHERE question_id=%d ORDER BY ID, sort_order", $_POST['id']));
		$final_answers=array();
		
		$user_answer=false;
		foreach($answers as $answer) {			
			$user_answer=false;
			if($question->answer_type!='textarea' and in_array($answer->ID, $ansArr)) $user_answer=true;						
			if($user_answer) $final_answers[]=array("answer"=>$answer->answer, "is_correct"=>$answer->correct);
		}
		
		// for textareas we only check whether the whole question is correct
		if($question->answer_type=='textarea') {
			list($p, $textarea_is_correct) = WTPQuestion :: calc_answer($question, $ansArr, $answers);
			$final_answers[]=array("answer"=>nl2br($ansArr[0]), "is_correct" => $textarea_is_correct);
		}
				
		// now display these answers
		$output .= "<div class='watupro-practice'>";
		$output .= "<p>".__("You answered:", 'watupro')."</p><ul>";
		foreach($final_answers as $answer) {
			$correct_class=$answer['is_correct']?"correct-answer":"";
			$output .= "<li class='answer user-answer $correct_class'><span class='answer'>".stripslashes($answer['answer'])."</span></li>";
		}
		
		// for gaps and sort just echo it
		if($question->answer_type=='gaps' or $question->answer_type == 'sort' 
			or $question->answer_type == 'matrix' or $question->answer_type == 'nmatrix'  or $question->answer_type == 'slider') {
     		require_once(WATUPRO_PATH."/i/models/question.php");
     		$question->q_answers = $answers;     		
     		list($points, $answer_text) = WatuPROIQuestion::process($question, $ansArr);     		
     		$output .= $answer_text;	
     }
	
		// if textarea AND empty final answers, just display user's answer
		if($question->answer_type=='textarea' and !sizeof($final_answers)) {
			$output .= "<li class='answer user-answer correct-answer'><span>".wpautop(stripslashes($ansArr[0]), 0)."</span></li>";
		}
		
		$output .= "</ul>";
		
		// now display question feedback if any
		if(!empty($question->explain_answer)) {	$output .= "<hr>";
			$question->q_answers = $answers;
			list($points, $is_correct) = WTPQuestion::calc_answer($question, $ansArr, $answers);
			$output .= $_question->answer_feedback($question, $is_correct, $ansArr, $points);
		}
		$output .= "</div>";
		
		$output = apply_filters('watupro_content', $output);
		echo $output;	
		exit;
	}
}