<?php
$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern("d MMMM YYYY 'в' H:mm");
?>
<h3 class="text-center">Отчёт по расселению участников</h3>
<p>Список мест проживания участников <?=$this->pixie->config->get('party.event.title')?> из других городов. Сформирован <?php echo $formatter->format(new \DateTime('now')); ?>. Готов на <?=$page['ready'][0]?>%. Расселены <?=$page['ready'][1] .' '. $this->pixie->party->declension($page['ready'][1], array('участник','участника','участников'))?> из <?=$page['ready'][2] .' '. $this->pixie->party->declension($page['ready'][2], array('нуждающегося','нуждающихся','нуждающихся'))?> в жилье, использованы <?=$page['ready'][3] .' '. $this->pixie->party->declension($page['ready'][3], array('дом','дома','домов'))?>.</p>

<table class="table table-bordered1 table-condensed">
	<thead>
		<tr class="text-info">
			<th><i class="fa fa-home"></i> Хозяева</th>
			<th colspan="6"><i class="fa fa-child"></i> Гости</th>
		</tr>
		<tr>
			<th>Контакты</th>
			<th>Имя</th>
			<th>Дни</th>
			<th>Город</th>
			<th>Транспорт</th>
			<th>Телефон</th>
			<th>Прибыл</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($page['list'] as $key=>$church): ?>Центральная

		<tr>
			<td colspan="7" class="text-center"><strong>Церковь &laquo;<?=$key?>&raquo;</strong></td>
		</tr>
<?php foreach($church as $house): ?>
<?php foreach($house['guests'] as $id=>$guest): ?>
		<tr>
<?php if(!$id): ?>
			<td rowspan="<?=count($house['guests'])?>">
				<strong><a href="/admin/houses/<?=$house['id']?>"><?=$house['name']?></a></strong>
				<ul class="text-muted fa-ul">
					<?php if($house['phone']): ?>
					<li><i class="fa-li fa fa-phone"></i> <?=str_replace(';','<br/>',$house['phone'])?></li>
					<?php endif; ?>
					<?php if($house['district']): ?>
					<li><i class="fa-li fa fa-map-marker"></i> <?=$house['district']?></li>
					<?php endif; ?>
					<?php if($house['transport']): ?>
					<li><i class="fa-li fa fa-car"></i> Транспорт на <?=$house['transport']?> мест</li>
					<?php endif; ?>
					<?php if($house['meeting']): ?>
					<li><i class="fa-li fa fa-child"></i> Может встретить</li>
					<?php endif; ?>
				</ul>
			</td>
<?php endif; ?>
			<td><span class="badge"><?=$guest['age']?></span> <a href="/admin/participants/<?=$guest['id']?>"><?=$guest['name']?></a></td>
			<td><?=$guest['days']?></td>
			<td><?=$guest['city']?></td>
			<td><?=$guest['transport']?></td>
			<td><?=preg_replace('/[\(\)\-]/','',$guest['phone'])/*str_replace(',','<br/>',$guest['phone'])*/?></td>
			<td><?=($guest['attend'] ? "<i class=\"fa fa-check\"></i>" : "")?></td>
		</tr>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
	</tbody>
</table>
