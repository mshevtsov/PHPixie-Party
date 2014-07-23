<?php
$formatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
$formatter->setPattern('d MMMM');
?>
<div class="row">
	<div class="col-md-12 col-sm-12 col-xs-12">

		<div class="chat-panel panel panel-default chat-boder chat-panel-head" >
			<div class="panel-heading">
				<i class="fa fa-comments fa-fw"></i>
				Комментарии участников
			</div>

			<div class="panel-body">
				<ul class="chat-box">
<?php foreach ($page['participants'] as $part): ?>
					<li class="left clearfix">
						<span class="chat-img pull-left">
							<img src="http://placehold.it/50" alt="" class="img-circle" />
						</span>
						<div class="chat-body">
							<strong><?php if($part->website) echo "<a href=\"{$part->website}\" target=\"_blank\"><i class=\"fa fa-vk\"></i></a> ";?><?=$part->firstname?> <?=$part->lastname?></strong>
							<small class="text-muted pull-right"><i class="fa fa-clock-o fa-fw"></i><?php echo $formatter->format(new \DateTime($part->created)) ? $formatter->format(new \DateTime($part->created)) : "дата неизвестна"; ?></small>
							<p><?=$part->comment?></p>
						</div>
					</li>
<!-- 
					<li class="right clearfix">
						<span class="chat-img pull-right">
							<img src="http://placehold.it/50" alt="" class="img-circle" />
						</span>
						<div class="chat-body clearfix">
							<small class="text-muted"><i class="fa fa-clock-o fa-fw"></i><?php echo $formatter->format(new \DateTime($part->created)) ? $formatter->format(new \DateTime($part->created)) : "дата неизвестна"; ?></small>
							<strong class="pull-right"><?=$part->firstname?> <?=$part->lastname?></strong>
							<p><?=$part->comment?></p>
						</div>
					</li>
 -->
<?php endforeach; ?>
				</ul>
			</div>

		</div>

	</div>
</div>
