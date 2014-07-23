<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?=$this->pixie->config->get('party.event.short_title')?> Панель управления<?php if(isset($page['title'])) echo ": ". $page['title']; ?></title>
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css"/>
	<link href="/styles/font-awesome.css" rel="stylesheet" />
	<link href="/scripts/binary-admin/morris/morris-0.4.3.min.css" rel="stylesheet" />
	<link href="//cdn.datatables.net/plug-ins/be7019ee387/integration/bootstrap/3/dataTables.bootstrap.css" rel="stylesheet" />
	<link href="/styles/binary-admin.css" rel="stylesheet" />
	<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
	<!-- <link rel="stylesheet" href="/styles/footable.core.min.css"/> -->
</head>
<body>
	<div id="wrapper">
		<nav class="navbar navbar-default navbar-cls-top " role="navigation" style="margin-bottom: 0">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="/" title="Главная страница сайта"><?=$this->pixie->config->get('party.event.short_title')?></a> 
			</div>
			<div style="padding: 15px 50px 5px" class="pull-right"><a href="/admin/logout" class="btn btn-danger square-btn-adjust">Выход</a> </div>
		</nav>   
		<!-- /. NAV TOP  -->
		<nav class="navbar-default navbar-side" role="navigation">
			<div class="sidebar-collapse">
				<ul class="nav" id="main-menu">
					<li>
						<a href="/admin" class="active-menu"><i class="fa fa-dashboard fa-3x"></i> Панель управления</a>
					</li>
					<li>
						<a href="/admin/participants"><i class="fa fa-users fa-3x"></i> Участники<span class="fa arrow"></span></a>
						<ul class="nav nav-second-level">
							<li>
								<a href="/admin/participants">Полный список</a>
							</li>
							<li>
								<a href="/admin/comments">Комментарии участников</a>
							</li>
							<li>
								<a href="/admin/confirm">Ожидают подтверждения</a>
							</li>
						</ul>
					</li>
					<li>
						<a  href="#"><i class="fa fa-cogs fa-3x"></i> Исправления<span class="fa arrow"></span></a>
						<ul class="nav nav-second-level">
							<li>
								<a href="/admin/duplicates">Повторы анкет участников</a>
							</li>
							<li>
								<a href="/admin/repair">Неверный формат</a>
							</li>
						</ul>
					</li>
					<li  >
						<a  href="chart.html"><i class="fa fa-bar-chart-o fa-3x"></i> Morris Charts</a>
					</li>	
					<li  >
						<a  href="table.html"><i class="fa fa-table fa-3x"></i> Table Examples</a>
					</li>
					<li  >
						<a  href="form.html"><i class="fa fa-edit fa-3x"></i> Forms </a>
					</li>				
					

					<li>
						<a href="#"><i class="fa fa-sitemap fa-3x"></i> Multi-Level Dropdown<span class="fa arrow"></span></a>
						<ul class="nav nav-second-level">
							<li>
								<a href="#">Second Level Link</a>
							</li>
							<li>
								<a href="#">Second Level Link</a>
							</li>
							<li>
								<a href="#">Second Level Link<span class="fa arrow"></span></a>
								<ul class="nav nav-third-level">
									<li>
										<a href="#">Third Level Link</a>
									</li>
									<li>
										<a href="#">Third Level Link</a>
									</li>
									<li>
										<a href="#">Third Level Link</a>
									</li>

								</ul>

							</li>
						</ul>
					</li>  
					<li>
						<a href="blank.html"><i class="fa fa-square-o fa-3x"></i> Blank Page</a>
					</li>	
				</ul>

			</div>

		</nav>  
		<!-- /. NAV SIDE  -->
		<div id="page-wrapper" >
			<div id="page-inner">
				<div class="row">
					<div class="col-md-12">
						<h2><?php echo isset($page['title']) ? $page['title'] : "Панель управления"; ?></h2>   
						<?php
						if(isset($page['subview']))
							include($page['subview'] .'.php');
						else if(isset($page['content']))
							echo $page['content'];
						?>
					</div>
				</div>
				<!-- /. ROW  -->
				<hr />
			</div>
			<!-- /. PAGE INNER  -->
		</div>
		<!-- /. PAGE WRAPPER  -->
	</div>
	<!-- /. WRAPPER  -->

	<script type="text/javascript" src="//yandex.st/jquery/2.1.1/jquery.min.js"></script>
	<script type="text/javascript" src="/scripts/bootstrap/bootstrap.min.js"></script>
	<script src="/scripts/binary-admin/jquery.metisMenu.js"></script>

	<script src="/scripts/binary-admin/dataTables/jquery.dataTables.js"></script>
	<script src="/scripts/binary-admin/dataTables/dataTables.bootstrap.js"></script>

	<script src="/scripts/binary-admin/morris/raphael-2.1.0.min.js"></script>
	<script src="/scripts/binary-admin/morris/morris.js"></script>
	<script src="/scripts/binary-admin/custom.js"></script>

	<script src="/scripts/admin.js" type="text/javascript"></script>

</body>
</html>
