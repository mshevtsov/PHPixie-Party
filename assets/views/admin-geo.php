<div class="row">
<?php if(count($page['cities'])): ?>
	<div class="col-xs-6">
		<ul class="list-group">
<?php foreach($page['cities'] as $key=>$value): ?>
			<li class="list-group-item">
				<span class="badge"><?=$value?></span>
				<?=$key?>
			</li>
<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<?php if(count($page['regions'])): ?>
	<div class="col-xs-6">
		<ul class="list-group">
<?php foreach($page['regions'] as $key=>$value): ?>
			<li class="list-group-item">
				<span class="badge"><?=$value?></span>
				<?=$key?>
			</li>
<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
</div>