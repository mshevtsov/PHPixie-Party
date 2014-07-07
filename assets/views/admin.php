<!DOCTYPE html>
<html ng-app>
	<head>
		<title><?=$this->pixie->config->get('party.event.short_title')?> Панель управления<?php if(isset($page['title'])) echo ": ". $page['title']; ?></title>
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css"/>
		<link rel="stylesheet" href="/styles/admin.css"/>
	</head>
	<body>
<?php include("admin-menu.php"); ?>

	<div class="container">
		<div class="panel panel-default">
<?php if(isset($page['title'])): ?>
			<div class="panel-heading">
				<h3 class="panel-title"><?=$page['title']?></h3>
			</div>
<?php endif; ?>
			<div class="panel-body">
<?php
if(isset($page['subview']))
	include($page['subview'] .'.php');
else if(isset($page['content']))
	echo $page['content'];
?>
			</div>
		</div>
	</div>

	<script type="text/javascript" src="http://yandex.st/jquery/2.1.1/jquery.min.js"></script>
	</body>
</html>
