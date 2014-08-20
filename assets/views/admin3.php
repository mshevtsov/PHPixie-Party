<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php if(isset($page['title'])) echo strip_tags($page['title']); ?> - <?=$this->pixie->config->get('party.event.short_title')?></title>
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css"/>
	<link href="/styles/font-awesome.css" rel="stylesheet" />
	<link href="/styles/binary-admin.css" rel="stylesheet" />
	<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>

<?php
if(isset($page['subview']))
	include($page['subview'] .'.php');
else if(isset($page['content']))
	echo $page['content'];
?>


<script type="text/javascript" src="//yandex.st/jquery/2.1.1/jquery.min.js"></script>
<!-- <script type="text/javascript" src="/scripts/bootstrap/bootstrap.min.js"></script> -->
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.2.0/js/bootstrap.min.js"></script>
<script src="/scripts/binary-admin/custom.js"></script>

<?php if(isset($page['script'])): ?>
<script>
<?=$page['script']?>
</script>
<?php endif; ?>
</body>
</html>
