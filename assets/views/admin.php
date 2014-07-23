<!DOCTYPE html>
<html ng-app>
	<head>
		<title><?=$this->pixie->config->get('party.event.short_title')?> Панель управления<?php if(isset($page['title'])) echo ": ". $page['title']; ?></title>
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css"/>
		<!-- <link rel="stylesheet" href="/styles/admin.css"/> -->
		<link rel="stylesheet" href="/styles/footable.core.min.css"/>
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
			
<?php
if(isset($page['subview']))
	include($page['subview'] .'.php');
else if(isset($page['content']))
	echo "<div class=\"panel-body\">\n{$page['content']}\n</div>\n";
?>

		</div>
	</div>

	<script type="text/javascript" src="//yandex.st/jquery/2.1.1/jquery.min.js"></script>
	<script src="/scripts/footable/footable.min.js" type="text/javascript"></script>
	<script src="/scripts/footable/footable.sort.min.js" type="text/javascript"></script>
	<script src="/scripts/footable/footable.filter.min.js" type="text/javascript"></script>
	<script src="/scripts/footable/footable.paginate.min.js" type="text/javascript"></script>

	<script src="/scripts/admin.js" type="text/javascript"></script>

	</body>
</html>
