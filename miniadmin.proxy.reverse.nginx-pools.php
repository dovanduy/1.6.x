<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}

if($_GET["section"]){pools_section();exit;}
if(isset($_GET["poolid-js"])){pools_js();exit;}
if(isset($_GET["poolid-popup"])){pools_popup();exit;}
if(isset($_POST["poolname"])){pools_save();exit;}
if(isset($_GET["pools-search"])){pools_search();exit;}
if(isset($_GET["pools-delete"])){pools_delete();exit;}
if(isset($_GET["poolid-tabs"])){pools_tab();exit;}
if(isset($_GET["poolid-sources-search"])){pools_sources_search();exit;}

if(isset($_GET["poolid-sources-section"])){pools_sources_section();exit;}
if(isset($_GET["poolid-sources-link-js"])){pools_sources_js();exit;}
if(isset($_GET["poolid-sources-popup"])){pools_sources_popup();exit;}
if(isset($_POST["pools-source-delete"])){pools_sources_delete();exit;}
if(isset($_POST["pools-source-up"])){pools_sources_up();exit;}
if(isset($_POST["pools-source-down"])){pools_sources_down();exit;}
if(isset($_POST["pool-source-id"])){pools_sources_save();exit;}

pools_section();

function pools_sources_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$poolid=$_GET["pool-id"];
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{link_source}", "Loadjs('$page?poolid-sources-link-js=0&pool-id=$poolid')"));
	echo $boot->SearchFormGen("servername","poolid-sources-search","&pool-id=$poolid",$EXPLAIN);	
	
}

function pools_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_pool}", "Loadjs('$page?poolid-js=0')"));
	echo $boot->SearchFormGen("poolname","pools-search",null,$EXPLAIN);

}

function pools_sources_up(){
	$ID=$_POST["pools-source-up"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_pools_list WHERE ID='$ID'"));
	$zOrder=$ligne["zorder"];
	
	$poolid=$ligne["poolid"];
	if($zOrder>0){
			
			$zOrder=$zOrder-1;
			$FrontOrder=$ligne["zorder"]+1;
			
	}else{return;}
	$q->QUERY_SQL("UPDATE nginx_pools_list SET zorder=$zOrder WHERE ID=$ID");
	$q->QUERY_SQL("UPDATE nginx_pools_list SET zorder=$FrontOrder WHERE 
			zorder=$zOrder AND poolid=$poolid AND ID!=$ID");
	
	
	$sql="SELECT ID FROM nginx_pools_list WHERE poolid=$poolid ORDER BY zorder";
	$c=1;
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("UPDATE nginx_pools_list SET zorder=$c WHERE ID={$ligne["ID"]}");
		$c++;
	}
	
}
function pools_sources_down(){
	$ID=$_POST["pools-source-down"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_pools_list WHERE ID='$ID'"));
	$zOrder=$ligne["zorder"];

	
	$poolid=$ligne["poolid"];
	$zOrder=$zOrder+2;
	
	
			
	
	$q->QUERY_SQL("UPDATE nginx_pools_list SET zorder=$zOrder WHERE ID=$ID");
	
	if(!$q->ok){echo $q->mysql_error;}

	$sql="SELECT ID FROM nginx_pools_list WHERE poolid=$poolid ORDER BY zorder";
	$c=1;
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$q->QUERY_SQL("UPDATE nginx_pools_list SET zorder=$c WHERE ID={$ligne["ID"]}");
		$c++;

	}

}
function pools_sources_search(){
	$prox=new squid_reverse();
	$searchstring=string_to_flexquery("pools-search");
	$q=new mysql_squid_builder();
	$poolid=$_GET["pool-id"];
	$nginx_pools="( SELECT nginx_pools_list.*,reverse_sources.servername,reverse_sources.ipaddr,reverse_sources.port FROM nginx_pools_list,reverse_sources
			WHERE nginx_pools_list.sourceid=reverse_sources.ID
			AND nginx_pools_list.poolid=$poolid) as t";
	
	$sql="SELECT * FROM $nginx_pools WHERE 1 $searchstring ORDER BY zorder LIMIT 0,250";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}
	$tpl=new templates();
	$deleteTXT=$tpl->javascript_parse_text("{delete}");
	$t=time();
	
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	
		$icon="64-network-server.png";
	
		$color="black";
		$md=md5(serialize($ligne));
		$servername=$ligne["servername"] ." [{$ligne["ipaddr"]}:{$ligne["port"]}]";
		$delete=imgsimple("delete-64.png",null,"Delete$t('{$ligne["ID"]}','$md')");
		
		$upd=imgtootltip("arrow-up-32.png",null,"Up$t('{$ligne["ID"]}')");
		$down=imgtootltip("arrow-down-32.png",null,"Down$t('{$ligne["ID"]}')");
	
		$jsedit=$boot->trswitch("Loadjs('$page?poolid-sources-link-js={$ligne["ID"]}&pool-id=$poolid')");
		$zorder=$ligne["zorder"];
		$tr[]="
		<tr style='color:$color' id='$md'>
		<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/$icon'></td>
		<td width=80% $jsedit style='vertical-align:middle'><span style='font-size:18px;font-weight:bold'>$servername</span></td>
		<td width=1% nowrap $jsedit style='vertical-align:middle;text-align:center !important'><span style='font-size:22px;font-weight:bold'>$zorder</span></td>
		
		<td width=1% nowrap style='vertical-align:middle;text-align:center !important'>$upd</td>
		<td width=1% nowrap style='vertical-align:middle;text-align:center !important'>$down</td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
	
	
	
	}
	
	$page=CurrentPageName();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{source}</th>
					<th colspan=3>{order}</th>
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
	
function Delete$t(id,md){
	FreeWebIDMEM$t=md;
	if(confirm('$deleteTXT')){
		var XHR = new XHRConnection();
		XHR.appendData('pools-source-delete',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
var xUp$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
}	

function Up$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('pools-source-up',ID);
		XHR.sendAndLoad('$page', 'POST',xUp$t);

}
function Down$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('pools-source-down',ID);
		XHR.sendAndLoad('$page', 'POST',xUp$t);

}
	</script>
	";
	
	
	
	}


function pools_sources_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$sourceid=$_GET["poolid-sources-link-js"];
	if(!is_numeric($sourceid)){$sourceid=0;}
	$nginx_pools="( SELECT nginx_pools_list.*,reverse_sources.servername FROM nginx_pools_list,reverse_sources WHERE nginx_pools_list.sourceid=reverse_sources.ID ) as t";	
	$title="{link_source}";
	$poolid=$_GET["pool-id"];
	
	if($sourceid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM $nginx_pools WHERE ID='$sourceid'"));
		$title=$ligne["servername"];
		$title=$tpl->javascript_parse_text($title);
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(670,'$page?poolid-sources-popup=yes&sourceid=$sourceid&pool-id=$poolid','$title')";
}
function pools_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$poolid=$_GET["poolid-js"];
	if(!is_numeric($poolid)){$poolid=0;}
	$title="{new_source}";
	
	if($poolid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='$poolid'"));
		
		$title=$ligne["poolname"];
		$title=$tpl->javascript_parse_text($title);
		echo "YahooWin(990,'$page?poolid-tabs=yes&pool-id=$poolid','$title')";
		return;
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(990,'$page?poolid-popup=yes&pool-id=$poolid','$title')";	
	
}

function pools_tab(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	$poolid=$_GET["pool-id"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='$poolid'"));
	$title=$ligne["poolname"];
	$page=CurrentPageName();
	
	$array[$title]="$page?poolid-popup=$poolid&pool-id=$poolid";
	$array["{sources}"]="$page?poolid-sources-section=$poolid&pool-id=$poolid";
	echo $boot->build_tab($array);	
	
}

function pools_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$poolid=$_GET["pool-id"];
	if(!is_numeric($poolid)){$poolid=0;}

	$title="{new_pool}";
	$bt="{add}";
	if($poolid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_pools WHERE ID='$poolid'"));
		$title=$ligne["poolname"];
		$bt="{apply}";
	}
	
	$boot=new boostrap_form();
	$boot->set_hidden("poolid", $poolid);
	
	$hashtype[null]="{default}";
	$hashtype["ip_hash"]="ip_hash";
	$hashtype["least_conn"]="least_conn";
	if(!is_numeric($ligne["keepalive"])){$ligne["keepalive"]=0;}
	$boot->set_formtitle($title);
	$boot->set_field("poolname", "{name}", $ligne["poolname"]);
	$boot->set_list("hashtype", "{type}", $hashtype,$ligne["hashtype"]);
	$boot->set_field("keepalive", "{keepalive}", $ligne["keepalive"]);
	
	$boot->set_button($bt);
	if($poolid==0){$boot->set_CloseYahoo("YahooWin");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
}

function pools_sources_popup(){
	$ID=$_GET["sourceid"];
	$q=new mysql_squid_builder();

	if(!is_numeric($ID)){$ID=0;}
	
	$title="{new_source}";
	$bt="{add}";
	if($ID>0){
		$q=new mysql_squid_builder();
		
		$nginx_pools="( SELECT nginx_pools_list.*,reverse_sources.servername FROM nginx_pools_list,reverse_sources WHERE nginx_pools_list.sourceid=reverse_sources.ID ) as t";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM $nginx_pools WHERE ID='$ID'"));
		$title=$ligne["servername"];
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_pools_list WHERE ID='$ID'"));
		$bt="{apply}";
	}
	
	$boot=new boostrap_form();
	$boot->set_hidden("pool-source-id", $ID);
	$boot->set_hidden("poolid", $_GET["pool-id"]);
	
	$sql="SELECT ID,servername FROM reverse_sources";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sources[$ligne2["ID"]]=$ligne2["servername"];
	}
	
	$boot->set_formtitle($title);
	
	$backuptype["none"]="{default}";
	$backuptype["backup"]="backup";
	$backuptype["down"]="down";
	if(!is_numeric($ligne["max_fails"])){$ligne["max_fails"]=3;}
	if(!is_numeric($ligne["fail_timeout"])){$ligne["fail_timeout"]=30;}
	
	
	$boot->set_list("sourceid", "{source}", $sources,$ligne["sourceid"]);
	$boot->set_button($bt);
	$boot->set_field("max_fails", "{max_failed}", $ligne["max_fails"]);
	$boot->set_field("fail_timeout", "{connect_timeout} ({seconds})", $ligne["fail_timeout"]);
	$boot->set_list("backuptype", "{type}", $backuptype,$ligne["backuptype"]);
	
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
}

function pools_sources_delete(){
	$ID=$_POST["pools-source-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_pools_list WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
}

function websites_directories_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$folderid=$_GET["folderid"];
	$servername=$_GET["servername"];
	if(!is_numeric($folderid)){$folderid=0;}
	$title="$servername::{new_directory}";
	if($folderid>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT directory FROM reverse_dirs WHERE folderid='$folderid'"));
		$title="$servername::{$ligne["directory"]}";
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(700,'$page?website-directory-popup=yes&folderid=$folderid&servername=$servername','$title')";	
}

function websites_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$add="popup-webserver";
	
	$title="{new_website}";
	if($servername<>null){
		$title=$servername;
		$add="popup-webserver-tabs";
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(800,'$page?$add&servername=$servername','$title')";	
	
}

function delete_folder_id_js(){
	header("content-type: application/x-javascript");
	$folderid=$_GET["delete-folder-id-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT directory FROM reverse_dirs WHERE folderid='$folderid'"));
	$directory=$tpl->javascript_parse_text("{delete} {$ligne["directory"]} ?");
	$t=time();
	$html="
var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}	
	ExecuteByClassName('SearchFunction');
}	
		
function Delete$t(){
	if( !confirm('$directory') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-folder-id-perform','$folderid');
    XHR.sendAndLoad('$page', 'POST',xDelete$t);
}			
			
	Delete$t();";
	echo $html;
}

function pools_delete(){
	$ID=$_POST["pools-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_pools_list WHERE poolid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$q->QUERY_SQL("DELETE FROM nginx_pools WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$q->QUERY_SQL("UPDATE FROM reverse_www SET poolid='0' WHERE poolid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}

	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
}






function websites_directories_delete(){
	$folderid=$_POST["delete-folder-id-perform"];
	$q=new mysql_squid_builder();
	$sql="DELETE FROM reverse_dirs  WHERE folderid=$folderid";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
	
}



function pools_save(){
	$poolid=$_POST["poolid"];
	unset($_POST["poolid"]);
	$revers=new squid_reverse();

	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$html=new htmltools_inc();
	$_POST["poolname"]= $html->StripSpecialsChars($_POST["poolname"]);

	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string($value)."'";
		$edit[]="`$key`='".mysql_escape_string($value)."'";
		
	}
	
	if($poolid>0){
		$sql="UPDATE nginx_pools SET ".@implode(",", $edit)." WHERE ID=$poolid";
	}else{
		$sql="INSERT IGNORE INTO nginx_pools (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
		
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");
}

function pools_sources_save(){
	$ID=$_POST["pool-source-id"];
	unset($_POST["pool-source-id"]);
	$revers=new squid_reverse();
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string($value)."'";
		$edit[]="`$key`='".mysql_escape_string($value)."'";
	
	}
	
	if($ID>0){
		$sql="UPDATE nginx_pools_list SET ".@implode(",", $edit)." WHERE ID=$ID";
	}else{
		$sql="INSERT IGNORE INTO nginx_pools_list (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
}

function source_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM reverse_sources WHERE ID='{$_POST["source-delete"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
}



function GetPrivs(){
		$users=new usersMenus();
		if($users->AsSystemWebMaster){return true;}
		if($users->AsSquidAdministrator){return true;}
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function pools_search(){

	$prox=new squid_reverse();
	$searchstring=string_to_flexquery("pools-search");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM nginx_pools WHERE 1 $searchstring ORDER BY poolname LIMIT 0,250";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){senderror($q->mysql_error);}
	$tpl=new templates();
	$deleteTXT=$tpl->javascript_parse_text("{delete}");
	$t=time();
	
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){

		$icon="64-cluster.png";
		
		$color="black";
		$md=md5(serialize($ligne));
		$poolname=$ligne["poolname"];
		$delete=imgsimple("delete-64.png",null,"Delete$t('{$ligne["ID"]}','$md')");

		$jsedit=$boot->trswitch("Loadjs('$page?poolid-js={$ligne["ID"]}&pool-id={$ligne["ID"]}')");
		

		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) AS Tcount FROM nginx_pools_list WHERE poolid='{$ligne["ID"]}'"));
		$CountDePools=$ligne2["Tcount"];
		$tr[]="
		<tr style='color:$color' id='$md'>
			<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/$icon'></td>
			<td width=80% $jsedit style='vertical-align:middle'><span style='font-size:18px;font-weight:bold'>$poolname</span></td>
			<td width=1% nowrap $jsedit style='vertical-align:middle;text-align:center !important'><span style='font-size:22px;font-weight:bold'>$CountDePools</span></td>

			
			<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";



	}
	
	$page=CurrentPageName();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th colspan=2>{name}</th>
					<th >{backends}</th>
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

function Delete$t(id,md){
	FreeWebIDMEM$t=md;
	if(confirm('$deleteTXT')){
		var XHR = new XHRConnection();
		XHR.appendData('pools-delete',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}

</script>
";



}

function parameters(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$squid=new squidbee();
	if(!$users->AsSquidAdministrator){
		senderror("{ERROR_NO_PRIVS}");
		return;
	}
	
	$sock=new sockets();
	$SquidReverseDefaultWebSite=$sock->GET_INFO("SquidReverseDefaultWebSite");
	$SquidReverseDefaultCert=$sock->GET_INFO("SquidReverseDefaultWebSite");
	if($SquidReverseDefaultWebSite==null){$SquidReverseDefaultWebSite=$squid->visible_hostnameF();}
	$boot->set_formtitle("{global_parameters}");
	$boot->set_field("SquidReverseDefaultWebSite","{default_website}",  "$SquidReverseDefaultWebSite");
	$sql="SELECT CommonName FROM sslcertificates ORDER BY CommonName";
	$q=new mysql();
	$sslcertificates[null]="{default}";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligneZ=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sslcertificates[$ligneZ["CommonName"]]=$ligneZ["CommonName"];
	}
	
	
	$boot->set_list("certificate_center", "{default_certificate}", $sslcertificates,$squid->certificate_center);	
	$boot->set_button("{apply}");
	echo $boot->Compile();
	
}
function parameters_save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidReverseDefaultWebSite", $_POST["SquidReverseDefaultWebSite"]);
	$squid=new squidbee();
	$squid->certificate_center=$_POST["certificate_center"];
	$squid->SaveToLdap();
	
}



function div_groupware($text,$enabled){
	$color_orange="#B64B13";
	if($enabled==0){$color_orange="#8C8C8C";}

	return $GLOBALS["CLASS_TPL"]->_ENGINE_parse_body("<div style=\"font-size:14px;font-weight:bold;font-style:italic;color:$color_orange;margin:0px;padding:0px\">$text</div>");
}

function build_icon($ligne,$servername=null){
	$icon="domain-main-64.png";
	if($ligne["groupware"]<>null){
		if(isset($GLOBALS["IMG_ARRAY_64"])){
			$icon=$GLOBALS["IMG_ARRAY_64"][$ligne["groupware"]];
		}
	}
	if(trim($ligne["resolved_ipaddr"])==null){
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $servername)){$icon="domain-main-64-grey.png";}
	}
	return $icon;

}

function websites_directories_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_directory}", 
			"Loadjs('$page?website-directory-js=yes&servername=$servername&folderid=')"));
	echo $boot->SearchFormGen("directory","directories-search","&servername=$servername",$EXPLAIN);	
	
}
function websites_directories_search(){
	$searchstring=string_to_flexquery("directories-search");
	$q=new mysql_squid_builder();
	
	$servername=$_GET["servername"];
	$sql="SELECT * FROM reverse_dirs WHERE servername='$servername' $searchstring ORDER BY directory LIMIT 0,250";
	$results=$q->QUERY_SQL($sql);
	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$icon="folder-move-64.png";
		$arrow="arrow-right-64.png";
		
		$md=md5(serialize($ligne));
		
		
		$delete=imgsimple("delete-48.png",null,"Loadjs('$page?delete-folder-id-js={$ligne["folderid"]}');");
		$jsEditWW=$boot->trswitch("Loadjs('website-js=yes&servername=$servername_enc')");
		$jsedit=$boot->trswitch("Loadjs('$page?website-directory-js=yes&servername=$servername&folderid={$ligne["folderid"]}')");
		$jseditA=$jsedit;
	
		
	
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
		$destination="{$ligne2["servername"]}:{$ligne2["port"]}";
		$jseditS=$boot->trswitch("Loadjs('$page?js-source=yes&source-id={$ligne["cache_peer_id"]}')");
		
	
			
	
		$directory=$ligne["directory"];
		$hostweb="<div><i>{$ligne["hostweb"]}</i></div>";
	
		$tr[]="
		<tr style='color:$color' id='$md'>
		<td width=1% nowrap $jsedit><img src='img/$icon'></td>
		<td width=80% $jsedit><span style='font-size:16px;font-weight:bold'>$directory</span></td>
		<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/$arrow'></td>
		<td width=1% nowrap $jsedit style='vertical-align:middle'>
			<span style='font-size:16px;font-weight:bold'>$destination</span>$hostweb
		</td>
			
			
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
	
	
	
	}
	
	
	$t=time();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("
<table class='table table-bordered table-hover'>
	<thead>
		<tr>
			<th colspan=2>{directory}</th>
			<th>{destination}</th>
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
	
function Delete$t(server,md){
	FreeWebIDMEM$t=md;
	if(confirm('$delete_freeweb_text')){
		var XHR = new XHRConnection();
		XHR.appendData('website-delete',server);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
</script>
";
	
	
	
	}
	