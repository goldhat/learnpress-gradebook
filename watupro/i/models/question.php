<?php
// Intelligence specific question queries
class WatuPROIQuestion {
	public static $advanced_settings = "";
	public static $personality_grades = null;
	
	static function edit($vars, $id) {
		 global $wpdb;
		 
		 if($vars['answer_type']=='sort' or $vars['answer_type']=='matrix' or $vars['answer_type']=='nmatrix') {
		 	// sorting questions use gaps fields to avoid adding unnecessary fields
		 	$vars['correct_gap_points'] = $vars['correct_sort_points'];
		 	$vars['incorrect_gap_points'] = $vars['incorrect_sort_points'];
		 }
		 
		 if($vars['answer_type']=='slider') {
		 	// sorting questions use gaps fields to avoid adding unnecessary fields
		 	$vars['correct_gap_points'] = intval(@$vars['slide_from']);
		 	$vars['incorrect_gap_points'] = intval(@$vars['slide_to']);
		 }
		 
		 // this is required both here and in the base Question model to avoid bugs
	    if($vars['answer_type'] == 'checkbox' and !empty($vars['calculate_checkbox_whole'])) {
		 	$vars['correct_gap_points'] = $vars['correct_checkbox_points'];
		 	$vars['incorrect_gap_points'] = $vars['incorrect_checkbox_points'];
		 }
		 
		 if(empty($vars['correct_gap_points'])) $vars['correct_gap_points'] = 0;
		 if(empty($vars['incorrect_gap_points'])) $vars['incorrect_gap_points'] = 0;
		 if(empty($vars['sorting_answers'])) $vars['sorting_answers'] = '';
		 $slider_transfer_points = intval(@$vars['slider_transfer_points']);
		 
	   $sql = $wpdb->prepare("UPDATE ".WATUPRO_QUESTIONS." SET 
	   	correct_gap_points = %s, incorrect_gap_points=%s, sorting_answers=%s, gaps_as_dropdowns=%d, slider_transfer_points=%d
	   	WHERE ID = %d", 
	   	$vars['correct_gap_points'], $vars['incorrect_gap_points'], $vars['sorting_answers'], @$vars['gaps_as_dropdowns'], $slider_transfer_points, $id);
	   $wpdb->query($sql);	
	}
	
	// display a question like fill the gaps etc
	static function display($question, $qct, $question_count, $inprogress_details, $practice_mode = false) {		
		  $question_design = empty($question->design) ? '' : unserialize(stripslashes($question->design));	
		
			// handle right to left languages		 
		  $dir = is_rtl() ? " dir='rtl' " : "";
		  $rtl_class = empty(self :: $advanced_settings['is_rtl']) ? '' : 'watupro-rtl';
		  
		  $question_number = (empty(self :: $advanced_settings['dont_display_question_numbers']) and empty($practice_mode))? "<span class='watupro_num'>$qct. </span>"  : '';
			switch($question->answer_type) {
				case 'gaps':				
					// parse {{{xxxx}}} into input fields - pattern {{{([^}}}])*}}}
					$html = stripslashes($question->question);
					$matches = array();
					preg_match_all("/{{{([^}}}])*}}}/", $html, $matches);
					
					foreach($matches[0] as $cnt => $match) {
						 $value = ""; // inprogress value
						 if(!empty($inprogress_details[$question->ID][$cnt])) $value = $inprogress_details[$question->ID][$cnt];
						 
						 // CSS style passed as last part of the gap?
						 $parts = explode("|", $match);		
						 $cnt_parts = count($parts);			 
						 
						 if(count($parts) > 1) $maybe_style = array_pop($parts);
						 $css_style = (preg_match("/^style=/", @$maybe_style)) ? str_replace('}}}', '', $maybe_style)	 : '';		
						 if(!empty($css_style)) $cnt_parts--;
						 
						 // at this point we have removed the possible style. Let's see if there is a placeholder
						 if(count($parts) > 1) {						 	
						 	// if there was style as last part, we need to pop the parts again. Otherwise use $maybe_style
						 	if(!empty($css_style)) $maybe_placeholder = array_pop($parts);
						 	else $maybe_placeholder = $maybe_style; 
						 }	
						 //echo $maybe_placeholder;						 
						 
						 $placeholder = (preg_match("/^placeholder=/", @$maybe_placeholder)) ? str_replace('}}}', '', $maybe_placeholder)	 : '';
						 if(!empty($placeholder)) $cnt_parts--;	
						 
						 // when there is no passed CSS use the ones from the design section. Do this after finding $placeholder above because we check for $css_style var
						 if(empty($css_style) and !empty($question_design['gaps_width'])) {
						 	$css_style = 'style="min-width:'.$question_design['gaps_width'].'px;width:'.$question_design['gaps_width'].'px !important;"';
						 }		
						
						 $cnt++;
						 if(!empty($question->gaps_as_dropdowns) and $cnt_parts > 1) {
						 	// this gap will be displayed as drop-down selector
						 	$input = '<select name="gap_'.$question->ID.'_'.$cnt.'" class="watupro-gap answer answerof-'.$question->ID.'" '.$css_style.'>
						 		<option value=""></option>';
						 		
						 	$parts = explode("|", $match);	
						 	shuffle($parts);
						 	foreach($parts as	$part) {
						 		if(preg_match("/^style=/", $part) or preg_match("/^placeholder=/", $part)) continue;						 		
						 		$part = str_replace(array('{{{','}}}'), '', $part);
						 		if(strcmp($part, $value) == 0) $selected = ' selected';
						 		else $selected = '';
						 		$input .= '<option value="'.$part.'"'.$selected.'>' . $part . '</option>';
						 	}
						 	$input .= '</select>';	
						 }	
						 else { // default "fill the gaps" behavior						 		
						 	$input = '<input type="text" size="10" name="gap_'.$question->ID.'_'.$cnt.
						 		'" class="watupro-gap answer answerof-'.$question->ID.'" value="'.$value.'" '.$dir.' '.$css_style.' '.$placeholder.'>';
						 }		
						 
						 $match = watupro_preg_escape($match);
						 $html = preg_replace("/".$match."/", $input, $html, 1);						
					}					
					
					echo wpautop(stripslashes( $question_number . WTPQuestion :: flag_review($question, $qct)) .  $html, 0);
				break;	
				case 'sort':		
					$inprogress_values = array();
					if(!empty($inprogress_details[$question->ID][0])) {
						$inprogress_values = explode("|", urldecode(stripslashes($inprogress_details[$question->ID][0])));
						array_pop($inprogress_values);
					}

					if(empty($inprogress_values)) {					
						$sort_values = explode("\n",stripslashes(trim($question->sorting_answers)));
						shuffle($sort_values);
					} 
					else $sort_values = $inprogress_values;	
					
					$sorted_answers = ''; // let's initially record how they are sorted
					
					echo $question_number . WTPQuestion :: flag_review($question, $qct);					
					echo  watupro_nl2br(stripslashes($question->question));
					echo  "<!-- end question-content--></div>"; // end question-content
					echo "<div class='question-choices watupro-sortable-choices'>";					
					
					$horizontal_style = ($question->compact_format == 2) ? 'watupro-sortable-horizontal' : '';
					echo "<ul id='watuPROSortable".$question->ID."' class='watupro-sortable ".$horizontal_style."'>";
					foreach($sort_values as $ct=>$svalue):
							$ct++;
							$svalue = trim($svalue);
							$sorted_answers .= $svalue."|";?>
						<li id="watuPROSortable<?php echo $question->ID?>_<?php echo $ct?>"><?php echo $svalue?><!--|||<?php echo urlencode($svalue);?>--></li>
										
					<?php endforeach;
					echo "</ul>";
					echo '<input type="hidden" name="answer-'.$question->ID.'[]" id="watuPROSortableValue'.$question->ID.'" value="'.urlencode($sorted_answers).'" class="answer answerof-'.$question->ID.'">';
					?>					
					<script type="text/javascript">
					document.addEventListener('DOMContentLoaded', function(event) {
					    jQuery( "#watuPROSortable<?php echo $question->ID?>" ).sortable({								
								stop: function(event, ui) { WatuPROSort.sortable(event, ui) }				    	
					    	});					    
					  });
					</script><?php echo "</div><!-- end question-choices-->";
				break;
				
				case 'matrix':
				case 'nmatrix':
					$inprogress_values = @$inprogress_details[$question->ID];
												
					$matches = $question->q_answers;
					$lefts = array();
					$rights = array();
					
					foreach($matches as $match) {
						list($left, $right) = explode('{{{split}}}', $match->answer);
						$lefts[] = array("left"=>$left, "match_id"=>$match->ID);
						$rights[] = $right;
					}
					
					shuffle($rights);
					
					// display the rights div
					echo $question_number . WTPQuestion :: flag_review($question, $qct);
					echo watupro_nl2br(stripslashes($question->question));
					
					echo "<div class='question-choices'>";	 // we open wrapper div here, closed right after the watupro-matrix-droppable table
					if($question->answer_type == 'matrix') {							
						echo '<div class="watupro-matrix-right">';
						foreach($rights as $right) {
							// if we have it in $in_progress don't display it here
							if(is_array($inprogress_values) and in_array(md5($right), $inprogress_values)) continue;
							echo '<div class="watupro-matrix-draggable watuPRODraggable'.$question->ID.'">'.stripslashes($right).'<!--WTPMD5'.md5($right).'--></div>';
						}
						echo '</div>'; // end right matches					
						echo '</div>'; // end question choices
					}
					
					// now create the droppable area
					echo '<table class="question-choices watupro-matrix-droppable" id="watuPROMatrixDroppable_'.$question->ID.'">';
					
					foreach($lefts as $cnt=>$left) {
						$prefilled_class = (empty($inprogress_values[$cnt]) or $question->answer_type == 'nmatrix') ? '' : ' watupro-droppable-hover';
						echo '<tr><td class="watupro-matrix-left-cell">'.stripslashes($left['left']).'</td>
						<td class="watupro-matrix-right-cell watuPRODroppable'.$question->ID.$prefilled_class.'" id="watuPRODroppableCell'.$left['match_id'].'">';
						
						if($question->answer_type == 'nmatrix') {
							echo "<div class='question-choices'>";		
							echo '<div class="watupro-matrix-right">';
							foreach($rights as $rct => $right) {
								// if we have it in $in_progress don't display it here
								$drop_id = 'watuPRODroppableCell'.$left['match_id'];								
								$selected_class = '';
								
								if(is_array($inprogress_values) and self :: unmd5($inprogress_values[$cnt], $question->q_answers) == $right) $selected_class = 'watupro-nmatrix-selected';
								else {
									if(!empty($inprogress_values[$cnt])) $selected_class = 'watupro-nmatrix-hidden';
								}
								echo '<div id="watuproNMatrixSelection-'.$cnt.'-'.$rct.'" class="'.$selected_class.' watupro-matrix-draggable watupro-nmatrix-answer-'.$rct.'" onclick="'."WatuPROINMatrix.sel(this, '".$drop_id."', ".$rct.");return false;".'">'.stripslashes($right).'<!--WTPMD5'.md5($right).'--><p class="watupro-nmatrix-unselect">'.__('Unselect', 'watupro').'</p></div>';
							}
							echo '</div>'; // end right matches					
							echo '</div>'; // end question choices
						}
						else { 
							// legacy matrix
							// anything from $inprogress?
							if(is_array($inprogress_values) and !empty($inprogress_values[$cnt])) {
								echo '<div class="watupro-matrix-draggable watuPRODraggable'.$question->ID.'">'.stripslashes(self :: unmd5($inprogress_values[$cnt], $question->q_answers)).'<!--WTPMD5'.$inprogress_values[$cnt].'--></div>'; 
							}		
						}				
						
						echo '<input type="hidden" class="answerof-'.$question->ID.' watupro-nmatrix-answer-field"  id="field-watuPRODroppableCell'.$left['match_id'].'" name="matrix-left-'.$left['match_id'].'" value="'.@$inprogress_values[$cnt].'"></td></tr>';
					}
						
					echo '</table></div>'; // we also close the main question choices wrapper div here! Don't remove this closing div
					if($question->answer_type == 'matrix') :?>
					<script type="text/javascript">
					document.addEventListener('DOMContentLoaded', function(event) {
					    jQuery( ".watuPRODraggable<?php echo $question->ID?>" ).draggable({
							 revert: "invalid", // when not dropped, the item will revert back to its initial position
      					 containment: "document",		
      					 appendTo: 'body',				    	
					    });					    
						jQuery(".watuPRODroppable<?php echo $question->ID?>").droppable({
							accept: ".watuPRODraggable<?php echo $question->ID?>",
							greedy: true,
							hoverClass: 'watupro-droppable-hover',
							drop: function(event, ui) {
								WatuPROIDroppable.drop(event, ui);
								jQuery(this).droppable('option', 'accept', ui.draggable);
							},
							out: function(event, ui) {
								jQuery(this).droppable('option', 'accept', ".watuPRODraggable<?php echo $question->ID?>");
							}
						});    
					});
					</script>
					<?php
					endif; // end legacy matrix JS
				break; // end matrix/nmatrix		
				
				case 'slider':
					echo wpautop( $question_number . WTPQuestion :: flag_review($question, $qct) .  stripslashes($question->question),0);
					
					// current value
					$value = empty($inprogress_details[$question->ID][0]) ? $question->correct_gap_points : $inprogress_details[$question->ID][0];
					$value = intval($value);
					
					echo "<div class='watupro-slider' id='watuPROSlider".$question->ID."'></div><div id='watuPROSliderValue".$question->ID."'>".$value."</div>";
					echo '<input type="hidden" name="answer-'.$question->ID.'[]" class="answerof-'.$question->ID.'" id="field-watuPROSliderValue'.$question->ID.'" value="'.$value.'">';
					?>
					<script type="text/javascript">
					document.addEventListener('DOMContentLoaded', function(event) {
					   jQuery('#watuPROSlider<?php echo $question->ID?>').slider({
					   	min: <?php echo intval($question->correct_gap_points);?>,
					   	max: <?php echo intval($question->incorrect_gap_points);?>,
					   	value: <?php echo $value?>,
					   	slide: function( event, ui ) {
					   		jQuery('#watuPROSliderValue<?php echo $question->ID?>').html(ui.value);
					   		jQuery('#field-watuPROSliderValue<?php echo $question->ID?>').val(ui.value);
					   	}
					   });
					});
					</script>
					<?php
				break;	 // end slider
			}
	}
	
	// processes specific types of questions (like gaps) on submit
	// fill the gaps will not take effect in personality quizzes (makes no sense)
	// sorting questions will assign points for each grade depending on the sorted position. 
	// For example if there are 3 items the top one gets 3 points, next one 2, the bottom one - 1
	// When the admin enters the positions they will be matched to the grades (ordered by ID) 
	static function process($question, $user_answers) {	
		global $wpdb;
		$advanced_settings = self :: $advanced_settings;
		$is_empty = 0; // initally set as answered
		$rtl_class = empty(self :: $advanced_settings['is_rtl']) ? '' : 'watupro-rtl';
		
		switch($question->answer_type) {
			case 'gaps':							
				$html = stripslashes($question->question);	
				$matches = array();
				preg_match_all("/{{{([^}}}])*}}}/", $html, $matches);
				$points = 0;	
				$max_points = sizeof($matches[0]) * $question->correct_gap_points;	
				$any_non_empty = 0; // any answered gaps? for now 0
							
				foreach($matches[0] as $cnt=>$match) {					
					// in case we come from non-ajax there will be values like $_POST['gap_{questionID}_1'] etc
					if(isset($_POST['gap_'.$question->ID.'_'.($cnt+1)])) $user_answer = stripslashes($_POST['gap_'.$question->ID.'_'.($cnt+1)]);
					else $user_answer = stripslashes(@$user_answers[$cnt]);
					
					// avoid wrong "Question was not answered" text
					if(!empty($user_answer)) { $_POST['answer-'.$question->ID][] = $user_answer; $any_non_empty = 1;}
					
					// compare to know if it's correct or not
					$match = trim($match);
					
					// should we replace multiple spaces inside the match?
					if(!empty($advanced_settings['gaps_replace_spaces'])) {
						$user_answer = preg_replace('/\s+/', ' ' , $user_answer);
					}					
					
					$compare_match = str_replace("{{{", "", $match);
					$compare_match = str_replace("}}}", "", $compare_match);
					
					// by parsing the match on possible parts we'll cover both simple and complex matches
					$parts = explode('|', $compare_match);
					$cnt_parts = count($parts);
					$is_correct = 0;
					
					// handle cases with a dropdown
					if(count($parts) > 1) $maybe_style = array_pop($parts);
					$css_style = (preg_match("/^style=/", @$maybe_style)) ? str_replace('}}}', '', $maybe_style)	 : '';	
					if(!empty($css_style)) $cnt_parts--;
					if(empty($css_style) and !empty($maybe_style)) $parts[] = $maybe_style;			
					
					if(!empty($question->gaps_as_dropdowns) and $cnt_parts > 1) $dropdown_mode = true;
					else $dropdown_mode = false;	
					if($dropdown_mode) {
						// in this case just shrink parts to the 1st element
						$parts = array_slice($parts, 0, 1);
					}
					
					foreach($parts as $pct=>$part) {
						if(($pct+1 == count($parts)) and preg_match("/^style=/", $part)) continue; // last part might be passed style instead of real part
						
						// because strcasecmp does not work correctly on Unicode, we'll have to introduce two local vars whichwill be strotolowered if case insensitive
						$cmp_user_answer = trim($user_answer);
						$cmp_part = trim($part); 
						
						if(empty($question->open_end_mode) or $question->open_end_mode != 'sensitive_gaps') {
							$cmp_user_answer = mb_strtolower($cmp_user_answer);
							$cmp_part = mb_strtolower($cmp_part);
						}
						
						// jQuery will remove htmlentities at least from dropdowns so we have to make sure user answer is also checked as htmlentitied version						
						if(strcmp($cmp_user_answer, $cmp_part) == 0 or strcmp(htmlentities($cmp_user_answer), $cmp_part) == 0) {							
							$is_correct = 1;
							break;
						}
					}	
					
					// handle numeric from-to value entered as: {{{x...y}}}
					if(preg_match('/([0-9.]+)\.\.\.([0-9.]+)/', $compare_match)) {
						$parts = explode('...', $compare_match);
						
						if($parts[0] <= trim($user_answer) and $parts[1] >= trim($user_answer)) $is_correct=1;
						
						// if user's answer contains decimal but decimals are not allowed, revert back to not correct
						if(strstr($user_answer, '.') and !strstr($parts[0], '.')) $is_correct = 0;
					}		
					
					// now add points and mark it
					if($is_correct) {
						 $img = '<img alt="Correct" src="'.WATUPRO_URL.'correct.png" hspace="5">';
						 $points += $question->correct_gap_points;						
					}	 
					else {						
						// reveal correct?
						$revealed_answer = '';
						if(!empty(self :: $advanced_settings['reveal_correct_gaps'])) {
							$revealed_answer = preg_replace("/(\{\{\{|\}\}\})*/", '', $match);
							
							if($dropdown_mode) {
								$revealed_answer = $parts[0];
							}
							else {
								$revealed_answer = '';
								
								foreach($parts as $part) {
									if(preg_match("/^style=/", $part) or preg_match("/^placeholder=/", $part)) break; // these are at the end so we just break
									if(!empty($revealed_answer)) $revealed_answer .= ' '.__('or', 'watupro').' ';
									$revealed_answer .= stripslashes($part);
								}
							} // end constructing revealed answer
							
							if(strstr($revealed_answer, '$')) $revealed_answer = str_replace('$', '&dollar;', $revealed_answer);
							$revealed_answer = '<span class="watupro-revealed-gap">' . sprintf(_wtpt(__('(correct answer: %s)', 'watupro')), $revealed_answer) . '</span>';
						}						
						
						$img = $revealed_answer.'<img alt="Wrong" src="'.WATUPRO_URL.'wrong.png" hspace="5">';
						if(empty($user_answer)) $user_answer = _wtpt(__('[no answer]', 'watupro'));
						$points += $question->incorrect_gap_points;						
					}
	
					// handle nasty problems with dollar sign
					if(strstr($match, '$')) $match = str_replace('$', '&dollar;', $match);
					if(strstr($user_answer, '$')) $user_answer = str_replace('$', '&dollar;', $user_answer);					
					if(strstr($html,'$')) $html = str_replace('$', '&dollar;', $html);
					
					$match = watupro_preg_escape($match);
					$user_answer = watupro_preg_escape($user_answer);
				
					$html = preg_replace('/'.$match.'/', '<span class="user-answer">'.stripslashes($user_answer).'</span>&nbsp;'.$img, $html, 1);					
				}		
				
			//	$html = stripslashes($html);
				$html .= "</div>"; // has to close the question contents div	
				
				if(!$any_non_empty) $is_empty = 1;			
			break;
			
			case 'sort':			
				$s_values = explode("\n", stripslashes(trim($question->sorting_answers)));				
				$html = '';
				$max_points = sizeof($s_values) * $question->correct_gap_points;	
				$points = 0;
				// reconfigure user answer because in this question type it comes as a single value separated by |
				$user_answer = @$user_answers[0];
				$user_answers = explode("|", urldecode($user_answer));
				array_pop($user_answers); // the last one doesn't play because the string ends with |
				$all_correct = true; // used when question is treated as a whole
				
				foreach($user_answers as $cnt=>$user_answer) {		
					$user_answer = stripslashes($user_answer);								
					foreach($s_values as $sct=>$s_value) {
						if($sct!=$cnt) continue;
						$s_value = stripslashes($s_value);
												
						if(strcmp(trim($s_value), trim($user_answer)) == 0) {
							$img='<img alt="Correct" src="'.WATUPRO_URL.'/correct.png" hspace="5">';
						 	$points += $question->correct_gap_points;
						}
						else {
							$img='<img alt="Wrong" src="'.WATUPRO_URL.'/wrong.png" hspace="5">';
							$points += $question->incorrect_gap_points;
							$all_correct = false;
						} 
					}					
					if($question->is_survey) $img = '';
					$html .= "<li>".$user_answer.'&nbsp;'.$img."</li>";
				}
				
				// if we treat question as a whole, points are calculated in different way
				if(!empty($question->calculate_whole)) {
					$max_points = $question->correct_gap_points;
					$points = $all_correct ? $question->correct_gap_points : $question->incorrect_gap_points;
				}		
			break;
			
			case 'matrix':
			case 'nmatrix':
				$all_correct = true; // used when question is treated as a whole
				$max_points = sizeof($question->q_answers) * $question->correct_gap_points;	
				$points = 0;
				$any_non_empty = 0;
				
				$html = "<table class='watupro-matrix-table'>";
				foreach($question->q_answers as $cnt=>$match) {
					list($left, $right) = explode('{{{split}}}', $match->answer);
					$html .= "<tr><td>".stripslashes($left)."</td>";
					
					// when no_ajax
					if(isset($_POST['matrix-left-'.$match->ID]))  $user_answers[$cnt] = $_POST['matrix-left-'.$match->ID];
					// avoid wrong "Question was not answered" text
					if(!empty($user_answers[$cnt])) {$_POST['answer-'.$question->ID][] = $user_answers[$cnt]; $any_non_empty = 1;}
				
					if(!empty($user_answers[$cnt]) and md5($right) == $user_answers[$cnt]) {
						$img='<img alt="Correct" src="'.WATUPRO_URL.'/correct.png" hspace="5">';
						$points += $question->correct_gap_points;
					}
					else {
						$img='<img alt="Wrong" src="'.WATUPRO_URL.'/wrong.png" hspace="5">';
						$points += $question->incorrect_gap_points;
						$all_correct = false;
					}

					if(!empty($advanced_settings['no_checkmarks'])) $img = '';
					
					$html .= "<td>".stripslashes( self :: unmd5(@$user_answers[$cnt], $question->q_answers) ).' '.$img."</td></tr>";
				}				
				
				$html .= "</table>";
				if(!$any_non_empty) $is_empty = 1;	
				
				// if we treat question as a whole, points are calculated in different way
				if(!empty($question->calculate_whole)) {
					$max_points = $question->correct_gap_points;
					$points = $all_correct ? $question->correct_gap_points : $question->incorrect_gap_points;
				}	
			break;
			
			// sliders user "correct_gap_points" for min and "incorrect_gap_points" for max to save from creating new DB columns 
			case 'slider':
				// print_r($user_answers);
				$html = "";
				$user_answer = intval(@$user_answers[0]);
				$points = $max_points = $is_correct = 0;
				$user_answer_class = ($question->is_survey or !empty($_watu->this_quiz->is_personality_quiz)) ? 'user-answer-unrevealed' : 'user-answer';
				$class = 'answer '.$user_answer_class;
				$answer_text = sprintf(__('%d out of %d', 'watupro'), $user_answer, intval($question->incorrect_gap_points));		
				
				// this is used to calculate whether asnwer is correct here in process()  method. Important when "slider transfer points" is true
				// because question->calc_answer assumes correct if points are positive, which won't be true in this case.
				// the variable $is_correct_calculated will be used to override this				
				$is_correct_calculated = false; 	
				
				$output_sent = false;
				foreach($question->q_answers as $answer) {
					if(!strstr($answer->answer, ',')) continue;
					if($answer->point > $max_points) $max_points = $answer->point;
					
					// find the user's answer
					$answer->answer = str_replace(' ', '', $answer->answer); 
					list($from, $to) = explode(",", $answer->answer);		
					
					if($from <= $user_answer and $user_answer <= $to) {
						// this is the given answer
						$points = $answer->point;												
						if($answer->correct and !$question->is_survey) {
								$class .= ' correct-answer'; 
								$is_correct_calculated = true;
						}			
						$sr_text = watupro_screen_reader_text($class);			
						$html .= "<ul><li class='$class'><span class='answer'><!--WATUEMAIL".$class."WATUEMAIL-->" . $answer_text . "</span>$sr_text</li></ul>\n";			
						$output_sent = true;			
						break;
					}			
				} // end foreach
				
				// if the user's answer was not covered by admin we have to show it here
				if(!$output_sent) $html .= "<ul><li class='$class'><span class='answer'><!--WATUEMAIL".$class."WATUEMAIL-->" . $answer_text . "</span></li>$sr_text</ul>\n";
				
				// override answer points and transfer directly instead?
				if(!empty($question->slider_transfer_points)) $points = $user_answer;
			break;
		}		
		
		return array($points, $html, $max_points, @$is_correct_calculated, $is_empty);
	} // end process question
	
	// small helper currently used by matrix questions. Compares the encoded answer
	// to md5 of all "right" parts of the answers to retrieve the decoded answer
	// curently used by matrix questions 
	static function unmd5($encoded, $answers) {
		foreach($answers as $answer) {
			list($left, $right) = explode('{{{split}}}', $answer->answer);			
			if(md5($right) == $encoded) return $right; 
		}
		
		return ''; // just in case
	}
	
	// displays option to reuse questions from another quiz
	static function reuse_questions($exam, &$intelligence_display) {
		global $wpdb, $user_ID;
		$reused_exams = explode(",", $exam->reuse_questions_from);
		$advanced_settings = unserialize( stripslashes($exam->advanced_settings) );
		
		if(!empty($_POST['save_reuse'])) {
			// when the checkbox is unchecked, vanish the dropdown selection
			if(empty($_POST['reuse_questions'])) $_POST['reuse_questions_from'] = 0;			
			
			$reuse_questions_from = @implode(",", watupro_int_array(@$_POST['reuse_questions_from']));
			$advanced_settings['filter_reuse_cat_id'] = intval($_POST['reuse_cat_id']);
			$advanced_settings['filter_reuse_title'] = sanitize_text_field($_POST['reuse_title']);
			$advanced_settings['filter_reuse_comments'] = sanitize_text_field($_POST['reuse_comments']);
			$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_EXAMS." SET reuse_questions_from=%s, advanced_settings=%s 
				WHERE ID=%d", $reuse_questions_from, serialize($advanced_settings), $exam->ID));
				
			$reused_exams = @$_POST['reuse_questions_from'];
		}
		
		$multiuser_access = WatuPROIMultiUser::check_access('exams_access');	
		$own_sql = ($multiuser_access == 'own') ? $wpdb->prepare(" AND tE.editor_id = %d ", $user_ID) : "";
		
		$cat_sql = (!empty($advanced_settings['filter_reuse_cat_id'])) ? $wpdb->prepare(" AND tE.cat_id=%d ", $advanced_settings['filter_reuse_cat_id']) : '';
		$title_sql = empty($advanced_settings['filter_reuse_title']) ? '' : $wpdb->prepare(" AND tE.name LIKE %s ", '%'.$advanced_settings['filter_reuse_title'].'%');
		$comment_sql = empty($advanced_settings['filter_reuse_comments']) ? '' : $wpdb->prepare(" AND tE.admin_comments LIKE %s ", '%'.$advanced_settings['filter_reuse_comments'].'%');
		
		// select other existing exams
		$exams = $wpdb->get_results("SELECT tE.* 
			FROM ".WATUPRO_EXAMS." tE JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.exam_id = tE.ID 
			WHERE tE.ID!=". $exam->ID ." AND tE.reuse_questions_from=0 $own_sql $cat_sql $title_sql $comment_sql
			GROUP BY tE.ID ORDER BY tE.name");
			
		if(!empty($reused_exams) and !empty($reused_exams[0])) $intelligence_display = "style='display:none;'";	
		
		// select categories to allow filter by category
		$reuse_cats = $wpdb->get_results("SELECT * FROM ".WATUPRO_CATS." ORDER BY name");
		
		if(@file_exists(get_stylesheet_directory().'/watupro/i/reuse_questions.php')) require get_stylesheet_directory().'/watupro/i/reuse_questions.php';
		else require WATUPRO_PATH."/i/views/reuse_questions.php";
	}
	
	// filters the reuse quizzes by user defined criteria
	static function select_reuse_quizzes() {
		global $wpdb;
		
		$exam = $wpdb->get_row($wpdb->prepare("SELECT ID, name, reuse_questions_from FROM ".WATUPRO_EXAMS." WHERE ID=%d", intval($_POST['exam_id'])));
				
		$multiuser_access = WatuPROIMultiUser::check_access('exams_access');	
		$own_sql = ($multiuser_access == 'own') ? $wpdb->prepare(" AND tE.editor_id = %d ", $user_ID) : "";
		
		$cat_id = intval($_POST['reuse_cat_id']);
		$cat_sql = $cat_id ? $wpdb->prepare(" AND tE.cat_id=%d ", $cat_id) : '';
		
		
		$title = sanitize_text_field($_POST['reuse_title']);
		$title_sql = empty($title) ? '' : $wpdb->prepare(" AND tE.name LIKE %s ", '%'.$title.'%');
		
		$comments = sanitize_text_field($_POST['reuse_comments']);
		$comment_sql = empty($comments) ? '' : $wpdb->prepare(" AND tE.admin_comments LIKE %s ", '%'.$title.'%');		
		
		$exams = $wpdb->get_results("SELECT tE.* 
			FROM ".WATUPRO_EXAMS." tE JOIN ".WATUPRO_QUESTIONS." tQ ON tQ.exam_id = tE.ID 
			WHERE tE.ID!=".intval($_POST['exam_id'])." AND tE.reuse_questions_from=0 $own_sql 
			$cat_sql $title_sql $comment_sql
			GROUP BY tE.ID ORDER BY tE.name");
			
		$reused_exams = explode(",", $exam->reuse_questions_from);	
			
		$output = '<option value="0">'.__('- Please select -', 'watupro').'</option>';
		foreach($exams as $ex) {
			$output .= '<option value="'.$ex->ID.'"'. ((@in_array($ex->ID, $reused_exams)) ? "selected" : '').'>'.stripslashes($ex->name) . ' (ID '.$ex->ID.')</option>'."\n";
		}
		 
		return $output; 
	}
	
	// filter question processed text for some reason
	// for example in advanced settings we may have disabled right/wrong checkmarks
	static function filter_text($current_text, $qct, $question_content, $is_correct) {
		$advanced_settings = self :: $advanced_settings;
		$_question = new WTPQuestion();
		
		// remove checkmarks if so is selected
		if(!empty($advanced_settings['no_checkmarks']) or (!empty($advanced_settings['no_checkmarks_unresolved']) and !$is_correct) ) {
			$current_text = $_question->display_unresolved($current_text);
		}	
		
		return $current_text;
	} // end filter_text
	
	// saves the answers of a match / matrix question
	static function save_matrix($question_id, $old_answers) {
		global $wpdb;
				
		// first edit old answers (and delete these that are emptied)
		foreach($old_answers as $answer) {
			if(empty($_POST['left_match_'.$answer->ID])) {
				$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPRO_ANSWERS." WHERE ID=%d", $answer->ID));
				continue;
			}
			
			// if not empty, save
			$ans_text = $_POST['left_match_'.$answer->ID].'{{{split}}}'.$_POST['right_match_'.$answer->ID];
			
			$wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_ANSWERS." SET answer=%s WHERE ID=%d", $ans_text, $answer->ID));
		}
		
		// now add new matches
		if(!empty($_POST['new_matches_left'])) {
			foreach($_POST['new_matches_left'] as $cnt=>$left) {
				if(empty($left) or empty($_POST['new_matches_right'][$cnt])) continue;
				
				$ans_text = $left.'{{{split}}}'.$_POST['new_matches_right'][$cnt];
				
				$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRO_ANSWERS." SET 
					question_id=%d, answer=%s, sort_order=%d",
					$question_id, $ans_text, $cnt + 1));
			}
		}		
	} // end save_matrix
	
	// calculate personality results on sorting questions
	static function sort_question_personality($question, $user_answers, &$user_grade_ids) {
		global $wpdb;
		
		// grades already selected?
		if(empty( self :: $personality_grades)) {
			$reuse_default_grades = $wpdb->get_var($wpdb->prepare("SELECT reuse_default_grades FROM ".WATUPRO_EXAMS." WHERE ID=%d", $question->exam_id));
			$grade_exam_id =  $reuse_default_grades ? 0 : $question->exam_id;
			 $grades = $wpdb->get_results($wpdb->prepare("SELECT ID, gtitle FROM ".WATUPRO_GRADES." 
				WHERE exam_id=%d AND cat_id=0 AND percentage_based=0 ORDER BY ID", $grade_exam_id));				
			self :: $personality_grades = $grades;
		}	
		$grades = self :: $personality_grades;		
		
		$s_values = explode("\n", stripslashes(trim($question->sorting_answers)));				
		$top_points = sizeof($s_values);	
		
		// reconfigure user answer because in this question type it comes as a single value separated by |
		$user_answer = $user_answers[0];
		$user_answers = explode("|", urldecode($user_answer));
		array_pop($user_answers); // the last one doesn't play because the string ends with |
		
		// now foreach $s_values you have to check on which position the user has sorted it
		// to figure out points and add it as many times to the $user_grade_ids array	
		foreach($s_values as $svcnt=>$s_value) {
			foreach($user_answers as $cnt=>$answer) {
				if(strcmp(trim($s_value), trim($answer)) == 0) {					
					$times_to_add = $top_points - $cnt;
					
				   if($times_to_add and !empty($grades[$svcnt]->ID)) {
				   	// add this grade ID $times_to_add times to $user_grade_ids
				   	for($i = 0; $i < $times_to_add; $i++) $user_grade_ids[] = $grades[$svcnt]->ID;
					}
				}
			} // end foreach answer
		}	// end foreach value			
		
	} // end calculating personality on sorting questions
}