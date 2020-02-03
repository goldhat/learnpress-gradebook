<?php
// functions that manage the users.php page in admin and maybe more
class WTPReportsUser {
	static function add_status_column($columns) {	
		$columns['exam_reports'] = sprintf(__('%s Reports', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD));
	 	return $columns;	
	}
	
	// chart by question category performance on a single quiz
	// atts: width (of the bar), height (max-height of the chart), from=answered, correct; color
	static function chart_by_category($atts) {
		global $wpdb;
		
		$taking_id = empty($atts['taking_id']) ? $_POST['watupro_current_taking_id'] : intval($atts['taking_id']);
		
		// select taking, if not found return empty string
		$taking = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WATUPRO_TAKEN_EXAMS . " WHERE ID=%d", $taking_id));
		
		if(empty($taking->ID)) return "";
		
		$width = empty($atts['width']) ? 100 : intval($atts['width']);
		if($width < 10) $width = 10;
		$height = empty($atts['height']) ? 300 : intval($atts['height']);
		if($height < 100) $height = 100;
		$step = round($height / 100, 2);
		
		$color = empty($atts['color']) ? '#CCC' : $atts['color'];
		
		$from = empty($atts['from']) ? 'correct' : $atts['from'];
		if(!in_array($from, array('correct', 'answered', 'points', 'percent_max_points'))) $from = 'correct';
		
		$survey_sql = empty($atts['include_survey_questions']) ? 'AND tQ.is_survey=0' : '';
		
		// select answers join by category
		$answers = $wpdb->get_results($wpdb->prepare("SELECT tA.answer, tA.is_correct, tC.ID as cat_id, tC.name as cat,
			tQ.is_survey as is_survey, tA.points as points, tA.question_id as question_id
			FROM ".WATUPRO_STUDENT_ANSWERS." tA JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id $survey_sql
			LEFT JOIN ".WATUPRO_QCATS." tC ON tC.ID = tQ.cat_id
			WHERE tA.taking_id=%d order by tA.ID", $taking_id));
			
		// when calculating % of max points we need to get max points for each question
		// we also need this when doing the chart on absolute points to properly identify the step 
		if($from == 'percent_max_points' or $from == 'points') {
			$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WATUPRO_EXAMS . " WHERE ID=%d", $taking->exam_id));
			$max_of_all = 0;
			foreach($answers as $answer) $qids[] = $answer->question_id;
	 		$questions = $wpdb->get_results("SELECT * FROM ".WATUPRO_QUESTIONS." WHERE ID IN (".implode(',', $qids).")");
	 		$_watu = new WatuPRO();
	 		$_watu->match_answers($questions, $exam);	 	
	 		foreach($questions as $question) {
	 			foreach($answers as $cnt=>$answer) {
	 				if($answer->question_id == $question->ID) {
	 					$answers[$cnt]->max_points = WTPQuestion::max_points($question);
	 				}
	 			}	 			
	 		}
		}	// end percent_max_points
		
		// fill categories array		
		$cats = array();
		foreach($answers as $answer) {			
			if(!isset($cats[$answer->cat_id])) { 
				// create the cat element			
				if(empty($answer->cat)) $answer->cat = __('Uncategorized', 'watupro');
				$cats[$answer->cat_id] = array("id"=>$answer->cat_id, "name"=>$answer->cat, 
					"num_questions"=>0, "num_answered"=>0, "num_correct"=>0, "points"=>0, "max_points"=>0);
			}
			
			$cats[$answer->cat_id]['num_questions']++;
			if(!empty($answer->answer)) $cats[$answer->cat_id]['num_answered']++;
			if(!empty($answer->is_correct)) $cats[$answer->cat_id]['num_correct']++;
			if(!empty($answer->points)) $cats[$answer->cat_id]['points'] += $answer->points;
			if(!empty($answer->max_points)) $cats[$answer->cat_id]['max_points'] += $answer->max_points;
		}	
		
		// now accordingly to "from" calculate the % in each category
		foreach($cats as $cnt => $cat) {
			switch($from) {				
				case 'points': 
					$percent = $cat['points'];
					if($max_of_all < $cat['max_points']) $max_of_all = $cat['max_points']; 
				break;
				case 'percent_max_points': 
					$percent = ($cat['max_points'] and $cat['points'] > 0) ? round(100 * $cat['points'] / $cat['max_points']) : 0; 
				break;
				case 'answered': $percent = round(100 * $cat['num_answered'] / $cat['num_questions']); break;
				case 'correct': default: $percent = round(100 * $cat['num_correct'] / $cat['num_questions']); break;
			}			
			$cats[$cnt]['percent'] = $percent;
			$cat['percent'] = $percent;
			
			// define label and color for the chart
			switch($from) {
				case 'percent_max_points': $label = sprintf(__('<b>%s</b> - %d%% (%s of %s points)', 'watupro'), stripslashes($cat['name']), $cat['percent'], $cat['points'], $cat['max_points']); break;
				case 'points': $label = sprintf(__('<b>%s</b> - %s points', 'watupro'), stripslashes($cat['name']), $cat['percent']); break;
				case 'answered': case 'correct': default: $label = sprintf(__('<b>%s</b> - %d%%', 'watupro'), stripslashes($cat['name']), $cat['percent']); break;
			}
			
			$cats[$cnt]['label'] = $label;
			$r = rand(128,255); 
       	$g = rand(128,255); 
       	$b = rand(128,255); 	
        	$cats[$cnt]['color'] = 'rgba('.$r.', '.$g.', '.$b.', 0.3)';	
		}
			
		// when working with absolute points (from = "points") we have to redefine the $step
		if($from == 'points') $step = round($height / $max_of_all);
			
		include(WATUPRO_PATH . "/modules/reports/views/barchart-by-category.html.php");
	} // end chart_by_category 
	
	// chart by question category performance on a single quiz
	// atts: size of the pie
	// for now simply show points per category
	static function pie_by_category($atts) {
		global $wpdb;
		
		$taking_id = empty($atts['taking_id']) ? $_POST['watupro_current_taking_id'] : intval($atts['taking_id']);
		$radius = empty($atts['radius']) ? 100 : intval($atts['radius']);
		if($radius <= 0) $radius = 100;
		$show = empty($atts['from']) ? 'points' : sanitize_text_field($atts['from']);
		
		// select taking, if not found return empty string
		$taking = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . WATUPRO_TAKEN_EXAMS . " WHERE ID=%d", $taking_id));
		
		if(empty($taking->ID)) return "";
		
		$survey_sql = empty($atts['include_survey_questions']) ? 'AND tQ.is_survey=0' : '';
		
		// select answers join by category
		$answers = $wpdb->get_results($wpdb->prepare("SELECT tA.answer, tA.is_correct, tC.ID as cat_id, tC.name as cat,
			tQ.is_survey as is_survey, tA.points as points, tA.question_id as question_id
			FROM ".WATUPRO_STUDENT_ANSWERS." tA JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.ID = tA.question_id $survey_sql
			LEFT JOIN ".WATUPRO_QCATS." tC ON tC.ID = tQ.cat_id
			WHERE tA.taking_id=%d order by tA.ID", $taking_id));
		
			
		// fill categories array		
		$cats = array();
		foreach($answers as $answer) {			
			if(!isset($cats[$answer->cat_id])) { 
				// create the cat element			
				if(empty($answer->cat)) $answer->cat = __('Uncategorized', 'watupro');
				$cats[$answer->cat_id] = array("id"=>$answer->cat_id, "name"=>$answer->cat, 
					"num_questions"=>0, "num_answered"=>0, "num_correct"=>0, "points"=>0, "max_points"=>0);
			}
			
			$cats[$answer->cat_id]['num_questions']++;
			if(!empty($answer->answer)) $cats[$answer->cat_id]['num_answered']++;
			if(!empty($answer->is_correct)) $cats[$answer->cat_id]['num_correct']++;
			if(!empty($answer->points)) $cats[$answer->cat_id]['points'] += $answer->points;
			if(!empty($answer->max_points)) $cats[$answer->cat_id]['max_points'] += $answer->max_points;
		}	
		
		if($show == 'points') {
			echo "<div id='watupror-pie-chart-all' style='height:".($radius*2+20)."px;'>";
			echo "</div>";
			?>
			<script type="text/javascript">
			 var r = Raphael("watupror-pie-chart-all");
		    r.piechart(<?php echo $radius+10?>, <?php echo $radius+10?>, <?php echo $radius?>, [<?php foreach($cats as $cat): 
		    		$points = ($cat['points'] >= 0) ? $cat['points'] : 0.1;
		    		echo $points.",";
		    		endforeach;?>],	{
					legend: [<?php foreach($cats as $cat) echo '"'.$cat['name'].' - '.$cat['points'].'",'?>],
					legendpos: 'east',
					maxSlices: 10    
		    });
		   </script>     
			<?php
		} // end if points
		
		// output on-page CSS accordingly to the radius
		echo "<style type='text/css'>
			.watupro-flex-item-".$show." {
			text-align: center !important;
		}
		
		.watupro-flex-item svg {
			width: ".($radius*2+round($radius*0.3))."px !important;	
		}
		</style>";
		
		if($show == 'correct') {
			echo '<div class="watupro-flex">';
			foreach($cats as $key => $cat) {
				// calculate % correct and % wrong
				$percent_correct = $cat['num_questions'] ? round(100 * $cat['num_correct'] / $cat['num_questions']) : 0;
				$percent_wrong = 100 - $percent_correct;	
				
				// the following is needed because a pie is not drawn at all otherwise
				if($percent_wrong == 0) $percent_wrong = 0.1;
				if($percent_correct == 0) $percent_correct = 0.1;
				
				echo "<div class='watupro-flex-item watupro-flex-item-".$show."' style='overflow:visible;'>
					<h4>".stripslashes($cat['name'])."</h4>
					<div id='watupror-pie-chart-".$key."' style='min-height:".($radius*3)."px;'>";
				echo "</div>
				</div>";
				?>
				<script type="text/javascript">
				 var r = Raphael("watupror-pie-chart-<?php echo $key;?>");
			    r.piechart(<?php echo $radius+10?>, <?php echo $radius+20?>, <?php echo $radius?>, [<?php echo $percent_correct.','.$percent_wrong?>], {
						legend: ['<?php printf(__('%d%% correct answers', 'watupro'), $percent_correct);?>', '<?php printf(__('%d%% wrong answers', 'watupro'), $percent_wrong);?>'],
						colors: ['<?php echo ($percent_correct < 1 ? 'red':'green')?>', 'red'],
						legendpos: 'bottom',
						maxSlices: 10    
			    });
			   </script>     
			<?php
			} // end foreach cat
			echo '</div>'; // close the container
		} // end if percent
	} // end pie_by_category
}