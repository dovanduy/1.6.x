<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}


if(isset($_GET["replace-section"])){section_replace();exit;}
if(isset($_GET["replace-rules-section"])){section_rules_replace();exit;}




if(isset($_GET["replace-search"])){replace_search();exit;}
if(isset($_GET["js-replace"])){replace_js();exit;}
if(isset($_GET["js-replace-group"])){replace_group_js();exit;}
if(isset($_GET["replace-group-tabs"])){replace_group_tab();exit;}
if(isset($_GET["replace-group-popup"])){replace_group_popup();exit;}
if(isset($_GET["group-replace-search"])){replace_group_search();exit;}
if(isset($_POST["replace-group-delete"])){replace_group_delete();exit; }

if(isset($_GET["replace-popup"])){replace_popup();exit;}

if(isset($_POST["editgroupid"])){replace_group_save();exit;}
if(isset($_POST["ID"])){replace_popup_save();exit;}
if(isset($_POST["replace-delete"])){replace_delete();exit;}




function GetPrivs(){
	$NGNIX_PRIVS=$_SESSION["NGNIX_PRIVS"];
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}
	if(count($_SESSION["NGNIX_PRIVS"])>0){return true;} 
	return false;

}
function AdminPrivs(){
	$users=new usersMenus();
	if($users->AsSystemWebMaster){return true;}
	if($users->AsSquidAdministrator){return true;}	
	
}

/*
 * 		
		`ID` INT NOT NULL AUTO_INCREMENT,
		`directory` CHAR(255)  NOT NULL,
		`levels` CHAR(20)  NOT NULL,
		`keys_zone` CHAR(40)  NOT NULL,
		`keys_zone_size` smallint(1)  NOT NULL,
		`inactive` smallint(3)  NOT NULL DEFAULT '10',
		`max_size` smallint(1)  NOT NULL DEFAULT '1',
		`loader_files` INT(5) NOT NULL DEFAULT '100',
		`loader_sleep` INT(10) NOT NULL DEFAULT '50',
		`loader_threshold` INT(10) NOT NULL DEFAULT '200',
 */

function replace_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$title="{new_rule}";
	$bt="{add}";
	$ID=$_GET["ID"];
	$boot=new boostrap_form();
	$sock=new sockets();
	$groupid=$_GET["groupid"];
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_replace WHERE ID='$ID'"));
		$bt="{apply}";
		$title="{$ligne["rulename"]}";
		$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
		$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
		$groupid=$ligne["groupid"];
	}else{
		$boot->set_hidden("groupid", $groupid);
	}

	if($ligne["rulename"]==null){$ligne["rulename"]=time();}
	$boot->set_hidden("ID", $ID);
	
	
	
	$boot->set_formtitle($title);
	$boot->set_field("rulename", "{name}", $ligne["rulename"]);
	$boot->set_textarea("stringtosearch", "{search}", $ligne["stringtosearch"],array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_textarea("replaceby", "{replace}", $ligne["replaceby"],array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_button($bt);
	if($ID==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_RefreshSearchs();
	
	echo $boot->Compile();

}
function replace_group_tab(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ID=$_GET["ID"];
	
	$t=time();
	$boot=new boostrap_form();
	$mini=new miniadm();
	$users=new usersMenus();
	$array["{parameters}"]="$page?replace-group-popup=yes&ID=$ID";
	if($ID>0){
		$array["{rules}"]="$page?replace-rules-section=yes&groupid=$ID";
	}
	echo $boot->build_tab($array);	
	
}
function replace_group_popup(){
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$title="{new_group}";
	$bt="{add}";
	
	$boot=new boostrap_form();
	$sock=new sockets();
	
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_replace_group WHERE ID='$ID'"));
		$bt="{apply}";
		$title="{$ligne["groupname"]}";
	}
	
	if($ligne["groupname"]==null){$ligne["groupname"]="New Group";}
	$boot->set_hidden("editgroupid", $ID);
	$boot->set_formtitle($title);
	$boot->set_field("groupname", "{groupname}", $ligne["groupname"]);
	
	$boot->set_button($bt);
	if($ID==0){$boot->set_CloseYahoo("YahooWin");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
}

function replace_group_delete(){
	$ID=$_POST["replace-group-delete"];
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_replace WHERE groupid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM nginx_replace_group WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function replace_group_save(){
	$ID=$_POST["editgroupid"];
	unset($_POST["editgroupid"]);
	$q=new mysql_squid_builder();
	$rev=new squid_reverse();
		
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE nginx_replace_group SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO nginx_replace_group (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
}

function replace_popup_save(){
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	$q=new mysql_squid_builder();
	$rev=new squid_reverse();
	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$html=new htmltools_inc();
	$_POST["stringtosearch"]=url_decode_special_tool($_POST["stringtosearch"]);
	$_POST["replaceby"]=url_decode_special_tool($_POST["replaceby"]);
	$editF=false;

	if($ID>0){
		$editF=true;
	}

	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}

	if($editF){
		$sql="UPDATE nginx_replace SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{
		$sql="INSERT IGNORE INTO nginx_replace (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");
}

function replace_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];

	$title="{new_rule}";
	if($ID>0){
		$title="{rule}:$ID";
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(800,'$page?replace-popup&ID=$ID&groupid={$_GET["groupid"]}','$title')";

}
function replace_group_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	
	$title="{new_group}";
	if($ID>0){
		$title="{group}:$ID";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_replace_group WHERE ID='$ID'"));
		$title="{group}:{$ligne["groupname"]}";
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(990,'$page?replace-group-tabs&ID=$ID','$title')";	
}

function section_rules_replace(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_rule}", "Loadjs('$page?js-replace=yes&ID=0&groupid={$_GET["groupid"]}')"));
	echo $boot->SearchFormGen("stringtosearch,rulename","replace-search","&groupid={$_GET["groupid"]}",$EXPLAIN);
}

function section_replace(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$error=null;
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("nginx.php?status-infos=yes")));
	if(!is_array($ARRAY["MODULES"])){$ARRAY["MODULES"]=array();}
	$COMPAT=FALSE;
	while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
		if(preg_match("#http_substitutions_filter#", $a)){
			$COMPAT=true;
			break;
		}
	}
	
	if(!$COMPAT){
		$error=$tpl->_ENGINE_parse_body("<p class=text-error>{error_nginx_substitutions_filter}</p>");
	}
	
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_group}", "Loadjs('$page?js-replace-group=yes&ID=0')"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{apply_parameters}", "Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=restart&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');"));
	echo $tpl->_ENGINE_parse_body("<div class=text-info>{nginx_replace_explain}</div>").
	$error.$boot->SearchFormGen("groupname","group-replace-search",null,$EXPLAIN);	
}
function replace_group_search(){
	$prox=new squid_reverse();
	$searchstring=string_to_flexquery("group-replace-search");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM nginx_replace_group WHERE 1 $searchstring ORDER BY groupname LIMIT 0,250";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	$tpl=new templates();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	
	if($GLOBALS["VERBOSE"]){echo "<H1>$sql</H1><br>". mysql_num_rows($results)." Entries<hr>";}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ID=$ligne["ID"];
		$icon="www-web-search-64.png";
		$icon2="folder-network-64.png";
		$color="black";
		$md=md5(serialize($ligne));
	
		$stringtosearch=htmlentities($ligne["stringtosearch"]);
		$delete=imgsimple("delete-64.png",null,"Delete$t('{$ligne["ID"]}','$md')");
	
		$jsedit=$boot->trswitch("Loadjs('$page?js-replace-group=yes&ID={$ligne["ID"]}')");
	
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as TCOUNT FROM nginx_replace WHERE groupid='$ID'"));
		$RulesNumber=$ligne2["TCOUNT"];
		
		
		$tr[]="
		<tr style='color:$color' id='$md'>
		<td width=1% nowrap $jsedit style='vertical-align:middle' nowrap><img src='img/$icon'></td>
		<td width=80% $jsedit style='vertical-align:middle'>
		<span style='font-size:18px;font-weight:bold'>{$ligne["groupname"]}</span>
		</td>
	
		<td width=1% nowrap $jsedit style='vertical-align:middle' nowrap>
		<span style='font-size:18px;font-weight:bold'>$RulesNumber</span>
		</td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
	}
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{group}</th>
					<th >{rules}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
				 <script>
				 var FreeWebIDMEM$t='';
				 var xDelete$t=function (obj) {
				 var results=obj.responseText;
				 if(results.length>10){alert(results);return;}
				 $('#'+FreeWebIDMEM$t).remove();
	}
	
	function Delete$t(ID,md){
	FreeWebIDMEM$t=md;
	if(confirm('Remove '+ID+'?')){
	var XHR = new XHRConnection();
	XHR.appendData('replace-group-delete',ID);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
	}</script>
	";
		
	
}


function replace_search(){
	$prox=new squid_reverse();
	$searchstring=string_to_flexquery("replace-search");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM nginx_replace WHERE groupid={$_GET["groupid"]} $searchstring ORDER BY rulename LIMIT 0,250";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	$tpl=new templates();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	
	if($GLOBALS["VERBOSE"]){echo "<H1>$sql</H1><br>". mysql_num_rows($results)." Entries<hr>";}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$icon="www-web-search-64.png";
		$icon2="folder-network-64.png";
		$color="black";
		$md=md5(serialize($ligne));
		
		$stringtosearch=htmlentities($ligne["stringtosearch"]);
		$delete=imgsimple("delete-64.png",null,"Delete$t('{$ligne["ID"]}','$md')");
	
		$jsedit=$boot->trswitch("Loadjs('$page?js-replace=yes&ID={$ligne["ID"]}')");
		
	
		
		$tr[]="
		<tr style='color:$color' id='$md'>
		<td width=1% nowrap $jsedit style='vertical-align:middle' nowrap><img src='img/$icon'></td>
		<td width=80% $jsedit style='vertical-align:middle'>
			<span style='font-size:18px;font-weight:bold'>{$ligne["rulename"]}</span>
		</td>
		
		<td width=1% nowrap $jsedit style='vertical-align:middle' nowrap>
			<span style='font-size:18px;font-weight:bold'>$stringtosearch</span>
		</td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
	}	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{rule}</th>
					<th >{search}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
<script>
 var FreeWebIDMEM$t='';
var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	$('#'+FreeWebIDMEM$t).remove();
}
	
function Delete$t(ID,md){
	FreeWebIDMEM$t=md;
	if(confirm('Remove '+ID+'?')){
		var XHR = new XHRConnection();
		XHR.appendData('replace-delete',ID);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}</script>			 					 		
";	
	
}

function replace_delete(){
	$ID=$_POST["replace-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE reverse_www SET replaceid=0 WHERE replaceid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("UPDATE reverse_dirs SET replaceid=0 WHERE replaceid=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM nginx_replace WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
}


