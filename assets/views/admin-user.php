<?php
$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern("d MMMM YYYY 'в' H:mm");
$formatterShort = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatterShort->setPattern('d MMMM YYYY');
$formatterShort2 = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatterShort2->setPattern('d MMMM H:mm');

$panel = array(
	'userIcon' => "<span class=\"pull-right label label-". ($page['user']->active ? "success" : "danger") ."\">№ {$page['user']->id}</span>",
	'identity' => array(
		"<i class=\"fa fa-user fa-fw\"></i> {$page['user']->name}",
		"<i class=\"fa fa-envelope fa-fw\"></i> {$page['user']->email}",
		$page['user']->active
			? "<i class=\"fa fa-check-circle fa-fw text-success\"></i> ". $formatterShort->format(new \DateTime($page['user']->codesent)) ." активирован"
			: "<i class=\"fa fa-times-circle fa-fw text-danger\"></i> ". $formatter->format(new \DateTime($page['user']->codesent)) ." <span class=\"text-muted\">отправлен код</span>",
		"<i class=\"fa fa-gift fa-fw\"></i> Льготный период <button data-userid=\"{$page['user']->id}\" class=\"pull-right btn btn-default btn-xs grace-user". ($page['user']->grace ? " active" : "") ."\"><i class=\"fa fa-gift". ($page['user']->grace ? " text-success" : "") ."\"></i> Льгота</button>",
		),
	'participantsIcon' => '',
	'participants' => array(),
	'ordersIcon' => '',
	'orders' => array(),
	'inbox' => array(),
	'outbox' => array(),
);
if($page['user']->role=='admin')
	$panel['identity'][] = "<span class=\"text-primary\"><i class=\"fa fa-certificate fa-fw\"></i> <strong>Администратор</strong></span>";
else if($page['user']->role=='operator')
	$panel['identity'][] = "<span class=\"text-primary\"><i class=\"fa fa-certificate fa-fw\"></i> <strong>Оператор</strong></span>";
else
	$panel['identity'][] = "<span class=\"text-muted\"><i class=\"fa fa-certificate fa-fw\"></i> Пользователь</span>";
if($page['user']->money)
	$panel['identity'][] = "<i class=\"fa fa-money fa-fw text-success\"></i> {$page['user']->money} <i class=\"fa fa-rouble\"></i>";

if($page['user']->participants->count_all()) {
	$team = $page['user']->participants->order_by('gender','asc')->find_all()->as_array();
	$teamCount = $page['user']->participants->where('cancelled',0)->count_all();
	$panel['participantsIcon'] = " <span class=\"pull-right label label-default\"><i class=\"fa fa-users fa-lg fa-fw\"></i> {$teamCount}</span>";
	foreach($team as $item)
		$panel['participants'][] = ($item->cancelled ? "<s>" : "") ."<i class=\"fa fa-fw ". ($item->gender==2 ? 'fa-female text-danger' : 'fa-male text-primary') ."\"></i> <a href=\"/admin/participants/{$item->id}\">{$item->firstname} {$item->lastname}</a>". ($item->cancelled ? "</s>" : ""). ($item->money ? "<sup>{$item->money}</sup>" : "");
}

if($page['user']->orders->count_all()) {
	$panel['orders'] = $page['user']->orders->find_all()->as_array();
	$panel['ordersIcon'] = " <span class=\"pull-right label label-default\"><i class=\"fa fa-money fa-lg fa-fw\"></i> ". count($panel['orders']) ."</span>";
}

if($page['user']->inbox->count_all()) {
	$inbox = $page['user']->inbox->find_all()->as_array();
	foreach($inbox as $item) {
		$content = trim(strip_tags($item->content));
		$message = "<span class=\"label label-default\">";
		if($item->type=='email')
			$message .= "<i class=\"fa fa-envelope fa-fw\"></i> ";
		else if($item->type=='vk')
			$message .= "<i class=\"fa fa-vk fa-fw\"></i> ";
		else if($item->type=='personal')
			$message .= "<i class=\"fa fa-comment-o fa-fw\"></i> ";
		else if($item->type=='phone')
			$message .= "<i class=\"fa fa-phone fa-fw\"></i> ";
		$message .= $formatterShort2->format(new \DateTime($item->date)) ."</span> ";
		$message .= "<strong><a href=\"/admin/users/{$item->sender->id}\">{$item->sender->name}</a>:</strong> <span class=\"usermsg\" data-msgid=\"{$item->id}\">". substr($content, 0, 200) ."&hellip;</span>";
		$panel['inbox'][] = $message;
	}
}

if($page['user']->outbox->count_all()) {
	$outbox = $page['user']->outbox->find_all()->as_array();
	foreach($outbox as $item) {
		$content = trim(strip_tags($item->content));
		$message = "<span class=\"label label-default\">";
		if($item->type=='email')
			$message .= "<i class=\"fa fa-envelope fa-fw\"></i> ";
		else if($item->type=='vk')
			$message .= "<i class=\"fa fa-vk fa-fw\"></i> ";
		else if($item->type=='personal')
			$message .= "<i class=\"fa fa-comment-o fa-fw\"></i> ";
		else if($item->type=='phone')
			$message .= "<i class=\"fa fa-phone fa-fw\"></i> ";
		$message .= $formatterShort2->format(new \DateTime($item->date)) ."</span> ";
		$message .= "для <strong><a href=\"/admin/users/{$item->recipient->id}\">{$item->recipient->name}</a>:</strong> <span class=\"usermsg\" data-msgid=\"{$item->id}\">". substr($content, 0, 200) ."&hellip;</span>";
		$panel['outbox'][] = $message;
	}
}

?>
<hr/>

<div class="row">

	<div class="col-sm-6 col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading"><h4>Регистратор <?=$panel['userIcon']?></h4></div>
		<?php if(count($panel['identity'])): ?>
			<ul class="list-group">
		<?php foreach($panel['identity'] as $item): ?>
				<li class="list-group-item"><?=$item?></li>
		<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		</div>
	</div>

	<?php if(count($panel['participants'])): ?>
	<div class="col-sm-6 col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading"><h4>Участники<?=$panel['participantsIcon']?></h4></div>
			<ul class="list-group">
		<?php foreach($panel['participants'] as $item): ?>
				<li class="list-group-item"><?=$item?></li>
		<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>
</div>

<?php if(count($panel['inbox'])): ?>
<div class="row">
	<div class="col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading"><h4>Сообщения для регистратора</h4></div>
			<ul class="list-group">
		<?php foreach($panel['inbox'] as $item): ?>
				<li class="list-group-item one-line"><?=$item?></li>
		<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if(count($panel['inbox']) || count($panel['outbox'])): ?>
<div class="modal fade" id="modal-usermsg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title">Сообщение</h4>
			</div>
			<div class="modal-body">
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if(count($panel['outbox'])): ?>
<div class="row">
	<div class="col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading"><h4>Сообщения от регистратора</h4></div>
			<ul class="list-group">
		<?php foreach($panel['outbox'] as $item): ?>
				<li class="list-group-item one-line"><?=$item?></li>
		<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if(count($panel['orders'])): ?>
<div class="row">
	<div class="col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading"><h4>Платежи<?=$panel['ordersIcon']?></h4></div>
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
					<tr<?php if(!$order->payed) echo " class=\"text-muted\"";?>>
						<td class="text-muted"><small><?=$order->id?></small></td>
						<td data-order="<?=$order->payed?>"><?php echo $order->payed ? "<span class=\"fa fa-check-circle text-success\" title=\"получен\"></span>" : "<span class=\"fa fa-times-circle text-danger\" title=\"не получен\">"; ?></td>
						<td><?=$order->sum?></td>
						<td><?php
			try {
				$parts = unserialize($order->purpose);
				if(count($parts)) {
					$names = array();
					foreach($parts as $part) {
						$p = $this->pixie->orm->get('participant',$part);
						if($p->loaded())
							$names[] = "<a href=\"/admin/participants/{$p->id}\">{$p->firstname} {$p->lastname}</a>". ($p->money ? "<sup>{$p->money}</sup>" : "");
					}
					if(count($names))
						echo implode("<br/>", $names);
				}
			}
			catch(Exception $e) {
				echo "ошибка";
			}
			?></td>
						<td data-order="<?=strtotime($order->datecreated)?>"><?php echo $formatter->format(new \DateTime($order->datecreated)); ?></td>
						<td data-order="<?=strtotime($order->datepayed ? $order->datepayed : "tomorrow")?>"><?php echo $order->datepayed ? $formatter->format(new \DateTime($order->datepayed)) : "&mdash;"; ?></td>
					</tr>
			<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php endif; ?>


