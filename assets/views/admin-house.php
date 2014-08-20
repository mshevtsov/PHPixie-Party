<?php

$partsCount = $page['house']->participants->count_all();
$places = $page['house']->places1 + $page['house']->places2 + $page['house']->places3 + $page['house']->places4;

$panel = array(
	'personalIcon' => "<span class=\"pull-right label label-info\"># <span class=\"house-id\">{$page['house']->id}</span></span>",
	'guestsIcon' => "<span class=\"pull-right label label-". ($partsCount<$places ? "success" : "danger") ."\"><i class=\"fa fa-users fa-fw\"></i> {$partsCount}</span>",
	'conditionsIcon' => "<span class=\"pull-right label label-default\"><i class=\"fa fa-users fa-fw\"></i> {$places}</span>",
	'geoIcon' => '',
	'personal' => array(
		'<i class="fa fa-home fa-fw text-primary"></i> '. ($page['house']->church ? $page['house']->church : "<span class=\"text-danger\">Церковь не указана</span>"),
		'<i class="fa fa-phone fa-fw text-primary"></i> '. ($page['house']->phone ? $page['house']->phone : "<span class=\"text-danger\">Телефон не указан</span>"),
		'<i class="fa fa-history fa-fw text-primary"></i> '. ($page['house']->future ? "Готовы в будущем" : "<span class=\"text-muted\"><s>Готовы в будущем</s></span>"),
		),
	'geo' => array(),
	'conditions' => array(
		'<i class="fa fa-users fa-fw text-primary"></i> Количество мест<ul>'.
			($page['house']->places1 ? "<li><strong>{$page['house']->places1}</strong> для мужчин</li>" : "") .
			($page['house']->places2 ? "<li><strong>{$page['house']->places2}</strong> для женщин</li>" : "") .
			($page['house']->places3 ? "<li><strong>{$page['house']->places3}</strong> для любого пола</li>" : "") .
			($page['house']->places4 ? "<li><strong>{$page['house']->places4}</strong> запасных</li>" : "") .'</ul>',
		'<i class="fa fa-coffee fa-fw text-primary"></i> '. ($page['house']->meal ? "Завтраки" : "<s><span class=\"text-muted\">Завтраки</span></s>"),
		'<i class="fa fa-car fa-fw text-primary"></i> '. ($page['house']->transport ? "Транспорт на {$page['house']->transport} мест" : "<span class=\"text-muted\"><s>Транспорт</s></span>"),
		'<i class="fa fa-car fa-fw text-primary"></i> '. ($page['house']->meeting ? "Можно встретить" : "<span class=\"text-muted\"><s>Можно встретить</s></span>"),
		'<i class="fa fa-car fa-fw text-primary"></i> '. ($page['house']->delivery ? "Можно доставлять" : "<span class=\"text-muted\"><s>Можно доставлять</s></span>"),
		),
	'guests' => array(),
	'map' => '',
);

if($page['house']->coord) {
	$coordEvent = $this->pixie->config->get('party.event.coord');
	$coordHouse = explode(',',$page['house']->coord);
	$distance = $this->pixie->party->vincentyGreatCircleDistance($coordEvent[0], $coordEvent[1], trim($coordHouse[0]), trim($coordHouse[1]));
	$panel['geoIcon'] = "<span class=\"pull-right label label-default\"><i class=\"fa fa-map-marker\"></i> ". round($distance/1000) ." км</span>";
	$panel['map'] = "<div id=\"mapHouse\" class=\"yamap\" data-coord=\"{$page['house']->coord}\" data-target=\"". implode(',',$this->pixie->config->get('party.event.coord')) ."\"></div>";
}

if($page['house']->comment)
	$panel['personal'][] = '<i class="fa fa-comment fa-fw text-primary"></i> '. $page['house']->comment;
if($page['house']->route)
	$panel['geo'][] = '<i class="fa fa-taxi fa-fw text-primary"></i> '. $page['house']->route;
if($page['house']->address)
	$panel['geo'][] = '<i class="fa fa-map-marker fa-fw text-primary"></i> '. $page['house']->address;
if($page['house']->busstop)
	$panel['geo'][] = '<i class="fa fa-map-marker fa-fw text-primary"></i> '. $page['house']->busstop;
if($page['house']->district)
	$panel['geo'][] = '<i class="fa fa-map-marker fa-fw text-primary"></i> '. $page['house']->district;
if($page['house']->city)
	$panel['geo'][] = '<i class="fa fa-building-o fa-fw text-primary"></i> '. $page['house']->city;


// Создание списка гостей и подсчёт свободных мест для определения фильтров для подселения
$places1 = $page['house']->places1;
$places2 = $page['house']->places2;
$places3 = $page['house']->places3;
$places4 = $page['house']->places4;

if($partsCount) {
	$parts = $page['house']->participants->where('cancelled',0)->order_by('gender','asc')->find_all()->as_array();

	foreach($parts as $part) {
		$panel['guests'][] = "<i class=\"fa ". ($part->gender==2 ? 'fa-female text-danger' : 'fa-male text-primary') ." fa-fw\"></i> <a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a> <button class=\"btn btn-xs btn-default moveout pull-right\" data-partid=\"{$part->id}\"><i class=\"fa fa-times fa-sm\"></i></button>";

		// Считаем, сколько каких мест сейчас уже занято
		if($part->gender==1 && $places1 || $part->gender==2 && $places2) {
			$part->gender==1
				? $places1--
				: $places2--;
		}
		else if($places3)
			$places3--;
		else if($places4)
			$places4--;
	}
}

$genderAjax = "all";
if(!$places3) {
	if(!$places1)
		$genderAjax = "female";
	else if(!$places2)
		$genderAjax = "male";
}
$genderAjax = "<span class=\"gender-filter\" data-gender=\"{$genderAjax}\"></span>";

?>



<div class="row">
	<div class="col-sm-6">
		<div class="row">
			<div class="col-xs-12 col-sm-6">
				<div class="panel panel-default">
					<div class="panel-heading"><h4>Контакты <?=$panel['personalIcon']?></h4></div>
				<?php if(count($panel['personal'])): ?>
					<ul class="list-group">
				<?php foreach($panel['personal'] as $item): ?>
						<li class="list-group-item"><?=$item?></li>
				<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				</div>
				<div class="panel panel-default">
					<div class="panel-heading"><h4>Место <?=$panel['geoIcon']?></h4></div>
				<?php if(count($panel['geo'])): ?>
					<ul class="list-group">
				<?php foreach($panel['geo'] as $item): ?>
						<li class="list-group-item"><?=$item?></li>
				<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				</div>
			</div>
			<div class="col-xs-12 col-sm-6">
				<div class="panel panel-default">
					<div class="panel-heading"><h4>Условия <?=$panel['conditionsIcon']?></h4></div>
				<?php if(count($panel['conditions'])): ?>
					<ul class="list-group">
				<?php foreach($panel['conditions'] as $item): ?>
						<li class="list-group-item"><?=$item?></li>
				<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?=$genderAjax?></div>
<?php if(count($panel['guests'])): ?>
				<div class="panel panel-default">
					<div class="panel-heading"><h4>Гости <?=$panel['guestsIcon']?></h4></div>
					<ul class="list-group">
				<?php foreach($panel['guests'] as $item): ?>
						<li class="list-group-item"><?=$item?></li>
				<?php endforeach; ?>
					</ul>
				</div>
<?php endif; ?>
			</div>
		</div>
	</div>

<?php if($panel['map']): ?>
	<div class="col-sm-6">
		<div class="panel panel-default">
			<div class="panel-body">
				<?=$panel['map']?>
			</div>
		</div>
	</div>
<?php endif; ?>
</div>


<div class="panel panel-default">
	<div class="panel-heading"><h4>Заселение</h4></div>
	<div class="table-responsive">
		<table class="table table-condensed table-hover dataTables settlers-special">
			<thead>
				<tr>
					<th>Имя</th>
					<th><span style="white-space:nowrap"><i class="fa fa-male text-primary"></i><i class="fa fa-female text-danger"></i></span></th>
					<th><i class="fa fa-calendar" title="возраст"></i></th>
					<th>Регион</th>
					<th>Трансп.</th>
					<th><i class="fa fa-users" title="номер команды"></i></th>
					<th><i class="fa fa-money" title="дата оплаты"></i></th>
					<th><i class="fa fa-comment" title="комментарий участника"></i></th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>


<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
