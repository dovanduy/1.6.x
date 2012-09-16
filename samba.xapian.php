<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.kav4samba.inc');
	include_once('ressources/class.system.network.inc');
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}
	if(isset($_GET["debug-page"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);$GLOBALS["VERBOSE"]=true;}

	
	if(!CheckSambaRights()){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "<H1>$ERROR_NO_PRIVS</H1>";die();
	}
	
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_POST["EnableSambaXapian"])){EnableSambaXapian();exit;}
	if(isset($_GET["xapian-db-size"])){xapiandbsize();exit;}
	if(isset($_GET["portal-options"])){portal_options();exit;}
	if(isset($_POST["XapianSearchTitle"])){portal_options_save();exit;}
tabs();


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$users=new usersMenus();
	if(!$users->XAPIAN_PHP_INSTALLED){
		
			$help=Paragraphe("help-64.png","{help}","{online_help}",
	"javascript:s_PopUpFull('http://nas-appliance.org/index.php?cID=188','1024','900');"
	,null,280);
		
	echo $tpl->_ENGINE_parse_body("
		<center style='margin:50px'>
		<table style='width:100%'>
			<tr>
				<td width=1%><img src='img/error-128.png'></td>
				<td valign='top' style='font-size:18px'>{XAPIAN_NOT_INSTALLED_ERROR}</td>
			</tr>
		</table>
		<p>$help</p>
		
		</center>
		
		");
			
		return;
	}
	
	

	$array["parameters"]='{main_settings}';
	$array["folders"]='{folders}';
	$array["portal-options"]='{portal_options}';
	
	
	
	
	$tpl=new templates();
	

	while (list ($num, $ligne) = each ($array) ){
		if($num=="folders"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"samba.xapian.folders.php\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
			
		}
		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_samba_xapian style='width:100%;height:710px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_samba_xapian').tabs();
			});
		</script>";		
		
	
	
}

function portal_options(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$XapianSearchTitle=$sock->GET_INFO("XapianSearchTitle");
	$XapianRemoveLangage=$sock->GET_INFO("XapianRemoveLangage");
	$XapianRemoveLogon=$sock->GET_INFO("XapianRemoveLogon");
	
	$XapianDisableAdm=$sock->GET_INFO("XapianDisableAdm");
	if(!is_numeric($XapianDisableAdm)){$XapianDisableAdm=0;}
	
	
	
	if($XapianSearchTitle==null){$XapianSearchTitle="Xapian Desktop {search}";}
	if(!is_numeric($XapianRemoveLangage)){$XapianRemoveLangage=0;}
	if(!is_numeric($XapianRemoveLogon)){$XapianRemoveLogon=0;}
	
	
	$t=time();
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{portal_title}:</td>
		<td>". Field_text("XapianSearchTitle",$XapianSearchTitle,"font-size:16px;width:320px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{remove_the_language_selector}:</td>
		<td>". Field_checkbox("XapianRemoveLangage",1,$XapianRemoveLangage)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{remove_the_logon_option}:</td>
		<td>". Field_checkbox("XapianRemoveLogon",1,$XapianRemoveLogon)."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{disable_administration_tasks}:</td>
		<td>". Field_checkbox("XapianDisableAdm",1,$XapianDisableAdm)."</td>
	</tr>		
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveXapianPortalOptions()","16px")."</td>
	</table>
	<script>
	var X_SaveXapianPortalOptions= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		RefreshTab('main_config_samba_xapian');
	}
	
	function SaveXapianPortalOptions(){
		var XHR = new XHRConnection();
		XHR.appendData('XapianSearchTitle',document.getElementById('XapianSearchTitle').value);
		
		if(document.getElementById('XapianRemoveLangage').checked){
			XHR.appendData('XapianRemoveLangage',1);
		}else{
			XHR.appendData('XapianRemoveLangage',0);
		}
		if(document.getElementById('XapianRemoveLogon').checked){
			XHR.appendData('XapianRemoveLogon',1);
		}else{
			XHR.appendData('XapianRemoveLogon',0);
		}	
		if(document.getElementById('XapianDisableAdm').checked){
			XHR.appendData('XapianDisableAdm',1);
		}else{
			XHR.appendData('XapianDisableAdm',0);
		}			
	
		XHR.sendAndLoad('$page', 'POST',X_SaveXapianPortalOptions);	
		
	}
</script>	
	
	
	";
echo $tpl->_ENGINE_parse_body($html);	
	
}


function parameters(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();		
	$EnableSambaXapian=$sock->GET_INFO("EnableSambaXapian");
	$EnableScannedOnly=$sock->GET_INFO('EnableScannedOnly');
	$EnableHomesXapian=$sock->GET_INFO('EnableHomesXapian');
	if(!is_numeric($EnableSambaXapian)){$EnableSambaXapian=0;}
	if(!is_numeric($EnableScannedOnly)){$EnableScannedOnly=0;}	
	$SambaXapianAuth=unserialize(base64_decode($sock->GET_INFO("SambaXapianAuth")));
	
	$l["none"]="none";
	$l["danish"]="danish";
	$l["dutch"]="dutch";
	$l["english"]="english";
	$l["finnish"]="finnish";
	$l["french"]="french";
	$l["german"]="german";
	$l["german2"]="german2";
	$l["hungarian"]="hungarian";
	$l["italian"]="italian";
	$l["kraaij_pohlmann"]="kraaij_pohlmann";
	$l["lovins"]="lovins";
	$l["norwegian"]="norwegian";
	$l["porter"]="porter";
	$l["portuguese"]="portuguese";
	$l["romanian"]="romanian";
	$l["russian"]="russian";	
	$language=Field_array_Hash($l,"lang-$t",$SambaXapianAuth["lang"],null,null,0,"font-size:14px");
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();

	//EnableSambaXapian
	$t=time();
	$formcred="
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 style='font-size:18px'>{credentials}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{username}:</td>
		<td>". Field_text("username-$t",$SambaXapianAuth["username"],"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{password}:</td>
		<td>". Field_password("password-$t",$SambaXapianAuth["password"],"font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{domain}:</td>
		<td>". Field_text("domain-$t",$SambaXapianAuth["domain"],"font-size:14px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{address}:</td>
		<td>". Field_array_Hash($ips,"ip-$t",$SambaXapianAuth["ip"],"style:font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{language}:</td>
		<td>$language</td>
	</tr>	
	</table>	
	";

	
	$p=Paragraphe_switch_img("{ENABLE_XAPIAN_SAMBA2}", "{ENABLE_XAPIAN_SAMBA_TEXT2}","EnableSambaXapian",$EnableSambaXapian,null,450);
	$p2=Paragraphe_switch_img("{XAPIAN_SCAN_HOME_FOLDERS}","{XAPIAN_SCAN_HOME_FOLDERS_TEXT}",
	"EnableHomesXapian",$EnableHomesXapian,null,450);
	

	
	$forms="<table style='width:99%' class=form>
	<tr>
		<td>$p</td>
	</tr>
	<tr>
	<td>$formcred</td>
	</tr>
	<tr>
		<td><div style='margin-top:20px'>$p2</div></td>
	</tr>	
	<tr>
		<td align='right'><hr>". button("{apply}","SaveXapianEnable()","16px")."</td>
	</tr>
	</table>
	
	";
	$help=Paragraphe("help-64.png","{help}","{online_help}",
	"javascript:s_PopUpFull('http://nas-appliance.org/index.php?cID=188','1024','900');"
	,null,280);
	$events=Paragraphe("events-64.png","{events}","{display_indexation_events}",
	"javascript:Loadjs('squid.update.events.php?table=system_admin_events&category=xapian');"
	,null,280);	
	
		
	
	$html="<table style='width:100%'>
	<tr>
		<td valign='top' style='width:50%'>
			$events
			<div id='xapian-db-size'></div>
			$help
			
			
		
		</td>
		<td valign='top'>$forms</td>
	</tr>
	</table>
	
	<script>
	
	var X_SaveXapianEnable= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		RefreshTab('main_config_samba_xapian');
	}
	
	function SaveXapianEnable(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableSambaXapian')){
			XHR.appendData('EnableSambaXapian',document.getElementById('EnableSambaXapian').value);
			document.getElementById('img_EnableSambaXapian').src='img/wait_verybig.gif';
		}
		if(document.getElementById('EnableHomesXapian')){
			XHR.appendData('EnableHomesXapian',document.getElementById('EnableHomesXapian').value);
			document.getElementById('img_EnableHomesXapian').src='img/wait_verybig.gif';
		}		
		
		XHR.appendData('username',document.getElementById('username-$t').value);
		XHR.appendData('domain',document.getElementById('domain-$t').value);
		XHR.appendData('ip',document.getElementById('ip-$t').value);
		XHR.appendData('username',document.getElementById('username-$t').value);
		XHR.appendData('lang',document.getElementById('lang-$t').value);
		var pp=encodeURIComponent(document.getElementById('password-$t').value);
		XHR.appendData('password',pp);		
		XHR.sendAndLoad('$page', 'POST',X_SaveXapianEnable);	
		
	}
	
	function xapiandbsize(){
		LoadAjax('xapian-db-size','$page?xapian-db-size=yes');
	}
	xapiandbsize();
	QuickLinkShow('quicklinks-APP_SAMBA');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function EnableSambaXapian(){
	$sock=new sockets();
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$sock->SET_INFO("EnableSambaXapian", $_POST["EnableSambaXapian"]);
	$sock->SET_INFO("EnableHomesXapian", $_POST["EnableHomesXapian"]);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SambaXapianAuth");
	$samab=new samba();
	$samab->SaveToLdap();	
}
function xapiandbsize(){
	$tpl=new templates();
	$sock=new sockets();
	$dbsize=$sock->getFrameWork("xapian.php?xapian-db-size=yes");
	if($dbsize>0){$dbsize=FormatBytes($dbsize);}
		$help=Paragraphe("disk-64.png","{size}:&nbsp;$dbsize","{xapian_db_size_explain}","",null,280);
	echo $tpl->_ENGINE_parse_body($help);
	
}
function portal_options_save(){
	
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST) ){
		$sock->SET_INFO($key, $value);
	}
	
}