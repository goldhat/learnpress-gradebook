<div class="wrap">
	<?php if(empty($in_shortcode)):?>
		<h2><?php printf(__('%s: Stats Per Category', 'watupro'), stripslashes(apply_filters('watupro_qtranslate', $exam->name)))?></h2>
		
		<p><a href="admin.php?page=watupro_takings&exam_id=<?php echo $exam->ID?>"><?php _e("Back to the users list", 'watupro')?></a></p>
		
		<form method="post">
		  <p><?php _e('Filter by WP user login or email address:', 'watupro');?> <input type="text" name="user_filter" value="<?php echo empty($_POST['user_filter'])? '' : $_POST['user_filter']?>">
		  <input type="submit" name="filter" value="<?php _e('Filter', 'watupro');?>">
		  <?php if(!empty($_POST['user_filter'])):?>
            <input type="button" value="<?php _e('Clear Filter', 'watupro');?>" onclick="window.location='admin.php?page=watupro_cat_stats&exam_id=<?php echo $exam->ID?>';">
        <?php endif;?> 
		</form>
		
		<p><?php _e('Shortcode to display this page:', 'watupro')?> <input type="text" value='[watupror-stats-per-category <?php echo $exam->ID?> show="both"<?php if(!empty($_POST['user_filter'])) echo ' user_filter="'.$_POST['user_filter'].'"'?>]' size="50" readonly="true" onclick="this.select();"> <?php _e('You can pass value "cats" or "tags" to the attribute "show" to display only report by categories or tags.', 'watupro');?></p>		
	<?php endif;?>
	
	<?php if(!empty($cats) and (empty($atts['show']) or $atts['show'] != 'tags')):?>
   	<table class="widefat">
   		<tr><th><?php _e('Category Name', 'watupro')?></th><th><?php _e('Total questions asked', 'watupro')?></th>
   		<th><?php _e('Answered - num and %', 'watupro')?></th><th><?php _e('Unanswered - num and %', 'watupro')?></th>
   		<th><?php _e('Num. Correct answers', 'watupro')?></th>
   		<th><?php _e('% Correct answers (from answered)', 'watupro')?></th>
   		<th><?php _e('% Correct answers (from total)', 'watupro')?></th>
   		<th><?php _e('Points collected', 'watupro')?></th></tr>
   		<?php foreach($cats as $cat):
   		if(!empty($cat->exclude_from_reports)) continue;
   		$class = ('alternate' == @$class) ? '' : 'alternate';?>	
   			<tr class="<?php echo $class;?>"><td><?php echo stripslashes(apply_filters('watupro_qtranslate', $cat->name))?></td>
   			<td><?php echo $cat->total?></td>
   			<td><?php printf(__('%d (%d%%)', 'watupro'), $cat->num_answered, $cat->perc_answered);?></td>
   			<td><?php printf(__('%d (%d%%)', 'watupro'), $cat->num_unanswered, $cat->perc_unanswered);?></td>			
   			<td><?php echo $cat->num_correct;?></td>
   			<td><?php printf(__('%d%%', 'watupro'), $cat->perc_correct_a);?></td>
   			<td><?php printf(__('%d%%', 'watupro'), $cat->perc_correct_t);?></td>
   			<td><?php echo $cat->points;?></td></tr>				
   		<?php endforeach;?>
   	</table>
   <?php endif;?>	
	
	<?php if(!empty($tags) and count($tags) and (empty($atts['show']) or $atts['show'] != 'cats')):?>
	   <p>&nbsp;</p>
   	<table class="widefat">
   		<tr><th><?php _e('Tag', 'watupro')?></th><th><?php _e('Total questions asked', 'watupro')?></th>
   		<th><?php _e('Answered - num and %', 'watupro')?></th><th><?php _e('Unanswered - num and %', 'watupro')?></th>
   		<th><?php _e('Num. Correct answers', 'watupro')?></th>
   		<th><?php _e('% Correct answers (from answered)', 'watupro')?></th>
   		<th><?php _e('% Correct answers (from total)', 'watupro')?></th>
   		<th><?php _e('Points collected', 'watupro')?></th></tr>
   		<?php foreach($tags as $tag):   		
   		$class = ('alternate' == @$class) ? '' : 'alternate';?>	
   			<tr class="<?php echo $class;?>"><td><?php echo $tag->tag?></td>
   			<td><?php echo $tag->total?></td>
   			<td><?php printf(__('%d (%d%%)', 'watupro'), $tag->num_answered, $tag->perc_answered);?></td>
   			<td><?php printf(__('%d (%d%%)', 'watupro'), $tag->num_unanswered, $tag->perc_unanswered);?></td>			
   			<td><?php echo $tag->num_correct;?></td>
   			<td><?php printf(__('%d%%', 'watupro'), $tag->perc_correct_a);?></td>
   			<td><?php printf(__('%d%%', 'watupro'), $tag->perc_correct_t);?></td>
   			<td><?php echo $tag->points;?></td></tr>				
   		<?php endforeach;?>
   	</table>
	<?php endif;?>
</div>	