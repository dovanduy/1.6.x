<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["force"])){$GLOBALS["FORCE"]=true;}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["filemd5-js"])){zoom_js();exit;}
if(isset($_GET["filemd5"])){zoom_popup();exit;}


table();


function zoom_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	echo "s_PopUpFull('$page?filemd5={$_GET["filemd5-js"]}',1024,650,'{$_GET["filemd5-js"]}')";
}

function zoom_popup(){
	$q=new mysql_squid_builder();
	$sql="SELECT report FROM squeezer WHERE filemd5='{$_GET["filemd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$report=$ligne["report"];
	preg_match("#<head>(.*?)<\/head>#is", $report,$re);
	$report=str_replace($re[1], $re[1]."<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />", $report);
	echo $report;
	
}


function table(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$CORP=0;
	$date_from=$tpl->_ENGINE_parse_body("{from}");
	$date_to=$tpl->_ENGINE_parse_body("{to}");
	$limit=$tpl->_ENGINE_parse_body("{limit}");
	$add_new_cached_web_site=$tpl->_ENGINE_parse_body("{add_new_cached_web_site}");
	$add_default_settings=$tpl->_ENGINE_parse_body("{add_default_settings}");
	$refresh_pattern_intro=$tpl->_ENGINE_parse_body("{refresh_pattern_intro}");
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($_SESSION["CORP"]){$CORP=1;}
	$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}
	if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->javascript_parse_text("{options}");
	$performance_reports=$tpl->javascript_parse_text("{performance_reports}");

	
$html="<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>

$(document).ready(function(){
	$('#flexRT$t').flexigrid({
		url: '$page?search=yes&t=$t',
		dataType: 'json',
		colModel : [
			{display: '$date_from', name : 'datefrom', width : 405, sortable : true, align: 'left'},	
			{display: '$date_to', name : 'dateto', width : 405, sortable : true, align: 'left'},
		],
		
		searchitems : [
			{display: '$date_from', name : 'datefrom'},
			{display: '$date_to', name : 'dateto'}
			],		
		
		sortname: 'dateto',
		sortorder: 'desc',
		usepager: true,
		title: '<strong style=font-size:18px>$performance_reports</strong>',
		useRp: true,
		rp: 100,
		showTableToggleBtn: false,
		width: '99%',
		height: 400,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
		
	});   
});
</script>";

echo $html;


}


function search(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$database="artica_backup";
	$sock=new sockets();
	
	
	$search='%';
	$table="squeezer";
	$page=1;
	$FORCE_FILTER=null;
	
	if($q->COUNT_ROWS($table,$database)==0){
		json_error_show("no data");
		return ;
	}
	
	if(!$q->TABLE_EXISTS($table,$database)){
		json_error_show("no report");
		return ;
	}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	if(!is_numeric($rp)){$rp=1;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		json_error_show($q->mysql_error);
	}	
	
	if(mysql_num_rows($results)==0){
		json_error_show("no data");
	}
	
	$fontsize=18;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["filemd5"];
		$color="black";
		
		$select="s_PopUpFull('$MyPage?filemd5=$ID',1600,650,'$ID')";
		
		
		
		$link="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$select\" 
		style='font-size:{$fontsize}px;text-decoration:underline;color:$color'>";
		
		$ftime=strtotime($ligne["datefrom"]);
		$stime=strtotime($ligne["dateto"]);
		
		$fftime=$q->time_to_date($ftime,true);
		$sstime=$q->time_to_date($stime,true);
		
		
	$data['rows'][] = array(
		'id' => $ID,
		'cell' => array(
		"<span style='font-size:{$fontsize}px;color:$color'>$link{$fftime}</a></span>"
		,"<span style='font-size:{$fontsize}px;color:$color'>$link{$sstime}</a></span>",)
		);
	}
	
	
echo json_encode($data);		
	
}
