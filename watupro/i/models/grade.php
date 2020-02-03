<?php
// Intelligence module grade object
class WTPIGrade {
	// @param grade_id array - all the grade IDs collected
	// for personality quizzes
	static function calculate($grade_ids) {
		global $wpdb;		
		$grade = __('None', 'watupro');
		$grade_obj = (object)array("title"=>__('None', 'watupro'), "description"=>"");
		$do_redirect = false;
		$certificate_id=0;
		if(empty($grade_ids)) $grade_ids = array();
		
		// from version 4.4.1 $grade_ids may contain arrays of multiple grade objects. Like this:
		// [5, 1|3, 1, 4|5|1] so we need to break it further
		$final_grade_ids = array();
		foreach($grade_ids as $grade_id) {
			if(strstr($grade_id, '|')) {
				$grids = explode('|', $grade_id);
				$final_grade_ids = array_merge($final_grade_ids, $grids);
			}			
			else $final_grade_ids[] = $grade_id;
		}
		
		$grade_ids = $final_grade_ids;
		
		// store the grade_ids in the DB. We may need this in the shortcode and elsehwere
		$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_TAKEN_EXAMS." SET personality_grade_ids=%s
			WHERE ID=%d", serialize($grade_ids), @$GLOBALS['watupro_taking_id']));
			
		// find exam ID, we'll need it later
		$exam_id = $wpdb->get_var($wpdb->prepare("SELECT exam_id FROM ".WATUPRO_TAKEN_EXAMS." WHERE ID=%d", @$GLOBALS['watupro_taking_id'] ));
		$advanced_settings = $wpdb->get_var($wpdb->prepare("SELECT advanced_settings FROM ".WATUPRO_EXAMS." WHERE ID=%d", $exam_id));
		$advanced_settings = unserialize(stripslashes($advanced_settings));
		
		//print_r($grade_ids);
		// find the top grade
		if(count($grade_ids)) {
			$grade_ids = array_count_values($grade_ids);
			
         // is there more than one top grade? If yes, we'll have to build a cumulative grade
         $top_cnt = max(array_values($grade_ids));         
         $top_grades = array();
		   foreach($grade_ids as $grid => $cnt) {
		      if($cnt == $top_cnt) $top_grades[] = $grid;
         } 
         
			if(count($top_grades) <= 1 or !empty($advanced_settings['single_personality_result'])) {
			   // default behavior: just one grade found OR the setting "always assign only one personality" is selected
			   // place counts as keys, IDs as values of the associative array		
   			$grade_ids = array_flip($grade_ids);							
   
   			// sort so most count is on top
   			krsort($grade_ids);						
   			$grade_id = array_shift($grade_ids);
			}
			else {			   
			   // multiple top grades: we may need to automatically create a cumulative grade and enter it in the DB.
			   // in case such cumulative grade exists we'll just need to find the ID
			   // for the moment we'll just not issue certificates with these grades. We'll see how to probably handle such stuff in future version
			   $cumulative_ids_sql = '';
			   foreach($top_grades as $top_grade) $cumulative_ids_sql .= " AND included_grade_ids LIKE '%|".$top_grade."|%' ";
			   
			   $grade_id = $wpdb->get_var("SELECT * FROM ".WATUPRO_GRADES." WHERE is_cumulative_grade=1 $cumulative_ids_sql");
			   
			   if(empty($grade_id)) {
               // prepare the variables
               $cumulative_grade_title = $cumulative_grade_desc = '';
               $included_grade_ids = '|';
               foreach($top_grades as $cnt=>$top_grade_id) {
                  // these grades won't be hundreds and this query won't happen often so it's OK to run DB q for each one
                  // spare your comments, if you are an optimization freak
                  $top_grade = $wpdb->get_row($wpdb->prepare("SELECT gtitle, gdescription FROM ". WATUPRO_GRADES." WHERE ID=%d", $top_grade_id));
                  if($cnt) $cumulative_grade_title .= ", ";
                  $cumulative_grade_title .= stripslashes($top_grade->gtitle);
                  $cumulative_grade_desc .= stripslashes($top_grade->gdescription).'<p>&nbsp;</p>';
                  $included_grade_ids .= $top_grade_id . '|';                   
               }			      
			      
			      // create the cumulative grade
			      $wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_GRADES." SET 
			         exam_id=%d, gtitle=%s, gdescription=%s, is_cumulative_grade=1, included_grade_ids=%s",
			         $exam_id, $cumulative_grade_title, $cumulative_grade_desc, $included_grade_ids));
			      $grade_id = $wpdb->insert_id;   
			   }
			} // end case with multiple top grades
			
			// finally select the grade
   		$grow = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_GRADES." WHERE ID=%d", $grade_id));
   		list($grade, $grade_obj, $certificate_id, $do_redirect) = WTPGrade :: match_grade($grow); 	
						
		}	// end figure out the winning grade(s)	
		
		return array($grade, $certificate_id, $do_redirect, $grade_obj);
	}
	
	// this method loops through all personality grades in the given quiz
	// $atts['sort'] : best, worst, alphabetic, default (order of creation) 
	// $atts['empty'] : true to show types where you got 0, false to not show them. Default: true
	// $atts['limit'] : how many grades to show. Defaults to no limit 
	static function expand_personality_result($atts, $content = '') {
		global $wpdb;
		if(empty($content)) return '';
	
		
		$taking_id = intval($_POST['watupro_current_taking_id']);
		if(empty($taking_id)) return '';
		
		// now select grades
		$taking = $wpdb->get_row($wpdb->prepare("SELECT exam_id, personality_grade_ids FROM ".WATUPRO_TAKEN_EXAMS." WHERE ID=%d", $taking_id));
		
		// is personality at all?
		$is_personality = $wpdb->get_var($wpdb->prepare("SELECT is_personality_quiz FROM ".WATUPRO_EXAMS." WHERE ID=%d", $taking->exam_id));
		
		if(!$is_personality) return '';
		
		// take care for cases when the quiz reuses default grades
		$exam = $wpdb->get_row($wpdb->prepare("SELECT reuse_default_grades, grades_by_percent FROM ".WATUPRO_EXAMS." WHERE ID=%d", $taking->exam_id));
		$reuse_default_grades = $exam->reuse_default_grades;
		$grade_exam_id =  $reuse_default_grades ? 0 : $taking->exam_id;
		
		// now select grades
		$grades = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRO_GRADES."
			WHERE cat_id=0 AND exam_id=%d AND percentage_based=%d AND is_cumulative_grade=0 ORDER BY ID", $grade_exam_id, $exam->grades_by_percent)); 
			
		$grade_ids = unserialize($taking->personality_grade_ids);
		$grade_ids = array_count_values($grade_ids);			
		
		// fill the numbers
		foreach($grades as $cnt=>$grade) {
			$grades[$cnt]->count = 0;
			foreach($grade_ids as $grade_id => $count) {
				if($grade_id == $grade->ID) $grades[$cnt]->count = $count;
			}
			
			// omit grades that did not collect any points?
			if(!empty($atts['empty']) and $atts['empty']=='false' and empty($grades[$cnt]->count)) unset($grades[$cnt]); 
		}
		
		// sort
		if(!empty($atts['sort']) and $atts['sort'] != 'default') {
			if($atts['sort'] == 'best') uasort($grades, array(__CLASS__, 'sort_results_best'));
			if($atts['sort'] == 'worst') uasort($grades, array(__CLASS__, 'sort_results_worst'));
			if($atts['sort'] == 'alpha') uasort($grades, array(__CLASS__, 'sort_results_alpha'));
		}
		
		// limit
		if(!empty($atts['limit']) and is_numeric($atts['limit'])) {
			$grades = array_slice($grades, 0, $atts['limit']);
		}
		
		// calculate total number of answers with grade so we can know the % of each grade
		$total_count = 0;
		foreach($grades as $grade) $total_count += $grade->count;
		foreach($grades as $cnt => $grade) {
			$percent = $total_count > 0 ? round($grade->count * 100 / $total_count) : 0;
			$grades[$cnt]->percent = $percent;
		}
						
		// and replace the texts
		if(empty($atts['chart'])) {
			// default behavior: text
			$final_content = '';
			
			$n = 0;
			foreach($grades as $grade) {
				// by passing arguments like "rank" or "personality" we can display just a specific result here
				$n++;				
				if(!empty($atts['rank']) and is_numeric($atts['rank']) and $atts['rank'] != $n) continue;
				if(!empty($atts['personality']) and strcasecmp($atts['personality'], stripslashes($grade->gtitle)) != 0) continue;				
				
				$grade_content = str_replace('{{{personality-type}}}', stripslashes($grade->gtitle), $content);
				$grade_content = str_replace('{{{personality-type-description}}}', wpautop(stripslashes($grade->gdescription)), $grade_content);
				$grade_content = str_replace('{{{num-answers}}}', $grade->count, $grade_content);
				$grade_content = str_replace('{{{percent-answers}}}', $grade->percent, $grade_content);
				$final_content .= wpautop($grade_content);
			}
		}
		else {
			$max_points = 0;
			foreach($grades as $grade) {
				if($grade->count > $max_points) $max_points = $grade->count; 
			}	
			if($max_points == 0) return '';
			$step = round(200 / $max_points, 2);
			
			$colors = array("red", "green", "blue", "yellow", "brown", "orange", "gray", "purple", "maroon");
			
			// display bar chart
			$final_content = '<table class="watupro-personality-chart"><tr>';
			foreach($grades as $cnt => $grade) {
				$color_counter = ($cnt > 8) ? $cnt % 8: $cnt;
				$final_content .= '<td align="center" style="vertical-align:bottom;">';
				$final_content .= '<div style="background-color:'.$colors[$color_counter].';width:100px;height:'.round($step * $grade->count). 'px;">&nbsp;</div>'; 
				$final_content .= '</td>';
			}
			$final_content .= '</tr><tr>';
			
			foreach($grades as $grade) {
				$grade_content = str_replace('{{{personality-type}}}', stripslashes($grade->gtitle), $content);
				$grade_content = str_replace('{{{personality-type-description}}}', wpautop(stripslashes($grade->gdescription)), $grade_content);
				$grade_content = str_replace('{{{num-answers}}}', $grade->count, $grade_content);
				$grade_content = str_replace('{{{percent-answers}}}', $grade->percent, $grade_content);
				$final_content .= '<td>'.wpautop($grade_content).'</td>';
			}
			
			$final_content .= '</tr></table>'; 
		}
		
		return $final_content;
	}
	
	// sort personality results by best on top
	static function sort_results_best($grade_a, $grade_b) {
		if($grade_a->count == $grade_b->count) return 0;
		return ($grade_a->count > $grade_b->count) ? -1 : 1;
	}
	
	// sort personality results by worst on top
	static function sort_results_worst($grade_a, $grade_b) {
		if($grade_a->count == $grade_b->count) return 0;
		return ($grade_a->count < $grade_b->count) ? -1 : 1;
	}
	
	// sort personality results by alpha
	static function sort_results_alpha($grade_a, $grade_b) {
		if($grade_a->gtitle == $grade_b->gtitle) return 0;
		return ($grade_a->gtitle < $grade_b->gtitle) ? -1 : 1;
	}
}