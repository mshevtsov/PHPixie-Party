<?php
function makeWebsiteLink($str) {
	$match = array();
	if(preg_match("/^(https?\:\/\/)?(www\.)?([\w-\.]+)(\/(.+))?$/", $str, $match)) {
		switch($match[3]) {
			case 'vk.com':
				$icon = "vk";
				break;
			case 'facebook.com':
				$icon = "facebook-square";
				break;
			case 'twitter.com':
				$icon = "twitter";
				break;
			default:
				$icon = "external-link";
		}
		return "<i class=\"fa fa-{$icon} fa-fw\"></i> <a href=\"http://{$match[3]}{$match[4]}\" target=\"_blank\">". ($icon=="external-link" ? $match[3].$match[4] : $match[5]) ."</a>";
	}
	else
		return false;
}

if($page['part']->website && preg_match("/vk.com\/([\d\w\.]+)$/",$page['part']->website,$match))
	$user_id = $match[1];
else
	$user_id = "";
$enda = $page['part']->gender==2 ? "а" : "";

$findabout = unserialize($page['part']->findabout);
foreach($findabout as $key=>$value)
	$findabout[$key] = $this->pixie->config->get('party.userfields.findabout.options.'. $value);

$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern('d MMMM YYYY');
$formatterShort = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatterShort->setPattern('d MMMM');
$formatterShort2 = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatterShort2->setPattern('d MMMM H:mm');
$formatterShort3 = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatterShort3->setPattern("d.MM");

$age = date_diff(date_create($page['part']->birthday), date_create('now'))->y;

$daysTotal = $this->pixie->config->get('party.userfields.days.options');
$daysTotal = max(array_keys($daysTotal));

$subject_types = array(
	array('Республика', 'респ.', 'республики', 0),
	array('Край', 'кр.', 'края', 1),
	array('Область', 'обл.', 'области', 1),
	array('Автономная область', 'АО', 'автономной области', 1),
	array('Автономный округ', 'АО', 'автономного округа', 1),
	array('Город', 'г.', 'города', 0)
);


$panel = array(
	'userIcon' => '',
	'payIcon' => '',
	'ordersIcon' => '',
	'unknown' => array(),
	'identity' => array(),
	'conditions' => array(),
	'geo' => array(),
	'payment' => array(),
	'orders' => array(),
	'accomodation' => array(),
);





// Панель недостающих данных
if(!$this->pixie->party->makeWebsiteLink($page['part']->website))
	$panel['unknown'][] = "Сайт неизвестен";
if($age<1 || $age>=200)
	$panel['unknown'][] = "Возраст неизвестен";
if(trim($page['part']->phone)=='')
	$panel['unknown'][] = "Телефон неизвестен";
if(trim($page['part']->email)=='')
	$panel['unknown'][] = "Email неизвестен";
if(trim($page['part']->city)=='')
	$panel['unknown'][] = "Город неизвестен";
if(!$page['part']->region->loaded())
	$panel['unknown'][] = "Регион неизвестен";
if(trim($page['part']->church)=='')
	$panel['unknown'][] = "Церковь неизвестна";





// Панель личной информации
$panel['userIcon'] = '<span class="pull-right label label-'. ($page['part']->gender==2 ? 'danger' : 'primary') .'"><i class="hidden-sm fa fa-'. ($page['part']->gender==2 ? 'fe' : '') .'male"></i>'. ($age>=1 && $age<200 ? ' '.$age : '') .'</span>';
if($age>=1 && $age<200)
	$panel['identity'][] = "<i class=\"fa fa-calendar fa-fw\"></i> ". $formatter->format(new \DateTime($page['part']->birthday));
if(trim($page['part']->phone)!='')
	$panel['identity'][] = "<i class=\"fa fa-mobile fa-fw\"></i> <a href=\"tel:{$page['part']->phone}\">{$page['part']->phone}</a>";
if(trim($page['part']->email)!='') {
	$email = trim(strtolower($page['part']->email));
	$panel['identity'][] = "<i class=\"fa fa-envelope fa-fw\"></i> <a href=\"mailto:{$email}\">{$email}</a>";
}
if($user_id) {
	$panel['identity'][] = "<div class=\"text-center\"><a href=\"{$page['part']->website}\" target=\"_blank\"><img src=\"http://placehold.it/100&amp;text=Loading\" class=\"img-thumbnail img-responsive img-rounded\" data-userid=\"{$user_id}\"/></a>\n<div>". $this->pixie->party->makeWebsiteLink($page['part']->website) ."</div></div>";
}
else if($this->pixie->party->makeWebsiteLink($page['part']->website))
	$panel['identity'][] = $this->pixie->party->makeWebsiteLink($page['part']->website);

if(count($findabout)) {
	$tags = array();
	foreach($findabout as $find)
		$tags[] = "<span class=\"label label-info tags\">{$find}</span>";
	$panel['identity'][] = '<i class="fa fa-bullhorn fa-fw"></i> '. implode(' ', $tags);
}





// Панель географии
if(trim($page['part']->city))
	$panel['geo'][] = array('/admin/fellows/'. $page['part']->id, '<i class="fa fa-map-marker fa-fw"></i> '. trim($page['part']->city) ." <span class=\"badge pull-right\">". $this->pixie->party->getFellowsCountByPart($page['part'],true,true) ."</span>");
if($page['part']->region->loaded())
	$panel['geo'][] = array('/admin/fellows/'. $page['part']->id, '<i class="fa fa-globe fa-fw"></i> '. $page['part']->region->name .($page['part']->region->suffix && $page['part']->region->type ? ' '. $subject_types[$page['part']->region->type-1][1] : '') ." <span class=\"badge pull-right\">". $this->pixie->party->getFellowsCountByPart($page['part'],true,false) ."</span>");
if(trim($page['part']->church))
	$panel['geo'][] = '<i class="fa fa-home fa-fw"></i> '. trim($page['part']->church);
$panel['geo'][] = "<i class=\"fa fa-edit fa-fw\"></i> <small class=\"text-muted\">#{$page['part']->user->id}</small> <a href=\"/admin/users/{$page['part']->user->id}\">{$page['part']->user->name}</a>: {$page['part']->user->email} <button data-userid=\"{$page['part']->user->id}\" title=\"Льготная регистрация\" class=\"btn btn-default btn-xs grace-user". ($page['part']->user->grace ? " active" : "") ."\"><i class=\"fa fa-gift". ($page['part']->user->grace ? " text-success" : "") ."\"></i> Льгота</button>";
if($page['part']->user->participants->where('cancelled',0)->count_all() > 1) {
	$team = $page['part']->user->participants->where('cancelled',0)->order_by('gender','asc')->order_by('money','desc')->find_all()->as_array();
	$teamList = "Зарегистрированы вместе (всего ". count($team) ."):\n<ul class=\"fa-ul\">";
	foreach($team as $item)
		if($item->id != $page['part']->id)
			$teamList .= "<li><i class=\"fa-li fa ". ($item->gender==2 ? 'fa-female text-danger' : 'fa-male text-primary') ."\"></i> <a href=\"/admin/participants/{$item->id}\">{$item->firstname} {$item->lastname}</a>". ($item->money ? "<sup>{$item->money}</sup>" : "") ."</li>";
	$teamList .= "</ul>\n";
	$panel['geo'][] = $teamList;
}




// Панель оплаты
if($page['part']->money)
	$panel['payment'][] = array("<span class=\"dash-count text-success\"><span>{$page['part']->money}</span> <i class=\"fa fa-rouble\"></i></span> оплачено","payment-view-balance");
$check = $this->pixie->party->getSumToPay($page['part']);
if($check['needToFull'])
	$panel['payment'][] = array("<span id=\"needToPay\" data-userid=\"{$page['part']->id}\"><span class=\"dash-count text-warning\"><span>{$check['needToFull']}</span> <i class=\"fa fa-rouble\"></i></span> осталось оплатить</span>","payment-view-needtofull");
else
	$panel['payment'][] = array("<span id=\"needToPay\" data-userid=\"{$page['part']->id}\"><i class=\"fa fa-check text-success\"></i> Больше платить ничего не надо</span>","payment-view-needtofull");
if($check['sumExcess'])
	$panel['payment'][] = array("<span class=\"dash-count text-danger\"><span>{$check['sumExcess']}</span> <i class=\"fa fa-rouble\"></i></span> лишних","payment-view-sumexcess");

if($page['part']->created)
	$panel['payment'][] = array('<i class="fa fa-file-text-o text-muted fa-fw"></i> Анкета: '. $formatterShort->format(new \DateTime($page['part']->created)));

if($page['part']->money) {
	$payDates = array();
	$orders = $page['part']->user->orders->find_all();
	foreach($orders as $order) {
		if($order->balance==0)
			continue;
		$purpose = unserialize($order->purpose);
		if(in_array($page['part']->id, $purpose))
			$payDates[] = $formatterShort->format(new \DateTime($order->datepayed));
	}
	if(count($payDates))
		$panel['payment'][] = array('<i class="fa fa-money text-success fa-fw"></i> Оплата: '. implode(', ', $payDates));
}

$panel['payIcon'] = '<span class="pull-right text-danger"><i class="fa fa-times-circle fa-lg"></i></span>';
if($page['part']->paydate || $page['part']->approved) {
	if($period = $this->pixie->party->getCurrentPeriod($page['part']->paydate)) {
		if($page['part']->money >= $period['prices']['prepay'])
			$panel['payIcon'] = '<span class="pull-right text-success"><i class="fa fa-check-circle fa-lg"></i></span>';
	}
}

if($this->pixie->party->getPartGrace($page['part']))
	$panel['payment'][] = array("<i class=\"fa fa-gift fa-fw text-success\"></i> Льготная регистрация учитывается","payment-view-grace");
else if($this->pixie->party->getPartPayPeriod($page['part']))
	$panel['payment'][] = array("<span class=\"fa-stack\"><i class=\"fa fa-gift fa-fw fa-stack-1x\"></i><i class=\"fa fa-ban fa-stack-2x text-danger\"></i></span> <span class=\"text-danger\">Льготная регистрация не использована</span>","payment-view-grace");
else
	$panel['payment'][] = array("<span class=\"fa-stack\"><i class=\"fa fa-edit fa-fw fa-stack-1x\"></i><i class=\"fa fa-ban fa-stack-2x text-danger\"></i></span> <span class=\"text-danger\">Регистрация на месте</span>","payment-view-grace");



// Панель проживания
if($page['part']->house->loaded()) {
	$panel['accomodation'][] = "<i class=\"fa fa-fw fa-home\"></i> <a href=\"/admin/houses/{$page['part']->house->id}\">{$page['part']->house->name}</a> <span class=\"label label-info\">#{$page['part']->house->id}</span>";

	$log = $this->pixie->orm->get('log')->where('type',6)->where('id1',$page['part']->id)->order_by('created','desc')->find();
	if($log->loaded()) {
		$author = $this->pixie->orm->get('user',$log->author);
		if($author->loaded())
			$panel['accomodation'][] = "<i class=\"fa fa-fw fa-edit\"></i> поселил: <a href=\"/admin/users/{$author->id}\">{$author->name}</a>, ". $formatterShort->format(new \DateTime($log->created));
	}

	$neightbours = $page['part']->house->participants->where('cancelled',0)->where('id','<>',$page['part']->id)->order_by('gender','asc')->find_all()->as_array();
	if(count($neightbours)) {
		$names = "";
		foreach($neightbours as $part) {
			$names .= "<li><i class=\"fa fa-li ". ($part->gender==2 ? 'fa-female text-danger' : 'fa-male text-primary') ." fa-fw\"></i> <a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a></li>";
		}
		$panel['accomodation'][] = "Соседи: <ul class=\"fa-ul\">{$names}</ul>";
	}

}


$dayButtons = "<div class=\"btn-group\" id=\"part-days\" data-toggle=\"buttons\">";
$daysList = $this->pixie->config->get('party.userfields.days.list');
$daysPart = $page['part']->dayslist
	? unserialize($page['part']->dayslist)
	: array();
foreach($daysList as $i=>$day)
	$dayButtons .= "<label class=\"btn btn-primary". ((isset($daysPart[$i]) && $daysPart[$i]) ? " active" : "") ."\"><input type=\"checkbox\" data-dayid=\"{$i}\"> ". $formatterShort3->format(new \DateTime($day)) ."</label>\n";
$dayButtons .= "</div>";

// Панель условий
if($page['part']->cancelled)
	$panel['conditions'][] = array('danger',"<i class=\"fa fa-fw fa-times-circle fa-lg\"></i> <strong>Участие отменено</strong>");
$panel['conditions'][] = $dayButtons;//"Дни участия:<br/>{$dayButtons}";
if($page['part']->meal==1)
	$panel['conditions'][] = array('success','<i class="fa fa-cutlery fa-fw"></i> С питанием');
else
	$panel['conditions'][] = array('danger','<i class="fa fa-cutlery fa-fw"></i> Без питания');

switch($page['part']->accomodation) {
	case 1:
		$panel['conditions'][] = array('success','<i class="fa fa-home fa-fw"></i> Нужно бесплатное жильё');
		break;
	case 2:
		$panel['conditions'][] = array('warning','<i class="fa fa-building-o fa-fw"></i> Нужен хостел');
		break;
	case 3:
		$panel['conditions'][] = '<i class="fa fa-home fa-fw"></i> Жильё не нужно&nbsp;&mdash; местный';
		break;
	case 4:
		$panel['conditions'][] = "<i class=\"fa fa-home fa-fw\"></i> Жильё не нужно&nbsp;&mdash; сам{$enda} найдёт";
		break;
	case 5:
		$panel['conditions'][] = array('danger','<i class="fa fa-question-circle fa-fw"></i> О жилье&nbsp;&mdash; в комментарии');
		$panel['unknown'][] = 'Проживание в комментарии';
		break;
	default:
		if(in_array($page['part']->city, $this->pixie->config->get('party.event.host_city'))) {
			$panel['conditions'][] = '<i class="fa fa-home fa-fw"></i> О жилье не указал'. $enda;
		}
		else {
			$panel['conditions'][] = array('danger','<i class="fa fa-question-circle fa-fw"></i> О жилье&nbsp;&mdash; неизвестно');
			$panel['unknown'][] = 'Проживание неизвестно';
		}
		break;
}

switch($page['part']->transport) {
	case 1:
		$panel['conditions'][] = array('success','<i class="fa fa-car fa-fw"></i> Приедет на поезде');
		break;
	case 2:
		$panel['conditions'][] = array('warning','<i class="fa fa-plane fa-fw"></i> Прилетит на самолёте');
		break;
	case 3:
		$panel['conditions'][] = array('warning','<i class="fa fa-car fa-fw"></i> Приедет на автомобиле');
		break;
	case 4:
		$panel['conditions'][] = array('warning','<i class="fa fa-truck fa-fw"></i> Приедет на рейсовом автобусе');
		break;
	case 5:
		$panel['conditions'][] = array('warning','<i class="fa fa-truck fa-fw"></i> Приедет на церковном автобусе');
		break;
	case 6:
		$panel['conditions'][] = '<i class="fa fa-home fa-fw"></i> Встречать не нужно&nbsp;&mdash; местный';
		break;
	default:
		if(in_array(trim($page['part']->city), $this->pixie->config->get('party.event.host_city'))) {
			$panel['conditions'][] = '<i class="fa fa-home fa-fw"></i> Неизвестно как приедет';
		}
		else {
			$panel['conditions'][] = array('danger','<i class="fa fa-question-circle fa-fw"></i> Неизвестно как приедет');
			$panel['unknown'][] = 'Прибытие неизвестно';
		}
		break;
}




$duplicates = $this->pixie->orm->get('participant')->where('id','<>',$page['part']->id)->where('firstname',trim($page['part']->firstname))->where('lastname',trim($page['part']->lastname))->find_all()->as_array();


$orders = $this->pixie->orm->get('order')->find_all()->as_array();
foreach($orders as $order) {
	try {
		$purpose = unserialize($order->purpose);
		if(count($purpose)) {
			if(in_array($page['part']->id, $purpose))
				$panel['orders'][] = $order;
		}
	}
	catch(Exception $e) {
		continue;
	}
}
$panel['ordersIcon'] = " <span class=\"pull-right label label-default\"><i class=\"fa fa-money fa-lg fa-fw\"></i> ". count($panel['orders']) ."</span>";
/*
if($page['part']->user->orders->count_all()) {
	$orders = $page['part']->user->orders->find_all()->as_array();
	foreach($orders as $order) {
		try {
			$purpose = unserialize($order->purpose);
			if(count($purpose)) {
				if(in_array($page['part']->id, $purpose))
					$panel['orders'][] = $order;
			}
		}
		catch(Exception $e) {
			continue;
		}
	}
	$panel['ordersIcon'] = " <span class=\"pull-right label label-default\"><i class=\"fa fa-money fa-lg fa-fw\"></i> ". count($panel['orders']) ."</span>";
}
*/


?>

<div class="row">

	<div class="col-sm-6 col-md-4">

		<div class="panel panel-default">
			<span id="part-id" data-value="<?=$page['part']->id?>"></span>
			<div class="panel-heading"><h4>Личная информация <?=$panel['userIcon']?></h4></div>
		<?php if(count($panel['identity'])): ?>
			<ul class="list-group">
		<?php foreach($panel['identity'] as $item): ?>
				<li class="list-group-item"><?=$item?></li>
		<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		</div>

	</div>

	<div class="col-sm-6 col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading"><h4>Условия участия</h4></div>
		<?php if(count($panel['conditions'])): ?>
			<ul class="list-group">
				<li class="list-group-item">
					<div class="btn-group">
						<button title="Подтвержд<?=($page['part']->gender==1 ? "ён" : "ена")?>" class="btn btn-default btn-xs part-approve<?=($page['part']->approved ? " active" : "")?>"><i class="fa fa-2x fa-check<?=($page['part']->approved ? " text-success" : "")?>"></i><br/>Подтв.</button>
						<button title="Прибыл<?=($page['part']->gender==1 ? "" : "а")?>" class="btn btn-default btn-xs part-attend<?=($page['part']->attend ? " active" : "")?>"><i class="fa fa-2x fa-car<?=($page['part']->attend ? " text-success" : "")?>"></i><br/>Прибыл<?=($page['part']->gender==1 ? "" : "а")?></button>
						<button title="Отмена" class="btn btn-default btn-xs part-cancel<?=($page['part']->cancelled ? " active" : "")?>"><i class="fa fa-2x fa-times<?=($page['part']->cancelled ? " text-success" : "")?>"></i><br/>Отмена</button>
						<button title="Оплатить" class="btn btn-default btn-xs part-pay" data-toggle="modal" data-target=".payment-modal"><i class="fa fa-2x fa-money"></i><br/>Оплатить</button>
					</div>
				</li>
		<?php foreach($panel['conditions'] as $item): ?>
<?php if(is_array($item)): ?>
				<li class="list-group-item list-group-item-<?=$item[0]?>"><?=$item[1]?></span></li>
<?php else: ?>
				<li class="list-group-item"><?=$item?></li>
<?php endif; ?>
		<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading"><h4>Оплата <?=$panel['payIcon']?></h4></div>
		<?php if(count($panel['payment'])): ?>
			<ul class="list-group">
		<?php foreach($panel['payment'] as $item): ?>
				<li class="list-group-item"<?=((isset($item[1])&&$item[1]) ? " id=\"{$item[1]}\"" : "")?>><?=$item[0]?></li>
		<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		</div>

	</div>

	<div class="col-sm-6 col-md-4">
<!-- 
		<div class="panel panel-default part-controls">
			<div class="panel-heading"><h4>Управление</h4></div>
			<div class="panel-body">
				<div class="btn-group">
					<button title="Подтвержд<?=($page['part']->gender==1 ? "ён" : "ена")?>" class="btn btn-default btn-xs part-approve<?=($page['part']->approved ? " active" : "")?>"><i class="fa fa-2x fa-check<?=($page['part']->approved ? " text-success" : "")?>"></i><br/>Подтв.</button>
					<button title="Прибыл<?=($page['part']->gender==1 ? "" : "а")?>" class="btn btn-default btn-xs part-attend<?=($page['part']->attend ? " active" : "")?>"><i class="fa fa-2x fa-car<?=($page['part']->attend ? " text-success" : "")?>"></i><br/>Прибыл<?=($page['part']->gender==1 ? "" : "а")?></button>
					<button title="Отмена" class="btn btn-default btn-xs part-cancel<?=($page['part']->cancelled ? " active" : "")?>"><i class="fa fa-2x fa-times<?=($page['part']->cancelled ? " text-success" : "")?>"></i><br/>Отмена</button>
					<button title="Оплатить" class="btn btn-default btn-xs part-pay" data-toggle="modal" data-target=".payment-modal"><i class="fa fa-2x fa-money"></i><br/>Оплатить</button>
				</div>
			</div>
		</div> -->

		<div class="modal fade payment-modal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Закрыть</span></button>
						<h4 class="modal-title">Взнос наличными</h4>
					</div>
					<div class="modal-body">
						<div class="input-group">
							<input type="number" name="payment-sum" class="form-control input-lg text-right" id="payment-sum"<?php echo $check['needToFull'] ? " value=\"{$check['needToFull']}\"" : ""; ?> placeholder="Укажите сумму"/><span class="input-group-addon">руб.</span>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
						<input type="submit" class="btn btn-primary" id="payment-submit" value="Принять" />
					</div>
				</div>
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading"><h4>Откуда</h4></div>
		<?php if(count($panel['geo'])): ?>
			<div class="list-group">
		<?php foreach($panel['geo'] as $item): ?>
<?php if(is_array($item)): ?>
				<a class="list-group-item" href="<?=$item[0]?>"><?=$item[1]?></span></a>
<?php else: ?>
				<div class="list-group-item"><?=$item?></div>
<?php endif; ?>
		<?php endforeach; ?>
			</div>
		<?php endif; ?>
		</div>

<?php if(count($panel['accomodation'])): ?>
		<div class="panel panel-success">
			<div class="panel-heading"><h4>Проживание</h4></div>
			<div class="list-group">
		<?php foreach($panel['accomodation'] as $item): ?>
				<div class="list-group-item"><?=$item?></div>
		<?php endforeach; ?>
			</div>
		</div>
<?php endif; ?>

<?php if(trim($page['part']->comment)): ?>
		<div class="panel panel-primary">
			<div class="panel-heading"><h4>Комментарий</h4></div>
			<div class="panel-body">
				<i class="fa fa-quote-left fa-fw text-primary"></i> <?=$page['part']->comment?>
			</div>
		</div>
<?php endif; ?>

<?php if(count($duplicates)): ?>
		<div class="panel panel-danger">
			<div class="panel-heading"><h4>Дубликаты</h4></div>
			<div class="list-group">
		<?php foreach($duplicates as $item): ?>
				<div class="list-group-item"><?=($item->cancelled ? "<s>" : "")?><a href="/admin/participants/<?=$item->id?>"><?=$item->firstname?> <?=$item->lastname?></a><?php echo ($item->money ? " <sup>{$item->money}</sup>" : "");?><?=($item->cancelled ? "</s>" : "")?></div>
		<?php endforeach; ?>
			</div>
		</div>
<?php endif; ?>


		<div class="panel panel-<?php echo count($panel['unknown']) ? 'danger' : 'success';?>">
			<div class="panel-heading"><h4><?=$page['partInfo']['percent']?>% заполнено</h4></div>
		<?php if(count($panel['unknown'])): ?>
			<ul class="list-group">
		<?php foreach($panel['unknown'] as $item): ?>
				<li class="list-group-item"><i class="fa fa-question-circle fa-fw text-danger"></i> <?=$item?></li>
		<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		</div>

	</div>

</div>




<?php if(count($panel['orders'])): ?>
<div class="row">
	<div class="col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading"><h4>Платежи<?=$panel['ordersIcon']?></h4></div>
			<div class="table-responsive">
			<table class="table table-condensed table-hover">
				<thead>
					<tr>
						<th>№</th>
						<th><span class="hidden-xs">статус</span></th>
						<th>сумма</th>
						<th>назначение</th>
						<th data-sort="descending">дата заявки</th>
						<th>дата завершения</th>
					</tr>
				</thead>
				<tbody>
			<?php foreach($panel['orders'] as $order): ?>
<?php
if($order->payed) {
	$state = $order->cash
		? "<span class=\"fa fa-money text-success\" title=\"получен наличными\"></span>"
		: "<span class=\"fa fa-credit-card text-success\" title=\"получен переводом\"></span>";
}
else
	$state = "<span class=\"fa fa-times-circle text-danger\" title=\"не получен\">";
?>
					<tr<?php if(!$order->payed || $order->cancelled) echo " class=\"text-muted\"";?>>
						<td class="text-muted"><small><?=$order->id?></small></td>
						<td data-order="<?=$order->payed?>"><?=$state?></td>
						<td><?=$order->sum?></td>
						<td><?php
			try {
				$parts = unserialize($order->purpose);
				if(count($parts)) {
					$names = array();
					foreach($parts as $part) {
						$p = $this->pixie->orm->get('participant',$part);
						if($p->loaded())
							$names[] = ($p->cancelled ? "<s>" : "") ."<a href=\"/admin/participants/{$p->id}\">{$p->firstname} {$p->lastname}</a>". ($p->cancelled ? "</s>" : "");
					}
					if(count($names))
						echo implode("<br/>", $names);
					if($order->cash)
						echo "<br/>Принято: <a href=\"/admin/users/{$order->user->id}\">{$order->user->name}</a>\n";;
				}
			}
			catch(Exception $e) {
				echo "ошибка";
			}
			?></td>
						<td data-order="<?=strtotime($order->datecreated)?>"><?php echo $formatterShort2->format(new \DateTime($order->datecreated)); ?></td>
						<td data-order="<?=strtotime($order->datepayed ? $order->datepayed : "tomorrow")?>"><?php echo $order->datepayed ? $formatterShort2->format(new \DateTime($order->datepayed)) : "&mdash;"; ?></td>
					</tr>
			<?php endforeach; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

