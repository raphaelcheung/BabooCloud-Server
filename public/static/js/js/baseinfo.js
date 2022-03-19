
var cloud_base;
var cloud_user;

//console.log($('#baseinfo').val());
var tmp = eval('(' + $('#baseinfo').val() + ')');
cloud_base = tmp.cloudbase;
cloud_user = tmp.user;
var cloud_token = $('#token').val();
