<table class="table">
<?php foreach($page['table'] as $id=>$row): ?>
	<tr>
<?php foreach($row as $title=>$value): ?>
		<td><?=$value?></td>
<?php endforeach; ?>
	</tr>
<?php endforeach; ?>
</table>