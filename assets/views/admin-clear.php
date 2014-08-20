<?php /*<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8"/>
	<title>clear</title>
	<meta name="robots" CONTENT="NOINDEX,NOFOLLOW" />
</head>
<body>
<?php */
if(isset($subview))
	include($subview .'.php');
else if(isset($content) && !empty($content))
	echo $content;
/* ?>
</body>
</html>
<?php */
