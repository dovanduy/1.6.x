<?php
if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	
	$users=new usersMenus();
	if(!$users->AsDansGuardianAdministrator){die();}	
	if(isset($_GET["events"])){popup_list();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["delete"])){Delete();exit;}
	
	js();

	
function js(){
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{whitelist}::{APP_UFDBGUARD}");
	echo "YahooWin4('550','$page?popup=yes&t=$t','$title')";
	
}

function popup(){
	$tt=$_GET["tt"];
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$new=$tpl->_ENGINE_parse_body("{new}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F}")." {blocked_requests}");
	$unblock=$tpl->javascript_parse_text("{unblock}");
	$UnBlockWebSiteExplain=$tpl->javascript_parse_text("{UnBlockWebSiteExplain}");
	$title=$tpl->javascript_parse_text("{whitelist}");
	$squid_ask_domain=$tpl->javascript_parse_text("{squid_ask_domain}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$t=time();
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$webservers', name : 'items', width : 461, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 31, sortable : false, align: 'center'},
		
		],
buttons : [
	{name: '$new', bclass: 'add', onpress : NewWebServer$t},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$t},

		],			
	searchitems : [
		{display: '$webservers', name : 'items'},
		
		],			
		
	sortname: 'items',
	sortorder: 'asc',
	usepager: true,
	useRp: true,
	title: '<span style=\"font-size:14px\">$title</span>',
	rp: 50,
	showTableToggleBtn: false,
	width: 530,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]
	
	});   
});

	var x_Delete$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      $('#row'+mem$t).remove();
	}	
	
	var x_reload$t=function(obj){
		var tempvalue=obj.responseText;
	     if(tempvalue.length>3){alert(tempvalue);return;}
		 $('#flexRT$t').flexReload();
		 $('#flexRT$tt').flexReload();
	}

function Delete$t(domain,id){
	mem$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('delete',domain);
	XHR.sendAndLoad('$page', 'POST',x_Delete$t);
}

function Apply$t(){
	Loadjs('squid.compile.progress.php?onlywhitelist=yes');
}

function NewWebServer$t(){
	var dom=prompt('$squid_ask_domain');
	if(dom){
		var XHR = new XHRConnection();
		XHR.appendData('unlock',dom);
		XHR.appendData('noreload',1);
		XHR.sendAndLoad('squid.blocked.events.php', 'POST',x_reload$t);	
	}
}

</script>
	
	
	";
echo $html;	

}
function popup_list(){
	$ID=$_GET["taskid"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$t=$_GET["t"];
	
	$search='%';
	$table="urlrewriteaccessdeny";
	$page=1;
	$FORCE_FILTER="";
	if(!$q->TABLE_EXISTS("$table",'artica_backup')){json_error_show("$table No such table");}
	if($q->COUNT_ROWS("$table",'artica_backup')==0){json_error_show("No data");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$q2=new mysql();

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date('Y-m-d');
	if(!$q->ok){json_error_show($q->mysql_error);}	

	while ($ligne = mysql_fetch_assoc($results)) {
	$ligne["zDate"]=str_replace($today,"{today}",$ligne["zDate"]);
	if(preg_match("#plus-(.+?)-artica#",$ligne["category"],$re)){$ligne["category"]=$re[1];}
	$ligne["zDate"]=$tpl->_ENGINE_parse_body("{$ligne["zDate"]}");
	$id=md5(serialize($ligne));
	
	
	
		
	$delete=imgsimple("delete-24.png",null,"Delete$t('{$ligne["items"]}','$id')");
	
	
	
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array(
			"<span style='font-size:16px;'>{$ligne["items"]}</span>",
			
			$delete
			)
		);
	}
	
	
echo json_encode($data);		


}

function Delete(){
	$table="urlrewriteaccessdeny";
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM urlrewriteaccessdeny WHERE items='{$_POST["delete"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-whitelist=yes");
}



