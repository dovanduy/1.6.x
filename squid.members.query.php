<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}	
	if(isset($_GET["search"])){search();exit;}

	if(isset($_GET["change-week"])){change_week_popup();exit;}
	if(isset($_GET["change-month"])){change_month_popup();exit;}
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	if($_GET["day"]==null){$_GET["day"]=$q->HIER();}		
	$member=$tpl->_ENGINE_parse_body("{member}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$mac=$tpl->_ENGINE_parse_body("{MAC}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$month=$tpl->_ENGINE_parse_body("{month}");
	$TB_WIDTH=550;
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$week</b>', bclass: 'Calendar', onpress : ChangeWeek$t},
	{name: '<b>$month</b>', bclass: 'Calendar', onpress : ChangeMonth$t},
	
		],";		
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?search=yes',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width : 120, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width : 120, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width : 167, sortable : true, align: 'left'},
		{display: '$mac', name : 'MAC', width : 139, sortable : true, align: 'left'},
		{display: '$size', name : 'QuerySize', width : 132, sortable : false, true: 'left'},
		{display: '$hits', name : 'hits', width : 101, sortable : false, true: 'left'},
		
		
	],$buttons
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: 'TCP/IP', name : 'ipaddr'},
		{display: '$hostname', name : 'hostname'},
		{display: '$mac', name : 'MAC'},
		],
	sortname: 'QuerySize',
	sortorder: 'desc',
	usepager: true,
	title: '$members&raquo;',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 872,
	height: 450,
	singleSelect: true
	
	});
});

function RefreshNodesSquidTbl(){
	$('#$t').flexReload();
}

function ChangeDay$t(){
	YahooWin(400,'$page?change-day=yes&t=$t','$day');
}

function ChangeWeek$t(){
	YahooWin(624,'$page?change-week=yes&t=$t','$week');
}
function ChangeMonth$t(){
	YahooWin(400,'$page?change-month=yes&t=$t','$month');
}
</script>";
	
	echo $html;
	
	
}

function change_week_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$t=$_GET["t"];
	$members=$tpl->_ENGINE_parse_body("{members}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$array=array();
	$tables=$q->LIST_TABLES_WEEKS();
	while (list ($index, $tablez) = each ($tables) ){
		if($q->COUNT_ROWS($tablez)==0){continue;}
		$array[$tablez]=$q->WEEK_TITLE_FROM_TABLENAME($tablez);
	}
	
	$field=Field_array_Hash($array,"table-query-$t",$table," DayMemberChangeWeekPanel$t()",null,0,"font-size:14px");
	$array=array();
	
	$html="
	<table style='width:99%' class=form>
		<tbody>
			<tr>
				<td class=legend style='font-size:16px;'>{week}:</td>
				<td>$field</td>
			</tr>
	</table>

	<script>
		function DayMemberChangeWeekPanel$t(){
			var xday=document.getElementById('table-query-$t').value;
			$('.ftitle').html('$members&raquo;$week&raquo;table:'+xday);
			$('#$t').flexOptions({url: '$page?search=yes&table='+xday,title:'$members'+xday}).flexReload();
		
		}
	
	</script>";
			echo $tpl->_ENGINE_parse_body($html);
	
}

function change_month_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$t=$_GET["t"];
	$members=$tpl->_ENGINE_parse_body("{members}");
	$month=$tpl->_ENGINE_parse_body("{month}");	
	$array=array();
	$tables=$q->LIST_TABLES_MONTH();
	while (list ($index, $tablez) = each ($tables) ){
		$array[$tablez]=$q->MONTH_TITLE_FROM_TABLENAME($tablez);
	}
	
$field=Field_array_Hash($array,"table-m-$t",$table," DayMemberChangeMonthPanel$t()",null,0,"font-size:14px");
	$array=array();
	
	$html="
	<table style='width:99%' class=form>
		<tbody>
			<tr>
				<td class=legend style='font-size:16px;'>{month}:</td>
				<td>$field</td>
			</tr>
	</table>

	<script>
		function DayMemberChangeMonthPanel$t(){
			var xday=document.getElementById('table-m-$t').value;
			$('.ftitle').html('$members&raquo;$month&raquo;table:'+xday);
			$('#$t').flexOptions({url: '$page?search=yes&table='+xday,title:'$members'+xday}).flexReload();
		
		}
	
	</script>";
			echo $tpl->_ENGINE_parse_body($html);
	
}

function search(){
	
	$q=new mysql_squid_builder();	
	$search=trim($_GET["search"]);
	$dayfull="{$_GET["day"]} 00:00:00";
	$date=strtotime($dayfull);
	$table="UserAuthDaysGrouped";
	if(isset($_GET["table"])){
		$table="{$_GET["table"]}";
		if(preg_match("#_(week|day)#", $table)){
			
			$table="(SELECT client as ipaddr,hostname,MAC,uid,SUM(size) as QuerySize,SUM(hits) as hits FROM $table
			GROUP BY ipaddr,hostname,MAC) as t";
		}
		
	}
	
	$tpl=new templates();
	$daysuffix=$tpl->_ENGINE_parse_body(date("{l} d",$date));
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	if(!isset($_GET["table"])){
		if($q->COUNT_ROWS($table)==0){json_error_show("Table empty");}
	}
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
		
	if($searchstring<>null){	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){json_error_show("$q->mysql_error");}
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(isset($_GET["table"])){$jsaddtable="&table={$_GET["table"]}";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=md5(serialize($line));
		$ligne["QuerySize"]=FormatBytes($ligne["QuerySize"]/1024);
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		
		$jsuid="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.zoom.php?field=uid&value={$ligne["uid"]}$jsaddtable')\"
		style='font-size:16px;text-decoration:underline'>";
		
		$jsipaddr="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.zoom.php?field=ipaddr&value={$ligne["ipaddr"]}$jsaddtable')\"
		style='font-size:16px;text-decoration:underline'>";

		$jshostname="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.zoom.php?field=hostname&value={$ligne["hostname"]}$jsaddtable')\"
		style='font-size:16px;text-decoration:underline'>";

		$jsMAC="
		<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('squid.members.zoom.php?field=MAC&value={$ligne["MAC"]}$jsaddtable')\"
		style='font-size:16px;text-decoration:underline'>";		
		

	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:16px'>$jsuid{$ligne["uid"]}</a></span>",
			"<span style='font-size:16px'>$jsipaddr{$ligne["ipaddr"]}</a></span>",
			"<span style='font-size:16px'>$jshostname{$ligne["hostname"]}</a></span>",
			"<span style='font-size:16px'>$jsMAC". strtoupper($ligne["MAC"])."</a></span>",
			"<span style='font-size:16px'>{$ligne["QuerySize"]}</span>",
			"<span style='font-size:16px'>{$ligne["hits"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);		
}



