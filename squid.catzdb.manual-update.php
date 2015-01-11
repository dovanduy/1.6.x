<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_POST["ManualArticaDBPath"])){ArticaDBPathSave();exit;}
if(isset($_POST["SAVENAS"])){nas_save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["nas-js"])){nas_js();exit;}
if(isset($_GET["nas-popup"])){nas_popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$ask=$tpl->javascript_parse_text("{manual_update}");
	echo "YahooWin5('700','$page?popup=yes','$ask',true);";

}
function nas_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$ask=$tpl->javascript_parse_text("{NAS_storage}");
	echo "YahooWin6('700','$page?nas-popup=yes','$ask',true);";

}

function nas_popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$ArticaDBNasUpdt=unserialize(base64_decode($sock->GET_INFO("ArticaDBNasUpdt")));
	if($ArticaDBNasUpdt["filename"]==null){$ArticaDBNasUpdt["filename"]="articadb.tar.gz";}
	
	$html="
	<div style='font-size:16px' class=text-info>{artica_db_shared_folder_explain}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>".Field_text("hostname-$t",$ArticaDBNasUpdt["hostname"],"font-size:16px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{shared_folder}:</td>
		<td>".Field_text("folder-$t",$ArticaDBNasUpdt["folder"],"font-size:16px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{filename}:</td>
		<td>".Field_text("filename-$t",$ArticaDBNasUpdt["filename"],"font-size:16px;width:300px")."</td>
	</tr>				
				
				
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td>".Field_text("username-$t",$ArticaDBNasUpdt["username"],"font-size:16px;width:200px")."</td>
	</tr>
	
	<tr>
	<td class=legend style='font-size:16px'>{password}:</td>
	<td>".Field_password("password-$t",$ArticaDBNasUpdt["password"],"font-size:16px;width:200px")."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>
	". button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
<script>
	var xSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin6Hide();
			UnlockPage();
		}
	
	
		function Save$t(){
			var XHR = new XHRConnection();
			XHR.appendData('SAVENAS',1);
			XHR.appendData('hostname',document.getElementById('hostname-$t').value);
			XHR.appendData('filename',encodeURIComponent(document.getElementById('filename-$t').value));
			XHR.appendData('folder',document.getElementById('folder-$t').value);
			XHR.appendData('username',encodeURIComponent(document.getElementById('username-$t').value));
			XHR.appendData('password',encodeURIComponent(document.getElementById('password-$t').value));
			XHR.sendAndLoad('$page', 'POST',xSave$t);
			
		}
</script>			
			
			
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function nas_save(){
	while (list ($num, $ligne) = each ($_POST) ){
		$_POST[$num]=url_decode_special_tool($ligne);
	}
	
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "ArticaDBNasUpdt");
	
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	//1753076
	
	$ManualArticaDBPath=$sock->GET_INFO("ManualArticaDBPath");
	$ManualArticaDBPathNAS=$sock->GET_INFO("ManualArticaDBPathNAS");
	if(!is_numeric($ManualArticaDBPathNAS)){$ManualArticaDBPathNAS=0;}
	
	if($ManualArticaDBPath==null){$ManualArticaDBPath="/home/manualupdate/articadb.tar.gz";}
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	
	$ArticaDBPathenc=urlencode($ArticaDBPath);
	$arrayinfos=unserialize(base64_decode($sock->getFrameWork("services.php?dir-status=$ArticaDBPathenc")));
	
	$REQUIRE=round(1753076/1024);
	$SIZE=round($arrayinfos["SIZE"]/1024);
	
	if($SIZE<$REQUIRE){
		$error="<center style='color:red;font-weight:bold;font-size:16px;margin:20px'><span >".
		$tpl->_ENGINE_parse_body("{no_enough_free_space_on_target}<br>&laquo;$ArticaDBPath&raquo;<br>({$SIZE}MB {require} {$REQUIRE}MB)</center>");
	}
	
	$t=time();
	$html="
	<div style='width:98%' class=form>
	<div class=text-info style='font-size:16px'>{squid_catz_dbs_manual_update_explain}</div>
			
	<center style='font-size:16px;text-decoration:underline;margin:20px' 
			OnClick=\"javascript:s_PopUp('http://www.artica.fr/artica-catzdb.php',800,800);\">{where_to_find_package} ?</center>		
	$error
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{use_remote_shared_folder}:</td>
		<td>". Field_checkbox("ManualArticaDBPathNAS",1,$ManualArticaDBPathNAS,"ManualArticaDBPathNASCK()")."</td>
		<td width=1%>". button("{settings}","Loadjs('$page?nas-js=yes')")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{source_package_path}:</td>
		<td>". Field_text("ManualArticaDBPath",$ManualArticaDBPath,"font-size:14px;width:320px")."</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{database_storage_path}:</td>
		<td>". Field_text("ArticaDBPath-$t",$ArticaDBPath,"font-size:16px;width:320px")."</td>
		<td width=1%>". button_browse("ArticaDBPath-$t")."</td>
	</tr>				
	<tr>
		<td colspan=3 align='right'>". button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.blacklist.upd.php');
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ManualArticaDBPath',document.getElementById('ManualArticaDBPath').value);
	XHR.appendData('ArticaDBPath',document.getElementById('ArticaDBPath-$t').value);
	if(document.getElementById('ManualArticaDBPathNAS').checked){
		XHR.appendData('ManualArticaDBPathNAS',1);
	}else{
		XHR.appendData('ManualArticaDBPathNAS',0);
	}
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}

function ManualArticaDBPathNASCK(){
	if(document.getElementById('ManualArticaDBPathNAS').checked){
		document.getElementById('ManualArticaDBPath').disabled=true;
	}else{
		document.getElementById('ManualArticaDBPath').disabled=false;
	}
}

</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function ArticaDBPathSave(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->SET_INFO("ManualArticaDBPathNAS", $_POST["ManualArticaDBPathNAS"]);
	$sock->SET_INFO("ManualArticaDBPath",$_POST["ManualArticaDBPath"]);
	$sock->SET_INFO("ArticaDBPath",$_POST["ArticaDBPath"]);
	$sock->SET_INFO("DisableArticaProxyStatistics", 0);
	$sock->SET_INFO("EnableRemoteStatisticsAppliance", 0);
	$sock->SET_INFO("EnableWebProxyStatsAppliance", 0);
	$sock->SET_INFO("EnableArticaDB", 1);	
	sleep(2);
	echo $tpl->javascript_parse_text("{task_has_been_scheduled_in_background_mode}",1);
	
}