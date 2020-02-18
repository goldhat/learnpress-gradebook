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

	}

	public static function initCronSchedule() {

		$timestamp = wp_next_scheduled( 'watu_gradebook_reporter' );

		if( $timestamp == false ) {
		  wp_schedule_event( time(), 'daily', 'watu_gradebook_reporter' );
		}

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
register_activation_hook( '__FILE__', array('WatuProGradeBook', 'initCronSchedule'));
