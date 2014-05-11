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
	
	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["proxies-list"])){proxies_list();exit;}
	if(isset($_GET["add-proxy"])){proxies_add_popup();exit;}
	if(isset($_POST["ipsrc"])){proxies_add();exit;}
	if(isset($_POST["proxy-delete"])){proxies_delete();exit;}
	if(isset($_POST["proxy-enable"])){proxies_enabled();exit;}
		js();
	
function js(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{proxy_child}");
	$html="YahooWin4(600,'$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==1){$ENABLED="TRUE";}else{
		$ENABLED=trim($sock->getFrameWork("squid.php?follow-xforwarded-for-enabled=yes"));
	}
	
	if($ENABLED<>"TRUE"){
		$html="
		<table style='width:98%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-128.png'></td>
			<td valign='top'><div style='font-size:18px'>{X_FORWARDED_FOR_NOT_ENABLED_IN_SQUID}</td>
		</tr>
		</table>
		
		";
		
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$q->CheckTablesSquid();
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$new_proxy=$tpl->javascript_parse_text("{new_proxy}");
	$proxy_child=$tpl->_ENGINE_parse_body("{proxy_child}");
	$delete_this_child=$tpl->javascript_parse_text("{delete_this_child}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$tt=$_GET["tt"];
	$t=time();		

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?proxies-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'isnull', width : 50, sortable : false, align: 'center'},
		{display: 'Proxys', name : 'ipsrc', width : 440, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 80, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 52, sortable : true, align: 'center'},
		
	],
buttons : [
	{name: '$new_proxy', bclass: 'add', onpress : AddProxyChild},
	{name: '$apply_params', bclass: 'apply', onpress : SquidBuildNow$t},

		],	
	searchitems : [
		{display: 'Proxy', name : 'ipsrc'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$proxy_child',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true
	
	});   
});	
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
	}
	
function AddProxyChild(){
	YahooWin5('450','$page?add-proxy=yes&t=$t','$new_proxy');

}

	var x_DeleteSquidChild$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#rowTSC'+tmp$t).remove();
		}		

	function DeleteSquidChild(ID){
		tmp$t=ID;
		if(confirm('$delete_this_child ?')){
			var XHR = new XHRConnection();
			XHR.appendData('proxy-delete',ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidChild$t);
		}
	}
	
	var x_EnableDisableProxyClient$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#table-$t').flexReload();
		}		
	
	function EnableDisableProxyClient(ID){
		var XHR = new XHRConnection();
		XHR.appendData('proxy-enable',ID);
		if(document.getElementById('ProxyClient_'+ID).checked){
			XHR.appendData('enable',1);
		}else{
			XHR.appendData('enable',0);
		}
		
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableProxyClient$t);	
	}

</script>
";
	
	echo $html;
	
	
}

function proxies_enabled(){
	$sql="UPDATE squid_balancers SET enabled={$_POST["enable"]} WHERE ID={$_POST["proxy-enable"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");	
}

function proxies_delete(){
	$sql="DELETE FROM squid_balancers WHERE ID={$_POST["proxy-delete"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");	
}

function proxies_list(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$t=$_GET["t"];
	
	$search='%';
	$table="squid_balancers";
	$FORCE_FILTER=null;
	$page=1;

	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No rules....");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();

	if($searchstring<>null){
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
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data....");}
	
	

	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$icon="42-server.png";
		$color="black";
		$disable=Field_checkbox("ProxyClient_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableProxyClient('{$ligne['ID']}')");
		$delete=imgsimple("delete-42.png",null,"DeleteSquidChild('{$ligne['ID']}')");
		if($ligne["enabled"]==0){
			$color="#8a8a8a";
			$icon="42-server-grey.png";
		}
		
		
		
	$data['rows'][] = array(
		'id' => "TSC{$ligne['ID']}",
		'cell' => array(
				"<img src='img/$icon'>",
				"<div style='font-size:22px;color:$color;margin-top:4px'>{$ligne['ipsrc']}</div>",
		"<div style='margin-top:4px'>$disable</div>","$delete")
		);
	}
	
	
	echo json_encode($data);		
	
}

function proxies_add_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$t=time();	
	$tt=$_GET["t"];
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px'>{source}:</td>
		<td>". field_ipv4("ipsrc-$t", null,"font-size:18px",false,"ChildEventAddCK$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>". button("{add}","ChildEventAdd$t()","22px")."</td>
	</tr>
	</table>
	<script>
		var x_ChildEventAdd$t= function (obj) {
			$('#table-$tt').flexReload();
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			
			YahooWin5Hide();
		}		

		function ChildEventAdd$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ipsrc',document.getElementById('ipsrc-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_ChildEventAdd$t);
		}
		
		function ChildEventAddCK$t(e){
			if(!checkEnter(e)){return;}
			ChildEventAdd$t();
		}
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function proxies_add(){
	$sql="INSERT IGNORE INTO squid_balancers (ipsrc,enabled) VALUES ('{$_POST["ipsrc"]}',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?squid-rebuild=yes");
}


