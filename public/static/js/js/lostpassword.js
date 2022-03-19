OC.Lostpassword = {
	sendErrorMsg : '发送密码重置邮件失败，请联系网站管理员',

	sendSuccessMsg : '点击将发送一封密码重置邮件。<br>如果等待一段时间后还没有收到，请联系网站管理员',

	encryptedMsg : "你的文件是加密的。如果还没备份密钥，则在重置密码后将无法恢复数据<br/>如果您不确定要做什么，请在继续之前与管理员联系。 <br />确定要继续吗？"
			+ ('<br /><input type="checkbox" id="encrypted-continue" value="Yes" />')
			+ '<label for="encrypted-continue">'
			+ '我知道我在做什么'
			+ '</label><br />',

	resetErrorMsg : '无法修改密码，请联系网站管理员',

	init : function() {
		$('#lost-password').click(OC.Lostpassword.resetLink);
		$('#reset-password #submit').click(OC.Lostpassword.resetPassword);
	},

	resetLink : function(event){
		event.preventDefault();
		if (!$('#user').val().length){
			$('#submit').trigger('click');
		} else {
			if (OC.config['lost_password_link']) {
				window.location = OC.config['lost_password_link'];
			} else {
				$.post(
					OC.generateUrl('/lostpassword/email'),
					{
						user : $('#user').val()
					},
					OC.Lostpassword.sendLinkDone
				);
			}
		}
	},

	sendLinkDone : function(result){
		var sendErrorMsg;

		if (result && result.status === 'success'){
			OC.Lostpassword.sendLinkSuccess();
		} else {
			if (result && result.msg){
				sendErrorMsg = result.msg;
			} else {
				sendErrorMsg = OC.Lostpassword.sendErrorMsg;
			}
			OC.Lostpassword.sendLinkError(sendErrorMsg);
		}
	},

	sendLinkSuccess : function(msg){
		var node = OC.Lostpassword.getSendStatusNode();
		// update is the better success message styling
		node.addClass('update').css({width:'auto'});
		node.html(OC.Lostpassword.sendSuccessMsg);
	},

	sendLinkError : function(msg){
		var node = OC.Lostpassword.getSendStatusNode();
		node.addClass('warning');
		node.html(msg);
		OC.Lostpassword.init();
	},

	getSendStatusNode : function(){
		if (!$('#lost-password').length){
			$('<p id="lost-password"></p>').insertBefore($('#remember_login'));
		} else {
			$('#lost-password').replaceWith($('<p id="lost-password"></p>'));
		}
		return $('#lost-password');
	},

	resetPassword : function(event){
		$('#password').parent().removeClass('shake');

		event.preventDefault();
		if ($('#password').val() === $('#retypepassword').val()){
			$('#reset-password #submit').addClass('icon-loading-small');
			$.post(
					$('#password').parents('form').attr('action'),
					{
						password : $('#password').val(),
						proceed: $('#encrypted-continue').is(':checked') ? 'true' : 'false'
					},
					OC.Lostpassword.resetDone
			);
		} else {
			//Password mismatch happened
			$('#password').val('');
			$('#retypepassword').val('');
			$('#password').parent().addClass('shake');
			$('#message').addClass('warning');
			$('#message').text('密码验证错误');
			$('#message').show();
			$('#password').focus();
		}
		if($('#encrypted-continue').is(':checked')) {
			$('#reset-password #submit').hide();
			$('#reset-password #float-spinner').removeClass('hidden');
		}
	},

	resetDone : function(result){
		var resetErrorMsg;
		if (result && result.status === 'success'){
			$.post(
					OC.webroot + '/',
					{
						user : window.location.href.split('/').pop(),
						password : $('#password').val()
					},
					OC.Lostpassword.redirect
			);
		} else {
			$('#reset-password #submit').removeClass('icon-loading-small');

			if (result && result.msg){
				resetErrorMsg = result.msg;
			} else if (result && result.encryption) {
				resetErrorMsg = OC.Lostpassword.encryptedMsg;
			} else {
				resetErrorMsg = OC.Lostpassword.resetErrorMsg;
			}
			OC.Lostpassword.resetError(resetErrorMsg);
		}
	},

	redirect : function(msg){
		if(OC.webroot !== '') {
			window.location = OC.webroot;
		} else {
			window.location = '/';
		}
	},

	resetError : function(msg){
		var node = OC.Lostpassword.getResetStatusNode();
		node.addClass('warning');
		node.html(msg);
	},

	getResetStatusNode : function (){
		if (!$('#lost-password').length){
			$('#reset-password .submit-wrap').prepend($('<p id="lost-password"></p>'));
		} else {
			$('#lost-password').replaceWith($('<p id="lost-password"></p>'));
		}
		return $('#lost-password');
	}

};

$(document).ready(function () {
	OC.Lostpassword.init();
	$('#password').keypress(function () {
		/*
		 The warning message should be shown only during password mismatch.
		 Else it should not.
		 */
		if (($('#password').val().length >= 0)
			&& ($('#retypepassword').length)
			&& ($('#retypepassword').val().length === 0)) {
			$('#message').removeClass('warning');
			$('#message').text('');
		}
	});
});
