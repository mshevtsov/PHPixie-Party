<?php
// $markers = array();
// foreach($page['houses'] as $item) {
// 	$markers[] = array(
// 		'id'=>$item->id,
// 	);
// }
?>

<div class="alert alert-danger">Это пока ещё только наброски, к сожалению. Но скоро появится возможность расселять.</div>

<div class="row">
	<div class="col-sm-6">
		<div id="mapHouse" class="yamap"></div>
		<div class="text-center">
			<div class="btn-group" id="mapPlaces" data-toggle="buttons">
				<label class="btn btn-default btn-sm">
					<input type="radio" name="options" id="mapPlacesM" value="places1" data-color="blue"><i class="fa fa-male fa-fw text-primary"></i>
				</label>
				<label class="btn btn-default btn-sm">
					<input type="radio" name="options" id="mapPlacesF" value="places2" data-color="red"><i class="fa fa-female fa-fw text-danger"></i>
				</label>
				<label class="btn btn-default btn-sm">
					<input type="radio" name="options" id="mapPlacesB" value="places3" data-color="green"><i class="fa fa-users fa-fw text-success"></i>
				</label>
			</div>
			<button id="mapMeeting" type="button" class="btn btn-sm btn-default" data-toggle="button"><i class="fa fa-car fa-fw"></i></button>
		</div>
	</div>

	<div class="col-sm-6">


	</div>


</div>


<div class="row">
	<div class="col-xs-12">
		<div class="table-responsive">
			<table class="table table-condensed table-hover dataTables houses">
				<thead>
					<tr>
						<th>Имя</th>
						<th>Места</th>
						<th>Встреча</th>
						<th>Район</th>
					</tr>
				</thead>
				<tbody>
			<?php foreach($page['houses'] as $item): ?>
					<tr>
						<td><?=$item->name?></td>
						<td data-order="<?php echo $item->places1 + $item->places2 + $item->places3 + $item->places4; ?>"><?php if($item->places1) echo $item->places1 ."М "; if($item->places2) echo $item->places2 ."Ж "; if($item->places3) echo $item->places3 ."Л "; if($item->places4) echo $item->places4 ."в"; ?></td>
						<td><?php if($item->meeting) echo "да"; ?></td>
						<td><?php if($item->district) echo $item->district; ?></td>
					</tr>
			<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
