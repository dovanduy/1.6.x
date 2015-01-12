<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	


	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		die();exit();
	}
	
	if(isset($_GET["members"])){members_table();exit;}
	if(isset($_GET["members-list"])){members_list();exit;}
	if(isset($_GET["MAC-js"])){mac_js();exit;}
	if(isset($_GET["MAC-popup"])){mac_popup();exit;}
	if(isset($_POST["MAC"])){Save();exit;}
	if(isset($_GET["MAC-delete-js"])){mac_js_delete();exit;}
	if(isset($_POST["delete"])){mac_delete();exit;}
	tabs();
	
	
function mac_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$MAC=$_GET["MAC-js"];
	$q=new mysql_squid_builder();
	if($MAC<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='$MAC'"));
		$title="$MAC::{$ligne["uid"]}::{$ligne["hostname"]}";
	}else{
		$title="{new_alias}";
	}
	$MACENC=urlencode($MAC);
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(890,'$page?MAC-popup=yes&MAC=$MACENC','$title')";

}
function mac_js_delete(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$q=new mysql_squid_builder();
	$MAC=$_GET["MAC-delete-js"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='$MAC'"));
	
	$tt=time();
	$pattern=$tpl->javascript_parse_text("{delete} {alias}: $MAC {$ligne["uid"]}::{$ligne["hostname"]} ?");
	$html="
var xSave$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#MAC_TO_UID_TABLE').flexReload();
}



function Save$tt(){
	var XHR = new XHRConnection();
	if(!confirm('$pattern')){return;}
	XHR.appendData('delete',  '$MAC');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}

Save$tt();
";
echo $html;

}

function mac_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$MAC=$_GET["MAC"];
	$btname="{add}";
	$t=time();
	if($MAC<>null){$btname="{apply}";}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='$MAC'"));
	
	$html="
		<div style='width:98%' class=form>
		<table style='width:100%'>
		".Field_text_table("MAC-$t", "{ComputerMacAddress}",$ligne["MAC"],22,null,350).
		Field_text_table("uid-$t", "{uid}",$ligne["uid"],22,null,350).
		Field_text_table("hostname-$t", "{hostname}",$ligne["hostname"],22,null,350).
		Field_button_table_autonome("$btname", "Save$t()",32)."
		</table>	
		</div>
				
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	var MAC='$MAC';
	$('#MAC_TO_UID_TABLE').flexReload();
	if(MAC.length==0){YahooWin3Hide();}
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('MAC',  document.getElementById('MAC-$t').value);
	XHR.appendData('uid',  document.getElementById('uid-$t').value);
	XHR.appendData('hostname',  document.getElementById('hostname-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>								
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$q=new mysql_squid_builder();
	$_POST["MAC"]=strtolower($_POST["MAC"]);
	$_POST["MAC"]=str_replace("-", ":", $_POST["MAC"]);
	
	
	$q->QUERY_SQL("DELETE FROM webfilters_nodes WHERE MAC='{$_POST["MAC"]}'");
	
	
	$sql="INSERT INTO webfilters_nodes (MAC,uid,hostname,nmapreport,nmap)
	VALUES ('{$_POST["MAC"]}','{$_POST["uid"]}','{$_POST["hostname"]}','',0)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function mac_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_nodes WHERE MAC='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}

function tabs(){
	$sock=new sockets();
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["members"]='{members}';
	
	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){

		if($num=="networks"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.identd.network.php\" style='font-size:20px'><span>$ligne</span></a></li>\n");
			continue;
		}


		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:20px'><span>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
	}
	echo build_artica_tabs($html, "mactouid_tabs",1024)."<script>LeftDesign('users-white-256.png');</script>";
}

function members_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$new_alias=$tpl->_ENGINE_parse_body("{new_alias}");
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	$title=$tpl->_ENGINE_parse_body("{proxy_members_aliases}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$new_alias', bclass: 'add', onpress : NewRule$t},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$t},
	],";

	$html="
	<table class='MAC_TO_UID_TABLE' style='display: none' id='MAC_TO_UID_TABLE' style='width:99%'></table>
	<script>

	$(document).ready(function(){
	$('#MAC_TO_UID_TABLE').flexigrid({
	url: '$page?members-list=yes',
	dataType: 'json',
	colModel : [
	{display: '$ComputerMacAddress', name : 'MAC', width :219, sortable : true, align: 'left'},
	{display: '$member', name : 'uid', width :292, sortable : true, align: 'left'},
	{display: '$hostname', name : 'hostname', width :273, sortable : true, align: 'left'},
	{display: '$delete', name : 'DEL', width :80, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
	{display: '$ComputerMacAddress', name : 'MAC'},
	{display: '$member', name : 'uid'},
	{display: '$hostname', name : 'hostname'},
	
	],
	sortname: 'MAC',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 938,
	height: 350,
	singleSelect: true

});
});

function NewRule$t(){
	Loadjs('$page?MAC-js=');
}

function Apply$t(){
	Loadjs('squid.macToUid.progress.php');
}

</script>";

	echo $html;

}

function members_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	

	$search='%';
	$page=1;
	$total=0;
	$rp=50;
	$tablename="webfilters_nodes";
	if($q->COUNT_ROWS($tablename,"artica_events")==0){json_error_show("$tablename No such table");}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT * FROM $tablename WHERE 1 $searchstring";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);

	}else{
		$sql="SELECT COUNT(*) FROM $tablename";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total =mysql_numrows($results);

	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$sql="SELECT * FROM $tablename WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show("$tablename $sql<br>$q->mysql_error");}

	$textcss="<span style='font-size:22px'>";
	while ($ligne = mysql_fetch_assoc($results)) {
		$maecen=urlencode($ligne['MAC']);
		$jsweb="
		<a href=\"javascript:blur()\"
		OnClick=\"javascript:Loadjs('$MyPage?MAC-js=$maecen')\"
		style='font-size:22px;text-decoration:underline'>";

		$jsjscat="Loadjs('squid.categorize.php?www={$ligne["sitename"]}&day={$_GET["day"]}&week=&month=');";
		$jscat="<a href=\"javascript:blur()\"
		OnClick=\"javascript:$jsjscat\"
		style='font-size:12px;text-decoration:underline'>
		";

		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?MAC-delete-js=$maecen')");


		$data['rows'][] = array(
				'id' => $ligne['MAC'],
				'cell' => array(
						$textcss.$jsweb.$ligne["MAC"]."</span>",
						$textcss.$jsweb.$ligne["uid"]."</a></span>",
						$textcss.$jsweb.$ligne["hostname"]."</span>",$delete
						
				)
		);
	}


	echo json_encode($data);
}