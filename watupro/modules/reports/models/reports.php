<?php
class WTPReports {
	public static $add_scripts = false;	
	public static $user = array();
	
	static function admin_menu() {
		$cap_level = current_user_can(WATUPRO_MANAGE_CAPS)?WATUPRO_MANAGE_CAPS:'read';	
		$manage_level = current_user_can(WATUPRO_MANAGE_CAPS)?WATUPRO_MANAGE_CAPS:'manage_options';		
		
		add_submenu_page('my_watupro_exams', __("Quiz Reports", 'watupro'), __("Quiz Reports", 'watupro'), $cap_level, 'watupro_reports', 
				array(__CLASS__, "dispatch"));
	   add_submenu_page('watupro_exams', __("Analytics Integration", 'watupro'), __("Analytics Integration", 'watupro'), 'manage_options', 'watupro_analytics', 
				array('WTPTracker', "options"));						
				
		// hidden pages
		add_submenu_page(NULL, __("Stats Per Question", 'watupro'), __("Status Per Question", 'watupro'), $cap_level, 'watupro_question_stats',
			array('WatuPROStats', 'per_question'));		
		add_submenu_page(NULL, __("All Question Answers", 'watupro'), __("All Question Answers", 'watupro'), $cap_level, 'watupro_question_answers',
			array('WatuPROStats', 'all_answers'));		
		add_submenu_page(NULL, __("Chart By Grade", 'watupro'), __("Chart By Grade", 'watupro'), $cap_level, 'watupro_question_chart',
			array('WatuPROStats', 'chart_by_grade'));		
		add_submenu_page(NULL, __("Stats per Category", 'watupro'), __("Stats per Category", 'watupro'), $cap_level, 'watupro_cat_stats',
			array('WatuPROStats', 'per_category'));				
	}
	
	// decides which tab to load
	static function dispatch() {
		global $user_ID;
		
		// define user ID
		if(!empty($_GET['user_id']) and is_numeric($_GET['user_id']) and current_user_can(WATUPRO_MANAGE_CAPS)) $report_user_id = intval($_GET['user_id']);	
		else $report_user_id = $user_ID;	
		
		// select user to display info
		$user = get_userdata($report_user_id);
		if($user_ID != $report_user_id) echo '<p>'.__('Showing quiz reports for ', 'watupro').' <b>'.$user->data->user_nicename.'</b></p>';
		
		$tab = empty($_GET['tab']) ? '' : $_GET['tab'];
		switch($tab) {
			case 'tests': self::tests($report_user_id); break; // exams taken
			case 'skills': self::skills($report_user_id); break; // question categories
			case 'time': self::time($report_user_id); break;
			case 'history': self::history($report_user_id); break;
			default: self::overview($report_user_id); break;
		}
	}
	
	static function overview($report_user_id, $has_tabs = true) {
		 global $wpdb;
		 
		 // all exams taken
		 $taken_exams = $wpdb->get_results($wpdb->prepare("SELECT tT.*, tE.cat_id as cat_id 
		 	FROM ".WATUPRO_TAKEN_EXAMS." tT JOIN ".WATUPRO_EXAMS." tE ON tT.exam_id = tE.id
		 	WHERE user_id=%d AND tT.in_progress=0 ORDER BY date", $report_user_id));
		 	
		 // tests attempted var
		 $num_attempts_total = count($taken_exams);
		 
		 // calculate total points and avg. % correct
		 $total_points = $total_percent = 0;
		 foreach($taken_exams as $taking) {
		 	$total_points += $taking->points;
		 	$total_percent += $taking->percent_correct;
		 }	
		 $avg_percent = $num_attempts_total ? round($total_percent / $num_attempts_total) : 0;
		 	
		 // skills practiced (question categories)
		 $skills = $wpdb->get_results($wpdb->prepare("SELECT cat_id FROM ".
		 	WATUPRO_QUESTIONS." WHERE ID IN 
		 		(SELECT question_id FROM ".WATUPRO_STUDENT_ANSWERS." tA 
		 			JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tA.taking_id=tT.ID AND tT.in_progress=0
		 			WHERE tT.user_id=%d  )
		 	AND is_inactive=0 AND is_survey=0", $report_user_id));
	
		 $skids = array();
		 foreach($skills as $skill) $skids[] = $skill->cat_id;
		 $skids = array_filter($skids);		 
		 $num_skills = sizeof($skids);
		 		 
		 // certificates earned
		 $cnt_certificates = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_USER_CERTIFICATES."
		 WHERE user_id=%d", $report_user_id));
		 
		 // figure out num exams taken by exam category - select categories I have access to
		 $cat_ids = WTPCategory::user_cats($report_user_id);
		 $cats = $wpdb->get_results("SELECT * FROM ".WATUPRO_CATS." WHERE ID IN(".implode(",", $cat_ids).")", ARRAY_A);
		 $cats = array_merge( array(array("ID"=>0, "name"=>__('Uncategorized', 'watupro'))), $cats);
		 		 
		 $report_cats = array();
		 // for any categories that don't have zero, add them to report_cats along with time_spent
		 foreach($cats as $cnt=>$cat) {
		 		$num_attempts = 0;
		 		foreach($taken_exams as $taken_exam) {
		 				if($taken_exam->cat_id == $cat['ID']) $num_attempts++;
		 		}
		 		
		 		$cats[$cnt]['num_attempts'] = $num_attempts;
		 		if($num_attempts) $report_cats[] = $cats[$cnt];
		 }
		 
		 // now select question categories
		 $qcats = $wpdb->get_results($wpdb->prepare("SELECT COUNT(tA.ID) as cnt, tC.name as name
		 	FROM ".WATUPRO_QCATS." tC JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.cat_id = tC.ID 
		 	JOIN ".WATUPRO_STUDENT_ANSWERS." tA ON tA.question_id = tQ.ID 	AND tA.user_id = %d
		 	JOIN ".WATUPRO_TAKEN_EXAMS." tT ON tA.taking_id=tT.ID AND tT.in_progress=0
		 	WHERE  tC.exclude_from_reports=0 
		 	GROUP BY tC.ID ORDER BY tC.name", $report_user_id));
		 $question_cats = array(); // fill only these that has at least 1 answer
		 foreach($qcats as $qcat) {
		 	if($qcat->cnt > 0) $question_cats[] = $qcat;
		 }	
		 	
		 self::$add_scripts = true;
		 if(@file_exists(get_stylesheet_directory().'/watupro/reports/overview.php')) require get_stylesheet_directory().'/watupro/reports/overview.php';
		 else require WATUPRO_PATH."/modules/reports/views/overview.php";
		 self::print_scripts();
	}
	
	static function tests($report_user_id, $has_tabs = true) {
		// details about taken exams
		global $wpdb, $post;
		$this_post = $post;
		
		if(!empty($_GET['view_details'])) {
			watupro_taking_details(true);
			return false;
		}
		
		// select all taken exams along with exam data
		$offset = empty($_GET['offset']) ? 0 : intval($_GET['offset']);
		$page_limit = 50;
		$sql = "SELECT SQL_CALC_FOUND_ROWS tT.*, tE.name as name, tT.exam_id as exam_id,
			tE.published_odd as published_odd, tE.published_odd_url as published_odd_url,
			tG.gtitle as grade_title 
		  FROM ".WATUPRO_EXAMS." tE, ".WATUPRO_TAKEN_EXAMS." tT
		  LEFT JOIN ".WATUPRO_GRADES." tG ON tG.ID = tT.grade_id
			WHERE tT.user_id=%d AND tT.in_progress=0 AND tT.exam_id=tE.ID
		  ORDER BY tT.ID DESC LIMIT %d, %d"; 
		$exams = $wpdb->get_results($wpdb->prepare($sql, $report_user_id, $offset, $page_limit)); 
		$count = $wpdb->get_var("SELECT FOUND_ROWS()");
		$tids = array(-1); // taking IDs 
		foreach($exams as $exam) $tids[] = $exam->ID; 
		
		// select count answers per taking for non survey questions for this user
		$cnt_answers = $wpdb->get_results($wpdb->prepare("SELECT COUNT(tA.ID) as cnt, tA.taking_id as taking_id 
			FROM " . WATUPRO_STUDENT_ANSWERS." tA JOIN ".WATUPRO_TAKEN_EXAMS." tT 
			ON tT.ID = tA.taking_id 
			JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id 
			WHERE tT.user_id=%d AND tQ.is_survey=0 AND tT.ID IN (".implode(',', $tids).") 
			GROUP BY tT.ID", $report_user_id));
		
		$posts=$wpdb->get_results("SELECT * FROM {$wpdb->posts} 
		WHERE post_content LIKE '%[watupro %]%' 
		AND post_status='publish'
		ORDER BY post_date DESC"); 
		
		// match posts to exams
		foreach($exams as $cnt=>$exam) {
			$exams[$cnt]->time_spent = self::time_spent($exam);
			$exams[$cnt]->cnt_answers = 0;
			foreach($posts as $post) {
				if(stristr($post->post_content,"[WATUPRO ".$exam->exam_id."]")) {
					$exams[$cnt]->post=$post;			
					break;
				}
			}
			
			foreach($cnt_answers as $cnt_ans) {
				// note that $exam->ID here is actually the taking ID!
				if($cnt_ans->taking_id == $exam->ID) {
					$exams[$cnt]->cnt_answers = $cnt_ans->cnt;
				} 
			} // end foreach $cnt_answers
		} // end foreach $exams
		
		if(!empty($_GET['export'])) {
			$newline=watupro_define_newline();
			$rows=array();			
			$rows [] = __('Quiz name', 'watupro'). "\t" . __('Time spent', 'watupro'). "\t"
				. __('Problems attempted', 'watupro'). "\t" . __('Grade', 'watupro') . "\t"
				. __('Points', 'watupro') . "\t" . __('Percent correct', 'watupro');
			foreach($exams as $exam) {
				$result = stripslashes($exam->result);
				$result = str_replace(array("\t", "\r", "\n"), array("   ", " ", " "), $result);				
				
				$rows[] = stripslashes($exam->name) . "\t" . self::time_spent_human($exam->time_spent) .
					"\t" . $exam->cnt_answers . "\t" . $result ."\t" . $exam->points ."\t" . $exam->percent_correct;
			}	
			
			$csv=implode($newline,$rows);
			
			$now = gmdate('D, d M Y H:i:s') . ' GMT';		
			header('Content-Type: ' . watupro_get_mime_type());
			header('Expires: ' . $now);
			header('Content-Disposition: attachment; filename="user-'.$report_user_id.'.csv"');
			header('Pragma: no-cache');
			echo $csv;
			exit;
		}
		
		wp_enqueue_script('thickbox',null,array('jquery'));
		wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
		
		if(!$has_tabs) {
			// called in shortcode
			$permalink = get_permalink($this_post->ID);
			$params = array('view_details' => 1);
			$target_url = add_query_arg( $params, $permalink );
		}
		
		if(@file_exists(get_stylesheet_directory().'/watupro/reports/tests.php')) require get_stylesheet_directory().'/watupro/reports/tests.php';
		else require WATUPRO_PATH."/modules/reports/views/tests.php";
	}
	
	static function skills($report_user_id, $has_tabs = true, $atts = null) {
		global $wpdb;

		// shortcode params
		if(!empty($atts['chart_orientation']) and empty($_POST['chart_orientation'])) $_POST['chart_orientation'] = $atts['chart_orientation'];
		
		// select exam categories that I can access
		$cat_ids = WTPCategory::user_cats($report_user_id);
		$cat_id_sql=implode(",",$cat_ids);	
		$exam_cats = $wpdb->get_results("SELECT * FROM ".WATUPRO_CATS." WHERE ID IN ($cat_id_sql) ORDER BY name");

		// question categories
		$q_cats = $wpdb->get_results("SELECT tC.* FROM ".WATUPRO_QCATS." tC
			JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.cat_id = tC.ID AND tQ.is_inactive=0
			JOIN ".WATUPRO_EXAMS." tE ON tE.ID = tQ.exam_id AND (tE.cat_id=0 OR tE.cat_id IN ($cat_id_sql))
			WHERE tC.exclude_from_reports=0 GROUP BY tC.ID ORDER BY tC.name");
			

		// collect parent IDs here. We have to add any categories that are not included because they don't directly have questions
		// BUT their subcats have
		$parent_ids = $q_cat_ids = array(0);		
		foreach($q_cats as $q_cat) {
			if($q_cat->parent_id and !in_array($q_cat->parent_id, $parent_ids)) $parent_ids[] = $q_cat->parent_id;
			
			if(!$q_cat->parent_id) $q_cat_ids[] = $q_cat->ID;
		}	

		// now re-select all categories that are either in $q_cat_ids or $parent_ids. This is the actual $q_cats array
		$all_ids = array_merge($parent_ids, $q_cat_ids);
		
		$q_cats = $wpdb->get_results("SELECT * FROM ".WATUPRO_QCATS." WHERE ID IN (".implode(',', $all_ids ).") ORDER BY name");
		
		$all_cats = array();
		$subcats = $wpdb->get_results("SELECT * FROM ".WATUPRO_QCATS." WHERE parent_id!=0 ORDER BY name");
		foreach($q_cats as $cnt => $qcat) {
			$all_cats[] = $qcat;
			$cat_subs = array();
			foreach($subcats as $sub) {
				if($sub->parent_id == $qcat->ID) {
					$cat_subs[] = $sub;
					$all_cats[] = $sub;
				}
			}
			$q_cats[$cnt]->subs = $cat_subs;
		}	

		// add uncategorized
		$q_cats[] = (object)array("ID"=>0, "name"=>__('Uncategorized', 'watupro'));
		
		// exam category filter?
		if(!isset($_POST['cat'])) $_POST['cat'] = -1;
		$exam_cat_sql = ($_POST['cat'] < 0)? $cat_id_sql : intval(@$_POST['cat']);
				
		// now select all exams I have access to	
		WTPExam :: $show_skills_report = true;	
		list($my_exams) = WTPExam::my_exams($report_user_id, $exam_cat_sql);
		
		$skill_filter = empty($_POST['skill_filter'])?"all":$_POST['skill_filter'];
		
		// practiced only?
		if($skill_filter == 'practiced') {
			 $final_exams = array();
			 foreach($my_exams as $exam) {
			 	  if(!empty($exam->taking->ID)) $final_exams[] = $exam;
			 }
			 $my_exams = $final_exams;
		}
		
		// proficiency filter selected? If yes, we'll need to limit exams
		// to those that are taken with at least $_POST['proficiency_goal'] % correct answers		
		if($skill_filter == 'proficient') {				
				$final_exams = array();
				foreach($my_exams as $exam) {					 
					 if(!empty($exam->taking->ID) and $exam->taking->percent_correct >= $_POST['proficiency_goal']) {
					 		$final_exams[] = $exam;
					 }
				} // end exams loop		 
				
				$my_exams = $final_exams;
		}
		
		// for each exam select match answers and fill % correct info by category
		$taking_ids = array(0);
		foreach($my_exams as $my_exam) {
			if(!empty($my_exam->taking->ID)) $taking_ids[] = $my_exam->taking->ID;
		} 

		$user_answers = $wpdb->get_results("SELECT tA.is_correct as is_correct, tA.taking_id as taking_id, tQ.cat_id as cat_id 
			FROM ".WATUPRO_STUDENT_ANSWERS." tA JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id AND tQ.is_survey=0
			WHERE tA.taking_id IN (".implode(',', $taking_ids).") ORDER BY tA.ID");
			
		foreach($my_exams as $cnt=>$my_exam) {
			if(empty($my_exam->taking->ID)) continue;
			
			$cats = array();
			foreach($user_answers as $answer) {
				if($answer->taking_id != $my_exam->taking->ID) continue;
				
				$correct_key = $answer->is_correct ? 'num_correct' : 'num_incorrect';
				if(isset($cats[$answer->cat_id][$correct_key])) $cats[$answer->cat_id][$correct_key]++;
				else $cats[$answer->cat_id][$correct_key] = 1;
			}		
			
			// now foreach cat calculate the correctness
			foreach($cats as $cat_id=>$cat) {
				$num_correct = isset($cat['num_correct']) ? $cat['num_correct'] : 0;
				$num_incorrect = isset($cat['num_incorrect']) ? $cat['num_incorrect'] : 0;
				$total = $num_correct + $num_incorrect;
				$percentage = $total ? round(100 * $num_correct / $total) : 0;
				$cats[$cat_id]['percentage'] = $percentage;
			}
			
			// finally add cats to exam
			$my_exams[$cnt]->cats = $cats;
		}	
		
		// group exams by question category
		$skills = array(); // skills equal question categories
		$num_proficient = 0;
		foreach($all_cats as $qct=>$q_cat) {			
			// skill filter (question category) selected in the drop-down?
			if((@$_POST['q_cat']>-1) and $q_cat->ID != @$_POST['q_cat']) continue;
			
			// now construct array of this category along with the exams in it
			// then add in $skills. $skills is the final array that we'll use in the view
			$exams = array();
			foreach($my_exams as $exam) {
				 $has_questions = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".WATUPRO_QUESTIONS." 
				 	WHERE exam_id=%d AND cat_id=%d AND is_inactive=0 AND is_survey=0", $exam->ID, $q_cat->ID));
				 	
				 if(!$has_questions) continue;
				 
				 $exams[] = $exam;	
			}	
			
			$percent_correct = $num_correct = $total_user_answers = 0;
			$q_sub_ids = array();
			if(!empty($q_cat->subs)) foreach($q_cat->subs as $sub) $q_sub_ids[] = $sub->ID;
			foreach($user_answers as $user_answer) {				
				if($user_answer->cat_id != $q_cat->ID and !in_array($user_answer->cat_id, $q_sub_ids)) continue;
				$total_user_answers++;
				if($user_answer->is_correct) $num_correct++;
			}
			
			$percent_correct = $total_user_answers ? round(100 * $num_correct / $total_user_answers) : 0;
			
			$skills[] = array("category"=>$q_cat, "exams"=>$exams, "id"=>$q_cat->ID, "percent_correct"=>$percent_correct);
			if(sizeof($exams)) $num_proficient++; // proficient in X skills
		}	
		
		// reorder skills?
		if(!empty($_POST['chart_sort']) and $_POST['chart_sort'] == 'percent') {		
			uasort($skills, array(__CLASS__, 'reorder_skills_chart'));
		}
		
		if(@file_exists(get_stylesheet_directory().'/watupro/reports/skills.php')) require get_stylesheet_directory().'/watupro/reports/skills.php';
		else require WATUPRO_PATH."/modules/reports/views/skills.php";
	}
	
	// helper to reorder skills chart by proficiency
	static function reorder_skills_chart($a, $b) {
		if($a['percent_correct'] < $b['percent_correct']) return 1;
		else return -1;
	}
	
	// history
	static function history($report_user_id, $has_tabs = true) {
		global $wpdb;
		$report_user_id = intval($report_user_id);
		
		// select taken exams and fill the details for them
		$taken_exams = $wpdb->get_results("SELECT tT.*, tE.name as exam_name, tP.ID as post_id,
			tE.published_odd as published_odd, tE.published_odd_url as published_odd_url 
			FROM ".WATUPRO_TAKEN_EXAMS." tT JOIN ".WATUPRO_EXAMS." tE ON tE.ID = tT.exam_id 
			LEFT JOIN {$wpdb->posts} tP ON tP.post_status='publish' AND tP.post_title != ''
			AND tP.post_content LIKE CONCAT('%[watupro ', tE.ID, ']%') 
			WHERE tT.in_progress=0 AND tT.user_id=$report_user_id 
			GROUP BY tT.ID ORDER BY tT.end_time DESC");		
			
		$details = $wpdb->get_results($wpdb->prepare("SELECT tA.*, tQ.cat_id as cat_id 
			FROM ".WATUPRO_STUDENT_ANSWERS." tA JOIN ".WATUPRO_QUESTIONS." tQ
			ON tQ.ID = tA.question_id AND tQ.is_inactive=0 AND tQ.is_survey = 0
			WHERE tA.user_id=%d", $report_user_id));
			
		$total_time = $total_problems = $total_skills = 0;
			
		foreach($taken_exams as $cnt=>$exam) {			
			// add details
			$taken_exams[$cnt]->details = array();
			$taken_exams[$cnt]->num_problems = 0;
			$taken_exams[$cnt]->skills_practiced = array();
			foreach($details as $detail) {
				if($detail->taking_id != $exam->ID) continue; 
				$taken_exams[$cnt]->details[] = $detail;
				$taken_exams[$cnt]->num_problems++;
				if(!in_array($detail->cat_id, $taken_exams[$cnt]->skills_practiced)) $taken_exams[$cnt]->skills_practiced[] = $detail->cat_id; 
			}
			
			// calculate start time			
			list($date, $time) = explode(" ", $exam->start_time);			
			$date = explode("-",$date);
			$time = explode(":", $time);			
			$start_time = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
			$taken_exams[$cnt]->start_time = $start_time;
			
			// calculate end time
			list($date, $time) = explode(" ", $exam->end_time);
			$date = explode("-",$date);
			$time = explode(":", $time);			
			$end_time = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
			$taken_exams[$cnt]->end_time = $end_time;
			
			// fill the period property for later use (month, year)
			$taken_exams[$cnt]->period = date('F', $end_time)." ".$date[0];
						
			$taken_exams[$cnt]->period_morris = date("Y-m", $end_time); 
			
			$time_spent = ($end_time - $start_time) ? ($end_time - $start_time) : 0;
			
			$taken_exams[$cnt]->time_spent = $time_spent;
			$total_time += $time_spent;
			
			$total_problems += $taken_exams[$cnt]->num_problems;
			
			// num skills
			$taken_exams[$cnt]->num_skills = sizeof($taken_exams[$cnt]->skills_practiced);
			$total_skills += $taken_exams[$cnt]->num_skills;
		}	
		
		// summary calculations
		$total_sessions = sizeof($taken_exams);
		$avg_time_spent = $total_sessions? ($total_time / $total_sessions) : 0;
		$avg_problems = round($total_sessions? ($total_problems / $total_sessions) : 0);
		$avg_skills = round($total_sessions? ($total_skills / $total_sessions) : 0);
		
		// group takings by month/year for the chart and table
		$periods = array();
		foreach($taken_exams as $exam) {
			if(!in_array($exam->period, $periods)) $periods[] = $exam->period;
		}
		
		// now fill logs array which is actually periods with exams in them
		$logs = array();
		$max_exams = 0; // max exams in a period, so we can build the chart
		foreach($periods as $period) {
			 $period_exams = array();
			 $time_spent = 0;			 
			 foreach($taken_exams as $exam) {
			 		if($exam->period != $period) continue; 
			 		$period_exams[] = $exam;
			 		$time_spent += $exam->time_spent;
			 }
			 
			 $num_exams = sizeof($period_exams);
			 if($num_exams > $max_exams) $max_exams = $num_exams;
			 $logs[] = array("period"=>$period, "exams"=>$period_exams, "time_spent"=>$time_spent, 
			 		"num_exams"=> $num_exams);
		}
		
		// for the char we need reversed logs and no more than 12		
		$chartlogs = array_reverse($logs);
		if(sizeof($chartlogs)>12) $chartlogs = array_slice($chartlogs, sizeof($chartlogs) - 12);
		
		// let's keep the chart up to 200px high. Find height in px for 1 exam in chart
		$one_exam_height = $max_exams ? (200 / $max_exams) : 0;
		
		$date_format = get_option("date_format");
		$time_format = get_option('time_format');
		 
		if(@file_exists(get_stylesheet_directory().'/watupro/reports/history.php')) require get_stylesheet_directory().'/watupro/reports/history.php';
		else require WATUPRO_PATH."/modules/reports/views/history.php";	
	}
	
	// helper to calculate time spent in exam
	static function time_spent($exam) {
		return WTPRecord :: time_spent($exam);		
	} 
	
	static function time_spent_human($time_spent) {
		return WTPRecord :: time_spent_human($time_spent);		
	}	
	
	// register javascripts
	static function register_scripts() {
		// g.raphael to be removed when we replace all charts with chart.js
		wp_register_script('raphael', plugins_url('watupro/modules/reports/js/raphael-min.js'), null, '1.0', true);
		wp_register_script('g.raphael', plugins_url('watupro/modules/reports/js/g.raphael-min.js'), null, '1.0', true);
		wp_register_script('g.bar', plugins_url('watupro/modules/reports/js/g.bar-min.js'), null, '1.0', true);
		wp_register_script('g.line', plugins_url('watupro/modules/reports/js/g.line-min.js'), null, '1.0', true);
		wp_register_script('g.pie', plugins_url('watupro/modules/reports/js/g.pie.js'), null, '1.1', true);
		wp_register_script('g.dot', plugins_url('watupro/modules/reports/js/g.dot-min.js'), null, '1.0', true);
		wp_register_script('watupro-tracker', WATUPRO_URL .'modules/reports/js/event-tracker.js', array('jquery'), '1.0', true);	
	}
	
	static function print_scripts() {		
		if ( ! self::$add_scripts ) return false; 
		wp_print_scripts('raphael');
		wp_print_scripts('g.raphael');
		wp_print_scripts('g.bar');
		wp_print_scripts('g.line');
		wp_print_scripts('g.pie');
		wp_print_scripts('g.dot');
	}
	
	// enqueue front-end scripts
	static function front_scripts() {
		wp_enqueue_script('watupro-tracker');
	}
	
	// init module
	static function init() {
		self::register_scripts();
		add_action('wp_enqueue_scripts', array(__CLASS__, 'front_scripts'));
		
		add_shortcode( 'WATUPROR', array('WatuPROReportShortcodes', 'report') );
		add_shortcode( 'watupror-stats-per-question', array('WatuPROReportShortcodes', 'per_question') );  
		add_shortcode( 'watupror-stats-per-category', array('WatuPROReportShortcodes', 'per_category') );
		add_shortcode( 'watupror-question-answers', array('WatuPROReportShortcodes', 'all_answers') );
		add_shortcode( 'watupror-chart-by-grade', array('WatuPROReportShortcodes', 'chart_by_grade') );
		add_shortcode( 'watupror-poll', array('WatuPROReportShortcodes', 'poll') );
		add_shortcode( 'watupror-user-cat-chart', array('WatuPROReportShortcodes', 'user_category_chart') );
		add_shortcode( 'watupror-pie-chart', array('WatuPROReportShortcodes', 'pie_category_chart') );
		add_shortcode( 'watupror-qcat-total', array('WatuPROReportShortcodes', 'qcat_total') );
		add_shortcode( 'watupror-taken-tests', array('WatuPROReportShortcodes', 'taken_tests') );
		
		add_action('watupro_show_exam_js', array('WTPTracker', 'tracking_quiz_start'));
		add_action('watupro_completed_exam_detailed', array('WTPTracker', 'tracking_quiz_end'), 10, 5);
	}
}