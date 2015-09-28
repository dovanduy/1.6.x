<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');


if(isset($_GET["js"])){js();exit;}

$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){$tpl=new templates();echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["system"])){menu_system();exit;}
if(isset($_GET["change-hostname-js"])){change_hostname_js();exit;}
if(isset($_GET["reboot-proxy-js"])){reboot_proxy_js();exit;}
if(isset($_GET["reconfigure-proxy-jss"])){reconfigure_proxy_js();exit;}


if(isset($_GET["reboot-js"])){reboot_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["reload-proxy-js"])){reload_proxy_js();exit;}
if(isset($_GET["add-tag-js"])){add_tag_js();exit;}
if(isset($_GET["philesight-js"])){philesight_js();exit;}
if(isset($_GET["snapshot-js"])){snapshot_js();exit;}
if(isset($_GET["snapshot-restore-js"])){snapshot_restore_js();exit;}
if(isset($_GET["send-ping-js"])){send_ping_js();exit;}
if(isset($_GET["activedirectory-reconnect-js"])){reconnect_ad_js();}
if(isset($_GET["activedirectory-emergency-enable-js"])){emergency_enable_ad_js();}
if(isset($_GET["activedirectory-emergency-disable-js"])){emergency_disable_ad_js();}

if(isset($_GET["reconfigure-proxy-js"])){reconfigure_proxy_js();exit;}



if(isset($_GET["root-password-js"])){root_password_js();exit;}

if(isset($_GET["manager-password-popup"])){manager_password_popup();exit;}
if(isset($_GET["manager-password-js"])){manager_password_js();exit;}


if(isset($_GET["clean-proxy-caches-js"])){clean_proxy_cache_js();exit;}
if(isset($_GET["reindex-proxy-caches-js"])){reindex_proxy_cache_js();exit;}

if(isset($_POST["manager-password"])){manager_password_save();exit;}
if(isset($_POST["root_password"])){root_password_save();exit;}
if(isset($_POST["philesight"])){philesight_save();exit;}
if(isset($_POST["snapshot"])){snapshot_save();exit;}
if(isset($_POST["snapshot-restore"])){snapshot_restore_save();exit;}
if(isset($_POST["send-ping"])){send_ping();exit;}


if(isset($_POST["reboot"])){reboot_save();exit;}
if(isset($_POST["reboot-squid"])){reboot_proxy_save();exit;}
if(isset($_POST["clean-proxy-caches"])){clean_proxy_cache_save();exit;}
if(isset($_POST["reindex-proxy-caches"])){reindex_proxy_cache_save();exit;}
if(isset($_POST["reload-proxy"])){reload_proxy_cache_save();exit;}
if(isset($_POST["tag"])){add_tag_save();exit;}

if(isset($_POST["activedirectory-reconnect"])){reconnect_ad_save();exit;}
if(isset($_POST["activedirectory-emergency-enable"])){emergency_enable_ad();exit;}
if(isset($_POST["activedirectory-emergency-disable"])){emergency_disable_ad();exit;}



if(isset($_POST["reconfigure-proxy"])){reconfigure_proxy();exit;}



if(isset($_POST["delete"])){delete_save();exit;}
if(isset($_POST["ChangeHostName"])){change_hostname_save();exit;}
if(isset($_GET["proxy-service"])){menu_proxy_service_tabs();exit;}
if(isset($_GET["proxy-service-orders"])){menu_proxy_service();exit;}





js();

function manager_password_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$page=CurrentPageName();
	$tpl=new templates();
	
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("{global_admin_account}");
	echo "YahooWin4(650,'$page?manager-password-popup=yes&gpid={$_GET["gpid"]}&uuid=".urlencode($_GET["uuid"])."','$text');";
}


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{events}");
	$page=CurrentPageName();
	$artica_meta=new mysql_meta();
	if(isset($_GET["uuid"])){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
		$tag=$artica_meta->uuid_to_tag($_GET["uuid"]);
	}
	if(isset($_GET["gpid"])){
		$hostname=$artica_meta->gpid_to_name($_GET["gpid"]);
		$tag=$artica_meta->group_count($_GET["gpid"]) ." ".$tpl->javascript_parse_text("{computers}");
	}
	
	$_GET["gpid"]=intval($_GET["gpid"]);
	echo "YahooWin3('1250','$page?tabs=yes&gpid={$_GET["gpid"]}&uuid=".urlencode($_GET["uuid"])."','$hostname - $tag')";
}


function root_password_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
		$text=$tpl->javascript_parse_text("{root_password2}");
		
	
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
}
	
function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	var password=prompt('$text');
	if(!password){return;}
	XHR.appendData('root_password',encodeURIComponent(password));
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
xFunct$t();
";
	echo $html;	
	
}


function reconfigure_proxy_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("{reconfigure_proxy_service}")."\\n$hostname";
	
	
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
}
	
function xFunct$t(){
	if(!confirm('$text ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reconfigure-proxy','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
xFunct$t();
	";
	echo $html;
		
	
	
}

function reboot_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("{reboot_ask_uuid}");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();

}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reboot','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
echo $html;

}

function add_tag_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	$tag=$artica_meta->uuid_to_tag($_GET["uuid"]);
	$title=$tpl->javascript_parse_text("{add_tag}");
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	
	}
	
	function xFunct$t(){
	var tag=prompt('$hostname:$title','$tag');
	if(!tag){return;}
	var XHR = new XHRConnection();
	XHR.appendData('tag',tag);
	XHR.appendData('uuid','{$_GET["uuid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;	
	
}

function add_tag_save(){
	$q=new mysql_meta();
	$uuid=$_POST["uuid"];
	$q->CheckTables();
	$sql="SELECT hostag FROM metahosts WHERE uuid='$uuid'";
	$tag=mysql_escape_string2($_POST["tag"]);
	$q->QUERY_SQL("UPDATE metahosts SET `hostag`='$tag' WHERE uuid='$uuid'");
	if(!$q->ok){echo $q->mysql_error;}
}

function reconnect_ad_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if(intval($_GET["gpid"])==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("{reconnect_activedirectroy_ask}");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
}
	
function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('activedirectory-reconnect','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
xFunct$t();
	";
	echo $html;	
	
}

function emergency_enable_ad_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if(intval($_GET["gpid"])==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("{enable_emergency_mode} (Active directory)");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	}
	
	function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('activedirectory-emergency-enable','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	xFunct$t();
	";
	echo $html;	
}
function emergency_disable_ad_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if(intval($_GET["gpid"])==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("{disable_emergency_mode} (Active directory)");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
}
	
function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('activedirectory-emergency-disable','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
xFunct$t();
";
echo $html;	
}

function reload_proxy_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if(intval($_GET["gpid"])==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("{reload_proxy_service_ask}");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
}
	
function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reload-proxy','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
xFunct$t();
";
echo $html;
	
	
}

function reboot_proxy_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if(intval($_GET["gpid"])==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("$hostname:: {APP_SQUID}::{restart_service} ?");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	
	}
	
	function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reboot-squid','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;	
	
}

function clean_proxy_cache_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	
	$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	if(intval($_GET["gpid"])>0){
		$hostname=$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	
	$text=$tpl->javascript_parse_text("$hostname:: {APP_SQUID}::{clean_proxy_cache_explain} ?");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	
	}
	
	function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('clean-proxy-caches','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;	
	
}

function reindex_proxy_cache_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if(intval($_GET["gpid"])==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("$hostname:: {APP_SQUID}::{reindex_caches_warn} ?");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	
	}
	
	function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('reindex-proxy-caches','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;
		
}
function philesight_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("$hostname: {rescan} {directories_monitor}");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	YahooWin3Hide();
	}
	
	function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('philesight','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;	
	
}

function snapshot_restore_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}:\\n---------------\\n").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	
	$q=new mysql_meta();
	$sql="SELECT zDate FROM snapshots WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$xdate=$tpl->time_to_date(strtotime($ligne["zDate"]),true);
	
	
	
	
	$text=$tpl->javascript_parse_text("$hostname:\\n{ask_to_restore_a_snapshot_from_meta}\\n[$xdate]");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	YahooWin3Hide();
}
	
function xFunct$t(){
	if(!confirm('$text ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('snapshot-restore','{$_GET["zmd5"]}');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
	
xFunct$t();
";
echo $html;
}

function send_ping_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	
	}
	
function xFunct$t(){
	var XHR = new XHRConnection();
	XHR.appendData('send-ping','yes}');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
	
xFunct$t();
";
echo $html;	
	
}



function snapshot_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	$text=$tpl->javascript_parse_text("$hostname: {ask_to_send_a_snapshot_to_meta}");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	YahooWin3Hide();
}
	
function xFunct$t(){
	if(!confirm('$text ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('snapshot','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}
	
xFunct$t();
";
	echo $html;	
}

function delete_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	$text=$tpl->javascript_parse_text("{delete_ask_uuid}");
	$text=str_replace("%s", $hostname, $text);
	$page=CurrentPageName();
	$t=time();
	$html="
	var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	YahooWin3Hide();
	}
	
	function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','yes');
	XHR.appendData('uuid','{$_GET["uuid"]}');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
	}
	
	xFunct$t();
	";
	echo $html;	
	
}

function change_hostname_js(){
	header("content-type: application/x-javascript");
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	$changehostname_text=$tpl->javascript_parse_text("{ChangeHostName}");
	$page=CurrentPageName();
	$t=time();	
$html="
var x_ChangeHostName$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
		
}

function ChangeHostName$t(){
	var hostname=prompt('$changehostname_text','$hostname');
	if(hostname){
		var XHR = new XHRConnection();
		XHR.appendData('ChangeHostName',hostname);
		XHR.appendData('uuid','{$_GET["uuid"]}');
		LockPage();
		XHR.sendAndLoad('$page', 'POST',x_ChangeHostName$t);
	}

}

ChangeHostName$t();
";	

echo $html;
	
}


function reboot_save(){
	$uuid=$_POST["uuid"];
	$meta=new mysql_meta();
	
	$gpid=$_POST["gpid"];
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "PHILESIGHT")){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	
	
	if(!$meta->CreateOrder($uuid, "REBOOT")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}
}
function philesight_save(){
	$uuid=$_POST["uuid"];
	
	$meta=new mysql_meta();
	
	$gpid=$_POST["gpid"];
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "PHILESIGHT")){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	
	
	if(!$meta->CreateOrder($uuid, "PHILESIGHT")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}
}

function snapshot_save(){
	$uuid=$_POST["uuid"];
	
	$meta=new mysql_meta();
	
	$gpid=$_POST["gpid"];
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "SNAPSHOT")){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	if(!$meta->CreateOrder($uuid, "SNAPSHOT")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
}
function snapshot_restore_save(){
	$uuid=$_POST["uuid"];
	$zmd5=$_POST["snapshot-restore"];
	$meta=new mysql_meta();
	$array["ZMD5"]=$zmd5;
	
	$gpid=$_POST["gpid"];
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "SNAPSHOT_RESTORE",$array)){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	if(!$meta->CreateOrder($uuid, "SNAPSHOT_RESTORE",$array)){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
	
}

function send_ping(){
	$sock=new sockets();
	$uuid=$_POST["uuid"];
	$gpid=intval($_POST["gpid"]);
	$artica_meta=new mysql_meta();
	$tpl=new templates();
	if($gpid>0){
		$sock->getFrameWork("artica.php?meta-ping-group=$gpid");
		$tpl=new templates();
		$hostn=$artica_meta->gpid_to_name($gpid);
		echo $tpl->javascript_parse_text("$hostn\n----------------------------------------------------------\n{send_ping}\n{success}");
		return;
	}

	
	$uuidenc=urlencode($uuid);
	$sock->getFrameWork("artica.php?meta-ping-host=$uuidenc");
	
	$hostn=$artica_meta->uuid_to_host($uuid).' ('.$artica_meta->uuid_to_tag($uuid)."')";
	echo $tpl->javascript_parse_text("$hostn\n----------------------------------------------------------\n{send_ping}\n{success}",1);
	
	
}

function clean_proxy_cache_save(){
	$uuid=$_POST["uuid"];
	$gpid=intval($_POST["gpid"]);
	$meta=new mysql_meta();
	
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "CLEAN_PROXY_CACHES")){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}	
	
	
	if(!$meta->CreateOrder($uuid, "CLEAN_PROXY_CACHES")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
}
function reindex_proxy_cache_save(){
	$uuid=$_POST["uuid"];
	$gpid=intval($_POST["gpid"]);
	if($gpid>0){
		$meta=new mysql_meta();
		if(!$meta->CreateOrder_group($gpid, "REINDEX_PROXY_CACHES")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}
		return;
	}
	
	$meta=new mysql_meta();
	if(!$meta->CreateOrder($uuid, "REINDEX_PROXY_CACHES")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
}

function reload_proxy_cache_save(){
	$uuid=$_POST["uuid"];
	
	$gpid=intval($_POST["gpid"]);
	if($gpid>0){
		$meta=new mysql_meta();
		if(!$meta->CreateOrder_group($gpid, "RELOAD_PROXY")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}
		return;
	}
	
	
	$meta=new mysql_meta();
	if(!$meta->CreateOrder($uuid, "RELOAD_PROXY")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
}

function reconnect_ad_save(){
	$uuid=$_POST["uuid"];
	$gpid=intval($_POST["gpid"]);
	$meta=new mysql_meta();
	if($gpid>0){if(!$meta->CreateOrder_group($gpid, "RECONNECT_AD")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}return;}
	if(!$meta->CreateOrder($uuid, "RECONNECT_AD")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}	
	
}

function emergency_enable_ad(){
	$uuid=$_POST["uuid"];
	$gpid=intval($_POST["gpid"]);
	$meta=new mysql_meta();
	if($gpid>0){if(!$meta->CreateOrder_group($gpid, "URGENCY_AD")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}return;}
	if(!$meta->CreateOrder($uuid, "URGENCY_AD")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}
}
function emergency_disable_ad(){
	$uuid=$_POST["uuid"];
	$gpid=intval($_POST["gpid"]);
	$meta=new mysql_meta();
	if($gpid>0){if(!$meta->CreateOrder_group($gpid, "URGENCY_NOAD")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}return;}
	if(!$meta->CreateOrder($uuid, "URGENCY_NOAD")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}
}
function reconfigure_proxy(){
	$uuid=$_POST["uuid"];
	$gpid=intval($_POST["gpid"]);
	$meta=new mysql_meta();
	
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "PROXY_APPLY_PARAMS")){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	
	
	if(!$meta->CreateOrder($uuid, "PROXY_APPLY_PARAMS")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}
		
	
}


function reboot_proxy_save(){
	$uuid=$_POST["uuid"];
	
	$gpid=intval($_POST["gpid"]);
	if($gpid>0){
		$meta=new mysql_meta();
		if(!$meta->CreateOrder_group($gpid, "RESTART_PROXY")){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}
		return;
	}
	
	
	$meta=new mysql_meta();
	if(!$meta->CreateOrder($uuid, "RESTART_PROXY")){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
}

function delete_save(){
	//$GLOBALS["VERBOSE"]=true;
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$uuid=$_POST["uuid"];
	$meta=new mysql_meta();
	if(!$meta->DeleteUuid($uuid)){echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);}
}

function change_hostname_save(){
	
	$uuid=$_POST["uuid"];
	$hostname=$_POST["ChangeHostName"];
	$meta=new mysql_meta();
	$meta->QUERY_SQL("UPDATE metahosts SET `hostname`='$hostname' WHERE uuid='$uuid'");
	if(!$q->ok){echo $q->mysql_error;return;}
	if(!$meta->CreateOrder($uuid, "CHANGE_HOSTNAME",array("VALUE"=>$hostname))){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}
	
	
}

function root_password_save(){
	$uuid=$_POST["uuid"];
	$gpid=$_POST["gpid"];
	$password=url_decode_special_tool($_POST["root_password"]);
	$meta=new mysql_meta();
	
	
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "ROOT_PASSWORD",array("VALUE"=>$password))){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	
	if(!$meta->CreateOrder($uuid, "ROOT_PASSWORD",array("VALUE"=>$password))){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}	
	
}

function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$array["system"]='{system}';
	
	if($_GET["gpid"]==0){
		$isProxy=$artica_meta->isProxy($_GET["uuid"]);
		if($isProxy){ $array["proxy-service"]='{proxy_service}'; }
		$array["services"]='{services}';
		$array["snapshots"]='{snapshots}';
		$array["cloning"]='{cloning}';
		$array["snapshots-schedule"]='{schedules}';
	}else{
		$isProxy=$artica_meta->isProxyGroup($_GET["gpid"]);
		if($isProxy){ $array["proxy-service"]='{proxy_service}'; }
		
	}
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="services"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.services.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="snapshots"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.snapshots.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="cloning"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.cloning.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="snapshots-schedule"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.schedules.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "meta-hosts-{$_GET["uuid"]}{$_GET["gpid"]}");
	
}


function menu_system(){
	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$sock=new sockets();
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	$gpid=$_GET["gpid"];
	$LicenseText=null;
	if($gpid==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
		$ArticaVersion=$artica_meta->ArticaVersion($_GET["uuid"]);
		$tag=$artica_meta->uuid_to_tag($_GET["uuid"]);
		$LicenseInfos=$artica_meta->LicenseInfos($_GET["uuid"]);
		$LicenseJs="OnClick=\"javascript:Loadjs('artica-meta.host.license.php?uuid={$_GET["uuid"]}')\"";
		$LICT=" Community Edition";
		if($LicenseInfos["CORP_LICENSE"]){$LICT=" Entreprise Edition";}
		if($LicenseInfos["ExpiresSoon"]>0){if($LicenseInfos["ExpiresSoon"]<31){$LICT="<span style='color:red'>{trial_mode}</span>";}}
		
		
		$LicenseText="<div style='text-align:right;margin-top:-30px;margin-bottom:30px'><i><a href=\"javascript:blur();\" $LicenseJs style='font-size:14px;text-decoration:underline'>v$ArticaVersion - $LICT - {company}:{$LicenseInfos["COMPANY"]}</a></i></div>";
		
		
	}else{
		$hostname=$artica_meta->gpid_to_name($_GET["gpid"]);
		$tag=$artica_meta->group_count($_GET["gpid"]) ." ".$tpl->javascript_parse_text("{computers}");		
	}
	
	
	if($ArticaMetaUseSendClient==1){
		$tr[]=paragrapheFleche("{send_ping}", "Loadjs('$page?send-ping-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	}
	if($gpid==0){$tr[]=paragrapheFleche("{change_hostname}", "Loadjs('$page?change-hostname-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");}
	if($gpid==0){$tr[]=paragrapheFleche("{add_tag}", "Loadjs('$page?add-tag-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");}
	if($gpid==0){
		if(intval($artica_meta->IsAD($_GET["uuid"]))){
			$tr[]=paragrapheFleche("{activedirectroy_reconnection}", "Loadjs('$page?activedirectory-reconnect-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
			$tr[]=paragrapheFleche("{enable_emergency_mode} (Active directory)", "Loadjs('$page?activedirectory-emergency-enable-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
			$tr[]=paragrapheFleche("{disable_emergency_mode} (Active directory)", "Loadjs('$page?activedirectory-emergency-disable-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
		}
	}
	if($gpid>0){
		if($artica_meta->group_is_ad_inside($gpid)){
			$tr[]=paragrapheFleche("{activedirectroy_reconnection}", "Loadjs('$page?activedirectory-reconnect-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
			$tr[]=paragrapheFleche("{enable_emergency_mode} (Active directory)", "Loadjs('$page?activedirectory-emergency-enable-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
			$tr[]=paragrapheFleche("{disable_emergency_mode} (Active directory)", "Loadjs('$page?activedirectory-emergency-disable-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
		}else{
			if($GLOBALS["VERBOSE"]){echo "<H1>AD = FALSE</H1>";}
		}
	}
	$tr[]=paragrapheFleche("{reboot}", "Loadjs('$page?reboot-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{root_password2}", "Loadjs('$page?root-password-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{global_admin_account}", "Loadjs('$page?manager-password-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{install_package}", "Loadjs('artica-meta.packages.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{update_artica}", "Loadjs('artica-meta.update.artica.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	
	
	
	
	$tr[]=paragrapheFleche("{directories_monitor}", "Loadjs('$page?philesight-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	
	$tr[]=paragrapheFleche("{create_a_snapshot}", "Loadjs('$page?snapshot-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{restore_a_snapshot}", "Loadjs('artica-meta.snapshots.browse.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	if($gpid==0){$tr[]=paragrapheFleche("{delete}", "Loadjs('$page?delete-js=yes&uuid=".urlencode($_GET["uuid"])."')");}
	$html=
	"<div style='font-size:26px;margin-bottom:20px'>$hostname - $tag - {$_GET["uuid"]}</div>$LicenseText".
	
	CompileTr3($tr);
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function menu_proxy_service_tabs(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$uuid=$_GET["uuid"];
	$array["proxy-service-orders"]='{orders}';
	$q=new mysql_meta();
	if($_GET["gpid"]==0){
		if($q->TABLE_EXISTS("{$uuid}_WEEK_RTTH")){
			$array["proxy-service-rtt"]="{bandwidth}";
		}
	}
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="proxy-service-rtt"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.proxy.bandwidth.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="snapshots"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.snapshots.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "meta-proxy-service-{$_GET["uuid"]}{$_GET["gpid"]}");
	
}

function menu_proxy_service(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tr[]=paragrapheFleche("{reload_service}", "Loadjs('$page?reload-proxy-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{reconfigure_proxy_service}", "Loadjs('$page?reconfigure-proxy-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{restart_service}", "Loadjs('$page?reboot-proxy-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{clean_all_caches}", "Loadjs('$page?clean-proxy-caches-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{reindex_caches}", "Loadjs('$page?reindex-proxy-caches-js=yes&uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{disable_emergency_mode}", "Loadjs('artica-meta.urgency.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	$tr[]=paragrapheFleche("{enable_emergency_mode}", "Loadjs('artica-meta.urgency-enable.php?uuid=".urlencode($_GET["uuid"])."&gpid={$_GET["gpid"]}')");
	
	if(intval($_GET["gpid"])>0){
		$tr[]=paragrapheFleche("{transparent_whitelist}", "Loadjs('artica-meta.squidtransparent-white.php?gpid={$_GET["gpid"]}')");
		
	}
	
	
	
	$html=CompileTr3($tr);
	
	echo $tpl->_ENGINE_parse_body($html);
}


function manager_password_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$t=time();
	$global_admin_confirm=$tpl->javascript_parse_text("{global_admin_confirm}");
	
	if($_GET["gpid"]==0){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	}else{
		$hostname=$tpl->javascript_parse_text("{computers}: ").$artica_meta->gpid_to_name($_GET["gpid"]);
	}
	
	$html="
<div id='ChangePasswordDivNOtifiy' style='width:98%' class=form>
<table style='width:100%'>
	<tr>
		<td colspan=2 style='border-bottom:1px solid #CCCCCC;padding-top:4px;margin-bottom:20px'>
			<strong style='font-size:32px;'>{global_admin_account}: $hostname</strong>
		</td>
	</tr>
	<tr>
		<td colspan=2 >&nbsp;</td>
	</tr>
	<tr>
		<td align='right' class=legend nowrap style='font-size:22px'>{username}:</strong></td>
		<td align='left'>" . Field_text("change_admin-$t",null,'width:99%;font-size:22px;padding:3px',
					"script:ChangeGlobalAdminPasswordCheck$t(event)")."</td>
	</tr>
	<tr>
		<td align='right' class=legend nowrap class=legend style='font-size:22px'>{password}:</strong></td>
		<td align='left'>" . Field_password("change_password-$t",null,"width:90%;font-size:22px;padding:3px","script:ChangeGlobalAdminPasswordCheck$t(event)")."</td>
	</tr>
	".Field_button_table_autonome("{apply}", "ChangeGlobalAdminPassword$t",26)."
</table>
</div>
<script>
	
function ChangeGlobalAdminPasswordCheck$t(e){
	if(checkEnter(e)){ChangeGlobalAdminPassword$t();}
}

var xChangeGlobalAdminPassword$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	YahooWin4Hide();
}

function ChangeGlobalAdminPassword$t(){
	
	if(!confirm('$global_admin_confirm')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('change_admin',document.getElementById('change_admin-$t').value);
	XHR.appendData('uuid','{$_GET["uuid"]}');
	XHR.appendData('gpid','{$_GET["gpid"]}');
	var password=encodeURIComponent(document.getElementById('change_password-$t').value);
	XHR.appendData('change_password',password);
	XHR.appendData('manager-password','yes');
	XHR.sendAndLoad('$page', 'POST',xChangeGlobalAdminPassword$t);
}
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function manager_password_save(){
	$uuid=$_POST["uuid"];
	$gpid=$_POST["gpid"];
	$password=url_decode_special_tool($_POST["change_password"]);
	$username=$_POST["change_admin"];
	$meta=new mysql_meta();
	
	$ARRAY["USER"]=$username;
	$ARRAY["PASS"]=$password;
	
	$value=base64_encode(serialize($ARRAY));
	
	if($gpid>0){
		if(!$meta->CreateOrder_group($gpid, "MANAGER_CREDS",array("VALUE"=>$value))){
			echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
		}
		return;
	}
	
	if(!$meta->CreateOrder($uuid, "MANAGER_CREDS",array("VALUE"=>$value))){
		echo "Failed\nFunction:".__FUNCTION__."\nLine:".__LINE__."\nFile:".basename(__FILE__);
	}
		
	
}
