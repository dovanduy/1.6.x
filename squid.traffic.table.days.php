<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	
	
	if(isset($_GET["events-table"])){rows();exit;}
	if(isset($_GET["events-table-members"])){rows_members();exit;}
	if($_GET["table"]=="members"){tableau_membres();exit;}
	
	
	tableau();
	
	
function tableau_membres(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");	
	
	$t=time();

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?events-table-members=yes&day={$_GET["day"]}',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'uid', width :112, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width :141, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'client', width :97, sortable : true, align: 'left'},
		{display: '$ComputerMacAddress', name : 'MAC', width :107, sortable : true, align: 'left'},
		{display: '$hits', name : 'thits', width :55, sortable : true, align: 'center'},
		{display: '$size', name : 'tsize', width :55, sortable : true, align: 'center'},
		
		
	],
	

	searchitems : [
		{display: '$hostname', name : 'hostname'},
		{display: '$ipaddr', name : 'client'},
		{display: '$ComputerMacAddress', name : 'MAC'},
		{display: '$member', name : 'uid'},
		],
	sortname: 'thits',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 661,
	height: 250,
	singleSelect: true
	
	});   
});
	
</script>";
	
	echo $html;	
	
}	

	
function tableau(){
	$page=CurrentPageName();
	$tpl=new templates();
	$website=$tpl->_ENGINE_parse_body("{website}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");	
	$category=$tpl->_ENGINE_parse_body("{category}");
	$t=time();

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?events-table=yes&day={$_GET["day"]}',
	dataType: 'json',
	colModel : [
		{display: '$website', name : 'sitename', width :260, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width :177, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width :55, sortable : true, align: 'center'},
		{display: '$size', name : 'size', width :55, sortable : true, align: 'center'},
		{display: 'graph', name : 'aaa', width :41, sortable : false, align: 'center'},
		
	],
	

	searchitems : [
		{display: '$website', name : 'sitename'},
		{display: '$category', name : 'category'},
		],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 667,
	height: 350,
	singleSelect: true
	
	});   
});
	
</script>";
	
	echo $html;	
	
}

function rows_members(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$day=$_GET["day"];
	$time=strtotime("$day 00:00:00");	
	$table=date("Ymd",$time)."_hour";
	$textcss="<span style='font-size:12px'>";
	$search='%';
	$page=1;
	$total=0;
	
	
	if($q->COUNT_ROWS($table,"artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="HAVING (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT SUM(size) as tsize, SUM(hits) as thits,client,uid,MAC,hostname FROM `$table` GROUP BY client,uid,MAC,hostname $searchstring";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}else{
		$sql="SELECT SUM(size) as tsize, SUM(hits) as thits,client,uid,MAC,hostname FROM GROUP BY client,uid,MAC,hostname `$table`";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT SUM(size) as tsize, SUM(hits) as thits,client,uid,MAC,hostname FROM `$table` GROUP BY client,uid,MAC,hostname $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$jsweb="
		<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite={$ligne["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:12px;text-decoration:underline'>";
		
		$jsjscat="Loadjs('squid.categorize.php?www={$ligne["sitename"]}&day={$_GET["day"]}&week=&month=');";
		$jscat="<a href=\"javascript:blur()\"
		OnClick=\"javascript:$jsjscat\"
		style='font-size:12px;text-decoration:underline'>
		";

		

		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		$uid=ahref_member("uid",$ligne["uid"],$table);
		if($ligne["uid"]==null){
			$ligne["uid"]=$q->UID_FROM_MAC($ligne["MAC"]);
			$uid=$ligne["uid"];
		}
		
		
		
		
	$data['rows'][] = array(
		'id' => $ligne['MAC'],
		'cell' => array(
				$textcss.$uid."</a></span>",
				$textcss.ahref_member("hostname",$ligne["hostname"],$table)."</a></span>",
				$textcss.ahref_member("client",$ligne["client"],$table)."</span>",
				$textcss.ahref_member("MAC",$ligne["MAC"],$table)."</span>",
				$textcss.$ligne["thits"]."</span>",
				$textcss.$ligne["tsize"]."</span>",
				
				
				)
		);
	}
	
	
echo json_encode($data);	
}

function ahref_member($field,$data,$table){
	
return "<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.day.user.php?user=$data&field=$field&table=$table');\"
		style='font-size:12px;text-decoration:underline'>$data
		";	
}

function rows(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$day=$_GET["day"];
	$time=strtotime("$day 00:00:00");	
	$table=date("Ymd",$time)."_hour";
	
	$search='%';
	$page=1;
	$total=0;
	
	
	if($q->COUNT_ROWS($table,"artica_events")==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="HAVING (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT SUM(size) as size, SUM(hits) as hits,sitename,category FROM `$table` GROUP BY sitename,category $searchstring";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}else{
		$sql="SELECT SUM(size) as size, SUM(hits) as hits,sitename,category FROM GROUP BY sitename,category `$table`";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);
		
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT SUM(size) as size, SUM(hits) as hits,sitename,familysite,category FROM `$table` GROUP BY sitename,category $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	
	$textcss="<span style='font-size:12px'>";
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$jsweb="
		<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite={$ligne["familysite"]}&day={$_GET["day"]}')\"
		style='font-size:12px;text-decoration:underline'>";
		
		$jsjscat="Loadjs('squid.categorize.php?www={$ligne["sitename"]}&day={$_GET["day"]}&week=&month=');";
		$jscat="<a href=\"javascript:blur()\"
		OnClick=\"javascript:$jsjscat\"
		style='font-size:12px;text-decoration:underline'>
		";
		
				
		$category=$textcss.$jscat.$ligne["category"]."</a></span>";
		
		
		$linkbycat=imgsimple("arrow-blue-left-24.png",null,
			"Loadjs('squid.traffic.statistics.days.category.php?day={$_GET["day"]}&category=".urlencode($ligne["category"])."')");
		
		
		$tb=array();
		if(strpos($ligne["category"], ",")>0){
			$category=null;
			$tb=explode(",",$ligne["category"]);
			while (list ($num, $val) = each ($tb)){
				$jscat="<a href=\"javascript:blur()\"
				OnClick=\"javascript:Loadjs('squid.categories.php?js-popup-master=yes&category=$val&search={$ligne["sitename"]}&strictSearch=0');\"
				style='font-size:12px;text-decoration:underline'>
				";				
				$category=$category.$textcss.$jscat.$val."</a></span><br>";
			}
		}
		

		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		
	$data['rows'][] = array(
		'id' => $ligne['sitename'],
		'cell' => array(
				$textcss.$jsweb.$ligne["sitename"]."</a></span>",
				$category,
				$textcss.$ligne["hits"]."</span>",
				$textcss.$ligne["size"]."</span>",$linkbycat
				
				
				)
		);
	}
	
	
echo json_encode($data);		
}	

