<div class="row">
	<div class="col-md-2 col-sm-6 col-xs-6">
		<!-- <span class="dash-icon bg-color-red set-icon">
			<i class="fa fa-child"></i>
		</span> -->
		<div class="dash-count text-danger"><?=$page['partsReady']?><sup>+<?=$page['partsReadyToday']?></sup></div>
		<div class="text-muted">оплативших</div>
	</div>
	<div class="col-md-2 col-sm-6 col-xs-6">
		<!-- <span class="dash-icon bg-color-brown set-icon">
			<i class="fa fa-users"></i>
		</span> -->
		<div class="dash-count text-warning"><?=$page['participants']?><sup>+<?=$page['participantsToday']?></sup></div>
		<div class="text-muted">неоплативших</div>
	</div>
	<div class="col-md-2 col-sm-6 col-xs-6">
		<!-- <span class="dash-icon bg-color-green set-icon">
			<i class="fa fa-money"></i>
		</span> -->
		<div class="dash-count text-success"><?=$page['ordersPayed']?><sup>+<?=$page['ordersPayedToday']?></sup></div>
		<div class="text-muted">платежей</div>
	</div>
	<div class="col-md-2 col-sm-6 col-xs-6">
		<div class="dash-count text-info"><span class="hidden-xs"><?=$page['payedTotal']?></span><span class="visible-xs"><?=$page['payedTotalShort']?></span></div>
		<div class="text-muted"><i class="fa fa-rouble"></i> получено <span class="text-success">+<?=$page['payedToday']?></span></div>
	</div>
</div>
<!-- /. ROW  -->
<hr />

<?php
$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern('d MMMM');
?>
<!-- <div class="panel panel-default"> -->
	<table class="table table-condensed dataTables comments">
		<thead>
			<tr>
				<!-- <th>Имя</th> -->
				<!-- <th>Дата</th> -->
				<th>Комментарий</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($page['comments'] as $part): ?>
			<tr>
				<td>
						<div class="row">
							<div class="col-sm-3 col-xs-12">
								<strong><?php if($part->website) echo "<a href=\"{$part->website}\" target=\"_blank\"><i class=\"fa fa-vk\"></i></a> ";?><?=$part->firstname?> <?=$part->lastname?></strong>
								<span class="label label-default pull-right">
									<?php echo $formatter->format(new \DateTime($part->created)) ? $formatter->format(new \DateTime($part->created)) : "?"; ?>
								</span>
							</div>
							<div class="col-sm-9 col-xs-12"><?=$part->comment?></div>
						</div>
				</td>
<?php /*
				<td><strong><?php if($part->website) echo "<a href=\"{$part->website}\" target=\"_blank\"><i class=\"fa fa-vk\"></i></a> ";?><?=$part->firstname?> <?=$part->lastname?></strong></td>
				<td class="text-muted"><i class="fa fa-clock-o fa-fw"></i><?php echo $formatter->format(new \DateTime($part->created)) ? $formatter->format(new \DateTime($part->created)) : "дата неизвестна"; ?></td>
				<td><?=$part->comment?></td>
*/ ?>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<!-- </div> -->
<hr />
