<?php
?>

<form class="form-horizontal" role="form" method="POST">

<div class="row">
	<div class="col-sm-6">

		<div class="panel panel-default">
			<div class="panel-body">
				<div class="form-group">
					<label for="name" class="col-sm-3 control-label">Имя</label>
					<div class="col-sm-9">
						<input class="form-control" id="name" type="text" name="name" value="<?=htmlentities($page['house']->name)?>" placeholder="ФИО для обращения"/>
					</div>
				</div>
				<div class="form-group">
					<label for="church" class="col-sm-3 control-label">Церковь</label>
					<div class="col-sm-9">
						<input class="form-control" id="church" type="text" name="church" value="<?=htmlentities($page['house']->church)?>" placeholder="название церкви, напр. 'Центральная'"/>
					</div>
				</div>
				<div class="form-group">
					<label for="phone" class="col-sm-3 control-label">Телефон</label>
					<div class="col-sm-9">
						<input class="form-control" id="phone" type="text" name="phone" value="<?=htmlentities($page['house']->phone)?>" placeholder="один или два телефона через запятую"/>
					</div>
				</div>
				<div class="form-group">
					<label for="comment" class="col-sm-3 control-label">Комментарий</label>
					<div class="col-sm-9">
						<textarea class="form-control" id="comment" name="comment" rows="3"><?=htmlentities($page['house']->comment)?></textarea>
					</div>
				</div>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="form-group col-sm-6">
					<div class="row">
						<label for="places1" class="col-sm-6 control-label">Места М</label>
						<div class="col-sm-6">
							<input class="form-control" id="places1" type="number" name="places1" value="<?=$page['house']->places1?>"/>
						</div>
					</div>
				</div>
				<div class="form-group col-sm-6">
					<div class="row">
						<label for="places2" class="col-sm-6 control-label">Места Ж</label>
						<div class="col-sm-6">
							<input class="form-control" id="places2" type="number" name="places2" value="<?=$page['house']->places2?>"/>
						</div>
					</div>
				</div>
				<div class="form-group col-sm-6">
					<div class="row">
						<label for="places3" class="col-sm-6 control-label">Места Любых</label>
						<div class="col-sm-6">
							<input class="form-control" id="places3" type="number" name="places3" value="<?=$page['house']->places3?>"/>
						</div>
					</div>
				</div>
				<div class="form-group col-sm-6">
					<div class="row">
						<label for="places4" class="col-sm-6 control-label">Места Запас</label>
						<div class="col-sm-6">
							<input class="form-control" id="places4" type="number" name="places4" value="<?=$page['house']->places4?>"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-6">
		<div class="panel panel-default">
			<div class="panel-body">
				<div class="form-group">
					<label for="address" class="col-sm-3 control-label">Адрес</label>
					<div class="col-sm-9">
						<input class="form-control" id="address" type="text" name="address" value="<?=$page['house']->address?>" placeholder="ул. ХХХХ, д. ХХ, кв. ХХ"/>
					</div>
				</div>
				<div class="form-group">
					<label for="busstop" class="col-sm-3 control-label">Остановка</label>
					<div class="col-sm-9">
						<input class="form-control" id="busstop" type="text" name="busstop" value="<?=htmlentities($page['house']->busstop)?>" placeholder="ближ. остановка транспорта"/>
					</div>
				</div>
				<div class="form-group">
					<label for="district" class="col-sm-3 control-label">Район</label>
					<div class="col-sm-9">
						<input class="form-control" id="district" type="text" name="district" value="<?=htmlentities($page['house']->district)?>" placeholder="район города"/>
					</div>
				</div>
				<div class="form-group">
					<label for="city" class="col-sm-3 control-label">Город</label>
					<div class="col-sm-9">
						<input class="form-control" id="city" type="text" name="city" value="<?=htmlentities($page['house']->city)?>" placeholder="город"/>
					</div>
				</div>
				<div class="form-group">
					<label for="route" class="col-sm-3 control-label">Маршруты</label>
					<div class="col-sm-9">
						<input class="form-control" id="route" type="text" name="route" value="<?=htmlentities($page['house']->route)?>" placeholder="маршруты общ. транспорта"/>
					</div>
				</div>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-body text-center">

				<div class="form-group">
					<label for="transport" class="col-sm-6 control-label">Места в транспорте</label>
					<div class="col-sm-3">
						<input class="form-control" id="transport" type="number" name="transport" value="<?=$page['house']->transport?>"/>
					</div>
				</div>

				<div class="btn-group" data-toggle="buttons">
					<label class="btn btn-default<?php if($page['house']->meal) echo " active";?>">
						<input type="checkbox" name="meal"<?php if($page['house']->meal) echo " checked";?>> Завтраки
					</label>
					<label class="btn btn-default<?php if($page['house']->meeting) echo " active";?>">
						<input type="checkbox" name="meeting"<?php if($page['house']->meeting) echo " checked";?>> Встреча
					</label>
					<label class="btn btn-default<?php if($page['house']->delivery) echo " active";?>">
						<input type="checkbox" name="delivery"<?php if($page['house']->delivery) echo " checked";?>> Доставка
					</label>
				</div>
				<div data-toggle="buttons">
					<label class="btn btn-default<?php if($page['house']->future) echo " active";?>">
						<input type="checkbox" name="future"<?php if($page['house']->future) echo " checked";?>/> Готовность принимать в будущем
					</label>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="text-center"><input class="btn btn-primary" type="submit" value="Сохранить" /></div>
</form>
