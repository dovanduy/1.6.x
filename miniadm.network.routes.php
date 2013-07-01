<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

if(isset($_GET["ruleid"])){ruleid_js();exit;}



if(isset($_GET["routes-search"])){routes_search();exit;}
if(isset($_GET["routes-list"])){routes_section();exit;}


if(isset($_GET["mainroutes-list"])){mainroutes_section();exit;}
if(isset($_GET["mainroutes-search"])){mainroutes_search();exit;}
if(isset($_GET["mainrouteid"])){mainroutes_js();exit;}
if(isset($_GET["mainrouteid-popup"])){mainroutes_popup();exit;}
if(isset($_POST["zmd5"])){mainroutes_save();exit;}
if(isset($_POST["mainroute-delete"])){mainroutes_delete();exit;}



if(isset($_GET["ruleid-tabs"])){route_tabs();exit;}
if(isset($_GET["ruleid-popup"])){route_settings();exit;}
if(isset($_POST["routename"])){route_settings_save();exit;}

if(isset($_GET["ruleid-rules"])){rules_section();exit;}
if(isset($_GET["rules-search"])){rules_search();exit;}
if(isset($_GET["subruleid"])){subrules_js();exit;}
if(isset($_GET["subruleid-popup"])){subrules_popup();exit;}
if(isset($_POST["subruleid"])){subrules_save();exit;}
if(isset($_POST["route-delete"])){route_delete();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}


tabs();


function tabs(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{main_routes}"]="$page?mainroutes-list=yes";
	$array["{routes_tables}"]="$page?routes-list=yes";
	echo $boot->build_tab($array);
}

function routes_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$EXPLAIN["BUTTONS"][]=button("{new_group}","Loadjs('$page?ruleid=0');",16);
	$EXPLAIN["BUTTONS"][]=$compile_rules;
	echo $boot->SearchFormGen("rulename","routes-search",null,$EXPLAIN);	
	
}
function mainroutes_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$EXPLAIN["BUTTONS"][]=button("{new_route}","Loadjs('$page?mainrouteid=');",16);
	$EXPLAIN["BUTTONS"][]=$compile_rules;
	echo $boot->SearchFormGen("pattern,gateway","mainroutes-search",null,$EXPLAIN);	
}

function rules_section(){
	$q=new mysql();
	$q->BuildTables();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$compile_rules=null;
	$EXPLAIN["BUTTONS"][]=button("{new_rule}","Loadjs('$page?subruleid=0&rule-id={$_GET["rule-id"]}');",16);
	echo $boot->SearchFormGen("src,destination","rules-search","&rule-id={$_GET["rule-id"]}",$EXPLAIN);
		
}

function mainroutes_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{new_rule}");
	$ID=$_GET["mainrouteid"];
	
	if($ID>0){
		$q=new mysql();
		$sql="SELECT `pattern`,`gateway`  FROM nic_routes WHERE zmd5='$ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$tpl->_ENGINE_parse_body("{$ligne["pattern"]} {$ligne["gateway"]}");
	}
	$html="YahooWin2('850','$page?mainrouteid-popup=yes&zmd5=$ID','$title');";
	echo $html;	
	
}

function ruleid_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{new_rule}");
	$ID=$_GET["ruleid"];
	
	if($ID>0){
		$q=new mysql();
		$sql="SELECT `routename`  FROM iproute_table WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$tpl->_ENGINE_parse_body("$ID::{$ligne["routename"]}");
	}
	$html="YahooWin2('850','$page?ruleid-tabs=yes&rule-id=$ID','$title');";
	echo $html;
}

function subrules_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{new_rule}");
	$ruleid=$_GET["rule-id"];
	$subruleid=$_GET["subruleid"];
	if($subruleid>0){
		$q=new mysql();
		$sql="SELECT `src`,`destination`  FROM iproute_rules WHERE ID=$subruleid";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$tpl->_ENGINE_parse_body("$subruleid::{$ligne["src"]} - {$ligne["destination"]}");
	}
	$html="YahooWin3('850','$page?subruleid-popup=yes&rule-id=$ruleid&subrule=$subruleid','$title');";
	echo $html;	
	
}

function route_tabs(){
	$ID=$_GET["rule-id"];
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$array["{parameters}"]="$page?ruleid-popup=yes&rule-id=$ID";
	if($ID>0){
		$array["{rules}"]="$page?ruleid-rules=yes&rule-id=$ID";
	}
	echo $boot->build_tab($array);	
}

function route_settings_save(){
	$q=new mysql();
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$html=new htmltools_inc();
	$_POST["routename"]=$html->StripSpecialsChars($_POST["routename"]);
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string($value)."'";
		$edit[]="`$key`='".mysql_escape_string($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE iproute_table SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO iproute_table (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?ip-build-routes=yes");
}

function rule_delete(){
	$ID=$_POST["rule-delete"];
	$q=new mysql();
	$sql="DELETE FROM iproute_rules WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?ip-build-routes=yes");

}

function mainroutes_delete(){
	$ID=$_POST["mainroute-delete"];
	$q=new mysql();
	if($ID==null){echo "No such id;..\n";}
	$sql="DELETE FROM nic_routes WHERE zmd5='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?ip-build-routes=yes");	
	
}

function route_delete(){
	$ID=$_POST["route-delete"];
	$q=new mysql();
	$sql="DELETE FROM iproute_rules WHERE ruleid=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sql="DELETE FROM iproute_table WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?ip-build-routes=yes");
	
	
}

function subrules_save(){
	$q=new mysql();
	$ID=$_POST["subruleid"];
	unset($_POST["subruleid"]);

	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string($value)."'";
		$edit[]="`$key`='".mysql_escape_string($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE iproute_rules SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO iproute_rules (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?ip-build-routes=yes");
}

function route_settings(){
	$ID=$_GET["rule-id"];
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$buttonname="{add}";
	$boot=new boostrap_form();
	if($ID>0){
		$q=new mysql();
		$sql="SELECT *  FROM iproute_table WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$tpl->_ENGINE_parse_body("$ID::{$ligne["routename"]}");	
		$buttonname="{apply}";
		
	}else{
		$boot->set_CloseYahoo("YahooWin2");
	}
	
	$ip=new networking();
	$Interfaces=$ip->Local_interfaces();
	unset($Interfaces["lo"]);
	if(!is_numeric($ligne["enable"])){$ligne["enable"]=1;}	
	
	$boot->set_formtitle($title);
	$boot->set_hidden("ID", $ID);
	$boot->set_field("routename", "{groupname}", $ligne["routename"]);
	$boot->set_field("gateway", "{gateway}", $ligne["gateway"]);
	$boot->set_list("interface", "{interface}", $Interfaces,$ligne["interface"]);
	$boot->set_checkbox("enable", "{enabled}",  $ligne["enable"]);
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$boot->set_form_locked();
	}
	$boot->set_button($buttonname);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
	
}

function mainroutes_popup(){
	$zmd5=$_GET["zmd5"];
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$buttonname="{add}";
	$boot=new boostrap_form();
	if($zmd5<>null){
		$q=new mysql();
		$sql="SELECT *  FROM nic_routes WHERE zmd5='$zmd5'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$tpl->_ENGINE_parse_body("{$ligne["pattern"]}");
		$buttonname="{apply}";
	
	}else{
		$boot->set_CloseYahoo("YahooWin2");
	}
	
	$nics[null]="{select}";
	
	$types[1]="{network_nic}";
	$types[2]="{host}";	
	
	$ip=new networking();
	$Interfaces=$ip->Local_interfaces();
	unset($Interfaces["lo"]);
	$Interfaces[null]="{select}";
	if(!is_numeric($ligne["enable"])){$ligne["enable"]=1;}
	
	$boot->set_formtitle($title);
	$boot->set_hidden("zmd5", $zmd5);
	$boot->set_list("type", "{type}", $types,$ligne["type"]);
	$boot->set_field("pattern", "{destination}", $ligne["pattern"]);
	$boot->set_field("gateway", "{gateway}", $ligne["gateway"]);
	$boot->set_list("nic", "{interface}", $Interfaces,$ligne["interface"]);
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$boot->set_form_locked();
	}
	$boot->set_button($buttonname);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
}

function mainroutes_save(){
	$q=new mysql();
	$zmd5=$_POST["zmd5"];
	unset($_POST["zmd5"]);
	
	if($zmd5==null){
		$fields[]="`zmd5`";
		$values[]="'".$md5=md5(serialize($_POST))."'";
		
	}
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string($value)."'";
		$edit[]="`$key`='".mysql_escape_string($value)."'";
	
	}
	
	if($zmd5<>null){
		$sql="UPDATE nic_routes SET ".@implode(",", $edit)." WHERE zmd5='$zmd5'";
	}else{
		$sql="INSERT IGNORE INTO nic_routes (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?ip-build-routes=yes");
}

function subrules_popup(){
	$ruleid=$_GET["rule-id"];
	
	$ID=$_GET["subrule"];
	
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$buttonname="{add}";
	$boot=new boostrap_form();
	if($ID>0){
		$q=new mysql();
		$sql="SELECT *  FROM iproute_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$title=$tpl->_ENGINE_parse_body("$ID::{$ligne["src"]} - {$ligne["destination"]}");
		$buttonname="{apply}";
	
	}else{
		$boot->set_CloseYahoo("YahooWin3");
	}
	
	
	if(!is_numeric($ligne["enable"])){$ligne["enable"]=1;}
	
	$boot->set_formtitle($title);
	$boot->set_formdescription("{iprules_explain}");
	$boot->set_hidden("subruleid", $ID);
	$boot->set_hidden("ruleid", $ruleid);
	$boot->set_field("src", "{source}", $ligne["source"]);
	$boot->set_field("destination", "{destination}", $ligne["destination"]);
	$boot->set_field("priority", "{priority}", $ligne["priority"]);
	$boot->set_checkbox("enable", "{enabled}",  $ligne["enable"]);
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$boot->set_form_locked();
	}
	$boot->set_button($buttonname);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
}

function rules_search(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$t=time();
	//$q->QUERY_SQL("DROP TABLE iproute_table","artica_backup");
	$q->BuildTables();
	
	$search=string_to_flexquery("rules-search");
	$sql="SELECT * FROM iproute_rules WHERE ruleid={$_GET["rule-id"]} $search ORDER BY priority LIMIT 0,500";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){senderror($q->mysql_error);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$link=$boot->trswitch("Loadjs('$page?subruleid={$ligne["ID"]}&rule-id={$_GET["rule-id"]}');");

		$delete=imgtootltip("delete-32.png",null,"Delete$t({$ligne["ID"]})");
		$tr[]="<tr>
		<td style='font-size:14px' width=50% nowrap $link>{$ligne["src"]}</td>
		<td style='font-size:14px' width=50% nowrap $link>{$ligne["destination"]}</td>
		<td style='font-size:14px' width=1% nowrap $link>{$ligne["priority"]}</td>
		<td width=1% nowrap>$delete</td>
		</tr>
	
		";
	
	
	}
	$deleteTXT=$tpl->javascript_parse_text("{delete_rule}");
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{source}</th>
					<th>{destination}</th>
					<th>PRIO</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
					
	<script>
var FreeWebIDMEM$t='';
	var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
	}
	
function Delete$t(id){
	if(confirm('$deleteTXT \"'+id+'\" ?')){
		var XHR = new XHRConnection();
		XHR.appendData('rule-delete',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
	</script>	";	
	
}

function mainroutes_search(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$t=time();
	//$q->QUERY_SQL("DROP TABLE iproute_table","artica_backup");
	$q->BuildTables();
	$types[1]="{network_nic}";
	$types[2]="{host}";	
	
	$search=string_to_flexquery("mainroutes-search");
	$sql="SELECT * FROM nic_routes WHERE 1 $search ORDER BY pattern LIMIT 0,500";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){senderror($q->mysql_error);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$link=$boot->trswitch("Loadjs('$page?mainrouteid={$ligne["zmd5"]}');");
	
		$delete=imgtootltip("delete-64.png",null,"Delete$t('{$ligne["zmd5"]}')");
		$tr[]="<tr id='R{$ligne["ID"]}'>
		<td width=1% nowrap $link><img src='img/64-ip-settings.png'></td>
		<td style='font-size:18px' nowrap $link>&nbsp;". $tpl->javascript_parse_text($types[$ligne["type"]])."</td>
		<td style='font-size:18px' nowrap $link>&nbsp;{$ligne["nic"]}</td>
		<td style='font-size:18px' nowrap $link>&nbsp;{$ligne["pattern"]}</td>
		<td style='font-size:18px' nowrap $link>&nbsp;{$ligne["gateway"]}</td>
		<td  width=1% nowrap>$delete</td>
		</tr>
		";
	
	
	
	}
	$deleteTXT=$tpl->javascript_parse_text("{delete_route}");
	echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{type}</th>
					<th>{nic}</th>
					<th>{pattern}</th>
					<th>{gateway}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
<script>
var FreeWebIDMEM$t='';
	var xDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>10){alert(results);return;}
		ExecuteByClassName('SearchFunction');
	}
	
	function Delete$t(id){
	if(confirm('$deleteTXT \"'+id+'\" ?')){
	var XHR = new XHRConnection();
	XHR.appendData('mainroute-delete',id);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
	}
	</script>
		
	";
		
	
	
}



function routes_search(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();	
	$boot=new boostrap_form();
	$t=time();
	//$q->QUERY_SQL("DROP TABLE iproute_table","artica_backup");
	$q->BuildTables();
	
	$search=string_to_flexquery("routes-search");
	$sql="SELECT * FROM iproute_table WHERE 1 $search ORDER BY routename LIMIT 0,500";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){senderror($q->mysql_error);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$link=$boot->trswitch("Loadjs('$page?ruleid={$ligne["ID"]}');");
		
		
		
		$sql="SELECT * FROM iproute_rules WHERE ruleid={$ligne["ID"]} ORDER BY priority LIMIT 0,10";
		$results2=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){senderror($q->mysql_error);}
		$ff=array();
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			
			$ff[]="<div style='font-size:14px'>{$ligne2["src"]}&nbsp;-&nbsp;{$ligne2["destination"]}</div>";
		
		
		}		
		
		$delete=imgtootltip("delete-64.png",null,"Delete$t({$ligne["ID"]})");
		$tr[]="<tr id='R{$ligne["ID"]}'>
		<td width=1% nowrap $link><img src='img/64-ip-settings.png'></td>
		<td style='font-size:18px' $link>{$ligne["routename"]}</td>
		<td style='font-size:18px' $link width=1% nowrap $link>{$ligne["interface"]}</td>
		<td style='font-size:18px' $link width=1% nowrap $link>{$ligne["gateway"]}</td>
		<td $link width=1% nowrap>".@implode("\n", $ff)."</td>
		<td width=1% nowrap>$delete</td>
		</tr>		
				
		";
		
		
	}
	$deleteTXT=$tpl->javascript_parse_text("{delete_group}");
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{groupname}</th>
					<th>{interface}</th>
					<th>{gateway}</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
	<script>
var FreeWebIDMEM$t='';
	var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
	}
	
function Delete$t(id){
	if(confirm('$deleteTXT \"'+id+'\" ?')){
		var XHR = new XHRConnection();
		XHR.appendData('route-delete',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
	</script>					
					
";	
	
	
}