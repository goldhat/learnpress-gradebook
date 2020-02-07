<?php

class LearnPressGradeBookClasses {

	public static function init() {

		add_action('init', array('LearnPressGradeBookClasses', 'register'));
		add_filter( 'rwmb_meta_boxes', array('LearnPressGradeBookClasses', 'metaboxes'));

	}

	public function createReport( $postId ) {

		// get the exams in class
		$exams = rwmb_meta( 'gradebook_class_exam_selection', '', $postId );

		// get the users in class
		$users = rwmb_meta( 'gradebook_class_user_selection', '', $postId );

		// create csv data array with header row
		$csvData = [];
		$csvRow = array('Name', 'Username', 'Email', 'User ID');
		foreach( $exams as $examId ) {
			$csvRow[] = $this->fetchExamName( $examId );
		}
		$csvData[] = $csvRow;

		// loop over users and exams to get scores
		foreach( $users as $userId ) {

			$csvRow = [];

			$user = get_userdata( $userId );
			$csvRow[] = $user->first_name . ' ' . $user->last_name;
			$csvRow[] = $user->user_login;
			$csvRow[] = $user->user_email;
			$csvRow[] = $userId;

			foreach( $exams as $examId ) {
				$examResult = $this->fetchExamResultUser( $userId, $examId );
				if( $examResult ) {
					$csvRow[] = $examResult->percent_correct;
				} else {
					$csvRow[] = '-';
				}
			}

			$csvData[] = $csvRow;

		}

		$f = fopen('php://memory', 'w');
		foreach($csvData as $line) {
			fputcsv($f, $line);
		}
		fseek($f, 0);
		header("Content-type: application/csv", true, 200);
		header('Content-Disposition: attachment; filename="gradebook-export-' . $postId . '".csv;');
		fpassthru($f);

	}

	public function fetchExamName( $examId ) {

		global $wpdb;
		$result = $wpdb->get_col(
			$wpdb->prepare("SELECT name FROM wp_watupro_master
			WHERE id=%d
			LIMIT 1",
			$examId
		));
		return $result[0];

	}

	public function fetchExamResultUser( $userId, $examId ) {

		global $wpdb;
		$results = $wpdb->get_row(
			$wpdb->prepare("SELECT tak.percent_correct FROM wp_watupro_taken_exams AS tak
			WHERE tak.user_id=%d
			AND tak.exam_id=%d
			ORDER BY tak.percent_correct DESC LIMIT 1",
			$userId, $examId
		));
		if( empty( $results )) {
			return false;
		}
		return $results;

	}

	public static function metaboxes() {

		if( isset( $_REQUEST['post'] )) {
			$postId = $_REQUEST['post'];
		} else {
			$postId = false;
		}

		$prefix = 'gradebook_class_';

		$exams = LearnPressGradeBookClasses::fetchExams();
		$examChoices = [];
		foreach( $exams as $exam ) {
			$examChoices[ $exam->id ] = $exam->name;
		}

		$meta_boxes[] = array(
			'id' => 'gradebook_class_metabox',
			'title' => esc_html__( 'GradeBook Settings', 'learnpress-gradebook' ),
			'post_types' => array('gradebook_class'),
			'context' => 'after_title',
			'priority' => 'default',
			'autosave' => 'false',
			'fields' => array(
				array(
					'id' => $prefix . 'user_selection',
					'type' => 'user',
					'name' => esc_html__( 'Select Users', 'learnpress-gradebook' ),
					'field_type' => 'select_advanced',
					'multiple' => true
				),
				array(
					'id' => $prefix . 'exam_selection',
					'name' => esc_html__( 'Select Exams', 'metabox-online-generator' ),
					'type' => 'select_advanced',
					'multiple' => true,
					'placeholder' => esc_html__( 'Select an Item', 'metabox-online-generator' ),
					'options' => $examChoices,
				),
			),

		);

		end( $meta_boxes );
		$key = key( $meta_boxes );

		if( $postId ) {
			$meta_boxes[ $key ]['fields'][] = array(
				'id' => $prefix . 'export',
				'type' => 'button',
				'std' => 'Export GradeBook',
				'attributes' => array(
					'data-href' => '/gradebook/export/' . $postId
				)
			);
		}

		return $meta_boxes;

	}

	public static function fetchExams() {

		global $wpdb;
		$exams = $wpdb->get_results("SELECT id, name FROM wp_watupro_master");
		return $exams;

	}

	public static function register() {

		$labels = array(
			'name'                  => _x( 'GradeBooks', 'Post Type General Name', '' ),
			'singular_name'         => _x( 'GradeBook', 'Post Type Singular Name', '' ),
			'menu_name'             => __( 'GradeBooks', '' ),
			'name_admin_bar'        => __( 'GradeBooks', '' ),
			'archives'              => __( 'Item Archives', '' ),
			'attributes'            => __( 'Item Attributes', '' ),
			'parent_item_colon'     => __( 'Parent Item:', '' ),
			'all_items'             => __( 'All GradeBooks', '' ),
			'add_new_item'          => __( 'Add New GradeBook', '' ),
			'add_new'               => __( 'Add New GradeBook', '' ),
			'new_item'              => __( 'New Item', '' ),
			'edit_item'             => __( 'Edit Item', '' ),
			'update_item'           => __( 'Update Item', '' ),
			'view_item'             => __( 'View Item', '' ),
			'view_items'            => __( 'View Items', '' ),
			'search_items'          => __( 'Search Item', '' ),
			'not_found'             => __( 'Not found', '' ),
			'not_found_in_trash'    => __( 'Not found in Trash', '' ),
			'featured_image'        => __( 'Featured Image', '' ),
			'set_featured_image'    => __( 'Set featured image', '' ),
			'remove_featured_image' => __( 'Remove featured image', '' ),
			'use_featured_image'    => __( 'Use as featured image', '' ),
			'insert_into_item'      => __( 'Insert into item', '' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', '' ),
			'items_list'            => __( 'Items list', '' ),
			'items_list_navigation' => __( 'Items list navigation', '' ),
			'filter_items_list'     => __( 'Filter items list', '' ),
		);
		$args = array(
			'label'                 => __( 'GradeBooks', '' ),
			'description'           => __( 'Post Type Description', '' ),
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 155,
			'menu_icon'							=> 'dashicons-editor-kitchensink',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => false,
			'capability_type'       => 'page',
		);
		register_post_type( 'gradebook_class', $args );

	}


}
