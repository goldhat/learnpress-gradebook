<?php
// Intelligence initialization actions
require_once(WATUPRO_PATH."/i/models/dependency.php");
require_once(WATUPRO_PATH."/i/models/payment.php");
require_once(WATUPRO_PATH."/i/models/i.php");
require_once(WATUPRO_PATH."/i/models/question.php");
require_once(WATUPRO_PATH."/i/models/grade.php");
require_once(WATUPRO_PATH."/i/controllers/teacher.php");
require_once(WATUPRO_PATH."/i/controllers/payments.php");
require_once(WATUPRO_PATH."/i/controllers/exam.php");
require_once(WATUPRO_PATH."/i/controllers/multiuser.php");
require_once(WATUPRO_PATH."/i/controllers/coupons.php");
require_once(WATUPRO_PATH."/i/controllers/user-choice.php");
add_action('wp_ajax_watupro_lock_details', array("WatuPRODependency", "lock_details"));
add_action('wp_ajax_watupro_pay_with_points', array("WatuPROPayments", "pay_with_points"));
add_action('wp_ajax_watupro_pay_with_moolamojo', array("WatuPROPayments", "pay_with_moolamojo"));

// Paypal IPN
add_filter('query_vars', array("WatuPROPayment", "query_vars"));
add_action('parse_request', array("WatuPROPayment", "parse_request"));
add_action('init', array('WatuPROIntelligence', 'init'));

// extra pages
add_action( 'watupro_admin_menu', array("WatuPROIntelligence", "admin_menu"));

// other filters and actions
add_filter('watu_filter_current_question_text', array('WatuPROIQuestion', 'filter_text'), 10, 4);
add_filter('watupro_filter_view_show_exam', 'watuproi_show_exam', 10, 2);

// shortcodes
add_shortcode('watupro-expand-personality-result', array('WTPIGrade', 'expand_personality_result'));