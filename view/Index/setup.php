
<input type='hidden' id='hasMySQL' value='true'>
<input type='hidden' id='hasSQLite' value='true'>
<input type='hidden' id='hasPostgreSQL' value='true'>
<input type='hidden' id='hasOracle' value='true'>
<form action="setup" method="post" autocapitalize="none">
<input type="hidden" name="install" value="true">
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

	<fieldset id="adminaccount">
		<legend>创建 <strong>超级管理员账号</strong></legend>
		<p class="grouptop">
			<label for="adminlogin" class="infield">用户名</label>
			<input type="text" name="adminlogin" id="adminlogin"
				placeholder="请填写超级管理员用户名"
				value="<?php echo $adminlogin; ?>"
				autocomplete="off" autocorrect="off" autofocus required>
			
		</p>
		<p class="groupbottom">
			<label for="adminpass" class="infield">密码</label>
			<input type="password" name="adminpass" data-typetoggle="#showadminpass" id="adminpass"
				placeholder="请填写超级管理员密码"
				value="<?php echo $adminpass; ?>"
				autocomplete="off" autocorrect="off" required>
			
			<input type="checkbox" id="showadminpass" name="showadminpass">
			<label for="showadminpass"></label>
		</p>
	</fieldset>

	<fieldset id="advancedHeader">
		<legend><a id="showAdvanced">数据库与存储 <img src="<?php echo app\lib\Base::getImagePath('actions/caret.svg');  ?>" /></a></legend>
	</fieldset>

	<fieldset id="datadirField">
		<div id="datadirContent">
			<label for="directory">数据目录</label>
			<input type="text" name="directory" id="directory"
				placeholder="<?php echo $directory; ?>, 为了安全考虑，请勿设置到服务器根目录中的 public 目录下。"
				value="<?php echo $directory; ?>"
				autocomplete="off" autocorrect="off">
		</div>
	</fieldset>

	<fieldset id='databaseBackend'>

		<legend>配置数据库</legend>
		<div id="selectDbType">
		<input type="radio" name="dbtype" value="mysql" id="mysql" checked="checked"/>
		<label class="mysql" for="mysql"><?php echo $database['name']; ?></label>

		</div>
	</fieldset>

	<fieldset id='databaseField'>
		<div id="use_other_db">
			<p class="grouptop">
				<label for="dbuser" class="infield">数据库用户名</label>
				<input type="text" name="dbuser" id="dbuser"
					placeholder="数据库登录用户名"
					value="<?php echo $dbuser; ?>"
					autocomplete="off" autocorrect="off" required>
			</p>
			<p class="groupmiddle">
				<label for="dbpass" class="infield">数据库密码</label>
				<input type="password" name="dbpass" id="dbpass" data-typetoggle="#showdbpass"
					placeholder="数据库登录密码"
					value="<?php echo $dbpass; ?>"
					autocomplete="off" autocorrect="off" required>
				
				<input type="checkbox" id="showdbpass" name="showdbpass">
				<label for="showdbpass"></label>
			</p>
			<p class="groupmiddle">
				<label for="dbname" class="infield">数据库名称</label>
				<input type="text" name="dbname" id="dbname"
					placeholder="数据库名称"
					value="<?php echo $dbname; ?>"
					autocomplete="off" autocorrect="off"
					pattern="[0-9a-zA-Z$_-]+" required>
			</p>

			<p class="groupmiddle">
				<label for="dbhost" class="infield">数据库服务器</label>
				<input type="text" name="dbhost" id="dbhost"
					placeholder="请填写数据库服务器的地址"
					value="<?php echo $dbhost; ?>"
					autocomplete="off" autocorrect="off" required>
			</p>

			<p class="groupbottom">
				<label for="dbport" class="infield">数据库端口</label>
				<input type="text" name="dbport" id="dbport"
					placeholder="请填写数据库服务器的端口号"
					value="<?php echo $dbport; ?>"
					autocomplete="off" autocorrect="off" required>
			</p>

			<p class="info">
				请指定服务器的主机地址和端口号 (默认为 localhost:3306)
			</p>
		</div>
		</fieldset>


	<div class="icon-loading-dark float-spinner">&nbsp;</div>

	<div class="buttons"><input type="submit" class="primary" value="完成设置" data-finishing="设置中..."></div>

	<p class="info">
		<span class="icon-info-white"></span>
		需要帮助吗？
		<a target="_blank" rel="noreferrer" href="baidu.com">查看文档 ↗</a>
	</p>
</form>
