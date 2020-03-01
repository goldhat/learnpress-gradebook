<div class="wrap">

  <h1>WatuPro Gradebook Settings</h1>

  <form
    action="<?php echo esc_url( admin_url( 'admin-post.php' )); ?>"
    id="admin_form"
    method="POST">

    <input type="hidden" name="action" value="watupro_gradebook_save_settings">

    <p>
      <label>Reporting rows per cron run</label><br />
      <input id="watupro_gradebook_rows_per_run" name="watupro_gradebook_rows_per_run" type="text" value="<?php print $rows_per_run; ?>" />
    </p>

    <p>
      <input type="submit" value="Save Settings" />
    </p>

  </form>

</div>
