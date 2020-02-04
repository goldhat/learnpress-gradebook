<?php

/**
 * Plugin Name:			LearnPress GradeBook
 * Plugin URI:			http://eatbuildplay.com
 * Description:			Organize quizzes and students into gradebooks for swift reporting.
 * Version:					1.0.0
 * Author:					Casey Milne, Eat/Build/Play
 * Author URI:			http://eatbuildplay.com
 *
 * Text Domain: learnpress-gradebook
 * Domain Path: /languages/
 *
 */

define('LEARNPRESS_GRADEBOOK_PATH', plugin_dir_path( __FILE__ ));
define('LEARNPRESS_GRADEBOOK_URL', plugin_dir_url( __FILE__ ));


class LearnPressGradeBook {

	public function __construct() {

		require( LEARNPRESS_GRADEBOOK_PATH . '/inc/meta-box/meta-box.php');
		require( LEARNPRESS_GRADEBOOK_PATH . '/inc/classes.php');

		LearnPressGradeBookClasses::init();

		add_action( 'rwmb_enqueue_scripts', array('LearnPressGradeBook', 'scripts'));


	}

	public static function scripts() {
	   wp_enqueue_script(
			 'script-id',
			 LEARNPRESS_GRADEBOOK_URL . '/js/admin.js', 
			 array( 'jquery' ),
			 '',
			 true
		 );
	}

}

new LearnPressGradeBook;
