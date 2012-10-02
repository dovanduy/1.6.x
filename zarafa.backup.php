<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
if(isset($_GET["params"])){params();exit;}

popup();
	
	
function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=710;
	$from=$tpl->_ENGINE_parse_body("{sender}");
	$subject=$tpl->_ENGINE_parse_body("{subject}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$choose_day=$tpl->_ENGINE_parse_body("{choose_date}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$title=$tpl->_ENGINE_parse_body("{backup}");
	$parms="{name: '$parameters', bclass: 'Settings', onpress : Params$t},";
	
$buttons="buttons : [
		$choose_day_bt
			],	";	
	

	
//ztime 	zhour 	mailfrom 	instancename 	mailto 	domainfrom 	domainto 	senderhost 	recipienthost 	mailsize 	smtpcode 	
	$html="
	<div id='query-explain'></div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?byday-items=yes&t=$t&day=$today$byMonth',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :106, sortable : true, align: 'left'},	
		{display: '$file', name : 'filepath', width :152, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width :152, sortable : true, align: 'left'},
		{display: '$time', name : 'ztime', width :152, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'mailsize', width :103, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'mailsize', width :103, sortable : true, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$date', name : 'zDate'},
		{display: '$file', name : 'filepath'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=\"title-$t\">$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 940,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function Params$t(){
	YahooWin2('650','$page?params=yes&t=$t','$parameters');

}

</script>";
	
	echo $html;	
}