<?php
// Google Analytics and maybe other tracking 
class WTPTracker {
	// add tracking JS codes
	
	// starting quiz
	static function tracking_quiz_start($exam) {
		$options = get_option('watupro_track_events');
		if(empty($options['track_quiz_start'])) return ''; 
		?>
		WatuPROTrackStart = {'category' : '<?php echo WATUPRO_QUIZ_WORD_PLURAL?>', action: 'start', 'label' : "<?php 
			echo stripslashes($exam->name)?>", 'value' : <?php echo $exam->ID?>};
		<?php
	} // end tracking_quiz_start
	
	// quiz end - including pass / fail cases
	// $options['track_quiz_end'] - whether to track
	// $options['track_quiz_end_mode'] - blank, with grade title, or use grade title as action
	static function tracking_quiz_end($taking_id, $exam, $user_id, $points, $grade_id) {
		global $wpdb;
		$options = get_option('watupro_track_events');
		
		if(empty($options['track_quiz_end'])) return;
		$label = stripslashes($exam->name);
		$action = 'complete';
		
		if($options['track_quiz_end_mode'] != 'blank') {
			$grade = $wpdb->get_var($wpdb->prepare("SELECT gtitle FROM ".WATUPRO_GRADES." WHERE ID=%d", $grade_id));
			
			if($options['track_quiz_end_mode'] == 'append_grade') $label .= ' - '.stripslashes($grade);
			if($options['track_quiz_end_mode'] == 'grade_action' and !empty($grade)) $action = stripslashes($grade);
		}
		
		// output tracking JS
		?>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function(event) {
			WTPTracker.track('<?php echo WATUPRO_QUIZ_WORD_PLURAL?>', '<?php echo $action?>', "<?php echo $label?>", "<?php echo $exam->ID?>");
		});
		</script>
		<?php		
	} // end tracking quiz end

	// displays the settings page	
	static function options() {
		if(!empty($_POST['ok']) and check_admin_referer('watupro_analytics')) {
			$track_quiz_start = empty($_POST['track_quiz_start']) ? 0 : 1;
			$track_quiz_end = empty($_POST['track_quiz_end']) ? 0 : 1;
			
			$options = array('track_quiz_start' => $track_quiz_start, 'track_quiz_end' => $track_quiz_end,
				'track_quiz_end_mode' => sanitize_text_field($_POST['track_quiz_end_mode']));
			
			update_option('watupro_track_events', $options);	
		}
		
		$options = get_option('watupro_track_events');
		
		if(@file_exists(get_stylesheet_directory().'/watupro/reports/tracker-options.html.php')) require get_stylesheet_directory().'/watupro/reports/tracker-options.html.php';
		else require WATUPRO_PATH."/modules/reports/views/tracker-options.html.php";
	} // end options
}