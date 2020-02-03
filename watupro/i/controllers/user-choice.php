<?php
// handles quizzes that let user choose questions
class WatuPROIUserChoice {
   // called from watupro_shortcode to load either the form with selections 
   // or do_shortcode passing question IDs
   static function load($exam) {
      global $wpdb, $user_ID;
      $advanced_settings = unserialize(stripslashes($exam->advanced_settings));
      $q_exam_id = (watupro_intel() and $exam->reuse_questions_from) ? $exam->reuse_questions_from : $exam->ID;
      if(empty($q_exam_id)) $q_exam_id = 0;
      $quiz_id = $exam->ID;
      
      if(!empty($_POST['wtpuc_ok'])) {
    		// if user selected, set the vars in session 		
    		$_SESSION['wtpuc_'.$quiz_id] = $_POST; 		
   	}	      
      
      // select categories and / or questions and return the selector
   	$cats = $wpdb->get_results("SELECT * FROM ".WATUPRO_QCATS."
   		WHERE ID IN (SELECT cat_id FROM ".WATUPRO_QUESTIONS." WHERE exam_id IN ($q_exam_id))");
   		
   	// add uncategorized?
   	$num_uncat = $wpdb->get_var("SELECT COUNT(ID) FROM ".WATUPRO_QUESTIONS."
   		WHERE exam_id IN ($q_exam_id) AND cat_id=0 AND is_inactive=0 ");
   	if($num_uncat) $cats[] = (object)array("ID"=>0, "name" => __('Uncategorized', 'wtpuc'));
   	   	
   	if(empty($_POST['wtpuc_ok'])) {
   		// add questions and group?
   		$questions = $wpdb->get_results("SELECT ID, question, cat_id FROM ".WATUPRO_QUESTIONS."
   				WHERE exam_id IN ($q_exam_id) AND is_inactive=0");
   				
   		// match them to cats
   		foreach($cats as $cnt => $cat) {
   				$cat_questions = array();
   				foreach($questions as $question) {
   					if($question->cat_id == $cat -> ID) $cat_questions[] = $question;
   				}
   				
   				$cats[$cnt]->questions = $cat_questions;
   				$cats[$cnt]->num_questions = sizeof($cat_questions);
   		}
   		
         // num questions in the quiz
         $total_questions = $wpdb->get_var("SELECT COUNT(ID) FROM ".WATUPRO_QUESTIONS."
            WHERE exam_id IN ($q_exam_id) ANd is_inactive=0");   		
   		
   		ob_start();
   		if(@file_exists(get_stylesheet_directory().'/watupro/i/user-choice.html.php')) require get_stylesheet_directory().'/watupro/i/user-choice.html.php';
			else require WATUPRO_PATH."/i/views/user-choice.html.php";
   		$content = ob_get_clean();		
   	}	
   	else {		
   		// get question IDs from session or select X questions per category
   		$question_ids_str = '';

   		// define the mode - it comes from $_POST but must also be allowed by $advanced_settings. 
   		$mode = 'random_questions';
   		if(@$_POST['watupro_mode'] == 'per_category') $mode = 'per_category';
   		if(@$_POST['watupro_mode'] == 'keywords') $mode = 'keywords';
   		$qids = array();
   		
   		switch($mode) {   		
   		   case 'keywords':
   		       $keyword_sql = '';
   		       $cnt = 0;
   		       foreach($_POST['watupro_keywords'] as $keyword) {
   		          $keyword = sanitize_text_field($keyword);
   		          if(empty($keyword)) continue;
   		          if($cnt) $keyword_sql .= " OR ";
   		          $keyword_sql .= " tQ.question LIKE '%$keyword%' ";
   		          $cnt++;
   		       }
   		       
                $questions = $wpdb->get_results("SELECT ID FROM " . WATUPRO_QUESTIONS . " tQ
   		          WHERE exam_id IN ($q_exam_id)  AND is_inactive=0 AND ($keyword_sql) ORDER BY RAND()");
   		      
   		       foreach($questions as $question) $qids[] = $question->ID;   
   		       $question_ids_str = implode(",", $qids);	
      			 $_SESSION['wtpuc_'.$quiz_id]['sel_questions'] = $qids;   
   		   break;
   		   case 'per_category':
      			foreach($cats as $cat) {				
      				if(!empty($_POST['num_questions_'.$cat->ID]) and is_numeric($_POST['num_questions_'.$cat->ID])) {
      					$questions = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".WATUPRO_QUESTIONS."
      						WHERE exam_id IN ($q_exam_id) AND cat_id=%d AND is_inactive=0 
      						ORDER BY RAND() LIMIT %d", $cat->ID, $_POST['num_questions_'.$cat->ID]));
      					foreach($questions as $question) $qids[] = $question->ID;	
      				}
      			}				
      			if(empty($qids[0])) $qids[0] = -1;
      			$question_ids_str = implode(",", $qids);	
      			$_SESSION['wtpuc_'.$quiz_id]['sel_questions'] = $qids;
   		   break;
   		   case 'random_questions':
   		       // select X random questions from the whole quiz. X comes from post. 
   		       // If empty or outside the admin-defined boundaries, set X = min. questions or max. questions 
   		       $num = empty($_POST['num_questions']) ? 0 : intval($_POST['num_questions']);
   		       $min = intval(@$advanced_settings['user_choice_min']);
   		       if($min > 0 and $num < $min) $num = $min;   		       
   		       $max = intval(@$advanced_settings['user_choice_max']);
   		       if($max > 0 and $num > $max) $num = $max;
   		       
   		       $questions = $wpdb->get_results($wpdb->prepare("SELECT ID FROM " . WATUPRO_QUESTIONS . " tQ
   		          WHERE exam_id IN ($q_exam_id)  AND is_inactive=0 
   		          ORDER BY RAND() LIMIT %d", $num));
   		          
   		       foreach($questions as $cnt => $question) {
   		          if($cnt) $question_ids_str .= ',';
                   $question_ids_str .= $question->ID;   
                   $qids[] = $question->ID;		          
   		       }  
   		       $_SESSION['wtpuc_'.$quiz_id]['sel_questions'] = $qids;
   		   default:
   		   break;
   		}   		
   		
   		// if in some case no questions are found, select at least one question from the quiz
   		if(empty($question_ids_str)) {
   		   $random_question = $wpdb->get_var("SELECT ID FROM ".WATUPRO_QUESTIONS." 
   		    WHERE exam_id IN ($q_exam_id) AND is_inactive=0");
   		   if(empty($random_question)) $random_question = 1;
   		   $question_ids_str = $random_question; 
   		}    
   		
   		$content = do_shortcode('[watupro '.$quiz_id.' question_ids="'.$question_ids_str.'"]');
   	}		
   	
   	return $content;
   } // end load()
}