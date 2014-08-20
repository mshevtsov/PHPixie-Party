<hr />
<div class="row">
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-danger"><a href="/admin/participants"><?=$page['partsReady']?><sup title="за сегодня">+<?=$page['partsReadyToday']?></sup></a></div>
		<div class="text-muted"><i class="fa fa-users"></i> оплативших</div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-warning"><?=$page['participants']?><sup title="за сегодня">+<?=$page['participantsToday']?></sup></div>
		<div class="text-muted"><i class="fa fa-child"></i> неоплативших</div>
	</div>
<?php /*	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-success"><?=$page['ordersPayed']?><sup title="за сегодня">+<?=$page['ordersPayedToday']?></sup></div>
		<div class="text-muted">платежей</div>
	</div>*/ ?>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-info"><a href="/admin/paylist"><?=$page['payedTotalShort']?></a></div>
		<div class="text-muted"><i class="fa fa-rouble"></i> получено <span class="text-success" title="за сегодня">+<?=$page['payedToday']?></span></div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-info"><a href="/admin/cash"><?=$page['payedCashTotalShort']?></a></div>
		<div class="text-muted"><i class="fa fa-rouble"></i> наличных</div>
	</div>

	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-success"><a href="/admin/houses"><?=$page['settled']?><small>%</small></a></div>
		<div class="text-muted"><i class="fa fa-home"></i> расселено</div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-primary"><?=$page['percentMen']?><small>%</small></div>
		<div class="text-muted"><i class="fa fa-male"></i> мужчин</div>
	</div>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count"><a href="/admin/hello"><?=$page['partsAttend']?></a></div>
		<div class="text-muted"><i class="fa fa-car"></i> прибыло</div>
	</div>

	<?php if($page['birthdays']): ?>
	<div class="col-lg-2 col-md-3 col-sm-6 col-xs-6">
		<div class="dash-count text-danger"><a href="/admin/birthdays"><?=$page['birthdays']?></a></div>
		<div class="text-muted"><i class="fa fa-smile-o"></i> <?=$this->pixie->party->declension($page['birthdays'],array('именинник','именинника','именинников'))?></div>
	</div>
	<?php endif; ?>

</div>

<div>Общая сумма полученных взносов: <strong><?=$page['totalMoney']?></strong> рублей.</div>

<!-- /. ROW  -->
<hr/>

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

