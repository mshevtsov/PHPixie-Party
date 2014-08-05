<hr />
<div class="row">
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-danger"><?=$page['partsReady']?><sup title="за сегодня">+<?=$page['partsReadyToday']?></sup></div>
		<div class="text-muted">оплативших</div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-warning"><?=$page['participants']?><sup title="за сегодня">+<?=$page['participantsToday']?></sup></div>
		<div class="text-muted">неоплативших</div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-success"><?=$page['ordersPayed']?><sup title="за сегодня">+<?=$page['ordersPayedToday']?></sup></div>
		<div class="text-muted">платежей</div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-info"><?=$page['payedTotalShort']?></div>
		<div class="text-muted"><i class="fa fa-rouble"></i> получено <span class="text-success" title="за сегодня">+<?=$page['payedToday']?></span></div>
	</div>

	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-primary"><?=$page['countMen']?><sup><i class="fa fa-male text-primary"></i></sup></div>
		<div class="text-muted">мужчин</div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-danger"><?=$page['countWomen']?><sup><i class="fa fa-female text-danger"></i></sup></div>
		<div class="text-muted">женщин</div>
	</div>

</div>
<!-- /. ROW  -->

<?php
$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern('d/M');
?>
<div class="panel panel-default">
	<div class="panel-heading">Комментарии участников</div>
	<table class="table table-condensed dataTables comments">
		<thead class="hidden">
			<tr>
				<th>Комментарий</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($page['comments'] as $part): ?>
			<tr>
				<td>
					<div class="row">
						<div class="col-xs-12 col-sm-4 col-md-3 col-lg-2">
							<strong><a href="/admin/participants/<?=$part->id?>"><?=$part->firstname .' '. $part->lastname?></a></strong>
							<span class="label label-default pull-right">
								<?php echo $formatter->format(new \DateTime($part->created)) ? $formatter->format(new \DateTime($part->created)) : "?"; ?>
							</span>
						</div>
						<div class="col-xs-12 col-sm-8 col-md-9 col-lg-10"><?=$part->comment?></div>
					</div>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<hr />

<div class="row">
	<div class="col-sm-6">
		<div class="panel panel-default">
			<div class="panel-heading">Хроника регистраций</div>
			<div class="panel-body">
				<div id="morris-area-chart1"></div>
			</div>
		</div>
	</div>
	<div class="col-sm-6">
		<div class="panel panel-default">
			<div class="panel-heading">История сбора средств</div>
			<div class="panel-body">
				<div id="morris-area-chart2"></div>
			</div>
		</div>
	</div>
</div>

