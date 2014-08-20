<p>Будут питаться: <?=$page['partsMeal']?> чел.<br/>
<?php
ksort($page['daysMeal']);
$page['daysMeal'] = array_reverse($page['daysMeal'],true);

foreach($page['daysMeal'] as $id=>$day) {
	echo $id ." ". $this->pixie->party->declension($id,array('день','дня','дней')) .": ". $day ."<br/>";
}

$total = 0; $i = 1;
foreach($page['daysMeal'] as $id=>$day) {
	$total += $day;
	echo "День {$i}: ". $total ."<br/>";
	$i++;
}
?>В субботу питается парней: <?=$page['partsSatMealMale']?>, девушек: <?=$page['partsSatMealFemale']?>.</p>

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
