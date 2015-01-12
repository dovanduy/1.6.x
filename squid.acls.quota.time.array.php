<?php
$GLOBALS["DBPATH"]="/var/log/squid/QUOTADB.db";
	if($argv[1]=="--popup"){
		echo "POPUP\n";
		$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
		popup();
		exit;
	}
	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->javascript_parse_text('{ERROR_NO_PRIVS}');
		header("content-type: application/x-javascript");
		echo "<script>alert('$alert')</script>";
		die();	
	}
	
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["items-list"])){table_list();exit;}	
if(isset($_POST["DeleteQuotaKey"])){DeleteQuotaKey();exit;}
js();



function js(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{quota_time}");
	header("content-type: application/x-javascript");
	echo "YahooWin2(1200,'$page?popup=yes','$title');";

}


function popup(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$date_start=$tpl->_ENGINE_parse_body("{date_start}");
	$quota=$tpl->_ENGINE_parse_body("{quota_time}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$wait=$tpl->javascript_parse_text("{wait}");
	$status=$tpl->javascript_parse_text("{status}");
	$all=$tpl->javascript_parse_text("{all}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$website=$tpl->javascript_parse_text("{website}");
	$t=$_GET["t"];
	

	
	
	$buttons="buttons : [
		{name: '$new_item', bclass: 'add', onpress : LinkAclItem},
		{name: '$new_group', bclass: 'add', onpress : LinkAddAclItem},
		],";
	
	$html="
		<table class='QUOTA_TIME_ITEMS' style='display: none' id='QUOTA_TIME_ITEMS' style='width:99%'></table>
		<script>
		var DeleteAclKey=0;
		function LoadTable$t(){
		$('#QUOTA_TIME_ITEMS').flexigrid({
		url: '$page?items-list=yes&ID=$ID&t=$t&aclid={$_GET["aclid"]}',
		dataType: 'json',
		colModel : [
		{display: '$status', name : 'status', width :50, sortable : true, align: 'center'},
		
		{display: '$date_start', name : 'date_start', width :142, sortable : true, align: 'left'},
		{display: '$website', name : 'date_start', width :196, sortable : true, align: 'left'},
		{display: '$wait', name : 'wait', width :112, sortable : true, align: 'left'},
		{display: '$quota', name : 'gpid', width : 112, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'ipaddr', width : 133, sortable : false, align: 'left'},
		{display: '$MAC', name : 'MAC', width : 133, sortable : false, align: 'left'},
		{display: '$uid', name : 'uid', width : 133, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'up', width :36, sortable : false, align: 'center'},

	
		],
		
		searchitems : [
		{display: '$all', name : 'GroupName'},
		],
		sortname: 'zOrder',
		sortorder: 'asc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 15,
		showTableToggleBtn: false,
		width: '99%',
		height: 350,
		singleSelect: true
	
	});
	}

	
var x_DeleteQuotaKey= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#QUOTA_TIME_ITEMS').flexReload();

}	
	
function DeleteQuotaKey(mkey){
	DeleteAclKey=mkey;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteQuotaKey', mkey);
	XHR.sendAndLoad('$page', 'POST',x_DeleteQuotaKey);
}
LoadTable$t();
	</script>
	
	";
	
	echo $html;
	
}

function DeleteQuotaKey(){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$db_con = dba_open($GLOBALS["DBPATH"], "c","db4");
	if(!$db_con){echo "Unable to open database";return;}
	
	if(!dba_delete ($_POST["DeleteQuotaKey"] , $db_con)){
		echo "Delete failed\n";
	}
	
}


function table_list(){
	
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$db_con = dba_open($GLOBALS["DBPATH"], "r","db4");
	if(!$db_con){json_error_show("DB open failed",1);}
	
	
	
	$page=1;
	$rp=$_POST["rp"];
	
	
	if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexregex();

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = 0;
	$data['rows'] = array();
	$mainkey=dba_firstkey($db_con);
	$c=0;
	
	$span="<span style='font-size:14px;'>";
	while($mainkey !=false){
		$val=0;
		$status="<img src='img/ok24.png'>";
		$dataZ=dba_fetch($mainkey,$db_con);
		
		if($searchstring<>null){
			if(!preg_match("#$searchstring#", $dataZ)){
				$mainkey=dba_nextkey($db_con);
				continue;
			}
		}
		$array=unserialize($dataZ);
		$c++;
		$delete=imgsimple("delete-24.png",null,"DeleteQuotaKey('$mainkey')");
		$start=date("Y-m-d H:i:s",$array["START"]);
	

	
	
	if(isset($array["WAIT_START_TIME"])){
		$wait=xtime_passed_min($array["WAIT_START_TIME"],time())."Mn";
	}else{
		$wait="-";
	}
	$quota=$array["TIME"];
	$website=$array["website"];
	if(!is_numeric($quota)){$quota=0;}
	$ipaddr=$array["ipaddr"];
	$MAC=$array["MAC"];
	$username=$array["username"];
	if($array["LOCK"]==true){
		$status="<img src='img/24-red.png'>";
	}
	$data['rows'][] = array(
		'id' => "$mainkey",
		'cell' => array(
			$status,"$span$start</span>",
				"$span{$website}</span>",
				"$span{$wait}</span>",
				"$span{$quota}Mn</span>",
				"$span$ipaddr</span>",
				"$span$MAC</span>","$span$username</span>",$delete
			)
	);
	
	$mainkey=dba_nextkey($db_con);
	}
	dba_close($db_con);
	$data['total']=$c;
	echo json_encode($data);	


}
function xtime_passed_min($StartTime=0,$EndTime=0){
	$difference = ($EndTime - $StartTime);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}	
	
	
