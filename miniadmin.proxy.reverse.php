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
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
$PRIV=GetPrivs();if(!$PRIV){senderror("no priv");}

if(isset($_POST["DenyConfSave"])){websites_script_DenyConfSave();exit;}
if(isset($_POST["nginxconf"])){websites_script_nginxconf();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-section"])){parameters_section();exit;}

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["websites-section"])){websites_section();exit;}
if(isset($_GET["websites-search"])){websites_search();exit;}
if(isset($_GET["website-js"])){websites_js();exit;}
if(isset($_GET["website-script-js"])){websites_script_js();exit;}
if(isset($_GET["delete-websites-js"])){websites_delete_js();exit;}
if(isset($_GET["nginx-status"])){nginx_status();exit;}
if(isset($_GET["nginx-section-status"])){nginx_status_section();exit;}


if(isset($_POST["folderid-save"])){websites_directories_save();exit;}

if(isset($_GET["popup-webserver"])){websites_popup();exit;}
if(isset($_GET["popup-webserver-errors"])){websites_popup_webserver_errors();exit;}
if(isset($_POST["servername-errors"])){websites_popup_webserver_errors_save();exit;}


if(isset($_GET["popup-webserver-options"])){websites_popup_webserver_options_tabs();exit;}
if(isset($_GET["popup-webserver-options-section"])){websites_popup_webserver_options_section();exit;}


if(isset($_GET["popup-webserver-events"])){websites_popup_events();exit;}
if(isset($_GET["popup-webserver-events-search"])){websites_popup_events_search();exit;}

if(isset($_GET["popup-webserver-privs"])){websites_privs_section();exit;}
if(isset($_GET["privs-search"])){websites_privs_search();exit;}
if(isset($_POST["linkuser"])){websites_privs_add();exit;}
if(isset($_POST["uid-delete"])){websites_privs_del();exit;}


if(isset($_GET["popup-webserver-tabs"])){websites_popup_tabs();exit;}
if(isset($_POST["servername-edit"])){websites_save();exit;}
if(isset($_POST["website-delete"])){websites_delete();exit;}

if(isset($_GET["popup-webserver-replace"])){websites_popup_webserver_replace_section();exit;}
if(isset($_GET["popup-webserver-replace-search"])){websites_popup_webserver_replace_search();exit;}
if(isset($_GET["popup-webserver-replace-js"])){websites_popup_webserver_replace_js();exit;}
if(isset($_GET["popup-webserver-replace-popup"])){websites_popup_webserver_replace_popup();exit;}
if(isset($_POST["replaceid"])){websites_popup_webserver_replace_save();exit;}
if(isset($_POST["popup-webserver-replace-delete"])){websites_popup_webserver_replace_delete();exit;}

if(isset($_GET["popup-webserver-aliases"])){websites_popup_webserver_alias_section();exit;}
if(isset($_GET["popup-webserver-alias-search"])){websites_popup_webserver_alias_search();exit;}
if(isset($_GET["popup-webserver-alias-js"])){websites_popup_webserver_alias_js();exit;}
if(isset($_POST["popup-webserver-alias-delete"])){websites_popup_webserver_alias_delete();exit;}
if(isset($_POST["popup-webserver-alias-add"])){websites_popup_webserver_alias_add();exit;}


if(isset($_GET["popup-webserver-auth"])){websites_popup_webserver_auth_tab();exit;}
if(isset($_GET["popup-webserver-authparams"])){websites_popup_webserver_auth_form();exit;}
if(isset($_POST["ENABLE_LDAP_AUTH"])){websites_popup_webserver_auth_save();exit;}
if(isset($_POST["USE_AUTHENTICATOR"])){websites_popup_webserver_auth_save();exit;}
if(isset($_GET["website-script"])){websites_script();exit;}


if(isset($_GET["popup-webserver-directories"])){websites_directories_section();exit;}
if(isset($_GET["directories-search"])){websites_directories_search();exit;}
if(isset($_GET["website-directory-js"])){websites_directories_js();exit;}
if(isset($_GET["website-directory-popup"])){websites_directories_popup();exit;}
if(isset($_GET["website-directory-tabs"])){websites_directories_tabs();exit;}
if(isset($_POST["delete-folder-id-perform"])){websites_directories_delete();exit;}


if(isset($_GET["sources-tabs"])){sources_tabs();exit;}
if(isset($_GET["sources-section"])){sources_section();exit;}
if(isset($_GET["sources-search"])){sources_search();exit;}
if(isset($_GET["js-source"])){source_js();exit;}
if(isset($_GET["js-source-tests"])){source_tests_js();exit;}
if(isset($_GET["popup-source-tests"])){popup_source_tests();exit;}

if(isset($_GET["popup-source"])){source_popup();exit;}
if(isset($_GET["popup-source-main"])){source_popup_main();exit;}

if(isset($_POST["source-id"])){source_save();exit;}
if(isset($_POST["source-delete"])){source_delete();exit;}

if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-tab"])){report_tab();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["report-options"])){report_options();exit;}
if(isset($_POST["report"])){report_save();exit;}
if(isset($_POST["run"])){report_run();exit;}
if(isset($_POST["csv"])){save_options_save();exit;}
if(isset($_GET["csv"])){csv_download();exit;}

if(isset($_POST["certificate_center"])){parameters_save();exit;}
if(isset($_GET["delete-folder-id-js"])){delete_folder_id_js();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}

function source_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$source_id=$_GET["source-id"];
	if(!is_numeric($source_id)){$source_id=0;}
	$title="{new_source}";
	if($source_id>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_sources WHERE ID='$source_id'"));
		$title=$ligne["servername"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(990,'$page?popup-source&source-id=$source_id','$title')";
}

function source_tests_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$source_id=$_GET["js-source-tests"];
	if(!is_numeric($source_id)){$source_id=0;}
	$title="{new_source}";
	if($source_id>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_sources WHERE ID='$source_id'"));
		$title=$ligne["servername"];
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(990,'$page?popup-source-tests=$source_id','$title')";	
	
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
	echo "YahooWin2(890,'$page?website-directory-tabs=yes&folderid=$folderid&servername=$servername','$title')";	
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
	echo "YahooWin(1070,'$page?$add&servername=$servername','$title')";	
	
}

function websites_delete_js(){
	$tpl=new templates();
	$page=CurrentPageName();	
	header("content-type: application/x-javascript");
	$t=time();
	$servername=$_GET["servername"];
	$md=$_GET["md"];
	$delete_freeweb_nginx_text=$tpl->javascript_parse_text("$servername:: {delete_freeweb_nginx_text}");	
$html="
var xDeleteFreeWeb$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}	
	$('#'+FreeWebIDMEM{$md}).remove();
}	

function DeleteFreeWeb$t(){
	if(confirm('$delete_freeweb_nginx_text')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-servername','$servername');
    	XHR.sendAndLoad('freeweb.php', 'GET',xDeleteFreeWeb$t);
	}
}		
DeleteFreeWeb$t();		
";
echo $html;	
	
}


function websites_script_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$add="popup-webserver";
	
	$title="{new_website}";
	if($servername<>null){
		$title=$servername;
		echo "YahooWin(920,'$page?website-script=yes&servername=$servername','$title')";
	}
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

function websites_directories_tabs(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$folderid=$_GET["folderid"];
	$servername=$_GET["servername"];
	$array["{parameters}"]="$page?website-directory-popup=yes&folderid=$folderid&servername=$servername";
	$array["{replace_rules}"]="miniadmin.proxy.reverse.directory.replace.php?popup=yes&folderid=$folderid&servername=$servername";
	echo $boot->build_tab($array);
	
	
	
}

function websites_popup_tabs(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='{$_GET["servername"]}'"));
	$ALL=true;
	if($ligne["RedirectQueries"]<>null){$ALL=FALSE;}
	
	
	$array["{website}"]="$page?popup-webserver=yes&servername={$_GET["servername"]}";
	
	
	if($ALL){
		$array["{options}"]="$page?popup-webserver-options&servername={$_GET["servername"]}";
		$array["{templates_errors}"]="$page?popup-webserver-errors=yes&servername={$_GET["servername"]}";
		$array["{replace_rules}"]="$page?popup-webserver-replace=yes&servername={$_GET["servername"]}";
		$array["{paths}"]="$page?popup-webserver-directories=yes&servername={$_GET["servername"]}";
		$array["{aliases}"]="$page?popup-webserver-aliases=yes&servername={$_GET["servername"]}";
		$array["{authentication}"]="$page?popup-webserver-auth=yes&servername={$_GET["servername"]}";
		
		$array["{events}"]="$page?popup-webserver-events=yes&servername={$_GET["servername"]}&type=1";
		$array["{errors}"]="$page?popup-webserver-events=yes&servername={$_GET["servername"]}&type=2";
	}
	echo $boot->build_tab($array);
}

function websites_popup_webserver_auth_tab(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$error=null;
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("nginx.php?status-infos=yes")));
	if(!is_array($ARRAY["MODULES"])){$ARRAY["MODULES"]=array();}
	$COMPAT=FALSE;
	while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
		if(preg_match("#auth-request-nginx-module#", $a)){
			$COMPAT=true;
			break;
		}
	}
	
	if(!$COMPAT){
		$error=$tpl->_ENGINE_parse_body("<p class=text-error>{error_http_auth_request_module}</p>");
	}
	
	
	$array["{parameters}"]="$page?popup-webserver-authparams=yes&servername={$_GET["servername"]}";
	//$array["{members}"]="$page?popup-webserver-authmembers=yes&servername={$_GET["servername"]}";
	echo $error.$boot->build_tab($array);	
	
}


function websites_directories_popup(){
	$folderid=$_GET["folderid"];
	$servername=$_GET["servername"];
	if(!is_numeric($folderid)){$folderid=0;}
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$title="{new_path}";
	$bt="{add}";
	$boot=new boostrap_form();
	
	
	
	if($folderid>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_dirs WHERE folderid='$folderid'"));
		$title=stripslashes($ligne["directory"]);
		$bt="{apply}";
	}
	
	
	$results=$q->QUERY_SQL("SELECT ID,keys_zone FROM nginx_caches ORDER BY keys_zone LIMIT 0,250");
	$nginx_caches[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nginx_caches[$ligne2["ID"]]=$ligne2["keys_zone"];
	
	}	
	
	$sql="SELECT ID,ipaddr,servername FROM reverse_sources ORDER BY servername";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	$tpl=new templates();
	
	$array[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$array[$ligne2["ID"]]=$ligne2["servername"];
		$array2[$ligne2["ID"]]=$ligne2["ipaddr"];
	}

	
	$nginx_replaces[0]="{none}";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT ID,groupname FROM nginx_replace_group ORDER BY groupname");
	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error in line ". __LINE__."</p>";
	}
	
	$nginx_caches[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nginx_replaces[$ligne2["ID"]]=$ligne2["rulename"];
	
	}	
	
	
	$authrules[0]="{none}";
	$sql=" SELECT * FROM authenticator_rules WHERE enabled=1 ORDER BY rulename";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	while ($ligne21 = mysql_fetch_assoc($results)) {$authrules[$ligne21["ID"]]=$ligne21["rulename"];}
	
	
	$t=time();
	if(!is_numeric($ligne["webpageid"])){$ligne["webpageid"]=0;}
	if($ligne["servername"]<>null){$_GET["servername"]=$ligne["servername"];}
	$directory=trim(stripslashes($ligne["directory"]));
	$boot->set_formtitle($title." - $folderid -<div id='$t' style='font-size:14px'>http(s)://{$_GET["servername"]}/$directory</span></div>");
	
	$boot->set_hidden("servername",$_GET["servername"]);
	$boot->set_hidden("folderid-save", $folderid);
	$boot->set_field("directory", "{path}", $directory,array("KEYPRESS"=>"UpdateTitle$t"));
	$boot->set_checkbox("local", "{local_path}", $ligne["local"],array("LINK"=>"localdirectory","ENCODE"=>true));
	$boot->set_field("localdirectory", "{localpath}", $ligne["localdirectory"]);
	
	
	$parmas["BUTTON"]["JS"]="Loadjs('miniadmin.crm.browsepages.php?field-id=%s')";
	$parmas["BUTTON"]["LABEL"]=$tpl->_ENGINE_parse_body("{browse}...");
	$parmas["TOOLTIP"]="{webdirectoy_crm_page_ex}";
	$boot->set_field("webpageid", "{generated_page}", $ligne["webpageid"],$parmas);
	
	
	
	
	$boot->set_field("hostweb", "{website}", $ligne["hostweb"],array("TOOLTIP"=>"{nginx_website_dir_explain}"));
	$boot->set_list("authenticator", "authenticator", $authrules,$ligne["authenticator"]);
	$boot->set_list("replaceid", "{replace_rule}", $nginx_replaces,$ligne["replaceid"]);
	$boot->set_list("cacheid", "{cache}", $nginx_caches,$ligne["cacheid"]);
	$boot->set_list("cache_peer_id", "{source}", $array,$ligne["cache_peer_id"]);
	
	$boot->set_field("limit_rate_after", "{limit_rate_after}  MB", $ligne["limit_rate_after"],
			array("TOOLTIP"=>"{limit_rate_after_text}"));
	
	$boot->set_field("limit_rate", "{limit_rate}  MB/s", $ligne["limit_rate"],
			array("TOOLTIP"=>"{limit_rate_text}"));
	
	
	
	$boot->set_button($bt);
	if($folderid==0){$boot->set_CloseYahoo("YahooWin2");}
	$boot->set_RefreshSearchs();
	
	$boot->set_formdescription("{nginx_path_explain}");
	echo $boot->Compile()."
			
			
	<script>
		function UpdateTitle$t(e,id){
		document.getElementById('$t').innerHTML='http(s)://{$_GET["servername"]}/'+document.getElementById(id).value;
			
		}
			
	</script>";
	
	
}

function websites_popup_webserver_auth_form(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$ldap=new clladp();
	if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}

	
	$sql=" SELECT * FROM authenticator_rules WHERE enabled=1 ORDER BY rulename";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	$authrules[null]="{none}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$authrules[$ligne["ID"]]=$ligne["rulename"];
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$servername'"));
	$array=unserialize(base64_decode($ligne["webauth"]));
	
	
	

	if(!is_numeric($array["USE_AUTHENTICATOR"])){$array["USE_AUTHENTICATOR"]=0;}
	if(!is_numeric($array["USE_REMOTE_FRAMEWORK"])){$array["USE_REMOTE_FRAMEWORK"]=0;}
	if(!isset($array["REMOTE_FRAMEWORK"])){$array["REMOTE_FRAMEWORK"]="https://articaserver:9000/authenticator.php";}
	
	$boot=new boostrap_form();
	$boot->set_formtitle("{authentication}");
	$boot->set_formdescription("{nginx_authenticator_explain}");
	$boot->set_hidden("www-server", $servername);
	$boot->set_checkbox("USE_AUTHENTICATOR", "{enable}", $array["USE_AUTHENTICATOR"],array("DISABLEALL"=>true));
	$boot->set_field("LDAP_BANNER", "{banner}", $array["LDAP_BANNER"],array("ENCODE"=>true));
	$boot->set_list("AUTHENTICATOR_RULEID", "{rulename}", $authrules,$array["AUTHENTICATOR_RULEID"]);
	$boot->set_button("{apply}");
	echo $boot->Compile();

}

function websites_popup_webserver_auth_form_ldap(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$ldap=new clladp();
	if(!$q->FIELD_EXISTS("reverse_www","webauth")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD webauth TEXT");}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$servername'"));
	$array=unserialize(base64_decode($ligne["webauth"]));
	
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=$ldap->ldap_port;}
	
	if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]="cn=$ldap->ldap_admin,$ldap->suffix";}
	if(!isset($array["LDAP_SUFFIX"])){$array["LDAP_SUFFIX"]="$ldap->suffix";}
	if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]="$ldap->ldap_password";}
	
	if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$ldap->ldap_host;}
	if($array["LDAP_GROUP_ATTR"]==null){$array["LDAP_GROUP_ATTR"]="member";}
	if($array["LDAP_USER_ATTR"]==null){$array["LDAP_USER_ATTR"]="uid";}
	if($array["LDAP_OBJCLASS_ATTR"]==null){$array["LDAP_OBJCLASS_ATTR"]="userAccount";}
	if(!is_numeric($array["LDAP_REQUIRE_VALID"])){$array["LDAP_REQUIRE_VALID"]=1;}
	if(!is_numeric($array["LDAP_GROUP_ATTR_ISDN"])){$array["LDAP_GROUP_ATTR_ISDN"]=0;}
	if($array["LDAP_BANNER"]==null){$array["LDAP_BANNER"]="Please login";}
	
	$boot=new boostrap_form();
	$boot->set_formtitle("{ldap_authentication}");
	$boot->set_hidden("www-server", $servername);
	$boot->set_checkbox("ENABLE_LDAP_AUTH", "{enable}", $array["ENABLE_LDAP_AUTH"],array("DISABLEALL"=>true));
	$boot->set_field("LDAP_SERVER", "{ldap_server}", $array["LDAP_SERVER"]);
	$boot->set_field("LDAP_PORT", "{ldap_port}", $array["LDAP_PORT"]);
	$boot->set_field("LDAP_SUFFIX", "{ldap_suffix}", $array["LDAP_SUFFIX"]);
	
	
	$boot->set_field("LDAP_DN", "{bind_dn}", $array["LDAP_DN"],array("ENCODE"=>true));
	$boot->set_fieldpassword("LDAP_PASSWORD", "{password}", $array["LDAP_PASSWORD"],array("ENCODE"=>true));
	
	
	
	$boot->set_field("LDAP_GROUP_ATTR", "{ldap_group_attribute}", $array["LDAP_GROUP_ATTR"]);
	$boot->set_field("LDAP_GROUP_ATTR_ISDN", "{ldap_group_attribute}", $array["LDAP_GROUP_ATTR_ISDN"]);
	$boot->set_checkbox("LDAP_GROUP_ATTR_ISDN", "{LDAP_GROUP_ATTR_ISDN}", $array["LDAP_GROUP_ATTR_ISDN"],array("TOOLTIP"=>"{LDAP_GROUP_ATTR_ISDN_EXPLAIN}"));
	
	$boot->set_field("LDAP_USER_ATTR", "{ldap_user_attribute}", $array["LDAP_USER_ATTR"]);
	$boot->set_field("LDAP_OBJCLASS_ATTR", "{ldap_objectclass}", $array["LDAP_OBJCLASS_ATTR"]);
	
	$boot->set_field("LDAP_BANNER", "{banner}", $array["LDAP_BANNER"],array("ENCODE"=>true));
	$boot->set_checkbox("LDAP_REQUIRE_VALID", "{LDAP_REQUIRE_VALID}", $array["LDAP_REQUIRE_VALID"],array("TOOLTIP"=>"{LDAP_REQUIRE_VALID_EXPLAIN}"));
	
	$boot->set_button("{apply}");
	echo $boot->Compile();
	
}


function websites_popup_webserver_auth_save(){
	$servername=$_POST["www-server"];
	if(isset($_POST["LDAP_DN"])){$_POST["LDAP_DN"]=url_decode_special_tool($_POST["LDAP_DN"]);}
	if(isset($_POST["LDAP_PASSWORD"])){$_POST["LDAP_PASSWORD"]=url_decode_special_tool($_POST["LDAP_PASSWORD"]);}
	if(isset($_POST["LDAP_BANNER"])){$_POST["LDAP_BANNER"]=url_decode_special_tool($_POST["LDAP_BANNER"]);}
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `webauth` FROM reverse_www WHERE servername='$servername'"));
	$array=unserialize(base64_decode($ligne["webauth"]));
	
	while (list ($key, $value) = each ($_POST) ){
		$array[$key]=$value;
	}
	
	$encoded=mysql_escape_string2(base64_encode(serialize($array)));
	$sql="UPDATE reverse_www SET `webauth`='$encoded' WHERE servername='$servername'";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function ssl_certificates_list(){
	$sql="SELECT CommonName FROM sslcertificates ORDER BY CommonName";
	$q=new mysql();
	$sslcertificates[null]="{default}";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligneZ=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sslcertificates[$ligneZ["CommonName"]]=$ligneZ["CommonName"];
	}
	return $sslcertificates;
}

function sources_list(){
	$q=new mysql_squid_builder();
	$sql="SELECT ID,ipaddr,servername FROM reverse_sources ORDER BY servername";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	
	$CountDeSources=mysql_num_rows($results);
	
	$array[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$array[$ligne2["ID"]]=$ligne2["servername"];
		$array2[$ligne2["ID"]]=$ligne2["ipaddr"];
	}
	return array($array,$array2,$CountDeSources);
}

function caches_list(){
	$q2=new mysql_squid_builder();
	$results=$q2->QUERY_SQL("SELECT ID,keys_zone FROM nginx_caches ORDER BY keys_zone");
	$nginx_caches[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nginx_caches[$ligne2["ID"]]=$ligne2["keys_zone"];
	
	}
	
	return $nginx_caches;
}

function pool_list(){
	$q2=new mysql_squid_builder();
	$results=$q2->QUERY_SQL($sql="SELECT * FROM nginx_pools ORDER BY poolname");
	$nginx_pools[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nginx_pools[$ligne2["ID"]]=$ligne2["poolname"];
	
	}
	return $nginx_pools;
}
function replace_list(){
	$nginx_replaces[0]="{none}";
	$q2=new mysql_squid_builder();
	$results=$q2->QUERY_SQL("SELECT ID,groupname FROM nginx_replace_group ORDER BY groupname");
	if(!$q2->ok){
		echo "<p class=text-error>$q2->mysql_error</p>";
	}
	$nginx_caches[0]="{none}";
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nginx_replaces[$ligne2["ID"]]=$ligne2["rulename"];
	}
	return $nginx_replaces;
}

function websites_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$rv=new squid_reverse();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$title="{new_webserver}";
	$bt="{add}";
	
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$squid_reverse=new squid_reverse();
	$tpl=new templates();
	$sslcertificates=ssl_certificates_list();
	$sources_list=sources_list();
	$array=$sources_list[0];
	$array2=$sources_list[1];
	$CountDeSources=$sources_list[2];
	$nginx_caches=caches_list();
	$nginx_pools=pool_list();
	$nginx_replaces=replace_list();
	
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	
	
	
	if(!$q->FIELD_EXISTS("reverse_www", "debug")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `debug` smallint(1) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
	if(!$q->FIELD_EXISTS("reverse_www", "start_directory")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `start_directory` VARCHAR(255)");if(!$q->ok){echo $q->mysql_error_html();}}
	if(!$q->FIELD_EXISTS("reverse_www", "DenyConf")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `DenyConf` smallint(1) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
	if(!$q->FIELD_EXISTS("reverse_www", "ArticaErrors")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `ArticaErrors` smallint(1) NOT NULL DEFAULT 1");if(!$q->ok){echo $q->mysql_error_html();}}
	if(!$q->FIELD_EXISTS("reverse_www", "RedirectQueries")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `RedirectQueries` VARCHAR(255)");if(!$q->ok){echo $q->mysql_error_html();}}
	if(!$q->FIELD_EXISTS("reverse_www", "default_server")){
		$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `default_server` smallint( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `default_server` )");
		if(!$q->ok){echo $q->mysql_error_html();}
	}
	
	
	
	if($servername<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
		$title=$tpl->_ENGINE_parse_body("{port}:{$ligne["port"]} &laquo;$servername&raquo;");
		$bt="{apply}";
		$boot->set_hidden("servername-edit", $servername);
	}else{
		$ligne["enabled"]=1;
		$boot->set_field("servername-edit", "{website}", null,array("MANDATORY"=>true));
	}
	
	if(!is_numeric($ligne["port"])){$ligne["port"]=80;}
	if(!is_numeric($ligne["ArticaErrors"])){$ligne["ArticaErrors"]=1;}
	if($servername==null){$ligne["cache_peer_id"]=-1;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	
	
	if($ligne["cache_peer_id"]==0){
		if($EnableFreeWeb==1){
			$q2=new mysql();
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT `useSSL`,`sslcertificate` FROM `freeweb` WHERE `servername`='$servername'","artica_backup"));
			$ligne["certificate"]=$ligne2["sslcertificate"];
		}else{
			$ligne["cache_peer_id"]=-1;
		}
	}
	
	
	$boot->set_formtitle($title);
	$boot->set_field("port", "{inbound_port}", $ligne["port"]);
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"],array("DISABLEALL"=>true));
	$boot->set_checkbox("default_server", "{default_server}", $ligne["default_server"],array("TOOLTIP"=>"{NGINX_DEFAULT_SERVER}"));
	$boot->set_checkbox("owa", "{protect_owa}", $ligne["owa"]);
	$boot->set_checkbox("debug", "{debug}", $ligne["debug"]);
	$boot->set_list("cacheid", "{cache}", $nginx_caches,$ligne["cacheid"]);
	$boot->set_list("replaceid", "{replace_rule}", $nginx_replaces,$ligne["replaceid"]);
	
	if($servername<>null){
		$boot->set_formdescription("{certificate}: {$ligne["certificate"]}");
	}
	
	if($ligne["cache_peer_id"]==0){
			$boot->set_hidden("cache_peer_id", 0);
			$boot->set_hidden("enabled", 1);
			$boot->set_hidden("certificate", $ligne["certificate"]);
		
	}else{
		
		if(!AdminPrivs()){
			$boot->set_hidden("cache_peer_id", $ligne["cache_peer_id"]);
			$boot->set_hidden("start_directory", $ligne["start_directory"]);
		}else{
			$boot->set_list("cache_peer_id", "{destination}", $array,$ligne["cache_peer_id"]);
			$boot->set_field("start_directory", "{start_path}", $ligne["start_directory"]);
		}

		
		$boot->set_list("poolid", "{pool}", $nginx_pools,$ligne["poolid"]);

		
		
		$boot->set_field("RedirectQueries", "{RedirectQueries}", $ligne["RedirectQueries"]);
		$boot->set_checkbox("ArticaErrors", "{enable_template_errors}", $ligne["ArticaErrors"]);
		$boot->set_checkbox("ssl", "{UseSSL}", $ligne["ssl"],array("TOOLTIP"=>"{NGINX_USE_SSL_EXPLAIN}"));
		$boot->set_list("certificate", "{certificate}", $sslcertificates,$ligne["certificate"],array());
		
		if($CountDeSources==0){
			$boot->set_form_error("{you_need_to_define_sources_first}");
			$boot->set_form_locked();
		}
		
	}
	
	$boot->set_button($bt);
	if($servername==null){$boot->set_CloseYahoo("YahooWin");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
}

function websites_popup_webserver_errors(){
	$q=new mysql_squid_builder();
	$q2=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$rv=new squid_reverse();
	$boot=new boostrap_form();
	$squid_reverse=new squid_reverse();
	
	$boot->set_formtitle("{templates_errors}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	
	
	
	$errors_code[0]="{default}";
	$results=$q2->QUERY_SQL("SELECT ID,pagename FROM nginx_error_pages ORDER BY pagename");
	
	while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
		$errors_code[$ligne2["ID"]]=$ligne2["pagename"];
	}
	
	$boot->set_hidden("servername-errors", $servername);
	if(!is_numeric($ligne["ArticaErrors"])){$ligne["ArticaErrors"]=1;}
	reset($squid_reverse->errors_page);
	while (list ($key, $value) = each ($squid_reverse->errors_page) ){
		$boot->set_list("$value", "{error} $value", $errors_code,$ligne[$value]);
	}
	if($ligne["ArticaErrors"]==0){
		$boot->set_form_error("{templates_errors_disabled}");
		$boot->set_form_locked();
	}
	
	$boot->set_button("{apply}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
}

function websites_popup_webserver_options_tabs(){
$boot=new boostrap_form();
$users=new usersMenus();
$page=CurrentPageName();

$array["{options}"]="$page?popup-webserver-options-section=yes&servername={$_GET["servername"]}";
$array["{anti_exploits}"]="miniadmin.nginx.exploits.php?servername={$_GET["servername"]}";
$array["{anti_exploits} {events}"]="miniadmin.nginx.exploits.php?events=yes&servername={$_GET["servername"]}";

if(AdminPrivs()){
	$array["{privileges}"]="$page?popup-webserver-privs=yes&servername={$_GET["servername"]}";
}


echo $boot->build_tab($array);
}


function websites_popup_webserver_options_section(){
	
	$q=new mysql_squid_builder();
	$q2=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$rv=new squid_reverse();
	$boot=new boostrap_form();
	$squid_reverse=new squid_reverse();
	
	
	
	if(!$q->FIELD_EXISTS("reverse_www", "limit_rate_after")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `limit_rate_after` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}

	if(!$q->FIELD_EXISTS("reverse_www", "limit_rate")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `limit_rate` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}	
	
	if(!$q->FIELD_EXISTS("reverse_www", "proxy_read_timeout")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_read_timeout` smallint(3) NOT NULL DEFAULT 300");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "proxy_send_timeout")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_send_timeout` smallint(3) NOT NULL DEFAULT 300");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "proxy_buffers")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_buffers` VARCHAR(20) NOT NULL DEFAULT '8 8k'");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "proxy_buffer_size")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_buffer_size` smallint(10) NOT NULL DEFAULT '8'");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "proxy_cache_min_uses")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_cache_min_uses` smallint(3) NOT NULL DEFAULT '1'");if(!$q->ok){echo $q->mysql_error_html();}}

	if(!$q->FIELD_EXISTS("reverse_www", "proxy_buffering")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_buffering` smallint(1) NOT NULL DEFAULT '1'");if(!$q->ok){echo $q->mysql_error_html();}}	
	
	
//
	if(!$q->FIELD_EXISTS("reverse_www", "EnableHSTS")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `EnableHSTS` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}	
	
	if(!$q->FIELD_EXISTS("reverse_www", "RestrictIFrames")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `RestrictIFrames` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "XSSBrowser")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `XSSBrowser` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}	
	
	if(!$q->FIELD_EXISTS("reverse_www", "EnableCSP")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `EnableCSP` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "EnableSSLOCSP")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `EnableSSLOCSP` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}	
		
	if(!$q->FIELD_EXISTS("reverse_www", "ssl_protocols")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `ssl_protocols` VARCHAR(255)");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "ssl_ciphers")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `ssl_ciphers` TEXT");if(!$q->ok){echo $q->mysql_error_html();}}
	
	
	//
	
	if(!$q->FIELD_EXISTS("reverse_www", "LimitCnx")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `LimitCnx` smallint(5) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_www", "LimitReqs")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `LimitReqs` smallint(5) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	
	
	$boot->set_formtitle("{options}");
	
	$boot->set_spacertitle("{bandwith_limitation_full}");
	
	$boot->set_field("limit_rate_after", "{limit_rate_after}  MB", $ligne["limit_rate_after"],
			array("TOOLTIP"=>"{limit_rate_after_text}"));
	
	$boot->set_field("limit_rate", "{limit_rate}  MB/s", $ligne["limit_rate"],
			array("TOOLTIP"=>"{limit_rate_text}"));	
	
	
	
	$boot->set_spacertitle("{limits}");
	$boot->set_field("LimitCnx", "{LimitCnx}", $ligne["LimitCnx"],
			array("TOOLTIP"=>"{LimitCnx_text}"));	
	$boot->set_field("LimitReqs", "{LimitReqs}", $ligne["LimitReqs"],
			array("TOOLTIP"=>"{LimitReqs_text}"));		

	
	

	
	
	if($ligne["ssl_protocols"]==null){
		$ligne["ssl_protocols"]="TLSv1 TLSv1.1 TLSv1.2";
	}
	
	if($ligne["ssl_ciphers"]==null){
		$ligne["ssl_ciphers"]="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";
	}




$boot->set_field("proxy_read_timeout", "{proxy_read_timeout}  {seconds}", $ligne["proxy_read_timeout"],
			array("TOOLTIP"=>"{proxy_read_timeout_text}"));

$boot->set_field("proxy_send_timeout", "{proxy_send_timeout} {seconds}", $ligne["proxy_send_timeout"],
		array("TOOLTIP"=>"{proxy_send_timeout_text}"));




$boot->set_spacertitle("{cache}");
$boot->set_checkbox("proxy_buffering", "{proxy_buffering}", $ligne["proxy_buffering"],
		array("TOOLTIP"=>"{proxy_buffering_text}","LINK"=>"proxy_buffers,proxy_buffer_size,proxy_cache_min_uses"));

$boot->set_field("proxy_cache_min_uses", "{proxy_cache_min_uses}", $ligne["proxy_cache_min_uses"],
		array("TOOLTIP"=>"{proxy_cache_min_uses_text}"));

$boot->set_field("proxy_cache_valid", "{proxy_cache_valid}", $ligne["proxy_cache_valid"],
		array("TOOLTIP"=>"{proxy_cache_valid_text}"));



$boot->set_field("proxy_buffers", "{proxy_buffers}", $ligne["proxy_buffers"],
		array("TOOLTIP"=>"{proxy_buffers_text}"));

$boot->set_field("proxy_buffer_size", "{proxy_buffer_size} (k)", $ligne["proxy_buffer_size"],
		array("TOOLTIP"=>"{proxy_buffer_size_text}"));
$boot->set_field("proxy_cache_min_uses", "{proxy_cache_min_uses} {minutes}", $ligne["proxy_cache_min_uses"],
		array("TOOLTIP"=>"{proxy_cache_min_uses_text}"));

$boot->set_spacertitle("{browsers_security}");

$boot->set_checkbox("EnableHSTS", "{EnableHSTS}", $ligne["EnableHSTS"],
		array("TOOLTIP"=>"{EnableHSTS_text}"));

$boot->set_checkbox("RestrictIFrames", "{RestrictIFrames}", $ligne["RestrictIFrames"],
		array("TOOLTIP"=>"{RestrictIFrames_text}"));

$boot->set_checkbox("XSSBrowser", "{XSS_FILTERB}", $ligne["XSSBrowser"],
		array("TOOLTIP"=>"{XSS_FILTERB_TEXT}"));

$boot->set_checkbox("EnableCSP", "{EnableCSP}", $ligne["EnableCSP"],
		array("TOOLTIP"=>"{EnableCSP_text}"));


$boot->set_spacertitle("{ssl_security}");
$boot->set_checkbox("EnableSSLOCSP", "{EnableSSLOCSP}", $ligne["EnableSSLOCSP"],
		array("TOOLTIP"=>"{EnableSSLOCSP_text}"));


$boot->set_field("ssl_protocols", "{ssl_protocols}", $ligne["ssl_protocols"],array("TOOLTIP"=>"{ssl_protocols_text}"));
$boot->set_field("ssl_ciphers", "{ssl_ciphers}",$ligne["ssl_ciphers"]);


$boot->set_hidden("servername-errors", $servername);
$boot->set_button("{apply}");
$boot->set_RefreshSearchs();
echo $boot->Compile();

}


function websites_popup_webserver_errors_save(){
	$servername=$_POST["servername-errors"];
	unset($_POST["servername-errors"]);
	
	$q=new mysql_squid_builder();
	$q2=new mysql();
	$editF=false;
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	$sql="UPDATE reverse_www SET ".@implode(",", $edit)." WHERE servername='$servername'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
}

function source_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$source_id=$_GET["source-id"];
	if(!is_numeric($source_id)){$source_id=0;}
	if($source_id==0){source_popup_main();return;}
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$source_id'"));
	$servername=$ligne["servername"];
	
	$boot=new boostrap_form();	
	$array[$servername]="$page?popup-source-main=yes&source-id=$source_id";
	if(AdminPrivs()){
		$array["{privileges}"]="$page?popup-webserver-privs=yes&servername=$source_id";
	}
	echo $boot->build_tab($array);
	
}


function popup_source_tests(){
	$tpl=new templates();
	$page=CurrentPageName();
	$source_id=$_GET["popup-source-tests"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$source_id'"));
	$servername=$ligne["servername"];
	$isSuccesstxt=@implode("\n", unserialize(base64_decode($ligne["isSuccesstxt"])));
	$isSuccessTime=$ligne["isSuccessTime"];

	$html="<H1>$servername ($isSuccessTime)</H1>
		<textarea 
		style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:16px;font-weight:bold;padding:3px'
		id='SQUID_CONTENT-$t'>$isSuccesstxt</textarea>
	";
	echo $html;
	
}

function source_popup_main(){	
	$tpl=new templates();
	$page=CurrentPageName();
	$source_id=$_GET["source-id"];
	if(!is_numeric($source_id)){$source_id=0;}
	$q=new mysql_squid_builder();
	$title="{new_source}";
	$bt="{add}";
	if($source_id>0){
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$source_id'"));
		$title=$ligne["servername"];
		$bt="{apply}";
	}	
	if(!$q->FIELD_EXISTS("reverse_sources", "OnlyTCP")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `OnlyTCP` smallint(1) NOT NULL DEFAULT '0'");}
	
	if(!is_numeric($ligne["port"])){$ligne["port"]=80;}
	if(!is_numeric($ligne["sslport"])){$ligne["sslport"]=443;}
	if(!is_numeric($ligne["OnlyTCP"])){$ligne["OnlyTCP"]=0;}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["ForceRedirect"])){$ligne["ForceRedirect"]=1;}
	if(!is_numeric($ligne["proxy_read_timeout"])){$ligne["proxy_read_timeout"]=300;}
	if($source_id==0){$ligne["enabled"]=1;}
	$boot=new boostrap_form();
	$boot->set_hidden("source-id", $source_id);
	
	$boot->set_formtitle($title);
	$boot->set_hidden("OnlyTCP", "0");
	$boot->set_field("servername", "{servername2}", $ligne["servername"],array("TOOLTIP"=>"{nginx_servername2}"));
	$boot->set_field("ipaddr", "{destination}", $ligne["ipaddr"],array("TOOLTIP"=>"{nginx_destination}"));
	//$boot->set_checkbox("OnlyTCP", "{OnlyTCP}", $ligne["OnlyTCP"],array("TOOLTIP"=>"{nginx_OnlyTCP}"));
	
	
	$boot->set_field("port", "{port}",$ligne["port"]);
	$boot->set_field("forceddomain", "{forceddomain}",$ligne["forceddomain"],array("TOOLTIP"=>"{nginx_forceddomain}"));
	$boot->set_field("proxy_read_timeout", "{read_timeout} ({seconds})", $ligne["proxy_read_timeout"],array("TOOLTIP"=>"{nginx_proxy_read_timeout}"));
	$boot->set_checkbox("ForceRedirect", "{ForceRedirect}", $ligne["ForceRedirect"],array("TOOLTIP"=>"{nginx_ForceRedirect}"));
	$boot->set_checkbox("ssl", "{generate_ssl}", $ligne["ssl"]);
	
	
	$boot->set_checkbox("enabled", "{enabled}", $ligne["enabled"],array("DISABLEALL"=>true));
	$boot->set_button($bt);
	if($source_id==0){$boot->set_CloseYahoo("YahooWin");}
	$boot->set_RefreshSearchs();
	$AdminPrivs=AdminPrivs();
	if(!$AdminPrivs){
		$boot->set_form_locked();
	}
	echo $boot->Compile();
	
}

function websites_delete(){
	$servername=$_POST["website-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM reverse_www WHERE servername='$servername'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM reverse_privs WHERE servername='$servername'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM nginx_replace_www WHERE servername='$servername'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM nginx_aliases WHERE servername='$servername'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM nginx_replace_folder WHERE servername='$servername'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
}





function websites_save(){
	$servername=$_POST["servername-edit"];
	unset($_POST["servername-edit"]);
	$_POST["servername"]=$servername;
	$q=new mysql_squid_builder();
	$q2=new mysql();
	$editF=false;
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM reverse_www WHERE servername='$servername'"));
	if(trim($ligne["servername"])<>null){
		$editF=true;
	}
	
	
	if($_POST["default_server"]==1){$q->QUERY_SQL("UPDATE reverse_www SET `default_server`=0 WHERE `port`='{$_POST["port"]}'");}
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($editF){
		$sql="UPDATE reverse_www SET ".@implode(",", $edit)." WHERE servername='$servername'";
	}else{
		$ligne=mysql_fetch_array($q2->QUERY_SQL("SELECT servername FROM freeweb WHERE servername='$servername'","artica_backup"));
		if($ligne["servername"]<>null){
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{error_this_hostname_is_reserved_freeweb}");
			return;
		}
		$sql="INSERT IGNORE INTO reverse_www (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}


function websites_directories_delete(){
	$folderid=$_POST["delete-folder-id-perform"];
	$q=new mysql_squid_builder();
	$sql="DELETE FROM reverse_dirs  WHERE folderid=$folderid";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sql="DELETE FROM nginx_replace_folder WHERE folderid=$folderid";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reverse-proxy-apply=yes");	
	
}
function websites_directories_save(){
	$folderid=$_POST["folderid-save"];
	unset($_POST["folderid-save"]);
	$revers=new squid_reverse();
	$_POST["local"]=url_decode_special_tool($_POST["local"]);
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_dirs","authenticator")){
		$q->QUERY_SQL("ALTER TABLE `reverse_dirs` ADD `authenticator` INT(10) NOT NULL, ADD INDEX ( `authenticator`)");
	}
	
	if(!$q->FIELD_EXISTS("reverse_dirs", "limit_rate_after")){$q->QUERY_SQL("ALTER TABLE `reverse_dirs`
		ADD `limit_rate_after` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
	
	if(!$q->FIELD_EXISTS("reverse_dirs", "limit_rate")){$q->QUERY_SQL("ALTER TABLE `reverse_dirs`
		ADD `limit_rate` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
		
	
	
	
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	if($folderid>0){
		$sql="UPDATE reverse_dirs SET ".@implode(",", $edit)." WHERE `folderid`=$folderid";
	}else{
		$sql="INSERT IGNORE INTO reverse_dirs (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\nLine:".__LINE__."\n\n$sql\n\n";return;}
	$sock=new sockets();
	
	
}


function source_save(){
	$source_id=$_POST["source-id"];
	$revers=new squid_reverse();
	unset($_POST["source-id"]);
	$tpl=new templates();
	$_POST["ipaddr"]=trim(strtolower($_POST["ipaddr"]));
	$classip=new IP();
	if(!$classip->isIPAddress($_POST["ipaddr"])){
		$ip=gethostbyname($_POST["ipaddr"]);
		if(!$classip->isIPAddress($ip)){
			echo $tpl->javascript_parse_text("{unable_to_resolve}: {$_POST["ipaddr"]}\n",1);
			return;
		}
	}
	
	
	
	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
		
	}
	
	if($source_id>0){
		$sql="UPDATE reverse_sources SET ".@implode(",", $edit)." WHERE ID=$source_id";
	}else{
		$sql="INSERT IGNORE INTO reverse_sources (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
		
	}
	
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_sources", "OnlyTCP")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `OnlyTCP` smallint(1) NOT NULL DEFAULT '0'");}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}

function source_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM reverse_sources WHERE ID='{$_POST["source-delete"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM reverse_privs WHERE sourceid='{$_POST["source-delete"]}'");
	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$users=new usersMenus();
	$f=new squid_reverse();
	

	$html="
	<div class=BodyContent>
	<div style='font-size:14px'>
	<a href=\"miniadm.index.php\">{myaccount}</a>
	&nbsp;&raquo;&nbsp;<a href=\"$page\">{reverse_proxy_settings}</a>
	</div>
	<H1>{reverse_proxy_settings}</H1>
	<p>{reverse_proxy_settings_text}</p>
	</div>
	<div id='webstats-middle-$ff' class=BodyContent></div>

	<script>
	LoadAjax('webstats-middle-$ff','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	$AdminPrivs=AdminPrivs();
	$tpl=new templates();
	
	$button=$tpl->_ENGINE_parse_body(button("{apply_parameters}", "Loadjs('system.services.cmd.php?APPNAME=APP_NGINX&action=restart&cmd=%2Fetc%2Finit.d%2Fnginx&appcode=APP_NGINX');"));
	if(isset($_GET["subtitle"])){
		$subtitle=$tpl->_ENGINE_parse_body("<p class=explain>{reverse_proxy_settings_text}</p>");
	}

	$array["{websites}"]="$page?websites-section=yes";
	$array["{web_sources}"]="$page?sources-tabs=yes";
	if($users->NGINX_INSTALLED){
		$array["{caches}"]="miniadmin.nginx.caches.php?caches-section=yes";
		$array["{replace_rules}"]="miniadmin.nginx.replace.php?replace-section=yes";
	
		
		if($AdminPrivs){
			$array["{global_parameters}"]="$page?parameters=yes";
			$array["{errors_pages}"]="miniadmin.nginx.errors.php?section=yes";
			$array["Authenticator"]="miniadmin.nginx.authenticator.php";
		}
	
	
		$array["{status}"]="$page?nginx-status=yes";
		
	}
	
	$sock=new sockets();
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	
	if($EnableNginx==0){
		$button=$tpl->_ENGINE_parse_body(button("{activate_reverse_proxy}", "Loadjs('squid.nginx.php');"));
		$subtitle=$tpl->_ENGINE_parse_body("<p class=text-error>{warn_nginx_disabled}</p>");
	}
	
	
	echo "<div style='float:right'>$button</div>$subtitle".$boot->build_tab($array);
	
	
}
function sources_tabs(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	
	$array["{hosts}"]="$page?sources-section=yes";
	if(AdminPrivs()){
		$array["{pools}"]="miniadmin.proxy.reverse.nginx-pools.php?section=yes";
	}
	echo $boot->build_tab($array);	
	
}

function nginx_status(){
	$page=CurrentPageName();
	$html="<div id='nginx-section-status'></div>
		<script>LoadAjax('nginx-section-status','$page?nginx-section-status=yes');</script>";
	echo $html;
}
	
function nginx_status_section(){	
	$sock=new sockets();
	
	$ARRAY=unserialize(base64_decode($sock->getFrameWork("nginx.php?status-infos=yes")));
	$NOT["CONFIGURE"]=true; // 	1
	$NOT["ARGUMENTS:"]=true; // 	1
	$NOT["PREFIX"]=true; // 	/etc/nginx
	$NOT["SBIN-PATH"]=true; // 	/usr/sbin/nginx
	$NOT["CONF-PATH"]=true; // 	/etc/nginx/nginx.conf
	$NOT["ERROR-LOG-PATH"]=true; // 	/var/log/nginx/error.log
	$NOT["HTTP-LOG-PATH"]=true; // 	/var/log/nginx/access.log
	$NOT["PID-PATH"]=true; // 	/var/run/nginx.pid
	$NOT["LOCK-PATH"]=true; // 	/var/run/nginx.lock
	$NOT["HTTP-CLIENT-BODY-TEMP-PATH"]=true; // 	/var/cache/nginx/client_temp
	$NOT["HTTP-PROXY-TEMP-PATH"]=true; // 	/var/cache/nginx/proxy_temp
	$NOT["HTTP-FASTCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/fastcgi_temp
	$NOT["HTTP-UWSGI-TEMP-PATH"]=true; // 	/var/cache/nginx/uwsgi_temp
	$NOT["HTTP-SCGI-TEMP-PATH"]=true; // 	/var/cache/nginx/scgi_temp
	$NOT["USER"]=true; // 	nginx
	$NOT["GROUP"]=true; //
	$NOT["WITH-HTTP_GUNZIP_MODULE"]=true;
	$status=$ARRAY["STATUS"];
	$ini=new Bs_IniHandler();
	$ini->loadString($status);
	$nginx_status=DAEMON_STATUS_ROUND("APP_NGINX",$ini,null,0);
	
	$APP_NGINXDB=DAEMON_STATUS_ROUND("APP_NGINXDB",$ini,null,0);
	
	
	$t[]="<table style='width:100%'>";
	$t[]="<tr>
	<td>{APP_NGINX}</td>
	<td><strong>{$ARRAY["DEF"]["VENDOR"]}</strong> - {$ARRAY["DEF"]["VERSION"]}</td>
	</tr>";
	
	
	if(is_array($ARRAY["ARGS"])){
		while (list ($a, $b) = each ($ARRAY["ARGS"]) ){
			if(isset($NOT[$a])){continue;}
			$t[]="<tr>
				<td>{{$a}}</td>
				<td>$b</td>
			</tr>";
			
		}
		
		
	}
	
	if(is_array($ARRAY["MODULES"])){
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(isset($NOT[$a])){continue;}
			$t[]="<tr>
			<td>{ADD-MODULE}:</td>
			<td>$a</td>
			</tr>";
				
		}
		
	}
	
	$datas=$sock->getFrameWork("cmd.php?apachesrc-ini-status=yes");
	$ini->loadString(base64_decode($datas));
	$APP_APACHE_SRC=DAEMON_STATUS_ROUND("APP_APACHE_SRC",$ini,null,0);
	$APP_PHPFPM=DAEMON_STATUS_ROUND("APP_PHPFPM",$ini,null,0);
	
	$final=@implode("\n", $t)."</table>";
	
	$html="
	<table style='width:100%'>
	<tr>
		<td colspan=2>
			<div style='float:right'>".
			button("{manual_update}", "Loadjs('miniadmin.nginx.update.php')",18)."</div>
		<H3>{APP_NGINX} {$ARRAY["DEF"]["VERSION"]}</H3></td>
		
	</tr>
	<tr>
		<td valign='top' style='text-valign:top;padding:5px' width=350px>$nginx_status$APP_NGINXDB$APP_APACHE_SRC$APP_PHPFPM</td>
		<td valign='top' style='text-valign:top;padding:10px'><div style='width:98%' class=form>$final</div></td>
	</tr>
	</table>		
			
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}


function websites_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}
	$info=null;
	if($EnableNginxStats==0){
		$info=$tpl->_ENGINE_parse_body("<div class=explain>{EnableNginxStats_explain}</div>");
	}
	
	if(AdminPrivs()){
		$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_server}", "Loadjs('$page?website-js=yes&servername=')"));
	}
	
	echo $info.$boot->SearchFormGen("servername","websites-search",null,$EXPLAIN);

}

function websites_privs_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$explain=$tpl->_ENGINE_parse_body("<div class=explain>{NGINX_PRIVS_EXPLAIN}</div>");
	$t=time();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{link_member}", "Loadjs('miniadm.members.browse.php?CallBack=LinkUser$t')"));
	echo $boot->SearchFormGen(null,"privs-search","&servername={$_GET["servername"]}",$EXPLAIN)."
			
	<script>
	var xLinkUser$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
      	ExecuteByClassName('SearchFunction');
     	}	
	
	
		function LinkUser$t(uid){
		var XHR = new XHRConnection();
		XHR.appendData('linkuser',uid);
		XHR.appendData('linkuser-srv','{$_GET["servername"]}');
		XHR.sendAndLoad('$page', 'POST',xLinkUser$t);			
		}
		
	</script>		
	";
}

function websites_privs_add(){
	$q=new mysql_squid_builder();
	
	if(is_numeric($_POST["linkuser-srv"])){$_POST["sourceid"]=$_POST["linkuser-srv"];}
	if(!is_numeric($_POST["sourceid"])){$_POST["sourceid"]=0;}
	
	$q->QUERY_SQL("INSERT IGNORE INTO reverse_privs (`uid`,`servername`,`sourceid`) VALUES ('{$_POST["linkuser"]}','{$_POST["linkuser-srv"]}','{$_POST["sourceid"]}')");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function websites_privs_del(){
	$q=new mysql_squid_builder();
	$AND="AND servername='{$_POST["uid-srv"]}'";
	if(is_numeric($_POST["uid-srv"])){$AND="AND sourceid='{$_POST["uid-srv"]}'";}
	$q->QUERY_SQL("DELETE FROM reverse_privs WHERE uid='{$_POST["uid-delete"]}' $AND");
	if(!$q->ok){echo $q->mysql_error;return;}	
}

function websites_popup_events(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='{$_GET["servername"]}'"));
	if($ligne["cache_peer_id"]>0){
		$port="&port={$ligne["port"]}";
	}
	
	echo $boot->SearchFormGen(null,"popup-webserver-events-search","&servername={$_GET["servername"]}$port&type={$_GET["type"]}");	
}

function sources_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$AdminPrivs=AdminPrivs();
	if($AdminPrivs){
		$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_source}", "Loadjs('$page?js-source=yes&source-id=0')"));
	}
	echo $boot->SearchFormGen("ipaddr,servername","sources-search",null,$EXPLAIN);	
	
}

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


function websites_search(){
	$sock=new sockets();
	$q=new mysql();
	$CountDeFreeWebs=$q->COUNT_ROWS("freeweb", "artica_backup");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	$q=new mysql_squid_builder();
	
	if(!$q->TABLE_EXISTS("reverse_www")){$f=new squid_reverse();}
	if(!$q->TABLE_EXISTS("reverse_sources")){$f=new nginx_sources();$f->PatchTables();}
	
	
	if(!$q->FIELD_EXISTS("reverse_sources", "OnlyTCP")){
			$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `OnlyTCP` smallint(1) NOT NULL DEFAULT '0'");
			if(!$q->ok){echo "<p class=text-error>$q->mysql_error in ".basename(__FILE__)." line ".__LINE__."</p>";}
	
		}
	$CountDereverse=$q->COUNT_ROWS("reverse_www");
	if($CountDereverse==0){
		if($CountDeFreeWebs>0){
			$sock->getFrameWork("nginx.php?sync-freewebs=yes");
		}
	}
	
	
	
	$STATUS=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/nginx.status.acl"));
	
	$searchstring=string_to_flexquery("websites-search");
	
	$sql="SELECT * FROM reverse_www WHERE 1 $searchstring ORDER BY servername LIMIT 0,250";
	
	if(!AdminPrivs()){
		$sql="SELECT reverse_www.* FROM reverse_www,reverse_privs 
		WHERE reverse_privs.servername=reverse_www.servername
		AND reverse_privs.uid='{$_SESSION["uid"]}' $searchstring ORDER BY servername LIMIT 0,250";
	}
	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error_html();return;}
	
	$tpl=new templates();
	$all_text=$tpl->_ENGINE_parse_body("{all}");
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$q1=new mysql();
	$t=time();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$icon2="arrow-right-64.png";
				$icon="domain-main-64.png";
				$freewebicon="64-firewall-search.png";
				$color="black";
				$status=array();
				$portText=null;
				if($ligne["ssl"]==1){
					$certificate_text=$tpl->_ENGINE_parse_body("<div>{certificate}: {default}</div>");;
				}
				$md=md5(serialize($ligne));
				$RedirectQueries=$ligne["RedirectQueries"];
				$default_server=$ligne["default_server"];
				$explain_text=null;
				$SiteEnabled=$ligne["enabled"];
				$servername=$ligne["servername"];
				$servername_enc=urlencode($servername);
				$limit_rate=$ligne["limit_rate"];
				$limit_rate_after=$ligne["limit_rate_after"];
				
				$DeleteFreeWeb="Loadjs('$page?delete-websites-js=yes&servername=$servername_enc&md=$md')";
				
				if($ligne["DenyConf"]==1){$icon="hearth-blocked-64.png";}
				
				if($SiteEnabled==0){
					$icon="domain-main-64-grey.png";
					$color="#8a8a8a";
					$icon2="arrow-right-64-grey.png";
				}
				
				
				$delete=imgsimple("delete-48.png",null,$DeleteFreeWeb);
				$jsEditWW=$boot->trswitch("Loadjs('website-js=yes&servername=$servername_enc')");
				$jsedit=$boot->trswitch("Loadjs('$page?website-js=yes&servername=$servername_enc')");
				$jseditA=$jsedit;
				$jseditC=$boot->trswitch("Loadjs('$page?website-script-js=yes&servername=$servername')");
				
				if($ligne["certificate"]<>null){
					$certificate_text="".$tpl->_ENGINE_parse_body("<div>{certificate}: {$ligne["certificate"]}</div>");;
				}				
				
				
				if(isset($STATUS[$servername])){
					$ac=FormatNumber($STATUS[$servername]["AC"]);
					$ACCP=FormatNumber($STATUS[$servername]["ACCP"]);
					$ACHDL=FormatNumber($STATUS[$servername]["ACHDL"]);
					$ACRAQS=FormatNumber($STATUS[$servername]["ACRAQS"]);
					if($STATUS[$servername]["ACCP"]>0){
						$ss=round($STATUS[$servername]["ACRAQS"]/$STATUS[$servername]["ACCP"],2);
					}
					
					$reading=FormatNumber($STATUS[$servername]["reading"]);
					$writing=FormatNumber($STATUS[$servername]["writing"]);
					$waiting=FormatNumber($STATUS[$servername]["waiting"]);
	
					
					$status[]="{active_connections}: $ac&nbsp;|&nbsp;{accepteds}: $ACCP&nbsp;|&nbsp;{handles}:$ACRAQS ($ss/{second})";
					$status[]="&nbsp;|&nbsp;{keepalive}: $waiting&nbsp;|&nbsp;{reading}: $reading&nbsp;|&nbsp;{writing}:$writing";
				}
				
				
				if($limit_rate>0){
					$limit_rate_after_caption=$tpl->_ENGINE_parse_body("{limit_rate_after_caption}");
					$limit_rate_after_caption=str_replace("%s", "{$limit_rate}MB/s", $limit_rate_after_caption);
					$limit_rate_after_caption=str_replace("%f", "{$limit_rate_after}MB", $limit_rate_after_caption);
					$status[]="<div style='font-size:12px;font-weight:bold;color:#EEB853'>$limit_rate_after_caption</div>";
				}
				
				
				if(count($status)>0){
					$status_text=$tpl->_ENGINE_parse_body("<div style='font-size:12px'>".@implode("", $status)."</div>");
				}
				
				$FreeWebText=null;
				$explain_text=null;
				
				if($EnableFreeWeb==0){
					if($ligne["ipaddr"]=="127.0.0.1"){$ligne["ipaddr"]="{error}";}
					if($ligne["cache_peer_id"]==0){$ligne["cache_peer_id"]=-1;}
				}
				
				if(($ligne["ipaddr"]=="127.0.0.1") OR ($ligne["cache_peer_id"]==0)){
					$jsedit=$boot->trswitch("Loadjs('freeweb.edit.php?hostname=$servername&t=$t')");
					$certificate_text=null;
					$delete=imgsimple("delete-48.png",null,$DeleteFreeWeb);
					$jseditS=null;
					$freewebicon="domain-64.png";
					$FreeWebText="
					<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname=$servername&t=$t')\">
					127.0.0.1:82 (FreeWeb)</a>";
				}else{
					
					if($ligne["port"]>0){$portText=":{$ligne["port"]}";}	
					$explain_text=EXPLAIN_REVERSE($ligne["servername"]);
					$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port,OnlyTCP FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
					
					$OnlyTCP=$ligne2["OnlyTCP"];
					$FreeWebText="{$ligne2["servername"]}:{$ligne2["port"]}";
					$jseditS=$boot->trswitch("Loadjs('$page?js-source=yes&source-id={$ligne["cache_peer_id"]}')");
					if($OnlyTCP==0){
						$ligne["owa"]=0;
						if($ligne["ssl"]==1){if($ligne["port"]==80){$portText=$portText."/443";}}
					}
					
					if($OnlyTCP==1){
						$certificate_text=null;
						$portText=$portText." <strong>TCP</strong>";
					}
					
				}
				
				
				if($ligne["owa"]==1){
					$freewebicon="exchange-2010-64.png";
				}
				
				if($ligne["poolid"]>0){
					
					$freewebicon="64-cluster.png";
					$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT poolname FROM nginx_pools WHERE ID='{$ligne["poolid"]}'"));
					$ligne["ipaddr"]=$ligne2["poolname"];
					$jseditS=$boot->trswitch("Loadjs('miniadmin.proxy.reverse.nginx-pools.php?poolid-js={$ligne["poolid"]}&pool-id={$ligne["poolid"]}')");
					
				}
				
				$sql="SELECT * FROM nginx_aliases WHERE servername='$servername' $searchstring ORDER BY alias LIMIT 0,250";
				$results2=$q->QUERY_SQL($sql);
				$ali=array();$alitext=null;
				while($ligne=mysql_fetch_array($results2,MYSQL_ASSOC)){$ali[]="{$ligne["alias"]}";}
				
				if(count($ali)>0){$alitext="&nbsp;<i>(" .@implode("{or} ", $ali).")</i>&nbsp;"		;}
				$stats=null;
				
				$ligne2=mysql_fetch_array($q1->QUERY_SQL("SELECT COUNT(*) as tcount FROM awstats_files WHERE servername='$servername'","artica_backup"));
				if(!$q1->ok){echo "<p class=text-error>$q1->mysql_error</p>";}
				if($ligne2["tcount"]>0){
					// Abandonné pour l'instant
					//$stats="<a href='miniadmin.proxy.reverse.awstats.php?servername=".urlencode($servername)."'><img src='img/statistics-48.png'></a>";
				}

				$FinalDestination="{$ligne["ipaddr"]}$FreeWebText";
				if($RedirectQueries<>null){
					$FinalDestination=$RedirectQueries;
					$explain_text=$tpl->_ENGINE_parse_body("<br>{RedirectQueries_explain_table}");
				}
				
			
				if($default_server==1){
					$servername="$servername ($all_text * )";
					$icon="free-web-64.png";
					if($SiteEnabled==0){
						$icon="free-web-64-grey.png";
					}
				}
				
				$tr[]="
				<tr style='color:$color' id='$md'>
					<td width=64px nowrap $jseditC><img src='img/script-64.png' style='width:64px'></td>
					<td width=64px nowrap $jseditA><img src='img/$icon' style='width:64px'></td>
					<td width=80% style='vertical-align:middle'>
						<a href=\"javascript:blur();\" 
						style='font-size:18px;font-weight:bold;text-decoration:underline'
						OnClick=\"javascript:Loadjs('$page?website-js=yes&servername=$servername_enc')\">$servername$portText</a>
						$alitext$certificate_text$status_text
						$explain_text
						</td>
					<td width=1% nowrap style='vertical-align:middle'>$stats</td>
					<td width=64px nowrap $jsedit style='vertical-align:middle'><img src='img/$freewebicon' style='width:64px'></td>
					<td width=64px nowrap $jsedit style='vertical-align:middle'><img src='img/$icon2' style='width:64px'></td>
					<td width=1% nowrap $jseditS style='vertical-align:middle'>
						<span style='font-size:18px;font-weight:bold'>$FinalDestination</span>
						
					</td>
					
					
					<td width=1% nowrap style='vertical-align:middle'>$delete</td>
				</tr>
				";

	
	
	}
	$t=time();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_nginx_text=$tpl->javascript_parse_text("{delete_freeweb_nginx_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=4>{website}</th>
					
					<th colspan=2>{destination}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
<script>
var FreeWebIDMEM$t='';
		
	function FreeWebWebDavPerUsers(){
		Loadjs('freeweb.webdavusr.php?t=$t')
	}
	
	function RestoreSite(){
		Loadjs('freeweb.restoresite.php?t=$t')
	}
	
	function FreeWebsRefreshWebServersList(){
		ExecuteByClassName('SearchFunction');
	}
	
	
	var x_EmptyEvents= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		ExecuteByClassName('SearchFunction');

		
	}	
	
	var x_FreeWebsRebuildvHostsTable= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		alert('$freeweb_compile_background');
		ExecuteByClassName('SearchFunction');
		}

	
	var x_klmsresetwebpassword$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		ExecuteByClassName('SearchFunction');
	}	
	
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

var xDeleteFreeWeb$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}	
	$('#'+FreeWebIDMEM$t).remove();
}	

function DeleteFreeWeb$t(server,md){
	FreeWebIDMEM$t=md;
	if(confirm('$delete_freeweb_nginx_text')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-servername',server);
    	XHR.sendAndLoad('freeweb.php', 'GET',xDeleteFreeWeb$t);
	}
}

var x_FreeWebRefresh=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}	
	ExecuteByClassName('SearchFunction');
}		
		
		function FreeWebAddDefaultVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('AddDefaultOne','yes');
    		XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebRefresh);		
		}
		
		function FreeWeCheckVirtualHost(){
			var XHR = new XHRConnection();
			XHR.appendData('CheckAVailable','yes');
    		XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebDelete);			
		}
		
		var x_RebuildFreeweb$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}			
			ExecuteByClassName('SearchFunction');
		}			
		
		function RebuildFreeweb(){
			var XHR = new XHRConnection();
			XHR.appendData('rebuild-items','yes');
    		XHR.sendAndLoad('freeweb.php', 'GET',x_RebuildFreeweb$t);
		
		}

		function klmsresetwebpassword(){
		  if(confirm('$reset_admin_password ?')){
				var XHR = new XHRConnection();
				XHR.appendData('klms-reset-password','yes');
    			XHR.sendAndLoad('klms.php', 'POST',x_klmsresetwebpassword$t);
    		}		
		}
		
	function FreeWebsRebuildvHostsTable(servername){
		var XHR = new XHRConnection();
		XHR.appendData('FreeWebsRebuildvHosts',servername);
		XHR.sendAndLoad('freeweb.edit.php', 'POST',x_FreeWebsRebuildvHostsTable);
	}

	function FreeWebsEnableSite(servername){
		var XHR = new XHRConnection();
		XHR.appendData('FreeWebsEnableSite',servername);
		XHR.sendAndLoad('freeweb.servers.php', 'POST',x_FreeWebRefresh);	
	}
</script>			 					 				 		
";	
	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function sources_search(){
	$AdminPrivs=AdminPrivs();
	$prox=new squid_reverse();
	$searchstring=string_to_flexquery("sources-search");
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM reverse_sources WHERE 1 $searchstring ORDER BY servername LIMIT 0,250";
	
	if(!$AdminPrivs){
		$sql="SELECT reverse_sources.* FROM reverse_sources,reverse_privs
		WHERE reverse_privs.sourceid=reverse_sources.ID
		AND reverse_privs.uid='{$_SESSION["uid"]}' $searchstring ORDER BY servername LIMIT 0,250";
	}
	

	
	$results=$q->QUERY_SQL($sql,'artica_backup');
	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){

		$icon="64-idisk-server.png";
		$icon_failed="64-idisk-server-grey.png";
		$icon2="folder-network-64.png";
		$icon2_failed="folder-network-64-grey.png";
		$isSuccessIcon="none-20.png";
		$isSuccessIcon_failed="check-32-grey.png";
		$isSuccessIcon_success="check-32.png";
		$isSuccessLink=null;
		
		if($ligne["OnlyTCP"]==1){
			$icon="folder-network-64.png";
			$icon_failed="folder-network-64-grey.png";
		}
		
		$color="black";
		$md=md5(serialize($ligne));
		if($ligne["enabled"]==0){
			$icon=$icon_failed;
			$icon2=$icon2_failed;
			$color="#8a8a8a";
		}

		$servername=$ligne["servername"];
		$delete=imgsimple("delete-48.png",null,"Delete$t('{$ligne["ID"]}','$md')");

		$jsedit=$boot->trswitch($js_edit);
		if($ligne["ipaddr"]=="127.0.0.1"){$delete="&nbsp;";}
		
		$isSuccess=$ligne["isSuccess"];
		if(!$AdminPrivs){$delete="&nbsp;";}
		$jsedit=$boot->trswitch("Loadjs('$page?js-source=yes&source-id={$ligne["ID"]}')");
		
		$isSuccesstxt=unserialize(base64_decode($ligne["isSuccesstxt"]));
		if(count($isSuccesstxt)>1){
			$isSuccessLink=$boot->trswitch("Loadjs('$page?js-source-tests={$ligne["ID"]}')");
			$isSuccessIcon=$isSuccessIcon_success;
			if($isSuccess==0){
				$isSuccessIcon=$isSuccessIcon_failed;
				$color="#C40000";
			}
			
		}
		

		$tr[]="
		<tr style='color:$color' id='$md'>
			<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/$icon'></td>
			<td width=80% $jsedit style='vertical-align:middle'><span style='font-size:18px;font-weight:bold'>$servername</span></td>
			<td width=1% nowrap style='vertical-align:middle' $isSuccessLink><img src='img/$isSuccessIcon'></td>
			<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/$icon2'></td>
			<td width=1% nowrap $jsedit style='vertical-align:middle'><span style='font-size:18px;font-weight:bold'>{$ligne["ipaddr"]}:{$ligne["port"]}</span></td>
			<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";



	}
	$t=time();
	$page=CurrentPageName();
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th colspan=2>{servers}</th>
					<th>Tests</th>
					<th colspan=2>{address}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
<script>
var FreeWebIDMEM$t='';


function ApacheAllstatus(){
Loadjs('freeweb.status.php');
}


function FreeWebWebDavPerUsers(){
Loadjs('freeweb.webdavusr.php?t=$t')
}

function RestoreSite(){
Loadjs('freeweb.restoresite.php?t=$t')
}

function FreeWebsRefreshWebServersList(){
ExecuteByClassName('SearchFunction');
}


var x_EmptyEvents= function (obj) {
var results=obj.responseText;
if(results.length>3){alert(results);return;}
ExecuteByClassName('SearchFunction');


}

var x_FreeWebsRebuildvHostsTable= function (obj) {
var results=obj.responseText;
if(results.length>3){alert(results);return;}
alert('$freeweb_compile_background');
ExecuteByClassName('SearchFunction');
}


var x_klmsresetwebpassword$t= function (obj) {
var results=obj.responseText;
if(results.length>3){alert(results);return;}
ExecuteByClassName('SearchFunction');
}

var xDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	$('#'+FreeWebIDMEM$t).remove();
}

function Delete$t(id,md){
	FreeWebIDMEM$t=md;
	if(confirm('$delete_freeweb_text')){
		var XHR = new XHRConnection();
		XHR.appendData('source-delete',id);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}

var x_FreeWebRefresh=function (obj) {
var results=obj.responseText;
if(results.length>10){alert(results);return;}
ExecuteByClassName('SearchFunction');
}

function FreeWebAddDefaultVirtualHost(){
var XHR = new XHRConnection();
XHR.appendData('AddDefaultOne','yes');
XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebRefresh);
}

function FreeWeCheckVirtualHost(){
var XHR = new XHRConnection();
XHR.appendData('CheckAVailable','yes');
XHR.sendAndLoad('freeweb.php', 'POST',x_FreeWebDelete);
}

var x_RebuildFreeweb$t=function (obj) {
var results=obj.responseText;
if(results.length>0){alert(results);}
ExecuteByClassName('SearchFunction');
}

function RebuildFreeweb(){
var XHR = new XHRConnection();
XHR.appendData('rebuild-items','yes');
XHR.sendAndLoad('freeweb.php', 'GET',x_RebuildFreeweb$t);

}

function klmsresetwebpassword(){
if(confirm('$reset_admin_password ?')){
var XHR = new XHRConnection();
XHR.appendData('klms-reset-password','yes');
XHR.sendAndLoad('klms.php', 'POST',x_klmsresetwebpassword$t);
}
}

function FreeWebsRebuildvHostsTable(servername){
var XHR = new XHRConnection();
XHR.appendData('FreeWebsRebuildvHosts',servername);
XHR.sendAndLoad('freeweb.edit.php', 'POST',x_FreeWebsRebuildvHostsTable);
}

function FreeWebsEnableSite(servername){
var XHR = new XHRConnection();
XHR.appendData('FreeWebsEnableSite',servername);
XHR.sendAndLoad('freeweb.servers.php', 'POST',x_FreeWebRefresh);
}
</script>
";



}

function parameters(){
	$boot=new boostrap_form();
	$users=new usersMenus();
	$page=CurrentPageName();
	
	$array["{parameters}"]="$page?parameters-section=yes";
	echo $boot->build_tab($array);
		
	
}

function parameters_section(){
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
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	$EnableArticaInNGINX=$sock->GET_INFO("EnableArticaInNGINX");
	if($SquidReverseDefaultWebSite==null){$SquidReverseDefaultWebSite=$squid->visible_hostnameF();}
	
	if(!is_numeric($EnableArticaInNGINX)){$EnableArticaInNGINX=0;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	$MySQLNgnixType=$sock->GET_INFO("MySQLNgnixType");
	if(!is_numeric($MySQLNgnixType)){$MySQLNgnixType=1;}
	$EnableNginxStats=$sock->GET_INFO("EnableNginxStats");
	if(!is_numeric($EnableNginxStats)){$EnableNginxStats=0;}	
	
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLNginxParams")));
	$ListenPort=$TuningParameters["ListenPort"];
	$MySQLNginxWorkDir=$sock->GET_INFO("MySQLNginxWorkDir");
	if($MySQLNginxWorkDir==null){$MySQLNginxWorkDir="/home/nginxdb";}	
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	
	$boot->set_formtitle("{global_parameters}");
	
	$boot->set_checkbox("EnableNginx","{enable_nginx}",  "$EnableNginx",array("TOOLTIP"=>"{enable_nginx_text}"));
	$boot->set_checkbox("EnableFreeWeb","{enable_freeweb}",$EnableFreeWeb,array("TOOLTIP"=>"{enable_freeweb_text}"));
	
	$boot->set_checkbox("EnableArticaInNGINX","{ArticaWebConsoleAsFrontEnd}",  "$EnableArticaInNGINX");
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
	$form=$boot->Compile();
	
	$tpl=new templates();
	$button=button($tpl->_ENGINE_parse_body("{database_statistics_wizard}"), "Loadjs('MySQLNginx.wizard.php')");
	
	
	$array[1]="{server}";
	$array[2]="{client}";
	
	$DB[]="
	<H3>{statistics_database}</H3>
	<table style='width:100%'>
	<tr>
		<td style='font-size:16px' width=1% nowrap>{type}:</td>
		<td style='font-size:16px;font-weight:bold'>{$array[$MySQLNgnixType]}</td>
	</tr>";
	if($MySQLNgnixType==1){
		$DB[]="
	<tr>
		<td style='font-size:16px' width=1% nowrap>{directory}:</td>
		<td style='font-size:16px;font-weight:bold'>{$MySQLNginxWorkDir}</td>
	</tr>	
	<tr>
		<td style='font-size:16px' width=1% nowrap>{listen_port}:</td>
		<td style='font-size:16px;font-weight:bold'>{$ListenPort}</td>
	</tr>";	
	}else{
		$DB[]="
		<tr>
		<td style='font-size:16px' width=1% nowrap>{mysqlserver}:</td>
		<td style='font-size:16px;font-weight:bold'>{$TuningParameters["username"]}@{$TuningParameters["mysqlserver"]}:{$TuningParameters["RemotePort"]}</td>
		</tr>";		
	}
	
	$DB[]="<tr><td colspan=2 align='right'>$button</td></tr>";
	
	
	
	$DB[]="</table>";
	
	$html="<div class=form style='width:95%'>$form</div><div class=form style='width:95%'>".$tpl->_ENGINE_parse_body(@implode("\n", $DB))."</div>";
	
	echo $html;
	
	
	
}
function parameters_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableNginx", $_POST["EnableNginx"]);
	$sock->SET_INFO("SquidReverseDefaultWebSite", $_POST["SquidReverseDefaultWebSite"]);
	$sock->SET_INFO("EnableFreeWeb", $_POST["EnableFreeWeb"]);
	$sock->SET_INFO("EnableArticaInNGINX", $_POST["EnableArticaInNGINX"]);
	
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	
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
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_path}", 
			"Loadjs('$page?website-directory-js=yes&servername=$servername&folderid=')"));
	echo $boot->SearchFormGen("directory","directories-search","&servername=$servername",$EXPLAIN);	
	
}

function websites_popup_webserver_alias_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	
	
	$sock=new sockets();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_alias}",
			"Loadjs('$page?popup-webserver-alias-js=yes&servername=$servername')"));
	echo $boot->SearchFormGen("alias","popup-webserver-alias-search","&servername=$servername",$EXPLAIN);
		
	
	
}


function websites_popup_webserver_replace_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("nginx_replace_www", "zorder")){
		$q->QUERY_SQL("ALTER TABLE nginx_replace_www ADD `zorder` INT( 10 ) NOT NULL DEFAULT '0', ADD INDEX ( `zorder`)");
	}
	
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
	
	
	
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_rule}",
			"Loadjs('$page?popup-webserver-replace-js=yes&servername=$servername&replaceid=0')"));
			echo $error.$boot->SearchFormGen("rulename","popup-webserver-replace-search","&servername=$servername",$EXPLAIN);	
	
}

function websites_popup_webserver_replace_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["replaceid"];
	$servername=urlencode($_GET["servername"]);
	$title="{new_rule}";
	if($ID>0){
		$title="{rule}:$ID";
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin3(800,'$page?popup-webserver-replace-popup=yes&replaceid=$ID&servername=$servername','$title')";

}
function websites_popup_webserver_alias_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();

	$ask=$tpl->javascript_parse_text("{alias} ?");
	$t=time();
	$servername=$_GET["servername"];
echo "
var xAdd$t=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);return;}
	ExecuteByClassName('SearchFunction');
}
		
function Add$t(){
	var ali=prompt('$ask');
	if(!ali){return;}
	var XHR = new XHRConnection();
	XHR.appendData('popup-webserver-alias-add',ali);
	XHR.appendData('popup-webserver-alias-srv','$servername');
	XHR.sendAndLoad('$page', 'POST',xAdd$t);
	
}
Add$t();		
		
";	
}

function websites_popup_webserver_alias_add(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT IGNORE INTO nginx_aliases (`alias`,`servername`) VALUES ('{$_POST["popup-webserver-alias-add"]}','{$_POST["popup-webserver-alias-srv"]}')");
	if(!$q->ok){echo $q->mysql_error;return;}
}


function websites_popup_webserver_replace_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$title="{new_rule}";
	$bt="{add}";
	$ID=$_GET["replaceid"];
	$boot=new boostrap_form();
	$sock=new sockets();
	$servername=$_GET["servername"];
	
	if($ID>0){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nginx_replace_www WHERE ID='$ID'"));
		$bt="{apply}";
		$title="{$ligne["rulename"]}";
		$ligne["stringtosearch"]=stripslashes($ligne["stringtosearch"]);
		$ligne["replaceby"]=stripslashes($ligne["replaceby"]);
		$servername=$ligne["servername"];
	}
	if( $ligne["tokens"]==null){ $ligne["tokens"]="g";}
	if($ligne["rulename"]==null){$ligne["rulename"]=time();}
	$boot->set_hidden("replaceid", $ID);
	$boot->set_hidden("servername", $servername);
	$boot->set_formtitle($title);
	$boot->set_field("rulename", "{name}", $ligne["rulename"]);
	$boot->set_field("zorder", "{order}", $ligne["zorder"]);
	
	
	$boot->set_spacertitle("{search}");
	$boot->set_textarea("stringtosearch", "{search}", $ligne["stringtosearch"],array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_checkbox("AsRegex", "{regex}", $ligne["AsRegex"],array("TOOLTIP"=>"{replace_regex_explain}"));
	
	$boot->set_spacertitle("{replace}");
	$boot->set_textarea("replaceby", "{replace}", $ligne["replaceby"],array("MANDATORY"=>true,"ENCODE"=>true));
	$boot->set_field("tokens", "{flags}", $ligne["tokens"],array("MANDATORY"=>true));
	
	
	$boot->set_button($bt);
	if($ID==0){$boot->set_CloseYahoo("YahooWin3");}
	$boot->set_RefreshSearchs();
	$boot->set_formdescription("{nginx_subst_explain}");
	echo $boot->Compile();	
	
}
function websites_popup_webserver_replace_save(){
	$ID=$_POST["replaceid"];
	unset($_POST["replaceid"]);
	$_POST["stringtosearch"]=url_decode_special_tool($_POST["stringtosearch"]);
	$_POST["replaceby"]=url_decode_special_tool($_POST["replaceby"]);
	$q=new mysql_squid_builder();
	$f=new squid_reverse();

	while (list ($key, $value) = each ($_POST) ){
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";

	}

	if($ID>0){
		$sql="UPDATE nginx_replace_www SET ".@implode(",", $edit)." WHERE ID='$ID'";
	}else{

		$sql="INSERT IGNORE INTO nginx_replace_www (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n".__FUNCTION__."\n"."line:".__LINE__;return;}



}

function websites_popup_webserver_replace_search(){
	$searchstring=string_to_flexquery("popup-webserver-replace-search");
	$q=new mysql_squid_builder();
	
	$servername=$_GET["servername"];
	

	
	$sql="SELECT * FROM nginx_replace_www WHERE servername='$servername' $searchstring ORDER BY zorder LIMIT 0,250";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$f=new squid_reverse();$results=$q->QUERY_SQL($sql);}}
	if(!$q->ok){senderrors($q->mysql_error);}
	
	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$icon="www-web-search-64.png";
		$md=md5(serialize($ligne));
	
	
		$delete=imgsimple("delete-48.png",null,"Delete$t({$ligne["ID"]},'$md')");
		$jsEdit=$boot->trswitch("Loadjs('$page?popup-webserver-replace-js=yes&servername=$servername&replaceid={$ligne["ID"]}')");
		$stringtosearch=substr($ligne["stringtosearch"], 0,200)."...";
		$tr[]="
		<tr id='$md'>
			<td width=1% nowrap $jsEdit><img src='img/$icon'></td>
			<td width=80% $jsEdit><span style='font-size:16px;font-weight:bold'>{$ligne["rulename"]}</span><div style='margin-top:10px'><code style='font-size:13px !important'>$stringtosearch</code></div></td>
			<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
	
	
	
	}
	
	
	
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_rule}");
	echo $tpl->_ENGINE_parse_body("
				<table class='table table-bordered table-hover'>
	<thead>
		<tr>
			<th colspan=2>{rules}</th>
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
			XHR.appendData('popup-webserver-replace-delete',server);
			XHR.sendAndLoad('$page', 'POST',xDelete$t);
		}
	}
</script>
	";	
	
	
}
function websites_popup_webserver_replace_delete(){
	$ID=$_POST["popup-webserver-replace-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_replace_www WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}



function websites_popup_webserver_alias_search(){
	$searchstring=string_to_flexquery("popup-webserver-alias-search");
	$q=new mysql_squid_builder();
	
	$servername=$_GET["servername"];
	$sql="SELECT * FROM nginx_aliases WHERE servername='$servername' $searchstring ORDER BY alias LIMIT 0,250";
	$results=$q->QUERY_SQL($sql);
	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
		
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$md=md5(serialize($ligne));
		$delete=imgsimple("delete-48.png",null,"Delete$t({$ligne["ID"]},'$md')");
		$tr[]="
		<tr id='$md'>
			<td width=99%><span style='font-size:16px;font-weight:bold'>{$ligne["alias"]}</span></td>
			<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
		}
		
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_alias}");
	echo $tpl->_ENGINE_parse_body("
					<table class='table table-bordered table-hover'>
	<thead>
		<tr>
			<th>{aliases}</th>
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
		XHR.appendData('popup-webserver-alias-delete',server);
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
</script>
";
}
function websites_popup_webserver_alias_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM nginx_aliases WHERE ID='{$_POST["popup-webserver-alias-delete"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
}



function websites_directories_search(){
	$searchstring=string_to_flexquery("directories-search");
	$q=new mysql_squid_builder();
	
	$servername=$_GET["servername"];
	$servername_enc=urlencode($servername);
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
		$color="black";
		$md=md5(serialize($ligne));
		
		
		$delete=imgsimple("delete-48.png",null,"Loadjs('$page?delete-folder-id-js={$ligne["folderid"]}');");
		$jsEditWW=$boot->trswitch("Loadjs('website-js=yes&servername=$servername_enc')");
		$jsedit=$boot->trswitch("Loadjs('$page?website-directory-js=yes&servername=$servername&folderid={$ligne["folderid"]}')");
		$jseditA=$jsedit;
	
		
	
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
		$destination="{$ligne2["servername"]}:{$ligne2["port"]}";
		$jseditS=$boot->trswitch("Loadjs('$page?js-source=yes&source-id={$ligne["cache_peer_id"]}')");
		
	
			
		$jseditDest=$jsedit;
		$DestFont=16;
		$directory=trim(stripslashes($ligne["directory"]));
		$hostweb="<div><i>{$ligne["hostweb"]}</i></div>";
		
		if($ligne["local"]==1){
			$destination=$ligne["localdirectory"];
		}
		
		if($ligne["webpageid"]>0){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT subject FROM reverse_pages_content WHERE ID='{$ligne["webpageid"]}'"));
			$destination=$ligne2["subject"];
			$hostweb=null;
			$DestFont=12;
			$jseditDest=$boot->trswitch("Loadjs('miniadmin.crm.pages.php?ID={$ligne["webpageid"]}')");
		}
	
		$tr[]="
		<tr style='color:$color' id='$md'>
		<td width=1% nowrap $jsedit><img src='img/$icon'></td>
		<td width=80% $jsedit><span style='font-size:16px;font-weight:bold'>http(s)://$servername/$directory</span></td>
		<td width=1% nowrap $jsedit style='vertical-align:middle'><img src='img/$arrow'></td>
		<td width=1% nowrap $jseditDest style='vertical-align:middle'>
			<span style='font-size:{$DestFont}px;font-weight:bold'>$destination</span>$hostweb
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
			<th colspan=2>{paths}</th>
			<th colspan=2>{destination}</th>
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

function websites_privs_search(){
	$searchstring=string_to_flexquery("directories-search");
	$q=new mysql_squid_builder();
	
	$servername=$_GET["servername"];
	$WHERE="servername='$servername'";	
	if(is_numeric($servername)){
		$WHERE="sourceid='$servername'";	
	}
	$sql="SELECT * FROM reverse_privs WHERE $WHERE $searchstring ORDER BY uid LIMIT 0,250";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")){$pp=new squid_reverse();}
		$results=$q->QUERY_SQL($sql);
	}
	
	
	
	if(!$q->ok){senderrors($q->mysql_error);}
	
	$tpl=new templates();
	$GLOBALS["CLASS_TPL"]=$tpl;
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$md=md5(serialize($ligne));
		$delete=imgsimple("delete-24.png",null,"Delete$t('{$ligne["uid"]}','$md')");
		
	
		$tr[]="
		<tr id='$md'>
		<td width=80%><span style='font-size:18px;font-weight:bold'><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</span></td>
		<td width=1% nowrap style='vertical-align:middle'>$delete</td>
		</tr>
		";
	}
	
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete}");
	
	echo $tpl->_ENGINE_parse_body("
<table class='table table-bordered table-hover'>
	<thead>
		<tr>
			<th>{member}</th>
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
	
function Delete$t(uid,md){
	FreeWebIDMEM$t=md;
	if(confirm('$delete_freeweb_text '+ uid)){
		var XHR = new XHRConnection();
		XHR.appendData('uid-delete',uid);
		XHR.appendData('uid-srv','$servername');
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
}
</script>
";
}


function websites_popup_events_search(){
	$tpl=new templates();
	$servername=$_GET["servername"];
	$port=$_GET["port"];
	$sock=new sockets();
	$search=urlencode($_GET["popup-webserver-events-search"]);
	$datas=unserialize(base64_decode($sock->getFrameWork("nginx.php?www-events=yes&servername=$servername&port={$_GET["port"]}&search=$search&type={$_GET["type"]}")));
	krsort($datas);
	$boot=new boostrap_form();
	while (list ($key, $value) = each ($datas) ){
		$class=LineToClass($value);
		$tr[]="
		<tr class=$class>
		<td style='vertical-align:middle;font-size:11px'>$value</td>
		</tr>
		";		
	}
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
			<th>{events}</th>
			</tr>
			</thead>". @implode("", $tr)."</table>");	
}

FUNCTION  EXPLAIN_REVERSE($servername){
	$q=new mysql_squid_builder();
	$servernameencode=urlencode($servername);
	$exp[]="<div style='float:right;margin:10px'>
			<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.proxy.reverse.reconfigure.php?servername=$servernameencode')\">
			<img src='img/reconfigure-42.png' style='width:42px'></a></div>";
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	$ssl="{proto} (HTTP) ";
	
	if($ligne["ssl"]==1){
		$ssl="{proto} (HTTP<b>S</b>) ";
		
		if($ligne["port"]==80){
			$ssl="{proto} (HTTP) {and} {proto} (HTTP<b>S</b>) ";
		}
	}
	
	
	
	$page=CurrentPageName();
	$cache_peer_id=$ligne["cache_peer_id"];
	if($cache_peer_id==0){return;}
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT servername,ipaddr,port,ForceRedirect,OnlyTCP FROM reverse_sources WHERE ID='{$ligne["cache_peer_id"]}'"));
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error in ".basename(__FILE__)." line ".__LINE__."</p>";}
	$ForceRedirect="<br>{ForceRedirectyes_explain_table}";
	
	if($ligne["ForceRedirect"]==0){
		$ForceRedirect="<br>{ForceRedirectno_explain_table}";
	}
	
	if($ligne["ssl"]==1){
		$ssl="{proto} (HTTP<b>S</b>) ";
	}

	
	if($ligne["OnlyTCP"]==1){
		$ssl="{proto} TCP";$ForceRedirect=null;
		
	}
	$js="Loadjs('$page?js-source=yes&source-id={$ligne["cache_peer_id"]}')";
	$exp[]="<div><i style='font-size:12px'>$ssl";
	$exp[]="{redirect_communications_to}";
	//$exp[]="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\">";
	$exp[]="{$ligne["servername"]} {address} {$ligne["ipaddr"]} {on_port} {$ligne["port"]} id:$cache_peer_id";
	$exp[]=$ForceRedirect;
	
	
	$sql="SELECT * FROM nginx_exploits WHERE servername='$servername' LIMIT 0,5";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}
	
	$filters=array();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$groupid=$ligne["groupid"];
		$jsedit="Loadjs('miniadmin.nginx.exploits.groups.php?js-group=yes&ID=$groupid&servername={$_GET["servername"]}')";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM nginx_exploits_items WHERE groupid='$groupid'"));
		$RulesNumber=$ligne2["tcount"];
		$AF="<a href=\"javascript:blur();\" OnClick=\"javascript:$jsedit\" style='text-decoration:underline'>";
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM nginx_exploits_groups WHERE ID='$groupid'"));
		$filters[]="{group} $AF{$ligne2["groupname"]} ($RulesNumber {items})</a>";
	}
	if(count($filters)>0){
		$exp[]="<br>{check_anti_exploit_with}:".@implode(", ", $filters);
	}
	
	$jsban="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.nginx.exploits.php?firewall-js=yes&servername=$servername')\"
	style='text-decoration:underline'>";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT maxaccess,sendlogs FROM nginx_exploits_fw WHERE servername='$servername'"));
	if($ligne["maxaccess"]>0){
		$exp[]="<br>{bann_ip_after} $jsban{$ligne["maxaccess"]} {events}</a>";
	}
	if($ligne["sendlogs"]==1){$exp[]=",&nbsp;{write_logs_for} {$jsban}403 {errors}</a>";}
	
	
	$exp[]="</div>";
	$tpl=new templates();
	return $tpl->_ENGINE_parse_body(@implode(" ", $exp));
	
}
function websites_script(){
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_www", "DenyConf")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `DenyConf` smallint(1) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
	$sock=new sockets();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	$datas=unserialize(base64_decode($sock->getFrameWork("nginx.php?conf-view=".urlencode($servername))));
	$filename=$datas["FILENAME"];
	$datasR=@file_get_contents("/usr/share/artica-postfix/ressources/logs/$filename");
	
	$sock=new sockets();
	$tpl=new templates();
	$DenySquidWriteConf=$sock->GET_INFO("DenySquidWriteConf");
	if(!is_numeric($DenySquidWriteConf)){$DenySquidWriteConf=0;}
	$t=time();
	$page=CurrentPageName();
	$html="
	<div id='$t'></div>
		<div style='width:98%' class=form>
		<table>
			<tr>
				<td class=legend style='font-size:14px'>". $tpl->_ENGINE_parse_body("{deny_artica_to_write_config}")."</td>
				<td>". Field_checkbox("DenyConf$t", 1,$ligne["DenyConf"],"DenyConfSave$t()")."</td>
			</tr>
		</table>
		<textarea
			style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
			id='SQUID_CONTENT-$t'>$datasR</textarea>
			<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>
	</div>
	<script>
		var xDenyConfSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
		}
	
		function DenyConfSave$t(){
			var XHR = new XHRConnection();
			var DenyConfSave=0;
			if(document.getElementById('DenyConf$t').checked){DenyConfSave=1;}
			XHR.appendData('DenyConfSave', DenyConfSave);
			XHR.appendData('servername', '$servername');
			XHR.sendAndLoad('$page', 'POST',xDenyConfSave$t);
	}
	
	var xSaveUserConfFile$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(results.length>3){alert(results);return;}
	}	
	
	function SaveUserConfFile$t(){
		var XHR = new XHRConnection();
		XHR.appendData('servername', '$servername');
		XHR.appendData('filename', encodeURIComponent('$filename'));
		
		XHR.appendData('nginxconf', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',xSaveUserConfFile$t);
		}
	</script>
		";
	echo $html;
	
}
function websites_script_DenyConfSave(){
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("reverse_www", "DenyConf")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `DenyConf` smallint(1) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
	$q->QUERY_SQL("UPDATE reverse_www SET `DenyConf`='{$_POST["DenyConfSave"]}' WHERE `servername`='{$_POST["servername"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}
function websites_script_nginxconf(){
	$nginxconf=url_decode_special_tool($_POST["nginxconf"]);
	$servername=urlencode($_POST["servername"]);
	$filename=url_decode_special_tool($_POST["filename"]);
	$filename=urlencode($filename);
	@file_put_contents("ressources/logs/web/$servername", $nginxconf);
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("nginx.php?replic-conf=$servername&dest=$filename"));
	
	
}