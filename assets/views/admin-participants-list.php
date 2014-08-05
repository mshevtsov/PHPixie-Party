
<div class="table-responsive">
<table class="table table-condensed table-hover dataTables participants">
	<thead>
		<tr>
<?php foreach(reset($page['table']) as $id => $row): ?>
			<th><?=$id?></th>
<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
<?php foreach($page['table'] as $id => $row): ?>
		<tr>
<?php foreach($row as $title => $value): ?>
			<td<?php if(is_array($value)) echo " data-order=\"{$value[0]}\""; echo ">". (is_array($value) ? $value[1] : $value); ?></td>
<?php endforeach; ?>
		</tr>
<?php endforeach; ?>
	</tbody>

</table>
</div>
