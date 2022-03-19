<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form method="post" name="login" autocapitalize="none" action="/login" >
<?php if (isset($messages) && \count($messages) > 0): ?>
		<?php foreach ($messages as $err_title => $err_val): ?>
			<div class="warning">
				<strong><?php echo $err_title; ?></strong>
				<p>
                    <?php if(is_array($err_val)) : ?>
					<?php foreach ($err_val as $err): ?>
						<?php echo $err; ?><br>
					<?php endforeach; ?>
                    <?php else: ?>
                        <?php echo $err_val; ?>
                    <?php endif; ?>
				</p>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
    <?php if (!empty($redirect_url)) {
        echo '<input type="hidden" name="redirect_url" value="' . $redirect_url . '">';
    } ?>
    
    <?php if (isset($apacheauthfailed) && $_apacheauthfailed): ?>
        <div class="warning">
            身份验证失败!<br>
            <small>请联系网站管理员</small>
        </div>
    <?php endif; ?>

    <?php if (isset($internalexception) && $internalexception): ?>
        <div class="warning">
            发生内部错误<br>
            <small>请向网站管理员反馈问题并寻求帮助</small>
        </div>
    <?php endif; ?>
            
    <div id="message" class="hidden">
        <img class="float-spinner" alt=""
            src="<?php echo app\lib\Base::getImagePath('loading-dark.gif'); ?>">
        <span id="messageText"></span>
        <!-- the following div ensures that the spinner is always inside the #message div -->
        <div style="clear: both;"></div>
    </div>

<!--     <?php if (isset($licenseMessage)): ?>
        <div class="warning">
            <?php echo $licenseMessage; ?>
        </div>
    <?php endif; ?> -->

    <div class="grouptop <?php if (!empty($invalidpassword)) { echo ' shake'; } ?>">

        <?php if ($strictLoginEnforced === true) {
            $label = '登录';
        } else {
            $label = '用户名';
        } ?>

        <label for="user" class=""><?php echo $label; ?></label>
        
        <input type="text" name="user" id="user"
            value="<?php echo $loginName; ?>"
            aria-label="<?php echo $strictLoginEnforced === true ? '登录' : '用户名'; ?>"
            <?php echo $user_autofocus ? '自动登录' : ''; ?>
            placeholder="<?php echo $label; ?>"
            autocomplete="on" autocorrect="off" required>
			
	</div>

	<div class="groupbottom<?php if (!empty($invalidpassword)) { echo ' shake'; } ?>">
        <label for="password" class="">密码</label>
        
        <input type="password" name="password" id="password" value=""
            <?php echo $user_autofocus ? '' : 'autofocus'; ?>
            aria-label="密码"
            placeholder="密码"
            autocomplete="off" autocorrect="off" required>
		</div>
		
		<div class="submit-wrap">
			<?php if (!empty($invalidpassword) && !empty($canResetPassword)) { ?>
				<a id="lost-password" class="warning" href="<?php echo $resetPasswordLink; ?>">
					密码错误，要重新输入吗？
				</a>
            <?php } elseif (!empty($invalidpassword)) { ?>
                <p class="warning">
                    密码错误
                </p>
			<?php } ?>

			<?php if (!empty($csrf_error)) { ?>
                <p class="warning">
                    您上次的登录时间太长已失效，请重新登录
                </p>
			<?php } ?>
				
			<button type="button" id="submit" class="login-button">
				<span>登录</span>
				<div class="loading-spinner"><div></div><div></div><div></div><div></div></div>
			</button>
		</div>

		<?php if ($rememberLoginAllowed === true) : ?>
		<div class="remember-login-container">

			<?php
			$stayLoggedInText = '自动登录';
            if ($rememberLoginState === false) { ?>
			    <input type="checkbox" name="remember_login" value="1" id="remember_login" class="checkbox checkbox--white" aria-label="<?php echo $stayLoggedInText; ?>">
			<?php } else { ?>
			    <input type="checkbox" name="remember_login" value="1" id="remember_login" class="checkbox checkbox--white" checked="checked" aria-label="<?php echo $stayLoggedInText; ?>">
			<?php } ?>

			<label for="remember_login"><?php echo $stayLoggedInText; ?></label>
		</div>
		<?php endif; ?>

		<input type="hidden" name="timezone-offset" id="timezone-offset"/>
		<input type="hidden" name="timezone" id="timezone"/>
        <input type="hidden" name="remember" id="remember" value='0'/>

</form>

<?php if (!empty($alt_login)) { ?>
<form id="alternative-logins">
		<legend>可选登录</legend>
		<ul>
			<?php foreach ($alt_login as $login): ?>
				<?php if (isset($login['img'])) {
					?>
					<li><a href="<?php echo $login['href']; ?>" ><img src="<?php echo $login['img']; ?>"/></a></li>
				<?php
				} else {
					?>
						<li><a class="button" href="<?php echo $login['href']; ?>" ><?php echo $login['name']; ?></a></li>
					<?php
				} ?>
			<?php endforeach; ?>
		</ul>
</form>
<?php
			}