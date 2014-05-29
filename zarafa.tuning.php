<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	if(isset($_POST["ZarafaCacheCellSize"])){Save();exit;}
	if(isset($_POST["ZARAFA_LANG"])){ZARAFA_LANG();exit;}
	if(isset($_POST["ZarafaStoreOutside"])){ZarafaStoreOutside_save();exit;}
	if(isset($_POST["ZarafaServerSMTPIP"])){SaveZarafaNet();exit;}
	if(isset($_POST["build-locales"])){BuildLocales();exit;}
	if(isset($_GET["locales-gen-running"])){locales_gen_running();exit;}
page();


function page(){
	
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$memdispo=$users->MEM_TOTAL_INSTALLEE*1024;
	$page=CurrentPageName();
	
	$convert_current_attachments_text=$tpl->javascript_parse_text("{convert_current_attachments}");
	$ZarafaCacheCellSize=$sock->GET_INFO("ZarafaCacheCellSize");
	$ZarafaCacheObjectSize=$sock->GET_INFO("ZarafaCacheObjectSize");
	$ZarafaCacheIndexedObjectSize=$sock->GET_INFO("ZarafaCacheIndexedObjectSize");
	$ZarafaCacheQuotaSize=$sock->GET_INFO("ZarafaCacheQuotaSize");
	$ZarafaCacheQuotaLifeTime=$sock->GET_INFO("ZarafaCacheQuotaLifeTime");
	$ZarafaCacheAclSize=$sock->GET_INFO("ZarafaCacheAclSize");
	$ZarafaCacheUserSize=$sock->GET_INFO("ZarafaCacheUserSize");
	$ZarafaCacheUserDetailsSize=$sock->GET_INFO("ZarafaCacheUserDetailsSize");
	$ZarafaCacheUserDetailsLifeTime=$sock->GET_INFO("ZarafaCacheUserDetailsLifeTime");
	$ZarafaThreadStackSize=$sock->GET_INFO("ZarafaThreadStackSize");
	$ZarafaCacheServerSize=$sock->GET_INFO("ZarafaCacheServerSize");
	
	
	if(!is_numeric($ZarafaCacheCellSize)){$ZarafaCacheCellSize=round($memdispo/2);}
	if(!is_numeric($ZarafaCacheQuotaLifeTime)){$ZarafaCacheQuotaLifeTime=1;}
	if(!is_numeric($ZarafaCacheUserDetailsLifeTime)){$ZarafaCacheUserDetailsLifeTime=5;}
	if(!is_numeric($ZarafaThreadStackSize)){$ZarafaThreadStackSize=512;}
	
	if(!is_numeric($ZarafaCacheUserDetailsSize)){$ZarafaCacheUserDetailsSize=1048576;}
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize/1024;
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize/1024;
	$ZarafaCacheUserDetailsSize=round($ZarafaCacheUserDetailsSize);		
	
	if(!is_numeric($ZarafaCacheAclSize)){$ZarafaCacheAclSize=1048576;}
	$ZarafaCacheAclSize=$ZarafaCacheAclSize/1024;
	$ZarafaCacheAclSize=$ZarafaCacheAclSize/1024;
	$ZarafaCacheAclSize=round($ZarafaCacheAclSize);	
	
	
	if(!is_numeric($ZarafaCacheServerSize)){$ZarafaCacheServerSize=1048576;}
	$ZarafaCacheServerSize=$ZarafaCacheServerSize/1024;
	$ZarafaCacheServerSize=$ZarafaCacheServerSize/1024;
	$ZarafaCacheServerSize=round($ZarafaCacheServerSize);		
	
	
	if(!is_numeric($ZarafaCacheUserSize)){$ZarafaCacheUserSize=1048576;}
	$ZarafaCacheUserSize=$ZarafaCacheUserSize/1024;
	$ZarafaCacheUserSize=$ZarafaCacheUserSize/1024;
	$ZarafaCacheUserSize=round($ZarafaCacheUserSize);		
	
	
	if(!is_numeric($ZarafaCacheQuotaSize)){$ZarafaCacheQuotaSize=16777216;}
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize/1024;
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize/1024;
	$ZarafaCacheQuotaSize=round($ZarafaCacheQuotaSize);
	
	$ZarafaCacheCellSize=$ZarafaCacheCellSize/1024;
	$ZarafaCacheCellSize=$ZarafaCacheCellSize/1024;
	$ZarafaCacheCellSize=round($ZarafaCacheCellSize);
	
	
	if(!is_numeric($ZarafaCacheObjectSize)){$ZarafaCacheObjectSize=16777216;}
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize/1024;
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize/1024;
	$ZarafaCacheObjectSize=round($ZarafaCacheObjectSize);	
	
	if(!is_numeric($ZarafaCacheIndexedObjectSize)){$ZarafaCacheIndexedObjectSize=16777216;}
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize/1024;
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize/1024;
	$ZarafaCacheIndexedObjectSize=round($ZarafaCacheIndexedObjectSize);	
	$t=time();
	
	
	$ZARAFA_LANG=$sock->GET_INFO("ZARAFA_LANG");
	$languages=unserialize(base64_decode($sock->getFrameWork("zarafa.php?locales=yes")));
	while (list ($index, $data) = each ($languages) ){
		if(preg_match("#cannot set#i", $data)){continue;}
		$langbox[$data]=$data;
	}
	
$ZarafaUserSafeMode=$sock->GET_INFO("ZarafaUserSafeMode");
$ZarafaServerListenIP=$sock->GET_INFO("ZarafaServerListenIP");
$ZarafaServerListenPort=$sock->GET_INFO("ZarafaServerListenPort");


$ZarafaServerSMTPIP=$sock->GET_INFO("ZarafaServerSMTPIP");
$ZarafaServerSMTPPORT=$sock->GET_INFO("ZarafaServerSMTPPORT");
if($ZarafaServerSMTPIP==null){$ZarafaServerSMTPIP="127.0.0.1";}
if($ZarafaServerListenIP==null){$ZarafaServerListenIP="127.0.0.1";}
$ZarafaMAPISSLEnabled=$sock->GET_INFO("ZarafaMAPISSLEnabled");
$ZarafaMAPISSLPort=$sock->GET_INFO("ZarafaMAPISSLPort");
$ZarafaStoreOutside=$sock->GET_INFO("ZarafaStoreOutside");
$ZarafaStoreCompressionLevel=$sock->GET_INFO("ZarafaStoreCompressionLevel");
$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");

if(!is_numeric($ZarafaServerSMTPPORT)){$ZarafaServerSMTPPORT=25;}
if(!is_numeric($ZarafaServerListenPort)){$ZarafaServerListenPort=236;}
if(!is_numeric($ZarafaMAPISSLPort)){$ZarafaMAPISSLPort=237;}


if(!is_numeric($ZarafaStoreOutside)){$ZarafaStoreOutside=0;}
if(!is_numeric($ZarafaStoreCompressionLevel)){$ZarafaStoreCompressionLevel=6;}
if($ZarafaStoreOutsidePath==null){$ZarafaStoreOutsidePath="/var/lib/zarafa";}

for($i=0;$i<10;$i++){
	$ZarafaStoreCompressionLevelAr[$i]=$i;
}
	
	$net=new networking();
	
	$nets=$net->ALL_IPS_GET_ARRAY();
	$nets["0.0.0.0"]="{all}";
	
	$netsSMTP=$nets;
	
	unset($netsSMTP["0.0.0.0"]);
	$SMTPfield=Field_array_Hash($netsSMTP,"ZarafaServerSMTPIP",$ZarafaServerSMTPIP,"font-size:18px;padding:3px");
	
	$ZarafaServerListenIP=Field_array_Hash($nets,"ZarafaServerListenIP",$ZarafaServerListenIP,"style:font-size:18px;padding:3px");
	
		
	$langbox[null]="C";
	
	$mailbox_language=Field_array_Hash($langbox,"ZARAFA_LANG",$ZARAFA_LANG,"style:font-size:18px;padding:3px");
	$build_locales_explain=$tpl->javascript_parse_text("{build_locales_explain}");
	
	
	$html="
	<div id='$t'>
	<div class=explain style='font-size:16px'>{zarafa_tune_explain}</div>
	
<div style='width:98%' class=form>	
	<div id='locales-gen-running-$t'></div>
	<table style='width:99%'>	
	<tr>
		<td class=legend style='font-size:18px'>{zarafaMbxLang}:</td>
		<td style='font-size:13px'>$mailbox_language</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{build_languages}","BuildLocales()",26)."&nbsp;|&nbsp;". button("{apply}","SaveZarafLang()",26)."</td>
	</tr>	
</table>
</div>	
<div style='width:98%' class=form>	
	<table style='width:99%'>				
	<tr>
		<td colspan=3 style='font-size:22px'>{listen_addresses}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{outgoing_smtp_server}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaServerSMTPIP",$ZarafaServerSMTPIP,"font-size:18px;width:200px")."</td>
		<td></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{outgoing_smtp_server_port}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaServerSMTPPORT",$ZarafaServerSMTPPORT,"font-size:18px;width:100px")."</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{mapi_address}:</td>
		<td style='font-size:18px'>$ZarafaServerListenIP</td>
		<td></td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{mapi_port}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaServerListenPort",$ZarafaServerListenPort,"font-size:18px;width:100px")."</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>MAPI SSL:</td>
		<td style='font-size:18px'>". Field_checkbox("ZarafaMAPISSLEnabled",1,$ZarafaMAPISSLEnabled,"ZarafaMAPISSLEnabledCheck()")."</td>
		<td></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>MAPI {ssl_port}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaMAPISSLPort",$ZarafaMAPISSLPort,"font-size:18px;width:100px")."</td>
		<td></td>
	</tr>					
				
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveZarafaNet()",26)."</td>
	</tr>				
</table>
</div>	
<br>
<div style='width:98%' class=form>	
	<table style='width:99%'>				
	<tr>
		<td colspan=3 style='font-size:22px'>{attachments}</td>
	</tr>	
			<tr>
				<td class=legend style='font-size:18px'>{ZarafaStoreOutside}:</td>
				<td>". Field_checkbox("ZarafaStoreOutside",1,$ZarafaStoreOutside,"CheckZarafaATTCH()")."</td>
				<td></td>
			</tr>	
			<tr>
				<td class=legend style='font-size:18px'>{attachments_path}:</td>
				<td>". Field_text("ZarafaStoreOutsidePath",$ZarafaStoreOutsidePath,"width:320px;font-size:18px;padding:3px")."</td>
				<td>". button_browse("ZarafaStoreOutsidePath")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:18px'>{attachments_compression_level}:</td>
				<td>". Field_array_Hash($ZarafaStoreCompressionLevelAr,"ZarafaStoreCompressionLevel",$ZarafaStoreCompressionLevel,"style:font-size:18px;padding:3px")."</td>
				<td></td>
			</tr>	
			<tr>
				<td colspan=3 align='right'><a href=\"javascript:blur();\" OnClick=\"DbAttachConverter()\" 
				style='font-size:18px;text-decoration:underline'>{convert_current_attachments}</a></td>
			</tr>
			<tr><td colspan=3 align='right'><hr>". button("{apply}","SaveZarafaAttch()",26)."</td></tr>																	
		</table>					
</div>				
<br>
<div style='width:98%' class=form>	
	<table style='width:99%'>
	<tr>
		<td colspan=3 style='font-size:22px'>{performance}</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaThreadStackSize}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaThreadStackSize",$ZarafaThreadStackSize,"font-size:18px;width:100px")."&nbsp;KB</td>
		<td>". help_icon("{ZarafaThreadStackSize_explain}")."</td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaCacheServerSize}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheServerSize",$ZarafaCacheServerSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheServerSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{cache_cell_size}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheCellSize",$ZarafaCacheCellSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{zcache_cell_size_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{cache_object_size}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheObjectSize",$ZarafaCacheObjectSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{cache_object_size_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{cache_indexedobject_size}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheIndexedObjectSize",$ZarafaCacheIndexedObjectSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{cache_indexedobject_size_explain}")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaCacheUserSize}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheUserSize",$ZarafaCacheUserSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheUserSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaCacheUserDetailsSize}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheUserDetailsSize",$ZarafaCacheUserDetailsSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheUserDetailsSize_explain}")."</td>
	</tr>
	
	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaCacheAclSize}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheAclSize",$ZarafaCacheAclSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<tD>". help_icon("{ZarafaCacheAclSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaCacheQuotaSize}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheQuotaSize",$ZarafaCacheQuotaSize,"font-size:18px;width:100px")."&nbsp;MB</td>
		<td>". help_icon("{ZarafaCacheQuotaSize_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaCacheQuotaLifeTime}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheQuotaLifeTime",$ZarafaCacheQuotaLifeTime,"font-size:18px;width:90px")."&nbsp;{minutes}</td>
		<td>". help_icon("{ZarafaCacheQuotaLifeTime_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{ZarafaCacheUserDetailsLifeTime}:</td>
		<td style='font-size:18px'>". Field_text("ZarafaCacheUserDetailsLifeTime",$ZarafaCacheUserDetailsLifeTime,"font-size:18px;width:90px")."&nbsp;{minutes}</td>
		<td>". help_icon("{ZarafaCacheUserDetailsLifeTime_explain}")."</td>
	</tr>
	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveZarafTuning()",26)."</td>
	</tr>
	
	</tbody>
	</table>
</div>
	</div>
	<script>
var X_SaveZarafTuning= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTab('main_config_zarafa2');
	}	
	
function SaveZarafLang(){
	var XHR = new XHRConnection();
	XHR.appendData('ZARAFA_LANG',document.getElementById('ZARAFA_LANG').value);
	XHR.sendAndLoad('$page', 'POST',X_SaveZarafTuning);	
}
function CheckZarafaATTCH(){
	document.getElementById('ZarafaStoreOutsidePath').disabled=true;
	document.getElementById('ZarafaStoreCompressionLevel').disabled=true;
	if(document.getElementById('ZarafaStoreOutside').checked){
		document.getElementById('ZarafaStoreOutsidePath').disabled=false;
		document.getElementById('ZarafaStoreCompressionLevel').disabled=false;			
	}
}

function BuildLocales(){
	var XHR = new XHRConnection();
	if(!confirm('$build_locales_explain')){return;}
	XHR.appendData('build-locales','yes');
	XHR.sendAndLoad('$page', 'POST',X_SaveZarafTuning);	
}

function SaveZarafaAttch(){
	var XHR = new XHRConnection();
	if(document.getElementById('ZarafaStoreOutside').checked){XHR.appendData('ZarafaStoreOutside',1);}else{XHR.appendData('ZarafaStoreOutside',0);}
	XHR.appendData('ZarafaStoreOutsidePath',document.getElementById('ZarafaStoreOutsidePath').value);
	XHR.appendData('ZarafaStoreCompressionLevel',document.getElementById('ZarafaStoreCompressionLevel').value);
	XHR.sendAndLoad('$page', 'POST',X_SaveZarafTuning);	
}
function DbAttachConverter(){
	YahooWin('550','zarafa.web.php?DbAttachConverter-popup=yes','$convert_current_attachments_text');
}

function SaveZarafaNet(){

	var XHR = new XHRConnection();
	XHR.appendData('ZarafaServerSMTPIP',document.getElementById('ZarafaServerSMTPIP').value);
	XHR.appendData('ZarafaServerSMTPPORT',document.getElementById('ZarafaServerSMTPPORT').value);
	XHR.appendData('ZarafaServerListenIP',document.getElementById('ZarafaServerListenIP').value);
	XHR.appendData('ZarafaServerListenPort',document.getElementById('ZarafaServerListenPort').value);
	XHR.appendData('ZarafaMAPISSLPort',document.getElementById('ZarafaMAPISSLPort').value);
	if( document.getElementById('ZarafaMAPISSLEnabled').checked){
		XHR.appendData('ZarafaMAPISSLEnabled',1);
	}else{
		XHR.appendData('ZarafaMAPISSLEnabled',0);
	}
	 
	
	XHR.sendAndLoad('$page', 'POST',X_SaveZarafTuning);	
	
}

		function IsLocalgenRunninZarafa(){
			if(!document.getElementById('locales-gen-running-$t')){return;}
			LoadAjaxVerySilent('locales-gen-running','$page?locales-gen-running=yes');
		
		}

function ZarafaMAPISSLEnabledCheck(){
	document.getElementById('ZarafaMAPISSLPort').disabled=true;
	if( document.getElementById('ZarafaMAPISSLEnabled').checked){
		document.getElementById('ZarafaMAPISSLPort').disabled=false;
	}
}

	
function SaveZarafTuning(){
	var XHR = new XHRConnection();
	XHR.appendData('ZarafaCacheCellSize',document.getElementById('ZarafaCacheCellSize').value);
	XHR.appendData('ZarafaCacheObjectSize',document.getElementById('ZarafaCacheObjectSize').value);
	XHR.appendData('ZarafaCacheIndexedObjectSize',document.getElementById('ZarafaCacheIndexedObjectSize').value);
	
	XHR.appendData('ZarafaCacheUserSize',document.getElementById('ZarafaCacheUserSize').value);
	XHR.appendData('ZarafaCacheUserDetailsSize',document.getElementById('ZarafaCacheUserDetailsSize').value);
	XHR.appendData('ZarafaCacheAclSize',document.getElementById('ZarafaCacheAclSize').value);
	XHR.appendData('ZarafaCacheQuotaSize',document.getElementById('ZarafaCacheQuotaSize').value);
	XHR.appendData('ZarafaCacheQuotaLifeTime',document.getElementById('ZarafaCacheQuotaLifeTime').value);
	
	
	XHR.appendData('ZarafaCacheUserDetailsLifeTime',document.getElementById('ZarafaCacheUserDetailsLifeTime').value);
	XHR.appendData('ZarafaThreadStackSize',document.getElementById('ZarafaThreadStackSize').value);
	XHR.appendData('ZarafaCacheServerSize',document.getElementById('ZarafaCacheServerSize').value);
	XHR.sendAndLoad('$page', 'POST',X_SaveZarafTuning);	
}
ZarafaMAPISSLEnabledCheck();
CheckZarafaATTCH();
IsLocalgenRunninZarafa();
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function BuildLocales(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?locales-gen=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{task_has_been_scheduled_in_background_mode}");
	
}

function SaveZarafaNet(){
	$sock=new sockets();
	
	
	
	
	$sock->SET_INFO("ZarafaServerListenIP", $_POST["ZarafaServerListenIP"]);
	$sock->SET_INFO("ZarafaMAPISSLEnabled", $_POST["ZarafaMAPISSLEnabled"]);
	$sock->SET_INFO("ZarafaServerSMTPIP", $_POST["ZarafaServerSMTPIP"]);
	$sock->SET_INFO("ZarafaMAPISSLPort", $_POST["ZarafaMAPISSLPort"]);
	
	
	$sock->SET_INFO("ZarafaServerListenPort", $_POST["ZarafaServerListenPort"]);
	$sock->SET_INFO("ZarafaServerSMTPPORT", $_POST["ZarafaServerSMTPPORT"]);
	
	
	$sock->SET_INFO("ZarafaUserSafeMode", $_POST["ZarafaUserSafeMode"]);
	
	$sock->SET_INFO("ZarafaLogLevel", $_POST["ZarafaLogLevel"]);
	$sock->SET_INFO("ZarafaEnableSecurityLogging", $_POST["ZarafaEnableSecurityLogging"]);
	
	

	

	
	$sock->getFrameWork("zarafa.php?restart-server=yes");
	
	
	
}

function ZarafaStoreOutside_save(){
	
	$sock=new sockets();
	$sock->SET_INFO("ZarafaStoreOutside", $_POST["ZarafaStoreOutside"]);
	$sock->SET_INFO("ZarafaStoreOutsidePath", $_POST["ZarafaStoreOutsidePath"]);
	$sock->SET_INFO("ZarafaStoreCompressionLevel", $_POST["ZarafaStoreCompressionLevel"]);	
	
}


function Save(){
	
	$ZarafaCacheQuotaLifeTime=$_POST["ZarafaCacheQuotaLifeTime"];
	$ZarafaCacheUserDetailsLifeTime=$_POST["ZarafaCacheUserDetailsLifeTime"];
	$ZarafaThreadStackSize=$_POST["ZarafaThreadStackSize"];
	
	$ZarafaCacheServerSize=$_POST["ZarafaCacheServerSize"];
	$ZarafaCacheServerSize=$ZarafaCacheServerSize*1024;
	$ZarafaCacheServerSize=$ZarafaCacheServerSize*1024;

	
	$ZarafaCacheCellSize=$_POST["ZarafaCacheCellSize"];
	$ZarafaCacheCellSize=$ZarafaCacheCellSize*1024;
	$ZarafaCacheCellSize=$ZarafaCacheCellSize*1024;
	
	$ZarafaCacheObjectSize=$_POST["ZarafaCacheObjectSize"];
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize*1024;
	$ZarafaCacheObjectSize=$ZarafaCacheObjectSize*1024;	
	
	$ZarafaCacheUserSize=$_POST["ZarafaCacheUserSize"];
	$ZarafaCacheUserSize=$ZarafaCacheUserSize*1024;
	$ZarafaCacheUserSize=$ZarafaCacheUserSize*1024;	
	
	$ZarafaCacheUserDetailsSize=$_POST["ZarafaCacheUserDetailsSize"];
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize*1024;
	$ZarafaCacheUserDetailsSize=$ZarafaCacheUserDetailsSize*1024;	

	$ZarafaCacheAclSize=$_POST["ZarafaCacheAclSize"];
	$ZarafaCacheAclSize=$ZarafaCacheAclSize*1024;
	$ZarafaCacheAclSize=$ZarafaCacheAclSize*1024;	

	$ZarafaCacheQuotaSize=$_POST["ZarafaCacheQuotaSize"];
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize*1024;
	$ZarafaCacheQuotaSize=$ZarafaCacheQuotaSize*1024;	
	
	$ZarafaCacheIndexedObjectSize=$_POST["ZarafaCacheIndexedObjectSize"];
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize*1024;
	$ZarafaCacheIndexedObjectSize=$ZarafaCacheIndexedObjectSize*1024;		
	
	$sock=new sockets();
	$sock->SET_INFO("ZarafaCacheCellSize",$ZarafaCacheCellSize);
	$sock->SET_INFO("ZarafaCacheObjectSize",$ZarafaCacheObjectSize);
	$sock->SET_INFO("ZarafaCacheIndexedObjectSize",$ZarafaCacheIndexedObjectSize);	
	
	$sock->SET_INFO("ZarafaCacheUserSize",$ZarafaCacheUserSize);	
	$sock->SET_INFO("ZarafaCacheUserDetailsSize",$ZarafaCacheUserDetailsSize);	
	$sock->SET_INFO("ZarafaCacheAclSize",$ZarafaCacheAclSize);	
	$sock->SET_INFO("ZarafaCacheQuotaSize",$ZarafaCacheQuotaSize);	
	$sock->SET_INFO("ZarafaCacheQuotaLifeTime",$ZarafaCacheQuotaLifeTime);
	$sock->SET_INFO("ZarafaCacheUserDetailsLifeTime",$ZarafaCacheUserDetailsLifeTime);
	$sock->SET_INFO("ZarafaThreadStackSize",$ZarafaThreadStackSize);
	$sock->SET_INFO("ZarafaCacheServerSize",$ZarafaCacheServerSize);			
	$sock->getFrameWork("zarafa.php?restart=yes");
	
}

function ZARAFA_LANG(){
	$sock=new sockets();
	$sock->SET_INFO("ZARAFA_LANG", $_POST["ZARAFA_LANG"]);
	$sock->getFrameWork("zarafa.php?build-init=yes");
	
	
}

function locales_gen_running(){
	$sock=new sockets();
	$page=CurrentPageName();
	$array=unserialize(base64_decode($sock->getFrameWork("services.php?locales-gen-running=yes")));
	if(!is_array($array)){echo "<script>
		setTimeout(\"IsLocalgenRunninZarafa()\",3000);
	</script>";
	return;}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<p class=text-info style='font-size:18px'>{languages} {running} {since} :{$array["SINCE"]}mn PID {$array["PID"]}</p>
	
	<script>

		
		setTimeout(\"IsLocalgenRunninZarafa()\",5000);
	</script>
	");
	
	
}

