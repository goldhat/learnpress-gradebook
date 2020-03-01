<?php

class WatuProGradeBookClasses {

	public $postId;
	public $exams;
	public $users;
	public $exportFilenameSetting;
	public $exportFilename;
	public $csvData;
	public $csvFile;
	public $fieldPrefix = 'gradebook_class_';

	public static function init() {

		add_action('init', array('WatuProGradeBookClasses', 'register'));
		add_filter( 'rwmb_meta_boxes', array('WatuProGradeBookClasses', 'metaboxes'));
		add_filter( 'rwmb_gradebook_class_user_selection_value', array('WatuProGradeBookClasses', 'userSelectionSaveFilter'), 10, 2 );

	}

	public function setPostId( $postId ) {
		$this->postId = $postId;
	}

	public function loadExams() {
		$this->exams = rwmb_meta( 'gradebook_class_exam_selection', '', $this->postId );
	}

	public function loadUsers() {
		$this->users = $this->getUsers( $this->postId );
	}

	public function getUsers( $postId ) {
		return get_post_meta( $postId, 'gradebook_class_user_selection_ordered', 1 );
	}

	public function loadExportFilename() {
		$this->exportFilenameSetting = rwmb_meta( 'gradebook_class_filename', '', $this->postId );
	}

	public function reportHeaderRow() {
		$headerRow = array('Name', 'Username', 'Email', 'User ID');
		if( empty( $this->exams )) {
			return $headerRow;
		}
		foreach( $this->exams as $examId ) {
			$headerRow[] = $this->fetchExamName( $examId );
		}
		return $headerRow;
	}

	public function makeUserRow( $userId ) {
		$csvRow = $this->reportUserRow( $userId );
		$this->csvData[] = $csvRow;
	}

	private function reportUserRow( $userId ) {
		$csvRow = [];
		$user = get_userdata( $userId );
		$csvRow[] = $user->first_name . ' ' . $user->last_name;
		$csvRow[] = $user->user_login;
		$csvRow[] = $user->user_email;
		$csvRow[] = $userId;
		foreach( $this->exams as $examId ) {
			$examResult = $this->fetchExamResultUser( $userId, $examId );
			if( $examResult ) {
				$csvRow[] = $examResult->percent_points;
			} else {
				$csvRow[] = '-';
			}
		}
		return $csvRow;
	}

	public function makeCsv() {
		$this->csvFile = fopen('php://memory', 'w');
		foreach($this->csvData as $line) {
			fputcsv($this->csvFile, $line);
		}
		fseek($this->csvFile, 0);
	}

	public function makeExportFilename() {

		// first set either the user choice or the default
		if( $this->exportFilenameSetting == '' ) {
			$filename = 'gradebook-[title]';
		} else {
			$filename = $this->exportFilenameSetting;
		}

		// process placeholders
		$postTitle = get_the_title( $this->postId );
		$postTitle = str_replace( ' ', '-', $postTitle );
		$postTitle = strtolower( $postTitle );
		$filename = str_replace('[title]', $postTitle, $filename);

		// set property
		$this->exportFilename = $filename;

	}

	public function doCsvDownload() {
		header("Content-type: application/csv",true,200);
		header('Content-Disposition: attachment; filename=' . $this->exportFilename . '.csv');
		fpassthru( $this->csvFile );
	}

	public function reportBuild() {
		$report = get_post_meta( $this->postId, 'gradebook_report', 1 );
		// add header if report currently empty
		if( empty( $report )) {
			watuproGradebookLog('test2', 'Report was empty, starting report with header row.');
			$this->reportStart();
		}

		$count = 1;
		$incomplete = true;
		$rows_per_run = get_option('watupro_gradebook_rows_per_run', 10);
		while(($incomplete && $count <= $rows_per_run)) {
			$incomplete = $this->reportAddLine();
			$count++;
		}

	}

	/*
	 * Add header line to saved report
	 * Doing this clears existing report
	 */
	public function reportStart() {
		$report = [];
		$this->loadUsers();
		$this->loadExams();
		$report[] = $this->reportHeaderRow();
		update_post_meta( $this->postId, 'gradebook_report', $report );
	}

	/*
	 * Add 1 user report line to saved report
	 */
	public function reportAddLine() {
		$report = get_post_meta( $this->postId, 'gradebook_report', 1 );
		$this->loadUsers();
		$this->loadExams();

		$reportRowCount = count($report) -1;
		$userCount = count( $this->users );

		if( $reportRowCount >= $userCount ) {
			update_post_meta($this->postId, 'gradebook_report_complete', 1);
			return false;
		}
		else {
			$nextUserId = $this->users[ $reportRowCount ];
			$report[] = $this->reportUserRow( $nextUserId );
			update_post_meta( $this->postId, 'gradebook_report', $report );
			return true;
		}

	}

	public function exportReport() {

		$this->csvData = get_post_meta( $this->postId, 'gradebook_report', 1 );

		if( empty( $this->csvData )) {

			print 'Sorry this report is not ready for export';
			print '<pre>';
			var_dump( $this->csvData );
			print '</pre>';
			wp_die();

		}

		$this->makeCsv();
		$this->loadExportFilename();
		$this->makeExportFilename();
		$this->doCsvDownload();

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
			$wpdb->prepare("SELECT tak.percent_points FROM wp_watupro_taken_exams AS tak
			WHERE tak.user_id=%d
			AND tak.exam_id=%d
			ORDER BY tak.percent_points DESC LIMIT 1",
			$userId, $examId
		));
		if( empty( $results )) {
			return false;
		}
		return $results;

	}

	public static function userSelectionSaveFilter( $new, $field ) {

		// get users stored
		$postId = $_POST['post_ID'];
		$users = get_post_meta( $postId, 'gradebook_class_user_selection_ordered', 1 );
		if( !is_array( $users )) {
			$users = array();
		}

		// get user array passed
		$usersJson = $_POST['gradebook_classes_user_selection_json'];
		$users = json_decode( stripslashes( $usersJson) );

		// add new if there are any
		if( is_array( $new ) && !empty( $new )) {
			foreach( $new as $newUserId ) {
				if( !in_array( $newUserId, $users )) {
					$users[] = $newUserId;
				}
			}
		}

		// weed out any duplicates
		$users = array_unique( $users );

		update_post_meta( $postId, 'gradebook_class_user_selection_ordered', $users );
		return array();

	}

	public static function metaboxes( $meta_boxes ) {

		$obj = new WatuProGradeBookClasses;
		$prefix = $obj->fieldPrefix;

		if( isset( $_REQUEST['post'] )) {
			$postId = $_REQUEST['post'];
		} else {
			$postId = false;
		}

		/*
		 * Create report status
		 */
		if( !$postId ) {
			$reportStatus = 'No status yet.';
		} else {
			$report = get_post_meta( $postId, 'gradebook_report', 1 );
			if( !$report || $report == '' || empty( $report ) ) {
				$reportStatus = 'Report has not started running yet.';
			} else {
				$reportedRows = count($report) - 1;
				$reportStatus = $reportedRows . ' total rows reported so far.';
			}
		}

		$exams = WatuProGradeBookClasses::fetchExams();
		$examChoices = [];
		foreach( $exams as $exam ) {
			$examChoices[ $exam->id ] = $exam->name;
		}

		// make user table
		$users = get_post_meta( $postId, 'gradebook_class_user_selection_ordered', 1 );
		$userTable = WatuProGradeBookClasses::userTable( $users );

		$meta_boxes[] = array(
			'id' => 'gradebook_class_metabox',
			'title' => esc_html__( 'GradeBook Settings', 'watupro-gradebook' ),
			'post_types' => array('gradebook_class'),
			'context' => 'after_title',
			'priority' => 'default',
			'autosave' => 'false',
			'fields' => array(
				array(
					'id' => $prefix . 'user_selection',
					'type' => 'user',
					'name' => esc_html__( 'Select Users', 'watupro-gradebook' ),
					'field_type' => 'select_advanced',
					'multiple' => true,
					'query_args' => array(
						'number' => -1
					)
				),
				array(
					'type' => 'custom_html',
					'std'  => $userTable
				),
				array(
			    'type' => 'divider',
				),
				array(
					'id' => $prefix . 'exam_selection',
					'name' => esc_html__( 'Select Exams', 'watupro-gradebook' ),
					'type' => 'select_advanced',
					'multiple' => true,
					'placeholder' => esc_html__( 'Select an Item', 'watupro-gradebook' ),
					'options' => $examChoices,
				),
				array(
			    'type' => 'divider',
				),
				array(
			    'type' => 'custom_html',
					'std'  => $reportStatus
				),
				array(
					'id' => $prefix . 'filename',
					'name' => esc_html__( 'Set Export Filename', 'watupro-gradebook' ),
					'type' => 'text',
					'placeholder' => esc_html__( 'gradebook-[title]', 'watupro-gradebook' ),
					'append' => '.csv'
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

	public static function userTable( $users ) {

		$userRows = '';
		if( !empty( $users )) {
			$rowNumber = 1;
			foreach( $users as $uid ) {
				$userData = get_userdata( $uid );
				$userRows .= '<tr>';
				$userRows .= '<td>'. $rowNumber . '</td>';
				$userRows .= '<td>'. $uid . '</td>';
				$userRows .= '<td>' . $userData->user_login . '</td>';
				$userRows .= '<td><button class="gb-delete">Delete</button></td>';
				$userRows .= '</tr>';
				$rowNumber++;
			}
		} else {
			$userRows .= '<tr>';
			$userRows .= '<td colspan="4">No users currently in gradebook.</td>';
			$userRows .= '</tr>';
		}

		$userTable = '
			<textarea id="gradebook_classes_user_selection_json" name="gradebook_classes_user_selection_json" style="display:none"></textarea>
			<table class="display stripe">
				<thead>
					<tr>
						<th>Ordering</th>
						<th>User ID</th>
						<th>Username</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					' . $userRows . '
				</tbody>
			</table>
		';

		return $userTable;

	}

}
