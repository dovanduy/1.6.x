<?php
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	include_once('ressources/class.freeweb.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
$usersmenus=new usersMenus();



if(!$usersmenus->AsWebMaster){
	echo "<p class=text-error>{ERROR_NO_PRIVS}</p>";
	die();
}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_POST["servername"])){add_wordpress();exit;}
if(isset($_GET["wordpress-status"])){wordpress_status();exit;}
if(isset($_GET["wordpress-info"])){wordpress_info();exit;}
if(isset($_GET["backup"])){wordpress_backup();exit;}
if(isset($_POST["FTP_ENABLE"])){wordpress_backup_save();exit;}
tabs();


function tab_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{artica_license}");
	$html="AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes')";
	echo $html;	
	
}

function add_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_wordpress_site}");
	$html="YahooWin(650,'$page?add-popup=yes&t={$_GET["t"]}','$title')";
	echo $html;
	
}
function add_popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$parcourir_domaines=button("{browse}...","Loadjs('browse.domains.php?field=domainname-$t')",12);
	$administrator=$_SESSION["uid"];
	$password=null;
	if($administrator==-100){
		$ldap=new clladp();
		$administrator=$ldap->ldap_admin;
		$password=$ldap->ldap_password;
	}
	
	if($password==null){$password=PasswordGenerator();}
	
	$html="
<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px;vertical-align:middle'>{webservername}:</td>
			<td colspan=2>".Field_text("servername-$t",null,"font-size:18px;padding:3px;font-weight:bold;width:190px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px;vertical-align:middle'>{domainname}:</td>
			<td>".Field_text("domainname-$t",null,"font-size:18px;padding:3px;width:220px;font-weight:bold"
					,null,null,null,false,"SaveCheck$t(event)")."</td>
			<td>$parcourir_domaines</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px;vertical-align:middle'>{website_directory}:</td>
			<td>".Field_text("dirname-$t",null,"font-size:18px;padding:3px;width:220px;font-weight:bold"
					,null,null,null,false,"SaveCheck$t(event)")."</td>
			<td>". button_browse("dirname-$t")."</td>
		</tr>
		<tr><td colspan=2><div style='margin:20px;font-size:20px'>{wordpress_administrator}</div></td></tr>		
		<tr>
			<td class=legend style='font-size:18px;vertical-align:middle'>{administrator}:</td>	
			<td colspan=2>".Field_text("administrator-$t",$administrator,"font-size:18px;padding:3px;width:220px;font-weight:bold"
					,null,null,null,false,"SaveCheck$t(event)")."</td>
			
		</tr>
		<tr>
			<td class=legend style='font-size:18px;vertical-align:middle'>{password}:</td>	
			<td colspan=2>".Field_password("password-$t",$password,"font-size:18px;padding:3px;width:220px;font-weight:bold"
					,null,null,null,false,"SaveCheck$t(event)")."</td>
			
		</tr>							
		<tr>
			<td colspan=3 align='right'><hr>". button("{add}", "Save$t()","26")."</td>
		</tr>
	</table>
</div>	
<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		YahooWinHide();
		$('#freewebs-table-{$_GET["t"]}').flexReload();
		UnlockPage();
		
	}
	
function SaveCheck$t(e){
	if(!checkEnter(e)){return;}
	Save$t();

}
function Save$t(){
		LockPage();
		var XHR = new XHRConnection();
		XHR.appendData('servername',document.getElementById('servername-$t').value);
		XHR.appendData('domainname',document.getElementById('domainname-$t').value);
		XHR.appendData('directory',encodeURIComponent(document.getElementById('dirname-$t').value));
		XHR.appendData('administrator',encodeURIComponent(document.getElementById('administrator-$t').value));
		XHR.appendData('password',encodeURIComponent(document.getElementById('password-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);
		}

</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function add_wordpress(){
	
	$servername=trim(strtolower($_POST["servername"]));
	$domainname=trim(strtolower($_POST["domainname"]));
	
	if($domainname<>null){
		$servername="$servername.$domainname";
	}
	
	$servername=str_replace('..', '.', $servername);
	$servername=str_replace('/', '.', $servername);
	$servername=str_replace('\\', '.', $servername);
	$servername=str_replace(' ', '.', $servername);
	$servername=str_replace('$', '.', $servername);
	$servername=str_replace('#', '.', $servername);
	$servername=str_replace('%', '.', $servername);
	$servername=str_replace('*', '.', $servername);
	$servername=str_replace('..', '.', $servername);
	$_POST["administrator"]=url_decode_special_tool($_POST["administrator"]);
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	$_POST["directory"]=url_decode_special_tool($_POST["directory"]);
	
	if(substr($servername, strlen($servername)-1,1)=='.'){$servername=substr($servername, 0,strlen($servername)-1);}
	if(substr($servername,0,1)=='.'){$servername=substr($servername, 1,strlen($servername));}
	
	$free=new freeweb();
	$free->servername=$servername;
	$free->groupware="WORDPRESS";
	$free->groupware_admin=$_POST["administrator"];
	$free->groupware_password=$_POST["password"];
	$free->www_dir=$_POST["directory"];
	if(!$free->CreateSite(true)){echo $free->error; }
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	
	
	$array["status"]="{status}";
	$array["table"]='{wordpress_websites}';
	$array["backup"]='{backup}';
	$array["schedules"]='{schedules}';
	$array["events"]='{events}';
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="schedules"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"schedules.php?ForceTaskType=77\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="events"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"apache.watchdog-events.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		
		$tab[]="<li style='font-size:{$fontsize}px'><a href=\"$page?$num=yes\"><span >$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_artica_wordpress",1100)."<script>LeftDesign('wp-256.png');</script>";
	
}

function status(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$INSTALLED=trim($sock->getFrameWork("wordpress.php?is_installed=yes"));
	
	if($INSTALLED<>"TRUE"){
		$button_install="<center style='margin:80px'>".button("{install_Wordpress}","Loadjs('wordpress.install.php')",32).
		"</center>";
		$version="- - -";
		
	}	
	
	if($INSTALLED=="TRUE"){
		$version=trim($sock->getFrameWork("wordpress.php?version=yes"));
		$par="<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td valign='top' style='vertical-align:top;width:300px'><div id='wordpress-status'></div></td>
			<td valign='top' style='vertical-align:top;width:99%'><div id='wordpress-info'></div></td>
		</tr>
		</table>
		</div>		
		<script>
			LoadAjax('wordpress-status','$page?wordpress-status=yes');
			LoadAjax('wordpress-info','$page?wordpress-info=yes');
		</script>
		";
	}
		
	$html="<div style='font-size:30px;margin-bottom:25px'>Wordpress {version} $version</div>
	<div style='font-size:26px;margin-top:15px'>
	
	$button_install$par
	</div>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function table(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$bt_klms_reset_pwd=null;
	$joomlaservername=$tpl->_ENGINE_parse_body("{joomlaservername}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$requests=$tpl->_ENGINE_parse_body("{requests}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$new_server=$tpl->_ENGINE_parse_body("{new_wordpress_site}");
	$add_default_www=$tpl->_ENGINE_parse_body("{add_default_www}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");
	$WebDavPerUser=$tpl->_ENGINE_parse_body("{WebDavPerUser}");
	$rebuild_items=$tpl->_ENGINE_parse_body("{rebuild_items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$help=$tpl->_ENGINE_parse_body("{help}");
	$restore=$tpl->_ENGINE_parse_body("{restore}");
	$status=$tpl->javascript_parse_text("{status}");
	$reset_admin_password=$tpl->javascript_parse_text("{reset_admin_password}");
	$choose_your_zarafa_webserver_type=$tpl->_ENGINE_parse_body("{choose_your_zarafa_webserver_type}");
	$freeweb_compile_background=$tpl->javascript_parse_text("{freeweb_compile_background}");
	$enable=$tpl->javascript_parse_text("{enable}");
	$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$bt_help="{name: '$help', bclass: 'Help', onpress : HelpSection},";
	$bt_restore="{name: '$restore', bclass: 'Restore', onpress : RestoreSite},";
	$bt_stats="{name: '$status', bclass: 'Network', onpress : ApacheAllstatus},";
	$MAIN_TITLE=null;
	$tablewidth=874;
	$servername_size=241;
	$bt_function_add="AddNewFreeWebServer";


	if($_GET["force-groupware"]<>null){
		include_once(dirname(__FILE__)."/ressources/class.apache.inc");
		$default_www=null;
		$bt_webdav=null;
		$ach=new vhosts();
		$MAIN_TITLE="<span style=font-size:18px>".$tpl->_ENGINE_parse_body("{".$ach->TEXT_ARRAY[$_GET["force-groupware"]]["TITLE"]."}")."</span>";
		

	}
	

	if(!$users->APACHE_MOD_STATUS){
		$bt_stats=null;

	}

	



	$t=time();

	$buttons="
	buttons : [
	{name: '<b>$new_server</b>', bclass: 'add', onpress : $bt_function_add},$bt_rebuild

	],";
	$html="
	<table class='freewebs-table-$t' style='display: none' id='freewebs-table-$t' style='width:100%;margin:-10px'></table>
	<script>
	FreeWebIDMEM='';
	$('#freewebs-table-$t').flexigrid({
	url: 'freeweb.servers.php?servers-list=yes&t=$t&force-groupware=WORDPRESS',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'icon', width : 31, sortable : false, align: 'center'},
	{display: '$joomlaservername', name : 'servername', width :$servername_size, sortable : true, align: 'left'},
	{display: 'compile', name : 'compile', width :80, sortable : false, align: 'center'},
	{display: '$enable', name : 'enabled', width :80, sortable : true, align: 'center'},
	{display: '$size', name : 'DirectorySize', width :80, sortable : true, align: 'center'},
	{display: '$memory', name : 'memory', width :80, sortable : false, align: 'center'},
	{display: '$requests', name : 'requests', width : 72, sortable : false, align: 'center'},
	{display: 'SSL', name : 'useSSL', width : 31, sortable : true, align: 'center'},
	{display: 'RESOLV', name : 'resolved_ipaddr', width : 31, sortable : true, align: 'center'},
	{display: 'DNS', name : 'dns', width : 31, sortable : false, align: 'center'},
	{display: '$member', name : 'member', width : 31, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none1', width : 80, sortable : false, align: 'center'},
	],
	$buttons

	searchitems : [
	{display: '$joomlaservername', name : 'servername'},
	],
	sortname: 'servername',
	sortorder: 'desc',
	usepager: true,
	title: '$MAIN_TITLE',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});


function HelpSection(){
	LoadHelp('freewebs_explain','',false);
}

function AddNewFreeWebServer(){
	Loadjs('$page?add-js=yes&t=$t')
	}



function ApacheAllstatus(){
Loadjs('freeweb.status.php');
}


function RestoreSite(){
		Loadjs('freeweb.restoresite.php?t=$t')
}

function FreeWebsRefreshWebServersList(){
	$('#freewebs-table-$t').flexReload();
}


var x_EmptyEvents= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#freewebs-table-$t').flexReload();
	//$('#grid_list').flexOptions({url: 'newurl/'});
	// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
}

var x_FreeWebsRebuildvHostsTable= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	alert('$freeweb_compile_background');
	$('#freewebs-table-$t').flexReload();
	//$('#grid_list').flexOptions({url: 'newurl/'});
	// $('#fgAllPatients').flexOptions({ query: 'blah=qweqweqwe' }).flexReload();
}


var x_klmsresetwebpassword$t= function (obj) {
var results=obj.responseText;
if(results.length>3){alert(results);return;}
$('#freewebs-table-$t').flexReload();
}

var x_FreeWebDelete=function (obj) {
var results=obj.responseText;
if(results.length>10){alert(results);return;}
$('#row'+FreeWebIDMEM).remove();
if(document.getElementById('container-www-tabs')){	RefreshTab('container-www-tabs');}
}

function FreeWebDelete(server,dns,md){
FreeWebIDMEM=md;
if(confirm('$delete_freeweb_text')){
var XHR = new XHRConnection();
if(dns==1){if(confirm('$delete_freeweb_dnstext')){XHR.appendData('delete-dns',1);}else{XHR.appendData('delete-dns',0);}}
XHR.appendData('delete-servername',server);
XHR.sendAndLoad('freeweb.php', 'GET',x_FreeWebDelete);
}
}

var x_FreeWebRefresh=function (obj) {
var results=obj.responseText;
if(results.length>10){alert(results);return;}
$('#freewebs-table-$t').flexReload();
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
$('#freewebs-table-$t').flexReload();
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
</script>";

echo $html;

}
function wordpress_status(){
	
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
	$datas=$sock->getFrameWork("cmd.php?apachesrc-ini-status=yes");
	$ini->loadString(base64_decode($datas));
	
	
	
	
	$serv[]=DAEMON_STATUS_ROUND("APP_APACHE_SRC",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_PHPFPM",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("PUREFTPD",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_TOMCAT",$ini,null,0);
	$serv[]=DAEMON_STATUS_ROUND("APP_NGINX",$ini,null,0);
	
	
	$refresh="<div style='text-align:right;margin-top:8px'>".
	imgtootltip("refresh-24.png","{refresh}","RefreshTab('main_artica_wordpress')")."</div>";
	while (list ($a,$b) = each ($serv) ){if(trim($b)==null){continue;}$statusT[]=$b;}
	$status=@implode("<br>", $statusT).$refresh;
	echo $tpl->_ENGINE_parse_body($status);	
	
}

function wordpress_info(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="<div style='font-size:18px;margin-bottom:30px' class=explain>{APP_WORDPRESS_TEXT}<p>&nbsp;</p>{APP_WORDPRESS_ARTICA_TEXT}</div>
		<center style='margin:30px'>". button("{new_wordpress_site}","Loadjs('$page?add-js=yes&t=')",35)."</center>	
			
		";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function wordpress_backup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$WordpressBackupParams=unserialize(base64_decode($sock->GET_INFO("WordpressBackupParams")));
	if($WordpressBackupParams["DEST"]==null){$WordpressBackupParams["DEST"]="/home/wordpress-backup";}
	$t=time();
	
$html="
<div id='div-$t'></div>
	<div class=explain style='font-size:16px'>{wordpress_backup_parameters}</div>
	<div style='width:98%' class=form>
		<table style='width:99%'>
		<tr>
			<td class=legend style='font-size:18px'>{backup_directory}:</td>
			<td>". Field_text("DEST-$t",$WordpressBackupParams["DEST"],"font-size:18px;padding:3px;width:253px")."</td>
			<td>". button("{browse}","Loadjs('SambaBrowse.php?no-shares=yes&field=DEST-$t&no-hidden=yes')")."</td>
		</tr>
		<tr><td colspan=3><hr><span style='font-size:26px;vertical-align:middle'>FTP: {backup}</td></tr>
		<tr><td colspan=3>". Paragraphe_switch_img("{enable_FTP_backup}",
		"{FTP_backup_zarafa_explain}","FTP_ENABLE-$t",intval($WordpressBackupParams["FTP_ENABLE"]),null,650)."</td>
						</tr>
			<tr>
				<td class=legend style='font-size:18px;vertical-align:middle'>{ftp_server}:</td>
				<td style='font-size:18px'>". Field_text("FTP_SERVER-$t",
							$WordpressBackupParams["FTP_SERVER"],"font-size:18px;padding:3px;width:210px")."&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px;vertical-align:middle'>{ftp_username}:</td>
				<td style='font-size:18px'>". Field_text("FTP_USER-$t",
							$WordpressBackupParams["FTP_USER"],"font-size:18px;padding:3px;width:190px")."&nbsp;</td>
							<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px'>{ftp_password}:</td>
				<td style='font-size:18px;vertical-align:middle'>". Field_password("FTP_PASS-$t",
							$WordpressBackupParams["FTP_PASS"],"font-size:18px;padding:3px;width:190px")."&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan=3 align='right'><hr>". button("{apply}","Save$t()","26px")."</td>
							</tr>
		</table>
</div>
<script>
	var x_Save$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		document.getElementById('div-$t').innerHTML='';
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('DEST',encodeURIComponent(document.getElementById('DEST-$t').value));
		XHR.appendData('FTP_ENABLE',document.getElementById('FTP_ENABLE-$t').value);
		XHR.appendData('FTP_USER',document.getElementById('FTP_USER-$t').value);
		XHR.appendData('FTP_PASS',encodeURIComponent(document.getElementById('FTP_PASS-$t').value));
		XHR.appendData('FTP_SERVER',document.getElementById('FTP_SERVER-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
	}
	
	function Check$t(){

	}

</script>
";
	
echo $tpl->_ENGINE_parse_body($html);
}

function wordpress_backup_save(){
	$sock=new sockets();
	$_POST["FTP_PASS"]=url_decode_special_tool($_POST["FTP_PASS"]);
	$_POST["DEST"]=url_decode_special_tool($_POST["DEST"]);
	print_r($_POST);
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "WordpressBackupParams");


}