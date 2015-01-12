<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');


$user=new usersMenus();
if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();exit();
}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SquidEnforceRules"])){Save();exit;}
if(isset($_GET["service-status"])){ServiceStatus();exit;}

tabs();
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$array["status"]='{enforce_rules}';
	$array["rules"]='{rules}';
	$array["mirror"]='{mirror}';
	$array["whitelist"]='{whitelist}';
	$array["downloader-event"]='{downloader_events}';
	


	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.artica-rules.rules.php?$num=yes\"
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="mirror"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.artica-rules.mirror.php?$num=yes\"
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="whitelist"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.artica-rules.whitelist.php\"
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="downloader-event"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.artica-rules.downloader-events.php\"
					style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
				
		}		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"
		style='font-size:18px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "main_artica_enforce_rules");


}

//06.86.63.42.99


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$SquidEnforceRules=intval($sock->GET_INFO("SquidEnforceRules"));
	$HyperCacheStoragePath=$sock->GET_INFO("HyperCacheStoragePath");
	$HyperCacheMemEntries=intval($sock->GET_INFO("HyperCacheMemEntries"));
	$HyperCacheBuffer=intval($sock->GET_INFO("HyperCacheBuffer"));
	$HyperCacheBuffer=intval($sock->GET_INFO("HyperCacheBuffer"));
	if($HyperCacheMemEntries==0){$HyperCacheMemEntries=500000;}
	if($HyperCacheBuffer==0){$HyperCacheBuffer=50;}
	if($HyperCacheStoragePath==null){$HyperCacheStoragePath="/home/artica/proxy-cache";}
	$HyperCacheListenAddr=$sock->GET_INFO("HyperCacheListenAddr");
	$HyperCacheHTTPListenPort=intval($sock->GET_INFO("HyperCacheHTTPListenPort"));
	if(!is_numeric($HyperCacheHTTPListenPort)){$HyperCacheHTTPListenPort=8700;}
	$HyperCacheHTTPListenPortSSL=$sock->GET_INFO("HyperCacheHTTPListenPortSSL");
	if(!is_numeric($HyperCacheHTTPListenPort)){$HyperCacheHTTPListenPort=8700;}
	if(!is_numeric($HyperCacheHTTPListenPortSSL)){$HyperCacheHTTPListenPortSSL=8900;}
	if($HyperCacheHTTPListenPort==0){$HyperCacheHTTPListenPort=8700;}
	
	$ip=new networking();
	$ipsH=$ip->ALL_IPS_GET_ARRAY();
	unset($ipsH["127.0.0.1"]);
	$html="<table style='width:100%'>
	<tr>
		<td style='width:240px;vertical-align:top'><div id='status-enf'></div></td>
		<td style='width:99%'>
			<div style='width:98%' class=form>
			". Paragraphe_switch_img("{enforce_rules}", "{enforce_rules_explain}","SquidEnforceRules",
					$SquidEnforceRules,null,820)."
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:22px'>{storage_files_path}:</td>
					<td>".Field_text("HyperCacheStoragePath", $HyperCacheStoragePath,"font-size:22px;width:350px")."</td>
					<td>". button_browse("HyperCacheStoragePath")."</td>
				</tr>
				<tr>
					<td class=legend style='font-size:22px'>{cache_memory} ({items}):</td>
					<td>".Field_text("HyperCacheMemEntries", $HyperCacheMemEntries,"font-size:22px;width:110px")."</td>
					<td>&nbsp;</td>
				</tr>	
				<tr>
					<td class=legend style='font-size:22px'>{buffer} ({items}):</td>
					<td>".Field_text("HyperCacheBuffer", $HyperCacheBuffer,"font-size:22px;width:110px")."</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td style='font-size:22px' class=legend nowrap>{listen_address} ({web_service}):</td>
					<td>". Field_array_Hash($ipsH,"HyperCacheListenAddr",$HyperCacheListenAddr,"style:font-size:22px")."</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td style='font-size:22px' class=legend nowrap>{listen_port} ({web_service}):</td>
					<td>".Field_text("HyperCacheHTTPListenPort", $HyperCacheHTTPListenPort,"font-size:22px;width:110px")."</td>
					<td>&nbsp;</td>
				</tr>							
							
					<tr><td colspan=3 align='right'><hr>".button("{apply}","Save$t()",30)."</td></tr>
				</table>
			</div>
		</td>
	</tr>
	</table>
<script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	Loadjs('squid.artica-rules.progress.php');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var EnableSquidCacheBoosters=0;
	XHR.appendData('HyperCacheStoragePath',document.getElementById('HyperCacheStoragePath').value);
	XHR.appendData('HyperCacheMemEntries',document.getElementById('HyperCacheMemEntries').value);
	XHR.appendData('HyperCacheBuffer',document.getElementById('HyperCacheBuffer').value);
	XHR.appendData('SquidEnforceRules',document.getElementById('SquidEnforceRules').value);
	XHR.appendData('HyperCacheHTTPListenPort',document.getElementById('HyperCacheHTTPListenPort').value);
	XHR.appendData('HyperCacheListenAddr',document.getElementById('HyperCacheListenAddr').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);						
}

LoadAjax('status-enf','$page?service-status=yes');

</script>										
	";
	
	
echo $tpl->_ENGINE_parse_body($html);	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidEnforceRules", $_POST["SquidEnforceRules"]);
	$sock->SET_INFO("HyperCacheStoragePath", $_POST["HyperCacheStoragePath"]);
	$sock->SET_INFO("HyperCacheMemEntries", $_POST["HyperCacheMemEntries"]);
	$sock->SET_INFO("HyperCacheBuffer", $_POST["HyperCacheBuffer"]);
	$sock->SET_INFO("HyperCacheHTTPListenPort", $_POST["HyperCacheHTTPListenPort"]);
	$sock->SET_INFO("HyperCacheListenAddr", $_POST["HyperCacheListenAddr"]);
	
	
	
}

function ServiceStatus(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$data=$sock->getFrameWork('cmd.php?hypercacheweb-ini-status=yes');
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($data));
	$APP_UFDBCAT=DAEMON_STATUS_ROUND("APP_HYPERCACHE_WEB",$ini,null,1);
	echo $tpl->_ENGINE_parse_body($APP_UFDBCAT).
	"<div style='margin-top:10px;text-align:right'>".imgsimple("refresh-32.png",null,"LoadAjax('status-enf','$page?service-status=yes');")."</div>";
}

