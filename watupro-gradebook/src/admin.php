<?php

class WatuProGradeBookAdmin {

  public function __construct() {
    add_action('admin_menu', array($this, 'addPages'));
    add_action('admin_post_watupro_gradebook_save_settings', array( $this, 'processSettingsForm'));
  }

  public function processSettingsForm() {

    $rowsPerRun = $_POST['watupro_gradebook_rows_per_run'];
    if( $rowsPerRun >= 0 ) {
      update_option('watupro_gradebook_rows_per_run', $rowsPerRun);
    }
		
		wp_safe_redirect(admin_url('admin.php?page=watupro-gradebook-settings'));

  }

  public function addPages() {
    add_options_page(
      'WatuPro Gradebook',
      'WatuPro Gradebook',
      'manage_options',
      'watupro-gradebook-settings',
      array( $this, 'pageSettings'),
      90
    );
  }

  public function pageSettings() {
    $rows_per_run = get_option('watupro_gradebook_rows_per_run', 10);
    require( WATUPRO_GRADEBOOK_PATH . 'templates/settings.php' );
  }

}
