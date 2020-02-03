<?php
// global stats for the exam - for the moment means reports per question.  
class WatuPROStats {
	static function per_question($in_shortcode = false, $atts = null) {
		global $wpdb;
		
		// select exam
		$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_GET['exam_id']));
		
		// select questions
		$source_id = $exam->reuse_questions_from ? $exam->reuse_questions_from : $exam->ID;
		$questions = $wpdb -> get_results("SELECT * FROM ".WATUPRO_QUESTIONS."
			 WHERE exam_id IN ($source_id) ORDER BY sort_order, ID");
		$qids = array(0);
		foreach($questions as $question) $qids[] = $question->ID;
		$qid_sql = implode(", ", $qids);
		
		// select choices
		$choices = $wpdb->get_results("SELECT * FROM ".WATUPRO_ANSWERS." WHERE question_id IN ($qid_sql)");
		
		$period_sql = '';
		if($in_shortcode) {
		   if(!empty($atts['period']) and $atts['period']=='dates' and !empty($atts['start_date']) and !empty($atts['end_date'])) {
		      $period_sql = $wpdb->prepare(" AND tT.date >= %s AND tT.date <=%s ", $atts['start_date'], $atts['end_date']); 
		   } 
		}
		else {
		   if(!empty($_POST['period']) and $_POST['period']=='dates' and !empty($_POST['start_date']) and !empty($_POST['end_date'])) {
		      $period_sql = $wpdb->prepare(" AND tT.date >= %s AND tT.date <=%s ", $_POST['start_date'], $_POST['end_date']); 
		   } 
		} // end period_sql
		
		// select student answers
		$student_answers = $wpdb->get_results($wpdb->prepare("SELECT tA.ID as ID, tA.user_id as user_id,
			tA.exam_id as exam_id, tA.taking_id as taking_id, tA.question_id as question_id, tA.answer as answer,
			tA.points as points, tA.is_correct as is_correct
			FROM ".WATUPRO_STUDENT_ANSWERS." tA 
			JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tT.id = tA.taking_id
			WHERE tT.exam_id=%d AND tT.in_progress=0 $period_sql", $exam->ID));
		
		// now do the matches
		foreach($questions as $cnt=>$question) {
			$question_choices = array();
			$total_answers = $num_correct = 0; // total answers/choices on this question
			$question_answers = $question_correct_answers = 0;
			
			// fill choices along with times and % selected
			foreach($choices as $ct=>$choice) {
				if($choice->question_id != $question->ID) continue;
				
				$choice->times_selected = $choice->percentage = 0;
				
				foreach($student_answers as $answer) {
					if($answer->question_id != $question->ID) continue;
					
					// single answer and textarea correct check
					if(($question->answer_type=='radio' or $question->answer_type=='textarea') 
						and $answer->answer != $choice->answer) continue;
					
					// multiple answer
					if($question->answer_type=='checkbox') {
							$sub_choices = explode(", ", $answer->answer);
							$subchoice_found = false;
							foreach($sub_choices as $sub_choice) {
								if($choice->answer == $sub_choice) {
									$subchoice_found = true;
									break;
								}
							}
							
							if(!$subchoice_found) continue;
					}
					
					$choice->times_selected++;
					if($question->answer_type!='textarea') $total_answers++;
					if($choice->correct) $num_correct++;
				}
				
				$question_choices[] = $choice;
			}
			
			// now calculate the overall stats for the whole question
			$num_unanswered = 0;
			foreach($student_answers as $answer) {				
				if($answer->question_id == $question->ID) {
					if(empty($answer->answer)) $num_unanswered++;
					$question_answers++;
					if($answer->is_correct) $question_correct_answers++;
				}
			}
						
			// now we have all times_selected. Let's calculate % for each choice
			$choices_selected = 0;
			foreach($question_choices as $ct=>$choice) {
				// if total answers is < $question_answers, means we are in textarea question
				// so always choose the bigger
				if($total_answers < $question_answers) $total_answers = $question_answers;								
				
				if($total_answers) $percent = round(($choice->times_selected / $total_answers) * 100);
				else $percent = 0;
				
				$question_choices[$ct]->percentage = $percent;
				$choices_selected += $choice->times_selected;
			}
			
			// add unanswered
			if($num_unanswered) {				
	 			$un_perc = $total_answers ? round(($num_unanswered / $total_answers) * 100) : 0; 
				$question_choices[] = (object)array("answer" => __('Unanswered', 'watupro'), "times_selected"=>$num_unanswered, 
					"percentage"=>$un_perc);
			}
			
			$questions[$cnt]->choices = $question_choices;
			
			if(!$question_answers) $perc_correct = 0;
			else $perc_correct = round(($question_correct_answers / $question_answers) * 100);
			
			$questions[$cnt]->percent_correct = $perc_correct; 
			$questions[$cnt]->num_correct = $question_correct_answers;
			$questions[$cnt]->total_answers = $question_answers;
		}
		
		watupro_enqueue_datepicker();
		$dateformat = get_option('date_format');
		$start_date = empty($_POST['start_date']) ? date("Y-m").'-01' : $_POST['start_date'];
		$end_date = empty($_POST['end_date']) ? date("Y-m-d") : $_POST['end_date'];
		if(@file_exists(get_stylesheet_directory().'/watupro/reports/per-question.php')) require get_stylesheet_directory().'/watupro/reports/per-question.php';
		else require WATUPRO_PATH."/modules/reports/views/per-question.php";
	} // end per_question stats
	
	// shows all answers on a question
	static function all_answers($in_shortcode = false) {
		global $wpdb;
		
		$ob = empty($_GET['ob']) ? "tA.ID" : $_GET['ob'];
		$dir = empty($_GET['dir']) ? "DESC" : $_GET['dir'];
		if(!in_array($dir, array("ASC", "DESC"))) $dir = "DESC";
		$odir=($dir=='ASC')?'DESC':'ASC';
		$offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
		$date_format = get_option('date_format');		
		$page_limit = 20;
		$limit_sql = empty($_GET['export']) ? "LIMIT $offset, $page_limit" : "";
		
		// select exam
		$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_GET['exam_id']));
		
		// select question
		$question = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_QUESTIONS." WHERE ID=%d", $_GET['id']));
		
		// select all user answers joined, ordered and paginated
		$answers = $wpdb->get_results($wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS tA.*, tT.date as date, tT.ip as ip, 
		CONCAT(tT.email, tU.user_email) as email, tU.display_name as display_name
		FROM ".WATUPRO_STUDENT_ANSWERS." tA JOIN ". WATUPRO_TAKEN_EXAMS." tT ON tA.taking_id = tT.ID
		LEFT JOIN {$wpdb->users} tU ON tU.ID = tA.user_id
		WHERE tA.exam_id = %d AND tA.question_id = %d AND tT.in_progress = 0
		ORDER BY $ob $dir $limit_sql", $exam->ID, $question->ID));
		
		$count = $wpdb->get_var("SELECT FOUND_ROWS()");
		
		if(!empty($_GET['export'])) {
			$newline=watupro_define_newline();
			$rows=array();
			$titlerow = __('User name', 'watupro')."\t".__('Email', 'watupro')."\t".__('IP', 'watupro')."\t".__('Date', 'watupro').
				"\t".__('Answer', 'watupro')."\t".__('Points', 'watupro')."\t".__('Is Correct?', 'watupro');
			if(watupro_intel()) $titlerow .= "\t".__('Teacher comments', 'watupro');
			if(!empty($exam->question_hints) and !empty($question->hints)) $titlerow .= "\t".__('Hints used', 'watupro');
			$rows[] = $titlerow;
			
			foreach($answers as $answer) {
				// cleanup
				$answer->answer = str_replace("\t", "    ", $answer->answer);
				$answer->answer = str_replace('"', "'", $answer->answer);
				$answer->teacher_comments = str_replace("\t", "    ", $answer->teacher_comments);
				$answer->teacher_comments = str_replace('"', "'", $answer->teacher_comments);
				$answer->hints_used = str_replace("\t", "    ", $answer->hints_used);
				$answer->hints_used = str_replace('"', "'", $answer->hints_used);
				$answer->hints_used = str_replace("</div>", "; ", $answer->hints_used);
				$answer->hints_used = strip_tags($answer->hints_used);
				
				$row = "";
				$row .= $answer->user_id ? $answer->display_name : __("N/A",'watupro');
				$row .= "\t". ($answer->email ? $answer->email : __("N/A",'watupro'));
				$row .= "\t" . $answer->ip;
				$row .= "\t" . date($date_format, strtotime($answer->date));
				$row .= "\t" . $answer->answer;
				$row .= "\t" . $answer->points;
				$row .= "\t" . ($answer->is_correct ? __('Yes', 'watupro') : __('No', 'watupro'));
				if(watupro_intel()) $row .= "\t" . $answer->teacher_comments;
				if(!empty($exam->question_hints) and !empty($question->hints)) {
					$row .= "\t" . ($answer->num_hints_used ? sprintf(__('%d hints used:', 'watupro'), $answer->num_hints_used)." " . trim($answer->hints_used) : __('No hints used', 'watupro'));
				}	
				
				// remove new lines
				$row = str_replace("\n", " ", $row);
				$row = str_replace("\r", " ", $row);	
				$row = stripslashes($row);			
								
				$rows[] = $row;			
			}
			$csv=implode($newline,$rows);
			
			$now = gmdate('D, d M Y H:i:s') . ' GMT';		
			header('Content-Type: ' . watupro_get_mime_type());
			header('Expires: ' . $now);
			header('Content-Disposition: attachment; filename="exam-'.$exam->ID.'-question-'.$question->ID.'.csv"');
			header('Pragma: no-cache');
			echo $csv;
			exit;
		}
		
		if(@file_exists(get_stylesheet_directory().'/watupro/reports/all-question-answers.html.php')) require get_stylesheet_directory().'/watupro/reports/all-question-answers.html.php';
		else require WATUPRO_PATH."/modules/reports/views/all-question-answers.html.php";
	}
	
	// chart of performance by grade
	static function chart_by_grade($in_shortcode = false, $atts = null) {
		global $wpdb;
		
		// select exam
		$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_GET['exam_id']));
		
		// the code below is the same as in the play plugin controllers/charts.php. Think about a way to re-use it?
		
		// select all completed takings
		$takings = $wpdb->get_results($wpdb->prepare("SELECT tT.ID as ID, tT.points as points, tG.gtitle as grade_title 
			FROM ".WATUPRO_TAKEN_EXAMS." tT JOIN ".WATUPRO_GRADES." tG ON tG.ID = tT.grade_id
			WHERE tT.exam_id=%d AND tT.in_progress=0 ORDER BY tT.points DESC", $exam->ID));
			
		$num_takings = 0;
		$grades_arr = array(); // this array will hold grade_ids(keys) and no. users who got that grade (vals)
		foreach($takings as $taking) {
			$num_takings++;			
			if(isset($grades_arr[$taking->grade_title])) $grades_arr[$taking->grade_title]++;
			else $grades_arr[$taking->grade_title] = 1;
		}	
				
		// now recalculate grade values in %
		foreach($grades_arr as $key=>$val) {
			// avoid division by zero error
			if($num_takings == 0) {
				$grades_arr[$key] = 0;
				continue;
			} 
			
			// now calculate
			$percent = round( 100 * $val / $num_takings);
			$grades_arr[$key] = array("percent"=>$percent, "num_takings"=>$val);  
			if($percent == 0) unset($grades_arr[$key]);
		}
				
		// ksort($grades_arr);				
		WTPReports :: $add_scripts = true;	
		WTPReports :: register_scripts();
		
		$shortcode_size = empty($atts['size']) ? 100 : intval($atts['size']);
		
		if(@file_exists(get_stylesheet_directory().'/watupro/reports/chart-by-grade.html.php')) require get_stylesheet_directory().'/watupro/reports/chart-by-grade.html.php';
		else require WATUPRO_PATH."/modules/reports/views/chart-by-grade.html.php";
		WTPReports :: print_scripts();
	}
	
	// correct/incorrect poll stats
	static function poll_correct($question_id) {
		global $wpdb;
		
		$total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_STUDENT_ANSWERS."
			WHERE question_id=%d", $question_id));
		
		if(!$total) return array('total' => 0, 'correct' => 0, 'percent' => 0);	
			
		$num_correct = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_STUDENT_ANSWERS."
			WHERE question_id=%d AND is_correct", $question_id));	
			
		// calculate percentage
		$perc = round(100 * $num_correct / $total);
		
		return array('total' => $total, 'correct' => $num_correct, 'percent'=> $perc);	
	}
	
	// all answers poll stats
	// this function is used only on single-answer and multiple-answer questions
	static function poll_answers($question) {
		global $wpdb;
		
		$question_id = $question->ID;
		
		// select all answers to this question
		$answers = $wpdb->get_results($wpdb->prepare("SELECT ID, answer, correct FROM ".WATUPRO_ANSWERS." 
			WHERE question_id=%d ORDER BY ID", $question_id));
			
		// select all student answers
		$student_answers = $wpdb->get_results($wpdb->prepare("SELECT answer FROM ".WATUPRO_STUDENT_ANSWERS."
			WHERE question_id=%d AND exam_id=%d AND answer!='' ", $question_id, $question->exam_id));	
			
		// num takings on this whole quiz
		$num_quiz_takings = 0;
					
		// now match
		foreach($answers as $cnt=>$answer) {
			$num_takers = 0;
			foreach($student_answers as $student_answer) {
				if($question->answer_type == 'radio') {
					if($student_answer->answer == $answer->answer) $num_takers++;
				} 
				else {
					// checkbox
					if($student_answer->answer == $answer->answer
						or preg_match("/^".str_replace('/', '\/', $answer->answer).",/", $student_answer->answer)
						or preg_match("/, ".str_replace('/', '\/', $answer->answer).",/", $student_answer->answer)
						or preg_match("/, ".str_replace('/', '\/', $answer->answer)."$/", $student_answer->answer)) $num_takers++;
				}
			} // end foreach student answer
			
			$answers[$cnt]->num_takers = $num_takers;
			$num_quiz_takings += $num_takers;
		}
		
		// now go through answers again to calculate percent
		foreach($answers as $cnt=>$answer) {
			// calculate percentage
			if($num_quiz_takings) {
				$percent = round(100 * $answer->num_takers / $num_quiz_takings);
			} 
			else $percent = 0;
			
			$answers[$cnt]->percent = $percent;
		}
		
		return $answers;
	} // end poll_answers()
	
	// correct / incorrect per category
	static function per_category($in_shortcode = false, $atts = null) {
		global $wpdb;
		
		// select exam
		$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $_GET['exam_id']));
		$source_id = $exam->reuse_questions_from ? $exam->reuse_questions_from : $exam->ID;
		
		$user_join_sql = '';
		if(!empty($_POST['user_filter']) or !empty($atts['user_filter'])) {
		   $user_filter = empty($_POST['user_filter']) ? $atts['user_filter'] : $_POST['user_filter'];
		   if(strstr($user_filter, '@')) {
		      // find user by email
		      $user_filter = sanitize_email($user_filter);
		      $user = get_user_by('email', $user_filter);
		      if(!empty($user->ID)) $user_join_sql = $wpdb->prepare(" JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tT.ID = tA.taking_id AND tT.user_id=%d ", $user->ID);
		      else $user_join_sql = $wpdb->prepare(" JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tT.ID = tA.taking_id AND tT.email=%s ", $user_filter);
		   }
		   else {
		      // find by user login
		      $user_filter = sanitize_text_field($user_filter);
		      $user = get_user_by('login', $user_filter);
		      $user_join_sql = $wpdb->prepare(" JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tT.ID = tA.taking_id AND tT.user_id=%d ", intval(@$user->ID));
		   }
		}
		
		if(empty($atts['show']) or $atts['show'] != 'tags') {
   		// select question categories
   		$cats = $wpdb->get_results("SELECT DISTINCT(tQ.cat_id) as cat_id, tC.name as name, tC.exclude_from_reports as exclude_from_reports
   			FROM ".WATUPRO_QUESTIONS." tQ LEFT JOIN ".WATUPRO_QCATS." tC
   			ON tC.ID = tQ.cat_id
   			WHERE tQ.exam_id IN ($source_id) ORDER BY tC.name");
   			
   		// put uncategorized at the end
   		$final_cats = array();
   		$uncategorized = null;		
   		foreach($cats as $cat) {
   			if($cat->cat_id) $final_cats[] = $cat;
   			else $uncategorized = (object)array("name" => __('Uncategorized', 'watupro'), "cat_id" => 0);
   		}	
   		if(!empty($uncategorized->name)) $final_cats[] = $uncategorized;
   		$cats = $final_cats;
   			
   		// categories will probably not be hundreds. So instead of overloading the memory selecting all answers
   		// and then matching them, we'll run 3 queries for each category in the loop
   		foreach($cats as $cnt=>$cat) {
   			if(!empty($cat->exclude_from_reports)) continue;
   			$num_unanswered = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.cat_id=%d AND tA.answer=''", $exam->ID, $cat->cat_id)); 
   				
   			$num_correct = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.cat_id=%d AND tA.answer!='' AND tA.is_correct=1", $exam->ID, $cat->cat_id)); 	
   			
   			$num_wrong = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.cat_id=%d AND tA.answer!='' AND tA.is_correct=0", $exam->ID, $cat->cat_id));	
   				
   			$points = $wpdb->get_var($wpdb->prepare("SELECT SUM(tA.points) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.cat_id=%d", $exam->ID, $cat->cat_id));		
   				
   			$num_answered = $num_correct + $num_wrong;
   			$total = $num_answered + $num_unanswered;
   			
   			// % correct from answered and total
   			$perc_correct_a = $num_answered ? round(100 * $num_correct / $num_answered) : 0;
   			$perc_correct_t = $total ? round(100 * $num_correct / $total) : 0;
   			
   			// % wrong from answered and total
   			$perc_wrong_a = $num_answered ? round(100 * $num_wrong / $num_answered) : 0;
   			$perc_wrong_t = $total ? round(100 * $num_wrong / $total) : 0;
   			
   			// % answered and unanswered from total
   			$perc_answered = $total ? round(100 * $num_answered / $total) : 0;
   			$perc_unanswered = $total ? round(100 * $num_unanswered / $total) : 0;
   			
   			$cats[$cnt]->num_unanswered = $num_unanswered;
   			$cats[$cnt]->num_answered = $num_answered;
   			$cats[$cnt]->num_correct = $num_correct;
   			$cats[$cnt]->num_wrong = $num_wrong;
   			$cats[$cnt]->total = $total;
   			$cats[$cnt]->perc_correct_a = $perc_correct_a;
   			$cats[$cnt]->perc_correct_t = $perc_correct_t;
   			$cats[$cnt]->perc_wrong_a = $perc_wrong_a;
   			$cats[$cnt]->perc_wrong_t = $perc_wrong_t;
   			$cats[$cnt]->perc_answered = $perc_answered;
   			$cats[$cnt]->perc_unanswered = $perc_unanswered;
   			$cats[$cnt]->points = $points;
   		}	// end foreach cat
      } // end calculating cats		
		
		if(empty($atts['show']) or $atts['show'] != 'cats') {
   		// do the same for question tags if any
   		$qtags = $wpdb->get_results("SELECT DISTINCT(tags) as tags FROM ".WATUPRO_QUESTIONS."
   				WHERE exam_id IN ($source_id) AND tags!='' ORDER BY tags");				
   		$tags = $simple_tags = array();
   		foreach($qtags as $qtag) {
   		   $the_tags = explode('|', $qtag->tags);
   		   $the_tags = array_filter($the_tags);
   		   foreach($the_tags as $tag) {
   		      if(!in_array($tag, $simple_tags)) {
   		         $simple_tags[] = $tag;
   		         $tags[] = (object)array("tag" => $tag);
   		      }
   		   }
   		}			
         sort($tags);		
   			
   		foreach($tags as $cnt => $tag) {
   		   $num_unanswered = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.tags LIKE %s AND tA.answer=''", $exam->ID, '%|'.$tag->tag.'|%')); 
   				
   			$num_correct = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.tags LIKE %s AND tA.answer!='' AND tA.is_correct=1", $exam->ID, '%|'.$tag->tag.'|%')); 	
   			
   			$num_wrong = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.tags LIKE %s AND tA.answer!='' AND tA.is_correct=0", $exam->ID, '%|'.$tag->tag.'|%'));	
   				
   			$points = $wpdb->get_var($wpdb->prepare("SELECT SUM(tA.points) FROM ".WATUPRO_STUDENT_ANSWERS." tA
   				JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id	
   				$user_join_sql
   				WHERE tA.exam_id=%d AND tQ.tags LIKE %s", $exam->ID, '%|'.$tag->tag.'|%'));		
   				
   			$num_answered = $num_correct + $num_wrong;
   			$total = $num_answered + $num_unanswered;
   			
   			// % correct from answered and total
   			$perc_correct_a = $num_answered ? round(100 * $num_correct / $num_answered) : 0;
   			$perc_correct_t = $total ? round(100 * $num_correct / $total) : 0;
   			
   			// % wrong from answered and total
   			$perc_wrong_a = $num_answered ? round(100 * $num_wrong / $num_answered) : 0;
   			$perc_wrong_t = $total ? round(100 * $num_wrong / $total) : 0;
   			
   			// % answered and unanswered from total
   			$perc_answered = $total ? round(100 * $num_answered / $total) : 0;
   			$perc_unanswered = $total ? round(100 * $num_unanswered / $total) : 0;
   			
   			$tags[$cnt]->num_unanswered = $num_unanswered;
   			$tags[$cnt]->num_answered = $num_answered;
   			$tags[$cnt]->num_correct = $num_correct;
   			$tags[$cnt]->num_wrong = $num_wrong;
   			$tags[$cnt]->total = $total;
   			$tags[$cnt]->perc_correct_a = $perc_correct_a;
   			$tags[$cnt]->perc_correct_t = $perc_correct_t;
   			$tags[$cnt]->perc_wrong_a = $perc_wrong_a;
   			$tags[$cnt]->perc_wrong_t = $perc_wrong_t;
   			$tags[$cnt]->perc_answered = $perc_answered;
   			$tags[$cnt]->perc_unanswered = $perc_unanswered;
   			$tags[$cnt]->points = $points;
   		} // end foreach tag
   	} // end calculating tags	
				
		include(WATUPRO_PATH."/modules/reports/views/per-category.html.php");
	} // end per_category()
	
	// points or percent correct per question category - from all takings of a given user
	static function qcat_total($atts) {
		global $wpdb, $user_ID;
		
		$user_id = empty($atts['user_id']) ? $user_ID : intval($atts['user_id']);
		if(empty($user_id)) return __('Missing user ID', 'watupro');
		
		$cat_id = intval(@$atts['cat_id']);
		
		// for categories with subcategories we must include them as well
		$subcats = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".WATUPRO_QCATS."
			WHERE parent_id=%d", $cat_id));
		if(count($subcats)) {
			$ids = array($cat_id);
			foreach($subcats as $sub) $ids[] = $sub->ID;
			$cat_id_sql = " AND tQ.cat_id IN (". implode(',', $ids) .") ";
		}
		else $cat_id_sql = $wpdb->prepare(" AND cat_id=%d ", $cat_id);
		
		// difficulty level SQL
		$difflevel_sql = '';
		if(!empty($atts['difficulty_level'])) {
			$difflevel_sql = $wpdb->prepare(" AND tQ.difficulty_level LIKE %s ", $atts['difficulty_level']);
		}
				
		if(empty($atts['what']) or $atts['what'] == 'points') {
			// points
			$total = $wpdb->get_var($wpdb->prepare("SELECT SUM(tA.points) FROM ".WATUPRO_STUDENT_ANSWERS." tA
				JOIN ".WATUPRO_QUESTIONS." tQ ON tA.question_id=tQ.ID
				JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tA.taking_id=tT.ID AND tT.in_progress=0
				WHERE tT.user_id=%d $cat_id_sql $difflevel_sql", $user_id));
				
			return  intval($total);	
		}
		else {
			// percent correct answer
			$total_answers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
				JOIN ".WATUPRO_QUESTIONS." tQ ON tA.question_id=tQ.ID
				JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tA.taking_id=tT.ID AND tT.in_progress=0
				WHERE tT.user_id=%d $cat_id_sql $difflevel_sql", $user_id));
			if(!$total_answers) return 0;	
			$correct_answers = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tA.ID) FROM ".WATUPRO_STUDENT_ANSWERS." tA
				JOIN ".WATUPRO_QUESTIONS." tQ ON tA.question_id=tQ.ID
				JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tA.taking_id=tT.ID AND tT.in_progress=0
				WHERE tT.user_id=%d $cat_id_sql $difflevel_sql AND tA.is_correct=1", $user_id));	
			return  round(100 * $correct_answers / $total_answers);
		}
		
		return  $total;
	} // end qcat_total
	
	// number of taken tests per category or total
	static function taken_tests($atts) {
		global $wpdb, $user_ID; 
		$text = empty($atts['text']) ? __('{{num-taken}} from {{total}} total', 'watupro') :  $atts['text'];
		$cat_id = empty($atts['cat_id']) ? 0 : intval($atts['cat_id']);
		$user_id = empty($atts['user_id']) ? $user_ID : intval($atts['user_id']);
		
		// select num available quizzes (in the category)
		$published_odd_ids = $wpdb->get_results("SELECT ID FROM ".WATUPRO_EXAMS." 	WHERE published_odd = 1");
		$post_contents = $wpdb->get_results("SELECT post_content FROM {$wpdb->posts} WHERE post_content LIKE '[watupro %]'");
		
		$all_ids = array();
		
		foreach($published_odd_ids as $id) $all_ids[] = $id->ID;
		
		foreach($post_contents as $post) {
			// extract quiz ID
			$matches = array();
			preg_match_all("/\[watupro([^\]])*\]/", $post->post_content, $matches);
			// extract quiz ID from $matches[0][0]
			$parts = explode(' ', $matches[0][0]);

			// in case there are arguments after the quiz number:
			if(strstr($parts[1], ' ')) {
				$sparts = explode(' ', $parts[1]);
				$parts[1] = $sparts[0];
			} 
			
			// cleanup ] if any
			$quiz_id = intval(str_replace(']', '', $parts[1]));
			if(!in_array($quiz_id, $all_ids)) $all_ids[] = $quiz_id;
		}
		
		$num_total = count($all_ids);
		// did we limit per category? If yes, need another query
		$cat_id_sql = '';
		if(!empty($cat_id)) {
			$num_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_EXAMS." 
				WHERE cat_id=%d AND ID IN (".implode(',', $all_ids).")", $cat_id) );
			$cat_id_sql = $wpdb->prepare(" AND tE.cat_id=%d ", $cat_id);	
		}
		
		// now select num attempted
		$attempted = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT(tT.exam_id) FROM ".WATUPRO_TAKEN_EXAMS." tT
			JOIN ".WATUPRO_EXAMS." tE ON tE.ID = tT.exam_id $cat_id_sql
			WHERE tT.exam_id IN (".implode(',', $all_ids).") AND tT.user_id=%d AND tT.in_progress=0", $user_id) );
		$num_attempts = count($attempted);	
		
		$text = str_replace('{{num-taken}}', $num_attempts, $text);
		$text = str_replace('{{total}}', $num_total, $text);
		
		return $text;
	} // end taken_tests
}