<?php
$GLOBALS["AS_ROOT"]=false;
$GLOBALS["VERBOSE"]=false;
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
$GLOBALS["ICON_FAMILY"]="SYSTEM";
session_start();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!$GLOBALS["AS_ROOT"]){
	if(!isset($_SESSION["uid"])){echo "window.location.href = 'logoff.php'";die();}
}


include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');

$users=new usersMenus();
if($GLOBALS["AS_ROOT"]){
	$users->AsAnAdministratorGeneric=true;
	$users->AsSystemAdministrator=true;
}
//if(!$users->AsAnAdministratorGeneric){writelogs("Redirect to users.index.php",__FUNCTION__,__FILE__,__LINE__);
//header('location:miniadm.php');exit;}

if(isset($_GET["showInfos"])){showInfos_js();exit;}

if(isset($_GET["showInfos-id"])){showInfos_popup();exit;}
if(isset($_POST["disable"])){disable();exit;}
if(isset($_GET["left-menus-services"])){left_menus_services();exit;}
if(isset($_GET["left-menus-actions"])){left_menus_actions();exit;}

if($GLOBALS["AS_ROOT"]){
	page();
	left_menus_services();
	left_menus_actions();
	wizards();
}

page();

function page(){

$page=CurrentPageName();
$tpl=new templates();
$cachePage=dirname(__FILE__)."/ressources/logs/web/".basename(__FILE__).".".__FUNCTION__;
if(!$GLOBALS["AS_ROOT"]){
	if(is_file($cachePage)){
		$data=file_get_contents($cachePage);
		if(strlen($data)>45){
			echo $tpl->_ENGINE_parse_body($data)."<script>UnlockPage();</script>";
			return;
		}
	}
	
}

$sock=new sockets();

$datas=base64_decode($sock->getFrameWork("status.php?cpu-check-nx=yes"));
if($datas<>null){NotifyAdmin("system-32.png","CPU Infos !",$datas,null);}

if(is_file("ressources/logs/INTERNET_FAILED")){NotifyAdmin("domain-whitelist-32.png","{INTERNET_FAILED}","{INTERNET_FAILED_TEXT}\n".@file_get_contents("ressources/logs/INTERNET_FAILED"),null);}

$left_menus_services_js="";

if(is_file("/usr/share/artica-postfix/ressources/logs/web/admin.index.status-infos.php.left_menus_actions")){
	$left_menus_actions=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.status-infos.php.left_menus_actions");
	
}

if(is_file("/usr/share/artica-postfix/ressources/logs/web/admin.index.status-infos.php.left_menus_services")){
	$left_menus_services=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/admin.index.status-infos.php.left_menus_services");
}

$services_next=$tpl->_ENGINE_parse_body("
<div style='font-size:16px;color:white' id='services-title'>
	Services:
</div>
<div id='left-menus-services'>$left_menus_services</div>
<div id='left-menus-actions'>$left_menus_actions</div>

<script>
	var content=document.getElementById('left-menus-services').innerHTML;
	if(content.length<5){
		LoadAjaxWhite('left-menus-services','$page?left-menus-services=yes');
	}
	
</script>");

$q=new mysql();

$sql="DELETE FROM adminevents WHERE `text`='{websites_not_categorized_text}'";
$q->QUERY_SQL($sql,"artica_events");

$sql="SELECT * FROM adminevents WHERE enabled=1 ORDER BY zDate DESC LIMIT 0,50";
$results=$q->QUERY_SQL($sql,"artica_events");
$html="<table style='width:99%' class=form><tbody>";
$c=0;
$f=squid_filters_infos();
	if(is_array($f)){
		while (list ($num, $ligne) = each ($f) ){
			$c++;
			if($ligne["subject"]==null){continue;}
			$ligne["subject"]=$tpl->_ENGINE_parse_body($ligne["subject"]);
			$strlen=strlen($ligne["subject"]);
			$org_text=$ligne["subject"];
			if($strlen>25){$text=substr($ligne["subject"], 0,21)."...";}else{$text=$org_text;}
			
			$html=$html."
			<tr>
				<td width=1%><img src='img/{$ligne["icon"]}'></td>
				<td style='font-size:11px' nowrap><a href=\"javascript:blur();\" OnClick=\"javascript:{$ligne["js"]}\" style='font-size:11px;text-decoration:underline'>$text</a></td>
			</tr>
			";	
		}
	}

if(mysql_num_rows($results)==0){
	if($c==0){
		
		if($GLOBALS["AS_ROOT"]){
			@mkdir(dirname($cachePage),0777,true);
			@file_put_contents($cachePage, $html);
			@chmod($cachePage, 0777);
			return;
		}
		
		echo $services_next;
		return;
	
	}
	$html=$html."</tbody></table><hr>$services_next<script>UnlockPage();</script>";
	
	if($GLOBALS["AS_ROOT"]){
		@mkdir(dirname($cachePage),0777,true);
		@file_put_contents($cachePage, $html);
		@chmod($cachePage, 0777);
		return;
	}
	
	echo $html;
	return;
}


while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	if($ligne["icon"]=="danger64.png"){$ligne["icon"]="danger32.png";}
	if($ligne["icon"]=="warning64.png"){$ligne["icon"]="warning-panneau-32.png";}
	if($ligne["icon"]=="pluswarning64.png"){$ligne["icon"]="warning-panneau-32.png";}
	if($ligne["icon"]=="danger32.png"){$ligne["icon"]="warning-panneau-32.png";}
	if($ligne["icon"]=="license-error-64.png"){$ligne["icon"]="license-error-32.png";}
	
	$ligne["subject"]=$tpl->_ENGINE_parse_body($ligne["subject"]);
	$strlen=strlen($ligne["subject"]);
	$org_text=$ligne["subject"];
	$text=$org_text;
	$text=texttooltip($text,$org_text,"Loadjs('$page?showInfos={$ligne["zmd5"]}')",null,0,"font-size:11px;text-decoration:underline");
	$html=$html."
	<tr>
		<td width=1%><img src='img/{$ligne["icon"]}'></td>
		<td style='font-size:11px' nowrap>$text</td>
	</tr>
	";
	}



	if($GLOBALS["AS_ROOT"]){
		$time=date("H:i:s");
		$html=$html."
		<tr>
		<td width=1% colspan=2 style='font-size:11px;text-align:right'>$time</td>
		</tr>";
	
	}

$html=$html."</tbody></table>

<div style='width:100%;text-align:right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjaxWhite('admin-left-infos','admin.index.status-infos.php');")."</div>
<hr>
$services_next


";
if($GLOBALS["AS_ROOT"]){
	@mkdir(dirname($cachePage),0777,true);
	@file_put_contents($cachePage, $html);
	@chmod($cachePage, 0777);
	return;
}
echo $tpl->_ENGINE_parse_body($html);

}

function left_menus_services(){
	
	$cachePage=dirname(__FILE__)."/ressources/logs/web/".basename(__FILE__).".".__FUNCTION__;
	if(!$GLOBALS["AS_ROOT"]){
		if(is_file($cachePage)){
			$users=new usersMenus();
			if($users->AsSystemAdministrator){
				$tpl=new templates();
				$data=@file_get_contents($cachePage);
				if(strlen($data)>45){
					echo $tpl->_ENGINE_parse_body($data).wizards();
					return;
				}
			}
		}
	
	}	
	if(!$GLOBALS["AS_ROOT"]){	
		if(GET_CACHED(__FILE__, __FUNCTION__,null,false,1)){wizards();return;}
		if(internal_load()>1.2){if(GET_CACHED(__FILE__, __FUNCTION__)){wizards();return;}}	
	}
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$users=new usersMenus();
$t=time();
$OnlySMTP=false;
if($users->SMTP_APPLIANCE){$OnlySMTP=true;}
if($users->KASPERSKY_SMTP_APPLIANCE){$OnlySMTP=true;}
$SambaEnabled=$sock->GET_INFO("SambaEnabled");
if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}
$SambaJS="QuickLinksSamba()";
if($SambaEnabled==0){$SambaJS="only:Loadjs('samba.disable.php')";}
$IsPostfixlockedInt=0;
$IsPostfixlocked=base64_decode($sock->getFrameWork("postfix.php?islocked=yes"));
if($IsPostfixlocked=="TRUE"){$IsPostfixlockedInt=1;}

if($GLOBALS["AS_ROOT"]){
	$users->AsSambaAdministrator=true;
	$users->AsWebStatisticsAdministrator=true;
	$users->AsInventoryAdmin=true;
	$users->AsAnAdministratorGeneric=true;
	$users->AsPostfixAdministrator=true;
	$users->AsSystemAdministrator=true;
}


	
	if(!$GLOBALS['AS_ROOT']){
		$script="<script>LoadAjaxWhite('left-menus-actions','$page?left-menus-actions=yes');</script>";
	}
	
	$html="
		$bookmarks
		<table style='width:99%' class=form>
		<tbody>
		$license
		$squid_stats
		$postfix
		$miltergreylist
		$amavis
		$mimedefang
		$cyrus
		$mailman
		$fetchmail
		$haProxy
		$crossreads
		$ssh	
		$freeradius
		$openvpn
		$freewebs
		$pdns
		$ejabberd
		$computers
		$samba
		$dropbox
		$hamachi
		$ocs
		$mysql
		$logrotate
		$vmware
		$updateutility
		$roundcube
		</tbody>
		</table>
		$script
		
		
		";
	
	if($GLOBALS["AS_ROOT"]){
		if($GLOBALS["VERBOSE"]){echo "Saving $cachePage\n"; }
		@mkdir(dirname($cachePage),0777,true);
		@file_put_contents($cachePage, $html);
		@chmod($cachePage, 0777);
		return;
	}
	
		$html=$tpl->_ENGINE_parse_body($html);
		SET_CACHED(__FILE__, __FUNCTION__, null, $html);
		echo $html;
		wizards();
	
}

function left_menus_format($text,$img,$js,$explain=null){
	$id=md5("$text,$img,$js,$explain");
	$tpl=new templates();
	$animate="AnimateDiv('BodyContent');";
	if(preg_match("#only:(.+)#", $js,$re)){
		$js=$re[1];
		$animate=null;
	}

	
	$text=$tpl->_ENGINE_parse_body("{{$text}}");
	$uri="<a href=\"javascript:blur()\" OnClick=\"javascript:SeTimeOutIMG32('$id');$js\" style='font-size:11px;text-decoration:underline;text-transform:capitalize;'>
	$text</a>";
		
	
	return "<tr>
			<td width=1%>". imgsimple("$img",null,"SeTimeOutIMG32('$id');$js",null,$id)."</td>
			<td style='font-size:11px' nowrap>$uri</td>
			</tr>";

}

function left_menus_actions(){
	
	$cachePage=dirname(__FILE__)."/ressources/logs/web/".basename(__FILE__).".".__FUNCTION__;
	if(!$GLOBALS["AS_ROOT"]){
		if(is_file($cachePage)){
			$users=new usersMenus();
			if($users->AsSystemAdministrator){
				$data=@file_get_contents($cachePage);
				if(strlen($data)>45){
					$tpl=new templates();
					echo $tpl->_ENGINE_parse_body($data).wizards();
					return;
				}
			}
		}
	
	}	
	$users=new usersMenus();
	if(!$GLOBALS["AS_ROOT"]){
			if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage\n";}
			if(GET_CACHED(__FILE__, __FUNCTION__,null,false,1)){return;}
			if(internal_load()>1.2){if(GET_CACHED(__FILE__, __FUNCTION__)){return;}}	
	}else{
		
		$users->AsPostfixAdministrator=true;
		$users->AsAnAdministratorGeneric=true;
		$users->AsWebStatisticsAdministrator=true;
		$users->AsPostfixAdministrator=true;
	}
	if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage\n";}	
$sock=new sockets();

$tpl=new templates();
$f=array();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
if($EnableWebProxyStatsAppliance==1){$users->SQUID_INSTALLED=true;}
$OnlySMTP=false;
if($users->SMTP_APPLIANCE){$OnlySMTP=true;}
if($users->KASPERSKY_SMTP_APPLIANCE){$OnlySMTP=true;}
if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage\n";}

if(!$users->PROXYTINY_APPLIANCE){
	if($users->SQUID_INSTALLED){
		if($users->AsWebStatisticsAdministrator ){
			$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
			if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
			if($EnableRemoteStatisticsAppliance==0){
				if($SQUIDEnable==1){
					if(!$users->SQUID_REVERSE_APPLIANCE){
						$f[]=left_menus_format("CATEGORIZE_A_WEBSITE","32-categories-add.png","Loadjs('squid.visited.php?add-www=yes')","ADDWEBSITE_PROXY_EXPLAIN");
						$f[]=left_menus_format("test_categories","loupe-32.png",   "Loadjs('squid.category.tests.php')",     "squid_test_categories_explain");
					}
				}
			}
		}
	}
}
if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage\n";}

	if($users->POSTFIX_INSTALLED){
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		if($EnablePostfixMultiInstance==1){
			if($users->AsPostfixAdministrator){
				$f[]=left_menus_format("NEW_SMTP_INSTANCE","32-network-server-add.png","Loadjs('postfix.multiple.instances.wizard.php')");
			}
			
		}
		$EnableAmavisDaemon=trim($sock->GET_INFO("EnableAmavisDaemon",true));	
		if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}	
		
		if($users->AsAnAdministratorGeneric){
			$f[]=left_menus_format("check_recipients","check-32.png","only:Loadjs('postfix.debug.mx.php')");
			if($users->AMAVIS_INSTALLED){
				if($users->spamassassin_installed){
					if($EnableAmavisDaemon==1){
						$f[]=left_menus_format("SPAMASSASSIN_RULES","script-32.png","only:Loadjs('spamassassin.rules.php?byid=admin-start_page')");
					}
				}
			}
		}
		
	}
	

	if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage users->POWER_DNS_INSTALLED = $users->POWER_DNS_INSTALLED users->AsAnAdministratorGeneric=$users->AsAnAdministratorGeneric\n";}	
	if($users->AsAnAdministratorGeneric){
		if(!$OnlySMTP){$f[]=left_menus_format("explorer","explorer-32.png","only:Loadjs('tree.php');",'SHARE_FOLDER_TEXT');}
		
		if(!$OnlySMTP){$f[]=left_menus_format("ADD_COMPUTER","computer-32-add.png","only:YahooUser(1051,'domains.edit.user.php?userid=newcomputer$&ajaxmode=yes','New computer');","ADD_COMPUTER_TEXT");}
		if($users->POWER_DNS_INSTALLED){

			$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
			$EnablePDNS=$sock->GET_INFO("EnablePDNS");
			$PowerDNSMySQLEngine=$sock->GET_INFO("PowerDNSMySQLEngine");
			if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
			

			
			if(!is_numeric($PowerDNSMySQLEngine)){$PowerDNSMySQLEngine=1;}
			if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}	
			if($DisablePowerDnsManagement==0){
				if($EnablePDNS==1){
					$f[]=left_menus_format("new_dns_entry","filter-add-32.png","only:YahooWin5('550','pdns.mysql.php?item-id=0&t=$t','PowerDNS');","new_dns_entry");
				}	
			}
		}
	}
	
	

	if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage -> ".count($f)."\n";}	
	if(count($f)>0){
		$html="
		<hr>
		<div style='font-size:16px;color:white;margin-top:8px;text-transform:capitalize;'>{actions}:</div>
		<table style='width:99%' class=form><tbody>
		".@implode("\n", $f)."
		</tbody>
		</table;
		";
		
		if($GLOBALS["VERBOSE"]){echo __LINE__." AS ROOT={$GLOBALS["AS_ROOT"]}\n";}
		
		if($GLOBALS["AS_ROOT"]){
			if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage\n";}
			@mkdir(dirname($cachePage),0777,true);
			@file_put_contents($cachePage, $html);
			@chmod($cachePage, 0777);
			return;
		}		
		
		
		if($GLOBALS["VERBOSE"]){echo __LINE__." Saving $cachePage ->".count($f)."\n";}
		$html= $tpl->_ENGINE_parse_body($html);
		SET_CACHED(__FILE__, __FUNCTION__, null, $html);
		echo $html;	
		
	}
	
	
	
}

function squid_filters_infos(){
	$tpl=new templates();
	$sock=new sockets();
	$ligne2=array();
	if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
	if(!$users->SQUID_INSTALLED){return null;}
	if($users->SQUID_REVERSE_APPLIANCE){return null;}
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$squidRemostatisticsServer=$sock->GET_INFO("squidRemostatisticsServer");
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($squidRemostatisticsServer)){$squidRemostatisticsServer=0;}
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	
	if($squidRemostatisticsServer==1){return;}
	if($EnableSquidRemoteMySQL==1){return;}
	if($DisableArticaProxyStatistics==1){return;}
	
	if($SQUIDEnable==0){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:squid_filters_infos():: SQUIDEnable is not enabled... Aborting\n";}
		return;
	}	
	
	$sql="SELECT count(*) as tcount FROM `visited_sites` WHERE LENGTH(category)=0";	
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("visited_sites")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("visited_sites")){return;}
	
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error;}
	if($ligne["tcount"]==0){return null;}
	
	$websites_not_categorized=$tpl->_ENGINE_parse_body("{websites_not_categorized}");
	$ligne2[0]["icon"]="32-categories.png";
	$ligne2[0]["subject"]=$ligne["tcount"]." $websites_not_categorized";
	$ligne2[0]["js"]="Loadjs('squid.visited.php?onlyNot=yes');";
	
	return $ligne2;

}


function showInfos_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT subject FROM adminevents WHERE zmd5='{$_GET["showInfos"]}'","artica_events"));
	$title=$tpl->_ENGINE_parse_body($ligne["subject"]);
	$html="YahooWin('500','$page?showInfos-id={$_GET["showInfos"]}','$title')";
	echo $html;
}

function showInfos_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM adminevents WHERE zmd5='{$_GET["showInfos-id"]}'","artica_events"));
	$icon=$ligne["icon"];
	if(preg_match("#([0-9]+)#", $icon,$re)){
		$icon=str_replace($re[1], 64, $icon);
		if(!is_file("img/$icon")){$icon=$ligne["icon"];}
	}
	
	
	if($ligne["jslink"]<>null){
		$link="
		<tr>
			<td colspan=2 align='right'>
			<table style='width:100%'>
			<tbody>
				<tr>
					<td valign='middle' align='right' style='font-size:16px;font-weight:bold'>{goto}</td>
					<td width=1%>". imgtootltip("arrow-right-64.png","{goto}",$ligne["jslink"])."</td>
				</tr>
				</tbody>
			</table>
			</td>
		</tr>
		
		";
	}
	
	$title=$tpl->_ENGINE_parse_body($ligne["subject"]);	
	$html="<div style='font-size:18px;margin-bottom:20px'>$title</div>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td width=1% valign='top'><img src='img/$icon'></td>
		<td width=99%' valign='top'><div class=explain style='font-size:14px'>{$ligne["text"]}</div></td>
	</tr>$link
			<td colspan=2 align='left' style='font-size:16px;font-weight:bold'><a href=\"javascript:blur();\" OnClick=\"javascript:RemoveNotifAdmin()\"
			style='font-size:16px;font-weight:bold;text-decoration:underline'>{ihavereaditremove}</a>
			
			</td>
		</tr>
		
	</tbody>
	</table>
	<script>
	var x_RemoveNotifAdmin= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		CacheOffSilent();
		YahooWinHide();
		LoadAjax('admin-left-infos','admin.index.status-infos.php');
	}		
	
	
	
	function RemoveNotifAdmin(){
		var XHR = new XHRConnection();
		XHR.appendData('disable','{$_GET["showInfos-id"]}');
		XHR.sendAndLoad('$page', 'POST',x_RemoveNotifAdmin);	
		
	}		
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function disable(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE adminevents SET enabled=0 WHERE zmd5='{$_POST["disable"]}'","artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}
}


function wizards(){
	return;
	$sock=new sockets();
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	$WizardNetLeaveUnconfigured=$sock->GET_INFO("WizardNetLeaveUnconfigured");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	if(!is_numeric($WizardNetLeaveUnconfigured)){$WizardNetLeaveUnconfigured=0;}		
	$hostname=base64_decode($sock->getFrameWork("network.php?fqdn=yes"));
	writelogs("network.php?fqdn=yes -> hostname=\"$hostname\"",__FUNCTION__,__FILE__,__LINE__);
	$mustchangeHostname=false;
	if(preg_match("#Name or service not known#", $hostname)){$hostname=trim($sock->GET_INFO("myhostname"));}
	if(preg_match("#locahost\.localdomain#", $hostname)){$mustchangeHostname=true;}
	if(preg_match("#localhost\.localdomain#", $hostname)){$mustchangeHostname=true;}
	if(preg_match("#[A-Za-z]+\s+[A-Za-z]+#", $hostname)){$mustchangeHostname=true;}
	
	if(!$mustchangeHostname){if(preg_match("#locahost\.localdomain#", $users->hostname)){$mustchangeHostname=true;}}
	if(!$mustchangeHostname){if(strpos($hostname, ".")==0){$mustchangeHostname=true;}}
	
	if($mustchangeHostname){
	writelogs("hostname=\"$hostname\" mustchangeHostname=True",__FUNCTION__,__FILE__,__LINE__);
	}else{
		writelogs("hostname=\"$hostname\" mustchangeHostname=False",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if($mustchangeHostname){echo "<script>Loadjs('admin.chHostname.php');</script>";}	
	
	if($WizardNetLeaveUnconfigured==0){
		$user=new usersMenus();
		if($user->VPS_OPENVZ){$sock->SET_INFO("WizardNetLeaveUnconfigured", 1);}
		$WizardNetLeaveUnconfigured=1;
	}
	
	
	if($DisableNetworksManagement==0){
		 if($WizardNetLeaveUnconfigured==0){
			if(!$mustchangeHostname){
				$q=new mysql();
				if($q->TestingConnection()){
					$countDeNIC=$q->COUNT_ROWS("nics", "artica_backup");
					if($q->ok){
						if($countDeNIC==0){echo "<script>Loadjs('admin.chNICs.php');</script>";}
					}
				}
			}
		}
	}	
	
}