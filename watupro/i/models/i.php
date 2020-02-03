<?php
class WatuPROIntelligence {
	static function init() {
		global $wpdb;
		add_action('watupro_completed_paid_exam', array('WatuPROPayment', 'completed_exam'), 10, 2);		
		add_shortcode('watupro-quiz-bundle', array('WatuPROPayments', 'bundle_button'));
		add_shortcode('watupro-coupon-field', array('WatuPROICoupons', 'coupon_field'));
		add_shortcode('watupro-my-bundles', array('WatuPROPayments', 'my_bundles_shortcode'));
	}	
	
	static function activate() {
		 // DB queries that will run only if Intelligence module is installed
		 global $wpdb;
		 $version = get_option('watupro_db_version');
		 
		 // extra fields in questions			 
		 watupro_add_db_fields( array( array("name" => "correct_gap_points", "type" => "DECIMAL(6,2) NOT NULL DEFAULT '0.00'"),
		 	array("name" => "incorrect_gap_points", "type" => "DECIMAL(6,2) NOT NULL DEFAULT '0.00'"),
		 	array("name" => "sorting_answers", "type" => "TEXT" ),
		 	array("name" => "gaps_as_dropdowns", "type" => "TINYINT UNSIGNED NOT NULL DEFAULT 0"),
		 	array("name" => "slider_transfer_points", "type" => "TINYINT UNSIGNED NOT NULL DEFAULT 0")),
		 		WATUPRO_QUESTIONS);
		 	
		 // extra fields in exams - 3.1
		 watupro_add_db_fields( array( array("name" => "retake_after", "type" => "INT UNSIGNED NOT NULL DEFAULT 0"),
		 	array("name" => "reuse_questions_from", "type" => "VARCHAR(255) NOT NULL DEFAULT ''"),
		 	array("name" => "is_personality_quiz", "type" => "TINYINT UNSIGNED NOT NULL DEFAULT 0")),
		 	WATUPRO_EXAMS );	
		 	
		 // extra field - teacher comments in taking and taking details
		 watupro_add_db_fields(array(
    		array("name"=>"teacher_comments", "type"=>"TEXT"),
    		array("name"=>"personality_grade_ids", "type"=>"TEXT"), /* serialized. For personality quizzes */
    		array("name"=>"last_edited", "type"=>"DATE"), /* when teached last edited this taking */
		 ), WATUPRO_TAKEN_EXAMS);	
			
		 watupro_add_db_fields(array(
    		array("name"=>"teacher_comments", "type"=>"TEXT"),    		
			), WATUPRO_STUDENT_ANSWERS);		
			
		 // dependency mode - points or percentage	
		 watupro_add_db_fields(array(
    		array("name"=>"mode", "type"=>"VARCHAR(100) NOT NULL DEFAULT 'points'")
			), WATUPRO_DEPENDENCIES);
			
		// in 4.1.2 change reuse_questions_from to varchar	
		if($version < 4.12) {
			$wpdb->query("ALTER TABLE ".WATUPRO_EXAMS." CHANGE reuse_questions_from reuse_questions_from VARCHAR(255) NOT NULL DEFAULT ''");
		}
	}
	
	static function admin_menu() {
		$bundle_caps = $coupon_caps = WATUPRO_MANAGE_CAPS;
		$student_caps = current_user_can(WATUPRO_MANAGE_CAPS) ? WATUPRO_MANAGE_CAPS:'read'; // used to be watupro_exams
		if( !WatuPROIMultiUser :: check_access('bundles_access', true)) $bundle_caps = 'administrator';
		if( !WatuPROIMultiUser :: check_access('coupons_access', true)) $coupon_caps = 'administrator';
		
		add_submenu_page(NULL, __("Manually Grade Test Results", 'watupro'), __("Manually Grade Test Results", 'watupro'), WATUPRO_MANAGE_CAPS, 'watupro_edit_taking', array('WatuPROITeacherController', 'edit_taking'));
		
		// payments page
		add_submenu_page(NULL, sprintf(__("%s Payments", 'watupro'), ucfirst(WATUPRO_QUIZ_WORD)), sprintf(__("%s Payments", 'watupro'), ucfirst(WATUPRO_QUIZ_WORD)), WATUPRO_MANAGE_CAPS, 'watupro_payments', array('WatuPROPayments', 'manage'));
		
		// certificate payments page
		add_submenu_page(NULL, __("Certificate Payments", 'watupro'), __("Certificate Payments", 'watupro'), WATUPRO_MANAGE_CAPS, 'watupro_certificate_payments', array('WatuPROPayments', 'certificate_payments'));
		
		// advanced settings
		 add_submenu_page(NULL, __('Advanced Quiz Settings', 'watupro'), __('Advanced Quiz Settings', 'watupro'), WATUPRO_MANAGE_CAPS, 'watupro_advanced', 'watupro_advanced_exam_settings');
		 
		// multiuser config
		add_submenu_page(NULL, __('WatuPRO Multiuser Configurations', 'watupro'), __('WatuPRO Multiuser Configurations', 'watupro'), 'administrator', 'watupro_multiuser', array('WatuPROIMultiUser', 'manage')); 
		
		// payment bundles
		add_submenu_page('watupro_exams', sprintf(__('%s Bundles', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD)), sprintf(__('%s Bundles', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD)), $bundle_caps, 'watupro_bundles', array('WatuPROPayments', 'bundles')); 
		
		// my bundles
		$enable_my_bundles = get_option('watupro_enable_my_bundles');
		if($enable_my_bundles) {
			add_submenu_page('my_watupro_exams', sprintf(__('My %s Bundles', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD)), sprintf(__('My %s Bundles', 'watupro'), ucfirst(WATUPRO_QUIZ_WORD)), $student_caps, 'watupro_my_bundles', array('WatuPROPayments', 'my_bundles')); 
		}
		
		// coupon codes
		add_submenu_page('watupro_exams', __('Coupon Codes', 'watupro'), __('Coupon Codes', 'watupro'), $coupon_caps, 'watupro_coupons', array('WatuPROICoupons', 'manage')); 
	}
	
	// small helper to add extra DB fields if they don't exist
	// DEPRECATED? probably we should use watupro_add_db_fields() instead
	static function add_db_fields($fields, $table) {
		global $wpdb;
		
		// check fields
		$table_fields = $wpdb->get_results("SHOW COLUMNS FROM `$table`");
		$table_field_names = array();
		foreach($table_fields as $f) $table_field_names[] = $f->Field;		
		$fields_to_add=array();
		
		foreach($fields as $field) {
			 if(!in_array($field['name'], $table_field_names)) {
			 	  $fields_to_add[] = $field;
			 } 
		}
		
		// now if there are fields to add, run the query
		if(!empty($fields_to_add)) {
			 $sql = "ALTER TABLE `$table` ";
			 
			 foreach($fields_to_add as $cnt => $field) {
			 	 if($cnt > 0) $sql .= ", ";
			 	 $sql .= "ADD $field[name] $field[type]";
			 } 
			 
			 $wpdb->query($sql);
		}
	}
	
	// load intelligence scripts that don't load on each page
	static function conditional_scripts($exam_id) {
		global $wpdb;
		
		// select exam
		$exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_EXAMS." WHERE ID=%d", $exam_id));
		$exam_id = empty($exam->reuse_questions_from) ? $exam_id : $exam->reuse_questions_from;
		
		// jquery sortable - let's load it only if there are sortable questions in this exam
		$has_sortables = $wpdb->get_var("SELECT COUNT(ID) FROM ".WATUPRO_QUESTIONS."
			WHERE exam_id IN ($exam_id) AND answer_type='sort' AND is_inactive=0");
		if($has_sortables) {
			wp_enqueue_script('jquery-ui-sortable');
		}	
		
		// draggables & dropables (matrix questions)
		$has_droppables = $wpdb->get_var("SELECT COUNT(ID) FROM ".WATUPRO_QUESTIONS."
			WHERE exam_id IN ($exam_id) AND (answer_type='matrix' or answer_type='nmatrix') AND is_inactive=0");
		if($has_droppables) {
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-ui-droppable');
		}
		
		// slider questions
		$has_sliders = $wpdb->get_var("SELECT COUNT(ID) FROM ".WATUPRO_QUESTIONS."
			WHERE exam_id IN ($exam_id) AND answer_type='slider' AND is_inactive=0");
		if($has_sliders) {
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-mouse');
			wp_enqueue_script('jquery-ui-slider');
			wp_enqueue_style('jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		}
		
		// touch/punch if there are any sortables or dragables
		if($has_sortables or $has_droppables or $has_sliders) {
				wp_register_script('jquery-ui-touch-punch', plugins_url('/watupro/i/js/jquery.ui.touch-punch.min.js'), array('jquery-ui-widget', 'jquery-ui-mouse'));
			wp_enqueue_script('jquery-ui-touch-punch');
		}
	} // end conditional scripts
}