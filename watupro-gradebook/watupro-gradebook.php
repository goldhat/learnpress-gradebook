<?php

/**
 * Plugin Name:			WatuPro GradeBook
 * Plugin URI:			http://eatbuildplay.com
 * Description:			Organize quizzes and students into gradebooks for swift reporting.
 * Version:					1.0.0
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

	}

	public static function cronRun() {

		// test cron running
		update_option( 'watupro_gradebook_log', time() );

		// get gradebooks to run reports
		$args = array(
			'numberposts' => -1,
			'post_type'   => 'gradebook_class'
		);
		$gradebooks = get_posts( $args );

		// get one gradebook to report
		$gradebook = $gradebook[0];

		$gbc = new WatuProGradeBookClasses;
		$gbc->createReport( $gradebook->ID );

		/*
		 * Break createReport() into smaller functions
		 * We should be able to fetch just 1 user at a time
		 * For now we can fetch all stats for 1 user
		 * Whether we continue to fetch 2+ users is determined by how many exams
		 */

	}

	public static function cronSchedule( $schedules ) {
		if( !isset( $schedules['5min'] )) {
			$schedules['5min'] = array(
				'interval' => 5*60,
				'display' => __('Every 5 minutes')
			);
    }
    return $schedules;
	}

	public static function initCronSchedule() {
	  wp_schedule_event( time(), '5min', 'watu_gradebook_reporter' );
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
			$gbc->createReport( $gradebookId );
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
