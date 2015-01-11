<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.milter.greylist.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.maincf.multi.inc');
	$user=new usersMenus();
	
	if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}
	
	if(!isset($_GET["hostname"])){
		if($user->AsPostfixAdministrator==false){header('location:users.index.php');exit();}
	}else{
		if(!PostFixMultiVerifyRights()){
			$tpl=new templates();
			echo "alert('". $tpl->javascript_parse_text("{$_GET["hostname"]}::{ERROR_NO_PRIVS}")."');";
			die();exit();
		}
	}

	
	if(isset($_GET["search"])){popup_list();exit;}
	if(isset($_POST["empty-db"])){empty_db();exit;}
	popup();


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$explain=$tpl->_ENGINE_parse_body("{MILTERGREYLIST_STATUSDUMP_TEXT}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->_ENGINE_parse_body("{to}");
	$whitelisted=$tpl->_ENGINE_parse_body("{whitelisted}");
	if(!isset($_GET["hostname"])){$_GET["hostname"]="master";}
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	$empty=$tpl->javascript_parse_text("{empty}");
	$mgreylist_empty_db_warn=$tpl->javascript_parse_text("{mgreylist_empty_db_warn}");
	
$buttons="buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyDB$t},
	{separator: true},
	],	";	
	

if($explain<>null){$explain="<div class=text-info style='font-size:13px'>$explain</div>";}	
$html="
$explain
<center>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</center>	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?search=yes&hostname={$_GET["hostname"]}',
	dataType: 'json',
	colModel : [
		{display: '$time', name : 'stime', width : 122, sortable : false, align: 'left'},	
		{display: '$ipaddr', name : 'ip_addr', width :108, sortable : true, align: 'left'},
		{display: '$from', name : 'mailfrom', width :225, sortable : true, align: 'left'},
		{display: '$to', name : 'mailto', width : 220, sortable : true, align: 'left'},
		{display: '$whitelisted', name : 'whitelisted', width : 25, sortable : true, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$ipaddr', name : 'ip_addr'},
		{display: '$from', name : 'mailfrom'},
		{display: '$to', name : 'mailto'},
		],
	sortname: 'stime',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 780,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

		var x_EmptyDB$t= function (obj) {
			var results=obj.responseText;
			if(results.length>5){alert(results);}
			RefreshTab('main_config_mgreylist');
		}

function EmptyDB$t(){
	if(confirm('$mgreylist_empty_db_warn')){
		var XHR = new XHRConnection();
		XHR.appendData('empty-db','yes');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		AnimateDiv('flexRT$t');
		XHR.sendAndLoad('$page', 'POST',x_EmptyDB$t);		
	
	}

}

</script>

";
echo $html;	
}


function popup_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="greylist_turples";
	$database="artica_events";
	$page=1;
	$FORCE_FILTER="AND hostname='{$_GET["hostname"]}'";
	
	if($q->COUNT_ROWS($table,$database)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
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
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
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
		if(trim($ligne["ip_addr"])=="#"){continue;}	
		if($ligne["mailfrom"]=="Summary:"){continue;}
		$time=date("Y-m-d H:i:s",$ligne["stime"]);			
		$whitelisted="&nbsp;";
		if($ligne["whitelisted"]==1){$whitelisted="<img src='img/20-check.png'>";}		
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array("<span style='font-size:13px'>$time</span>"
		,"<span style='font-size:13px'>{$ligne["ip_addr"]}</span>",
		"<span style='font-size:13px'>{$ligne["mailfrom"]}</span>",
		"<span style='font-size:13px'>{$ligne["mailto"]}</span>",
		$whitelisted )
		);
	}
	
	
echo json_encode($data);		

}

function empty_db(){
	$q=new mysql();
	$hostname=$_POST["hostname"];
	
	$sql="DELETE FROM greylist_turples WHERE hostname='$hostname'";
	$q->QUERY_SQL($sql,"artica_events");
	$sock=new sockets();
	$sock->getFrameWork("milter-greylist.php?empty-database=yes&hostname=$hostname");
		
}
