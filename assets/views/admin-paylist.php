<?php
$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern('d MMMM, H:mm');
?>
<div class="table-responsive">
<table class="table table-condensed table-hover dataTables paylist">
	<thead>
		<tr>
			<th>№</th>
			<th><span class="hidden-xs">статус</span></th>
			<th>сумма</th>
			<th data-sort="descending">дата заявки</th>
			<th>дата завершения</th>
			<th>назначение</th>
			<th>отправитель</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($page['orders'] as $order): ?>
		<tr<?php if(!$order->payed) echo " class=\"text-muted\"";?>>
			<td class="text-muted"><small><?=$order->id?></small></td>
			<td data-order="<?=$order->payed?>"><?php echo $order->payed ? "<span class=\"fa fa-check-circle text-success\" title=\"получен\"></span>" : "<span class=\"fa fa-times-circle text-danger\" title=\"не получен\">"; ?></td>
			<td><?=$order->sum?></td>
			<td data-order="<?=strtotime($order->datecreated)?>"><?php echo $formatter->format(new \DateTime($order->datecreated)); ?></td>
			<td data-order="<?=strtotime($order->datepayed ? $order->datepayed : "tomorrow")?>"><?php echo $order->datepayed ? $formatter->format(new \DateTime($order->datepayed)) : "&mdash;"; ?></td>
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
			<td><?php echo "<a href=\"/admin/users/{$order->user->id}\">{$order->user->name}</a> ({$order->user->email})"; ?>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
</div>
