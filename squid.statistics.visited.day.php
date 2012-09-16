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
	
	if(isset($_GET["history-list"])){web_list();exit;}
	
page();	
	
function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$website=$tpl->_ENGINE_parse_body("{website}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$familysite=$tpl->_ENGINE_parse_body("{familysite}");
	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?history-list=yes&day={$_GET["day"]}&table={$_GET["table"]}',
	dataType: 'json',
	colModel : [
		{display: '$website', name : 'sitename', width :227, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'familysite', width : 187, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width : 85, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width : 64, sortable : true, align: 'left'},
		],
	searchitems : [
		{display: '$website', name : 'sitename'},
		{display: '$familysite', name : 'familysite'},
		],	

	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 630,
	height: 418,
	singleSelect: true
	
	});   
});
</script>

";	

	
	echo $tpl->_ENGINE_parse_body($html);

}	
	
function web_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table=$_GET["table"];
	$page=1;
	
	
	if($q->COUNT_ROWS($table,"artica_backup")==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = 0;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		if(strpos(" {$_POST["query"]}", "*")>0){
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		}else{
			$searchstring="AND (`{$_POST["qtype"]}` = '$search')";
		}
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$total = $q->COUNT_ROWS($table,"artica_backup");
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
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
		$md5=md5(@implode(" ", $ligne));
		$size=FormatBytes($ligne["size"]/1024);
		
		$linkfamily="
		<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=size&familysite={$ligne["familysite"]}&day={$_GET["day"]}')\"
		style=\"font-size:13px;text-decoration:underline;\">
		";
		
		$linkwebsite="
		<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.hours.php?familysite={$ligne["sitename"]}&day={$_GET["day"]}')\"
		style=\"font-size:13px;text-decoration:underline;\">";
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("<span style='font-size:13px'>$linkwebsite{$ligne["sitename"]}</a></span>"
		,"<span style='font-size:13px'>$linkfamily{$ligne["familysite"]}</a></span>",
		"<span style='font-size:13px'>$size</a></span>",
		"<span style='font-size:13px'>{$ligne["hits"]}</a></span>",
		$delete )
		);
	}
	
	
echo json_encode($data);		

}
