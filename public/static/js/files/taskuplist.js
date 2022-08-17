(function() {
	var TaskUpList = function($el, options) {
		this.initialize($el, options);
	};

	TaskUpList.prototype = {
        SORT_INDICATOR_ASC_CLASS: 'icon-triangle-n',
        SORT_INDICATOR_DESC_CLASS: 'icon-triangle-s',

        id: 'uplist',
        appName: '上传任务',
        isEmpty: true,
        useUndo: false,

        _uploader: null,

        $tasks: [],

		$el: null,

        /**
         * Files table
         */
        $table: null,

        /**
         * List of rows (table tbody)
         */
        $upTaskList: null,

        /**
         * @type OCA.Files.BreadCrumb
         */
        breadcrumb: null,

        /**
         * Whether the file list was initialized already.
         * @type boolean
         */
        initialized: false,

        /**
         * Last clicked row
         */
        $currentRow: null,

        $indexLastPage: -1,

		pageSize: function() {
			return Math.ceil(this.$container.height() / 50);
		},

		taskActions: null,

		initialize: function($el, options) {
            console.log('taskup: inited');
			var self = this;
            this._uploader = OC.UploaderInstance;
            console.log(this._uploader);
			options = options || {};
			if (this.initialized) {
				return;
			}

            

			this.$el = $el;
			if (options.id) {
				this.id = options.id;
			}

			this.$container = options.scrollContainer || $(window);
			this.$table = $el.find('table:first');
			this.$upTaskList = $el.find('#upTaskList');
            //console.log('3：' + this.$upTaskList);

			/* if (_.isUndefined(options.detailsViewEnabled) || options.detailsViewEnabled) {
                this._detailsView = new OCA.Files.DetailsView();
                this._detailsView.$el.insertBefore(this.$el);
                this._detailsView.$el.addClass('disappear');
            } */

			//this._initFileActions(options.fileActions);

			this.tasks = [];
			this._onResize = _.debounce(_.bind(this._onResize, this), 100);
			$('#app-content').on('appresized', this._onResize);
			$(window).resize(this._onResize);

			this.$el.on('show', this._onResize);

            this.$el.on('urlChanged', _.bind(this._onUrlChanged, this));

            this.$container.on('scroll.' + this.$el.attr('id'), _.bind(this._onScroll, this));

            if (options.scrollTo) {
                this.$upTaskList.one('updated', function() {
                    self.scrollTo(options.scrollTo, options.detailTabId);
                });
            }

            OC.Plugins.attach('OCA.Files.TaskUpList', this);

            this._uploader.on('statuschange', function(upload, status, statustext){
                console.log('statuschange: ' + upload.getFile().name + ', ' + status + '，' + statustext);
                var taskItem = self.$upTaskList.find('#taskRow_' + upload.getId());
                
                //更新进度状态描述
                self._refreshStatus(taskItem, status, statustext);
                //更新操作按钮的状态
                self.updateAction(taskItem, status);
            });

            this._uploader.on('invalid', function(upload){
                console.log('invalid');
                
                var taskItem = self.$upTaskList.find('#taskRow_' + upload.getId());
                
                //更新进度状态描述

                self._refreshStatus(taskItem, upload.getStatus(), upload.getStatusText());
                self._refreshProgress(taskItem, upload.getStatus());


                //更新操作按钮的状态
                self.updateAction(taskItem, upload.getStatus());
            });

            this._uploader.on('add', function(upload){
                self._refreshTotalProgress();
            });

            this._uploader.on('error', function(upload){
                console.log('error');
                console.log(upload.getStatusText());
                var taskItem = self.$upTaskList.find('#taskRow_' + upload.getId());
                
                //更新进度状态描述

                self._refreshStatus(taskItem, upload.getStatus(), upload.getStatusText());

                self._refreshProgress(taskItem, upload.getStatus());


                //更新操作按钮的状态
                self.updateAction(taskItem, upload.getStatus());
            });

            this._uploader.on('uploadProgress', function(upload, percentage){
                var taskItem = self.$upTaskList.find('#taskRow_' + upload.getId());


                var desItem = taskItem.find('.taskdesc');
                desItem.text('文件上传中...' + percentage + '%');

                self._refreshProgress(taskItem, upload.getStatus(), parseInt(percentage));

                desItem.toggleClass('error', false);

            });

            this._uploader.on('md5Progress', function(upload, percentage){
                var taskItem = self.$upTaskList.find('#taskRow_' + upload.getId());

                var desItem = taskItem.find('.taskdesc');

                desItem.text('正在读取文件...' + percentage + '%');

                self._refreshProgress(taskItem, upload.getStatus(), parseInt(percentage));


                desItem.toggleClass('error', false);
            });

            this._uploader.on('success', function(upload){
                console.log('success');
                var taskItem = self.$upTaskList.find('#taskRow_' + upload.getId());

                taskItem.find('.taskdesc').text('已完成');
                self._refreshProgress(taskItem, upload.getStatus(), 100);

                self._hidePlayPause(taskItem);

            });

            this._uploader.on('remove', function(upload){
                console.log('remove');
                var taskItem = self.$upTaskList.find('#taskRow_' + upload.getId());
                taskItem.remove();
                self._refreshTotalProgress();
            });

            this._uploader.on('start', function(){
                self._refreshTotalProgress();
            });

            this._uploader.on('complete', function(upload){
                self._refreshTotalProgress();
            });
            
            $('#btnStartAll').click(function(){
                console.log('_onClickStartAll');
                var files = self._uploader.getFiles('inited', 'error', 'interrupt');
                console.log(files);
               
                _.each(files, function(file){
                    self._uploader.startUploadByFile(file);
                });
            });

            $('#btnPauseAll').click(function(){
                console.log(self._uploader);
                var files = self._uploader.getFiles('queued', 'progress');
                _.each(files, function(file){
                    self._uploader.pauseUploadByFile(file);
                });
            });

            $('#btnCancelAll').click(function(){
                console.log(self._uploader);

                var files = self._uploader.getFiles('inited', 'queued', 'progress', 'complete', 'error', 'interrupt', 'invalid');
                _.each(files, function(file){
                    self._uploader.cancelUploadByFile(file);
                });
            });

            this._refreshTotalProgress();
		},

        _refreshProgress: function(taskItem, status, val){
            switch(status){
                case 'inited':
                case 'queued':
                case 'progress':
                    this._showProgress(taskItem, val);
                    break;

                case 'complete':

                    this._showProgress(taskItem, 100);
                    break;

                case 'interrupt':
                    this._showProgress(taskItem);
                    break;

                case 'invalid':
                case 'error':
                case 'cancelled':
                    this._disableProgress(taskItem);
                    break;
            }
        },

        _refreshTotalProgress: function() {
            //更新总进度条
            var stats = this._uploader.getStats();
            console.log(stats);
            var total = stats.queueNum + stats.progressNum + stats.successNum;

            if (total <= 0){
                this._showTotalProgress();
            }else{
                var val = (stats.successNum * 100 / total).toFixed(2);
                console.log(val);
                this._showTotalProgress(val);
            }
        },

        _showProgress: function(taskItem, val){
            console.log('_showProgress');
            var progress = taskItem.find('#taskprogress');
            if (val != null){
                progress.progressbar({value: val});
            }
            progress.toggleClass('disabled', false);
        },

        _showTotalProgress: function(val){
            var progress = $('#totalprogress');
            var label = $('#totalprogress .label');
        
            if (val != null){
                val = parseFloat(val);

                progress.progressbar({value: val});
                progress.toggleClass('disabled', false);

                label.text(val + ' %');
            }else{
                progress.progressbar({value: false});
                progress.toggleClass('disabled', true);
                //progress.text('0.0 %');
                label.text( '0.0 %');

            }
        },

        _disableProgress: function(taskItem){
            console.log('_disableProgress');

            var progress = taskItem.find('#taskprogress');
            progress.progressbar({value: false});
            progress.toggleClass('disabled', true);
        },
        _showPlay: function(taskItem){
            var item = taskItem.find('#actionPlay');
            item.removeClass('action-pause');
            item.addClass('action-play');
            item.toggleClass('hidden', false);

            item = taskItem.find('#actionPlay span');
            item.removeClass('icon-pause');
            item.addClass('icon-play');
        },

        _showPause: function(taskItem){
            var item = taskItem.find('#actionPlay');
            item.removeClass('action-play');
            item.addClass('action-pause');

            item.toggleClass('hidden', false);


            item = taskItem.find('#actionPlay span');
            item.removeClass('icon-play');
            item.addClass('icon-pause');
        },

        _hidePlayPause: function(taskItem){
            var item = taskItem.find('#actionPlay');
            item.toggleClass('hidden', true);
        },

        updateAction: function(taskItem, status){
            switch(status){
                case 'error':
                case 'interrupt':
                case 'inited':
                    this._showPlay(taskItem);
                    break;
                case 'queued':
                case 'progress':
                    this._showPause(taskItem);
                    break;
                case 'complete':
                    this._hidePlayPause(taskItem);
                    break;
                case 'invalid':
                    this._hidePlayPause(taskItem);
                    break;
            }
        },

        _refreshStatus: function(taskItem, status, statustext){

            var descItem = taskItem.find('.taskdesc');
            switch(status){
                case 'inited':
                    descItem.text('已添加');
                    descItem.toggleClass('error', false);
                    break;

                case 'queued':
                    descItem.text('排队等待中...');
                    descItem.toggleClass('error', false);
                    break;

                case 'progress':
                    descItem.text('正在上传...');
                    descItem.toggleClass('error', false);
                    break;

                case 'complete':
                    descItem.text('已完成');
                    descItem.toggleClass('error', false);
                    break;

                case 'interrupt':
                    descItem.text('已暂停');
                    descItem.toggleClass('error', false);
                    break;

                case 'invalid':
                case 'error':
                    {
                        switch(statustext){
                            case 'http':
                                descItem.text('请求失败，请稍候重试...');
                                break;
                            case 'abort':
                                descItem.text('已取消');
                                break;
                            case 'server':
                                descItem.text('服务器异常，请稍候重试');
                                break;
                            case 'timeout':
                                descItem.text('连接超时，请稍候重试');
                                break;
                            default: 
                                descItem.text(statustext);
                                break;
                        }
                        descItem.toggleClass('error', true);
                    }

                    break;
            }
        },

        destroy: function() {
            console.log('taskdown: destroy');
            OC.Plugins.detach('OCA.Files.TaskUpList', this);
            $('#app-content').off('appresized', this._onResize);
            // HACK: this will make reload work when reused
            this.$el.find('#dir').val('');
            // remove summary
            this.$el.find('tfoot tr.summary').remove();
            this.$upTaskList.empty();
            // remove events attached to the $el
            this.$el.off('show', this._onResize);
            this.$el.off('urlChanged');
            // remove events attached to the $container
            this.$container.off('scroll.' + this.$el.attr('id'));
        },

		_onResize: function() {
			var containerWidth = this.$el.width();
			var actionsWidth = 0;
			$.each(this.$el.find('#controls .actions'), function(index, action) {
				actionsWidth += $(action).outerWidth();
			});

			containerWidth -= $('#app-navigation-toggle').width();

			this.$table.find('>thead').width($('#app-content').width() - OC.Util.getScrollBarWidth());
		},

		_onScroll: function(e) {
            if (this.$container.scrollTop() + this.$container.height() > this.$el.height() - 300) {
                this._nextPage(true);
            }
        },

		setPageTitle: function(title){
            if (title) {
                title += ' - ';
            } else {
                title = '';
            }
            title += this.appName;
            
            window.document.title = title + ' - ' + cloud_base.title;

            return true;
        },

		_nextPage: function(animate) {
            var index = this.$upTaskList.children().length,
                count = this.pageSize(),
                tr,
                taskData,
                newTrs = [];
                //isAllSelected = this.isAllSelected(),

            if (index >= this.tasks.length) {
                return false;
            }

            while (count > 0 && index < this.tasks.length) {
                taskData = this.tasks[index];
                tr = this._renderRow(taskData, {updateSummary: false, silent: true, hidden: false});
                this.$upTaskList.append(tr);

                if (animate) {
                    tr.addClass('appear transparent');
                }
                newTrs.push(tr);
                index++;
            }

            if (index >= this.tasks.length){
                this.loadNextPage();
            }

            // trigger event for newly added rows
            /* if (newTrs.length > 0) {
                this.$upTaskList.trigger($.Event('fileActionsReady', {fileList: this, $files: newTrs}));
            } */

            if (animate) {
                // defer, for animation
                window.setTimeout(function() {
                    for (var i = 0; i < newTrs.length; i++ ) {
                        newTrs[i].removeClass('transparent');
                    }
                }, 0);
            }

            return newTrs;
        },

		_getIconUrl: function(fileInfo) {
            var mimeType = fileInfo.mimetype || 'application/octet-stream';
            if (mimeType === 'httpd/unix-directory') {
                // use default folder icon
                if (fileInfo.mountType === 'shared' || fileInfo.mountType === 'shared-root') {
                    return OC.MimeType.getIconUrl('dir-shared');
                } else if (fileInfo.mountType === 'external-root') {
                    return OC.MimeType.getIconUrl('dir-external');
                }
                return OC.MimeType.getIconUrl('dir');
            }
            return OC.MimeType.getIconUrl(mimeType);
        },

		_createRow: function(taskData, options) {
			var td, basename, extension, 
                icon = taskData.icon || this._getIconUrl(taskData),
				taskName = taskData.title,
                //mime = taskData.mimetype,
                taskTarget = taskData.target,
                taskId = taskData.id,
                self = this;


            options = options || {};

            //containing tr
            var tr = $('<tr id="taskRow_' + taskId + '"></tr>').attr({
                "data-id" : taskId,
                "data-name": taskName,
                //"data-mime": mime,
                "data-status": 'stop',
                "data-total": '5.06GB',
                "data-done": '1.05GB',

                //"data-etag": fileData.etag,
                //"data-permissions": fileData.permissions || this.getDirectoryPermissions()
            });


            td = $('<td class="taskname"></td>');
            td.append('<div class="thumbdiv"><div class="thumbnail" style="background-image:url(' + icon + '); "></div></div>');

            taskName = taskData.task_display_text || taskName; 
            basename = taskName.substr(0, taskName.lastIndexOf('.'));
            extension = taskName.substr(taskName.lastIndexOf('.'));

            //文件名
            var nameSpan = $('<span></span>').addClass('nametext');
            var innernameSpan = $('<span></span>').addClass('innernametext').text(taskName);
            nameSpan.append(innernameSpan);

            //目标路径
            var innerPathSpan = $('<span></span>').addClass('innerpathtext').text(taskTarget);

            nameSpan.append(innerPathSpan);
            td.append(nameSpan);
            tr.append(td);
            
            //添加进度条
            td = $('<td></td>').attr({ "class": "taskprogress" });
            var progressDiv = $('<div id="taskprogress"><em class="label outer" style="display:none"><span class="desktop">准备上传中...</span><span class="mobile">...</span></em></div>');
            td.append(progressDiv);

            //进度描述
            var desDiv = $('<div class="taskdesc"></div>');
            td.append(desDiv);

            progressDiv.progressbar({value: 0});
            /*progressDiv.find('.ui-progressbar-value').
                html('<em class="label inner"><span class="desktop">'
                    + '上传中...'
                    + '</span><span class="mobile">'
                    + '...'
                    + '</span></em>');*/

            progressDiv.tipsy({gravity:'n', fade:true, live:true});
            progressDiv.fadeIn();

            tr.append(td);

            //添加操作按钮
            td = $('<td></td>').attr({ "class": "taskaction" });
            var actionPlay = $('<a id="actionPlay" title="" class="action action-play permanent" href="javascript:void(0);"><span class="icon icon-play"></span></a>');
            var actionCancel = $('<a id="actionCancel" title="" class="action action-cancel permanent" href="javascript:void(0);"><span class="icon icon-close"></span></a>');
            var actionFolder = $('<a id="actionFolder" title="" class="action action-folder permanent" href="javascript:void(0);"><span class="icon icon-folder"></span></a>');

            td.append(actionPlay);
            td.append(actionCancel);
            td.append(actionFolder);
            tr.append(td);

            actionPlay.on('click', function(){
                if (actionPlay.hasClass('action-play')){
                    self._uploader.startUpload(taskId);
                } else {
                    self._uploader.pauseUpload(taskId);
                }
            });

            actionCancel.on('click', function(){
                self._uploader.cancelUpload(taskId);
            });

            console.log(taskData.status);



            this._refreshProgress(tr, taskData.status, 0);
            this._refreshStatus(tr, taskData.status, taskData.statustext)
            this.updateAction(tr, taskData.status);
            return tr;
		},

        add: function(taskData, options) {
            var index = -1;
            var $tr;
            var $rows;
            var $insertionPoint;

            options = _.extend({animate: true}, options || {});

            $rows = this.$upTaskList.children();
            index = this._findInsertionIndex(taskData);

            if (index > this.tasks.length) {
                index = this.tasks.length;
            }else {
                $insertionPoint = $rows.eq(index);
            }

            if ($insertionPoint.length) {
                $tr = this._renderRow(taskData, options);
                $insertionPoint.before($tr);
            }else{
                if (index === $rows.length){
                    $tr = this._renderRow(taskData, options);
                    this.$upTaskList.append($tr);
                }
            }

            this.isEmpty = false;
            this.tasks.splice(index, 0, taskData);

            if ($tr && options.animate){
                $tr.addClass('appear transparent');
                window.setTimeout(function(){
                    $tr.removeClass('transparent');
                });
            }

            if (options.scrollTo){
                this.scrollTo(taskData.name);
            }

            return $tr;
        },

        _renderRow: function(taskData, options){
            options = options || {};


            var tr = this._createRow(taskData, options);
            //var filenameTd = tr.find('td.filename');

            
            return tr;
        },

        _setCurrentRow: function($rowEl){
            this.$currentRow = $rowEl;
        },

        loadNextPage: function(){
            //this.showMask();
            var self = this;

            var index = this.$indexLastPage + 1;

            var datas = this._uploader.getUploads(index * 20, (index + 1) * 20 - 1);
            
            //console.log('loadNextPage: ' + index);
            //console.log(datas);

            if (datas.length > 0){
                self.$indexLastPage = index;

                if (index == 0){
                    self.setTasks(datas);
                }else{
                    self.addTasks(datas);
                }
            }
        },

        reload: function(){
            this.$indexLastPage = -1;
            this.loadNextPage();
        },

        remove: function(id, options){
            options = options || {};
            this.tasks.splice()
            var el = this.findTaskEl(id);
            var index = el.index();

            this.tasks.splice(index, 1);
            el.remove();
            this.isEmpty = !this.tasks.length;
            
            var lastIndex = this.$upTaskList.children().length;
            if (lastIndex < this.tasks.length && lastIndex < this.pageSize()){
                this._nextPage(true);
            }

            return el;
        },

        updateEmptyContent: function(){
            
        },

        showMask: function(){
            var $mask = this.$el.find('.mask');
            if ($mask.exists()){
                return;
            }

            this.$table.addClass('hidden');
            this.$el.find('#emptycontent').addClass('hidden');

            $mask = $('<div class="mask transparent"></div>');

            $mask.css('background-image', 'url('+ OC.imagePath('static/img/loading.gif') + ')');
            $mask.css('background-repeat', 'no-repeat');
            this.$el.append($mask);

            $mask.removeClass('transparent');
        },

        hideMask: function(){
            this.$el.find('.mask').remove();
            this.$table.removeClass('hidden');
        },

        scrollTo: function(task, detailTabId){
            if (!_.isArray(task)) {
                task = [task];
            }
            if (!_.isUndefined(detailTabId)) {
                var taskid = task[task.length - 1];
                //Double check if the area that you are scrolling is beyond the page limit?
                var pageSize = this.pageSize();
                var index = _.findIndex(this.tasks, function (obj) {
                    return obj.id === taskid;
                });
                if (index >= pageSize) {
                    var numberOfMorePagesToScroll = Math.floor(index / pageSize);
                    while (numberOfMorePagesToScroll > 0) {
                        this._nextPage();
                        numberOfMorePagesToScroll--;
                    }
                }
            }
        },

        _scrollToRow: function($taskRow, callback) {
            var currentOffset = this.$container.scrollTop();
            var additionalOffset = 0;
            var $controls = this.$el.find('#controls');
            if ($controls.exists()) {
                additionalOffset += $controls.height() + $controls.offset().top;
            }

            // Animation
            var $scrollContainer = this.$container;
            if ($scrollContainer[0] === window) {
                // need to use "body" to animate scrolling
                // when the scroll container is the window
                $scrollContainer = $('body');
            }
            $scrollContainer.animate({
                // Scrolling to the top of the new element
                scrollTop: currentOffset + $taskRow.offset().top - $taskRow.height() * 2 - additionalOffset
            }, {
                duration: 500,
                complete: callback
            });
        },

        setTasks: function(taskArray){
            var self = this;
            
            this.tasks = taskArray;
            this.$upTaskList.empty();

            this.isEmpty = this.tasks.length === 0;
            this._nextPage();
            this.updateEmptyContent();

            $(window).scrollTop(0);
            this.$upTaskList.trigger(jQuery.Event('updated'));

            _.defer(function(){
                self.$el.closest('#app-content').trigger(jQuery.Event('apprendered'));
            });
        },

        addTasks: function(taskArray){
            var self = this;
            
            this.tasks = this.tasks.concat(taskArray);

            this.isEmpty = this.tasks.length === 0;
            this._nextPage();
            this.updateEmptyContent();
        },

        _onUrlChanged: function(e){
            //console.log('_onUrlChanged');
            //console.log(e);
            if (e && e.view == 'uptasks'){
                this.reload();
            }
        },
	};

    OCA.Files.TaskUpList = TaskUpList;
})();

$(document).ready(function() {
    $(window).bind('beforeunload', function () {
        if (OCA.Files.TaskUpList.lastAction) {
            OCA.Files.TaskUpList.lastAction();
        }
    });
    $(window).on('unload', function () {
        $(window).trigger('beforeunload');
    });
});