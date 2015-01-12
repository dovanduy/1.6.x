<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.tcpip.inc');
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
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{day}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$title=$tpl->_ENGINE_parse_body("{today}: {requests} {since} ".date("H")."h");
	$change_day=$tpl->_ENGINE_parse_body("{change_day}");
	$this_week=$tpl->javascript_parse_text("{this_week}");
	$title="$this_week {$_GET["MAC"]} {$_GET["ipaddr"]}";
	
	$t=time();
	$html="
	<input type='hidden' id='daycache$t' value='$defaultday'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&day=$defaultday&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'day', width :301, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 204, sortable : true, align: 'left'},
		],
		
			
	
	searchitems : [
		{display: '$time', name : 'day'},
		],
	sortname: 'day',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});


function ChangeDay(){
	YahooWin6('375','$page?change-day-popup=yes&t=$t&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}','$change_day');
}

</script>
	
	
	";
	
	echo $html;
}

function change_day_popup(){
	$q=new mysql_squid_builder();	
	$tpl=new templates();
	$t=$_GET["t"];
	$page=CurrentPageName();
	
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
			<td class=legend nowrap>{from_date}:</td>
			<td>". field_date("sdate$t",$_GET["day"],"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>	
			<td>". button("{go}","ChangeDay$t()",18)."</td>
		</tr>
		</tbody>
	</table>	
	<script>
		function ChangeDay$t(){
			var zday=document.getElementById('sdate$t').value;
			document.getElementById('daycache$t').value=zday;
			$('#flexRT$t').flexOptions({url: '$page?search=yes&day='+zday+'&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}'}).flexReload(); 
		
		}
		document.getElementById('sdate$t').value=document.getElementById('daycache$t').value;
		
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function search(){
	$Mypage=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();	
	$t=time();
	$fontsize=13;
	$type=$_GET["type"];
	$field_query="size";
	$field_query2="SUM(size)";	
	$table_field="{size}";
	$category=$tpl->_ENGINE_parse_body("{category}");
	$table="WEEK_RTTH";
	$member=$tpl->_ENGINE_parse_body("{member}");
	$sitename=$tpl->_ENGINE_parse_body("{website}");
	
	
	
	$search='%';
	$page=1;
	$ORDER="ORDER BY ID DESC";
	$ip=new IP();
	$Select="MAC";
	$FORCE_FILTER=" AND `MAC`='{$_GET["MAC"]}'";	
	
	if($ip->isIPAddress($_GET["ipaddr"])){
		$Select="ipaddr";
		
		$FORCE_FILTER="ipaddr='{$_GET["ipaddr"]}'";
	}
	
	if($ip->IsvalidMAC($_GET["MAC"])){
		$Select="MAC";
		$FORCE_FILTER="MAC='{$_GET["MAC"]}'";
	}
	
	$table="(SELECT `day`,SUM(size) as size,$Select FROM WEEK_RTTH GROUP BY `day`,$Select HAVING $FORCE_FILTER) as t";
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$search=string_to_flexquery();
	if($search<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT* FROM $table WHERE 1 $search $ORDER $limitSql";
	$results=$q->QUERY_SQL($sql);
	
	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	

	if(!$q->ok){json_error_show($q->mysql_error);}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$data['total'] = mysql_num_rows($results);
	
	$style="style='font-size:22px'";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
		$categorize="Loadjs('squid.categorize.php?www={$ligne["sitename"]}')";
		if(trim($ligne["category"])==null){$ligne["category"]="<span style='color:#D70707'>{categorize_this_website}</span>";}
	
						
		$id=md5(@implode("", $ligne));
		
		if(trim($ligne["uid"])=="-"){$ligne["uid"]=null;}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$q->UID_FROM_MAC($ligne["MAC"]);}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$q->UID_FROM_IP($ligne["CLIENT"]);}
		
		
		$categorize="<a href=\"javascript:blur()\" 
		OnClick=\"javascript:$categorize\" 
		style='font-size:{$fontsize}px;text-decoration:underline'>";
		
		
		$familysite=$q->GetFamilySites($ligne["sitename"]);
		$TrafficHour="<a href=\"javascript:blur()\" 
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.hours.php?familysite={$ligne["sitename"]}&day={$_GET["day"]}')\" 
		style='font-size:{$fontsize}px;text-decoration:underline'>";
		
		$dd=date("Y-m");
		$D=$q->time_to_date(strtotime("$dd-{$ligne["day"]} 00:00:00"));
 		
		
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style>$D</span>",
			"<span $style>{$ligne["size"]}</a></span>",
			)
			);		
		
		
	}

echo json_encode($data);	
		
	
}
