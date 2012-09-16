<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["change-day-popup"])){change_day_popup();exit;}

	
	page();

	
function page(){
	
	$hour_table=date('Ymd')."_hour";
	$q=new mysql_squid_builder();
	$defaultday=$q->HIER();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$title=$tpl->_ENGINE_parse_body("{today}: {requests} {since} ".date("H")."h");
	$change_day=$tpl->_ENGINE_parse_body("{change_day}");
	$t=time();
	$html="
	<input type='hidden' id='daycache$t' value='$defaultday'>
	<div style='margin:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&MAC={$_GET["MAC"]}',
	dataType: 'json',
	colModel : [
		{display: '$rule', name : 'hour', width :60, sortable : true, align: 'left'},
		{display: '$country', name : 'country', width : 70, sortable : false, align: 'left'},
		{display: '$webservers', name : 'sitename', width : 282, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 73, sortable : true, align: 'left'},
		{display: 'hits', name : 'hits', width : 60, sortable : true, align: 'left'}

		],
		
buttons : [
		{name: '$change_day', bclass: 'add', onpress : ChangeDay},
		],			
	
	searchitems : [
		{display: '$webservers', name : 'sitename'},
		],
	sortname: 'hour',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 625,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


function ChangeDay(){
	YahooWin6('375','$page?change-day-popup=yes&t=$t&MAC={$_GET["MAC"]}','$change_day');
}

</script>
	
	
	";
	
	echo $html;
}