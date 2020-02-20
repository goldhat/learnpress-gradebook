<?php

/**
 * Plugin Name:			WatuPro GradeBook
 * Plugin URI:			http://eatbuildplay.com
 * Description:			Organize quizzes and students into gradebooks for swift reporting.
 * Version:					1.0.6
 * Author:					Casey Milne, Eat/Build/Play
 * Author URI:			http://eatbuildplay.com
 *
 * Text Domain: watupro-gradebook
 * Domain Path: /languages/
 *
 */

define('WATUPRO_GRADEBOOK_PATH', plugin_dir_path( __FILE__ ));
define('WATUPRO_GRADEBOOK_URL', plugin_dir_url( __FILE__ ));

class WatuProGradeBook {

	public function __construct() {

		require_once( WATUPRO_GRADEBOOK_PATH . '/inc/meta-box/meta-box.php');
		require_once( WATUPRO_GRADEBOOK_PATH . '/inc/classes.php');

		WatuProGradeBookClasses::init();

		add_action( 'template_redirect',array('WatuProGradeBook', 'downloadReport'));
		add_action( 'rwmb_enqueue_scripts', array('WatuProGradeBook', 'script'));

		add_action( 'watu_gradebook_reporter', array('WatuProGradeBook', 'cronRun'));
		add_filter( 'cron_schedules', array('WatuProGradeBook', 'cronSchedule'));

		add_action( 'save_post_gradebook_class', array('WatuProGradeBook', 'savePost'), 10, 3 );

	}

	public static function savePost( $postId, $post, $update ) {

		$gbc = new WatuProGradeBookClasses;
		$gbc->setPostId( $postId );
		$gbc->reportStart();
		update_post_meta($postId, 'gradebook_report_complete', 0);

	}

	public static function cronRun() {

		// get gradebooks to run reports
		$args = array(
			'numberposts' => -1,
			'post_type'   => 'gradebook_class'
		);
		$gradebooks = get_posts( $args );

		// get one gradebook to report
		$reportingGradebook = false;
		foreach( $gradebooks as $gradebook ) {
			$isComplete = get_post_meta($gradebook->ID, 'gradebook_report_complete', 1);
			if( !$isComplete ) {
				$reportingGradebook = $gradebook;
				break;
			}
		}

		// check if no gradebooks to report
		if( !$reportingGradebook ) {
			return;
		}

		// do reporting
		$gbc = new WatuProGradeBookClasses;
		$gbc->setPostId( $gradebook->ID );
		$gbc->reportBuild();

	}

	public static function cronSchedule( $schedules ) {
		if( !isset( $schedules['1min'] )) {
			$schedules['1min'] = array(
				'interval' => 1*60,
				'display' => __('Every minute')
			);
    }
    return $schedules;
	}

	public static function initCronSchedule() {
	  wp_schedule_event( time(), '1min', 'watu_gradebook_reporter' );
	}

	public static function removeCronSchedule() {

		 $timestamp = wp_next_scheduled( 'watu_gradebook_reporter' );
	   wp_unschedule_event( $timestamp, 'watu_gradebook_reporter' );

	}

	public static function script() {

		wp_enqueue_script(
			'script-id',
			WATUPRO_GRADEBOOK_URL . '/admin.js',
			array( 'jquery' ),
			'',
			true
		);

	}

	public static function downloadReport() {

		if( substr( $_SERVER['REQUEST_URI'], 0, 18 ) == '/gradebook/export/' ) {
			$gradebookId = substr( $_SERVER['REQUEST_URI'], 18 );
			$gbc = new WatuProGradeBookClasses;
			$gbc->setPostId( $gradebookId );
			$gbc->exportReport();
    	exit();
  	}

	}

}

new WatuProGradeBook;

/*
 * Cron scheduling
 */
register_activation_hook( WATUPRO_GRADEBOOK_PATH . '/watupro-gradebook.php', array('WatuProGradeBook', 'initCronSchedule'));
register_deactivation_hook( WATUPRO_GRADEBOOK_PATH . '/watupro-gradebook.php', array('WatuProGradeBook', 'removeCronSchedule'));
