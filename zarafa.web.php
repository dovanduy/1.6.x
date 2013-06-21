<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	
	
	
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["ZarafaApacheEnable"])){zarafa_settings_webmail_save();exit;}
	if(isset($_POST["ZarafaEnableServer"])){zarafa_settings_server_save();exit;}
	if(isset($_POST["ZarafaIMAPsEnable"])){zarafa_settings_imap_save();exit;}
	
	if(isset($_GET["ZarafaApachePort"])){SAVE();exit;}
	if(isset($_GET["DbAttachConverter-popup"])){DbAttachConverter_popup();exit;}
	if(isset($_GET["DbAttachConverterPerform"])){DbAttachConverter_Perform();exit;}
	if(isset($_GET["popup-webmail"])){zarafa_settings_webmail();exit;}
	if(isset($_GET["popup-server"])){zarafa_settings_server();exit;}
	if(isset($_GET["popup-imap"])){zarafa_settings_imap();exit;}
	
	
	js();
	
	
function js(){
$page=CurrentPageName();
$users=new usersMenus();
$tpl=new templates();
$start="APP_ZARAFA_WEB()";
if(isset($_GET["in-line"])){$start="APP_ZARAFA_WEB_INLINE()";}
$title=$tpl->_ENGINE_parse_body('{APP_ZARAFA_WEB}');

$html="

function APP_ZARAFA_WEB(){
	YahooWin3('550','$page?tabs=yes','$title');
	
	}
	
function APP_ZARAFA_WEB_INLINE(){
	$('#zarafa-inline-config').load('$page?tabs=yes&font-size=16');
}
	
var X_APP_ZARAFA_WEB_SAVE= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	document.getElementById('zrfa-logo').src='img/zarafa-web-128.png';
	}	
	

	
function APP_ZARAFA_WEB_SAVE(){
	var XHR = new XHRConnection();
	
	
	
	
	XHR.appendData('ZarafaStoreOutsidePath',document.getElementById('ZarafaStoreOutsidePath').value);
	XHR.appendData('ZarafaStoreCompressionLevel',document.getElementById('ZarafaStoreCompressionLevel').value);
	XHR.appendData('ZarafaServerSMTPIP',document.getElementById('ZarafaServerSMTPIP').value);
	XHR.appendData('ZarafaServerSMTPPORT',document.getElementById('ZarafaServerSMTPPORT').value);
	

	if(document.getElementById('ZarafaAllowToReinstall').checked){XHR.appendData('ZarafaAllowToReinstall',1);}else{XHR.appendData('ZarafaAllowToReinstall',0);}
	if(document.getElementById('ZarafaUserSafeMode').checked){XHR.appendData('ZarafaUserSafeMode',1);}else{XHR.appendData('ZarafaUserSafeMode',0);}	
	if(document.getElementById('ZarafaStoreOutside').checked){XHR.appendData('ZarafaStoreOutside',1);}else{XHR.appendData('ZarafaStoreOutside',0);}
	
			
	
	
	
	

	
	
	
	XHR.appendData('ou','$ou_decrypted');
	document.getElementById('zrfa-logo').src='img/wait_verybig.gif';
	XHR.sendAndLoad('$page', 'GET',X_APP_ZARAFA_WEB_SAVE);	
}
	
$start
QuickLinkShow('quicklinks-APP_ZARAFA');
";

echo $html;	
	
}


function tabs(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	
	$array["popup"]="{general_settings}";
	if($_GET["font-size"]>14){$_GET["font-size"]=14;}
	if(isset($_GET["font-size"])){$fontsize="font-size:{$_GET["font-size"]}px;";}
	$array["popup-tune"]="{service_tuning}";
	$array["popup-mysql"]="{mysql_tuning}";
	
	if($users->ZARAFA_INDEXER_INSTALLED){
		$array["popup-indexer"]="{APP_ZARAFA_INDEXER}";
	}
	$array["tools"]="{tools}";
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="status"){
			$html[]="<li><a href=\"zarafa.index.php?popup-status=yes\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="popup-indexer"){
			$html[]="<li><a href=\"zarafa.indexer.php\"><span>$ligne</span></a></li>\n";
			continue;
		}
		
		if($num=="popup-tune"){
			$html[]="<li><a href=\"zarafa.tuning.php\"><span>$ligne</span></a></li>\n";
			continue;
		}		
		
		if($num=="popup-mysql"){
			$html[]="<li><a href=\"zarafa.mysqlparams.php\"><span>$ligne</span></a></li>\n";
			continue;
		}

		if($num=="tools"){
			$html[]="<li><a href=\"zarafa.tools.php\"><span>$ligne</span></a></li>\n";
			continue;
		}	

		if($num=="popup-orphans"){
			$html[]="<li><a href=\"zarafa.orphans.php\"><span>$ligne</span></a></li>\n";
			continue;
		}			
		
		$html[]="<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_config_zarafa2 style='width:100%;height:100%;overflow:auto;$fontsize'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zarafa2').tabs();
			
			
			});
			QuickLinkShow('quicklinks-APP_ZARAFA');
		</script>";		
	
	
	echo $tpl->_ENGINE_parse_body($tab);
	
}

function SAVE(){
	$sock=new sockets();
	$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
	if(!is_numeric($_GET["ZarafaApachePort"])){$_GET["ZarafaApachePort"]=9010;}
	if(!is_numeric($ZarafaApachePort)){$ZarafaApachePort=9010;}
	
	if($ZarafaApachePort<>$_GET["ZarafaApachePort"]){
		$socket = @socket_create(AF_INET, SOCK_STREAM, 0);
		if(socket_connect($socket, "127.0.0.1", $_GET["ZarafaApachePort"])){
			@socket_close($socket);
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{error_port_already_use} {$_GET["ZarafaApachePort"]}");
		}else{
			$sock->SET_INFO("ZarafaApachePort",trim($_GET["ZarafaApachePort"]));	
		}
	}
	
	
	
	
	
	$sock->SET_INFO("ZarafaApacheSSL",trim($_GET["ZarafaApacheSSL"]));
	
	
	
	$sock->SET_INFO("ZarafaUserSafeMode",trim($_GET["ZarafaUserSafeMode"]));
	$sock->SET_INFO("ZarafaServerListenIP",trim($_GET["ZarafaServerListenIP"]));
	$sock->SET_INFO("ZarafaApacheServerName",trim($_GET["ZarafaApacheServerName"]));
	$sock->SET_INFO("ZarafaStoreOutside",trim($_GET["ZarafaStoreOutside"]));
	$sock->SET_INFO("ZarafaStoreOutsidePath",trim($_GET["ZarafaStoreOutsidePath"]));
	$sock->SET_INFO("ZarafaStoreCompressionLevel",trim($_GET["ZarafaStoreCompressionLevel"]));
	$sock->SET_INFO("ZarafaAspellEnabled",trim($_GET["ZarafaAspellEnabled"]));
	$sock->SET_INFO("ZarafaEnableServer",trim($_GET["ZarafaEnableServer"]));
	$sock->SET_INFO("ZarafaApacheEnable",trim($_GET["ZarafaApacheEnable"]));
	
	
	
	$sock->SET_INFO("ZarafaServerSMTPPORT",trim($_GET["ZarafaServerSMTPPORT"]));
	$sock->SET_INFO("ZarafaServerSMTPIP",trim($_GET["ZarafaServerSMTPIP"]));
	
	$sock->SET_INFO("ZarafaIMAPsPort",trim($_GET["ZarafaIMAPsPort"]));
	$sock->SET_INFO("ZarafaPop3sPort",trim($_GET["ZarafaPop3sPort"]));
	$sock->SET_INFO("ZarafaIMAPPort",trim($_GET["ZarafaIMAPPort"]));
	$sock->SET_INFO("ZarafaPop3Port",trim($_GET["ZarafaPop3Port"]));
	
	$sock->SET_INFO("Zarafa7IMAPDisable",trim($_GET["Zarafa7IMAPDisable"]));
	$sock->SET_INFO("Zarafa7Pop3Disable",trim($_GET["Zarafa7Pop3Disable"]));
	
	$sock->SET_INFO("ZarafaPop3Enable",trim($_GET["ZarafaPop3Enable"]));
	$sock->SET_INFO("ZarafaPop3sEnable",trim($_GET["ZarafaPop3sEnable"]));
	$sock->SET_INFO("ZarafaIMAPEnable",trim($_GET["ZarafaIMAPEnable"]));
	$sock->SET_INFO("ZarafaIMAPsEnable",trim($_GET["ZarafaIMAPsEnable"]));
	$sock->SET_INFO("ZarafaAllowToReinstall",trim($_GET["ZarafaAllowToReinstall"]));
	
	
	
	
	$sock->SET_INFO("ZarafaGatewayBind",$_GET["ZarafaGatewayBind"]);
	
	
	
		
	
	$sock->getFrameWork("zarafa.php?restart=yes");
	$sock->getFrameWork("services.php?restart-artica-status=yes");
	
}

function popup(){
	
	$sock=new sockets();
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$array["popup-webmail"]="{WEBMAIL}";
	$array["popup-server"]="{APP_ZARAFA_SERVER}";
	
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	if($ZarafaDedicateMySQLServer){
		$array["server2"]="{second_instance}";
	}
	$array["popup-imap"]="IMAP/POP3";
	
	
	

	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="server2"){
			$html[]="<li><a href=\"zarafa.second.php\"><span>$ligne</span></a></li>\n";
			continue;
		}
			
		
		$html[]="<li><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n";
			
		}	
	
	$tab="<div id=main_config_zarafa3 style='width:102%;$fontsize;margin:-10px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_zarafa3').tabs();
			
			
			});
			QuickLinkShow('quicklinks-APP_ZARAFA');
		</script>";		
	
	
	echo $tpl->_ENGINE_parse_body($tab);	
	
	
	
}


function popup_old(){
	
$users=new usersMenus();
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();

$zarafa_version=$sock->getFrameWork("zarafa.php?getversion=yes");
preg_match("#^([0-9]+)\.#", $zarafa_version,$re);
$major_version=$re[1];
if(!is_numeric($major_version)){$major_version=6;}

$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
$ZarafaUserSafeMode=$sock->GET_INFO("ZarafaUserSafeMode");
$ZarafaApacheServerName=$sock->GET_INFO("ZarafaApacheServerName");
if(trim($ZarafaApacheServerName)==null){$ZarafaApacheServerName=$users->hostname;}

$enable_ssl=$sock->GET_INFO("ZarafaApacheSSL");	
if($ZarafaApachePort==null){$ZarafaApachePort="9010";}




$ZarafaServerSMTPIP=$sock->GET_INFO("ZarafaServerSMTPIP");
$ZarafaServerSMTPPORT=$sock->GET_INFO("ZarafaServerSMTPPORT");
if($ZarafaServerSMTPIP==null){$ZarafaServerSMTPIP="127.0.0.1";}

if(!is_numeric($ZarafaServerSMTPPORT)){$ZarafaServerSMTPPORT=25;}
$ZarafaPop3Enable=$sock->GET_INFO("ZarafaPop3Enable");
$ZarafaPop3sEnable=$sock->GET_INFO("ZarafaPop3sEnable");
$ZarafaIMAPEnable=$sock->GET_INFO("ZarafaIMAPEnable");
$ZarafaIMAPsEnable=$sock->GET_INFO("ZarafaIMAPsEnable");
$ZarafaPop3Port=$sock->GET_INFO("ZarafaPop3Port");
$ZarafaIMAPPort=$sock->GET_INFO("ZarafaIMAPPort");
$ZarafaPop3sPort=$sock->GET_INFO("ZarafaPop3sPort");
$ZarafaIMAPsPort=$sock->GET_INFO("ZarafaIMAPsPort");
$ZarafaAllowToReinstall=$sock->GET_INFO("ZarafaAllowToReinstall");
$ZarafaSessionTime=$sock->GET_INFO("ZarafaSessionTime");

$Zarafa7IMAPDisable=$sock->GET_INFO("Zarafa7IMAPDisable");
$Zarafa7Pop3Disable=$sock->GET_INFO("Zarafa7Pop3Disable");
$ZarafaImportContactsInLDAPEnable=$sock->GET_INFO("ZarafaImportContactsInLDAPEnable");
$ZarafaEnablePlugins=$sock->GET_INFO("ZarafaEnablePlugins");
$ZarafaEnableServer=$sock->GET_INFO("ZarafaEnableServer");
$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
$ZarafaGatewayBind=$sock->GET_INFO("ZarafaGatewayBind");

if(!is_numeric($ZarafaPop3Enable)){$ZarafaPop3Enable=1;}
if(!is_numeric($ZarafaIMAPEnable)){$ZarafaIMAPEnable=1;}
if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}


if(!is_numeric($Zarafa7IMAPDisable)){$Zarafa7IMAPDisable=0;}
if(!is_numeric($Zarafa7Pop3Disable)){$Zarafa7Pop3Disable=0;}
if(!is_numeric($ZarafaImportContactsInLDAPEnable)){$ZarafaImportContactsInLDAPEnable=0;}

if(!is_numeric($ZarafaPop3Port)){$ZarafaPop3Port=110;}
if(!is_numeric($ZarafaPop3sPort)){$ZarafaPop3sPort=995;}
if(!is_numeric($ZarafaIMAPPort)){$ZarafaIMAPPort=143;}
if(!is_numeric($ZarafaIMAPsPort)){$ZarafaIMAPsPort=993;}
if(!is_numeric($ZarafaAllowToReinstall)){$ZarafaAllowToReinstall=1;}
if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}
if(!is_numeric($ZarafaEnableServer)){$ZarafaEnableServer=1;}
if(trim($ZarafaGatewayBind)==null){$ZarafaGatewayBind="0.0.0.0";}
$ZarafaSessionTime_field=$ZarafaSessionTime/60;




if($enable_ssl==null){$enable_ssl="0";}
if($ZarafaiCalEnable==null){$ZarafaiCalEnable=0;}
if(!is_numeric($ZarafaUserSafeMode)){$sock->SET_INFO("ZarafaUserSafeMode",0);$ZarafaUserSafeMode=0;}
$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");
$ZarafaStoreCompressionLevel=$sock->GET_INFO("ZarafaStoreCompressionLevel");

$ZarafaAspellEnabled=$sock->GET_INFO("ZarafaAspellEnabled");
if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
$ZarafaAspellInstalled=0;
$ZarafaAspellInstalled_text="({not_installed})";

if($users->ASPELL_INSTALLED){
	$ZarafaAspellInstalled=1;
	$ZarafaAspellInstalled_text="({installed})";
}


if(!is_numeric($ZarafaStoreOutside)){$ZarafaStoreOutside=0;}
if(!is_numeric($ZarafaStoreCompressionLevel)){$ZarafaStoreCompressionLevel=6;}
if($ZarafaStoreOutsidePath==null){$ZarafaStoreOutsidePath="/var/lib/zarafa";}

for($i=0;$i<10;$i++){
	$ZarafaStoreCompressionLevelAr[$i]=$i;
}

if($ZarafaUserSafeMode==1){
	$ZarafaUserSafeMode_warn="
	<hr>
	<center>
		<img src='img/error-128.png'>
		<H3 style='font-size:18px;color:black;margin-bottom:10px;margin-bottom:10px'>{ZARAFA_SAFEMODE_EXPLAIN}</H3>
	</center>
	
	";
	
}


$net=new networking();

$nets=$net->ALL_IPS_GET_ARRAY();
$nets["0.0.0.0"]="{all}";

$netsSMTP=$nets;
unset($netsSMTP["0.0.0.0"]);
$SMTPfield=Field_array_Hash($netsSMTP,"ZarafaServerSMTPIP",$ZarafaServerSMTPIP,"font-size:13px;padding:3px");

$convert_current_attachments_text=$tpl->javascript_parse_text("{convert_current_attachments}");

$fieldsServ[]="ZarafaStoreOutsidePath";
$fieldsServ[]="ZarafaStoreCompressionLevel";
$fieldsServ[]="ZarafaIMAPsEnable";
$fieldsServ[]="Zarafa7IMAPDisable";

$fieldsServ[]="ZarafaServerSMTPIP";
$fieldsServ[]="ZarafaPop3Enable";
$fieldsServ[]="Zarafa7Pop3Disable";
$fieldsServ[]="ZarafaPop3Port";
$fieldsServ[]="ZarafaPop3sPort";
$fieldsServ[]="ZarafaIMAPEnable";
$fieldsServ[]="ZarafaIMAPPort";
$fieldsServ[]="ZarafaStoreOutside";
$fieldsServ[]="ZarafaUserSafeMode";
$fieldsServ[]="ZarafaPop3sEnable";
$fieldsServ[]="ZarafaServerSMTPPORT";

while (list($num,$val)=each($fieldsServ)){	
	$fieldsServjs1[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=true;}";
	$fieldsServjs2[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=false;}";
	
}
$fieldsHTTP[]="ZarafaApacheServerName";
$fieldsHTTP[]="ZarafaApachePort";
$fieldsHTTP[]="ZarafaApacheSSL";
$fieldsHTTP[]="ZarafaSessionTime";
$fieldsHTTP[]="ZarafaWebNTLM";
$fieldsHTTP[]="ZarafaAspellEnabled";
$fieldsHTTP[]="ZarafaEnablePlugins";
$fieldsHTTP[]="ZarafaImportContactsInLDAPEnable";

while (list($num,$val)=each($fieldsHTTP)){	
	$fieldsHTTPjs1[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=true;}";
	$fieldsHTTPjs2[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=false;}";
	
}

if($ZarafaEnableServer==0){
	$ZarafaEnableServerWarning="<div style='font-size:14px;color:#890000;font-weight:bold;width:100%'><center>{zarafa_server_disabled}</center></div>";
}	
$ip=new networking();
$ips=$ip->ALL_IPS_GET_ARRAY();	
$ips["0.0.0.0"]="{all}";
$ZarafaGatewayBindAR=Field_array_Hash($ips,"ZarafaGatewayBind",$ZarafaGatewayBind,"style:font-size:14px;padding:3px");

$html="$ZarafaEnableServerWarning
<table style='width:100%'>
<tr>
	<td valign='top'><img id='zrfa-logo' src='img/zarafa-web-128.png'><center style='font-size:13px'>v.$zarafa_version</center>$ZarafaUserSafeMode_warn</td>
	<td valign='top'>
	

		
		<p>&nbsp;</p>
	


<table style='width:99%' class=form>

			<tr><td colspan=2 align='right'><hr>". button("{apply}","APP_ZARAFA_WEB_SAVE()","14px")."</td></tr>		
</table>
<table style='width:99%' class=form>
		<tr>
			<td colspan=2><H3 style='font-size:18px;color:black;margin-bottom:10px;margin-bottom:10px'>{attachments_path}</H3></td>
		</tr>						
			<tr>
				<td class=legend style='font-size:12px'>{ZarafaStoreOutside}:</td>
				<td>". Field_checkbox("ZarafaStoreOutside",1,$ZarafaStoreOutside,"CheckZarafaFields()")."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:12px'>{attachments_path}:</td>
				<td>". Field_text("ZarafaStoreOutsidePath",$ZarafaStoreOutsidePath,"width:220px;font-size:13px;padding:3px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:12px'>{attachments_compression_level}:</td>
				<td>". Field_array_Hash($ZarafaStoreCompressionLevelAr,"ZarafaStoreCompressionLevel",$ZarafaStoreCompressionLevel,"style:font-size:13px;padding:3px")."</td>
			</tr>	
			<tr>
				<td colspan=2 align='right'><a href=\"javascript:blur();\" OnClick=\"DbAttachConverter()\" 
				style='font-size:13px;text-decoration:underline'>{convert_current_attachments}</a></td>
			</tr>
			<tr><td colspan=2 align='right'><hr>". button("{apply}","APP_ZARAFA_WEB_SAVE()","14px")."</td></tr>																	
		</table>		

		<p>&nbsp;</p>
		

		
		
		
		
		<p>&nbsp;</p>
		
	<table style='width:99%' class=form>
		<tr>
			<td colspan=2><H3 style='font-size:18px;color:black;margin-bottom:10px;margin-bottom:10px'>{other_settings}</H3></td></tr>
			<tr>
				<td class=legend style='font-size:12px'>{user_safe_mode}:</td>
				<td width=1%>". Field_checkbox("ZarafaUserSafeMode",1,$ZarafaUserSafeMode)."</td>
				<td width=1%>". help_icon("{user_safe_mode_text}")."</td>
			</tr>
						<tr>
				<td class=legend style='font-size:14px'>{ZarafaAllowToReinstall}:</td>
				<td>". Field_checkbox("ZarafaAllowToReinstall",1,$ZarafaAllowToReinstall)."</td>
			</tr>
			</table>	
			
			
			
	</td>
	</tr>
	<tr>
				<td colspan=2 align='right'>
				<hr>
					". button("{apply}","APP_ZARAFA_WEB_SAVE()")."
				</td>
			</tr>	
</table>

<script>

	
	
	function ZarafaEnableServerCheck(){
		". @implode("\n", $fieldsServjs1)."
		if(document.getElementById('ZarafaEnableServer').checked){
		". @implode("\n", $fieldsServjs2)."
		CheckZarafaFields();
		}
	}
	

	


	function CheckZarafaFields(){
		var ZarafaAspellInstalled=$ZarafaAspellInstalled;
		var ZarafaStoreOutside=$ZarafaStoreOutside;	
		var major_version=$major_version;
		document.getElementById('ZarafaStoreOutsidePath').disabled=true;
		document.getElementById('ZarafaStoreCompressionLevel').disabled=true;
		document.getElementById('ZarafaAspellEnabled').disabled=true;
		
		document.getElementById('ZarafaPop3Port').disabled=true;
		document.getElementById('ZarafaIMAPPort').disabled=true;
		document.getElementById('ZarafaPop3sPort').disabled=true;
		document.getElementById('ZarafaIMAPsPort').disabled=true;
		
		document.getElementById('Zarafa7IMAPDisable').disabled=true;
		document.getElementById('Zarafa7Pop3Disable').disabled=true;
		if(major_version>6){
			document.getElementById('Zarafa7IMAPDisable').disabled=false;
			document.getElementById('Zarafa7Pop3Disable').disabled=false;
		}

		
		if(document.getElementById('ZarafaPop3Enable').checked){document.getElementById('ZarafaPop3Port').disabled=false;}
		if(document.getElementById('ZarafaPop3sEnable').checked){document.getElementById('ZarafaPop3sPort').disabled=false;}
		if(document.getElementById('ZarafaIMAPEnable').checked){document.getElementById('ZarafaIMAPPort').disabled=false;}
		if(document.getElementById('ZarafaIMAPsEnable').checked){document.getElementById('ZarafaIMAPsPort').disabled=false;}
		
		if(!document.getElementById('ZarafaPop3Enable').checked){
			if(major_version>6){
				document.getElementById('Zarafa7Pop3Disable').disabled=true;
			}else{
				document.getElementById('Zarafa7Pop3Disable').disabled=false;
			}
		}
		
		if(!document.getElementById('ZarafaIMAPEnable').checked){
			if(major_version>6){
				document.getElementById('Zarafa7IMAPDisable').disabled=true;
			}else{
				document.getElementById('Zarafa7IMAPDisable').disabled=false;
			}
		}		
		
		if(document.getElementById('ZarafaStoreOutside').checked){
			document.getElementById('ZarafaStoreOutsidePath').disabled=false;
			document.getElementById('ZarafaStoreCompressionLevel').disabled=false;
		}
		
		if(ZarafaAspellInstalled==1){
			document.getElementById('ZarafaAspellEnabled').disabled=false;
		}
		
		
	}
	CheckZarafaFields();
	ZarafaEnableServerCheck();
	ZarafaApacheDisableCheck();
</script>
";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function DbAttachConverter_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
	if($ZarafaStoreOutside<>1){echo "<script>YahooWinHide();</script>";return;}
	
	$html="
	<div class=explain id='zarafa_store_outside_div'>{zarafa_store_outside_text}</div>
	<center style='margin:10px'>". button("{run}","DbAttachConverterPerform()")."</center>
	
	<script>
var X_DbAttachConverterPerform= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	YahooWinHide();
	}	
	

	
function DbAttachConverterPerform(){
	var XHR = new XHRConnection();
	XHR.appendData('DbAttachConverterPerform','yes');
	XHR.sendAndLoad('$page', 'GET',X_DbAttachConverterPerform);		
	}
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function DbAttachConverter_Perform(){
	
	$q=new mysql();
	$sock=new sockets();
	$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");
	if($ZarafaStoreOutsidePath==null){$ZarafaStoreOutsidePath="/var/lib/zarafa";}
	$sqladm=urlencode(base64_encode($q->mysql_admin));
	$sqlpass=urlencode(base64_encode($q->mysql_password));
	$attachpath=urlencode($ZarafaStoreOutsidePath);
	$sock->getFrameWork("zarafa.php?DbAttachConverter=yes&sqladm=$sqladm&mysqlpass=$sqlpass&path=$attachpath");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{cyrreconstruct_wait}");
	
	
}

function zarafa_settings_server(){
$page=CurrentPageName();
$tpl=new templates();	
$sock=new sockets();
$users=new usersMenus();	
	


$zarafa_version=$sock->getFrameWork("zarafa.php?getversion=yes");
preg_match("#^([0-9]+)\.#", $zarafa_version,$re);
$major_version=$re[1];
if(!is_numeric($major_version)){$major_version=6;}

$ZarafaUserSafeMode=$sock->GET_INFO("ZarafaUserSafeMode");
$ZarafaServerListenIP=$sock->GET_INFO("ZarafaServerListenIP");
$ZarafaServerSMTPIP=$sock->GET_INFO("ZarafaServerSMTPIP");
$ZarafaServerSMTPPORT=$sock->GET_INFO("ZarafaServerSMTPPORT");
if($ZarafaServerSMTPIP==null){$ZarafaServerSMTPIP="127.0.0.1";}
if($ZarafaServerListenIP==null){$ZarafaServerListenIP="127.0.0.1";}
if(!is_numeric($ZarafaServerSMTPPORT)){$ZarafaServerSMTPPORT=25;}
$ZarafaPop3Enable=$sock->GET_INFO("ZarafaPop3Enable");
$ZarafaPop3sEnable=$sock->GET_INFO("ZarafaPop3sEnable");
$ZarafaIMAPEnable=$sock->GET_INFO("ZarafaIMAPEnable");
$ZarafaIMAPsEnable=$sock->GET_INFO("ZarafaIMAPsEnable");
$ZarafaPop3Port=$sock->GET_INFO("ZarafaPop3Port");
$ZarafaIMAPPort=$sock->GET_INFO("ZarafaIMAPPort");
$ZarafaPop3sPort=$sock->GET_INFO("ZarafaPop3sPort");
$ZarafaIMAPsPort=$sock->GET_INFO("ZarafaIMAPsPort");
$ZarafaAllowToReinstall=$sock->GET_INFO("ZarafaAllowToReinstall");
$ZarafaSessionTime=$sock->GET_INFO("ZarafaSessionTime");

$Zarafa7IMAPDisable=$sock->GET_INFO("Zarafa7IMAPDisable");
$Zarafa7Pop3Disable=$sock->GET_INFO("Zarafa7Pop3Disable");
$ZarafaImportContactsInLDAPEnable=$sock->GET_INFO("ZarafaImportContactsInLDAPEnable");
$ZarafaEnablePlugins=$sock->GET_INFO("ZarafaEnablePlugins");
$ZarafaEnableServer=$sock->GET_INFO("ZarafaEnableServer");
$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
$ZarafaGatewayBind=$sock->GET_INFO("ZarafaGatewayBind");
$ZarafaMAPISSLEnabled=$sock->GET_INFO('ZarafaMAPISSLEnabled');
$ZarafaEnableSecurityLogging=$sock->GET_INFO("ZarafaEnableSecurityLogging");
$ZarafaLogLevel=$sock->GET_INFO("ZarafaLogLevel");
if(!is_numeric($ZarafaPop3Enable)){$ZarafaPop3Enable=1;}
if(!is_numeric($ZarafaIMAPEnable)){$ZarafaIMAPEnable=1;}
if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
if(!is_numeric($ZarafaMAPISSLEnabled)){$ZarafaMAPISSLEnabled=0;}
if(!is_numeric($ZarafaEnableSecurityLogging)){$ZarafaEnableSecurityLogging=0;}


if(!is_numeric($ZarafaLogLevel)){$ZarafaLogLevel=2;}

if(!is_numeric($Zarafa7IMAPDisable)){$Zarafa7IMAPDisable=0;}
if(!is_numeric($Zarafa7Pop3Disable)){$Zarafa7Pop3Disable=0;}
if(!is_numeric($ZarafaImportContactsInLDAPEnable)){$ZarafaImportContactsInLDAPEnable=0;}

if(!is_numeric($ZarafaPop3Port)){$ZarafaPop3Port=110;}
if(!is_numeric($ZarafaPop3sPort)){$ZarafaPop3sPort=995;}
if(!is_numeric($ZarafaIMAPPort)){$ZarafaIMAPPort=143;}
if(!is_numeric($ZarafaIMAPsPort)){$ZarafaIMAPsPort=993;}
if(!is_numeric($ZarafaAllowToReinstall)){$ZarafaAllowToReinstall=1;}
if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}
if(!is_numeric($ZarafaEnableServer)){$ZarafaEnableServer=1;}
if(trim($ZarafaGatewayBind)==null){$ZarafaGatewayBind="0.0.0.0";}
$ZarafaSessionTime_field=$ZarafaSessionTime/60;




if($enable_ssl==null){$enable_ssl="0";}
if($ZarafaiCalEnable==null){$ZarafaiCalEnable=0;}
if(!is_numeric($ZarafaUserSafeMode)){$sock->SET_INFO("ZarafaUserSafeMode",0);$ZarafaUserSafeMode=0;}
$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");
$ZarafaStoreCompressionLevel=$sock->GET_INFO("ZarafaStoreCompressionLevel");

$ZarafaAspellEnabled=$sock->GET_INFO("ZarafaAspellEnabled");
if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
$ZarafaAspellInstalled=0;
$ZarafaAspellInstalled_text="({not_installed})";

if($users->ASPELL_INSTALLED){
	$ZarafaAspellInstalled=1;
	$ZarafaAspellInstalled_text="({installed})";
}


if(!is_numeric($ZarafaStoreOutside)){$ZarafaStoreOutside=0;}
if(!is_numeric($ZarafaStoreCompressionLevel)){$ZarafaStoreCompressionLevel=6;}
if($ZarafaStoreOutsidePath==null){$ZarafaStoreOutsidePath="/var/lib/zarafa";}

for($i=0;$i<10;$i++){
	$ZarafaStoreCompressionLevelAr[$i]=$i;
}

if($ZarafaUserSafeMode==1){
	$ZarafaUserSafeMode_warn="
	<hr>
	<center>
		<img src='img/error-128.png'>
		<H3 style='font-size:18px;color:black;margin-bottom:10px;margin-bottom:10px'>{ZARAFA_SAFEMODE_EXPLAIN}</H3>
	</center>
	
	";
	
}


$net=new networking();

$nets=$net->ALL_IPS_GET_ARRAY();
$nets["0.0.0.0"]="{all}";

for($i=1;$i<6;$i++){
	$ZarafaLogLevelHash[$i]=$i;
}

$netfield=Field_array_Hash($nets,"ZarafaServerListenIP",$ZarafaServerListenIP,"style:font-size:14px;padding:3px");
$SMTPfield=Field_array_Hash($nets,"ZarafaServerSMTPIP",$ZarafaServerSMTPIP,"style:font-size:14px;padding:3px");
$ZarafaLogLevel=Field_array_Hash($ZarafaLogLevelHash,"ZarafaLogLevel",$ZarafaLogLevel,"style:font-size:14px;padding:3px");
$convert_current_attachments_text=$tpl->javascript_parse_text("{convert_current_attachments}");

$fieldsServ[]="ZarafaStoreOutsidePath";
$fieldsServ[]="ZarafaStoreCompressionLevel";
$fieldsServ[]="ZarafaIMAPsEnable";
$fieldsServ[]="Zarafa7IMAPDisable";
$fieldsServ[]="ZarafaServerListenIP";
$fieldsServ[]="ZarafaServerSMTPIP";
$fieldsServ[]="ZarafaPop3Enable";
$fieldsServ[]="Zarafa7Pop3Disable";
$fieldsServ[]="ZarafaPop3Port";
$fieldsServ[]="ZarafaPop3sPort";
$fieldsServ[]="ZarafaIMAPEnable";
$fieldsServ[]="ZarafaIMAPPort";
$fieldsServ[]="ZarafaStoreOutside";
$fieldsServ[]="ZarafaUserSafeMode";
$fieldsServ[]="ZarafaPop3sEnable";
$fieldsServ[]="ZarafaServerSMTPPORT";

while (list($num,$val)=each($fieldsServ)){	
	$fieldsServjs1[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=true;}";
	$fieldsServjs2[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=false;}";
	
}


if($ZarafaEnableServer==0){
	$ZarafaEnableServerWarning="<div style='font-size:14px;color:#890000;font-weight:bold;width:100%'><center>{zarafa_server_disabled}</center></div>";
}	
$ip=new networking();
$ips=$ip->ALL_IPS_GET_ARRAY();	
$ips["0.0.0.0"]="{all}";
$ZarafaGatewayBindAR=Field_array_Hash($ips,"ZarafaGatewayBind",$ZarafaGatewayBind,"style:font-size:14px;padding:3px");
$t=time();
$html="$ZarafaEnableServerWarning
$ZarafaUserSafeMode_warn
<div style='font-size:14px' id='anim-$t'></div>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:14px'>{ZarafaEnableServer}:</td>
			<td><strong style='font-size:14px'>". Field_checkbox("ZarafaEnableServer", 1,$ZarafaEnableServer,"")."</td>
			<td>&nbsp;</td>
		</tr>
			<tr>
				<td class=legend style='font-size:14px'>{user_safe_mode}:</td>
				<td width=1%>". Field_checkbox("ZarafaUserSafeMode",1,$ZarafaUserSafeMode)."</td>
				<td width=1%>". help_icon("{user_safe_mode_text}")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{ZarafaAllowToReinstall}:</td>
				<td>". Field_checkbox("ZarafaAllowToReinstall",1,$ZarafaAllowToReinstall)."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{log_level}:</td>
				<td>$ZarafaLogLevel</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{security_logging}:</td>
				<td>". Field_checkbox("ZarafaEnableSecurityLogging",1,$ZarafaEnableSecurityLogging)."</td>
			</tr>			
			
			
			
		
	</tr>
	<tr><td colspan=3 align='right'><hr></td></tr>
		<tr>
			<td colspan=3><H3 style='font-size:18px;color:black;margin-bottom:10px;margin-bottom:10px'>SMTP</H3></td>
		</tr>					
			
			<tr>
				<td class=legend style='font-size:14px'>{smtp_server}:</td>
				<td><strong style='font-size:14px'>$SMTPfield</strong></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{smtp_server_port}:</td>
				<td>". Field_text("ZarafaServerSMTPPORT",$ZarafaServerSMTPPORT,"width:90px;font-size:14px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr><td colspan=3 align='right'><hr></td></tr>	
			<tr><td colspan=3><H3 style='font-size:18px;color:black;margin-bottom:10px;'>MAPI</H3></td></tr>					
	<tr>
		<td class=legend style='font-size:14px'>{mapi_ip}:</td>
		<td>$netfield</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{mapi_port}:</td>
		<td><strong style='font-size:14px'>236</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{enable_ssl} (237):</td>
		<td><strong style='font-size:14px'>". Field_checkbox("ZarafaMAPISSLEnabled", 1,$ZarafaMAPISSLEnabled,"")."</strong></td>
		<td>&nbsp;</td>
	</tr>
	<tr><td colspan=3 align='right'><hr></td></tr>
		<tr>
			<td colspan=3><H3 style='font-size:18px;color:black;margin-bottom:10px'>{attachments_path}</H3></td>
		</tr>						
			<tr>
				<td class=legend style='font-size:14px'>{ZarafaStoreOutside}:</td>
				<td>". Field_checkbox("ZarafaStoreOutside",1,$ZarafaStoreOutside,"CheckZarafaATTCH()")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{attachments_path}:</td>
				<td>". Field_text("ZarafaStoreOutsidePath",$ZarafaStoreOutsidePath,"width:220px;font-size:14px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{attachments_compression_level}:</td>
				<td>". Field_array_Hash($ZarafaStoreCompressionLevelAr,"ZarafaStoreCompressionLevel",$ZarafaStoreCompressionLevel,"style:font-size:13px;padding:3px")."</td>
				<td>&nbsp;</td>
			</tr>	
			<tr>
				<td colspan=3 align='right'><a href=\"javascript:blur();\" OnClick=\"DbAttachConverter()\" 
				style='font-size:14px;text-decoration:underline'>{convert_current_attachments}</a></td>
			</tr>	
	
	
	<tr><td colspan=3 align='right'><hr>". button("{apply}","APP_ZARAFA_WEB_SAVE$t()","16px")."</td></tr>		
	</table>
	<script>
		function CheckZarafaATTCH(){
			document.getElementById('ZarafaStoreOutsidePath').disabled=true;
			document.getElementById('ZarafaStoreCompressionLevel').disabled=true;
			if(document.getElementById('ZarafaStoreOutside').checked){
				document.getElementById('ZarafaStoreOutsidePath').disabled=false;
				document.getElementById('ZarafaStoreCompressionLevel').disabled=false;			
			
			}
		
		}
	
	
		var X_APP_ZARAFA_WEB_SAVE$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_zarafa3');
			}	
			
		
			function APP_ZARAFA_WEB_SAVE$t(){
				var XHR = new XHRConnection();
				if(document.getElementById('ZarafaEnableServer').checked){XHR.appendData('ZarafaEnableServer',1);}else{XHR.appendData('ZarafaEnableServer',0);}
				if(document.getElementById('ZarafaStoreOutside').checked){XHR.appendData('ZarafaStoreOutside',1);}else{XHR.appendData('ZarafaStoreOutside',0);}
				if(document.getElementById('ZarafaUserSafeMode').checked){XHR.appendData('ZarafaUserSafeMode',1);}else{XHR.appendData('ZarafaUserSafeMode',0);}
				if(document.getElementById('ZarafaAllowToReinstall').checked){XHR.appendData('ZarafaAllowToReinstall',1);}else{XHR.appendData('ZarafaAllowToReinstall',0);}
				if(document.getElementById('ZarafaEnableSecurityLogging').checked){XHR.appendData('ZarafaEnableSecurityLogging',1);}else{XHR.appendData('ZarafaEnableSecurityLogging',0);}
				
				
				
				
				XHR.appendData('ZarafaServerListenIP',document.getElementById('ZarafaServerListenIP').value);
				XHR.appendData('ZarafaServerSMTPIP',document.getElementById('ZarafaServerSMTPIP').value);
				XHR.appendData('ZarafaServerSMTPPORT',document.getElementById('ZarafaServerSMTPPORT').value);
				XHR.appendData('ZarafaStoreOutsidePath',document.getElementById('ZarafaStoreOutsidePath').value);
				XHR.appendData('ZarafaStoreCompressionLevel',document.getElementById('ZarafaStoreCompressionLevel').value);
				XHR.appendData('ZarafaLogLevel',document.getElementById('ZarafaLogLevel').value);
				
				
				
				
				if(document.getElementById('ZarafaMAPISSLEnabled').checked){XHR.appendData('ZarafaMAPISSLEnabled',1);}else{XHR.appendData('ZarafaMAPISSLEnabled',0);}
				AnimateDiv('anim-$t');
				XHR.sendAndLoad('$page', 'POST',X_APP_ZARAFA_WEB_SAVE$t);	
			}
			
			function DbAttachConverter(){
				YahooWin('550','$page?DbAttachConverter-popup=yes','$convert_current_attachments_text');
			
			}			
			
			ZarafaApacheDisableCheck();
			CheckZarafaATTCH();
		</script>	
	";

echo $tpl->_ENGINE_parse_body($html);
	
}

function zarafa_settings_server_save(){
	$sock=new sockets();
	
	$ZarafaServerListenIP=$sock->GET_INFO("ZarafaServerListenIP");
	
	$sock->SET_INFO("ZarafaEnableServer", $_POST["ZarafaEnableServer"]);
	$sock->SET_INFO("ZarafaServerListenIP", $_POST["ZarafaServerListenIP"]);
	$sock->SET_INFO("ZarafaMAPISSLEnabled", $_POST["ZarafaMAPISSLEnabled"]);
	$sock->SET_INFO("ZarafaServerSMTPIP", $_POST["ZarafaServerSMTPIP"]);
	$sock->SET_INFO("ZarafaServerSMTPPORT", $_POST["ZarafaServerSMTPPORT"]);
	$sock->SET_INFO("ZarafaUserSafeMode", $_POST["ZarafaUserSafeMode"]);
	$sock->SET_INFO("ZarafaAllowToReinstall", $_POST["ZarafaAllowToReinstall"]);
	$sock->SET_INFO("ZarafaLogLevel", $_POST["ZarafaLogLevel"]);
	$sock->SET_INFO("ZarafaEnableSecurityLogging", $_POST["ZarafaEnableSecurityLogging"]);
	
	
	$sock->SET_INFO("ZarafaStoreOutside", $_POST["ZarafaStoreOutside"]);
	$sock->SET_INFO("ZarafaStoreOutsidePath", $_POST["ZarafaStoreOutsidePath"]);
	$sock->SET_INFO("ZarafaStoreCompressionLevel", $_POST["ZarafaStoreCompressionLevel"]);
	
	if($ZarafaServerListenIP<>$_POST["ZarafaServerListenIP"]){
		$sock->getFrameWork("cmd.php?zarafa-restart-server=yes");
		return;
	}
	
	$sock->getFrameWork("zarafa.php?restart-server=yes");
}


function zarafa_settings_imap(){
	
$users=new usersMenus();
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();

$zarafa_version=$sock->getFrameWork("zarafa.php?getversion=yes");
preg_match("#^([0-9]+)\.#", $zarafa_version,$re);
$major_version=$re[1];
if(!is_numeric($major_version)){$major_version=6;}

$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
$ZarafaUserSafeMode=$sock->GET_INFO("ZarafaUserSafeMode");
$enable_ssl=$sock->GET_INFO("ZarafaApacheSSL");	
if($ZarafaApachePort==null){$ZarafaApachePort="9010";}
$ZarafaServerSMTPIP=$sock->GET_INFO("ZarafaServerSMTPIP");
$ZarafaServerSMTPPORT=$sock->GET_INFO("ZarafaServerSMTPPORT");
if($ZarafaServerSMTPIP==null){$ZarafaServerSMTPIP="127.0.0.1";}
if(!is_numeric($ZarafaServerSMTPPORT)){$ZarafaServerSMTPPORT=25;}
$ZarafaPop3Enable=$sock->GET_INFO("ZarafaPop3Enable");
$ZarafaPop3sEnable=$sock->GET_INFO("ZarafaPop3sEnable");
$ZarafaIMAPEnable=$sock->GET_INFO("ZarafaIMAPEnable");
$ZarafaIMAPsEnable=$sock->GET_INFO("ZarafaIMAPsEnable");
$ZarafaPop3Port=$sock->GET_INFO("ZarafaPop3Port");
$ZarafaIMAPPort=$sock->GET_INFO("ZarafaIMAPPort");
$ZarafaPop3sPort=$sock->GET_INFO("ZarafaPop3sPort");
$ZarafaIMAPsPort=$sock->GET_INFO("ZarafaIMAPsPort");
$ZarafaAllowToReinstall=$sock->GET_INFO("ZarafaAllowToReinstall");
$Zarafa7IMAPDisable=$sock->GET_INFO("Zarafa7IMAPDisable");
$Zarafa7Pop3Disable=$sock->GET_INFO("Zarafa7Pop3Disable");
$ZarafaImportContactsInLDAPEnable=$sock->GET_INFO("ZarafaImportContactsInLDAPEnable");
$ZarafaEnablePlugins=$sock->GET_INFO("ZarafaEnablePlugins");
$ZarafaEnableServer=$sock->GET_INFO("ZarafaEnableServer");
$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
$ZarafaGatewayBind=$sock->GET_INFO("ZarafaGatewayBind");

if(!is_numeric($ZarafaPop3Enable)){$ZarafaPop3Enable=1;}
if(!is_numeric($ZarafaIMAPEnable)){$ZarafaIMAPEnable=1;}
if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}

if(!is_numeric($Zarafa7IMAPDisable)){$Zarafa7IMAPDisable=0;}
if(!is_numeric($Zarafa7Pop3Disable)){$Zarafa7Pop3Disable=0;}
if(!is_numeric($ZarafaImportContactsInLDAPEnable)){$ZarafaImportContactsInLDAPEnable=0;}

if(!is_numeric($ZarafaPop3Port)){$ZarafaPop3Port=110;}
if(!is_numeric($ZarafaPop3sPort)){$ZarafaPop3sPort=995;}
if(!is_numeric($ZarafaIMAPPort)){$ZarafaIMAPPort=143;}
if(!is_numeric($ZarafaIMAPsPort)){$ZarafaIMAPsPort=993;}
if(!is_numeric($ZarafaAllowToReinstall)){$ZarafaAllowToReinstall=1;}
if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}
if(!is_numeric($ZarafaEnableServer)){$ZarafaEnableServer=1;}
if(trim($ZarafaGatewayBind)==null){$ZarafaGatewayBind="0.0.0.0";}

if($ZarafaiCalEnable==null){$ZarafaiCalEnable=0;}
if(!is_numeric($ZarafaUserSafeMode)){$sock->SET_INFO("ZarafaUserSafeMode",0);$ZarafaUserSafeMode=0;}
$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");
$ZarafaStoreCompressionLevel=$sock->GET_INFO("ZarafaStoreCompressionLevel");

$ZarafaAspellEnabled=$sock->GET_INFO("ZarafaAspellEnabled");
if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
$ZarafaAspellInstalled=0;
$ZarafaAspellInstalled_text="({not_installed})";

if($users->ASPELL_INSTALLED){
	$ZarafaAspellInstalled=1;
	$ZarafaAspellInstalled_text="({installed})";
}


if(!is_numeric($ZarafaStoreOutside)){$ZarafaStoreOutside=0;}
if(!is_numeric($ZarafaStoreCompressionLevel)){$ZarafaStoreCompressionLevel=6;}
if($ZarafaStoreOutsidePath==null){$ZarafaStoreOutsidePath="/var/lib/zarafa";}

for($i=0;$i<10;$i++){
	$ZarafaStoreCompressionLevelAr[$i]=$i;
}

if($ZarafaUserSafeMode==1){
	$ZarafaUserSafeMode_warn="
	<hr>
	<center>
		<img src='img/error-128.png'>
		<H3 style='font-size:18px;color:black;margin-bottom:10px;margin-bottom:10px'>{ZARAFA_SAFEMODE_EXPLAIN}</H3>
	</center>
	
	";
	
}


$net=new networking();

$nets=$net->ALL_IPS_GET_ARRAY();
$nets["0.0.0.0"]="{all}";
$netsSMTP=$nets;
unset($netsSMTP["0.0.0.0"]);
$SMTPfield=Field_array_Hash($netsSMTP,"ZarafaServerSMTPIP",$ZarafaServerSMTPIP,"font-size:13px;padding:3px");
$convert_current_attachments_text=$tpl->javascript_parse_text("{convert_current_attachments}");

$fieldsServ[]="ZarafaStoreOutsidePath";
$fieldsServ[]="ZarafaStoreCompressionLevel";
$fieldsServ[]="ZarafaIMAPsEnable";
$fieldsServ[]="Zarafa7IMAPDisable";

$fieldsServ[]="ZarafaServerSMTPIP";
$fieldsServ[]="ZarafaPop3Enable";
$fieldsServ[]="Zarafa7Pop3Disable";
$fieldsServ[]="ZarafaPop3Port";
$fieldsServ[]="ZarafaPop3sPort";
$fieldsServ[]="ZarafaIMAPEnable";
$fieldsServ[]="ZarafaIMAPPort";
$fieldsServ[]="ZarafaStoreOutside";
$fieldsServ[]="ZarafaUserSafeMode";
$fieldsServ[]="ZarafaPop3sEnable";
$fieldsServ[]="ZarafaServerSMTPPORT";


if($ZarafaEnableServer==0){
	$ZarafaEnableServerWarning="<div style='font-size:14px;color:#890000;font-weight:bold;width:100%'><center>{zarafa_server_disabled}</center></div>";
}	
$ip=new networking();
$ips=$ip->ALL_IPS_GET_ARRAY();	
$ips["0.0.0.0"]="{all}";
$ZarafaGatewayBindAR=Field_array_Hash($ips,"ZarafaGatewayBind",$ZarafaGatewayBind,"style:font-size:14px;padding:3px");
$t=time();	
	
$html="
<div id='anim-$t'></div>
<table style='width:99%' class=form>
			<tr>
				<td class=legend style='font-size:14px'>{listen_ip} (POP/IMAP):</td>
				<td><strong style='font-size:13px'>$ZarafaGatewayBindAR</strong></td>
			</tr>			
			<tr>
				<td class=legend style='font-size:14px'>{disable_pop3}:</td>
				<td><strong style='font-size:13px'>". Field_checkbox("Zarafa7Pop3Disable", 1,$Zarafa7Pop3Disable,"CheckZarafaFieldsPOP3()")."</td>
			</tr>						
			<tr>
				<td class=legend style='font-size:14px'>{pop3_port}:</td>
				<td>". Field_text("ZarafaPop3Port",$ZarafaPop3Port,"width:90px;font-size:13px;padding:3px")."</td>
			</tr>		
			<tr>
				<td class=legend style='font-size:14px'>{enable_pop3s}:</td>
				<td><strong style='font-size:13px'>". Field_checkbox("ZarafaPop3sEnable", 1,$ZarafaPop3sEnable,"CheckZarafaFieldsPOP3S()")."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{pop3s_port}:</td>
				<td>". Field_text("ZarafaPop3sPort",$ZarafaPop3sPort,"width:90px;font-size:13px;padding:3px")."</td>
			</tr>	
			
			<tr><td colspan=2 align='right'><hr></td></tr>

			<tr>
				<td class=legend style='font-size:14px'>{disable_imap}:</td>
				<td><strong style='font-size:13px'>". Field_checkbox("Zarafa7IMAPDisable", 1,$Zarafa7IMAPDisable,"CheckZarafaFieldsIMAP()")."</td>
			</tr>				
			<tr>
				<td class=legend style='font-size:14px'>{imap_port}:</td>
				<td>". Field_text("ZarafaIMAPPort",$ZarafaIMAPPort,"width:90px;font-size:13px;padding:3px")."</td>
			</tr>

			<tr>
				<td class=legend style='font-size:14px'>{enable_imaps}:</td>
				<td><strong style='font-size:13px'>". Field_checkbox("ZarafaIMAPsEnable", 1,$ZarafaIMAPsEnable,"CheckZarafaFieldsIMAPS()")."</td>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px'>{imaps_port}:</td>
				<td>". Field_text("ZarafaIMAPsPort",$ZarafaIMAPsPort,"width:90px;font-size:13px;padding:3px")."</td>
			</tr>	
			<tr><td colspan=2 align='right'><hr>". button("{apply}","APP_ZARAFA_WEB_SAVE$t()","16px")."</td></tr>					
		</table>
		
		<script>
		
		var X_APP_ZARAFA_WEB_SAVE$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_zarafa3');
			}
			
			
		function CheckZarafaFieldsPOP3(){
			var Zarafa7Pop3Disable=0;
			if(document.getElementById('Zarafa7Pop3Disable').checked){Zarafa7Pop3Disable=1;}
			document.getElementById('ZarafaPop3Port').disabled=true;
			document.getElementById('ZarafaPop3sPort').disabled=true;
			document.getElementById('ZarafaPop3sEnable').disabled=true;
			if(Zarafa7Pop3Disable==0){
				document.getElementById('ZarafaPop3Port').disabled=false;
				document.getElementById('ZarafaPop3sPort').disabled=false;
				document.getElementById('ZarafaPop3sEnable').disabled=false;			
			}
		}
		
		function CheckZarafaFieldsPOP3S(){
			if(document.getElementById('Zarafa7Pop3Disable').checked){return;}
			document.getElementById('ZarafaPop3sPort').disabled=true;
			if(document.getElementById('ZarafaPop3sEnable').checked){
				document.getElementById('ZarafaPop3sPort').false=true;
			}
		}
		
		function CheckZarafaFieldsIMAP(){
			var Zarafa7IMAPDisable=0;
			if(document.getElementById('Zarafa7IMAPDisable').checked){Zarafa7IMAPDisable=1;}
			document.getElementById('ZarafaIMAPPort').disabled=true;
			document.getElementById('ZarafaIMAPsEnable').disabled=true;
			document.getElementById('ZarafaIMAPsPort').disabled=true;
			if(Zarafa7IMAPDisable==0){
				document.getElementById('ZarafaIMAPPort').disabled=false;
				document.getElementById('ZarafaIMAPsEnable').disabled=false;
				document.getElementById('ZarafaIMAPsPort').disabled=false;			
			}
		}	

		function CheckZarafaFieldsIMAPS(){
			if(document.getElementById('Zarafa7IMAPDisable').checked){return;}
			document.getElementById('ZarafaIMAPsPort').disabled=true;
			if(document.getElementById('ZarafaIMAPsEnable').checked){
				document.getElementById('ZarafaIMAPsPort').disabled=false;
			}
		}		

		
		
		
	function APP_ZARAFA_WEB_SAVE$t(){
			var XHR = new XHRConnection();	
			XHR.appendData('ZarafaPop3Port',document.getElementById('ZarafaPop3Port').value);
			XHR.appendData('ZarafaIMAPPort',document.getElementById('ZarafaIMAPPort').value);
			XHR.appendData('ZarafaPop3sPort',document.getElementById('ZarafaPop3sPort').value);
			XHR.appendData('ZarafaIMAPsPort',document.getElementById('ZarafaIMAPsPort').value);
			XHR.appendData('ZarafaGatewayBind',document.getElementById('ZarafaGatewayBind').value);
			if(document.getElementById('ZarafaPop3sEnable').checked){XHR.appendData('ZarafaPop3sEnable',1);}else{XHR.appendData('ZarafaPop3sEnable',0);}
			if(document.getElementById('ZarafaIMAPsEnable').checked){XHR.appendData('ZarafaIMAPsEnable',1);}else{XHR.appendData('ZarafaIMAPsEnable',0);}
			
			
			if(document.getElementById('Zarafa7Pop3Disable').checked){
				XHR.appendData('ZarafaPop3Enable',0)
				XHR.appendData('Zarafa7Pop3Disable',1);
			}else{
				XHR.appendData('Zarafa7Pop3Disable',0);
				XHR.appendData('ZarafaPop3Enable',1)
			}
			if(document.getElementById('Zarafa7IMAPDisable').checked){
				XHR.appendData('Zarafa7IMAPDisable',1);
				XHR.appendData('ZarafaIMAPEnable',0);
			}else{
				XHR.appendData('Zarafa7IMAPDisable',0);
				XHR.appendData('ZarafaIMAPEnable',1);
			}
			
		
			AnimateDiv('anim-$t');
			XHR.sendAndLoad('$page', 'POST',X_APP_ZARAFA_WEB_SAVE$t);
			}
			
		CheckZarafaFieldsPOP3();
		CheckZarafaFieldsPOP3S();
		CheckZarafaFieldsIMAPS();
			
		</script>
		";	
echo $tpl->_ENGINE_parse_body($html);
	
}

function zarafa_settings_imap_save(){
	$sock=new sockets();
	$sock->SET_INFO("ZarafaPop3Enable", $_POST["ZarafaPop3Enable"]);
	$sock->SET_INFO("Zarafa7Pop3Disable", $_POST["Zarafa7Pop3Disable"]);
	$sock->SET_INFO("ZarafaGatewayBind", $_POST["ZarafaGatewayBind"]);
	
	$sock->SET_INFO("ZarafaPop3Port", $_POST["ZarafaPop3Port"]);
	$sock->SET_INFO("ZarafaPop3sPort", $_POST["ZarafaPop3sPort"]);
	$sock->SET_INFO("ZarafaPop3sEnable", $_POST["ZarafaPop3sEnable"]);

	$sock->SET_INFO("Zarafa7IMAPDisable", $_POST["Zarafa7IMAPDisable"]);
	$sock->SET_INFO("ZarafaIMAPEnable", $_POST["ZarafaIMAPEnable"]);	
	
	$sock->SET_INFO("ZarafaIMAPsPort", $_POST["ZarafaIMAPsPort"]);
	$sock->SET_INFO("ZarafaIMAPPort", $_POST["ZarafaIMAPPort"]);
	$sock->SET_INFO("ZarafaIMAPsEnable", $_POST["ZarafaIMAPsEnable"]);
	
	$sock->getFrameWork("zarafa.php?restart-gateway=yes");

}

function zarafa_settings_webmail(){
$page=CurrentPageName();
$tpl=new templates();	
$sock=new sockets();
$users=new usersMenus();
$zarafa_version=$sock->getFrameWork("zarafa.php?getversion=yes");
preg_match("#^([0-9]+)\.#", $zarafa_version,$re);
$major_version=$re[1];
if(!is_numeric($major_version)){$major_version=6;}

$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
$ZarafaUserSafeMode=$sock->GET_INFO("ZarafaUserSafeMode");
$ZarafaApacheServerName=$sock->GET_INFO("ZarafaApacheServerName");
if(trim($ZarafaApacheServerName)==null){$ZarafaApacheServerName=$users->hostname;}

$enable_ssl=$sock->GET_INFO("ZarafaApacheSSL");	
if($ZarafaApachePort==null){$ZarafaApachePort="9010";}
$ZarafaApacheEnable=$sock->GET_INFO("ZarafaApacheEnable");
$ZarafaImportContactsInLDAPEnable=$sock->GET_INFO("ZarafaImportContactsInLDAPEnable");
$ZarafaWebNTLM=$sock->GET_INFO("ZarafaWebNTLM");



$ZarafaEnablePlugins=$sock->GET_INFO("ZarafaEnablePlugins");

if(!is_numeric($ZarafaApacheEnable)){$ZarafaApacheEnable=1;}
if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
if(!is_numeric($ZarafaImportContactsInLDAPEnable)){$ZarafaImportContactsInLDAPEnable=0;}
if(!is_numeric($ZarafaSessionTime)){$ZarafaSessionTime=1440;}
$ZarafaSessionTime_field=$ZarafaSessionTime/60;




if($enable_ssl==null){$enable_ssl="0";}
if($ZarafaiCalEnable==null){$ZarafaiCalEnable=0;}
if(!is_numeric($ZarafaUserSafeMode)){$sock->SET_INFO("ZarafaUserSafeMode",0);$ZarafaUserSafeMode=0;}
$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");
$ZarafaStoreCompressionLevel=$sock->GET_INFO("ZarafaStoreCompressionLevel");

$ZarafaAspellEnabled=$sock->GET_INFO("ZarafaAspellEnabled");
if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
$ZarafaAspellInstalled=0;
$ZarafaAspellInstalled_text="({not_installed})";

if($users->ASPELL_INSTALLED){
	$ZarafaAspellInstalled=1;
	$ZarafaAspellInstalled_text="({installed})";
}

$fieldsHTTP[]="ZarafaApacheServerName";
$fieldsHTTP[]="ZarafaApachePort";
$fieldsHTTP[]="ZarafaApacheSSL";
$fieldsHTTP[]="ZarafaSessionTime";
$fieldsHTTP[]="ZarafaWebNTLM";
$fieldsHTTP[]="ZarafaAspellEnabled";
$fieldsHTTP[]="ZarafaEnablePlugins";
$fieldsHTTP[]="ZarafaImportContactsInLDAPEnable";

while (list($num,$val)=each($fieldsHTTP)){	
	$fieldsHTTPjs1[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=true;}";
	$fieldsHTTPjs2[]="if(document.getElementById('$val')){document.getElementById('$val').disabled=false;}";
	
}

if(!$users->APACHE_INSTALLED){
	$html="
	<table style='width:100%'>
	<tr>
		<td valign='top'><img id='zrfa-logo' src='img/zarfa-web-error-128.png'></td>
		<td valign='top'>	
			<table style='width:100%'>
			<tr>
				<td colspan=2>
				<p style='font-size:14px;color:#C61010'>{ZARAFA_ERROR_NO_APACHE}</p>
				
				</td>
			</tr>
			</table>
		</td>
		</tr>
		</table>";
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html);
		return;
}

	
$t=time();	
$html="
<div class=explain style='font-size:14px' id='anim-$t'>{zarafa_settings_webmail}</div>
<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{enable_http_service}:</td>
		<td>". Field_checkbox("ZarafaApacheEnable",1,$ZarafaApacheEnable,'ZarafaApacheDisableCheck()')."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{servername}:</td>
		<td>". Field_text("ZarafaApacheServerName",$ZarafaApacheServerName,"font-size:13px;padding:3px;width:210px")."</td>
	</tr>		
		<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td>". Field_text("ZarafaApachePort",$ZarafaApachePort,"font-size:13px;padding:3px;width:60px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{enable_ssl}:</td>
		<td>". Field_checkbox("ZarafaApacheSSL",1,$enable_ssl)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{SessionTime}:</td>
		<td style='font-size:13px;padding:3px;'>". Field_text("ZarafaSessionTime",$ZarafaSessionTime_field,"font-size:13px;padding:3px;width:60px")."&nbsp;{minutes}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:14px'>{ZarafaWebNTLM}:</td>
		<td>". Field_checkbox("ZarafaWebNTLM",1,$ZarafaWebNTLM)."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:14px'>{spell_checker}&nbsp;$ZarafaAspellInstalled_text&nbsp;:</td>
		<td>". Field_checkbox("ZarafaAspellEnabled",1,$ZarafaAspellEnabled)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{ZarafaEnablePlugins}:</td>
		<td>". Field_checkbox("ZarafaEnablePlugins",1,$ZarafaEnablePlugins)."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:14px'>{ZarafaImportContactsInLDAPEnable}&nbsp;:</td>
		<td>". Field_checkbox("ZarafaImportContactsInLDAPEnable",1,$ZarafaImportContactsInLDAPEnable)."</td>
	</tr>			
		<tr><td colspan=2 align='right'><hr>". button("{apply}","APP_ZARAFA_WEB_SAVE$t()","16px")."</td></tr>							
	</table>
	<script>
		var X_APP_ZARAFA_WEB_SAVE$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('main_config_zarafa3');
			}	
	function ZarafaApacheDisableCheck(){
		var ZarafaAspellInstalled=$ZarafaAspellInstalled;
		". @implode("\n", $fieldsHTTPjs1)."
		if(document.getElementById('ZarafaApacheEnable').checked){
		". @implode("\n", $fieldsHTTPjs2)."
		
		if(ZarafaAspellInstalled==0){
			document.getElementById('ZarafaAspellEnabled').disabled=true;
		}
		
		}
	}			
		
			function APP_ZARAFA_WEB_SAVE$t(){
				var XHR = new XHRConnection();
				if(document.getElementById('ZarafaApacheEnable').checked){XHR.appendData('ZarafaApacheEnable',1);}else{XHR.appendData('ZarafaApacheEnable',0);}
				XHR.appendData('ZarafaApacheServerName',document.getElementById('ZarafaApacheServerName').value);
				XHR.appendData('ZarafaApachePort',document.getElementById('ZarafaApachePort').value);
				XHR.appendData('ZarafaSessionTime',document.getElementById('ZarafaSessionTime').value);
				if(document.getElementById('ZarafaApacheSSL').checked){XHR.appendData('ZarafaApacheSSL',1);}else{XHR.appendData('ZarafaApacheSSL',0);}
				if(document.getElementById('ZarafaWebNTLM').checked){XHR.appendData('ZarafaWebNTLM',1);}else{XHR.appendData('ZarafaWebNTLM',0);}
				if(document.getElementById('ZarafaAspellEnabled').checked){XHR.appendData('ZarafaAspellEnabled',1);}else{XHR.appendData('ZarafaAspellEnabled',0);}
				if(document.getElementById('ZarafaEnablePlugins').checked){XHR.appendData('ZarafaEnablePlugins',1);}else{XHR.appendData('ZarafaEnablePlugins',0);}
				if(document.getElementById('ZarafaImportContactsInLDAPEnable').checked){XHR.appendData('ZarafaImportContactsInLDAPEnable',1);}else{XHR.appendData('ZarafaImportContactsInLDAPEnable',0);}
				AnimateDiv('anim-$t');
				XHR.sendAndLoad('$page', 'POST',X_APP_ZARAFA_WEB_SAVE$t);	
			}
			
			ZarafaApacheDisableCheck();
		</script>		
		";	
echo $tpl->_ENGINE_parse_body($html);
	
}

function zarafa_settings_webmail_save(){
	$sock=new sockets();
	$ZarafaApachePort=$sock->GET_INFO("ZarafaApachePort");
	if(!is_numeric($_POST["ZarafaApachePort"])){$_POST["ZarafaApachePort"]=9010;}
	if(!is_numeric($ZarafaApachePort)){$ZarafaApachePort=9010;}
	
	if($ZarafaApachePort<>$_POST["ZarafaApachePort"]){
		$socket = @socket_create(AF_INET, SOCK_STREAM, 0);
		if(socket_connect($socket, "127.0.0.1", $_POST["ZarafaApachePort"])){
			@socket_close($socket);
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{error_port_already_use} {$_POST["ZarafaApachePort"]}");
		}else{
			$sock->SET_INFO("ZarafaApachePort",trim($_POST["ZarafaApachePort"]));	
		}
	}
	
	
	
	
	$sock->SET_INFO("ZarafaApacheEnable",trim($_POST["ZarafaApacheEnable"]));
	$sock->SET_INFO("ZarafaApacheSSL",trim($_POST["ZarafaApacheSSL"]));
	$sock->SET_INFO("ZarafaApacheServerName",trim($_POST["ZarafaApacheServerName"]));
	$sock->SET_INFO("ZarafaWebNTLM",trim($_POST["ZarafaWebNTLM"]));
	$sock->SET_INFO("ZarafaEnablePlugins",trim($_POST["ZarafaEnablePlugins"]));
	$sock->SET_INFO("ZarafaAspellEnabled",trim($_POST["ZarafaAspellEnabled"]));
	$sock->SET_INFO("ZarafaImportContactsInLDAPEnable",trim($_POST["ZarafaImportContactsInLDAPEnable"]));
	$sock->getFrameWork("cmd.php?zarafa-restart-web=yes");
	
}



?>