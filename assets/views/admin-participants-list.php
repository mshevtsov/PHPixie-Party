
<div class="form-inline" role="form">
	<div class="form-group">
		<label for="filter" class="control-label">Быстрый поиск</label>
		<input id="filter" class="form-control" placeholder="Начните вводить"/>
	</div>

	<div class="form-group">
		<label for="filter-status" class="control-label">Фильтр</label>
		<select id="filter-status" class="form-control">
			<option></option>
			<option value="1">Не активирован</option>
			<option value="10">Предоплата</option>
			<option value="20">Полный взнос</option>
			<option value="8">В обработке</option>
			<option value="3">Ещё не оплатил</option>
		</select>
	</div>
</div>

<div class="table-responsive">
<table class="table table-condensed table-hover footable" data-page-size="20" data-page-navigation=".pagn" data-filter="#filter">
	<thead>
		<tr class="active">
<?php foreach(reset($page['table']) as $title=>$value): ?>
			<th<?php
if($title=='email') echo ' data-hide="phone"';
// if($title=='name') echo ' data-toggle="true"';

if($title=='оплачено') echo ' data-type="numeric" data-sort-initial="descending"';
?>><?=$title?></th>
<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
<?php foreach($page['table'] as $id=>$row): ?>
		<tr>
<?php foreach($row as $title=>$value): ?>
			<td<?php
$code = "";
if($title=='статус') {
	if($value=='Не активирован')
		$code = 1;
	else if(strpos($value,'Предоплата')!==false)
		$code = 10;
	else if(strpos($value,'Полный взнос')!==false)
		$code = 20;
	else if(strpos($value,'в обработке')!==false)
		$code = 8;
	else if(strpos($value,'Ещё не оплатил')!==false)
		$code = 3;
	else
		$code = 2;
}
if($code!="")
	echo " data-value=\"{$code}\"";
echo ">";
if(is_array($value))
	echo implode(", ", $value);
else
	echo $value;

?></td>
<?php endforeach; ?>
		</tr>
<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="<?=count(reset($page['table']))?>">
				<div class="pagn text-center hide-if-no-paging"></div>
			</td>
		</tr>
	</tfoot>
</table>
</div>
