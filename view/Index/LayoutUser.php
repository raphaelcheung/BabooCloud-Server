<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" >
	<input type="hidden" name="baseinfo" id="baseinfo" value="<?php echo $baseinfo; ?>" />
	<input type="hidden" name="token" id="token" value="<?php echo $token; ?>"/>
	<head data-user="<?php echo $user_uid; ?>" data-user-displayname="<?php echo $user_displayname; ?>" data-requesttoken="testrequesttoken">
		<meta charset="utf-8">
		<title>
			<?php
				/** @var OC_Theme $theme */
				echo !empty($application) ? $application . ' - ' : '';
			?>
            <?php echo $GLOBALS['baseinfo']['title']; ?>
		</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="referrer" content="never">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0">

		<meta name="apple-itunes-app" content="app-id=1243523532452345">

		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="apple-mobile-web-app-title" content="<?php echo !empty($application) && $appid != 'files' ? $application : $GLOBALS['baseinfo']['title']; ?>">
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="theme-color" content="#041e42">
		<link rel="icon" href="<?php echo app\lib\Base::getImagePath('favicon.ico'); /* IE11+ supports png */ ?>">
		<link rel="apple-touch-icon-precomposed" href="<?php echo app\lib\Base::getImagePath('favicon-touch.png'); ?>">
		<link rel="mask-icon" sizes="any" href="<?php echo app\lib\Base::getImagePath('favicon-mask.svg'); ?>" color="#041e42">

		<?php foreach ($cssfiles as $cssfile): ?>
			<link rel="stylesheet" href="<?php echo $cssfile; ?>">
		<?php endforeach; ?>

		<?php foreach ($jsfiles as $jsfile): ?>
			<script src="<?php echo $jsfile; ?>"></script>
		<?php endforeach; ?>

	</head>
	<body id="<?php echo $bodyid;?>" >

		<?php include('LayoutNoscript.php'); ?>
		<div id="notification-container">
			<div id="notification"></div>
		</div>
		<header role="banner">
			<div id="header">
				<a href="<?php echo app\lib\Base::getRoute('/index'); ?>" id="owncloud" tabindex="1">
					<h1 class="logo-icon">
						<?php echo $GLOBALS['baseinfo']['title']; ?>
					</h1>
				</a>
				<a href="#" class="header-appname-container menutoggle" tabindex="2">
					<button class="burger">
						菜单
					</button>
					<h1 class="header-appname">
						<?php echo !empty($application) ? $application : '应用'; ?>
					</h1>
				</a>
				<div id="logo-claim" style="display:none;">商标声明</div>
				<div id="settings">
					<div id="expand" tabindex="6" role="link" class="menutoggle">
						<div class="avatardiv" style="display: none">

						</div>

						<span id="expandDisplayName"><?php  echo \trim($user_displayname) != '' ? $user_displayname : $user_uid; ?></span>
					</div>
					<div id="expanddiv">
						<ul>
							<?php foreach ($settingsnavigation as $entry):?>
							<li>
								<a href="<?php echo $entry['href']; ?>" class="active">
									<img alt="" src="<?php echo $entry['icon']; ?>"/>
									<?php echo $entry['name']; ?>
								</a>
							</li>
							<?php endforeach; ?>
							<li>
								<a id="logout" >
									<img alt="" src="<?php echo app\lib\Base::getImagePath('actions/logout.svg'); ?>"/>
									注销
								</a>
							</li>
						</ul>
					</div>
				</div>

				<form class="searchbox" action="#" method="post" role="search" novalidate>
					<label for="searchbox" class="hidden-visually">
						搜索
					</label>
					<input id="searchbox" type="search" name="query"
						value="" required
						autocomplete="off" tabindex="5"/>
				</form>
			</div>
		</header>
		

		<div role="navigation">
			<div id="navigation">
				<div id="apps">
					<ul>
						<?php foreach ($navigation as $entry): ?>
							<li data-id="<?php echo $entry['id']; ?>">
								<a href="<?php echo $entry['href']; ?>" tabindex="3" class="active" >
									<img class="app-icon" alt="" src="<?php echo $entry['icon']; ?>">
									<div class="icon-loading-dark" style="display:none;"></div>
									<span>
										<?php echo $entry['name']; ?>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>

		<div id="content-wrapper">
			<div id="content" class="app-<?php echo $appid; ?>" role="main">
				<?php echo $content; ?>
			</div>
		</div>
	</body>
</html>
