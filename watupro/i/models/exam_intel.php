<?php
class WatuPROIExam {
	// update extra fields when adding and saving
	static function extra_fields($exam_id, $vars) {
		 global $wpdb;
		 $wpdb->query($wpdb->prepare("UPDATE ".WATUPRO_EXAMS." 
		 	SET retake_after=%d, is_personality_quiz=%d WHERE ID=%d", 
		 	$vars['retake_after'], @$vars['is_personality_quiz'], $exam_id));
	}
	
	// check extra limitations for resubmitting the exam
	static function can_retake($exam) {		
		if($exam->retake_after == 0) return true;
				
		global $wpdb, $user_ID;
		
		// see if the latest attempt is "too recent"
		$recent_attempt = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_TAKEN_EXAMS." 
			WHERE exam_id=%d AND user_id=%d AND end_time > '".current_time('mysql')."' - INTERVAL %d HOUR", $exam->ID, $user_ID, $exam->retake_after));
					
		if(!empty($recent_attempt->ID)) {
			// if ratake_after is > 100, let's round to days
			$time = $exam->retake_after>100 ? __('days', 'watupro'):__('hours', 'watupro');
			$retake_after_time = $exam->retake_after>100 ? round($exam->retake_after / 24) : $exam->retake_after;			
			
			
			printf(__("You need to wait at least %d %s after your previous attempt on this test.", 'watupro'), $retake_after_time, $time);
			return false;
		}			
			
		return true;	
	}
	
	// alter price of an exam - for example because this user has taken it already but there is a setting that next takings
	// are cheaper or more expensive
	static function adjust_price(&$exam) {
		global $wpdb, $user_ID;
		
		if(!is_user_logged_in()) return false;
		
		// this is needed only for exams where users have to pay each time
		if(empty($exam->pay_always) or empty($exam->fee)) return false;
		
		$advanced_settings = unserialize(stripslashes($exam->advanced_settings));
		
		// should the price be changed on each attempt?
		if(empty($advanced_settings['attempts_price_change_amt'])) return false;
		
		// so, we have a change defined. Let's see how many paid attempts did this user make so far
		// so we can calculate the adjusted price
		$num_used_payments = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".WATUPRO_PAYMENTS."
			WHERE exam_id=%d AND user_id=%d AND status='used'", $exam->ID, $user_ID));
		if(!$num_used_payments) return false;
		
		$adjustment_amount = $num_used_payments * $advanced_settings['attempts_price_change_amt'];
		
		if($advanced_settings['attempts_price_change_action'] == 'reduce') {
			$new_fee = $exam->fee - $adjustment_amount;
			if($advanced_settings['attempts_price_change_limit'] and $new_fee < $advanced_settings['attempts_price_change_limit']) $new_fee = $advanced_settings['attempts_price_change_limit'];
			if($new_fee < 0) $new_fee = 0; 
		}
		else {
			// increase price
			$new_fee = $exam->fee + $adjustment_amount;
			if($advanced_settings['attempts_price_change_limit'] and $new_fee > $advanced_settings['attempts_price_change_limit']) $new_fee = $advanced_settings['attempts_price_change_limit'];
		}	
		
		// finally assign
		$exam->fee = $new_fee;
		
		return true;
	} // end adjust_price
}