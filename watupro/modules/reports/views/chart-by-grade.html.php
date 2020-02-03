<?php if(!empty($in_shortcode)): WTPReports :: print_scripts(); endif;?>

<div class="wrap">
	<?php if(empty($in_shortcode)):?>
		<h1><?php _e('Chart by grades', 'watupro')?></h1>
		
		<p><?php _e('Quiz:', 'watupro')?> <b><?php echo $exam->name?></b></p>	
		<p><a href="admin.php?page=watupro_takings&exam_id=<?php echo $exam->ID?>"><?php _e("Back to the users list", 'watupro')?></a></p>
		
		<p><?php _e('This chart shows what percentage of users achieved each of the results in this quiz.', 'watupro')?></p>
	<?php endif;?>
	<div>
					<div id="chart<?php echo $exam->ID?>" class="postarea">
					</div>
			</div>
			
	<?php if(empty($in_shortcode)):?>
		<p><?php _e('Shortcode to display this page:', 'watupro')?> <input type="text" size="30" readonly="true" onclick="this.select();" value="[watupror-chart-by-grade <?php echo $exam->ID?> size=100]"></p>
	<?php endif;?>		
</div>

<script>
<?php if(empty($in_shortcode)):?>window.onload = function() {<?php endif;?>
    var r<?php echo $exam->ID?> = Raphael("chart<?php echo $exam->ID?>", '100%', <?php echo $in_shortcode ? $shortcode_size*3 : 600?>);
    r<?php echo $exam->ID?>.piechart(<?php echo $in_shortcode ? intval($shortcode_size*1.5) : 300?>, <?php echo $in_shortcode ? $shortcode_size : 200?>, <?php echo $in_shortcode ? $shortcode_size : 200?>, [<?php foreach($grades_arr as $grade=>$vals) echo $vals['percent'].",";?>],	{
			legend: [<?php foreach($grades_arr as $grade=>$vals) echo '"'.$grade.' \n '.sprintf(__('%d%% (%d responses)', 'watupro'), $vals['percent'], $vals['num_takings']).'",'?>],
			maxSlices: 10,
			init: true    
    });
<?php if(empty($in_shortcode)):?>}<?php endif;?>
</script>