<div class="row">
	<div class="col-md-12">

<?php if(isset($page['fellowCity']) && count($page['fellowCity'])): ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?php echo reset($page['fellowCity'])->city; ?></div>
			<table class="table table-condensed dataTables">
				<tbody>
				<?php foreach($page['fellowCity'] as $part): ?>
					<tr>
						<td><?php echo "<a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a>"; ?></td>
						<!-- <td><?php ?></td> -->
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
<?php endif; ?>
<?php if(isset($page['fellowRegion']) && count($page['fellowRegion'])): ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?php echo reset($page['fellowRegion'])->region->name; ?></div>
			<table class="table table-condensed dataTables">
				<tbody>
				<?php foreach($page['fellowRegion'] as $part): ?>
					<tr>
						<td><?php echo "<a href=\"/admin/participants/{$part->id}\">{$part->firstname} {$part->lastname}</a>"; ?></td>
						<!-- <td><?php ?></td> -->
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
<?php endif; ?>
<?php if((!isset($page['fellowCity']) || !count($page['fellowCity'])) && (!isset($page['fellowRegion']) || !count($page['fellowRegion']))): ?>
		<div class="alert alert-danger">Попутчиков ещё нет</div>
<?php endif; ?>
	</div>
</div>
<!-- /. ROW  -->
