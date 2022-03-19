<div id="controls">
		<div class="actions creatable hidden">
			<div id="uploadprogresswrapper">
				<div id="uploadprogressbar">
					<em class="label outer" style="display:none"><span class="desktop">上传中...</span><span class="mobile">...</span></em>
				</div>

				<input type="button" class="stop icon-close" style="display:none" value="" />
			</div>
		</div>
		<div id="file_action_panel"></div>

		<div class="notCreatable notPublic hidden">
			你没有当前目录的上传权限
		</div>
	<?php /* Note: the template attributes are here only for the public page. These are normally loaded
			 through ajax instead (updateStorageStatistics).
	*/ ?>
	<input type="hidden" name="permissions" value="" id="permissions">
	<input type="hidden" id="free_space" value="<?php echo isset($freeSpace) ? $freeSpace : '' ?>">

	<input type="hidden" class="max_human_file_size"
		   value="(max <?php echo isset($uploadMaxHumanFilesize) ? $uploadMaxHumanFilesize : ''; ?>)">
</div>

<div id="emptycontent" class="hidden">
	<div class="icon-folder"></div>
	<h2>空空如也</h2>
	<p class="uploadmessage hidden">请上传一些内容或同步设备！</p>
	<p class="nouploadmessage hidden">你没有在此目录上传的权限！</p>
</div>

<div class="nofilterresults emptycontent hidden">
	<div class="icon-search"></div>
	<h2>空空如也</h2>
	<p></p>
</div>

<table id="filestable" data-allow-public-upload="yes" data-preview-x="32" data-preview-y="32">
	<thead>
		<tr>
			<th id='headerName' class="hidden column-name">
				<div id="headerName-container">
					<input type="checkbox" id="select_all_files" class="select-all checkbox"/>
					<label for="select_all_files">
						<span class="hidden-visually">全选</span>
					</label>
					<a class="name sort columntitle" data-sort="name"><span>名称</span><span class="sort-indicator"></span></a>
					<span id="selectedActionsList" class="selectedActions">
						<a href="" class="download">
							<span class="icon icon-download"></span>
							<span>下载</span>
						</a>
						<a href="" class="download mobile button">
							<span class="icon icon-download "></span>
						</a>
						<a href="" class="delete-selected mobile button">
							<span class="icon icon-delete"></span>
						</a>
					</span>
				</div>
			</th>
			<th id="headerSize" class="hidden column-size">
				<a class="size sort columntitle" data-sort="size"><span>大小</span><span class="sort-indicator"></span></a>
			</th>
			<th id="headerDate" class="hidden column-mtime">
				<a id="modified" class="columntitle" data-sort="mtime"><span>修改时间</span><span class="sort-indicator"></span></a>
					<span class="selectedActions"><a href="" class="delete-selected">
						<span class="icon icon-delete"></span>
						<span>删除</span>
					</a></span>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
<input type="hidden" name="dir" id="dir" value="" />
<div class="hiddenuploadfield">
	<input type="file" id="file_upload_start" class="hiddenuploadfield" name="files[]" />
</div>
<div id="editor"></div><!-- FIXME Do not use this div in your app! It is deprecated and will be removed in the future! -->
<div id="uploadsize-message" title="上传文件太大">
	<p>
	您要上传的文件大小超过了服务器的限制
	</p>
</div>
