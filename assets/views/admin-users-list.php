<?php
$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern('d MMMM');
?>
<div class="table-responsive">
<table class="table table-condensed table-hover dataTables users">
	<thead>
		<tr>
			<th data-sort="ascending">id</th>
			<th>имя</th>
			<th>email</th>
			<th>дата</th>
			<th>участники</th>
			<th>незарег</th>
			<th>платежи</th>
			<th>льгота</th>
			<th>активен</th>
			<th>роль</th>
			<th data-orderable="0">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($page['users'] as $user): ?>
		<tr<?php if(!$user->active) echo " class=\"text-muted\"";?>>
			<td class="text-muted"><small><?=$user->id?></small></td>
			<td><a href="/admin/users/<?=$user->id?>"><?=$user->name?></a></td>
			<td><?=$user->email?></td>
			<td data-order="<?=strtotime($user->codesent)?>"><?php echo $formatter->format(new \DateTime($user->codesent)); ?></td>
			<td class="text-center"><?=$user->participants->where('cancelled',0)->count_all()?></td>
			<td class="text-center"><?=$user->participants->where('cancelled',0)->where('money','<',$this->pixie->config->get('party.periods.1.prices.prepay'))->count_all()?></td>
			<td data-order="<?=$user->orders->count_all()?>" class="text-center"><?=$user->orders->count_all()?><?php $o = $user->orders->order_by('datecreated','desc')->limit(1)->find(); if($o->loaded()) {echo "<sup>". ceil((strtotime('today')-strtotime($o->datecreated))/86400) ."</sup>";}?></td>
			<td class="text-center" data-order="<?=$user->grace ? 1 : 0;?>"><?php echo $user->grace ? "<i class=\"fa fa-check-circle text-success\"></i>" : ""; ?></td>
			<td class="text-center" data-order="<?=$user->active ? 1 : 0;?>"><?php echo $user->active ? "<i class=\"fa fa-check text-success\"></i>" : ""; ?></td>
			<td data-order="<?php if($user->role=='admin') echo 10; else if($user->role=='operator') echo 5;?>"><?=$user->role?></td>
			<td>
				<div class="btn-group usermsg" data-userid="<?=$user->id?>" data-inbox="<?=$user->inbox->count_all()?>">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fa fa-envelope fa-fw"></i> <?=$user->inbox->count_all()?> <span class="caret"></span></button>
					<ul class="dropdown-menu dropdown-menu-right" role="menu">
						<li><a href="#" data-msgtype="1">Напомнить об активации</a></li>
						<li><a href="#" data-msgtype="2">Поторопить с оплатой</a></li>
						<li><a href="#" data-msgtype="3">Предложить помощь</a></li>
					</ul>
				</div>
			</td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
</div>
