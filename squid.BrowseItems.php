<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}


if(isset($_GET["popup"])){page();exit;}
if(isset($_GET["groups-list"])){group_list();exit;}


js();



function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{members}::{$_GET["type"]}");
	$html="YahooWinBrowse('600','$page?popup=yes&field={$_GET["field"]}&type={$_GET["type"]}','$title')";
	echo $html;
	}



function page(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$mac=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	
	$t=time();		

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?groups-list=yes&field={$_GET["field"]}&type={$_GET["type"]}',
	dataType: 'json',
	colModel : [
		{display: '$ipaddr', name : 'ipaddr', width : 112, sortable : true, align: 'left'},
		{display: '$mac', name : 'MAC', width : 117, sortable : true, align: 'left'},
		{display: '$members', name : 'uid', width : 240, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 31, sortable : false, align: 'left'},
		
	],
	searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$mac', name : 'MAC'},
		{display: '$members', name : 'uid'},
		],
	sortname: 'ipaddr',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 570,
	height: 350,
	singleSelect: true
	
	});   
});

</script>
	
	";
	
	echo $html;
	
}

function group_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="(SELECT MAC,ipaddr,uid FROM UserAutDB GROUP BY MAC,ipaddr,uid) as t";
	$page=1;

	if($q->COUNT_ROWS("UserAutDB")==0){json_error_show("No data in database");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table  WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show($q->mysql_error);}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data");}
	

	$aclss=new squid_acls_groups();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		
		$valueSelect=$ligne[$_GET["type"]];
		$select=imgsimple("arrow-right-24.png","","YahooWinBrowseHide();document.getElementById('{$_GET["field"]}').value='$valueSelect'");
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}'"));
	$data['rows'][] = array(
		'id' => "group{$ligne['ID']}",
		'cell' => array("
		<span style='font-size:14px;'>{$ligne['ipaddr']}</span>",
		"<span style='font-size:14px;'>{$ligne['MAC']}</span>",
		"<span style='font-size:14px;'>{$ligne['uid']}</span>",
	
	$select)
		);
	}
	
	
	echo json_encode($data);	
}


