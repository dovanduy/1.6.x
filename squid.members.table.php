<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}	

	if(isset($_GET["list-day"])){table_list_day();exit;}
	if(isset($_GET["list-week"])){table_list_week();exit;}
	if(isset($_GET["list-month"])){table_list_month();exit;}
	
	
	
	if(isset($_GET["change-week"])){change_week_popup();exit;}
	if(isset($_GET["change-day"])){change_day_popup();exit;}
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
	$date=$tpl->_ENGINE_parse_body("{date}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$day=$tpl->_ENGINE_parse_body("{day}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$month=$tpl->_ENGINE_parse_body("{month}");
	$TB_WIDTH=550;
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$day</b>', bclass: 'Calendar', onpress : ChangeDay$t},
	{name: '<b>$week</b>', bclass: 'Calendar', onpress : ChangeWeek$t},
	{name: '<b>$month</b>', bclass: 'Calendar', onpress : ChangeMonth$t},
	
		],";		
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?list-day=yes&day={$_GET["day"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 92, sortable : true, align: 'left'},
		{display: '$member', name : 'uid', width : 238, sortable : true, align: 'left'},
		{display: '$sitename', name : 'sitename', width : 354, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 65, sortable : false, true: 'left'},
		{display: '$hits', name : 'hits', width : 41, sortable : false, true: 'left'},
		
		
	],$buttons
	searchitems : [
		{display: '$member', name : 'uid'},
		{display: 'TCP/IP', name : 'client'},
		{display: '$hostname', name : 'client'},
		{display: '$date', name : 'zDate'},
		{display: '$sitename', name : 'sitename'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$members&raquo;{$_GET["day"]}',
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
			$('#$t').flexOptions({url: '$page?list-week=yes&week='+xday,title:'$members'+xday}).flexReload();
		
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
			$('#$t').flexOptions({url: '$page?list-month=yes&month='+xday,title:'$members'+xday}).flexReload();
		
		}
	
	</script>";
			echo $tpl->_ENGINE_parse_body($html);
	
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
	$table=date("Ymd",$date)."_hour";
	
	$tpl=new templates();
	$daysuffix=$tpl->_ENGINE_parse_body(date("{l} d",$date));
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("Table empty");}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="hour";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos("  $search", "%")>0){
			$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		}else{
			$searchstring="AND (`{$_POST["qtype"]}` = '$search')";
		}
		
		if(preg_match("#(.*?)\s+AND date=([0-9]+)#",$search, $re)){
			if(strpos("  {$re[1]}", "%")>0){
				$searchstring1="(`{$_POST["qtype"]}` LIKE '{$re[1]}')";
			}else{
				$searchstring1="(`{$_POST["qtype"]}` = '{$re[1]}')";
			}
			$searchstring="AND ( (hour={$re[2]}) AND $searchstring1)";
			
		}
		
		
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){json_error_show("$q->mysql_error");}
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["uid"]==null){$ligne["uid"]="-";}
		$member="{$ligne["uid"]} ({$ligne["client"]} [{$ligne["client"]}])";
		$md5=md5(@implode(" ", $ligne));
		$date="{$ligne["hour"]}h";
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$category=null;if(isset($ligne["category"])){$category=" ({$ligne["category"]})";}
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:12px'>$daysuffix&nbsp;$date</span>",
			"<span style='font-size:12px'>$member</span>",
			"<span style='font-size:12px'>{$ligne["sitename"]}$category</span>",
			"<span style='font-size:12px'>{$ligne["size"]}</span>",
			"<span style='font-size:12px'>{$ligne["hits"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);		
}

function table_list_month(){
	$q=new mysql_squid_builder();	
	$search=trim($_GET["search"]);
	$tpl=new templates();
	$table=$_GET["month"];
	
	$dayAR=$q->WEEK_TABLE_TO_MONTH($table);
	
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("Table $table is empty");}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="day";}
			
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos("  $search", "%")>0){
			$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		}else{
			$searchstring="AND (`{$_POST["qtype"]}` = '$search')";
		}
		
		if(preg_match("#(.*?)\s+AND date=([0-9]+)#",$search, $re)){
			if(strpos("  {$re[1]}", "%")>0){
				$searchstring1="(`{$_POST["qtype"]}` LIKE '{$re[1]}')";
			}else{
				$searchstring1="(`{$_POST["qtype"]}` = '{$re[1]}')";
			}
			$searchstring="AND ( (day={$re[2]}) AND $searchstring1)";
			
		}
		
		
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){json_error_show("$q->mysql_error");}
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["uid"]==null){$ligne["uid"]="-";}
		$member="{$ligne["uid"]} ({$ligne["client"]} [{$ligne["client"]}])";
		$md5=md5(@implode(" ", $ligne));
		$DateText=null;
		$date="{$ligne["day"]}";
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$DateText=$tpl->_ENGINE_parse_body($dayAR[$date]);
		if(trim($DateText)==null){$DateText=$tpl->_ENGINE_parse_body("{day} $date");}
		$category=null;if(isset($ligne["category"])){$category=" ({$ligne["category"]})";}
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:12px'>$DateText</span>",
			"<span style='font-size:12px'>$member</span>",
			"<span style='font-size:12px'>{$ligne["sitename"]}$category</span>",
			"<span style='font-size:12px'>{$ligne["size"]}</span>",
			"<span style='font-size:12px'>{$ligne["hits"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function table_list_week(){
	$q=new mysql_squid_builder();	
	$search=trim($_GET["search"]);
	$tpl=new templates();
	$table=$_GET["week"];
	
	$dayAR=$q->WEEK_TABLE_TO_MONTH($table);
	
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("Table $table is empty");}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="day";}
			
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos("  $search", "%")>0){
			$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		}else{
			$searchstring="AND (`{$_POST["qtype"]}` = '$search')";
		}
		
		if(preg_match("#(.*?)\s+AND date=([0-9]+)#",$search, $re)){
			if(strpos("  {$re[1]}", "%")>0){
				$searchstring1="(`{$_POST["qtype"]}` LIKE '{$re[1]}')";
			}else{
				$searchstring1="(`{$_POST["qtype"]}` = '{$re[1]}')";
			}
			$searchstring="AND ( (day={$re[2]}) AND $searchstring1)";
			
		}
		
		
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){json_error_show("$q->mysql_error");}
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		if($ligne["uid"]==null){$ligne["uid"]="-";}
		$member="{$ligne["uid"]} ({$ligne["client"]} [{$ligne["client"]}])";
		$md5=md5(@implode(" ", $ligne));
		$DateText=null;
		$date="{$ligne["day"]}";
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$DateText=$tpl->_ENGINE_parse_body($dayAR[$date]);
		if(trim($DateText)==null){$DateText=$tpl->_ENGINE_parse_body("{day} $date");}
		$category=null;if(isset($ligne["category"])){$category=" ({$ligne["category"]})";}
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:12px'>$DateText</span>",
			"<span style='font-size:12px'>$member</span>",
			"<span style='font-size:12px'>{$ligne["sitename"]}$category</span>",
			"<span style='font-size:12px'>{$ligne["size"]}</span>",
			"<span style='font-size:12px'>{$ligne["hits"]}</span>",
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);	
	
}