/*
 * Copyright (c) 2018
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * The file upload code uses several hooks to interact with blueimps jQuery file upload library:
 * 1. the core upload handling hooks are added when initializing the plugin,
 * 2. if the browser supports progress events they are added in a separate set after the initialization
 * 3. every app can add it's own triggers for fileupload
 *    - files adds d'n'd handlers and also reacts to done events to add new rows to the filelist
 *    - TODO pictures upload button
 *    - TODO music upload button
 */

/* global jQuery, humanFileSize, md5 */

/**
 * File upload object
 *
 * @class OC.FileUpload
 * @classdesc
 *
 * Represents a file upload
 *
 * @param {OC.Uploader} uploader uploader
 * @param {Object} data blueimp data
 */
OC.FileUpload = function(uploader, file, targetpath, originalpath) {
	if (!file || !uploader) {
		throw 'OC.FileUpload 初始化失败，参数不能为空';
	}

	this._uploader = uploader;
	this._file = file;

	this._targetFolder = targetpath;
	this._originalFullPath = originalpath;

	var path = OC.joinPaths(this._targetFolder, this.getFile().name);
	this._targetFullPath = path;


	this.id = md5(this.getFile().__hash + '-' + path + '-' + (new Date()).getTime());
};
OC.FileUpload.CONFLICT_MODE_DETECT = 0;
OC.FileUpload.CONFLICT_MODE_OVERWRITE = 1;
OC.FileUpload.CONFLICT_MODE_AUTORENAME = 2;
OC.FileUpload.CONFLICT_MODE_AUTORENAME_SERVER = 3;
OC.FileUpload.prototype = {

	/**
	 * Unique upload id
	 *
	 * @type string
	 */
	id: null,

	/**
	 * Upload element
	 *
	 * @type Object
	 */
	$uploadEl: null,

	_md5: null,
	_uploader: null,
	_originalFullPath: '',
	_targetFolder: '',
	_targetFullPath: '',
	_file: null,
	_cancelMsg: '',
	_progress: 0,

	/**
	 * @type int
	 */
	_conflictMode: OC.FileUpload.CONFLICT_MODE_DETECT,

	/**
	 * New name from server after autorename
	 *
	 * @type String
	 */
	_newName: null,
	_chunksStatus: [],

	/**
	 * Returns the unique upload id
	 *
	 * @return string
	 */
	getId: function() {
		return this.id;
	},

	getProgress: function(){
		return this._progress;
	},

	setProgress: function(val){
		this._progress = val;
	},

	getCancelMsg: function(){
		return this._cancelMsg;
	},

	setCancelMsg: function(msg){
		this._cancelMsg = msg;
	},

	getMD5: function(){
		return this._md5;
	},

	getLastModified: function(){
		return this._file.lastModifiedDate;
	},

	setChunksStatus: function(chunks){
		this._chunksStatus = chunks;
	},

	getChunkStatus(id){
		if (this._chunksStatus.length > id){
			return this._chunksStatus[id];
		}else{
			return 0;
		}
	},

	getType: function(){
		return this._file.type;
	},

	getStatus: function(){
		return this._file.getStatus();
	},

	getStatusText: function(){
		return this._file.statusText;
	},

	setMD5: function(md5){
		this._md5 = md5;
	},

	/**
	 * Returns the file to be uploaded
	 *
	 * @return {File} file
	 */
	getFile: function() {
		return this._file;
	},

	getOriginalFullPath: function() {
		return this._originalFullPath;
	},

	getTargetFullPath: function() {
		return this._targetFullPath;
	},

	/**
	 * Return the final filename.
	 *
	 * @return {String} file name
	 */
	getFileName: function() {
		// autorenamed name
		if (this._newName) {
			return this._newName;
		}

		var fileName = this.getFile().name;
		return this.sanitizeFileName(fileName);
	},

	/**
	 * Return the sanitized file name.
	 *
	 * @return {String} file name
	 */
	sanitizeFileName: function(fileName) {
		return fileName.trim();
	},

	/**
	 * Return the sanitized path.
	 *
	 * @return {String} path
	 */
	sanitizePath: function(path) {
		if(!path){
			return path;
		}

		var pathSegments = path.split('/');
		var sanitizedPathSegments = [];

		for (var i = 0; i < pathSegments.length; i++) {
			sanitizedPathSegments.push(pathSegments[i].trim());
		}

		return sanitizedPathSegments.join('/');
	},

	getLastModified: function() {
		var file = this.getFile();
		if (file.lastModifiedDate) {
			return file.lastModifiedDate.getTime() / 1000;
		}
		if (file.lastModified) {
			return file.lastModified / 1000;
		}
		return null;
	},

	getTargetFolder: function() {
		return this._targetFolder;
	},

	/**
	 * Get full path for the target file, including relative path,
	 * without the file name.
	 *
	 * @return {String} full path
	 */
	getFullPath: function() {
		var relativePath = this.getFile().relativePath;
		var sanitizedRelativePath =  this.sanitizePath(relativePath);
		return OC.joinPaths(this._targetFolder, sanitizedRelativePath || '');
	},

	/**
	 * Returns conflict resolution mode.
	 *
	 * @return {int} conflict mode
	 */
	getConflictMode: function() {
		return this._conflictMode || OC.FileUpload.CONFLICT_MODE_DETECT;
	},

	/**
	 * Set conflict resolution mode.
	 * See CONFLICT_MODE_* constants.
	 *
	 * @param {int} mode conflict mode
	 */
	setConflictMode: function(mode) {
		this._conflictMode = mode;
	},

	/**
	 * Returns whether the upload is in progress
	 *
	 * @return {bool}
	 */
	isPending: function() {
		return this._file.getStatus() === 'inited' || this._file.getStatus() === 'queued';
	},

	isPause: function() {
		return this._file.getStatus() === 'interrupt';
	},

	deleteUpload: function() {
		delete this.data.jqXHR;
	},

	/**
	 * Trigger autorename and append "(2)".
	 * Multiple calls will increment the appended number.
	 */
	autoRename: function() {
		var name = this.sanitizeFileName(this.getFile().name);
		if (!this._renameAttempt) {
			this._renameAttempt = 1;
		}

		var dotPos = name.lastIndexOf('.');
		var extPart = '';
		if (dotPos > 0) {
			this._newName = name.substr(0, dotPos);
			extPart = name.substr(dotPos);
		} else {
			this._newName = name;
		}

		// generate new name
		this._renameAttempt++;
		this._newName = this._newName + ' (' + this._renameAttempt + ')' + extPart;
	},

	/**
	 * Submit the upload
	 */
	submit: function() {
		var self = this;


	},

	/**
	 * Process end of transfer
	 */
	done: function() {

		var uid = OC.getCurrentUser().uid;
		var mtime = this.getLastModified();
		var size = this.getFile().size;
		var headers = {};
		if (mtime) {
			headers['X-OC-Mtime'] = mtime;
		}
		if (size) {
			headers['OC-Total-Length'] = size;
		}
		headers['OC-LazyOps'] = 1;

		var doneDeferred = $.Deferred();

		this.uploader.davClient.move(
			'uploads/' + uid + '/' + this.getId() + '/.file',
			'files/' + uid + '/' + OC.joinPaths(this.getFullPath(), this.getFileName()),
			true,
			headers
		).then(function (status, response) {
			// a 202 response means the server is performing the final MOVE in an async manner,
			// so we need to poll its status
			if (status === 202) {
				var poll = function() {
					$.ajax(response.xhr.getResponseHeader('oc-jobstatus-location')).then(function(data) {
						var obj = JSON.parse(data);
						if (obj.status === 'finished') {
							doneDeferred.resolve(status, response);
						}
						if (obj.status === 'error') {
							OC.Notification.show(obj.errorMessage);
							doneDeferred.reject(status, response);
						}
						if (obj.status === 'started' || obj.status === 'initial') {
							// call it again after some short delay
							setTimeout(poll, 1000);
						}
					});
				};

				// start the polling
				poll();
			} else {
				doneDeferred.resolve(status, response);
			}

		}).fail( function(status, response) {
			doneDeferred.reject(status, response);
		});

		return doneDeferred.promise();
	},

	_deleteChunkFolder: function() {
		// delete transfer directory for this upload

	},


	/**
	 * retry the upload
	 */
	retry: function() {
		if (!this.data.stalled) {
			console.log('Retrying upload ' + this.id);
			this.data.stalled = true;
			this.data.abort();
		}
	},

	/**
	 * Fail the upload
	 */
	fail: function() {
		this.deleteUpload();
		if (this.data.isChunked) {
			this._deleteChunkFolder();
		}
	},

	/**
	 * Returns the server response
	 *
	 * @return {Object} response
	 */
	getResponse: function() {
		var response = this.data.response();
		if (response.errorThrown) {
			if (response.errorThrown === 'timeout') {
				return {
					status: 0,
					message: '上传文件超时 "' + this.getFileName() + '"',
				};
			}

			// attempt parsing Sabre exception is available
			var xml = response.jqXHR.responseXML;
			if (xml.documentElement.localName === 'error' && xml.documentElement.namespaceURI === 'DAV:') {
				/*var messages = xml.getElementsByTagNameNS('http://sabredav.org/ns', 'message');
				var exceptions = xml.getElementsByTagNameNS('http://sabredav.org/ns', 'exception');
				if (messages.length) {
					response.message = messages[0].textContent;
				}
				if (exceptions.length) {
					response.exception = exceptions[0].textContent;
				}*/
				return response;
			}
		}

		if (typeof response.result !== 'string' && response.result) {
			//fetch response from iframe
			response = $.parseJSON(response.result[0].body.innerText);
			if (!response) {
				// likely due to internal server error
				response = { status: 500 };
			}
		} else if (response.result) {
			response = response.result;
		} else if (response.jqXHR) {
			if (response.jqXHR.status === 0 && response.jqXHR.statusText === 'error') {
				// timeout (IE11)
				return {
					status: 0,
					message: '上传文件超时 "' + this.getFileName() + '"',
				};
			}
			return {
				status: response.jqXHR.status,
				message: '上传文件 "' + this.getFileName() + '" 时发生未知错误 "' + response.jqXHR.statusText + '"'
			};
		}
		return response;
	},

	/**
	 * Returns the status code from the response
	 *
	 * @return {int} status code
	 */
	getResponseStatus: function() {
		if (this.uploader.isXHRUpload()) {
			var xhr = this.data.response().jqXHR;
			if (xhr) {
				return xhr.status;
			}
			return null;
		}
		return this.getResponse().status;
	},

	/**
	 * Returns the response header by name
	 *
	 * @param {String} headerName header name
	 * @return {Array|String} response header value(s)
	 */
	getResponseHeader: function(headerName) {
		headerName = headerName.toLowerCase();
		if (this.uploader.isXHRUpload()) {
			return this.data.response().jqXHR.getResponseHeader(headerName);
		}

		var headers = this.getResponse().headers;
		if (!headers) {
			return null;
		}

		var value =  _.find(headers, function(value, key) {
			return key.toLowerCase() === headerName;
		});
		if (_.isArray(value) && value.length === 1) {
			return value[0];
		}
		return value;
	}
};

/**
 * keeps track of uploads in progress and implements callbacks for the conflicts dialog
 * @namespace
 */

OC.Uploader = function() {
	this.init.apply(this, arguments);
};

OC.Uploader.prototype = _.extend({
	/**
	 * @type Array<OC.FileUpload>
	 */
	_uploads: {},

	/**
	 * List of directories known to exist.
	 *
	 * Key is the fullpath and value is boolean, true meaning that the directory
	 * was already created so no need to create it again.
	 */
	_knownDirs: {},

	/**
	 * @type OCA.Files.FileList
	 */
	fileList: null,

	/**
	 * @type OC.Files.Client
	 */
	filesClient: null,

	/**
	 * Webdav client pointing at the root "dav" endpoint
	 *
	 * @type OC.Files.Client
	 */
	davClient: null,

	/**
	 * Upload progressbar element
	 *
	 * @type Object
	 */
	$uploadprogressbar: null,

	/**
	 * @type int
	 */
	_uploadStallTimeout: 60,
	/**
	 * Function that will allow us to know if Ajax uploads are supported
	 * @link https://github.com/New-Bamboo/example-ajax-upload/blob/master/public/index.html
	 * also see article @link http://blog.new-bamboo.co.uk/2012/01/10/ridiculously-simple-ajax-uploads-with-formdata
	 */
	_supportAjaxUploadWithProgress: function() {
		if (window.TESTING) {
			return true;
		}
		return supportFileAPI() && supportAjaxUploadProgressEvents() && supportFormData();

		// Is the File API supported?
		function supportFileAPI() {
			var fi = document.createElement('INPUT');
			fi.type = 'file';
			return 'files' in fi;
		}

		// Are progress events supported?
		function supportAjaxUploadProgressEvents() {
			var xhr = new XMLHttpRequest();
			return !! (xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
		}

		// Is FormData supported?
		function supportFormData() {
			return !! window.FormData;
		}
	},

	/**
	 * Returns whether an XHR upload will be used
	 *
	 * @return {bool} true if XHR upload will be used,
	 * false for iframe upload
	 */
	isXHRUpload: function () {
		return !this.fileUploadParam.forceIframeTransport &&
			((!this.fileUploadParam.multipart && $.support.xhrFileUpload) ||
			$.support.xhrFormDataFileUpload);
	},

	/**
	 * Makes sure that the upload folder and its parents exists
	 *
	 * @param {String} fullPath full path
	 * @return {Promise} promise that resolves when all parent folders
	 * were created
	 */
	ensureFolderExists: function(fullPath) {
		if (!fullPath || fullPath === '/') {
			return $.Deferred().resolve().promise();
		}

		// remove trailing slash
		if (fullPath.charAt(fullPath.length - 1) === '/') {
			fullPath = fullPath.substr(0, fullPath.length - 1);
		}

		var self = this;
		var promise = this._knownDirs[fullPath];

		if (this.fileList) {
			// assume the current folder exists
			this._knownDirs[this.fileList.getCurrentDirectory()] = $.Deferred().resolve().promise();
		}

		if (!promise) {
			var deferred = new $.Deferred();
			promise = deferred.promise();
			this._knownDirs[fullPath] = promise;

			// make sure all parents already exist
			var parentPath = OC.dirname(fullPath);
			var parentPromise = this._knownDirs[parentPath];
			if (!parentPromise) {
				parentPromise = this.ensureFolderExists(parentPath);
			}

			parentPromise.then(function() {
				self.filesClient.createDirectory(fullPath).always(function(status) {
					// 405 is expected if the folder already exists
					if ((status >= 200 && status < 300) || status === 405) {
						self.trigger('createdfolder', fullPath);
						deferred.resolve();
						return;
					}
					OC.Notification.show('无法创建文件夹 "' + fullPath + '"', {type: 'error'});
					deferred.reject();
				});
			}, function() {
				deferred.reject();
			});
		}

		return promise;
	},

	/**
	 * Submit the given uploads
	 *
	 * @param {Array} array of uploads to start
	 */
	submitUploads: function(uploads) {
		var self = this;
		_.each(uploads, function(upload) {
			self._uploads[upload.data.uploadId] = upload;
			upload.submit();
		});
	},

	/**
	 * Show conflict for the given file object
	 *
	 * @param {OC.FileUpload} file upload object
	 */
	showConflict: function(fileUpload) {
		//show "file already exists" dialog
		var self = this;
		var file = fileUpload.getFile();
		// already attempted autorename but the server said the file exists ? (concurrently added)
		if (fileUpload.getConflictMode() === OC.FileUpload.CONFLICT_MODE_AUTORENAME) {
			// attempt another autorename, defer to let the current callback finish
			_.defer(function() {
				self.onAutorename(fileUpload);
			});
			return;
		}
		// retrieve more info about this file
		/*this.filesClient.getFileInfo(fileUpload.getFullPath()).then(function(status, fileInfo) {
			var original = fileInfo;
			var replacement = file;
			OC.dialogs.fileexists(fileUpload, original, replacement, self);
		});*/

		//todo 判断是否重名，并弹窗显示
	},
	/**
	 * cancels all uploads
	 */
	cancelUploads:function() {
		this.log('canceling uploads');
		jQuery.each(this._uploads, function(i, upload) {
			upload.abort();
			upload.aborted = true;
		});
		this.clear();
	},
	/**
	 * Clear uploads
	 */
	clear: function() {
		var remainingUploads = {};
		_.each(this._uploads, function(upload, key) {
			if (!upload.isDone && !upload.aborted) {
				remainingUploads[key] = upload;
			}
		});
		this._uploads = remainingUploads;
		this._knownDirs = {};
	},
	/**
	 * Returns an upload by id
	 *
	 * @param {int} data uploadId
	 * @return {OC.FileUpload} file upload
	 */
	getUpload: function(data) {
		if (_.isString(data)) {
			return this._uploads[data];
		} else if (data.uploadId) {
			return this._uploads[data.uploadId];
		}
		return null;
	},

	showUploadCancelMessage: _.debounce(function() {
		OC.Notification.show('上传已被取消', {timeout : 7, type: 'error'});
	}, 500),
	/**
	 * Checks the currently known uploads.
	 * returns true if any hxr has the state 'pending'
	 * @returns {boolean}
	 */
	isProcessing:function() {
		var count = 0;

		jQuery.each(this._uploads, function(i, upload) {
			if (upload.isPending()) {
				count++;
			}
		});
		return count > 0;
	},
	/**
	 * callback for the conflicts dialog
	 */
	onCancel:function() {
		this.cancelUploads();
	},
	/**
	 * callback for the conflicts dialog
	 * calls onSkip, onReplace or onAutorename for each conflict
	 * @param {object} conflicts - list of conflict elements
	 */
	onContinue:function(conflicts) {
		var self = this;
		//iterate over all conflicts
		jQuery.each(conflicts, function (i, conflict) {
			conflict = $(conflict);
			var keepOriginal = conflict.find('.original input[type="checkbox"]:checked').length === 1;
			var keepReplacement = conflict.find('.replacement input[type="checkbox"]:checked').length === 1;
			if (keepOriginal && keepReplacement) {
				// when both selected -> autorename
				self.onAutorename(conflict.data('data'));
			} else if (keepReplacement) {
				// when only replacement selected -> overwrite
				self.onReplace(conflict.data('data'));
			} else {
				// when only original selected -> skip
				// when none selected -> skip
				self.onSkip(conflict.data('data'));
			}
		});
	},
	/**
	 * handle skipping an upload
	 * @param {OC.FileUpload} upload
	 */
	onSkip:function(upload) {
		this.log('skip', null, upload);
		upload.deleteUpload();
	},
	/**
	 * handle replacing a file on the server with an uploaded file
	 * @param {FileUpload} data
	 */
	onReplace:function(upload) {
		this.log('replace', null, upload);
		upload.setConflictMode(OC.FileUpload.CONFLICT_MODE_OVERWRITE);
		this.submitUploads([upload]);
	},
	/**
	 * handle uploading a file and letting the server decide a new name
	 * @param {object} upload
	 */
	onAutorename:function(upload) {
		this.log('autorename', null, upload);
		upload.setConflictMode(OC.FileUpload.CONFLICT_MODE_AUTORENAME);

		do {
			upload.autoRename();
			// if file known to exist on the client side, retry
		} while (this.fileList && this.fileList.inList(upload.getFileName()));

		// resubmit upload
		this.submitUploads([upload]);
	},
	_trace:false, //TODO implement log handler for JS per class?
	log:function(caption, e, data) {
		if (this._trace) {
			console.log(caption);
			console.log(data);
		}
	},
	/**
	 * checks the list of existing files prior to uploading and shows a simple dialog to choose
	 * skip all, replace all or choose which files to keep
	 *
	 * @param {array} selection of files to upload
	 * @param {object} callbacks - object with several callback methods
	 * @param {function} callbacks.onNoConflicts
	 * @param {function} callbacks.onSkipConflicts
	 * @param {function} callbacks.onReplaceConflicts
	 * @param {function} callbacks.onChooseConflicts
	 * @param {function} callbacks.onCancel
	 */
	checkExistingFiles: function (selection, callbacks) {
		var self = this;
		var fileList = this.fileList;
		var conflicts = [];
		// only keep non-conflicting uploads
		selection.uploads = _.filter(selection.uploads, function(upload) {
			var file = upload.getFile();
			if (file.relativePath) {
				// can't check in subfolder contents, let backend handle this
				return true;
			}
			if (!fileList) {
				// no list to check against
				return true;
			}

			var currentDirectory = fileList.getCurrentDirectory();
			var targetFolder = upload.getTargetFolder();
			// let backend handle the conflict check if files are dragged into another folder
			if (targetFolder && currentDirectory && currentDirectory !== targetFolder) {
				return true;
			}

			var fileInfo = fileList.findFile(self.sanitizeFileName(file.name));
			if (fileInfo) {
				var sharePermission = parseInt($("#sharePermission").val());
				if (sharePermission === (OC.PERMISSION_READ | OC.PERMISSION_CREATE)) {
					OC.Notification.show('文件已存在 "'+ fileInfo.name + '"', {type: 'error'});
					return false;
				}
				conflicts.push([
					// original
					_.extend(fileInfo, {
						directory: fileInfo.directory || fileInfo.path || fileList.getCurrentDirectory()
					}),
					// replacement (File object)
					upload
				]);
				return false;
			}
			return true;
		});

		if (conflicts.length) {
			// wait for template loading
			OC.dialogs.fileexists(null, null, null, this).done(function () {
				_.each(conflicts, function (conflictData) {
					OC.dialogs.fileexists(conflictData[1], conflictData[0], conflictData[1].getFile(), this);
				});
			});
		}

		// upload non-conflicting files
		// note: when reaching the server they might still meet conflicts
		// if the folder was concurrently modified, these will get added
		// to the already visible dialog, if applicable
		callbacks.onNoConflicts(selection);
	},

	/**
	 * Return the sanitized file name.
	 *
	 * @return {String} file name
	 */
	sanitizeFileName: function(fileName) {
		return fileName.trim();
	},

	_hideProgressBar: function() {
		var self = this;
		window.clearInterval(this._progressBarInterval);
		$('#uploadprogresswrapper .stop').fadeOut();
		this.$uploadprogressbar.fadeOut(function() {
			self.$uploadEl.trigger(new $.Event('resized'));
		});
	},

	_showProgressBar: function() {
		/*this.$uploadprogressbar.fadeIn();
		this.$uploadEl.trigger(new $.Event('resized'));
		if (this._progressBarInterval) {
			window.clearInterval(this._progressBarInterval);
		}
		this._progressBarInterval = window.setInterval(_.bind(this._updateProgressBar, this), 1000);
		this._lastProgress = 0;*/
	},

	_updateProgressBar: function() {
		/*var progress = parseInt(this.$uploadprogressbar.attr('data-loaded'), 10);
		var total = parseInt(this.$uploadprogressbar.attr('data-total'), 10);
		if (progress !== this._lastProgress) {
			this._lastProgress = progress;
			this._lastProgressTime = new Date().getTime();
		} else {
			if (progress >= total) {
				// change message if we stalled at 100%
				this.$uploadprogressbar.find('.label .desktop').text('正在处理文件中...');
			}
			if (new Date().getTime() - this._lastProgressTime >= this._uploadStallTimeout * 1000 ) {
				// TODO: move to "fileuploadprogress" event instead and use data.uploadedBytes
				// stalling needs to be checked here because the file upload no longer triggers events
				// restart upload
				this.log('progress stalled'); // retry chunk (and prevent IE from dying)
				_.each(this._uploads, function(upload) {
					// FIXME: harden by only retry pending, not the finished ones
					upload.retry();
				});
			}
		}*/
	},

	/**
	 * Returns whether the given file is known to be a received shared file
	 *
	 * @param {Object} file file
	 * @return {bool} true if the file is a shared file
	 */
	_isReceivedSharedFile: function(file) {
		if (!window.FileList) {
			return false;
		}
		var $tr = window.FileList.findFileEl(file.name);
		if (!$tr.length) {
			return false;
		}

		return ($tr.attr('data-mounttype') === 'shared-root' && $tr.attr('data-mime') !== 'httpd/unix-directory');
	},

	/**
	 * Initialize the upload object
	 *
	 * @param {Object} $uploadEl upload element
	 * @param {Object} options
	 * @param {OCA.Files.FileList} [options.fileList] file list object
	 * @param {OC.Files.Client} [options.filesClient] files client object
	 * @param {Object} [options.dropZone] drop zone for drag and drop upload
	 * @param {String|function} [options.url] optional target url or function
	 */
	init: function($uploadEl, options) {
		var self = this;
		options = options || {};

		this._uploads = {};
		this._knownDirs = {};

		this.fileList = options.fileList;
		this.filesClient = options.filesClient || OC.Files.getClient();
		/*this.davClient = new OC.Files.Client({
			host: this.filesClient.getHost(),
			root: OC.linkToRemoteBase('dav'),
			useHTTPS: OC.getProtocol() === 'https',
			userName: this.filesClient.getUserName(),
			password: this.filesClient.getPassword()
		});*/

		if (options.url) {
			this.url = options.url;
		}
		if (options.uploadStallTimeout) {
			this._uploadStallTimeout = options.uploadStallTimeout;
		}

		$uploadEl = $($uploadEl);
		this.$uploadEl = $uploadEl;

		this.$uploadprogressbar = $('#uploadprogressbar');

		if ($uploadEl.exists()) {
			$('#uploadprogresswrapper .stop').on('click', function() {
				self.cancelUploads();
			});

			this.fileUploadParam = {
				type: 'PUT',
				dropZone: options.dropZone, // restrict dropZone to content div
				autoUpload: false,
				sequentialUploads: true,
				maxRetries: options.uploadStallRetries || 3,
				retryTimeout: 500,
				//singleFileUploads is on by default, so the data.files array will always have length 1
				/**
				 * on first add of every selection

				 * - on conflict show dialog
				 *   - skip all -> remember as single skip action for all conflicting files
				 *   - replace all -> remember as single replace action for all conflicting files
				 *   - choose -> show choose dialog
				 *     - mark files to keep
				 *       - when only existing -> remember as single skip action
				 *       - when only new -> remember as single replace action
				 *       - when both -> remember as single autorename action
				 * - start uploading selection
				 * @param {object} e
				 * @param {object} data
				 * @returns {boolean}
				 */
				add: function(e, data) {
					self.log('add', e, data);
					var that = $(this), freeSpace;

					var upload = new OC.FileUpload(self, data);
					// can't link directly due to jQuery not liking cyclic deps on its ajax object
					data.uploadId = upload.getId();

					// we need to collect all data upload objects before
					// starting the upload so we can check their existence
					// and set individual conflict actions. Unfortunately,
					// there is only one variable that we can use to identify
					// the selection a data upload is part of, so we have to
					// collect them in data.originalFiles turning
					// singleFileUploads off is not an option because we want
					// to gracefully handle server errors like 'already exists'

					// create a container where we can store the data objects
					if ( ! data.originalFiles.selection ) {
						// initialize selection and remember number of files to upload
						data.originalFiles.selection = {
							uploads: [],
							filesToUpload: data.originalFiles.length,
							totalBytes: 0
						};
					}
					// TODO: move originalFiles to a separate container, maybe inside OC.Upload
					var selection = data.originalFiles.selection;

					// add uploads
					if ( selection.uploads.length < selection.filesToUpload ) {
						// remember upload
						selection.uploads.push(upload);
					}

					//examine file
					var file = upload.getFile();
					try {
						// FIXME: not so elegant... need to refactor that method to return a value
						Files.isFileNameValid(file.name);
					}
					catch (errorMessage) {
						data.textStatus = 'invalidcharacters';
						data.errorThrown = errorMessage;
					}

					if (data.targetDir) {
						upload.setTargetFolder(data.targetDir);
						delete data.targetDir;
					}

					// in case folder drag and drop is not supported file will point to a directory
					// http://stackoverflow.com/a/20448357
					if ( ! file.type && file.size % 4096 === 0 && file.size <= 102400) {
						var dirUploadFailure = false;
						try {
							var reader = new FileReader();
							reader.readAsBinaryString(file);
						} catch (NS_ERROR_FILE_ACCESS_DENIED) {
							//file is a directory
							dirUploadFailure = true;
						}

						if (dirUploadFailure) {
							data.textStatus = 'dirorzero';
							data.errorThrown = '无法上传文件 "' + file.name + '"，因为它是文件夹或只有 0 大小';
						}
					}

					// only count if we're not overwriting an existing shared file
					if (self._isReceivedSharedFile(file)) {
						file.isReceivedShare = true;
					} else {
						// add size
						selection.totalBytes += file.size;
					}

					// check free space
					freeSpace = $('#free_space').val();
					if (freeSpace >= 0 && selection.totalBytes > freeSpace) {
						data.textStatus = 'notenoughspace';
						data.errorThrown = '可用空间不足，无法上传文件';
					}

					// end upload for whole selection on error
					if (data.errorThrown) {
						// trigger fileupload fail handler
						var fu = that.data('blueimp-fileupload') || that.data('fileupload');
						fu._trigger('fail', e, data);
						return false; //don't upload anything
					}

					// check existing files when all is collected
					if ( selection.uploads.length >= selection.filesToUpload ) {

						//remove our selection hack:
						delete data.originalFiles.selection;

						var callbacks = {

							onNoConflicts: function (selection) {
								self.submitUploads(selection.uploads);
							},
							onSkipConflicts: function (selection) {
								//TODO mark conflicting files as toskip
							},
							onReplaceConflicts: function (selection) {
								//TODO mark conflicting files as toreplace
							},
							onChooseConflicts: function (selection) {
								//TODO mark conflicting files as chosen
							},
							onCancel: function (selection) {
								$.each(selection.uploads, function(i, upload) {
									upload.abort();
								});
							}
						};

						_.each(selection.uploads, function(upload) {
							self.trigger('beforeadd', upload);
						});

						self.checkExistingFiles(selection, callbacks);

					}

					return true; // continue adding files
				},
				/**
				 * called after the first add, does NOT have the data param
				 * @param {object} e
				 */
				start: function(e) {
					self.log('start', e, null);
					//hide the tooltip otherwise it covers the progress bar
					$('#upload').tipsy('hide');
				},
				fail: function(e, data) {
					var upload = self.getUpload(data);
					if (upload && upload.data && upload.data.stalled) {
						self.log('retry', e, upload);
						// jQuery Widget Factory uses "namespace-widgetname" since version 1.10.0:
						var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload'),
							retries = upload.data.retries || 0,
							retry = function () {
								var uid = OC.getCurrentUser().uid;
								upload.uploader.davClient.getFolderContents(
									'uploads/' + uid + '/' + upload.getId()
								)
								.done(function (status, files) {
									data.uploadedBytes = 0;
									_.each(files, function(file) {
										// only count numeric file names to omit .file and .file.zsync
										if (!isNaN(parseFloat(file.name))
											&& isFinite(file.name)
											// only count full chunks
											&& file.size === fu.options.maxChunkSize
										) {
											data.uploadedBytes += file.size;
										}
									});

									// clear the previous data:
									upload.data.stalled = false;
									data.data = null;
									// overwrite chunk
									delete data.headers['If-None-Match'];
									data.submit();
								})
								.fail(function (status, ex) {
									self.log('failed to retry', status, ex);
									fu._trigger('fail', e, data);
								});
							};
						if (upload && upload.data && upload.data.stalled &&
							data.uploadedBytes < data.files[0].size &&
							retries < fu.options.maxRetries) {
							retries += 1;
							upload.data.retries = retries;
							window.setTimeout(retry, retries * fu.options.retryTimeout);
							return;
						}
						fu.prototype
							.options.fail.call(this, e, data);
						return;
					}

					var status = null;
					if (upload) {
						status = upload.getResponseStatus();
					}
					self.log('fail', e, upload);
					self._hideProgressBar();

					if (data.textStatus === 'abort') {
						self.showUploadCancelMessage();
					} else if (status === 412) {
						// file already exists
						self.showConflict(upload);
					} else if (status === 403) {
						// permission denied
						var response = upload.getResponse();
						message = response.message;
						// If the message comes from a storage wrapper exception it should be already
						// translated. Otherwise we have a default exception message and translate it here
						if (message === '') {
							message = '你没有权限在这个路径上传文件或创建文件';
						}
						OC.Notification.show(message, {type: 'error'});
					} else if (status === 404) {
						// target folder does not exist any more
						var dir = upload.getFullPath();
						if (dir && dir !== '/') {
							OC.Notification.show('目标文件夹 "' + dir + '" 不存在', {type: 'error'});
						} else {
							OC.Notification.show('目标文件夹不存在', {type: 'error'});
						}
						self.cancelUploads();
					} else if (status === 423) {
						// file is locked
						OC.Notification.show('该文件 "' + upload.getFileName() + '" 当前已被锁定，请稍后再尝试', {type: 'error'});
					} else if (status === 507) {
						// not enough space
						OC.Notification.show('可用空间不足', {type: 'error'});
						self.cancelUploads();
					} else {
						// HTTP connection problem or other error
						var message = '';
						if (upload) {
							var response = upload.getResponse();
							message = response.message;
						}
						OC.Notification.show(message || data.errorThrown, {type: 'error'});
					}

					if (upload) {
						upload.fail();
					}
				},
				/**
				 * called for every successful upload
				 * @param {object} e
				 * @param {object} data
				 */
				done:function(e, data) {
					var upload = self.getUpload(data);
					var that = $(this);
					self.log('done', e, upload);
					upload.isDone = true;

					var status = upload.getResponseStatus();
					if (status < 200 || status >= 300) {
						// trigger fail handler
						var fu = that.data('blueimp-fileupload') || that.data('fileupload');
						fu._trigger('fail', e, data);
						return;
					}
				},
				/**
				 * called after last upload
				 * @param {object} e
				 * @param {object} data
				 */
				stop: function(e, data) {
					self.log('stop', e, data);
				}
			};

			if (options.maxChunkSize) {
				this.fileUploadParam.maxChunkSize = options.maxChunkSize;
			}

			// initialize jquery fileupload (blueimp)
			var fileupload = this.$uploadEl.fileupload(this.fileUploadParam);

			if (this._supportAjaxUploadWithProgress()) {
				//remaining time
				var bufferSize = 20;
				var buffer = [];
				var bufferIndex = 0;
				var bufferTotal = 0;
				var filledBufferSize = 0;
				for(var i = 0; i < bufferSize;i++){
					buffer[i] = 0;
				}

				// add progress handlers
				fileupload.on('fileuploadadd', function(e, data) {
					self.log('progress handle fileuploadadd', e, data);
					self.trigger('add', e, data);
				});
				// add progress handlers
				fileupload.on('fileuploadstart', function(e, data) {
					self.log('progress handle fileuploadstart', e, data);
					$('#uploadprogresswrapper .stop').show();
					$('#uploadprogresswrapper .label').show();
					self.$uploadprogressbar.progressbar({value: 0});
					self.$uploadprogressbar.find('.ui-progressbar-value').
						html('<em class="label inner"><span class="desktop">'
							+ '上传中...'
							+ '</span><span class="mobile">'
							+ '...'
							+ '</span></em>');
					self.$uploadprogressbar.tipsy({gravity:'n', fade:true, live:true});
					self._showProgressBar();
					self.trigger('start', e, data);
				});
				fileupload.on('fileuploadprogress', function(e, data) {
					self.log('progress handle fileuploadprogress', e, data);
					//TODO progressbar in row
					self.trigger('progress', e, data);
				});
				fileupload.on('fileuploadprogressall', function(e, data) {
					self.log('progress handle fileuploadprogressall', e, data);
					var progress = (data.loaded / data.total) * 100;
					var remainingBits = (data.total - data.loaded) * 8;
					var remainingSeconds = remainingBits / data.bitrate;

					//Take the average remaining seconds of the last bufferSize events
					//to prevent fluctuation and provide a smooth experience
					if (isFinite(remainingSeconds) && remainingSeconds >= 0) {
						bufferTotal = bufferTotal - (buffer[bufferIndex]) + remainingSeconds;
						buffer[bufferIndex] = remainingSeconds;
						bufferIndex = (bufferIndex + 1) % bufferSize;
						if (filledBufferSize < bufferSize) {
							filledBufferSize++;
						}
					}

					if (!oc_appconfig.files.hide_upload_estimation) {
						var smoothRemainingSeconds = (bufferTotal / filledBufferSize);
						var h = moment.duration(smoothRemainingSeconds, "seconds").humanize();
						self.$uploadprogressbar.find('.label .mobile').text(h);
						self.$uploadprogressbar.find('.label .desktop').text(h);
					}

					self.$uploadprogressbar.attr('data-loaded', data.loaded);
					self.$uploadprogressbar.attr('data-total', data.total);
					self.$uploadprogressbar.attr('original-title',
						humanFileSize(data.loaded) + ' of ' + humanFileSize(data.total) + ' (' + humanFileSize(data.bitrate) +'/s)' , 
					);
					self.$uploadprogressbar.progressbar('value', progress);
					self.trigger('progressall', e, data);
				});
				fileupload.on('fileuploadstop', function(e, data) {
					self.log('progress handle fileuploadstop', e, data);

					self.clear();
					self.trigger('stop', e, data);
				});
				fileupload.on('fileuploadfail', function(e, data) {
					self.log('progress handle fileuploadfail', e, data);
					//if user pressed cancel hide upload progress bar and cancel button
					if (data.errorThrown === 'abort') {
						self._hideProgressBar();
					}
					self.trigger('fail', e, data);
				});

				fileupload.on('fileuploadchunksend', function(e, data) {
					// modify the request to adjust it to our own chunking
					var upload = self.getUpload(data);
					var range = data.contentRange.split(' ')[1];
					var chunkId = range.split('/')[0].split('-')[0];
					data.url = OC.getRootPath() +
						'/remote.php/dav/uploads' +
						'/' + encodeURIComponent(OC.getCurrentUser().uid) +
						'/' + encodeURIComponent(upload.getId()) +
						'/' + encodeURIComponent(chunkId);
					delete data.contentRange;
					delete data.headers['Content-Range'];

					// reset retries
					upload.data.retries = 0;
				});
				fileupload.on('fileuploadchunkdone', function(e, data) {
					$(data.xhr().upload).unbind('progress');
				});
				fileupload.on('fileuploaddone', function(e, data) {
					var upload = self.getUpload(data);
					upload.done().then(function() {
						self.trigger('done', e, upload);
						// defer because sometimes the current upload is still in pending
						// state but frees itself afterwards
						_.defer(function() {
							// don't hide if there are more files to process
							if (!self.isProcessing()) {
								self._hideProgressBar();
							}
						});
					}).fail(function(status, response) {
						var message = response.message;
						self._hideProgressBar();
						if (status === 507) {
							// not enough space
							OC.Notification.show(message || '可用空间不足', {type: 'error'});
							self.cancelUploads();
						} else if (status === 409) {
							OC.Notification.show(message || '目标文件夹不存在', {type: 'error'});
						} else {
							OC.Notification.show(message || '文件块组装出错，错误码 '+ status, {type: 'error'});
						}
						self.trigger('fail', e, data);
					});
				});
				fileupload.on('fileuploaddrop', function(e, data) {
					self.trigger('drop', e, data);
				});

			}
		}

		// warn user not to leave the page while upload is in progress
		$(window).on('beforeunload', function(e) {
			if (self.isProcessing()) {
				return '正在上传文件，离开当前页面会取消上传';
			}
		});

		//add multiply file upload attribute to all browsers except konqueror (which crashes when it's used)
		if (navigator.userAgent.search(/konqueror/i) === -1) {
			this.$uploadEl.attr('multiple', 'multiple');
		}

		return this.fileUploadParam;
	}
}, OC.Backbone.Events);

OC.Uploader_ = function() {
	this.init.apply(this, arguments);
};

OC.Uploader_.prototype = _.extend({
	_uploads: {},
	_uploads_order: [],
	_uploads_file: {},

	_targetDir: '',
	fileList: null,
	_webuploader: null,
	_loginToken: null,

	keepOriginal: true,
	keepReplacement: true,

	init: function(options) {
		var self = this;
		options = options || {};
		this._uploads = {};
		this._uploads_order = [];
		this._uploads_file = {};
		this.fileList = options.fileList;
		
		if (options.url) {
			this.url = options.url;
		}

		this.uploadParams = {
			auto: false,
			swf: './Uploader.swf',
			server: '/task/upload',
			resize: false,
			chunked: true,				//开启分片上传
			chunkRetry: 2,
			// 分片大小
			chunkSize: options.maxChunkSize ? options.maxChunkSize : 5 * 1024 * 1024,	
			
			threads: 5,
			fileNumLimit: 10000, 			// 限制文件上传个数
			methods: 'POST',
			duplicate: false,			// 去重， 根据文件名字、文件大小和最后修改时间来生成hash Key
		};

		 // WebUploader提供的钩子（hook），在文件上传前先判断服务是否已存在这个文件
		 WebUploader.Uploader.register({
			'before-send-file': 'beforeSendFile', //整个文件上传前
			'after-send-file': 'afterSendFile' //传完所有分片后，如果promise被拒绝了，则文件上传出错停止
		  }, {
			afterSendFile: function(file){
				var that = this;
				var upload = self._uploads_file[file.id];
				if (!upload){
					that.owner.cancelFile(file);
					console.warn('找不到对应的 upload: ' + file.name);
					return;
				}

				var deferred = WebUploader.Deferred();
				var promise = deferred.promise();

				var chunks = Math.ceil(file.size / (5 * 1024 * 1024));

				$.ajax({
					url: OC.filePath('task/uploaddone?id=') + upload.getId()+'&chunks='+chunks,
					data: {},
					type: "GET",
					datatype: "json",
					headers: {logintoken: self._loginToken},
					async: false,
					cache: false,
					data: {},
					success: function(data, result, response){
						deferred.resolve();
					},
					error: function(data, result){
						file.setStatus('error', data.responseJSON + '，请重新添加任务');
						console.warn('上传完成时出错：' + data.responseJSON);
						deferred.reject();
					},
				});

				return promise;
			},
			beforeSendFile: function( file ) {
				var that = this;
	
				var upload = self._uploads_file[file.id];
				if (!upload){
					that.owner.cancelFile(file);
					console.warn('找不到对应的 upload: ' + file.name);
					return;
				}


				var deferred = WebUploader.Deferred();
				var promise = deferred.promise();

				//计算MD5
				if (upload.getMD5() == null){
					self._webuploader.md5File(file)
					.progress(function(percentage){
						self.trigger('md5Progress', upload, percentage);
					}).then(function(val){
						upload.setMD5(val);
						deferred.resolve();
					}, function(){
						console.warn('无法计算MD5: ' + file.name);
						upload.setCancelMsg('无法计算文件MD5，请重新添加上传文件');

						that.owner.stop(file);
						file.setStatus('invalid', '无法计算文件MD5，请重新添加任务');
						self.trigger('invalid', upload);

						deferred.reject();
					});
				}else{
					deferred.resolve();
				}


				promise.then(function(){
					//在服务器上创建对应的上传任务
					$.ajax({
						url: OC.filePath('task/appendupload'),
						data: {},
						type: "POST",
						datatype: "json",
						headers: {logintoken: self._loginToken},
						async: false,
						cache: false,
						data: {
							from: upload.getOriginalFullPath(),
							target: upload.getTargetFullPath(),
							filehash: upload.getMD5(),
							type: upload.getType(),
							lastmodified: upload.getLastModified(),
							id: upload.getId(),
							size: upload.getFile().size,
						},
						success: function(data, result, response){
							//服务返回的数据中包含了已经收到的文件块，存好来
							upload.setChunksStatus(data);
							deferred.resolve();
						},
						error: function(data, result){
							upload.setCancelMsg('添加上传任务失败');

							that.owner.stop(file);
							file.setStatus('invalid', data.responseJSON + '，请重新添加任务');
							self.trigger('invalid', upload);

							//file.setStatus('error', '无法在服务器创建上传任务，请重试');
							console.warn('服务器添加上传任务失败：' + file.name);
							deferred.reject();
						},
					});
				}, function(){
					deferred.reject();
				});

				return promise;
			}
		  });

		  this._webuploader = WebUploader.create(this.uploadParams);

		//文件被添加之前
		this._webuploader.on('beforeFileQueued', function(file) {
			try {
				// FIXME: not so elegant... need to refactor that method to return a value
				Files.isFileNameValid(file.name);

				return true;
			} catch (errorMessage) {
				OC.Notification.show(file.name + '上传失败：' + errorMessage, {timeout : 7, type: 'error'});
				return false;
			}
		});

		//当文件被添加进队列
		this._webuploader.on('fileQueued', function(file) {
			var upload = new OC.FileUpload(self, file, self._targetDir, '');
			self._uploads[upload.id] = upload;
			self._uploads_order.push(upload);
			self._uploads_file[file.id] = upload;

			file.on('statuschange', function(status, prevStatus){
				self.trigger('statuschange', upload, status, upload.getStatusText());
			});

			self.trigger('add', upload);
		});
		

		// 文件上传过程中创建进度条实时显示。
		this._webuploader.on('uploadProgress', function(file, percentage) {
			//console.log('_webuploader: uploadProgress');

			var upload = self._uploads_file[file.id];

			if (!upload){
				console.warn('找不到文件对应的上传任务：' + file.name);
				return;
			}

			self.trigger('uploadProgress', upload, percentage);
		});

		this._webuploader.on('uploadSuccess', function(file) {
			console.log('_webuploader: success');

			var upload = self._uploads_file[file.id];

			if (!upload){
				console.warn('找不到文件对应的上传任务：' + file.name);
				return;
			}

			self.trigger('success', upload);
		});
		
		this._webuploader.on('uploadError', function(file, reason) {
			//console.log('_webuploader: error');
			//console.log(reason);
			var upload = self._uploads_file[file.id];

			if (!upload){
				console.warn('找不到文件对应的上传任务：' + file.name);
				return;
			}

			self.trigger('error', upload);
		});
		
		this._webuploader.on('uploadComplete', function(file) {
			//console.log('_webuploader: complete');

			var upload = self._uploads_file[file.id];

			if (!upload){
				console.warn('找不到文件对应的上传任务：' + file.name);
				return;
			}

			self.trigger('complete', upload);
		});

		//文件从队列中删除时
		this._webuploader.on('fileDequeued', function(file) {
			console.log('_webuploader: dequeued');

			var upload = self._uploads_file[file.id];

			if (!upload){
				console.warn('找不到文件对应的上传任务：' + file.name);
				return;
			}

			self.trigger('remove', upload);
		});
		

		this._webuploader.on('uploadBeforeSend', function(object, data, headers) {
			//console.log('uploadBeforeSend: ' + object.chunk);
			
			var upload = self._uploads_file[object.file.id];

			if (!upload){
				console.warn('找不到文件对应的上传任务：' + object.name);
			}

			//console.log('uploadBeforeSend: ' + )
			if (upload.getChunkStatus(object.chunk) != 0){
				console.warn('跳过已上传的文件块：' + object.chunk);
				return false;
			}

			//带上身份验证信息
			headers['logintoken'] = this._loginToken;
			data['uploadid'] = upload.getId();
			return true;
		});

		return this.uploadParams;
	},

	setToken: function(token) {
		this._loginToken = token;
	},

	appendFiles: function(files, targetdir) {
		if (files.length <= 0) {
			return;
		}

		this._targetDir = targetdir;

		for(var i = 0; i < files.length; i++){
			this._webuploader.addFiles(files[i]);
		}
	},

	pauseUpload: function(id) {
		var upload = this._uploads[id];
		//console.log('pauseUpload: ' + upload.getFile().name);
		this._webuploader.stop(upload.getFile(), true);
	},

	pauseUploads: function() {
		var self = this;
		_.each(this._uploads, function(upload) {
			self._webuploader.stop(upload.getFile(), true);
		});
	},

	startUpload: function(id) {
		var upload = this._uploads[id];
		this._webuploader.upload(upload.getFile());
	},

	startUploads: function() {
		var self = this;
		_.each(this._uploads, function(upload) {
			self._webuploader.upload(upload.getFile());
		});
	},


	cancelUpload: function(id) {
		var upload = this._uploads[id];
		this._uploads[id] = null;
		this._uploads_file[md5(upload.getFile())] = null;

		Array.prototype.indexOf = function(val) { 
			for (var i = 0; i < this.length; i++) { 
				if (this[i].id == val) return i; 
			} 
			return -1; 
		};

		var idx = this._uploads_order.indexOf(upload.id);
		console.log(idx);
		if (idx > -1){
			this._uploads_order.splice(idx, 1); 
		}

		this._webuploader.cancelFile(upload.getFile());

	},

	cancelUploads: function() {
		var self = this;
		_.each(this._uploads, function(upload) {
			self.cancelUpload(upload.getId());
		});
	},

	submitUploads: function(uploads) {

	},

	showConflict: function(fileUpload) {
		var self = this;
		_.defer(function() {
			self.onAutorename(fileUpload);
		});
	},

	cancelUploads: function() {
		//this.log('取消所有上传任务');
		jQuery.each(this._uploads, function(i, upload) {
			upload.cancelUpload();
		});

		this.clear();
	},

	clear: function() {
		var remainingUploads = {};
		_.each(this._uploads, function(upload, key) {
			if (!upload.isDone && !upload.aborted) {
				remainingUploads[key] = upload;
			}
		});
		this._uploads = remainingUploads;
	},

	getUpload: function(data) {
		/*if (_.isString(data)) {
			return this._uploads[data];
		} else if (data.uploadId) {
			return this._uploads[data.uploadId];
		}*/

		return null;
	},

	showUploadCancelMsg: _.debounce(function() {
		OC.Notification.show('上传已被取消', {timeout : 7, type: 'error'});
	}, 500),

	onCancel: function() {
		this.cancelUploads();
	},

	onContinue: function(conflicts) {
		var self = this;
		jQuery.each(conflicts, function(i, conflict) {
			conflict = $(conflict);
			if (keepOriginal && keepReplacement) {
				// when both selected -> autorename
				self.onAutorename(conflict.data('data'));
			} else if (keepReplacement) {
				// when only replacement selected -> overwrite
				self.onReplace(conflict.data('data'));
			} else {
				// when only original selected -> skip
				// when none selected -> skip
				self.onSkip(conflict.data('data'));
			}
		});
	},

	onSkip: function(upload) {
		this.log('skip', null, upload);
		upload.deleteUpload();
	},

	onReplace: function(upload) {
		this.log('replace', null, upload);
		upload.setConflictMode(OC.FileUpload.CONFLICT_MODE_OVERWRITE);
		this.submitUploads([upload]);
	},

	onAutorename:function(upload) {
		this.log('autorename', null, upload);
		upload.setConflictMode(OC.FileUpload.CONFLICT_MODE_AUTORENAME);

		do {
			upload.autoRename();
			// if file known to exist on the client side, retry
		} while (this.fileList && this.fileList.inList(upload.getFileName()));

		// resubmit upload
		this.submitUploads([upload]);
	},

	_trace:false, //TODO implement log handler for JS per class?
	log:function(caption, e, data) {
		if (this._trace) {
			console.log(caption);
			console.log(data);
		}
	},

	sanitizeFileName: function(fileName) {
		return fileName.trim();
	},

	getUploads: function(start, end) {
		var len = this._uploads_order.length;
		if (start >= len) {
			return [];
		}

		if (end >= len) {
			end = len - 1;
		}

		var results = [];
		for(var i = start; i <= end; i++) {
			var upload = this._uploads_order[i];
			results.push({
				from: upload.getOriginalFullPath(),
				title: upload.getFileName(),
				target: upload.getTargetFullPath(),
				id: upload.getId(),
				status: upload.getStatus(),
				statustext: upload.getStatusText()
			});
		}

		return results;
	},
}, OC.Backbone.Events);

OC.UploaderInstance = new OC.Uploader_({});