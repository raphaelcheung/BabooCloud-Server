<div id="app-navigation">
    <ul class="with-icon">
        <li data-id="files" class="nav-files active">
            <a href="#" class="nav-icon-files svg">
                全部文件
            </a>
        </li>
        <li data-id="favorites" class="nav-favorites active">
            <a href="#" class="nav-icon-favorites svg">
                收藏
            </a>
        </li>
    </ul>
    <div id="app-settings">
        <div id="app-settings-header">
            <button class="settings-button" data-apps-slide-toggle="#app-settings-content">
                设置
            </button>
        </div>
        <div id="app-settings-content">
            <div id="files-setting-showhidden">
                <input class="checkbox" id="showhiddenfilesToggle" checked="checked" type="checkbox"/>
                <label for="showhiddenfilesToggle">显示隐藏文件</label>
            </div>
            <label for="webdavurl">WebDAV</label>
            <input id="webdavurl" type="text" readonly="readonly" value="asdfasdfs" />
            <em></em>
        </div>
    </div>
</div>

<div id="app-content">
    <div id="app-content-files" class="hidden viewcontainer">
        <div id="controls">
            <div class="actions creatable ">
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
            <input type="hidden" name="permissions" value="" id="permissions" />
            <input type="hidden" id="free_space" value="<?php echo isset($freeSpace) ? $freeSpace : ''; ?>" />

            <input type="hidden" class="max_human_file_size" value="(max <?php echo isset($uploadMaxHumanFilesize) ? $uploadMaxHumanFilesize : ''; ?>)" />
        </div>

        <div id="emptycontent" class="hidden">
            <div class="icon-folder"></div>
            <h2>空空如也</h2>
            <p class="uploadmessage hidden">请上传一些内容</p>
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
                            <input type="checkbox" id="select_all_files" class="select-all checkbox" />
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
                        <span class="selectedActions">
                            <a href="" class="delete-selected">
                                <span class="icon icon-delete"></span>
                                <span>删除</span>
                            </a>
                        </span>
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

    </div>

    <div id="searchresults" class="hidden"></div>
</div><!-- closing app-content -->

<!-- config hints for javascript -->
<input type="hidden" name="filesApp" id="filesApp" value="1" />
<input type="hidden" name="usedSpacePercent" id="usedSpacePercent" value="<?php echo $usedSpacePercent; ?>" />
<input type="hidden" name="owner" id="owner" value="<?php echo $owner; ?>" />
<input type="hidden" name="ownerDisplayName" id="ownerDisplayName" value="<?php echo $ownerDisplayName; ?>" />
<input type="hidden" name="fileNotFound" id="fileNotFound" value="<?php echo $fileNotFound; ?>" />

<?php if (!$isPublic) : ?>
    <input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php echo $mailNotificationEnabled; ?>" />
    <input type="hidden" name="mailPublicNotificationEnabled" id="mailPublicNotificationEnabled" value="<?php echo $mailPublicNotificationEnabled; ?>" />
    <input type="hidden" name="socialShareEnabled" id="socialShareEnabled" value="<?php echo $socialShareEnabled; ?>" />
    <input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php echo $allowShareWithLink; ?>" />
    <input type="hidden" name="defaultFileSorting" id="defaultFileSorting" value="<?php echo $defaultFileSorting; ?>" />
    <input type="hidden" name="defaultFileSortingDirection" id="defaultFileSortingDirection" value="<?php echo $defaultFileSortingDirection; ?>" />
    <input type="hidden" name="showHiddenFiles" id="showHiddenFiles" value="<?php echo $showHiddenFiles; ?>" />
<?php endif;
