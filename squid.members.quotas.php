<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}	
	if(isset($_GET["list-day"])){table_list_day();die();}
	if(isset($_GET["change-day"])){change_day_popup();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	
	$member=$tpl->_ENGINE_parse_body("{member}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$day=$tpl->_ENGINE_parse_body("{day}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$month=$tpl->_ENGINE_parse_body("{month}");
	$MAC=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$TB_WIDTH=550;
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$day</b>', bclass: 'Calendar', onpress : ChangeDay$t},
	
		],";		
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?list-day=yes&day={$_GET["day"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width : 133, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width : 96, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width : 242, sortable : true, align: 'left'},
		{display: '$MAC', name : 'MAC', width : 119, sortable : false, true: 'left'},
		{display: '$size', name : 'size', width : 101, sortable : false, true: 'left'},
		
		
	],$buttons
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: 'TCP/IP', name : 'ipaddr'},
		{display: '$hostname', name : 'hostname'},
		{display: '$MAC', name : 'MAC'},
		],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '$members&raquo;{$_GET["day"]}',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 842,
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

function change_day_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$t=$_GET["t"];
	$members=$tpl->_ENGINE_parse_body("{members}&raquo;");
	
	$day=$tpl->_ENGINE_parse_body("{day}");	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne["tdate"];

	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$maxdate=date('Y-m-d');
	
	$html="
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap style='font-size:16px'>{from_date}:</td>
		<td>". field_date("SdateMember-$t",$_GET["day"],"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>	
		<td>". button("{go}","DayMemberChangeDate$t()",16)."</td>
	</tr>
	</tbody>
	</table>
	
	<script>
		function DayMemberChangeDate$t(){
			var xday=document.getElementById('SdateMember-$t').value;
			$('.ftitle').html('$members&raquo;$day:'+xday);
			$('#$t').flexOptions({url: '$page?list-day=yes&day='+xday,title:'$members'+xday}).flexReload();
		
		}
	
	</script>";

	echo $tpl->_ENGINE_parse_body($html);
	
}

function table_list_day(){
	
	$q=new mysql_squid_builder();	
	$search=trim($_GET["search"]);
	$dayfull="{$_GET["day"]} 00:00:00";
	$date=strtotime($dayfull);
	$table="UserSizeD_".date("Ymd",$date);
	
	$tpl=new templates();
	
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table, no such table",1);}
	if($q->COUNT_ROWS($table)==0){json_error_show("Table empty",1);}
	
	$table="(SELECT uid,ipaddr,hostname,account,MAC,SUM(size) as size FROM `$table` GROUP BY uid,ipaddr,hostname,account,MAC) as t";
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="hour";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
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
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["uid"]==null){$ligne["uid"]="&nbsp;";}
		$md5=md5(@implode(" ", $ligne));
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:14px'>$uid</span>",
			"<span style='font-size:14px'>{$ligne["ipaddr"]}$category</span>",
			"<span style='font-size:14px'>{$ligne["hostname"]}$category</span>",
			"<span style='font-size:14px'>{$ligne["MAC"]}</span>",
			"<span style='font-size:14px'>{$ligne["size"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);		
}	