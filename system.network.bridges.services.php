<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.firehol.inc');
	
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsSystemAdministrator==false){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}
	

	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["pattern-js"])){pattern_js();exit;}
	if(isset($_GET["pattern-popup"])){pattern_popup();exit;}
	if(isset($_POST["Link"])){pattern_link();exit;}
	if(isset($_POST["delete-pattern"])){pattern_delete();exit;}
	if(isset($_GET["delete-pattern-js"])){pattern_delete_js();exit;}
	if(isset($_GET["switch-pattern-js"])){switch_pattern_js();exit;}
	if(isset($_GET["excludes-js"])){exclude_js();exit;}
	if(isset($_GET["excludes-tabs"])){exclude_tabs();exit;}
	
	if(isset($_POST["switch-pattern"])){switch_pattern();exit;}
	
	
	
	table();
	
function pattern_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_network}");
	echo "YahooWin3('600','$page?pattern-popup=yes&portid={$_GET["portid"]}&include={$_GET["include"]}','$title')";
}

function exclude_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{excludes} {$_GET["service"]}");
	echo "YahooWin3('600','$page?excludes-tabs=yes&service={$_GET["service"]}&routerid={$_GET["routerid"]}','$title')";	
}
function exclude_tabs(){
	$tpl=new templates();
	$array["include"]='{whitelisted_destination_networks}';
	$array["exclude"]="{whitelisted_src_networks}";
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="include"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"system.network.bridges.excludes.php?destination=1&service={$_GET["service"]}&routerid={$_GET["routerid"]}\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="exclude"){
			$html[]= $tpl->_ENGINE_parse_body("<li>
					<a href=\"system.network.bridges.excludes.php?destination=0&service={$_GET["service"]}&routerid={$_GET["routerid"]}\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
	}
	echo build_artica_tabs($html, "main_routers_excludes_tabs");
	
	
}




function switch_pattern_js(){
	$page=CurrentPageName();
	$t=time();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$routerid=$_GET["routerid"];
	$tpl=new templates();
echo "
var xdel$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	$('#TABLE_SERVICES_ROUTERS{$_GET["routerid"]}').flexReload();
}
	
	
function del$t(){
	var XHR = new XHRConnection();
	XHR.appendData('switch-pattern','$ID');
	XHR.sendAndLoad('$page', 'POST',xdel$t);
}
del$t();";	
	
	
}
	
function pattern_delete_js(){
	$page=CurrentPageName();
	$t=time();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["delete-pattern-js"]);
	$routerid=$_GET["routerid"];
	$tpl=new templates();
echo "
var xdel$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	$('#TABLE_SERVICES_ROUTERS{$_GET["routerid"]}').flexReload();
}
	
	
function del$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete-pattern','$ID');
	XHR.sendAndLoad('$page', 'POST',xdel$t);
}
del$t();";
}
	

function pattern_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM firehol_services_routers WHERE ID={$_POST["delete-pattern"]}","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	$q->QUERY_SQL("DELETE FROM `firehol_routers_exclude` WHERE `routerid`='{$_POST["delete-pattern"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}
		
		
function switch_pattern(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT allow FROM firehol_services_routers 
			WHERE ID='{$_POST["switch-pattern"]}'","artica_backup"));
	
	$allow=$ligne["allow"];
	if($allow==0){$allow=1;}else{$allow=0;}
	$q->QUERY_SQL("UPDATE firehol_services_routers SET allow=$allow WHERE ID='{$_POST["switch-pattern"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}
	
	
function table(){
	$page=CurrentPageName();
		$tpl=new templates();
		$tt=time();
		$t=$_GET["t"];
		$type=$tpl->javascript_parse_text("{type}");
		$networks=$tpl->_ENGINE_parse_body("{networks}");
		$new_rule=$tpl->_ENGINE_parse_body("{link_service}");
		$port=$tpl->javascript_parse_text("{listen_port}");
		$allow=$tpl->javascript_parse_text("{allow}");
		$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
		$enabled=$tpl->javascript_parse_text("{enabled}");
		$apply=$tpl->javascript_parse_text("{apply}");
		$excludes=$tpl->javascript_parse_text("{excludes}");
		$q=new mysql();
		if(!$q->TABLE_EXISTS("firehol_services_routers","artica_backup")){$fire=new firehol();$fire->checkTables();}
		$service=$tpl->_ENGINE_parse_body("{services}");
	
	
	$title="<strong style=font-size:30px>$service</strong>";
	
	$buttons="
		buttons : [
		{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$tt},
		{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reconf', onpress : Apply$tt},
		],";
	
		$html="
		<table class='TABLE_SERVICES_ROUTERS{$_GET["routerid"]}' style='display: none' id='TABLE_SERVICES_ROUTERS{$_GET["routerid"]}' style='width:100%'></table>
<script>
function Start$tt(){
	$('#TABLE_SERVICES_ROUTERS{$_GET["routerid"]}').flexigrid({
		url: '$page?search=yes&routerid={$_GET["routerid"]}',
		dataType: 'json',
		colModel : [
				{display: '<strong style=font-size:18px>$allow</strong>', name : 'allow', width :70, sortable : true, align: 'center'},
				{display: '<strong style=font-size:18px>$service</strong>', name : 'service', width :570, sortable : true, align: 'left'},
				{display: '<strong style=font-size:18px>$excludes</strong>', name : 'excludes', width :70, sortable : false, align: 'left'},
				{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
			],
			$buttons
			searchitems : [
				{display: '$service', name : 'service'},
			],
			sortname: 'ID',
			sortorder: 'desc',
			usepager: true,
			title: '$title',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: '99%',
			height: 350,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200]
	});
}
	
var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#TABLE_SERVICES_ROUTERS{$_GET["routerid"]}').flexReload();
}

function Apply$tt(){
	Loadjs('firehol.progress.php');
}
	
	
function NewRule$tt(){
  Loadjs('BrowseFireholeServices.php?CallBack=LinkRule$tt');
}
function LinkRule$tt(service){
	var XHR = new XHRConnection();
	XHR.appendData('Link', service);
	XHR.appendData('routerid', '{$_GET["routerid"]}');
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
}
var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}
	
	
function RuleEnable$tt(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
	XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
}
	
Start$tt();
</script>
	";
	echo $html;
}

function pattern_link(){
	
	$sql="INSERT IGNORE INTO firehol_services_routers (routerid,service,zOrder,allow)
			VALUES ('{$_POST["routerid"]}','{$_POST["Link"]}',0,1)";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}
	
function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	if(!$q->TABLE_EXISTS("firehol_services_routers")){$fire=new firehol();$fire->checkTables();}
	$sock=new sockets();
	$t=$_GET["t"];
	$search='%';
	$table="firehol_services_routers";
	$page=1;
	$total=0;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE routerid={$_GET["routerid"]} $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["TCOUNT"];
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM `$table` WHERE routerid={$_GET["routerid"]} $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,'artica_backup');
	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
	$all=$tpl->javascript_parse_text("{all}");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("!!! no data");}
	
		
	$tpl=new templates();
	$all=$tpl->javascript_parse_text("{all}");
	while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$ID=$ligne["ID"];
			$service=$ligne["service"];
			$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-pattern-js=$ID&routerid={$_GET["routerid"]}',true)");
			$pic="cloud-deny-48.png";
			if($ligne["allow"]==1){$pic="cloud-goto-48.png";}
			$allow=imgtootltip($pic,"{allow}/{deny}","Loadjs('$MyPage?switch-pattern-js=yes&ID=$ID&routerid={$_GET["routerid"]}')");
			
			$excludes=imgtootltip("folder-script-48.png","","Loadjs('$MyPage?excludes-js=yes&service=$service&routerid={$_GET["routerid"]}')");
			
			
			
			
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNt(ID) as tcount FROM firehol_routers_exclude
			WHERE service='$service' AND routerid='{$_GET["routerid"]}' AND destination=1","artica_backup"));
			
			$whitelisted_destination_networks=$tpl->javascript_parse_text("{whitelisted_destination_networks}: {$ligne2["tcount"]}");
			
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNt(ID) as tcount FROM firehol_routers_exclude
			WHERE service='$service' AND routerid='{$_GET["routerid"]}' AND destination=0","artica_backup"));
			$whitelisted_src_networks=$tpl->javascript_parse_text("{whitelisted_src_networks}: {$ligne2["tcount"]}");
			
			
			
			
			$data['rows'][] = array(
					'id' => $ID,
					'cell' => array(
					"<center style='margin-top:3px;font-size:16px;font-weight:normal;color:$color'>$allow</center>",
					"<span style='font-size:36px;font-weight:normal;color:$color'>$service</span>
					<br> <span style='font-size:18px'>$whitelisted_destination_networks<br>$whitelisted_src_networks</span>		
					",
					"<center style='margin-top:3px;font-size:16px;font-weight:normal;color:$color'>$excludes</center>",
					"<center style='margin-top:3px;font-size:16px;font-weight:normal;color:$color'>$delete</center>",)
			);
			}
	
			echo json_encode($data);
			}	