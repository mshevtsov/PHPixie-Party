<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">Телефоны</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped table-condensed dataTables">
						<thead>
							<tr>
								<th>Исходный</th>
								<th>Исправленный</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($page['candidates']['phones'] as $item): ?>
							<tr class="odd gradeX">
								<td><?=$item[0]?></td>
								<td><?=$item[1]?></td>
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
					<table class="table table-striped table-condensed dataTables">
						<thead>
							<tr>
								<th>Исходный</th>
								<th>Исправленный</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($page['candidates']['websites'] as $item): ?>
							<tr class="odd gradeX">
								<td><?=$item[0]?></td>
								<td><?=$item[1]?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</div>
		</div>

	</div>
</div>
<!-- /. ROW  -->
