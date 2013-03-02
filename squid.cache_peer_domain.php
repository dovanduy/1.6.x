<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	if(isset($_POST["SaveWWW"])){domains_save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["domains-list"])){domains_list();exit;}
	if(isset($_POST["reverse"])){domain_reverse();exit;}
	if(isset($_POST["enabled"])){domain_enabled();exit;}
	if(isset($_POST["delete"])){domain_delete();exit;}
	
	
	
	js();
	
function js(){
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{squid_parent_proxy}::{domains}");
	$html="
	YahooWin5('564','$page?popup=yes&t=$t&servername={$_GET["servername"]}','$title');";
	echo $html;

}

function domains_save(){
	$md5=md5($_POST["SaveWWW"].$_POST["servername"]);
	$sql_add="INSERT INTO cache_peer_domain (`md5`,servername,domain,reverse,enabled)
	VALUES('$md5','{$_POST["servername"]}','{$_POST["SaveWWW"]}',0,1)";
	
	
	
	$q=new mysql();
	$sql=$sql_add;
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		echo $q->mysql_error."\n$sql";
		return;
	}
	
	
}

function popup(){
	
	$squid=new squidbee();
	$page=CurrentPageName();
	$tpl=new templates();
	$webserver=$tpl->_ENGINE_parse_body("{webserver}");
	$listen_port=$tpl->_ENGINE_parse_body("{listen_port}");
	$server_type=$tpl->_ENGINE_parse_body("{server_type}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$add_a_parent_proxy=$tpl->_ENGINE_parse_body("{new_webserver}");
	$squid_ask_domain=$tpl->javascript_parse_text("{squid_ask_domain}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$tt=$_GET["t"];
	$t=time();
$html="

<div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowmem$t;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?domains-list=yes&t=$t&servername={$_GET["servername"]}',
	dataType: 'json',
	colModel : [
		{display: '$webserver', name : 'servername', width :389, sortable : true, align: 'left'},
		{display: '$reverse', name : 'reverse', width :32, sortable : true, align: 'center'},
		{display: 'enabled', name : 'enabled', width :32, sortable : true, align: 'center'},
		{display: 'delete', name : 'hits', width : 31, sortable : false, align: 'left'}

		],
		
buttons : [
		{name: '$add_a_parent_proxy', bclass: 'add', onpress : newWebserver$t},
		{name: '$apply_params', bclass: 'Reload', onpress : SquidBuildNow$t},
		],			
	
	searchitems : [
		{display: '$webserver', name : 'servername'},
		],
	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 550,
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

var x_newWebserver$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	Refresh$t();
}

var x_delete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#row'+rowmem$t).remove();
}
var x_silent$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
}
function newWebserver$t(){
	var www=prompt('$squid_ask_domain');
	if(!www){return;}
	var XHR = new XHRConnection();
	XHR.appendData('SaveWWW',www);
	XHR.appendData('servername','{$_GET["servername"]}');
	XHR.sendAndLoad('$page', 'POST',x_newWebserver$t);	
}

function Refresh$t(){
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}
function Reverse$t(md5){
	var XHR = new XHRConnection();
	XHR.appendData('reverse',md5);
	XHR.sendAndLoad('$page', 'POST',x_silent$t);		
}

function Enable$t(md5){
	var XHR = new XHRConnection();
	XHR.appendData('enabled',md5);
	XHR.sendAndLoad('$page', 'POST',x_silent$t);		
} 

function DeleteDomains$t(md5){
	rowmem$t=md5;
	var XHR = new XHRConnection();
	XHR.appendData('delete',md5);
	XHR.sendAndLoad('$page', 'POST',x_delete$t);	
}
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}

</script>
";	
	echo $tpl->_ENGINE_parse_body($html);
}

function domains_list(){
$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$squid=new squidbee();
	$t=$_GET["t"];
	$search='%';
	$table="cache_peer_domain";
	$MySQLbase="artica_backup";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	
	$total=0;
	if($q->COUNT_ROWS($table,$MySQLbase)==0){json_error_show("No data...");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	$table="(SELECT * FROM cache_peer_domain WHERE servername='{$_GET["servername"]}') as t";
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$MySQLbase));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$MySQLbase));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$MySQLbase);
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		
		
		if($ligne["enabled"]==0){
			$color="#B3B3B3";
		}		
		
		$delete="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:DeleteDomains$t('{$ligne["md5"]}')\">
		<img src='img/delete-24.png' style='border:0px;color:$color'>
		</a>";
		
		$reverse=Field_checkbox("reverse_{$ligne["md5"]}", 1,$ligne["reverse"],"Reverse$t('{$ligne["md5"]}')");
		$enabled=Field_checkbox("enabled_{$ligne["md5"]}", 1,$ligne["enabled"],"Enable$t('{$ligne["md5"]}')");
		

		
	
	$data['rows'][] = array(
		'id' =>"{$ligne["md5"]}",
		'cell' => array(
				"<span style='font-size:14px;color:$color'>{$ligne["domain"]}</span>",
				$reverse,
				$enabled,
				$delete )
		);
	}
	
	
echo json_encode($data);	
}

function domain_reverse(){
	$q=new mysql();
	$sql="SELECT reverse FROM cache_peer_domain WHERE `md5`='{$_POST["reverse"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$reverse=$ligne["reverse"];
	if($reverse==0){$reverse=1;}else{$reverse=0;}
	$q->QUERY_SQL("UPDATE cache_peer_domain SET reverse=$reverse WHERE `md5`='{$_POST["reverse"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function domain_delete(){
	$q=new mysql();
	$sql="SELECT DELETE FROM cache_peer_domain WHERE `md5`='{$_POST["delete"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	if(!$q->ok){echo $q->mysql_error;return;}
}
function domain_enabled(){
	$q=new mysql();
	$sql="SELECT enabled FROM cache_peer_domain WHERE `md5`='{$_POST["enabled"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$reverse=$ligne["enabled"];
	if($reverse==0){$reverse=1;}else{$reverse=0;}
	$q->QUERY_SQL("UPDATE cache_peer_domain SET enabled=$reverse WHERE `md5`='{$_POST["enabled"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}
?>