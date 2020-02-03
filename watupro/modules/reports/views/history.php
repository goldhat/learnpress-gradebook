<?php if($has_tabs):?>
<h2 class="nav-tab-wrapper">
	<a class='nav-tab' href="admin.php?page=watupro_reports&user_id=<?php echo $report_user_id?>"><?php _e('Overview', 'watupro')?></a>
	<?php if(!get_option('watupro_nodisplay_reports_tests')):?><a class='nav-tab' href='admin.php?page=watupro_reports&tab=tests&user_id=<?php echo $report_user_id?>'><?php _e('Tests', 'watupro')?></a><?php endif;?>
	<?php if(!get_option('watupro_nodisplay_reports_skills')):?><a class='nav-tab' href='admin.php?page=watupro_reports&tab=skills&user_id=<?php echo $report_user_id?>'><?php _e('Skills/Categories', 'watupro')?></a><?php endif;?>
	<?php if(!get_option('watupro_nodisplay_reports_history')):?><a class='nav-tab nav-tab-active'><?php _e('History', 'watupro')?></a><?php endif;?>
</h2>
<?php endif;?>

<div class="wrap">
	<h2><?php _e('Usage summary', 'watupro')?></h2>
	
	<div>
			<div class="postarea">
				<table>
					<tr><td><?php _e('Total quiz sessions:', 'watupro')?></td><td><strong><?php echo $total_sessions;?></strong></td></tr>
					<tr><td><?php _e('Average time spent per session:', 'watupro')?></td><td><strong><?php echo self::time_spent_human($avg_time_spent);?></strong></td></tr>
					<tr><td><?php _e('Total question answered:', 'watupro')?></td><td><strong><?php echo $total_problems;?></strong></td></tr>
					<tr><td><?php _e('Average questions per session:', 'watupro')?></td><td><strong><?php echo $avg_problems;?></strong></td></tr>
					<tr><td><?php _e('Average skills per session:', 'watupro')?></td><td><strong><?php echo $avg_skills;?></strong></td></tr>
				</table>
			</div>
	</div>		
	
	<h2><?php _e('Number of tests attempted over time', 'watupro')?></h2>
	
	<div style="overflow:auto;">
			<table id="chart" cellpadding="7">
				<tr>
					<?php foreach($chartlogs as $log):?>
						<td align="center" style="vertical-align:bottom;"><div style='width:80px;background-color:lightblue;height:<?php echo round($log['num_exams'] * $one_exam_height)?>px;'>
							<h3><?php echo $log['num_exams']?></h3>
						</div></td>						
					<?php endforeach;?>
				</tr>
				<tr>
					<?php foreach($chartlogs as $log):?>
						<td align="center"><?php echo $log['period']?></td>						
					<?php endforeach;?>
				</tr>
			</table>
	</div>	
	
	<h2><?php _e('Usage log', 'watupro')?></h2>
	
	<div>
		<table class="widefat">
			<thead>
				<tr><th width="30%"><?php _e('Date', 'watupro')?></th><th><?php _e('Quiz', 'watupro')?></th><th><?php _e('Session start time', 'watupro')?></th><th><?php _e('Session end time', 'watupro')?></th>
				<th><?php _e('Time spent', 'watupro')?></th><th><?php _e('Problems attempted', 'watupro')?></th>
				<th><?php _e('Skills/Question categories', 'watupro')?></th>
				<th><?php _e('Percent correct', 'watupro');?></th></tr>
			</thead>
			<tbody>	
			<?php foreach($logs as $log):				?>
				<tr><td colspan="8" style="background:#EEE;"><?php echo $log['period']?></td></tr>
				<?php foreach($log['exams'] as $exam):
					$class = ('alternate' == @$class) ? '' : 'alternate'?>
					<tr class="<?php echo $class?>"><td> &nbsp; - <?php echo date_i18n($date_format, $exam->start_time)?></td>
					<td><?php if( empty($exam->exam_name)): _e('n/a', 'watupro'); 
					else: 
						if(empty($exam->post_id) and empty($exam->published_odd)): echo stripslashes(apply_filters('watupro_qtranslate', $exam->exam_name)); 
						else: echo "<a href='".(!empty($exam->post_id) ? get_permalink($exam->post_id) : $exam->published_odd_url)
							."' target='_blank'>".stripslashes(apply_filters('watupro_qtranslate', $exam->exam_name))."</a>";
						endif;
					endif;
					?></td>
					<td><?php echo date_i18n($date_format.' '.$time_format,$exam->start_time)?></td>
					<td><?php echo date_i18n($date_format.' '.$time_format,$exam->end_time)?></td>
					<td><?php echo self::time_spent_human($exam->time_spent)?></td>
					<td align="center"><?php echo $exam->num_problems?></td>
					<td align="center"><?php echo $exam->num_skills?></td>
					<td align="center"><?php echo $exam->percent_correct?>%</td></tr>
			<?php endforeach; 
			endforeach;?>	
			</tbody>
		</table>	
	</div>
</div>