<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" >
	<head data-requesttoken="testrequesttoken">
		<meta charset="utf-8">
		<title>
		<?php echo $GLOBALS['baseinfo']['title']; ?>
		</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="referrer" content="never">
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0">

		<meta name="apple-itunes-app" content="app-id=234532452345">} ?>

		<meta name="theme-color" content="#041e42">
		<link rel="icon" href="<?php echo app\lib\Base::getImagePath('favicon.ico'); ?>">
		<link rel="apple-touch-icon-precomposed" href="<?php echo app\lib\Base::getImagePath('favicon-touch.png'); ?>">
		<link rel="mask-icon" sizes="any" href="<?php echo app\lib\Base::getImagePath('favicon-mask.svg'); ?>" color="#1B223D">

		<?php foreach ($cssfiles as $cssfile): ?>
			<link rel="stylesheet" href="<?php echo $cssfile; ?>">
		<?php endforeach; ?>
		
		<?php foreach ($jsfiles as $jsfile): ?>
			<script src="<?php echo $jsfile; ?>"></script>
		<?php endforeach; ?>

	</head>
	<body id="<?php echo $_bodyid; ?>">
		<div class="wrapper">
			<div class="v-align">
				<?php if ($_bodyid === 'body-login'): ?>
					<header role="banner">
						<div id="header">
							<div class="logo">
								<h1 class="hidden-visually">
									<?php echo $GLOBALS['baseinfo']['title']; ?>
								</h1>
							</div>
							<div id="logo-claim" style="display:none;">LogoClaim</div>
						</div>
					</header>
				<?php endif; ?>
				<?php echo $_content; ?>
				<div class="push"></div><!-- for sticky footer -->
			</div>
		</div>
		<footer role="contentinfo">
			<p class="info">
				<?php echo $_footer; ?>
			</p>
		</footer>
	</body>
</html>
