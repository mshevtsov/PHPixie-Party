<?php
$empty = true;
foreach($page['candidates'] as $key => $value)
	if(count($value)) {
		$empty = false;
		break;
	}
?>
<?php if($empty): ?>
<div class="alert alert-success">Нарушений формата не найдено</div>
<?php else: ?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">Телефоны</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped table-condensed dataTables common">
						<thead>
							<tr>
								<th>Исходный</th>
								<th>Исправленный</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($page['candidates']['phones'] as $item): ?>
							<tr>
								<td><a href="/admin/participants/<?=$item[0]?>"><?=$item[1]?></a></td>
								<td><?=$item[2]?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading">Сайты и соцсети</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped table-condensed dataTables common">
						<thead>
							<tr>
								<th>Исходный</th>
								<th>Исправленный</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($page['candidates']['websites'] as $item): ?>
							<tr>
								<td><a href="/admin/participants/<?=$item[0]?>"><?=$item[1]?></a></td>
								<td><a href="<?=$item[2]?>" target="_blank"><?=$item[2]?></a></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
		</div>

	</div>
</div>
<form method="POST">
	<div class="text-center">
		<input type="hidden" name="action" value="confirm" />
		<input type="submit" value="Всё правильно, сохранить" class="btn btn-primary confirm"/>
	</div>
</form>
<?php endif; ?>
