<!-- using tables because of PDF bridge. Don't change -->
<table class="watupro-barchart" cellpadding="5">
	<tr><?php foreach($cats as $cat):?>
		<td style="vertical-align:bottom;width:<?php echo $width?>px;">		
			<?php if($cat['percent'] > 0):?>
			<table style="width:<?php echo $width?>px;margin:auto;"><tr><td style="width:<?php echo $width?>px;height:<?php echo round($step*$cat['percent'])?>px;background-color:<?php echo $color?>;">&nbsp;</td></tr></table>		
			<?php else:?>
			&nbsp;	
			<?php endif;?>
		</td>
	<?php endforeach;?>
	</tr>
	<tr>
		<?php foreach($cats as $cat):?>
		<td><p><?php echo $cat['label']?></p></td>
		<?php endforeach;?>
	</tr>
</table>