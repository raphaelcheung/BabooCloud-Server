var dbtypes;
$(document).ready(function() {
	dbtypes={
		sqlite:!!$('#hasSQLite').val(),
		mysql:!!$('#hasMySQL').val(),
		postgresql:!!$('#hasPostgreSQL').val(),
		oracle:!!$('#hasOracle').val()
	};

	$('#selectDbType').buttonset();
	// change links inside an info box back to their default appearance
	$('#selectDbType p.info a').button('destroy');

	if($('#hasSQLite').val()){
		$('#use_other_db').hide();
		$('#use_oracle_db').hide();
	} else {
		$('#sqliteInformation').hide();
	}
	$('#adminlogin').change(function(){
		$('#adminlogin').val($.trim($('#adminlogin').val()));
	});

	$('#dbhost').change(function(){
		$('#dbhost').val($.trim($('#dbhost').val()));
	});

	$('#dbport').change(function(){
		$('#dbport').val($.trim($('#dbport').val()));
	});

	$('#mysql,#pgsql').click(function() {
		$('#use_other_db').slideDown(250);
		$('#use_oracle_db').slideUp(250);
		$('#sqliteInformation').hide();
		$('#dbname').attr('pattern','[0-9a-zA-Z$_-]+');
	});

	$('#dbname').attr('pattern','[0-9a-zA-Z$_-]+');

	$('input[checked]').trigger('click');

	$('#showAdvanced').click(function(e) {
		e.preventDefault();
		$('#datadirContent').slideToggle(250);
		$('#databaseBackend').slideToggle(250);
		$('#databaseField').slideToggle(250);
	});
	$("form").submit(function(){
		$('button#submit')
			.addClass('icon-loading-small')
			.css('opacity', '1');

		// Save form parameters
		var post = $(this).serializeArray();

		// Show spinner while finishing setup
		$('.float-spinner').show(250);

		// Disable inputs
		$(':submit', this).attr('disabled','disabled');
		$('input', this).addClass('ui-state-disabled').attr('disabled','disabled');
		// only disable buttons if they are present
		if($('#selectDbType').find('.ui-button').length > 0) {
			$('#selectDbType').buttonset('disable');
		}
		$('.strengthify-wrapper, .tipsy')
			.css('-ms-filter', '"progid:DXImageTransform.Microsoft.Alpha(Opacity=30)"')
			.css('filter', 'alpha(opacity=30)')
			.css('opacity', .3);

		// Create the form
		var form = $('<form>');
		form.attr('action', $(this).attr('action'));
		form.attr('method', 'POST');

		for(var i=0; i<post.length; i++){
			var input = $('<input type="hidden">');
			input.attr(post[i]);
			form.append(input);
		}

		// Submit the form
		form.appendTo(document.body);
		form.submit();
		return false;
	});

	// Expand latest db settings if page was reloaded on error
	var currentDbType = $('input[type="radio"]:checked').val();

	if (currentDbType === undefined){
		$('input[type="radio"]').first().click();
	}

	if (
		currentDbType === 'sqlite' ||
		(dbtypes.sqlite && currentDbType === undefined)
	){
		$('#datadirContent').hide(250);
		$('#databaseBackend').hide(250);
		$('#databaseField').hide(250);
		$('.float-spinner').hide(250);
	}

	$('#adminpass').strengthify({
		zxcvbn: OC.linkToDirect('../static/js/vendor/zxcvbn/dist/zxcvbn.js'),
		titles: [
			'非常弱 的密码',
			'较弱 的密码',
			'正常 的密码',
			'较强 的密码',
			'非常强 的密码'
		],
		drawTitles: true
	});
});
