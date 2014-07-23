<?php
if($page['part']->website && preg_match("/vk.com\/([\d\w\.]+)$/",$page['part']->website,$match))
	$user_id = $match[1];
else
	$user_id = "";
$enda = $page['part']->gender==2 ? "а" : "";

$findabout = unserialize($page['part']->findabout);
foreach($findabout as $key=>$value)
	$findabout[$key] = $this->pixie->config->get('party.userfields.findabout.options.'. $value);

$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern('d MMMM YYYY');

$age = date_diff(date_create($page['part']->birthday), date_create('now'))->y;

$daysTotal = $this->pixie->config->get('party.userfields.days.options');
$daysTotal = max(array_keys($daysTotal));

$accom = "";
switch($page['part']->accomodation) {
	case 1:
		$accom = '<span class="label label-success"><i class="fa fa-home"></i> нужно жильё</span>';
		break;
	case 2:
		$accom = '<span class="label label-warning"><i class="fa fa-building-o"></i> нужен хостел</span>';
		break;
	case 3:
		$accom = '<span class="label label-default"><i class="fa fa-home"></i> местный</span>';
		break;
	case 4:
		$accom = '<span class="label label-default"><i class="fa fa-home"></i> сам найдёт</span>';
		break;
	case 5:
		$accom = '<span class="label label-danger"><i class="fa fa-question-circle"></i> см. комментарии</span>';
		break;
	default:
		if(in_array($page['part']->city, $this->pixie->config->get('party.event.host_city')))
			$accom = '<span class="label label-default"><i class="fa fa-home"></i> о жилье не указано</span>';
		else
			$accom = '<span class="label label-danger"><i class="fa fa-question-circle"></i> о жилье не указано</span>';
		break;
}

$transport = "";
switch($page['part']->transport) {
	case 1:
		$transport = '<span class="label label-success"><i class="fa fa-car"></i> приедет на поезде</span>';
		break;
	case 2:
		$transport = '<span class="label label-warning"><i class="fa fa-plane"></i> прилетит на самолёте</span>';
		break;
	case 3:
		$transport = '<span class="label label-warning"><i class="fa fa-car"></i> приедет на автомобиле</span>';
		break;
	case 4:
		$transport = '<span class="label label-warning"><i class="fa fa-truck"></i> приедет на рейсовом автобусе</span>';
		break;
	case 5:
		$transport = '<span class="label label-warning"><i class="fa fa-truck"></i> приедет на церковном автобусе</span>';
		break;
	case 6:
		$transport = '<span class="label label-default"><i class="fa fa-home"></i> местный</span>';
		break;
	default:
		if(in_array($page['part']->city, $this->pixie->config->get('party.event.host_city')))
			$transport = '<span class="label label-default"><i class="fa fa-home"></i> о транспорте не указано</span>';
		else
			$transport = '<span class="label label-danger"><i class="fa fa-question-circle"></i> о транспорте не указано</span>';
		break;
}

?>
							<p><span class="label label-<?php echo $page['partInfo']['percent']>90 ? 'info' : 'warning';?>"><?=$page['partInfo']['percent']?>% заполнено</span></p>

							<p><?php echo $page['part']->gender==1 ? '<i class="fa fa-male text-primary"></i>' : '<i class="fa fa-female text-danger"></i>'; ?> <?php echo ($age>=1 && $age<1000) ? $formatter->format(new \DateTime($page['part']->birthday)) ." <span class=\"label label-info\">{$age}</span>" : '<span class="label label-danger">дата рождения не указана</span>'; ?></p>
							<p><?php echo $page['part']->email ? '<i class="fa fa-envelope"></i> '. $page['part']->email : '<span class="label label-danger"><i class="fa fa-envelope"></i> Email не указан</span>'; ?></p>
							<p><?php echo $page['part']->phone ? '<i class="fa fa-mobile"></i> '. $page['part']->phone : '<span class="label label-danger"><i class="fa fa-mobile"></i> Телефон не указан</span>'; ?></p>
							<p><strong>Дни участия:</strong> <?php echo str_repeat('<i class="fa fa-check-square-o"></i> ',$page['part']->days); echo str_repeat('<i class="fa fa-square-o"></i> ',$daysTotal-$page['part']->days); ?></p>
							<p><?php echo $page['part']->meal ? '<span class="label label-success"><i class="fa fa-check-square"></i> с питанием</span>' : '<span class="label label-default"><i class="fa fa-square"></i> без питания</span>'; ?></p>
							<p><?=$accom?></p>
<?php if($page['part']->transport != 6): ?>
							<p><?=$transport?></p>
<?php endif; ?>
							<p><span class="label label-info"><i class="fa fa-map-marker"></i> <?=$page['part']->city?></span> <?php echo $page['part']->region->loaded() ? $page['part']->region->name : '<span class="label label-danger"><i class="fa fa-question-circle"></i> регион не указан</span>';?> <a href="/admin/fellows/<?=$page['part']->id?>" role="button" class="btn btn-default btn-sm<?php if(!$this->pixie->party->getFellowsCountByPart($page['part'])) echo ' disabled';?>" title="Посмотреть остальных людей оттуда"><i class="fa fa-search"></i> <?php echo $this->pixie->party->getFellowsCountByPart($page['part'],false);?></a></p>
							<p><span class="label label-info"><i class="fa fa-home"></i> <?=$page['part']->church?></span></p>


<?php if(count($findabout)): ?>
							<p><strong>Узнал<?=$enda?> о конфе </strong>
<?php foreach($findabout as $find): ?>
								<span class="label label-info tags"><?=$find?></span> 
<?php endforeach; ?>
							</p>
<?php endif; ?>
							<p><strong>Stats: </strong>
								<span class="label label-primary"><i class="fa fa-user"></i> 20,7K followers</span>
								<span class="label label-danger"><i class="fa fa-eye"></i> 245 Views</span>
								<span class="label label-success"><i class="fa fa-cog"></i> 43 Forks</span>
							</p>
						</div><!--/col-->
						<div class="col-xs-12 col-sm-4 text-center">
									<ul class="list-inline text-center" title="Social Links">
									<li><a href="http://twitter.com" title="Twitter Feed" rel="nofollow"><span class="fa fa-twitter fa-lg"></span></a></li>
									<li><a href="http://www.facebook.com" title="Facebook Wall" rel="nofollow"><span class="fa fa-facebook fa-lg"></span></a></li>
									<li><a href="http://www.linkedin.com" title="LinkedIn Profile" rel="nofollow"><span class="fa fa-linkedin fa-lg"></span></a></li>
									<li><a href="http://www.skype.com" title="Skype" rel="nofollow"><span class="fa fa-skype fa-lg"></span></a></li>
									</ul>
								<img src="http://placehold.it/100x100" id="avatar" class="center-block img-circle img-thumbnail img-responsive" user-id="<?=$user_id?>"/>
								<ul class="list-inline ratings text-center" title="Ratings">
									<li><a href="#"><span class="fa fa-star fa-lg"></span></a></li>
									<li><a href="#"><span class="fa fa-star fa-lg"></span></a></li>
									<li><a href="#"><span class="fa fa-star fa-lg"></span></a></li>
									<li><a href="#"><span class="fa fa-star fa-lg"></span></a></li>
									<li><a href="#"><span class="fa fa-star fa-lg"></span></a></li>
								</ul>
						</div><!--/col-->
