<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/ressources/class.monit.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");

$users=new usersMenus();
if(!$users->AsArticaAdministrator==true){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["UnifiListenInterface"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}

tabs();


function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$is_installed=$sock->getFrameWork("unifi.php?is_installed=yes");
	
	if($is_installed<>"TRUE"){
		$html="<center style='margin:100px'>
			<div class=explain style='font-size:22px;margin-bottom:30px'>{UNIFI_CONTROLLER_EXPLAIN}</div>
			".button("{install_upgrade}", "Loadjs('unifi.install.progress.php')",50)."
					
			</center>";
		
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	$array["main"]="{parameters}";
	
	
	
	
	$fontsize=22;
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="hosts"){
			$tab[]="<li><a href=\"artica-meta.hosts.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
	
	
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "unifi_tabs",1490)."<script>LeftDesign('management-console-256.png');</script>";
	
	
	
}

function main(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$html="
	<div style='font-size:30px;margin-bottom:20px'>{UNIFI_CONTROLLER}</div>	

	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td style='width:500px' valign='top'><div id='unifi-status'></div></td>
			<td style='width:990px' valign='top'><div id='unifi-parameters'></div></td>
		</tr>
	</table>
	</div>
	<script>LoadAjaxRound('unifi-parameters','$page?parameters=yes');</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function parameters(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$ip=new networking();
	
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$UnifiListenInterface=$sock->GET_INFO("UnifiListenInterface");
	
	$UnifiHTTPPort=intval($sock->GET_INFO("UnifiHTTPPort"));
	$UnifiHTTPSPort=intval($sock->GET_INFO("UnifiHTTPSPort"));
	$UnifiPortalPort=intval($sock->GET_INFO("UnifiPortalPort"));
	$UnifiPortalSSLPort=intval($sock->GET_INFO("UnifiPortalSSLPort"));
	$UnifiUDPPort=intval($sock->GET_INFO("UnifiUDPPort"));
	$UnifiUUID=$sock->GET_INFO("UnifiUUID");
	if($UnifiUUID==null){$UnifiUUID=$sock->getFrameWork("unifi.php?GetUUID=yes");}
	if($UnifiHTTPPort==0){$UnifiHTTPPort=8088;}
	if($UnifiHTTPSPort==0){$UnifiHTTPSPort=8443;}
	if($UnifiPortalPort==0){$UnifiPortalPort=8880;}
	if($UnifiPortalSSLPort==0){$UnifiPortalSSLPort=8943;}
	if($UnifiUDPPort==0){$UnifiUDPPort=3478;}
	
	while (list ($eth, $none) = each ($interfaces) ){
		$nic=new system_nic($eth);
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		
	
	}
	
	$array[null]="{all}";
	$EnableUnifiController=intval($sock->GET_INFO("EnableUnifiController"));
	$p=Paragraphe_switch_img("{ENABLE_UNIFI_CONTROLLER}", "{UNIFI_CONTROLLER_EXPLAIN}","EnableUnifiController",$EnableUnifiController,null,890);
	
	
	$html="<div style='font-size:30px;margin-bottom:20px'>{parameters}</div>
	<div style='width:98%' class=form>$p
	
	<div style='font-size:26px;margin-bottom:10px;text-align:right'><a href=\"https://{$_SERVER["SERVER_NAME"]}:$UnifiHTTPSPort\" style='text-decoration:underline' target=_new>{web_interface}</a></div>
	
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{listen_interface}:</td>
		<td style='font-size:20px'>". Field_array_Hash($array, "UnifiListenInterface-$t",$UnifiListenInterface,"style:font-size:22px;font-weight:bold")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{UnifiHTTPPort}:</td>
		<td style='font-size:20px'>". Field_text("UnifiHTTPPort-$t",$UnifiHTTPPort,"font-size:22px;font-weight:bold;width:150px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{UnifiHTTPSPort}:</td>
		<td style='font-size:20px'>". Field_text("UnifiHTTPSPort-$t",$UnifiHTTPSPort,"font-size:22px;font-weight:bold;width:150px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{UnifiPortalPort}:</td>
		<td style='font-size:20px'>". Field_text("UnifiPortalPort-$t",$UnifiPortalPort,"font-size:22px;font-weight:bold;width:150px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:22px'>{UnifiPortalSSLPort}:</td>
		<td style='font-size:20px'>". Field_text("UnifiPortalSSLPort-$t",$UnifiPortalSSLPort,"font-size:22px;font-weight:bold;width:150px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px'>{UnifiUDPPort}:</td>
		<td style='font-size:20px'>". Field_text("UnifiUDPPort-$t",$UnifiUDPPort,"font-size:22px;font-weight:bold;width:150px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{uuid}:</td>
		<td style='font-size:20px'>". Field_text("UnifiUUID-$t",
				$UnifiUUID,"font-size:22px;font-weight:bold;width:560px")."</td>
	</tr>				
	<tr style='height:70px'>
				<td style=';text-align:right' colspan=2>". button("{apply}", "Save$t()","32")."</td>
	</tr>	
	
	</table>
<script>
	var xSave$t=function (obj) {
		var tempvalue=obj.responseText;
		if (tempvalue.length>3){alert(tempvalue);return;}
		RefreshTab('unifi_tabs');
		Loadjs('unifi.restart.progress.php');
		
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
		
		XHR.appendData('EnableUnifiController',document.getElementById('EnableUnifiController').value);
		XHR.appendData('UnifiListenInterface',document.getElementById('UnifiListenInterface-$t').value);
		XHR.appendData('UnifiHTTPPort',document.getElementById('UnifiHTTPPort-$t').value);
		XHR.appendData('UnifiHTTPSPort',encodeURIComponent(document.getElementById('UnifiHTTPSPort-$t').value));
		XHR.appendData('UnifiPortalPort',document.getElementById('UnifiPortalPort-$t').value);
		XHR.appendData('UnifiPortalSSLPort',document.getElementById('UnifiPortalSSLPort-$t').value);
		XHR.appendData('UnifiUDPPort',encodeURIComponent(document.getElementById('UnifiUDPPort-$t').value));
		XHR.appendData('UnifiUUID',encodeURIComponent(document.getElementById('UnifiUUID-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}

	
LoadAjaxRound('unifi-status','$page?status=yes');	
	
</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$sock=new sockets();
	
	while (list ($key, $line) = each ($_POST) ){
		$sock->SET_INFO($key, $line);
	}
	
}
function status(){
	$sock=new sockets();
	$sock->getFrameWork("unifi.php?status=yes");
	$tpl=new templates();
	
	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/unifi.status");
	
	$UNIFI_MONGODB=DAEMON_STATUS_ROUND("UNIFI_MONGODB",$ini,null);
	$UNIFI_CONTROLLER=DAEMON_STATUS_ROUND("UNIFI_CONTROLLER",$ini,null);
	
	echo $tpl->_ENGINE_parse_body("
			$UNIFI_CONTROLLER
			<p>&nbsp;</p>
			$UNIFI_MONGODB
			
			");
	
	
}