<style type="text/css">
<?php watupro_resp_table_css(700);?>
</style>

<?php if($has_tabs):?>
<h2 class="nav-tab-wrapper">
	<a class='nav-tab' href="admin.php?page=watupro_reports&user_id=<?php echo $report_user_id?>"><?php _e('Overview', 'watupro')?></a>
	<?php if(!get_option('watupro_nodisplay_reports_tests')):?><a class='nav-tab nav-tab-active'><?php _e('Tests', 'watupro')?></a><?php endif;?>
	<?php if(!get_option('watupro_nodisplay_reports_skills')):?><a class='nav-tab' href='admin.php?page=watupro_reports&tab=skills&user_id=<?php echo $report_user_id?>'><?php _e('Skills/Categories', 'watupro')?></a><?php endif;?>
	<?php if(!get_option('watupro_nodisplay_reports_history')):?><a class='nav-tab' href='admin.php?page=watupro_reports&tab=history&user_id=<?php echo $report_user_id?>'><?php _e('History', 'watupro')?></a><?php endif;?>
</h2>
<?php endif;?>

<div class="wrap">
	 <?php if($has_tabs):?>	
	 <p><a href="admin.php?page=watupro_reports&tab=tests&user_id=<?php echo $report_user_id?>&export=1&noheader=1"><?php _e('Export this page', 'watupro')?></a> <?php _e('(TAB delimited CSV file)', 'watupro')?></p>
	 <?php endif;?>
	 <table class="widefat watupro-table">
	 		<thead>
			<tr><th><?php printf(__("%s name", 'watupro'), __('Quiz', 'watupro'));?></th><th><?php _e('Time spent', 'watupro')?></th>
			<th><?php _e('Problems attempted', 'watupro')?></th><th><?php _e('Score and Grade', 'watupro')?></th>
			<th><?php _e('Percent correct', 'watupro')?></th>
			<th><?php _e('View Details', 'watupro')?></th></tr>
			</thead>
			<tbody>
			<?php foreach($exams as $exam):
				$class = ('alternate' == @$class) ? '' : 'alternate';?>
				<tr class="<?php echo $class?>"><td><?php if(!empty($exam->post)) echo "<a href='".get_permalink($exam->post->ID)."' target='_blank'>"; 
				if(empty($exam->post) and !empty($exam->published_odd)) echo "<a href='".$exam->published_odd_url."' target='_blank'>";
				echo stripslashes(apply_filters('watupro_qtranslate', $exam->name));
				if(!empty($exam->post) or !empty($exam->published_odd)) echo "</a>";?></td>
				<td><?php echo self::time_spent_human($exam->time_spent);?></td>
				<td><?php echo $exam->cnt_answers?></td>
				<td><?php echo $exam->grade_title ? stripslashes(apply_filters('watupro_qtranslate',$exam->grade_title)) : __('None', 'watupro');?> <p><strong><?php printf(__("(with %s points)", 'watupro'), $exam->points)?></strong></p></td>
				<td><?php echo $exam->percent_correct?>%</td>
				<td><?php if($has_tabs):?>
				<a href="#" onclick="WatuPRO.takingDetails('<?php echo $exam->ID?>');return false;"><?php _e('view', 'watupro')?></a>
				<?php else:?>
				<a href="<?php echo $target_url.'&id=' . $exam->ID;?>"><?php _e('view', 'watupro')?></a>
				<?php endif;?></td></tr>
			<?php endforeach;?>	
			</tbody> 
	 </table>
	 
	 <?php if($count):?>
	<p align="center"> <?php if($offset > 0):?><a href="admin.php?page=watupro_reports&tab=tests&user_id=<?php echo $report_user_id?>&offset=<?php echo ($offset - $page_limit)?>"><?php _e('Previous page', 'watupro')?></a><?php endif;?>
	&nbsp;
	 <?php if(($offset + 50) < $count):?><a href="admin.php?page=watupro_reports&tab=tests&user_id=<?php echo $report_user_id?>&offset=<?php echo ($offset + $page_limit)?>"><?php echo _wtpt(__('Next page', 'watupro'))?></a><?php endif;?> </p>
<?php endif;?>
</div>
<script type="text/javascript">
<?php watupro_resp_table_js();?>
</script>