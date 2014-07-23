<nav class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
				<span class="sr-only">Меню</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="/"><?=$this->pixie->config->get('party.event.short_title')?></a>
		</div>

		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<ul class="nav navbar-nav">
<?php
$menu = $this->pixie->config->get('party.admin.menu');
foreach($menu as $menutitle=>$menulink): ?>
				<li><a href="/admin/<?=$menulink?>"><?=$menutitle?></a></li>
<?php endforeach; ?>
				<li><a href="/admin/logout">Выход</a></li>
			</ul>
		</div><!-- /.navbar-collapse -->

	</div><!-- /.container-fluid -->
</nav>
