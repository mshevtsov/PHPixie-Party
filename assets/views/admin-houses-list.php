<div class="well"><p><strong>Инструкция:</strong> 1) выбрать с помощью галочек одного или нескольких участников в таблице справа для расселения, 2) выбрать на карте одну из меток на карте и нажать на неё, 3) во всплывающем окне нажать "поселить их сюда".</p>
<p><strong>Цвета меток:</strong> когда отмечены участники в таблице справа, на карте отмечаются только дома, которые могут принять сразу всех отмеченных участников. Зелёный: значит пол полностью соответствует принимаемому (т.е. не используются дома, принимающие "любой пол"). Жёлтый: значит используются дома, где принимают любой пол. Красный: значит используются запасные места, которые указаны хозяевами на крайний случай. Тёмно-зелёный: как простой зелёный, только круче - число свободных мест в доме совпадает с числом выбранных участников (это тип самый приоритетный для выбора в первую очередь).</p>
<p>Стоит учитывать транспорт: значок машины под картой фильтрует отображаемые дома по наличию возможности встретить. А в таблице в колонке "Транспорт" текст выделяется курсивом и грязно-жёлтым цветом тогда, когда участник выбрал для проживание пункт "хостел" или "другое".</p>
<p>На заголовки таблицы нужно кликать, чтобы включить сортировку по конкретному столбцу. Это полезно во многих случаях: увидеть, кто зарегистрирован вместе, кто с одного региона, кто на поезде, а кто на машине и т. д. Можно сортировать и самую первую колонку с галочками. А в последней колонке - наводить мышку на значки комментария, чтобы прочитать во всплывающей подсказке.</p></div>

<div class="progress">
	<div class="progress-bar progress-bar-<?php echo $page['ready']>=100 ? "success" : "danger"; ?> progress-bar-striped active"  role="progressbar" aria-valuenow="<?=$page['ready']?>" aria-valuemin="0" aria-valuemax="100" style="width: <?=$page['ready']?>%">
		расселено <?=$page['ready']?>%
	</div>
</div>


<div class="row">
	<div class="col-sm-6">
		<div id="mapHousesAll" class="yamap"></div>
		<div class="text-center">
			<div>
				<div class="btn-group" id="mapPlaces" data-toggle="buttons">
					<label class="btn btn-default btn-sm" title="места для мужчин">
						<input type="radio" name="options" id="mapPlacesM" value="places1" data-color="blue"><i class="fa fa-male fa-fw text-primary"></i>
					</label>
					<label class="btn btn-default btn-sm" title="места для женщин">
						<input type="radio" name="options" id="mapPlacesF" value="places2" data-color="red"><i class="fa fa-female fa-fw text-danger"></i>
					</label>
					<label class="btn btn-default btn-sm" title="места для любого пола">
						<input type="radio" name="options" id="mapPlacesB" value="places3" data-color="green"><i class="fa fa-users fa-fw text-success"></i>
					</label>
				</div>
				<button id="mapMeeting" type="button" class="btn btn-sm btn-default" data-toggle="button" title="хозяин может встретить"><i class="fa fa-car fa-fw"></i></button>
			</div>
			<div class="text-muted"><small>фильтр по полу и встрече</small></div>
		</div>
	</div>
	<div class="col-sm-6">
		<table class="display compact dataTables settlers">
			<thead>
				<tr>
					<th>#</th>
					<th>Имя</th>
					<th><span style="white-space:nowrap"><i class="fa fa-male text-primary"></i><i class="fa fa-female text-danger"></i></span></th>
					<th><i class="fa fa-calendar" title="возраст"></i></th>
					<th>Регион</th>
					<th>Трансп.</th>
					<th><i class="fa fa-users" title="номер команды"></i></th>
					<th><i class="fa fa-money" title="дата оплаты"></i></th>
					<th><i class="fa fa-comment" title="комментарий участника"></i></th>
					<th><i class="fa fa-check" title="прибыл"></i></th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>

<?php /*
<div class="row">

	<div class="col-sm-6">

		<p>Всего мест для мужчин: <?=$page['p1_total']?></p>
		<p>Свободных мест для мужчин: <?=$page['p1_free']?></p>
		<p>Всего мест для женщин: <?=$page['p2_total']?></p>
		<p>Свободных мест для женщин: <?=$page['p2_free']?></p>
		<p>Всего мест для любого пола: <?=$page['p3_total']?></p>
		<p>Свободных мест для любого пола: <?=$page['p3_free']?></p>
		<p>Всего дополнительных мест под вопросом: <?=$page['p4_total']?></p>
		<p>Свободных дополнительных мест под вопросом: <?=$page['p4_free']?></p>

		<p>Итого мест: <?=$page['p1_total']+$page['p2_total']+$page['p3_total']+$page['p4_total']?></p>
		<p>Итого свободных мест: <?=$page['p1_free']+$page['p2_free']+$page['p3_free']+$page['p4_free']?></p>

	</div>
</div>
*/ ?>


<div class="row">
	<div class="col-xs-12">
		<div class="table-responsive">
			<table class="table table-condensed table-hover dataTables houses">
				<thead>
					<tr>
						<th>#</th>
						<th>Имя</th>
						<th><i class="fa fa-users text-muted"></i></th>
						<th><i class="fa fa-users text-primary"></i></th>
						<th><i class="fa fa-phone"></i></th>
						<th><i class="fa fa-car"></i></th>
						<th>Район</th>
						<th>Церковь</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
